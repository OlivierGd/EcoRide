<?php
// api/retry_activation_email.php

require_once __DIR__ . '/../vendor/autoload.php';

// Charger les variables d'environnement
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

use Olivierguissard\EcoRide\Config\Database;
use Olivierguissard\EcoRide\Model\Mailer;
use Olivierguissard\EcoRide\Model\Users;

require_once '../functions/auth.php';
startSession();
requireAuth();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

try {
    $currentUserRole = (int)$_SESSION['role'];
    $userId = (int)($_POST['user_id'] ?? 0);

    if ($currentUserRole < 2) { // Moins que gestionnaire
        throw new Exception('Permissions insuffisantes');
    }

    if ($userId <= 0) {
        throw new Exception('ID utilisateur invalide');
    }

    // Récupérer l'utilisateur
    $user = Users::findUser($userId);
    if (!$user) {
        throw new Exception('Utilisateur introuvable');
    }

    // Vérifier qu'il n'a pas de mot de passe (compte non activé)
    $pdo = Database::getConnection();
    $sql = "SELECT password FROM users WHERE user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    $hasPassword = $stmt->fetchColumn();

    if (!empty($hasPassword)) {
        throw new Exception('L\'utilisateur a déjà un mot de passe actif');
    }

    // Renvoyer l'email d'activation
    $mailer = new Mailer();
    $result = $user->sendActivationEmail($pdo, $mailer);

    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => 'Email d\'activation renvoyé avec succès'
        ]);
    } else {
        throw new Exception($result['error']);
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
