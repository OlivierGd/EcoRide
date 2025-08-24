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
        $this->userId = isset($data['user_id']) ? (int)$data['user_id'] : null;
        $this->firstName = trim($data['firstname']);
        $this->lastName = trim($data['lastname']);
        $this->email = trim($data['email']);
        $this->password = trim($data['password'] ?? '');
        $this->profilePicture = $data['profile_picture'] ?? null;
        $this->ranking = (float)($data['ranking'] ?? 5);
        $this->credits = (int)($data['credits'] ?? 20);
        $this->status = $data['status'] ?? 'actif';
        $this->role = (int)($data['role'] ?? 0);
        $this->created_at = new DateTime('now', new DateTimeZone('Europe/Paris'));
    }

    public const ROLE_PASSAGER = 0;
    public const ROLE_CHAUFFEUR = 1;
    public const ROLE_GESTIONNAIRE = 2;
    public const ROLE_ADMIN = 3;
    public const STATUS_ACTIF = 'actif';
    public const STATUS_INACTIF = 'inactif';

    public function getRoleLabel(): string
    {
        return match ($this->role) {
            self::ROLE_PASSAGER => 'Passager',
            self::ROLE_CHAUFFEUR => 'Passager / Chauffeur',
            self::ROLE_GESTIONNAIRE => 'Gestionnaire',
            self::ROLE_ADMIN => 'Administrateur'
        };
    }

    public function getUserId(): ?int {return $this->userId;}

    public function getFirstName(): string {return $this->firstName;}

    public function getLastName(): string {return $this->lastName;}

    public function getEmail(): string {return $this->email;}

    public function getPassword(): string {return $this->password;}

    public function setPassword(): void {$this->password = password_hash($this->password, PASSWORD_DEFAULT);}

    public function getProfilePicture(): ?string {return $this->profilePicture;}

    public function getRanking(): float {return $this->ranking;}

    public function getCredits(): int {return $this->credits;}

    public function getRole(): int {return $this->role;}

    public function getStatus(): string {return $this->status;}

    public function getCreatedAt(): DateTime {return $this->created_at;}

    public function getInitials(): string {return $this->firstName[0] . $this->lastName[0];}

    public static function getCurrentUser(): ?self
    {
        if (!isset($_SESSION['user_id'])) {
            return null;
        }
        return self::findUser((int)$_SESSION['user_id']);
    }

    public static function getCurrentUserRole(): ?int
    {
        if (!isset($_SESSION['user_id'])) {
            return null;
        }
        return self::findUser((int)$_SESSION['user_id'])->getRole();
    }

    public function saveUserToDatabase($pdo): int|false
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

    public static function findUser(int $userId): ?self
    {
        $pdo = Database::getConnection();
        $sql = "SELECT * FROM users WHERE user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? new self($data) : null;
    }

    public static function getUsersCredits(int $userId): ?int
    {
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

    /**
     * Compte le nombre total d'utilisateurs
     */
    public static function countAllUsers(): int
    {
        try {
            $pdo = Database::getConnection();
            $sql = "SELECT COUNT(*) FROM users";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            return (int)$stmt->fetchColumn();
        } catch (\PDOException $e) {
            error_log("Erreur Users::countAllUsers : " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Sauvegarde un utilisateur créé par l'admin SANS mot de passe
     * Un email sera envoyé pour que l'utilisateur crée son propre mot de passe
     *
     * @param PDO $pdo Connexion à la base de données
     * @return array Résultat avec user_id si succès
     * @throws Exception En cas d'erreur de base de données
     */
    public function saveUserFromAdmin($pdo): array
    {
        try {
            // Pas de mot de passe - sera créé via le lien email
            $sql = "INSERT INTO users (firstName, lastName, email, ranking, credits, status, role, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $success = $stmt->execute([
                $this->firstName,
                $this->lastName,
                $this->email,
                $this->ranking,
                $this->credits,
                $this->status,
                $this->role,
                $this->created_at->format('Y-m-d H:i:s')
            ]);

            if ($success) {
                $userId = (int)$pdo->lastInsertId();
                $this->userId = $userId;

                return [
                    'success' => true,
                    'user_id' => $userId
                ];
            }
            return ['success' => false, 'error' => 'Échec de la création'];

        } catch (\PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Génère un token de réinitialisation pour un nouvel utilisateur
     * et envoie un email d'activation
     *
     * @param PDO $pdo Connexion à la base de données
     * @param Mailer $mailer Instance du service email
     * @return array Résultat de l'opération
     */
    public function sendActivationEmail($pdo, $mailer): array
    {
        try {
            // Générer un token unique
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+24 hours')); // 24h pour les nouveaux comptes

            // Supprimer les anciens tokens (au cas où)
            $sql = "DELETE FROM password_reset_tokens WHERE user_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$this->userId]);

            // Insérer le nouveau token
            $sql = "INSERT INTO password_reset_tokens (user_id, token, expires_at, created_at) 
                VALUES (?, ?, ?, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$this->userId, $token, $expires]);

            // Préparer l'email d'activation
            $activationUrl = "https://" . $_SERVER['HTTP_HOST'] . "/reset_password.php?token=" . $token;

            $subject = "Bienvenue sur EcoRide - Créez votre mot de passe";
            $htmlContent = "
            <h2>Bienvenue sur EcoRide, {$this->firstName} !</h2>
            <p>Un compte a été créé pour vous sur la plateforme EcoRide.</p>
            <p>Pour commencer à utiliser votre compte, vous devez créer votre mot de passe :</p>
            <p style='text-align: center; margin: 30px 0;'>
                <a href='{$activationUrl}' 
                   style='background: #28a745; color: white; padding: 15px 25px; text-decoration: none; border-radius: 5px; font-weight: bold;'>
                   Créer mon mot de passe
                </a>
            </p>
            <p><strong>Vos informations de compte :</strong></p>
            <ul>
                <li>Email : {$this->email}</li>
                <li>Rôle : {$this->getRoleLabel()}</li>
                <li>Crédits de départ : {$this->credits}</li>
            </ul>
            <p>Ce lien expire dans 24 heures.</p>
            <p>Si vous n'avez pas demandé la création de ce compte, contactez un administrateur.</p>
            <p>L'équipe EcoRide</p>
        ";

            $textContent = "Bienvenue sur EcoRide ! Créez votre mot de passe : {$activationUrl}";

            $result = $mailer->sendEmail(
                $this->email,
                $this->firstName . ' ' . $this->lastName,
                $subject,
                $htmlContent,
                $textContent
            );

            if ($result['success']) {
                return ['success' => true, 'message' => 'Email d\'activation envoyé'];
            } else {
                return ['success' => false, 'error' => 'Erreur lors de l\'envoi de l\'email'];
            }

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Vérifie si l'utilisateur connecté peut créer un utilisateur avec le rôle demandé
     *
     * @param int $currentUserRole Rôle de l'utilisateur connecté
     * @param int $targetRole Rôle que l'on veut attribuer au nouvel utilisateur
     * @return bool True si autorisé, false sinon
     */
    public static function canCreateUserWithRole(int $currentUserRole, int $targetRole): bool
    {
        switch ($currentUserRole) {
            case self::ROLE_ADMIN:
                // Les admins peuvent créer tous les types de comptes
                return true;

            case self::ROLE_GESTIONNAIRE:
                // Les gestionnaires peuvent créer uniquement des passagers et chauffeurs
                return in_array($targetRole, [self::ROLE_PASSAGER, self::ROLE_CHAUFFEUR]);

            default:
                // Les autres rôles ne peuvent rien créer
                return false;
        }
    }

    /**
     * Retourne la liste des rôles qu'un utilisateur peut créer
     *
     * @param int $currentUserRole Rôle de l'utilisateur connecté
     * @return array Liste des rôles autorisés avec leurs labels (clés préservées)
     */
    public static function getAllowedRolesForCreation(int $currentUserRole): array
    {
        switch ($currentUserRole) {
            case self::ROLE_ADMIN:
                return [
                    (string)self::ROLE_PASSAGER => 'Passager',
                    (string)self::ROLE_CHAUFFEUR => 'Passager / Chauffeur',
                    (string)self::ROLE_GESTIONNAIRE => 'Gestionnaire',
                    (string)self::ROLE_ADMIN => 'Administrateur'
                ];

            case self::ROLE_GESTIONNAIRE:
                return [
                    (string)self::ROLE_PASSAGER => 'Passager',
                    (string)self::ROLE_CHAUFFEUR => 'Passager / Chauffeur'
                ];

            default:
                return [];
        }
    }

    /**
     * Vérifie si l'utilisateur connecté peut modifier un utilisateur donné
     *
     * @param int $currentUserRole Rôle de l'utilisateur connecté
     * @param int $targetUserRole Rôle de l'utilisateur à modifier
     * @return bool True si modification autorisée
     */
    public static function canModifyUser(int $currentUserRole, int $targetUserRole): bool
    {
        switch ($currentUserRole) {
            case self::ROLE_ADMIN:
                // Les admins peuvent modifier tous les comptes
                return true;

            case self::ROLE_GESTIONNAIRE:
                // Les gestionnaires peuvent modifier uniquement passagers et chauffeurs
                return in_array($targetUserRole, [self::ROLE_PASSAGER, self::ROLE_CHAUFFEUR]);

            default:
                // Les autres rôles ne peuvent rien modifier
                return false;
        }
    }

    /**
     * Vérifie si l'utilisateur connecté peut modifier le rôle d'un utilisateur
     *
     * @param int $currentUserRole Rôle de l'utilisateur connecté
     * @param int $targetUserRole Rôle actuel de l'utilisateur à modifier
     * @param int $newRole Nouveau rôle souhaité
     * @return bool True si changement de rôle autorisé
     */
    public static function canChangeUserRole(int $currentUserRole, int $targetUserRole, int $newRole): bool
    {
        switch ($currentUserRole) {
            case self::ROLE_ADMIN:
                // Les admins peuvent changer tous les rôles
                return true;

            case self::ROLE_GESTIONNAIRE:
                // Les gestionnaires peuvent modifier le rôle uniquement si :
                // 1. L'utilisateur cible est passager ou chauffeur
                // 2. Le nouveau rôle est passager ou chauffeur
                return in_array($targetUserRole, [self::ROLE_PASSAGER, self::ROLE_CHAUFFEUR])
                    && in_array($newRole, [self::ROLE_PASSAGER, self::ROLE_CHAUFFEUR]);

            default:
                return false;
        }
    }

    /**
     * Détermine les actions possibles sur un utilisateur selon les permissions
     *
     * @param int $currentUserRole Rôle de l'utilisateur connecté
     * @param int $targetUserRole Rôle de l'utilisateur cible
     * @return array Actions autorisées
     */
    public static function getAllowedActionsForUser(int $currentUserRole, int $targetUserRole): array
    {
        $actions = [
            'can_view' => false,
            'can_edit_profile' => false,
            'can_edit_role' => false,
            'can_reset_password' => false,
            'can_delete' => false
        ];

        switch ($currentUserRole) {
            case self::ROLE_ADMIN:
                $actions = [
                    'can_view' => true,
                    'can_edit_profile' => true,
                    'can_edit_role' => true,
                    'can_reset_password' => true,
                    'can_delete' => true
                ];
                break;

            case self::ROLE_GESTIONNAIRE:
                if (in_array($targetUserRole, [self::ROLE_PASSAGER, self::ROLE_CHAUFFEUR])) {
                    // Peut tout faire sur passagers et chauffeurs
                    $actions = [
                        'can_view' => true,
                        'can_edit_profile' => true,
                        'can_edit_role' => true,
                        'can_reset_password' => true,
                        'can_delete' => true
                    ];
                } elseif (in_array($targetUserRole, [self::ROLE_GESTIONNAIRE, self::ROLE_ADMIN])) {
                    // Peut seulement voir les gestionnaires et admins
                    $actions = [
                        'can_view' => true,
                        'can_edit_profile' => false,
                        'can_edit_role' => false,
                        'can_reset_password' => false,
                        'can_delete' => false
                    ];
                }
                break;
        }
        return $actions;
    }
}