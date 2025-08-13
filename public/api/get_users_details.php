<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Olivierguissard\EcoRide\Config\Database;

// Vérifier la présence de l'id (soit user_id, soit id)
$userId = null;
if (isset($_GET['user_id'])) {
    $userId = (int)$_GET['user_id'];
} elseif (isset($_GET['id'])) {
    $userId = (int)$_GET['id'];
}

if (!$userId) {
    http_response_code(400);
    echo json_encode(['error' => 'ID utilisateur manquant']);
    exit;
}

try {
    $pdo = Database::getConnection();

    // Requête pour récupérer les infos complètes de l'utilisateur
    $sql = "SELECT user_id, firstname, lastname, email, role, status, credits, ranking, created_at 
            FROM users WHERE user_id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'Utilisateur non trouvé']);
        exit;
    }

    header('Content-Type: application/json');
    echo json_encode($user);
    exit;
} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur BDD : ' . $e->getMessage()]);
    exit;
}