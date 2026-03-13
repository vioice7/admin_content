<?php

/**
 * setup.php - One-time database initialisation script
 * Run from project root: php setup.php
 *
 * #2 FIX: block web access entirely. If this file is ever reachable via HTTP
 * (e.g. misconfigured webroot pointing to project root instead of public/)
 * a visitor could re-seed the database and recreate the admin account.
 */
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit;
}

const LINE = PHP_EOL;

function log_step(string $message): void
{
    echo '  → ' . $message . LINE;
}

function log_section(string $title): void
{
    echo LINE . '[ ' . $title . ' ]' . LINE;
}

function abort(string $message): never
{
    echo LINE . '  ✗ ERROR: ' . $message . LINE . LINE;
    exit(1);
}

// ─── Load config ────────────────────────────────────────────────────────────

$configPath = __DIR__ . '/config/database.php';

if (!file_exists($configPath)) {
    abort('config/database.php not found. Please create it before running setup.');
}

$config = require $configPath;

$host     = $config['host']     ?? 'localhost';
$dbname   = $config['dbname']   ?? null;
$user     = $config['user']     ?? 'root';
$password = $config['password'] ?? '';

if (!$dbname) {
    abort('No database name set in config/database.php.');
}

// ─── Create database if it doesn't exist ────────────────────────────────────

log_section('Database');

try {
    $pdo = new PDO("mysql:host={$host}", $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    log_step("Database `{$dbname}` ready.");

    $pdo->exec("USE `{$dbname}`");
} catch (PDOException $e) {
    abort('Could not connect to MySQL: ' . $e->getMessage());
}

// ─── Create tables ───────────────────────────────────────────────────────────

log_section('Migrations');

$pdo->exec("
    CREATE TABLE IF NOT EXISTS users (
        id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name       VARCHAR(100)        NOT NULL,
        email      VARCHAR(100) UNIQUE NOT NULL,
        password   VARCHAR(255)        NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
log_step('Table `users` OK.');

$pdo->exec("
    CREATE TABLE IF NOT EXISTS posts (
        id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        author_id INT UNSIGNED NOT NULL,
        title     VARCHAR(255) NOT NULL,
        content   TEXT         NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
log_step('Table `posts` OK.');

// ─── Rate limiting table ─────────────────────────────────────────────────────

$pdo->exec("
    CREATE TABLE IF NOT EXISTS login_attempts (
        id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        identifier VARCHAR(255) NOT NULL,
        type       ENUM('ip', 'user') NOT NULL,
        attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_identifier_type (identifier, type),
        INDEX idx_attempted_at (attempted_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
log_step('Table `login_attempts` OK.');

// ─── Seed default admin ──────────────────────────────────────────────────────

log_section('Admin User');

$check = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$check->execute(['admin@cms.com']);

if ($check->fetch()) {
    log_step('Admin user already exists, skipping.');
} else {
    $hash = password_hash('password', PASSWORD_ARGON2ID);
    $stmt = $pdo->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)');
    $stmt->execute(['Admin', 'admin@cms.com', $hash]);
    log_step('Admin user created (admin@cms.com / password).');
}

// ─── Done ────────────────────────────────────────────────────────────────────

echo LINE . '  ✓ Setup complete. Start your server with:' . LINE;
echo '    php -S localhost:8000 -t public' . LINE . LINE;
