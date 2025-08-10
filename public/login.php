<?php

use Olivierguissard\EcoRide\Config\Database;

require_once __DIR__ . '/../vendor/autoload.php';
require_once 'functions/auth.php';
startSession();
if (isAuthenticated()) {
    updateActivity();
}

$erreur = null;
$debug_info = []; // Stocke les infos de debug

// Debug silencieux (pas d'echo)
$debug_info[] = "SESSION: " . print_r($_SESSION, true);
$debug_info[] = "isAuthenticated(): " . (isAuthenticated() ? 'TRUE' : 'FALSE');
$debug_info[] = "User ID: " . (getUserId() ?? 'NULL');

if (isAuthenticated()) {
    header('Location: rechercher.php');
    exit;
}

if (!empty($_POST['emailUser']) && !empty($_POST['passwordUser'])) {
    $email = $_POST['emailUser'];
    $password = $_POST['passwordUser'];
    $remember = isset($_POST['remember']) && $_POST['remember'] === '1';

    $debug_info[] = "Tentative de connexion pour: " . $email;

    try {
        // Requête pour récupérer l'utilisateur en db
        $debug_info[] = "Tentative de connexion à la base...";
        $pdo = Database::getConnection();
        $debug_info[] = "Connexion DB OK";

        $sql = "SELECT COUNT(*) as total FROM users";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        $debug_info[] = "Nombre total d'utilisateurs: " . $count['total'];

        $sql = "SELECT * FROM users WHERE email = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $debug_info[] = "Utilisateur trouvé: " . ($user ? 'OUI' : 'NON');

        if ($user) {
            $debug_info[] = "ID utilisateur: " . $user['user_id'];
            $debug_info[] = "Email trouvé: " . $user['email'];
            $passwordCheck = password_verify($password, $user['password']);
            $debug_info[] = "Vérification mot de passe: " . ($passwordCheck ? 'OK' : 'ÉCHEC');

            if ($passwordCheck) {
                $debug_info[] = "Avant loginUserComplete";

                // Utilise la fonction Remember Me
                loginUserComplete($user, $remember);

                $debug_info[] = "Après loginUserComplete - SESSION: " . print_r($_SESSION, true);
                $debug_info[] = "isAuthenticated() après login: " . (isAuthenticated() ? 'TRUE' : 'FALSE');

                // Redirection SANS echo avant
                header('Location: profil.php');
                exit;
            }
        }

        $erreur = 'Adresse e-mail ou mot de passe incorrect';
        // Log de la tentative de connexion échoué
        if ($user) {
            logLogin($user['user_id'], 'password', false);
        }

    } catch (Exception $e) {
        $debug_info[] = "ERREUR: " . $e->getMessage();
        $erreur = 'Erreur de connexion à la base de données';
    }
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
    <title><?php if (isset($pageTitle)) { echo $pageTitle; } else { echo 'EcoRide - Covoiturage écologique';} ?></title>
</head>

<body>
<div class="container py-5">
    <div class="min-vh-100 d-flex flex-column px-3 py-5 main-wrapper">
        <header class="d-flex justify-content-between align-items-center mb-4">
            <a href="index.php" class="btn btn-light p-2 rounded-circle">
                <i class="bi bi-arrow-left"></i>
            </a>
            <img src="assets/pictures/logoEcoRide.png" alt="logo EcoRide" class="logo rounded" width="90em">
            <div style="width: 40px;"></div>
        </header>

        <!-- Main -->
        <main class="flex-fill">
            <h1 class="h4 fw-semibold text-center mb-4">Connexion</h1>

            <!-- DEBUG INFO (affiché après le HTML, pas de conflit headers) -->
            <?php if (!empty($debug_info)): ?>
                <div class="alert alert-info small">
                    <strong>🔍 DEBUG INFO:</strong><br>
                    <?php foreach ($debug_info as $info): ?>
                        <div style="font-family: monospace; font-size: 11px; margin: 2px 0; padding: 2px; background: rgba(255,255,255,0.5);">
                            <?= htmlspecialchars($info) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($erreur) : ?>
                <div class="alert alert-danger" role="alert">
                    <?= htmlspecialchars($erreur) ?>
                </div>
            <?php endif; ?>

            <!-- Message d'information pour les connexions automatiques -->
            <?php if (isset($_SESSION['auto_login']) && $_SESSION['auto_login']) : ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i>
                    Connexion automatique réussie ! Bienvenue <?= htmlspecialchars($_SESSION['firstName']) ?>.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['auto_login']); ?>
            <?php endif; ?>

            <form action="" method="post" id="loginForm" class="mb-4">
                <div class="mb-3 position-relative">
                    <span class="position-absolute top-50 translate-middle-y start-0 ps-3 text-secondary">
                        <i class="ri-mail-line"></i>
                    </span>
                    <input type="email" id="email" class="form-control ps-5" name="emailUser" placeholder="Adresse e-mail" required>
                </div>

                <div class="mb-3 position-relative">
                    <span class="position-absolute top-50 translate-middle-y start-0 ps-3 text-secondary">
                        <i class="ri-lock-line"></i>
                    </span>
                    <input type="password" id="password" class="form-control ps-5 pe-5" name="passwordUser" placeholder="Mot de passe" required>
                    <button type="button" id="togglePassword" class="btn position-absolute top-50 end-0 translate-middle-y pe-3">
                        <i class="ri-eye-off-line" id="passwordIcon"></i>
                    </button>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="remember" name="remember" value="1">
                        <label class="form-check-label" for="remember">
                            <span>Se souvenir de moi</span>
                            <span class="text-muted d-block" style="font-size: 0.8rem;">Rester connecté pendant 6 mois</span>
                        </label>
                    </div>
                    <a href="#" class="text-decoration-none text-success small">Mot de passe oublié ?</a>
                </div>

                <button type="submit" class="btn btn-success w-100">Se connecter</button>
            </form>

            <!-- Navigation de debug temporaire -->
            <div class="alert alert-warning">
                <strong>🚀 NAVIGATION DEBUG:</strong><br>
                <div class="btn-group-vertical w-100 mt-2">
                    <a href="profil.php" class="btn btn-sm btn-outline-primary">Aller au profil</a>
                    <a href="rechercher.php" class="btn btn-sm btn-outline-success">Aller à la recherche</a>
                    <a href="proposer.php" class="btn btn-sm btn-outline-info">Proposer un trajet</a>
                    <a href="inscription.php" class="btn btn-sm btn-outline-secondary">Créer un compte</a>
                </div>
            </div>

            <!-- Divider -->
            <div class="d-flex align-items-center my-4">
                <hr class="flex-grow-1">
                <span class="px-3 text-muted small">ou</span>
                <hr class="flex-grow-1">
            </div>

            <!-- Sign up -->
            <div class="text-center">
                <p class="text-muted mb-3">Pas encore utilisateur EcoRide ?</p>
                <a href="inscription.php" class="btn btn-outline-success w-100">Se créer un compte</a>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/login.js"></script>
</body>
</html>