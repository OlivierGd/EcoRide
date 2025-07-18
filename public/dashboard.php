<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Olivierguissard\EcoRide\Config\Database;
use Olivierguissard\EcoRide\Model\Users;

require_once 'functions/auth.php';
startSession();
isAuthenticated();
requireAuth();



?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="assets/pictures/logoEcoRide.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <title>EcoRide - Console Admin</title>
    <style>
        .sidebar {
            width: 220px;
            min-height: 100vh;
        }
        @media (max-width: 768px) {
            .sidebar { display: none; }
        }
    </style>
</head>

<body>

    <nav class="d-flex">
        <!-- SIDEBAR -->
        <nav class="sidebar bg-dark text-white p-3">
            <h3>EcoRide</h3>
            <ul class="nav flex-column">
                <li class="nav-item"><a href="#users" class="nav-link text-white">Utilisateurs</a></li>
                <li class="nav-item"><a href="#stats" class="nav-link text-white">Statistiques</a></li>
                <li class="nav-item"><a href="#comments" class="nav-link text-white">Commentaires</a></li>
            </ul>
        </nav>


        <!-- CONTENU PRINCIPAL -->
        <main class="flex-grow-1 p-4">
            <!-- HEADER -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Console d’administration</h1>
                <!-- Ici tu ajoutes date picker, filtres globaux… -->
            </div>

            <!-- SECTION UTILISATEURS -->
            <section id="users" class="mb-5">
                <div class="d-flex justify-content-between mb-3">
                    <h2>Rechercher un utilisateur</h2>
                </div>
                <form id="searchUserForm" class="row g-3 mb-4">
                    <div class="col-md-4">
                        <input type="text" class="form-control" name="query" placeholder="Nom, prénom ou email">
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary" type="submit">Rechercher</button>
                    </div>
                </form>
                <div id="userDetails">
                    <!-- Les infos détaillées de l’utilisateur apparaîtront ici -->
                </div>
            </section>

            <!-- SECTION STATISTIQUES -->
            <section id="stats" class="mb-5">
                <!-- Graphiques dynamiques (à coder) -->
            </section>

            <!-- SECTION COMMENTAIRES -->
            <section id="comments">
                <!-- Tableau commentaires + actions (à coder) -->
                <section id="comments" class="mb-5">
                    <div class="d-flex justify-content-between mb-3">
                        <h2>Commentaires utilisateurs</h2>
                        <form id="commentsFilterForm" class="d-flex gap-2">
                            <select class="form-select" name="rating" style="width:auto">
                                <option value="">Tous les rankings</option>
                                <option value="5">5 ★</option>
                                <option value="4">4 ★</option>
                                <option value="3">3 ★</option>
                                <option value="2">2 ★</option>
                                <option value="1">1 ★</option>
                            </select>
                            <input type="date" class="form-control" name="date_min" style="width:auto" placeholder="Date min">
                            <input type="date" class="form-control" name="date_max" style="width:auto" placeholder="Date max">
                            <button type="submit" class="btn btn-primary">Filtrer</button>
                        </form>
                    </div>
                    <div id="commentsTableContainer">
                        <!-- Tableau généré ici par JS -->
                    </div>
                </section>

            </section>
        </main>
    </nav>


<footer>

</footer>

<!-- Bootstrap JS et Chart.js (pour la suite) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="assets/js/dashboard.js"></script>
</body>
</html>
