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