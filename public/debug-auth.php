<?php
// debug-auth.php - Diagnostic du système d'authentification

require_once __DIR__ . '/../vendor/autoload.php';
use Olivierguissard\EcoRide\Config\Database;

// Pas d'inclusion de functions/auth.php pour l'instant pour éviter les erreurs

$debug_steps = [];
$debug_steps[] = "=== DIAGNOSTIC SYSTÈME AUTHENTIFICATION ===";

// Test 1 : Session PHP basique
session_start();
$debug_steps[] = "✅ Session PHP démarrée";
$debug_steps[] = "Session ID: " . session_id();
$debug_steps[] = "Session actuelle: " . print_r($_SESSION, true);

// Test 2 : Connexion base de données
$debug_steps[] = "=== TEST CONNEXION BASE DE DONNÉES ===";
try {
    $pdo = Database::getConnection();
    $debug_steps[] = "✅ Connexion DB réussie";

    // Test requête simple
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    $debug_steps[] = "✅ Requête test réussie - Utilisateurs en DB: " . $count['total'];

} catch (Exception $e) {
    $debug_steps[] = "❌ ERREUR DB: " . $e->getMessage();
}

// Test 3 : Structure de la table users
$debug_steps[] = "=== STRUCTURE TABLE USERS ===";
try {
    $stmt = $pdo->query("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'users' ORDER BY ordinal_position");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        $debug_steps[] = "Colonne: " . $col['column_name'] . " (" . $col['data_type'] . ")";
    }
} catch (Exception $e) {
    $debug_steps[] = "❌ ERREUR structure: " . $e->getMessage();
}

// Test 4 : Table tokens/remember (source probable du problème)
$debug_steps[] = "=== TEST TABLE TOKENS/REMEMBER ===";
try {
    // Essayer de trouver la table des tokens
    $stmt = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_name LIKE '%token%' OR table_name LIKE '%remember%' OR table_name LIKE '%session%'");
    $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($tables)) {
        $debug_steps[] = "⚠️ Aucune table de tokens trouvée";
    } else {
        foreach ($tables as $table) {
            $debug_steps[] = "Table trouvée: " . $table['table_name'];

            // Essayer de voir la structure
            try {
                $stmt = $pdo->query("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = '{$table['table_name']}' ORDER BY ordinal_position");
                $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($cols as $col) {
                    $debug_steps[] = "  - " . $col['column_name'] . " (" . $col['data_type'] . ")";
                }
            } catch (Exception $e) {
                $debug_steps[] = "  ❌ Erreur lecture structure: " . $e->getMessage();
            }
        }
    }
} catch (Exception $e) {
    $debug_steps[] = "❌ ERREUR recherche tables: " . $e->getMessage();
}

// Test 5 : Reproduction de l'erreur SQL problématique
$debug_steps[] = "=== TEST REQUÊTE PROBLÉMATIQUE ===";
try {
    // Essayer la requête qui semble poser problème d'après les logs
    // "ORDER BY last_used_at ASC LIMIT $2" suggère une table avec last_used_at
    $stmt = $pdo->query("SELECT table_name FROM information_schema.columns WHERE column_name = 'last_used_at'");
    $tablesWithLastUsed = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($tablesWithLastUsed)) {
        foreach ($tablesWithLastUsed as $table) {
            $debug_steps[] = "Table avec 'last_used_at': " . $table['table_name'];

            // Tester une requête similaire à celle qui échoue
            try {
                $testQuery = "SELECT * FROM {$table['table_name']} ORDER BY last_used_at ASC LIMIT 1";
                $stmt = $pdo->query($testQuery);
                $debug_steps[] = "✅ Requête test OK sur " . $table['table_name'];
            } catch (Exception $e) {
                $debug_steps[] = "❌ ERREUR requête test sur " . $table['table_name'] . ": " . $e->getMessage();
            }
        }
    } else {
        $debug_steps[] = "⚠️ Aucune table avec 'last_used_at' trouvée";
    }
} catch (Exception $e) {
    $debug_steps[] = "❌ ERREUR test requête: " . $e->getMessage();
}

// Test 6 : Test simple de création de session
$debug_steps[] = "=== TEST CRÉATION SESSION SIMPLE ===";
$_SESSION['test_timestamp'] = time();
$_SESSION['test_data'] = 'Session de test créée';
$debug_steps[] = "✅ Données test ajoutées à la session";
$debug_steps[] = "Session après test: " . print_r($_SESSION, true);

// Test 7 : Inclusion sécurisée de functions/auth.php
$debug_steps[] = "=== TEST INCLUSION AUTH.PHP ===";
try {
    // Capturer les erreurs potentielles
    ob_start();
    require_once 'functions/auth.php';
    $output = ob_get_clean();

    if (!empty($output)) {
        $debug_steps[] = "⚠️ Sortie inattendue de auth.php: " . $output;
    }

    $debug_steps[] = "✅ functions/auth.php inclus";

    // Tester les fonctions si elles existent
    if (function_exists('isAuthenticated')) {
        $debug_steps[] = "✅ Fonction isAuthenticated() disponible";
        $isAuth = isAuthenticated();
        $debug_steps[] = "isAuthenticated() retourne: " . ($isAuth ? 'TRUE' : 'FALSE');
    } else {
        $debug_steps[] = "❌ Fonction isAuthenticated() non trouvée";
    }

    if (function_exists('getUserId')) {
        $debug_steps[] = "✅ Fonction getUserId() disponible";
        $userId = getUserId();
        $debug_steps[] = "getUserId() retourne: " . ($userId ?? 'NULL');
    } else {
        $debug_steps[] = "❌ Fonction getUserId() non trouvée";
    }

} catch (Exception $e) {
    $debug_steps[] = "❌ ERREUR inclusion auth.php: " . $e->getMessage();
} catch (Error $e) {
    $debug_steps[] = "❌ ERREUR FATALE auth.php: " . $e->getMessage();
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔍 Debug Authentification - EcoRide</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
</head>
<body>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="alert alert-warning">
                <h2 class="alert-heading">🔍 Diagnostic Système d'Authentification</h2>
                <p class="mb-0">Cette page diagnostique les problèmes d'authentification et de session.</p>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>📋 Résultats du diagnostic</h5>
                </div>
                <div class="card-body">
                    <div style="font-family: monospace; font-size: 12px; max-height: 600px; overflow-y: auto; background: #f8f9fa; padding: 15px; border-radius: 5px;">
                        <?php foreach ($debug_steps as $step): ?>
                            <div style="margin: 3px 0; padding: 3px;">
                                <?php
                                $color = 'black';
                                if (strpos($step, '✅') !== false) $color = 'green';
                                if (strpos($step, '❌') !== false) $color = 'red';
                                if (strpos($step, '⚠️') !== false) $color = 'orange';
                                if (strpos($step, '===') !== false) $color = 'blue';
                                ?>
                                <span style="color: <?= $color ?>;"><?= htmlspecialchars($step) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>🧪 Actions de test</h5>
                </div>
                <div class="card-body">
                    <div class="btn-group">
                        <a href="?refresh=1" class="btn btn-primary">🔄 Relancer le diagnostic</a>
                        <a href="login.php" class="btn btn-outline-secondary">← Retour Login</a>
                        <a href="test-autocomplete.php" class="btn btn-outline-success">Test Autocomplétion</a>
                        <a href="index.php" class="btn btn-outline-info">Accueil</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="alert alert-info">
                <h6>📝 Analyse automatique :</h6>
                <div id="auto-analysis"></div>
            </div>
        </div>
    </div>
</div>

<script>
    // Analyse automatique des résultats
    document.addEventListener('DOMContentLoaded', function() {
        const logContent = document.querySelector('.card-body div[style*="font-family: monospace"]').textContent;
        const analysisDiv = document.getElementById('auto-analysis');

        let analysis = [];

        if (logContent.includes('❌ ERREUR DB:')) {
            analysis.push('🚨 <strong>Problème de base de données détecté</strong>');
        }

        if (logContent.includes('SQLSTATE[42601]')) {
            analysis.push('🚨 <strong>Erreur SQL dans le système d\'authentification</strong>');
        }

        if (logContent.includes('✅ Connexion DB réussie')) {
            analysis.push('✅ Base de données accessible');
        }

        if (logContent.includes('❌ Fonction isAuthenticated() non trouvée')) {
            analysis.push('🚨 <strong>Fonctions d\'authentification manquantes</strong>');
        }

        if (logContent.includes('✅ functions/auth.php inclus')) {
            analysis.push('✅ Fichier auth.php chargé correctement');
        }

        if (analysis.length === 0) {
            analysis.push('ℹ️ Diagnostic en cours...');
        }

        analysisDiv.innerHTML = analysis.join('<br>');
    });
</script>

</body>
</html>