-- ============================================================
-- UMUHUZA COOPERATIVE - Database Setup
-- Run this file in phpMyAdmin or MySQL CLI
-- ============================================================

CREATE DATABASE IF NOT EXISTS umuhuza_cooperative
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE umuhuza_cooperative;

-- ============================================================
-- Users table (Admin accounts)
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id         INT PRIMARY KEY AUTO_INCREMENT,
    username   VARCHAR(50) UNIQUE NOT NULL,
    email      VARCHAR(100) UNIQUE NOT NULL,
    password   VARCHAR(255) NOT NULL,          -- Hashed with password_hash()
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- Members table
-- ============================================================
CREATE TABLE IF NOT EXISTS members (
    id         INT PRIMARY KEY AUTO_INCREMENT,
    name       VARCHAR(100) NOT NULL,
    phone      VARCHAR(20) NOT NULL,
    village    VARCHAR(100) NOT NULL,
    join_date  DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- Products table (Maize inventory per member)
-- ============================================================
CREATE TABLE IF NOT EXISTS products (
    id         INT PRIMARY KEY AUTO_INCREMENT,
    member_id  INT NOT NULL,
    quantity   DECIMAL(10,2) NOT NULL DEFAULT 0,   -- kg in stock
    price      DECIMAL(10,2) NOT NULL,              -- RWF per kg
    type       VARCHAR(50) NOT NULL,                -- Maize variety/type
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
);

-- ============================================================
-- Clients table (Buyers)
-- ============================================================
CREATE TABLE IF NOT EXISTS clients (
    id         INT PRIMARY KEY AUTO_INCREMENT,
    name       VARCHAR(100) NOT NULL,
    phone      VARCHAR(20) NOT NULL,
    location   VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- Sales table
-- ============================================================
CREATE TABLE IF NOT EXISTS sales (
    id         INT PRIMARY KEY AUTO_INCREMENT,
    client_id  INT NOT NULL,
    product_id INT NOT NULL,
    quantity   DECIMAL(10,2) NOT NULL,             -- kg sold
    total      DECIMAL(12,2) NOT NULL,             -- RWF total
    sale_date  DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id)  REFERENCES clients(id)  ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- ============================================================
-- Useful indexes for performance
-- ============================================================
CREATE INDEX idx_products_member  ON products(member_id);
CREATE INDEX idx_sales_client     ON sales(client_id);
CREATE INDEX idx_sales_product    ON sales(product_id);
CREATE INDEX idx_sales_date       ON sales(sale_date);

-- ============================================================
-- Sample seed data (optional — remove in production)
-- ============================================================

-- Sample admin (password: admin123)
INSERT INTO users (username, email, password) VALUES
('admin', 'admin@umuhuza.rw', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Sample members
INSERT INTO members (name, phone, village, join_date) VALUES
('Uwimana Jean Pierre', '0788100001', 'Karama', '2023-01-15'),
('Mukamana Esperance',  '0788100002', 'Rwamagana', '2023-02-10'),
('Habimana Bosco',      '0788100003', 'Gahini', '2023-03-05'),
('Nyirahabimana Alice', '0788100004', 'Muhazi', '2023-04-20'),
('Nshimiyimana Eric',   '0788100005', 'Kabarondo', '2023-05-12');

-- Sample products
INSERT INTO products (member_id, quantity, price, type) VALUES
(1, 850.00,  350, 'White Maize'),
(2, 600.00,  340, 'Yellow Maize'),
(3, 1200.00, 360, 'White Maize'),
(4, 80.00,   345, 'White Maize'),
(5, 950.00,  355, 'Yellow Maize');

-- Sample clients
INSERT INTO clients (name, phone, location) VALUES
('Kigali Grain Traders Ltd', '0788200001', 'Kigali'),
('Agahozo Shalom Youth Village', '0788200002', 'Rwamagana'),
('Rwanda Millers Co.',         '0788200003', 'Rubavu'),
('USAID Food Program',         '0788200004', 'Kigali');

-- Sample sales
INSERT INTO sales (client_id, product_id, quantity, total, sale_date) VALUES
(1, 1, 200.00, 70000.00, '2024-06-01'),
(2, 3, 300.00, 108000.00, '2024-06-10'),
(3, 2, 150.00, 51000.00, '2024-06-15'),
(1, 5, 400.00, 142000.00, '2024-07-01'),
(4, 3, 200.00, 72000.00, '2024-07-20');

-- NOTE: If using the sample data, the password for the admin account is: admin123
-- The hash above ($2y$10$92IXUNpkjO0rOQ5...) is the default Laravel/PHP hash for "password"
-- For "admin123", please register through the app's register.php page instead.
