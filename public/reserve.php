<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Olivierguissard\EcoRide\Model\Bookings;
use Olivierguissard\EcoRide\Model\Trip;

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

// Enregistre la réservation dans bookings
$booking = new Bookings([
    'trip_id'       => $tripId,
    'user_id'       => $userId,
    'seats_reserved'=> $seatsReserved,
    // status et created_at seront par défaut
]);
if ($booking->saveBookingToDatabase()) {
    $_SESSION['flash_success'] = 'Réservation enregistrée !';
} else {
    $_SESSION['flash_error'] = 'Impossible de réserver ce trajet.';
}

// Redirige vers rechercher
header('Location: rechercher.php');
exit;
