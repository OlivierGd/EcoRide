<?php

use Olivierguissard\EcoRide\Model\SuggestTrip;
use Olivierguissard\EcoRide\Model\Travels;

$pageTitle = 'Proposer un trajet - EcoRide';

require_once __DIR__ . '/../vendor/autoload.php';;
require_once __DIR__ . '/../src/Model/SuggestTrip.php';
$errors = null;
$success = false;
if (isset($_POST['suggestedStartCity'], $_POST['suggestedEndCity'], $_POST['proposalDate'],
    $_POST['proposalTime'], $_POST['places'], $_POST['priceRequested'], $_POST['commentForPassenger'])) {

    $voyage = new Travels(
            $_POST['suggestedStartCity'],
            $_POST['suggestedEndCity'],
            new DateTime($_POST['proposalDate']), // Conversion en DateTime
            $_POST['proposalTime'],
            $_POST['places'],
            $_POST['priceRequested'],
            $_POST['commentForPassenger']);


    if ($voyage->isValid()) {
        $suggestTrip = new SuggestTrip(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'voyages');
        $suggestTrip->addVoyage($voyage);
        $success = true;
        $_POST = [];
    } else {
        $errors = $voyage->getErrors();
    }
}
//$voyage = $suggestTrip->getVoyages();
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
    <nav class="navbar bg-white fixed-top shadow-sm">
        <div class="container px-3" style="max-width: 900px">
            <a href="index.php" class="navbar-brand d-flex align-items-center">
                <img src="assets/pictures/logoEcoRide.png" alt="logo EcoRide" class="d-inline-block align-text-center rounded" width="60">

            </a>
            <span class="navbar-text fw-medium">Proposer un trajet</span>
            <a class="btn btn-success" role="button" href="login.php">Connexion</a>
        </div>
    </nav>
<!-- Formulaire Multi-Étapes -->
    <main class="pt-5 mt-5">
        <form action="" method="post" id="suggestedTripForm" class="multi-step-form">
            <!-- Étapes contrôlées par radio -->
            <input type="radio" name="step" id="step1" checked hidden>
            <input type="radio" name="step" id="step2" hidden>
            <input type="radio" name="step" id="step3" hidden>
            <input type="radio" name="step" id="step4" hidden>

            <div class="steps container py-4">

                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger" role="alert">
                    Formulaire invalide
                </div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success" role="alert">
                        Merci d'avoir publié votre trajet !
                    </div>
                <?php endif; ?>
                <!-- Barre de progression -->
                <div class="progress mb-4">
                    <div class="progress-bar bg-success" role="progressbar" style="width: 25%;" id="progressBar"></div>
                </div>

                <!-- Étape 1 : Itinéraire -->
                <div class="step step1">
                    <h2>1. Itinéraire</h2>
                    <div class="mb-3">
                        <label for="suggestedStartCity" class="form-label"><i class="bi bi-geo-alt"></i> Ville de départ</label>
                        <input type="text" name="suggestedStartCity"  class="form-control ps-5" id="suggestedStartCity" placeholder="Départ" required>
                        <?php if (isset($errors['suggestedStartCity'])): ?>
                        <div class="invalid-feedback"><?= $errors['suggestedStartCity'] ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label for="suggestedEndCity" class="form-label"><i class="bi bi-pin-map"></i> Destination</label>
                        <input type="text" name="suggestedEndCity" class="form-control ps-5" id="suggestedEndCity" placeholder="Destination" required>
                        <?php if (isset($errors['suggestedEndCity'])): ?>
                            <div class="invalid-feedback"><?= $errors['suggestedEndCity'] ?></div>
                        <?php endif; ?>
                    </div>
                    <p>+ Ajouter un arrêt supplémentaire</p>
                </div>

                <!-- Étape 2 : Date/Heure -->
                <div class="step step2">
                    <h2>2. Date et Heure</h2>
                    <div class="mb-3">
                        <label for="proposalDate" class="form-label">Date</label>
                        <input type="date" name="proposalDate" class="form-control" id="proposalDate" required>
                        <?php if (isset($errors['proposalDate'])): ?>
                            <div class="invalid-feedback"><?= $errors['proposalDate'] ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label for="proposalTime" class="form-label">Heure</label>
                        <input type="time" name="proposalTime" class="form-control" id="proposalTime" required>
                        <?php if (isset($errors['proposalTime'])): ?>
                            <div class="invalid-feedback"><?= $errors['proposalTime'] ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Étape 3 : Places/Prix -->
                <div class="step step3">
                    <h2>3. Places et Prix</h2>
                    <h3>Nombre de places disponibles</h3>
                    <div class="mb-3">
                        <div class="btn-group" role="group">
                            <input type="radio" class="btn-check placeAvailable" name="places" id="place1" value="1">
                            <label class="btn btn-outline-success" for="place1">1</label>
                            <input type="radio" class="btn-check placeAvailable" name="places" id="place2" value="2">
                            <label class="btn btn-outline-success" for="place2">2</label>
                            <input type="radio" class="btn-check placeAvailable" name="places" id="place3" value="3" checked>
                            <label class="btn btn-outline-success" for="place3">3</label>
                            <input type="radio" class="btn-check placeAvailable" name="places" id="place4" value="4">
                            <label class="btn btn-outline-success" for="place4">4</label>
                            <input type="radio" class="btn-check placeAvailable" name="places" id="place5" value="5">
                            <label class="btn btn-outline-success" for="place5">5</label>
                        </div>
                    </div>
                    <h3>Prix par passager</h3>
                    <div class="input-group flex-nowrap mb-3">
                        <span class="input-group-text">Crédits</span>
                        <input type="number" class="form-control" name="priceRequested" id="priceRequested" placeholder="--" value="20" min="0" step="1" required>
                    </div>
                    <p>Jusqu'à <strong id="totalPrice"></strong> crédits pour ce trajet avec <strong id="placeFree"></strong> passagers</p>
                </div>

                <!-- Étape 4 : Options -->
                <div class="step step4">
                    <h2>4. Options</h2>
                    <h3>Préférences</h3>
                    <div class="form-check form-switch">
                        <input type="checkbox" name="no-smoking" class="form-check-input"  id="no-smoking" checked>
                        <label class="form-check-label" for="no-smoking">Non-fumeur</label>
                    </div>
                    <div class="form-check form-switch">
                        <input type="checkbox" name="musicPlay" class="form-check-input"  id="musicPlay" checked>
                        <label class="form-check-label" for="musicPlay">Musique autorisée</label>
                    </div>
                    <div class="form-check form-switch">
                        <input type="checkbox" name="discussTogether" class="form-check-input"  id="discussTogether" checked>
                        <label class="form-check-label" for="discussTogether">Discussions bienvenues</label>
                    </div>
                    <h3>Commentaire pour les passagers</h3>
                    <div class="form-floating mb-3">
                        <textarea name="commentForPassenger" class="form-control" id="commentForPassenger" style="height: 100px"></textarea>
                        <label for="commentForPassenger">Ex: Je pars du parking de la gare de Lyon...</label>
                    </div>
                </div>
            </div>
            <button type="button" class="btn btn-lg btn-success" id="publishSuggestedForm">Publier ce trajet</button> <!-- boutton de type button pour ne pas soumettre le formulaire. -->
            <div class="p-5"></div>
        </form>
        <section>
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
</body>

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

<script src="assets/js/proposer.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
</body>
</html>
