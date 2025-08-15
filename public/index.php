<?php
require __DIR__ . '/../vendor/autoload.php';

use Olivierguissard\EcoRide\Model\Car;
use Olivierguissard\EcoRide\Model\Trip;
use Olivierguissard\EcoRide\Model\Users;

require_once 'functions/auth.php';
startSession();
if (isAuthenticated()) {
    updateActivity();
}

require_once __DIR__ . '/../src/Helpers/helpers.php';

// Met à jour les statuts expirés avant de récupérer les données
Trip::updateExpiredTripsStatus();

// Récupération des statistiques dynamiques
$totalGreenTrips = Trip::countGreenTrips();
$totalUsers = Users::countAllUsers();
// Récupération des 3 prochains voyages
$upcomingTrips = Trip::findNext3UpcomingTrips();


$pageTitle = 'Accueil - EcoRide';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="assets/pictures/logoEcoRide.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/index.css">
    <title><?= $pageTitle ?? 'EcoRide - Covoiturage écologique' ?></title>
</head>
<body>
<!-- Navbar -->
<header>
    <nav class="navbar bg-body-tertiary">
        <div class="container" style="max-width: 900px;">
            <a class="navbar-brand" href="index.php">
                <img src="assets/pictures/logoEcoRide.png" alt="Logo EcoRide" width="60" class="d-inline-block align-text-center rounded">
            </a>
            <h2 class="fw-bold mb-1 text-success fs-2">EcoRide</h2>
            <?= displayInitialsButton(); ?>
        </div>
    </nav>
</header>
<!-- Main content -->
<main>
    <div class="container" style="max-width: 900px;">
        <!-- Picture section -->
        <section>
            <div class="pt-2"></div>
            <div class="position-relative text-white">
                <img src="assets/pictures/voitures.jpg" class="img-fluid w-100 rounded-3" alt="Réduisez votre empreinte carbone avec EcoRide">

                <!-- Overlay sombre -->
                <div class="position-absolute top-0 start-0 w-100 h-100 bg-dark opacity-50 rounded-3"></div>

                <!-- Texte centré et responsive -->
                <div class="position-absolute top-50 start-50 translate-middle text-center px-3">
                    <h1 class="fw-bold fs-3 fs-md-2 fs-lg-1">Voyagez vert, voyagez ensemble</h1>
                    <p class="lead fs-6 fs-md-5 fs-lg-4">Réduisez votre empreinte carbone avec EcoRide</p>
                </div>
            </div>
        </section>

        <!-- Formulaire de recherche de trajet -->
        <section class="mt-5">
            <h3 class="fw-bold mb-4"><i class="bi bi-geo-alt text-success me-2"></i>Trouvez votre trajet</h3>
            <form action="rechercher.php" method="get" id="formSearchDestination" class="p-4 bg-white rounded-4 shadow-sm">

               <!-- Départ -->
                <div class="input-container mb-3">
                    <div class="input-group bg-light rounded-3">
                        <span class="input-group-text bg-transparent border-0">
                           <i class="bi bi-geo-alt text-secondary"></i>
                        </span>
                        <input type="text" name="startCity" class="form-control border-0 bg-transparent"
                               autocomplete="off" id="searchStartCity" placeholder="Ville de départ">
                    </div>
                    <div id="startCitySuggestions" class="suggestion-box" style="display: none;"></div>
                </div>

                <!-- Destination -->
                <div class="input-container mb-3">
                    <div class="input-group bg-light rounded-3">
                        <span class="input-group-text bg-transparent border-0">
                            <i class="bi bi-pin-map text-secondary"></i>
                        </span>
                        <input type="text" name="endCity" class="form-control border-0 bg-transparent"
                               autocomplete="off" id="searchEndCity" placeholder="Destination">
                    </div>
                    <div id="endCitySuggestions" class="suggestion-box" style="display: none;"></div>
                </div>

                <!-- Date du voyage -->
                <div class="input-group mb-4 bg-light rounded-3">
                    <span class="input-group-text bg-transparent border-0">
                        <i class="bi bi-calendar-event text-secondary"></i>
                    </span>
                    <input type="date" name="departureDate" class="form-control border-0 bg-transparent" id="searchDate">
                </div>

                <!-- Bouton de recherche -->
                <div class="d-grid">
                    <button type="submit" class="btn btn-success d-flex justify-content-center align-items-center gap-2 rounded-3">
                        <i class="bi bi-search"></i> Rechercher
                    </button>
                </div>
            </form>
        </section>

        <!--Stats section-->
        <section>
            <div class="row row-cols-1 row-cols-md-3 g-4 pt-4">
                <div class="col">
                    <div class="card h-80">
                        <div class="card-body d-flex align-items-center flex-column">
                            <h5 class="card-title"><i class="bi bi-ev-front"></i></h5>
                            <p class="card-text">Trajets Verts</p>
                            <p class="card-text"><strong><?= $totalGreenTrips ?></strong></p>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card h-80">
                        <div class="card-body d-flex align-items-center flex-column">
                            <h5 class="card-title"><i class="bi bi-person"></i></h5>
                            <p class="card-text">Utilisateurs</p>
                            <p class="card-text"><strong><?= $totalUsers ?></strong></p>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card h-80">
                        <div class="card-body d-flex align-items-center flex-column">
                            <h5 class="card-title"><i class="bi bi-leaf"></i></h5>
                            <p class="card-text">CO2 économisé</p>
                            <p class="card-text"><strong>56 T</strong></p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!--Popular destinations-->
        <section>
            <div class="d-flex justify-content-between align-items-center pt-5 mb-3">
                <h3 class="fw-bold mb-4"><i class="bi bi-flag text-success me-2"></i>Destinations populaires</h3>
                <a href="rechercher.php" class="text-primary fw-semibold small">Voir tout</a>
            </div>

            <div class="row row-cols-1 row-cols-md-2 g-4">
                <div class="col">
                    <div class="card">
                        <img src="assets/pictures/paris.jpg" class="card-img-top" alt="Picture of Paris">
                        <div class="card-body">
                            <h5 class="card-title">Paris</h5>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card">
                        <img src="assets/pictures/lyon.jpg" class="card-img-top" alt="Picture of Lyon">
                        <div class="card-body">
                            <h5 class="card-title">Lyon</h5>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card">
                        <img src="assets/pictures/marseille.jpg" class="card-img-top" alt="Picture of Marseille">
                        <div class="card-body">
                            <h5 class="card-title">Marseille</h5>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card">
                        <img src="assets/pictures/bordeaux.jpg" class="card-img-top" alt="Picture of Bordeaux">
                        <div class="card-body">
                            <h5 class="card-title">Bordeaux</h5>
                        </div>
                    </div>
                </div>
            </div>
        </section>


        <!-- Section trajets pour les 3 prochains voyages -->
        <section>
            <div class="d-flex justify-content-between align-items-center pt-5 mb-3">
                <h3 class="fw-bold mb-4"><i class="bi bi-rocket-takeoff text-success me-2"></i>Prochains voyages</h3>
                <a href="rechercher.php" class="text-primary fw-semibold small">Voir tout</a>
            </div>

            <div class="container p-0">
                <?php if (empty($upcomingTrips)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-calendar-x fs-1"></i>
                        <p class="mt-3">Aucun voyage disponible pour le moment</p>
                        <a href="proposer.php" class="btn btn-success">Proposer un trajet</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($upcomingTrips as $trip): ?>
                        <div class="mb-3">
                            <?php
                            $driver = Users::findUser($trip->getDriverId());
                            $car = Car::findCarById($trip->getVehicleId());
                            // Préparer les données pour _card_trip_simple.php
                            $item = [
                                'trip' => $trip,
                                'booking' => null,
                                'role' => 'visiteur'
                            ];
                            include 'components/_card_trip_simple.php';
                            ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <!-- Eco Impact -->
        <section>
            <div class="bg-success-subtle rounded p-4 my-4">
                <!-- titre avec l'icône feuille -->
                <div class="d-flex align-items-center mb-3">
                    <div class="rounded-circle bg-success d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                        <i class="bi bi-leaf text-white fs-4"></i>
                    </div>
                    <h2 class="mb-0">Impact écologique</h2>
                </div>
                <div>
                    <p>En choisissant un trajet en covoiturage électrique, vous réduisez
                        votre empreinte carbone de près de 75% par rapport à un voyage en
                        voiture individuelle.</p>
                </div>

                <div class="bg-white rounded px-3 mx-2 p-1 mb-2">
                    <p class="mt-4 mb-1 fw-semibold">CO2 économisé ce mois : <span>4,8 tonnes</span></p>
                    <div class="progress rounded" role="progressbar" aria-label="CO2 économisé" aria-valuenow="55" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" style="width: 55%"></div>
                    </div>
                </div>
                <div class="bg-white rounded px-3 mx-2 p-1">
                    <p class="mt-4 mb-1 fw-semibold">Trajets verts ce mois : <span>325 trajets</span></p>
                    <div class="progress" role="progressbar" aria-label="Animated striped example" aria-valuenow="80" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" style="width: 80%"></div>
                    </div>
                </div>
            </div>
        </section>
    </div>
    <!-- Section légale -->
    <section class="mb-5">
        <div class="border-top pt-4">
            <div class="row g-3">
                <!-- Informations du webmaster -->
                <div class="col-12 col-md-4">
                    <h6 class="fw-bold text-muted mb-2">
                        <i class="bi bi-code-slash text-success me-1"></i>
                        Webmaster
                    </h6>
                    <p class="small mb-1">Olivier Guissard</p>
                    <p class="small text-muted mb-0">Développeur web</p>
                </div>

                <!-- Contact -->
                <div class="col-12 col-md-4">
                    <h6 class="fw-bold text-muted mb-2">
                        <i class="bi bi-envelope text-success me-1"></i>
                        Contact
                    </h6>
                    <a href="mailto:contact@ecoride.fr" class="text-decoration-none small">
                        contact@ecoride.fr
                    </a>
                </div>

                <!-- Mentions légales -->
                <div class="col-12 col-md-4">
                    <h6 class="fw-bold text-muted mb-2">
                        <i class="bi bi-file-text text-success me-1"></i>
                        Informations légales
                    </h6>
                    <div class="d-flex flex-column gap-1">
                        <a href="cgv.php" class="text-decoration-none small">Conditions Générales de Vente</a>
                        <a href="cgu.php" class="text-decoration-none small">Conditions Générales d'Utilisation</a>
                    </div>
                </div>
            </div>

            <!-- Copyright -->
            <div class="text-center mt-4 pt-3 border-top">
                <p class="small text-muted mb-0">
                    © <?= date('Y') ?> EcoRide - Tous droits réservés
                </p>
            </div>
        </div>
    </section>
    <div class="pb-5"></div>
</main>

<!-- Tab bar-->
<footer>
    <?php include 'footer.php'; ?>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
<script src="assets/js/index.js"></script>
</body>
</html>