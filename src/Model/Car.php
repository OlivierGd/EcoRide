<?php

namespace Olivierguissard\EcoRide\Model;

use PDOException;
use Olivierguissard\EcoRide\Config\Database;
use PDO;

class Car
{
    private ?int $vehicleId;
    private int $userId;
    private string $marque;
    private string $modele;
    private string $carburant;
    private string $immatriculation;
    private int $places;
    private bool $actif;
    private string $createdAt;
    private array $errors = [];

    public function __construct(array $data = [])
    {
        $this->vehicleId       = $data['id_vehicule'] ?? null;
        $this->userId          = (int)($data['id_conducteur'] ?? 0);
        $this->marque          = trim($data['marque'] ?? '');
        $this->modele          = trim($data['modele'] ?? '');
        $this->carburant       = $data['type_carburant'] ?? '';
        $this->immatriculation = trim($data['plaque_immatriculation'] ?? '');
        $this->places          = (int)($data['nbr_places'] ?? 1);
        $this->actif           = isset($data['actif']) ? (bool)$data['actif'] : true;
        $this->createdAt       = $data['date_creation'] ?? date('Y-m-d H:i:s');
    }
    public function getVehicleId(): ?int
    {
        return $this->vehicleId;
    }
    public function getUserId(): int
    {
        return $this->userId;
    }
    public function getMarque(): string
    {
        return $this->marque;
    }
    public function getModele(): string
    {
        return $this->modele;
    }
    public function getCarburant(): string
    {
        return $this->carburant;
    }
    public function getImmatriculation(): string
    {
        return $this->immatriculation;
    }
    public function getPlaces(): int
    {
        return $this->places;
    }
    public function getActif(): bool
    {
        return $this->actif;
    }
    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }
    public function getErrors(): array
    {
        return $this->errors;
    }
    // === Setters ===
    public function setMarque(string $marque): void
    {
        $this->marque = trim($marque);
    }
    public function setModele(string $modele): void
    {
        $this->modele = trim($modele);
    }
    public function setCarburant(string $carburant): void
    {
        $allowedCarburants = ['Electrique', 'Hybride', 'Essence', 'Gasoil'];
        if (in_array(trim($carburant), $allowedCarburants, true)) {
            $this->carburant = trim($carburant);
        } else {
            $this->errors['carburant'] = 'Type de carburant invalide.';
        }
    }
    public function setImmatriculation(string $immatriculation): void
    {
        $this->immatriculation = trim($immatriculation);
    }
    public function setPlaces(int $places): void
    {
        if ($places >= 1 && $places <= 5) {
            $this->places = $places;
        } else {
            $this->errors['places'] = 'Le nombre de places doit être compris entre 1 et 8.';
        }
    }
    public function setActif(bool $actif): void
    {
        $this->actif = $actif;
    }



    public function validateCar(): bool
    {
        $this->errors = []; // Reset des erreurs

        if ($this->marque === '') {
            $this->errors['marque'] = 'La marque de la voiture est requise.';
        }
        if ($this->modele === '') {
            $this->errors['modele'] = 'Le modèle de la voiture est requis.';
        }
        if (!in_array(trim($this->carburant), ['Electrique', 'Hybride', 'Essence', 'Gasoil'], true)) {
            $this->errors['carburant'] = 'Le carburant de la voiture est invalide. Sélectionnez un carburant dans la liste';
        }
        if (!preg_match('/^[A-Z0-9-]{2,10}$/', $this->immatriculation)) {
            $this->errors['immatriculation'] = 'Plaque invalide.';
        }
        if ($this->places < 1 || $this->places > 8) {
            $this->errors['places'] = 'Le nombre de places de la voiture doit-être compris entre 1 et 8.';
        }
        return empty($this->errors);
    }


    public function saveVehicleToDatabase() : bool
    {
        try {
            if (!$this->validateCar()) {
                error_log('Validation échouée avant sauvegarde');
                return false;
            }

            $pdo = Database::getConnection();
            if ($this->vehicleId) {
                $sql = "UPDATE vehicule SET marque=?, modele=?, type_carburant=?, plaque_immatriculation=?, nbr_places=?, actif=? WHERE id_vehicule = ?";
                $stmt = $pdo->prepare($sql);
                $success = $stmt->execute([
                    $this->marque,
                    $this->modele,
                    $this->carburant,
                    $this->immatriculation,
                    $this->places,
                    $this->actif,
                    $this->vehicleId
                ]);

            } else {
                $sql = "INSERT INTO vehicule (id_conducteur, marque, modele, type_carburant,plaque_immatriculation, nbr_places, actif, date_creation) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);

                error_log('Tentative d\'insertion véhicule avec les valeurs : ' . print_r([
                        $this->user_id,
                        $this->marque,
                        $this->modele,
                        $this->carburant,
                        $this->immatriculation,
                        $this->places,
                        $this->actif,
                        $this->created_at,
                    ], true));

                $success = $stmt->execute([
                    $this->userId,
                    $this->marque,
                    $this->modele,
                    $this->carburant,
                    $this->immatriculation,
                    $this->places,
                    $this->actif,
                    $this->createdAt,
                ]);
                if ($success) {
                    $this->vehicleId = (int)$pdo->lastInsertId();
                    error_log('Insertion réussie avec l\'ID : ' . $this->vehicleId);
                } else {
                    error_log('Erreur lors de l\'insertion : ' . print_r($stmt->errorInfo(), true));
                }
                return $success;
            }
        } catch (PDOException $e) {
            error_log('Exception PDO : ' . $e->getMessage());
            if( str_contains($e->getMessage(), 'duplicate key') || $e->getCode() == '23505' ) {
                $this->errors['immatriculation'] = "Cette plaque d'immatriculation est déjà enregistrée. Veuillez en saisir une autre.";
            } else {
                $this->errors['database'] = "Erreur lors de la sauvegarde : " . $e->getMessage();
            }
            return false;
        }
    }

    public function deleteCar(): bool
    {
        $this->setActif(false);
        return $this->saveVehicleToDatabase();
    }

    public static function findActiveVehiclesByUser(int $userId): array
    {
        $pdo = Database::getConnection();
        $sql = "SELECT * FROM vehicule WHERE id_conducteur = ? AND actif = true ORDER BY date_creation DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($r) => new self($r), $rows);
        }

    /**
     * Finds and retrieves an instance of the object based on the provided ID.
     *
     * @param int $carId The unique identifier of the record to be fetched from the database.
     * @return self|null Returns an instance of the object if the record is found, or null if no matching record exists.
     */
    public static function findCarById(int $carId): ?self
        {
            $pdo = Database::getConnection();
            $sql = "SELECT * FROM vehicule WHERE id_vehicule = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$carId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? new self($row) : null;
        }

        /**
         * Recupère le nombre de place d'un véhicule par son ID
         */
        public static function getPlacesByVehiculeId(int $vehiculeId): int
        {
            $pdo = Database::getConnection();
            $sql = "SELECT nbr_places FROM vehicule WHERE id_vehicule = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$vehiculeId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? (int)$result['nbr_places'] : 0;
        }
    }
