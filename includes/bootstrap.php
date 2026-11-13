<?php

declare(strict_types=1);

define('ROOT', dirname(__DIR__));

if (!function_exists('array_is_list')) {
    function array_is_list(array $array): bool
    {
        $i = 0;
        foreach ($array as $k => $_) {
            if ($k !== $i++) {
                return false;
            }
        }
        return true;
    }
}

set_exception_handler(static function (Throwable $e): void {
    if (!headers_sent()) {
        http_response_code(500);
    }
    $debug = false;
    try {
        $debug = (bool) (config()['debug'] ?? false);
    } catch (Throwable) {
    }
    $text = ($e instanceof RuntimeException || $debug)
        ? $e->getMessage()
        : 'Something went wrong. Please try again.';
    echo '<!DOCTYPE html><html lang="en"><meta charset="utf-8"><title>Error</title><body><h1>Error</h1><p>'
        . htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
        . '</p></body></html>';
});

function config(): array
{
    static $cfg = null;
    if ($cfg !== null) {
        return $cfg;
    }
    $path = ROOT . '/config.php';
    if (!is_file($path)) {
        throw new RuntimeException('config.php is missing. Copy config.example.php to config.php and enter the password hash.');
    }
    $loaded = require $path;
    if (!is_array($loaded)) {
        throw new RuntimeException('config.php must return an array of settings.');
    }
    $cfg = $loaded;
    return $cfg;
}

config();
date_default_timezone_set('Europe/Sarajevo');

error_reporting(E_ALL);
if (PHP_SAPI !== 'cli') {
    ini_set('display_errors', '0');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' blob:; style-src 'self'; script-src 'self'; form-action 'self'; base-uri 'self'; object-src 'none'; frame-ancestors 'self'");
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/data.php';
require_once __DIR__ . '/images.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/listing.php';
require_once __DIR__ . '/mail.php';
