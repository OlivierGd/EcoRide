<?php
function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// Vérifie si l'utilisateur est connecté / authentifié
function isAuthenticated(): bool {
      startSession();
      return !empty($_SESSION['user_id']);
}

// Retourne le user_id
function getUserId(): ?int {
    startSession();
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null ;
}

// Sécuriser les pages privées (auth. obligatoire)
function requireAuth(): void {
    if (!isAuthenticated()) {
        header('Location: login.php');
        exit;
    }
}

