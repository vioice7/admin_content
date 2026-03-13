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
        $config = require '../config/database.php';
        $db = \App\Core\Database::getInstance($config);

        $this->postModel = new Post($db);
        $this->auth = new AuthService($db);
    }

    /**
     * Display all posts (public side)
     */
    public function index($params = [])
    {
        $page   = max(1, (int) ($_GET['page'] ?? 1));
        $limit  = 4;
        $offset = ($page - 1) * $limit;

        $posts      = $this->postModel->getPostsPaginated($limit, $offset);
        $totalPosts = $this->postModel->getTotalPosts();
        $totalPages = (int) ceil($totalPosts / $limit);

        require '../views/posts/index.php';
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

        require '../views/posts/show.php';
    }
}
