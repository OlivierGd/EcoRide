<?php

require_once __DIR__ . '/../vendor/autoload.php';;

use Olivierguissard\EcoRide\Model\Trip;
use Olivierguissard\EcoRide\Model\Car;
use Olivierguissard\EcoRide\Service\CreditService;

require_once 'functions/auth.php';
requireAuth();
updateActivity();

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
        'duration_hours',
        'duration_minutes'
    ];
    $data = array_intersect_key($_POST, array_flip($keys));

    // Gere la date et l'heure dans un seul champ "departure_at"
    if (!empty($data['departure_date']) && !empty($data['departure_time'])) {
        $data['departure_at'] = $data['departure_date'] . ' ' . $data['departure_time'];
    } else {
        // valeur par défaut ou gestion d’erreur
        $data['departure_at'] = date('Y-m-d H:i:s');
    }
    $hours = (int)($_POST['duration_hours'] ?? 0);
    $minutes = (int)($_POST['duration_minutes'] ?? 0);
    $data['estimated_duration'] = sprintf('PT%dH%dM', $hours, $minutes);  // ISO 8601

    if (!empty($data['trip_id'])) {
        $voyage = Trip::find((int)$data['trip_id']);
        $voyage->setDriverId((int)$data['driver_id']);
        $voyage->setStartCity($data['start_city']);
        $voyage->setEndCity($data['end_city']);
        $voyage->setDepartureAt(new DateTime($data['departure_at']));
        $voyage->setPricePerPassenger($data['price_per_passenger']);
        $voyage->setComment($data['comment']);
        $voyage->setNoSmoking(isset($data['no_smoking']));
        $voyage->setMusicAllowed(isset($data['music_allowed']));
        $voyage->setDiscussAllowed(isset($data['discuss_allowed']));
        $voyage->setEstimatedDuration($data['estimated_duration']);
    } else {
        $data['driver_id'] = $_SESSION['user_id'];
        $voyage = new Trip($data);

        try {
            // Débit automatique de 2 crédits
            CreditService::debitForTripPublication($userID);
        } catch (Exception $e) {
            $error = $e->getMessage();
            echo "<div class='alert alert-danger' role='alert'>Erreur : $error.
        <a href='paiements.php'>Ajoutez des crédits ici</a></div>";
            exit;
        }
    }

    if ($voyage->validateTrip()) {
        if ($voyage->saveToDatabase()) {
            $_SESSION['flash_success'] = 'Le voyage est enregistré !';
            $success = true;
            header('Location: /rechercher.php');
            exit;
        } else {
            $_SESSION['flash_error'] = 'Une erreur est survenue lors de l\'enregistrement du voyage.';
        }
    } else {
        $_SESSION['flash_error'] = 'Le voyage n\'est pas valide.';
    }
}
$voyage = Trip::findTripsByDriver($userID);

// Récupère la liste des véhicules de l'utilisateur connecté
$vehicles = Car::findByUser($userID);
if (empty($vehicles)) {
    $_SESSION['flash_error'] = "Vous devez enregistrez un véhicule avant de pouvoir proposer un trajet.";
    header('Location: profil.php');
    exit;
}

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
    <title>Proposer un trajet - EcoRide</title>
</head>

<body>
<header>
    <nav class="navbar bg-body-tertiary">
        <div class="container" style="max-width: 900px;">
            <a class="navbar-brand" href="index.php">
                <img src="assets/pictures/logoEcoRide.png" alt="Logo EcoRide" width="60" class="d-inline-block align-text-center rounded">
            </a>
            <h2 class="fw-bold mb-1 text-success">Proposer un trajet</h2>
            <?= displayInitialsButton(); ?>
        </div>
    </nav>
</header>

<main class="container px-3 py-2 mt-1 pt-5">
    <!-- Messages de succès/erreur -->
    <div class="alert alert-success alert-dismissible fade show d-none" role="alert" id="successAlert">
        <i class="bi bi-check-circle me-2"></i>
        <span id="successMessage"></span>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>

    <div class="alert alert-danger alert-dismissible fade show d-none" role="alert" id="errorAlert">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <span id="errorMessage"></span>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>

    <form action="" method="post" id="suggestedTripForm">

        <!-- Section 1: Itinéraire -->
        <section class="mt-4">
            <h3 class="fw-bold mb-4"><i class="bi bi-geo-alt text-success me-2"></i>Itinéraire</h3>
            <div class="p-4 bg-white rounded-4 shadow-sm mb-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="input-group bg-light rounded-3">
                            <span class="input-group-text bg-transparent border-0">
                                <i class="bi bi-geo-alt text-secondary"></i>
                            </span>
                            <input type="text" name="start_city" class="form-control border-0 bg-transparent"
                                   autocomplete="off" id="startCity" placeholder="Ville de départ" required>
                        </div>
                        <div id="startCitySuggestions" class="suggestion-box" style="display: none;"></div>
                    </div>
                    <div class="col-md-6">
                        <div class="input-group bg-light rounded-3">
                            <span class="input-group-text bg-transparent border-0">
                                <i class="bi bi-signpost-split text-secondary"></i>
                            </span>
                            <input type="text" name="start_location" class="form-control border-0 bg-transparent"
                                   id="startLocation" placeholder="Lieu de départ précis (ex: Parking gare de Lyon)" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="input-group bg-light rounded-3">
                            <span class="input-group-text bg-transparent border-0">
                                <i class="bi bi-pin-map text-secondary"></i>
                            </span>
                            <input type="text" name="end_city" class="form-control border-0 bg-transparent"
                                   autocomplete="off" id="endCity" placeholder="Ville de destination" required>
                        </div>
                        <div id="endCitySuggestions" class="suggestion-box" style="display: none;"></div>
                    </div>
                    <div class="col-md-6">
                        <div class="input-group bg-light rounded-3">
                            <span class="input-group-text bg-transparent border-0">
                                <i class="bi bi-signpost-split text-secondary"></i>
                            </span>
                            <input type="text" name="end_location" class="form-control border-0 bg-transparent"
                                   id="endLocation" placeholder="Lieu d'arrivée précis (ex: Entrée principale université)" required>
                        </div>
                    </div>
                </div>
                <small class="text-muted mt-2 d-block">
                    <i class="bi bi-plus-circle me-1"></i>
                    Ajouter un arrêt supplémentaire (fonctionnalité à venir)
                </small>
            </div>
        </section>

        <!-- Section 2: Date et Heure -->
        <section class="mt-4">
            <h3 class="fw-bold mb-4"><i class="bi bi-calendar-event text-success me-2"></i>Date et Heure</h3>
            <div class="p-4 bg-white rounded-4 shadow-sm mb-4">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="input-group bg-light rounded-3">
                                <span class="input-group-text bg-transparent border-0">
                                    <i class="bi bi-calendar-date text-secondary"></i>
                                </span>
                            <input type="date" name="departure_date" class="form-control border-0 bg-transparent"
                                   id="departureDate" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="input-group bg-light rounded-3">
                                <span class="input-group-text bg-transparent border-0">
                                    <i class="bi bi-clock text-secondary"></i>
                                </span>
                            <input type="time" name="departure_time" class="form-control border-0 bg-transparent"
                                   id="departureTime" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted small mb-1">
                            <i class="bi bi-hourglass-split me-1"></i>Durée estimée
                        </label>
                        <div class="row g-1">
                            <div class="col-6">
                                <select class="form-select bg-light border-0 rounded-3" name="duration_hours" required>
                                    <option value="" disabled selected>Heures</option>
                                    <?php for ($i = 0; $i <= 10; $i++): ?>
                                        <option value="<?= $i ?>"><?= $i ?>h</option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-6">
                                <select class="form-select bg-light border-0 rounded-3" name="duration_minutes" required>
                                    <option value="" disabled selected>Min</option>
                                    <option value="0">00</option>
                                    <option value="15">15</option>
                                    <option value="30">30</option>
                                    <option value="45">45</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section 3: Véhicule et Places -->
        <section class="mt-4">
            <h3 class="fw-bold mb-4"><i class="bi bi-car-front text-success me-2"></i>Véhicule et Places</h3>
            <div class="p-4 bg-white rounded-4 shadow-sm mb-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted small mb-2">Véhicule utilisé</label>
                        <select class="form-select bg-light border-0 rounded-3" name="vehicle_id" required>
                            <option value="" disabled selected>Choisissez votre véhicule</option>
                            <!-- Les options seront générées par PHP -->
                            <option value="1">Peugeot 308 - Essence</option>
                            <option value="2">Renault Zoé - Électrique</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small mb-2">Places disponibles</label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="available_seats" id="place1" value="1">
                            <label class="btn btn-outline-success flex-fill" for="place1">1</label>
                            <input type="radio" class="btn-check" name="available_seats" id="place2" value="2">
                            <label class="btn btn-outline-success flex-fill" for="place2">2</label>
                            <input type="radio" class="btn-check" name="available_seats" id="place3" value="3" checked>
                            <label class="btn btn-outline-success flex-fill" for="place3">3</label>
                            <input type="radio" class="btn-check" name="available_seats" id="place4" value="4">
                            <label class="btn btn-outline-success flex-fill" for="place4">4</label>
                            <input type="radio" class="btn-check" name="available_seats" id="place5" value="5">
                            <label class="btn btn-outline-success flex-fill" for="place5">5</label>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section 4: Prix -->
        <section class="mt-4">
            <h3 class="fw-bold mb-4"><i class="bi bi-currency-euro text-success me-2"></i>Prix</h3>
            <div class="p-4 bg-white rounded-4 shadow-sm mb-4">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label text-muted small mb-2">Prix par passager</label>
                        <div class="input-group bg-light rounded-3">
                            <input type="number" class="form-control border-0 bg-transparent text-center fw-bold"
                                   name="price_per_passenger" id="pricePerPassenger"
                                   placeholder="20" value="20" min="0" step="1" required>
                            <span class="input-group-text bg-transparent border-0 text-muted">crédits</span>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="alert alert-info border-0 bg-light mb-0">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong id="totalPrice">60</strong> crédits maximum pour ce trajet avec
                            <strong id="placeFree">3</strong> passagers
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section 5: Préférences et Commentaires -->
        <section class="mt-4">
            <h3 class="fw-bold mb-4"><i class="bi bi-gear text-success me-2"></i>Préférences et Commentaires</h3>
            <div class="p-4 bg-white rounded-4 shadow-sm mb-4">
                <div class="row g-3">
                    <div class="col-md-4">
                        <h6 class="text-muted mb-3">Préférences de voyage</h6>
                        <div class="d-flex flex-column gap-2">
                            <div class="form-check form-switch">
                                <input type="checkbox" name="no_smoking" class="form-check-input" id="no-smoking" checked>
                                <label class="form-check-label" for="no-smoking">
                                    <i class="bi bi-slash-circle me-1"></i>Non-fumeur
                                </label>
                            </div>
                            <div class="form-check form-switch">
                                <input type="checkbox" name="music_allowed" class="form-check-input" id="musicPlay" checked>
                                <label class="form-check-label" for="musicPlay">
                                    <i class="bi bi-music-note me-1"></i>Musique autorisée
                                </label>
                            </div>
                            <div class="form-check form-switch">
                                <input type="checkbox" name="discuss_allowed" class="form-check-input" id="discussTogether" checked>
                                <label class="form-check-label" for="discussTogether">
                                    <i class="bi bi-chat-dots me-1"></i>Discussions bienvenues
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <h6 class="text-muted mb-3">Commentaire pour les passagers</h6>
                        <div class="form-floating">
                                <textarea name="comment" class="form-control bg-light border-0"
                                          id="commentForPassenger" style="height: 120px"
                                          placeholder="Ajoutez des informations utiles pour vos futurs passagers..."></textarea>
                            <label for="commentForPassenger" class="text-muted">
                                Ex: Je pars du parking de la gare de Lyon, n'hésitez pas à me contacter...
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Bouton de soumission -->
        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4 mb-5">
            <button type="button" class="btn btn-success btn-lg px-4 rounded-3" id="publishSuggestedForm">
                <i class="bi bi-plus-circle me-2"></i>Publier ce trajet
            </button>
        </div>
    </form>

    <!-- Modale de confirmation -->
    <div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="confirmationModalLabel">
                        <i class="bi bi-check-circle me-2"></i>Confirmer la publication de votre trajet
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>

                <div class="modal-body">
                    <div class="alert alert-info border-0">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Coût de publication :</strong> 2 crédits seront débités de votre compte pour publier ce trajet.
                    </div>
                    <div id="modalText"></div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>Annuler
                    </button>
                    <button type="button" class="btn btn-success" id="confirmSubmit">
                        <i class="bi bi-check-lg me-1"></i>Confirmer et publier
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="pt-5"></div>
</main>

<footer>
    <?php include 'footer.php'; ?>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
<script src="assets/js/cities-autocomplete.js"></script>
<script src="assets/js/proposer.js"></script>

</body>
</html>
