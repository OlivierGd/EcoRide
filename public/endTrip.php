<?php
// endTrip.php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Model/Mailer.php';
require_once __DIR__ . '/../src/Helpers/helpers.php';
require_once 'functions/auth.php';

startSession();
requireAuth();
updateActivity();

use Olivierguissard\EcoRide\Model\Bookings;
use Olivierguissard\EcoRide\Model\Trip;
use Olivierguissard\EcoRide\Model\Users;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Méthode non autorisée');
}

$tripId = (int)($_POST['trip_id'] ?? 0);
$userId = getUserId();

// Vérifications de base
$trip = Trip::loadTripById($tripId);
if (!$trip) {
    die('Trajet introuvable');
}

if ($trip->getDriverId() !== $userId) {
    die('Vous n\'êtes pas le chauffeur de ce trajet');
}

if ($trip->getTripStatus() !== 'en_cours' && $trip->getTripStatus() !== 'a_venir') {
    die('Ce trajet ne peut pas être terminé');
}

try {
    $pdo = \Olivierguissard\EcoRide\Config\Database::getConnection();
    $pdo->beginTransaction();

    // 1. Marquer le trajet comme terminé
    $trip->updateTripStatus('termine');

    // 2. Récupérer tous les passagers pour leur envoyer l'email de validation
    $sqlPassengers = "SELECT b.booking_id, b.user_id, u.firstname, u.lastname, u.email 
                      FROM bookings b 
                      JOIN users u ON b.user_id = u.user_id
                      WHERE b.trip_id = ? 
                      AND b.status != 'annule'";
    $stmt = $pdo->prepare($sqlPassengers);
    $stmt->execute([$tripId]);
    $passengers = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    // 3. Envoyer les emails de demande de validation
    $mailer = new \Olivierguissard\EcoRide\Model\Mailer();

    foreach ($passengers as $passenger) {
        $validationUrl = "https://votre-domaine.com/validation.php?booking_id=" . $passenger['booking_id'];

        $subject = "EcoRide : Validez votre trajet {$trip->getStartCity()} → {$trip->getEndCity()}";
        $htmlContent = "
            <p>Bonjour <strong>{$passenger['firstname']} {$passenger['lastname']}</strong>,</p>
            <p>Votre trajet <b>{$trip->getStartCity()} → {$trip->getEndCity()}</b> du " . $trip->getDepartureDateFr() . " est terminé.</p>
            <p>Pour finaliser la transaction, merci de valider votre trajet en cliquant sur le lien ci-dessous :</p>
            <p><a href=\"{$validationUrl}\" style=\"background-color: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;\">Valider mon trajet</a></p>
            <p>Cette validation permettra au chauffeur de recevoir ses crédits.</p>
            <p>Merci pour votre confiance !</p>";

        try {
            $mailer->sendEmail(
                $passenger['email'],
                $passenger['firstname'],
                $subject,
                $htmlContent,
                strip_tags($htmlContent)
            );
        } catch (Exception $e) {
            error_log("Erreur envoi email validation à {$passenger['email']}: " . $e->getMessage());
        }
    }

    $pdo->commit();

    $_SESSION['flash_success'] = 'Trajet marqué comme terminé. Les passagers ont été notifiés pour validation.';
    header('Location: historique.php');
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['flash_error'] = $e->getMessage();
    header('Location: historique.php');
    exit;
}