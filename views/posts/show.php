<?php

$title       = htmlspecialchars($post['title'] ?? 'Post');
$postTitle   = htmlspecialchars($post['title']);
$postAuthor  = htmlspecialchars($post['author_name'] ?? 'Unknown');
$postDate    = date('F j, Y \a\t g:i A', strtotime($post['created_at']));
$postContent = nl2br(htmlspecialchars($post['content']));

$content = <<<HTML
<article class="post-article">
    <h1>{$postTitle}</h1>
    <p class="post-meta">By {$postAuthor}</p>
    <p class="post-date">Published: {$postDate}</p>

    <hr class="divider">

    <div class="post-content">{$postContent}</div>

    <hr class="divider">

    <a href="/" class="button">Back to Posts</a>
</article>
HTML;

require __DIR__ . '/../layout.php';
