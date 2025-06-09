<?php
require_once __DIR__ . '/../src/config/db.php';

$stmt = $pdo->query("SELECT 'Bienvenue sur EcoRide !' AS message");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
?>

    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <title>EcoRide</title>
    </head>
    <body>
    <h1><?php echo htmlspecialchars($result['message']); ?></h1>
    </body>
    </html>
<?php
