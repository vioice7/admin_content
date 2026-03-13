<?php

/**
 * Database configuration.
 *
 * #3 / blocker FIX: credentials are now read from environment variables so
 * this file never contains secrets and is safe to commit.
 *
 * Set in your server environment, .env loader, or php-fpm pool config:
 *   DB_HOST   (default: localhost)
 *   DB_NAME   (default: tecaim)
 *   DB_USER   — required, no default
 *   DB_PASS   — required, no default
 *
 * For local development you can still use a .env file loaded by a library
 * such as vlucas/phpdotenv, or export the vars in your shell before running
 * the built-in server:
 *   export DB_USER=root DB_PASS='' && php -S localhost:8000 -t public
 */

return [
    'host'     => getenv('DB_HOST') ?: 'localhost',
    'dbname'   => getenv('DB_NAME') ?: 'tecaim',
    'user'     => getenv('DB_USER'),   // no fallback — must be set explicitly
    'password' => getenv('DB_PASS'),   // no fallback — must be set explicitly
];
