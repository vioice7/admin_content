<?php

namespace App\Core;

class Security
{
    private const CSRF_TOKEN_KEY        = 'csrf_token';
    private const CSRF_TOKEN_LENGTH     = 32;
    private const SESSION_TIMEOUT       = 1800; // 30 minutes
    private const MAX_LOGIN_ATTEMPTS_IP = 10;
    private const MAX_LOGIN_ATTEMPTS_USER = 5;
    private const LOGIN_ATTEMPT_WINDOW  = 900; // 15 minutes

    /**
     * #5 FIX: Only trust forwarded headers when REMOTE_ADDR is a known proxy.
     * Add your load-balancer / CDN IPs or CIDR ranges here.
     */
    private const TRUSTED_PROXIES = [
        '127.0.0.1',
        '::1',
        // Add your actual proxy IPs, e.g. '10.0.0.0/8', '172.16.0.0/12'
    ];

    // ─── Session ─────────────────────────────────────────────────────────────

    public static function configureSession(): void
    {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.use_strict_mode', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.gc_maxlifetime', self::SESSION_TIMEOUT);

        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
    }

    public static function startSession(): void
    {
        self::configureSession();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION['last_activity']) &&
            (time() - $_SESSION['last_activity']) > self::SESSION_TIMEOUT) {
            self::destroySession();
            return;
        }

        $_SESSION['last_activity'] = time();
    }

    public static function regenerateSession(): void
    {
        session_regenerate_id(true);
    }

    public static function destroySession(): void
    {
        $_SESSION = [];

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

    // ─── CSRF ─────────────────────────────────────────────────────────────────

    public static function generateCsrfToken(): string
    {
        if (!isset($_SESSION[self::CSRF_TOKEN_KEY])) {
            $_SESSION[self::CSRF_TOKEN_KEY] = bin2hex(random_bytes(self::CSRF_TOKEN_LENGTH));
        }
        return $_SESSION[self::CSRF_TOKEN_KEY];
    }

    public static function validateCsrfToken(string $token): bool
    {
        if (!isset($_SESSION[self::CSRF_TOKEN_KEY])) {
            return false;
        }
        return hash_equals($_SESSION[self::CSRF_TOKEN_KEY], $token);
    }

    public static function regenerateCsrfToken(): void
    {
        unset($_SESSION[self::CSRF_TOKEN_KEY]);
        self::generateCsrfToken();
    }

    public static function getCsrfTokenField(): string
    {
        $token = self::generateCsrfToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }

    // ─── Password ─────────────────────────────────────────────────────────────

    /**
     * #10 FIX: Added special character requirement.
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
        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            $errors[] = 'Password must contain at least one special character';
        }

        return $errors;
    }

    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2ID);
    }

    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    // ─── Rate limiting ────────────────────────────────────────────────────────

    /**
     * #7 FIX: Validate $type before use.
     * #12 FIX: Prune stale rows inline to keep the table bounded.
     */
    public static function checkLoginRateLimit(\PDO $pdo, string $identifier, string $type = 'ip'): bool
    {
        self::validateRateLimitType($type); // #7

        // #12: prune rows older than 1 day while we're here
        $pdo->prepare(
            'DELETE FROM login_attempts WHERE attempted_at < NOW() - INTERVAL 1 DAY'
        )->execute();

        $maxAttempts = $type === 'ip' ? self::MAX_LOGIN_ATTEMPTS_IP : self::MAX_LOGIN_ATTEMPTS_USER;
        $windowStart = date('Y-m-d H:i:s', time() - self::LOGIN_ATTEMPT_WINDOW);

        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM login_attempts
             WHERE identifier = ? AND type = ? AND attempted_at >= ?'
        );
        $stmt->execute([$identifier, $type, $windowStart]);

        return (int) $stmt->fetchColumn() < $maxAttempts;
    }

    public static function recordLoginAttempt(\PDO $pdo, string $identifier, string $type = 'ip'): void
    {
        self::validateRateLimitType($type); // #7

        $stmt = $pdo->prepare(
            'INSERT INTO login_attempts (identifier, type) VALUES (?, ?)'
        );
        $stmt->execute([$identifier, $type]);
    }

    public static function resetLoginAttempts(\PDO $pdo, string $identifier, string $type = 'ip'): void
    {
        self::validateRateLimitType($type); // #7

        $stmt = $pdo->prepare(
            'DELETE FROM login_attempts WHERE identifier = ? AND type = ?'
        );
        $stmt->execute([$identifier, $type]);
    }

    /**
     * #7: Throw early on invalid type rather than silently storing garbage.
     */
    private static function validateRateLimitType(string $type): void
    {
        if (!in_array($type, ['ip', 'user'], true)) {
            throw new \InvalidArgumentException("Invalid rate-limit type: '{$type}'. Must be 'ip' or 'user'.");
        }
    }

    // ─── IP detection ─────────────────────────────────────────────────────────

    /**
     * #5 FIX: Only trust X-Forwarded-For / CF-Connecting-IP when REMOTE_ADDR
     * is a known proxy. Falls back to REMOTE_ADDR for direct connections.
     */
    public static function getClientIP(): string
    {
        $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        if (self::isTrustedProxy($remoteAddr)) {
            // CF-Connecting-IP is always a single IP, prefer it when behind Cloudflare
            if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
                $ip = trim($_SERVER['HTTP_CF_CONNECTING_IP']);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }

            // X-Forwarded-For is a comma-separated list; take the first public IP
            if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                foreach (explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']) as $candidate) {
                    $candidate = trim($candidate);
                    if (filter_var($candidate, FILTER_VALIDATE_IP,
                        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                        return $candidate;
                    }
                }
            }

            if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
                $ip = trim($_SERVER['HTTP_X_REAL_IP']);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return $remoteAddr;
    }

    /**
     * Check whether a given IP is in the TRUSTED_PROXIES list.
     * Supports exact matches and CIDR notation (e.g. 10.0.0.0/8).
     */
    private static function isTrustedProxy(string $ip): bool
    {
        foreach (self::TRUSTED_PROXIES as $proxy) {
            if (strpos($proxy, '/') !== false) {
                if (self::ipInCidr($ip, $proxy)) {
                    return true;
                }
            } elseif ($ip === $proxy) {
                return true;
            }
        }
        return false;
    }

    private static function ipInCidr(string $ip, string $cidr): bool
    {
        [$subnet, $bits] = explode('/', $cidr);
        $bits    = (int) $bits;
        $ipLong  = ip2long($ip);
        $netLong = ip2long($subnet);
        if ($ipLong === false || $netLong === false) {
            return false;
        }
        $mask = $bits === 0 ? 0 : (~0 << (32 - $bits));
        return ($ipLong & $mask) === ($netLong & $mask);
    }

    // ─── Input / output ───────────────────────────────────────────────────────

    public static function sanitizeString(string $input): string
    {
        return trim($input);
    }

    public static function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function validateUrl(string $url): bool
    {
        $url = filter_var($url, FILTER_SANITIZE_URL);
        return filter_var($url, FILTER_VALIDATE_URL,
            FILTER_FLAG_SCHEME_REQUIRED | FILTER_FLAG_HOST_REQUIRED) !== false;
    }

    public static function validateFilename(string $filename): bool
    {
        if (strlen($filename) > 255) {
            return false;
        }
        if (strpos($filename, '..') !== false
            || strpos($filename, '/') !== false
            || strpos($filename, '\\') !== false) {
            return false;
        }
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($extension, $allowedExtensions);
    }

    public static function generateRandomFilename(string $originalFilename): string
    {
        $extension = pathinfo($originalFilename, PATHINFO_EXTENSION);
        return bin2hex(random_bytes(16)) . '.' . $extension;
    }

    // ─── Headers ──────────────────────────────────────────────────────────────

    public static function setSecurityHeaders(): void
    {
        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('X-XSS-Protection: 0');
        header('Content-Type: text/html; charset=utf-8');

        if (isset($_SERVER['HTTPS'])) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }

        $csp  = "default-src 'self'; ";
        $csp .= "script-src 'self'; ";
        $csp .= "style-src 'self' 'unsafe-inline'; ";
        $csp .= "img-src 'self' data: https:; ";
        $csp .= "font-src 'self'; ";
        $csp .= "connect-src 'self'; ";
        $csp .= "media-src 'none'; ";
        $csp .= "object-src 'none'; ";
        $csp .= "frame-src 'none';";

        header('Content-Security-Policy: ' . $csp);
    }

    // ─── Logging ──────────────────────────────────────────────────────────────

    public static function logSecurityEvent(string $event, array $data = []): void
    {
        $logData = [
            'timestamp'  => date('Y-m-d H:i:s'),
            'event'      => $event,
            'ip'         => self::getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'data'       => $data,
        ];

        error_log('SECURITY: ' . json_encode($logData));
    }
}
