<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Olivierguissard\EcoRide\Config\Database;
use Olivierguissard\EcoRide\Model\Trip;
use Olivierguissard\EcoRide\Model\Users;

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
    $user = Users::getCurrentUser();
    if (!$user) {
        throw new Exception('Utilisateur non trouvé');
    }
    $currentUserRole = $user->getRole();

    // Vérifier les permissions (gestionnaire ou admin)
    if ($currentUserRole < 2) {
        throw new Exception('Permissions insuffisantes pour voir les détails des trajets');
    }

    // Récupérer l'ID du trajet
    $tripId = (int)($_GET['trip_id'] ?? 0);
    $trip = Trip::findTripsByTripId($tripId);
    if (!$trip) {
        throw new Exception('Trajet introuvable');
    }

    $pdo = Database::getConnection();

    // Données du conducteur
    $driverSql = "SELECT firstname, lastname, ranking, email FROM users WHERE user_id = ?";
    $driverStmt = $pdo->prepare($driverSql);
    $driverStmt->execute([$trip->getDriverId()]);
    $driverResult = $driverStmt->fetch(PDO::FETCH_ASSOC);

    if (!$driverResult) {
        throw new Exception('Conducteur introuvable');
    }

    // Données du véhicule
    $carSql = "SELECT marque, modele, type_carburant, plaque_immatriculation, nbr_places FROM vehicule WHERE id_vehicule = ?";
    $carStmt = $pdo->prepare($carSql);
    $carStmt->execute([$trip->getVehicleId()]);
    $carResult = $carStmt->fetch(PDO::FETCH_ASSOC);

    if (!$carResult) {
        throw new Exception('Véhicule du trajet introuvable');
    }

    // Organiser les données par sections
    $tripData = [
        'trip_id'           => $trip->getTripId(),
        'start_city'        => $trip->getStartCity(),
        'start_location'    => $trip->getStartLocation(),
        'end_city'          => $trip->getEndCity(),
        'end_location'      => $trip->getEndLocation(),
        'departure_at'      => $trip->getDepartureDateAndTime(),
        'estimated_duration' => (int)($tripDetailsResult['estimated_duration'] ?? 0),
        'price_per_passenger' => $trip->getPricePerPassenger(),
        'available_seats'   => $trip->getAvailableSeats(),
        'remaining_seats'   => $trip->getRemainingSeats(),
        'status'            => $trip->getTripStatus(),
        'comment'           => $trip->getComment(),
        'no_smoking'        => $trip->getNoSmoking(),
        'music_allowed'     => $trip->getMusicAllowed(),
        'discuss_allowed'   => $trip->getDiscussAllowed(),
    ];

    $driverData = [
        'firstname' => $driverResult['firstname'],
        'lastname'  => $driverResult['lastname'],
        'ranking'   => (float)$driverResult['ranking'],
        'email'     => $driverResult['email']
    ];

    $carData = [
        'marque'    => $carResult['marque'],
        'modele'    => $carResult['modele'],
        'carburant' => $carResult['type_carburant'],
        'immatriculation' => $carResult['plaque_immatriculation'],
        'places'    => (int)$carResult['nbr_places']
    ];

    // Log de l'accès
    $logMessage = "Détails trajet #{$tripId} consultés par " . $user->getFirstName() . " " . $user->getLastName() . " (ID: " . $user->getUserId() . ")";
    error_log($logMessage);

    echo json_encode([
        'success'   => true,
        'trip'      => $tripData,
        'driver'    => $driverData,
        'car'       => $carData
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
