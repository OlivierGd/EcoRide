<?php

namespace Olivierguissard\EcoRide\Model;

use DateTime;
use Olivierguissard\EcoRide\Config\Database;
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
        $this->status       = $data['status'] ?? 'en attente';
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
}

