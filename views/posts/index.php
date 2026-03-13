<?php

$title = 'Posts';

// Build post cards
$postsHtml = '';
if (empty($posts)) {
    $postsHtml = '<p>No posts yet.</p>';
} else {
    foreach ($posts as $post) {
        $postId      = htmlspecialchars($post['id']);
        $postTitle   = htmlspecialchars($post['title']);
        $postAuthor  = htmlspecialchars($post['author_name'] ?? 'Unknown');
        $postDate    = date('F j, Y', strtotime($post['created_at']));
        $postExcerpt = htmlspecialchars(substr($post['content'], 0, 200));

        $postsHtml .= <<<HTML
            <div class="post-card">
                <h2><a href="/posts/{$postId}" class="post-title-link">{$postTitle}</a></h2>
                <p class="post-meta">By {$postAuthor} on {$postDate}</p>
                <p class="post-excerpt">{$postExcerpt}...</p>
                <a href="/posts/{$postId}" class="button">Read More</a>
            </div>
        HTML;
    }
}

// Build pagination
$paginationHtml = '';
if ($totalPages > 1) {
    $prevLink = $page > 1
        ? '<a href="?page=' . ($page - 1) . '">&laquo; Previous</a>'
        : '';

    $nextLink = $page < $totalPages
        ? '<a href="?page=' . ($page + 1) . '">Next &raquo;</a>'
        : '';

    $pageLinks = '';
    for ($i = 1; $i <= $totalPages; $i++) {
        $pageLinks .= $i === $page
            ? "<span class=\"current\">{$i}</span>"
            : "<a href=\"?page={$i}\">{$i}</a>";
    }

    $paginationHtml = <<<HTML
        <div class="pagination">
            {$prevLink}
            {$pageLinks}
            {$nextLink}
        </div>
    HTML;
}

$content = <<<HTML
<div class="posts-list">
    {$postsHtml}
</div>

{$paginationHtml}
HTML;

require '../views/layout.php';
