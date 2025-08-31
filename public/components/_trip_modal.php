<?php

use Olivierguissard\EcoRide\Model\Trip;
use Olivierguissard\EcoRide\Model\Users;
use Olivierguissard\EcoRide\Model\Car;

/**
 * Rend la modale d'un trajet de manière réutilisable
 *
 * @param Trip  $trip   Le trajet à afficher
 * @param Users $driver Le conducteur du trajet
 * @param Car   $car    Le véhicule utilisé
 */
if (!function_exists('renderTripModal')) {
    function renderTripModal(Trip $trip, Users $driver, Car $car): void {
        $arrivalTime = clone $trip->getDepartureAt();
        $interval = $trip->getEstimatedDurationAsInterval();
        if ($interval) {
            $arrivalTime->add($interval);
        }
        $arrivalFormatted = $arrivalTime->format('H:i');

        // ID unique pour éviter les conflits entre modales
        $modalId = 'tripModal-' . (int)$trip->getTripId();
        ?>

        <!-- Modale Bootstrap réutilisable -->
        <div class="modal fade" id="<?= $modalId ?>" tabindex="-1"
             aria-labelledby="<?= $modalId ?>Label" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">

                    <!-- Header simple -->
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title" id="<?= $modalId ?>Label">Détails du trajet</h5>
                        <button type="button" class="btn-close btn-close-white"
                                data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>

                    <!-- Corps de la modale - Design hybride -->
                    <div class="modal-body">
                        <p><strong>Conducteur :</strong> <?= htmlspecialchars($driver->getFirstName()) ?> <?= renderStars($driver->getRanking()) ?> (<?= $driver->getRanking()?>)</p>
                        <p><strong>Départ de :</strong> <?= htmlspecialchars($trip->getStartCity()) ?>, <?= htmlspecialchars($trip->getStartLocation()) ?></p>
                        <p><strong>Arrivée à :</strong> <?= htmlspecialchars($trip->getEndCity()) ?>, <?= htmlspecialchars($trip->getEndLocation()) ?></p>
                        <p><strong>Voyage prévu le :</strong> <?= $trip->getDepartureDateFr() ?> à <?= $trip->getDepartureTime() ?></p>
                        <p><strong>Heure d'arrivée estimée :</strong> <?= $arrivalFormatted ?></p>

                        <!-- Informations importantes avec badges -->
                        <div class="row mb-3">
                            <div class="col-4">
                                <p class="mb-1"><strong>Places disponibles :</strong></p>
                                <?php $places = (int)$trip->getRemainingSeats(); ?>
                                <span class="fs-6"><?= $places ?> place<?= $places > 1 ? 's' : '' ?></span>
                            </div>
                            <div class="col-4">
                                <p class="mb-1"><strong>Crédits demandés :</strong></p>
                                <span class="text-dark fs-6"><?= (int)$trip->getPricePerPassenger() ?> crédits</span>
                            </div>
                            <div class="col-4">
                                <p class="mb-1"><strong>Véhicule :</strong></p>
                                <small class="ext-dark fs-6"><?= htmlspecialchars($car->getMarque() . ' ' . $car->getModele()) ?></small>
                            </div>
                        </div>

                        <!-- Préférences -->
                        <?php
                        $hasPreferences = $trip->getNoSmoking() || $trip->getMusicAllowed() || $trip->getDiscussAllowed();
                        if ($hasPreferences):
                            ?>
                            <div class="mb-3">
                                <p class="mb-2"><strong>Préférences :</strong></p>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php if ($trip->getNoSmoking()): ?>
                                        <span class="badge bg-light text-dark border"><i class="bi bi-slash-circle text-success"></i> Non-fumeur</span>
                                    <?php endif; ?>
                                    <?php if ($trip->getMusicAllowed()): ?>
                                        <span class="badge bg-light text-dark border"><i class="bi bi-music-note-beamed text-success"></i> Musique autorisée</span>
                                    <?php endif; ?>
                                    <?php if ($trip->getDiscussAllowed()): ?>
                                        <span class="badge bg-light text-dark border"><i class="bi bi-chat-left-dots-fill text-success"></i> Discussion autorisée</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Commentaire seulement s'il existe -->
                        <?php if (!empty(trim($trip->getComment()))): ?>
                            <div class="mb-3">
                                <p class="mb-2"><strong>Commentaire :</strong></p>
                                <p class="text-muted fst-italic"><?= nl2br(htmlspecialchars($trip->getComment())) ?></p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Footer simple -->
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                    </div>

                </div>
            </div>
        </div>
        <?php
    }
}

?>