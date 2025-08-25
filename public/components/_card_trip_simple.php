<?php
/** @var array $item contient: 'trip', 'booking', 'role' */

// Récupération des informations du conducteur et du véhicule
use Olivierguissard\EcoRide\Model\Users;
use Olivierguissard\EcoRide\Model\Car;
$trip = $item['trip'];
$driver = Users::findUser($trip->getDriverId());
$car = Car::findCarById($trip->getVehicleId());
?>

<div class="card shadow-sm mb-3 rounded-4">
    <div class="card-body">
        <!-- Informations du conducteur -->
        <div class="d-flex align-items-center mb-2">
            <div class="fw-bold me-2">
                <?= htmlspecialchars($driver ? $driver->getFirstName() . ' ' . strtoupper(substr($driver->getLastName(), 0, 1)) . '.' : 'Conducteur') ?>
            </div>
            <?php if ($driver): ?>
                <div class="d-flex align-items-center small text-warning me-2">
                    <?php for($i = 1; $i <= 5; $i++): ?>
                        <?php if ($i <= floor($driver->getRanking())): ?>
                            <i class="bi bi-star-fill"></i>
                        <?php elseif ($i - 0.5 <= $driver->getRanking()): ?>
                            <i class="bi bi-star-half"></i>
                        <?php else: ?>
                            <i class="bi bi-star"></i>
                        <?php endif; ?>
                    <?php endfor; ?>
                    <span class="ms-1 text-secondary">(<?= number_format($driver->getRanking(), 1) ?>)</span>
                </div>
            <?php endif; ?>
            <?php if ($car): ?>
                <span class="badge rounded-pill bg-success ms-auto"><?= htmlspecialchars($car->getCarburant()) ?></span>
            <?php endif; ?>
        </div>

        <!-- Trajet -->
        <div class="mb-2">
            <i class="bi bi-geo-alt me-1"></i> <strong><?= htmlspecialchars($trip->getStartCity()) ?></strong>
            <span class="mx-2 text-muted">→</span>
            <i class="bi bi-pin-map me-1"></i> <strong><?= htmlspecialchars($trip->getEndCity()) ?></strong>
        </div>

        <!-- Date, heure, prix et places -->
        <div class="d-flex align-items-center text-secondary small mb-2">
            <div class="me-3">
                <i class="bi bi-calendar-event me-1"></i><?= $trip->getDepartureDateFr() ?>
            </div>
            <div class="me-3">
                <i class="bi bi-clock me-1"></i><?= $trip->getDepartureTime() ?>
            </div>
            <div class="me-3">
                <i class="bi bi-cash-coin me-1"></i><?= $trip->getPricePerPassenger() ?> crédits
            </div>
            <div>
                <i class="bi bi-people-fill me-1"></i><?= $trip->getRemainingSeats() ?> place<?= $trip->getRemainingSeats() > 1 ? 's' : '' ?>
            </div>
        </div>

        <!-- Bouton de réservation -->
        <div class="d-flex justify-content-end">
            <a href="rechercher.php?startCity=<?= urlencode($trip->getStartCity()) ?>&endCity=<?= urlencode($trip->getEndCity()) ?>" 
               class="btn btn-success btn-sm rounded-pill px-4">
                <i class="bi bi-search me-1"></i>Voir le trajet
            </a>
        </div>
    </div>
</div>
