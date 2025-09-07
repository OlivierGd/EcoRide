<?php
// init-db.php : appliqué par Fly avant chaque release
require_once __DIR__.'/vendor/autoload.php';

use Olivierguissard\EcoRide\Config\Database;

try {
    $pdo = Database::getConnection();
    $sql = file_get_contents(__DIR__ . '/sql/init_db.sql');
    $pdo->exec($sql);
    echo "✅ Schéma appliqué ou déjà à jour.\n";
} catch (Throwable $e) {
    fwrite(STDERR, "❌ ERREUR init DB : ".$e->getMessage()."\n");
    exit(1);             // Arrête le déploiement si erreur
}

