<?php

namespace Olivierguissard\EcoRide\Model;

use class\PDOException;
use Olivierguissard\EcoRide\config\Database;
use PDO;

class Car
{
    public ?int $id;
    public int $user_id;
    public string $marque;
    public string $modele;
    public string $carburant;
    public string $immatriculation;
    public int $places;
    public bool $actif;
    public string $created_at;
    public array $errors = [];

    public function __construct(array $data = [])
    {
        $this->id               = $data['id_vehicule'] ?? null;
        $this->user_id          = (int)($data['id_conducteur'] ?? 0);
        $this->marque           = trim($data['marque'] ?? '');
        $this->modele            = trim($data['modele'] ?? '');
        $this->carburant        = $data['type_carburant'] ?? '';
        $this->immatriculation  = trim($data['plaque_immatriculation'] ?? '');
        $this->places           = (int)($data['nbr_places'] ?? 1);
        $this->actif            = isset($data['actif']) ? (bool)$data['actif'] : true;
        $this->created_at       = $data['date_creation'] ?? date('Y-m-d H:i:s');
    }

    public function validateCar(): bool
    {
        if ($this->marque === '') {
            $this->errors['marque'] = 'La marque de la voiture est requise.';
        }
        if ($this->modele === '') {
            $this->errors['modele'] = 'Le modèle de la voiture est requis.';
        }
        if (!in_array($this->carburant, ['electrique', 'hybride', 'essence', 'gazole'], true)) {
            $this->errors['carburant'] = 'Le carburant de la voiture est invalide. Sélectionnez un carburant dans la liste';
        }
        if (!preg_match('/^[A-Z0-9-]{2,10}$/', $this->immatriculation)) {
            $this->errors['immatriculation'] = 'Plaque invalide.';
        }
        if ($this->places < 1 && $this->places > 5) {
            $this->errors['places'] = 'Le nombre de places de la voiture doit-être compris entre 1 et 5.';
        }
        return empty($this->errors);
    }


    public function saveToDatabase() : bool
    {
        try {
            if (!$this->validateCar()) {
                error_log('Validation échouée avant sauvegarde');
                return false;
            }

            $pdo = Database::getConnection();
            if ($this->id) {
                $sql = "UPDATE vehicule SET marque=?, modele=?, type_carburant=?, plaque_immatriculation=?, nbr_places=?, actif=? WHERE id_vehicule = ?";
                $stmt = $pdo->prepare($sql);
                $ok = $stmt->execute([
                    $this->marque,
                    $this->modele,
                    $this->carburant,
                    $this->immatriculation,
                    $this->places,
                    $this->actif,
                    $this->id
                ]);
                return $ok;
            } else {
                $sql = "INSERT INTO vehicule (id_conducteur, marque, modele, type_carburant,plaque_immatriculation, nbr_places, actif, date_creation) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);

                // Debug - Afficher les valeurs à insérer
                error_log('Tentative d\'insertion avec les valeurs : ' . print_r([
                        $this->user_id,
                        $this->marque,
                        $this->modele,
                        $this->carburant,
                        $this->immatriculation,
                        $this->places,
                        $this->actif,
                        $this->created_at,
                    ], true));

                $ok = $stmt->execute([
                    $this->user_id,
                    $this->marque,
                    $this->modele,
                    $this->carburant,
                    $this->immatriculation,
                    $this->places,
                    $this->actif,
                    $this->created_at,
                ]);
                if ($ok) {
                    $this->id = (int)$pdo->lastInsertId();
                    error_log('Insertion réussie avec l\'ID : ' . $this->id);
                } else {
                    error_log('Erreur lors de l\'insertion : ' . print_r($stmt->errorInfo(), true));
                }
                return $ok;
            }
        } catch (PDOException $e) {
            error_log('Exception PDO : ' . $e->getMessage());
            $this->errors['database'] = "Erreur lors de la sauvegarde : " . $e->getMessage();
            return false;
        }
    }

    public function deleteCar(): bool
    {
        $this->actif = false;
        return $this->saveToDatabase();
    }

    public static function findByUser(int $user_id): array
    {
        $pdo = Database::getConnection();
        $sql = "SELECT * FROM vehicule WHERE id_conducteur = ? AND actif = true ORDER BY date_creation DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($r) => new self($r), $rows);
        }

        public static function find(int $id): ?self
        {
            $pdo = Database::getConnection();
            $sql = "SELECT * FROM vehicule WHERE id_vehicule = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? new self($row) : null;
        }
    }
