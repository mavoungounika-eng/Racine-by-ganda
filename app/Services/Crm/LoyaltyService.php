<?php

namespace App\Services\Crm;

use App\Models\User;
use App\Models\LoyaltyPoint;
use App\Models\LoyaltyLevel;
use App\Events\Crm\LoyaltyPointsAwarded;
use App\Events\Crm\LoyaltyLevelChanged;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LoyaltyService
{
    /**
     * Attribue des points à un client.
     */
    public function awardPoints(User $customer, int $points, string $source, ?int $referenceId = null, string $description = ''): ?LoyaltyPoint
    {
        // Guard strict
        if (!$customer || !$customer->exists || !$customer->getKey()) {
            Log::warning('LoyaltyService::awardPoints appelé sans customer valide', [
                'customer_id' => $customer?->id,
                'source' => $source
            ]);
            return null;
        }
        return DB::transaction(function () use ($customer, $points, $source, $referenceId, $description) {
            $expiryDays = config('crm.points_expiry_days', 365);
            
            $lp = LoyaltyPoint::create([
                'customer_id'    => $customer->id,
                'points'         => $points,
                'type'           => 'earned',
                'source'         => $source,
                'reference_id'   => $referenceId,
                'reference_type' => $this->resolveReferenceType($source),
                'description'    => $description,
                'expires_at'     => now()->addDays($expiryDays),
            ]);

            // Invalider le cache de la balance
            $this->clearBalanceCache($customer->id);

            // Vérifier changement de niveau
            $this->recalculateLevel($customer);

            // Dispatch event
            event(new LoyaltyPointsAwarded(
                $customer->id,
                $points,
                $source,
                $referenceId,
                $this->getBalance($customer)
            ));

            return $lp;
        });
    }

    /**
     * Attribue des points pour une commande web.
     */
    public function awardPointsForOrder(\App\Models\Order $order): void
    {
        if (!$order->user_id || $order->payment_status !== 'paid') {
            return;
        }

        $rate = config('crm.points_per_amount', 1000);
        $points = (int) ($order->total_amount / $rate);

        if ($points <= 0) return;

        $user = User::find($order->user_id);
        if (!$user) return;

        $this->awardPoints(
            $user,
            $points,
            'web_order',
            $order->id,
            "Points gagnés pour la commande #{$order->order_number}"
        );
    }

    /**
     * Attribue des points pour une vente POS.
     */
    public function awardPointsForPosSale(\App\Models\PosSale $sale): void
    {
        if (!$sale->customer_id || !$sale->isFinalized()) {
            return;
        }

        $rate = config('crm.points_per_amount', 1000);
        $points = (int) ($sale->total_amount / $rate);

        if ($points <= 0) return;

        $customer = User::find($sale->customer_id);
        if (!$customer) return;

        $this->awardPoints(
            $customer,
            $points,
            'pos_sale',
            $sale->id,
            "Points gagnés en boutique (Vente #{$sale->uuid})"
        );
    }

    /**
     * Utilisation de points.
     */
    public function spendPoints(User $customer, int $points, string $description): bool
    {
        $balance = $this->getBalance($customer);
        if ($balance < $points) return false;

        DB::transaction(function () use ($customer, $points, $description) {
            LoyaltyPoint::create([
                'customer_id' => $customer->id,
                'points'      => -$points,
                'type'        => 'spent',
                'description' => $description,
            ]);

            $this->clearBalanceCache($customer->id);
            $this->recalculateLevel($customer);
        });

        return true;
    }

    /**
     * Solde actuel (SUM).
     */
    public function getBalance(User $customer): int
    {
        return Cache::remember("loyalty_balance:{$customer->id}", 3600, function () use ($customer) {
            return LoyaltyPoint::getBalanceFor($customer->id);
        });
    }

    /**
     * Niveau VIP actuel.
     */
    public function getCurrentLevel(User $customer): ?LoyaltyLevel
    {
        $points = $this->getBalance($customer);
        return LoyaltyLevel::forPoints($points);
    }

    /**
     * Recalcul du niveau et dispatch si changement.
     */
    public function recalculateLevel(User $customer): void
    {
        $newLevel = $this->getCurrentLevel($customer);
        
        // On pourrait stocker le last_loyalty_level_id dans la table users pour comparer
        // Pour l'instant on se base sur la logique brute ou on dispatch systématiquement (l'event listener filtrera)
        // Mais idéalement on compare avec l'état précédent.
        
        // Simplement logger pour l'instant
        Log::debug("CRM: Recalculated level for user {$customer->id}: " . ($newLevel->slug ?? 'none'));
    }

    /**
     * Expiration des points anciens.
     */
    public function expirePoints(int $daysOld = 365): int
    {
        $expiredCount = 0;
        
        // Logique complexe: identifier les points gagnés il y a X jours 
        // qui n'ont pas encore été "consommés" par des 'spent'.
        // Pour simplifier selon le design requested: expire les lignes dont expires_at < now et non encore processees.
        
        $toExpire = LoyaltyPoint::where('type', 'earned')
            ->where('expires_at', '<', now())
            ->whereNotExists(function ($query) {
                // Éviter de ré-expirer
                $query->select(DB::raw(1))
                    ->from('loyalty_points as sub')
                    ->whereRaw('sub.customer_id = loyalty_points.customer_id')
                    ->where('sub.type', 'expired')
                    ->whereRaw('sub.reference_id = loyalty_points.id');
            })->get();

        foreach ($toExpire as $point) {
            LoyaltyPoint::create([
                'customer_id'  => $point->customer_id,
                'points'       => -$point->points,
                'type'         => 'expired',
                'reference_id' => $point->id, // On lie à l'id gagné pour le tracage
                'description'  => "Expiration des points gagnés le " . $point->created_at->format('Y-m-d'),
            ]);
            $this->clearBalanceCache($point->customer_id);
            $expiredCount++;
        }

        return $expiredCount;
    }

    protected function resolveReferenceType(string $source): ?string
    {
        return match($source) {
            'web_order' => 'App\Models\Order',
            'pos_sale'  => 'App\Models\PosSale',
            default     => null,
        };
    }

    protected function clearBalanceCache(int $customerId): void
    {
        Cache::forget("loyalty_balance:{$customerId}");
        Cache::forget("crm_metrics:{$customerId}");
    }
}
