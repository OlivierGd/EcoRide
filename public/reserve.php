<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Olivierguissard\EcoRide\Model\Bookings;
use Olivierguissard\EcoRide\Model\Trip;
use Olivierguissard\EcoRide\Model\Users;
use Olivierguissard\EcoRide\Service\PaymentService;

require_once 'functions/auth.php';
startSession();
requireAuth();
updateActivity();

require_once __DIR__ . '/../src/Helpers/helpers.php';

$tripId = (int)($_POST['trip_id'] ?? 0);
$seatsReserved = (int)($_POST['seats_reserved'] ?? 1);
$userId = getUserId();

// Met à jour les status expirés avant traitement
Trip::updateExpiredTripsStatus();

// Vérifie qu'il reste des places
$trip = \Olivierguissard\EcoRide\Model\Trip::findTripsByTripId($tripId);
if (!$trip) {
    $_SESSION['flash_error'] = 'Trajet introuvable';
    header('Location: /rechercher.php');
    exit;
}

if (!$trip->canAcceptBookings()) {
    $message = 'Ce trajet n\'est plus disponible à la réservation';

    // Message plus spécifique selon la raison
    if ($trip->isPastTrip()) {
        $message = 'Ce trajet est déjà passé ou en cours.';
    } elseif ($trip->getTripStatus() === 'annule') {
        $message = 'Ce trajet a été annulé.';
    } elseif ($trip->getTripStatus() === 'en_cours') {
        $message = 'Ce trajet est déjà en cours.';
    } elseif ($trip->getTripStatus() === 'termine') {
        $message = 'Ce trajet est terminé.';
    } elseif ($trip->getRemainingSeats() <= 0) {
        $message = 'Il n\'y a plus de places disponibles pour ce trajet.';
    }

    $_SESSION['flash_error'] = $message;
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

// Vérification crédits suffisants
if ($passenger->getCredits() < $totalPrice) {
    $_SESSION['flash_error'] = [
        'type' => 'insufficient_credits',
        'message' => 'Pas assez de crédits pour ce trajet. Créditez votre compte.',
        'needed' => $totalPrice - $passenger->getCredits(),
        'trip_id' => $tripId
    ];
    header('Location: rechercher.php');
    exit;
}

try {
    // Vérifier s'il existe une réservation annulée pour ce trip/user
    $booking = Bookings::findAnnuleBookingByTripAndUser($tripId, $userId);
    if ($booking && $booking->getStatus() === 'annule') {
        // Réactiver la résa
        $booking->setStatus('reserve');
        $booking->setSeatsReserved($seatsReserved);
        $booking->setCreatedAt(new DateTime('now'));
        if (!$booking->saveBookingToDatabase()) {
            throw new Exception('Impossible de réactiver cette réservation.');
        }
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
    }
    $bookingId = $booking->getBookingId();

    // PaymentService gère le paiement
    if (!PaymentService::debitForReservation($userId, $totalPrice, $tripId, $bookingId)) {
        throw new Exception("Erreur lors du débit des crédits");
    }

    $_SESSION['flash_success'] = 'Réservation enregistrée avec succès !';

} catch (Exception $e) {
    $_SESSION['flash_error'] = 'Erreur lors de la réservation : ' . $e->getMessage();
}

header('Location: historique.php');
exit;
?>