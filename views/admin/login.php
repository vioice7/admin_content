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
            <input type="email" id="email" name="email" required maxlength="100">
        </div>

        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
        </div>

        <button type="submit" class="button full-width-button">Login</button>
    </form>
</div>
HTML;

require '../views/layout.php';
