<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Olivierguissard\EcoRide\Model\Bookings;
use Olivierguissard\EcoRide\Model\Trip;
use Olivierguissard\EcoRide\Model\Users;

require_once 'functions/auth.php';
startSession();
requireAuth();
updateActivity();

require_once __DIR__ . '/../src/Helpers/helperBookingStatus.php';

$userId = getUserId();
$allTrips = [];

// Trajets avec un role chauffeur
$driverTrips = Trip::findTripsByDriver($userId);
foreach ($driverTrips as $trip) {
    // Vérifier et mettre à jour le statut si nécessaire
    $trip->checkAndUpdateStatusIfExpired();

    $allTrips[] = [
            'trip'      => $trip,
            'booking'   => null,
            'role'      => 'chauffeur',
    ];
}

// Trajets avec un role passager
$passengerTrips = Trip::findTripsAsPassenger($userId);
foreach ($passengerTrips as $trip) {
    // Vérifier et mettre à jour le statut si nécessaire
    $trip->checkAndUpdateStatusIfExpired();

    $booking = \Olivierguissard\EcoRide\Model\Bookings::findByTripAndUser($trip->getTripId(), $userId);
    $allTrips[] = [
            'trip'      => $trip,
            'booking'   => $booking,
            'role'      => 'passager',
    ];
}

// Trier les trajets selon l'ordre : En cours > À venir > Terminés > Annulés
$tripsEnCours = [];
$tripsAVenir = [];
$tripsPasses = [];
$tripsAnnules = [];

foreach ($allTrips as $item) {
    $trip = $item['trip'];
    $status = $trip->getTripStatus();

    // Déterminer le statut réel basé sur la date et le statut en base
    if ($status === 'annule') {
        $tripsAnnules[] = $item;
    } elseif ($status === 'en_cours') {
        $tripsEnCours[] = $item;
    } elseif ($status === 'a_venir' && $trip->isTripUpcoming()) {
        $tripsAVenir[] = $item;
    } else {
        // Trajet terminé (soit status = 'termine', soit date passée)
        $tripsPasses[] = $item;
    }
}

// Tri par date croissante pour en_cours et a_venir (plus proches en premier)
usort($tripsEnCours, fn($a, $b) => $a['trip']->getDepartureAt() <=> $b['trip']->getDepartureAt());
usort($tripsAVenir, fn($a, $b) => $a['trip']->getDepartureAt() <=> $b['trip']->getDepartureAt());

// Tri par date décroissante pour passés (plus récents en premier)
usort($tripsPasses, fn($a, $b) => $b['trip']->getDepartureAt() <=> $a['trip']->getDepartureAt());

// Récupère le tableau des messages
$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success']);
unset($_SESSION['flash_error']);

$pageTitle = 'Historique des trajets';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="assets/pictures/logoEcoRide.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/historique.css">
    <title><?= $pageTitle ?? 'EcoRide - Covoiturage écologique' ?></title>
</head>

<body>
<main>
    <div class="container my-4 main-content">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <button class="btn btn-link text-dark p-0">
                <a href="profil.php"><i class="bi bi-chevron-left fs-5"></i></a>
            </button>
            <h5 class="fw-bold m-0">Historique des trajets</h5>
            <button class="btn btn-link text-success p-0" data-bs-toggle="modal" data-bs-target="#">
                <i class="bi bi-filter fs-4"></i>
            </button>
        </div>

        <?php if (empty($allTrips)): ?>
            <div class="alert alert-info">Aucun trajet trouvé.</div>
        <?php else: ?>

            <!-- Messages flash -->
            <?php if ($flashSuccess): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($flashSuccess) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if ($flashError): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($flashError) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Messages de succès GET -->
            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'trajet_annule'): ?>
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Trajet annulé avec succès. Tous les passagers ont été remboursés.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'reservation_annulee'): ?>
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <i class="bi bi-info-circle-fill me-2"></i>Votre réservation a été annulée et vous avez été remboursé.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Trajets en cours -->
            <?php if (!empty($tripsEnCours)): ?>
                <h3 class="mt-4 mb-3 text-info text-uppercase fw-semibold">
                    <i class="bi bi-activity"></i> Trajets en cours
                </h3>
                <div class="d-flex flex-column gap-3">
                    <?php foreach ($tripsEnCours as $item): ?>
                        <?php include 'components/_card_trip.php'; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Trajets à venir -->
            <?php if (!empty($tripsAVenir)): ?>
                <h3 class="fw-bold mb-3 mt-4">
                    <i class="bi bi-calendar text-success me-2"></i>Trajets à venir
                </h3>
                <div class="d-flex flex-column gap-3">
                    <?php foreach ($tripsAVenir as $item): ?>
                        <?php include 'components/_card_trip.php'; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Trajets passés -->
            <?php if (!empty($tripsPasses)): ?>
                <h3 class="fw-bold mb-3 mt-4">
                    <i class="bi bi-archive text-secondary me-2"></i>Trajets passés
                </h3>
                <div class="d-flex flex-column gap-3">
                    <?php foreach ($tripsPasses as $item): ?>
                        <?php include 'components/_card_trip.php'; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Trajets annulés -->
            <?php if (!empty($tripsAnnules)): ?>
                <h3 class="fw-bold mb-3 mt-4">
                    <i class="bi bi-x-circle text-danger me-2"></i>Trajets annulés
                </h3>
                <div class="d-flex flex-column gap-3">
                    <?php foreach ($tripsAnnules as $item): ?>
                        <?php include 'components/_card_trip.php'; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <?php endif; ?>
    </div>
</main>

<footer>
    <?php include 'footer.php'; ?>
</footer>

<script src="assets/js/profil.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>