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

    public function create(string $name, string $email, string $password): bool
    {
        $passwordErrors = \App\Core\Security::validatePasswordStrength($password);
        if (!empty($passwordErrors)) {
            throw new \InvalidArgumentException('Password does not meet requirements: ' . implode(', ', $passwordErrors));
        }

        if (!\App\Core\Security::validateEmail($email)) {
            throw new \InvalidArgumentException('Invalid email address');
        }

        $name  = \App\Core\Security::sanitizeString($name);
        $email = \App\Core\Security::sanitizeString($email);

        $sql = "INSERT INTO users (name, email, password, created_at)
                VALUES (:name, :email, :password, NOW())";

        return $this->db->query($sql, [
            ':name'     => $name,
            ':email'    => $email,
            ':password' => \App\Core\Security::hashPassword($password)
        ]) !== false;
    }

    public function findByEmail(string $email)
    {
        $sql = "SELECT * FROM users WHERE email = :email LIMIT 1";
        return $this->db->query($sql, [':email' => $email])->fetch(\PDO::FETCH_ASSOC);
    }

    public function findById(int $id)
    {
        $sql = "SELECT * FROM users WHERE id = :id LIMIT 1";
        return $this->db->query($sql, [':id' => $id])->fetch(\PDO::FETCH_ASSOC);
    }

    public function verifyPassword(string $password, string $hash): bool
    {
        return \App\Core\Security::verifyPassword($password, $hash);
    }

    public function update(int $id, string $name, string $email): bool
    {
        $sql = "UPDATE users SET name = :name, email = :email WHERE id = :id";
        return $this->db->query($sql, [
            ':id'    => $id,
            ':name'  => $name,
            ':email' => $email
        ]) !== false;
    }

    /**
     * Update password — caller must verify current password before calling this.
     */
    public function updatePassword(int $id, string $newPassword): bool
    {
        $sql = "UPDATE users SET password = :password WHERE id = :id";
        return $this->db->query($sql, [
            ':id'       => $id,
            ':password' => \App\Core\Security::hashPassword($newPassword)
        ]) !== false;
    }

    /**
     * Check if an email is already taken by a different user.
     */
    public function emailExistsForOtherUser(int $currentUserId, string $email): bool
    {
        $sql = "SELECT id FROM users WHERE email = :email AND id != :id LIMIT 1";
        return (bool) $this->db->query($sql, [
            ':email' => $email,
            ':id'    => $currentUserId
        ])->fetch();
    }

    // Getters / Setters
    public function setName(string $name): void  { $this->name = $name; }
    public function getName(): string             { return $this->name; }
    public function setEmail(string $email): void { $this->email = $email; }
    public function getEmail(): string            { return $this->email; }
    public function setPassword(string $password): void
    {
        $this->password = \App\Core\Security::hashPassword($password);
    }
    public function getId(): int { return $this->id; }
}
