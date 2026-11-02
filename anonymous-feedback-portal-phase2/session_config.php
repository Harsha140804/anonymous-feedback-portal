<?php

require_once 'security_headers.php';

// Enable strict session ID handling.
ini_set('session.use_strict_mode', '1');

// Configure session cookie security.
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax'
]);

// Start the session.
session_start();