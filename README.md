# Simple CMS - PHP OOP Learning Project

A minimal CMS built with PHP OOP principles to practice and refresh core concepts.

## Features

### Public Side
- ✅ View all posts
- ✅ View individual post

### Admin Side
- ✅ User authentication (login/logout)
- ✅ Create new posts
- ✅ Edit existing posts
- ✅ Delete posts
- ✅ Admin dashboard

## Security Features

### Session Security
- ✅ Secure session configuration with strict mode enabled
- ✅ HttpOnly and Secure cookie flags
- ✅ SameSite=Strict attribute
- ✅ Session regeneration on successful login
- ✅ Session inactivity timeout (30 minutes)
- ✅ Proper session destruction on logout with cookie cleanup

### Authentication & Authorization
- ✅ Password hashing using Argon2ID
- ✅ Password strength requirements (12+ chars, uppercase, lowercase, numbers)
- ✅ Login rate limiting (10 attempts per IP, 5 per username in 15 minutes)
- ✅ Login attempt logging with IP tracking
- ✅ Admin-only access controls with session validation
- ✅ CSRF protection on all forms and sensitive operations

### Input Validation & Sanitization
- ✅ Filename validation for uploads (allowed extensions, length limits, path traversal prevention)
- ✅ URL validation for external links (http/https only)
- ✅ HTML escaping (htmlspecialchars) on all user-controlled output
- ✅ Input trimming and type casting (e.g., intval for IDs)

### Database Security
- ✅ PDO with exception error mode
- ✅ Foreign key constraints enabled
- ✅ Prepared statements for all queries
- ✅ Transaction handling for atomic operations
- ✅ Database connection singleton pattern

### HTTP Security Headers
- ✅ X-Frame-Options: DENY (prevents clickjacking)
- ✅ X-Content-Type-Options: nosniff
- ✅ Referrer-Policy: strict-origin-when-cross-origin
- ✅ X-XSS-Protection: 0 (disabled, relies on CSP)
- ✅ Strict-Transport-Security (HSTS) for HTTPS
- ✅ Content Security Policy (CSP) restricting script/style sources

### Error Handling & Logging
- ✅ Error logging for security events (failed logins, validation failures)
- ✅ Graceful error handling without information disclosure
- ✅ Database error logging

## OOP Concepts Practiced

### 1. Classes & Objects
- `User` - User model for authentication
- `Post` - Post model for CRUD operations
- `Router` - Request routing
- `Database` - PDO database abstraction layer
- `AuthService` - Authentication business logic

### 2. Encapsulation
- Private properties and methods (e.g., `$db`, `matchPattern()`)
- Getters and setters for safe property access

### 3. Dependency Injection
- Controllers receive `Database` through constructor
- `AuthService` receives `Database` as dependency
- Models receive `Database` through constructor
- Promotes loose coupling and testability

### 4. MVC Pattern
```
Controllers/
├── PostController     - Handles post display logic
└── AdminController    - Handles admin operations

Models/
├── Post              - Post data & operations
└── User              - User data & operations

views/
├── posts/            - Public views
└── admin/            - Admin views
```

### 5. Routing with Dynamic Parameters
- Static routes: `/` → `PostController@index`
- Dynamic routes: `/posts/{id}` → Captures ID parameter
- HTTP method support: GET, POST

### 6. Authentication & Authorization
- Password hashing with Argon2ID
- Session security with strict mode, HttpOnly, Secure, and SameSite=Strict
- Session regeneration on successful login
- Session inactivity timeout (30 minutes)
- CSRF protection on all forms
- Secure session destruction on logout

## Project Structure

```
tecaim/
├── app/
│   ├── Controllers/
│   │   ├── PostController.php
│   │   └── AdminController.php
│   ├── Models/
│   │   ├── Post.php
│   │   └── User.php
│   ├── Services/
│   │   └── AuthService.php
│   └── Core/
│       ├── Router.php
│       ├── Database.php
│       └── Security.php
├── config/
│   └── database.php
├── public/
│   ├── index.php
│   └── style.css
├── views/
│   ├── layout.php
│   ├── posts/
│   │   ├── index.php
│   │   └── show.php
│   └── admin/
│       ├── login.php
│       ├── dashboard.php
│       ├── create-post.php
│       └── edit-post.php
├── vendor/
├── composer.json
└── setup.php
```

## Installation & Setup

### 1. Configure Database
Edit `config/database.php`:
```php
return [
    'host'     => 'localhost',
    'dbname'   => 'tecaim',
    'user'     => 'root',
    'password' => ''
];
```

### 2. Run Setup
From the project root:
```bash
php setup.php
```

This will:
- Create the `tecaim` database if it doesn't exist
- Create the `users` table
- Create the `posts` table
- Create the default admin user

### 3. Start Development Server
```bash
php -S localhost:8000 -t public
```

### 4. Open in Browser
Visit: **http://localhost:8000**

## Usage

### Public Site
- Home page: `/` — View all posts
- Single post: `/posts/{id}` — View specific post

### Admin Panel
- Login: `/admin/login`
- Dashboard: `/admin/dashboard`
- Create post: `/admin/posts/create`
- Edit post: `/admin/posts/{id}/edit`
- Delete: Form submission with CSRF protection
- Logout: `/admin/logout`

Default credentials: **admin@cms.com** / **password**

## Key Learning Points

### 1. Constructor Dependency Injection
```php
public function __construct()
{
    $config = require '../config/database.php';
    $db = Database::getInstance($config);
    $this->postModel = new Post($db);
}
```

### 2. Model Pattern
```php
class Post {
    public function create($title, $content, $author_id) { ... }
    public function update($id, $title, $content) { ... }
    public function delete($id) { ... }
}
```

### 3. Service Layer
```php
class AuthService {
    public function login($email, $password) { ... }
    public function isAuthenticated() { ... }
    public function getCurrentUser() { ... }
}
```

### 4. Dynamic Routing
```php
// Pattern matching with parameter extraction
private function matchPattern($pattern, $uri) {
    // /posts/{id} matches /posts/123
    // Extracts: ['id' => '123']
}
```

## Key Routes

```
GET  /                        → View all posts
GET  /posts/{id}              → View specific post
GET  /admin/login             → Login form
POST /admin/login             → Process login
GET  /admin/dashboard         → Admin dashboard
GET  /admin/posts/create      → Create post form
POST /admin/posts/create      → Create post
GET  /admin/posts/{id}/edit   → Edit post form
POST /admin/posts/{id}/edit   → Update post
POST /admin/posts/{id}/delete → Delete post
GET  /admin/logout            → Logout
```

## MySQL Queries Reference

```sql
-- View all posts with author info
SELECT posts.*, users.name AS author_name
FROM posts
LEFT JOIN users ON posts.author_id = users.id
ORDER BY posts.created_at DESC;

-- Get specific post
SELECT * FROM posts WHERE id = 1;

-- Get user's posts
SELECT * FROM posts WHERE author_id = 1;
```

## Next Steps to Enhance

1. **Add a Validator class** — Centralise input validation
2. **Add Comments** — Comments model & controller (1-to-many relationship)
3. **Add Categories** — Category model with many-to-many relationship
4. **Add File Uploads** — Featured images for posts
5. **Add Unit Tests** — PHPUnit test suite for models & services
6. **Add a Logger class** — Log user actions and security events
7. **Add custom Exceptions** — Structured error handling
8. **Add Caching** — Cache frequently accessed data
9. **Add Roles & Permissions** — UserRole model and access control

## Security Notes

⚠️ This is a learning project. In production, also consider:
- Email verification on registration
- Password reset functionality
- HTTPS enforcement
- Environment-based config (no credentials in version control)

---

**Created as a PHP OOP learning project** 📚
