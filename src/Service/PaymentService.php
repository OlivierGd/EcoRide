<?php

namespace Olivierguissard\EcoRide\Service;

use Olivierguissard\EcoRide\Config\Database;
use PDO;
use Exception;

class PaymentService
{
    /**
     * Traite un paiement et met à jour le solde de l'utilisateur
     *
     * @param int $userId ID de l'utilisateur
     * @param float $amount Montant (+ pour crédit, - pour débit)
     * @param string $type Type de transaction (achat|reservation|gain_course|remboursement|commission)
     * @param string $description Description de la transaction
     * @param int|null $tripId ID du trajet (optionnel)
     * @param int|null $bookingId ID de la réservation (optionnel)
     * @param string $statut Statut de la transaction (paye|credite|pending|annule)
     * @return bool Succès de l'opération
     * @throws Exception En cas d'erreur
     */
    public static function processPayment(
        int $userId,
        float $amount,
        string $type,
        string $description,
        ?int $tripId = null,
        ?int $bookingId = null,
        string $statut = 'paye'
    ): bool {
        $pdo = Database::getConnection();

        try {
            $pdo->beginTransaction();

            // 1. Récupérer le solde actuel avec verrouillage
            $sqlBalance = "SELECT credits FROM users WHERE user_id = ? FOR UPDATE";
            $stmt = $pdo->prepare($sqlBalance);
            $stmt->execute([$userId]);
            $currentBalance = (int)$stmt->fetchColumn();

            // 2. Calculer le nouveau solde
            $newBalance = $currentBalance + $amount;

            // 3. Vérifier solde suffisant pour les débits
            if ($amount < 0 && $newBalance < 0) {
                throw new Exception("Solde insuffisant. Solde actuel: {$currentBalance} crédits");
            }

            // 4. Mettre à jour le solde utilisateur
            $stmt = $pdo->prepare("UPDATE users SET credits = ? WHERE user_id = ?");
            $stmt->execute([$newBalance, $userId]);

            // 5. Pour les achats de crédits, trip_id = NULL
            $finalTripId = ($type === 'achat') ? null : $tripId;

            // 6. Insérer dans payments
            $sqlPayment = "INSERT INTO payments 
            (user_id, trip_id, booking_id, type_transaction, montant, description, 
             statut_transaction, balance_before, balance_after, date_transaction, commission_plateforme) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 0)";

            $stmtPayment = $pdo->prepare($sqlPayment);
            $stmtPayment->execute([
                $userId,
                $finalTripId,
                $bookingId,
                $type,
                $amount,
                $description,
                $statut,
                $currentBalance,
                $newBalance
            ]);

            $pdo->commit();
            return true;

        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Ajoute des crédits au compte d'un utilisateur (achat)
     *
     * @param int $userId ID de l'utilisateur
     * @param int $amount Montant de crédits à ajouter
     * @return bool Succès de l'opération
     */
    public static function addCredits(int $userId, int $amount): bool
    {
        return self::processPayment(
            $userId,
            $amount,
            'achat',
            "Achat de {$amount} crédits",
            null,
            null,
            'paye'
        );
    }

    /**
     * Débite des crédits pour une réservation
     *
     * @param int $userId ID de l'utilisateur
     * @param int $amount Montant à débiter
     * @param int $tripId ID du trajet
     * @param int $bookingId ID de la réservation
     * @return bool Succès de l'opération
     * @throws Exception
     */
    public static function debitForReservation(int $userId, int $amount, int $tripId, int $bookingId): bool
    {
        return self::processPayment(
            $userId,
            -$amount,
            'reservation',
            "Réservation trajet #{$tripId}",
            $tripId,
            $bookingId,
            'paye'
        );
    }

    /**
     * Débite des crédits pour la publication d'un trajet
     *
     * @param int $userId ID de l'utilisateur
     * @param int|null $tripId ID du trajet (optionnel)
     * @return bool Succès de l'opération
     * @throws Exception
     */
    public static function debitForTripPublication(int $userId, ?int $tripId = null): bool
    {
        return self::processPayment(
            $userId,
            -2,
            'publication_trajet',
            "Publication trajet" . ($tripId ? " #{$tripId}" : ""),
            $tripId,
            null,
            'paye'
        );
    }

    /**
     * Crédite un chauffeur pour un trajet terminé
     *
     * @param int $driverId ID du chauffeur
     * @param float $amount Montant à créditer
     * @param int $tripId ID du trajet
     * @return bool Succès de l'opération
     * @throws Exception
     */
    public static function payDriver(int $driverId, float $amount, int $tripId): bool
    {
        return self::processPayment(
            $driverId,
            $amount,
            'gain_course',
            "Gain chauffeur trajet #{$tripId}",
            $tripId,
            null,
            'credite'
        );
    }

    /**
     * Rembourse un utilisateur (annulation)
     *
     * @param int $userId ID de l'utilisateur
     * @param float $amount Montant à rembourser
     * @param int $tripId ID du trajet
     * @param int|null $bookingId ID de la réservation
     * @return bool Succès de l'opération
     * @throws Exception
     */
    public static function refund(int $userId, float $amount, int $tripId, ?int $bookingId = null): bool
    {
        return self::processPayment(
            $userId,
            $amount,
            'remboursement',
            "Remboursement trajet #{$tripId}",
            $tripId,
            $bookingId,
            'credite'
        );
    }

    /**
     * Récupère le solde actuel d'un utilisateur
     *
     * @param int $userId ID de l'utilisateur
     * @return int Solde en crédits
     */
    public static function getUserBalance(int $userId): int
    {
        $pdo = Database::getConnection();
        $sql = "SELECT credits FROM users WHERE user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        $balance = $stmt->fetchColumn();
        return (int)($balance ?? 0);
    }

    /**
     * Récupère l'historique des paiements d'un utilisateur
     *
     * @param int $userId ID de l'utilisateur
     * @param int|null $limit Limite du nombre de transactions
     * @return array Historique des transactions
     */
    public static function getUserPaymentHistory(int $userId, ?int $limit = null): array
    {
        $pdo = Database::getConnection();

        $sql = "SELECT 
                    type_transaction as type, montant, description,
                    date_transaction as date, balance_before, balance_after,
                    statut_transaction as statut
                FROM payments 
                WHERE user_id = :user_id 
                ORDER BY date_transaction DESC";

        if ($limit !== null) {
            $sql .= " LIMIT " . (int)$limit;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Vérifie si un utilisateur a assez de crédits
     *
     * @param int $userId ID de l'utilisateur
     * @param int $amount Montant requis
     * @return bool True si l'utilisateur a assez de crédits
     */
    public static function hasEnoughCredits(int $userId, int $amount): bool
    {
        return self::getUserBalance($userId) >= $amount;
    }

}
