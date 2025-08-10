<?php

namespace Olivierguissard\EcoRide\Config;

require_once __DIR__ . '/../../vendor/autoload.php';

use PDO;
use PDOException;
use Dotenv\Dotenv;
class Database
{
    private static ?PDO $pdo = null;
    public static function getConnection(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        // 1) Charge le .env (local) / ignore si absent (prod)
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
        $dotenv->safeLoad();

        // 2) Détection de l'environnement
        $environment = $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?? 'production';
        $databaseUrl = $_ENV['DATABASE_URL'] ?? getenv('DATABASE_URL');

        if (!empty($databaseUrl)) {
            // === MODE avec DATABASE_URL (Supabase/Production) ===
            $modeLabel = ($environment === 'development') ? 'DÉVELOPPEMENT (Supabase)' : 'PRODUCTION';
            error_log("Mode $modeLabel détecté (DATABASE_URL présent)");

            $parts = parse_url($databaseUrl);
            if ($parts === false) {
                die('Erreur critique : impossible de parser DATABASE_URL.');
            }

            $host = $parts['host'] ?? 'localhost';
            $port = $parts['port'] ?? 5432;
            $db   = ltrim($parts['path'] ?? '', '/');
            $user = $parts['user'] ?? null;
            $pass = $parts['pass'] ?? null;

            $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', $host, $port, $db);

            // Log pour debug (sans exposer le mot de passe)
            error_log("Connexion via DATABASE_URL: host=$host, port=$port, db=$db, user=$user");

        } else {
            // === MODE DÉVELOPPEMENT avec variables séparées ===
            error_log("Mode DÉVELOPPEMENT détecté (variables séparées)");

            $host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?? 'localhost';
            $port = $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?? 5432;
            $db   = $_ENV['DB_NAME'] ?? getenv('DB_NAME');
            $user = $_ENV['DB_USER'] ?? getenv('DB_USER');
            $pass = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD');

            // Validation des variables obligatoires
            if (empty($db)) {
                die('Erreur critique : DB_NAME non défini en mode développement.');
            }
            if (empty($user)) {
                die('Erreur critique : DB_USER non défini en mode développement.');
            }

            $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', $host, $port, $db);

            // Log pour debug
            error_log("Connexion DB dev: host=$host, port=$port, db=$db, user=$user");
        }

        // 3) Connexion PDO
        try {
            self::$pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);

            error_log("Connexion BDD réussie !");

        } catch (PDOException $e) {
            error_log("Erreur PDO: " . $e->getMessage());
            die('Erreur de connexion à la BDD : ' . $e->getMessage());
        }

        return self::$pdo;
    }
}