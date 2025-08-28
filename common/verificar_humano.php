<?php
require_once '../base.php';

if (!isset($_COOKIE['user_id']) || !isset($_COOKIE['username']) || !isset($_COOKIE['role'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

setcookie('human_verified', '1', [
    'path' => '/',
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Strict'
]);

http_response_code(200);
echo json_encode(['verified' => true]);
