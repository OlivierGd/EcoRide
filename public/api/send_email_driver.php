<?php

require_once __DIR__ . '/../../vendor/autoload.php';

// Charger les variables d'environnement
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

use Olivierguissard\EcoRide\Config\Database;
use Olivierguissard\EcoRide\Model\Mailer;

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
        throw new Exception('Permissions insuffisantes pour envoyer des emails');
    }

    // Récupérer les paramètres
    $tripId = (int)($_POST['trip_id'] ?? 0);
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $driverName = trim($_POST['driver_name'] ?? '');
    $passengerName = trim($_POST['passenger_name'] ?? '');

    // Validation des paramètres
    if ($tripId <= 0) {
        throw new Exception('ID de trajet invalide');
    }

    if (empty($subject)) {
        throw new Exception('L\'objet de l\'email est requis');
    }

    if (empty($message)) {
        throw new Exception('Le message de l\'email est requis');
    }

    $pdo = Database::getConnection();

    // Récupérer les informations du chauffeur et du trajet
    $sql = "SELECT t.trip_id, t.start_city, t.end_city, t.departure_at,
                   d.user_id as driver_id, d.email as driver_email, d.firstname as driver_firstname, d.lastname as driver_lastname
            FROM trips t
            JOIN users d ON t.driver_id = d.user_id
            WHERE t.trip_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$tripId]);
    $tripData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tripData) {
        throw new Exception('Trajet ou chauffeur introuvable');
    }

    // Préparer l'email
    $driverEmail = $tripData['driver_email'];
    $driverFullName = $tripData['driver_firstname'] . ' ' . $tripData['driver_lastname'];

    // Créer le contenu HTML de l'email
    $htmlContent = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <div style='background: #28a745; color: white; padding: 20px; text-align: center;'>
                <h2 style='margin: 0;'>EcoRide - Message de l'équipe</h2>
            </div>
            
            <div style='padding: 20px; background: #f8f9fa;'>
                <p><strong>Bonjour {$driverFullName},</strong></p>
                
                <div style='background: white; padding: 15px; border-left: 4px solid #28a745; margin: 20px 0;'>
                    <h4 style='color: #28a745; margin-top: 0;'>Détails du trajet concerné :</h4>
                    <p><strong>Trajet #{$tripId}</strong></p>
                    <p><strong>Itinéraire :</strong> {$tripData['start_city']} → {$tripData['end_city']}</p>
                    <p><strong>Date :</strong> " . date('d/m/Y à H:i', strtotime($tripData['departure_at'])) . "</p>
                </div>
                
                <div style='background: white; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                    <h4 style='color: #333; margin-top: 0;'>Message de l'équipe EcoRide :</h4>
                    <div style='line-height: 1.6;'>" . nl2br(htmlspecialchars($message)) . "</div>
                </div>
                
                <p style='margin-top: 30px;'>
                    Si vous avez des questions, n'hésitez pas à nous contacter en répondant à cet email.
                </p>
                
                <p style='margin-bottom: 0;'>
                    Cordialement,<br>
                    <strong>L'équipe EcoRide</strong>
                </p>
            </div>
            
            <div style='background: #6c757d; color: white; padding: 15px; text-align: center; font-size: 12px;'>
                <p style='margin: 0;'>Cet email a été envoyé par un administrateur EcoRide</p>
                <p style='margin: 5px 0 0 0;'>Expéditeur: {$_SESSION['firstName']} {$_SESSION['lastName']}</p>
            </div>
        </div>
    ";

    // Contenu texte pour les clients email qui ne supportent pas le HTML
    $textContent = "
Bonjour {$driverFullName},

Trajet concerné : #{$tripId}
Itinéraire : {$tripData['start_city']} → {$tripData['end_city']}
Date : " . date('d/m/Y à H:i', strtotime($tripData['departure_at'])) . "

Message de l'équipe EcoRide :
{$message}

Cordialement,
L'équipe EcoRide

---
Cet email a été envoyé par : {$_SESSION['firstName']} {$_SESSION['lastName']}
    ";

    // Envoyer l'email
    $mailer = new Mailer();
    $result = $mailer->sendEmail(
        $driverEmail,
        $driverFullName,
        $subject,
        $htmlContent,
        $textContent
    );

    if ($result['success']) {
        // Log de l'action
        $logMessage = "Email envoyé au chauffeur {$driverFullName} (trajet #{$tripId}) par " . $_SESSION['firstName'] . " " . $_SESSION['lastName'] . " (ID: " . $_SESSION['user_id'] . ")";
        error_log($logMessage);

        echo json_encode([
            'success' => true,
            'message' => "Email envoyé avec succès à {$driverFullName}",
            'trip_id' => $tripId,
            'driver_email' => $driverEmail
        ]);

    } else {
        throw new Exception('Échec de l\'envoi de l\'email: ' . ($result['body']['error'] ?? 'Erreur inconnue'));
    }

} catch (Exception $e) {
    error_log("Erreur send_email_driver.php : " . $e->getMessage());

    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>