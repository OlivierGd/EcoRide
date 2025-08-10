<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Olivierguissard\EcoRide\Config\Database;

require_once 'functions/auth.php';
startSession();
requireAuth();
updateActivity();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = getUserId() ?? null;
    $amount = intval($_POST['creditAmount'] ?? 0);

    if ($userId && $amount >= 5 && $amount % 5 === 0 && $amount <= 500) {
        $success = \Olivierguissard\EcoRide\Service\CreditService::addCredits($userId, $amount, 'achat');
        if ($success) {
            $_SESSION['flash_success'] = 'Crédits ajoutés avec succès.';
            header('Location: paiements.php?success=1');
        } else {
            $_SESSION['flash_error'] = 'Une erreur est survenue lors de l\'ajout de crédit.';
            header('Location: paiements.php?error=1');
        }
    } else {
        $_SESSION['flash_error'] = 'Montant invalide (minimum 5, maximum 500, multiple de 5).';
        header('Location: paiements.php?invalid=1');
    }
    exit;
}
header('Location: paiements.php');
exit;
