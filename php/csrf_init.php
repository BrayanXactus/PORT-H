<?php

session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$token = $_SESSION['csrf_token'];

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (!empty($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);

setcookie('XSRF-TOKEN', $token, [
    'expires'  => 0,
    'path'     => '/',
    'secure'   => $isHttps,
    'httponly' => false,
    'samesite' => 'Lax',
]);

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['ok' => true]);