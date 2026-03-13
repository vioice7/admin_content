<?php

/**
 * router.php — PHP built-in server router script
 *
 * Usage: php -S localhost:8000 -t public public/router.php
 *
 * The built-in server serves real files (css, images, js) directly.
 * Anything else is forwarded to index.php so the app router handles it.
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Serve real static files (css, js, images, fonts, etc.) directly
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    return false;
}

// Everything else goes through the app
require __DIR__ . '/index.php';
