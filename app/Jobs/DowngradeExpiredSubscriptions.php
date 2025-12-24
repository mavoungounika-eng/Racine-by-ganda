<?php

namespace App\Jobs;

use App\Models\CreatorSubscription;
use App\Models\CreatorPlan;
use App\Services\CreatorCapabilityService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job pour downgrader automatiquement les abonnements expirés vers FREE.
 * 
 * PHASE 9: Gestion automatique de l'expiration
 */
class DowngradeExpiredSubscriptions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected bool $dryRun;

    public function __construct(bool $dryRun = false)
    {
        $this->dryRun = $dryRun;
    }

    public function handle(CreatorCapabilityService $capabilityService): void
    {
        $freePlan = CreatorPlan::where('code', 'free')->first();

        if (!$freePlan) {
            Log::error('Plan FREE non trouvé pour le downgrade automatique');
            return;
        }

        // Trouver les abonnements expirés
        $expiredSubscriptions = CreatorSubscription::where('status', 'active')
            ->whereNotNull('ends_at')
            ->where('ends_at', '<', now())
            ->where('creator_plan_id', '!=', $freePlan->id) // Ne pas downgrader ceux qui sont déjà FREE
            ->with(['creator', 'plan'])
            ->get();

        $count = $expiredSubscriptions->count();

        if ($count === 0) {
            Log::info('Aucun abonnement expiré à downgrader');
            return;
        }

        Log::info("Downgrade de {$count} abonnement(s) expiré(s) vers FREE");

        foreach ($expiredSubscriptions as $subscription) {
            if ($this->dryRun) {
                Log::info("DRY-RUN: Downgrade de l'abonnement #{$subscription->id} (Créateur: {$subscription->creator_id})");
                continue;
            }

            // Downgrader vers FREE
            $subscription->update([
                'creator_plan_id' => $freePlan->id,
                'status' => 'active', // Reste actif mais avec plan FREE
                // Conserver les données (started_at, ends_at, etc.)
            ]);

            // Invalider le cache du créateur
            if ($subscription->creator) {
                $capabilityService->clearCache($subscription->creator);
            }

            Log::info("Abonnement #{$subscription->id} downgradé vers FREE (Créateur: {$subscription->creator_id})");
        }

        if (!$this->dryRun) {
            Log::info("✅ {$count} abonnement(s) downgradé(s) avec succès");
        }
    }
}
