# UMUHUZA COOPERATIVE — Web Application
## Installation & Setup Guide

### Requirements
- PHP 7.4+ (8.x recommended)
- MySQL 5.7+ or MariaDB 10.4+
- Apache/Nginx with mod_rewrite
- XAMPP / WAMP / LAMP recommended for local development

---

### Step 1: Database Setup
1. Open **phpMyAdmin** or MySQL CLI
2. Import the file **`database.sql`**
   - This creates the database `umuhuza_cooperative` and all tables
   - Sample data is included (optional)

---

### Step 2: Configure Database Connection
Open **`config.php`** and update:
```php
define('DB_HOST', 'localhost');   // Your MySQL host
define('DB_USER', 'root');        // Your MySQL username
define('DB_PASS', '');            // Your MySQL password
define('DB_NAME', 'umuhuza_cooperative');
```

---

### Step 3: Deploy Files
Copy all files to your web server root (e.g., `htdocs/umuhuza/` for XAMPP)

**File Structure:**
```
umuhuza/
├── assets/
│   └── css/
│       └── style.css
├── includes/
│   ├── header.php
│   └── footer.php
├── config.php
├── auth_check.php
├── index.php
├── login.php
├── register.php
├── logout.php
├── dashboard.php
├── members.php
├── products.php
├── clients.php
├── sales.php
├── reports.php
└── database.sql
```

---

### Step 4: Create Admin Account
1. Visit: `http://localhost/umuhuza/register.php`
2. Fill in username, email, and password (min 6 characters)
3. Click **Create Account**
4. Login at `http://localhost/umuhuza/login.php`

---

### Features
| # | Module | Feature |
|---|--------|---------|
| 1-6 | Authentication | Login, Register, Session, Logout, Password Hashing |
| 7-13 | Database | PDO connection, all 5 tables with FK relations |
| 14-18 | Members | Full CRUD + Search + Pagination |
| 19-23 | Products | Full CRUD + Stock Status Indicators |
| 24-27 | Clients | Full CRUD + Purchase History |
| 28-33 | Sales | Full CRUD + Auto Total + Stock Deduction |
| 34-36 | Reports | Sales, Stock, Member Contributions with date filters |
| 37-40 | UI/UX | Sidebar nav, success/error alerts, glassmorphism CSS |

---

### Security Features
- Passwords hashed with `password_hash()` (BCRYPT)
- PDO prepared statements (SQL injection prevention)
- Session-based authentication
- Session ID regeneration on login
- XSS prevention via `htmlspecialchars()`
- Delete confirmations for destructive actions

---

### Default Login (if using sample data)
> Register a new account via `register.php` — it's the recommended approach.
> The sample `admin` user in `database.sql` has password: `password` (standard PHP hash)
