<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Olivierguissard\EcoRide\Model\Bookings;

require_once 'functions/auth.php';
startSession();
requireAuth();
updateActivity();

$bookingId = (int)($_POST['booking_id'] ?? 0);
$userId = getUserId();

if (!$bookingId || !$userId) {
    $_SESSION['flash_error'] = "Données manquantes pour l'annulation.";
    header("Location: historique.php");
    exit;
}

try {
    $success = Bookings::cancelByPassenger($bookingId, $userId);
    
    if ($success) {
        $_SESSION['flash_success'] = "Réservation annulée avec succès !";
    } else {
        $_SESSION['flash_error'] = "Erreur lors de l'annulation de la réservation.";
    }
} catch (Exception $e) {
    $_SESSION['flash_error'] = "Erreur lors de l'annulation : " . $e->getMessage();
}

header("Location: historique.php");
exit;
?>