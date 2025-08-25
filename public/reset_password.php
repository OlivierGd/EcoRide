<?php

require __DIR__ . '/../vendor/autoload.php';

use Olivierguissard\EcoRide\Config\Database;

require_once 'functions/auth.php';
startSession();

// Déconnecter l'utilisateur s'il est connecté (pour éviter les conflits de session)
if (isAuthenticated()) {
    session_destroy();
    startSession();
}

$token = $_GET['token'] ?? '';
$error = '';
$success = false;
$user = null;

// Vérifier le token
if (empty($token)) {
    $error = 'Token manquant ou invalide';
} else {
    try {
        $pdo = Database::getConnection();

        // Vérifier si le token existe et n'a pas expiré
        $sql = "SELECT prt.*, u.firstname, u.lastname, u.email 
                FROM password_reset_tokens prt 
                JOIN users u ON u.user_id = prt.user_id 
                WHERE prt.token = ? AND prt.expires_at > NOW() AND prt.used_at IS NULL";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$token]);
        $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$tokenData) {
            $error = 'Token invalide ou expiré';
        } else {
            $user = $tokenData;
        }

    } catch (Exception $e) {
        $error = 'Erreur lors de la vérification du token';
        error_log("Erreur reset_password token: " . $e->getMessage());
    }
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($password) || strlen($password) < 6) {
        $error = 'Le mot de passe doit contenir au moins 6 caractères';
    } elseif ($password !== $confirmPassword) {
        $error = 'Les mots de passe ne correspondent pas';
    } else {
        try {
            // Hasher le nouveau mot de passe
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Commencer une transaction
            $pdo->beginTransaction();

            // Mettre à jour le mot de passe de l'utilisateur
            $sql = "UPDATE users SET password = ? WHERE user_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$hashedPassword, $user['user_id']]);

            // Marquer le token comme utilisé
            $sql = "UPDATE password_reset_tokens SET used_at = NOW() WHERE token = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$token]);

            // Supprimer tous les autres tokens de cet utilisateur
            $sql = "DELETE FROM password_reset_tokens WHERE user_id = ? AND token != ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$user['user_id'], $token]);

            $pdo->commit();
            $success = true;

        } catch (Exception $e) {
            $pdo->rollback();
            $error = 'Erreur lors de la mise à jour du mot de passe';
            error_log("Erreur reset_password update: " . $e->getMessage());
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
    <link rel="stylesheet" href="assets/css/reset_password.css">
    <title>Nouveau mot de passe - EcoRide</title>
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
            <?php if ($success): ?>
                <div class="text-center">
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <strong>Mot de passe mis à jour !</strong>
                    </div>
                    <h1 class="h4 fw-semibold mb-4">Réinitialisation réussie</h1>
                    <p class="text-muted mb-4">Votre mot de passe a été mis à jour avec succès.</p>
                    <a href="login.php" class="btn btn-success">
                        <i class="bi bi-box-arrow-in-right"></i> Se connecter
                    </a>
                </div>

            <?php elseif ($error): ?>
                <div class="text-center">
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                    <a href="forgot_password.php" class="btn btn-outline-success">
                        Demander un nouveau lien
                    </a>
                </div>

            <?php else: ?>
                <h1 class="h4 fw-semibold text-center mb-2">Nouveau mot de passe</h1>
                <p class="text-center text-muted mb-4">
                    Bonjour <?= htmlspecialchars($user['firstname']) ?>, choisissez votre nouveau mot de passe.
                </p>

                <form action="" method="post" id="resetForm">
                    <div class="mb-3 position-relative">
                        <span class="position-absolute top-50 translate-middle-y start-0 ps-3 text-secondary">
                            <i class="bi bi-lock"></i>
                        </span>
                        <input type="password" name="password" class="form-control ps-5 pe-5" placeholder="Nouveau mot de passe"
                               required minlength="6" id="password">
                        <button type="button" id="togglePassword1" class="btn position-absolute top-50 end-0 translate-middle-y pe-3">
                            <i class="bi bi-eye-slash" id="passwordIcon1"></i>
                        </button>
                    </div>

                    <div class="mb-4 position-relative">
                        <span class="position-absolute top-50 translate-middle-y start-0 ps-3 text-secondary">
                            <i class="bi bi-lock-fill"></i>
                        </span>
                        <input type="password" name="confirm_password" class="form-control ps-5 pe-5" placeholder="Confirmer le mot de passe"
                               required minlength="6" id="confirmPassword">
                        <button type="button" id="togglePassword2" class="btn position-absolute top-50 end-0 translate-middle-y pe-3">
                            <i class="bi bi-eye-slash" id="passwordIcon2"></i>
                        </button>
                    </div>

                    <div class="mb-3">
                        <small class="text-muted">
                            <i class="bi bi-info-circle"></i>
                            Le mot de passe doit contenir au moins 6 caractères.
                        </small>
                    </div>

                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-shield-check"></i> Mettre à jour le mot de passe
                    </button>
                </form>
            <?php endif; ?>
        </main>
    </div>
</div>

<script src="assets/js/reset_password.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>