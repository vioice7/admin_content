# Quick Start Guide

## 1️⃣ Configure Database

Edit `config/database.php`:
```php
return [
    'host' => 'localhost',      // Your MySQL host
    'dbname' => 'tecaim',          // Database name
    'user' => 'root',           // MySQL user
    'password' => ''            // MySQL password
];
```

## 2️⃣ Create Database

```bash
# Using MySQL command line
mysql -u root -e "CREATE DATABASE tecaim;"

# Or log into MySQL and run:
# CREATE DATABASE cms;
```

## 3️⃣ Run Migrations

From the project root directory:
```bash
php migrate.php
```

✅ This will:
- Create `users` table
- Create `posts` table  
- Create default admin user

## 4️⃣ Start PHP Server

```bash
php -S localhost:8000 -t public
```

## 5️⃣ Open in Browser

Visit: **http://localhost:8000**

## 📝 Test the CMS

### Public Site
- Homepage: View all posts
- Click "Read More" to see individual posts

### Admin Features
1. Click "Admin Login" in top-right
2. Login with default credentials:
   - **Email**: admin@cms.com
   - **Password**: password
3. Create a new post
4. Edit or delete posts from dashboard
5. Click logout when done

---

## 🎯 Key Routes

```
GET  /                      → View all posts
GET  /posts/{id}            → View specific post
GET  /admin/login           → Login form
POST /admin/login           → Process login
GET  /admin/dashboard       → Admin dashboard
GET  /admin/posts/create    → Create post form
POST /admin/posts/create    → Create post
GET  /admin/posts/{id}/edit → Edit post form
POST /admin/posts/{id}/edit → Update post
POST /admin/posts/{id}/delete → Delete post
GET  /admin/logout          → Logout
```

---

## 📚 OOP Concepts in This Project

### Single Responsibility Principle
- **Router** - Only handles routing
- **Database** - Only handles DB operations
- **Models** - Only handle data & queries
- **Controllers** - Only handle request/response logic
- **AuthService** - Only handles authentication

### Dependency Injection
Controllers & Services receive their dependencies through constructors, not creating them internally.

### Encapsulation
- Private properties & methods
- Public getters/setters
- Access control

### Abstraction
- Database class abstracts PDO complexity
- Models abstract data operations
- AuthService abstracts authentication logic

---

## 🔧 Troubleshooting

### "404 - Page not found"
- Check routes in `public/index.php`
- Ensure rewrite rules are working (if using Apache)

### "Database connection error"
- Check `config/database.php` settings
- Ensure MySQL is running
- Verify database exists: `mysql -u root -e "SHOW DATABASES;"`

### "Tables don't exist"
- Run `php migrate.php` again
- Check database user has CREATE privileges

### "Login fails"
- Ensure default user was created: `php migrate.php`
- Try clearing browser cookies and cache

---

## 💡 Exercises to Practice OOP

1. **Create a Comment Model** - Add comments to posts (1-to-many relationship)
2. **Add Input Validation** - Create a Validator class
3. **Add Permissions** - Create a UserRole model
4. **Add Categories** - Create a Category model & many-to-many relationship
5. **Create Unit Tests** - Practice testing models & services
6. **Add Logging** - Create a Logger class
7. **Handle Errors** - Create custom Exception classes

---

Happy learning! 🚀
