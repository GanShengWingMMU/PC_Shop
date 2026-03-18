-- 关闭外键检查，避免重置表时报错
SET FOREIGN_KEY_CHECKS = 0;

-- 1. 刪除舊表 (如果存在的话，确保环境干净)
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

-- =========================================================
-- 开始建立核心资料表
-- =========================================================

-- 2. 管理員表 (Admins)
CREATE TABLE `admins` (
  `admin_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL UNIQUE,
  `role` varchar(20) DEFAULT 'SuperAdmin', /* SuperAdmin, Manager 等 */
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. 客戶表 (Customers)
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

-- 4. 商品分類表 (Categories)
CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. 商品總表 (Products) - [🌟 高分强化：增加了功耗与套餐标记]
CREATE TABLE `products` (
  `product_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL, 
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL, 
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `image_url` varchar(255) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Available',
  `tdp_wattage` int(11) DEFAULT 0, /* 🌟 新增：用于 PC Builder 快速计算总功耗 */
  `is_package` tinyint(1) DEFAULT 0, /* 🌟 新增：区分散件(0)还是预设套餐(1) */
  PRIMARY KEY (`product_id`),
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`category_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. 購物車表 (Shopping Cart)
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

-- 7. 訂單總表 (Orders)
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

-- 8. 訂單明細表 (Order Details) - [🚨 致命漏洞已修复：改用 SET NULL 保留历史账单]
CREATE TABLE `order_details` (
  `order_detail_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL, /* 必须允许 NULL，才能在商品被删时保留订单记录 */
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL, 
  PRIMARY KEY (`order_detail_id`),
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`order_id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`product_id`) ON DELETE SET NULL /* 🚨 修复为 SET NULL */
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. 付款紀錄表 (Payments)
CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `payment_method` varchar(50) NOT NULL, 
  `payment_status` varchar(20) DEFAULT 'Unpaid', 
  `transaction_date` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`payment_id`),
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`order_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. 儲存的信用卡 (Saved Cards)
CREATE TABLE `saved_cards` (
  `card_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `cardholder_name` varchar(100) NOT NULL,
  `last_four_digits` varchar(4) NOT NULL,
  `expiry_date` varchar(5) NOT NULL, 
  PRIMARY KEY (`card_id`),
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`customer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11. 商品評價表 (Reviews)
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

-- 12. 諮詢服務表 (Consultations)
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

-- 13. 儲存的電腦組裝菜單 (Saved Builds)
CREATE TABLE `saved_builds` (
  `build_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `build_name` varchar(100) DEFAULT 'My Custom PC',
  `total_price` decimal(10,2) DEFAULT 0.00,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`build_id`),
  FOREIGN KEY (`customer_id`) REFERENCES `customers`(`customer_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 14. 菜單裡面的詳細零件 (Build Items)
CREATE TABLE `build_items` (
  `build_item_id` int(11) NOT NULL AUTO_INCREMENT,
  `build_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`build_item_id`),
  FOREIGN KEY (`build_id`) REFERENCES `saved_builds`(`build_id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`product_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 15. 產品規格表 (Product Specifications) - [💡 进阶优化：增加了复合索引]
CREATE TABLE `product_specifications` (
  `spec_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `spec_name` varchar(100) NOT NULL,
  `spec_value` varchar(255) NOT NULL,
  PRIMARY KEY (`spec_id`),
  FOREIGN KEY (`product_id`) REFERENCES `products`(`product_id`) ON DELETE CASCADE,
  INDEX `idx_spec_search` (`spec_name`, `spec_value`) /* 💡 优化：大幅提升兼容性过滤的查询速度 */
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 重新开启外键检查
SET FOREIGN_KEY_CHECKS = 1;