<?php

$title           = 'My Profile';
$currentName     = htmlspecialchars($user['name']);
$currentEmail    = htmlspecialchars($user['email']);
$csrfTokenField  = \App\Core\Security::getCsrfTokenField();

$content = <<<HTML
<div class="form-container">
    <h1>My Profile</h1>

    <!-- ── Profile details ───────────────────────────────────────── -->
    <h2 class="section-title" style="margin-top:32px;">Account Details</h2>

    <form method="POST" action="/admin/profile/update">
        {$csrfTokenField}

        <div class="form-group">
            <label for="name">Name:</label>
            <input type="text" id="name" name="name"
                   value="{$currentName}" required maxlength="100"
                   autocomplete="name">
        </div>

        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email"
                   value="{$currentEmail}" required maxlength="100"
                   autocomplete="email">
        </div>

        <div class="form-actions">
            <button type="submit" class="button">Save Changes</button>
            <a href="/admin/dashboard" class="button button-secondary cancel-button">Cancel</a>
        </div>
    </form>

    <hr class="divider">

    <!-- ── Change password ───────────────────────────────────────── -->
    <h2 class="section-title">Change Password</h2>
    <p style="margin-bottom:16px;color:#666;font-size:14px;">
        Min 12 characters, at least one uppercase, one lowercase, one number, one special character.
    </p>

    <form method="POST" action="/admin/profile/password">
        {$csrfTokenField}

        <div class="form-group">
            <label for="current_password">Current Password:</label>
            <input type="password" id="current_password" name="current_password" required
                   autocomplete="current-password">
        </div>

        <div class="form-group">
            <label for="new_password">New Password:</label>
            <input type="password" id="new_password" name="new_password" required
                   autocomplete="new-password">
        </div>

        <div class="form-group">
            <label for="confirm_password">Confirm New Password:</label>
            <input type="password" id="confirm_password" name="confirm_password" required
                   autocomplete="new-password">
        </div>

        <div class="form-actions">
            <button type="submit" class="button">Change Password</button>
        </div>
    </form>
</div>
HTML;

require __DIR__ . '/../layout.php';
