<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Olivierguissard\EcoRide\Model\Bookings;

require_once 'functions/auth.php';
requireAuth();
updateActivity();

$tripId = (int)($_POST['trip_id'] ?? 0);
$driverId = getUserId();

try {
    Bookings::cancelTripByDriver($tripId, $driverId);
    $_SESSION['flash_success'] = "Trajet annulé, passagers remboursés !";
} catch (Exception $e) {
    $_SESSION['flash_error'] = "Erreur lors de l'annulation du trajet : " . $e->getMessage();
}
header("Location: profil.php");
exit;
