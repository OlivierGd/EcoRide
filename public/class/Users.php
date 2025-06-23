<?php

namespace class;

use PDOException;

class users
{
    private string $firstName;
    private string $lastName;
    private string $email;
    private string $password;

    public function __construct($firstName, $lastName, $email, $password)
    {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->email = $email;
        $this->password = $password;
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
    public function saveToDatabase($pdo)
    {
        try {
            $sql = "INSERT INTO users (firstName, lastName, email, password) VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            return $stmt->execute([$this->firstName, $this->lastName, $this->email, $this->password]);
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de l'enregistrement : " . $e->getMessage());
        }
    }
}