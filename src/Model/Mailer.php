<?php

namespace Olivierguissard\EcoRide\Model;

use Mailjet\Client;
use Mailjet\Resources;

class Mailer
{
    private string $apiKey;
    private string $apiSecret;
    private string $fromEmail;
    private string $fromName;
    private \Mailjet\Client $mj;

    public function __construct()
    {
        // Gestion des variables d'environnement
        $this->apiKey = $_ENV['MAILJET_API_KEY'] ?? getenv('MAILJET_API_KEY') ?? '';
        $this->apiSecret = $_ENV['MAILJET_API_SECRET'] ?? getenv('MAILJET_API_SECRET') ?? '';
        $this->fromEmail = $_ENV['MAILJET_FROM_EMAIL'] ?? getenv('MAILJET_FROM_EMAIL') ?? '';
        $this->fromName = $_ENV['MAILJET_FROM_NAME'] ?? getenv('MAILJET_FROM_NAME') ?? '';

        // Vérification que les clés sont bien définies
        if (empty($this->apiKey) || empty($this->apiSecret) || empty($this->fromEmail)) {
            error_log("MAILER ERROR: Variables d'environnement Mailjet manquantes");
            error_log("API_KEY présent: " . (!empty($this->apiKey) ? 'OUI' : 'NON'));
            error_log("API_SECRET présent: " . (!empty($this->apiSecret) ? 'OUI' : 'NON'));
            error_log("FROM_EMAIL présent: " . (!empty($this->fromEmail) ? 'OUI' : 'NON'));

            throw new \Exception("Configuration Mailjet incomplète. Vérifiez vos variables d'environnement.");
        }

        $this->mj = new Client($this->apiKey, $this->apiSecret, true, ['version' => 'v3.1']);
    }

    /**
     * Envoie un email avec Mailjet
     */
    public function sendEmail(string $toEmail, string $toName, string $subject, string $htmlContent, string $textContent = ''): array
    {
        $body = [
            'Messages' => [
                [
                    'From' => [
                        'Email' => $this->fromEmail,
                        'Name' => $this->fromName
                    ],
                    'To' => [
                        [
                            'Email' => $toEmail,
                            'Name' => $toName
                        ]
                    ],
                    'Subject' => $subject,
                    'TextPart' => $textContent ?: 'Bonjour, ceci est une notification d\'EcoRide',
                    'HTMLPart' => $htmlContent
                ]
            ]
        ];

        try {
            $response = $this->mj->post(Resources::$Email, ['body' => $body]);
            return [
                'success' => $response->success(),
                'body' => $response->getBody()
            ];
        } catch (\Exception $e) {
            error_log("MAILER SEND ERROR: " . $e->getMessage());
            return [
                'success' => false,
                'body' => ['error' => $e->getMessage()]
            ];
        }
    }

    // Méthodes pour debugging
    public function getApiKey(): string { return substr($this->apiKey, 0, 8) . '...'; }
    public function getFromEmail(): string { return $this->fromEmail; }
}