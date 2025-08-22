<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Olivierguissard\EcoRide\Config\Database;

require_once __DIR__ . '/functions/auth.php';
startSession();
requireAuth();
updateActivity();

$currentUserRole = (int)$_SESSION['role'] ?? 0;
$isAdmin = $currentUserRole === 3;
$isManagerOrAdmin = $currentUserRole >= 2;

if (!$isManagerOrAdmin) {
    header('Location: index.php');
    exit;
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="assets/pictures/logoEcoRide.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <title>EcoRide - Console Admin</title>
</head>

<body>

<!-- SIDEBAR FIXE -->
<nav class="sidebar-fixed bg-dark text-white">
    <div>
        <div class="container">
            <img src="assets/pictures/logoEcoRide.png" alt="Logo EcoRide" class="img-fluid mb-3 rounded-1" style="width: 4em;">
            <h3 class="mb-3">EcoRide</h3>
        </div>
        <ul class="nav flex-column mt-2">
            <li class="nav-item"><a href="#users" class="nav-link">Utilisateurs</a></li>
            <?php if ($isAdmin): ?>
                <li class="nav-item"><a href="#revenus" class="nav-link">Statistiques</a></li>
            <?php endif; ?>
            <li class="nav-item"><a href="#comments" class="nav-link">Commentaires</a></li>
        </ul>
    </div>

    <div class="pt-3 border-top border-secondary">
        <div class="small opacity-75 mb-1">
            <?= htmlspecialchars($_SESSION['firstName'][0] . ' ' . $_SESSION['lastName']) ?>
        </div>
        <a href="index.php" class="nav-link p-0">
            <i class="bi bi-box-arrow-left"></i> Retour
        </a>
    </div>
</nav>

<!-- CONTENU PRINCIPAL -->
<main class="main-with-sidebar">
    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Console d’administration</h1>
    </div>
<!-- SECTION UTILISATEURS -->
    <?php include 'dashboardView/dashboardUser.php'; ?>

<!-- SECTION FINANCIER -->
    <?php if ($isAdmin): ?>
        <section class="mb-5" id="revenus">
            <!-- Graphique historique trajets +7 jours -->
            <div class="d-flex justify-content-between mb-3 pt-3">
                <h2 class="text-success">Trajets sur 7 jours</h2>
                <?php
                $filterId = 'filter_trips';
                $namePrefix = 'trips';
                include __DIR__ . '/components/_filter_date.php';
                ?>
            </div>

            <div style="height: 300px;">
                <canvas id="chartTripsByDay" height="100px"></canvas>
            </div>

            <div class="d-flex justify-content-between mb-3 pt-5">
                <h2 class="text-success">Commissions (publication de trajets)</h2>
                <?php
                $filterId = 'filter_commissions';
                $namePrefix = 'commissions';
                include __DIR__ . '/components/_filter_date.php';
                ?>
            </div>

            <div class="alert alert-success fw-bold">
                Gain total collecté : <?= (fmod($totalGagne, 1) == 0 ?
                    number_format($totalGagne,0, ',','') :
                    number_format($totalGagne,2, ',','')) ?> crédits
            </div>

            <!-- Graphique financier -->
            <div style="height: 300px">
                <canvas id="chartCommissionsMonthly" ></canvas>
            </div>

            <!-- Tableau -->
            <h2 class="text-success mt-5">Historique des commissions</h2>
            <div class="table-responsive mt-4">
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <th>Date</th>
                        <th>Chauffeur</th>
                        <th>Montant</th>
                        <th>Solde avant</th>
                        <th>Solde après</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($commissions as $c): ?>
                        <tr>
                            <td><?= date('d/m/Y H:i', strtotime($c['created_at'])) ?></td>
                            <td><?= htmlspecialchars($c['firstname'] . ' ' . $c['lastname']) ?></td>
                            <td><?= abs((float)$c['amounts']) ?> crédits</td>
                            <td><?= $c['balance_before'] ?></td>
                            <td><?= $c['balance_after'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>

    <!-- SECTION COMMENTAIRES -->
    <section id="comments" class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="text-success">Commentaires et avis des utilisateurs</h2>
        </div>

        <!-- Filtres -->
        <!-- Filtres -->
        <div class="card mb-4">
            <div class="card-body">
                <h6 class="card-title mb-3">
                    <i class="bi bi-funnel"></i> Filtrer les commentaires
                </h6>

                <form id="commentsFilterForm" class="row g-3 align-items-end">
                    <!-- Filtre par statut -->
                    <div class="col-md-2">
                        <label for="statusFilter" class="form-label">Statut</label>
                        <select id="statusFilter" name="comment_status" class="form-select form-select-sm">
                            <option value="">Tous</option>
                            <option value="approved">Approuvé</option>
                            <option value="pending" selected>En attente</option>
                            <option value="rejected">Rejeté</option>
                        </select>
                    </div>

                    <!-- Filtre par ranking/note -->
                    <div class="col-md-2">
                        <label for="ratingFilter" class="form-label">Note minimum</label>
                        <select id="ratingFilter" name="rating" class="form-select form-select-sm">
                            <option value="">Toutes</option>
                            <option value="5">5 ★ exactement</option>
                            <option value="4">4 ★ et plus</option>
                            <option value="3">3 ★ et plus</option>
                            <option value="2">2 ★ et plus</option>
                            <option value="1">1 ★ et plus</option>
                        </select>
                    </div>

                    <!-- Filtre par période prédéfinie -->
                    <div class="col-md-2">
                        <label for="periodFilter" class="form-label">Période</label>
                        <select id="periodFilter" name="period_preset" class="form-select form-select-sm">
                            <option value="">Personnalisé</option>
                            <option value="today">Aujourd'hui</option>
                            <option value="yesterday">Hier</option>
                            <option value="last_7_days">7 derniers jours</option>
                            <option value="last_30_days">30 derniers jours</option>
                            <option value="this_month">Ce mois-ci</option>
                            <option value="last_month">Mois dernier</option>
                            <option value="this_year">Cette année</option>
                        </select>
                    </div>

                    <!-- Date de début -->
                    <div class="col-md-2">
                        <label for="dateStart" class="form-label">Du</label>
                        <input type="date" id="dateStart" name="date_start" class="form-control form-control-sm">
                    </div>

                    <!-- Date de fin -->
                    <div class="col-md-2">
                        <label for="dateEnd" class="form-label">Au</label>
                        <input type="date" id="dateEnd" name="date_end" class="form-control form-control-sm">
                    </div>

                    <!-- Boutons d'action -->
                    <div class="col-md-2">
                        <div class="d-flex gap-1">
                            <button type="submit" class="btn btn-primary btn-sm flex-fill">
                                <i class="bi bi-search"></i> Filtrer
                            </button>
                            <button type="button" id="resetFiltersBtn" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-arrow-clockwise"></i>
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Indicateur de filtres actifs -->
                <div id="activeFiltersIndicator" class="mt-2" style="display: none;">
                    <small class="text-muted">
                        <i class="bi bi-funnel-fill text-primary"></i>
                        <span id="activeFiltersText">Filtres actifs</span>
                        <button type="button" class="btn btn-link btn-sm p-0 ms-2" id="clearAllFilters">
                            Supprimer tous les filtres
                        </button>
                    </small>
                </div>
            </div>
        </div>

        <!-- Tableau des commentaires -->
        <div class="card">
            <div class="card-body p-0">
                <div id="commentsTableContainer">
                    <!-- Chargement initial -->
                    <div class="text-center p-4">
                        <div class="spinner-border text-success" role="status">
                            <span class="visually-hidden">Chargement...</span>
                        </div>
                        <p class="mt-2 text-muted">Chargement des commentaires...</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Info -->
        <div class="mt-3">
            <small class="text-muted">
                <i class="bi bi-info-circle"></i>
                Cliquez sur l'ID du trajet pour voir tous les détails du voyage.
                Les dates affichées correspondent au départ du trajet et à la date du commentaire.
            </small>
        </div>
    </section>
</main>


<footer>

</footer>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/dashboard2.js"></script>
<script src="assets/js/modules/dashboardUser.js"></script>
</body>
</html>
