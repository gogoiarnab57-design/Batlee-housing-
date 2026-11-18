-- Batlee Housing Solution - MySQL schema (import this in phpMyAdmin after creating an empty database)
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(60) NOT NULL UNIQUE,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(160) NULL,
  phone VARCHAR(25) NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','customer') NOT NULL DEFAULT 'customer',
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  slug VARCHAR(120) NOT NULL UNIQUE,
  sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS products (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id INT UNSIGNED NOT NULL,
  name VARCHAR(200) NOT NULL,
  description TEXT NULL,
  price DECIMAL(10,2) NOT NULL,
  mrp DECIMAL(10,2) NULL,
  stock INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  INDEX (category_id),
  FOREIGN KEY (category_id) REFERENCES categories(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS product_images (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id INT UNSIGNED NOT NULL,
  filename VARCHAR(80) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  INDEX (product_id),
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS orders (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_no VARCHAR(30) NOT NULL UNIQUE,
  user_id INT UNSIGNED NULL,
  cust_name VARCHAR(120) NOT NULL,
  phone VARCHAR(25) NOT NULL,
  email VARCHAR(160) NULL,
  address TEXT NOT NULL,
  landmark VARCHAR(160) NULL,
  city VARCHAR(100) NOT NULL,
  state VARCHAR(100) NOT NULL,
  pincode VARCHAR(10) NOT NULL,
  notes TEXT NULL,
  lat DECIMAL(10,7) NULL,
  lng DECIMAL(10,7) NULL,
  subtotal DECIMAL(10,2) NOT NULL,
  shipping DECIMAL(10,2) NOT NULL DEFAULT 0,
  total DECIMAL(10,2) NOT NULL,
  payment_method VARCHAR(20) NOT NULL,
  payment_status ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
  payment_ref VARCHAR(80) NULL,
  status ENUM('new','confirmed','packed','shipped','delivered','cancelled') NOT NULL DEFAULT 'new',
  created_at DATETIME NOT NULL,
  INDEX (user_id), INDEX (status), INDEX (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS order_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NULL,
  product_name VARCHAR(200) NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  qty INT NOT NULL,
  line_total DECIMAL(10,2) NOT NULL,
  INDEX (order_id),
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS reviews (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  rating TINYINT NOT NULL,
  comment TEXT NULL,
  status ENUM('pending','approved') NOT NULL DEFAULT 'pending',
  created_at DATETIME NOT NULL,
  INDEX (product_id),
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS messages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  phone VARCHAR(25) NOT NULL,
  message TEXT NOT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS settings (
  skey VARCHAR(60) PRIMARY KEY,
  svalue TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS login_attempts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ident VARCHAR(80) NOT NULL,
  ip VARCHAR(45) NOT NULL,
  created_at DATETIME NOT NULL,
  INDEX (ident), INDEX (ip)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- Starting data ----------
-- Default admin login: username @admin  /  password 12345  (change it after first login!)
INSERT INTO users (username, name, role, password_hash, created_at)
VALUES ('@admin', 'Administrator', 'admin', '$2y$10$nhYuHaUCcDC71U661LjhjuH9JUEHWAAZ3Ov1qbUrNYazKmxqGxNFq', NOW());

INSERT INTO categories (name, slug, sort_order) VALUES
 ('Bathroom Products', 'bathroom-products', 1),
 ('Kitchen Accessories', 'kitchen-accessories', 2),
 ('Hardware', 'hardware', 3),
 ('Home Utilities', 'home-utilities', 4);

INSERT INTO settings (skey, svalue) VALUES
 ('site_name', 'Batlee Housing Solution'),
 ('tagline', 'Bathroom, kitchen, hardware and home utilities - everything for your home in one place.'),
 ('hero_title', 'Everything your home needs'),
 ('hero_text', 'Bathroom fittings, kitchen accessories, hardware and daily home utilities at honest prices.'),
 ('phone', '+91 00000 00000'),
 ('whatsapp', ''),
 ('email', 'info@example.com'),
 ('address', 'Your shop address here'),
 ('hours', 'Mon - Sat, 10:00 AM - 7:00 PM'),
 ('helpline_note', 'Need help choosing a product or tracking an order? Call us or leave a message and we will get back to you.'),
 ('shipping_charge', '0'),
 ('free_shipping_above', '0'),
 ('upi_id', ''),
 ('pay_cod', '1'),
 ('pay_upi', '1'),
 ('pay_card', '1'),
 ('pay_netbanking', '1');

-- Sample products so the shop is not empty. Edit or delete them in the admin panel.
INSERT INTO products (category_id, name, description, price, mrp, stock, is_active, created_at) VALUES
 (1, 'Sample Vanity Set', 'Sample listing - replace with your own product, price and photos from the admin panel.', 4999.00, 6499.00, 10, 1, NOW()),
 (1, 'Sample Shower Head', 'Sample listing - replace with your own product, price and photos from the admin panel.', 899.00, 1199.00, 25, 1, NOW()),
 (2, 'Sample Kitchen Organizer', 'Sample listing - replace with your own product, price and photos from the admin panel.', 649.00, NULL, 30, 1, NOW()),
 (3, 'Sample Cabinet Handle Set', 'Sample listing - replace with your own product, price and photos from the admin panel.', 349.00, 499.00, 40, 1, NOW()),
 (4, 'Sample Storage Box', 'Sample listing - replace with your own product, price and photos from the admin panel.', 299.00, NULL, 50, 1, NOW());
