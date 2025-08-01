<?php

require_once __DIR__ . '/../vendor/autoload.php';;

use Olivierguissard\EcoRide\Config\Database;
use Olivierguissard\EcoRide\Model\Trip;
use Olivierguissard\EcoRide\Model\Users;
use Olivierguissard\EcoRide\Model\Car;

require_once 'functions/auth.php';
startSession();
isAuthenticated();

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
switch ($sort) {
    case 'price':
        $sql .= " ORDER BY t.price_per_passenger ASC";
        break;
    case 'time':
        $sql .= " ORDER BY t.departure_at ASC";
        break;
    case 'rating':
        $sql .= " ORDER BY u.ranking DESC";
        break;
    default:
        $sql .= " ORDER BY t.departure_at ASC";
}

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
    $car = Car::find($trip->getVehicleId());
    if ($car && !in_array($car->carburant, $energies)) {
        $energies[] = $car->carburant;
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

$flashError = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_error']);

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
    <header>
        <nav class="navbar bg-body-tertiary">
            <div class="container" style="max-width: 900px;">
                <a class="navbar-brand" href="/index.php">
                    <img src="assets/pictures/logoEcoRide.png" alt="Logo EcoRide" width="60" class="d-inline-block align-text-center rounded">
                </a>
                <h2 class="fw-bold mb-1 text-success">Trouver un voyage</h2>
                <?= displayInitialsButton(); ?>
            </div>
        </nav>
    </header>

<!-- Main Content -->
<main class="container px-3 py-2 mt-1 pt-5">
    <!-- Search Summary -->
    <section class="mt-1">
        <h2 class="fw-bold mb-4">Affinez votre recherche</h2>
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
        <h2 class="fw-bold mb-4">Les trajets disponibles</h2>
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
                <?php $showError = ($flashError && $flashError['trip_id'] && $flashError['trip_id'] == $trip->getTripId()); ?>

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

                        <form method="post" action="reserve.php" style="display:inline">
                            <input type="hidden" name="trip_id" value="<?= htmlspecialchars($trip->getTripId()) ?>">
                            <input type="hidden" name="seats_reserved" value="1">
                            <?php if ($remainingSeats > 0 && !$showError): ?>
                            <button type="submit" class="btn btn-primary">Réserver</button>
                            <?php elseif ($showError): ?>
                            <button type="button" class="btn btn-secondary disabled" disabled>Réservé</button>
                            <?php else: ?>
                            <button type="button" class="btn btn-secondary disabled" disabled>Complet</button>
                            <?php endif; ?>
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
                    En choisissant un trajet électrique pour votre voyage Paris-Lyon,
                    vous contribuez à réduire votre empreinte carbone.
                </p>
            </div>
        </div>
    </section>
</main>

<footer>
    <?php require 'footer.php'; ?>
</footer>

<script src="assets/js/rechercher.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
</body>
</html>
