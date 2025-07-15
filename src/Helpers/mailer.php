<?php

namespace Olivierguissard\EcoRide\Helpers;

use \Mailjet\Client;
use \Mailjet\Resources;

class Mailer
{
    private string $apiKey;
    private string $apiSecret;
    private string $fromEmail;
    private string $fromName;
    private \Mailjet\Client $mj;

    public function __construct()
    {
        $this->apiKey = $_ENV['MAILJET_API_KEY'];
        $this->apiSecret = $_ENV['MAILJET_API_SECRET'];
        $this->fromEmail = $_ENV['MAILJET_FROM_EMAIL'];
        $this->fromName = $_ENV['MAILJET_FROM_NAME'];

        $this->mj = new Client($this->apiKey, $this->apiSecret, true, ['version' => 'v3.1']);
    }

    /**
     * Envoie un email avec Mailjet
     * @param $toEmail
     * @param $toName
     * @param $subject
     * @param $htmlContent
     * @return bool|null
     */
    public function sendEmail(string $toEmail, string $toName, string $subject, string $htmlContent, string $textContent = ''): array
    {
        $body = [
            'Messages' => [
                [
                    'From' => [
                        'Email' => $this->fromEmail, // doit être validé sur Mailjet
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

        $response = $this->mj->post(Resources::$Email, ['body' => $body]);
        return [
            'success' => $response->success(),
            'body' => $response->getBody()
        ];
    }
}

