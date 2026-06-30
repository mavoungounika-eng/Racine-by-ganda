<?php

/**
 * Script de vérification complète du projet
 * Usage: php check-project.php
 */

echo "🔍 VÉRIFICATION COMPLÈTE DU PROJET RACINE-BACKEND\n";
echo str_repeat("=", 60) . "\n\n";

$errors = [];
$warnings = [];
$success = [];

// 1. Vérifier les fichiers essentiels
echo "📁 Vérification des fichiers essentiels...\n";
$essentialFiles = [
    'composer.json',
    'package.json',
    'vite.config.js',
    '.env.example',
    'routes/web.php',
    'bootstrap/app.php',
];

foreach ($essentialFiles as $file) {
    if (file_exists($file)) {
        $success[] = "✅ $file existe";
    } else {
        $errors[] = "❌ $file manquant";
    }
}

// 2. Vérifier les dossiers
echo "\n📂 Vérification des dossiers...\n";
$directories = [
    'app/Http/Controllers',
    'app/Models',
    'app/Http/Middleware',
    'resources/views',
    'database/migrations',
    'public',
];

foreach ($directories as $dir) {
    if (is_dir($dir)) {
        $success[] = "✅ $dir existe";
    } else {
        $errors[] = "❌ $dir manquant";
    }
}

// 3. Vérifier Vite
echo "\n⚡ Vérification Vite...\n";
if (file_exists('public/build/manifest.json')) {
    $success[] = "✅ Vite manifest.json existe";
} else {
    $warnings[] = "⚠️  Vite manifest.json non trouvé (fallback CDN activé)";
}

if (file_exists('node_modules')) {
    $success[] = "✅ node_modules existe";
} else {
    $warnings[] = "⚠️  node_modules non trouvé (exécutez: npm install)";
}

// 4. Vérifier les migrations
echo "\n🗄️  Vérification des migrations...\n";
if (is_dir('database/migrations')) {
    $migrations = glob('database/migrations/*.php');
    $success[] = "✅ " . count($migrations) . " migrations trouvées";
} else {
    $errors[] = "❌ Dossier migrations manquant";
}

// 5. Vérifier les routes
echo "\n🛣️  Vérification des routes...\n";
if (file_exists('routes/web.php')) {
    $content = file_get_contents('routes/web.php');
    if (strpos($content, 'admin.login') !== false) {
        $success[] = "✅ Route admin.login trouvée";
    } else {
        $errors[] = "❌ Route admin.login manquante";
    }
} else {
    $errors[] = "❌ routes/web.php manquant";
}

// 6. Vérifier les contrôleurs admin
echo "\n🎮 Vérification des contrôleurs admin...\n";
$adminControllers = [
    'app/Http/Controllers/Admin/AdminController.php',
    'app/Http/Controllers/Admin/AdminAuthController.php',
    'app/Http/Controllers/Admin/AdminDashboardController.php',
    'app/Http/Controllers/Admin/AdminCategoryController.php',
];

foreach ($adminControllers as $controller) {
    if (file_exists($controller)) {
        $success[] = "✅ " . basename($controller) . " existe";
    } else {
        $errors[] = "❌ $controller manquant";
    }
}

// 7. Vérifier les middlewares
echo "\n🛡️  Vérification des middlewares...\n";
$middlewares = [
    'app/Http/Middleware/AdminOnly.php',
    'app/Http/Middleware/SetLocale.php',
];

foreach ($middlewares as $middleware) {
    if (file_exists($middleware)) {
        $success[] = "✅ " . basename($middleware) . " existe";
    } else {
        $errors[] = "❌ $middleware manquant";
    }
}

// Résumé
echo "\n" . str_repeat("=", 60) . "\n";
echo "📊 RÉSUMÉ\n";
echo str_repeat("=", 60) . "\n\n";

if (count($success) > 0) {
    echo "✅ SUCCÈS (" . count($success) . ")\n";
    foreach ($success as $msg) {
        echo "   $msg\n";
    }
    echo "\n";
}

if (count($warnings) > 0) {
    echo "⚠️  AVERTISSEMENTS (" . count($warnings) . ")\n";
    foreach ($warnings as $msg) {
        echo "   $msg\n";
    }
    echo "\n";
}

if (count($errors) > 0) {
    echo "❌ ERREURS (" . count($errors) . ")\n";
    foreach ($errors as $msg) {
        echo "   $msg\n";
    }
    echo "\n";
}

// Conclusion
echo str_repeat("=", 60) . "\n";
if (count($errors) === 0) {
    echo "✅ PROJET EN BON ÉTAT !\n";
    if (count($warnings) > 0) {
        echo "⚠️  Quelques avertissements à vérifier\n";
    }
} else {
    echo "❌ ERREURS DÉTECTÉES - Action requise\n";
}
echo str_repeat("=", 60) . "\n";

