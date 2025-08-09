<?php
// vehicule-debug.php - Version debug pour identifier le problème

require_once __DIR__ . '/../vendor/autoload.php';
use Olivierguissard\EcoRide\Model\Car;

require_once 'functions/auth.php';

$debug_log = [];
$debug_log[] = "=== DÉBUT DEBUG VEHICULE.PHP ===";

// Test session avant tout
startSession();
$debug_log[] = "✅ Session démarrée";
$debug_log[] = "SESSION INITIALE: " . print_r($_SESSION, true);
$debug_log[] = "isAuthenticated() INITIAL: " . (isAuthenticated() ? 'TRUE' : 'FALSE');
$debug_log[] = "getUserId() INITIAL: " . (getUserId() ?? 'NULL');

// Test requireAuth
$debug_log[] = "=== AVANT requireAuth() ===";
try {
    requireAuth();
    $debug_log[] = "✅ requireAuth() RÉUSSI";
} catch (Exception $e) {
    $debug_log[] = "❌ ERREUR requireAuth(): " . $e->getMessage();
    // Afficher le debug et arrêter
    echo "<pre>" . implode("\n", $debug_log) . "</pre>";
    exit;
}

$debug_log[] = "SESSION APRÈS requireAuth(): " . print_r($_SESSION, true);

// Test updateActivity
$debug_log[] = "=== AVANT updateActivity() ===";
try {
    updateActivity();
    $debug_log[] = "✅ updateActivity() RÉUSSI";
} catch (Exception $e) {
    $debug_log[] = "❌ ERREUR updateActivity(): " . $e->getMessage();
}

$debug_log[] = "SESSION APRÈS updateActivity(): " . print_r($_SESSION, true);

$userID = getUserId();
$debug_log[] = "getUserId(): " . ($userID ?? 'NULL');

// Variables pour le formulaire
$errors = [];
$success = false;

// TRAITEMENT DU FORMULAIRE POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $debug_log[] = "=== TRAITEMENT POST DÉTECTÉ ===";
    $debug_log[] = "POST data: " . print_r($_POST, true);
    $debug_log[] = "SESSION AVANT TRAITEMENT: " . print_r($_SESSION, true);
    $debug_log[] = "isAuthenticated() AVANT TRAITEMENT: " . (isAuthenticated() ? 'TRUE' : 'FALSE');

    // Validation des données
    $marque = trim($_POST['marque'] ?? '');
    $modele = trim($_POST['modele'] ?? '');
    $carburant = trim($_POST['type_carburant'] ?? '');
    $places = (int)($_POST['nb_places'] ?? 0);

    $debug_log[] = "Données extraites: marque=$marque, modele=$modele, carburant=$carburant, places=$places";

    // Validation
    if (empty($marque)) {
        $errors[] = 'La marque est obligatoire';
        $debug_log[] = "❌ Erreur validation: marque vide";
    }
    if (empty($modele)) {
        $errors[] = 'Le modèle est obligatoire';
        $debug_log[] = "❌ Erreur validation: modèle vide";
    }
    if (empty($carburant)) {
        $errors[] = 'Le type de carburant est obligatoire';
        $debug_log[] = "❌ Erreur validation: carburant vide";
    }
    if ($places < 2 || $places > 9) {
        $errors[] = 'Le nombre de places doit être entre 2 et 9';
        $debug_log[] = "❌ Erreur validation: places invalides ($places)";
    }

    $debug_log[] = "Nombre d'erreurs de validation: " . count($errors);
    $debug_log[] = "SESSION APRÈS VALIDATION: " . print_r($_SESSION, true);

    // Si pas d'erreurs, créer le véhicule
    if (empty($errors) && $userID) {
        $debug_log[] = "=== TENTATIVE CRÉATION VÉHICULE ===";

        try {
            // Préparer les données pour le modèle Car
            $vehicleData = [
                    'user_id' => $userID,
                    'marque' => $marque,
                    'modele' => $modele,
                    'type_carburant' => $carburant,
                    'nb_places' => $places
            ];

            $debug_log[] = "Données véhicule: " . print_r($vehicleData, true);
            $debug_log[] = "SESSION AVANT CRÉATION: " . print_r($_SESSION, true);

            // Créer le véhicule (simulé pour debug)
            $debug_log[] = "SIMULATION: Création véhicule pour user $userID";

            // Pour le debug, on simule la création sans vraiment l'insérer
            $debug_log[] = "✅ SIMULATION RÉUSSIE - Véhicule créé";

            $debug_log[] = "SESSION APRÈS CRÉATION: " . print_r($_SESSION, true);
            $debug_log[] = "isAuthenticated() APRÈS CRÉATION: " . (isAuthenticated() ? 'TRUE' : 'FALSE');

            $success = true;
            $_SESSION['flash_success'] = 'Véhicule ajouté avec succès !';

            $debug_log[] = "SESSION FINALE: " . print_r($_SESSION, true);

            // NE PAS REDIRIGER EN MODE DEBUG
            $debug_log[] = "SIMULATION: Redirection vers profil.php (désactivée en debug)";

        } catch (Exception $e) {
            $debug_log[] = "❌ ERREUR CRÉATION VÉHICULE: " . $e->getMessage();
            $errors[] = 'Erreur lors de la création du véhicule: ' . $e->getMessage();
        }
    } else {
        $debug_log[] = "❌ Création véhicule annulée - erreurs ou pas d'userID";
    }

    $debug_log[] = "SESSION FINALE POST: " . print_r($_SESSION, true);
    $debug_log[] = "isAuthenticated() FINAL: " . (isAuthenticated() ? 'TRUE' : 'FALSE');
}

$debug_log[] = "=== FIN TRAITEMENT - AFFICHAGE HTML ===";

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔧 Debug Véhicule - EcoRide</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
</head>
<body>

<div class="container mt-4">
    <div class="alert alert-warning">
        <h2 class="alert-heading">🔧 Debug Véhicule - Mode Test</h2>
        <p class="mb-0">Cette page teste le processus d'ajout de véhicule pour identifier les bugs de session.</p>
    </div>

    <!-- Debug Log -->
    <div class="card mb-4">
        <div class="card-header">
            <h5>📋 Log de Debug</h5>
        </div>
        <div class="card-body">
            <div style="font-family: monospace; font-size: 11px; max-height: 400px; overflow-y: auto; background: #f8f9fa; padding: 10px; border-radius: 5px;">
                <?php foreach ($debug_log as $log): ?>
                    <div style="margin: 2px 0; padding: 1px;">
                        <?php
                        $color = 'black';
                        if (strpos($log, '✅') !== false) $color = 'green';
                        if (strpos($log, '❌') !== false) $color = 'red';
                        if (strpos($log, '===') !== false) $color = 'blue';
                        ?>
                        <span style="color: <?= $color ?>;"><?= htmlspecialchars($log) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Messages -->
    <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="bi bi-check-circle me-2"></i>
            Simulation réussie ! En mode normal, le véhicule serait créé et vous seriez redirigé.
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <h6>Erreurs de validation :</h6>
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Formulaire de test -->
    <div class="card">
        <div class="card-header">
            <h5>🚗 Formulaire d'ajout de véhicule (mode debug)</h5>
        </div>
        <div class="card-body">
            <form method="post">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Marque *</label>
                        <input type="text" name="marque" class="form-control"
                               value="<?= htmlspecialchars($_POST['marque'] ?? 'Peugeot') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Modèle *</label>
                        <input type="text" name="modele" class="form-control"
                               value="<?= htmlspecialchars($_POST['modele'] ?? '308') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Type de carburant *</label>
                        <select name="type_carburant" class="form-select" required>
                            <option value="">Choisir...</option>
                            <option value="Essence" <?= ($_POST['type_carburant'] ?? '') === 'Essence' ? 'selected' : '' ?>>Essence</option>
                            <option value="Diesel" <?= ($_POST['type_carburant'] ?? '') === 'Diesel' ? 'selected' : '' ?>>Diesel</option>
                            <option value="Électrique" <?= ($_POST['type_carburant'] ?? '') === 'Électrique' ? 'selected' : '' ?>>Électrique</option>
                            <option value="Hybride" <?= ($_POST['type_carburant'] ?? '') === 'Hybride' ? 'selected' : '' ?>>Hybride</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nombre de places *</label>
                        <select name="nb_places" class="form-select" required>
                            <option value="">Choisir...</option>
                            <?php for ($i = 2; $i <= 9; $i++): ?>
                                <option value="<?= $i ?>" <?= ($_POST['nb_places'] ?? '') == $i ? 'selected' : '' ?>>
                                    <?= $i ?> places
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-car-front me-1"></i>Tester l'ajout de véhicule
                        </button>
                        <small class="text-muted ms-2">Mode debug - pas de vraie insertion en base</small>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Navigation -->
    <div class="alert alert-info mt-4">
        <h6>🚀 Navigation</h6>
        <div class="btn-group">
            <a href="debug-auth.php" class="btn btn-outline-info btn-sm">Debug Auth</a>
            <a href="profil.php" class="btn btn-outline-secondary btn-sm">Profil</a>
            <a href="proposer.php" class="btn btn-outline-success btn-sm">Proposer</a>
            <a href="index.php" class="btn btn-outline-primary btn-sm">Accueil</a>
        </div>
    </div>

    <!-- Status final -->
    <div class="alert <?= isAuthenticated() ? 'alert-success' : 'alert-danger' ?> mt-3">
        <strong>STATUS FINAL:</strong><br>
        Authentifié: <?= isAuthenticated() ? '✅ OUI' : '❌ NON' ?><br>
        User ID: <?= getUserId() ?? 'NULL' ?><br>
        Session active: <?= session_status() === PHP_SESSION_ACTIVE ? '✅ OUI' : '❌ NON' ?>
    </div>
</div>

</body>
</html>