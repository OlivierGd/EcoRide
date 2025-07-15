<?php
error_log('TEST LOG --- ' . date('Y-m-d H:i:s'));
require_once __DIR__ . '/../vendor/autoload.php';
require_once 'functions/auth.php';
startSession();
isAuthenticated();
requireAuth();

use Olivierguissard\EcoRide\Model\Bookings;
use Olivierguissard\EcoRide\Model\Trip;
use Olivierguissard\EcoRide\Model\Users;

require_once __DIR__ . '/../src/Helpers/helperBookingStatus.php';

$userId = getUserId();
$allTrips = [];
// Trajets avec un role chauffeur
$driverTrips = Trip::findTripsByDriver($userId);
foreach ($driverTrips as $trip) {
    $allTrips[] = [
        'trip'      => $trip,
        'booking'   => null,
        'role'      => 'chauffeur',
    ];
}
// Trajets avec un role passager
$passengerTrips = Trip::findTripsAsPassenger($userId);
foreach ($passengerTrips as $trip) {
    $booking = \Olivierguissard\EcoRide\Model\Bookings::findByTripAndUser($trip->getTripId(), $userId);
    $allTrips[] = [
        'trip'      => $trip,
        'booking'   => $booking,
        'role'      => 'passager',
    ];
}
usort($allTrips, function ($a, $b) {
    return $a['trip']->getDepartureDate() <=> $b['trip']->getDepartureDate();
});

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
    <title><?php if (isset($pageTitle)) { echo $pageTitle; } else { echo 'EcoRide - Covoiturage écologique';} ?></title>
</head>

<body>
    <main>
        <div class="container my-4 main-content">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <button class="btn btn-link text-dark p-0"><a href="/profil.php"><i class="bi bi-chevron-left fs-5"></i></a></button>
                <h5 class="fw-bold m-0">Historique des trajets</h5>
                <button class="btn btn-link text-success p-0" data-bs-toggle="modal" data-bs-target="#"><i class="bi bi-filter fs-4"></i></button>
            </div>
            <?php if (empty($allTrips)): ?>
                <div class="alert alert-info">Aucun trajet trouvé.</div>
            <?php else: ?>
                <div class="d-flex flex-column gap-3">
                    <?php foreach ($allTrips as $item): ?>
                        <?php
                        /** @var Olivierguissard\EcoRide\Model\Trip $trip */
                        $trip = $item['trip'];
                        /** @var Olivierguissard\EcoRide\Model\Bookings|null $booking */
                        $booking = $item['booking'];
                        $role = $item['role'];
                        ?>
                        <div class="card shadow rounded-4 border-0">
                            <div class="card-body d-flex flex-column gap-2">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <!-- Badge rôle -->
                                    <span class="badge <?= $role === 'chauffeur' ? 'bg-primary' : 'bg-success' ?>">
                                        <?= ucfirst($role) ?>
                                    </span>
                                    <!-- Statut du trajet ou de la réservation -->
                                    <?php if ($role === 'chauffeur'): ?>
                                        <?php if ($trip->isTripUpcoming()): ?>
                                            <span class="badge bg-warning-subtle text-warning-emphasis">À venir</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-subtle text-secondary-emphasis">Terminé</span>
                                        <?php endif; ?>
                                    <?php elseif ($role === 'passager' && $booking): ?>
                                        <?= bookingStatusBadge($booking->getStatus()); ?>
                                    <?php endif; ?>
                                </div>
                                <!-- ... autres infos sur le trajet ... -->
                                <div>
                                    <strong><?= htmlspecialchars($trip->getStartCity()) ?></strong>
                                    <i class="bi bi-arrow-right mx-2"></i>
                                    <strong><?= htmlspecialchars($trip->getEndCity()) ?></strong>
                                </div>
                                <div class="text-muted small mb-2">
                                    <i class="bi bi-calendar-event me-1"></i>
                                    <?= $trip->getDepartureDateFr() ?> &nbsp;|&nbsp;
                                    <i class="bi bi-clock me-1"></i>
                                    <?= $trip->getDepartureTime() ?>
                                </div>
                                <div class="d-flex align-items-center gap-3 mb-2">
                                    <div>
                                        <i class="bi bi-cash-coin me-1"></i>
                                        <span class="fw-semibold">
                                    <?php if ($role === 'passager' && $booking): ?>
                                    <?php if ($booking->getStatus() === 'annule'): ?>
                                        <span class="text-danger">Annulé - Remboursé</span>
                                            <?php else: ?>
                                            <span><?= $trip->getPricePerPassenger() * $booking->getSeatsReserved() ?> crédits</span>
                                            <?php endif; ?>
                                    <?php else: ?>
                                        <span><?= $trip->getPricePerPassenger() ?> crédits</span>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    <div>
                                        <i class="bi bi-people-fill me-1"></i>
                                        <?php if ($role === 'passager' && $booking): ?>
                                            <?= $booking->getSeatsReserved() ?> place<?= $booking->getSeatsReserved() > 1 ? 's' : '' ?>
                                        <?php else: ?>
                                            <?= $trip->getAvailableSeats() ?> place<?= $trip->getAvailableSeats() > 1 ? 's' : '' ?>
                                            <?php if ($role === 'chauffeur'): ?>
                                                <span class="ms-2 small">(restantes: <?= $trip->getRemainingSeats() ?>)</span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <!-- Boutons -->
                                <div class="mt-3 d-flex gap-2 justify-content-end">
                                    <?php if ($role === 'chauffeur' && $trip->isTripUpcoming()): ?>
                                        <form action="cancelTrip.php" method="POST" class="m-0">
                                            <input type="hidden" name="trip_id" value="<?= $trip->getTripId() ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-4">
                                                <i class="bi bi-x-circle me-1"></i>Annuler
                                            </button>
                                        </form>
                                        <form action="startTrip.php" method="POST" class="m-0">
                                            <input type="hidden" name="trip_id" value="<?= $trip->getTripId() ?>">
                                            <button type="submit" class="btn btn-success btn-sm rounded-pill px-4">
                                                <i class="bi bi-flag me-1"></i>Démarrer
                                            </button>
                                        </form>
                                    <?php elseif ($role === 'passager' && $booking && $trip->isTripUpcoming() && $booking->getStatus() !== 'annule'): ?>
                                        <form action="cancelBooking.php" method="POST" class="m-0">
                                            <input type="hidden" name="booking_id" value="<?= (int)$booking->getBookingId() ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-4"
                                                    onclick="return confirm('Confirmer l\'annulation de votre réservation ?')">
                                                <i class="bi bi-x-circle me-1"></i>Annuler
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

<footer>
    <?php include 'footer.php'; ?>
</footer>

<script src="assets/js/profil.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
</body>
</html>

