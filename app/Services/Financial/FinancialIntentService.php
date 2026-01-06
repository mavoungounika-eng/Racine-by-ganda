<?php

namespace App\Services\Financial;

use App\Models\FinancialIntent;
use App\Models\Order;
use Modules\Accounting\Models\AccountingEntry;
use Modules\Accounting\Models\Journal;
use Modules\Accounting\Services\LedgerService;
use Modules\Accounting\Exceptions\LedgerException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * FinancialIntentService - Gestion des intentions financières
 * 
 * ARCHITECTURE:
 * Ce service est le point central pour créer et gérer les intents financiers.
 * Un event devient NOTIFICATIONNEL - seul l'intent est la source de vérité.
 * 
 * FLUX:
 * 1. Controller/Job → createPaymentIntent() → Intent(pending)
 * 2. Event dispatched → Listener → commitIntent() → Intent(committed) → AccountingEntry
 * 
 * GARANTIES:
 * - Idempotence via idempotency_key UNIQUE
 * - Traçabilité via status et timestamps
 * - Point d'irréversibilité clair (committed)
 */
class FinancialIntentService
{
    protected LedgerService $ledgerService;
    protected AccountingIdempotenceService $idempotenceService;

    public function __construct(LedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    /**
     * Créer un intent de paiement pour une commande
     * 
     * @param Order $order La commande payée
     * @return FinancialIntent L'intent créé ou existant
     */
    public function createPaymentIntent(Order $order): FinancialIntent
    {
        $idempotencyKey = FinancialIntent::generateIdempotencyKey('order', $order->id);

        // Vérifier si intent existe déjà (idempotence)
        $existingIntent = FinancialIntent::where('idempotency_key', $idempotencyKey)->first();
        
        if ($existingIntent) {
            Log::info('FinancialIntentService: Intent already exists', [
                'order_id' => $order->id,
                'intent_id' => $existingIntent->id,
                'status' => $existingIntent->status,
            ]);
            return $existingIntent;
        }

        // Créer nouvel intent
        $intent = FinancialIntent::create([
            'intent_type' => FinancialIntent::TYPE_PAYMENT,
            'reference_type' => 'order',
            'reference_id' => $order->id,
            'amount' => $order->total_amount,
            'currency' => 'XAF',
            'status' => FinancialIntent::STATUS_PENDING,
            'idempotency_key' => $idempotencyKey,
            'metadata' => [
                'payment_method' => $order->payment_method,
                'creator_id' => $order->creator_id,
                'is_marketplace' => (bool) $order->creator_id,
            ],
            'created_by' => auth()->id(),
        ]);

        Log::info('FinancialIntentService: Payment intent created', [
            'order_id' => $order->id,
            'intent_id' => $intent->id,
        ]);

        return $intent;
    }

    /**
     * Trouver un intent par référence
     */
    public function findByReference(string $type, int $id): ?FinancialIntent
    {
        return FinancialIntent::forReference($type, $id)->first();
    }

    /**
     * Trouver un intent par clé d'idempotence
     */
    public function findByIdempotencyKey(string $key): ?FinancialIntent
    {
        return FinancialIntent::where('idempotency_key', $key)->first();
    }

    /**
     * Commiter un intent - Créer l'écriture comptable
     * 
     * POINT D'IRRÉVERSIBILITÉ: Après cette méthode, l'intent est COMMITTED
     * et l'écriture comptable est créée.
     * 
     * @param FinancialIntent $intent L'intent à commiter
     * @param callable $entryCreator Fonction de création d'écriture
     * @return AccountingEntry L'écriture créée
     * @throws LedgerException Si l'intent n'est pas dans un état valide
     */
    public function commitIntent(FinancialIntent $intent, callable $entryCreator): AccountingEntry
    {
        // Guard: Vérifier état
        if ($intent->isCommitted()) {
            Log::info('FinancialIntentService: Intent already committed (idempotent)', [
                'intent_id' => $intent->id,
                'accounting_entry_id' => $intent->accounting_entry_id,
            ]);
            
            AccountingIdempotenceService::recordCollision(
                $intent->reference_type,
                $intent->reference_id,
                self::class,
                $intent->accounting_entry_id
            );
            
            return $intent->accountingEntry;
        }

        if (!$intent->canProcess()) {
            throw new LedgerException("Intent #{$intent->id} ne peut pas être traité (status: {$intent->status})");
        }

        return DB::transaction(function () use ($intent, $entryCreator) {
            // Verrouiller l'intent pour concurrence
            $intent = FinancialIntent::lockForUpdate()->find($intent->id);
            
            // Re-vérifier après lock
            if ($intent->isCommitted()) {
                return $intent->accountingEntry;
            }

            // Marquer en traitement
            $intent->markAsProcessing();

            try {
                // Créer l'écriture via le callback
                $entry = $entryCreator($intent, $this->ledgerService);

                // Marquer comme commis
                $intent->markAsCommitted($entry);

                Log::info('FinancialIntentService: Intent committed', [
                    'intent_id' => $intent->id,
                    'accounting_entry_id' => $entry->id,
                ]);

                return $entry;

            } catch (\Exception $e) {
                $intent->markAsFailed($e->getMessage());
                throw $e;
            }
        });
    }

    /**
     * Créer l'intent ET le commiter en une seule opération
     * (Pour migration progressive des flux existants)
     */
    public function createAndCommitPaymentIntent(Order $order, callable $entryCreator): AccountingEntry
    {
        $intent = $this->createPaymentIntent($order);
        return $this->commitIntent($intent, $entryCreator);
    }
}
