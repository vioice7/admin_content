<?php

namespace App\Services;

use App\Models\User;
use App\Core\Database;

/**
 * Authentication Service - Dependency Injection in action
 * This service handles all authentication logic
 */
class AuthService
{
    private User $userModel;
    private const SESSION_KEY = 'user_id';

    public function __construct(Database $db)
    {
        // Dependency Injection: AuthService receives Database through constructor
        $this->userModel = new User($db);
    }

    /**
     * Register a new user
     */
    public function register(string $name, string $email, string $password): bool
    {
        // Check if user already exists
        if ($this->userModel->findByEmail($email)) {
            return false;
        }

        return $this->userModel->create($name, $email, $password);
    }

    /**
     * Login a user
     */
    public function login(string $email, string $password): bool
    {
        // Sanitize email
        $email = \App\Core\Security::sanitizeString($email);

        // Get client IP for rate limiting
        $clientIP = \App\Core\Security::getClientIP();

        // Check rate limiting
        if (!\App\Core\Security::checkLoginRateLimit($clientIP, 'ip')) {
            \App\Core\Security::logSecurityEvent('rate_limit_exceeded', ['type' => 'ip', 'identifier' => $clientIP]);
            return false;
        }

        // Find user
        $user = $this->userModel->findByEmail($email);

        if (!$user) {
            // Record failed attempt for IP
            \App\Core\Security::recordLoginAttempt($clientIP, 'ip');
            \App\Core\Security::logSecurityEvent('login_failed', ['reason' => 'user_not_found', 'email' => $email]);
            return false;
        }

        // Check rate limiting for specific user
        if (!\App\Core\Security::checkLoginRateLimit($user['email'], 'user')) {
            \App\Core\Security::logSecurityEvent('rate_limit_exceeded', ['type' => 'user', 'identifier' => $user['email']]);
            return false;
        }

        // Verify password
        if (!$this->userModel->verifyPassword($password, $user['password'])) {
            // Record failed attempts
            \App\Core\Security::recordLoginAttempt($clientIP, 'ip');
            \App\Core\Security::recordLoginAttempt($user['email'], 'user');
            \App\Core\Security::logSecurityEvent('login_failed', ['reason' => 'invalid_password', 'user_id' => $user['id']]);
            return false;
        }

        // Successful login - reset rate limiting counters
        \App\Core\Security::resetLoginAttempts($clientIP, 'ip');
        \App\Core\Security::resetLoginAttempts($user['email'], 'user');

        // Set session
        $_SESSION[self::SESSION_KEY] = $user['id'];

        // Regenerate session ID for security
        \App\Core\Security::regenerateSession();

        // Regenerate CSRF token
        \App\Core\Security::regenerateCsrfToken();

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
