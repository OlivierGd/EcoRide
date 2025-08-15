<?php

require __DIR__ . '/../vendor/autoload.php';

use Olivierguissard\EcoRide\Config\Database;
use Olivierguissard\EcoRide\Model\Mailer;

require_once 'functions/auth.php';
startSession();

if (isAuthenticated()) {
    header('Location: rechercher.php');
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Veuillez saisir une adresse email valide';
    } else {
        try {
            $pdo = Database::getConnection();

            // Permet aux comptes créés par admin (même non encore activés) de recevoir l'email
            $sql = "SELECT user_id, firstname, lastname, password FROM users WHERE email = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Générer un token unique
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour')); // Expire dans 1 heure

                // D'abord supprimer les anciens tokens de cet utilisateur
                $sql = "DELETE FROM password_reset_tokens WHERE user_id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$user['user_id']]);

                // Puis insérer le nouveau token
                $sql = "INSERT INTO password_reset_tokens (user_id, token, expires_at, created_at) 
                        VALUES (?, ?, ?, NOW())";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$user['user_id'], $token, $expires]);

                // Envoyer l'email
                $mailer = new Mailer();
                $resetUrl = "http://" . $_SERVER['HTTP_HOST'] . "/reset_password.php?token=" . $token;

                $subject = "Réinitialisation de votre mot de passe EcoRide";

                // Message adapté si le compte a déjà un mot de passe ou non
                if (empty($user['password'])) {
                    // Compte créé par admin, pas encore activé
                    $htmlContent = "
                        <h2>Activation de votre compte EcoRide</h2>
                        <p>Bonjour {$user['firstname']},</p>
                        <p>Votre compte EcoRide a été créé par un administrateur.</p>
                        <p>Cliquez sur le lien ci-dessous pour définir votre mot de passe et activer votre compte :</p>
                        <p><a href='{$resetUrl}' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Activer mon compte</a></p>
                        <p>Ce lien expire dans 1 heure.</p>
                        <p>L'équipe EcoRide</p>
                    ";
                } else {
                    // Compte normal avec mot de passe existant
                    $htmlContent = "
                        <h2>Réinitialisation de mot de passe</h2>
                        <p>Bonjour {$user['firstname']},</p>
                        <p>Vous avez demandé à réinitialiser votre mot de passe EcoRide.</p>
                        <p>Cliquez sur le lien ci-dessous pour créer un nouveau mot de passe :</p>
                        <p><a href='{$resetUrl}' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Réinitialiser mon mot de passe</a></p>
                        <p>Ce lien expire dans 1 heure.</p>
                        <p>Si vous n'avez pas demandé cette réinitialisation, ignorez cet email.</p>
                        <p>L'équipe EcoRide</p>
                    ";
                }

                $textContent = strip_tags(str_replace('<br>', "\n", $htmlContent));

                try {
                    $result = $mailer->sendEmail(
                            $email,
                            $user['firstname'] . ' ' . $user['lastname'],
                            $subject,
                            $htmlContent,
                            $textContent
                    );

                    if ($result['success']) {
                        $message = 'Un email de réinitialisation a été envoyé à votre adresse.';
                    } else {
                        $error = 'Erreur lors de l\'envoi de l\'email. Veuillez réessayer.';
                        error_log("Erreur Mailjet: " . print_r($result, true));
                    }
                } catch (Exception $e) {
                    $error = 'Erreur lors de l\'envoi de l\'email. Veuillez réessayer.';
                    error_log("Exception Mailer: " . $e->getMessage());
                }
            } else {
                // Même message pour éviter l'énumération d'emails
                $message = 'Si cette adresse existe dans notre système, un email de réinitialisation a été envoyé.';
            }

        } catch (Exception $e) {
            $error = 'Une erreur est survenue. Veuillez réessayer plus tard.';
            error_log("Erreur forgot_password: " . $e->getMessage());
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <title>Mot de passe oublié - EcoRide</title>
</head>

<body>
<div class="container py-5">
    <div class="min-vh-100 d-flex flex-column px-3 py-5 main-wrapper">
        <header class="d-flex justify-content-between align-items-center mb-4">
            <a href="login.php" class="btn btn-light p-2 rounded-circle">
                <i class="bi bi-arrow-left"></i>
            </a>
            <img src="assets/pictures/logoEcoRide.png" alt="logo EcoRide" class="logo rounded" width="90em">
            <div style="width: 40px;"></div>
        </header>

        <main class="flex-fill">
            <h1 class="h4 fw-semibold text-center mb-4">Mot de passe oublié</h1>

            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($message): ?>
                <div class="alert alert-success" role="alert">
                    <i class="bi bi-check-circle me-2"></i>
                    <?= htmlspecialchars($message) ?>
                </div>
                <div class="text-center mt-4">
                    <a href="login.php" class="btn btn-success">Retour à la connexion</a>
                </div>
            <?php else: ?>
                <p class="text-center text-muted mb-4">
                    Saisissez votre adresse email pour recevoir un lien de réinitialisation.
                </p>

                <form action="" method="post" class="mb-4">
                    <div class="mb-3 position-relative">
                        <span class="position-absolute top-50 translate-middle-y start-0 ps-3 text-secondary">
                            <i class="bi bi-envelope"></i>
                        </span>
                        <input type="email"
                               name="email"
                               class="form-control ps-5"
                               placeholder="Votre adresse email"
                               required
                               value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                    </div>

                    <button type="submit" class="btn btn-success w-100 mb-3">
                        <i class="bi bi-send"></i> Envoyer le lien de réinitialisation
                    </button>
                </form>

                <div class="text-center">
                    <a href="login.php" class="text-decoration-none text-muted">
                        <i class="bi bi-arrow-left"></i> Retour à la connexion
                    </a>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>