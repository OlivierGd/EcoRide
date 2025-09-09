<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Model/Mailer.php';
require_once __DIR__ . '/../src/Helpers/helpers.php';

require_once 'functions/auth.php';
startSession();
requireAuth();
updateActivity();

use Olivierguissard\EcoRide\Model\Bookings;
use Olivierguissard\EcoRide\Model\Trip;
use Olivierguissard\EcoRide\Model\Users;
use Olivierguissard\EcoRide\Service\PaymentService;

$bookingId = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;
$booking = Bookings::findBookingByBookingId($bookingId);

if (!$booking) {
    die('Cette réservation est invalide ou annulée.');
}

if ($booking->getStatus() === 'annule') {
    die('Cette réservation a été annulée.');
}

if ($booking->getUserId() !== getUserId()) {
    die('Vous ne pouvez pas valider ce trajet.');
}

$trip = Trip::loadTripById($booking->getTripId());
$driver = Users::findUser($trip->getDriverId());

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = (int)($_POST['rating'] ?? 5);
    $commentaire = trim($_POST['commentaire'] ?? '');

    // Validation
    if ($rating < 1 || $rating > 5) {
        $error = "Attribuer une note entre 1 et 5.";
    } else {
        // 1. Insérer dans reviews
        $pdo = \Olivierguissard\EcoRide\Config\Database::getConnection();
        $sql = 'INSERT INTO reviews (trip_id, booking_id, user_id, rating, commentaire, status_review) VALUES (?, ?, ?, ?, ?, ?)';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
                $booking->getTripId(),
                $bookingId,
                $booking->getUserId(),
                $rating,
                $commentaire,
                'pending'
        ]);

        // Mettre à jour le booking en "valide"
        $booking->updateStatusValidation('valide');

        /**
         * Mettre à jour le ranking du chauffeur
         */
        $sqlMoy = "SELECT AVG(rating) FROM reviews
           JOIN trips ON reviews.trip_id = trips.trip_id
           WHERE trips.driver_id = ?";
        $stmtMoy = $pdo->prepare($sqlMoy);
        $stmtMoy->execute([$trip->getDriverId()]);
        $newRanking = round($stmtMoy->fetchColumn(), 2);
        $sqlUp = "UPDATE users SET ranking = ? WHERE user_id = ?";
        $stmtUp = $pdo->prepare($sqlUp);
        $stmtUp->execute([$newRanking, $trip->getDriverId()]);

        /**
         * Payer le chauffeur
         */
        try {
            // Vérifier si déjà payé
            $sqlCheckPaid = "SELECT COUNT(*) FROM payments 
                     WHERE trip_id = ? AND user_id = ? AND type_transaction = 'gain_course'";
            $stmtCheckPaid = $pdo->prepare($sqlCheckPaid);
            $stmtCheckPaid->execute([$booking->getTripId(), $trip->getDriverId()]);
            $alreadyPaid = $stmtCheckPaid->fetchColumn() > 0;

            if (!$alreadyPaid) {
                // Calculer le total des réservations payées
                $sqlCredits = "SELECT SUM(ABS(montant)) AS total
                      FROM payments p
                      WHERE p.trip_id = ?
                      AND p.type_transaction = 'reservation'
                      AND p.statut_transaction = 'paye'";
                $stmtCredits = $pdo->prepare($sqlCredits);
                $stmtCredits->execute([$booking->getTripId()]);
                $totalCredits = (float)$stmtCredits->fetchColumn();

                if ($totalCredits > 0) {
                    // Payer le chauffeur via PaymentService
                    PaymentService::payDriver($trip->getDriverId(), $totalCredits, $trip->getTripId());
                }
            }
        } catch (Exception $e) {
            error_log("Erreur paiement chauffeur : " . $e->getMessage());
        }

        // Rediriger ou afficher message succès
        header("Location: validation.php?booking_id={$bookingId}&success=1");
        exit;
    }
}

$pageTitle = 'Validation de votre trajet';
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
    <nav class="navbar bg-body-tertiary">
        <div class="container" style="max-width: 900px;">
            <a class="navbar-brand" href="index.php">
                <img src="assets/pictures/logoEcoRide.webp" alt="Logo EcoRide" width="60" class="d-inline-block align-text-center rounded">
            </a>
            <h2 class="fw-bold mb-1 text-success">Validation du trajet</h2>
            <?= displayInitialsButton(); ?>
        </div>
    </nav>
</header>

<main class="mt-5">
    <section class="container mt-5" >
        <div class="mb-4 text-center">
            <div class="mb-2">
                <span class="badge bg-primary me-2 fs-5"><?= htmlspecialchars($trip->getStartCity()) ?> <i class="bi bi-arrow-right mx-2"></i> <?= htmlspecialchars($trip->getEndCity()) ?></span>
            </div>
            <div class="mt-2 text-secondary">
                <i class="bi bi-calendar-date-fill"></i>
                <?= htmlspecialchars($trip->getDepartureDateFr()) ?>
                <i class="bi bi-clock-fill ms-2"></i>
                <?= htmlspecialchars($trip->getDepartureTime()) ?>
            </div>
            <div class="text-muted small mb-2">
                <i class="bi bi-person-fill"></i>
                Chauffeur : <strong><?= htmlspecialchars($driver->getFirstName() . ' ' . $driver->getLastName()) ?></strong>
            </div>
        </div>

        <?php if (isset($error)) : ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['success']) && $booking->getStatus() === 'valide'): ?>
            <div class="alert alert-success text-center">
                <i class="bi bi-check-circle fs-2 mb-2 d-block"></i>
                Merci, votre trajet a bien été validé !
                <div class="mt-2 text-muted">Le chauffeur a reçu ses crédits.</div>
            </div>
        <?php elseif ($booking->getStatus() === 'valide'): ?>
            <div class="alert alert-info text-center">
                <i class="bi bi-info-circle fs-2 mb-2 d-block"></i>
                Vous avez déjà validé ce trajet.
            </div>
        <?php else: ?>
            <form method="post" class="p-4 bg-white rounded-4 shadow-sm">
                <input type="hidden" name="booking_id" value="<?= $bookingId ?>">

                <!-- Note avec étoiles -->
                <div class="input-group mb-3 bg-light rounded-3 align-items-center">
                    <span class="input-group-text bg-transparent border-0">
                        <i class="bi bi-star-half text-warning"></i>
                    </span>
                    <select name="rating" id="rating" class="form-select border-0 bg-transparent" required>
                        <option value="">Note (1 à 5)</option>
                        <?php for ($i = 5; $i >= 1; $i--) : ?>
                            <option value="<?= $i ?>"><?= $i ?> ★</option>
                        <?php endfor; ?>
                    </select>
                </div>

                <!-- Commentaire -->
                <div class="input-group mb-3 bg-light rounded-3">
                    <span class="input-group-text bg-transparent border-0 align-items-start pt-2">
                        <i class="bi bi-chat-left-text text-secondary"></i>
                    </span>
                    <textarea name="commentaire" id="commentaire" class="form-control border-0 bg-transparent" rows="3" placeholder="Votre retour (facultatif)"></textarea>
                </div>

                <!-- Bouton valider -->
                <div class="d-grid">
                    <button type="submit" class="btn btn-success d-flex justify-content-center align-items-center gap-2 rounded-3">
                        <i class="bi bi-check-circle"></i> Valider mon trajet
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </section>
</main>

<footer>
    <?php include('footer.php'); ?>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
<script type="module" src="assets/js/index.js"></script>
</body>
</html>