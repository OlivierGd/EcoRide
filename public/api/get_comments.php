<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Olivierguissard\EcoRide\Config\Database;

// Préparation des filtres
$rating = isset($_GET['rating']) ? intval($_GET['rating']) : null;
$date_min = isset($_GET['date_min']) ? $_GET['date_min'] : null;
$date_max = isset($_GET['date_max']) ? $_GET['date_max'] : null;

try {
    $pdo = Database::getConnection();

    // Construire dynamiquement la requête selon les filtres
    $sql = "
        SELECT r.review_id, t.trip_id, t.departure_at AS trip_date, t.start_city, t.end_city,
               v.firstname AS voyager_firstname, v.lastname AS voyager_lastname,
               d.firstname AS driver_firstname, d.lastname AS driver_lastname,
               t.price_per_passenger, r.commentaire, r.rating, r.date_review
        FROM reviews r
        JOIN trips t ON r.trip_id = t.trip_id
        JOIN users v ON r.user_id = v.user_id      -- Le voyageur
        JOIN users d ON t.driver_id = d.user_id    -- Le chauffeur
        LEFT JOIN payments p ON p.trip_id = t.trip_id
        WHERE 1=1
    ";

    $params = [];

    if ($rating !== null) {
        $sql .= " AND r.rating = :rating";
        $params['rating'] = $rating;
    }
    if ($date_min) {
        $sql .= " AND t.departure_at >= :date_min";
        $params['date_min'] = $date_min;
    }
    if ($date_max) {
        $sql .= " AND t.departure_at <= :date_max";
        $params['date_max'] = $date_max;
    }

    $sql .= " ORDER BY t.departure_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($comments);
    exit;
} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur BDD : ' . $e->getMessage()]);
    exit;
}

