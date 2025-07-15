<?php
//debug
ini_set('log_errors', 1);
ini_set('error_log', '/tmp/php_debug.log'); // <- le chemin /tmp est universel sur Mac/Linux
error_log("=== TEST LOG === " . date('c'));

require_once __DIR__ . '/../vendor/autoload.php';

use Olivierguissard\EcoRide\Model\Bookings;

require_once 'functions/auth.php';
startSession();
requireAuth();

$bookingId = (int)($_POST['booking_id'] ?? 0);
$userId = getUserId();

// Debug
error_log('CANCEL BOOKING REQUEST: ' .  print_r($_REQUEST, true));

try {
    Bookings::cancelByPassenger($bookingId, $userId);
    $_SESSION['flash_success'] = "Réservation annulée et crédits remboursés !";
} catch (Exception $e) {
    $_SESSION['flash_error'] = "Erreur lors de l'annulation de la réservation : " . $e->getMessage();
}
header("Location: rechercher.php");
exit;

