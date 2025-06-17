<?php
function ajouter_vue () {
    $fichier = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'compteur';
    $fichier_journalier = $fichier . '-' . date('Y-m-d');
    if (file_exists($fichier)) {
        $compteur = (int)file_get_contents($fichier_journalier);
        $compteur++;
        file_put_contents($fichier_journalier, $compteur);
    } else {
        file_put_contents($fichier_journalier, '1');
    }
}

function recuperer_vue (): string {
    $fichier_journalier = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'compteur' . '-' . date('Y-m-d');
    return file_get_contents($fichier_journalier);
}

function nombre_vue_mois (int $annee, string $mois) {
    $mois = str_pad($mois, 2, '0', STR_PAD_LEFT); // "1" -> "01"
    $fichier = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'compteur-' . $annee . '-' . $mois . '-' . '*';
    $fichiers = glob($fichier);
    $total = 0;
    foreach ($fichiers as $fichier) {
        $vues = file_get_contents($fichier);
        $total += (int)$vues;
    }
    return $total;
}