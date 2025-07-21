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
$userId = getUserId();

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
    $_SESSION['flash_error'] = [
        'trip_id' => $tripId,
        'message' => 'Crédits insuffisants pour cette réservation. <br>
                        <a href="/public/paiements.php" class="btn btn-success btn-sm ms-2">Ajouter du crédit à votre compte.</a>'
        ];
    header('Location: /rechercher.php');
    exit;
}

// Enregistrement PDO en une étape (Evite situation bancale si une étape échoue)
$pdo = \Olivierguissard\EcoRide\Config\Database::getConnection();
try {
    $pdo->beginTransaction();

    // Vérifie s'il existe une réservation annulée pour ce trip/user
    $oldBooking = Bookings::findAnnuleBookingByTripAndUser($tripId, $userId);
    if ($oldBooking && $oldBooking->getStatus() === 'annule') {
        // Réactiver la résa
        $oldBooking->setStatus('reserve');
        $oldBooking->setSeatsReserved($seatsReserved);
        $oldBooking->setCreatedAt(new DateTime('now'));
        error_log("DEBUG: oldBooking=" . print_r($oldBooking, true));
        if (!$oldBooking->saveBookingToDatabase()) {
            throw new Exception('Impossible de réactiver cette réservation.');
        }
        $bookingId = $oldBooking->getBookingId();
        $booking = $oldBooking;
    } else {
        // Sinon, on fait une nouvelle résa
        $booking = new Bookings([
            'trip_id' => $tripId,
            'user_id' => $userId,
            'seats_reserved' => $seatsReserved,
        ]);
        if (!$booking->saveBookingToDatabase()) {
            throw new Exception('Impossible de réserver ce trajet.');
        }
        $bookingId = $booking->getBookingId();
    }

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
    $_SESSION['flash_success'] = [
        'message' => 'Réservation enregistrée !',
        'trip_id' => $tripId
        ];

    } catch (Exception $e) {
    $pdo->rollBack(); // En cas d'erreur, on annule tout
    $_SESSION['flash_error'] = 'Erreur de l\'enregistrement de la réservation :' . $e->getMessage();
}

header('Location: historique.php');
exit;
