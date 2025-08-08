<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Olivierguissard\EcoRide\Config\Database;
use Olivierguissard\EcoRide\Model\Users;
use Olivierguissard\EcoRide\Model\CreditsHistory;
use Olivierguissard\EcoRide\Service\DateFilterService;

require_once 'functions/auth.php';
startSession();
isAuthenticated();
requireAuth();

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
                <li class="nav-item"><a href="#revenus" class="nav-link">Statistiques</a></li>
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
            <h2 class="text-success">Rechercher un utilisateur</h2>
            <form id="searchUserForm" class="row g-3 mb-4">
                <div class="col-md-4">
                    <input type="text" class="form-control" name="query" placeholder="Nom, prénom ou email">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary" type="submit">Rechercher</button>
                </div>
            </form>
            <div id="userDetails"></div>
        </section>

        <!-- SECTION FINANCIER -->
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

        <!-- SECTION COMMENTAIRES -->
        <section id="comments" class="mb-5">
            <h2 class="text-success">Commentaires utilisateurs</h2>
            <form id="commentsFilterForm" class="d-flex gap-2">
                <select class="form-select" name="rating" style="width:auto">
                    <option value="">Tous les rankings</option>
                    <option value="5">5 ★</option>
                    <option value="4">4 ★</option>
                    <option value="3">3 ★</option>
                    <option value="2">2 ★</option>
                    <option value="1">1 ★</option>
                </select>
                <input type="date" class="form-control" name="date_min" style="width:auto">
                <input type="date" class="form-control" name="date_max" style="width:auto">
                <button type="submit" class="btn btn-primary">Filtrer</button>
            </form>
            <div id="commentsTableContainer"></div>
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
