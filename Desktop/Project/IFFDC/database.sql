-- ============================================================
-- IFFDC Maharajpur - Krishak Seva Kendra
-- Complete Database Schema
-- Import this file in phpMyAdmin
-- ============================================================

CREATE DATABASE IF NOT EXISTS `iffdc_shop` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `iffdc_shop`;

-- ============================================================
-- TABLE: admins
-- ============================================================
CREATE TABLE IF NOT EXISTS `admins` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `name`       VARCHAR(100) NOT NULL,
  `email`      VARCHAR(150) NOT NULL UNIQUE,
  `password`   VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default admin: email=admin@iffdc.com | password=Admin@1234
INSERT INTO `admins` (`name`, `email`, `password`) VALUES
('Admin', 'admin@iffdc.com', '$2y$10$9hh4q42cOw4rk/w0GZT66.uYZqmA4hM/7clQElgeuD8xheaNcxuFy');

-- ============================================================
-- TABLE: users (customers)
-- ============================================================
CREATE TABLE IF NOT EXISTS `users` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `name`          VARCHAR(100) NOT NULL,
  `email`         VARCHAR(150) NOT NULL UNIQUE,
  `phone`         VARCHAR(15) NOT NULL,
  `password`      VARCHAR(255) NOT NULL,
  `is_verified`   TINYINT(1) DEFAULT 0,
  `status`        ENUM('active','blocked') DEFAULT 'active',
  `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: email_verifications
-- ============================================================
CREATE TABLE IF NOT EXISTS `email_verifications` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT NOT NULL,
  `token`      VARCHAR(255) NOT NULL UNIQUE,
  `expires_at` DATETIME NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: password_resets
-- ============================================================
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `email`      VARCHAR(150) NOT NULL,
  `otp`        VARCHAR(6) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `used`       TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: categories
-- ============================================================
CREATE TABLE IF NOT EXISTS `categories` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `name`       VARCHAR(100) NOT NULL UNIQUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `categories` (`name`) VALUES
('Fertilizer'),
('Solid Fertilizer'),
('Bio-Fertilizer'),
('Seeds');

-- ============================================================
-- TABLE: products
-- ============================================================
CREATE TABLE IF NOT EXISTS `products` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `category_id`   INT NOT NULL,
  `name`          VARCHAR(200) NOT NULL,
  `description`   TEXT,
  `unit`          VARCHAR(50) NOT NULL DEFAULT '1 pc',
  `price`         DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `discount`      DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `offer_price`   DECIMAL(10,2) GENERATED ALWAYS AS (ROUND(price - (price * discount / 100), 2)) STORED,
  `stock_qty`     INT NOT NULL DEFAULT 0,
  `stock_status`  ENUM('in_stock','out_of_stock') DEFAULT 'in_stock',
  `image`         VARCHAR(255) DEFAULT 'default.jpg',
  `is_active`     TINYINT(1) DEFAULT 1,
  `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed products from existing frontend data
INSERT INTO `products` (`category_id`, `name`, `description`, `unit`, `price`, `discount`, `stock_qty`, `stock_status`, `image`) VALUES
(1, 'IFFDC Nano Urea', 'Advanced nano urea liquid fertilizer for better nitrogen uptake. Reduces fertilizer usage by 50%.', '500ml', 225.00, 0, 100, 'in_stock', 'NanoUrea.jpg'),
(1, 'IFFDC Nano DAP', 'Nano DAP liquid fertilizer for phosphorus needs. Superior absorption through nano technology.', '500ml', 600.00, 5, 80, 'in_stock', 'NanoDap.jpg'),
(1, 'NPK 19:19:19', 'Water soluble NPK fertilizer with equal ratio of nitrogen, phosphorus and potassium.', '1kg', 130.00, 0, 150, 'in_stock', 'NPK.jpg'),
(2, 'IFFDC UREA', 'Standard urea fertilizer for nitrogen supply. Best for paddy, wheat and vegetables.', '50kg Bag', 266.00, 0, 200, 'in_stock', 'Urea50kg.jpg'),
(2, 'IFFDC DAP', 'Di-Ammonium Phosphate fertilizer. Essential for root development and flowering.', '50kg Bag', 1350.00, 10, 120, 'in_stock', 'Dap50kg.jpg'),
(2, 'Muriate of Potash (MOP)', 'Potassium fertilizer for crop quality improvement and disease resistance.', '50kg Bag', 1700.00, 0, 90, 'in_stock', 'potoash50.jpg'),
(2, 'IFFDC NPK (12:32:16)', 'Complex NPK fertilizer ideal for all crops at planting time.', '50kg Bag', 1470.00, 8, 110, 'in_stock', 'NPK50kg.jpg'),
(3, 'Sagarika Liquid', 'Bio-stimulant made from seaweed. Promotes plant growth and soil health.', '500ml', 280.00, 0, 60, 'in_stock', 'Sagarika.jpg'),
(3, 'Neem Khali (Neem Cake)', 'Organic neem cake fertilizer. Natural pest repellent and soil conditioner.', '20kg Bag', 650.00, 5, 75, 'in_stock', 'NEEm.jpg');

-- ============================================================
-- TABLE: orders
-- ============================================================
CREATE TABLE IF NOT EXISTS `orders` (
  `id`             INT AUTO_INCREMENT PRIMARY KEY,
  `order_number`   VARCHAR(20) NOT NULL UNIQUE,
  `user_id`        INT NOT NULL,
  `total_amount`   DECIMAL(10,2) NOT NULL,
  `payment_method` ENUM('cod', 'online') DEFAULT 'cod',
  `payment_status` ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
  `payment_id`     VARCHAR(100) NULL,
  `shipping_name`  VARCHAR(100) NOT NULL,
  `address`        VARCHAR(255) NOT NULL,
  `shipping_phone` VARCHAR(15) NOT NULL,
  `pincode`        VARCHAR(10) NOT NULL,
  `status`         ENUM('pending','confirmed','delivered','cancelled') DEFAULT 'pending',
  `notes`          TEXT,
  `created_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: order_items
-- ============================================================
CREATE TABLE IF NOT EXISTS `order_items` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `order_id`    INT NOT NULL,
  `product_id`  INT NOT NULL,
  `product_name` VARCHAR(200) NOT NULL,
  `unit`        VARCHAR(50) NOT NULL,
  `quantity`    INT NOT NULL DEFAULT 1,
  `price`       DECIMAL(10,2) NOT NULL,
  `discount`    DECIMAL(5,2) NOT NULL DEFAULT 0,
  `subtotal`    DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (`order_id`)   REFERENCES `orders`(`id`)   ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- TABLE: website_settings
-- ============================================================
CREATE TABLE IF NOT EXISTS `website_settings` (
  `id`            INT AUTO_INCREMENT PRIMARY KEY,
  `setting_key`   VARCHAR(100) NOT NULL UNIQUE,
  `setting_value` TEXT,
  `updated_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `website_settings` (`setting_key`, `setting_value`) VALUES
('maintenance_mode',    '0'),
('maintenance_message', 'Website is temporarily unavailable right now. Please visit again later.'),
  ('shop_phone',          '910000000000'),
  ('shop_whatsapp',       '910000000000'),
  ('shop_address',        'Maharajpur, Uttar Pradesh, India'),
  ('shop_email',          'info@iffdc.com'),
  ('shop_gst',            '09AXXXX0000X1Z5'),
  ('low_stock_alert',     '10');

-- ============================================================
-- INDEXES for performance
-- ============================================================
CREATE INDEX idx_products_category ON products(category_id);
CREATE INDEX idx_products_status   ON products(stock_status, is_active);
CREATE INDEX idx_orders_user       ON orders(user_id);
CREATE INDEX idx_orders_status     ON orders(status);
CREATE INDEX idx_order_items_order ON order_items(order_id);
