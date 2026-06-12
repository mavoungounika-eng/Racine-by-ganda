<?php

namespace App\Services\Pos;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Services\CreatorCapabilityService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * PosConnectService — logique métier de l'API POS Connect (Electron / Sanctum).
 *
 * Contrairement au flux device JWT (PosSaleService + sessions de caisse),
 * ce flux léger crée directement des Orders (source = 'pos') pour un créateur
 * disposant d'un abonnement Signature actif (plan has_pos).
 *
 * Garanties :
 * - Décrément du stock et création de la commande dans la MÊME transaction
 *   (lockForUpdate sur les produits — pas de survente concurrente).
 * - Idempotence des ventes offline via orders.offline_id
 *   (contrainte unique creator_id + offline_id).
 */
class PosConnectService
{
    public const SIGNATURE_PLAN_CODE = 'signature';

    public const ACCESS_DENIED_MESSAGE = 'Un abonnement Signature actif est requis pour utiliser le POS.';

    public const SOURCE_POS = 'pos';

    public function __construct(
        protected CreatorCapabilityService $capabilities,
    ) {
    }

    /**
     * Le user a-t-il accès au POS Connect ?
     *
     * Logique retenue : abonnement actif dont le plan donne le POS
     * (has_pos = true — porté par le plan 'signature' en production).
     */
    public function hasPosAccess(User $user): bool
    {
        if (! $user->isCreator()) {
            return false;
        }

        $subscription = $this->capabilities->getActiveSubscription($user);

        if (! $subscription || ! $subscription->isActive()) {
            return false;
        }

        $plan = $subscription->plan;

        return $plan !== null
            && ($plan->has_pos || $plan->code === self::SIGNATURE_PLAN_CODE);
    }

    /**
     * Retrouver une vente POS déjà synchronisée via son offline_id.
     */
    public function findByOfflineId(User $creator, string $offlineId): ?Order
    {
        return Order::where('creator_id', $creator->id)
            ->where('offline_id', $offlineId)
            ->first();
    }

    /**
     * Créer une vente POS : commande + lignes + paiement + décrément du stock,
     * le tout dans une transaction unique avec verrouillage des produits.
     *
     * @param array{
     *     items: array<int, array{product_id: int|string, quantity: int|string, price?: float|string|null}>,
     *     payment_method: string,
     *     total?: float|string|null,
     *     currency?: string|null,
     *     monetbil_ref?: string|null,
     *     stripe_ref?: string|null,
     *     offline_id?: string|null
     * } $data
     *
     * @throws \DomainException produit invalide ou stock insuffisant (message utilisateur)
     */
    public function createOrder(User $creator, array $data): Order
    {
        $offlineId = isset($data['offline_id']) && $data['offline_id'] !== ''
            ? (string) $data['offline_id']
            : null;

        // Idempotence : la vente offline a déjà été synchronisée.
        if ($offlineId !== null && ($existing = $this->findByOfflineId($creator, $offlineId))) {
            return $existing;
        }

        try {
            return DB::transaction(function () use ($creator, $data, $offlineId) {
                // Fusionner les lignes dupliquées d'un même produit.
                $quantities = [];
                $unitPrices = [];

                foreach ($data['items'] as $item) {
                    $productId = (int) $item['product_id'];
                    $quantities[$productId] = ($quantities[$productId] ?? 0) + (int) $item['quantity'];

                    if (isset($item['price']) && $item['price'] !== '' && $item['price'] !== null) {
                        $unitPrices[$productId] = (float) $item['price'];
                    }
                }

                // Verrouiller les produits du créateur pour éviter toute survente concurrente.
                $products = Product::whereIn('id', array_keys($quantities))
                    ->where('user_id', $creator->id)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                $total = 0.0;

                foreach ($quantities as $productId => $quantity) {
                    $product = $products->get($productId);

                    if (! $product) {
                        throw new \DomainException(
                            "Produit #{$productId} introuvable ou n'appartenant pas à ce créateur."
                        );
                    }

                    if ((int) $product->stock < $quantity) {
                        throw new \DomainException(
                            "Stock insuffisant pour « {$product->title} » "
                            . "(disponible : {$product->stock}, demandé : {$quantity})."
                        );
                    }

                    $total += ($unitPrices[$productId] ?? (float) $product->price) * $quantity;
                }

                $total = round($total, 2);
                $currency = strtoupper((string) ($data['currency'] ?? 'XAF'));
                $paymentMethod = (string) $data['payment_method'];

                $order = Order::create([
                    'user_id'          => null,
                    'creator_id'       => $creator->id,
                    'status'           => 'completed',
                    'payment_status'   => 'paid',
                    'payment_method'   => $paymentMethod,
                    'source'           => self::SOURCE_POS,
                    'offline_id'       => $offlineId,
                    'total_amount'     => $total,
                    'currency'         => $currency,
                    'customer_name'    => 'Client POS',
                    'customer_email'   => $creator->email,
                    'customer_address' => 'Vente en boutique (POS)',
                ]);

                foreach ($quantities as $productId => $quantity) {
                    /** @var Product $product */
                    $product = $products->get($productId);

                    OrderItem::create([
                        'order_id'   => $order->id,
                        'product_id' => $product->id,
                        'quantity'   => $quantity,
                        'price'      => $unitPrices[$productId] ?? (float) $product->price,
                    ]);

                    // Décrément du stock dans la même transaction (produit verrouillé).
                    $product->decrement('stock', $quantity);
                }

                Payment::create([
                    'order_id'            => $order->id,
                    'provider'            => $paymentMethod,
                    'provider_payment_id' => $data['stripe_ref'] ?? $data['monetbil_ref'] ?? null,
                    'status'              => 'paid',
                    'amount'              => $total,
                    'currency'            => $currency,
                    'channel'             => self::SOURCE_POS,
                    'paid_at'             => now(),
                    'metadata'            => [
                        'source'     => self::SOURCE_POS,
                        'offline_id' => $offlineId,
                    ],
                ]);

                return $order->load('items');
            });
        } catch (QueryException $e) {
            // Course concurrente sur la contrainte unique (creator_id, offline_id) :
            // une autre requête a déjà inséré cette vente → renvoyer l'existante.
            if ($offlineId !== null && ($existing = $this->findByOfflineId($creator, $offlineId))) {
                return $existing;
            }

            throw $e;
        }
    }

    /**
     * Synchroniser un lot de ventes offline de façon idempotente.
     *
     * Chaque vente DOIT porter un offline_id : les doublons sont ignorés
     * (skipped), les ventes nouvelles sont créées (synced), les ventes
     * invalides (stock insuffisant…) sont rapportées dans failed sans
     * interrompre le lot.
     *
     * @param  array<int, array<string, mixed>>  $sales
     * @return array{synced_ids: array<int, string>, skipped_ids: array<int, string>, failed: array<int, array{offline_id: string, message: string}>}
     */
    public function syncOfflineSales(User $creator, array $sales): array
    {
        $syncedIds = [];
        $skippedIds = [];
        $failed = [];

        foreach ($sales as $sale) {
            $offlineId = (string) $sale['offline_id'];

            if (in_array($offlineId, $syncedIds, true)
                || in_array($offlineId, $skippedIds, true)
                || $this->findByOfflineId($creator, $offlineId)
            ) {
                $skippedIds[] = $offlineId;
                continue;
            }

            try {
                $this->createOrder($creator, $sale);
                $syncedIds[] = $offlineId;
            } catch (\DomainException $e) {
                $failed[] = [
                    'offline_id' => $offlineId,
                    'message'    => $e->getMessage(),
                ];
            }
        }

        return [
            'synced_ids'  => $syncedIds,
            'skipped_ids' => $skippedIds,
            'failed'      => $failed,
        ];
    }
}
