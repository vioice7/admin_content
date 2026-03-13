<?php

namespace App\Services;

use App\Models\User;
use App\Core\Database;

/**
 * Authentication Service
 */
class AuthService
{
    private User $userModel;
    private Database $db;
    private const SESSION_KEY = '_cms_uid'; // #11: non-guessable session key

    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->userModel = new User($db);
    }

    /**
     * Register a new user.
     * #blocker: CLI / internal use only — not exposed via any public route.
     * Call this only from setup.php or a future admin-only endpoint with its
     * own auth + CSRF gate.
     */
    public function register(string $name, string $email, string $password): bool
    {
        if ($this->userModel->findByEmail($email)) {
            return false;
        }

        return $this->userModel->create($name, $email, $password);
    }

    /**
     * Login a user.
     * Fix #1 : regenerate session BEFORE writing the user ID so the new
     *           session ID is the one that carries the authenticated identity.
     * Fix #8 : set a session flag on rate-limit so the controller can show
     *           a "please wait" message without leaking the limit threshold.
     */
    public function login(string $email, string $password): bool
    {
        $email    = \App\Core\Security::sanitizeString($email);
        $clientIP = \App\Core\Security::getClientIP();
        $pdo      = $this->db->getConnection();

        // Check IP rate limit
        if (!\App\Core\Security::checkLoginRateLimit($pdo, $clientIP, 'ip')) {
            \App\Core\Security::logSecurityEvent('rate_limit_exceeded', ['type' => 'ip', 'identifier' => $clientIP]);
            $_SESSION['login_rate_limited'] = true; // #8
            return false;
        }

        $user = $this->userModel->findByEmail($email);

        if (!$user) {
            \App\Core\Security::recordLoginAttempt($pdo, $clientIP, 'ip');
            \App\Core\Security::logSecurityEvent('login_failed', ['reason' => 'user_not_found', 'email' => $email]);
            return false;
        }

        // Check per-user rate limit
        if (!\App\Core\Security::checkLoginRateLimit($pdo, $user['email'], 'user')) {
            \App\Core\Security::logSecurityEvent('rate_limit_exceeded', ['type' => 'user', 'identifier' => $user['email']]);
            $_SESSION['login_rate_limited'] = true; // #8
            return false;
        }

        if (!$this->userModel->verifyPassword($password, $user['password'])) {
            \App\Core\Security::recordLoginAttempt($pdo, $clientIP, 'ip');
            \App\Core\Security::recordLoginAttempt($pdo, $user['email'], 'user');
            \App\Core\Security::logSecurityEvent('login_failed', ['reason' => 'invalid_password', 'user_id' => $user['id']]);
            return false;
        }

        // Successful login
        \App\Core\Security::resetLoginAttempts($pdo, $clientIP, 'ip');
        \App\Core\Security::resetLoginAttempts($pdo, $user['email'], 'user');

        // #1 FIX: regenerate first, THEN write identity into the new session.
        \App\Core\Security::regenerateSession();
        \App\Core\Security::regenerateCsrfToken();

        $_SESSION[self::SESSION_KEY] = $user['id'];

        \App\Core\Security::logSecurityEvent('login_successful', ['user_id' => $user['id']]);

        return true;
    }

    /**
     * Check if user is authenticated
     */
    public function isAuthenticated(): bool
    {
        return isset($_SESSION[self::SESSION_KEY]);
    }

    /**
     * Get current user ID
     */
    public function getCurrentUserId(): ?int
    {
        return $_SESSION[self::SESSION_KEY] ?? null;
    }

    /**
     * Get current user data
     */
    public function getCurrentUser()
    {
        if (!$this->isAuthenticated()) {
            return null;
        }

        return $this->userModel->findById($this->getCurrentUserId());
    }

    /**
     * Logout user
     */
    public function logout(): void
    {
        $userId = $this->getCurrentUserId();
        \App\Core\Security::logSecurityEvent('logout', ['user_id' => $userId]);
        \App\Core\Security::destroySession();
    }
}
