<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Helpers/helpers.php';
require_once 'functions/auth.php';
startSession();
requireAuth();
updateActivity();

use Olivierguissard\EcoRide\Service\PaymentService;

$userId = getUserId();
$tripId = (int)($_GET['trip_id'] ?? 0); // Trajet de retour

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $creditAmount = (int)($_POST['creditAmount'] ?? 0);

    if ($creditAmount < 5) {
        $_SESSION['flash_error'] = 'Le montant minimum est de 5 crédits.';
    } elseif ($creditAmount % 5 !== 0) {
        $_SESSION['flash_error'] = 'Le montant doit être un multiple de 5.';
    } else {
        try {
            // Utiliser PaymentService
            if (PaymentService::addCredits($userId, $creditAmount)) {
                $_SESSION['flash_success'] = [
                        'message' => "Vous avez acheté $creditAmount crédits avec succès !",
                        'trip_id' => $tripId
                ];
                header('Location: paiements.php');
                exit;
            } else {
                $_SESSION['flash_error'] = 'Erreur lors de l\'achat de crédits.';
            }
        } catch (Exception $e) {
            $_SESSION['flash_error'] = 'Erreur: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Acheter des crédits - EcoRide';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="assets/pictures/logoEcoRide.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <title><?= $pageTitle ?></title>
</head>
<body>
<header>
    <nav class="navbar bg-body-tertiary mb-3">
        <div class="container px-2" style="max-width: 900px;">
            <a class="navbar-brand" href="index.php">
                <img src="assets/pictures/logoEcoRide.png" alt="Logo EcoRide" width="45" class="rounded">
            </a>
            <h2 class="fw-bold text-success fs-4 mb-0">Acheter des crédits</h2>
            <?= displayInitialsButton(); ?>
        </div>
    </nav>
</header>

<main>
    <div class="container mt-3 mb-5 px-2" style="max-width: 600px;">

        <!-- Messages flash -->
        <?php if (isset($_SESSION['flash_error'])): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($_SESSION['flash_error']) ?>
            </div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>

        <!-- Formulaire d'achat -->
        <div class="card shadow-sm">
            <div class="card-body">
                <form method="POST">
                    <div class="mb-4">
                        <label for="creditAmount" class="form-label fw-bold">Montant à acheter</label>
                        <input type="number" class="form-control form-control-lg" id="creditAmount"
                               name="creditAmount" min="5" step="5" placeholder="Exemple: 20" required>
                        <div class="form-text">Minimum 5 crédits, par multiples de 5.</div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="bi bi-cart-plus"></i> Acheter des crédits
                        </button>
                        <a href="rechercher.php" class="btn btn-outline-primary">
                            <i class="bi bi-arrow-left"></i> Retour aux trajets
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>