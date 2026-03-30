-- Disable foreign key checks before truncating
SET FOREIGN_KEY_CHECKS = 0;

-- 1. Drop old tables if they exist
DROP TABLE IF EXISTS `product_specifications`;
DROP TABLE IF EXISTS `build_items`;
DROP TABLE IF EXISTS `saved_builds`;
DROP TABLE IF EXISTS `consultations`;
DROP TABLE IF EXISTS `reviews`;
DROP TABLE IF EXISTS `saved_cards`;
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `order_details`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `shopping_cart`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `customers`;
DROP TABLE IF EXISTS `admins`;

-- ==========================================
-- 2. Create Core Tables
-- ==========================================

CREATE TABLE `admins` (
  `admin_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL UNIQUE,
  `role` varchar(20) DEFAULT 'SuperAdmin',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `customers` (
  `customer_id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `default_shipping_address` text DEFAULT NULL,
  `account_status` varchar(20) DEFAULT 'Active',
  `reset_token` varchar(6) DEFAULT NULL,
  `reset_token_expire` datetime DEFAULT NULL,
  PRIMARY KEY (`customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL, 
  `product_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL, 
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `image_url` varchar(255) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Available',
  `tdp_wattage` int(11) DEFAULT 0, 
  `is_package` tinyint(1) DEFAULT 0, 
  PRIMARY KEY (`product_id`),
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`category_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `shopping_cart` (
  `cart_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `added_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`cart_id`),
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`customer_id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`product_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `order_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `total_amount` decimal(10,2) NOT NULL,
  `shipping_address` text NOT NULL, 
  `contact_number` varchar(20) NOT NULL,
  `order_status` varchar(20) DEFAULT 'Pending', 
  PRIMARY KEY (`order_id`),
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`customer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `order_details` (
  `order_detail_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL, 
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL, 
  PRIMARY KEY (`order_detail_id`),
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`order_id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`product_id`) ON DELETE SET NULL 
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `payment_method` varchar(50) NOT NULL, 
  `payment_status` varchar(20) DEFAULT 'Unpaid', 
  `transaction_date` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`payment_id`),
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`order_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `saved_cards` (
  `card_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `cardholder_name` varchar(100) NOT NULL,
  `last_four_digits` varchar(4) NOT NULL,
  `expiry_date` varchar(5) NOT NULL, 
  PRIMARY KEY (`card_id`),
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`customer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `rating` int(1) NOT NULL, 
  `comment` text DEFAULT NULL,
  `review_date` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`review_id`),
  FOREIGN KEY (`product_id`) REFERENCES `products`(`product_id`) ON DELETE CASCADE,
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`customer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `consultations` (
  `consultation_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `subject` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `status` varchar(20) DEFAULT 'Open', 
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`consultation_id`),
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`customer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `saved_builds` (
  `build_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `build_name` varchar(100) DEFAULT 'My Custom PC',
  `total_price` decimal(10,2) DEFAULT 0.00,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`build_id`),
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`customer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `build_items` (
  `build_item_id` int(11) NOT NULL AUTO_INCREMENT,
  `build_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`build_item_id`),
  FOREIGN KEY (`build_id`) REFERENCES `saved_builds`(`build_id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`product_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `product_specifications` (
  `spec_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `spec_name` varchar(100) NOT NULL,
  `spec_value` varchar(255) NOT NULL,
  PRIMARY KEY (`spec_id`),
  FOREIGN KEY (`product_id`) REFERENCES `products`(`product_id`) ON DELETE CASCADE,
  INDEX `idx_spec_search` (`spec_name`, `spec_value`) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- 3. Insert Test Data (Professional English)
-- ==========================================

INSERT INTO `categories` (`category_id`, `category_name`, `description`) VALUES
(1, 'Processor (CPU)', 'The brain of the computer'),
(2, 'Motherboard', 'Main circuit board'),
(3, 'Memory (RAM)', 'Short-term data access'),
(4, 'Graphics Card (GPU)', 'Renders images and video'),
(5, 'Storage (SSD)', 'Long-term data storage'),
(6, 'Power Supply (PSU)', 'Provides power to components'),
(7, 'PC Case', 'Enclosure for components'),
(8, 'Cooling System', 'Keeps components cool');

INSERT INTO `products` (`category_id`, `product_name`, `description`, `price`, `stock_quantity`, `tdp_wattage`) VALUES
(1, 'Intel Core i5-13400F', 'Mainstream Intel Processor. Keyword: LGA1700', 950.00, 10, 65),
(1, 'Intel Core i9-14900K', 'Enthusiast Intel Processor (High TDP). Keyword: LGA1700', 2800.00, 5, 253),
(1, 'AMD Ryzen 5 7600X', 'Solid AMD Ryzen Processor. Keyword: AM5', 1100.00, 10, 105),
(2, 'ASUS ROG STRIX Z790-F LGA1700 DDR5', 'High-end Intel board, supports DDR5 memory only.', 1450.00, 8, 30),
(2, 'MSI PRO H610M-G LGA1700 DDR4', 'Budget Intel board, supports DDR4 memory only.', 450.00, 15, 20),
(2, 'Gigabyte B650 AORUS ELITE AX AM5 DDR5', 'Premium AMD board, supports DDR5 memory only.', 1350.00, 5, 30),
(3, 'Kingston Fury Beast 16GB DDR4 3200MHz', 'Reliable standard DDR4 memory module.', 200.00, 30, 8),
(3, 'Corsair Vengeance 32GB DDR5 6000MHz', 'High-speed DDR5 memory module for gaming.', 650.00, 20, 10),
(3, 'G.Skill Trident Z5 RGB 64GB DDR5', 'Enthusiast DDR5 memory kit for heavy workloads.', 1200.00, 10, 15),
(4, 'NVIDIA GeForce GT 730 2GB', 'Basic display output only (Will cause severe bottleneck with high-end CPUs).', 250.00, 20, 30),
(4, 'NVIDIA RTX 4070 SUPER 12GB', 'Sweet spot for 1440p gaming and rendering.', 3100.00, 10, 220),
(4, 'NVIDIA RTX 4090 24GB', 'Ultimate flagship GPU (Requires massive power supply).', 8500.00, 2, 450),
(6, 'Corsair CV550 550W', 'Entry-level power supply (550W).', 220.00, 15, 550),
(6, 'FSP Hydro G Pro 850W', 'High-end gold certified power supply (850W).', 600.00, 10, 850),
(6, 'ASUS ROG Thor 1200W', 'Platinum overkill power supply (1200W).', 1500.00, 3, 1200),
(5, 'Samsung 990 PRO 1TB NVMe', 'Top-tier M.2 NVMe SSD.', 550.00, 15, 5),
(5, 'WD Blue SN570 500GB NVMe', 'Budget-friendly fast storage.', 200.00, 25, 5),
(7, 'NZXT H5 Flow Black', 'High airflow premium chassis.', 400.00, 10, 0),
(8, 'Deepcool AK400 Air Cooler', 'Efficient standard air cooler.', 150.00, 20, 0),
(8, 'NZXT Kraken 360 RGB AIO', 'Premium liquid cooler with LCD.', 850.00, 8, 15);

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- 1. 讓單一零件的 ID 可以是空的 (因為放整台主機時，不需要單一零件的 ID)
ALTER TABLE `shopping_cart` MODIFY `product_id` int(11) NULL;

-- 2. 新增一個 build_id 欄位，專門用來存放「整台主機」
ALTER TABLE `shopping_cart` ADD `build_id` int(11) NULL AFTER `product_id`;

-- 3. 加上外鍵保護，確保主機資料的安全
ALTER TABLE `shopping_cart` ADD CONSTRAINT `fk_cart_build` FOREIGN KEY (`build_id`) REFERENCES `saved_builds`(`build_id`) ON DELETE CASCADE;