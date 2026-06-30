<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Stripe\Stripe;

class CheckStripeLiveConfig extends Command
{
    protected $signature = 'stripe:check-live';
    protected $description = 'Vérifie la configuration Stripe pour le passage en production';

    public function handle()
    {
        $this->info('🔍 Vérification de la configuration Stripe Live...');

        $enabled = config('services.stripe.enabled') ?? env('STRIPE_ENABLED');
        $key = config('services.stripe.key');
        $secret = config('services.stripe.secret');
        $webhook = config('services.stripe.webhook_secret');

        // 1. Enabled
        if (!$enabled) {
            $this->error('❌ STRIPE_ENABLED est false ou null.');
            return 1;
        }
        $this->info('✅ Stripe est activé.');

        // 2. Public Key Live
        if (!str_starts_with($key, 'pk_live_')) {
            $this->error('❌ STRIPE_KEY ne semble pas être une clé Live (doit commencer par pk_live_). Valeur actuelle : ' . substr($key, 0, 8) . '...');
            return 1;
        }
        $this->info('✅ STRIPE_KEY est une clé Live.');

        // 3. Secret Key Live
        if (!str_starts_with($secret, 'sk_live_')) {
            $this->error('❌ STRIPE_SECRET ne semble pas être une clé Live (doit commencer par sk_live_). Valeur actuelle : ' . substr($secret, 0, 8) . '...');
            return 1;
        }
        $this->info('✅ STRIPE_SECRET est une clé Live.');

        // 4. Webhook Secret
        if (empty($webhook) || !str_starts_with($webhook, 'whsec_')) {
            $this->warn('⚠️ STRIPE_WEBHOOK_SECRET semble manquant ou invalide. Les webhooks (et donc la validation des paiements) ne fonctionneront pas.');
        } else {
            $this->info('✅ STRIPE_WEBHOOK_SECRET est configuré.');
        }

        // 5. Currency
        $currency = config('services.stripe.currency');
        if ($currency !== 'XAF' && $currency !== 'EUR') {
            $this->warn("⚠️ La devise configurée est $currency. Assurez-vous que c'est intentionnel (Recommandé: XAF ou EUR).");
        } else {
            $this->info("✅ Devise configurée : $currency");
        }

        $this->info('--------------------------------------------------');
        $this->info('🎉 Configuration Stripe Live apparemment valide !');
        return 0;
    }
}
