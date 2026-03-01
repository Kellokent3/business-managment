# 🚀 UMUHUZA COOPERATIVE  
### Modern Cooperative Management Web Application  

![PHP](https://img.shields.io/badge/PHP-8.x-blue)
![MySQL](https://img.shields.io/badge/MySQL-5.7+-orange)
![License](https://img.shields.io/badge/License-MIT-green)
![Status](https://img.shields.io/badge/Status-Production--Ready-brightgreen)

---

## 📌 Overview

**UMUHUZA Cooperative** is a modern web-based management system designed to help cooperatives efficiently manage:

- 👥 Members  
- 📦 Products  
- 🧾 Sales  
- 👤 Clients  
- 📊 Reports  

Built with **PHP (PDO)** and **MySQL**, the system focuses on security, performance, and a clean, user-friendly interface.

---

# 🖼️ Application Screenshots

## 🔐 Login Page
![Login Screenshot](screenshots/login.png)

## 📊 Dashboard
![Dashboard Screenshot](screenshots/dashboard.png)

## 👥 Members Management
![Members Screenshot](screenshots/members.png)

## 📦 Products Management
![Products Screenshot](screenshots/products.png)

## 🧾 Sales Module
![Sales Screenshot](screenshots/sales.png)

## 📝 Recent Activity
![Recent Screenshot](screenshots/Recent.png)

## ✏️ Edit Page
![Edit Screenshot](screenshots/Edit.png)

---

# ✨ Core Features

## 🔑 Authentication System
- Secure Registration & Login
- Password hashing using **BCRYPT**
- Session-based authentication
- Session ID regeneration
- Logout functionality

## 👥 Members Management
- Full CRUD operations
- Search & Pagination
- Member contribution tracking

## 📦 Products Management
- Full CRUD
- Real-time stock tracking
- Stock status indicators

## 👤 Clients Management
- Full CRUD
- Purchase history tracking

## 🧾 Sales System
- Create, update & delete sales
- Automatic total calculation
- Automatic stock deduction
- Linked client & product records

## 📊 Reports Module
- Sales reports (date filter)
- Stock reports
- Member contributions report

---

# 🔐 Security Features

✔ Passwords hashed using `password_hash()`  
✔ PDO Prepared Statements (SQL Injection Protection)  
✔ XSS protection using `htmlspecialchars()`  
✔ Secure session handling  
✔ Delete confirmation prompts  
✔ Foreign Key constraints for data integrity  

---

# 🗂️ Project Structure

```
UMUHUZA/
│
├── config/
│   └── database.php
│
├── screenshots/
│   ├── login.png
│   ├── dashboard.png
│   ├── members.png
│   ├── products.png
│   ├── sales.png
│   ├── Recent.png
│   └── Edit.png
│
├── includes/
│   ├── header.php
│   ├── footer.php
│   └── auth.php
│
├── members.php
├── products.php
├── sales.php
├── clients.php
├── reports.php
├── dashboard.php
├── login.php
├── register.php
├── logout.php
│
└── README.md
```

---

# ⚙️ Installation Guide

## 1️⃣ Clone the repository

```bash
git clone https://github.com/your-username/umuhuza.git
```

## 2️⃣ Import Database

- Create a database in phpMyAdmin
- Import the provided `.sql` file

## 3️⃣ Configure Database

Edit:

```
config/database.php
```

Update with your database credentials:

```php
$host = "localhost";
$db   = "umuhuza";
$user = "root";
$pass = "";
```

## 4️⃣ Run the Project

Place the folder inside:

```
C:\xampp\htdocs\
```

Then open:

```
http://localhost/umuhuza
```

---

# 📌 Technologies Used

- PHP 8.x  
- MySQL  
- HTML5  
- CSS3  
- Bootstrap  
- JavaScript  

---

# 📄 License

This project is licensed under the **MIT License**.

---

# 👨‍💻 Author

Developed by **Emery**  
📧 Feel free to contribute or fork this project.

---

# ⭐ Support

If you like this project, please ⭐ star the repository on GitHub.
