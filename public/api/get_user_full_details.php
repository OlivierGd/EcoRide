<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Olivierguissard\EcoRide\Config\Database;
use Olivierguissard\EcoRide\Model\Users;

require_once '../functions/auth.php';
startSession();
requireAuth();

header('Content-Type: application/json');

try {
    $currentUserRole = (int)$_SESSION['role'];
    $userId = (int)($_GET['id'] ?? 0);

    if ($userId <= 0) {
        throw new Exception('ID utilisateur invalide');
    }

    if ($currentUserRole < 2) { // Moins que gestionnaire
        throw new Exception('Permissions insuffisantes');
    }

    $pdo = Database::getConnection();

    // Récupérer les informations utilisateur
    $sql = "SELECT * FROM users WHERE user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception('Utilisateur introuvable');
    }

    $targetUserRole = (int)$user['role'];

    // Ajouter les permissions
    $user['permissions'] = Users::getAllowedActionsForUser($currentUserRole, $targetUserRole);

    // Récupérer les véhicules si autorisé
    if ($user['permissions']['can_view']) {
        $sql = "SELECT * FROM vehicule WHERE id_conducteur = ? ORDER BY id_vehicule";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        $user['vehicles'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $user['vehicles'] = [];
    }

    echo json_encode($user);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}