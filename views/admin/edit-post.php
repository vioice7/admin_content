<?php

$title = 'Edit Post';

$postId      = htmlspecialchars($post['id']);
$postTitle   = htmlspecialchars($post['title']);
$postContent = htmlspecialchars($post['content']);
$csrfTokenField = \App\Core\Security::getCsrfTokenField();

$content = <<<HTML
<div class="form-container">
    <h1>Edit Post</h1>

    <form method="POST" action="/admin/posts/{$postId}/edit">
        {$csrfTokenField}

        <div class="form-group">
            <label for="title">Post Title:</label>
            <input type="text" id="title" name="title" value="{$postTitle}" required maxlength="255">
        </div>

        <div class="form-group">
            <label for="content">Post Content:</label>
            <textarea id="content" name="content" required maxlength="65535">{$postContent}</textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="button">Update Post</button>
            <a href="/admin/dashboard" class="button button-secondary cancel-button">Cancel</a>
        </div>
    </form>
</div>
HTML;

require '../views/layout.php';
