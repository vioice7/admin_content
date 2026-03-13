<?php

namespace App\Controllers;

use App\Models\Post;

class SitemapController
{
    private Post $postModel;

    public function __construct()
    {
        $config = require __DIR__ . '/../../config/database.php';
        $db = \App\Core\Database::getInstance($config);
        $this->postModel = new Post($db);
    }

    public function index($params = [])
    {
        $scheme  = isset($_SERVER['HTTPS']) ? 'https' : 'http';
        $host    = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $baseUrl = $scheme . '://' . $host;

        $posts = $this->postModel->getAllPosts();

        header('Content-Type: application/xml; charset=utf-8');
        header('X-Robots-Tag: noindex'); // don't index the sitemap itself

        echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

        // Homepage
        echo $this->url($baseUrl . '/', date('Y-m-d'), 'daily', '1.0');

        // Individual posts
        foreach ($posts as $post) {
            $loc      = $baseUrl . '/posts/' . (int) $post['id'];
            $lastmod  = date('Y-m-d', strtotime($post['updated_at'] ?? $post['created_at']));
            echo $this->url($loc, $lastmod, 'weekly', '0.8');
        }

        echo '</urlset>' . PHP_EOL;
    }

    private function url(string $loc, string $lastmod, string $changefreq, string $priority): string
    {
        return "  <url>\n"
            . "    <loc>" . htmlspecialchars($loc, ENT_XML1) . "</loc>\n"
            . "    <lastmod>{$lastmod}</lastmod>\n"
            . "    <changefreq>{$changefreq}</changefreq>\n"
            . "    <priority>{$priority}</priority>\n"
            . "  </url>\n";
    }
}
