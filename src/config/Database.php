<?php

namespace Olivierguissard\EcoRide\Config;

use PDO;
use PDOException;
use Dotenv;

class Database
{
    private static ?PDO $pdo = null;

    public static function getConnection(): PDO
    {
        if (self::$pdo === null) {
            // Charge les variables d'environnement
            $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
            $dotenv->safeLoad(); // safeLoad() à la place de load() permet de charger le fichier.env en local et passer outre en prod.

            // Récupère la connexion complète
            $databaseUrl = getenv('DATABASE_URL');
            $pdo = new PDO($databaseUrl, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,]);

            $host = $_ENV['DB_HOST'];
            $port = $_ENV['DB_PORT'];
            $dbname = $_ENV['DB_NAME'];
            $user = $_ENV['DB_USER'];
            $password = $_ENV['DB_PASSWORD'];

            try {
                self::$pdo = new PDO("pgsql:host={$host};dbname={$dbname}", $user, $password);
                self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                die("Erreur de connexion à la BDD : " . $e->getMessage());
            }
        }
        return self::$pdo;
    }
}