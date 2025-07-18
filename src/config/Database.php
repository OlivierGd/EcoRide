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

        // 2) Détermine si on est en local (variables DB_*) ou en prod (DATABASE_URL)
        $dbHost = $_ENV['DB_HOST'] ?? getenv('DB_HOST');
        if ($dbHost) {
            // Mode local via variables distinctes
            $host = $dbHost;
            $port = $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?? 5432;
            $db   = $_ENV['DB_NAME'] ?? getenv('DB_NAME');
            $user = $_ENV['DB_USER'] ?? getenv('DB_USER');
            $pass = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD');

            if (!$db) {
                die('Erreur critique : DB_NAME non défini.');
            }

            $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', $host, $port, $db);
        } else {
            // Mode production via DATABASE_URL
            $rawUrl = $_ENV['DATABASE_URL'] ?? getenv('DATABASE_URL');
            if (!$rawUrl) {
                die('Erreur critique : DATABASE_URL non défini.');
            }

            $parts = parse_url($rawUrl);
            if ($parts === false) {
                die('Erreur critique : impossible de parser DATABASE_URL.');
            }

            $host = $parts['host'] ?? 'localhost';
            $port = $parts['port'] ?? 5432;
            $db   = ltrim($parts['path'] ?? '', '/');
            $user = $parts['user'] ?? null;
            $pass = $parts['pass'] ?? null;

            $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', $host, $port, $db);
        }

        // 3) Instanciation PDO
        try {
            self::$pdo = new PDO(
                $dsn,
                $user,
                $pass,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // lance les exceptions en cas d'erreur SQL
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Récupère les résultats dans un tableau associatif
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (PDOException $e) {
            die('Erreur de connexion à la BDD : ' . $e->getMessage());
        }
        return self::$pdo;
    }

}
