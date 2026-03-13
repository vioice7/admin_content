<?php

namespace App\Models;

use App\Core\Database;

class User
{
    private Database $db;
    private int $id;
    private string $name;
    private string $email;
    private string $password;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Create a new user
     */
    public function create(string $name, string $email, string $password): bool
    {
        // Validate password strength
        $passwordErrors = \App\Core\Security::validatePasswordStrength($password);
        if (!empty($passwordErrors)) {
            throw new \InvalidArgumentException('Password does not meet requirements: ' . implode(', ', $passwordErrors));
        }

        // Validate email
        if (!\App\Core\Security::validateEmail($email)) {
            throw new \InvalidArgumentException('Invalid email address');
        }

        // Sanitize inputs
        $name = \App\Core\Security::sanitizeString($name);
        $email = \App\Core\Security::sanitizeString($email);

        $sql = "INSERT INTO users (name, email, password, created_at) 
                VALUES (:name, :email, :password, NOW())";
        
        return $this->db->query($sql, [
            ':name' => $name,
            ':email' => $email,
            ':password' => \App\Core\Security::hashPassword($password)
        ]) !== false;
    }

    /**
     * Find user by email
     */
    public function findByEmail(string $email)
    {
        $sql = "SELECT * FROM users WHERE email = :email LIMIT 1";
        
        return $this->db->query($sql, [':email' => $email])->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Find user by ID
     */
    public function findById(int $id)
    {
        $sql = "SELECT * FROM users WHERE id = :id LIMIT 1";
        
        return $this->db->query($sql, [':id' => $id])->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Verify password
     */
    public function verifyPassword(string $password, string $hash): bool
    {
        return \App\Core\Security::verifyPassword($password, $hash);
    }

    /**
     * Update user profile
     */
    public function update(int $id, string $name, string $email): bool
    {
        $sql = "UPDATE users SET name = :name, email = :email WHERE id = :id";
        
        return $this->db->query($sql, [
            ':id' => $id,
            ':name' => $name,
            ':email' => $email
        ]) !== false;
    }

    // Setters and Getters
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setPassword(string $password): void
    {
        $this->password = password_hash($password, PASSWORD_BCRYPT);
    }

    public function getId(): int
    {
        return $this->id;
    }
}
