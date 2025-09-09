<?php

require __DIR__ . '/../vendor/autoload.php';

use Olivierguissard\EcoRide\Config\Database;
use Olivierguissard\EcoRide\Model\Users;
use Olivierguissard\EcoRide\Service\PaymentService;

require_once 'functions/auth.php';
startSession();
if (isAuthenticated()) {
    updateActivity();
    header('Location: index.php');
    exit;
}

$pageTitle = 'Créer un compte - EcoRide';

$error = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération des POST
    $firstName  = trim(ucfirst($_POST['firstName'] ?? ''));
    $lastName   = trim(mb_strtoupper($_POST['lastName'] ?? ''));
    $email      = trim($_POST['email'] ?? '');
    $password   = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';

    // Validation des champs
    if (empty($_POST['firstName']) || strlen($_POST['firstName']) < 2) {
        $error[] = 'Le prénom doit contenir au moins 2 caractères';
    }
    if (empty($_POST['lastName']) || strlen($_POST['lastName']) < 2) {
        $error[] = 'Le nom doit contenir au moins 2 caractères';
    }
    if (empty($_POST['email']) || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $error[] = 'Email invalide';
    }
    if (empty($_POST['password']) || strlen($_POST['password']) < 6) {
        $error[] = 'Le mot de passe doit contenir au moins 6 caractères.';
    }
    if ($_POST['password'] !== $_POST['confirmPassword']) {
        $error[] = 'Les mots de passe ne correspondent pas.';
    }

    // Si pas d'erreurs, on procède à l'inscription
    if (empty($error)) {
        try {
            $pdo = Database::getConnection();
            // Vérifie si l'email existe déjà
            $sql = 'SELECT user_id FROM users WHERE email = :email';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':email' => $email]);

            if ($stmt->fetchColumn() > 0) {
                $error[] = "Un compte avec cette adresse existe déjà";
            } else {
                // Crée l'utilisateur avec 0 crédit
                $user = new Users([
                        'firstname' => $firstName,
                        'lastname'  => $lastName,
                        'email'     => $email,
                        'password'  => $password,
                        'credits'   => 0, // Démarre avec 0 crédits
                ]);
                $user->setPassword();
                $user_id = $user->saveUserToDatabase($pdo);

                if ($user_id) {
                    // Ajoute les crédits de bienvenue via PaymentsService
                    if (PaymentService::addWelcomeCredits($user_id)) {
                        // Récupère les crédits mis à jour
                        $updatedUser = Users::findUser($user_id);

                        startSession();
                        session_regenerate_id(true);

                        $_SESSION['user_id']    = $user_id;
                        $_SESSION['connecte']   = true;
                        $_SESSION['email']      = $updatedUser->getEmail();
                        $_SESSION['firstName']  = $updatedUser->getFirstName();
                        $_SESSION['lastName']   = $updatedUser->getLastName();
                        $_SESSION['status']     = $updatedUser->getStatus();
                        $_SESSION['role']       = $updatedUser->getRole();
                        $_SESSION['credits']    = $updatedUser->getCredits();
                        $_SESSION['ranking']    = $updatedUser->getRanking();
                        $_SESSION['profilePicture'] = $updatedUser->getProfilePicture();
                        $_SESSION['success_registration'] = true;

                        header('Location: /index.php');
                        exit;
                    } else {
                        $pdo->rollBack();
                        $error[] = "Erreur lors de l'attribution des crédits de bienvenue.";
                    }
                } else {
                    $pdo->rollBack();
                    $error[] = 'Erreur lors de la création du compte';
                }
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error[] = 'Erreur lors de l\'inscription : ' . $e->getMessage();
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error[] = 'Erreur lors de l\'attribution des crédits : ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/webp" href="assets/pictures/logoEcoRide.webp">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/index.css">
    <title><?php if (isset($pageTitle)) { echo $pageTitle; } else { echo 'EcoRide - Covoiturage écologique';} ?></title>
</head>
<body>
<header>
    <nav class="navbar fixed-top bg-white shadow-sm">
        <div class="container" style="max-width: 900px">
            <a class="navbar-brand" href="index.php">
                <img src="assets/pictures/logoEcoRide.webp" alt="logo EcoRide" class="d-inline-block align-text-center rounded" width="60">
                EcoRide
            </a>
            <a class="btn btn-success" role="button" href="login.php">Connexion</a>
        </div>
    </nav>
    <div class="<?= (isset($error) || ini_get('display_errors')) ? 'has-error' : '' ?>"></div>
</header>
<main>
    <!-- Formulaire nouvelle inscription -->
    <section class="mt-5">
        <h2 class="fw-bold mb-4">Votre inscription</h2>
        <?php if (!empty($error)) : ?>
            <div class="alert alert-danger">
                <?= implode('<br>', array_map('htmlspecialchars', $error)) ?>
            </div>
        <?php endif; ?>

        <form action="" method="post" id="formNewUser" class="p-4 bg-white rounded-4 shadow-sm">

            <!-- Prénom utilisateur -->
            <div class="input-group mb-3 bg-light rounded-3">
                        <span class="input-group-text bg-transparent border-0">
                           <i class="bi bi-person text-secondary"></i>
                       </span>
                <input type="text" name="firstName" class="form-control border-0 bg-transparent" id="firstName"
                       placeholder="Prénom" required minlength="2" value="<?= isset($_POST['firstName']) ? htmlspecialchars($_POST['firstName'], ENT_QUOTES, 'UTF-8') : '' ?>">
            </div>

            <!-- Nom utilisateur -->
            <div class="input-group mb-3 bg-light rounded-3">
                <span class="input-group-text bg-transparent border-0">
                    <i class="bi bi-person text-secondary"></i>
                </span>
                <input type="text" name="lastName" class="form-control border-0 bg-transparent" id="lastName"
                       placeholder="Nom" required minlength="2" value="<?=isset($_POST['lastName']) ? htmlspecialchars($_POST['lastName'], ENT_QUOTES, 'UTF-8') : '' ?>">
            </div>

            <!-- Email utilisateur -->
            <div class="input-group mb-4 bg-light rounded-3">
                <span class="input-group-text bg-transparent border-0">
                    <i class="bi bi-envelope-at text-secondary"></i>
                </span>
                <input type="email" name="email" class="form-control border-0 bg-transparent" id="email"
                       placeholder="Email" required value="<?=isset($_POST['email']) ? htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8') : '' ?>">
            </div>

            <!-- Mot de passe utilisateur -->
            <div class="input-group mb-4 bg-light rounded-3">
                <span class="input-group-text bg-transparent border-0">
                    <i class="bi bi-lock text-secondary"></i>
                </span>
                <input type="password" name="password" class="form-control border-0 bg-transparent" id="password" placeholder="Mot de passe" required minlength="6">
            </div>

            <!-- Confirmation mot de passe utilisateur -->
            <div class="input-group mb-4 bg-light rounded-3">
                <span class="input-group-text bg-transparent border-0">
                    <i class="bi bi-lock text-secondary"></i>
                </span>
                <input type="password" name="confirmPassword" class="form-control border-0 bg-transparent" id="confirmPassword" placeholder="Confirmer le mot de passe" required>
            </div>

            <!-- Bouton s'inscrire -->
            <div class="d-grid">
                <button type="submit" class="btn btn-success d-flex justify-content-center align-items-center gap-2 rounded-3">
                    S'inscrire
                </button>
            </div>
        </form>
    </section>
</main>

<footer>
    <?php include 'footer.php'; ?>
</footer>
</body>
