<?php

use Olivierguissard\EcoRide\Config\Database;

function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        // Session courte pour la sécurité (2 heures)
        ini_set('session.gc_maxlifetime', 7200);
        ini_set('session.cookie_lifetime', 0); // Se ferme avec le navigateur
        ini_set('session.use_strict_mode', 1);

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        session_start();

        // Vérification auto-login SEULEMENT si pas connecté
        if (!isAuthenticated()) {
            checkRememberToken();
        }

        // Régénération périodique de l'ID de session
        if (!isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = time();
        } elseif (time() - $_SESSION['last_regeneration'] > 300) {  // 5 minutes
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    }
}

// Vérifie si l'utilisateur est connecté
function isAuthenticated(): bool {
    startSession();
    // Vérification plus robuste
    return !empty($_SESSION['user_id']) && !empty($_SESSION['connecte']) && $_SESSION['connecte'] === true;
}

// Retourne le user_id
function getUserId(): ?int {
    startSession();
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

// Sécuriser les pages privées (auth. obligatoire)
function requireAuth(): void {
    startSession(); // S'assurer que la session est démarrée
    if (!isAuthenticated()) {
        header('Location: login.php');
        exit;
    }
}

// Met à jour l'activité de l'utilisateur
function updateActivity(): void {
    startSession();
    if (isAuthenticated()) {
        $_SESSION['last_activity'] = time();
    }
}

// === REMEMBER ME ===

// connecte l'utilisateur avec toutes ses données
function loginUserComplete(array $user, bool $remember = false): void {
    startSession();

    // S'assurer que toutes les variables de session sont définies
    $_SESSION['connecte']   = true;
    $_SESSION['email']      = $user['email'];
    $_SESSION['firstName']  = $user['firstname'];
    $_SESSION['lastName']   = $user['lastname'];
    $_SESSION['status']     = $user['status'];
    $_SESSION['role']       = $user['role'];
    $_SESSION['credits']    = $user['credits'];
    $_SESSION['ranking']    = $user['ranking'];
    $_SESSION['user_id']    = (int)$user['user_id']; // S'assurer que c'est un entier
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();

    // log de connexion
    logLogin($user['user_id'], 'password', true);

    // Crée un token "Remember" si demandé
    if ($remember) {
        createRememberToken($user['user_id']);
    }
}

// Vérifie le token "Remember Me"
function checkRememberToken(): void {
    if (!isset($_COOKIE['ecoride_remember'])) {
        return;
    }

    $token = $_COOKIE['ecoride_remember'];

    try {
        $pdo = Database::getConnection();

        // Utiliser password_verify pour vérifier le token
        $sql = "SELECT ut.token_hash, u.* 
                FROM users u 
                JOIN user_tokens ut ON u.user_id = ut.user_id 
                WHERE ut.expires_at > NOW() AND ut.is_active = true";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $tokens = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $tokenUser = null;
        foreach ($tokens as $tokenData) {
            if (password_verify($token, $tokenData['token_hash'])) {
                $tokenUser = $tokenData;
                break;
            }
        }

        if ($tokenUser) {
            // Reconnexion automatique avec toutes les données
            $_SESSION['connecte']   = true;
            $_SESSION['email']      = $tokenUser['email'];
            $_SESSION['firstName']  = $tokenUser['firstname'];
            $_SESSION['lastName']   = $tokenUser['lastname'];
            $_SESSION['status']     = $tokenUser['status'];
            $_SESSION['role']       = $tokenUser['role'];
            $_SESSION['credits']    = $tokenUser['credits'];
            $_SESSION['ranking']    = $tokenUser['ranking'];
            $_SESSION['user_id']    = (int)$tokenUser['user_id'];
            $_SESSION['login_time'] = time();
            $_SESSION['last_activity'] = time();
            $_SESSION['auto_login'] = true;  // Marque comme connexion automatique

            // Met à jour last_used
            $sql = "UPDATE user_tokens SET last_used = NOW() WHERE token_hash = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$tokenUser['token_hash']]);

            // log de connexion
            logLogin($tokenUser['user_id'], 'remember_token', true);

        } else {
            // Token invalide, supprime le cookie
            setcookie('ecoride_remember', '', [
                'expires' => time() - 3600,
                'path' => '/',
                'domain' => '',
                'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        }

    } catch (Exception $e) {
        error_log("Erreur vérification token EcoRide : " . $e->getMessage());
    }
}

// Information sur l'appareil
function getDeviceInfo(): string {
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

    // Détection de l'appareil
    if (preg_match('/Mobile|Android|iPhone/', $userAgent)) {
        $deviceType = 'Mobile';
    } elseif (preg_match('/Tablet|iPad/', $userAgent)) { // CORRECTION: Tablet au lieu de Tablettet
        $deviceType = 'Tablet';
    } else {
        $deviceType = 'Desktop';
    }
    return $deviceType . ' - ' . substr($userAgent, 0, 100);
}

// Log des connexions
function logLogin(int $userId, string $method, bool $success): void {
    try {
        $pdo = Database::getConnection();
        $sql = "INSERT INTO login_history (user_id, login_method, success, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $userId,
            $method,
            $success ? 'true' : 'false',
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ]);
    } catch (Exception $e) {
        error_log("Erreur log de connexion EcoRide : " . $e->getMessage());
    }
}

// Déconnexion complète du compte
function logoutUser(): void {
    startSession();

    if (isset($_SESSION['user_id'])) {
        // Désactive le token de cet appareil
        if (isset($_COOKIE['ecoride_remember'])) {
            $token = $_COOKIE['ecoride_remember'];

            try {
                $pdo = Database::getConnection();

                // Chercher et désactiver le token correspondant
                $sql = "SELECT token_hash FROM user_tokens WHERE user_id = ? AND is_active = true";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$_SESSION['user_id']]);
                $tokens = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($tokens as $tokenData) {
                    if (password_verify($token, $tokenData['token_hash'])) {
                        $sql = "UPDATE user_tokens SET is_active = false WHERE token_hash = ?";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([$tokenData['token_hash']]);
                        break;
                    }
                }
            } catch (Exception $e) {
                error_log("Erreur désactivation du token EcoRide : " . $e->getMessage());
            }

            // Supprime le cookie
            setcookie('ecoride_remember', '', [
                'expires' => time() - 3600,
                'path' => '/',
                'domain' => '',
                'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        }
    }

    // Supprime la session
    $_SESSION = array();

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

// === FONCTION DE DEBUG ===
function debugAuthStatus(): array {
    startSession();
    return [
        'session_status' => session_status(),
        'session_id' => session_id(),
        'user_id' => $_SESSION['user_id'] ?? 'null',
        'connecte' => $_SESSION['connecte'] ?? 'null',
        'isAuthenticated' => isAuthenticated() ? 'true' : 'false',
        'cookie_remember' => isset($_COOKIE['ecoride_remember']) ? 'exists' : 'not_exists',
        'session_data' => $_SESSION
    ];
}


function limitUserTokens(int $userId, int $maxTokens): void {
    try {
        $pdo = Database::getConnection();
        $sql = "SELECT COUNT(*) FROM user_tokens 
                WHERE user_id = ? AND expires_at > NOW() AND is_active = true";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        $count = $stmt->fetchColumn();

        if ($count >= $maxTokens) {
            $sql = "DELETE FROM user_tokens 
                    WHERE token_id IN ( 
                        SELECT token_id FROM (
                            SELECT token_id FROM user_tokens
                            WHERE user_id = ? AND expires_at > NOW() AND is_active = true
                            ORDER BY last_used ASC LIMIT ?
                        ) as subquery
                    )";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userId, $count - $maxTokens + 1]);
        }
    } catch (Exception $e) {
        error_log("Erreur limitation tokens EcoRide (5 appareils max) : " . $e->getMessage());
    }
}

function createRememberToken(int $userId): void {
    try {
        $pdo = Database::getConnection();

        // Limite à 5 appareils par utilisateur
        limitUserTokens($userId, 4);

        $token = bin2hex(random_bytes(32));
        $tokenHash = password_hash($token, PASSWORD_DEFAULT);
        $tokenExpiry = date('Y-m-d H:i:s', strtotime('+6 months'));

        $deviceInfo = getDeviceInfo();
        $ipAdresse = $_SERVER['REMOTE_ADDR'] ?? null;

        $sql = "INSERT INTO user_tokens (user_id, token_hash, expires_at, device_info, ip_address) VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId, $tokenHash, $tokenExpiry, $deviceInfo, $ipAdresse]);

        // Cookie de 6 mois
        setcookie('ecoride_remember', $token, [
            'expires' => strtotime($tokenExpiry),
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    } catch (Exception $e) {
        error_log("Erreur de création du token EcoRide : " . $e->getMessage());
    }
}

function logoutAllDevices(int $userId): void {
    try {
        $pdo = Database::getConnection();
        $sql = "UPDATE user_tokens SET is_active = false WHERE user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
    } catch (Exception $e) {
        error_log("Erreur de déconnexion de tous les appareils: " . $e->getMessage());
    }
}

function getUserDevices(int $userId): array {
    try {
        $pdo = Database::getConnection();
        $sql = "SELECT token_id, device_info, created_at, last_used, ip_address 
                FROM user_tokens WHERE user_id = ? 
                AND expires_at > NOW() 
                AND is_active = true ORDER BY last_used DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Erreur de récupération appareils: " . $e->getMessage());
        return [];
    }
}

function revokeDevice(int $tokenId, int $userId): bool
{
    if ($tokenId <= 0 || $userId <= 0) {
        error_log("Paramètres invalides pour revokeDevice: tokenId=$tokenId, userId=$userId");
        return false;
    }

    try {
        $pdo = Database::getConnection();
        $sql = "UPDATE user_tokens SET is_active = false WHERE token_id = ? AND user_id = ? AND is_active = true";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$tokenId, $userId]);

        if ($stmt->rowCount() > 0) {
            return true;
        } else {
            error_log("Aucun token actif trouvé pour tokenId=$tokenId et userId=$userId");
            return false;
        }
    } catch (PDOException $e) {
        error_log("Erreur base de données lors de la révocation appareil: " . $e->getMessage());
        return false;
    } catch (Exception $e) {
        error_log("Erreur générale lors de la révocation appareil: " . $e->getMessage());
        return false;
    }
}
/**
 * Nettoie les tokens de réinitialisation de mot de passe expirés
 *
 * Supprime automatiquement les tokens expirés depuis plus de 24 heures
 * pour éviter l'accumulation de données inutiles en base de données.
 *
 * @return int|false Le nombre de tokens supprimés ou false en cas d'erreur
 * @throws PDOException Si la connexion à la base de données échoue
 * @since 1.0.0
 * @author Olivier Guissard
 *
 * @example
 * $deleted = cleanupExpiredTokens();
 * if ($deleted !== false) {
 *     echo "$deleted tokens supprimés";
 * }
 */
function cleanupExpiredTokens() {
    try {
        $pdo = Database::getConnection();

        // Supprimer les tokens expirés depuis plus de 24h
        $sql = "DELETE FROM password_reset_tokens WHERE expires_at < NOW() - INTERVAL '24 hours'";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();

        return $stmt->rowCount(); // Nombre de tokens supprimés

    } catch (Exception $e) {
        error_log("Erreur cleanup tokens: " . $e->getMessage());
        return false;
    }
}

/**
 * Nettoyage probabiliste automatique des tokens expirés
 *
 * Exécute cleanupExpiredTokens() avec une probabilité de 1% à chaque appel
 * pour maintenir la base de données propre sans impact sur les performances.
 *
 * Statistiques : Avec 100 connexions/jour = ~1 nettoyage quotidien
 *
 * @see cleanupExpiredTokens() La fonction de nettoyage appelée
 */
// Appeler automatiquement le nettoyage (1 chance sur 100 à chaque session)
if (mt_rand(1, 100) === 1) {
    cleanupExpiredTokens();
}

/**
 * Vérifie si l'utilisateur connecté a le rôle minimum requis
 *
 * @param int $minimumRole Rôle minimum requis (0=passager, 1=chauffeur, 2=gestionnaire, 3=admin)
 * @return bool True si l'utilisateur a le rôle suffisant
 */
function hasMinimumRole(int $minimumRole): bool {
    if (!isAuthenticated()) {
        return false;
    }

    $userRole = (int)$_SESSION['role'];
    return $userRole >= $minimumRole;
}

/**
 * Vérifie si l'utilisateur connecté est un gestionnaire ou admin
 *
 * @return bool True si gestionnaire ou admin
 */
function isManagerOrAdmin(): bool {
    return hasMinimumRole(2); // Gestionnaire = 2
}

/**
 * Vérifie si l'utilisateur connecté est un admin
 *
 * @return bool True si admin
 */
function isAdmin(): bool {
    return hasMinimumRole(3); // Admin = 3
}

/**
 * Require un rôle minimum ou redirige
 *
 * @param int $minimumRole Rôle minimum requis
 * @param string $redirectTo Page de redirection si pas autorisé
 */
function requireMinimumRole(int $minimumRole, string $redirectTo = 'index.php'): void {
    if (!hasMinimumRole($minimumRole)) {
        header("Location: $redirectTo");
        exit;
    }
}