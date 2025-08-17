<?php

namespace Olivierguissard\EcoRide\Model;

use DateTime;
use Olivierguissard\EcoRide\Config\Database;
use Olivierguissard\EcoRide\Service\PaymentService;
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
                $sql = "UPDATE bookings SET trip_id=?, user_id=?, seats_reserved=?, status=?, created_at=? WHERE booking_id = ?";
                $stmt = $pdo->prepare($sql);
                $result = $stmt->execute([
                    $this->tripId,
                    $this->userId,
                    $this->seatsReserved,
                    $this->status,
                    $this->createdAt->format('Y-m-d H:i:s'),
                    $this->bookingId
                ]);
                return $result;
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
                $bookingId = $stmt->fetchColumn();
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

            // 2. Vérifier s'il y a un paiement à rembourser
            $sql = "SELECT montant FROM payments 
                WHERE booking_id = ? AND type_transaction = 'reservation'
                ORDER BY date_transaction DESC LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$bookingId]);
            $montant = $stmt->fetchColumn();

            // 3. Annuler la réservation
            $sql = "UPDATE bookings SET status = 'annule' WHERE booking_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$bookingId]);

            // 4. Rembourser si un paiement existe (même négatif)
            if ($montant !== false && $montant != 0) {
                $montantRemboursement = abs((float)$montant);

                // Récupérer le solde actuel avec verrou
                $sql = "SELECT credits FROM users WHERE user_id = ? FOR UPDATE";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$userId]);
                $currentBalance = (int)$stmt->fetchColumn();

                // Calculer le nouveau solde
                $newBalance = $currentBalance + $montantRemboursement;

                // Mettre à jour le solde
                $sql = "UPDATE users SET credits = ? WHERE user_id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$newBalance, $userId]);

                // Enregistrer la transaction de remboursement
                $sql = "INSERT INTO payments 
                    (user_id, trip_id, booking_id, type_transaction, montant, description, 
                     statut_transaction, balance_before, balance_after, date_transaction, commission_plateforme) 
                    VALUES (?, ?, ?, 'remboursement', ?, 'Remboursement annulation passager', 
                            'credite', ?, ?, NOW(), 0)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $userId, $bookingData['trip_id'], $bookingId, $montantRemboursement,
                    $currentBalance, $newBalance
                ]);

                // Mettre à jour la session si nécessaire
                if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $userId) {
                    $_SESSION['credits'] = $newBalance;
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

            // 1. Vérifier que le trajet existe et appartient au conducteur
            $sql = "SELECT * FROM trips WHERE trip_id = ? AND driver_id = ? 
                AND departure_at > NOW() AND (status IS NULL OR status != 'annule')";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$tripId, $driverId]);
            $trip = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$trip) {
                throw new \Exception("Trajet introuvable ou déjà annulé/démarré/terminé.");
            }

            // 2. Récupérer toutes les réservations actives avec leurs paiements
            $sql = "SELECT b.*, p.montant, p.statut_transaction
                FROM bookings b 
                LEFT JOIN payments p ON (b.booking_id = p.booking_id AND p.type_transaction = 'reservation')
                WHERE b.trip_id = ? AND b.status != 'annule'";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$tripId]);
            $bookingsWithPayments = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // 3. Traiter chaque réservation
            foreach ($bookingsWithPayments as $booking) {
                $bookingId = $booking['booking_id'];
                $userId = $booking['user_id'];
                $montantPaiement = isset($booking['montant']) ? abs((float)$booking['montant']) : 0;

                // a. Annuler la réservation
                $sqlCancel = "UPDATE bookings SET status = 'annule' WHERE booking_id = ?";
                $stmtCancel = $pdo->prepare($sqlCancel);
                $stmtCancel->execute([$bookingId]);

                // b. Rembourser si un paiement existe
                if ($montantPaiement > 0) {
                    // Récupérer le solde actuel avec un verrou (FOR UPDATE)
                    $sqlBalance = "SELECT credits FROM users WHERE user_id = ? FOR UPDATE";
                    $stmtBalance = $pdo->prepare($sqlBalance);
                    $stmtBalance->execute([$userId]);
                    $currentBalance = (int)$stmtBalance->fetchColumn();

                    // Calculer le nouveau solde
                    $newBalance = $currentBalance + $montantPaiement;

                    // Mettre à jour le solde
                    $sqlUpdate = "UPDATE users SET credits = ? WHERE user_id = ?";
                    $stmtUpdate = $pdo->prepare($sqlUpdate);
                    $stmtUpdate->execute([$newBalance, $userId]);

                    // Enregistrer la transaction de remboursement
                    $sqlPayment = "INSERT INTO payments 
                        (user_id, trip_id, booking_id, type_transaction, montant, description, 
                         statut_transaction, balance_before, balance_after, date_transaction, commission_plateforme) 
                        VALUES (?, ?, ?, 'remboursement', ?, 'Remboursement trajet annulé par conducteur', 
                                'credite', ?, ?, NOW(), 0)";
                    $stmtPayment = $pdo->prepare($sqlPayment);
                    $stmtPayment->execute([
                        $userId, $tripId, $bookingId, $montantPaiement,
                        $currentBalance, $newBalance
                    ]);

                    // Mettre à jour la session si c'est l'utilisateur courant
                    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $userId) {
                        $_SESSION['credits'] = $newBalance;
                    }
                }
            }

            // 4. Annuler le trajet
            $sql = "UPDATE trips SET status = 'annule' WHERE trip_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$tripId]);

            $pdo->commit();
            return true;

        } catch (\Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public static function findBookingStatus(int $bookingId): ?string
    {
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

    /**
     * Vérifie si au moins un passager a validé le trajet
     */
    public static function hasAtLeastOneValidation(int $tripId): bool
    {
        $pdo = \Olivierguissard\EcoRide\Config\Database::getConnection();
        $sql = "SELECT COUNT(*) FROM bookings 
            WHERE trip_id = ? 
            AND status = 'valide'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$tripId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Calcule le montant total à payer au chauffeur (tous les passagers qui ont payé)
     */
    public static function calculateDriverPayment(int $tripId): float
    {
        $pdo = \Olivierguissard\EcoRide\Config\Database::getConnection();

        // Récupérer le prix par passager
        $sqlTrip = "SELECT price_per_passenger FROM trips WHERE trip_id = ?";
        $stmtTrip = $pdo->prepare($sqlTrip);
        $stmtTrip->execute([$tripId]);
        $pricePerPassenger = (float)$stmtTrip->fetchColumn();

        // Compter TOUS les passagers qui ont payé (même ceux qui n'ont pas validé)
        $sqlBookings = "SELECT SUM(b.seats_reserved) as total_seats
                    FROM bookings b
                    JOIN payments p ON b.booking_id = p.booking_id
                    WHERE b.trip_id = ? 
                    AND b.status != 'annule'
                    AND p.type_transaction = 'reservation'
                    AND p.statut_transaction = 'debite'";
        $stmtBookings = $pdo->prepare($sqlBookings);
        $stmtBookings->execute([$tripId]);
        $totalSeats = (int)$stmtBookings->fetchColumn();

        return $pricePerPassenger * $totalSeats;
    }

    /**
     * Vérifie que le chauffeur a payé les frais de publication
     */
    public static function hasDriverPaidPublicationFees(int $tripId, int $driverId): bool
    {
        $pdo = \Olivierguissard\EcoRide\Config\Database::getConnection();
        $sql = "SELECT COUNT(*) FROM payments 
            WHERE trip_id = ? 
            AND user_id = ? 
            AND type_transaction = 'publication_trajet'
            AND statut_transaction = 'debite'
            AND montant = 2";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$tripId, $driverId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Vérifie qu'au moins un passager a payé sa réservation
     */
    public static function hasAtLeastOnePassengerPaid(int $tripId): bool
    {
        $pdo = \Olivierguissard\EcoRide\Config\Database::getConnection();

        $sqlPayments = "SELECT COUNT(DISTINCT b.booking_id) 
                    FROM bookings b
                    JOIN payments p ON b.booking_id = p.booking_id
                    WHERE b.trip_id = ? 
                    AND b.status != 'annule'
                    AND p.type_transaction = 'reservation'
                    AND p.statut_transaction = 'debite'";
        $stmt = $pdo->prepare($sqlPayments);
        $stmt->execute([$tripId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Vérifie si le chauffeur a déjà été payé pour ce trajet
     */
    public static function hasDriverBeenPaid(int $tripId, int $driverId): bool
    {
        $pdo = \Olivierguissard\EcoRide\Config\Database::getConnection();
        $sql = "SELECT COUNT(*) FROM payments 
            WHERE trip_id = ? 
            AND user_id = ? 
            AND type_transaction = 'gain_course'
            AND statut_transaction = 'credite'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$tripId, $driverId]);
        return (int)$stmt->fetchColumn() > 0;
    }
}