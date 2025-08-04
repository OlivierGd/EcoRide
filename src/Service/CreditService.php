<?php

namespace Olivierguissard\EcoRide\Service;

use Olivierguissard\EcoRide\Model\Users;
use Olivierguissard\EcoRide\Config\Database;
use Olivierguissard\EcoRide\Model\CreditsHistory;

class CreditService
{
    /**
     * Ajoute des crédits au compte utilisateur
     */
    public static function addCredits(int $userId, float $amount, string $type = 'achat'): bool
    {
        $pdo = Database::getConnection();

        try {
            // Récupère l'utilisateur
            $user = Users::findUser($userId);
            if (!$user) {
                throw new \Exception("Utilisateur non trouvé");
            }

            $balanceBefore = $user->getCredits();
            $balanceAfter  = $balanceBefore + $amount;

            // Mise à jour du solde dans users
            $sql = "UPDATE users SET credits = :credits WHERE user_id = :user_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':credits'  => $balanceAfter,
                ':user_id'  => $userId
            ]);

            // Enregistrement dans credits_history (booking_id est null ici)
            CreditsHistory::saveTransactionCreditHistory(
                $userId,
                $amount,
                $balanceBefore,
                $balanceAfter,
                $type,
                'Validé',
                null
            );

            // Met à jour la session
            $_SESSION['credits'] = $balanceAfter;

            return true;

        } catch (\Exception $exception) {
            error_log("Erreur CreditService::addCredits : " . $exception->getMessage());
            return false;
        }
    }


    /**
     * Débite un utilisateur (ex: pour une résa) - bookingId requis
     * @throws \Exception
     */
    public static function debitCredits(int $userId, int $amount, ?int $bookingId = null): bool
    {
        $pdo = Database::getConnection();

        // Récupère l'utilisateur pour vérifier et mettre à jour les crédits
        $user = Users::findUser($userId);
        if (!$user) {
            throw new \Exception("Utilisateur non trouvé");
        }

        $balanceBefore = $user->getCredits();
        $balanceAfter = $balanceBefore - $amount;

        if ($balanceAfter < 0) {
            throw new \Exception("Solde insuffisant");
        }

        // Mise à jour du solde de l'utilisateur dans la table users
        $sql = "UPDATE users SET credits = :newBalance WHERE user_id = :userId";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'newBalance' => $balanceAfter,
            'userId'     => $userId
        ]);

        // Enregistrement dans credits_history
        CreditsHistory::saveTransactionCreditHistory(
            $userId,
            -$amount,                // négatif car débit
            $balanceBefore,
            $balanceAfter,
            'reservation',
            'Validé',
            $bookingId
        );

        // Mise à jour de la session si besoin
        $_SESSION['credits'] = $balanceAfter;

        return true;
    }
}