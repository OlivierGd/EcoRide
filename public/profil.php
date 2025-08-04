<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Olivierguissard\EcoRide\Model\Trip;
use Olivierguissard\EcoRide\Model\Users;

require_once 'functions/auth.php';
startSession();
requireAuth();

require_once __DIR__ . '/../src/Helpers/helpers.php';

// Vérification de la session
if (!isset($_SESSION['connecte']) || !$_SESSION['connecte']) {
    header('Location: login.php');
    exit;
}

// Affiche une alerte une fois si le compte est créé
if (isset($_SESSION['update_success'])) {
    echo '<div class="alert alert-success mt-5" role="alert">' . $_SESSION['update_success'] . '</div>';
    unset($_SESSION['update_success']);
}

// Rôles user :
$roles = [
        0 => 'Passager',
        1 => 'Chauffeur',
        2 => 'Gestionnaire',
        3 => 'Administrateur',
];
$roleLabel = $roles[$_SESSION['role']] ?? 'Passager';

$userId = getUserId();
$passengerTrips = Trip::findTripsUpcoming($userId);
$driverTrips = Trip::findUpcomingByDriver($userId);
$passengerTrips = Trip::findTripsUpcomingByPassenger($userId);
$allTrips = array_merge(array_map(fn($t)=>['trip'=>$t, 'role'=>'chauffeur'], $driverTrips), array_map(fn($t)=>['trip'=>$t, 'role'=>'passager'], $passengerTrips));
$credits = Users::getUsersCredits($userId);
$pageTitle = 'Mon profil - EcoRide';

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="assets/pictures/logoEcoRide.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/profil.css">
    <title><?php if (isset($pageTitle)) { echo $pageTitle; } else { echo 'EcoRide - Covoiturage écologique';} ?></title>
</head>
<body>
    <header>
        <nav class="navbar bg-body-tertiary">
            <div class="container" style="max-width: 900px;">
                <a class="navbar-brand" href="/index.php">
                    <img src="assets/pictures/logoEcoRide.png" alt="Logo EcoRide" width="60" class="d-inline-block align-text-center rounded">
                </a>
                <h2 class="fw-bold mb-1 text-success">Mon profil</h2>
                <?= displayInitialsButton(); ?>
            </div>
        </nav>
    </header>

<main>
    <!-- Profil utilisateur -->
    <section>
        <div class="container mt-5 pt-5">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="profile-container text-center pb-4 border-bottom">
                        <!-- Cercle avec initiales ou photo -->
                        <div class="rounded-circle mx-auto mb-3 d-flex justify-content-center align-items-center shadow"
                             style="width: 110px; height: 110px; background: linear-gradient(135deg, #4ade80, #22c55e);">
                            <?php if (!empty($_SESSION['profilePicture'])) : ?>
                                <img src="<?= htmlspecialchars($_SESSION['profilePicture']) ?>"
                                     alt="Photo de profil" class="img-fluid rounded-circle"
                                     style="width: 100%; height: 100%; object-fit: cover;">
                            <?php else : ?>
                                <span class="fs-1 fw-bold text-white">
                                    <?= strtoupper($_SESSION['firstName'][0] . $_SESSION['lastName'][0]) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <h3 class="fw-bold mt-2"><?= htmlspecialchars($_SESSION['firstName'] . ' ' . $_SESSION['lastName']) ?></h3>
                        <!-- Stars + badge -->
                        <div class="d-flex justify-content-center align-items-center mb-2">
                            <?= renderStarsAndRanking((float)($_SESSION['ranking'] ?? 0)); ?>
                        </div>
                        <span class="badge bg-success mb-2"><?= $roleLabel ?> vérifié</span><br>
                        <button class="btn btn-outline-success btn-sm mt-1" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                            <i class="bi bi-pencil"></i> Modifier le profil
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Statistiques -->
    <section class="bg-light py-4">
        <div class="container">
            <div class="row text-center">
                <div class="col-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <i class="bi bi-ev-front text-success fs-2"></i>
                            <div class="fw-bold mt-2">47</div>
                            <div class="text-muted small">Trajets effectués</div>
                        </div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <i class="bi bi-leaf text-success fs-2"></i>
                            <div class="fw-bold mt-2">128 kg</div>
                            <div class="text-muted small">CO₂ économisés</div>
                        </div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <i class="bi bi-currency-euro text-success fs-2"></i>
                            <div class="fw-bold mt-2"><?= htmlspecialchars($credits) ?></div>
                            <div class="text-muted small">Solde de crédits</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Trajets à venir -->
    <section>
        <div class="container mt-4">
            <h4 class="mb-3 text-success"><i class="bi bi-calendar-event"></i> Trajets à venir</h4>
            <?php if (empty($allTrips)): ?>
                <div class="alert alert-light text-center">Aucun trajet à venir.</div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach($allTrips as $item):
                        $t = $item['trip'];
                        $role = $item['role'];
                        ?>
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="card h-100 border-0 shadow">
                                <div class="card-body d-flex flex-column">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <small class="text-muted"><?= $t->getDepartureDateFr() ?> · <?= $t->getDepartureTime() ?></small>
                                        <span class="badge <?= $role==='chauffeur' ? 'bg-primary' : 'bg-success' ?>">
                                            <?= $role==='chauffeur' ? 'Je conduis' : 'Passager' ?>
                                        </span>
                                    </div>
                                    <div>
                                        <span class="fw-semibold"><?= htmlspecialchars($t->getStartCity()) ?></span>
                                        <i class="bi bi-arrow-right mx-1"></i>
                                        <span class="fw-semibold"><?= htmlspecialchars($t->getEndCity()) ?></span>
                                    </div>
                                    <div class="mt-auto text-end">
                                        <span class="fw-bold"><?= $t->getPricePerPassenger() ?> crédits</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Préférences écologiques -->
    <section>
        <div class="container my-5">
            <h4 class="mb-3 text-success"><i class="bi bi-leaf"></i> Préférences écologiques</h4>
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="row gy-3">
                        <!-- Par préférence -->
                        <div class="col-6 col-md-3">
                            <div class="d-flex flex-column align-items-center">
                                <i class="bi bi-chat-left-dots-fill text-success fs-3"></i>
                                <div class="mt-1 fw-semibold">Conversation</div>
                                <span class="small text-muted">J'aime discuter</span>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="d-flex flex-column align-items-center">
                                <i class="bi bi-music-note-beamed text-success fs-3"></i>
                                <div class="mt-1 fw-semibold">Musique</div>
                                <span class="small text-muted">Ambiance musicale</span>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="d-flex flex-column align-items-center">
                                <i class="bi bi-github text-success fs-3"></i>
                                <div class="mt-1 fw-semibold">Animaux</div>
                                <span class="small text-muted">Petits animaux ok</span>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="d-flex flex-column align-items-center">
                                <i class="bi bi-slash-circle text-success fs-3"></i>
                                <div class="mt-1 fw-semibold">Non-fumeur</div>
                                <span class="small text-muted">Trajet sain</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Paramètres du compte -->
    <section>
        <div class="container mb-5">
            <h4 class="mb-3 text-success"><i class="bi bi-gear"></i> Paramètres du compte</h4>
            <div class="list-group shadow-sm" style="gap: 0.5rem; display: flex; flex-direction: column;">
                <a href="#" class="list-group-item list-group-item-action d-flex align-items-center">
                    <i class="bi bi-person-circle fs-4 me-2 text-success"></i>
                    <div>
                        <strong>Informations personnelles</strong>
                        <div class="small text-muted">Nom, e-mail, photo...</div>
                    </div>
                </a>
                <a href="historique.php" class="list-group-item list-group-item-action d-flex align-items-center">
                    <i class="bi bi-clock-history fs-4 me-2 text-success"></i>
                    <div>
                        <strong>Mes trajets</strong>
                        <div class="small text-muted">Historique et réservations</div>
                    </div>
                </a>
                <a href="vehicule.php" class="list-group-item list-group-item-action d-flex align-items-center">
                    <i class="bi bi-car-front-fill fs-4 me-2 text-primary"></i>
                    <div>
                        <strong>Mes véhicules</strong>
                        <div class="small text-muted">Ajouter, gérer</div>
                    </div>
                </a>
                <a href="paiements.php" class="list-group-item list-group-item-action d-flex align-items-center">
                    <i class="bi bi-credit-card-2-front-fill fs-4 me-2 text-warning"></i>
                    <div>
                        <strong>Paiements</strong>
                        <div class="small text-muted">Mes transactions</div>
                    </div>
                </a>
                <a href="dashboard.php" class="list-group-item list-group-item-action d-flex align-items-center">
                    <i class="bi bi-kanban fs-4 me-2 text-primary"></i>
                    <div>
                        <strong>Console d'administration</strong>
                        <div class="small text-muted">Management</div>
                    </div>
                </a>
                <!-- ESPACE pour le footer -->
                <div style="height: 1.5rem;"></div>
                <a href="/logout.php" class="list-group-item list-group-item-action d-flex align-items-center bg-danger text-white rounded mb-3 mt-auto">
                    <i class="bi bi-box-arrow-right fs-4 me-2"></i>
                    <strong>Se déconnecter</strong>
                </a>
            </div>
            <!-- Ajoute une marge basse supplémentaire pour dégager le bouton du footer -->
            <div style="height: 80px;"></div>
        </div>
    </section>


    <!-- Modale pour modifier le profil -->
    <div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editProfileModalLabel">Modifier vos données</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="updateProfile.php" id="editProfileForm" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="firstName" class="form-label"><b>Prénom :</b></label>
                            <input type="text" class="form-control" id="firstName" name="firstName" value="<?= htmlspecialchars($_SESSION['firstName']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="lastName" class="form-label"><b>Nom :</b></label>
                            <input type="text" class="form-control" id="lastName" name="lastName" value="<?= htmlspecialchars($_SESSION['lastName']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label"><b>Email :</b></label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($_SESSION['email']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="profilePicture" class="form-label"><b>Photo de Profil :</b></label>
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
    <?php include 'footer.php'; ?>
</footer>

<script src="assets/js/profil.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
</body>
</html>
