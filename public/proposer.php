<?php

require_once __DIR__ . '/../vendor/autoload.php';;

use Olivierguissard\EcoRide\Model\Trip;
use Olivierguissard\EcoRide\Model\Car;

require_once 'functions/auth.php';
requireAuth();

require_once __DIR__ . '/../src/Helpers/helpers.php';

$userID = getUserId();
$voyages = Trip::findTripsByDriver($userID);
$vehicles = Car::findByUser($userID);

// Soumettre le formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log('Données POST reçues : ' . print_r($_POST, true));
    $keys = [
        'trip_id',
        'driver_id',
        'start_city',
        'end_city',
        'departure_date',
        'departure_time',
        'vehicle_id',
        'available_seats',
        'price_per_passenger',
        'comment',
        'no_smoking',
        'music_allowed',
        'discuss_allowed',
        'start_location',
        'end_location',
        'estimated_duration'
    ];
    $data = array_intersect_key($_POST, array_flip($keys));

    // Gere la date et l'heure dans un seul champ "departure_at"
    if (!empty($data['departure_date']) && !empty($data['departure_time'])) {
        $data['departure_at'] = $data['departure_date'] . ' ' . $data['departure_time'];
    } else {
        // valeur par défaut ou gestion d’erreur
        $data['departure_at'] = date('Y-m-d H:i:s');
    }

    if (!empty($data['trip_id'])) {
        $voyage = Trip::find((int)$data['trip_id']);

        $voyage->setDriverId((int)$data['driver_id']);
        $voyage->setStartCity($data['start_city']);
        $voyage->setEndCity($data['end_city']);
        $voyage->setDepartureAt(new DateTime($data['departure_at']));
        $voyage->setPricePerPassenger($data['price_per_passenger']);
        $voyage->setComment($data['comment']);
        $voyage->setNoSmoking($data['no_smoking']);
        $voyage->setMusicAllowed($data['music_allowed']);
        $voyage->setDiscussAllowed($data['discuss_allowed']);
    } else {
        $data['driver_id'] = $_SESSION['user_id'];
        $voyage = new Trip($data);
    }

    if ($voyage->validateTrip()) {

        if ($voyage->saveToDatabase()) {
            $_SESSION['flash_success'] = 'Le voyage est enregistré !';
        } else {
            $_SESSION['flash_error'] = 'Une erreur est survenue lors de l\'enregistrement du voyage.';
        }
    } else {
        $_SESSION['flash_error'] = 'Le voyage n\'est pas valide.';
    }
    header('Location: /rechercher.php');
    exit;
}
$voyage = Trip::findTripsByDriver($_SESSION['user_id']);

// Récupère la liste des véhicules de l'utilisateur connecté
$vehicles = Car::findByUser($_SESSION['user_id']);

$success = false;
$pageTitle = 'Proposer un trajet - EcoRide';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="assets/pictures/logoEcoRide.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/proposer.css">
    <title><?php if (isset($pageTitle)) { echo $pageTitle; } else { echo 'EcoRide - Covoiturage écologique';} ?></title>
</head>

<body>
    <header>
        <nav class="navbar bg-body-tertiary">
            <div class="container" style="max-width: 900px;">
                <a class="navbar-brand" href="/index.php">
                    <img src="assets/pictures/logoEcoRide.png" alt="Logo EcoRide" width="60" class="d-inline-block align-text-center rounded">
                </a>
                <h2 class="fw-bold mb-1 text-success">Proposer un trajet</h2>
                <?= displayInitialsButton(); ?>
            </div>
        </nav>
    </header>

<!-- New formulaire -->
    <main class="container px-3 py-2 mt-1 pt-5">
        <section class="mt-1">
            <h2 class="fw-bold mb-4">Publier un trajet</h2>

            <!-- Barre de progression -->
            <div class="progress-container mb-4">
                <div class="progress-steps d-flex justify-content-between align-items-center">
                    <div class="step text-center">
                        <div class="circle bg-success text-white fw-bold">1</div>
                        <div class="step-label mt-2 small">Itinéraire</div>
                    </div>
                    <div class="bar flex-grow-1 mx-2 bg-secondary" style="height: 2px"></div>
                    <div class="step text-center">
                        <div class="circle bg-light border border-secondary text-secondary fw-bold">2</div>
                        <div class="step-label mt-2 small">Date & Heure</div>
                    </div>
                    <div class="bar flex-grow-1 mx-2 bg-secondary" style="height: 2px"></div>
                    <div class="step text-center">
                        <div class="circle bg-light border border-secondary text-secondary fw-bold">3</div>
                        <div class="step-label mt-2 small">Places & Prix</div>
                    </div>
                    <div class="bar flex-grow-1 mx-2 bg-secondary" style="height: 2px"></div>
                    <div class="step text-center">
                        <div class="circle bg-light border border-secondary text-secondary fw-bold">4</div>
                        <div class="step-label mt-2 small">Options</div>
                    </div>
                </div>
            </div>

            <!-- Formulaire publication de trajet -->
            <form id="tripForm" action="proposer.php" method="post" class="p-4 bg-white rounded-4 shadow-sm needs-validation" novalidate>

                <!-- Etape 1 : Itinéraire -->
                <div class="form-step" id="step1">
                    <div class="mb-4">
                        <label class="fw-semibold mb-2">Ville de départ</label>
                        <div class="input-group bg-light rounded-3 mb-2">
                    <span class="input-group-text bg-transparent border-0">
                        <i class="bi bi-geo-alt text-secondary"></i>
                    </span>
                            <input type="text" name="start_city" class="form-control border-0 bg-transparent" placeholder="Ville de départ" required>
                            <div class="invalid-feedback">Veuillez renseigner la ville de départ.</div>
                        </div>
                        <div class="input-group bg-light rounded-3">
                    <span class="input-group-text bg-transparent border-0">
                        <i class="bi bi-house-door text-secondary"></i>
                    </span>
                            <input type="text" name="start_location" class="form-control border-0 bg-transparent" placeholder="Adresse de départ">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="fw-semibold mb-2">Destination</label>
                        <div class="input-group bg-light rounded-3 mb-2">
                    <span class="input-group-text bg-transparent border-0">
                        <i class="bi bi-pin-map text-secondary"></i>
                    </span>
                            <input type="text" name="end_city" class="form-control border-0 bg-transparent" placeholder="Ville d'arrivée" required>
                            <div class="invalid-feedback">Veuillez renseigner la ville d'arrivée.</div>
                        </div>
                        <div class="input-group bg-light rounded-3">
                    <span class="input-group-text bg-transparent border-0">
                        <i class="bi bi-house-door text-secondary"></i>
                    </span>
                            <input type="text" name="end_location" class="form-control border-0 bg-transparent" placeholder="Adresse d'arrivée">
                        </div>
                    </div>

                </div>
                <!-- Etape 2 : Date / Heure -->
                <div class="form-step" id="step2">
                    <div class="mb-4">
                        <label class="fw-semibold mb-2">Date du voyage</label>
                        <div class="input-group bg-light rounded-3 mb-3">
                    <span class="input-group-text bg-transparent border-0">
                        <i class="bi bi-calendar-event text-secondary"></i>
                    </span>
                            <input type="date" name="departure_date" class="form-control border-0 bg-transparent" required>
                            <div class="invalid-feedback">Veuillez renseigner la date de départ.</div>
                        </div>
                        <div class="input-group bg-light rounded-3">
                    <span class="input-group-text bg-transparent border-0">
                        <i class="bi bi-clock text-secondary"></i>
                    </span>
                            <input type="time" name="departure_time" class="form-control border-0 bg-transparent" required>
                            <div class="invalid-feedback">Veuillez renseigner l'heure de départ.</div>
                        </div>
                    </div>

                </div>
                <!-- Etape 3 : Places / Prix / Véhicule -->
                <div class="form-step" id="step3">
                    <div class="mb-4">
                        <label class="fw-semibold mb-2">Véhicule</label>
                        <select name="vehicle_id" class="form-select rounded-3 mb-3" required>
                            <option value="">Choisissez votre véhicule</option>
                            <?php foreach ($vehicles as $veh): ?>
                                <option value="<?= htmlspecialchars($veh->id_vehicule) ?>">
                                    <?= htmlspecialchars($veh->marque . ' ' . $veh->modele . ' (' . $veh->carburant . ')') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <label class="fw-semibold mb-2">Nombre de places disponibles</label>
                        <div class="btn-group w-100 mb-3" role="group">
                            <input type="radio" class="btn-check" name="available_seats" id="seat1" value="1">
                            <label class="btn btn-outline-success" for="seat1">1</label>
                            <input type="radio" class="btn-check" name="available_seats" id="seat2" value="2">
                            <label class="btn btn-outline-success" for="seat2">2</label>
                            <input type="radio" class="btn-check" name="available_seats" id="seat3" value="3">
                            <label class="btn btn-outline-success" for="seat3">3</label>
                            <input type="radio" class="btn-check" name="available_seats" id="seat4" value="4">
                            <label class="btn btn-outline-success" for="seat4">4</label>
                            <input type="radio" class="btn-check" name="available_seats" id="seat5" value="5">
                            <label class="btn btn-outline-success" for="seat5">5</label>
                        </div>

                        <label class="fw-semibold mb-2">Prix par passager</label>
                        <div class="input-group bg-light rounded-3">
                    <span class="input-group-text bg-transparent border-0">
                        <i class="bi bi-currency-euro text-secondary"></i>
                    </span>
                            <input type="number" name="price_per_passenger" class="form-control border-0 bg-transparent" min="1" placeholder="Prix en crédits" required>
                            <div class="invalid-feedback">Veuillez renseigner un prix valide.</div>
                        </div>
                    </div>

                </div>
                <!-- Etape 4 : Options -->
                <div class="form-step" id="step4">
                    <div class="mb-4">
                        <label class="fw-semibold mb-2">Options</label>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="no_smoking" id="noSmoking" checked>
                            <label class="form-check-label" for="noSmoking">Non fumeur</label>
                        </div>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="music_allowed" id="musicAllowed" checked>
                            <label class="form-check-label" for="musicAllowed">Musique autorisée</label>
                        </div>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="discuss_allowed" id="discussAllowed" checked>
                            <label class="form-check-label" for="discussAllowed">Discussions bienvenues</label>
                        </div>
                        <label for="comment" class="form-label">Commentaire pour les passagers</label>
                        <textarea class="form-control" name="comment" id="comment" rows="3" placeholder="Ex : Je pars du parking de la gare..."></textarea>
                    </div>

                </div>
                <!-- Bouton publication -->
                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-2">
                    <button type="button" class="btn btn-success d-flex justify-content-center align-items-center gap-2 rounded-3" data-bs-toggle="modal" data-bs-target="#confirmationModal">
                        <i class="bi bi-send-check"></i> Publier ce trajet
                    </button>
                </div>
            </form>

            <!-- Modale de confirmation -->
            <div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="confirmationModalLabel">Confirmer le trajet</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>
                        <div class="modal-body" id="modalText">
                            <!-- Texte récapitulatif intégré via le js -->
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="button" class="btn btn-success" id="confirmSubmitBtn">Confirmer</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="container mb-5"></div>
        </section>
    </main>

<!-- Ancien Formulaire Multi-Étapes
    <main class="pt-5 mt-5">
        <form action="" method="post" id="suggestedTripForm" class="multi-step-form">
            <!-- Étapes contrôlées par radio
            <input type="radio" name="step" id="step1" checked hidden>
            <input type="radio" name="step" id="step2" hidden>
            <input type="radio" name="step" id="step3" hidden>
            <input type="radio" name="step" id="step4" hidden>

            <div class="steps container py-4">

                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger" role="alert">
                    Formulaire invalidegfhd
                </div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success" role="alert">
                        Merci d'avoir publié votre trajet !
                    </div>
                <?php endif; ?>
                <!-- Barre de progression
                <div class="progress mb-4">
                    <div class="progress-bar bg-success" role="progressbar" style="width: 25%;" id="progressBar"></div>
                </div>

                <!-- Étape 1 : Itinéraire
                <div class="step step1">
                    <h2>1. Itinéraire</h2>
                    <div class="mb-3">
                        <label for="startCity" class="form-label"><i class="bi bi-geo-alt"></i> Ville de départ</label>
                        <input type="text" name="start_city"  class="form-control ps-5" id="startCity" placeholder="Départ" required>
                        <?php if (isset($errors['startCity'])): ?>
                        <div class="invalid-feedback"><?= $errors['startCity'] ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label for="startLocation" class="form-label"><i class="bi bi-geo-alt"></i> Adresse de départ</label>
                        <input type="text" name="start_location" class="form-control ps-5" id="startLocation" placeholder="Préciser l'adresse / lieux-dit">
                        <?php if (isset($errors['startLocation'])): ?>
                            <div class="invalid-feedback"><?= $errors['startLocation'] ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label for="endCity" class="form-label"><i class="bi bi-pin-map"></i> Ville de destination</label>
                        <input type="text" name="end_city" class="form-control ps-5" id="endCity" placeholder="Destination" required>
                        <?php if (isset($errors['endCity'])): ?>
                            <div class="invalid-feedback"><?= $errors['endCity'] ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label for="endLocation" class="form-label"><i class="bi bi-pin-map"></i> Adresse de destination</label>
                        <input type="text" name="end_location" class="form-control ps-5" id="endLocation" placeholder="Adresse de destination">
                        <?php if (isset($errors['endLocation'])): ?>
                            <div class="invalid-feedback"><?= $errors['endLocation'] ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label for="estimatedDuration" class="form-label"><i class="bi bi-stopwatch"></i> Durée de voyage estimée</label>
                        <input type="text" name="estimated_duration" class="form-control ps-5" id="estimatedDuration" placeholder="Durée du voyage" required>
                        <?php if (isset($errors['estimatedDuration'])): ?>
                            <div class="invalid-feedback"><?= $errors['estimatedDuration'] ?></div>
                        <?php endif; ?>
                    </div>
                    <p>+ Ajouter un arrêt supplémentaire</p>
                </div>

                <!-- Étape 2 : Date/Heure
                <div class="step step2">
                    <h2>2. Date et Heure</h2>
                    <div class="mb-3">
                        <label for="departureDate" class="form-label">Date</label>
                        <input type="date" name="departure_date" class="form-control" id="departureDate" required>
                        <?php if (isset($errors['departureDate'])): ?>
                            <div class="invalid-feedback"><?= $errors['departureDate'] ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label for="departureTime" class="form-label">Heure</label>
                        <input type="time" name="departure_time" class="form-control" id="departureTime" required>
                        <?php if (isset($errors['departureTime'])): ?>
                            <div class="invalid-feedback"><?= $errors['departureTime'] ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Étape 3 : Places/Prix
                <div class="step step3">
                    <h2>3. Places et Prix</h2>
                    <h3>Véhicule utilisé</h3>
                    <div class="mb-3">
                        <label for="vehiclesUser">Véhicule</label>
                        <select class="form-select" id="vehiclesUser" name="vehicle_id" required>
                            <option value="" disabled selected>Choisissez votre véhicule</option>
                            <?php foreach ($vehicles as $veh): ?>
                                <option value="<?= htmlspecialchars($veh->id) ?>">
                                    <?= htmlspecialchars($veh->marque . ' ' . $veh->modele) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <h3>Nombre de places disponibles</h3>
                    <div class="mb-3">
                        <div class="btn-group" role="group">
                            <input type="radio" class="btn-check placeAvailable" name="available_seats" id="place1" value="1">
                            <label class="btn btn-outline-success" for="place1">1</label>
                            <input type="radio" class="btn-check placeAvailable" name="available_seats" id="place2" value="2">
                            <label class="btn btn-outline-success" for="place2">2</label>
                            <input type="radio" class="btn-check placeAvailable" name="available_seats" id="place3" value="3" checked>
                            <label class="btn btn-outline-success" for="place3">3</label>
                            <input type="radio" class="btn-check placeAvailable" name="available_seats" id="place4" value="4">
                            <label class="btn btn-outline-success" for="place4">4</label>
                            <input type="radio" class="btn-check placeAvailable" name="available_seats" id="place5" value="5">
                            <label class="btn btn-outline-success" for="place5">5</label>
                        </div>
                    </div>
                    <h3>Prix par passager</h3>
                    <div class="input-group flex-nowrap mb-3">
                        <span class="input-group-text">Crédits</span>
                        <input type="number" class="form-control" name="price_per_passenger" id="pricePerPassenger" placeholder="--" value="20" min="0" step="1" required>
                    </div>
                    <p>Jusqu'à <strong id="totalPrice"></strong> crédits pour ce trajet avec <strong id="placeFree"></strong> passagers</p>
                </div>

                <!-- Étape 4 : Options
                <div class="step step4">
                    <h2>4. Options</h2>
                    <h3>Préférences</h3>
                    <div class="form-check form-switch">
                        <input type="checkbox" name="no_smoking" class="form-check-input"  id="no-smoking" checked>
                        <label class="form-check-label" for="no-smoking">Non-fumeur</label>
                    </div>
                    <div class="form-check form-switch">
                        <input type="checkbox" name="music_allowed" class="form-check-input"  id="musicPlay" checked>
                        <label class="form-check-label" for="musicPlay">Musique autorisée</label>
                    </div>
                    <div class="form-check form-switch">
                        <input type="checkbox" name="discuss_allowed" class="form-check-input"  id="discussTogether" checked>
                        <label class="form-check-label" for="discussTogether">Discussions bienvenues</label>
                    </div>
                    <h3>Commentaire pour les passagers</h3>
                    <div class="form-floating mb-3">
                        <textarea name="comment" class="form-control" id="commentForPassenger" style="height: 100px"></textarea>
                        <label for="commentForPassenger">Ex: Je pars du parking de la gare de Lyon...</label>
                    </div>
                </div>
            </div>
            <button type="button" class="btn btn-lg btn-success" id="publishSuggestedForm">Publier ce trajet</button> <!-- boutton de type button pour ne pas soumettre le formulaire.
            <div class="p-5"></div>
        </form>
        <section>
            // Modale de validation du formulaire de trajet
            <div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="confirmationModalLabel">Proposer un trajet :</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>

                        <div class="modal-body">
                            <p id="modalText"></p>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Annuler</button>
                            <button type="button" class="btn btn-success" id="confirmSubmit">Proposer</button>
                        </div>
                    </div>
                </div>
        </section>
    </main>
</body> -->


<!-- Tab Bar -->
<footer>
    <?php include 'footer.php'; ?>
</footer>

<script src="assets/js/proposer.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
</body>
</html>
