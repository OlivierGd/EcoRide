<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Olivierguissard\EcoRide\Model\Trip;

require_once 'functions/auth.php';
requireAuth();
updateActivity();

if (!isset($_POST['trip_id'])) {
    header('Location: historique.php?error=missing_trip');
    exit;
}

$tripId = (int)$_POST['trip_id'];
$userId = getUserId();
$trip = Trip::find($tripId);

if (!$trip) {
    header('Location: historique.php?error=not_found');
    exit;
}

// Vérifie que l'utilisateur est bien le chauffeur du trajet
if ($trip->getDriverId() !== $userId) {
    header('Location: historique.php?error=unauthorized');
    exit;
}

// Vérifie que le statut du trajet est bien "A venir"
if (strtolower($trip->getTripStatus()) !== 'a venir' && strtolower($trip->getTripStatus()) !== 'a_venir') {
    header('Location: historique.php?error=bad_status');
    exit;
}

// Mets à jour le statut du trajet
$trip->updateTripStatus('en_cours');

header('Location: historique.php?msg=trajet_demarre');
exit;

