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
            // Charge .env en local, ignore en prod
            $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
            $dotenv->safeLoad();

            // Récupère l'URL complète, en dev via .env, en prod via Fly Secrets
            $dsn = getenv('DATABASE_URL');
            if (!$dsn) {
                die('Erreur critique : DATABASE_URL non défini en environnement.');
            }

            try {
                self::$pdo = new PDO(
                    $dsn,
                    null,
                    null,
                    [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    ]
                );
            } catch (PDOException $e) {
                die('Erreur de connexion à la BDD : ' . $e->getMessage());
            }
        }

        return self::$pdo;
    }
}