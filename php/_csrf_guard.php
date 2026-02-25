<?php
session_start();

function csrf_require(): void
{
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
        return;
    }

    if (empty($_SESSION['csrf_token'])) {
        http_response_code(403);
        echo 'CSRF: missing server token';
        exit;
    }

    $hdr = $_SERVER['HTTP_X_XSRF_TOKEN'] ?? '';

    if (!$hdr || !hash_equals($_SESSION['csrf_token'], $hdr)) {
        http_response_code(403);
        echo 'CSRF: invalid token';
        exit;
    }
}