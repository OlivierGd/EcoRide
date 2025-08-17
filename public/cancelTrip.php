<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Olivierguissard\EcoRide\Model\Bookings;

require_once 'functions/auth.php';

startSession();
requireAuth();
updateActivity();

$tripId = (int)($_POST['trip_id'] ?? 0);
$driverId = getUserId();

if (!$tripId || !$driverId) {
    $_SESSION['flash_error'] = "Données manquantes pour l'annulation du trajet.";
    header("Location: historique.php");
    exit;
}

try {
    $success = Bookings::cancelTripByDriver($tripId, $driverId);

    if ($success) {
        $_SESSION['flash_success'] = "Trajet annulé, passagers remboursés !";
    } else {
        $_SESSION['flash_error'] = "Erreur lors de l'annulation du trajet.";
    }
} catch (Exception $e) {
    $_SESSION['flash_error'] = "Erreur lors de l'annulation du trajet : " . $e->getMessage();
}

header("Location: historique.php");
exit;
?>