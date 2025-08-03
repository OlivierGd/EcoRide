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
    private DateTime $departureAt;
    private int $availableSeats;
    private int $pricePerPassenger;
    private ?string $comment;
    private bool $noSmoking;
    private bool $musicAllowed;
    private bool $discussAllowed;
    private string $createdAt;
    private array $errors = [];
    private ?string $roleForTrip = null;
    private ?int $bookingId = null;
    private ?string $bookingStatus = null;
    private string $tripStatus;
    private string $startLocation;
    private string $endLocation;
    private ?\DateInterval $estimatedDuration = null;

    public function __construct(array $data = [])
    {
        $this->tripId       = $data['trip_id'] ?? null;
        $this->driverId     = (int)($data['driver_id'] ?? 0);
        $this->vehicleId    = (int)($data['vehicle_id'] ?? 0);
        $this->startCity    = trim($data['start_city'] ?? '');
        $this->endCity      = trim($data['end_city'] ?? '');
        $this->departureAt  = new DateTime($data['departure_at'] ?? 'now');
        $this->availableSeats = (int)($data['available_seats'] ?? 1);
        $this->pricePerPassenger = (int)($data['price_per_passenger'] ?? 0);
        $this->comment      = $data['comment'] ?? '';
        $this->noSmoking    = $data['no_smoking'] ?? false;
        $this->musicAllowed = $data['music_allowed'] ?? false;
        $this->discussAllowed = $data['discuss_allowed'] ?? false;
        $this->createdAt    = $data['created_at'] ?? date('Y-m-d H:i:s');
        $this->tripStatus   = $data['status'] ?? 'a_venir';
        $this->startLocation = $data['start_location'] ?? '';
        $this->endLocation = $data['end_location'] ?? '';
        $this->estimatedDuration = $data['estimated_duration'] ?? null;
    }
    public function setBookingStatus(?string $status): void
    {
        $this->bookingStatus = $status;
    }
    public function getBookingStatus(): ?string
    {
        return $this->bookingStatus;
    }
    public function getBookingId(): ?int
    {
        return $this->bookingId;
    }
    public function setBookingId(?int $bookingId): void
    {
        $this->bookingId = $bookingId;
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
    public function getDepartureDateAndTime(): string
    {
        return $this->departureAt->format('Y-m-d H:i');
    }
    public function getDepartureDate(): string
    {
        return $this->departureAt->format('Y-m-d');
    }
    public function getDepartureDateFr(): string
    {
        return $this->departureAt->format('d/m/Y');
    }
    public function getDepartureTime(): string
    {
        return $this->departureAt->format('H:i');
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
    public function getRoleForTrip(): ?string {
        return $this->roleForTrip;
    }
    public function setRoleForTrip(string $roleForTrip): void {
        $this->roleForTrip = $roleForTrip;
    }
    public function getTripStatus(): string
    {
        return $this->tripStatus;
    }
    public function setTripStatus(string $tripStatus): void
    {
        $this->tripStatus = $tripStatus;
    }
    public function getStartLocation(): string
    {
        return $this->startLocation;
    }
    public function getEndLocation(): string
    {
        return $this->endLocation;
    }
    public function getEstimatedDuration(): ?\DateInterval
    {
        return $this->estimatedDuration;
    }


    public function validateTrip(): bool
    {
        if ($this->startCity === '') {
            $this->errors['start_city'] = 'Ville de départ requise.';
        }
        if ($this->endCity === '') {
            $this->errors['end_city'] = 'Ville de destination requise.';
        }
        if ($this->departureAt < new \DateTime('now')) {
            $this->errors['departure_at'] = 'La date/heure doit être dans le futur.';
        }
        if ($this->pricePerPassenger < 1) {
            $this->errors['price_per_passenger'] = 'Le prix ne peut-être négatif.';
        }
        return empty($this->errors);
    }
    // Enregistrement du trajet : update et sauvegarde
    public function saveToDatabase() : bool
    {
        try {
            if (!$this->validateTrip()) {
                error_log('Validation échouée du voyage avant sauvegarde');
                return false;
            }

            $pdo = Database::getConnection();
            if ($this->tripId !== null) {
                $sql = "UPDATE trips SET vehicle_id=?, start_city=?, end_city=?, departure_at=?, available_seats=?, price_per_passenger=?, comment=?, no_smoking=?, music_allowed=?, discuss_allowed=?, start_location=?, end_location=?, estimated_duration=? WHERE trip_id = ? ";
                $stmt = $pdo->prepare($sql);
                return $stmt->execute([
                    $this->vehicleId,
                    $this->startCity,
                    $this->endCity,
                    $this->departureAt->format('Y-m-d H:i:s'),
                    $this->availableSeats,
                    $this->pricePerPassenger,
                    $this->comment,
                    $this->noSmoking,
                    $this->musicAllowed,
                    $this->discussAllowed,
                    $this->tripId,
                    $this->startLocation,
                    $this->endLocation,
                    $this->estimatedDuration
                ]);
            } else {
                $sql = "INSERT INTO trips (driver_id, vehicle_id, start_city, end_city, departure_at, available_seats, price_per_passenger, comment, no_smoking, music_allowed, discuss_allowed, created_at, start_location, end_location, estimated_duration) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) RETURNING trip_id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $this->driverId,
                    $this->vehicleId,
                    $this->startCity,
                    $this->endCity,
                    $this->departureAt->format('Y-m-d H:i:s'),
                    $this->availableSeats,
                    $this->pricePerPassenger,
                    $this->comment,
                    $this->noSmoking,
                    $this->musicAllowed,
                    $this->discussAllowed,
                    $this->createdAt,
                    $this->startLocation,
                    $this->endLocation,
                    $this->estimatedDuration
                ]);
                // Récupère l'ID généré
                $this->tripId = (int)$stmt->fetchColumn();
                return true;
            }
        } catch (\PDOException $e) {
            throw new \Exception("Erreur lors de l'enregistrement : " . $e->getMessage());
        }
    }

    public static function findTripsByTripId(int $tripId): ?self
    {
        $pdo = Database::getConnection();
        $sql = "SELECT * FROM trips WHERE trip_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$tripId]);
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $data ? new self($data) : null;
    }

    public static function findTripsByDriver(int $driverId): array
    {
        $pdo = Database::getConnection();
        $sql = "SELECT * FROM trips WHERE driver_id = ? ORDER BY departure_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$driverId]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        // Hydrate chaque ligne dans un objet Trip
        return array_map(fn($r) => new self($r), $rows);
    }

    // Renvoie les trajets dont je suis le chauffeur et dont la date de départ est > maintenant.
    public static function findUpcomingByDriver(int $driverId): array
    {
        $pdo = Database::getConnection();
        $sql = "SELECT * FROM trips WHERE driver_id = ? AND departure_at > now() ORDER BY departure_at ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$driverId]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return array_map(fn($r) => new self($r), $rows);
    }

    // REnvoi les trajets à venir dont je suis passager
    public static function findTripsUpcomingByPassenger(int $userId): array
    {
       $pdo = Database::getConnection();
       $sql = "SELECT t.* FROM trips t JOIN bookings b ON b.trip_id = t.trip_id WHERE b.user_id = ? AND t.departure_at > now() ORDER BY t.departure_at ASC";
       $stmt = $pdo->prepare($sql);
       $stmt->execute([$userId]);
       $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
       return array_map(fn($r) => new self($r), $rows);
    }

    public static function findTripsUpcoming(int $limit = null): array
    {
        $pdo = Database::getConnection();
        $sql = "SELECT * FROM trips WHERE departure_at > now() ORDER BY departure_at ASC";
        if ($limit !== null) {
            $sql .= " LIMIT " . (int)$limit;
        }
        $stmt = $pdo->query($sql);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return array_map(fn($r) => new self($r), $rows);
    }

    public function isTripUpcoming(): bool
    {
        $now = new \DateTime('now', new \DateTimeZone('Europe/Paris') );
        $departure = new \DateTime($this->getDepartureDate() . ' ' . $this->getDepartureTime(), new \DateTimeZone('Europe/Paris') );
        return $departure > $now;
    }

    /**
     * Récupère les prochains trajet où je suis passager
     */
    public static function findTripsAsPassenger(int $userId): array
    {
        $pdo = Database::getConnection();
        $sql = "SELECT t.* FROM trips AS t JOIN bookings AS b ON t.trip_id = b.trip_id WHERE b.user_id = :u";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['u' => $userId]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return array_map(fn($r) => new self($r), $rows);
    }

    // Calcul du nombre de place encore disponible (=initial - réservées && !annulée)
    public function getRemainingSeats(): int
    {
        $pdo = Database::getConnection();
        $sql = "SELECT COALESCE(SUM(seats_reserved),0) FROM bookings WHERE trip_id = ? AND status != 'annule'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$this->tripId]);
        $reservedSeats = $stmt->fetchColumn();
        return max(0, $this->availableSeats - $reservedSeats);
    }

    /**
     * Charge un objet Trip par son ID, ou null s’il n’existe pas
     */
    public static function find(int $tripId): ?self
    {
        $pdo = Database::getConnection();
        $sql = "SELECT * FROM trips WHERE trip_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$tripId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ? new self($row) : null;
    }

    /**
     * Met à jour le statut d'un voyage spécifique.
     *
     * @param string $newStatus Le nouveau statut à définir pour le voyage.
     * @return bool Retourne true si la mise à jour a réussi, sinon false.
     */
    public function updateTripStatus(string $newStatus): bool
    {
        $pdo = Database::getConnection();
        $sql = "UPDATE trips SET status = ? WHERE trip_id = ?";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([$newStatus, $this->tripId]);
        if ($result) {
            $this->tripStatus = $newStatus;
        }
        return $result;
    }


}