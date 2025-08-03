<?php

use Olivierguissard\EcoRide\Config\Database;
use Olivierguissard\EcoRide\Model\Users;

require __DIR__ . '/../vendor/autoload.php';
startSession();

require_once 'functions/auth.php';
if (isAuthenticated()) {
    header('Location: /profil.php');
    exit;
}

$pdo = Database::getConnection();

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
            // Vérifie si l'email existe déjà
            $stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = ?');
            $stmt->execute([trim($_POST['email'])]);

            if ($stmt->fetchColumn() > 0) {
                $error[] = "Un compte avec cette adresse existe déjà";
            } else {
                $user = new Users([
                    'firstname' => $firstName,
                    'lastname'  => $lastName,
                    'email'     => $email,
                    'password'  => $password
                ]);
                $user->setPassword();
                $user_id = $user->saveUserToDatabase($pdo);

                if ($user_id) {
                    $_SESSION['user_id']    = $user_id;
                    $_SESSION['connecte']   = true;
                    $_SESSION['email']      = $user->getEmail();
                    $_SESSION['firstName']  = $user->getFirstName();
                    $_SESSION['lastName']   = $user->getLastName();
                    $_SESSION['status']     = $user->getStatus();
                    $_SESSION['role']       = $user->getRole();
                    $_SESSION['credits']    = $user->getCredits();
                    $_SESSION['ranking']    = $user->getRanking();
                    $_SESSION['profilePicture'] = $user->getProfilePicture();
                    $_SESSION['success_registration'] = true;

                    header('Location: /profil.php');
                    exit;
                } else {
                    $error[] = 'Erreur lors de la création du compte';
                }
            }
        } catch (PDOException $e) {
            $error[] = 'Erreur lors de l\'inscription : ' . $e->getMessage();
        }
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
    <link rel="stylesheet" href="assets/css/index.css">
    <title><?php if (isset($pageTitle)) { echo $pageTitle; } else { echo 'EcoRide - Covoiturage écologique';} ?></title>
</head>
<body>
    <header>
        <nav class="navbar fixed-top bg-white shadow-sm">
            <div class="container" style="max-width: 900px">
                <a class="navbar-brand" href="/index.php">
                    <img src="assets/pictures/logoEcoRide.png" alt="logo EcoRide" class="d-inline-block align-text-center rounded" width="60">
                    EcoRide
                </a>
                <a class="btn btn-success" role="button" href="/login.php">Connexion</a>
            </div>
        </nav>
        <div class="<?= (isset($erreur) || ini_get('display_errors')) ? 'has-error' : '' ?>">
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
            <div class="alert alert-success" role="alert">
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


