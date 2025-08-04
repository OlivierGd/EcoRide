<?php

namespace Olivierguissard\EcoRide\Model;

use DateTime;
use DateTimeZone;
use Dom\Text;
use Exception;
use Olivierguissard\EcoRide\Config\Database;
use PDO;
use PDOException;

class Users
{
    private ?int $userId;
    private string $firstName;
    private string $lastName;
    private string $email;
    private string $password;
    private ?string $profilePicture;
    private float $ranking;
    private int $credits; // Recoit 20 crédits à l'inscription
    private int $role; // 0:passager, 1:chauffeur, 2:gestionnaire, 3:admin
    private string $status; // actif, inactif
    private DateTime $created_at;

    public function __construct(array $data = [])
    {
        $this->userId       = isset($data['user_id']) ? (int)$data['user_id'] : null;
        $this->firstName    = trim($data['firstname']);
        $this->lastName     = trim($data['lastname']);
        $this->email        = trim($data['email']);
        $this->password     = trim($data['password']);
        $this->profilePicture = $data['profile_picture'] ?? null;
        $this->ranking      = (float)($data['ranking'] ?? 5);
        $this->credits      = (int)($data['credits'] ?? 20);
        $this->status       = $data['status'] ?? 'actif';
        $this->role         = (int)($data['role'] ?? 0);
        $this->created_at   = new DateTime('now', new DateTimeZone('Europe/Paris'));
    }

    public const ROLE_PASSAGER      = 0;
    public const ROLE_CHAUFFEUR     = 1;
    public const ROLE_GESTIONNAIRE  = 2;
    public const ROLE_ADMIN         = 3;
    public const STATUS_ACTIF       = 'actif';
    public const STATUS_INACTIF     = 'inactif';
    public function getRoleLabel() : string
    {
        return match ($this->role) {
             self::ROLE_PASSAGER   => 'Passager',
             self::ROLE_CHAUFFEUR  => 'Passager / Chauffeur',
             self::ROLE_GESTIONNAIRE => 'Gestionnaire',
             self::ROLE_ADMIN      => 'Administrateur'
        };
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }
    public function getFirstName(): string
    {
        return $this->firstName;
    }
    public function getLastName(): string
    {
        return $this->lastName;
    }
    public function getEmail(): string
    {
        return $this->email;
    }
    public function getPassword(): string
    {
        return $this->password;
    }
    public function setPassword(): void
    {
        $this->password = password_hash($this->password, PASSWORD_DEFAULT);
    }
    public function getProfilePicture(): ?string
    {
        return $this->profilePicture;
    }
    public function getRanking() : float
    {
        return $this->ranking;
    }
    public function getCredits() : int
    {
        return $this->credits;
    }
    public function getRole() : int
    {
        return $this->role;
    }
    public function getStatus() : string
    {
        return $this->status;
    }
    public function getCreatedAt() : DateTime
    {
        return $this->created_at;
    }
    public function getInitials() : string
    {
        return $this->firstName[0] . $this->lastName[0];
    }
    public function saveUserToDatabase($pdo) : int|false
    {
        try {
            $sql = "INSERT INTO users (firstName, lastName, email, password, ranking, credits, status, role, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $success = $stmt->execute([
                $this->firstName,
                $this->lastName,
                $this->email,
                $this->password,
                $this->ranking,
                $this->credits,
                $this->status,
                $this->role,
                $this->created_at->format('Y-m-d H:i:s')]);
            if ($success) {
                return (int)$pdo->lastInsertId(); // permet de récupérer user_id
            }
            return false;
        } catch (\PDOException $e) {
            throw new Exception("Erreur lors de l'enregistrement : " . $e->getMessage());
        }
    }

    public static function findUser(int $userId) : ?self
    {
        $pdo = Database::getConnection();
        $sql = "SELECT * FROM users WHERE user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? new self($data) : null;
    }

    public static function getUsersCredits(int $userId) : ?int {
        try {
            $pdo = Database::getConnection();
            $sql = "SELECT credits FROM users WHERE user_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userId]);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Erreur Users::getUsersCredits : " . $e->getMessage());
            return null;
        }
    }
}