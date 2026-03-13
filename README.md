# Simple CMS — PHP OOP Learning Project

A minimal CMS built with PHP OOP principles to practice and refresh core concepts.

---

## Quick Start

**1. Configure the database** — edit `config/database.php`:
```php
return [
    'host'     => 'localhost',
    'dbname'   => 'tecaim',
    'user'     => 'root',
    'password' => ''
];
```

**2. Run setup** (creates DB, tables, and default admin user):
```bash
php setup.php
```

**3. Start the server:**
```bash
php -S localhost:8000 -t public
```

**4. Open** http://localhost:8000

---

## Routes

```
GET  /                          View all posts
GET  /posts/{id}                View a post
GET  /admin/login               Login form
POST /admin/login               Process login
POST /admin/logout              Logout (CSRF protected)
GET  /admin/dashboard           Admin dashboard
GET  /admin/posts/create        Create post form
POST /admin/posts/create        Create post
GET  /admin/posts/{id}/edit     Edit post form
POST /admin/posts/{id}/edit     Update post
POST /admin/posts/{id}/delete   Delete post
```

---

## Security

- **Passwords** — Argon2ID hashing, 12+ char strength requirements
- **Sessions** — strict mode, HttpOnly + Secure + SameSite=Strict cookies, 30-min timeout, regeneration on login
- **CSRF** — token on every form and state-changing action (including logout)
- **Rate limiting** — 10 attempts/IP and 5 attempts/username per 15 min, stored in DB
- **Input** — prepared statements everywhere, trimming and type casting, output escaped with `htmlspecialchars` at render time
- **Headers** — `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `CSP`, `HSTS` (on HTTPS)
- **Authorization** — ownership check before any edit/delete (user can only modify their own posts)

---

## OOP Concepts Practiced

**Classes & MVC structure:**
```
app/Controllers/    AdminController, PostController
app/Models/         Post, User
app/Services/       AuthService
app/Core/           Router, Database, Security
```

**Dependency Injection** — controllers and services receive `Database` through their constructors, promoting loose coupling and testability.

**Encapsulation** — private properties and methods with public getters/setters.

**Singleton** — `Database::getInstance()` ensures a single PDO connection per request.

**Routing with dynamic parameters** — `/posts/{id}` extracts named parameters via regex pattern matching.

---

## Troubleshooting

| Problem | Fix |
|---|---|
| 404 on all pages | Check routes in `public/index.php`; ensure Apache rewrite rules are active if not using the built-in server |
| Database connection error | Verify MySQL is running and `config/database.php` credentials are correct |
| Tables don't exist | Re-run `php setup.php` |
| Login fails | Re-run `php setup.php` to ensure the admin user exists; clear browser cookies |

---

## Next Steps

1. **Validator class** — centralise input validation logic
2. **Comments** — 1-to-many relationship with posts
3. **Categories** — many-to-many relationship with posts
4. **File uploads** — featured images for posts
5. **Unit tests** — PHPUnit suite for models and services
6. **Logger class** — structured log for user actions and security events
7. **Custom exceptions** — typed error handling
8. **Roles & permissions** — `UserRole` model and access control

> ⚠️ **Production checklist:** use environment variables for config (never commit credentials), enforce HTTPS, add email verification, and implement password reset.
