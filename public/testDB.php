<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Olivierguissard\EcoRide\Config\Database;

try {
    $pdo = Database::getConnection();
    $stmt = $pdo->query('SELECT NOW()');
    $row = $stmt->fetch();

    echo "<h2>✅ Connexion réussie à PostgreSQL !</h2>";
    echo "<p>Heure du serveur : <strong>" . $row['now'] . "</strong></p>";
} catch (Exception $e) {
    echo "<h2>❌ Erreur de connexion :</h2>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}

