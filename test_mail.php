<?php

require 'vendor/autoload.php';

use \Mailjet\Resources;

// Charger les variables d'environnement
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Récupérer les clés API
$apiKey = $_ENV['MAILJET_API_KEY'];
$apiSecret = $_ENV['MAILJET_API_SECRET'];

// Vérifier si les clés sont chargées
if (empty($apiKey) || empty($apiSecret)) {
    die("Erreur : Les clés API Mailjet ne sont pas chargées. Vérifiez votre fichier .env.");
}

echo "Clés API chargées. Tentative d'envoi...\n";

$mj = new \Mailjet\Client($apiKey, $apiSecret, true, ['version' => 'v3.1']);

$body = [
    'Messages' => [
        [
            'From' => [
                'Email' => "VOTRE_ADRESSE_EXPEDITEUR_VALIDÉE@VOTREDOMAINE.COM",
                'Name' => "Test EcoRide"
            ],
            'To' => [
                [
                    'Email' => "VOTRE_ADRESSE_DESTINATAIRE@gmail.com",
                    'Name' => "Destinataire de Test"
                ]
            ],
            'Subject' => "Test d'envoi depuis EcoRide",
            'TextPart' => "Bonjour, ceci est un test de délivrabilité.",
            'HTMLPart' => "<h3>Bonjour,</h3><p>Ceci est un test de délivrabilité.</p>"
        ]
    ]
];

try {
    $response = $mj->post(Resources::$Email, ['body' => $body]);
    if ($response->success()) {
        echo "Succès ! L'e-mail a été envoyé (ou du moins, accepté par Mailjet).\n";
        echo "Réponse de l'API :\n";
        print_r($response->getData());
    } else {
        echo "Échec de l'envoi.\n";
        echo "Statut de l'erreur : " . $response->getStatus() . "\n";
        echo "Réponse de l'API :\n";
        print_r($response->getData());
    }
} catch (Exception $e) {
    echo "Une exception a été levée : " . $e->getMessage();
}

?>

