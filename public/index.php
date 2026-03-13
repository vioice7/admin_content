<?php
require '../vendor/autoload.php';

\App\Core\Security::startSession();
\App\Core\Security::setSecurityHeaders();

use App\Core\Router;

$router = new Router();

// Public Routes
$router->get('/', 'PostController@index');
$router->get('/posts/{id}', 'PostController@show');

// Admin — Auth
$router->get('/admin/login', 'AdminController@login');
$router->post('/admin/login', 'AdminController@handleLogin');
$router->post('/admin/logout', 'AdminController@logout');

// Admin — Dashboard
$router->get('/admin/dashboard', 'AdminController@dashboard');

// Admin — Profile
$router->get('/admin/profile', 'AdminController@profileForm');
$router->post('/admin/profile/update', 'AdminController@updateProfile');
$router->post('/admin/profile/password', 'AdminController@updatePassword');

// Admin — Posts
$router->get('/admin/posts/create', 'AdminController@createPostForm');
$router->post('/admin/posts/create', 'AdminController@createPost');
$router->get('/admin/posts/{id}/edit', 'AdminController@editPostForm');
$router->post('/admin/posts/{id}/edit', 'AdminController@updatePost');
$router->post('/admin/posts/{id}/delete', 'AdminController@deletePost');

$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
