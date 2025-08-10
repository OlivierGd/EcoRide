<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Olivierguissard\EcoRide\Model\Car;

require_once 'functions/auth.php';
startSession();
requireAuth();
updateActivity();

require_once __DIR__ . '/../src/Model/Car.php';
require_once __DIR__ . '/../src/Helpers/helpers.php';


// Si le formulaire est soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log('Données POST reçues : ' . print_r($_POST, true));

    if (!empty($_POST['id_vehicule'])) {
        $data = array_intersect_key($_POST, array_flip(['id_vehicule', 'marque', 'modele', 'type_carburant', 'nbr_places', 'plaque_immatriculation']));
        $voiture = Car::find((int)$data['id_vehicule']);

        $voiture->marque = htmlspecialchars(trim($data['marque']));
        $voiture->modele = trim($data['modele']);
        $voiture->carburant = trim($data['type_carburant']);
        $voiture->places = (int)$data['nbr_places'];
        $voiture->immatriculation = trim($data['plaque_immatriculation']);

        if ($voiture->validateCar()) {
            if ($voiture->saveToDatabase()) {
                $_SESSION['flash_success'] = 'Véhicule modifié !';
            } else {
                $_SESSION['flash_error'] = 'Erreur lors de la modification du véhicule';
            }
        } else {
            $_SESSION['flash_error'] = implode('<br>', $voiture->errors);
        }
    } else {
        // Ajout d'un nouveau véhicule
        $data = array_intersect_key($_POST, array_flip(['marque', 'modele', 'type_carburant', 'nbr_places', 'plaque_immatriculation']));
        $voiture = new Car($_POST);
        $voiture->user_id = getUserId();

        if ($voiture->validateCar()) {
            if ($voiture->saveToDatabase()) {
                $_SESSION['flash_success'] = 'Véhicule ajouté avec succès !';
            } else {
                $_SESSION['flash_error'] = implode('<br>', $voiture->errors);
                $_SESSION['form_data'] = $_POST;
            }
        } else {
            $_SESSION['flash_error'] = implode('<br>', $voiture->errors);
            $_SESSION['form_data'] = $_POST;
        }
        header('Location: vehicule.php');
        exit;
    }

}
$vehicules = Car::findByUser($_SESSION['user_id']);

$pageTitle = 'Mes véhicules';
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
    <header>
        <nav class="navbar bg-body-tertiary">
            <div class="container" style="max-width: 900px;">
                <a class="navbar-brand" href="index.php">
                    <img src="assets/pictures/logoEcoRide.png" alt="Logo EcoRide" width="60" class="d-inline-block align-text-center rounded">
                </a>
                <h2 class="fw-bold mb-1 text-success">Mes véhicules</h2>
                <?= displayInitialsButton(); ?>
            </div>
        </nav>
    </header>
</header>

<main>
    <div class="container my-3">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <button class="btn btn-link text-dark p-0"><a href="profil.php"><i class="bi bi-chevron-left fs-5"></i></a></button>
            <button class="btn btn-link text-success p-0" data-bs-toggle="modal" data-bs-target="#ajoutVehiculeModal"><i class="bi bi-plus fs-4"></i></button>
        </div>

        <!-- Affiche la liste des véhicules enregistrés par l'utilisateur -->
        <?php if (!empty($_SESSION['flash_success'])) : ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $_SESSION['flash_success'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($_SESSION['flash_error']) && empty($_SESSION['form_data'])) : ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= $_SESSION['flash_error']; unset($_SESSION['flash_error']); ?>
        </div>
        <?php endif; ?>

        <?php foreach ($vehicules as $vehicule) : ?>
        <div class="card mb-3 border-0 shadow-sm">
            <div class="card-body d-flex align-items-center">
                <div class="me-3 bg-light rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                    <i class="bi bi-car-front-fill text-primary fs-5"></i>
                </div>
                <div class="flex-grow-1">
                    <h6 class="mb-1 fw-semibold"><?= htmlspecialchars($vehicule->marque . ' ' . $vehicule->modele) ?></h6>
                    <div class="d-flex align-items-center flex-wrap">
                        <span class="badge bg-success-subtle text-success border border-success me-2"><?= htmlspecialchars($vehicule->carburant) ?></span>
                        <small class="text-muted me-2">Places disponibles max : <?= htmlspecialchars($vehicule->places) ?></small>
                        <small class="text-muted">Immatriculation : <?= htmlspecialchars($vehicule->immatriculation) ?></small>
                    </div>
                </div>
                <div class="ms-2">
                    <button type="button"
                            class="btn btn-link text-muted p-0"
                            data-bs-toggle="modal"
                            data-bs-target="#editVehiculeModal"
                            data-id="<?= htmlspecialchars($vehicule->id) ?>"
                            data-marque="<?= htmlspecialchars($vehicule->marque) ?>"
                            data-modele="<?= htmlspecialchars($vehicule->modele) ?>"
                            data-carburant="<?= htmlspecialchars($vehicule->carburant) ?>"
                            data-places="<?= htmlspecialchars($vehicule->places) ?>"
                            data-immatriculation="<?= htmlspecialchars($vehicule->immatriculation) ?>">
                        <i class="bi bi-three-dots-vertical fs-4"></i>
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>



        <!-- Bouton flottant pour ajouter un véhicule -->
        <button class="btn btn-success rounded-circle position-fixed start-50 translate-middle-x bottom-1 end-0 m-4 shadow" data-bs-toggle="modal" data-bs-target="#ajoutVehiculeModal" style="width: 56px; height: 56px;">
            <i class="bi bi-plus-lg fs-4"></i>
        </button>

        <!-- Modale : ajout d'un véhicule -->
        <div class="modal fade" id="ajoutVehiculeModal" tabindex="-1" aria-labelledby="ajoutVehiculeLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content rounded-4">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold" id="ajoutVehiculeLabel">Ajouter un véhicule</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <?php if (!empty($_SESSION['form_data']) && !empty($_SESSION['flash_error'])) : ?>
                        <div class="alert alert-danger" role="alert">
                            <?= htmlspecialchars($_SESSION['flash_error']) ?>
                        </div>
                        <?php endif; ?>
                        <form action="vehicule.php" method="post" id="formAjoutVehicule" enctype="multipart/form-data">

                            <!-- Marque -->
                            <div class="mb-3">
                                <label for="marqueVehicule" class="form-label">Marque du véhicule :</label>
                                <input type="text" class="form-control" id="marqueVehicule" name="marque"
                                       value="<?= htmlspecialchars($_SESSION['form_data']['marque'] ?? '') ?>" placeholder="Ex: Tesla">
                            </div>

                            <!-- Modèle -->
                            <div class="mb-3">
                                <label for="modeleVehicule" class="form-label">Modèle du véhicule :</label>
                                <input type="text" class="form-control" id="modeleVehicule" name="modele"
                                       value="<?= htmlspecialchars($_SESSION['form_data']['modele'] ?? '') ?>" placeholder="Ex: Model 3">
                            </div>

                            <!-- Type -->
                            <div class="mb-3">
                                <label for="typeVehicule" class="form-label">Énergie utilisée :</label>
                                <select class="form-select" id="typeVehicule" name="type_carburant">
                                    <option value="" selected disabled>Type d'énergie</option>
                                    <option value="Electrique"<?= ($_SESSION['form_data']['type_carburant'] ?? '') === 'Electrique' ? 'selected' : '' ?>>Électrique</option>
                                    <option value="Hybride"<?= ($_SESSION['form_data']['type_carburant'] ?? '') === 'Hybride' ? 'selected' : '' ?>>Hybride</option>
                                    <option value="Essence"<?= ($_SESSION['form_data']['type_carburant'] ?? '') === 'Essence' ? 'selected' : '' ?>>Essence</option>
                                    <option value="Gasoil"<?= ($_SESSION['form_data']['type_carburant'] ?? '') === 'Gasoil' ? 'selected' : '' ?>>Gasoil</option>
                                </select>
                            </div>

                            <!-- Plaque -->
                            <div class="mb-3">
                                <label for="plaqueVehicule" class="form-label">Plaque d'immatriculation :</label>
                                <input type="text" class="form-control" id="plaqueVehicule" name="plaque_immatriculation"
                                       value="<?= htmlspecialchars($_SESSION['form_data']['plaque_immatriculation'] ?? '') ?>" placeholder="Ex: AB-123-CD">
                            </div>

                            <!-- Nombre de places -->
                            <div class="mb-3">
                                <label for="nbPlaces" class="form-label">Nombre de places :</label>
                                <select class="form-select" id="nbPlaces" name="nbr_places">
                                    <option value="" selected disabled>Choisissez</option>
                                    <option value="1" <?= ($_SESSION['form_data']['nbr_places'] ?? '') === '1' ? 'selected' : '' ?>>1</option>
                                    <option value="2" <?= ($_SESSION['form_data']['nbr_places'] ?? '') === '2' ? 'selected' : '' ?>>2</option>
                                    <option value="3" <?= ($_SESSION['form_data']['nbr_places'] ?? '') === '3' ? 'selected' : '' ?>>3</option>
                                    <option value="4" <?= ($_SESSION['form_data']['nbr_places'] ?? '') === '4' ? 'selected' : '' ?>>4</option>
                                    <option value="5" <?= ($_SESSION['form_data']['nbr_places'] ?? '') === '5' ? 'selected' : '' ?>>5</option>
                                </select>
                            </div>
                        </form>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" form="formAjoutVehicule" class="btn btn-success w-100">Ajouter</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modale : édite un véhicule -->
    <div class="modal fade" id="editVehiculeModal" tabindex="-1" aria-labelledby="editVehiculeLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content rounded-4">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="editVehiculeLabel">Modifier le véhicule</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <form id="formEditVehicule" method="post" action="vehicule.php">
                    <div class="modal-body">
                        <!-- Ne pas afficher l’ID -->
                        <input type="hidden" name="id_vehicule" id="edit-id">

                        <div class="mb-3">
                            <label for="edit-marque" class="form-label">Marque : </label>
                            <input type="text" class="form-control" name="marque" id="edit-marque" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit-modele" class="form-label">Modèle : </label>
                            <input type="text" class="form-control" name="modele" id="edit-modele" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit-carburant" class="form-label">Carburant : </label>
                            <select class="form-select" name="type_carburant" id="edit-carburant" required>
                                <option value="Electrique">Électrique</option>
                                <option value="Hybride">Hybride</option>
                                <option value="Essence">Essence</option>
                                <option value="Gasoil">Gasoil</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit-places" class="form-label">Nombre de places : </label>
                            <select class="form-select" name="nbr_places" id="edit-places" required>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <option value="<?= $i ?>"><?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit-immatriculation" class="form-label">Immatriculation</label>
                            <input type="text" class="form-control" name="plaque_immatriculation" id="edit-immatriculation" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success w-100">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>
<?php require_once 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
<script src="assets/js/vehicule.js"></script>
<?php if (isset($_SESSION['form_data'])) : ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = new bootstrap.Modal(document.getElementById('ajoutVehiculeModal'));
            modal.show();
        });
    </script>
<?php endif; ?>
<?php unset($_SESSION['form_data'], $_SESSION['flash_success'], $_SESSION['flash_error']); ?>
</body>
</html>
