<?php
require_once dirname(__DIR__) . '/vendor/autoload.php';

use Dotenv\Dotenv;
// Charge le fichier .env
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();
$dotenv->required(['DB_USER', 'DB_PASSWORD'])->notEmpty();

$pdo = new PDO("pgsql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']};user={$_ENV['DB_USER']};password={$_ENV['DB_PASSWORD']}");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Permet de retourner l'erreur si problème
$error = null;
try {
    $query = $pdo->query('SELECT * FROM vehicule');
    $vehicules = $query->fetchAll(PDO::FETCH_OBJ);
} catch (PDOException $e) {
    $error = $e->getMessage();
}

?>

<h1>Mes véhicules</h1>
<?php if ($error) : ?>
    <div class="alert alert-danger"><?= $error ?></div>
<?php else : ?>
<ul>
    <?php foreach ($vehicules as $vehicule) : ?>
        <li><?= $vehicule->marque ?></li>
    <?php endforeach; ?>
</ul>


<?php endif; ?>
<?php require_once 'footer.php'; ?>