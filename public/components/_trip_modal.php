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
        // BONNE PRATIQUE : Calculer les données nécessaires au début
        // Cela rend le template plus lisible et sépare la logique des vues
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

                    <!-- Header simple et épuré -->
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title" id="<?= $modalId ?>Label">Détails du trajet</h5>
                        <button type="button" class="btn-close btn-close-white"
                                data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>

                    <!-- Corps de la modale - Design hybride épuré -->
                    <div class="modal-body">
                        <p><strong>Conducteur :</strong> <?= htmlspecialchars($driver->getFirstName()) ?> (<?= renderStars($driver->getRanking()) ?>)</p>
                        <p><strong>Départ :</strong> <?= htmlspecialchars($trip->getStartCity()) ?>, <?= htmlspecialchars($trip->getStartLocation()) ?></p>
                        <p><strong>Arrivée :</strong> <?= htmlspecialchars($trip->getEndCity()) ?>, <?= htmlspecialchars($trip->getEndLocation()) ?></p>
                        <p><strong>Départ prévu :</strong> <?= $trip->getDepartureDateFr() ?> à <?= $trip->getDepartureTime() ?></p>
                        <p><strong>Arrivée estimée :</strong> <?= $arrivalFormatted ?></p>

                        <!-- Informations importantes avec badges légers -->
                        <div class="row mb-3">
                            <div class="col-4">
                                <p class="mb-1"><strong>Places disponibles</strong></p>
                                <?php $places = (int)$trip->getRemainingSeats(); ?>
                                <span class="fs-6"><?= $places ?> place<?= $places > 1 ? 's' : '' ?></span>
                            </div>
                            <div class="col-4">
                                <p class="mb-1"><strong>Prix</strong></p>
                                <span class="text-dark fs-6"><?= (int)$trip->getPricePerPassenger() ?> crédits</span>
                            </div>
                            <div class="col-4">
                                <p class="mb-1"><strong>Véhicule</strong></p>
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
                                        <span class="badge bg-light text-dark border">🚭 Non-fumeur</span>
                                    <?php endif; ?>
                                    <?php if ($trip->getMusicAllowed()): ?>
                                        <span class="badge bg-light text-dark border">🎵 Musique autorisée</span>
                                    <?php endif; ?>
                                    <?php if ($trip->getDiscussAllowed()): ?>
                                        <span class="badge bg-light text-dark border">💬 Discussion autorisée</span>
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

/**
 * Fonction utilitaire pour afficher les étoiles de notation
 * POURQUOI une fonction séparée : Réutilisabilité et lisibilité
 */
if (!function_exists('renderStars')) {
    function renderStars($rating): string {
        $fullStars = floor($rating);
        $hasHalfStar = ($rating - $fullStars) >= 0.5;
        $emptyStars = 5 - $fullStars - ($hasHalfStar ? 1 : 0);

        $html = '';

        // Étoiles pleines
        for ($i = 0; $i < $fullStars; $i++) {
            $html .= '<i class="bi bi-star-fill text-warning"></i>';
        }

        // Demi-étoile
        if ($hasHalfStar) {
            $html .= '<i class="bi bi-star-half text-warning"></i>';
        }

        // Étoiles vides
        for ($i = 0; $i < $emptyStars; $i++) {
            $html .= '<i class="bi bi-star text-warning"></i>';
        }

        $html .= ' <small class="text-muted">(' . number_format($rating, 1) . '/5)</small>';

        return $html;
    }
}
?>