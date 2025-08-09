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
            'secure' => strpos($_SERVER['HTTP_HOST'] ?? '', '.fly.dev') !== false,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        session_start();

        // Vérification auto-login si pas de session active
        if (isset($_SESSION['connecte']) && !($_SESSION['connecte'])) {
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

// Vérifie si l'utilisateur est connecté / authentifié
function isAuthenticated(): bool {
      startSession();
      return !empty($_SESSION['user_id']);
}

// Retourne le user_id
function getUserId(): ?int {
    startSession();
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null ;
}

// Sécuriser les pages privées (auth. obligatoire)
function requireAuth(): void {
    if (!isAuthenticated()) {
        header('Location: login.php');
        exit;
    }
}

// Met à jour l'acitivité de l'utilisateur
function updateActivity(): void {
    startSession();
    if (isAuthenticated()) {
        $_SESSION['last_activity'] = time();
    }
}

// === Fonctionnalités pour REMEBER ME ===

// connecte l'utilisateur avec toutes ses données
function loginUserComplete(array $user, bool $remember = false): void {
    startSession();
    $_SESSION['connecte']   = true;
    $_SESSION['email']      = $user['email'];
    $_SESSION['firstName']  = $user['firstname'];
    $_SESSION['lastName']   = $user['lastname'];
    $_SESSION['status']     = $user['status'];
    $_SESSION['role']       = $user['role'];
    $_SESSION['credits']    = $user['credits'];
    $_SESSION['ranking']    = $user['ranking'];
    $_SESSION['user_id']    = $user['user_id'];
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();

    // log de connexion
    logLogin($user['user_id'], 'password', true);

    // Crée un token "Remeber si demandé
    if ($remember) {
        createRememberToken($user['user_id']);
    }
}

// Crée un token "Remeber" pour l'utilisateur
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
            'secure' => strpos($_SERVER['HTTP_HOST'] ?? '', '.fly.dev') !== false,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    } catch (Exception $e) {
        error_log("Erreur de création du token EcoRide : " . $e->getMessage());
    }
}

// Limite le nombre de token par utilisateur
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
                    SELECT token_id FROM user_tokens
                    WHERE user_id = ? AND expires_at > NOW() AND is_active = true
                    ORDER BY last_used ASC LIMIT ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userId, $count - $maxTokens +1]);
        }
    } catch (Exception $e) {
        error_log("Erreur limitation tokens EcoRide (5 appareils max) : " . $e->getMessage());
    }
}

// Vérifie le token "Remeber Me"
function checkRememberToken(): void {
    if (!isset($_COOKIE['ecoride_remember'])) {
        return;
    }
    $token = $_COOKIE['ecoride_remember'];
    $tokenHash = hash('sha256', $token);
        try {
            $pdo = Database::getConnection();
            $sql = "SELECT u.* FROM users u JOIN user_tokens ut ON u.user_id = ut.user_id WHERE ut.token_hash = ? AND ut.expires_at > NOW() AND ut.is_active = true";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$tokenHash]);
            $tokenUser = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($tokenUser) {
                // Reconnexion automatique
                $_SESSION['connecte']   = true;
                $_SESSION['email']      = $tokenUser['email'];
                $_SESSION['firstName']  = $tokenUser['firstname'];
                $_SESSION['lastName']   = $tokenUser['lastname'];
                $_SESSION['status']     = $tokenUser['status'];
                $_SESSION['role']       = $tokenUser['role'];
                $_SESSION['credits']    = $tokenUser['credits'];
                $_SESSION['ranking']    = $tokenUser['ranking'];
                $_SESSION['user_id']    = $tokenUser['user_id'];
                $_SESSION['login_time'] = time();
                $_SESSION['last_activity'] = time();
                $_SESSION['auto_login'] = true;  //Marque comme connexion automatique

                // Met à jour last_used
                $sql = "UPDATE user_tokens SET last_used = NOW() WHERE token_hash = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$tokenHash]);

                // log de connexion
                logLogin($tokenUser['user_id'], 'remember_token', true);
        } else {
                // Token invalide, supprime le cookie
                setcookie('ecoride_remember', '', [
                    'expires' => time() - 3600,
                    'path' => '/',
                    'domain' => '',
                    'secure' => strpos($_SERVER['HTTP_HOST'] ?? '', '.fly.dev') !== false,
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]);
            }
    } catch (Exception $e) {
            error_log("Erreur vérification token EcoRide : " . $e->getMessage());
        }
}

// Information sur l'appreil
function getDeviceInfo(): string {
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    // Dectection de l'appareil
    if (preg_match('/Mobile|Android|iPhone/', $userAgent)) {
        $deviceType = 'Mobile';
    } elseif (preg_match('/Tablettet|iPad/', $userAgent)) {
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
        $stmt->execute([$userId, $method, $success, $_SERVER['REMOTE_ADDR'] ?? null , $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown']);;
    } catch (Exception $e) {
        error_log("Erreur log de connexion EcoRide : " . $e->getMessage());
    }
}

// Deconnexion complète du compte
function logoutUser(): void
{
    startSession();
    
    if (isset($_SESSION['user_id'])) {
        // Desactive le token de cet appareil
        if (isset($_COOKIE['ecoride_remember'])) {
            $token = $_COOKIE['ecoride_remember'];
            $tokenHash = hash('sha256', $token);

            try {
                $pdo = Database::getConnection();
                $sql = "UPDATE user_tokens SET is_active = false WHERE token_hash = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$tokenHash]);
            } catch (Exception $e) {
                error_log("Erreur désactivation du token EcoRide : " . $e->getMessage());
            }
            // Supprime le cookie
            setcookie('ecoride_remember', '', [
                'expires' => time() - 3600,
                'path' => '/',
                'domain' => '',
                'secure' => strpos($_SERVER['HTTP_HOST'] ?? '', '.fly.dev') !== false,
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

// Deconnexion de tous les appareils
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

// Récupère les appareils connectés d'un utilisateur
function getUserDevices(int $userId): array {
    try {
        $pdo = Database::getConnection();
        $sql = "SELECT token_id, device_info, created_at,last_used,ip_address 
                    FROM user_tokens WHERE user_id = ? 
                    AND expires_at > NOW() 
                    AND is_active = true ORDER BY last_used DESC ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Erreur de récupération appareils: " . $e->getMessage());
        return [];
    }
}

// Révoque un appareil spécifique
function revokeDevice(int $tokenId, int $userId): bool {
    // Validation des paramètres
    if ($tokenId <= 0 || $userId <= 0) {
        error_log("Paramètres invalides pour revokeDevice: tokenId=$tokenId, userId=$userId");
        return false;
    }

    try {
        $pdo = Database::getConnection();
        $sql = "UPDATE user_tokens SET is_active = false WHERE token_id = ? AND user_id = ? AND is_active = true";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$tokenId, $userId]);
        
        // Vérifier si au moins une ligne a été mise à jour
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
