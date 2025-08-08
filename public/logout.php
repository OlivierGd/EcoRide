<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once 'functions/auth.php';

startSession();

// Déconnexion complètr
logoutUser();

// Redirection vers la page de connexion

header('Location: login.php');
exit;
