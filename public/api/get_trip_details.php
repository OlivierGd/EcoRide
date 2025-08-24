<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Olivierguissard\EcoRide\Config\Database;

require_once '../functions/auth.php';
startSession();
requireAuth();

header('Content-Type: application/json');

// Vérifier que c'est une requête GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

try {
    $currentUserRole = (int)($_SESSION['role'] ?? 0);

    // Vérifier les permissions (gestionnaire ou admin)
    if ($currentUserRole < 2) {
        throw new Exception('Permissions insuffisantes pour voir les détails des trajets');
    }

    // Récupérer l'ID du trajet
    $tripId = (int)($_GET['trip_id'] ?? 0);

    if ($tripId <= 0) {
        throw new Exception('ID de trajet invalide');
    }

    $pdo = Database::getConnection();

    // Requête pour récupérer toutes les informations du trajet, conducteur et véhicule
    $sql = "
        SELECT 
            -- Informations du trajet
            t.trip_id,
            t.start_city,
            t.start_location, 
            t.end_city,
            t.end_location,
            t.departure_at,
            t.estimated_duration,
            t.price_per_passenger,
            t.available_seats,
            t.remaining_seats,
            t.status,
            t.travel_preferences,
            
            -- Informations du conducteur
            u.firstname,
            u.lastname,
            u.ranking,
            u.email as driver_email,
            
            -- Informations du véhicule
            c.marque,
            c.modele,
            c.carburant,
            c.immatriculation,
            c.places as vehicle_seats
            
        FROM trips t
        INNER JOIN users u ON t.driver_id = u.user_id
        INNER JOIN cars c ON t.vehicle_id = c.car_id
        WHERE t.trip_id = ?
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$tripId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        throw new Exception('Trajet introuvable');
    }

    // Organiser les données par sections
    $tripData = [
        'trip_id' => (int)$result['trip_id'],
        'start_city' => $result['start_city'],
        'start_location' => $result['start_location'],
        'end_city' => $result['end_city'],
        'end_location' => $result['end_location'],
        'departure_at' => $result['departure_at'],
        'estimated_duration' => (int)$result['estimated_duration'],
        'price_per_passenger' => (int)$result['price_per_passenger'],
        'available_seats' => (int)$result['available_seats'],
        'remaining_seats' => (int)$result['remaining_seats'],
        'status' => $result['status'],
        'travel_preferences' => $result['travel_preferences']
    ];

    $driverData = [
        'firstname' => $result['firstname'],
        'lastname' => $result['lastname'],
        'ranking' => (float)$result['ranking'],
        'email' => $result['driver_email']
    ];

    $carData = [
        'marque' => $result['marque'],
        'modele' => $result['modele'],
        'carburant' => $result['carburant'],
        'immatriculation' => $result['immatriculation'],
        'places' => (int)$result['vehicle_seats']
    ];

    // Log de l'accès
    $logMessage = "Détails trajet #{$tripId} consultés par " . $_SESSION['firstName'] . " " . $_SESSION['lastName'] . " (ID: " . $_SESSION['user_id'] . ")";
    error_log($logMessage);

    echo json_encode([
        'success' => true,
        'trip' => $tripData,
        'driver' => $driverData,
        'car' => $carData
    ]);

} catch (Exception $e) {
    error_log("Erreur get_trip_details.php : " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

?>
