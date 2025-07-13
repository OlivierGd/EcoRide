<?php

require_once __DIR__ . '/../vendor/autoload.php';;

use Olivierguissard\EcoRide\Model\Trip;
use Olivierguissard\EcoRide\Model\Users;
use Olivierguissard\EcoRide\Model\Car;

require_once 'functions/auth.php';
startSession();
isAuthenticated();

require_once __DIR__ . '/../src/Helpers/helpers.php';

// Récupère les trajets futurs
$trips = Trip::findTripsUpcoming();
$countTrip = count($trips);

$pageTitle = 'Rechercher un voyage';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="assets/pictures/logoEcoRide.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/rechercher.css">
    <title><?php if (isset($pageTitle)) { echo $pageTitle; } else { echo 'EcoRide - Covoiturage écologique';} ?></title>
</head>
<body>
<!-- Navbar -->
<nav class="navbar bg-white fixed-top shadow-sm">
    <div class="container px-3" style="max-width: 900px" >
        <a href="index.php" class="navbar-brand d-flex align-items-center">
            <img src="assets/pictures/logoEcoRide.png" alt="logo EcoRide" class="d-inline-block align-text-center rounded" width="60">
        </a>
        <span class="navbar-text fw-medium">Trouver un voyage</span>
        <?= displayInitialsButton(); ?>
    </div>
</nav>

<!-- Main Content -->
<main class="container px-3 py-2 mt-5 pt-5">
    <!-- Search Summary -->
    <section class="bg-primary-light rounded p-3 mb-3">
        <div class="d-flex align-items-center mb-2">
            <div class="flex-grow-1">
                <div class="d-flex align-items-center fs-6 fw-medium">
                    <span>Paris</span>
                    <div class="mx-2 text-secondary-emphasis">
                        <i class="ri-arrow-right-line"></i>
                    </div>
                    <span>Lyon</span>
                </div>
                <div class="small text-secondary mt-1">
                    <i class="ri-calendar-line me-1"></i>
                    <span id="currentDate">Lundi 5 mai 2025</span>
                </div>
            </div>
            <div class="bg-white rounded-pill px-3 py-1 small fw-medium text-primary">
                <?= $countTrip ?> trajet<?= ($countTrip > 1) ? 's' : '' ?> trouv<?= $countTrip > 1 ? 'és' : 'é' ?>
            </div>
        </div>
    </section>

    <!-- Filter Options -->
    <section class="mb-3">
        <div class="d-flex gap-2 filter-container pb-2">
            <button class="btn btn-primary rounded-pill py-1 px-3 filter-btn">
                <i class="ri-sort-asc-line me-1"></i>
                Prix croissant
            </button>
            <button class="btn btn-light rounded-pill py-1 px-3 border filter-btn">
                <i class="ri-time-line me-1"></i>
                Heure
            </button>
            <button class="btn btn-light rounded-pill py-1 px-3 border filter-btn">
                <i class="ri-star-line me-1"></i>
                Avis
            </button>
            <button class="btn btn-light rounded-pill py-1 px-3 border filter-btn">
                <i class="ri-charging-pile-line me-1"></i>
                Électrique
            </button>
            <button class="btn btn-light rounded-pill py-1 px-3 border filter-btn">
                <i class="ri-user-line me-1"></i>
                Places
            </button>
        </div>
    </section>

    <!-- Rides List -->
    <section class="mb-3">
        <!-- Ride card -->
        <?php foreach ($trips as $trip):
        // Le conducteur
        $driver = Users::findUser($trip->getDriverId());
        // Le véhicule utilisé
        $car = Car::find($trip->getVehicleId());
        // Calcul du nombre de places
        $remainingSeats = $trip->getRemainingSeats();
        // Variables
        $initialsBtn = displayInitialsButton($driver);
        $nameLabel = htmlspecialchars($driver->getFirstName() . ' ' . strtoupper(substr($driver->getLastName(),0,1)));
        $stars = renderStars($driver->getRanking());
        $ranking = htmlspecialchars($driver->getRanking());
        $energy = htmlspecialchars($car->carburant);
        $startCity = htmlspecialchars($trip->getStartCity());
        $endCity = htmlspecialchars($trip->getEndCity());
        $time = htmlspecialchars($trip->getDepartureTime());
        $date = htmlspecialchars($trip->getDepartureDateFr());
        $price = htmlspecialchars($trip->getPricePerPassenger());
        $vehicleLabel = htmlspecialchars($car->marque . ' ' . $car->modele);
        ?>
            <div class="container p-0">
                <!-- Carte trajet -->
                <div class="card shadow-sm mb-3 rounded-4">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <div class="fw-bold me-2"><?= $nameLabel ?></div>
                            <div class="d-flex align-items-center small text-warning me-2">
                                <?= $stars ?>
                                <span class="ms-1 text-secondary">(<?= $ranking?>)</span>
                            </div>
                            <span class="badge rounded-pill bg-success ms-auto"><?= $energy ?></span>
                        </div>
                        <div class="mb-2">
                            <i class="bi bi-geo-alt me-1"></i> <strong><?= $startCity ?></strong>
                            <span class="mx-2 text-muted">→</span>
                            <i class="bi bi-pin-map me-1"></i> <strong><?= $endCity ?></strong>
                        </div>
                        <div class="d-flex align-items-center text-secondary small mb-2">
                            <div class="me-3"><i class="bi bi-calendar-event me-1"></i><?= $date ?>, <?= $time ?></div>
                            <div class="me-3"><i class="bi bi-person-fill-add me-1"></i><?= $remainingSeats ?> place<?= $remainingSeats > 1 ? 's' : '' ?></div>
                            <div><i class="bi bi-currency-euro me-1"></i><?= $price ?> crédits</div>
                        </div>
                        <form method="post" action="reserve.php" style="display:inline">
                            <input type="hidden" name="trip_id" value="<?= htmlspecialchars($trip->getTripId()) ?>">
                            <input type="hidden" name="seats_reserved" value="1">
                            <button type="submit" class="btn btn-primary">Réserver</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <!-- Load More Button -->
        <?php if ($countTrip > 10): ?>
            <button class="btn btn-light w-100 mb-3 border rounded">Voir plus de trajets</button>
        <?php endif; ?>
    </section>

    <!-- Eco Impact Banner -->
    <section class="bg-primary-light rounded p-3 mb-3">
        <div class="d-flex align-items-start">
            <div class="bg-primary rounded-circle me-3 p-2 text-white" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                <i class="ri-leaf-line"></i>
            </div>
            <div>
                <h3 class="small fw-medium mb-1">
                    Économisez jusqu'à 3.2 kg de CO₂
                </h3>
                <p class="small text-secondary-emphasis">
                    En choisissant un trajet électrique pour votre voyage Paris-Lyon,
                    vous contribuez à réduire votre empreinte carbone.
                </p>
            </div>
        </div>
    </section>
</main>

<!-- Tab Bar -->
<footer>
    <?php require 'footer.php'; ?>
</footer>

<script src="assets/js/script.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
</body>
</html>
