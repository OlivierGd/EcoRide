<?php

namespace Olivierguissard\EcoRide\Model;

use DateTime;
use Olivierguissard\EcoRide\Config\Database;
use PDOException;
class Trip
{
    private ?int $tripId;
    private int $driverId;
    private int $vehicleId;
    private string $startCity;
    private string $endCity;
    private string $departureDateTime;
    private int $availableSeats;
    private int $pricePerPassenger;
    private ?string $comment;
    private bool $noSmoking;
    private bool $musicAllowed;
    private bool $discussAllowed;
    private string $createdAt;
    private array $errors = [];

    public function __construct(array $data = [])
    {
        $this->tripId       = $data['trip_id'] ?? null;
        $this->driverId     = (int)($data['driver_id'] ?? 0);
        $this->vehicleId    = (int)($data['vehicle_id'] ?? 0);
        $this->startCity    = trim($data['start_city'] ?? '');
        $this->endCity      = trim($data['end_city'] ?? '');
        $this->departureDateTime  = $data['departure_at'] ?? date('Y-m-d H:i:s');
        $this->availableSeats = (int)($data['available_seats'] ?? 1);
        $this->pricePerPassenger = (int)($data['price_per_passenger'] ?? 0);
        $this->comment      = $data['comment'] ?? '';
        $this->noSmoking    = $data['no_smoking'] ?? false;
        $this->musicAllowed = $data['music_allowed'] ?? false;
        $this->discussAllowed = $data['discuss_allowed'] ?? false;
        $this->createdAt    = $data['created_at'] ?? date('Y-m-d H:i:s');
    }
    public function getTripId(): ?int
    {
        return $this->tripId;
    }
    public function getDriverId(): int
    {
        return $this->driverId;
    }
    public function getVehicleId(): int
    {
        return $this->vehicleId;
    }
    public function getStartCity(): string
    {
        return $this->startCity;
    }
    public function getEndCity(): string
    {
        return $this->endCity;
    }
    public function getDepartureDate(): string
    {
        return $this->departureDateTime->format('Y-m-d');
    }
    public function getDepartureTime(): string
    {
        return $this->departureDateTime->format('H:i');
    }
    public function getAvailableSeats(): int
    {
        return $this->availableSeats;
    }
    public function getPricePerPassenger(): int
    {
        return $this->pricePerPassenger;
    }
    public function getComment(): string
    {
        return $this->comment;
    }
    public function getNoSmoking(): bool
    {
        return $this->noSmoking;
    }
    public function getMusicAllowed(): bool
    {
        return $this->musicAllowed;
    }
    public function getDiscussAllowed(): bool
    {
        return $this->discussAllowed;
    }
    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function validateTrip(): bool
    {
        if ($this->startCity === '') {
            $this->errors['start_city'] = 'Ville de départ requise.';
        }
        if ($this->endCity === '') {
            $this->errors['end_city'] = 'Ville de destination requise.';
        }
        if ($this->departureDateTime < new \DateTime('now')) {
            $this->errors['departureDateTime'] = 'La date/heure doit être dans le futur.';
        }
        if ($this->pricePerPassenger < 1) {
            $this->errors['price_per_passenger'] = 'Le prix ne peut-être négatif.';
        }
        return empty($this->errors);
    }
    // Enregistrement du trajet
    public function saveToDatabase($pdo) : bool
    {
        try {
            if (!$this->validateTrip()) {
                error_log('Validation échouée du voyage avant sauvegarde');
                return false;
            }

            $pdo = Database::getConnection();
            if ($this->tripId) {
                $sql = "UPDATE trips SET vehicle_id=?, start_city=?, end_city=?, departure_at=?, available_seats=?, price_per_passenger=?, comment=?, no_smoking=?, music_allowed=?, discuss_allowed=?, created_at=? WHERE id_voyage = ? ";
                $stmt = $pdo->prepare($sql);
                $success = $stmt->execute([
                    $this->startCity,
                    $this->endCity,
                    $this->departureDateTime->format('Y-m-d H:i:s'),
                    $this->availableSeats,
                    $this->pricePerPassenger,
                    $this->comment,
                    $this->noSmoking,
                    $this->musicAllowed,
                    $this->discussAllowed
                ]);
            } else {
                $sql = "INSERT INTO trips (driver_id, vehicle_id, start_city, end_city, departure_at, available_seats, price_per_passenger, comment, no_smoking, music_allowed, discuss_allowed, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $success = $stmt->execute([
                    $this->driverId,
                    $this->vehicleId,
                    $this->startCity,
                    $this->endCity,
                    $this->departureDateTime->format('Y-m-d H:i:s'),
                    $this->availableSeats,
                    $this->pricePerPassenger,
                    $this->comment,
                    $this->noSmoking,
                    $this->musicAllowed,
                    $this->discussAllowed,
                    $this->createdAt
                ]);
            }

            if ($success) {
                $this->tripId = (int)$pdo->lastInsertId();
            }
            return false;
        } catch (\PDOException $e) {
            throw new \Exception("Erreur lors de l'enregistrement : " . $e->getMessage());
        }
    }
    public static function findByUser(int $driverId): array
    {
        $pdo = Database::getConnection();
        $sql = "SELECT * FROM trips WHERE driver_id = ? ORDER BY departure_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$driverId]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        // Hydrate chaque ligne dans un objet Trip
        return array_map(fn($r) => new self($r), $rows);
    }
}