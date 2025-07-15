<?php

require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

use Olivierguissard\EcoRide\Helpers\Mailer;


// Instanciation du mailer
$mailer = new Mailer();

// Mets ici un email auquel tu as accès pour vérifier la réception
$toEmail = 'oguissard@me.com';
$toName = 'Olivier (test)';

$subject = 'Test envoi Mailjet depuis EcoRide';
$htmlContent = '<h2>Test Mailjet</h2><p>Bravo, ton système Mailjet fonctionne !</p>';
$textContent = "Test Mailjet : Bravo, ton système Mailjet fonctionne !";

// Envoi du mail
$result = $mailer->sendEmail($toEmail, $toName, $subject, $htmlContent, $textContent);

// Affiche le résultat
if ($result['success']) {
    echo "<h3>Mail envoyé avec succès à $toEmail !</h3>";
} else {
    echo "<h3>Erreur d'envoi !</h3>";
    echo '<pre>' . print_r($result['data'], true) . '</pre>';
}
