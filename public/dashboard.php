<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Olivierguissard\EcoRide\Config\Database;
use Olivierguissard\EcoRide\Model\Users;

require_once 'functions/auth.php';
startSession();
requireAuth();

$user = Users::getCurrentUser();
$currentUserRole = $user->getRole();
$isAdmin = $currentUserRole === 3;
$isManagerOrAdmin = $currentUserRole >= 2;

// Variable pour déterminer quelle section afficher
$currentSection = isset($_GET['section']) ? $_GET['section'] : 'users';

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
            <h3 class="mb-3">EcoRide v2</h3>
        </div>
        <ul class="nav flex-column mt-2">
            <li class="nav-item">
                <a href="?section=users" class="nav-link <?= $currentSection === 'users' ? 'active bg-success' : '' ?>">
                    <i class="bi bi-people"></i> Utilisateurs
                </a>
            </li>
            <?php if ($isAdmin): ?>
                <li class="nav-item">
                    <a href="?section=financier" class="nav-link <?= $currentSection === 'financier' ? 'active bg-success' : '' ?>">
                        <i class="bi bi-graph-up"></i> Financier
                    </a>
                </li>
            <?php endif; ?>
            <li class="nav-item">
                <a href="?section=reviews" class="nav-link <?= $currentSection === 'reviews' ? 'active bg-success' : '' ?>">
                    <i class="bi bi-chat-square-text"></i> Commentaires
                </a>
            </li>
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
        <h1>Console d'administration</h1>
        <div class="badge bg-info"><?= ucfirst($currentSection) ?></div>
    </div>

    <!-- CONTENU DYNAMIQUE -->
    <div id="dashboard-content">
        <?php
        switch($currentSection) {
            case 'users':
                include_once 'dashboardView/dashboardUser.php';
                break;
            case 'financier':
                if ($isAdmin) {
                    include_once 'dashboardView/dashboardFinance.php';
                } else {
                    echo '<div class="alert alert-warning">Accès non autorisé</div>';
                }
                break;
            case 'reviews':
                include_once 'dashboardView/dashboardReview.php';
                break;
        }
        ?>
    </div>
</main>

<!-- Conteneur pour les notifications toast injectés par JavaScript-->
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1100"></div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Scripts spécifiques selon la section -->
<?php if($currentSection === 'users'): ?>
    <script src="assets/js/modules/dashboardUser.js"></script>
<?php elseif($currentSection === 'financier' && $isAdmin): ?>
    <script src="assets/js/modules/dashboardFinance.js"></script>
<?php elseif($currentSection === 'reviews'): ?>
    <script src="assets/js/modules/dashboardReview.js"></script>
<?php endif; ?>

</body>
</html>