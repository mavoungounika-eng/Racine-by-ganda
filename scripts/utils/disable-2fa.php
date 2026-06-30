<?php

/**
 * Script pour désactiver la 2FA pour les comptes admin
 * Usage: php disable-2fa.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;

echo "🔓 Désactivation de la 2FA pour les comptes admin...\n\n";

$emails = ['admin@racine.com', 'dev@racine.com'];

foreach ($emails as $email) {
    $user = User::where('email', $email)->first();
    
    if ($user) {
        $user->two_factor_secret = null;
        $user->two_factor_recovery_codes = null;
        $user->two_factor_confirmed_at = null;
        $user->two_factor_required = false;
        $user->trusted_device_token = null;
        $user->trusted_device_expires_at = null;
        $user->save();
        
        echo "✅ 2FA désactivée pour : {$email}\n";
    } else {
        echo "⚠️  Compte non trouvé : {$email}\n";
    }
}

echo "\n✅ Terminé ! La 2FA est désactivée pour les comptes admin.\n";
echo "🔗 Accédez maintenant à : http://localhost:8000/admin/login\n";
echo "📝 Note : En environnement local, la 2FA est automatiquement bypassée.\n";

