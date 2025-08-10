<?php
// proposer-debug.php - Debug spécifique pour navigation profil→proposer

require_once __DIR__ . '/../vendor/autoload.php';

use Olivierguissard\EcoRide\Model\Trip;
use Olivierguissard\EcoRide\Model\Car;

require_once 'functions/auth.php';

$debug_log = [];
$debug_log[] = "=== DEBUG PROPOSER.PHP DEPUIS PROFIL ===";
$debug_log[] = "URL actuelle: " . ($_SERVER['REQUEST_URI'] ?? 'N/A');
$debug_log[] = "Referer: " . ($_SERVER['HTTP_REFERER'] ?? 'N/A');
$debug_log[] = "Méthode: " . $_SERVER['REQUEST_METHOD'];
$debug_log[] = "Paramètres GET: " . print_r($_GET, true);

// Test session avant toute chose
$debug_log[] = "=== ÉTAT SESSION INITIAL ===";
$sessionBefore = session_status();
$debug_log[] = "Session status avant startSession(): " . $sessionBefore;

if (isset($_SESSION)) {
    $debug_log[] = "SESSION existe déjà: " . print_r($_SESSION, true);
} else {
    $debug_log[] = "SESSION n'existe pas encore";
}

// Démarrer session
startSession();
$debug_log[] = "✅ startSession() appelée";
$debug_log[] = "Session status après startSession(): " . session_status();
$debug_log[] = "Session ID: " . session_id();
$debug_log[] = "SESSION APRÈS startSession(): " . print_r($_SESSION, true);

// Test isAuthenticated
$isAuthBefore = isAuthenticated();
$debug_log[] = "isAuthenticated() AVANT requireAuth(): " . ($isAuthBefore ? 'TRUE' : 'FALSE');
$userIdBefore = getUserId();
$debug_log[] = "getUserId() AVANT requireAuth(): " . ($userIdBefore ?? 'NULL');

// Test requireAuth
$debug_log[] = "=== TEST requireAuth() ===";
try {
    requireAuth();
    $debug_log[] = "✅ requireAuth() RÉUSSI";
} catch (Exception $e) {
    $debug_log[] = "❌ ERREUR requireAuth(): " . $e->getMessage();

    // En cas d'erreur, afficher tout le debug
    echo "<div style='background:red;color:white;padding:20px;'>";
    echo "<h2>🚨 ERREUR DANS requireAuth()</h2>";
    foreach ($debug_log as $log) {
        echo "<div style='margin:2px 0;'>" . htmlspecialchars($log) . "</div>";
    }
    echo "</div>";
    exit;
}

$debug_log[] = "SESSION APRÈS requireAuth(): " . print_r($_SESSION, true);
$isAuthAfter = isAuthenticated();
$debug_log[] = "isAuthenticated() APRÈS requireAuth(): " . ($isAuthAfter ? 'TRUE' : 'FALSE');

// Test updateActivity
$debug_log[] = "=== TEST updateActivity() ===";
try {
    updateActivity();
    $debug_log[] = "✅ updateActivity() RÉUSSI";
} catch (Exception $e) {
    $debug_log[] = "❌ ERREUR updateActivity(): " . $e->getMessage();
}

$debug_log[] = "SESSION APRÈS updateActivity(): " . print_r($_SESSION, true);

// Test getUserId
$userID = getUserId();
$debug_log[] = "getUserId() FINAL: " . ($userID ?? 'NULL');

// Test récupération véhicules
$debug_log[] = "=== TEST RÉCUPÉRATION VÉHICULES ===";
if ($userID) {
    try {
        $vehicles = Car::findByUser($userID);
        $debug_log[] = "✅ Car::findByUser() RÉUSSI - " . count($vehicles) . " véhicules trouvés";

        foreach ($vehicles as $vehicle) {
            $debug_log[] = "Véhicule: " . ($vehicle->marque ?? 'N/A') . " " . ($vehicle->modele ?? 'N/A');
        }
    } catch (Exception $e) {
        $debug_log[] = "❌ ERREUR Car::findByUser(): " . $e->getMessage();
        $vehicles = [];
    }
} else {
    $debug_log[] = "⚠️ Pas d'userID - skip test véhicules";
    $vehicles = [];
}

$hasVehicles = !empty($vehicles);
$canCreateTrip = $hasVehicles;

$debug_log[] = "Nombre de véhicules: " . count($vehicles);
$debug_log[] = "Peut créer trajet: " . ($canCreateTrip ? 'OUI' : 'NON');

$debug_log[] = "=== SESSION FINALE ===";
$debug_log[] = "SESSION: " . print_r($_SESSION, true);
$debug_log[] = "isAuthenticated(): " . (isAuthenticated() ? 'TRUE' : 'FALSE');

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔍 Debug Proposer depuis Profil - EcoRide</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
</head>
<body>

<div class="container mt-4">
    <div class="alert alert-info">
        <h2 class="alert-heading">🔍 Debug Navigation Profil → Proposer</h2>
        <p class="mb-0">Cette page diagnostique les problèmes de déconnexion lors de la navigation profil → proposer.</p>
    </div>

    <!-- Debug complet -->
    <div class="card mb-4">
        <div class="card-header">
            <h5>📋 Log Debug Complet</h5>
        </div>
        <div class="card-body">
            <div style="font-family: monospace; font-size: 11px; max-height: 500px; overflow-y: auto; background: #f8f9fa; padding: 15px; border-radius: 5px;">
                <?php foreach ($debug_log as $log): ?>
                    <div style="margin: 2px 0; padding: 2px;">
                        <?php
                        $color = 'black';
                        if (strpos($log, '✅') !== false) $color = 'green';
                        if (strpos($log, '❌') !== false) $color = 'red';
                        if (strpos($log, '⚠️') !== false) $color = 'orange';
                        if (strpos($log, '===') !== false) $color = 'blue';
                        ?>
                        <span style="color: <?= $color ?>;"><?= htmlspecialchars($log) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Résumé du statut -->
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6>📊 Statut de l'authentification</h6>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <strong>Authentifié:</strong>
                        <span class="badge <?= isAuthenticated() ? 'bg-success' : 'bg-danger' ?>">
                            <?= isAuthenticated() ? '✅ OUI' : '❌ NON' ?>
                        </span>
                    </div>
                    <div class="mb-2">
                        <strong>User ID:</strong>
                        <span class="badge bg-info"><?= getUserId() ?? 'NULL' ?></span>
                    </div>
                    <div class="mb-2">
                        <strong>Session ID:</strong>
                        <span class="badge bg-secondary"><?= session_id() ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6>🚗 Statut des véhicules</h6>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <strong>Véhicules:</strong>
                        <span class="badge <?= $hasVehicles ? 'bg-success' : 'bg-warning' ?>">
                            <?= count($vehicles) ?> véhicule(s)
                        </span>
                    </div>
                    <div class="mb-2">
                        <strong>Peut créer trajet:</strong>
                        <span class="badge <?= $canCreateTrip ? 'bg-success' : 'bg-danger' ?>">
                            <?= $canCreateTrip ? '✅ OUI' : '❌ NON' ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation de test -->
    <div class="alert alert-warning mt-4">
        <h6>🧪 Tests de navigation</h6>
        <div class="btn-group">
            <a href="profil.php" class="btn btn-outline-secondary">← Retour Profil</a>
            <a href="proposer.php" class="btn btn-outline-success">Proposer Normal</a>
            <a href="vehicule.php" class="btn btn-outline-info">Véhicules</a>
            <a href="index.php" class="btn btn-outline-primary">Accueil</a>
        </div>
        <div class="mt-2">
            <small class="text-muted">
                Testez ces liens pour voir si la session reste active lors de la navigation.
            </small>
        </div>
    </div>

    <!-- Simulation du formulaire si tout va bien -->
    <?php if (isAuthenticated() && $userID): ?>
        <div class="card mt-4">
            <div class="card-header bg-success text-white">
                <h6>✅ Session OK - Simulation du formulaire proposer</h6>
            </div>
            <div class="card-body">
                <p>Si vous voyez cette section, cela signifie que :</p>
                <ul>
                    <li>✅ La session est active</li>
                    <li>✅ L'utilisateur est authentifié</li>
                    <li>✅ Le problème pourrait être ailleurs</li>
                </ul>

                <?php if ($hasVehicles): ?>
                    <div class="alert alert-success">
                        <strong>Vous avez <?= count($vehicles) ?> véhicule(s)</strong> - Le formulaire proposer devrait être accessible !
                    </div>
                    <a href="proposer.php" class="btn btn-success">
                        <i class="bi bi-arrow-right me-1"></i>Aller au vrai formulaire proposer
                    </a>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <strong>Aucun véhicule</strong> - Le formulaire sera grisé mais accessible.
                    </div>
                    <a href="vehicule.php" class="btn btn-warning">
                        <i class="bi bi-car-front me-1"></i>Ajouter un véhicule d'abord
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

</div>

</body>
</html>
