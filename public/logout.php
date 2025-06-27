<?php
session_start();
session_unset(); // Libère les variables de session
session_destroy(); // Detruit la session

// Detruit le cookie de session
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

header('Location: /index.php');
exit;
