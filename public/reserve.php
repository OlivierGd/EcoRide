<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Olivierguissard\EcoRide\Model\Bookings;
use Olivierguissard\EcoRide\Model\Trip;
use Olivierguissard\EcoRide\Model\Users;
use Olivierguissard\EcoRide\Model\Payment;
use Olivierguissard\EcoRide\Service\CreditService;

require_once 'functions/auth.php';
startSession();
requireAuth();
updateActivity();

require_once __DIR__ . '/../src/Helpers/helpers.php';

$tripId = (int)($_POST['trip_id'] ?? 0);
$seatsReserved = (int)($_POST['seats_reserved'] ?? 1);
$userId = getUserId();

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
        'message' => 'Crédits insuffisants pour cette réservation. <a href="paiements.php" class="btn btn-success btn-sm ms-2">Ajouter du crédit</a>'
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
        if (!$oldBooking->saveBookingToDatabase()) {
            throw new Exception('Impossible de réactiver cette réservation.');
        }
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
    }
    $bookingId = $booking->getBookingId();

    // Déduire les crédits du passager
    if (!CreditService::debitCredits($userId, $totalPrice, $bookingId)) {
        throw new Exception("Erreur lors du débit des crédits");
    }

    // Enregistrer le paiement dans la table payments
    Payment::create([
        'user_id' => $userId,
        'trip_id' => $tripId,
        'booking_id' => $bookingId,
        'type_transaction' => 'reservation',
        'montant' => $totalPrice,
        'description' => "Paiement réservation trajet #$tripId",
        'statut_transaction' => 'paye',
        'commission_plateforme' => 0
    ]);

    $pdo->commit();
    $_SESSION['flash_success'] = 'Réservation enregistrée avec succès !';

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['flash_error'] = 'Erreur lors de la réservation : ' . $e->getMessage();
}

header('Location: historique.php');
exit;
?>