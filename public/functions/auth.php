<?php
function est_connecte(): bool { // vérifie si la session est active sinon active la session
      if (session_status() === PHP_SESSION_NONE) {
       session_start();
    }
   return !empty($_SESSION['connecte']);
}

function utilisateur_connecte(): void {
    if (!est_connecte()) {
        header('Location: /public/login.php');
        exit;
    }
}