<?php
/**
 * API pour les rôles connectés et rôles authorisés depuis le dashboard
 */
require_once __DIR__ . '/../../vendor/autoload.php';

use Olivierguissard\EcoRide\Model\Users;

require_once '../functions/auth.php';
startSession();
requireAuth();

header('Content-Type: application/json');

try {
    $currentUserRole = (int)$_SESSION['role'];

    if ($currentUserRole < 2) { // Moins que gestionnaire
        throw new Exception('Permissions insuffisantes');
    }

    $allowedRoles = Users::getAllowedRolesForCreation($currentUserRole);


    echo json_encode([
        'success' => true,
        'allowed_roles' => $allowedRoles,
        'current_user_role' => $currentUserRole
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}