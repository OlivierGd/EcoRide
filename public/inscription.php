<?php
$pageTitle = 'Créer un compte - EcoRide';
require_once 'header.php';
require_once 'class/Users.php';
require_once dirname(__DIR__) . '/vendor/autoload.php';

use class\Users;
use Dotenv\Dotenv;

// Charge le fichier .env
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();
$dotenv->required(['DB_USER', 'DB_PASSWORD'])->notEmpty();

$pdo = new PDO("pgsql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};user={$_ENV['DB_USER']};password={$_ENV['DB_PASSWORD']}");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Permet de retourner l'erreur si problème

$error = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validation des champs
    if (empty($_POST['firstName']) || strlen($_POST['firstName']) < 2) {
        $error = 'Le prénom doit contenir au moins 2 caractères';
    }
    if (empty($_POST['lastName']) || strlen($_POST['lastName']) < 2) {
        $error = 'Le nom doit contenir au moins 2 caractères';
    }
    if (empty($_POST_['email']) || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Email invalide';
    }
    if (empty($_POST['password']) || strlen($_POST['password']) < 6) {
        $error = 'Le mot de passe doit contenir au moins 6 caractères.';
    }
    if ($_POST['password'] !== $_POST['confirmPassword']) {
        $error = 'Les mots de passe ne correspondent pas.';
    }

    // Si pas d'erreurs, on procède à l'inscription
    if (empty($error)) {
        try {
            // Vérifie si l'email existe déjà
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
            $stmt->execute([$_POST['email']]);
            $emailExist = $stmt->fetchColumn();
            if ($emailExist > 0) {
                $error[] = 'Cette adresse email est déjà utilisée';
            } else {
                $user = new Users(trim($_POST['firstName']),
                    trim($_POST['lastName']),
                    trim($_POST['email']),
                    password_hash($_POST['password'], PASSWORD_DEFAULT));
                $user->saveToDatabase($pdo);
                $success = true;
            }
        } catch (PDOException $e) {
            $error[] = 'Erreur lors de l\'inscription : ' . $e->getMessage();
            error_log($e->getMessage());
        }
    }
}
?>
<main>
    <!-- Formulaire nouvelle inscription -->
    <section class="mt-5">
        <h2 class="fw-bold mb-4">Votre inscription</h2>
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
