<?php

namespace class;

use DateTimeZone;
use PDOException;
use Exception;
use DateTime;

class Users
{
    private string $firstName;
    private string $lastName;
    private string $email;
    private string $password;
    private ?string $profilePicture;
    private float $ranking;
    private int $credits; // Recoit 20 crédits à l'inscription
    private int $role; // 0:utilisateur, 1:chauffeur, 2:gestionnaire, 3:admin
    private string $status; // actif, inactif
    private DateTime $created_at;

    public function __construct(
        $firstName,
        $lastName,
        $email,
        $password,
        $profilePicture = null,
        $ranking = 5,
        $credits = 20,
        $status = 'actif',
        $role = 0
        )
    {
        $this->firstName = trim($firstName);
        $this->lastName = trim($lastName);
        $this->email = trim($email);
        $this->password = trim($password);
        $this->profilePicture = $profilePicture;
        $this->ranking = $ranking;
        $this->credits = $credits;
        $this->status = $status;
        $this->role = $role;
        $this->created_at = new DateTime('now', new DateTimeZone('Europe/Paris'));
    }

    public function getFirstname(): string
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
    public function saveToDatabase($pdo) : bool
    {
        try {
            $sql = "INSERT INTO users (firstName, lastName, email, password, credits, status, role, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            return $stmt->execute([
                $this->firstName,
                $this->lastName,
                $this->email,
                $this->password,
                $this->credits,
                $this->status,
                $this->role,
                $this->created_at->format('Y-m-d H:i:s')]);
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de l'enregistrement : " . $e->getMessage());
        }
    }
}