<?php

declare(strict_types=1);

$root = dirname(__DIR__);

if (is_readable($root . '/.env')) {
    foreach (file($root . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        putenv("$key=$value");
        $_ENV[$key] = $value;
    }
}

$appConfig = require $root . '/config/app.php';
$dbConfig = require $root . '/config/database.php';

session_name($appConfig['session_name']);
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => $appConfig['session_lifetime'],
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/auth.php';