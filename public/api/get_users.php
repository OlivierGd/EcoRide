<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Olivierguissard\EcoRide\Config\Database;

// Vérifie si le paramètre existe avant de faire un trim lorsqu'on utilise query
if (!isset($_GET['query'])) {
    http_response_code(400);
    echo json_encode([]);
    exit;
}

$query = trim($_GET['query']);
try {
    $pdo = Database::getConnection();// Récupère objet de connexion à la BDD
    $sql = "SELECT user_id, firstname, lastname, email, role, status FROM users WHERE lastname ILIKE :query OR firstname ILIKE :query OR email ILIKE :query ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['query' => '%' . $query . '%']);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);// Récupère tous les résultats sous forme de tableau associatif
    header('Content-Type: application/json');// Spécifie que la réponse sera du JSON
    echo json_encode($users);// Transforme le tableau PHP en JSON pour le front-end JS
    exit;
} catch (\PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Une erreur est survenue lors de la récupération des données : '. $e->getMessage()]);
    exit;
}
