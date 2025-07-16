<?php

require __DIR__ . '/../vendor/autoload.php';

require_once 'functions/auth.php';
startSession();
isAuthenticated();

require_once __DIR__ . '/../src/Helpers/helpers.php';


// chemin du dossier data
$dataDir = __DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'data';
$file = $dataDir . DIRECTORY_SEPARATOR . date('Y-m-d') . '.txt';
$searchForTrip = [];
if(isset($_GET['villeDepart']) && isset($_GET['villeArrivee']) && isset($_GET['dateVoyage'])) {
    $searchForTrip = [
        'villeDepart' => $_GET['villeDepart'],
        'villeArrivee' => $_GET['villeArrivee'],
        'dateVoyage' => $_GET['dateVoyage']
    ];
}
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
    <title><?php if (isset($pageTitle)) { echo $pageTitle; } else { echo 'EcoRide - Covoiturage écologique';} ?></title>
</head>
<body>
<!-- Navbar -->
<header>
    <nav class="navbar bg-white fixed-top shadow-sm">
        <div class="container px-3" style="max-width: 900px">
            <a href="index.php" class="navbar-brand d-flex align-items-center">
                <img src="assets/pictures/logoEcoRide.png" alt="logo EcoRide" class="d-inline-block align-text-center rounded" width="60">

            </a>
            <h1>EcoRide</h1>
            <div><?= displayInitialsButton(); ?></div>
        </div>
    </nav>
    <div class="<?= (isset($erreur) || ini_get('display_errors')) ? 'has-error' : '' ?>">
</header>
<!-- Main content -->
<main>
    <div class="container" style="max-width: 900px;">
        <!-- Picture section -->
        <section>
            <div style="height: 5rem"></div>
            <div class="pt-5"></div>
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
            <h2 class="fw-bold mb-4">Trouvez votre trajet</h2>
            <form action="index.php" method="get" id="formSearchDestination" class="p-4 bg-white rounded-4 shadow-sm">

               <!-- Départ -->
                <div class="input-group mb-3 bg-light rounded-3">
                    <span class="input-group-text bg-transparent border-0">
                       <i class="bi bi-geo-alt text-secondary"></i>
                   </span>
                   <input type="text" name="villeDepart" class="form-control border-0 bg-transparent" id="searchStartCity" placeholder="Ville de départ" required>
                </div>

                <!-- Destination -->
              <div class="input-group mb-3 bg-light rounded-3">
            <span class="input-group-text bg-transparent border-0">
                <i class="bi bi-pin-map text-secondary"></i>
            </span>
                    <input type="text" name="villeArrivee" class="form-control border-0 bg-transparent" id="searchEndCity" placeholder="Destination" required>
                </div>

                <!-- Date du voyage -->
                <div class="input-group mb-4 bg-light rounded-3">
            <span class="input-group-text bg-transparent border-0">
                <i class="bi bi-calendar-event text-secondary"></i>
            </span>
                    <input type="date" name="dateVoyage" class="form-control border-0 bg-transparent" id="searchDate" required>
                </div>

                <!-- Bouton de recherche -->
                <div class="d-grid">
                    <button type="submit" class="btn btn-success d-flex justify-content-center align-items-center gap-2 rounded-3">
                        <i class="bi bi-search"></i> Rechercher
                    </button>
                </div>
            </form>
            <?php /*echo '<pre>';
            print_r($searchForTrip);
            echo '</pre>';
            */ ?>
        </section>

        <!--Stats section-->
        <section>
            <div class="row row-cols-1 row-cols-md-3 g-4">
                <div class="col">
                    <div class="card h-80">
                        <div class="card-body d-flex align-items-center flex-column">
                            <h5 class="card-title"><i class="bi bi-ev-front"></i></h5>
                            <p class="card-text">Trajets Verts</p>
                            <p class="card-text">1.245</p>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card h-80">
                        <div class="card-body d-flex align-items-center flex-column">
                            <h5 class="card-title"><i class="bi bi-person"></i></h5>
                            <p class="card-text">Utilisateurs</p>
                            <p class="card-text">8.732</p>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card h-80">
                        <div class="card-body d-flex align-items-center flex-column">
                            <h5 class="card-title"><i class="bi bi-leaf"></i></h5>
                            <p class="card-text">CO2 économisé</p>
                            <p class="card-text">56 T</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!--Popular destinations-->
        <section>
            <h2 class="pt-5">Destinations populaires</h2>
            <span>Voir tout</span>
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

        <section>
            <div class="d-flex justify-content-between align-items-center pt-5 mb-3">
                <h2 class="mb-0">Trajets récents</h2>
                <a href="/rechercher.php" class="text-primary fw-semibold small">Voir tout</a>
            </div>

            <div class="container p-0">
                <!-- Carte trajet -->
                <div class="card shadow-sm mb-3 rounded-4">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-2">
                            <div class="fw-bold me-2">Sophie M.</div>
                            <div class="d-flex align-items-center small text-warning me-2">
                                <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-half"></i>
                                <span class="ms-1 text-secondary">(4.5)</span>
                            </div>
                            <span class="badge rounded-pill bg-success ms-auto">Électrique</span>
                        </div>
                        <div class="mb-2">
                            <i class="bi bi-geo-alt me-1"></i> <strong>Paris</strong>
                            <span class="mx-2 text-muted">→</span>
                            <i class="bi bi-pin-map me-1"></i> <strong>Lyon</strong>
                        </div>
                        <div class="d-flex align-items-center text-secondary small mb-2">
                            <div class="me-3"><i class="bi bi-calendar-event me-1"></i>10 mai, 10:00</div>
                            <div class="me-3"><i class="bi bi-person-fill-add me-1"></i>2 places</div>
                            <div><i class="bi bi-currency-euro me-1"></i>15 crédits</div>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button class="btn btn-primary btn-sm rounded-pill">Réserver</button>
                        </div>
                    </div>
                </div>
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
            <div style="height: 5rem"></div>
        </section>
    </div>
</main>

<!-- Tab bar-->
<footer>
    <?php include 'footer.php'; ?>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
<script type="module" src="assets/js/script.js"></script>
</body>
</html>