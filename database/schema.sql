-- Mini Project 3 - Inventory Management System
-- Jalankan: mysql -u root -p < database/schema.sql
CREATE DATABASE IF NOT EXISTS inventory_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE inventory_db;

DROP TABLE IF EXISTS stock_movements;
DROP TABLE IF EXISTS product_suppliers;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS suppliers;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS user_profiles;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin','staff') NOT NULL DEFAULT 'staff',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- One-to-one: users <-> user_profiles (FK + UNIQUE)
CREATE TABLE user_profiles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE suppliers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    phone VARCHAR(30) NULL
) ENGINE=InnoDB;

-- One-to-many: categories -> products
CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT NOT NULL,
    sku VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    price DECIMAL(12,2) NOT NULL CHECK (price >= 0),
    stock INT NOT NULL DEFAULT 0 CHECK (stock >= 0),
    minimum_stock INT NOT NULL DEFAULT 5 CHECK (minimum_stock >= 0),
    FOREIGN KEY (category_id) REFERENCES categories(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Many-to-many: products <-> suppliers (junction table + composite PK)
CREATE TABLE product_suppliers (
    product_id INT NOT NULL,
    supplier_id INT NOT NULL,
    supplier_price DECIMAL(12,2) NULL,
    PRIMARY KEY (product_id, supplier_id),
    FOREIGN KEY (product_id) REFERENCES products(id) ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Histori transaksi: RESTRICT agar produk/user yang punya histori tidak terhapus sembarangan
CREATE TABLE stock_movements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    type ENUM('IN','OUT') NOT NULL,
    quantity INT NOT NULL CHECK (quantity > 0),
    note VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ===== Data contoh =====
-- Login: admin / admin123  dan  staff / staff123
INSERT INTO users (username, password_hash, role) VALUES
('admin', '$2y$12$.jnbKfso22mmu2SL2ffSc.Ym0SR6ROuVFmjHEkVz3yXYXBj..qAXu', 'admin'),
('staff', '$2y$12$nwTEm0YEIsBiiS3c7xPhzuQmPwq4i0DQ9nM3PU93wRTaa84SJFWUy', 'staff');
INSERT INTO user_profiles (user_id, full_name) VALUES (1,'Administrator Toko'),(2,'Staff Gudang');

INSERT INTO categories (name) VALUES ('Aksesoris'),('Elektronik'),('Alat Tulis');
INSERT INTO suppliers (name, phone) VALUES ('PT Maju Jaya','081234567890'),('CV Sumber Rejeki','082198765432');

-- stok produk = total IN - total OUT dari stock_movements di bawah
INSERT INTO products (category_id, sku, name, price, stock, minimum_stock) VALUES
(1,'KB-001','Keyboard Mechanical',350000,15,5),
(1,'MS-001','Mouse Wireless',120000,3,5),
(2,'HD-001','Headset Gaming',275000,8,5),
(3,'PN-001','Pulpen Gel (box)',45000,40,10);
INSERT INTO product_suppliers (product_id, supplier_id, supplier_price) VALUES
(1,1,300000),(1,2,310000),(2,1,95000),(3,2,230000),(4,1,35000);
INSERT INTO stock_movements (product_id, user_id, type, quantity, note) VALUES
(1,1,'IN',20,'Stok awal'),(1,2,'OUT',5,'Penjualan'),
(2,1,'IN',10,'Stok awal'),(2,2,'OUT',7,'Penjualan'),
(3,1,'IN',8,'Stok awal'),(4,1,'IN',40,'Stok awal');
