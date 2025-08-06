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

    /**
     * Déduit 2 crédits pour commissions EcoRide
     * @param int $userId
     * @return bool
     * @throws \Exception
     */
    public static function debitForTripPublication(int $userId): bool
    {
        $cost = 2;

        $pdo = Database::getConnection();
        $user = Users::findUser($userId);

        if (!$user) {
            throw new \Exception("Utilisateur introuvable");
        }

        $balanceBefore = $user->getCredits();
        if ($balanceBefore < $cost) {
            throw new \Exception("Solde insuffisant pour publier un trajet.");
        }

        $balanceAfter = $balanceBefore - $cost;

        try {
            $pdo->beginTransaction();

            // Débit
            $sql = "UPDATE users SET credits = :credits WHERE user_id = :user_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':credits' => $balanceAfter,
                ':user_id' => $userId
            ]);

            // Historique
            $sql = "INSERT INTO postgres.public.credits_history(user_id, amounts, status, balance_before, balance_after, created_at) VALUES (:user_id, :amounts, :status, :before, :after, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':user_id' => $userId,
                ':amounts' => -$cost,
                ':status' => 'trajet_propose',
                ':before' => $balanceBefore,
                ':after' => $balanceAfter
            ]);

            $pdo->commit();
            return true;
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Extrait les commissions pour les voyages proposés
     */
    public static function getCommissionHistory(?string $dateMin = null, ?string $dateMax = null): array
    {
        try {
            $pdo = Database::getConnection();

            $sql = "SELECT ch.*, u.firstname, u.lastname 
                   FROM credits_history ch 
                   JOIN users u ON ch.user_id = ch.user_id 
                   WHERE ch.status = 'trajet_propose'";

            $params = [];

            if ($dateMin !== null) {
                $sql .= " AND ch.created_at >= :dateMin";
                $params[':dateMin'] = $dateMin;
            }

            if ($dateMax !== null) {
                $sql .= " AND ch.created_at <= :dateMax";
                $params[':dateMax'] = $dateMax;
            }

            $sql .= " ORDER BY ch.created_at DESC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("Erreur getCommissionHistory : " . $e->getMessage());
            return [];
        }
    }

    public static function getMonthlyCommissionTotals(): array
    {
        try {
            $pdo = Database::getConnection();
            $sql = " SELECT TO_CHAR(created_at, 'YYYY-MM') AS month, SUM(amounts) AS total
                    FROM credits_history
                    WHERE status = 'trajet_propose'
                    GROUP BY month
                    ORDER BY month ASC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log("Erreur getMonthlyCommissionTotals : " . $e->getMessage());
            return [];
        }
    }
}