<?php
// components/_card_trip.php

$trip = $item['trip'];
$booking = $item['booking'];
$role = $item['role'];
$status = $trip->getTripStatus();

// Déterminer les actions possibles
$canStartTrip = false;
$canEndTrip = false;
$needsValidation = false;
$canCancel = false;

if ($role === 'chauffeur') {
    // Le chauffeur peut démarrer le trajet SEULEMENT s'il est à venir
    $canStartTrip = $status === 'a_venir';

    // Le chauffeur peut terminer le trajet SEULEMENT s'il est en cours
    $canEndTrip = $status === 'en_cours';

    // Le chauffeur peut annuler un trajet SEULEMENT s'il n'est pas terminé ET qu'il n'a pas été payé
    if ($status === 'termine') {
        // Vérifier si le chauffeur a été payé
        $pdo = \Olivierguissard\EcoRide\Config\Database::getConnection();
        $sqlCheckPaid = "SELECT COUNT(*) FROM payments 
                         WHERE trip_id = ? AND user_id = ? AND type_transaction = 'gain_course'";
        $stmtCheckPaid = $pdo->prepare($sqlCheckPaid);
        $stmtCheckPaid->execute([$trip->getTripId(), getUserId()]);
        $hasBeenPaid = $stmtCheckPaid->fetchColumn() > 0;

        $canCancel = !$hasBeenPaid; // Peut annuler seulement s'il n'a pas été payé
    } else {
        $canCancel = $status !== 'termine';
    }
} else {
    // Le passager doit valider si le trajet est terminé et pas encore validé
    $needsValidation = $status === 'termine' && $booking && $booking->getStatus() !== 'valide';

    // Le passager peut annuler sa réservation si le trajet est à venir
    $canCancel = $booking && $booking->getStatus() !== 'annule' && $status === 'a_venir' && $trip->isTripUpcoming();
}

// Fonction utilitaire avec protection contre la redéclaration
if (!function_exists('displayTripStatusLabel')) {
    function displayTripStatusLabel($status) {
        switch ($status) {
            case 'a_venir': return 'À venir';
            case 'en_cours': return 'En cours';
            case 'termine': return 'Terminé';
            case 'annule': return 'Annulé';
            default: return 'Inconnu';
        }
    }
}
?>

<div class="card shadow-sm border-0 mb-3">
    <div class="card-body">
        <!-- Header avec statut -->
        <div class="d-flex justify-content-between align-items-start mb-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge <?= getTripStatusBadgeClass($status) ?> fs-6">
                    <?= displayTripStatusLabel($status) ?>
                </span>
                <span class="badge bg-light text-dark border">
                    <?= $role === 'chauffeur' ? 'Chauffeur' : 'Passager' ?>
                </span>
            </div>
            <small class="text-muted">
                <?= date('d/m/Y', strtotime($trip->getDepartureDate())) ?>
            </small>
        </div>

        <!-- Itinéraire -->
        <div class="mb-3">
            <div class="d-flex align-items-center gap-2 mb-1">
                <i class="bi bi-geo-alt-fill text-success"></i>
                <strong><?= htmlspecialchars($trip->getStartCity()) ?></strong>
            </div>
            <div class="d-flex align-items-center gap-2 text-muted">
                <i class="bi bi-arrow-down"></i>
                <span>à <?= htmlspecialchars($trip->getDepartureTime()) ?></span>
            </div>
            <div class="d-flex align-items-center gap-2 mt-1">
                <i class="bi bi-geo-fill text-danger"></i>
                <strong><?= htmlspecialchars($trip->getEndCity()) ?></strong>
            </div>
        </div>

        <!-- Informations du booking pour les passagers -->
        <?php if ($role === 'passager' && $booking): ?>
            <div class="mb-3 p-2 bg-light rounded">
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">Vos places réservées</small>
                    <span class="badge bg-primary"><?= $booking->getSeatsReserved() ?> place(s)</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-1">
                    <small class="text-muted">Statut</small>
                    <?= bookingStatusBadge($booking->getStatus()) ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Informations pour les chauffeurs -->
        <?php if ($role === 'chauffeur'): ?>
            <div class="mb-3 p-2 bg-light rounded">
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">Prix par passager</small>
                    <span class="fw-bold"><?= $trip->getPricePerPassenger() ?> crédits</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-1">
                    <small class="text-muted">Places restantes</small>
                    <span class="badge bg-info text-dark"><?= $trip->getRemainingSeats() ?>/<?= $trip->getAvailableSeats() ?></span>
                </div>
                <?php if ($status === 'termine'): ?>
                    <?php
                    // Vérifier si le chauffeur a été payé en regardant dans payments
                    $pdo = \Olivierguissard\EcoRide\Config\Database::getConnection();
                    $sqlCheckPaid = "SELECT COUNT(*) FROM payments 
                                     WHERE trip_id = ? AND user_id = ? AND type_transaction = 'gain_course'";
                    $stmtCheckPaid = $pdo->prepare($sqlCheckPaid);
                    $stmtCheckPaid->execute([$trip->getTripId(), getUserId()]);
                    $hasBeenPaid = $stmtCheckPaid->fetchColumn() > 0;

                    // Si payé, récupérer le montant
                    $totalPaid = 0;
                    if ($hasBeenPaid) {
                        $sqlAmount = "SELECT montant FROM payments 
                                     WHERE trip_id = ? AND user_id = ? AND type_transaction = 'gain_course' 
                                     LIMIT 1";
                        $stmtAmount = $pdo->prepare($sqlAmount);
                        $stmtAmount->execute([$trip->getTripId(), getUserId()]);
                        $totalPaid = (float)$stmtAmount->fetchColumn();
                    }
                    ?>
                    <div class="d-flex justify-content-between align-items-center mt-1">
                        <small class="text-muted">Paiement</small>
                        <?php if ($hasBeenPaid): ?>
                            <span class="badge bg-success">
                                Crédité +<?= $totalPaid ?> crédits
                            </span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark">
                                En attente validation
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Actions -->
        <div class="d-flex gap-2 flex-wrap">
            <?php if ($canStartTrip): ?>
                <form method="POST" action="startTrip.php" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir démarrer ce trajet ?')">
                    <input type="hidden" name="trip_id" value="<?= $trip->getTripId() ?>">
                    <button type="submit" class="btn btn-info btn-sm">
                        <i class="bi bi-play-circle"></i> Démarrer le trajet
                    </button>
                </form>
            <?php endif; ?>

            <?php if ($canEndTrip): ?>
                <form method="POST" action="endTrip.php" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir marquer ce trajet comme terminé ?')">
                    <input type="hidden" name="trip_id" value="<?= $trip->getTripId() ?>">
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="bi bi-check-circle"></i> Terminer le trajet
                    </button>
                </form>
            <?php endif; ?>

            <?php if ($needsValidation): ?>
                <a href="validation.php?booking_id=<?= $booking->getBookingId() ?>" class="btn btn-warning btn-sm">
                    <i class="bi bi-star"></i> Valider le trajet
                </a>
            <?php endif; ?>

            <?php if ($canCancel && $status !== 'termine' && $status !== 'annule'): ?>
                <?php if ($role === 'chauffeur'): ?>
                    <form method="POST" action="cancelTrip.php" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir annuler ce trajet ? Tous les passagers seront remboursés.')">
                        <input type="hidden" name="trip_id" value="<?= $trip->getTripId() ?>">
                        <button type="submit" class="btn btn-outline-danger btn-sm">
                            <i class="bi bi-x-circle"></i> Annuler le trajet
                        </button>
                    </form>
                <?php else: ?>
                    <form method="POST" action="cancelBooking.php" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir annuler votre réservation ?')">
                        <input type="hidden" name="booking_id" value="<?= $booking->getBookingId() ?>">
                        <button type="submit" class="btn btn-outline-danger btn-sm">
                            <i class="bi bi-x-circle"></i> Annuler ma réservation
                        </button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Bouton pour voir le trajet -->
            <a href="rechercher.php?startCity=<?= urlencode($trip->getStartCity()) ?>&endCity=<?= urlencode($trip->getEndCity()) ?>"
               class="btn btn-outline-primary btn-sm">
                <i class="bi bi-search"></i> Voir trajet
            </a>
        </div>
    </div>
</div>