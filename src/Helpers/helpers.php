<?php
// Extrait et affiche les initiales de la personne connectée
function getInitials(string $firstName, string $lastName): string
{
    return strtoupper($firstName[0]) . strtoupper($lastName[0]);
}

// Affiche un bouton rond avec initiale dans le header si connecté
function displayInitialsButton(): string
{
    if (isAuthenticated() && isset($_SESSION['firstName'], $_SESSION['lastName'])) {
    // Utilisateur connecté : afficher le rond vert avec initiales
    $initials = strtoupper($_SESSION['firstName'][0] . $_SESSION['lastName'][0]);
    // On retourne le HTML du bouton profil
    return '<a href="/profil.php" class="btn rounded-circle bg-success text-white d-flex justify-content-center align-items-center fw-bold"'
        . ' style="width:40px; height:40px; font-size:1.2rem;">'
        . $initials
        . '</a>';
    } else {
    // Non connecté : bouton Connexion
    return '<a href="/login.php" class="btn btn-success" role="button">Connexion</a>';
    }
}