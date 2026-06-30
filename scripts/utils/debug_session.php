<?php

/**
 * SESSION DEBUG SCRIPT
 * 
 * Ce script permet de debugger la session après connexion
 * pour vérifier si user_context est bien présent
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "==========================================\n";
echo "SESSION DEBUG - USER CONTEXT\n";
echo "==========================================\n";
echo "\n";

// Simuler une requête avec une session
$sessionId = $argv[1] ?? null;

if (!$sessionId) {
    echo "❌ Usage: php debug_session.php <SESSION_ID>\n";
    echo "\n";
    echo "Pour obtenir le SESSION_ID:\n";
    echo "  1. Connectez-vous sur le site\n";
    echo "  2. Ouvrez les DevTools (F12)\n";
    echo "  3. Onglet 'Application' > 'Cookies'\n";
    echo "  4. Copiez la valeur du cookie 'laravel_session'\n";
    exit(1);
}

echo "Session ID: {$sessionId}\n";
echo "\n";

// Charger la session depuis le fichier
$sessionPath = storage_path('framework/sessions/' . $sessionId);

if (!file_exists($sessionPath)) {
    echo "❌ Fichier de session introuvable: {$sessionPath}\n";
    exit(1);
}

echo "✅ Fichier de session trouvé\n";
echo "\n";

$sessionData = file_get_contents($sessionPath);
$decoded = unserialize($sessionData);

echo "Contenu de la session:\n";
echo "--------------------\n";

if (isset($decoded['_token'])) {
    echo "  _token: {$decoded['_token']}\n";
}

if (isset($decoded['login_web_59ba36addc2b2f9401580f014c7f58ea4e30989d'])) {
    echo "  ✅ User authenticated (ID: {$decoded['login_web_59ba36addc2b2f9401580f014c7f58ea4e30989d']})\n";
} else {
    echo "  ❌ User NOT authenticated\n";
}

if (isset($decoded['user_context'])) {
    echo "  ✅ user_context found!\n";
    echo "\n";
    echo "  UserContext:\n";
    $context = $decoded['user_context'];
    foreach ($context as $key => $value) {
        if (is_array($value)) {
            $value = json_encode($value);
        }
        echo "    - {$key}: {$value}\n";
    }
} else {
    echo "  ❌ user_context NOT FOUND\n";
    echo "\n";
    echo "  ⚠️  This is the problem! The user will be logged out.\n";
}

echo "\n";
echo "Toutes les clés de session:\n";
echo "--------------------\n";
foreach (array_keys($decoded) as $key) {
    echo "  - {$key}\n";
}

echo "\n";
echo "==========================================\n";
