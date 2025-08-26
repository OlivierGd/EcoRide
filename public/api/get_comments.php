<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Olivierguissard\EcoRide\Config\Database;
use Olivierguissard\EcoRide\Service\DateFilterService;

/**
 * Calcule les dates pour une période prédéfinie
 */
function calculatePeriodDates($period) {
    $today = new DateTime();

    switch($period) {
        case 'today':
            return [
                'start' => $today->format('Y-m-d'),
                'end' => $today->format('Y-m-d')
            ];

        case 'yesterday':
            $yesterday = clone $today;
            $yesterday->modify('-1 day');
            return [
                'start' => $yesterday->format('Y-m-d'),
                'end' => $yesterday->format('Y-m-d')
            ];

        case 'last_7_days':
            $week = clone $today;
            $week->modify('-7 days');
            return [
                'start' => $week->format('Y-m-d'),
                'end' => $today->format('Y-m-d')
            ];

        case 'last_30_days':
            $month = clone $today;
            $month->modify('-30 days');
            return [
                'start' => $month->format('Y-m-d'),
                'end' => $today->format('Y-m-d')
            ];

        case 'this_month':
            return [
                'start' => $today->format('Y-m-01'),
                'end' => $today->format('Y-m-d')
            ];

        case 'last_month':
            $lastMonth = clone $today;
            $lastMonth->modify('first day of last month');
            $endLastMonth = clone $today;
            $endLastMonth->modify('last day of last month');
            return [
                'start' => $lastMonth->format('Y-m-d'),
                'end' => $endLastMonth->format('Y-m-d')
            ];

        case 'this_year':
            return [
                'start' => $today->format('Y-01-01'),
                'end' => $today->format('Y-m-d')
            ];

        default:
            return null;
    }
}

// Gestion des filtres
$preset = $_GET['period_preset'] ?? '';
if ($preset) {
    $dates = calculatePeriodDates($preset);
    $date_min = $dates['start'] ?? null;
    $date_max = $dates['end'] ?? null;
} else {
    $date_min = $_GET['date_start'] ?? null;
    $date_max = $_GET['date_end'] ?? null;
}

// Autres filtres
$rating = isset($_GET['rating']) ? intval($_GET['rating']) : null;
$status = $_GET['comment_status'] ?? null;

try {
    $pdo = Database::getConnection();

    $sql = "
        SELECT r.review_id, r.trip_id, r.rating, r.commentaire, r.date_review, r.status_review,
               t.departure_at AS trip_date, t.start_city, t.end_city, t.start_location, t.end_location, 
               t.price_per_passenger, t.comment as trip_comment, t.no_smoking, t.music_allowed, 
               t.discuss_allowed, t.estimated_duration,
               v.firstname AS voyager_firstname, v.lastname AS voyager_lastname, v.ranking AS voyager_ranking,
               d.firstname AS driver_firstname, d.lastname AS driver_lastname, d.ranking AS driver_ranking,
               ve.marque, ve.modele, ve.type_carburant
        FROM reviews r
        JOIN trips t ON r.trip_id = t.trip_id
        JOIN users v ON r.user_id = v.user_id
        JOIN users d ON t.driver_id = d.user_id
        JOIN vehicule ve ON t.vehicle_id = ve.id_vehicule
        WHERE 1=1
    ";

    $params = [];

    // Application des filtres de date sur la date du commentaire
    if ($date_min) {
        $sql .= " AND r.date_review >= :date_min";
        $params['date_min'] = $date_min;
        error_log('Filtre date_min appliqué: ' . $date_min);
    }

    if ($date_max) {
        $sql .= " AND r.date_review <= :date_max";
        $params['date_max'] = $date_max . ' 23:59:59'; // Inclure toute la journée
        error_log('Filtre date_max appliqué: ' . $date_max);
    }

    // Filtre par rating
    if ($rating !== null) {
        if ($rating === 5) {
            $sql .= " AND r.rating = :rating";
            $params['rating'] = 5;
            error_log('Filtre rating appliqué: = 5');
        } else {
            $sql .= " AND r.rating >= :rating";
            $params['rating'] = $rating;
            error_log('Filtre rating appliqué: >= ' . $rating);
        }
    }

    // Filtre par statut
    if ($status) {
        $sql .= " AND r.status_review = :status";
        $params['status'] = $status;
        error_log('Filtre statut appliqué: ' . $status);
    }

    $sql .= " ORDER BY r.date_review DESC, t.departure_at DESC";

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