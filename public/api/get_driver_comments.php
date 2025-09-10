<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Olivierguissard\EcoRide\Config\Database;

require_once '../functions/auth.php';
startSession();

// Vérifier que l'utilisateur est connecté
if (!isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['error' => 'Connexion requise pour voir les commentaires']);
    exit;
}

$driverId = $_GET['driver_id'] ?? null;
if (!$driverId) {
    http_response_code(400);
    echo json_encode(['error' => 'ID chauffeur requis']);
    exit;
}

try {
    $pdo = Database::getConnection();

    // Requête pour les commentaires du chauffeur
    $sql = "SELECT r.rating, r.commentaire, r.date_review,
                   u.firstname AS passenger_name
            FROM reviews r
            JOIN trips t ON r.trip_id = t.trip_id  
            JOIN users u ON r.user_id = u.user_id
            WHERE t.driver_id = :driver_id 
            AND r.status_review = 'approved'
            ORDER BY r.date_review DESC
            LIMIT 20";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['driver_id' => $driverId]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($comments);

} catch (Exception $e) {
    error_log("Erreur get_driver_comments.php : " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erreur lors du chargement des commentaires']);
}
?>
