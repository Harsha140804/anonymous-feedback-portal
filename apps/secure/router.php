<?php
declare(strict_types=1);

// PHP development-server router. Apache uses .htaccess for static responses.
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = rawurldecode($path);

if (str_contains($path, "\0") || str_contains($path, '..') || preg_match('~/(?:\\.|[^/]+\\.(?:sql|log|ini|bak))~i', $path)) {
    require_once __DIR__ . '/security_headers.php';
    http_response_code(403);
    exit('Forbidden.');
}

if ($path === '/' || str_ends_with(strtolower($path), '.php')) {
    return false;
}

$fullPath = realpath(__DIR__ . $path);
if ($fullPath !== false &&
    str_starts_with($fullPath, __DIR__ . DIRECTORY_SEPARATOR) &&
    is_file($fullPath) &&
    strtolower(pathinfo($fullPath, PATHINFO_EXTENSION)) === 'css'
) {
    require_once __DIR__ . '/security_headers.php';
    header('Content-Type: text/css; charset=UTF-8');
    readfile($fullPath);
    return true;
}

require_once __DIR__ . '/security_headers.php';
http_response_code(404);
header('Content-Type: text/plain; charset=UTF-8');
echo 'Not found.';
return true;
