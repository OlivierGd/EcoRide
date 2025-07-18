<?php

require_once __DIR__ . '/../../vendor/autoload.php'; // adapte le chemin si besoin

use Olivierguissard\EcoRide\Config\Database;

// 1. Vérifie la présence de l’id
if (!isset($_GET['user_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID utilisateur manquant']);
    exit;
}

$userId = intval($_GET['user_id']);

try {
    $pdo = Database::getConnection();
    // 2. Requête pour récupérer les infos principales de l’utilisateur
    $sql = "SELECT user_id, firstname, lastname, email, role, status, created_at FROM users WHERE user_id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'Utilisateur non trouvé']);
        exit;
    }

    // Ajoute ici d’autres requêtes pour compléter (trajets, crédits, véhicules…)

    header('Content-Type: application/json');
    echo json_encode($user);
    exit;
} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur BDD : ' . $e->getMessage()]);
    exit;
}

