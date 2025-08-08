<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Olivierguissard\EcoRide\Config\Database;


require_once __DIR__ . '/functions/auth.php';
requireAuth();
updateActivity();


// Connexion à la BDD
$pdo = Database::getConnection();

// Recupere les données de la modale
$firstName  = $_POST['firstName'];
$lastName   = $_POST['lastName'];
$email      = $_POST['email'];
$profilePicture = $_SESSION['profilePicture'] ?? '';
$user_id    = $_SESSION['user_id'];

// Vérifie si les champs sont complétés :
if (empty($firstName) || empty($lastName) || empty($email)) {
    $error = 'Veuillez remplir tous les champs';
    echo $error;
    exit;
}

// Vérifie si l'email existe déjà dans la BDD
$stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ? AND user_id != ?');
$stmt->execute([$email, $user_id]);
$emailExist = $stmt->fetchColumn();
if ($emailExist > 0) {
    echo "L'email est déjà utilisé. Veuillez en choisir un autre.";
    exit;
}
// Mise à jour de la BDD
$sql = "UPDATE users SET firstname = ?, lastname = ?, email = ?, profile_picture = ? WHERE user_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$firstName, $lastName, $email, $profilePicture, $user_id]);

// Gerer la photo de profil
$profilePicture = $_SESSION['profilePicture']; // Recupere la photo existante.
if (!empty($_FILES['profilePicture']['name'])) {
    // Nouveau fichier
    $profilePicture = 'uploads/' . basename($_FILES['profilePicture']['name']);
    move_uploaded_file($_FILES['profilePicture']['tmp_name'], $profilePicture);
}

// Mettre à jour les données dans la session
$_SESSION['firstName'] = $firstName;
$_SESSION['lastName'] = $lastName;
$_SESSION['email'] = $email;
$_SESSION['profilePicture'] = $profilePicture;
$_SESSION['success_update'] = 'Vos données ont été mises à jour !';

header('Location: profil.php');
exit;