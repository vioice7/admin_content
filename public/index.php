<?php
require '../vendor/autoload.php';

// Start secure session
\App\Core\Security::startSession();

// Set security headers
\App\Core\Security::setSecurityHeaders();

use App\Core\Router;

$router = new Router();

// Public Routes
$router->get('/', 'PostController@index');
$router->get('/posts/{id}', 'PostController@show');

// Admin Routes
$router->get('/admin/login', 'AdminController@login');
$router->post('/admin/login', 'AdminController@handleLogin');
$router->get('/admin/logout', 'AdminController@logout');

$router->get('/admin/dashboard', 'AdminController@dashboard');

$router->get('/admin/posts/create', 'AdminController@createPostForm');
$router->post('/admin/posts/create', 'AdminController@createPost');

$router->get('/admin/posts/{id}/edit', 'AdminController@editPostForm');
$router->post('/admin/posts/{id}/edit', 'AdminController@updatePost');

$router->post('/admin/posts/{id}/delete', 'AdminController@deletePost');

// Dispatch the request
$uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

$router->dispatch($uri, $method);
