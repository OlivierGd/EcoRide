<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Olivierguissard\EcoRide\Model\Bookings;
use Olivierguissard\EcoRide\Model\Trip;
use Olivierguissard\EcoRide\Model\Users;
use Olivierguissard\EcoRide\Model\Payment;

require_once 'functions/auth.php';
startSession();
requireAuth();

$tripId = (int)($_POST['trip_id'] ?? 0);
$seatsReserved = (int)($_POST['seats_reserved'] ?? 1);
$userId = $_SESSION['user_id'];

//debug
error_log('RESERVE REQUEST: ' .  print_r($_REQUEST, true));

// Vérifie qu'il reste des places
$trip = \Olivierguissard\EcoRide\Model\Trip::findTripsByTripId($tripId);
if (!$trip) {
    $_SESSION['flash_error'] = 'Trajet introuvable';
    header('Location: /rechercher.php');
    exit;
}

$remaining = $trip->getRemainingSeats();
if ($seatsReserved > $remaining) {
    $_SESSION['flash_error'] = 'Pas assez de places disponibles.';
    header('Location: rechercher.php');
    exit;
}

// Vérifie le solde suffisant de crédits
$pricePerPassenger = $trip->getPricePerPassenger();
$totalPrice = $pricePerPassenger * $seatsReserved;

// Récupère le passager depuis la classe Users
$passenger = Users::findUser($userId);
if (!$passenger) {
    $_SESSION['flash_error'] = 'Utilisateur non trouvé.';
    header('Location: /rechercher.php');
    exit;
}
// Vérification si le passager à un solde suffisant de crédit
if ($passenger->getCredits() < $totalPrice) {
    $_SESSION['flash_error'] = 'Crédits insuffisants pour cette réservation. Ajouté du crédit à votre compte.';
    header('Location: /profil.php');
    exit;
}

// Enregistrement PDO en une étape (Evite situation bancale si une étape échoue)
$pdo = \Olivierguissard\EcoRide\Config\Database::getConnection();
try {
    $pdo->beginTransaction();

    // Enregistre la réservation dans bookings
    $booking = new Bookings([
        'trip_id' => $tripId,
        'user_id' => $userId,
        'seats_reserved' => $seatsReserved,
    ]);
    if (!$booking->saveBookingToDatabase()) {
        throw new Exception('Impossible de réserver ce trajet.');
    }
    $bookingId = $booking->getBookingId();

    // Déduire les crédits du passager
    $sql = 'UPDATE users SET credits = credits - ? WHERE user_id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$totalPrice, $userId]);

    // Créer une entrée dans payments
    Payment::create([
        'user_id' => $userId,
        'trip_id' => $tripId,
        'booking_id' => $bookingId,
        'type_transaction' => 'reservation',
        'montant' => $totalPrice,
        'description' => 'Réservation trajet #' . $tripId,
        'statut_transaction' => 'reserve',
        'commission_plateforme' => 0
    ]);

    $pdo->commit();
    $_SESSION['flash_success'] = 'Réservation enregistrée !';

    } catch (Exception $e) {
    $pdo->rollBack(); // En cas d'erreur, on annule tout
    $_SESSION['flash_error'] = 'Erreur de l\'enregistrement de la réservation :' . $e->getMessage();
}

header('Location: rechercher.php');
exit;
