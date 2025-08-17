<?php
/**
 * API pour la mise à jour d'un utilisateur depuis la console d'administration
 * Permet de modifier le profil, le rôle et le statut selon les permissions
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use Olivierguissard\EcoRide\Config\Database;
use Olivierguissard\EcoRide\Model\Users;

require_once '../functions/auth.php';
startSession();

header('Content-Type: application/json; charset=utf-8');

// Vérifier que c'est une requête POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

try {
    // Vérifications de sécurité
    if (!isAuthenticated()) {
        throw new Exception("Authentification requise");
    }

    $currentUserRole = (int)($_SESSION['role'] ?? 0);
    if ($currentUserRole < Users::ROLE_GESTIONNAIRE) {
        throw new Exception("Permissions insuffisantes - Rôle gestionnaire requis");
    }

    // Récupérer les données du formulaire
    $userId = (int)($_POST['user_id'] ?? 0);
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = (int)($_POST['role'] ?? 0);
    $status = trim($_POST['status'] ?? '');

    // Validation des données
    if (!$userId) {
        throw new Exception("ID utilisateur manquant");
    }
    if (empty($firstName)) {
        throw new Exception("Le prénom est obligatoire");
    }
    if (empty($lastName)) {
        throw new Exception("Le nom est obligatoire");
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Email invalide");
    }
    if (!in_array($status, [Users::STATUS_ACTIF, Users::STATUS_INACTIF])) {
        throw new Exception("Statut invalide. Doit être 'actif' ou 'inactif'");
    }

    $pdo = Database::getConnection();
    $pdo->beginTransaction();

    // Récupérer l'utilisateur cible pour vérifier les permissions
    $targetUser = Users::findUser($userId);
    if (!$targetUser) {
        throw new Exception("Utilisateur non trouvé");
    }

    $targetUserRole = $targetUser->getRole();

    // Vérifier les permissions de modification
    $permissions = Users::getAllowedActionsForUser($currentUserRole, $targetUserRole);

    if (!$permissions['can_edit_profile']) {
        throw new Exception("Vous n'avez pas les permissions pour modifier cet utilisateur");
    }

    // Vérifier que l'email n'est pas déjà utilisé par un autre utilisateur
    $sqlEmailCheck = "SELECT user_id FROM users WHERE email = ? AND user_id != ?";
    $stmtEmailCheck = $pdo->prepare($sqlEmailCheck);
    $stmtEmailCheck->execute([$email, $userId]);
    if ($stmtEmailCheck->fetchColumn()) {
        throw new Exception("Cette adresse email est déjà utilisée par un autre utilisateur");
    }

    // Construire la requête de mise à jour
    $updateFields = [];
    $updateParams = [];

    // Champs du profil (toujours modifiables si can_edit_profile)
    $updateFields[] = "firstName = ?";
    $updateParams[] = $firstName;

    $updateFields[] = "lastName = ?";
    $updateParams[] = $lastName;

    $updateFields[] = "email = ?";
    $updateParams[] = $email;

    $updateFields[] = "status = ?";
    $updateParams[] = $status;

    // Rôle (seulement si autorisé)
    if ($permissions['can_edit_role'] && Users::canChangeUserRole($currentUserRole, $targetUserRole, $role)) {
        $updateFields[] = "role = ?";
        $updateParams[] = $role;
        $roleChanged = true;
    } elseif (isset($_POST['role']) && $role != $targetUserRole) {
        // Si on essaie de changer le rôle sans permission
        throw new Exception("Vous n'avez pas les permissions pour modifier le rôle de cet utilisateur");
    } else {
        $roleChanged = false;
    }

    // Ajouter l'ID utilisateur pour la clause WHERE
    $updateParams[] = $userId;

    // Exécuter la mise à jour
    $sql = "UPDATE users SET " . implode(", ", $updateFields) . " WHERE user_id = ?";
    $stmt = $pdo->prepare($sql);
    $success = $stmt->execute($updateParams);

    if (!$success) {
        throw new Exception("Échec de la mise à jour en base de données");
    }

    $pdo->commit();

    // Log de l'action
    $logMessage = "Utilisateur #{$userId} modifié par " . $_SESSION['firstName'] . " " . $_SESSION['lastName'] . " (ID: " . $_SESSION['user_id'] . ")";
    if ($roleChanged) {
        $logMessage .= " - Rôle changé vers: " . $role;
    }
    if ($status !== $targetUser->getStatus()) {
        $logMessage .= " - Statut changé vers: " . $status;
    }
    error_log($logMessage);

    // Message de succès personnalisé
    $successMessage = "Utilisateur modifié avec succès";
    if ($status === 'inactif' && $targetUser->getStatus() === 'actif') {
        $successMessage = "Utilisateur désactivé avec succès";
    } elseif ($status === 'actif' && $targetUser->getStatus() === 'inactif') {
        $successMessage = "Utilisateur réactivé avec succès";
    }

    echo json_encode([
        'success' => true,
        'message' => $successMessage,
        'user_id' => $userId,
        'changes' => [
            'profile_updated' => true,
            'role_changed' => $roleChanged,
            'status_changed' => ($status !== $targetUser->getStatus()),
            'new_status' => $status
        ]
    ]);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log("Erreur update_user.php : " . $e->getMessage());

    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
