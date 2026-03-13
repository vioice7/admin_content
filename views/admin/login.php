<?php

$title = 'Admin Login';
$csrfTokenField = \App\Core\Security::getCsrfTokenField();

$content = <<<HTML
<div class="login-container">
    <h1>Admin Login</h1>

    <form method="POST" action="/admin/login">
        {$csrfTokenField}

        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required maxlength="100"
                   autocomplete="email">
        </div>

        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required
                   autocomplete="current-password">
        </div>

        <button type="submit" class="button full-width-button">Login</button>
    </form>
</div>
HTML;

require __DIR__ . '/../layout.php';
