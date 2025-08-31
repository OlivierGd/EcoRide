<?php

/**
 * Generates an HTML badge based on the booking status.
 *
 * @param string $status The booking status to determine the badge type.
 *                       Accepted values include 'reserve', 'annule', 'rembourse',
 *                       'en attente', 'a_valider', 'valide', 'termine', or others.
 *
 * @return string An HTML string representing the badge for the given booking status.
 */
function bookingStatusBadge(string $status): string
{
    $status = trim(strtolower((string)$status));
    return match ($status) {
        'reserve'   => '<span class="badge bg-success">Réservé</span>',
        'annule'    => '<span class="badge bg-danger">Annulé</span>',
        'rembourse' => '<span class="badge bg-info">Remboursé</span>',
        'en attente' => '<span class="badge bg-warning text-dark">En attente</span>',
        'a_valider' => '<span class="badge bg-success">Á Valider</span>',
        'valide'    => '<span class="badge bg-success">Validé</span>',
        'termine'   => '<span class="badge bg-success">Terminé</span>',
        default     => '<span class="badge bg-secondary">Inconnu</span>',
    };
}

/**
 * Determines the CSS class for a trip status badge based on the provided status.
 *
 * @param string $status The status of the trip, which can include values such as 'a_venir', 'en_cours', 'a_valider', 'termine', 'annule', or others.
 * @return string The corresponding CSS class for the provided status.
 */
function getTripStatusBadgeClass(string $status): string
{
    return match ($status) {
        'a_venir'   => 'bg-warning-subtle text-warning-emphasis',
        'en_cours'  => 'bg-primary-subtle text-primary-emphasis',
        'a_valider' => 'bg-info-subtle text-info-emphasis',
        'termine'   => 'bg-secondary-subtle text-secondary-emphasis',
        'annule'    => 'bg-danger-subtle text-danger-emphasis',
        default     => 'bg-light text-dark',
    };
}

