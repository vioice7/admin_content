<?php

namespace App\Core;

class Security
{
    private const CSRF_TOKEN_KEY = 'csrf_token';
    private const CSRF_TOKEN_LENGTH = 32;
    private const SESSION_TIMEOUT = 1800; // 30 minutes
    private const MAX_LOGIN_ATTEMPTS_IP = 10;
    private const MAX_LOGIN_ATTEMPTS_USER = 5;
    private const LOGIN_ATTEMPT_WINDOW = 900; // 15 minutes

    /**
     * Configure secure session settings
     */
    public static function configureSession(): void
    {
        // Set secure session configuration
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.use_strict_mode', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.gc_maxlifetime', self::SESSION_TIMEOUT);

        // Set session cookie parameters
        session_set_cookie_params([
            'lifetime' => 0, // Session cookie
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
    }

    /**
     * Start secure session
     */
    public static function startSession(): void
    {
        self::configureSession();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Check session timeout
        if (isset($_SESSION['last_activity']) &&
            (time() - $_SESSION['last_activity']) > self::SESSION_TIMEOUT) {
            self::destroySession();
            return;
        }

        $_SESSION['last_activity'] = time();
    }

    /**
     * Regenerate session ID securely
     */
    public static function regenerateSession(): void
    {
        session_regenerate_id(true);
    }

    /**
     * Destroy session and cleanup cookies
     */
    public static function destroySession(): void
    {
        // Clear session data
        $_SESSION = [];

        // Delete session cookie
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }

    /**
     * Generate CSRF token
     */
    public static function generateCsrfToken(): string
    {
        if (!isset($_SESSION[self::CSRF_TOKEN_KEY])) {
            $_SESSION[self::CSRF_TOKEN_KEY] = bin2hex(random_bytes(self::CSRF_TOKEN_LENGTH));
        }
        return $_SESSION[self::CSRF_TOKEN_KEY];
    }

    /**
     * Validate CSRF token
     */
    public static function validateCsrfToken(string $token): bool
    {
        if (!isset($_SESSION[self::CSRF_TOKEN_KEY])) {
            return false;
        }
        return hash_equals($_SESSION[self::CSRF_TOKEN_KEY], $token);
    }

    /**
     * Regenerate CSRF token
     */
    public static function regenerateCsrfToken(): void
    {
        unset($_SESSION[self::CSRF_TOKEN_KEY]);
        self::generateCsrfToken();
    }

    /**
     * Get CSRF token input field HTML
     */
    public static function getCsrfTokenField(): string
    {
        $token = self::generateCsrfToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }

    /**
     * Validate password strength
     */
    public static function validatePasswordStrength(string $password): array
    {
        $errors = [];

        if (strlen($password) < 12) {
            $errors[] = 'Password must be at least 12 characters long';
        }

        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }

        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }

        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }

        return $errors;
    }

    /**
     * Hash password using Argon2ID
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2ID);
    }

    /**
     * Verify password
     */
    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Check login rate limiting
     */
    public static function checkLoginRateLimit(string $identifier, string $type = 'ip'): bool
    {
        $key = "login_attempts_{$type}_{$identifier}";
        $timeKey = "login_time_{$type}_{$identifier}";

        $maxAttempts = $type === 'ip' ? self::MAX_LOGIN_ATTEMPTS_IP : self::MAX_LOGIN_ATTEMPTS_USER;

        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = 0;
            $_SESSION[$timeKey] = time();
        }

        // Reset counter if window has passed
        if ((time() - $_SESSION[$timeKey]) > self::LOGIN_ATTEMPT_WINDOW) {
            $_SESSION[$key] = 0;
            $_SESSION[$timeKey] = time();
        }

        if ($_SESSION[$key] >= $maxAttempts) {
            return false; // Rate limited
        }

        return true;
    }

    /**
     * Record login attempt
     */
    public static function recordLoginAttempt(string $identifier, string $type = 'ip'): void
    {
        $key = "login_attempts_{$type}_{$identifier}";
        $timeKey = "login_time_{$type}_{$identifier}";

        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = 0;
            $_SESSION[$timeKey] = time();
        }

        $_SESSION[$key]++;
    }

    /**
     * Reset login attempts on successful login
     */
    public static function resetLoginAttempts(string $identifier, string $type = 'ip'): void
    {
        $key = "login_attempts_{$type}_{$identifier}";
        $timeKey = "login_time_{$type}_{$identifier}";

        unset($_SESSION[$key], $_SESSION[$timeKey]);
    }

    /**
     * Get client IP address
     */
    public static function getClientIP(): string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',  // Standard proxy
            'HTTP_X_REAL_IP',        // Nginx
            'REMOTE_ADDR'            // Direct connection
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];

                // Handle comma-separated IPs (take first one)
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }

                // Validate IP
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    /**
     * Sanitize input string
     */
    public static function sanitizeString(string $input): string
    {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Validate email
     */
    public static function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate URL
     */
    public static function validateUrl(string $url): bool
    {
        $url = filter_var($url, FILTER_SANITIZE_URL);
        return filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED | FILTER_FLAG_HOST_REQUIRED) !== false;
    }

    /**
     * Validate filename for uploads
     */
    public static function validateFilename(string $filename): bool
    {
        // Check length
        if (strlen($filename) > 255) {
            return false;
        }

        // Check for path traversal
        if (strpos($filename, '..') !== false || strpos($filename, '/') !== false || strpos($filename, '\\') !== false) {
            return false;
        }

        // Check allowed extensions (customize as needed)
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        return in_array($extension, $allowedExtensions);
    }

    /**
     * Generate random filename
     */
    public static function generateRandomFilename(string $originalFilename): string
    {
        $extension = pathinfo($originalFilename, PATHINFO_EXTENSION);
        return bin2hex(random_bytes(16)) . '.' . $extension;
    }

    /**
     * Set security headers
     */
    public static function setSecurityHeaders(): void
    {
        // Prevent clickjacking
        header('X-Frame-Options: DENY');

        // Prevent MIME type sniffing
        header('X-Content-Type-Options: nosniff');

        // Referrer policy
        header('Referrer-Policy: strict-origin-when-cross-origin');

        // Disable XSS protection (rely on CSP)
        header('X-XSS-Protection: 0');

        // HSTS (only for HTTPS)
        if (isset($_SERVER['HTTPS'])) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }

        // Content Security Policy
        $csp = "default-src 'self'; ";
        $csp .= "script-src 'self'; ";
        $csp .= "style-src 'self' 'unsafe-inline'; "; // Allow inline styles for simplicity
        $csp .= "img-src 'self' data: https:; "; // Allow data URIs and HTTPS images
        $csp .= "font-src 'self'; ";
        $csp .= "connect-src 'self'; ";
        $csp .= "media-src 'none'; ";
        $csp .= "object-src 'none'; ";
        $csp .= "frame-src 'none';";

        header('Content-Security-Policy: ' . $csp);
    }

    /**
     * Log security event
     */
    public static function logSecurityEvent(string $event, array $data = []): void
    {
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $event,
            'ip' => self::getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'data' => $data
        ];

        // In production, write to secure log file
        error_log('SECURITY: ' . json_encode($logData));
    }
}