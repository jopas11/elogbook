<?php
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../config/database.php';

class AuthController {
    public static function logout() {
        if (isset($_SESSION['user'])) {
            logAudit('logout', 'Logout: ' . $_SESSION['user']['email']);
        }

        $_SESSION = [];
        session_destroy();

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }

        redirect('login.php');
    }
}
