<?php

require_once 'functions/auth.php';
startSession();
isAuthenticated();

require_once __DIR__ . '/../src/Helpers/helpers.php';
require_once __DIR__ . '/../vendor/autoload.php';;
require_once __DIR__ . '/../src/Model/SuggestTrip.php';


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
        <span class="navbar-text fw-medium">Résultats de la recherche</span>
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
                8 trajets trouvés
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
        <!-- Ride 1 -->
        <a href="#" class="card-link">
            <div class="card mb-3">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center mb-3">
                        <div class="me-3">
                            <img src="/api/placeholder/48/48" alt="Sophie M." class="rounded-circle profile-img">
                        </div>
                        <div>
                            <p class="fw-medium mb-0">Sophie M.</p>
                            <div class="driver-rating text-yellow">
                                <i class="ri-star-fill"></i>
                                <i class="ri-star-fill"></i>
                                <i class="ri-star-fill"></i>
                                <i class="ri-star-fill"></i>
                                <i class="ri-star-half-fill"></i>
                                <span class="text-secondary-emphasis ms-1">(4.5)</span>
                            </div>
                        </div>
                        <div class="ms-auto">
                            <span class="badge badge-electric rounded-pill">Électrique</span>
                        </div>
                    </div>

                    <div class="d-flex mb-3">
                        <div class="d-flex flex-column align-items-center me-3">
                            <div class="route-icon">
                                <i class="ri-map-pin-line"></i>
                            </div>
                            <div class="route-line"></div>
                            <div class="route-icon">
                                <i class="ri-flag-line"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="mb-3">
                                <p class="small fw-medium mb-0">Paris, Gare de Lyon</p>
                                <p class="small text-secondary-emphasis mb-0">Départ à 8h30</p>
                            </div>
                            <div>
                                <p class="small fw-medium mb-0">Lyon, Part-Dieu</p>
                                <p class="small text-secondary-emphasis mb-0">
                                    Arrivée à 12h15 • 3h45 de trajet
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                        <div class="d-flex align-items-center">
                            <div class="text-primary me-1">
                                <i class="ri-car-line"></i>
                            </div>
                            <p class="small text-secondary-emphasis mb-0">Tesla Model 3</p>
                        </div>
                        <div class="d-flex align-items-center">
                            <div class="text-secondary-emphasis me-1">
                                <i class="ri-user-line"></i>
                            </div>
                            <p class="small text-secondary-emphasis mb-0">2 places</p>
                        </div>
                        <div class="fw-medium fs-5 text-primary">15 €</div>
                    </div>
                </div>
            </div>
        </a>

        <!-- Ride 2 -->
        <a href="#" class="card-link">
            <div class="card mb-3">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center mb-3">
                        <div class="me-3">
                            <img src="/api/placeholder/48/48" alt="Thomas D." class="rounded-circle profile-img">
                        </div>
                        <div>
                            <p class="fw-medium mb-0">Thomas D.</p>
                            <div class="driver-rating text-yellow">
                                <i class="ri-star-fill"></i>
                                <i class="ri-star-fill"></i>
                                <i class="ri-star-fill"></i>
                                <i class="ri-star-fill"></i>
                                <i class="ri-star-line"></i>
                                <span class="text-secondary-emphasis ms-1">(4.0)</span>
                            </div>
                        </div>
                        <div class="ms-auto">
                            <span class="badge badge-standard rounded-pill">Standard</span>
                        </div>
                    </div>

                    <div class="d-flex mb-3">
                        <div class="d-flex flex-column align-items-center me-3">
                            <div class="route-icon">
                                <i class="ri-map-pin-line"></i>
                            </div>
                            <div class="route-line"></div>
                            <div class="route-icon">
                                <i class="ri-flag-line"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="mb-3">
                                <p class="small fw-medium mb-0">Paris, Porte de Vincennes</p>
                                <p class="small text-secondary-emphasis mb-0">Départ à 9h00</p>
                            </div>
                            <div>
                                <p class="small fw-medium mb-0">Lyon, Perrache</p>
                                <p class="small text-secondary-emphasis mb-0">
                                    Arrivée à 13h00 • 4h00 de trajet
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                        <div class="d-flex align-items-center">
                            <div class="text-primary me-1">
                                <i class="ri-car-line"></i>
                            </div>
                            <p class="small text-secondary-emphasis mb-0">Peugeot 308</p>
                        </div>
                        <div class="d-flex align-items-center">
                            <div class="text-secondary-emphasis me-1">
                                <i class="ri-user-line"></i>
                            </div>
                            <p class="small text-secondary-emphasis mb-0">3 places</p>
                        </div>
                        <div class="fw-medium fs-5 text-primary">12 €</div>
                    </div>
                </div>
            </div>
        </a>

        <!-- Ride 3 -->
        <a href="#" class="card-link">
            <div class="card mb-3">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center mb-3">
                        <div class="me-3">
                            <img src="/api/placeholder/48/48" alt="Laurent F." class="rounded-circle profile-img">
                        </div>
                        <div>
                            <p class="fw-medium mb-0">Laurent F.</p>
                            <div class="driver-rating text-yellow">
                                <i class="ri-star-fill"></i>
                                <i class="ri-star-fill"></i>
                                <i class="ri-star-fill"></i>
                                <i class="ri-star-fill"></i>
                                <i class="ri-star-fill"></i>
                                <span class="text-secondary-emphasis ms-1">(5.0)</span>
                            </div>
                        </div>
                        <div class="ms-auto">
                            <span class="badge badge-electric rounded-pill">Électrique</span>
                        </div>
                    </div>

                    <div class="d-flex mb-3">
                        <div class="d-flex flex-column align-items-center me-3">
                            <div class="route-icon">
                                <i class="ri-map-pin-line"></i>
                            </div>
                            <div class="route-line"></div>
                            <div class="route-icon">
                                <i class="ri-flag-line"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="mb-3">
                                <p class="small fw-medium mb-0">Paris, Montparnasse</p>
                                <p class="small text-secondary-emphasis mb-0">Départ à 10h15</p>
                            </div>
                            <div>
                                <p class="small fw-medium mb-0">Lyon, Bellecour</p>
                                <p class="small text-secondary-emphasis mb-0">
                                    Arrivée à 14h00 • 3h45 de trajet
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                        <div class="d-flex align-items-center">
                            <div class="text-primary me-1">
                                <i class="ri-car-line"></i>
                            </div>
                            <p class="small text-secondary-emphasis mb-0">Renault Zoe</p>
                        </div>
                        <div class="d-flex align-items-center">
                            <div class="text-secondary-emphasis me-1">
                                <i class="ri-user-line"></i>
                            </div>
                            <p class="small text-secondary-emphasis mb-0">1 place</p>
                        </div>
                        <div class="fw-medium fs-5 text-primary">18 €</div>
                    </div>
                </div>
            </div>
        </a>

        <!-- Ride 4 -->
        <a href="#" class="card-link">
            <div class="card mb-3">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center mb-3">
                        <div class="me-3">
                            <img src="/api/placeholder/48/48" alt="Marie L." class="rounded-circle profile-img">
                        </div>
                        <div>
                            <p class="fw-medium mb-0">Marie L.</p>
                            <div class="driver-rating text-yellow">
                                <i class="ri-star-fill"></i>
                                <i class="ri-star-fill"></i>
                                <i class="ri-star-fill"></i>
                                <i class="ri-star-fill"></i>
                                <i class="ri-star-half-fill"></i>
                                <span class="text-secondary-emphasis ms-1">(4.7)</span>
                            </div>
                        </div>
                        <div class="ms-auto">
                            <span class="badge badge-electric rounded-pill">Électrique</span>
                        </div>
                    </div>

                    <div class="d-flex mb-3">
                        <div class="d-flex flex-column align-items-center me-3">
                            <div class="route-icon">
                                <i class="ri-map-pin-line"></i>
                            </div>
                            <div class="route-line"></div>
                            <div class="route-icon">
                                <i class="ri-flag-line"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="mb-3">
                                <p class="small fw-medium mb-0">Paris, La Défense</p>
                                <p class="small text-secondary-emphasis mb-0">Départ à 11h30</p>
                            </div>
                            <div>
                                <p class="small fw-medium mb-0">Lyon, Villeurbanne</p>
                                <p class="small text-secondary-emphasis mb-0">
                                    Arrivée à 15h15 • 3h45 de trajet
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                        <div class="d-flex align-items-center">
                            <div class="text-primary me-1">
                                <i class="ri-car-line"></i>
                            </div>
                            <p class="small text-secondary-emphasis mb-0">Kia e-Niro</p>
                        </div>
                        <div class="d-flex align-items-center">
                            <div class="text-secondary-emphasis me-1">
                                <i class="ri-user-line"></i>
                            </div>
                            <p class="small text-secondary-emphasis mb-0">2 places</p>
                        </div>
                        <div class="fw-medium fs-5 text-primary">16 €</div>
                    </div>
                </div>
            </div>
        </a>

        <!-- Ride 5 -->
        <a href="#" class="card-link">
            <div class="card mb-3">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center mb-3">
                        <div class="me-3">
                            <img src="/api/placeholder/48/48" alt="Isabelle R." class="rounded-circle profile-img">
                        </div>
                        <div>
                            <p class="fw-medium mb-0">Isabelle R.</p>
                            <div class="driver-rating text-yellow">
                                <i class="ri-star-fill"></i>
                                <i class="ri-star-fill"></i>
                                <i class="ri-star-fill"></i>
                                <i class="ri-star-line"></i>
                                <i class="ri-star-line"></i>
                                <span class="text-secondary-emphasis ms-1">(3.2)</span>
                            </div>
                        </div>
                        <div class="ms-auto">
                            <span class="badge badge-standard rounded-pill">Standard</span>
                        </div>
                    </div>

                    <div class="d-flex mb-3">
                        <div class="d-flex flex-column align-items-center me-3">
                            <div class="route-icon">
                                <i class="ri-map-pin-line"></i>
                            </div>
                            <div class="route-line"></div>
                            <div class="route-icon">
                                <i class="ri-flag-line"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="mb-3">
                                <p class="small fw-medium mb-0">Paris, Bastille</p>
                                <p class="small text-secondary-emphasis mb-0">Départ à 12h45</p>
                            </div>
                            <div>
                                <p class="small fw-medium mb-0">Lyon, Croix-Rousse</p>
                                <p class="small text-secondary-emphasis mb-0">
                                    Arrivée à 16h45 • 4h00 de trajet
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                        <div class="d-flex align-items-center">
                            <div class="text-primary me-1">
                                <i class="ri-car-line"></i>
                            </div>
                            <p class="small text-secondary-emphasis mb-0">Citroën C3</p>
                        </div>
                        <div class="d-flex align-items-center">
                            <div class="text-secondary-emphasis me-1">
                                <i class="ri-user-line"></i>
                            </div>
                            <p class="small text-secondary-emphasis mb-0">3 places</p>
                        </div>
                        <div class="fw-medium fs-5 text-primary">10 €</div>
                    </div>
                </div>
            </div>
        </a>

        <!-- Load More Button -->
        <button class="btn btn-light w-100 mb-3 border rounded">
            Voir plus de trajets
        </button>
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
    <nav class="navbar fixed-bottom bg-body-tertiary px-4">
        <div class="container d-flex justify-content-around text-center" style="max-width: 900px">
            <a class="nav-item nav-link d-flex flex-column" href="/index.php">
                <i class="bi bi-house fs-4"></i>
                <span>Accueil</span>
            </a>
            <a class="nav-item nav-link d-flex flex-column" href="/rechercher.php">
                <i class="bi bi-zoom-in fs-4"></i>
                <span>Rechercher</span>
            </a>
            <a class="nav-item nav-link d-flex flex-column" href="/proposer.php">
                <i class="bi bi-ev-front fs-4"></i>
                <span>Proposer</span>
            </a>
            <a class="nav-item nav-link d-flex flex-column" href="/profil.php">
                <i class="bi bi-person fs-4"></i>
                <span>Profil</span>
            </a>
        </div>
    </nav>
</footer>

<script src="assets/js/script.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
</body>
</html>
