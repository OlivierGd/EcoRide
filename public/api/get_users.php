<?php
/**
 * Récupère les informations user pour le dashboard + recherche avec filtres
 */
require_once __DIR__ . '/../../vendor/autoload.php';

use Olivierguissard\EcoRide\Config\Database;
use Olivierguissard\EcoRide\Model\Users;

require_once '../functions/auth.php';
startSession();
requireAuth();

header('Content-Type: application/json');

try {
    $currentUserRole = (int)$_SESSION['role'];

    // Vérifier les permissions d'accès
    if ($currentUserRole < 2) { // Moins que gestionnaire
        throw new Exception('Permissions insuffisantes');
    }

    $query = $_GET['query'] ?? '';
    $role = $_GET['role'] ?? '';
    $status = $_GET['status'] ?? '';

    $pdo = Database::getConnection();

    $sql = "SELECT user_id, firstname, lastname, email, role, status, credits, ranking, created_at 
            FROM users WHERE 1=1";
    $params = [];

    if ($query) {
        $sql .= " AND (firstname ILIKE ? OR lastname ILIKE ? OR email ILIKE ?)";
        $searchTerm = '%' . $query . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    if ($role !== '') {
        $sql .= " AND role = ?";
        $params[] = (int)$role;
    }

    if ($status) {
        $sql .= " AND status = ?";
        $params[] = $status;
    }

    $sql .= " ORDER BY created_at DESC LIMIT 50";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Ajouter les permissions pour chaque utilisateur
    foreach ($users as &$user) {
        $user['permissions'] = Users::getAllowedActionsForUser(
            $currentUserRole,
            (int)$user['role']
        );
    }

    echo json_encode($users);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}