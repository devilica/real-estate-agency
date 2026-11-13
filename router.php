<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli-server') {
    http_response_code(404);
    echo 'Page not found.';
    return;
}

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');
if (preg_match('#^/(data|includes)(/|$)#', $uri) || in_array($uri, ['/config.php', '/config.example.php', '/router.php'], true)) {
    http_response_code(403);
    echo 'Forbidden.';
    return true;
}
if (preg_match('#^/uploads/.+\.(php|phtml|phar)$#i', $uri)) {
    http_response_code(403);
    echo 'Forbidden.';
    return true;
}
if ($uri !== '/' && is_file(__DIR__ . str_replace('/', DIRECTORY_SEPARATOR, $uri))) {
    return false;
}
if ($uri === '/') {
    require __DIR__ . '/index.php';
    return true;
}
if ($uri === '/admin' || $uri === '/admin/') {
    chdir(__DIR__ . '/admin');
    require __DIR__ . '/admin/index.php';
    return true;
}

http_response_code(404);
echo 'Page not found.';
return true;
