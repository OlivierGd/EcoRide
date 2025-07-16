<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once 'functions/auth.php';
startSession();
isAuthenticated();
requireAuth();

use Olivierguissard\EcoRide\Model\Trip;
use Olivierguissard\EcoRide\Model\Bookings;
use Olivierguissard\EcoRide\Model\Users;
use Olivierguissard\EcoRide\Helpers\Mailer;


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

// Vérifie que le statut du trajet est bien "En cours"
if (strtolower($trip->getTripStatus()) !== 'en cours' && strtolower($trip->getTripStatus()) !== 'en_cours') {
    header('Location: historique.php?error=bad_status');
    exit;
}

// Mets à jour le statut du trajet à "A valider"
$trip->updateTripStatus('a_valider');

// 1. Récupérer les bbokings pour ce trajet
$bookings = Bookings::findBookingsByTripId($tripId);

// 2. Mets à jour le status des bookings
foreach ($bookings as $booking) {
    if ($booking->getStatus() !== 'annule') {
        $booking->updateStatusValidation('a_valider');
    }
}

// 3. Envoyer un email pour chaque passager qui n'a pas annulé
$mailer = new Mailer();
foreach ($bookings as $booking) {
    if ($booking->getStatus() !== 'annule') {
        $user = Users::findUser($booking->getUserId());
        if ($user) {
            // Génèrer un lien de validation
            $validationLink = 'http://localhost:8080/validation.php?booking_id=' . $booking->getBookingId();

            $subject = "Nous vous remercions de valider votre trajet sur EcoRide";
            $htmlContent = "
            <p>Bonjour, <strong>{$user->getFirstName()} {$user->getLastName()}</strong></p>
            <p>Nous vous remercions de valider votre trajet sur EcoRide.</p>
            <p>Pour valider votre trajet, cliquez sur le lien suivant : <a href='{$validationLink}'>{$validationLink}</a></p>
            <p>Donnez une note et laisser un commentaire à votre chauffeur.</p>";

            $mailer->sendEmail($user->getEmail(), $user->getFirstName(), $subject, $htmlContent, strip_tags($htmlContent));
        }
    }
}
header('Location: historique.php?msg=trajet_arrive_mail_envoye');
exit;
