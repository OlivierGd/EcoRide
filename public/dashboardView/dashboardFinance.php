<?php
// === SECTION FINANCIER ===

use Olivierguissard\EcoRide\Service\DateFilterService;
use Olivierguissard\EcoRide\Config\Database;

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
?>

<!-- SECTION FINANCIER - HTML -->
<section class="mb-5" id="revenus">
    <!-- Graphique historique trajets +7 jours -->
    <div class="d-flex justify-content-between mb-3 pt-3">
        <h2 class="text-success">Trajets sur 7 jours</h2>
        <?php
        $filterId = 'filter_trips';
        $namePrefix = 'trips';
        include __DIR__ . '/../components/_filter_date.php';
        ?>
    </div>

    <div style="height: 300px;">
        <canvas id="chartTripsByDay" height="100px"></canvas>
    </div>

    <div class="d-flex justify-content-between mb-3 pt-5">
        <h2 class="text-success">Commissions (publication de trajets)</h2>
        <?php
        $filterId = 'filter_commissions';
        $namePrefix = 'commissions';
        include __DIR__ . '/../components/_filter_date.php';
        ?>
    </div>

    <div class="alert alert-success fw-bold">
        Gain total collecté : <?= (fmod($totalGagne, 1) == 0 ?
                number_format($totalGagne,0, ',','') :
                number_format($totalGagne,2, ',','')) ?> crédits
    </div>

    <!-- Graphique financier -->
    <div style="height: 300px">
        <canvas id="chartCommissionsMonthly" ></canvas>
    </div>

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

<!-- Variables JavaScript pour les graphiques -->
<script>
    const monthlyData = <?= json_encode($monthlyData) ?>;
    const tripsByDay = <?= json_encode($tripsByDay) ?>;
</script>