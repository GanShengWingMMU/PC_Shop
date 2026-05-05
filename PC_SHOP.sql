-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 05, 2026 at 06:07 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `pcshop`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `admin_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` varchar(20) DEFAULT 'SuperAdmin',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `username`, `password`, `email`, `role`, `created_at`) VALUES
(1, 'superadmin', 'password', 'boss@pcshop.com', 'SuperAdmin', '2026-04-29 21:17:21');

-- --------------------------------------------------------

--
-- Table structure for table `bank`
--

CREATE TABLE `bank` (
  `id` int(11) NOT NULL,
  `bank_name` varchar(50) DEFAULT 'Maybank',
  `cardholder_name` varchar(100) NOT NULL,
  `card_number` varchar(16) NOT NULL,
  `cvc` varchar(3) NOT NULL,
  `fpx_username` varchar(100) DEFAULT NULL,
  `fpx_password` varchar(255) DEFAULT NULL,
  `balance` decimal(10,2) DEFAULT 50000.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bank`
--

INSERT INTO `bank` (`id`, `bank_name`, `cardholder_name`, `card_number`, `cvc`, `fpx_username`, `fpx_password`, `balance`) VALUES
(1, 'Maybank', 'Ali Bin Abu', '1111222233334444', '123', NULL, NULL, 8303.00),
(2, 'Maybank', 'Gan Sheng Wing', '9999888877776666', '999', 'ganshengwing', '123456', 99999.00),
(3, 'CIMB', 'Sheng Wing Gan', '5555666677778888', '888', 'gancimb', 'password123', 25000.00),
(4, 'CIMB', 'Sheng Wing Gan', '5555666677778888', '888', 'gancimb', 'password123', 25000.00),
(5, 'CIMB', 'Sheng Wing Gan', '5555666677778888', '888', 'gancimb', 'password123', 25000.00);

-- --------------------------------------------------------

--
-- Table structure for table `build_items`
--

CREATE TABLE `build_items` (
  `build_item_id` int(11) NOT NULL,
  `pc_build` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `build_items`
--

INSERT INTO `build_items` (`build_item_id`, `pc_build`, `product_id`, `quantity`) VALUES
(1, 1, 1, 1),
(2, 1, 4, 1),
(3, 1, 10, 1),
(4, 1, 13, 1),
(5, 1, 8, 1),
(6, 1, 16, 1),
(7, 1, 19, 1),
(8, 1, 18, 1),
(9, 5, 1, 1),
(10, 5, 4, 1),
(11, 5, 10, 1),
(12, 5, 13, 1),
(13, 5, 8, 1),
(14, 5, 16, 1),
(15, 5, 19, 1),
(16, 5, 18, 1),
(17, 6, 1, 1),
(18, 6, 4, 1),
(19, 6, 10, 1),
(20, 6, 13, 1),
(21, 6, 9, 1),
(22, 6, 17, 1),
(23, 6, 20, 1),
(24, 6, 18, 1),
(25, 7, 2, 1),
(26, 7, 4, 1),
(27, 7, 10, 1),
(28, 7, 14, 1),
(29, 7, 8, 1),
(30, 7, 16, 1),
(31, 7, 20, 1),
(32, 7, 18, 1),
(33, 8, 1, 1),
(34, 8, 4, 1),
(35, 8, 10, 1),
(36, 8, 13, 1),
(37, 8, 8, 1),
(38, 8, 16, 1),
(39, 8, 19, 1),
(40, 8, 18, 1),
(41, 9, 1, 1),
(42, 9, 4, 1),
(43, 9, 10, 1),
(44, 9, 13, 1),
(45, 9, 8, 1),
(46, 9, 16, 1),
(47, 9, 19, 1),
(48, 9, 18, 1),
(49, 10, 17, 1),
(50, 10, 1, 1),
(51, 10, 4, 1),
(52, 10, 11, 1),
(53, 10, 13, 1),
(54, 10, 8, 1),
(55, 10, 20, 1),
(56, 10, 18, 1),
(57, 11, 1, 1),
(58, 11, 4, 1),
(59, 11, 10, 1),
(60, 11, 13, 1),
(61, 11, 8, 1),
(62, 11, 16, 1),
(63, 11, 19, 1),
(64, 11, 18, 1),
(65, 12, 1, 1),
(66, 12, 4, 1),
(67, 12, 10, 1),
(68, 12, 13, 1),
(69, 12, 8, 1),
(70, 12, 16, 1),
(71, 12, 19, 1),
(72, 12, 18, 1),
(73, 13, 1, 1),
(74, 13, 4, 1),
(75, 13, 10, 1),
(76, 13, 13, 1),
(77, 13, 8, 1),
(78, 13, 16, 1),
(79, 13, 19, 1),
(80, 13, 18, 1),
(85, 14, 1, 1),
(86, 14, 4, 1),
(87, 14, 10, 1),
(88, 14, 13, 1),
(89, 14, 8, 1),
(90, 14, 16, 1),
(91, 14, 19, 1),
(92, 14, 18, 1),
(93, 15, 2, 1),
(94, 15, 5, 1),
(95, 15, 7, 1),
(96, 15, 11, 1),
(97, 15, 16, 1),
(98, 15, 18, 1),
(99, 15, 14, 1),
(100, 15, 19, 1),
(101, 15, 22, 1),
(102, 15, 23, 1),
(103, 15, 25, 1),
(104, 16, 1, 1),
(105, 16, 4, 1),
(106, 16, 10, 1),
(107, 16, 13, 1),
(108, 16, 8, 1),
(109, 16, 16, 1),
(110, 16, 19, 1),
(111, 16, 18, 1),
(112, 16, 21, 1),
(113, 16, 23, 1),
(114, 16, 25, 1),
(115, 17, 1, 1),
(116, 17, 4, 1),
(117, 17, 10, 1),
(118, 17, 13, 1),
(119, 17, 8, 1),
(120, 17, 16, 1),
(121, 17, 19, 1),
(122, 17, 18, 1),
(123, 17, 21, 1),
(124, 17, 23, 1),
(125, 17, 25, 1),
(126, 18, 1, 1),
(127, 18, 4, 1),
(128, 18, 10, 1),
(129, 18, 13, 1),
(130, 18, 8, 1),
(131, 18, 16, 1),
(132, 18, 19, 1),
(133, 18, 18, 1),
(134, 18, 21, 1),
(135, 18, 23, 1),
(136, 18, 26, 1),
(137, 19, 1, 1),
(138, 19, 4, 1),
(139, 19, 10, 1),
(140, 19, 13, 1),
(141, 19, 8, 1),
(142, 19, 16, 1),
(143, 19, 19, 1),
(144, 19, 18, 1),
(145, 19, 21, 1),
(146, 19, 23, 1),
(147, 19, 25, 1),
(148, 20, 1, 1),
(149, 20, 4, 1),
(150, 20, 10, 1),
(151, 20, 13, 1),
(152, 20, 8, 1),
(153, 20, 16, 1),
(154, 20, 19, 1),
(155, 20, 18, 1),
(156, 20, 21, 1),
(157, 20, 23, 1),
(158, 20, 25, 1),
(159, 21, 1, 1),
(160, 21, 4, 1),
(161, 21, 10, 1),
(162, 21, 13, 1),
(163, 21, 8, 1),
(164, 21, 16, 1),
(165, 21, 19, 1),
(166, 21, 18, 1),
(167, 21, 21, 1),
(168, 21, 23, 1),
(169, 21, 25, 1),
(170, 22, 1, 1),
(171, 22, 4, 1),
(172, 22, 10, 1),
(173, 22, 13, 1),
(174, 22, 8, 1),
(175, 22, 16, 1),
(176, 22, 19, 1),
(177, 22, 18, 1),
(178, 22, 21, 1),
(179, 22, 23, 1),
(180, 22, 25, 1);

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `category_name`, `description`) VALUES
(1, 'Processor (CPU)', 'The brain of the computer'),
(2, 'Motherboard', 'Main circuit board'),
(3, 'Memory (RAM)', 'Short-term data access'),
(4, 'Graphics Card (GPU)', 'Renders images and video'),
(5, 'Storage (SSD)', 'Long-term data storage'),
(6, 'Power Supply (PSU)', 'Provides power to components'),
(7, 'PC Case', 'Enclosure for components'),
(8, 'Cooling System', 'Keeps components cool'),
(9, 'Operating System', NULL),
(10, 'Case Fans', NULL),
(11, 'Monitor', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `community_comments`
--

CREATE TABLE `community_comments` (
  `comment_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `community_likes`
--

CREATE TABLE `community_likes` (
  `like_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `community_likes`
--

INSERT INTO `community_likes` (`like_id`, `post_id`, `customer_id`, `created_at`) VALUES
(2, 2, 5, '2026-05-02 22:31:33'),
(3, 1, 5, '2026-05-02 22:32:28'),
(2, 2, 5, '2026-05-02 22:31:33'),
(3, 1, 5, '2026-05-02 22:32:28'),
(2, 2, 5, '2026-05-02 22:31:33'),
(3, 1, 5, '2026-05-02 22:32:28');

-- --------------------------------------------------------

--
-- Table structure for table `community_posts`
--

CREATE TABLE `community_posts` (
  `post_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `pc_build_id` int(11) DEFAULT NULL COMMENT '如果是分享配置，则关联装机单',
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `post_type` enum('Showcase','Discussion','Question') DEFAULT 'Discussion',
  `views` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `community_posts`
--

INSERT INTO `community_posts` (`post_id`, `customer_id`, `pc_build_id`, `title`, `content`, `post_type`, `views`, `created_at`) VALUES
(1, 5, NULL, 'test', 'hello', 'Discussion', 0, '2026-05-01 22:36:47'),
(2, 5, 28, 'god', '。。。', 'Showcase', 0, '2026-05-02 22:31:12'),
(1, 5, NULL, 'test', 'hello', 'Discussion', 0, '2026-05-01 22:36:47'),
(2, 5, 28, 'god', '。。。', 'Showcase', 0, '2026-05-02 22:31:12'),
(1, 5, NULL, 'test', 'hello', 'Discussion', 0, '2026-05-01 22:36:47'),
(2, 5, 28, 'god', '。。。', 'Showcase', 0, '2026-05-02 22:31:12');

-- --------------------------------------------------------

--
-- Table structure for table `consultations`
--

CREATE TABLE `consultations` (
  `consultation_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `subject` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `status` varchar(20) DEFAULT 'Open',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `customer_id` int(11) NOT NULL,
  `username` varchar(150) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `wallet_balance` decimal(10,2) NOT NULL DEFAULT 0.00,
  `reward_coins` int(11) NOT NULL DEFAULT 0,
  `membership_tier` varchar(20) DEFAULT 'Standard',
  `vip_expiry_date` datetime DEFAULT NULL,
  `auto_renew` tinyint(1) DEFAULT 0,
  `default_shipping_address` text DEFAULT NULL,
  `account_status` varchar(20) DEFAULT 'Active',
  `reset_token` varchar(6) DEFAULT NULL,
  `reset_token_expire` datetime DEFAULT NULL,
  `pref_gamer` int(11) DEFAULT 0,
  `pref_creator` int(11) DEFAULT 0,
  `pref_student` int(11) DEFAULT 0,
  `pref_enthusiast` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`customer_id`, `username`, `email`, `password`, `phone_number`, `wallet_balance`, `reward_coins`, `membership_tier`, `vip_expiry_date`, `auto_renew`, `default_shipping_address`, `account_status`, `reset_token`, `reset_token_expire`, `pref_gamer`, `pref_creator`, `pref_student`, `pref_enthusiast`) VALUES
(1, 'Sheng Wing Gan', 'ganshengwing1126@gmail.com', '$2y$10$6Na3FQF8P0dNwtlqRJrf2u4YNNXIohV5YkSx/KBPJtzqAY3RFGldG', NULL, 99971490.29, 500, 'VIP', '2028-03-25 17:40:32', 0, NULL, 'Active', NULL, NULL, 0, 0, 0, 0),
(2, 'Sheng Gan', 'ganshengwing11226@gmail.com', '$2y$10$vW1.TGCWwWQMw8qP57pjjuoePphWACuonBYU6YnK6u/Kkvhd7bJ4a', NULL, 0.00, 0, 'Standard', NULL, 0, NULL, 'Active', NULL, NULL, 0, 0, 0, 0),
(3, 'Sheng Gan', 'ganshengwing1126@yahoo.com', '$2y$10$P2hmbbymdla9zNVO1rI4TO/4I4LcSUfDgSkBPHxkl79J3Rc9VEwgO', NULL, 0.00, 0, 'Standard', NULL, 0, NULL, 'Active', NULL, NULL, 0, 0, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `customer_addresses`
--

CREATE TABLE `customer_addresses` (
  `address_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `recipient_name` varchar(100) NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `address_line1` varchar(255) NOT NULL,
  `address_line2` varchar(255) DEFAULT NULL,
  `city` varchar(100) NOT NULL,
  `state` varchar(100) NOT NULL,
  `postcode` varchar(20) NOT NULL,
  `country` varchar(100) DEFAULT 'Malaysia',
  `full_address` text NOT NULL,
  `is_default` tinyint(1) DEFAULT 0 COMMENT '1=Default, 0=Normal',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer_addresses`
--

INSERT INTO `customer_addresses` (`address_id`, `customer_id`, `recipient_name`, `phone_number`, `address_line1`, `address_line2`, `city`, `state`, `postcode`, `country`, `full_address`, `is_default`, `created_at`) VALUES
(1, 1, '', '', '', NULL, '', '', '', 'Malaysia', 'No 123, Jalan Multimedia, 63100 Cyberjaya, Selangor', 0, '2026-04-09 13:23:19'),
(2, 1, '', '', '', NULL, '', '', '', 'Malaysia', 'Sheng Wing Gan | 0162058560\na0805, 205 Short Rd\n05602 Berlin, Johor', 1, '2026-04-09 17:17:21');

-- --------------------------------------------------------

--
-- Table structure for table `fpx_accounts`
--

CREATE TABLE `fpx_accounts` (
  `account_id` int(11) NOT NULL,
  `bank_name` varchar(50) NOT NULL COMMENT 'Maybank, CIMB, RHB, Public Bank',
  `username` varchar(100) NOT NULL COMMENT '網銀登入帳號',
  `password` varchar(255) NOT NULL COMMENT '網銀登入密碼',
  `balance` decimal(10,2) NOT NULL DEFAULT 10000.00 COMMENT '帳戶餘額'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fpx_accounts`
--

INSERT INTO `fpx_accounts` (`account_id`, `bank_name`, `username`, `password`, `balance`) VALUES
(1, 'Maybank', 'ganshengwing', '123456', 88888.00),
(2, 'CIMB Clicks', 'gancimb', '123456', 20000.00),
(3, 'RHB', 'shengwing_rhb', '123456', 50000.00);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `order_name` varchar(100) DEFAULT 'My Custom Order',
  `customer_id` int(11) NOT NULL,
  `order_date` datetime DEFAULT current_timestamp(),
  `total_amount` decimal(10,2) NOT NULL,
  `coins_used` int(11) NOT NULL DEFAULT 0,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `shipping_address` text NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `order_status` varchar(20) DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `order_name`, `customer_id`, `order_date`, `total_amount`, `coins_used`, `discount_amount`, `shipping_address`, `contact_number`, `order_status`) VALUES
(7, 'My Custom Order', 1, '2026-04-09 19:20:44', 21680.00, 0, 0.00, 'Sheng Wing Gan | 0162058560\\r\\na0805, 205 Short Rd\\r\\n05602 Berlin, Johor', '', 'Pending'),
(8, 'My Custom Order', 1, '2026-04-09 19:22:37', 0.00, 1000000888, 99999999.99, 'Sheng Wing Gan | 0162058560\\r\\na0805, 205 Short Rd\\r\\n05602 Berlin, Johor', '', 'Pending'),
(9, 'My Custom Order', 1, '2026-04-09 19:30:08', 6697.00, 0, 0.00, 'Sheng Wing Gan | 0162058560\\r\\na0805, 205 Short Rd\\r\\n05602 Berlin, Johor', '', 'Pending'),
(10, 'My Custom Order', 1, '2026-04-09 19:35:48', 6692.00, 55, 5.00, 'Sheng Wing Gan | 0162058560\\r\\na0805, 205 Short Rd\\r\\n05602 Berlin, Johor', '', 'Pending'),
(11, 's', 1, '2026-04-09 23:11:35', 6047.00, 0, 0.00, 'Sheng Wing Gan | 0162058560\\r\\na0805, 205 Short Rd\\r\\n05602 Berlin, Johor', '', 'Pending'),
(12, 'My Custom Order', 1, '2026-04-09 23:28:54', 950.00, 0, 0.00, 'Gan Sheng Wing | 012-3456789\nMMU Cyberjaya', '012-3456789', 'Completed'),
(13, 'My Custom Order', 1, '2026-04-11 17:26:52', 6697.00, 0, 0.00, 'Sheng Wing Gan | 0162058560\\r\\na0805, 205 Short Rd\\r\\n05602 Berlin, Johor', '', 'Pending'),
(14, 'My Custom Order', 1, '2026-04-11 17:41:34', 6697.00, 0, 0.00, 'Sheng Wing Gan | 0162058560\\r\\na0805, 205 Short Rd\\r\\n05602 Berlin, Johor', '', 'Pending'),
(15, 'My Custom Order', 1, '2026-04-19 23:30:46', 6697.00, 0, 0.00, 'Sheng Wing Gan | 0162058560\\r\\na0805, 205 Short Rd\\r\\n05602 Berlin, Johor', '', 'Pending');

-- --------------------------------------------------------

--
-- Table structure for table `order_details`
--

CREATE TABLE `order_details` (
  `order_detail_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `pc_build` int(11) DEFAULT NULL,
  `package_id` int(11) DEFAULT NULL,
  `affiliate_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_details`
--

INSERT INTO `order_details` (`order_detail_id`, `order_id`, `product_id`, `pc_build`, `package_id`, `affiliate_id`, `quantity`, `unit_price`) VALUES
(1, 7, NULL, 9, NULL, NULL, 1, 4620.00),
(2, 7, NULL, 10, NULL, NULL, 1, 7820.00),
(3, 7, NULL, 11, NULL, NULL, 1, 4620.00),
(4, 7, NULL, 13, NULL, NULL, 1, 4620.00),
(5, 8, NULL, 15, NULL, NULL, 1, 10677.00),
(6, 9, NULL, 16, NULL, NULL, 1, 6697.00),
(7, 10, NULL, 17, NULL, NULL, 1, 6697.00),
(8, 11, NULL, 18, NULL, NULL, 1, 6047.00),
(9, 12, 1, NULL, NULL, NULL, 1, 950.00),
(10, 13, NULL, 20, NULL, NULL, 1, 6697.00),
(11, 14, NULL, 21, NULL, NULL, 1, 6697.00),
(12, 15, NULL, 22, NULL, NULL, 1, 6697.00);

-- --------------------------------------------------------

--
-- Table structure for table `packages`
--

CREATE TABLE `packages` (
  `package_id` int(11) NOT NULL,
  `package_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `target_persona` enum('Gamer','Creator','Student','Enthusiast') NOT NULL,
  `stock_status` enum('Available','Out of Stock') DEFAULT 'Available',
  `score_gamer` int(11) DEFAULT 0,
  `score_creator` int(11) DEFAULT 0,
  `score_student` int(11) DEFAULT 0,
  `score_enthusiast` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `packages`
--

INSERT INTO `packages` (`package_id`, `package_name`, `description`, `price`, `image_url`, `target_persona`, `stock_status`, `score_gamer`, `score_creator`, `score_student`, `score_enthusiast`, `created_at`) VALUES
(1, 'Esports Predator V1', 'Intel i5-13400F + RTX 4060. The ultimate sweet spot for 1080p competitive gaming and esports titles like Valorant and CS2.', 3499.00, 'https://via.placeholder.com/300x200/ff007f/FFF?text=Esports+Predator', 'Gamer', 'Available', 9, 3, 1, 4, '2026-04-09 10:59:36'),
(2, 'Studio Pro Workstation', 'AMD Ryzen 9 7950X + 64GB DDR5 + RTX 4080. Built for 3D rendering, video editing, and heavy creative workloads.', 9899.00, 'https://via.placeholder.com/300x200/0078D4/FFF?text=Studio+Pro', 'Creator', 'Available', 7, 10, 0, 5, '2026-04-09 10:59:36'),
(3, 'Campus Starter Box', 'Intel Core i3-12100 + 16GB RAM + 512GB NVMe. Fast, reliable, and affordable. Perfect for assignments, web browsing, and media consumption.', 1599.00, 'https://via.placeholder.com/300x200/00e676/000?text=Campus+Starter', 'Student', 'Available', 1, 2, 10, 0, '2026-04-09 10:59:36'),
(4, 'Neon Liquid Beast', 'Intel Core i9-14900K + RTX 4090 + Custom Hard-Tube Liquid Cooling. For those who demand absolute perfection and maximum RGB aesthetics.', 18500.00, 'https://via.placeholder.com/300x200/8a2be2/FFF?text=Neon+Liquid+Beast', 'Enthusiast', 'Available', 10, 8, 0, 10, '2026-04-09 10:59:36');

-- --------------------------------------------------------

--
-- Table structure for table `package_items`
--

CREATE TABLE `package_items` (
  `id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `package_items`
--

INSERT INTO `package_items` (`id`, `package_id`, `product_id`) VALUES
(1, 1, 1),
(2, 1, 5),
(3, 1, 7),
(4, 1, 11),
(5, 1, 17),
(6, 1, 13),
(7, 1, 18),
(8, 1, 19),
(9, 2, 3),
(10, 2, 6),
(11, 2, 8),
(12, 2, 12),
(13, 2, 16),
(14, 2, 14),
(15, 2, 18),
(16, 2, 20),
(17, 3, 1),
(18, 3, 5),
(19, 3, 7),
(20, 3, 10),
(21, 3, 17),
(22, 3, 13),
(23, 3, 18),
(24, 3, 19),
(25, 4, 2),
(26, 4, 4),
(27, 4, 9),
(28, 4, 12),
(29, 4, 16),
(30, 4, 15),
(31, 4, 18),
(32, 4, 20);

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `payment_status` varchar(20) DEFAULT 'Unpaid',
  `transaction_date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `order_id`, `payment_method`, `reference_number`, `payment_status`, `transaction_date`) VALUES
(1, 7, 'E-Wallet', NULL, 'Paid', '2026-04-09 19:20:44'),
(2, 8, 'E-Wallet', NULL, 'Paid', '2026-04-09 19:22:37'),
(3, 9, 'Online Banking', NULL, 'Paid', '2026-04-09 19:30:08'),
(4, 10, 'E-Wallet', NULL, 'Paid', '2026-04-09 19:35:48'),
(5, 11, 'Online Banking (FPX)', NULL, 'Paid', '2026-04-09 23:11:35'),
(6, 13, 'Online Banking (FPX)', NULL, 'Paid', '2026-04-11 17:26:52'),
(7, 14, 'Visa ending in 4444', NULL, 'Paid', '2026-04-11 17:41:34'),
(8, 15, 'FPX - Maybank2U', NULL, 'Paid', '2026-04-19 23:30:46');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `image_url` varchar(255) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Available',
  `tdp_wattage` int(11) DEFAULT 0,
  `is_package` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `category_id`, `product_name`, `description`, `price`, `stock_quantity`, `image_url`, `status`, `tdp_wattage`, `is_package`) VALUES
(1, 1, 'Intel Core i5-13400F', 'Mainstream Intel Processor. Keyword: LGA1700', 950.00, 10, NULL, 'Available', 65, 0),
(2, 1, 'Intel Core i9-14900K', 'Enthusiast Intel Processor (High TDP). Keyword: LGA1700', 2800.00, 5, NULL, 'Available', 253, 0),
(3, 1, 'AMD Ryzen 5 7600X', 'Solid AMD Ryzen Processor. Keyword: AM5', 1100.00, 10, NULL, 'Available', 105, 0),
(4, 2, 'ASUS ROG STRIX Z790-F LGA1700 DDR5', 'High-end Intel board, supports DDR5 memory only.', 1450.00, 8, NULL, 'Available', 30, 0),
(5, 2, 'MSI PRO H610M-G LGA1700 DDR4', 'Budget Intel board, supports DDR4 memory only.', 450.00, 15, NULL, 'Available', 20, 0),
(6, 2, 'Gigabyte B650 AORUS ELITE AX AM5 DDR5', 'Premium AMD board, supports DDR5 memory only.', 1350.00, 5, NULL, 'Available', 30, 0),
(7, 3, 'Kingston Fury Beast 16GB DDR4 3200MHz', 'Reliable standard DDR4 memory module.', 200.00, 30, NULL, 'Available', 8, 0),
(8, 3, 'Corsair Vengeance 32GB DDR5 6000MHz', 'High-speed DDR5 memory module for gaming.', 650.00, 20, NULL, 'Available', 10, 0),
(9, 3, 'G.Skill Trident Z5 RGB 64GB DDR5', 'Enthusiast DDR5 memory kit for heavy workloads.', 1200.00, 10, NULL, 'Available', 15, 0),
(10, 4, 'NVIDIA GeForce GT 730 2GB', 'Basic display output only (Will cause severe bottleneck with high-end CPUs).', 250.00, 20, NULL, 'Available', 30, 0),
(11, 4, 'NVIDIA RTX 4070 SUPER 12GB', 'Sweet spot for 1440p gaming and rendering.', 3100.00, 10, NULL, 'Available', 220, 0),
(12, 4, 'NVIDIA RTX 4090 24GB', 'Ultimate flagship GPU (Requires massive power supply).', 8500.00, 2, NULL, 'Available', 450, 0),
(13, 6, 'Corsair CV550 550W', 'Entry-level power supply (550W).', 220.00, 15, NULL, 'Available', 550, 0),
(14, 6, 'FSP Hydro G Pro 850W', 'High-end gold certified power supply (850W).', 600.00, 10, NULL, 'Available', 850, 0),
(15, 6, 'ASUS ROG Thor 1200W', 'Platinum overkill power supply (1200W).', 1500.00, 3, NULL, 'Available', 1200, 0),
(16, 5, 'Samsung 990 PRO 1TB NVMe', 'Top-tier M.2 NVMe SSD.', 550.00, 15, NULL, 'Available', 5, 0),
(17, 5, 'WD Blue SN570 500GB NVMe', 'Budget-friendly fast storage.', 200.00, 25, NULL, 'Available', 5, 0),
(18, 7, 'NZXT H5 Flow Black', 'High airflow premium chassis.', 400.00, 10, NULL, 'Available', 0, 0),
(19, 8, 'Deepcool AK400 Air Cooler', 'Efficient standard air cooler.', 150.00, 20, NULL, 'Available', 0, 0),
(20, 8, 'NZXT Kraken 360 RGB AIO', 'Premium liquid cooler with LCD.', 850.00, 8, NULL, 'Available', 15, 0),
(21, 9, 'Microsoft Windows 11 Home 64-bit', 'Standard edition for gamers and home users. USB Flash Drive included.', 549.00, 0, 'https://via.placeholder.com/280x180/0078D4/FFF?text=Windows+11+Home', 'Available', 0, 0),
(22, 9, 'Microsoft Windows 11 Pro 64-bit', 'Advanced features for professionals and developers. BitLocker included.', 899.00, 0, 'https://via.placeholder.com/280x180/111/FFF?text=Windows+11+Pro', 'Available', 0, 0),
(23, 10, 'Corsair iCUE AR120 RGB 120mm (3-Pack)', 'High performance cooling fans with customizable RGB lighting sync.', 229.00, 0, 'https://via.placeholder.com/280x180/FF007F/FFF?text=Corsair+RGB+Fans', 'Available', 5, 0),
(24, 10, 'ARCTIC P12 PWM PST 120mm', 'Pressure-optimized quiet fan for excellent airflow and low noise.', 45.00, 0, 'https://via.placeholder.com/280x180/333/FFF?text=Arctic+P12', 'Available', 2, 0),
(25, 11, 'ASUS TUF Gaming VG27AQ 27\" 165Hz', '27-inch WQHD (2560x1440) IPS gaming monitor with ultrafast 165Hz refresh rate.', 1299.00, 0, 'https://via.placeholder.com/280x180/000/FFF?text=ASUS+TUF+27', 'Available', 0, 0),
(26, 11, 'AOC 24G2SP 24\" 165Hz IPS', '24-inch Full HD (1920x1080) gaming monitor, perfect for esports.', 649.00, 0, 'https://via.placeholder.com/280x180/ff0000/FFF?text=AOC+24G2', 'Available', 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `product_specifications`
--

CREATE TABLE `product_specifications` (
  `spec_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `spec_name` varchar(100) NOT NULL,
  `spec_value` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `rating` int(1) NOT NULL,
  `comment` text DEFAULT NULL,
  `review_date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`review_id`, `product_id`, `customer_id`, `rating`, `comment`, `review_date`) VALUES
(1, 1, 1, 5, 'henbang', '2026-04-09 23:29:14');

-- --------------------------------------------------------

--
-- Table structure for table `saved_builds`
--

CREATE TABLE `saved_builds` (
  `pc_build` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `build_name` varchar(100) DEFAULT 'My Custom PC',
  `total_price` decimal(10,2) DEFAULT 0.00,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `saved_builds`
--

INSERT INTO `saved_builds` (`pc_build`, `customer_id`, `build_name`, `total_price`, `created_at`) VALUES
(1, 1, 'Custom Gaming PC #3067', 4620.00, '2026-03-31 11:00:18'),
(2, 1, 'Custom Gaming PC #4617', 4620.00, '2026-03-31 11:05:24'),
(3, 1, 'Custom Gaming PC #8857', 4620.00, '2026-03-31 11:05:25'),
(4, 1, 'Custom Gaming PC #8777', 4620.00, '2026-03-31 11:05:25'),
(5, 1, 'Custom Gaming PC #5268', 4620.00, '2026-03-31 11:06:44'),
(6, 1, 'Custom Gaming PC #8232', 5520.00, '2026-03-31 11:07:14'),
(7, 1, 'Custom Gaming PC #7699', 7550.00, '2026-03-31 16:43:12'),
(8, 1, 'Custom Gaming PC #9227', 4620.00, '2026-03-31 17:08:29'),
(9, 1, 'Custom Gaming PC #1264', 4620.00, '2026-03-31 17:24:22'),
(10, 1, 'Custom Gaming PC #9158', 7820.00, '2026-04-04 17:49:41'),
(11, 1, 'strong man', 4620.00, '2026-04-04 18:52:47'),
(12, 1, '超級吊', 4620.00, '2026-04-05 14:39:39'),
(13, 1, 'My Custom Build', 4620.00, '2026-04-05 15:17:12'),
(14, 3, 'yeaaaaaaa', 4620.00, '2026-04-06 09:17:24'),
(15, 1, 'q', 10677.00, '2026-04-09 19:22:33'),
(16, 1, 'e', 6697.00, '2026-04-09 19:29:53'),
(17, 1, 'qw', 6697.00, '2026-04-09 19:30:46'),
(18, 1, 's', 6047.00, '2026-04-09 23:02:07'),
(19, 1, 'x', 6697.00, '2026-04-11 16:44:47'),
(20, 1, 'd', 6697.00, '2026-04-11 16:59:53'),
(21, 1, '4tgrrg', 6697.00, '2026-04-11 17:27:51'),
(22, 1, 'i', 6697.00, '2026-04-12 22:40:26');

-- --------------------------------------------------------

--
-- Table structure for table `saved_cards`
--

CREATE TABLE `saved_cards` (
  `card_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `bank_id` int(11) DEFAULT NULL,
  `cardholder_name` varchar(100) NOT NULL,
  `last_four_digits` varchar(4) NOT NULL,
  `expiry_date` varchar(5) NOT NULL,
  `card_brand` varchar(50) NOT NULL COMMENT 'Visa, Mastercard, etc.',
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `saved_cards`
--

INSERT INTO `saved_cards` (`card_id`, `customer_id`, `bank_id`, `cardholder_name`, `last_four_digits`, `expiry_date`, `card_brand`, `is_default`, `created_at`) VALUES
(1, 1, NULL, '22', '5353', '11/24', 'Credit Card', 0, '2026-04-09 23:11:21'),
(2, 1, NULL, '日人人日r', '2331', '22/11', 'Credit Card', 1, '2026-04-11 17:06:45');

-- --------------------------------------------------------

--
-- Table structure for table `shopping_cart`
--

CREATE TABLE `shopping_cart` (
  `cart_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `pc_build` int(11) DEFAULT NULL,
  `package_id` int(11) DEFAULT NULL,
  `affiliate_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `added_at` datetime DEFAULT current_timestamp()
) ;

--
-- Dumping data for table `shopping_cart`
--

INSERT INTO `shopping_cart` (`cart_id`, `customer_id`, `product_id`, `pc_build`, `package_id`, `affiliate_id`, `quantity`, `added_at`) VALUES
(9, 3, NULL, 14, NULL, NULL, 1, '2026-04-06 09:17:24'),
(19, 1, 4, NULL, NULL, NULL, 1, '2026-04-29 23:38:35'),
(20, 1, NULL, NULL, 4, NULL, 2, '2026-04-30 00:06:55'),
(21, 1, 3, NULL, NULL, NULL, 1, '2026-05-05 21:16:02');

-- --------------------------------------------------------

--
-- Table structure for table `wallet_transactions`
--

CREATE TABLE `wallet_transactions` (
  `transaction_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL COMMENT 'Top-up, Payment, Refund',
  `amount` decimal(10,2) NOT NULL,
  `coins_earned` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `wallet_transactions`
--

INSERT INTO `wallet_transactions` (`transaction_id`, `customer_id`, `type`, `amount`, `coins_earned`, `created_at`) VALUES
(1, 1, 'Top-up', 8888.00, 888, '2026-04-09 13:04:35'),
(2, 1, 'Top-up', 99999999.99, 1000000000, '2026-04-09 17:25:22'),
(3, 1, 'Payment', -21680.00, 0, '2026-04-09 19:20:44'),
(4, 1, 'Payment', 0.00, 0, '2026-04-09 19:22:37'),
(5, 1, 'Top-up', 50.00, 5, '2026-04-09 19:31:06'),
(6, 1, 'Top-up', 500.00, 50, '2026-04-09 19:31:24'),
(7, 1, 'Payment', -6692.00, 0, '2026-04-09 19:35:48'),
(8, 1, 'Payment', -29.90, 0, '2026-05-05 23:40:32'),
(9, 1, 'Payment', -29.90, 0, '2026-05-05 23:40:34'),
(10, 1, 'Payment', -29.90, 0, '2026-05-05 23:40:34'),
(11, 1, 'Payment', -29.90, 0, '2026-05-05 23:40:35'),
(12, 1, 'Payment', -29.90, 0, '2026-05-05 23:40:35'),
(13, 1, 'Payment', -29.90, 0, '2026-05-05 23:40:37'),
(14, 1, 'Payment', -29.90, 0, '2026-05-05 23:40:38'),
(15, 1, 'Payment', -29.90, 0, '2026-05-05 23:40:38'),
(16, 1, 'Payment', -29.90, 0, '2026-05-05 23:40:38'),
(17, 1, 'Payment', -29.90, 0, '2026-05-05 23:40:38'),
(18, 1, 'Payment', -29.90, 0, '2026-05-05 23:40:38'),
(19, 1, 'Payment', -29.90, 0, '2026-05-05 23:40:39'),
(20, 1, 'Payment', -29.90, 0, '2026-05-05 23:40:39'),
(21, 1, 'Payment', -29.90, 0, '2026-05-05 23:40:39'),
(22, 1, 'Payment', -29.90, 0, '2026-05-05 23:40:39'),
(23, 1, 'Payment', -29.90, 0, '2026-05-05 23:40:39'),
(24, 1, 'Payment', -29.90, 0, '2026-05-05 23:40:40'),
(25, 1, 'Payment', -29.90, 0, '2026-05-05 23:40:40'),
(26, 1, 'Payment', -29.90, 0, '2026-05-05 23:40:40'),
(27, 1, 'Payment', -29.90, 0, '2026-05-05 23:40:40'),
(28, 1, 'Payment', -29.90, 0, '2026-05-05 23:40:40'),
(29, 1, 'Payment', -29.90, 0, '2026-05-05 23:40:40'),
(30, 1, 'Payment', -29.90, 0, '2026-05-05 23:40:41');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `bank`
--
ALTER TABLE `bank`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `build_items`
--
ALTER TABLE `build_items`
  ADD PRIMARY KEY (`build_item_id`),
  ADD KEY `build_id` (`pc_build`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `consultations`
--
ALTER TABLE `consultations`
  ADD PRIMARY KEY (`consultation_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`customer_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `customer_addresses`
--
ALTER TABLE `customer_addresses`
  ADD PRIMARY KEY (`address_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `fpx_accounts`
--
ALTER TABLE `fpx_accounts`
  ADD PRIMARY KEY (`account_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `order_details`
--
ALTER TABLE `order_details`
  ADD PRIMARY KEY (`order_detail_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `fk_order_details_build` (`pc_build`),
  ADD KEY `fk_order_details_package` (`package_id`);

--
-- Indexes for table `packages`
--
ALTER TABLE `packages`
  ADD PRIMARY KEY (`package_id`);

--
-- Indexes for table `package_items`
--
ALTER TABLE `package_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `package_id` (`package_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `product_specifications`
--
ALTER TABLE `product_specifications`
  ADD PRIMARY KEY (`spec_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `idx_spec_search` (`spec_name`,`spec_value`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `saved_builds`
--
ALTER TABLE `saved_builds`
  ADD PRIMARY KEY (`pc_build`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `saved_cards`
--
ALTER TABLE `saved_cards`
  ADD PRIMARY KEY (`card_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `fk_saved_cards_bank` (`bank_id`);

--
-- Indexes for table `shopping_cart`
--
ALTER TABLE `shopping_cart`
  ADD PRIMARY KEY (`cart_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `fk_shopping_cart_pc_build` (`pc_build`),
  ADD KEY `fk_shopping_cart_package` (`package_id`);

--
-- Indexes for table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `bank`
--
ALTER TABLE `bank`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `build_items`
--
ALTER TABLE `build_items`
  MODIFY `build_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=181;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `consultations`
--
ALTER TABLE `consultations`
  MODIFY `consultation_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `customer_addresses`
--
ALTER TABLE `customer_addresses`
  MODIFY `address_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `fpx_accounts`
--
ALTER TABLE `fpx_accounts`
  MODIFY `account_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `order_details`
--
ALTER TABLE `order_details`
  MODIFY `order_detail_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `packages`
--
ALTER TABLE `packages`
  MODIFY `package_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `product_specifications`
--
ALTER TABLE `product_specifications`
  MODIFY `spec_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `saved_builds`
--
ALTER TABLE `saved_builds`
  MODIFY `pc_build` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `saved_cards`
--
ALTER TABLE `saved_cards`
  MODIFY `card_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `shopping_cart`
--
ALTER TABLE `shopping_cart`
  MODIFY `cart_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `build_items`
--
ALTER TABLE `build_items`
  ADD CONSTRAINT `build_items_ibfk_1` FOREIGN KEY (`pc_build`) REFERENCES `saved_builds` (`pc_build`) ON DELETE CASCADE,
  ADD CONSTRAINT `build_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `consultations`
--
ALTER TABLE `consultations`
  ADD CONSTRAINT `consultations_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE;

--
-- Constraints for table `customer_addresses`
--
ALTER TABLE `customer_addresses`
  ADD CONSTRAINT `customer_addresses_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE;

--
-- Constraints for table `order_details`
--
ALTER TABLE `order_details`
  ADD CONSTRAINT `fk_order_details_build` FOREIGN KEY (`pc_build`) REFERENCES `saved_builds` (`pc_build`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_order_details_package` FOREIGN KEY (`package_id`) REFERENCES `packages` (`package_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `order_details_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_details_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE SET NULL;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE CASCADE;

--
-- Constraints for table `product_specifications`
--
ALTER TABLE `product_specifications`
  ADD CONSTRAINT `product_specifications_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE;

--
-- Constraints for table `saved_builds`
--
ALTER TABLE `saved_builds`
  ADD CONSTRAINT `saved_builds_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE;

--
-- Constraints for table `saved_cards`
--
ALTER TABLE `saved_cards`
  ADD CONSTRAINT `fk_saved_cards_bank` FOREIGN KEY (`bank_id`) REFERENCES `bank` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `saved_cards_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE;

--
-- Constraints for table `shopping_cart`
--
ALTER TABLE `shopping_cart`
  ADD CONSTRAINT `fk_shopping_cart_package` FOREIGN KEY (`package_id`) REFERENCES `packages` (`package_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_shopping_cart_pc_build` FOREIGN KEY (`pc_build`) REFERENCES `saved_builds` (`pc_build`) ON DELETE CASCADE,
  ADD CONSTRAINT `shopping_cart_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `shopping_cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  ADD CONSTRAINT `wallet_transactions_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
