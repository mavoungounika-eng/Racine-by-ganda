<?php
/**
 * Script de réinitialisation de la base de données
 * Exécutez: sudo php reset-db.php
 */

try {
    echo "🔄 Réinitialisation de la base de données...\n";
    
    // Config DB directe
    $dbName = 'racine';
    $dbUser = 'root';
    $dbPass = '';
    
    echo "📊 Base de données: $dbName\n";
    
    // Se connecter via socket Unix (nécessite root)
    $connection = new PDO(
        "mysql:unix_socket=/var/run/mysqld/mysqld.sock",
        $dbUser,
        $dbPass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Supprimer la base de données
    echo "🗑️  Suppression de la base de données existante...\n";
    $connection->exec("DROP DATABASE IF EXISTS `$dbName`");
    echo "✅ Base de données supprimée\n";
    
    // Créer la base de données
    echo "✨ Création de la nouvelle base de données...\n";
    $connection->exec("CREATE DATABASE `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ Base de données créée\n";
    
    $connection = null;
    
    // Exécuter les migrations via Artisan
    echo "\n🚀 Exécution des migrations et seeders...\n";
    $output = shell_exec('php artisan migrate:fresh --seed 2>&1');
    echo $output;
    
    echo "\n✅ Base de données réinitialisée avec succès !\n";
    
} catch (Exception $e) {
    echo "\n❌ Erreur: " . $e->getMessage() . "\n";
    exit(1);
}
