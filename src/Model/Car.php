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
    private string $statut; // 'actif', 'inactif', 'supprime'
    private string $createdAt;
    private ?string $dateSuppression = null;
    private array $errors = [];

    // Constantes pour les statuts
    public const STATUT_ACTIF = 'actif';
    public const STATUT_INACTIF = 'inactif';
    public const STATUT_SUPPRIME = 'supprime';

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
        $this->statut          = $data['statut'] ?? self::STATUT_ACTIF;
        $this->createdAt       = $data['date_creation'] ?? date('Y-m-d H:i:s');
        $this->dateSuppression = $data['date_suppression'] ?? null;
    }

    public function getVehicleId(): ?int { return $this->vehicleId; }
    public function getUserId(): int { return $this->userId; }
    public function getMarque(): string { return $this->marque; }
    public function getModele(): string { return $this->modele; }
    public function getCarburant(): string { return $this->carburant; }
    public function getImmatriculation(): string { return $this->immatriculation; }
    public function getPlaces(): int { return $this->places; }
    public function getActif(): bool { return $this->actif; }
    public function getCreatedAt(): string { return $this->createdAt; }
    public function getErrors(): array { return $this->errors; }
    public function getStatut(): string { return $this->statut; }
    public function getDateSuppression(): ?string { return $this->dateSuppression; }
    public function isDeleted(): bool { return $this->statut === self::STATUT_SUPPRIME; }
    public function isActive(): bool { return $this->statut === self::STATUT_ACTIF; }
    public function setMarque(string $marque): void { $this->marque = trim($marque); }
    public function setModele(string $modele): void { $this->modele = trim($modele); }
    public function setImmatriculation(string $immatriculation): void { $this->immatriculation = trim($immatriculation); }
    public function setActif(bool $actif): void {
        $this->actif = $actif;
        $this->statut = $actif ? self::STATUT_ACTIF : self::STATUT_INACTIF;
    }
    public function setStatut(string $statut): void
    {
        $allowedStatuts = [self::STATUT_ACTIF, self::STATUT_INACTIF, self::STATUT_SUPPRIME];
        if (in_array($statut, $allowedStatuts)) {
            $this->statut = $statut;
            $this->actif = ($statut === self::STATUT_ACTIF);
        } else {
            $this->errors['statut'] = 'Statut invalide.';
        }
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

    public function setPlaces(int $places): void
    {
        if ($places >= 1 && $places <= 8) {
            $this->places = $places;
        } else {
            $this->errors['places'] = 'Le nombre de places doit être compris entre 1 et 8.';
        }
    }

    // Méthodes de gestion du cycle de vie

    /**
     * Marque le véhicule comme supprimé (soft delete)
     */
    public function softDeleteVehicle(): bool
    {
        $this->statut = self::STATUT_SUPPRIME;
        $this->actif = false;
        $this->dateSuppression = date('Y-m-d H:i:s');
        return $this->saveVehicleToDatabase();
    }

    /**
     * Restaure un véhicule supprimé
     */
    public function restoreDeletedVehicle(): bool
    {
        $this->statut = self::STATUT_ACTIF;
        $this->actif = true;
        $this->dateSuppression = null;
        return $this->saveVehicleToDatabase();
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
            $this->errors['carburant'] = 'Le carburant de la voiture est invalide.';
        }
        if (!preg_match('/^[A-Z0-9-]{2,10}$/', $this->immatriculation)) {
            $this->errors['immatriculation'] = 'Plaque invalide.';
        }
        if ($this->places < 1 || $this->places > 8) {
            $this->errors['places'] = 'Le nombre de places doit être compris entre 1 et 8.';
        }

        // Validation supplémentaire pour les véhicules existants avec des trajets
        if ($this->vehicleId) {
            $tripCount = $this->countTrips();
            if ($tripCount > 0) {
                // Pour un véhicule avec des trajets, tous les champs sont obligatoires
                if (empty(trim($this->marque))) {
                    $this->errors['marque'] = 'La marque ne peut pas être vide pour un véhicule ayant des trajets.';
                }
                if (empty(trim($this->modele))) {
                    $this->errors['modele'] = 'Le modèle ne peut pas être vide pour un véhicule ayant des trajets.';
                }
                if (empty(trim($this->carburant))) {
                    $this->errors['carburant'] = 'Le carburant ne peut pas être vide pour un véhicule ayant des trajets.';
                }
                if (empty(trim($this->immatriculation))) {
                    $this->errors['immatriculation'] = 'L\'immatriculation ne peut pas être vide pour un véhicule ayant des trajets.';
                }
            }
        }

        return empty($this->errors);
    }

    public function saveVehicleToDatabase(): bool
    {
        try {
            if (!$this->validateCar()) {
                error_log('Validation échouée avant sauvegarde');
                return false;
            }
            $pdo = Database::getConnection();
            if ($this->vehicleId) {
                $sql = "UPDATE vehicule SET marque=?, modele=?, type_carburant=?, plaque_immatriculation=?, 
                        nbr_places=?, actif=?, statut=?, date_suppression=? WHERE id_vehicule = ?";
                $stmt = $pdo->prepare($sql);
                return $stmt->execute([
                    $this->marque,
                    $this->modele,
                    $this->carburant,
                    $this->immatriculation,
                    $this->places,
                    $this->actif,
                    $this->statut,
                    $this->dateSuppression,
                    $this->vehicleId
                ]);
            } else {
                $sql = "INSERT INTO vehicule (id_conducteur, marque, modele, type_carburant, plaque_immatriculation, 
                        nbr_places, actif, statut, date_creation) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $success = $stmt->execute([
                    $this->userId,
                    $this->marque,
                    $this->modele,
                    $this->carburant,
                    $this->immatriculation,
                    $this->places,
                    $this->actif,
                    $this->statut,
                    $this->createdAt,
                ]);

                if ($success) {
                    $this->vehicleId = (int)$pdo->lastInsertId();
                }
                return $success;
            }
        } catch (PDOException $e) {
            error_log('Exception PDO : ' . $e->getMessage());
            if (str_contains($e->getMessage(), 'duplicate key') || $e->getCode() == '23505') {
                $this->errors['immatriculation'] = "Cette plaque d'immatriculation est déjà enregistrée.";
            } else {
                $this->errors['database'] = "Erreur lors de la sauvegarde : " . $e->getMessage();
            }
            return false;
        }
    }

    /**
     * Méthode legacy pour compatibilité - soft delete
     */
    public function deleteCar(): bool
    {
        return $this->softDeleteVehicle();
    }

    /**
 * Récupère les véhicules actifs d'un utilisateur (exclut les supprimés)
 */
public static function findActiveVehiclesByUser(int $userId): array
{
    try {
        $pdo = Database::getConnection();

        $sql = "SELECT * FROM vehicule WHERE id_conducteur = ? AND statut != ? ORDER BY date_creation DESC";
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([$userId, self::STATUT_SUPPRIME]);
        if (!$result) {
            error_log("ERREUR: Échec de l'exécution de la requête");
            error_log("Erreur PDO: " . print_r($stmt->errorInfo(), true));
            return [];
        }

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("Nombre de lignes récupérées: " . count($rows));

        if (!empty($rows)) {
            error_log("Première ligne: " . print_r($rows[0], true));
        }

        $vehicles = [];
        foreach ($rows as $index => $row) {
            error_log("Traitement ligne $index...");
            try {
                $vehicle = new self($row);
                $vehicles[] = $vehicle;
                error_log("Véhicule créé: ID=" . $vehicle->getVehicleId() . ", Marque=" . $vehicle->getMarque());
            } catch (Exception $e) {
                error_log("ERREUR lors de la création du véhicule $index: " . $e->getMessage());
            }
        }

        error_log("Nombre d'objets Car créés: " . count($vehicles));
        error_log("=== FIN findActiveVehiclesByUser ===");

        return $vehicles;

    } catch (PDOException $e) {
        error_log('ERREUR PDO dans findActiveVehiclesByUser: ' . $e->getMessage());
        error_log('Code erreur: ' . $e->getCode());
        error_log('Stack trace: ' . $e->getTraceAsString());
        return [];
    } catch (Exception $e) {
        error_log('ERREUR générale dans findActiveVehiclesByUser: ' . $e->getMessage());
        error_log('Stack trace: ' . $e->getTraceAsString());
        return [];
    }
}



    /**
     * Récupère tous les véhicules d'un utilisateur (y compris supprimés, pour l'admin)
     */
    public static function findAllVehiclesByUser(int $userId, bool $includeSupprime = false): array
    {
        $pdo = Database::getConnection();
        $sql = "SELECT * FROM vehicule WHERE id_conducteur = ?";
        $params = [$userId];

        if (!$includeSupprime) {
            $sql .= " AND statut != ?";
            $params[] = self::STATUT_SUPPRIME;
        }

        $sql .= " ORDER BY date_creation DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($r) => new self($r), $rows);
    }

    public static function findCarById(int $carId): ?self
    {
        $pdo = Database::getConnection();
        $sql = "SELECT * FROM vehicule WHERE id_vehicule = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$carId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? new self($row) : null;
    }

    public static function getPlacesByVehiculeId(int $vehiculeId): int
    {
        $pdo = Database::getConnection();
        $sql = "SELECT nbr_places FROM vehicule WHERE id_vehicule = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$vehiculeId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? (int)$result['nbr_places'] : 0;
    }

    /**
     * Compte le nombre de trajets effectués avec ce véhicule
     */
    public function countTrips(): int
    {
        $pdo = Database::getConnection();
        $sql = "SELECT COUNT(*) FROM trips WHERE vehicle_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$this->vehicleId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Vérifie si le véhicule peut être supprimé (pas de trajets futurs)
     */
    public function canBeDeleted(): bool
    {
        $pdo = Database::getConnection();
        $sql = "SELECT COUNT(*) FROM trips WHERE vehicle_id = ? AND departure_at > NOW() AND status != 'annule'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$this->vehicleId]);
        return (int)$stmt->fetchColumn() === 0;
    }
}