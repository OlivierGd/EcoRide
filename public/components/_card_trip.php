<?php
/** @var array $item contient: 'trip', 'booking', 'role' */
$trip = $item['trip'];
$booking = $item['booking'];
$role = $item['role'];

$trip->checkAndUpdateStatusIfExpired();
$isPast = $trip->isPastTrip();

?>

    <div class="card shadow rounded-4 border-0">
        <div class="card-body d-flex flex-column gap-2">
            <?php if (isset($flashSuccess) && $flashSuccess && $flashSuccess['trip_id'] === $trip->getTripId()): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <?= htmlspecialchars($flashSuccess['message']) ?>
                </div>
            <?php endif; ?>

            <!-- Rôle + statut -->
            <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="badge <?= $role === 'chauffeur' ? 'bg-primary' : 'bg-success' ?>">
                <?= ucfirst($role) ?>
            </span>

                <?php if ($role === 'chauffeur'): ?>
                    <span class="badge <?= getTripStatusBadgeClass($trip->getTripStatus()) ?>">
                    <?= ucfirst(str_replace('_', ' ', $trip->getTripStatus())) ?>
                </span>
                <?php elseif ($role === 'passager' && $booking): ?>
                    <?= bookingStatusBadge($booking->getStatus()); ?>
                <?php endif; ?>
            </div>

            <!-- Trajet -->
            <div>
                <strong><?= htmlspecialchars($trip->getStartCity()) ?></strong>
                <i class="bi bi-arrow-right mx-2"></i>
                <strong><?= htmlspecialchars($trip->getEndCity()) ?></strong>
            </div>

            <!-- Date + heure -->
            <div class="text-muted small mb-2">
                <i class="bi bi-calendar-event me-1"></i>
                <?= $trip->getDepartureDateFr() ?> &nbsp;|&nbsp;
                <i class="bi bi-clock me-1"></i>
                <?= $trip->getDepartureTime() ?>
            </div>

            <!-- Prix + places -->
            <div class="d-flex align-items-center gap-3 mb-2">
                <div>
                    <i class="bi bi-cash-coin me-1"></i>
                    <span class="fw-semibold">
                    <?php if ($role === 'passager' && $booking): ?>
                        <?php if ($booking->getStatus() === 'annule'): ?>
                            <span class="text-danger">Annulé - Remboursé</span>
                        <?php else: ?>
                            <?= $trip->getPricePerPassenger() * $booking->getSeatsReserved() ?> crédits
                        <?php endif; ?>
                    <?php else: ?>
                        <?= $trip->getPricePerPassenger() ?> crédits
                    <?php endif; ?>
                </span>
                </div>
                <div>
                    <i class="bi bi-people-fill me-1"></i>
                    <?php if ($role === 'passager' && $booking): ?>
                        <?= $booking->getSeatsReserved() ?> place<?= $booking->getSeatsReserved() > 1 ? 's' : '' ?>
                    <?php else: ?>
                        <?= $trip->getAvailableSeats() ?> place<?= $trip->getAvailableSeats() > 1 ? 's' : '' ?>
                        <?php if ($role === 'chauffeur'): ?>
                            <span class="ms-2 small">(restantes: <?= $trip->getRemainingSeats() ?>)</span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Boutons -->
            <!-- Boutons actions du chauffeur -->
            <div class="mt-3 d-flex gap-2 justify-content-end">
                <?php if ($role === 'chauffeur'): ?>
                    <?php if ($trip->getTripStatus() === 'a_venir'): ?>
                        <form action="../cancelTrip.php" method="POST" class="m-0">
                            <input type="hidden" name="trip_id" value="<?= $trip->getTripId() ?>">
                            <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-4"
                                <?= $isPast ? 'disabled title="Ce trajet est déjà passé"' : '' ?>>
                                <i class="bi bi-x-circle me-1"></i>Annuler
                            </button>
                        </form>
                        <form action="../startTrip.php" method="POST" class="m-0">
                            <input type="hidden" name="trip_id" value="<?= $trip->getTripId() ?>">
                            <button type="submit" class="btn btn-success btn-sm rounded-pill px-4"
                                <?= $isPast ? 'disabled title="Ce trajet est déjà passé"' : '' ?>>
                                <i class="bi bi-flag me-1"></i>Démarrer
                            </button>
                        </form>
                    <?php elseif ($trip->getTripStatus() === 'en_cours'): ?>
                        <form action="../endTrip.php" method="POST" class="m-0">
                            <input type="hidden" name="trip_id" value="<?= $trip->getTripId() ?>">
                            <button type="submit" class="btn btn-success btn-sm rounded-pill px-4">
                                <i class="bi bi-flag me-1"></i>Arrivé à destination
                            </button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>

                <!-- Bouton action du passager -->
                <?php if ($role === 'passager' && $booking): ?>
                    <?php if ($trip->isTripUpcoming() && $booking->getStatus() !== 'annule'): ?>
                        <form action="../cancelBooking.php" method="POST" class="m-0">
                            <input type="hidden" name="booking_id" value="<?= (int)$booking->getBookingId() ?>">
                            <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-4"
                                    onclick="return confirm('Confirmer l\'annulation de votre réservation ?')">
                                <i class="bi bi-x-circle me-1"></i>Annuler
                            </button>
                        </form>
                    <?php endif; ?>

                    <?php if ($trip->getTripStatus() === 'a_valider' && $booking->getStatus() === 'a_valider'): ?>
                        <a href="../validation.php?booking_id=<?= $booking->getBookingId() ?>"
                           class="btn btn-success btn-sm rounded-pill px-4">
                            <i class="bi bi-check-circle me-1"></i>Valider le trajet
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php
