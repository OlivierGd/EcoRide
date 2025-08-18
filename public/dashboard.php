<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Olivierguissard\EcoRide\Config\Database;
use Olivierguissard\EcoRide\Model\Users;
use Olivierguissard\EcoRide\Model\CreditsHistory;
use Olivierguissard\EcoRide\Service\DateFilterService;

require_once 'functions/auth.php';
startSession();
requireAuth();
updateActivity();

$currentUserRole = (int)$_SESSION['role'] ?? 0;
$isAdmin = $currentUserRole === 3;
$isManagerOrAdmin = $currentUserRole >= 2;

if (!$isManagerOrAdmin) {
    header('Location: index.php');
    exit;
}

// === Commissions ===
$commissionsPreset = $_GET['commissions_preset'] ?? null;
if ($commissionsPreset) {
    $range = DateFilterService::getDateRangeFromPreset($commissionsPreset);
    $dateMin = $range['date_min'];
    $dateMax = $range['date_max'];
} else {
    $dateMin = $_GET['commissions_start'] ?? $_GET['date_min'] ?? null;
    $dateMax = $_GET['commissions_end'] ?? $_GET['date_max'] ?? null;
}

// === Trajets ===
$tripsPreset = $_GET['trips_preset'] ?? null;
if ($tripsPreset) {
    $rangeTrips = DateFilterService::getDateRangeFromPreset($tripsPreset);
    $tripDateMin = $rangeTrips['date_min'];
    $tripDateMax = $rangeTrips['date_max'];
} else {
    $tripDateMin = $_GET['trips_start'] ?? $_GET['date_min'] ?? null;
    $tripDateMax = $_GET['trips_end'] ?? $_GET['date_max'] ?? null;
    // Valeurs par défaut si aucune date n'est définie
    if (!$tripDateMin || !$tripDateMax) {
        $today = new DateTimeImmutable();
        $tripDateMin = $today->modify('-7 days')->format('Y-m-d');
        $tripDateMax = $today->modify('+7 days')->format('Y-m-d');
    }
}

// Récupération des commissions pour le tableau
$pdo = Database::getConnection();
$sql = "SELECT ch.*, u.firstname, u.lastname
        FROM credits_history ch
        JOIN users u ON u.user_id = ch.user_id
        WHERE ch.status = 'trajet_propose'";

$params = [];
if ($dateMin) {
    $sql .= " AND ch.created_at >= :date_min";
    $params[':date_min'] = $dateMin;
}
if ($dateMax) {
    $sql .= " AND ch.created_at <= :date_max";
    $params[':date_max'] = $dateMax;
}
$sql .= " ORDER BY ch.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$commissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Gain total collecté
$totalGagne = array_reduce($commissions, function ($carry, $item) {
    return $carry + abs((float)$item['amounts']);
}, 0);

// Graphique (groupées par mois)
$sqlGraph = "
    SELECT TO_CHAR(created_at, 'MM-YYYY') AS month,
           SUM(ABS(amounts)) AS total
    FROM credits_history
    WHERE status = 'trajet_propose'
";

if ($dateMin) {
    $sqlGraph .= " AND created_at >= :date_min_graph";
    $params[':date_min_graph'] = $dateMin;
}
if ($dateMax) {
    $sqlGraph .= " AND created_at <= :date_max_graph";
    $params[':date_max_graph'] = $dateMax;
}

$sqlGraph .= " GROUP BY month ORDER BY month ASC";
$stmtGraph = $pdo->prepare($sqlGraph);
$stmtGraph->execute(array_filter($params, fn($k) => str_contains($k, '_graph'), ARRAY_FILTER_USE_KEY));
$monthlyData = $stmtGraph->fetchAll(PDO::FETCH_ASSOC);

// Graphique groupé par trajets
$sqlGraphTrip = "SELECT 
        TO_CHAR(departure_at::date, 'DD TMMonth') AS day,
        COUNT(*) FILTER (WHERE status != 'annulé') AS valid_trips,
        COUNT(*) FILTER (WHERE status = 'annulé') AS cancelled_trips
    FROM trips
    WHERE departure_at::date BETWEEN :start AND :end
    GROUP BY departure_at::date
    ORDER BY departure_at::date ASC";
$stmtGraphTrip = $pdo->prepare($sqlGraphTrip);
$stmtGraphTrip->execute([
    ':start' => $tripDateMin,
    ':end' => $tripDateMax,
]);
$tripsByDay = $stmtGraphTrip->fetchAll(PDO::FETCH_ASSOC);
error_log('tripByDay: ' . print_r($tripsByDay, true));

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="assets/pictures/logoEcoRide.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <title>EcoRide - Console Admin</title>
</head>

<body>
    <!-- SIDEBAR FIXE -->
    <nav class="sidebar-fixed bg-dark text-white">
        <div>
            <div class="container">
                <img src="assets/pictures/logoEcoRide.png" alt="Logo EcoRide" class="img-fluid mb-3 rounded-1" style="width: 4em;">
                <h3 class="mb-3">EcoRide</h3>
            </div>
            <ul class="nav flex-column mt-2">
                <li class="nav-item"><a href="#users" class="nav-link">Utilisateurs</a></li>
                <?php if ($isAdmin): ?>
                    <li class="nav-item"><a href="#revenus" class="nav-link">Statistiques</a></li>
                <?php endif; ?>
                <li class="nav-item"><a href="#comments" class="nav-link">Commentaires</a></li>
            </ul>
        </div>

        <div class="pt-3 border-top border-secondary">
            <div class="small opacity-75 mb-1">
                <?= htmlspecialchars($_SESSION['firstName'][0] . ' ' . $_SESSION['lastName']) ?>
            </div>
            <a href="index.php" class="nav-link p-0">
                <i class="bi bi-box-arrow-left"></i> Retour
            </a>
        </div>
    </nav>

    <!-- CONTENU PRINCIPAL -->
    <main class="main-with-sidebar">
        <!-- HEADER -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Console d’administration</h1>
        </div>

        <!-- SECTION UTILISATEURS -->
        <section id="users" class="mb-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="text-success">Gestion des utilisateurs</h2>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createUserModal">
                    <i class="bi bi-person-plus"></i> Créer un utilisateur
                </button>
            </div>

            <!-- Recherche et filtres -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="position-relative">
                        <input type="text"
                               class="form-control"
                               id="searchUserInput"
                               placeholder="Rechercher par nom, prénom ou email (min. 3 caractères)"
                               autocomplete="off">
                        <div id="searchSuggestions" class="position-absolute w-100 bg-white border border-top-0 rounded-bottom shadow-sm" style="z-index: 1000; display: none; max-height: 200px; overflow-y: auto;"></div>
                    </div>
                </div>
                <div class="col-md-2">
                    <select class="form-select" id="roleFilter">
                        <option value="">Tous les rôles</option>
                        <option value="0">Passager</option>
                        <option value="1">Passager / Chauffeur</option>
                        <option value="2">Gestionnaire</option>
                        <option value="3">Administrateur</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" id="statusFilter">
                        <option value="">Tous les statuts</option>
                        <option value="actif">Actif</option>
                        <option value="inactif">Inactif</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-outline-secondary" id="resetFilters">
                        <i class="bi bi-arrow-clockwise"></i> Réinitialiser
                    </button>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary" id="searchButton">
                        <i class="bi bi-search"></i> Rechercher
                    </button>
                </div>
            </div>

            <!-- Résultats de recherche -->
            <div id="userResults" class="table-responsive" style="display: none;">
                <table class="table table-striped">
                    <thead class="table-dark">
                    <tr>
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Rôle</th>
                        <th>Statut</th>
                        <th>Crédits</th>
                        <th>Ranking</th>
                        <th>Créé le</th>
                        <th>Actions</th>
                    </tr>
                    </thead>
                    <tbody id="userTableBody">
                    <!-- Les résultats seront injectés ici -->
                    </tbody>
                </table>
            </div>

            <!-- Détails utilisateur sélectionné (gardé pour compatibilité avec l'ancien système) -->
            <div id="userDetails" style="display: none;"></div>
        </section>

        <!-- Modal Édition d'utilisateur -->
        <div class="modal fade" id="editUserModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Éditer l'utilisateur</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form id="editUserForm">
                        <input type="hidden" id="editUserId" name="user_id">
                        <div class="modal-body">
                            <!-- Informations personnelles -->
                            <h6 class="text-success mb-3">Informations personnelles</h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label for="editFirstName" class="form-label">Prénom *</label>
                                    <input type="text" class="form-control" id="editFirstName" name="firstName" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="editLastName" class="form-label">Nom *</label>
                                    <input type="text" class="form-control" id="editLastName" name="lastName" required>
                                </div>
                                <div class="col-12">
                                    <label for="editEmail" class="form-label">Email *</label>
                                    <input type="email" class="form-control" id="editEmail" name="email" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="editRole" class="form-label">Rôle *</label>
                                    <select class="form-select" id="editRole" name="role" required>
                                        <option value="0">Passager</option>
                                        <option value="1">Passager / Chauffeur</option>
                                        <option value="2">Gestionnaire</option>
                                        <option value="3">Administrateur</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="editStatus" class="form-label">Statut *</label>
                                    <select class="form-select" id="editStatus" name="status" required>
                                        <option value="actif">Actif</option>
                                        <option value="inactif">Inactif</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Véhicules -->
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="text-success mb-0">Véhicules</h6>
                                <button type="button" class="btn btn-sm btn-success" id="addVehicleBtn">
                                    <i class="bi bi-plus-circle"></i> Ajouter un véhicule
                                </button>
                            </div>
                            <div id="vehiclesList">
                                <!-- Les véhicules seront injectés ici -->
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-check-circle"></i> Sauvegarder
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Template pour nouveau véhicule -->
        <template id="vehicleTemplate">
            <div class="vehicle-item border rounded p-3 mb-3">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h6 class="mb-0">Véhicule</h6>
                    <button type="button" class="btn btn-sm btn-outline-danger remove-vehicle">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
                <input type="hidden" class="vehicle-id" name="vehicles[0][id]" value="">
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label">Marque</label>
                        <input type="text" class="form-control vehicle-marque" name="vehicles[0][marque]" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Modèle</label>
                        <input type="text" class="form-control vehicle-modele" name="vehicles[0][modele]" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Carburant</label>
                        <select class="form-select vehicle-carburant" name="vehicles[0][carburant]" required>
                            <option value="">Sélectionner</option>
                            <option value="Electrique">Électrique</option>
                            <option value="Hybride">Hybride</option>
                            <option value="Essence">Essence</option>
                            <option value="Gasoil">Gasoil</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Immatriculation</label>
                        <input type="text" class="form-control vehicle-immatriculation" name="vehicles[0][immatriculation]" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Places</label>
                        <input type="number" class="form-control vehicle-places" name="vehicles[0][places]" min="1" max="8" required>
                    </div>
                </div>
            </div>
        </template>
        </section>

        <!-- Modal Création d'utilisateur -->
        <div class="modal fade" id="createUserModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Créer un nouvel utilisateur</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form id="createUserForm">
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="firstName" class="form-label">Prénom *</label>
                                    <input type="text" class="form-control" id="firstName" name="firstName" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="lastName" class="form-label">Nom *</label>
                                    <input type="text" class="form-control" id="lastName" name="lastName" required>
                                </div>
                                <div class="col-12">
                                    <label for="email" class="form-label">Email *</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="role" class="form-label">Rôle *</label>
                                    <select class="form-select" id="role" name="role" required>
                                        <!-- Les options seront chargées dynamiquement selon les permissions -->
                                    </select>
                                    <small class="text-muted">Seuls les rôles que vous pouvez créer sont affichés</small>
                                </div>
                                <div class="col-md-6">
                                    <label for="status" class="form-label">Statut *</label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="actif" selected>Actif</option>
                                        <option value="inactif">Inactif</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mt-3">
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i>
                                    <strong>Procédure :</strong> Un email sera automatiquement envoyé à l'utilisateur
                                    pour qu'il crée son propre mot de passe. Le lien sera valable 24 heures.
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-person-plus"></i> Créer et envoyer l'email
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- SECTION FINANCIER -->
        <?php if ($isAdmin): ?>
            <section class="mb-5" id="revenus">
                <!-- Graphique historique trajets +7 jours -->
                <div class="d-flex justify-content-between mb-3 pt-3">
                    <h2 class="text-success">Trajets sur 7 jours</h2>
                    <?php
                    $filterId = 'filter_trips';
                    $namePrefix = 'trips';
                    include __DIR__ . '/components/_filter_date.php';
                    ?>
                </div>

                <canvas id="chartTripsByDay" height="80"></canvas>

                <div class="d-flex justify-content-between mb-3 pt-5">
                    <h2 class="text-success">Commissions (publication de trajets)</h2>
                    <?php
                    $filterId = 'filter_commissions';
                    $namePrefix = 'commissions';
                    include __DIR__ . '/components/_filter_date.php';
                    ?>
                </div>

                <div class="alert alert-success fw-bold">
                    Gain total collecté : <?= (fmod($totalGagne, 1) == 0 ?
                        number_format($totalGagne,0, ',','') :
                        number_format($totalGagne,2, ',','')) ?> crédits
                </div>

                <!-- Graphique financier -->
                <canvas id="chartCommissions" height="80"></canvas>

                <!-- Tableau -->
                <h2 class="text-success mt-5">Historique des commissions</h2>
                <div class="table-responsive mt-4">
                    <table class="table table-striped">
                        <thead>
                        <tr>
                            <th>Date</th>
                            <th>Chauffeur</th>
                            <th>Montant</th>
                            <th>Solde avant</th>
                            <th>Solde après</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($commissions as $c): ?>
                            <tr>
                                <td><?= date('d/m/Y H:i', strtotime($c['created_at'])) ?></td>
                                <td><?= htmlspecialchars($c['firstname'] . ' ' . $c['lastname']) ?></td>
                                <td><?= abs((float)$c['amounts']) ?> crédits</td>
                                <td><?= $c['balance_before'] ?></td>
                                <td><?= $c['balance_after'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>

        <!-- SECTION COMMENTAIRES -->
        <section id="comments" class="mb-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="text-success">Commentaires et avis des utilisateurs</h2>
            </div>

            <!-- Filtres -->
            <!-- Filtres -->
            <div class="card mb-4">
                <div class="card-body">
                    <h6 class="card-title mb-3">
                        <i class="bi bi-funnel"></i> Filtrer les commentaires
                    </h6>

                    <form id="commentsFilterForm" class="row g-3 align-items-end">
                        <!-- Filtre par statut -->
                        <div class="col-md-2">
                            <label for="statusFilter" class="form-label">Statut</label>
                            <select id="statusFilter" name="comment_status" class="form-select form-select-sm">
                                <option value="">Tous</option>
                                <option value="approved">Approuvé</option>
                                <option value="pending" selected>En attente</option>
                                <option value="rejected">Rejeté</option>
                            </select>
                        </div>

                        <!-- Filtre par ranking/note -->
                        <div class="col-md-2">
                            <label for="ratingFilter" class="form-label">Note minimum</label>
                            <select id="ratingFilter" name="rating" class="form-select form-select-sm">
                                <option value="">Toutes</option>
                                <option value="5">5 ★ exactement</option>
                                <option value="4">4 ★ et plus</option>
                                <option value="3">3 ★ et plus</option>
                                <option value="2">2 ★ et plus</option>
                                <option value="1">1 ★ et plus</option>
                            </select>
                        </div>

                        <!-- Filtre par période prédéfinie -->
                        <div class="col-md-2">
                            <label for="periodFilter" class="form-label">Période</label>
                            <select id="periodFilter" name="period_preset" class="form-select form-select-sm">
                                <option value="">Personnalisé</option>
                                <option value="today">Aujourd'hui</option>
                                <option value="yesterday">Hier</option>
                                <option value="last_7_days">7 derniers jours</option>
                                <option value="last_30_days">30 derniers jours</option>
                                <option value="this_month">Ce mois-ci</option>
                                <option value="last_month">Mois dernier</option>
                                <option value="this_year">Cette année</option>
                            </select>
                        </div>

                        <!-- Date de début -->
                        <div class="col-md-2">
                            <label for="dateStart" class="form-label">Du</label>
                            <input type="date" id="dateStart" name="date_start" class="form-control form-control-sm">
                        </div>

                        <!-- Date de fin -->
                        <div class="col-md-2">
                            <label for="dateEnd" class="form-label">Au</label>
                            <input type="date" id="dateEnd" name="date_end" class="form-control form-control-sm">
                        </div>

                        <!-- Boutons d'action -->
                        <div class="col-md-2">
                            <div class="d-flex gap-1">
                                <button type="submit" class="btn btn-primary btn-sm flex-fill">
                                    <i class="bi bi-search"></i> Filtrer
                                </button>
                                <button type="button" id="resetFiltersBtn" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-arrow-clockwise"></i>
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- Indicateur de filtres actifs -->
                    <div id="activeFiltersIndicator" class="mt-2" style="display: none;">
                        <small class="text-muted">
                            <i class="bi bi-funnel-fill text-primary"></i>
                            <span id="activeFiltersText">Filtres actifs</span>
                            <button type="button" class="btn btn-link btn-sm p-0 ms-2" id="clearAllFilters">
                                Supprimer tous les filtres
                            </button>
                        </small>
                    </div>
                </div>
            </div>

            <!-- Tableau des commentaires -->
            <div class="card">
                <div class="card-body p-0">
                    <div id="commentsTableContainer">
                        <!-- Chargement initial -->
                        <div class="text-center p-4">
                            <div class="spinner-border text-success" role="status">
                                <span class="visually-hidden">Chargement...</span>
                            </div>
                            <p class="mt-2 text-muted">Chargement des commentaires...</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Info -->
            <div class="mt-3">
                <small class="text-muted">
                    <i class="bi bi-info-circle"></i>
                    Cliquez sur l'ID du trajet pour voir tous les détails du voyage.
                    Les dates affichées correspondent au départ du trajet et à la date du commentaire.
                </small>
            </div>
        </section>
    </main>


<footer>

</footer>

<!-- Bootstrap JS et Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>const monthlyData = <?= json_encode($monthlyData) ?>;</script>
<script>const tripsByDay = <?= json_encode($tripsByDay) ?>;</script>
<script src="assets/js/dashboard.js"></script>
</body>
</html>
