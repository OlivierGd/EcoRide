<?php

function bookingStatusBadge(string $status): string
{
    $status = trim(strtolower((string)$status));
    return match ($status) {
        'reserve' => '<span class="badge bg-success">Réservé</span>',
        'annule' => '<span class="badge bg-danger">Annulé</span>',
        'rembourse' => '<span class="badge bg-info">Remboursé</span>',
        'en attente' => '<span class="badge bg-warning text-dark">En attente</span>',
        default => '<span class="badge bg-secondary">Inconnu</span>',
    };
}


