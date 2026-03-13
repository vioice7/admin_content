<?php

namespace App\Controllers;

use App\Core\Database;
use App\Models\Post;
use App\Models\User;
use App\Services\AuthService;

class AdminController
{
    private Post $postModel;
    private User $userModel;
    private AuthService $auth;

    public function __construct()
    {
        $config = require '../config/database.php';
        $db = \App\Core\Database::getInstance($config);

        $this->postModel = new Post($db);
        $this->userModel = new User($db);
        $this->auth = new AuthService($db);
    }

    private function requireAuth()
    {
        if (!$this->auth->isAuthenticated()) {
            header('Location: /admin/login');
            exit;
        }
    }

    // ─── Auth ────────────────────────────────────────────────────────────────

    public function login($params = [])
    {
        if ($this->auth->isAuthenticated()) {
            header('Location: /admin/dashboard');
            exit;
        }
        require '../views/admin/login.php';
    }

    public function handleLogin($params = [])
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/login');
            exit;
        }

        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!\App\Core\Security::validateCsrfToken($csrfToken)) {
            \App\Core\Security::logSecurityEvent('csrf_validation_failed', ['action' => 'login']);
            $_SESSION['error'] = 'Security validation failed';
            header('Location: /admin/login');
            exit;
        }

        $email    = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $_SESSION['error'] = 'Email and password are required';
            header('Location: /admin/login');
            exit;
        }

        if (!$this->auth->login($email, $password)) {
            $_SESSION['error'] = 'Invalid email or password';
            header('Location: /admin/login');
            exit;
        }

        header('Location: /admin/dashboard');
        exit;
    }

    public function logout($params = [])
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/dashboard');
            exit;
        }

        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!\App\Core\Security::validateCsrfToken($csrfToken)) {
            header('Location: /admin/dashboard');
            exit;
        }

        $this->auth->logout();
        header('Location: /');
        exit;
    }

    // ─── Dashboard ───────────────────────────────────────────────────────────

    public function dashboard($params = [])
    {
        $this->requireAuth();

        $posts = $this->postModel->getPostsByAuthor($this->auth->getCurrentUserId());
        $user  = $this->auth->getCurrentUser();

        require '../views/admin/dashboard.php';
    }

    // ─── Profile ─────────────────────────────────────────────────────────────

    public function profileForm($params = [])
    {
        $this->requireAuth();
        $user = $this->auth->getCurrentUser();
        require '../views/admin/profile.php';
    }

    /**
     * Handle name + email update.
     */
    public function updateProfile($params = [])
    {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/profile');
            exit;
        }

        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!\App\Core\Security::validateCsrfToken($csrfToken)) {
            \App\Core\Security::logSecurityEvent('csrf_validation_failed', ['action' => 'update_profile', 'user_id' => $this->auth->getCurrentUserId()]);
            $_SESSION['error'] = 'Security validation failed';
            header('Location: /admin/profile');
            exit;
        }

        $name  = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if (empty($name) || empty($email)) {
            $_SESSION['error'] = 'Name and email are required';
            header('Location: /admin/profile');
            exit;
        }

        if (strlen($name) > 100) {
            $_SESSION['error'] = 'Name must be less than 100 characters';
            header('Location: /admin/profile');
            exit;
        }

        if (!\App\Core\Security::validateEmail($email)) {
            $_SESSION['error'] = 'Invalid email address';
            header('Location: /admin/profile');
            exit;
        }

        $userId = $this->auth->getCurrentUserId();

        // Prevent stealing another user's email
        if ($this->userModel->emailExistsForOtherUser($userId, $email)) {
            $_SESSION['error'] = 'That email is already in use';
            header('Location: /admin/profile');
            exit;
        }

        try {
            if ($this->userModel->update($userId, $name, $email)) {
                \App\Core\Security::logSecurityEvent('profile_updated', ['user_id' => $userId]);
                $_SESSION['success'] = 'Profile updated successfully';
            } else {
                $_SESSION['error'] = 'Failed to update profile';
            }
        } catch (\Exception $e) {
            \App\Core\Security::logSecurityEvent('profile_update_failed', ['user_id' => $userId, 'error' => $e->getMessage()]);
            $_SESSION['error'] = 'An error occurred while updating your profile';
        }

        header('Location: /admin/profile');
        exit;
    }

    /**
     * Handle password change.
     * Requires the current password to be confirmed before updating.
     */
    public function updatePassword($params = [])
    {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/profile');
            exit;
        }

        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!\App\Core\Security::validateCsrfToken($csrfToken)) {
            \App\Core\Security::logSecurityEvent('csrf_validation_failed', ['action' => 'update_password', 'user_id' => $this->auth->getCurrentUserId()]);
            $_SESSION['error'] = 'Security validation failed';
            header('Location: /admin/profile');
            exit;
        }

        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword     = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $_SESSION['error'] = 'All password fields are required';
            header('Location: /admin/profile');
            exit;
        }

        if ($newPassword !== $confirmPassword) {
            $_SESSION['error'] = 'New passwords do not match';
            header('Location: /admin/profile');
            exit;
        }

        $passwordErrors = \App\Core\Security::validatePasswordStrength($newPassword);
        if (!empty($passwordErrors)) {
            $_SESSION['error'] = implode(' ', $passwordErrors);
            header('Location: /admin/profile');
            exit;
        }

        $userId = $this->auth->getCurrentUserId();
        $user   = $this->userModel->findById($userId);

        if (!$this->userModel->verifyPassword($currentPassword, $user['password'])) {
            \App\Core\Security::logSecurityEvent('password_change_failed', ['reason' => 'wrong_current_password', 'user_id' => $userId]);
            $_SESSION['error'] = 'Current password is incorrect';
            header('Location: /admin/profile');
            exit;
        }

        try {
            if ($this->userModel->updatePassword($userId, $newPassword)) {
                \App\Core\Security::logSecurityEvent('password_changed', ['user_id' => $userId]);
                // Regenerate session after password change
                \App\Core\Security::regenerateSession();
                \App\Core\Security::regenerateCsrfToken();
                $_SESSION['success'] = 'Password changed successfully';
            } else {
                $_SESSION['error'] = 'Failed to change password';
            }
        } catch (\Exception $e) {
            \App\Core\Security::logSecurityEvent('password_change_failed', ['user_id' => $userId, 'error' => $e->getMessage()]);
            $_SESSION['error'] = 'An error occurred while changing your password';
        }

        header('Location: /admin/profile');
        exit;
    }

    // ─── Posts ───────────────────────────────────────────────────────────────

    public function createPostForm($params = [])
    {
        $this->requireAuth();
        require '../views/admin/create-post.php';
    }

    public function createPost($params = [])
    {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/posts/create');
            exit;
        }

        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!\App\Core\Security::validateCsrfToken($csrfToken)) {
            \App\Core\Security::logSecurityEvent('csrf_validation_failed', ['action' => 'create_post', 'user_id' => $this->auth->getCurrentUserId()]);
            $_SESSION['error'] = 'Security validation failed';
            header('Location: /admin/posts/create');
            exit;
        }

        $title   = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');

        if (empty($title) || empty($content)) {
            $_SESSION['error'] = 'Title and content are required';
            header('Location: /admin/posts/create');
            exit;
        }

        if (strlen($title) > 255) {
            $_SESSION['error'] = 'Title must be less than 255 characters';
            header('Location: /admin/posts/create');
            exit;
        }

        if (strlen($content) > 65535) {
            $_SESSION['error'] = 'Content is too long';
            header('Location: /admin/posts/create');
            exit;
        }

        $author_id = $this->auth->getCurrentUserId();

        try {
            if ($this->postModel->create($title, $content, $author_id)) {
                $_SESSION['success'] = 'Post created successfully';
                header('Location: /admin/dashboard');
                exit;
            } else {
                $_SESSION['error'] = 'Failed to create post';
                header('Location: /admin/posts/create');
                exit;
            }
        } catch (\Exception $e) {
            \App\Core\Security::logSecurityEvent('post_creation_failed', ['user_id' => $author_id, 'error' => $e->getMessage()]);
            $_SESSION['error'] = 'An error occurred while creating the post';
            header('Location: /admin/posts/create');
            exit;
        }
    }

    public function editPostForm($params = [])
    {
        $this->requireAuth();

        $id = $params['id'] ?? null;
        if (!$id) {
            header('Location: /admin/dashboard');
            exit;
        }

        $post = $this->postModel->getPostById($id);

        if (!$post || $post['author_id'] != $this->auth->getCurrentUserId()) {
            http_response_code(403);
            die('Forbidden');
        }

        require '../views/admin/edit-post.php';
    }

    public function updatePost($params = [])
    {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/dashboard');
            exit;
        }

        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!\App\Core\Security::validateCsrfToken($csrfToken)) {
            \App\Core\Security::logSecurityEvent('csrf_validation_failed', ['action' => 'update_post', 'user_id' => $this->auth->getCurrentUserId()]);
            $_SESSION['error'] = 'Security validation failed';
            header('Location: /admin/dashboard');
            exit;
        }

        $id      = $params['id'] ?? null;
        $title   = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');

        if (!$id || empty($title) || empty($content)) {
            $_SESSION['error'] = 'Invalid data';
            header('Location: /admin/dashboard');
            exit;
        }

        $id = (int) $id;
        if ($id <= 0) {
            $_SESSION['error'] = 'Invalid post ID';
            header('Location: /admin/dashboard');
            exit;
        }

        if (strlen($title) > 255) {
            $_SESSION['error'] = 'Title must be less than 255 characters';
            header('Location: /admin/dashboard');
            exit;
        }

        if (strlen($content) > 65535) {
            $_SESSION['error'] = 'Content is too long';
            header('Location: /admin/dashboard');
            exit;
        }

        $post = $this->postModel->getPostById($id);

        if (!$post || $post['author_id'] != $this->auth->getCurrentUserId()) {
            http_response_code(403);
            die('Forbidden');
        }

        try {
            if ($this->postModel->update($id, $title, $content)) {
                $_SESSION['success'] = 'Post updated successfully';
                header('Location: /admin/dashboard');
                exit;
            } else {
                $_SESSION['error'] = 'Failed to update post';
                header('Location: /admin/posts/' . $id . '/edit');
                exit;
            }
        } catch (\Exception $e) {
            \App\Core\Security::logSecurityEvent('post_update_failed', ['user_id' => $this->auth->getCurrentUserId(), 'post_id' => $id, 'error' => $e->getMessage()]);
            $_SESSION['error'] = 'An error occurred while updating the post';
            header('Location: /admin/posts/' . $id . '/edit');
            exit;
        }
    }

    public function deletePost($params = [])
    {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /admin/dashboard');
            exit;
        }

        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!\App\Core\Security::validateCsrfToken($csrfToken)) {
            \App\Core\Security::logSecurityEvent('csrf_validation_failed', ['action' => 'delete_post', 'user_id' => $this->auth->getCurrentUserId()]);
            $_SESSION['error'] = 'Security validation failed';
            header('Location: /admin/dashboard');
            exit;
        }

        $id = $params['id'] ?? null;
        if (!$id) {
            header('Location: /admin/dashboard');
            exit;
        }

        $id = (int) $id;
        if ($id <= 0) {
            $_SESSION['error'] = 'Invalid post ID';
            header('Location: /admin/dashboard');
            exit;
        }

        $post = $this->postModel->getPostById($id);

        if (!$post || $post['author_id'] != $this->auth->getCurrentUserId()) {
            http_response_code(403);
            die('Forbidden');
        }

        try {
            if ($this->postModel->delete($id)) {
                $_SESSION['success'] = 'Post deleted successfully';
                header('Location: /admin/dashboard');
                exit;
            } else {
                $_SESSION['error'] = 'Failed to delete post';
                header('Location: /admin/dashboard');
                exit;
            }
        } catch (\Exception $e) {
            \App\Core\Security::logSecurityEvent('post_deletion_failed', ['user_id' => $this->auth->getCurrentUserId(), 'post_id' => $id, 'error' => $e->getMessage()]);
            $_SESSION['error'] = 'An error occurred while deleting the post';
            header('Location: /admin/dashboard');
            exit;
        }
    }
}
