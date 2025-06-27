<?php
require_once 'functions/auth.php';
utilisateur_connecte();

$pageTitle = 'Mon profil - EcoRide';

// Vérification de la session
if (!isset($_SESSION['connecte']) || !$_SESSION['connecte']) {
    header('Location: login.php');
    exit;
}

if (!empty($_SESSION['success_registration'])) {
    echo '<div class="alert alert-success text-center" role="alert">Votre compte a bien été créé !</div>';
    unset($_SESSION['success_registration']);
}

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
    <title><?php /*if (isset($pageTitle)) { echo $pageTitle; } else { echo 'EcoRide - Covoiturage écologique';} */?></title>
</head>
<body>
<nav class="navbar bg-body-tertiary">
    <div class="container" style="max-width: 900px;">
        <a class="navbar-brand" href="/index.php">
            <img src="assets/pictures/logoEcoRide.png" alt="Logo EcoRide" width="60" class="rounded">
        </a>
        <h2>Mon Profil</h2>
        <a href="/logout.php">Se déconnecter</a>
    </div>
</nav>


<main>

    <!-- Section Profil personne connectée -->
    <section>
        <div class="container mt-5 pt-5">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="profile-container text-center">

                        <!-- Cercle avec initiales ou photo -->
                        <div class="rounded-circle d-flex justify-content-center align-items-center mx-auto bg-secondary text-white shadow mb-3"
                             style="width: 100px; height: 100px; font-size: 32px; font-weight: bold; overflow: hidden;">
                            <?php if (!empty($_SESSION['profilePicture'])) : ?>
                                <img src="<?= htmlspecialchars($_SESSION['profilePicture']) ?>"
                                     alt="Photo de profil"
                                     class="img-fluid rounded-circle"
                                     style="width: 100%; height: 100%; object-fit: cover;">
                            <?php else : ?>
                                <?= strtoupper($_SESSION['firstName'][0] . $_SESSION['lastName'][0]) ?>
                            <?php endif; ?>
                        </div>

                        <!-- Nom complet -->
                        <h3><?= htmlspecialchars($_SESSION['firstName'] . ' ' . $_SESSION['lastName']) ?></h3>

                        <!-- Affichage du ranking en étoiles -->
                        <div class="d-flex justify-content-center align-items-center mb-2">
                            <?php
                            $fullStars = floor($_SESSION['ranking']);
                            $halfStar = ($_SESSION['ranking'] - $fullStars) >= 0.5;
                            for ($i = 0; $i < $fullStars; $i++) {
                                echo '<i class="bi bi-star-fill text-warning"></i>';
                            }
                            if ($halfStar) {
                                echo '<i class="bi bi-star-half text-warning"></i>';
                            }
                            $emptyStars = 5 - $fullStars - ($halfStar ? 1 : 0);
                            for ($i = 0; $i < $emptyStars; $i++) {
                                echo '<i class="bi bi-star text-warning"></i>';
                            }
                            ?>
                            <span class="ms-2">(<?= number_format($_SESSION['ranking'], 1) ?>)</span>
                        </div>

                        <!-- Badge rôle -->
                        <div class="mb-3">
                            <?php
                            $roles = [
                                0 => 'Utilisateur',
                                1 => 'Conducteur',
                                2 => 'Gestionnaire',
                                3 => 'Administrateur'
                            ];
                            $roleLabel = $roles[$_SESSION['role']] ?? 'Inconnu';
                            ?>
                            <span class="badge bg-success"><?= $roleLabel ?> vérifié</span>
                        </div>

                        <!-- Bouton modifier -->
                        <button class="btn btn-sm" data-bs-toggle="modal" data-bs-target="#editProfileModal"><i class="fas fa-edit"></i><i class="bi bi-pencil"></i> Modifier le profil</button>
                        <?php if (isset($_SESSION['update_success'])) {
                            echo '<div class="alert alert-success mt-3" role="alert">' . $_SESSION['update_success'] . '</div>';
                            unset($_SESSION['update_success']);
                        }
                        ?>
                    </div>
                </div>

    </section>

    <!-- Stats track done -->
    <section>
        <h2>Statistiques</h2>
        <div class="row row-cols-1 row-cols-md-3 g-4 mb-4">
            <div class="col">
                <div class="card h-80">
                    <div class="card-body d-flex align-items-center flex-column">
                        <h5 class="card-title card-title rounded-circle bg-success d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;"><i class="bi bi-ev-front text-white"></i></h5>
                        <p class="card-text">Trajets effectués</p>
                        <p class="card-text"><strong>47</strong></p>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card h-80">
                    <div class="card-body d-flex align-items-center flex-column">
                        <h5 class="card-title card-title rounded-circle bg-success d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;"><i class="bi bi-person text-white"></i></h5>
                        <p class="card-text">Economies de CO2</p>
                        <p class="card-text"><strong>128 kg</strong></p>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card h-80">
                    <div class="card-body d-flex align-items-center flex-column">
                        <h5 class="card-title rounded-circle bg-success d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;"><i class="bi bi-leaf text-white"></i></h5>
                        <p class="card-text">Economies</p>
                        <p class="card-text"><strong>215 €</strong></p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Next Trip for connected user -->
    <section>
        <h2>Trajets à venir</h2>
        <div class="px-5 mb-8">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="h5 font-weight-semibold text-dark">Trajets à venir</h3>
                <a href="#" class="text-primary small font-weight-medium">Voir tout</a>
            </div>

            <div class="upcoming-trips d-flex overflow-auto pb-2">
                <div class="min-vw-280 bg-white rounded shadow-sm p-4 border border-light">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <p class="small font-weight-medium text-dark">Lundi, 13 mai</p>
                            <p class="small text-muted">Départ à 08:30</p>
                        </div>
                        <span class="px-2 py-1 bg-success text-white small rounded-pill">Confirmé</span>
                    </div>

                    <div class="d-flex align-items-center mb-3">
                        <div class="mr-3 d-flex flex-column align-items-center">
                            <div class="rounded-circle bg-primary" style="width: 12px; height: 12px;"></div>
                            <div class="bg-light" style="width: 2px; height: 40px;"></div>
                            <div class="rounded-circle bg-secondary" style="width: 12px; height: 12px;"></div>
                        </div>

                        <div>
                            <p class="small font-weight-medium text-dark">Lyon, Gare Part-Dieu</p>
                            <p class="small font-weight-medium text-dark mt-2">Grenoble, Centre-ville</p>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle overflow-hidden mr-2" style="width: 32px; height: 32px;">
                                <img src="#" alt="Conducteur" class="w-100 h-100">
                            </div>
                            <p class="small text-muted">Marie L.</p>
                        </div>
                        <p class="small font-weight-bold text-dark">12,50 €</p>
                    </div>
                </div>

                <div class="min-vw-280 bg-white rounded shadow-sm p-4 border border-light">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <p class="small font-weight-medium text-dark">Vendredi, 17 mai</p>
                            <p class="small text-muted">Départ à 17:15</p>
                        </div>
                        <span class="px-2 py-1 bg-warning text-dark small rounded-pill">En attente</span>
                    </div>

                    <div class="d-flex align-items-center mb-3">
                        <div class="mr-3 d-flex flex-column align-items-center">
                            <div class="rounded-circle bg-primary" style="width: 12px; height: 12px;"></div>
                            <div class="bg-light" style="width: 2px; height: 40px;"></div>
                            <div class="rounded-circle bg-secondary" style="width: 12px; height: 12px;"></div>
                        </div>

                        <div>
                            <p class="small font-weight-medium text-dark">Lyon, Part-Dieux</p>
                            <p class="small font-weight-medium text-dark mt-2">Annecy, Gare</p>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle overflow-hidden mr-2" style="width: 32px; height: 32px;">
                                <img src="#" alt="Conducteur" class="w-100 h-100">
                            </div>
                            <p class="small text-muted">Lucas B.</p>
                        </div>
                        <p class="small font-weight-bold text-dark">15,00 €</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!--Preference de covoiturage-->
    <section>
        <div class="px-5 mb-8">
            <h3 class="h6 mb-3">
                Préférences de covoiturage
            </h3>
            <div class="bg-white rounded shadow-sm border border-light">
                <div class="p-4 border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="mb-0 font-weight-medium">Conversation</p>
                            <p class="mb-0 text-muted">Je préfère discuter pendant le trajet</p>
                        </div>
                        <label class="d-flex align-items-center cursor-pointer">
                            <input type="checkbox" checked />
                            <span class="custom-checkbox"></span>
                        </label>
                    </div>
                </div>

                <div class="p-4 border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="mb-0 font-weight-medium">Musique</p>
                            <p class="mb-0 text-muted">J'aime écouter de la musique</p>
                        </div>
                        <label class="d-flex align-items-center cursor-pointer">
                            <input type="checkbox" checked />
                            <span class="custom-checkbox"></span>
                        </label>
                    </div>
                </div>

                <div class="p-4 border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="mb-0 font-weight-medium">Animaux acceptés</p>
                            <p class="mb-0 text-muted">Petits animaux en cage uniquement</p>
                        </div>
                        <label class="d-flex align-items-center cursor-pointer">
                            <input type="checkbox" />
                            <span class="custom-checkbox"></span>
                        </label>
                    </div>
                </div>

                <div class="p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="mb-0 font-weight-medium">Fumeur</p>
                            <p class="mb-0 text-muted">Je préfère les trajets non-fumeur</p>
                        </div>
                        <label class="d-flex align-items-center cursor-pointer">
                            <input type="checkbox" />
                            <span class="custom-checkbox"></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Paramètres du compte -->
    <section>
        <div class="pb-5">
            <h3>Paramètres du compte</h3>
            <div class="container my-4">
                <div class="list-group">
                    <a href="#" class="list-group-item list-group-item-action d-flex align-items-start">
                        <div class="me-3 text-success">
                            <i class="bi bi-geo-alt-fill fs-4"></i>
                        </div>
                        <div>
                            <h6 class="mb-1 fw-semibold">Informations personnelles</h6>
                            <small class="text-muted">Mes préférences</small>
                        </div>
                    </a>

                    <a href="#" class="list-group-item list-group-item-action d-flex align-items-start">
                        <div class="me-3 text-success">
                            <i class="bi bi-geo-alt-fill fs-4"></i>
                        </div>
                        <div>
                            <h6 class="mb-1 fw-semibold">Mes Trajets</h6>
                            <small class="text-muted">Trajets à venir et historique</small>
                        </div>
                    </a>

                    <a href="vehicule.php" class="list-group-item list-group-item-action d-flex align-items-start">
                        <div class="me-3 text-primary">
                            <i class="bi bi-car-front-fill fs-4"></i>
                        </div>
                        <div>
                            <h6 class="mb-1 fw-semibold">Mes Véhicules</h6>
                            <small class="text-muted">Gérer vos véhicules enregistrés</small>
                        </div>
                    </a>

                    <a href="#" class="list-group-item list-group-item-action d-flex align-items-start">
                        <div class="me-3 text-purple">
                            <i class="bi bi-credit-card-2-front-fill fs-4"></i>
                        </div>
                        <div>
                            <h6 class="mb-1 fw-semibold">Paiements et Transactions</h6>
                            <small class="text-muted">Méthodes de paiement et historique</small>
                        </div>
                    </a>

                    <a href="#" class="list-group-item list-group-item-action d-flex align-items-start">
                        <div class="me-3 text-success">
                            <i class="bi bi-leaf-fill fs-4"></i>
                        </div>
                        <div>
                            <h6 class="mb-1 fw-semibold">Préférences Écologiques</h6>
                            <small class="text-muted">Options de voyage écologique</small>
                        </div>
                    </a>

                    <a href="#" class="list-group-item list-group-item-action d-flex align-items-start">
                        <div class="me-3 text-secondary">
                            <i class="bi bi-question-circle-fill fs-4"></i>
                        </div>
                        <div>
                            <h6 class="mb-1 fw-semibold">Aide et Support</h6>
                            <small class="text-muted">Questions fréquentes et assistance</small>
                        </div>
                    </a>

                    <a href="/public/dashboard.php" class="list-group-item list-group-item-action d-flex align-items-start">
                        <div class="me-3 text-secondary">
                            <i class="bi bi-question-circle-fill fs-4"></i>
                        </div>
                        <div>
                            <h6 class="mb-1 fw-semibold">Dashboard</h6>
                            <small class="text-muted">Accès au tableau de suivis</small>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Modale pour modifier le profil -->
    <div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editProfileModalLabel">Modifier le Profil</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="updateProfile.php" id="editProfileForm" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="firstName" class="form-label">Prénom</label>
                            <input type="text" class="form-control" id="firstName" name="firstName" value="<?= htmlspecialchars($_SESSION['firstName']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="lastName" class="form-label">Nom</label>
                            <input type="text" class="form-control" id="lastName" name="lastName" value="<?= htmlspecialchars($_SESSION['lastName']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($_SESSION['email']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="profilePicture" class="form-label">Photo de Profil</label>
                            <input type="file" class="form-control" id="profilePicture" name="profilePicture">
                        </div>
                        <button type="submit" class="btn btn-success">Mettre à jour</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</main>

<footer>
    <nav class="navbar fixed-bottom bg-body-tertiary px-4">
        <div class="container d-flex justify-content-around text-center" style="max-width: 900px">
            <a class="nav-item nav-link d-flex flex-column" href="/public/index.php">
                <i class="bi bi-house fs-4"></i>
                <span>Accueil</span>
            </a>
            <a class="nav-item nav-link d-flex flex-column" href="/public/rechercher.php">
                <i class="bi bi-zoom-in fs-4"></i>
                <span>Rechercher</span>
            </a>
            <a class="nav-item nav-link d-flex flex-column" href="/public/proposer.php">
                <i class="bi bi-ev-front fs-4"></i>
                <span>Proposer</span>
            </a>
            <a class="nav-item nav-link d-flex flex-column" href="/public/profil.php">
                <i class="bi bi-person fs-4"></i>
                <span>Profil</span>
            </a>
        </div>
    </nav>
</footer>


<script src="assets/js/profil.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
</body>
</html>
