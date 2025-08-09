<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Olivierguissard\EcoRide\Model\Trip;
use Olivierguissard\EcoRide\Model\Car;
use Olivierguissard\EcoRide\Service\CreditService;

require_once 'functions/auth.php';

// DEBUG COMPLET - À SUPPRIMER APRÈS DIAGNOSTIC
$debug_steps = [];
$debug_steps[] = "=== DÉBUT DEBUG PROPOSER.PHP ===";

// Démarrer la session
startSession();
$debug_steps[] = "Session démarrée";

// État initial de la session
$debug_steps[] = "SESSION INITIALE: " . print_r($_SESSION, true);
$debug_steps[] = "isAuthenticated() INITIAL: " . (isAuthenticated() ? 'TRUE' : 'FALSE');
$debug_steps[] = "getUserId() INITIAL: " . (getUserId() ?? 'NULL');

// Test de requireAuth()
$debug_steps[] = "=== AVANT requireAuth() ===";
try {
    requireAuth();
    $debug_steps[] = "requireAuth() RÉUSSI";
} catch (Exception $e) {
    $debug_steps[] = "ERREUR requireAuth(): " . $e->getMessage();
    // Arrêt ici si requireAuth() échoue
    echo "<div style='background:red;color:white;padding:20px;'>";
    foreach ($debug_steps as $step) {
        echo htmlspecialchars($step) . "<br>";
    }
    echo "</div>";
    exit;
}

$debug_steps[] = "SESSION APRÈS requireAuth(): " . print_r($_SESSION, true);
$debug_steps[] = "isAuthenticated() APRÈS requireAuth(): " . (isAuthenticated() ? 'TRUE' : 'FALSE');

// Test updateActivity()
$debug_steps[] = "=== AVANT updateActivity() ===";
try {
    updateActivity();
    $debug_steps[] = "updateActivity() RÉUSSI";
} catch (Exception $e) {
    $debug_steps[] = "ERREUR updateActivity(): " . $e->getMessage();
}

$debug_steps[] = "SESSION APRÈS updateActivity(): " . print_r($_SESSION, true);

// Test getUserId()
$debug_steps[] = "=== TEST getUserId() ===";
try {
    $userID = getUserId();
    $debug_steps[] = "getUserId() RÉUSSI: " . ($userID ?? 'NULL');
} catch (Exception $e) {
    $debug_steps[] = "ERREUR getUserId(): " . $e->getMessage();
    $userID = null;
}

// Test requête base de données
$debug_steps[] = "=== TEST BASE DE DONNÉES ===";
if ($userID) {
    try {
        $voyages = Trip::findTripsByDriver($userID);
        $debug_steps[] = "Trip::findTripsByDriver() RÉUSSI - Nombre de voyages: " . count($voyages);

        $vehicles = Car::findByUser($userID);
        $debug_steps[] = "Car::findByUser() RÉUSSI - Nombre de véhicules: " . count($vehicles);

        if (empty($vehicles)) {
            $debug_steps[] = "ATTENTION: Aucun véhicule trouvé - redirection probable vers profil.php";
        }

    } catch (Exception $e) {
        $debug_steps[] = "ERREUR REQUÊTE DB: " . $e->getMessage();
        $vehicles = [];
    }
} else {
    $debug_steps[] = "SKIP TEST DB - Pas d'userID";
    $vehicles = [];
    $voyages = [];
}

// Gestion du formulaire POST (seulement si pas de debug)
$debug_steps[] = "=== TRAITEMENT FORMULAIRE ===";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $debug_steps[] = "Formulaire POST reçu - données: " . print_r($_POST, true);
    // Pour le debug, on ne traite pas le POST
}

// Vérification finale avant affichage
$debug_steps[] = "SESSION FINALE: " . print_r($_SESSION, true);
$debug_steps[] = "isAuthenticated() FINAL: " . (isAuthenticated() ? 'TRUE' : 'FALSE');
$debug_steps[] = "=== FIN DEBUG - AFFICHAGE HTML ===";

require_once __DIR__ . '/../src/Helpers/helpers.php';

// Vérification véhicules (commentée pour debug)
/*
if (empty($vehicles)) {
    $_SESSION['flash_error'] = "Vous devez enregistrez un véhicule avant de pouvoir proposer un trajet.";
    header('Location: profil.php');
    exit;
}
*/

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
    <title>DEBUG - Proposer un trajet</title>
</head>

<body>
<header>
    <nav class="navbar bg-body-tertiary">
        <div class="container" style="max-width: 900px;">
            <a class="navbar-brand" href="index.php">
                <img src="assets/pictures/logoEcoRide.png" alt="Logo EcoRide" width="60" class="d-inline-block align-text-center rounded">
            </a>
            <h2 class="fw-bold mb-1 text-success">🔍 DEBUG Proposer un trajet</h2>
            <?= displayInitialsButton(); ?>
        </div>
    </nav>
</header>

<main class="container px-3 py-2 mt-1 pt-5">
    <!-- AFFICHAGE DEBUG COMPLET -->
    <div class="alert alert-info">
        <h5><strong>🔍 DEBUG PROPOSER.PHP:</strong></h5>
        <div style="font-family: monospace; font-size: 11px; max-height: 400px; overflow-y: auto; background: rgba(255,255,255,0.8); padding: 10px; border-radius: 5px;">
            <?php foreach ($debug_steps as $step): ?>
                <div style="margin: 2px 0; padding: 2px;">
                    <?= htmlspecialchars($step) ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- NAVIGATION DEBUG -->
    <div class="alert alert-warning">
        <strong>🚀 NAVIGATION TEST:</strong><br>
        <div class="btn-group mt-2">
            <a href="profil.php" class="btn btn-sm btn-outline-primary">Retour Profil</a>
            <a href="rechercher.php" class="btn btn-sm btn-outline-success">Rechercher</a>
            <a href="index.php" class="btn btn-sm btn-outline-secondary">Accueil</a>
        </div>
    </div>

    <!-- STATUS FINAL -->
    <div class="alert <?= isAuthenticated() ? 'alert-success' : 'alert-danger' ?>">
        <strong>STATUS FINAL:</strong><br>
        Authentifié: <?= isAuthenticated() ? '✅ OUI' : '❌ NON' ?><br>
        User ID: <?= getUserId() ?? 'NULL' ?><br>
        Véhicules: <?= isset($vehicles) ? count($vehicles) : 'N/A' ?>
    </div>

    <!-- MINI FORMULAIRE DE TEST -->
    <div class="card">
        <div class="card-header">
            <h5>Formulaire simplifié (pour test)</h5>
        </div>
        <div class="card-body">
            <form method="post">
                <div class="mb-3">
                    <input type="text" name="start_city" class="form-control" placeholder="Ville de départ" required>
                </div>
                <div class="mb-3">
                    <input type="text" name="end_city" class="form-control" placeholder="Ville d'arrivée" required>
                </div>
                <button type="submit" class="btn btn-primary">Test Submit</button>
            </form>
        </div>
    </div>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>