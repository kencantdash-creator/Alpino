-- AgriLink Community database
-- Import with phpMyAdmin: Import tab > choose this file > Go

CREATE DATABASE IF NOT EXISTS agrilink CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE agrilink;

DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS products;

CREATE TABLE products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  category VARCHAR(30) NOT NULL,
  price DECIMAL(8,2) NOT NULL,
  unit VARCHAR(20) NOT NULL,
  quantity INT NOT NULL DEFAULT 0,
  location VARCHAR(120) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  buyer_name VARCHAR(80) NOT NULL,
  contact VARCHAR(30) NOT NULL,
  quantity INT NOT NULL,
  method ENUM('Pickup','Delivery') NOT NULL,
  address VARCHAR(160) NULL,
  status ENUM('Pending','Confirmed','Ready','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO products (name, category, price, unit, quantity, location) VALUES
('Native Tomato', 'Vegetables', 60.00, 'kg', 40, 'Purok 3, Brgy. Cabalawan'),
('Eggplant', 'Vegetables', 55.00, 'kg', 25, 'Purok 1, Brgy. Sagkahan'),
('Banana (Lakatan)', 'Fruits', 70.00, 'kg', 8, 'Purok 5, Brgy. San Jose'),
('Sweet Potato', 'Root Crops', 45.00, 'kg', 60, 'Purok 2, Brgy. Cabalawan'),
('Brown Rice', 'Grains', 62.00, 'kg', 100, 'Brgy. Abucay Farm Hub'),
('Native Eggs', 'Poultry & Eggs', 12.00, 'piece', 120, 'Purok 4, Brgy. Sagkahan'),
('Lemongrass', 'Herbs', 30.00, 'bundle', 0, 'Purok 5, Brgy. San Jose');
