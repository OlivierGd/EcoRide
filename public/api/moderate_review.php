<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Olivierguissard\EcoRide\Config\Database;

require_once '../functions/auth.php';
startSession();
requireAuth();

header('Content-Type: application/json');

// Vérifier que c'est une requête POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

try {
    $currentUserRole = (int)($_SESSION['role'] ?? 0);

    // Vérifier les permissions (gestionnaire ou admin)
    if ($currentUserRole < 2) {
        throw new Exception('Permissions insuffisantes pour modérer les commentaires');
    }

    // Récupérer les paramètres
    $reviewId = (int)($_POST['review_id'] ?? 0);
    $action = trim($_POST['action'] ?? '');

    // Validation des paramètres
    if ($reviewId <= 0) {
        throw new Exception('ID de commentaire invalide');
    }

    if (!in_array($action, ['approve', 'reject'])) {
        throw new Exception('Action invalide. Doit être "approve" ou "reject"');
    }

    $pdo = Database::getConnection();

    // Vérifier que le commentaire existe
    $sqlCheck = "SELECT review_id, status_review FROM reviews WHERE review_id = ?";
    $stmtCheck = $pdo->prepare($sqlCheck);
    $stmtCheck->execute([$reviewId]);
    $review = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if (!$review) {
        throw new Exception('Commentaire introuvable');
    }

    // Déterminer le nouveau statut
    $newStatus = ($action === 'approve') ? 'approved' : 'rejected';
    $currentStatus = $review['status_review'];

    // Vérifier si un changement est nécessaire
    if ($currentStatus === $newStatus) {
        $statusLabel = ($newStatus === 'approved') ? 'déjà approuvé' : 'déjà rejeté';
        throw new Exception("Ce commentaire est {$statusLabel}");
    }

    // Mettre à jour le statut
    $sqlUpdate = "UPDATE reviews SET status_review = ? WHERE review_id = ?";
    $stmtUpdate = $pdo->prepare($sqlUpdate);
    $success = $stmtUpdate->execute([$newStatus, $reviewId]);

    if (!$success) {
        throw new Exception('Échec de la mise à jour du commentaire');
    }

    // Log de l'action
    $actionLabel = ($action === 'approve') ? 'approuvé' : 'rejeté';
    $logMessage = "Commentaire #{$reviewId} {$actionLabel} par " . $_SESSION['firstName'] . " " . $_SESSION['lastName'] . " (ID: " . $_SESSION['user_id'] . ")";
    error_log($logMessage);

    // Message de succès
    $successMessage = ($action === 'approve') ?
        'Commentaire approuvé avec succès' :
        'Commentaire rejeté avec succès';

    echo json_encode([
        'success' => true,
        'message' => $successMessage,
        'review_id' => $reviewId,
        'new_status' => $newStatus,
        'action' => $action
    ]);

} catch (Exception $e) {
    error_log("Erreur moderate_review.php : " . $e->getMessage());

    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>