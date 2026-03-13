<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'CMS'; ?></title>
    <link rel="stylesheet" href="/style.css">
    <link rel="sitemap" type="application/xml" href="/sitemap.xml">
</head>
<body>
    <header>
        <nav>
            <h1><a href="/" class="logo-link">📝 CMS</a></h1>
            <div>
                <a href="/">Home</a>
                <?php if (isset($user)): ?>
                    <a href="/admin/dashboard">Dashboard</a>
                    <a href="/admin/profile">Profile</a>
                    <?php $logoutCsrf = \App\Core\Security::getCsrfTokenField(); ?>
                    <form method="POST" action="/admin/logout" style="display:inline;">
                        <?php echo $logoutCsrf; ?>
                        <button type="submit" class="nav-logout-btn">
                            Logout (<?php echo htmlspecialchars($user['name']); ?>)
                        </button>
                    </form>
                <?php else: ?>
                    <a href="/admin/login">Admin Login</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <div class="container">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($_SESSION['success']); ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($_SESSION['error']); ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php echo $content; ?>
    </div>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Simple CMS - Built with PHP OOP</p>
    </footer>
</body>
</html>
