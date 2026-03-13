<?php
$title = 'Create New Post';

$csrfTokenField = \App\Core\Security::getCsrfTokenField();

$content = <<<HTML
<div class="form-container">
    <h1>Create New Post</h1>

    <form method="POST" action="/admin/posts/create">
        {$csrfTokenField}

        <div class="form-group">
            <label for="title">Post Title:</label>
            <input type="text" id="title" name="title" required maxlength="255">
        </div>

        <div class="form-group">
            <label for="content">Post Content:</label>
            <textarea id="content" name="content" required maxlength="65535"></textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="button">Create Post</button>
            <a href="/admin/dashboard" class="button button-secondary cancel-button">Cancel</a>
        </div>
    </form>
</div>
HTML;

require __DIR__ . '/../layout.php';
