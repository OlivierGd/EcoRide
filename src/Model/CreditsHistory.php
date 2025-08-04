<?php

namespace Olivierguissard\EcoRide\Model;

use PDO;
use PDOException;
use Olivierguissard\EcoRide\Config\Database;
class CreditsHistory
{
    /**
     * Enregistre une transaction crédit/débit dans credits_history
     */
    public static function saveTransactionCreditHistory(
        int $userId,
        float $amount,
        float $balanceBefore,
        float $balanceAfter,
        string $type,
        string $status = "Validé",
        ?int $bookingId = null
    ): bool {
        $pdo = Database::getConnection();
        try {
            $sql = "INSERT INTO credits_history (user_id, booking_id, amounts, balance_before, balance_after, type, status, created_at, date_credit) 
                        VALUES (:user_id, :booking_id, :amount, :before, :after, :type, :status, now(), now())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'user_id'   => $userId,
                'booking_id' => $bookingId ?? null,
                'amount'    => $amount,
                'before'    => $balanceBefore,
                'after'     => $balanceAfter,
                'type'      => $type,
                'status'    => $status
            ]);
            return true;
        } catch (PDOException $exception) {
            error_log("Erreur CreditsHistory::saveTransactionCreditHistory : " . $exception->getMessage());
            return false;
        }
    }
}