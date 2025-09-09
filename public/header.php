<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/webp" href="assets/pictures/logoEcoRide.webp">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/index.css">
    <title><?php if (isset($pageTitle)) { echo $pageTitle; } else { echo 'EcoRide - Covoiturage écologique';} ?></title>
</head>
<body>
<!-- Navbar -->
<header>
    <nav class="navbar fixed-top bg-white shadow-sm">
        <div class="container" style="max-width: 900px">
            <a class="navbar-brand" href="/index.php">
                <img src="assets/pictures/logoEcoRide.webp" alt="logo EcoRide" class="d-inline-block align-text-center rounded" width="60">
                EcoRide
            </a>
            <a class="btn btn-success" role="button" href="/login.php">Connexion</a>
        </div>
    </nav>
    <div class="<?= (isset($erreur) || ini_get('display_errors')) ? 'has-error' : '' ?>">
</header>
