<?php

namespace App\Services\Pos;

use App\Models\PosSession;
use App\Models\PosCashMovement;
use App\Models\User;
use App\Events\PosSessionClosed;
use App\Traits\AuditsPosOperations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PosSessionService - Gestion lifecycle session de caisse
 * 
 * INVARIANTS:
 * - Une machine ne peut avoir qu'UNE session 'open' à la fois
 * - opening_cash obligatoire à l'ouverture
 * - closing_cash obligatoire pour passer à 'closed'
 * 
 * AUDIT:
 * - Toutes actions automatiquement loggées
 * - Traçabilité complète (Qui/Quoi/Quand/Où/Contexte)
 */
class PosSessionService
{
    use AuditsPosOperations;
    /**
     * Ouvrir une nouvelle session de caisse
     * 
     * @param string $machineId UUID de la machine
     * @param int $userId ID de l'utilisateur
     * @param float $openingCash Montant cash d'ouverture
     * @return PosSession
     * @throws \Exception Si session déjà ouverte
     */
    public function openSession(string $machineId, int $userId, float $openingCash): PosSession
    {
        return DB::transaction(function () use ($machineId, $userId, $openingCash) {
            // Vérifier qu'aucune session n'est déjà ouverte pour cette machine
            $existingSession = PosSession::forMachine($machineId)->open()->first();
            
            if ($existingSession) {
                throw new \Exception("Une session est déjà ouverte pour cette machine (ID: {$existingSession->id})");
            }

            // Créer la session
            $session = PosSession::create([
                'machine_id' => $machineId,
                'opened_by' => $userId,
                'opened_at' => now(),
                'opening_cash' => $openingCash,
                'status' => PosSession::STATUS_OPEN,
                'is_active' => 1, // ✅ Hard invariant: only one per user
            ]);

            // Créer le mouvement d'ouverture
            PosCashMovement::createOpening($session, $openingCash, $userId);

            Log::info('POS session opened', [
                'session_id' => $session->id,
                'machine_id' => $machineId,
                'opened_by' => $userId,
                'opening_cash' => $openingCash,
            ]);

            // 📋 AUDIT TRAIL
            self::logPosAction(\App\Models\PosOperatorAuditLog::ACTION_SESSION_OPEN, [
                'session_id' => $session->id,
                'opening_cash' => $openingCash,
                'notes' => 'Ouverture session via service',
            ], $userId);

            return $session;
        });
    }

    /**
     * Obtenir la session ouverte d'une machine
     * 
     * @param string $machineId
     * @return PosSession|null
     */
    public function getOpenSession(string $machineId): ?PosSession
    {
        return PosSession::forMachine($machineId)->open()->first();
    }

    /**
     * Requérir une session ouverte (ou throw)
     * 
     * @param string $machineId
     * @return PosSession
     * @throws \Exception Si pas de session ouverte
     */
    public function requireOpenSession(string $machineId): PosSession
    {
        $session = $this->getOpenSession($machineId);
        
        if (!$session) {
            throw new \Exception("Aucune session ouverte pour cette machine. Veuillez ouvrir une session.");
        }

        return $session;
    }

    /**
     * Préparer la clôture (calcul expected_cash)
     * 
     * @param PosSession $session
     * @return array Données de préparation
     */
    public function prepareClose(PosSession $session): array
    {
        if (!$session->canClose()) {
            throw new \Exception("Cette session ne peut pas être clôturée (status: {$session->status})");
        }

        // Calculer expected_cash
        $expectedCash = $session->calculateExpectedCash();
        
        // Mettre à jour le status
        $session->update([
            'status' => PosSession::STATUS_CLOSING,
            'expected_cash' => $expectedCash,
        ]);

        // Statistiques de la session
        $sales = $session->sales;
        $cashSales = $sales->where('payment_method', 'cash');
        $cardSales = $sales->where('payment_method', 'card');
        $mobileSales = $sales->where('payment_method', 'mobile_money');

        return [
            'session_id' => $session->id,
            'opening_cash' => $session->opening_cash,
            'expected_cash' => $expectedCash,
            'total_sales' => $sales->count(),
            'total_amount' => $sales->sum('total_amount'),
            'cash_sales' => [
                'count' => $cashSales->count(),
                'amount' => $cashSales->sum('total_amount'),
            ],
            'card_sales' => [
                'count' => $cardSales->count(),
                'amount' => $cardSales->sum('total_amount'),
            ],
            'mobile_sales' => [
                'count' => $mobileSales->count(),
                'amount' => $mobileSales->sum('total_amount'),
            ],
            'movements' => $session->cashMovements()->get(),
        ];
    }

    /**
     * Clôturer la session
     * 
     * CORRECTION 1: Verrou transactionnel anti-double-clôture
     * - lockForUpdate() empêche concurrence
     * - Vérification status AVANT modification
     * - Protection niveau DB (pas seulement applicatif)
     * 
     * @param PosSession $session
     * @param float $closingCash Montant cash compté
     * @param int $userId ID de l'utilisateur
     * @param string|null $notes Notes optionnelles
     * @return PosSession
     * @throws \DomainException Si session déjà fermée
     */
    public function closeSession(PosSession $session, float $closingCash, int $userId, ?string $notes = null): PosSession
    {
        return DB::transaction(function () use ($session, $closingCash, $userId, $notes) {
            // 🔒 VERROU TRANSACTIONNEL - Empêche double clôture
            $session = PosSession::where('id', $session->id)
                ->lockForUpdate()
                ->firstOrFail();

            // ✅ Vérification status AVANT toute modification
            if (!in_array($session->status, [PosSession::STATUS_OPEN, PosSession::STATUS_CLOSING], true)) {
                throw new \DomainException("Session already closed (status: {$session->status})");
            }

            // Calculer expected_cash si nécessaire
            $expectedCash = $session->expected_cash ?? $session->calculateExpectedCash();

            // Créer le mouvement de clôture
            PosCashMovement::createClosing($session, $closingCash, $userId);

            // Fermer la session (atomic update)
            $session->update([
                'status' => PosSession::STATUS_CLOSED,
                'is_active' => null, // ✅ Release hard invariant (allows next session)
                'closing_cash' => $closingCash,
                'expected_cash' => $expectedCash,
                'cash_difference' => $closingCash - $expectedCash,
                'closed_at' => now(),
                'closed_by' => $userId,
                'notes' => $notes,
            ]);

            // 🔍 DÉTECTER DISCREPANCY CASH
            $cashDifference = $closingCash - $expectedCash;
            $threshold = (float) config('pos.cash_discrepancy_threshold', 1.00);
            if (abs($cashDifference) >= $threshold) {
                event(new \App\Events\CashDiscrepancyDetected(
                    $session,
                    $expectedCash,
                    $closingCash,
                    $cashDifference
                ));
            }

            // Confirmer tous les paiements cash pending de cette session
            $this->confirmAllCashPayments($session, $userId);

            Log::info('POS session closed', [
                'session_id' => $session->id,
                'machine_id' => $session->machine_id,
                'closed_by' => $userId,
                'closing_cash' => $closingCash,
                'expected_cash' => $expectedCash,
                'cash_difference' => $session->cash_difference,
            ]);

            // 📋 AUDIT TRAIL
            self::logPosAction(\App\Models\PosOperatorAuditLog::ACTION_SESSION_CLOSE, [
                'session_id' => $session->id,
                'expected_cash' => $expectedCash,
                'closing_cash' => $closingCash,
                'difference' => $cashDifference,
                'notes' => $notes ?? 'Clôture session via service',
            ], $userId);

            // Dispatcher l'événement de clôture
            event(new PosSessionClosed($session));

            return $session->fresh();
        });
    }

    /**
     * Confirmer tous les paiements cash pending d'une session
     */
    protected function confirmAllCashPayments(PosSession $session, int $userId): void
    {
        $pendingCashPayments = $session->sales()
            ->with('payments')
            ->get()
            ->flatMap(fn($sale) => $sale->payments)
            ->filter(fn($payment) => $payment->isCash() && $payment->isPending());

        foreach ($pendingCashPayments as $payment) {
            $payment->confirm($userId, 'SESSION_CLOSE');
        }

        Log::info('All cash payments confirmed on session close', [
            'session_id' => $session->id,
            'payments_confirmed' => $pendingCashPayments->count(),
        ]);
    }

    /**
     * Créer un ajustement cash
     * 
     * @param PosSession $session
     * @param float $amount Montant (positif)
     * @param string $direction 'in' ou 'out'
     * @param string $reason Raison de l'ajustement
     * @param int $userId
     * @return PosCashMovement
     */
    public function createAdjustment(PosSession $session, float $amount, string $direction, string $reason, int $userId): PosCashMovement
    {
        if (!$session->isOpen()) {
            throw new \Exception("Impossible d'ajuster une session fermée");
        }

        return PosCashMovement::createAdjustment($session, $amount, $direction, $reason, $userId);
    }
}
