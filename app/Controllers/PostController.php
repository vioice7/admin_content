<?php

namespace App\Controllers;

use App\Core\Database;
use App\Models\Post;
use App\Services\AuthService;

class PostController
{
    private Post $postModel;
    private AuthService $auth;

    public function __construct()
    {
        $config = require __DIR__ . '/../../config/database.php';
        $db = \App\Core\Database::getInstance($config);

        $this->postModel = new Post($db);
        $this->auth = new AuthService($db);
    }

    /**
     * Display all posts (public side)
     * #4 FIX: clamp $page to [1, $totalPages] so a huge ?page= value cannot
     *         trigger a massive SQL OFFSET against an empty result set.
     */
    public function index($params = [])
    {
        $limit      = 4;
        $totalPosts = $this->postModel->getTotalPosts();
        $totalPages = max(1, (int) ceil($totalPosts / $limit));

        // #4: clamp between 1 and $totalPages
        $page   = max(1, min((int) ($_GET['page'] ?? 1), $totalPages));
        $offset = ($page - 1) * $limit;

        $posts = $this->postModel->getPostsPaginated($limit, $offset);

        require __DIR__ . '/../../views/posts/index.php';
    }

    /**
     * Display single post (public side)
     */
    public function show($params = [])
    {
        $id = $params['id'] ?? null;

        if (!$id) {
            header('Location: /');
            exit;
        }

        $id = (int) $id;
        if ($id <= 0) {
            http_response_code(404);
            die('Post not found');
        }

        $post = $this->postModel->getPostById($id);

        if (!$post) {
            http_response_code(404);
            die('Post not found');
        }

        require __DIR__ . '/../../views/posts/show.php';
    }
}
