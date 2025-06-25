<?php
session_start();
require_once dirname(__DIR__) . '/vendor/autoload.php';

use Olivierguissard\EcoRide\Config\Database;
use class\Users;

require_once 'functions/auth.php';
if (est_connecte()) {
    header('Location: ../public/profil.php');
    exit;
}

$pdo = Database::getConnection();

$pageTitle = 'Créer un compte - EcoRide';
require_once 'header.php';
require_once 'class/Users.php';

$error = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
                $user = new Users(
                    ($_POST['firstName']),
                    ($_POST['lastName']),
                    ($_POST['email']),
                    ($_POST['password']));
                $user->setPassword();
                $user->saveToDatabase($pdo);

                $_SESSION['connecte'] = true;
                $_SESSION['email'] = $user->getEmail();
                $_SESSION['firstName'] = $user->getFirstName();
                $_SESSION['lastName'] = $user->getLastName();
                $_SESSION['status'] = $user->getStatus();
                $_SESSION['role'] = $user->getRole();
                $_SESSION['credits'] = $user->getCredits();
                $_SESSION['ranking'] = $user->getRanking();
                $_SESSION['profilePicture'] = $user->getProfilePicture();
                $_SESSION['success_registration'] = true;

                header('Location: ../public/profil.php');
                exit;
            }
        } catch (PDOException $e) {
            $error[] = 'Erreur lors de l\'inscription : ' . $e->getMessage();
        }
    }
}
?>
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

<?php
require_once 'footer.php';
?>
