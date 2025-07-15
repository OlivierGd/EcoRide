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

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function saveBookingToDatabase(): bool
    {
        try {
            $pdo = Database::getConnection();
            if ($this->bookingId !== null) {
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
            } else {
                $sql = "INSERT INTO bookings (trip_id, user_id, seats_reserved, status, created_at) VALUES (?, ?, ?, ?, ?) RETURNING booking_id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $this->tripId,
                    $this->userId,
                    $this->seatsReserved,
                    $this->status,
                    $this->createdAt->format('Y-m-d H:i:s')
                ]);
                // Récupère l'ID généré
                $this->bookingId = (int)$stmt->fetchColumn();
                return true;
            }
        } catch (\PDOException $e) {
            throw new \Exception("Erreur lors de l'enregistrement : " . $e->getMessage());
        }
    }

    // Compte le nombre de places déjà réservées
    public static function countByTrip(int $tripId): int
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(seats_reserved),0) FROM bookings WHERE trip_id = ?");
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
            error_log("CANCEL: étape 1 - vérification réservation");
            // 1. Vérifier l'existence et l'appartenance de la réservation, ET qu'elle n'est pas déjà annulée
            $sql = "SELECT * FROM bookings WHERE booking_id = ? AND user_id = ? AND status != 'annule'";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$bookingId, $userId]);
            $booking = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$booking) {
                error_log("CANCEL: réservation introuvable ou déjà annulée");
                throw new \Exception("Réservation introuvable ou déjà annulée.");
            }
            error_log("CANCEL: étape 2 - vérification date trajet");
            // 2. Vérifier que le trajet n'est pas commencé ou terminé
            $sql = "SELECT departure_at FROM trips WHERE trip_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$booking['trip_id']]);
            $departure = $stmt->fetchColumn();
            if (!$departure || new \DateTime($departure) < new \DateTime()) {
                error_log("CANCEL: trajet déjà démarré ou terminé");
                throw new \Exception("Annulation impossible, trajet déjà démarré ou terminé.");
            }
            error_log("CANCEL: étape 3 - récupération paiement");
            // 3. Récupérer le paiement lié à cette réservation (pour le montant à rembourser)
            $sql = "SELECT * FROM payments WHERE booking_id = ? AND type_transaction = 'reservation'";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$bookingId]);
            $payment = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$payment) {
                error_log("CANCEL: aucun paiement trouvé");
                throw new \Exception("Aucun paiement de réservation trouvé.");
            }
            $montant = (float)$payment['montant'];

            error_log("CANCEL: étape 4 - update bookings");
            // 4. Annuler la réservation (update bookings)
            $sql = "UPDATE bookings SET status = 'annule' WHERE booking_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$bookingId]);

            error_log("CANCEL: étape 5 - remboursement user");
            // 5. Rembourser les crédits au passager (update users)
            $sql = "UPDATE users SET credits = credits + ? WHERE user_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$montant, $userId]);

            error_log("CANCEL: étape 6 - insert payments");
            // 6. Ajouter une ligne "remboursement" dans la table payments
            \Olivierguissard\EcoRide\Model\Payment::create([
                'user_id'             => $userId,
                'trip_id'             => $booking['trip_id'],
                'booking_id'          => $bookingId,
                'type_transaction'    => 'remboursement',
                'montant'             => $montant,
                'description'         => 'Remboursement suite à annulation par passager',
                'statut_transaction'  => 'rembourse',
                'commission_plateforme'=> 0
            ]);
            error_log("CANCEL: étape 7 - insert credits_history");
            // 7. Loger dans credits_history
            $sql = "INSERT INTO credits_history (user_id, montant, type, description, created_at)VALUES (?, ?, 'remboursement', ?, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $userId,
                $montant,
                'Remboursement trajet annulé #'.$booking['trip_id']
            ]);

            $pdo->commit();

            // 8. Notifier le chauffeur ici (email, notification...)
            error_log("CANCEL: SUCCESS");
            return true;
        } catch (\Exception $e) {
            $pdo->rollBack();
            // Pour le debug, tu peux logger ici $e->getMessage()
            error_log("CANCEL: ERROR - ".$e->getMessage());
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
                $sqlHistory = "INSERT INTO credits_history (user_id, montant, type, description, created_at)
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

}

