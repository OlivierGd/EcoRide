<?php
// Inclure les bibliothèques installées avec Composer
require_once __DIR__ . '/../../vendor/autoload.php';

//Crée une instance de classe Dotenv pour lire un fichier .env
// CreateImmutable() : plus sécurisé que create(), empeche de réécrire accidentellement
// des variables d'environnement existantes dans $_ENV
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
// Charge les variables définies dans .env
$dotenv->load();

$host = ['DB_HOST'];
$port = ['DB_PORT'];
$dbname = ['DB_NAME'];
$user = ['DB_USER'];
$password = ['DB_PASSWORD'];

try {
    $pdo = new PDO ("pgsql:host=$host;port=$port;dbname=$dbname, $user, $password");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // echo "Connexion reussie";
} catch (PDOException $e) {
    die("Erreur : " . $e->getMessage());
}