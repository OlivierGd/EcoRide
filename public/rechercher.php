<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Olivierguissard\EcoRide\Config\Database;
use Olivierguissard\EcoRide\Model\Trip;
use Olivierguissard\EcoRide\Model\Users;
use Olivierguissard\EcoRide\Model\Car;

require_once 'functions/auth.php';
startSession();
if (isAuthenticated()) {
    updateActivity();
}

require_once __DIR__ . '/../src/Helpers/helpers.php';

// Récupération des critères de recherche (GET)
$startCity     = trim($_GET['startCity'] ?? '');
$endCity       = trim($_GET['endCity'] ?? '');
$departureDate = trim($_GET['departureDate'] ?? '');
// Récupération des critères des filtres avancés
$energySelected = $_GET['energy'] ?? '';
$placesSelected = $_GET['places'] ?? '';
$ratingSelected = $_GET['rating'] ?? '';
$sort = $_GET['sort'] ?? '';


// Requête SQL dynamique pour les filtres avancés
$pdo = Database::getConnection();
$sql = "SELECT t.* FROM trips t 
        JOIN vehicule v ON t.vehicle_id = v.id_vehicule
        JOIN users u ON t.driver_id = u.user_id
        WHERE t.departure_at > NOW()";
$params = [];

if ($startCity !== '') {
    $sql .= " AND t.start_city ILIKE :startCity";
    $params[':startCity'] = "%$startCity%";
}
if ($endCity !== '') {
    $sql .= " AND t.end_city ILIKE :endCity";
    $params[':endCity'] = "%$endCity%";
}
if ($departureDate !== '') {
    $sql .= " AND DATE(t.departure_at) = :departureDate";
    $params[':departureDate'] = $departureDate;
}
if ($energySelected !== '') {
    $sql .= " AND v.type_carburant = :energy";
    $params[':energy'] = $energySelected;
}
if ($placesSelected !== '') {
    $sql .= " AND t.available_seats >= :places";
    $params[':places'] = $placesSelected;
}
if ($ratingSelected !== '') {
    $sql .= " AND u.ranking >= :rating";
    $params[':rating'] = $ratingSelected;
}

// Tri suivant l'option choisie
$sql .= match ($sort) {
    'price'  => " ORDER BY t.price_per_passenger ASC",
    'time'   => " ORDER BY t.departure_at ASC",
    'rating' => " ORDER BY u.ranking DESC",
    default  => " ORDER BY t.departure_at ASC"
};


// Execute la requête
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$trips = array_map(fn($r) => new Trip($r), $rows);


// Initialise les tableaux pour chaque filtre
$energies = [];
$places = [];
$ratings = [];

foreach ($trips as $trip) {
    // Énergie du véhicule utilisé
    $car = Car::findCarById($trip->getVehicleId());
    if ($car && !in_array($car->getCarburant(), $energies)) {
        $energies[] = $car->getCarburant();
    }

    // Nombre de places disponibles
    $remainingSeats = $trip->getRemainingSeats();
    if (!in_array($remainingSeats, $places)) {
        $places[] = $remainingSeats;
    }

    // Classement du conducteur (ranking)
    $driver = Users::findUser($trip->getDriverId());
    if ($driver) {
        $ranking = intval($driver->getRanking());
        if (!in_array($ranking, $ratings)) {
            $ratings[] = $ranking;
        }
    }
}
// Trier les tableaux pour affichage croissant
sort($energies);
sort($places);
rsort($ratings); // Décroissant pour le ranking

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
    <title><?= $pageTitle ?? 'EcoRide - Covoiturage écologique' ?></title>
</head>
<body>
    <header>
        <nav class="navbar bg-body-tertiary">
            <div class="container" style="max-width: 900px;">
                <a class="navbar-brand" href="index.php">
                    <img src="assets/pictures/logoEcoRide.png" alt="Logo EcoRide" width="60" class="d-inline-block align-text-center rounded">
                </a>
                <h2 class="fw-bold mb-1 text-success">Trouver un voyage</h2>
                <?= displayInitialsButton(); ?>
            </div>
        </nav>
    </header>

<!-- Main Content -->
<main class="container px-3 py-2 mt-1 pt-5">
    <?php if (isset($_SESSION['flash_error']) && is_array($_SESSION['flash_error']) && $_SESSION['flash_error']['type'] === 'insufficient_credits'): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <h5><i class="bi bi-exclamation-triangle"></i> Réservation impossible</h5>
            <p class="mb-3"><?= htmlspecialchars($_SESSION['flash_error']['message']) ?></p>
            <a href="buyCredits.php?trip_id=<?= $_SESSION['flash_error']['trip_id'] ?>" class="btn btn-success">
                <i class="bi bi-credit-card"></i> Ajouter des crédits
            </a>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php elseif (isset($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= is_array($_SESSION['flash_error']) ? $_SESSION['flash_error']['message'] : $_SESSION['flash_error'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['flash_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= is_array($_SESSION['flash_success']) ? $_SESSION['flash_success']['message'] : $_SESSION['flash_success'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <!-- Search Summary -->
    <section class="mt-1">
        <h3 class="fw-bold mb-4"><i class="bi bi-search text-success me-2"></i>Recherche un trajet</h3>
        <form action="rechercher.php" method="get" id="formSearchDestination" class="p-4 bg-white rounded-4 shadow-sm">
            <!-- Départ -->
            <div class="input-group mb-3 bg-light rounded-3">
                <span class="input-group-text bg-transparent border-0">
                    <i class="bi bi-geo-alt text-secondary"></i>
                </span>
                <input type="text" name="startCity" class="form-control border-0 bg-transparent" id="searchStartCity"
                       placeholder="Ville de départ" value="<?= htmlspecialchars($startCity) ?>">
            </div>
            <!-- Destination -->
            <div class="input-group mb-3 bg-light rounded-3">
                <span class="input-group-text bg-transparent border-0">
                    <i class="bi bi-pin-map text-secondary"></i>
                </span>
                <input type="text" name="endCity" class="form-control border-0 bg-transparent" id="searchEndCity"
                       placeholder="Destination" value="<?= htmlspecialchars($endCity) ?>">
            </div>
            <!-- Date du voyage -->
            <div class="input-group mb-4 bg-light rounded-3">
                <span class="input-group-text bg-transparent border-0">
                    <i class="bi bi-calendar-event text-secondary"></i>
                </span>
                <input type="date" name="departureDate" class="form-control border-0 bg-transparent" id="searchDate"
                       value="<?= htmlspecialchars($departureDate) ?>">
            </div>
            <small class="form-text text-muted">Laissez vide pour afficher tous les trajets à venir.</small>
            <!-- Filtres avancés -->
            <div class="row mb-3 g-2">
                <div class="col-6 col-md-3">
                    <select name="sort" class="form-select rounded-3">
                        <option value="">Trier par</option>
                        <option value="price" <?= $sort === 'price' ? 'selected' : '' ?>>Prix croissant</option>
                        <option value="time" <?= $sort === 'time' ? 'selected' : '' ?>>Heure</option>
                        <option value="rating" <?= $sort === 'rating' ? 'selected' : '' ?>>Avis</option>
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <select name="energy" class="form-select rounded-3">
                        <option value="">Type de véhicule</option>
                        <?php foreach ($energies as $energy): ?>
                            <option value="<?= htmlspecialchars($energy) ?>" <?= $energy === $energySelected ? 'selected' : '' ?>><?= htmlspecialchars($energy) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <select name="places" class="form-select rounded-3">
                        <option value="">Places min</option>
                        <?php foreach ($places as $p): ?>
                            <option value="<?= $p ?>" <?= $p == $placesSelected ? 'selected' : '' ?>><?= $p ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <select name="rating" class="form-select rounded-3">
                        <option value="">Avis min</option>
                        <?php foreach ($ratings as $r): ?>
                            <option value="<?= $r ?>" <?= $r == $ratingSelected ? 'selected' : '' ?>><?= $r ?> étoiles</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <!-- Bouton de recherche -->
            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-2">
                <button type="submit" class="btn btn-success d-flex justify-content-center align-items-center gap-2 rounded-3">
                    <i class="bi bi-search"></i> Rechercher
                </button>
                <button type="button" id="resetSearchForm" class="btn btn-outline-secondary ms-2" aria-label="Réinitialiser le formulaire de recherche">
                    <i class="bi bi-x-circle"></i> Vider
                </button>
            </div>

        </form>
    </section>

    <!-- Rides List -->
    <section class="mt-5 mb-3">
        <h3 class="fw-bold mb-4"><i class="bi bi-search-heart text-success me-2"></i>Les trajets disponibles</h3>
        <!-- Ride card -->
        <?php foreach ($trips as $trip):
        // Le conducteur
        $driver = Users::findUser($trip->getDriverId());
        // Le véhicule utilisé
        $car = Car::findCarById($trip->getVehicleId());
        if (!$driver || !$car) continue;

        // Calcul du nombre de places
        $remainingSeats = $trip->getRemainingSeats();

        // Détermination de l'erreur pour ce trajet spécifique
        $showError = false;
        $flashError = getFlash('error');
        if (is_array($flashError) && isset($flashError['trip_id']) && $flashError['trip_id'] == $trip->getTripId()) {
            $showError = true;
        }


        // Données affichées
        $initialsBtn = displayInitialsButton($driver);
        $nameLabel  = htmlspecialchars($driver->getFirstName() . ' ' . strtoupper(substr($driver->getLastName(),0,1)));
        $stars      = renderStars($driver->getRanking());
        $ranking    = htmlspecialchars($driver->getRanking());
        $energy     = htmlspecialchars($car->getCarburant());;
        $startCity  = htmlspecialchars($trip->getStartCity());
        $endCity    = htmlspecialchars($trip->getEndCity());
        $time       = htmlspecialchars($trip->getDepartureTime());
        $date       = htmlspecialchars($trip->getDepartureDateFr());
        $price      = htmlspecialchars($trip->getPricePerPassenger());
        $vehicleLabel = htmlspecialchars($car->getMarque() . ' ' . $car->getModele());
        ?>
            <div class="container p-0">
                <?php if ($showError && isset($flashError['message'])): ?>
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <?= $flashError['message'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

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
                            <div class="d-flex align-items-center gap-2">
                                <span>
                                    <i class="bi bi-people-fill me-1"></i>
                                    <?= $remainingSeats ?> place<?= $remainingSeats > 1 ? 's' : '' ?> restante<?= $remainingSeats > 1 ? 's' : '' ?>
                                </span>
                            </div>
                            <div><i class="bi bi-currency-euro me-1"></i><?= $price ?> crédits</div>
                        </div>
                        <!-- Message d'erreur si solde crédit insuffisant. -->
                        <?php if ($showError): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-circle"></i>
                                <?= $flashError['message']; ?>
                            </div>
                        <?php endif; ?>
                        <?php if (isAuthenticated()) : ?>
                            <!-- Bouton détails modal si non connecté -->
                        <?php else: ?>
                            <button type="button" class="btn btn-outline-secondary me-2" onclick="alert('Connectez-vous pour voir les détails du trajet.'); window.location.href='login.php';">
                                Détails
                            </button>
                        <?php endif; ?>
                        <!-- Bouton détails trajets -->
                        <button type="button" class="btn btn-outline-secondary me-2" data-bs-toggle="modal"
                                data-bs-target="#tripModal-<?= $trip->getTripId(); ?>">Détails
                        </button>

                        <form method="post" action="reserve.php" style="display:inline">
                            <input type="hidden" name="trip_id" value="<?= htmlspecialchars($trip->getTripId()) ?>">
                            <input type="hidden" name="seats_reserved" value="1">
                            <?php if ($remainingSeats > 0 && !$showError): ?>
                            <button type="button"
                                class="btn btn-primary"
                                data-bs-toggle="modal"
                                data-bs-target="#reservationModal"
                                data-trip-id="<?= $trip->getTripId() ?>"
                                data-start-city="<?= $startCity ?>"
                                data-end-city="<?= $endCity ?>"
                                data-departure="<?= $trip->getDepartureDateFr() ?> à <?= $trip->getDepartureTime() ?>"
                                data-price="<?= $trip->getPricePerPassenger() ?>">
                                Réserver
                            </button>
                            <?php elseif ($showError): ?>
                            <button type="button" class="btn btn-secondary disabled" disabled>Réservé</button>
                            <?php else: ?>
                            <button type="button" class="btn btn-secondary disabled" disabled>Complet</button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>

        <!-- Modale infos trajets (Bouton détails) -->
            <?php
            $arrivalTime = clone $trip->getDepartureAt();
            $interval = $trip->getEstimatedDurationAsInterval();
            if ($interval) {
                $arrivalTime->add($interval);
            }
            $arrivalFormatted = $arrivalTime->format('H:i');
            ?>
            <div class="modal fade" id="tripModal-<?= $trip->getTripId(); ?>" tabindex="-1" aria-labelledby="tripModalLabel-<?= $trip->getTripId(); ?>" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <div class="modal-header bg-success text-white">
                            <h5 class="modal-title" id="tripModalLabel-<?= $trip->getTripId(); ?>">Détails du trajet</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>
                        <div class="modal-body">
                            <p><strong>Conducteur :</strong> <?= htmlspecialchars($driver->getFirstName()) ?> (<?= renderStars($driver->getRanking()) ?>)</p>
                            <p><strong>Départ :</strong> <?= $trip->getStartCity() ?>, <?= $trip->getStartLocation() ?></p>
                            <p><strong>Arrivée :</strong> <?= $trip->getEndCity() ?>, <?= $trip->getEndLocation() ?></p>
                            <p><strong>Départ prévu :</strong> <?= $trip->getDepartureDateFr() ?> à <?= $trip->getDepartureTime() ?></p>
                            <p><strong>Arrivée estimée :</strong> <?= $arrivalFormatted ?></p>
                            <p><strong>Places disponibles :</strong> <?= $trip->getRemainingSeats() ?></p>
                            <p><strong>Prix :</strong> <?= $trip->getPricePerPassenger() ?> crédits</p>
                            <p><strong>Commentaire :</strong> <?= nl2br(htmlspecialchars($trip->getComment())) ?></p>
                            <p><strong>Préférences :</strong>
                                <?= $trip->getNoSmoking() ? '🚭 Non-fumeur, ' : '' ?>
                                <?= $trip->getMusicAllowed() ? '🎵 Musique autorisée, ' : '' ?>
                                <?= $trip->getDiscussAllowed() ? '💬 Discussion autorisée' : '' ?>
                            </p>
                            <p><strong>Véhicule :</strong> <?= htmlspecialchars($car->getMarque() . ' ' . $car->getModele()) ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Modale de confirmation de voyage -->
            <div class="modal fade" id="reservationModal" tabindex="-1" aria-labelledby="reservationModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">

                        <div class="modal-header bg-success text-white">
                            <h5 class="modal-title">Confirmer votre réservation</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>

                        <div class="modal-body">
                            <p>Vous réservez une place pour le voyage :</p>
                            <ul class="list-unstyled">
                                <li><strong>Départ :</strong> <span id="modalStartCity"></span></li>
                                <li><strong>Arrivée :</strong> <span id="modalEndCity"></span></li>
                                <li><strong>Date :</strong> <span id="modalDeparture"></span></li>
                                <li><strong>Crédits requis :</strong> <span id="modalPrice"></span> crédits</li>
                            </ul>

                            <form method="post" action="reserve.php">
                                <input type="hidden" name="trip_id" id="confirmTripId">
                                <input type="hidden" name="seats_reserved" value="1">
                                <div class="d-flex justify-content-end">
                                    <button type="submit" class="btn btn-success me-2">Confirmer</button>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                </div>
                            </form>
                        </div>

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
    <section class="bg-primary-light rounded p-3 mb-5">
        <div class="d-flex align-items-start">
            <div class="bg-primary rounded-circle me-3 p-2 text-white" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                <i class="ri-leaf-line"></i>
            </div>
            <div>
                <h3 class="small fw-medium mb-1">
                    Économisez jusqu'à 3.2 kg de CO₂
                </h3>
                <p class="small text-secondary-emphasis">
                    En choisissant un trajet électrique pour votre voyage,
                    vous contribuez à réduire votre empreinte carbone.
                </p>
            </div>
        </div>
    </section>
</main>

<footer>
    <?php require 'footer.php'; ?>
</footer>

<script src="assets/js/cities-autocomplete.js"></script>
<script src="assets/js/rechercher.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>

</body>
</html>