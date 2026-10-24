<?php
define('SESSION_IDLE_MINUTES', 120);

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();

    if (isset($_SESSION['user_id'], $_SESSION['last_seen']) && time() - $_SESSION['last_seen'] > SESSION_IDLE_MINUTES * 60) {
        $_SESSION = [];
        session_regenerate_id(true);
    }
    if (isset($_SESSION['user_id'])) {
        $_SESSION['last_seen'] = time();
    }
}
?>
