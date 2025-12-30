#!/usr/bin/env php
<?php
/**
 * Script de Migration Graphique RACINE BY GANDA
 * Remplace automatiquement les anciennes classes CSS par les nouvelles
 * 
 * Ancienne palette: Violet/Or
 * Nouvelle palette: Orange/Jaune
 */

$replacements = [
    // Classes de couleurs
    'bg-racine-violet' => 'bg-racine-orange',
    'text-racine-violet' => 'text-racine-orange',
    'bg-racine-gold' => 'bg-racine-yellow',
    'text-racine-gold' => 'text-racine-yellow',
    'border-racine-violet' => 'border-racine-orange',
    'border-racine-gold' => 'border-racine-yellow',
    
    // Badges
    'badge-racine-violet' => 'badge-racine-orange',
    'badge-racine-gold' => 'badge-racine-yellow',
    
    // Boutons (garder les noms génériques)
    // btn-racine-primary reste inchangé (utilise déjà la nouvelle palette)
    // btn-racine-secondary reste inchangé
    
    // Gradients
    'gradient-racine-primary' => 'gradient-racine-warm',
    'gradient-racine-gold' => 'gradient-racine-fire',
];

$directories = [
    __DIR__ . '/../resources/views/frontend',
    __DIR__ . '/../resources/views/auth',
    __DIR__ . '/../resources/views/cart',
    __DIR__ . '/../resources/views/checkout',
];

$filesProcessed = 0;
$replacementsMade = 0;

echo "🎨 RACINE BY GANDA - Migration Graphique\n";
echo "==========================================\n\n";

foreach ($directories as $directory) {
    if (!is_dir($directory)) {
        echo "⚠️  Répertoire non trouvé: $directory\n";
        continue;
    }
    
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory)
    );
    
    foreach ($files as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $filePath = $file->getPathname();
            $content = file_get_contents($filePath);
            $originalContent = $content;
            $fileReplacements = 0;
            
            foreach ($replacements as $old => $new) {
                $count = 0;
                $content = str_replace($old, $new, $content, $count);
                $fileReplacements += $count;
            }
            
            if ($fileReplacements > 0) {
                file_put_contents($filePath, $content);
                $filesProcessed++;
                $replacementsMade += $fileReplacements;
                echo "✅ " . basename($filePath) . " - $fileReplacements remplacements\n";
            }
        }
    }
}

echo "\n==========================================\n";
echo "📊 RÉSUMÉ\n";
echo "==========================================\n";
echo "Fichiers modifiés: $filesProcessed\n";
echo "Remplacements effectués: $replacementsMade\n";
echo "\n✨ Migration terminée avec succès!\n";
