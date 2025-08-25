<?php

use Olivierguissard\EcoRide\Config\Database;
use Predis\Client;
use Dotenv\Dotenv;

// --- Helpers cookie ---
function isHttps(): bool {
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
}

function cookieDomain(): string {
    // Host sans port, sans www
    $host = strtolower($_SERVER['HTTP_HOST'] ?? 'localhost');
    $host = preg_replace('/:\d+$/', '', $host);     // retire :8081
    if (strpos($host, 'www.') === 0) {
        $host = substr($host, 4);
    }
    // En local/IP -> pas de Domain (host-only)
    if ($host === 'localhost' || filter_var($host, FILTER_VALIDATE_IP)) {
        return '';
    }
    // En prod -> couvre sous-domaines
    return '.' . $host;
}

/**
 * Initializes and starts a PHP session with additional configurations for security and performance.
 * The method checks if a session is already active, determines the environment for Redis usage
 * (enabled in production), sets secure cookie parameters, and periodically regenerates the session ID.
 * Additionally, it handles an auto-login mechanism if the user is not authenticated.
 *
 * @return void
 */
function startSession(): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    // Détermine l'environnement
    $environment = $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?? 'production';

    // Active Redis uniquement en prod
    // Connexion Redis en TCP (non TLS)
    if (!str_contains(strtolower($environment), 'development')) {
        $redis = new Client([
            'scheme'   => 'tcp',
            'host'     => 'fly-withered-glitter-9761.upstash.io',
            'port'     => 6379,
            'password' => '2ef2ce77ce904842b61644b1db5ed9cb',
            'timeout'  => 10.0,
            'read_write_timeout' => 10.0,
        ]);
        // Définir le gestionnaire de session avec Predis
        $handler = new Predis\Session\Handler($redis);
        $handler->register();
    }

    // Garde un nom unique
    session_name('ecoride_session');

    // Sécurité de base
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.gc_maxlifetime', '7200');
    ini_set('session.cookie_lifetime', '0');

    $secure = isHttps();

    // Paramètres dev/prod
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => cookieDomain(),          // '' en local (host-only), jamais de port
        'secure'   => $secure,                 // true uniquement en HTTPS
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();


    // Régénération périodique de l'ID de session
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    } elseif (time() - $_SESSION['last_regeneration'] > 300) {  // 5 minutes
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
    // Vérification auto-login SEULEMENT si pas connecté
    if (!isAuthenticated()) {
        checkRememberToken();
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
function setInvalidRememberCookie(): void {
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    setcookie('ecoride_remember', '', [
        'expires' => time() - 3600,
        'path' => '/',
        'domain' => '',
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

// Vérifie le token "Remember Me"
function checkRememberToken(): void
{
    // Déjà connecté ou pas de cookie -> rien à faire
    if (isAuthenticated() || empty($_COOKIE['ecoride_remember'])) {
        return;
    }

    // Cookie attendu: "<id>:<secret>"
    $raw = $_COOKIE['ecoride_remember'];
    if (strpos($raw, ':') === false) {
        // Ancien format non supporté -> on nettoie et on sort
        setInvalidRememberCookie();
        return;
    }

    [$tokenId, $secret] = explode(':', $raw, 2);
    $tokenId = (int)$tokenId;
    if ($tokenId <= 0 || $secret === '') {
        setInvalidRememberCookie();
        return;
    }

    try {
        // Récupère le token + l'utilisateur
        $pdo = Database::getConnection();
        $sql = "SELECT t.token_id, t.user_id, t.token_hash, t.expires_at, t.is_active,
                   u.email, u.firstname, u.lastname, u.status, u.role, u.credits, u.ranking
                FROM user_tokens t
                JOIN users u ON u.user_id = t.user_id
                WHERE t.token_id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $tokenId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        // Garde-fous
        if (!$row || !$row['is_active'] || strtotime($row['expires_at']) < time()) {
            setInvalidRememberCookie();
            return;
        }

        // Vérif du secret
        $expected = $row['token_hash'];
        if (!hash_equals($expected, hash('sha256', $secret))) {
            // Secret invalide -> désactive le token + nettoie le cookie
            $sql = "UPDATE user_tokens SET is_active = false WHERE token_id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt ->execute([':id' => $tokenId]);
            setInvalidRememberCookie();
            return;
        }

        // Connexion: session + rotation d'ID
        startSession();
        session_regenerate_id(true);

        $_SESSION['connecte']      = true;
        $_SESSION['user_id']       = (int)$row['user_id'];
        $_SESSION['email']         = $row['email'] ?? '';
        $_SESSION['firstName']     = $row['firstname'] ?? '';
        $_SESSION['lastName']      = $row['lastname'] ?? '';
        $_SESSION['status']        = $row['status'] ?? '';
        $_SESSION['role']          = $row['role'] ?? 0;
        $_SESSION['credits']       = $row['credits'] ?? 0;
        $_SESSION['ranking']       = $row['ranking'] ?? 0;
        $_SESSION['login_time']    = $_SESSION['login_time'] ?? time();
        $_SESSION['last_activity'] = time();
        $_SESSION['auto_login']    = true;

        // Rotation anti-replay + prolongation
        $newSecret = rtrim(strtr(base64_encode(random_bytes(33)), '+/', '-_'), '=');
        $newHash   = hash('sha256', $newSecret);
        $sql = "UPDATE user_tokens SET token_hash = :hash, expires_at = NOW() + INTERVAL '6 months' WHERE token_id = :id";
        $pdo->prepare($sql);
        $stmt->execute([
            ':hash' => $newHash,
            ':ua'   => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            ':ip'   => $_SERVER['REMOTE_ADDR'] ?? null,
            ':id'   => $tokenId,
        ]);

        setcookie('ecoride_remember', $tokenId . ':' . $newSecret, [
            'expires'  => time() + 60*60*24*30*6,
            'path'     => '/',
            'domain'   => cookieDomain(),
            'secure'   => isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        logLogin((int)$row['user_id'], 'remember_token', true);
    } catch (Exception $e) {
        error_log("Erreur remember-token: " . $e->getMessage());
        setInvalidRememberCookie();
    }
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

    // 1) Révoquer le token "remember" de CET appareil (nouveau format id:secret)
    if (!empty($_COOKIE['ecoride_remember'])) {
        $raw = $_COOKIE['ecoride_remember'];

        if (strpos($raw, ':') !== false) {
            [$tokenId] = explode(':', $raw, 2);
            $tokenId = (int)$tokenId;

            if ($tokenId > 0) {
                try {
                    $pdo = Database::getConnection();
                    $stmt = $pdo->prepare("UPDATE user_tokens SET is_active = false WHERE token_id = :id");
                    $stmt->execute([':id' => $tokenId]);
                } catch (Exception $e) {
                    error_log("Erreur désactivation token EcoRide : " . $e->getMessage());
                }
            }
        }

        // Supprimer le cookie côté navigateur
        setInvalidRememberCookie();
    }

    // 2) Détruire la session PHP proprement
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
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
// description courte de l’appareil (pour user_tokens.device_info)
if (!function_exists('getDeviceInfo')) {
    function getDeviceInfo(): string {
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        if (preg_match('/Mobile|Android|iPhone/i', $ua)) {
            $type = 'Mobile';
        } elseif (preg_match('/Tablet|iPad/i', $ua)) {
            $type = 'Tablet';
        } else {
            $type = 'Desktop';
        }
        // On tronque pour tenir dans varchar(255)
        return $type . ' - ' . substr($ua, 0, 100);
    }
}



function createRememberToken(int $userId): void {
    try {
        $pdo = Database::getConnection();

        // Limite à 5 appareils
        limitUserTokens($userId, 4);

        // Nouveau schéma: secret + hash sha256 stocké en DB
        $secret    = rtrim(strtr(base64_encode(random_bytes(33)), '+/', '-_'), '=');
        $tokenHash = hash('sha256', $secret);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+6 months'));

        $deviceInfo = getDeviceInfo();
        $ipAdresse  = $_SERVER['REMOTE_ADDR'] ?? null;

        // INSERT et on récupère token_id
        $stmt = $pdo->prepare("
            INSERT INTO user_tokens (user_id, token_hash, expires_at, device_info, ip_address, is_active)
            VALUES (?, ?, ?, ?, ?, true)
            RETURNING token_id
        ");
        $stmt->execute([$userId, $tokenHash, $expiresAt, $deviceInfo, $ipAdresse]);
        $tokenId = (int)$stmt->fetchColumn();

        // Cookie 6 mois: tokenId:secret
        $isSecure = isHttps();
        setcookie('ecoride_remember', $tokenId . ':' . $secret, [
            'expires'  => strtotime($expiresAt),
            'path'     => '/',
            'domain'   => cookieDomain(),  // '' en local
            'secure'   => $isSecure,
            'httponly' => true,
            'samesite' => 'Lax',
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