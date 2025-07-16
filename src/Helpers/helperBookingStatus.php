<?php

function bookingStatusBadge(string $status): string
{
    $status = trim(strtolower((string)$status));
    return match ($status) {
        'reserve' => '<span class="badge bg-success">Réservé</span>',
        'annule' => '<span class="badge bg-danger">Annulé</span>',
        'rembourse' => '<span class="badge bg-info">Remboursé</span>',
        'en attente' => '<span class="badge bg-warning text-dark">En attente</span>',
        'valider' => '<span class="badge bg-success">Termine</span>',
        default => '<span class="badge bg-secondary">Inconnu</span>',
    };
}

function getTripStatusBadgeClass($status) {
    switch ($status) {
        case 'a_venir':
            return 'bg-warning-subtle text-warning-emphasis';
        case 'en_cours':
            return 'bg-primary-subtle text-primary-emphasis';
        case 'a_valider':
            return 'bg-info-subtle text-info-emphasis';
        case 'termine':
            return 'bg-secondary-subtle text-secondary-emphasis';
        case 'annule':
            return 'bg-danger-subtle text-danger-emphasis';
        default:
            return 'bg-light text-dark';
    }
}

