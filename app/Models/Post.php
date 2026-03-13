<?php

namespace App\Models;

use App\Core\Database;

class Post
{
    private Database $db;
    private int $id;
    private string $title;
    private string $content;
    private int $author_id;
    private string $created_at;
    private ?string $updated_at = null;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Get all posts with author information
     */
    public function getAllPosts()
    {
        $sql = "SELECT posts.*, users.name as author_name FROM posts 
                LEFT JOIN users ON posts.author_id = users.id 
                ORDER BY posts.created_at DESC";
        
        return $this->db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get paginated posts
     */
    public function getPostsPaginated(int $limit, int $offset)
    {
        $sql = "SELECT posts.*, users.name as author_name FROM posts 
                LEFT JOIN users ON posts.author_id = users.id 
                ORDER BY posts.created_at DESC 
                LIMIT ? OFFSET ?";
        
        return $this->db->query($sql, [$limit, $offset])->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get total number of posts
     */
    public function getTotalPosts(): int
    {
        $sql = "SELECT COUNT(*) as total FROM posts";
        
        $result = $this->db->query($sql)->fetch(\PDO::FETCH_ASSOC);
        return (int) $result['total'];
    }

    /**
     * Get a single post by ID
     */
    public function getPostById(int $id)
    {
        $sql = "SELECT posts.*, users.name as author_name FROM posts 
                LEFT JOIN users ON posts.author_id = users.id 
                WHERE posts.id = :id";
        
        return $this->db->query($sql, [':id' => $id])->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Create a new post
     */
    public function create(string $title, string $content, int $author_id): bool
    {
        $sql = "INSERT INTO posts (title, content, author_id, created_at) 
                VALUES (:title, :content, :author_id, NOW())";
        
        return $this->db->query($sql, [
            ':title' => $title,
            ':content' => $content,
            ':author_id' => $author_id
        ]) !== false;
    }

    /**
     * Update a post
     */
    public function update(int $id, string $title, string $content): bool
    {
        $sql = "UPDATE posts SET title = :title, content = :content, updated_at = NOW() 
                WHERE id = :id";
        
        return $this->db->query($sql, [
            ':id' => $id,
            ':title' => $title,
            ':content' => $content
        ]) !== false;
    }

    /**
     * Delete a post
     */
    public function delete(int $id): bool
    {
        $sql = "DELETE FROM posts WHERE id = :id";
        
        return $this->db->query($sql, [':id' => $id]) !== false;
    }

    /**
     * Get posts by author
     */
    public function getPostsByAuthor(int $author_id)
    {
        $sql = "SELECT * FROM posts WHERE author_id = :author_id ORDER BY created_at DESC";
        
        return $this->db->query($sql, [':author_id' => $author_id])->fetchAll(\PDO::FETCH_ASSOC);
    }

    // Setters and Getters for OOP practice
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setAuthorId(int $author_id): void
    {
        $this->author_id = $author_id;
    }

    public function getAuthorId(): int
    {
        return $this->author_id;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCreatedAt(): string
    {
        return $this->created_at;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updated_at;
    }
}
