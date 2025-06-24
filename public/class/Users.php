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
    private $profilePicture;
    private float $ranking;
    private int $credits; // Recoit 20 crédits à l'inscription
    private string $role; // actif, inactif
    private string $status; // 0:utilisateur, 1:chauffeur, 2:gestionnaire, 3:admin
    private DateTime $created_at;

    public function __construct(
        $firstName,
        $lastName,
        $email,
        $password,
        $profilePicture = null,
        $ranking = 5,
        $credits = 20,
        $role = 'actif',
        $status = 0,
        )
    {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->email = $email;
        $this->password = $password;
        $this->profilePicture = $profilePicture;
        $this->ranking = $ranking;
        $this->credits = $credits;
        $this->role = $role;
        $this->status = $status;
        $this->created_at = new DateTime('now', new DateTimeZone('Europe/Paris'));
    }

    public function getFirstname()
    {
        return $this->firstName;
    }
    public function getName()
    {
        return $this->lastName;
    }
    public function getEmail()
    {
        return $this->email;
    }
    public function getPassword()
    {
        return $this->password;
    }
    public function setPassword(): void
    {
        $this->password = password_hash($this->password, PASSWORD_DEFAULT);
    }
    public function getProfilePicture() {
        return $this->profilePicture;
    }
    public function getRanking() {
        return $this->ranking;
    }
    public function getCredits() {
        return $this->credits;
    }
    public function getRole()
    {
        return $this->role;
    }
    public function getStatus()
    {
        return $this->status;
    }
    public function getCreatedAt() : DateTime
    {
        return $this->created_at;
    }
    public function saveToDatabase($pdo)
    {
        try {
            $sql = "INSERT INTO users (firstName, lastName, email, password, credits, role, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            return $stmt->execute([
                $this->firstName,
                $this->lastName,
                $this->email,
                $this->password,
                $this->credits,
                $this->role,
                $this->status,
                $this->created_at->format('Y-m-d H:i:s')]);
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de l'enregistrement : " . $e->getMessage());
        }
    }
}