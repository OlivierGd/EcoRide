<?php
/**
 * Création d'un nouvel utilisateur par un administrateur depuis la modale'
 */
require_once __DIR__ . '/../../vendor/autoload.php';

// Charger les variables d'environnement
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

use Olivierguissard\EcoRide\Config\Database;
use Olivierguissard\EcoRide\Model\Mailer;
use Olivierguissard\EcoRide\Model\Users;

require_once '../functions/auth.php';
startSession();
requireAuth(); // Vérifier que c'est un admin

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

try {
    // Récupère l'utilisateur connecté
    $currentUserId = getUserId();
    if (!$currentUserId) {
        throw new Exception('Utilisateur non authentifié');
    }

    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = (int)($_POST['role'] ?? 0);
    $status = $_POST['status'] ?? 'actif';

    // Validation basique
    if (empty($firstName) || empty($lastName) || empty($email)) {
        throw new Exception('Tous les champs sont requis');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Email invalide');
    }

    // VÉRIFICATION DES PERMISSIONS DE RÔLE
    $currentUserRole = Users::getCurrentUserRole();
    if (!$currentUserRole) {
        throw new Exception('Impossible de déterminer votre rôle');
    }

    if (!Users::canCreateUserWithRole($currentUserRole, $role)) {
        $roleLabels = [
            0 => 'Passager',
            1 => 'Passager / Chauffeur',
            2 => 'Gestionnaire',
            3 => 'Administrateur'
        ];

        $currentRoleLabel = $roleLabels[$currentUserRole] ?? 'Inconnu';
        $targetRoleLabel = $roleLabels[$role] ?? 'Inconnu';

        throw new Exception("Permissions insuffisantes : un {$currentRoleLabel} ne peut pas créer un compte {$targetRoleLabel}");
    }

    $pdo = Database::getConnection();

    // Vérifier si l'email existe déjà
    $sql = 'SELECT user_id FROM users WHERE email = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email]);

    if ($stmt->fetchColumn() > 0) {
        throw new Exception('Un compte avec cette adresse existe déjà');
    }

    // Créer l'utilisateur SANS mot de passe
    $user = new Users([
        'firstname' => $firstName,
        'lastname' => $lastName,
        'email' => $email,
        'role' => $role,
        'status' => $status
        // Pas de 'password' - sera créé par l'utilisateur
    ]);

    // Sauvegarder en base
    $result = $user->saveUserFromAdmin($pdo);

    if (!$result['success']) {
        throw new Exception($result['error']);
    }

    // Envoyer l'email d'activation
    $mailer = new Mailer();
    $emailResult = $user->sendActivationEmail($pdo, $mailer);

    if ($emailResult['success']) {
        echo json_encode([
            'success' => true,
            'message' => 'Utilisateur créé avec succès. Un email d\'activation a été envoyé.',
            'user_id' => $result['user_id'],
            'email_sent' => true,
            'user_name' => $firstName . ' ' . $lastName,
            'user_email' => $email
        ]);
    } else {
        // Utilisateur créé mais email pas envoyé
        echo json_encode([
            'success' => true,
            'message' => 'Utilisateur créé, mais erreur lors de l\'envoi de l\'email.',
            'user_id' => $result['user_id'],
            'email_sent' => false,
            'email_error' => $emailResult['error'],
            'user_name' => $firstName . ' ' . $lastName,
            'user_email' => $email
        ]);
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (Error $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur : ' . $e->getMessage()
    ]);
}
?>
