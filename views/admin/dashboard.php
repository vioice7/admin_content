<?php

$title = 'Admin Dashboard';
$userName = htmlspecialchars($user['name']);
$csrfTokenField = \App\Core\Security::getCsrfTokenField();

// Build posts table rows
$postsHtml = '';
if (empty($posts)) {
    $postsHtml = '<p>No posts yet. <a href="/admin/posts/create">Create your first post</a></p>';
} else {
    $rows = '';
    foreach ($posts as $post) {
        $postId    = htmlspecialchars($post['id']);
        $postTitle = htmlspecialchars($post['title']);
        $postDate  = date('M j, Y', strtotime($post['created_at']));

        $rows .= <<<HTML
            <tr>
                <td><a href="/posts/{$postId}">{$postTitle}</a></td>
                <td>{$postDate}</td>
                <td class="actions">
                    <a href="/admin/posts/{$postId}/edit" class="button">Edit</a>
                    <form method="POST" action="/admin/posts/{$postId}/delete">
                        {$csrfTokenField}
                        <button type="submit" class="button button-danger"
                                onclick="return confirm('Are you sure?')">Delete</button>
                    </form>
                </td>
            </tr>
        HTML;
    }

    $postsHtml = <<<HTML
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                {$rows}
            </tbody>
        </table>
    HTML;
}

$content = <<<HTML
<div>
    <h1>Dashboard</h1>

    <p class="welcome-message">Welcome, <strong>{$userName}</strong></p>

    <div class="section">
        <h2 class="section-title">My Posts</h2>
        <a href="/admin/posts/create" class="button button-success">Create New Post</a>
    </div>

    {$postsHtml}
</div>
HTML;

require __DIR__ . '/../layout.php';
