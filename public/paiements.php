<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Olivierguissard\EcoRide\Config\Database;

require_once 'functions/auth.php';
startSession();
requireAuth();
updateActivity();

require_once __DIR__ . '/../src/Helpers/helpers.php';

$user_id = getUserId();
$totalCredits = 0;

try {
    $pdo = Database::getConnection();
    $sql = "SELECT credits FROM users WHERE users.user_id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['user_id' => $user_id]);
    $totalCredits = $stmt->fetchColumn();
    $totalCredits = (int) $totalCredits;

} catch (PDOException $e) {
    error_log("Erreur de récupération des crédits : " . $e->getMessage());
}

$transactions = [];

try {
    $sql = "SELECT amounts, balance_before, balance_after, type, date_credit, status, created_at 
                FROM credits_history WHERE user_id = :user_id ORDER BY created_at DESC ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['user_id' => $user_id]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($results as $result) {
        $transactions[] = [
            'date' => $result['created_at'],
            'type' => $result['type'],
            'montant' => $result['amounts'],
            'solde_depart' => $result['balance_before'],
            'solde_arrive' => $result['balance_after'],
            'statut' => $result['status']
        ];
    }
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des transactions : " . $e->getMessage());
    $transactions = []; // fallback vide
}

$pageTitle = 'Mes paiements - EcoRide';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="assets/pictures/logoEcoRide.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/paiements.css">
    <title><?= $pageTitle ?></title>
</head>
<body>
<header>
    <nav class="navbar bg-body-tertiary mb-3">
        <div class="container px-2" style="max-width: 900px;">
            <a class="navbar-brand" href="index.php">
                <img src="assets/pictures/logoEcoRide.png" alt="Logo EcoRide" width="45" class="rounded">
            </a>
            <h2 class="fw-bold text-success fs-4 mb-0">Mes paiements</h2>
            <?= displayInitialsButton(); ?>
        </div>
    </nav>
</header>

<main>
    <?php if (isset($_SESSION['flash_success'])): ?>
        <div class="alert alert-success text-center">
            <?php 
            // Gestion des messages de succès en tableau ou string
            if (is_array($_SESSION['flash_success'])) {
                echo $_SESSION['flash_success']['message'] ?? 'Opération réussie';
            } else {
                echo $_SESSION['flash_success'];
            }
            unset($_SESSION['flash_success']); 
            ?>
        </div>
    <?php elseif (isset($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger text-center">
            <?php 
            // Gestion des messages d'erreur en tableau ou string
            if (is_array($_SESSION['flash_error'])) {
                echo $_SESSION['flash_error']['message'] ?? 'Erreur inconnue';
            } else {
                echo $_SESSION['flash_error'];
            }
            unset($_SESSION['flash_error']); 
            ?>
        </div>
    <?php endif; ?>

    <div class="container mt-3 mb-5 px-2" style="max-width: 900px;">
        <!-- Bloc crédits -->
        <section class="mb-4">
            <div class="row g-2 align-items-center flex-wrap">
                <div class="col-12 col-md-6">
                    <div class="d-flex align-items-center justify-content-center justify-content-md-start">
                        <div class="credit-icon me-3 flex-shrink-0">
                            <i class="bi bi-credit-card-2-front-fill text-white fs-2"></i>
                        </div>
                        <div>
                            <div class="mb-0 fw-bold text-success">Crédits disponibles</div>
                            <div class="fs-3 fw-bold"><?= htmlspecialchars($totalCredits) ?> <span class="fs-6">crédits</span></div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 text-center text-md-end mt-2 mt-md-0">
                    <a href="#buyCreditModal" class="btn btn-success btn-lg w-100 w-md-auto" data-bs-toggle="modal" style="max-width:250px;">
                        <i class="bi bi-plus-circle"></i> Acheter des crédits
                    </a>
                </div>
            </div>
        </section>

        <!-- Tableau transactions -->
        <section>
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-success text-white fw-bold">
                    Historique des transactions
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="align-middle">
                        <tr>
                            <th>Date</th>
                            <th class="d-none d-md-table-cell">Type</th>
                            <th>Montants</th>
                            <th class="d-none d-sm-table-cell">Initial</th>
                            <th class="d-none d-sm-table-cell">Solde</th>
                            <th>État</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($transactions)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">Aucune transaction trouvée.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($transactions as $tx): ?>
                                <tr>
                                    <td>
                                        <span class="d-inline d-md-none"><?= date('d/m', strtotime($tx['date'])) ?></span>
                                        <span class="d-none d-md-inline"><?= date('d/m/Y H:i', strtotime($tx['date'])) ?></span>
                                    </td>
                                    <td class="d-none d-md-table-cell">
                                        <?php if ($tx['type'] === 'Achat'): ?>
                                            <span class="badge bg-success">Achat</span>
                                        <?php else: ?>
                                            <span class="badge bg-info text-dark"><?= htmlspecialchars($tx['type']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="<?= $tx['montant'] < 0 ? 'text-danger' : 'text-success' ?>">
                                        <?= $tx['montant'] > 0 ? '+' : '' ?><?= htmlspecialchars($tx['montant']) ?>
                                    </td>
                                    <td class="d-none d-sm-table-cell"><?= htmlspecialchars($tx['solde_depart']) ?></td>
                                    <td class="d-none d-sm-table-cell"><?= htmlspecialchars($tx['solde_arrive']) ?></td>
                                    <td class="d-none d-sm-table-cell">
                                        <?= displayTypeTransactionBadge($tx['statut']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
        <div class="fixed-bottom-space"></div>
    </div>

    <!-- MODALE Achat crédits -->
    <div class="modal fade" id="buyCreditModal" tabindex="-1" aria-labelledby="buyCreditModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" method="POST" action="buyCredits.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="buyCreditModalLabel">Acheter des crédits</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="creditAmount" class="form-label">Montant à acheter</label>
                        <input type="number" class="form-control" id="creditAmount" name="creditAmount" min="5" step="5" required>
                        <small class="text-muted">Minimum 5 crédits, par pas de 5.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-cart-plus"></i> Acheter
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<footer>
    <?php include 'footer.php'; ?>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>