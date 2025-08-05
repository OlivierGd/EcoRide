<?php

namespace Olivierguissard\EcoRide\Model;

use DateTime;
use Olivierguissard\EcoRide\Config\Database;
use PDO;
use PDOException;
class Bookings
{
    private ?int $bookingId;
    private int $tripId;
    private int $userId;
    private int $seatsReserved;
    private string $status;
    private \DateTime $createdAt;

    public function __construct(array $data = [])
    {
        $this->bookingId    = $data['booking_id'] ?? null;
        $this->tripId       = (int)$data['trip_id'];
        $this->userId       = (int)$data['user_id'];
        $this->seatsReserved = (int)$data['seats_reserved'];
        $this->status       = $data['status'] ?? 'reserve';
        $this->createdAt    = isset($data['created_at']) ? new \DateTime($data['created_at']) : new \DateTime('now');
    }

    public function getBookingId(): ?int
    {
        return $this->bookingId;
    }

    public function getTripId(): int
    {
        return $this->tripId;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getSeatsReserved(): int
    {
        return $this->seatsReserved;
    }
    public function setSeatsReserved(int $seatsReserved): void
    {
        $this->seatsReserved = $seatsReserved;
    }

    public function getStatus(): string
    {
        return $this->status;
    }
    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }
    public function setCreatedAt(DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function saveBookingToDatabase(): bool
    {
        try {
            $pdo = Database::getConnection();
            if ($this->bookingId !== null) {
                error_log("DEBUG: SQL = " . $sql);
                error_log("DEBUG: UPDATE booking_id=" . $this->bookingId . " | status=" . $this->status);
                $sql = "UPDATE bookings SET trip_id=?, user_id=?, seats_reserved=?, status=?, created_at=? WHERE booking_id = ? ";
                $stmt = $pdo->prepare($sql);
                return $stmt->execute([
                    $this->tripId,
                    $this->userId,
                    $this->seatsReserved,
                    $this->status,
                    $this->createdAt->format('Y-m-d H:i:s'),
                    $this->bookingId
                ]);
                error_log("DEBUG: ROWS UPDATED = " . $stmt->rowCount());
            } else {
                error_log("DEBUG: INSERT booking");
                $sql = "INSERT INTO bookings (trip_id, user_id, seats_reserved, status, created_at) VALUES (?, ?, ?, ?, ?) RETURNING booking_id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $this->tripId,
                    $this->userId,
                    $this->seatsReserved,
                    $this->status,
                    $this->createdAt->format('Y-m-d H:i:s')
                ]);
                $bookingId = $stmt->fetchColumn(); // pour debug a supprimer après
                error_log("DEBUG: fetchColumn() = " . print_r($bookingId, true)); // pour debug a supprimer après
                // Récupère l'ID généré
                $this->bookingId = (int)$bookingId;
                return true;
            }
        } catch (\PDOException $e) {
            throw new \Exception("Erreur lors de l'enregistrement : " . $e->getMessage());
        }
    }

    // Compte le nombre de places déjà réservées (Réservations actives)
    public static function countByTrip(int $tripId): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(seats_reserved),0) FROM bookings WHERE trip_id = ? AND status != 'annule'");
        $stmt->execute([$tripId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Renvoi bookingId de la réservation dans le cas d'une annulation par un passager
     * @param int $tripId
     * @param int $userId
     * @return int|null
     */
    public static function findBookingId(int $tripId, int $userId): int | null
    {
        $pdo = \Olivierguissard\EcoRide\Config\Database::getConnection();
        $sql = "SELECT booking_id FROM bookings WHERE trip_id = ? AND user_id = ? ORDER BY booking_id DESC LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$tripId, $userId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ? (int)$result['booking_id'] : null;
    }


    /**
     * Annule une réservation à la demande du passager, rembourse les crédits et logue le mouvement.
     * @param int $bookingId L'ID de la réservation à annuler
     * @param int $userId L'ID du passager (pour sécuriser l'action)
     * @return bool Succès de l'annulation
     * @throws Exception en cas d'échec
     */
    public static function cancelByPassenger(int $bookingId, int $userId): bool
{
    $pdo = \Olivierguissard\EcoRide\Config\Database::getConnection();

    try {
        $pdo->beginTransaction();
        
        // 1. Vérifier l'existence de la réservation
        $sql = "SELECT b.*, t.departure_at FROM bookings b 
            JOIN trips t ON b.trip_id = t.trip_id 
            WHERE b.booking_id = ? AND b.user_id = ? AND b.status != 'annule'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$bookingId, $userId]);
        $bookingData = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$bookingData) {
            throw new \Exception("Réservation introuvable ou déjà annulée.");
        }

        // 2. Annuler la réservation
        $sql = "UPDATE bookings SET status = 'annule' WHERE booking_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$bookingId]);

        // 3. Vérifier s'il y a un paiement à rembourser
        $sql = "SELECT montant FROM payments WHERE booking_id = ? AND type_transaction = 'reservation'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$bookingId]);
        $montant = $stmt->fetchColumn();
        
        // 4. Rembourser seulement s'il y a un paiement (logique métier)
        if ($montant !== false && $montant > 0) {
            $sql = "UPDATE users SET credits = credits + ? WHERE user_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$montant, $userId]);
            
            // Mettre à jour la session si c'est l'utilisateur courant
            if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $userId) {
                $newCredits = \Olivierguissard\EcoRide\Model\Users::getUsersCredits($userId);
                $_SESSION['credits'] = $newCredits;
            }
        }

        // 5. Libérer les places dans le trajet
        $sql = "UPDATE trips SET available_seats = available_seats + ? WHERE trip_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$bookingData['seats_reserved'], $bookingData['trip_id']]);

        $pdo->commit();
        return true;

    } catch (\Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

    /**
     * Annule un trajet à la demande du conducteur.
     * Annule toutes les réservations et rembourse les passagers.
     * @param int $tripId L'ID du trajet à annuler
     * @param int $driverId L'ID du conducteur (pour sécuriser l'action)
     * @return bool Succès de l'annulation du trajet
     * @throws Exception en cas d'échec
     */
    public static function cancelTripByDriver(int $tripId, int $driverId): bool
    {
        $pdo = \Olivierguissard\EcoRide\Config\Database::getConnection();

        try {
            $pdo->beginTransaction();

            // 1. Vérifier que le trajet existe, appartient au conducteur, et n'est pas déjà annulé/terminé
            $sql = "SELECT * FROM trips WHERE trip_id = ? AND driver_id = ? AND departure_at > NOW() AND (status IS NULL OR status != 'annule')";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$tripId, $driverId]);
            $trip = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$trip) {
                throw new \Exception("Trajet introuvable ou déjà annulé/démarré/terminé.");
            }

            // 2. Récupérer toutes les réservations actives du trajet
            $sql = "SELECT * FROM bookings WHERE trip_id = ? AND status != 'annule'";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$tripId]);
            $bookings = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // 3. Pour chaque réservation, procéder à l'annulation/remboursement
            foreach ($bookings as $booking) {
                $bookingId = $booking['booking_id'];
                $userId = $booking['user_id'];

                // a. Paiement d'origine (montant à rembourser)
                $sqlPay = "SELECT * FROM payments WHERE booking_id = ? AND type_transaction = 'reservation'";
                $stmtPay = $pdo->prepare($sqlPay);
                $stmtPay->execute([$bookingId]);
                $payment = $stmtPay->fetch(\PDO::FETCH_ASSOC);

                if (!$payment) {
                    // Pas de paiement, skip (ou lance une exception selon ta politique)
                    continue;
                }
                $montant = (float)$payment['montant'];

                // b. Annuler la réservation
                $sqlUpdate = "UPDATE bookings SET status = 'annule' WHERE booking_id = ?";
                $stmtUpdate = $pdo->prepare($sqlUpdate);
                $stmtUpdate->execute([$bookingId]);

                // c. Rembourser le passager
                $sqlUser = "UPDATE users SET credits = credits + ? WHERE user_id = ?";
                $stmtUser = $pdo->prepare($sqlUser);
                $stmtUser->execute([$montant, $userId]);

                // d. Log paiement remboursement
                \Olivierguissard\EcoRide\Model\Payment::create([
                    'user_id'             => $userId,
                    'trip_id'             => $tripId,
                    'booking_id'          => $bookingId,
                    'type_transaction'    => 'remboursement',
                    'montant'             => $montant,
                    'description'         => 'Remboursement suite à annulation trajet par conducteur',
                    'statut_transaction'  => 'rembourse',
                    'commission_plateforme'=> 0
                ]);

                // e. Log credits_history
                $sqlHistory = "INSERT INTO credits_history (user_id, amounts, type, status, created_at)
                           VALUES (?, ?, 'remboursement', ?, NOW())";
                $stmtHistory = $pdo->prepare($sqlHistory);
                $stmtHistory->execute([
                    $userId,
                    $montant,
                    'Remboursement trajet annulé par conducteur #'.$tripId
                ]);
            }

            // 4. Annuler le trajet lui-même (statut dans trips)
            $sql = "UPDATE trips SET status = 'annule' WHERE trip_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$tripId]);

            $pdo->commit();

            // 5. (TODO) Notifier tous les passagers concernés

            return true;
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
    public static function findBookingStatus(int $bookingId): ?string {
        $pdo = \Olivierguissard\EcoRide\Config\Database::getConnection();
        $sql = "SELECT status FROM bookings WHERE booking_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$bookingId]);
        $result = $stmt->fetchColumn();
        return $result !== false ? $result : null;
    }

    public static function findByTripAndUser(int $tripId, int $userId): ?self
    {
        $pdo = \Olivierguissard\EcoRide\Config\Database::getConnection();
        $sql = "SELECT * FROM bookings WHERE trip_id = ? AND user_id = ? ORDER BY created_at DESC LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$tripId, $userId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ? new self($result) : null;
    }
// Retourne les bookings qui ne sont pas annulés
    public static function findBookingsByTripId(int $tripId): array
    {
        $pdo = \Olivierguissard\EcoRide\Config\Database::getConnection();
        $sql = "SELECT * FROM bookings WHERE trip_id = ? AND status != 'annule' ORDER BY created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$tripId]);
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return array_map(fn($row) => new self($row), $result);
    }

    // Trouve une réservation par son bookingId
    public static function findBookingByBookingId(int $bookingId): ?self
    {
        $pdo = \Olivierguissard\EcoRide\Config\Database::getConnection();
        $sql = "SELECT * FROM bookings WHERE booking_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$bookingId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ? new self($result) : null;
    }

    public function updateStatusValidation(string $newStatus): bool
    {
        $pdo = \Olivierguissard\EcoRide\Config\Database::getConnection();
        $sql = "UPDATE bookings SET status = ? WHERE booking_id = ?";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$newStatus, $this->bookingId]);
    }

    public static function findAnnuleBookingByTripAndUser(int $tripId, int $userId): ?self
    {
        $pdo = \Olivierguissard\EcoRide\Config\Database::getConnection();
        $sql = "SELECT * FROM bookings WHERE trip_id = ? AND user_id = ? AND status = 'annule' ORDER BY created_at DESC LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$tripId, $userId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ? new self($result) : null;
    }

    /**
     * Compte le nombre de trajets effectués (non annulés) par un utilisateur
     * @param int $userId L'ID de l'utilisateur
     * @return int Le nombre de trajets effectués
     */
    public static function countUserCompletedTrips(int $userId): int
    {
        $pdo = \Olivierguissard\EcoRide\Config\Database::getConnection();
        $sql = "SELECT COUNT(*) FROM bookings WHERE user_id = ? AND status != 'annule'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }

}