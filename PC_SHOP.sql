-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 08, 2026 at 02:54 PM
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
(1, 'superadmin', 'Superadmin@123', 'boss@pcshop.com', 'SuperAdmin', '2026-04-29 21:17:21'),
(4, 'admin', 'Admin@@12345', 'admin123@gmail.com', 'Admin', '2026-05-19 14:19:02');

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
(2, 'Maybank', 'Gan Sheng Wing', '9999888877776666', '999', NULL, NULL, 42300.00),
(3, 'Maybank', 'FPX User 1', '0000', '000', 'ganshengwing', '123456', 76601.00);

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
(180, 22, 25, 1),
(190, 24, 27, 1),
(191, 24, 37, 1),
(192, 24, 38, 1),
(193, 24, 46, 1),
(194, 24, 49, 1),
(195, 24, 53, 1),
(196, 24, 56, 1),
(197, 24, 60, 1),
(198, 24, 22, 1),
(199, 25, 27, 1),
(200, 25, 37, 1),
(201, 25, 38, 1),
(202, 25, 46, 1),
(203, 25, 49, 1),
(204, 25, 53, 1),
(205, 25, 56, 1),
(206, 25, 60, 1),
(207, 25, 22, 1),
(208, 25, 23, 1),
(209, 25, 26, 1),
(210, 26, 27, 1),
(211, 26, 33, 1),
(212, 26, 38, 1),
(213, 26, 47, 1),
(214, 26, 49, 1),
(215, 26, 52, 1),
(216, 26, 57, 1),
(217, 26, 61, 1),
(218, 26, 21, 1),
(219, 26, 23, 1),
(220, 26, 25, 1),
(221, 27, 27, 1),
(222, 27, 33, 1),
(223, 27, 38, 1),
(224, 27, 47, 1),
(225, 27, 49, 1),
(226, 27, 52, 1),
(227, 27, 57, 1),
(228, 27, 61, 1),
(229, 27, 21, 1),
(230, 28, 30, 1),
(231, 28, 36, 1),
(232, 28, 40, 1),
(233, 28, 43, 1),
(234, 28, 50, 1),
(235, 28, 54, 1),
(236, 28, 58, 1),
(237, 28, 61, 1),
(238, 28, 21, 1),
(239, 28, 23, 1),
(240, 28, 25, 1),
(241, 29, 30, 1),
(242, 29, 36, 1),
(243, 29, 40, 1),
(244, 29, 43, 1),
(245, 29, 50, 1),
(246, 29, 54, 1),
(247, 29, 58, 1),
(248, 29, 61, 1),
(249, 29, 21, 1),
(250, 29, 23, 1),
(251, 29, 25, 1),
(373, 41, 22, 1),
(374, 41, 23, 1),
(375, 41, 25, 1),
(376, 41, 32, 1),
(377, 41, 34, 1),
(378, 41, 41, 1),
(379, 41, 45, 1),
(380, 41, 51, 1),
(381, 41, 55, 1),
(382, 41, 56, 1),
(383, 41, 60, 1);

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
(5, 2, 6, '2026-05-18 01:55:38');

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
(3, 6, NULL, 'hi dear suhaimi', 'i just wanna say ur build is godlike ahahhaha', 'Discussion', 0, '2026-05-18 01:56:15');

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
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `birthday` date DEFAULT NULL,
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
  `pref_enthusiast` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`customer_id`, `username`, `first_name`, `last_name`, `email`, `password`, `phone_number`, `birthday`, `wallet_balance`, `reward_coins`, `membership_tier`, `vip_expiry_date`, `auto_renew`, `default_shipping_address`, `account_status`, `reset_token`, `reset_token_expire`, `pref_gamer`, `pref_creator`, `pref_student`, `pref_enthusiast`, `created_at`) VALUES
(1, 'Sheng Wing Gan', NULL, NULL, 'ganshengwing1126@gmail.com', '$2y$10$6Na3FQF8P0dNwtlqRJrf2u4YNNXIohV5YkSx/KBPJtzqAY3RFGldG', NULL, NULL, 99972177.99, 0, 'Standard', NULL, 0, NULL, 'Active', NULL, NULL, 0, 0, 0, 0, '2026-04-30 20:09:29'),
(3, 'Sheng Gan', NULL, NULL, 'ganshengwing1126@yahoo.com', '$2y$10$P2hmbbymdla9zNVO1rI4TO/4I4LcSUfDgSkBPHxkl79J3Rc9VEwgO', NULL, NULL, 0.00, 0, 'Standard', NULL, 0, NULL, 'Active', NULL, NULL, 0, 0, 0, 0, '2026-04-30 20:09:29'),
(5, 'MrSuhaimi', 'XUAN', 'YEOH', 'queit0126@gmail.com', '$2y$10$7xIGYUoYA838MBDwMys20.mgW.n0jcHAKOsGCgHOf2tnyq3iKa/xO', NULL, NULL, 100455.00, 10000, 'VIP', '2026-06-08 09:57:49', 1, NULL, 'Active', '242270', '2026-05-12 18:54:26', 7, 5, 10, 0, '2026-05-01 13:59:14'),
(6, 'kskbl', '何桥月光下', '奈', 'UIS292@gmail.com', '$2y$10$DfU8a04xIV3OhjZ.wZy5rOyFXBfivjKW8rijnqlMi.EcyUt93Pxcu', '+60122222620', '2025-11-17', 1000.00, 50, 'VIP', '2026-06-08 15:33:29', 0, NULL, 'Active', NULL, NULL, 27, 44, 13, 0, '2026-05-09 21:32:45'),
(7, 'XUANMING0126', NULL, NULL, 'chenweishen8733@gmail.com', '$2y$10$t1mb1tQakaIxjZZJG/2/RurpbpIpkGQ9mObmsvcM9AFz.I0ZskP3.', '', '2026-05-18', 0.00, 0, 'Standard', NULL, 0, NULL, 'Active', NULL, NULL, 0, 0, 0, 0, '2026-05-18 16:39:41'),
(8, 'Alvis', NULL, NULL, 'ocalvis88@gmail.com', '$2y$10$JHAUBkQ2sgoDKHebVWIvNe7uUqsr3XUVHonZzXlpWy83oOcnjen4W', '', '2005-10-07', 0.00, 0, 'Standard', NULL, 0, NULL, 'Active', NULL, NULL, 0, 0, 0, 0, '2026-05-21 22:15:47');

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
(2, 1, '', '', '', NULL, '', '', '', 'Malaysia', 'Sheng Wing Gan | 0162058560\na0805, 205 Short Rd\n05602 Berlin, Johor', 1, '2026-04-09 17:17:21'),
(4, 5, 'YEOH XUAN MING', '0122222620', '68,JALAN UTAMA28 TAMAN MUTIARA RINI', '', 'Johor Bahru', 'Johor', '81300', 'Malaysia', '68,JALAN UTAMA28 TAMAN MUTIARA RINI, 81300 Johor Bahru, Johor', 0, '2026-05-01 21:35:32'),
(5, 5, 'YYEYY', '0123456789', '58,Jalan Udara 22,Taman Universiti', '', 'perak', 'sembilan', '81365', 'Malaysia', '58,Jalan Udara 22,Taman Universiti, 81365 perak, sembilan', 1, '2026-05-01 21:39:12'),
(8, 6, 'YEOH XUAN MING', '+60122222620', '68,JALAN UTAMA28 TAMAN MUTIARA RINI', '', 'Johor Bahru', 'Johor', '81300', 'Malaysia', '68,JALAN UTAMA28 TAMAN MUTIARA RINI, 81300 Johor Bahru, Johor', 0, '2026-05-18 02:03:47'),
(9, 7, 'Sheng Wing Gan', '+60162058560', '205 Short Rd', '', 'Berlin', 'VT', '81300', 'Malaysia', '205 Short Rd, 81300 Berlin, VT', 1, '2026-05-18 16:41:57'),
(10, 8, 'Alvis', '+601158534889', '2812, Jalan Sri Putri 10/2', '', 'Kulai', 'Johor', '81000', 'Malaysia', '2812, Jalan Sri Putri 10/2, 81000 Kulai, Johor', 1, '2026-05-22 21:43:08');

-- --------------------------------------------------------

--
-- Table structure for table `fpx_accounts`
--

CREATE TABLE `fpx_accounts` (
  `account_id` int(11) NOT NULL,
  `bank_name` varchar(50) NOT NULL COMMENT 'Maybank, CIMB, RHB, Public Bank',
  `username` varchar(100) NOT NULL COMMENT '网银登录账号',
  `password` varchar(255) NOT NULL COMMENT '网银登录密码',
  `balance` decimal(10,2) NOT NULL DEFAULT 10000.00 COMMENT '账户余额'
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
  `contact_number` varchar(20) DEFAULT NULL,
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
(15, 'My Custom Order', 1, '2026-04-19 23:30:46', 6697.00, 0, 0.00, 'Sheng Wing Gan | 0162058560\\r\\na0805, 205 Short Rd\\r\\n05602 Berlin, Johor', '', 'Pending'),
(16, 'My Custom Order', 5, '2026-05-02 22:30:30', 74860.00, 0, 0.00, 'YYEYY | 0123456789\\r\\n58,Jalan Udara 22,Taman Universiti, 81365 perak, sembilan', '', 'Completed'),
(17, 'My Custom Order', 5, '2026-05-02 22:31:55', 6427.00, 0, 0.00, 'YYEYY | 0123456789\\r\\n58,Jalan Udara 22,Taman Universiti, 81365 perak, sembilan', '', 'Pending'),
(18, 'My Custom Order', 5, '2026-05-09 16:55:10', 10204.00, 550, 55.00, 'YYEYY | 0123456789\\r\\n58,Jalan Udara 22,Taman Universiti, 81365 perak, sembilan', '', 'Pending'),
(19, 'My Custom Order', 5, '2026-05-09 21:03:11', 45.00, 0, 0.00, 'YYEYY | 0123456789\\r\\n58,Jalan Udara 22,Taman Universiti, 81365 perak, sembilan', '', 'Pending'),
(20, 'My Custom Order', 6, '2026-05-17 23:43:50', 10727.00, 0, 0.00, 'YYEYY | 01233226201323232\n68,JALAN UTAMA28 TAMAN MUTIARA RINI, 81300 Johor Bahru, Johor', NULL, 'Pending'),
(21, 'My Custom Order', 6, '2026-05-18 00:13:55', 10727.00, 0, 0.00, 'YYEYY | 01233226201323232\n68,JALAN UTAMA28 TAMAN MUTIARA RINI, 81300 Johor Bahru, Johor', NULL, 'Processing'),
(22, 'My Custom Order', 6, '2026-05-18 00:28:23', 10672.00, 550, 55.00, 'YYEYY | 01233226201323232\n68,JALAN UTAMA28 TAMAN MUTIARA RINI, 81300 Johor Bahru, Johor', NULL, 'Shipped'),
(23, 'My Custom Order', 6, '2026-05-18 00:31:22', 11787.00, 0, 0.00, 'YYEYY | 01233226201323232\n68,JALAN UTAMA28 TAMAN MUTIARA RINI, 81300 Johor Bahru, Johor', NULL, 'Processing'),
(24, 'My Custom Order', 5, '2026-05-18 16:45:44', 33750.00, 0, 0.00, 'YYEYY | 0123456789\n58,Jalan Udara 22,Taman Universiti, 81365 perak, sembilan', NULL, 'Shipped'),
(25, 'My Custom Order', 8, '2026-05-22 21:44:38', 2550.00, 0, 0.00, 'Alvis | +601158534889\n2812, Jalan Sri Putri 10/2, 81000 Kulai, Johor', NULL, 'Completed');

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
(12, 15, NULL, 22, NULL, NULL, 1, 6697.00),
(13, 16, NULL, NULL, 16, NULL, 2, 14419.00),
(14, 16, NULL, NULL, 14, NULL, 1, 14729.00),
(15, 16, NULL, 26, NULL, NULL, 1, 11787.00),
(16, 16, NULL, NULL, 3, NULL, 1, 2820.00),
(17, 16, NULL, NULL, 15, NULL, 1, 10259.00),
(18, 16, NULL, 29, NULL, NULL, 1, 6427.00),
(19, 17, NULL, 28, NULL, NULL, 1, 6427.00),
(20, 18, NULL, NULL, 15, NULL, 1, 10259.00),
(21, 19, 24, NULL, NULL, NULL, 1, 45.00),
(22, 20, NULL, NULL, NULL, NULL, 1, 10727.00),
(23, 21, NULL, NULL, NULL, NULL, 1, 10727.00),
(24, 22, NULL, NULL, NULL, NULL, 1, 10727.00),
(25, 23, NULL, NULL, NULL, NULL, 1, 11787.00),
(26, 24, 48, NULL, NULL, NULL, 25, 1350.00),
(27, 25, 44, NULL, NULL, NULL, 1, 2550.00);

-- --------------------------------------------------------

--
-- Table structure for table `packages`
--

CREATE TABLE `packages` (
  `package_id` int(11) NOT NULL,
  `package_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image_url` longtext DEFAULT NULL,
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
(1, 'Esports Predator V1', 'Intel i5-13400F + RTX 4060. The ultimate sweet spot for 1080p competitive gaming and esports titles like Valorant and CS2.', 0.00, 'image/pkg_6a26b69ebfb83.png', 'Gamer', 'Available', 9, 3, 1, 4, '2026-04-09 10:59:36'),
(3, 'Campus Starter Box', 'Intel Core i3-12100 + 16GB RAM + 512GB NVMe. Fast, reliable, and affordable. Perfect for assignments, web browsing, and media consumption.', 0.00, 'image/pkg_6a26b698c725e.png', 'Student', 'Available', 1, 2, 10, 0, '2026-04-09 10:59:36'),
(4, 'Neon Liquid Beast', 'Intel Core i9-14900K + RTX 4090 + Custom Hard-Tube Liquid Cooling. For those who demand absolute perfection and maximum RGB aesthetics.', 0.00, 'image/pkg_6a26b690bf805.png', 'Enthusiast', 'Available', 10, 8, 0, 10, '2026-04-09 10:59:36'),
(7, 'AMD Sweet Spot 1440p', 'Ryzen 7800X3D + RX 7800 XT. The absolute most cost-effective 1440p high-refresh-rate gaming machine available today.', 0.00, 'image/pkg_6a26b687232a5.png', 'Gamer', 'Available', 10, 4, 1, 5, '2026-04-30 11:22:59'),
(8, 'The 4K Juggernaut', 'Intel i7-14700K + RTX 4080 SUPER. Zero compromises. Built inside the gorgeous Lian Li O11 Dynamic.', 0.00, 'image/pkg_6a26b67ec1402.png', 'Enthusiast', 'Available', 9, 8, 1, 10, '2026-04-30 11:22:59'),
(9, 'Video Editor Pro Mac-Killer', 'Intel i7-14700K + 64GB DDR5 + RTX 4060 Ti + 2TB Gen4 SSD. Optimized entirely for Adobe Premiere and After Effects.', 0.00, 'image/pkg_6a26b6775b696.png', 'Creator', 'Available', 5, 10, 2, 6, '2026-04-30 11:22:59'),
(10, 'Campus Budget Brawler', 'Ryzen 7600 + 32GB RAM + 1TB SSD. Powerful enough for coding, multitasking, and light 1080p eSports gaming on a tight budget.', 0.00, 'image/pkg_6a26b66d33484.png', 'Student', 'Available', 5, 3, 10, 0, '2026-04-30 11:22:59'),
(11, 'NVIDIA 1080p Ultra Rig', 'Intel i5-12400F + RTX 4060 Ti + 16GB RAM. Max out every setting at 1080p with DLSS 3 Frame Gen support.', 0.00, 'image/pkg_6a26b665349a3.png', 'Gamer', 'Available', 8, 4, 5, 2, '2026-04-30 11:22:59'),
(12, 'Red Team Flagship', 'Ryzen 7800X3D + RX 7900 XTX 24GB + 360mm AIO. Unadulterated rasterization power designed to destroy 4K gaming.', 0.00, 'image/pkg_6a26b6582bee3.png', 'Enthusiast', 'Available', 10, 6, 1, 9, '2026-04-30 11:22:59'),
(13, 'Entry Code Compiler', 'Intel i5-12400F + RX 7600 + 32GB RAM. A highly efficient machine focused on RAM and fast storage for programming.', 0.00, 'image/pkg_6a26b6503331a.png', 'Student', 'Available', 4, 3, 9, 0, '2026-04-30 11:22:59'),
(14, 'Studio 3D Renderer', 'Ryzen 9 7950X + 64GB RAM + RTX 4070 Ti SUPER. 16 massive cores designed specifically for Blender and Cinema4D.', 0.00, 'image/pkg_6a26b647d53f6.png', 'Creator', 'Available', 7, 10, 1, 7, '2026-04-30 11:22:59'),
(15, 'Esports 360Hz Champion', 'Ryzen 7800X3D + RTX 4070 Ti SUPER. Built to push 300+ FPS in CS2, Valorant, and Apex Legends.', 0.00, 'image/pkg_6a26b63ee8b49.png', 'Gamer', 'Available', 9, 5, 1, 6, '2026-04-30 11:22:59'),
(16, 'Liquid Nitrogen Concept', 'Intel i9-13900K + RTX 4080 SUPER + 4TB SSD + Titanium PSU. The peak of PC building excess.', 0.00, 'image/pkg_6a26b636477a7.png', 'Enthusiast', 'Available', 9, 9, 0, 10, '2026-04-30 11:22:59');

-- --------------------------------------------------------

--
-- Table structure for table `package_items`
--

CREATE TABLE `package_items` (
  `id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `package_items`
--

INSERT INTO `package_items` (`id`, `package_id`, `product_id`, `quantity`) VALUES
(264, 16, 32, 1),
(265, 16, 34, 1),
(266, 16, 41, 1),
(267, 16, 45, 1),
(268, 16, 51, 1),
(269, 16, 55, 1),
(270, 16, 56, 1),
(271, 16, 60, 1),
(272, 16, 22, 1),
(273, 15, 27, 1),
(274, 15, 33, 1),
(275, 15, 38, 1),
(276, 15, 47, 1),
(277, 15, 49, 1),
(278, 15, 52, 1),
(279, 15, 57, 1),
(280, 15, 61, 1),
(281, 15, 21, 1),
(282, 14, 31, 1),
(283, 14, 37, 1),
(284, 14, 39, 1),
(285, 14, 47, 1),
(286, 14, 49, 1),
(287, 14, 53, 1),
(288, 14, 57, 1),
(289, 14, 60, 1),
(290, 14, 22, 1),
(291, 13, 30, 1),
(292, 13, 36, 1),
(293, 13, 40, 1),
(294, 13, 48, 1),
(295, 13, 50, 1),
(296, 13, 54, 1),
(297, 13, 58, 1),
(298, 13, 59, 1),
(299, 13, 21, 1),
(300, 12, 27, 1),
(301, 12, 37, 1),
(302, 12, 38, 1),
(303, 12, 46, 1),
(304, 12, 49, 1),
(305, 12, 53, 1),
(306, 12, 56, 1),
(307, 12, 60, 1),
(308, 12, 22, 1),
(309, 11, 30, 1),
(310, 11, 36, 1),
(311, 11, 40, 1),
(312, 11, 43, 1),
(313, 11, 50, 1),
(314, 11, 54, 1),
(315, 11, 58, 1),
(316, 11, 61, 1),
(317, 11, 21, 1),
(318, 10, 29, 1),
(319, 10, 33, 1),
(320, 10, 38, 1),
(321, 10, 48, 1),
(322, 10, 50, 1),
(323, 10, 54, 1),
(324, 10, 58, 1),
(325, 10, 59, 1),
(326, 10, 21, 1),
(327, 9, 28, 1),
(328, 9, 36, 1),
(329, 9, 39, 1),
(330, 9, 43, 1),
(331, 9, 49, 1),
(332, 9, 52, 1),
(333, 9, 57, 1),
(334, 9, 60, 1),
(335, 9, 22, 1),
(336, 8, 28, 1),
(337, 8, 34, 1),
(338, 8, 38, 1),
(339, 8, 45, 1),
(340, 8, 49, 1),
(341, 8, 53, 1),
(342, 8, 56, 1),
(343, 8, 60, 1),
(344, 8, 22, 1),
(345, 7, 27, 1),
(346, 7, 33, 1),
(347, 7, 38, 1),
(348, 7, 44, 1),
(349, 7, 49, 1),
(350, 7, 52, 1),
(351, 7, 57, 1),
(352, 7, 59, 1),
(353, 7, 21, 1),
(354, 4, 2, 1),
(355, 4, 4, 1),
(356, 4, 9, 1),
(357, 4, 12, 1),
(358, 4, 16, 1),
(359, 4, 15, 1),
(360, 4, 18, 1),
(361, 4, 20, 1),
(362, 3, 1, 1),
(363, 3, 5, 1),
(364, 3, 7, 1),
(365, 3, 10, 1),
(366, 3, 17, 1),
(367, 3, 13, 1),
(368, 3, 18, 1),
(369, 3, 19, 1),
(370, 1, 1, 1),
(371, 1, 5, 1),
(372, 1, 7, 1),
(373, 1, 11, 1),
(374, 1, 17, 1),
(375, 1, 13, 1),
(376, 1, 18, 1),
(377, 1, 19, 1);

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
(8, 15, 'FPX - Maybank2U', NULL, 'Paid', '2026-04-19 23:30:46'),
(9, 16, 'FPX - RHB Now', NULL, 'Paid', '2026-05-02 22:30:30'),
(10, 17, 'FPX - Maybank2U', NULL, 'Paid', '2026-05-02 22:31:55'),
(11, 18, 'Cash on Delivery', NULL, 'Pending', '2026-05-09 16:55:10'),
(12, 19, 'E-Wallet', NULL, 'Paid', '2026-05-09 21:03:11'),
(13, 20, 'Cash on Delivery', NULL, 'Pending', '2026-05-17 23:43:50'),
(14, 21, 'Visa ending in 6666', NULL, 'Paid', '2026-05-18 00:13:55'),
(15, 22, 'Credit Card ending in 6666', NULL, 'Paid', '2026-05-18 00:28:23'),
(16, 23, 'FPX - Maybank2U', NULL, 'Paid', '2026-05-18 00:31:22'),
(17, 24, 'Visa ending in 6666', NULL, 'Paid', '2026-05-18 16:45:44'),
(18, 25, 'Visa ending in 6666', NULL, 'Paid', '2026-05-22 21:44:38');

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
  `specifications` text DEFAULT NULL,
  `image_url` longtext DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Available',
  `tdp_wattage` int(11) DEFAULT 0,
  `is_package` tinyint(1) DEFAULT 0,
  `socket_type` varchar(20) DEFAULT NULL COMMENT 'For CPU and Motherboard (e.g., LGA1700, AM5)',
  `ram_type` varchar(20) DEFAULT NULL COMMENT 'For Motherboard and RAM (e.g., DDR4, DDR5)',
  `performance_tier` int(2) DEFAULT 0 COMMENT '1-10 scale for AI Bottleneck calculation'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `category_id`, `product_name`, `description`, `price`, `stock_quantity`, `specifications`, `image_url`, `status`, `tdp_wattage`, `is_package`, `socket_type`, `ram_type`, `performance_tier`) VALUES
(1, 1, 'Intel Core i5-13400F', 'Mainstream Intel Processor. Keyword: LGA1700', 950.00, 10, 'Architecture: Raptor Lake\r\nSocket: LGA 1700 (compatible with 600 and 700 series chipsets)\r\nClock Speeds: Up to 4.6 GHz Max Turbo\r\nCache: 20 MB Intel Smart Cache\r\nMemory Support: Supports both DDR4 and DDR5 RAM\r\nPower: 65W Processor Base Power', 'image/prod_6a26ba76b793d.jpg', 'Available', 65, 0, 'LGA1700', '', 5),
(2, 1, 'Intel Core i9-14900K', 'Enthusiast Intel Processor (High TDP). Keyword: LGA1700', 2800.00, 5, 'Total Cores: 24 (8 P-cores, 16 E-cores)\r\nTotal Threads: 32\r\nMax Turbo Frequency: Up to 6.0 GHz\r\nP-Core Base / Max Frequency: 3.2 GHz / 5.6 GHz\r\nE-Core Base / Max Frequency: 2.4 GHz / 4.4 GHz\r\nCache: 36 MB Intel Smart Cache (32 MB L2)\r\nProcessor Base Power: 125 W\r\nMaximum Turbo Power: 253 W\r\nLithography: Intel 7 (10 nm)\r\nSocket: LGA 1700\r\nMemory Support: Up to 192 GB DDR5 5600 MT/s or DDR4 3200 MT/s\r\nntegrated Graphics: Intel UHD Graphics 770', 'image/prod_6a26ba590e38e.jpg', 'Available', 253, 0, 'LGA1700', '', 10),
(3, 1, 'AMD Ryzen 5 7600X', 'Solid AMD Ryzen Processor. Keyword: AM5', 1100.00, 7, 'ArchitectureZen 4 (TSMC 5nm)\r\nCPU Cores / Threads6 Cores / 12 Threads\r\nClock Speeds4.7 GHz Base / Up to 5.3 GHz Boost\r\nCache384 KB (L1), 6 MB (L2), 32 MB (L3)\r\nSocket Type   AM5\r\nTDP (Power) 105W\r\nMemory Support DDR5\r\nIntegrated Graphics AMD Radeon Graphics (RDNA 2)\r\nOverclockingFully Unlocked\r\nMax. Temperature 95°C', 'image/prod_6a26ba3344a68.jpg', 'Available', 105, 0, 'AM5', '', 6),
(4, 2, 'ASUS ROG STRIX Z790-F LGA1700 DDR5', 'High-end Intel board, supports DDR5 memory only.', 1450.00, 8, 'CPU SocketIntel® LGA 1700 • Supports 14th, 13th, & 12th Gen Intel® Core™, Pentium® Gold, and Celeron® Processors\r\nMemory (RAM)4 x DIMM slots, Max. 192GB, DDR5 • Speeds up to 7800+ MT/s (OC) or up to 8000+ MT/s (OC) for the WiFi II edition • Dual-Channel & AEMP II support for optimized tuning\r\nExpansion Slots• 1 x PCIe 5.0 x16 SafeSlot (supports x16) • 2 x PCIe 4.0 x16 slots (supports x4) • 1 x PCIe 3.0 x1 slot\r\nStorage4 x M.2 slots & 4 x SATA 6Gb/s ports • M.2 slots support PCIe 4.0 x4\r\nNetworking• Intel® Wi-Fi 6E (WiFi 7 on the WiFi II model) & Bluetooth® 5.2 • Intel® 2.5Gb Ethernet with ASUS LANGuard\r\nAudioROG SupremeFX 7.1 Surround Sound High Definition Audio CODEC ALC4080 • Includes Savitech SV3H712 AMP for headset support\r\nPower Design16+1+2 power stages (90A) / 18+1+2 power stages for WiFi II\r\nUSB PortsRear I/O: USB 3.2 Gen 2x2 (Type-C), multiple USB 3.2 Gen 2 and Gen 1 ports • Front panel connectors feature PD 3.0 up to 30W', 'image/prod_6a26ba2a01f63.png', 'Available', 30, 0, 'LGA1700', 'DDR5', 4),
(5, 2, 'MSI PRO H610M-G LGA1700 DDR4', 'Budget Intel board, supports DDR4 memory only.', 450.00, 15, 'Brand	msi\r\nCPU Socket	LGA 1700\r\nCompatible Devices	Personal Computer\r\nRAM Memory Technology	DDR4\r\nCompatible Processors	Support Intel® Core™ 14th/ 13th/ 12th Gen Processors, Intel® Pentium® Gold and Celeron® Processors\r\nChipset Type	Intel H610\r\nMemory Clock Speed	3200 MHz\r\nPlatform	Windows 10, Windows 11\r\nModel Name	PRO\r\nCPU Model	Pentium', 'image/prod_6a26ba1cab01c.jpg', 'Available', 20, 0, 'LGA1700', 'DDR4', 9),
(6, 2, 'Gigabyte B650 AORUS ELITE AX AM5 DDR5', 'Premium AMD board, supports DDR5 memory only.', 1350.00, 5, 'Dimensions & Weight / Depth	24.4 cm\r\nDimensions & Weight / Width	30.5 cm\r\nHeader / Brand	GIGABYTE\r\nMainboard / Chipset Type	AMD B650\r\nMainboard / Compatible Processors	Ryzen\r\nMainboard / Processor Socket	Socket AM5\r\nNetworking / Data Link Protocol	\r\n2.5 Gigabit Ethernet\r\nBluetooth 5.3\r\nEthernet\r\nFast Ethernet\r\nGigabit Ethernet\r\nIEEE 802.11a\r\nIEEE 802.11ac\r\nIEEE 802.11ax (Wi-Fi 6E)\r\nIEEE 802.11b\r\nIEEE 802.11g\r\nIEEE 802.11n\r\nPackaged Quantity	1\r\nVideo Output / Type	Graphics adapter (CPU required)', 'image/prod_6a26ba13d092e.png', 'Available', 30, 0, 'AM5', 'DDR5', 7),
(7, 3, 'Kingston Fury Beast 16GB DDR4 3200MHz', 'Reliable standard DDR4 memory module.', 200.00, 30, 'Capacity: 16GB\r\nMemory Type: DDR4 UDIMM\r\nTested Speed: 3200MT/s \r\nTested Latency: CL16 \r\nVoltage: 1.35V for the overclocked 3200MHz profile\r\nDefault Profile: JEDEC standard DDR4-2400 at 1.2V\r\nHeatsink: Stylish, low-profile black heat spreader\r\nLighting: Available in Dynamic RGB versions', 'image/prod_6a26ba0b6e2d9.webp', 'Available', 8, 0, '', 'DDR4', 5),
(8, 3, 'Corsair Vengeance 32GB DDR5 6000MHz', 'High-speed DDR5 memory module for gaming.', 650.00, 17, 'Capacity: 32GB (2 × 16GB DIMMs)\r\nSpeed: 6000 MT/s (PC5-48000)\r\nTested Latency: CL30\r\nTested Voltage: 1.40V\r\nProfile Support: Includes Intel XMP 3.0 and AMD EXPO for easy, stable memory overclocking\r\nForm Factor: 288-pin Desktop DIMM\r\nCooling: Solid aluminum heat spreader available in various colors', 'image/prod_6a26ba00c867e.jpg', 'Available', 10, 0, '', 'DDR5', 7),
(9, 3, 'G.Skill Trident Z5 RGB 64GB DDR5', 'Enthusiast DDR5 memory kit for heavy workloads.', 1200.00, 10, 'Capacity: 64GB (2x32GB) dual-channel kit\r\nMemory Type: DDR5 Unbuffered DIMM\r\nTested Speeds & Latencies:6000 MT/s: CL36 (36-36-36-96) at 1.35V or CL30 (30-40-40-96)6400 MT/s: CL32 (32-39-39-102) at 1.40V\r\nSPD Specs (Default): 4800 MT/s at 1.10V\r\nError Checking (ECC): Non-ECC\r\nLighting Control: Compatible with G.Skill\'s lighting control software and third-party motherboard sync (e.g., Asus Aura Sync, MSI Mystic Light).\r\nProfile Support: Optimized for Intel XMP 3.0 (Extreme Memory Profile); select models also feature AMD EXPO support.', 'image/prod_6a26b9f584806.jpg', 'Available', 15, 0, '', 'DDR5', 5),
(10, 4, 'NVIDIA GeForce GT 730 2GB', 'Basic display output only (Will cause severe bottleneck with high-end CPUs).', 250.00, 20, 'GPU Clock~902 MHz~700 MHz\r\nMemory Size 2GB 2GB\r\nMemory Type GDDR5   DDR3\r\nMemory Bus64-bit  128-bit\r\nCUDA Cores384 384\r\nPower Draw (TDP)38 W  49 W', 'image/prod_6a26b9e805036.jpg', 'Available', 30, 0, '', '', 1),
(11, 4, 'NVIDIA RTX 4070 SUPER 12GB', 'Sweet spot for 1440p gaming and rendering.', 3100.00, 7, 'ArchitectureNVIDIA Ada Lovelace\r\nCUDA Cores7,168\r\nBoost Clock~2,475 MHz (varies by board partner)\r\nMemory Size12 GB GDDR6X\r\nMemory Bus192-bit\r\nMemory Speed21 Gbps (1313 MHz)\r\nMemory Bandwidth504 GB/s\r\nL2 Cache48 MB\r\nTDP (Power Draw)220 W (650W PSU recommended)\r\nPower Connectors1 × 16-pin (or 2 × 8-pin adapter included)\r\nRay Tracing Cores56 (3rd Generation)\r\nTensor Cores224 (4th Generation)\r\nMax Display Resolution7680 × 4320 (8K)', 'image/prod_6a26b9da3bc38.jpg', 'Available', 220, 0, '', '', 7),
(12, 4, 'NVIDIA RTX 4090 24GB', 'Ultimate flagship GPU (Requires massive power supply).', 8500.00, 2, 'NVIDIA CUDA® Cores: 16384\r\nStandard Memory Config 24 GB GDDR6X\r\nMemory Interface Width 384-bit\r\nBoost Clock (GHz): 2.52\r\nBase Clock (GHz): 2.23\r\nNVIDIA Architecture: Ada Lovelace', 'image/prod_6a26b9cf5edf3.jpg', 'Available', 450, 0, '', '', 10),
(13, 6, 'Corsair CV550 550W', 'Entry-level power supply (550W).', 220.00, 15, 'Dimensions & Weight / Depth	12.5 cm\r\nDimensions & Weight / Height	8.6 cm\r\nDimensions & Weight / Weight	1.87 kg\r\nDimensions & Weight / Width	15 cm\r\nGeneral / Subcategory	Power supplies\r\nHeader / Brand	CORSAIR\r\nHeader / Country Kits	United States\r\nMiscellaneous / Product Color	Black\r\nPackaged Quantity	1\r\nPower Device / Form Factor	Internal\r\nPower Device / Power Supply Compatibility	PC/Server\r\nPower Device / Voltage Required	AC 100-240 V', 'image/prod_6a26b9c688eea.jpg', 'Available', 550, 0, '', '', 7),
(14, 6, 'FSP Hydro G Pro 850W', 'High-end gold certified power supply (850W).', 600.00, 10, 'Rated Output Power	850W\r\nForm Factor	ATX\r\n80 PLUS Certification	Gold\r\nInput Voltage	100-240Vac\r\nInput Current	11-5.5A\r\nInput Frequency	50-60Hz\r\nPFC	Active PFC\r\nEfficiency	≥90% at typical load\r\nFan Type	FDB Fan, 135 mm\r\nDimensions(L x W x H)	150 x 150 x 86 mm\r\nOperation Temp.	0-50℃\r\nProtection	OCP, OVP, OPP, SCP, OTP', 'image/prod_6a26b9bd64091.jpg', 'Available', 850, 0, '', '', 7),
(15, 6, 'ASUS ROG Thor 1200W', 'Platinum overkill power supply (1200W).', 1500.00, 3, 'Dimensions 19 x 15 x 8.6 Centimeter\r\nEfficiency 80Plus Platinum\r\nMTBF >120,000 hrs @ 25°C\r\nProtection Features OPP/OVP/UVP/SCP/OCP/OTP\r\nHazardous Materials ROHS\r\nAC Input Range 100-240Vac\r\nRGB Connector Aura Sync\r\nDC Output Voltage +3.3V	+5V	+12V	-12V	+5Vsb\r\nMaximum Load 25A	25A	100A	0.3A	3A\r\nTotal Output 1200W\r\n\r\nCable Connectors\r\nMB 24/20-pin x1\r\nCPU 8/4-pin x2\r\nPCIE 8/6-pin x8\r\nSATA x12\r\nPeripheral 4-pin x5\r\nFloppy x1\r\n\r\nPackage Contents\r\nPower Cable x1\r\nMotherboard Power Cable x1 (610mm)\r\nCPU Cable x2 (650mm)\r\nPCI-E 1-to-1 Cable x4 (675mm)\r\nPCI-E 1-to-2 Cable x2 (675+75mm)\r\nSATA 1-to-4 Cable x2 (400+120+120+120mm)\r\nSATA 1-to-4 Cable x1 (350+150+150+150mm)\r\nPeripheral 1-to-2 Cable x1 (350+120mm)\r\nPeripheral 1-to-3 Cable x1 (450+120+120mm)\r\nSATA to Peripheral Cable x1 (150+150mm)\r\nFloppy Cable x1 (101mm)\r\nAddressable RGB Cable x2 (800mm)\r\nROG sticker x 1        \r\nROG cable tie x 6        \r\nSleeved Cable Combs (6-pin) x 4\r\nSleeved Cable Combs (8-pin) x 10\r\nSleeved Cable Combs (24-pin) x 2\r\nChassis Screws Package x 1\r\nCable Tie x 12\r\nUser Manual x 1\r\n\r\nAURA SYNC\r\nARGB\r\n\r\n0dB Technology\r\nSupported.', 'image/prod_6a26b9b1b55ad.png', 'Available', 1200, 0, '', '', 9),
(16, 5, 'Samsung 990 PRO 1TB NVMe', 'Top-tier M.2 NVMe SSD.', 550.00, 15, 'Form Factor: M.2 2280 (Single-Sided)\r\nInterface: PCIe Gen 4.0 x4 / NVMe 2.0\r\nNAND Type: Samsung V-NAND TLC\r\nController: In-house Samsung Controller\r\nCache Memory: 1GB LPDDR4\r\nSequential Read: Up to 7,450 MB/s\r\nSequential Write: Up to 6,900 MB/s\r\nRandom Read (QD32): Up to 1,200,000 IOPS\r\nRandom Write (QD32): Up to 1,550,000 IOPS', 'image/prod_6a26b9a7a5d1d.jpg', 'Available', 5, 0, '', '', 9),
(17, 5, 'WD Blue SN570 500GB NVMe', 'Budget-friendly fast storage.', 200.00, 25, 'Form Factor: M.2 2280 (22mm x 80mm)\r\nInterface: PCIe Gen3 x4 NVMe 1.4\r\nSequential Read: Up to 3,500 MB/s\r\nSequential Write: Up to 2,300 MB/s\r\nRandom Read/Write: 360K / 390K IOPS\r\nNAND Type: Kioxia 112-layer TLC\r\nController: WD 20-82-10048-A1 (Polaris MP16)', 'image/prod_6a26b99d198e7.png', 'Available', 5, 0, '', '', 7),
(18, 7, 'NZXT H5 Flow Black', 'High airflow premium chassis.', 400.00, 7, 'Form Factor: Compact ATX Mid-Tower\r\nMaterials: SGCC Steel and Dark Tinted Tempered Glass\r\nMotherboard Support: E-ATX (up to 277mm wide), ATX, Micro-ATX, Mini-ITX\r\nColor: Black\r\nDimensions: 465 mm(H) x 225mm(W) x 430 mm(D)\r\nWeight: 7.28 kg\r\nMax CPU Cooler Height: Up to 170 mm\r\nMax GPU Clearance: Up to 410 mm\r\nMax PSU Length: Up to 200 mm', 'image/prod_6a26b9934d288.webp', 'Available', 0, 0, '', '', 7),
(19, 8, 'Deepcool AK400 Air Cooler', 'Efficient standard air cooler.', 150.00, 20, 'Product Dimensions\r\n\r\n127×97×155 mm(L×W×H)\r\n\r\nHeatsink Dimensions\r\n\r\n120×45×152 mm(L×W×H)\r\n\r\nNet Weight\r\n\r\n661 g\r\n\r\nHeatpipe\r\n\r\nØ6 mm×4 pcs\r\n\r\nFan Dimensions\r\n\r\n120×120×25 mm(L×W×H)\r\n\r\nFan Speed\r\n\r\n500~1850 RPM±10%\r\n\r\nFan Airflow\r\n\r\n66.47 CFM\r\n\r\nFan Air Pressure\r\n\r\n2.04 mmAq\r\n\r\nFan Noise\r\n\r\n=29 dB(A)\r\n\r\nFan Connector\r\n\r\n4-pin PWM\r\n\r\nBearing Type\r\n\r\nFluid Dynamic Bearing\r\n\r\nFan Rated Voltage\r\n\r\n12 VDC\r\n\r\nFan Rated Current\r\n\r\n0.13 A\r\n\r\nFan Power Consumption\r\n\r\n1.56 W', 'image/prod_6a26b98980a9d.jpg', 'Available', 0, 0, '', '', 8),
(20, 8, 'NZXT Kraken 360 RGB AIO', 'Premium liquid cooler with LCD.', 850.00, 8, 'Radiator Dimensions: 397 × 120 × 27 mm\r\nRadiator Material: Aluminum\r\nCold Plate Material: Copper\r\nTubing Length: 420 mm (CIIR+EPDM rubber with nylon braided sleeve)\r\nPump Motor Speed: 3,100 ± 310 RPM\r\nFan Dimensions: 120 × 120 × 26 mm (Three fans or single-frame depending on SKU)\r\nRotational Speed: 500 – 2,400 ± 250 RPM\r\nAirflow: 75.05 CFM (per fan)\r\nStatic Pressure: 3.07 mm H₂O (per fan)\r\nNoise Level: 31.9 dBA (Max)\r\nBearing: Fluid Dynamic Bearing (FDB)', 'image/prod_6a26b980b43e5.jpg', 'Available', 15, 0, '', '', 7),
(21, 9, 'Microsoft Windows 11 Home 64-bit', 'Standard edition for gamers and home users. USB Flash Drive included.', 549.00, 6, 'Connectivity & Setup: Setting up Windows 11 Home for personal use strictly requires an active internet connection and a Microsoft Account.\r\nSecurity & Authentication: TPM 2.0 is mandatory for next-gen hardware tampering prevention. Windows Hello is supported for biometric logins (fingerprint or facial recognition)\r\nAdvanced Networking: Native support for Hyper-V, Firewall, and modern protocols including Wi-Fi 6, Wi-Fi 7 (when hardware is supported), and Bluetooth 5.3.\r\nAI Enhancements: Access to AI-powered features like Copilot, and for compatible Copilot+ PCs, enhanced hardware-accelerated NPU tasks', 'image/prod_6a26b977c660c.jpg', 'Available', 0, 0, '', '', 6),
(22, 9, 'Microsoft Windows 11 Pro 64-bit', 'Advanced features for professionals and developers. BitLocker included.', 899.00, 8, 'Security: BitLocker device encryption and Windows Information Protection (WIP).\r\nManagement & Remote Access: Domain join, Azure Active Directory, Group Policy, and Remote Desktop support.\r\nVirtualization: Hyper-V and Windows Sandbox for secure, virtualized environments.', 'image/prod_6a26b96a0432e.jpg', 'Available', 0, 0, '', '', 7),
(23, 10, 'Corsair iCUE AR120 RGB 120mm (3-Pack)', 'High performance cooling fans with customizable RGB lighting sync.', 229.00, 6, 'Fan Size: 120mm × 25mm\r\nBearing Type: Hydraulic bearing\r\nLighting: 8 Individually addressable RGB LEDs per fan\r\nRGB Control: Motherboard 3-pin ARGB header (adapter included) or Corsair iCUE controller\r\nFan Speed: 400 – 1,850 RPM (PWM Controlled)\r\nAirflow: Up to 59 CFM\r\nStatic Pressure: 2.83 mm H₂O\r\nNoise Level: 10.26 dBA to 27.3 dBA\r\nZero RPM Mode: Supported\r\nPower Connector: 4-pin PWM\r\nLighting Header: 3-pin +5V ARGB', 'image/prod_6a26b913d2ffa.jpg', 'Available', 5, 0, '', '', 7),
(24, 10, 'ARCTIC P12 PWM PST 120mm', 'Pressure-optimized quiet fan for excellent airflow and low noise.', 45.00, 9, 'Dimensions120 × 120 × 25 mm (Standard)\r\nFan Speed200 – 1,800 RPM (PWM controlled)\r\nZero RPM ModeYes (Stops spinning when PWM signal is < 5%)\r\nAirflow56.3 CFM (95.7 m³/h)\r\nStatic Pressure2.20 mm H₂O\r\nNoise Level0.3 Sone (extremely quiet)\r\nBearing TypeFluid Dynamic Bearing (FDB)\r\nConnector4-Pin Connector + 4-Pin Socket (for daisy-chaining)\r\nCurrent / Voltage0.08 A / 12 V DC\r\nCable Length400 mm\r\nWeight139g – 145g', 'image/prod_6a26b9081110e.jpg', 'Available', 2, 0, '', '', 7),
(25, 11, 'ASUS TUF Gaming VG27AQ 27\" 165Hz', '27-inch WQHD (2560x1440) IPS gaming monitor with ultrafast 165Hz refresh rate.', 1299.00, 8, '27-inch WQHD (2560x1440) IPS gaming monitor with ultrafast 165*Hz refresh rate \r\nASUS Extreme Low Motion Blur Sync (ELMB SYNC)\r\nCertified as G-SYNC Compatible', 'image/prod_6a26b8fe98825.png', 'Available', 0, 0, '', '', 6),
(26, 11, 'AOC 24G2SP 24\" 165Hz IPS', '24-inch Full HD (1920x1080) gaming monitor, perfect for esports.', 649.00, 10, 'Screen size (inch) 23.8\r\nPanel resolution 1920x1080\r\nPanel type IPS\r\nSync technology (VRR) Adaptive sync (Freesync Premium Pro after AMD certified)\r\nBlue Light Technology', 'image/prod_6a26b8f22a7d0.png', 'Available', 0, 0, '', '', 4),
(27, 1, 'AMD Ryzen 7 7800X3D', 'The undisputed king of gaming CPUs. 3D V-Cache technology. Keyword: AM5', 1850.00, 24, 'Cores/Threads: 8 Cores / 16 Threads\r\nClock Speeds: 4.2 GHz Base / up to 5.0 GHz Boost\r\nCache: 96 MB L3 Cache\r\nSocket & Architecture: AM5, Zen 4 (TSMC 5nm)\r\nTDP: 120W (requires a capable cooler)\r\nMemory Support: Dual-channel DDR5', 'image/prod_6a26b8e5871c9.jpg', 'Available', 120, 0, 'AM5', '', 9),
(28, 1, 'Intel Core i7-14700K', '20-core powerhouse for rendering and intense gaming. Keyword: LGA1700', 1980.00, 15, 'Total Cores / Threads: 20 / 28\r\nMax Turbo Frequency: Up to 5.6 GHz\r\nCache: 33 MB Intel Smart Cache\r\nSocket Compatibility: LGA 1700 (Intel 600 and 700 series chipsets)\r\nMemory Support: Up to DDR5-5600 or DDR4-3200\r\nIntegrated Graphics: Intel UHD Graphics 770\r\nPower Draw: 125 W Base Power / 253 W Maximum Turbo Power', 'image/prod_6a26b8dc5752a.jpg', 'Available', 253, 0, 'LGA1700', '', 9),
(29, 1, 'AMD Ryzen 5 7600', 'Incredible value for AM5 platform, excellent 1080p/1440p performer. Keyword: AM5', 1050.00, 40, 'Cores/Threads: 6 Cores / 12 Threads\r\nClock Speed: 3.8 GHz Base / 5.1 GHz \r\nBoostCache: 6MB L2 + 32MB L3\r\nSocket Type: AM5 (Requires DDR5 memory)\r\nIntegrated Graphics: Yes (AMD Radeon Graphics, 2 cores at 2200 MHz)\r\nTDP (Power): 65 Watts', 'image/prod_6a26b8a6f3f34.jpg', 'Available', 65, 0, 'AM5', '', 6),
(30, 1, 'Intel Core i5-12400F', 'The ultimate budget 6-core processor. Keyword: LGA1700', 580.00, 50, 'Cores / Threads: 6 Cores / 12 Threads\r\nClock Speeds: 2.5 GHz Base, up to 4.4 GHz Turbo\r\nCache: 18 MB Intel Smart Cache\r\nPower (TDP): 65W Base, 117W Maximum Turbo\r\nSocket: LGA1700\r\nMemory Support: DDR4 (up to 3200 MT/s) and DDR5 (up to 4800 MT/s)\r\nIncluded Cooler: Yes', 'image/prod_6a26b89a9eda1.jpg', 'Available', 65, 0, 'LGA1700', '', 5),
(31, 1, 'AMD Ryzen 9 7950X', '16-core rendering monster for ultimate creators. Keyword: AM5', 2850.00, 10, 'Architecture: TSMC 5nm FinFET (Zen 4)\r\nCores/Threads: 16 Cores / 32 Threads\r\nClock Speeds: 4.5 GHz Base, up to 5.7 GHz Max Boost\r\nCache: 80MB total (1MB L1, 16MB L2, 64MB L3)\r\nSocket: AM5 (Requires DDR5 memory)\r\nIntegrated Graphics: AMD Radeon Graphics included\r\nDefault TDP:170W', 'image/prod_6a26b86371d66.jpg', 'Available', 170, 0, 'AM5', '', 10),
(32, 1, 'Intel Core i9-13900K', 'Previous gen flagship, still an absolute beast. Keyword: LGA1700', 2650.00, 12, 'Total Cores: 24 (8 Performance-cores, 16 Efficient-cores)\r\nTotal Threads: 32Max Turbo Frequency: Up to 5.80 GHz\r\nCache: 36 MB Intel Smart Cache\r\nLithography: Intel 7 (10nm)\r\nSocket Compatibility: LGA 1700\r\nProcessor Base Power: 125 W\r\nMaximum Turbo Power: 253 W\r\nUnlocked for Overclocking: Yes\r\nMax Memory Size: Up to 192 GB\r\nMemory Types: Up to DDR5 5600 MT/s, DDR4 3200 MT/s\r\nGPU Name: Intel UHD Graphics 770\r\nGraphics Max Dynamic Frequency: 1.65 GHz\r\nMax Resolution (HDMI): 4096 x 2160 @ 60Hz\r\nMax Resolution (DP): 7680 x 4320 @ 60Hz', 'image/prod_6a26b85ae8213.jpg', 'Available', 253, 0, 'LGA1700', '', 9),
(33, 2, 'MSI MAG B650 TOMAHAWK WIFI', 'Premium AM5 board with heavy VRM heatsinks. DDR5 only.', 1150.00, 16, 'Dimensions & Weight / Depth	24.384 cm\r\nDimensions & Weight / Width	30.48 cm\r\nHeader / Brand	MSI\r\nMainboard / Chipset Type	AMD B650\r\nMainboard / Compatible Processors	Ryzen\r\nMainboard / Processor Socket	Socket AM5\r\nNetworking / Data Link Protocol	\r\n2.5 Gigabit Ethernet\r\nBluetooth 5.3\r\nIEEE 802.11a\r\nIEEE 802.11ac\r\nIEEE 802.11ax (Wi-Fi 6E)\r\nIEEE 802.11b\r\nIEEE 802.11g\r\nIEEE 802.11n\r\nPackaged Quantity	1\r\nVideo Output / Type	Graphics adapter (CPU required)', 'image/prod_6a26b8483b6d8.jpg', 'Available', 25, 0, 'AM5', 'DDR5', 5),
(34, 2, 'ASUS ROG STRIX B760-A GAMING WIFI', 'High-end Intel B760 DDR5 motherboard with supreme aesthetics.', 1100.00, 18, 'CPU \r\nIntel® Socket LGA1700 for Intel® Core™ 14th & 13th Gen Processors, Intel® Core™ 12th Gen, Pentium® Gold and Celeron® Processors*\r\nSupports Intel® Turbo Boost Technology 2.0 and Intel® Turbo Boost Max Technology 3.0**\r\n* Refer to www.asus.com for CPU support list.\r\n** Intel® Turbo Boost Max Technology 3.0 support depends on the CPU types.\r\nChipset  Intel® B760 Chipset\r\nMemory 4 x DIMM slots, Max. 192GB, DDR5 7800+(OC)/7600(OC)/7400(OC)/7200(OC)/7000(OC)/6800(OC)/6600(OC)/6400(OC)/ 6200(OC)/6000(OC)/5800(OC)/5600/5400/5200/5000/4800 Non-ECC, Un-buffered Memory*\r\nDual Channel Memory Architecture \r\nSupports Intel® Extreme Memory Profile (XMP)\r\nOptiMem II\r\nGraphics\r\n1 x DisplayPort**\r\n1 x HDMITM port***  \r\n* Graphics specifications may vary between CPU types. Please refer to www.intel.com for any updates. // Please refer to AMD CPU specifications.\r\n** Supports max. 4K@60Hz as specified in DisplayPort 1.4\r\n*** Supports 4K@60Hz as specified in HDMI 2.1.  \r\n**** VGA resolution support depends on processors\' or graphic cards\' resolution.\r\nExpansion Slots\r\nIntel® Core™ Processors (14th & 13th & 12th Gen)\r\n1 x PCIe 5.0 x16 slot\r\n\r\nIntel® B760 Chipset\r\n1 x PCIe 3.0 x16 slot (supports x4 mode)*\r\n2 x PCIe 3.0 x1 slots*\r\n\r\n* PCIe x1(G3)_1 and PCIe x1(G3)_2 slots share bandwidth with PCIe x16(G3). When PCIe x1(G3)_1 or PCIe x1(G3)_2 slot is operating, PCIe x16(G3) will only supports x2 mode.\r\nStorage\r\nTotal supports 3 x M.2 slots and 4 x SATA 6Gb/s ports*\r\n\r\nIntel® Core™ Processors (14th & 13th & 12th Gen)\r\nM.2_1 slot (Key M), type 2242/2260/2280 (supports PCIe 4.0 x4 mode)\r\n\r\nIntel® B760 Chipset\r\nM.2_2 slot (Key M), type 2242/2260/2280/22110 (supports PCIe 4.0 x4 mode)\r\nM.2_3 slot (Key M), type 2242/2260/2280 (supports PCIe 4.0 x4 mode)\r\n4 x SATA 6Gb/s ports\r\n* Intel® Rapid Storage Technology supports SATA RAID 0/1/5/10.\r\nEthernet\r\n1 x Intel® 2.5Gb Ethernet\r\nASUS LANGuard \r\nWireless & Bluetooth\r\nWi-Fi 6E\r\n 2x2 Wi-Fi 6E (802.11 a/b/g/n/ac/ax) \r\n Supports 2.4/5/6GHz frequency band*\r\n Bluetooth® v5.3**\r\n * WiFi 6E 6GHz regulatory may vary between countries.\r\n ** The Bluetooth version may vary, please refer to the Wi-Fi module manufacturer\'s website for the latest specifications.\r\nUSB\r\nRear USB (Total 9 ports)\r\n1 x USB 3.2 Gen 2x2 (20G) port (1 x USB Type-C®)\r\n1 x USB 3.2 Gen 2  (10G) port (1 x Type-A)\r\n3 x USB 3.2 Gen 1 (5G) port (2 x Type-A, 1 x USB Type-C®)\r\n4 x USB 2.0 ports (4 x Type-A)\r\n\r\nFront USB (Total 6 ports)\r\n1 x USB 3.2 Gen 2  (10G) connector (supports USB Type-C®)\r\n1 x USB 3.2 Gen 1 (5G) header supports 2 additional USB 3.2 Gen 1 ports\r\n1 x USB 2.0 header and 1 x USB 2.0 5-1 pin header support 3 additional USB 2.0 ports\r\nAudio\r\nROG SupremeFX 7.1 Surround Sound High Definition Audio CODEC ALC4080\r\n - Impedance sense for front and rear headphone outputs\r\n - Supports: Jack-detection, Multi-streaming, Front Panel Jack-retasking\r\n - High quality 120 dB SNR stereo playback output and 113 dB SNR recording input\r\n - Supports up to 32-Bit/384 kHz playback\r\n\r\nAudio Features \r\n- SupremeFX Shielding Technology\r\n- Savitech SV3H712 AMP\r\n- Premium audio capacitors\r\n- Dedicated audio PCB layers\r\n- Audio cover\r\nBack Panel I/O Ports\r\n1 x USB 3.2 Gen 2x2 (20G) port (1 x USB Type-C®)\r\n1 x USB 3.2 Gen 2 (10G) port (1 x Type-A)\r\n3 x USB 3.2 Gen 1 (5G) ports (2 x Type-A, 1 x USB Type-C®)\r\n4 x USB 2.0 ports (4 x Type-A) \r\n1 x DisplayPort\r\n1 x HDMI™ port\r\n1 x Wi-Fi Module\r\n1 x Intel® 2.5Gb Ethernet port\r\n5 x Audio jacks \r\n1 x BIOS FlashBack™  button', 'image/prod_6a26b83ec2f84.png', 'Available', 25, 0, 'LGA1700', 'DDR5', 8),
(35, 2, 'Gigabyte B550M DS3H', 'Budget king for older AM4 DDR4 builds.', 420.00, 30, 'CPU SocketAMD AM4 (Supports Ryzen 3000, 4000, and 5000 Series Processors)\r\nMemory (RAM)4x DDR4 DIMMs (Dual-Channel, up to 128GB usually supported)\r\nExpansion Slots1x PCIe 4.0 x16 (for graphics card), 1x PCIe 3.0 x16 (runs at x4), 1x PCIe 3.0 x1\r\nStorage1x M.2 (PCIe 4.0 x4/x3), 1x M.2 (PCIe 3.0 x2), 4x SATA 6Gb/s\r\nNetworkingRealtek GbE LAN (Gigabit Ethernet)\r\nAudioRealtek Audio Codec with high-end audio capacitors', 'image/prod_6a26b835c7b28.jpg', 'Available', 15, 0, 'AM4', 'DDR4', 8),
(36, 2, 'ASRock B760M PRO RS/D4', 'Solid budget board for Intel 12th/13th/14th gen. DDR4 only.', 650.00, 25, 'Supports 14th, 13th & 12th Gen Intel® Core™ Processors (LGA1700)\r\n7+1+1 Power Phase, Dr.MOS for VCore+GT\r\n4 x DDR4 DIMMs\r\nSupports Dual Channel, up to 5333+ (OC)\r\n2 PCIe 4.0 x16, 1 PCIe 4.0 x1, 1 M.2 Key E for WiFi\r\nGraphics Output Options: HDMI, DisplayPort\r\nRealtek ALC897 7.1 CH HD Audio Codec, Nahimic Audio\r\n4 SATA3, 2 Hyper M.2 (PCIe Gen4x4)\r\n2 USB 3.2 Gen1 Type-C (1 Rear, 1 Front),\r\n5 USB 3.2 Gen1 Type-A (3 Rear, 2 Front),\r\n6 USB 2.0 (2 Rear, 4 Front)\r\nDragon 2.5G LAN\r\nSupports ASRock Auto Driver Installer', 'image/prod_6a26b82720acb.jpg', 'Available', 20, 0, 'LGA1700', 'DDR4', 6),
(37, 2, 'ASUS ROG CROSSHAIR X670E HERO', 'Flagship AM5 board for extreme overclocking.', 3200.00, 5, 'CPU SocketAMD Socket AM5 Supports AMD Ryzen 9000, 8000, and 7000 Series Desktop Processors\r\nChipsetAMD X670\r\nMemory4 x DDR5 DIMM slots, Dual Channel (Max. 192GB)\r\nExpansion Slots2 x PCIe 5.0 x16 Safeslots (supports ×16 or ×8/×8) 1 x PCIe 4.0 x1 slot\r\nStorageOnboard: 2 x PCIe 5.0 M.2, 2 x PCIe 4.0 M.2 Bundled: 1 x PCIe 5.0 M.2 expansion card SATA: 6 x SATA 6Gb/s ports\r\nNetworking1 x Intel 2.5Gb Ethernet Wi-Fi 6E (802.11 a/b/g/n/ac/ax) & Bluetooth v5.3\r\nAudioROG SupremeFX 7.1 Surround Sound ALC4082 CODEC ESS ES9218 Quad DAC\r\nRear I/O2 x USB4 Type-C ports 1 x USB 3.2 Gen 2x2 Type-C 9 x USB 3.2 Gen 2 ports (8x Type-A, 1x Type-C) 1 x HDMI port BIOS FlashBack & Clear CMOS buttons\r\nForm FactorATX (12.0 in x 9.6 in)', 'image/prod_6a26b7d768ae2.png', 'Available', 35, 0, 'AM5', 'DDR5', 10),
(38, 3, 'Corsair Vengeance 32GB (2x16GB) DDR5 6000MHz CL30', 'The sweet spot speed and latency for Ryzen 7000 series.', 580.00, 39, 'Capacity: 32GB (2 x 16GB DIMMs)\r\nTested Speed: 6000 MT/s (PC5-48000)\r\nTested Latency: CL30 (specifically 30-36-36-76)\r\nTested Voltage: 1.4VMemory Type: DDR5\r\nProfiles: AMD EXPO & Intel XMP\r\nForm Factor: 288-pin DIMM\r\nHeat Spreader: Solid aluminum', 'image/prod_6a26b7cdc7614.jpg', 'Available', 10, 0, '', 'DDR5', 9),
(39, 3, 'G.Skill Trident Z5 Neo RGB 64GB (2x32GB) DDR5 6000MHz', 'High-capacity, high-speed RAM for video editing.', 1150.00, 15, 'Capacity: 64GB (2 x 32GB)\r\nMemory Type: DDR5Tested Speed: 6000 MT/s (PC5-48000)\r\nLatency Timings: Available in ultra-low latency configurations, typically CL30 (30-40-40-96) or CL36 (36-36-36-96) depending on the specific model variationTested \r\nVoltage: 1.40V (for CL30 kits)\r\nLighting: Customizable RGB lighting across a streamlined dual-textured aluminum heat spreader', 'image/prod_6a26b7c554e34.webp', 'Available', 12, 0, '', 'DDR5', 7),
(40, 3, 'Kingston FURY Beast 16GB (2x8GB) DDR4 3200MHz', 'Reliable budget DDR4 kit.', 190.00, 60, 'CapacitiesKits of 2: 16GB\r\nFrequencies	3200MT/s\r\nLatencies	CL16\r\nVoltage	1.35V\r\nOperating Temperature	0°C to 85°C\r\nDimensions	133.35mm x 34.1mm x 7.2mm', 'image/prod_6a26b7bc84007.jpg', 'Available', 5, 0, '', 'DDR4', 5),
(41, 3, 'Corsair Dominator Titanium 32GB DDR5 7200MHz', 'Ultra-premium high-frequency memory for Intel builds.', 850.00, 10, 'Memory Type: DDR5\r\nForm Factor: U-DIMM\r\nColor: White\r\nHeat Spreader: Aluminum\r\nCapacity: 16GB per DIMM\r\nSpeed: 7200 MT/s\r\nTimings: 34-44-44-96 2T\r\nOperating Voltage: 1.45V\r\nDimensions: 135.9 x 56.75×7.7mm(LxHxW)\r\nWeight: 77 grams', 'image/prod_6a26b7b386114.jpg', 'Available', 12, 0, '', 'DDR5', 9),
(42, 3, 'TeamGroup T-Force Delta RGB 32GB DDR4 3600MHz', 'Flashy RGB DDR4 kit for mid-range systems.', 380.00, 25, 'Capacity: 32GB (2 x 16GB dual-channel kit)\r\nMemory Type: DDR4 UDIMM (Desktop)\r\nTested Speed: 3600 MHz (PC4-28800)\r\nCAS Latency: CL18 (18-22-22-42)Voltage: 1.35V\r\nForm Factor: 288-pin \r\nDIMMLighting: 120° ultra-wide-angle, full-frame RGB Force Flow effect\r\nHeatsink: Geometric asymmetric aluminum alloy heat spreader\r\nColors Available: Typically offered in Black or White\r\nDimensions: 49mm (1.93 inches) in total height', 'image/prod_6a26b7a8e455f.jpg', 'Available', 8, 0, '', 'DDR4', 8),
(43, 4, 'ASUS TUF Gaming GeForce RTX™ 4060 Ti 8GB GDDR6 OC Edition', 'Excellent 1080p Ultra gaming with DLSS 3 Frame Gen.', 1850.00, 20, 'Graphic Engine\r\nNVIDIA® GeForce RTX™ 4060 Ti\r\n\r\nAI Performance\r\n366\r\n\r\nBus Standard\r\nPCI Express 4.0\r\n\r\nOpenGL\r\nOpenGL®4.6\r\n\r\nVideo Memory\r\n8GB GDDR6\r\n\r\nEngine Clock\r\nOC Mode: 2655MHz\r\nDefault Mode: 2625MHz (Boost)\r\n\r\nCUDA Core\r\n4352\r\n\r\nMemory Speed\r\n18 Gbps\r\n\r\nMemory Interface\r\n128-bit\r\n\r\nResolution\r\nDigital Max Resolution 7680 x 4320\r\n\r\nInterface\r\nYes x 1 (Native HDMI 2.1a), Yes x 3 (Native DisplayPort 1.4a), HDCP Support Yes (2.3)', 'image/prod_6a26b7a00ec82.png', 'Available', 160, 0, '', '', 5),
(44, 4, 'AMD Radeon RX 7800 XT 16GB', 'Unbeatable 1440p value, massive VRAM for textures.', 2550.00, 17, 'lock: GPU / Memory\r\nBoost Clock*: Up to 2475 MHz / 19.5 Gbps\r\nGame Clock**: 2169 MHz / 19.5 Gbps\r\nKey Specification\r\nAMD Radeon™ RX 7800 XT GPU\r\n16GB GDDR6 on 256-Bit Memory Bus\r\n60 AMD RDNA™ 3 Compute Units (with RT+AI Accelerators)\r\n64MB AMD Infinity Cache™ technology\r\nMicrosoft® DirectX® 12 Ultimate\r\nPCI® Express 4.0 Support\r\n2 x 8-pin Power Connectors\r\n3 x DisplayPort™ 2.1***, 1 x HDMI™ 2.1\r\nKey Features\r\nDual Fan Design\r\nStylish Metal Backplate\r\nStriped Ring Fan\r\nUltra-fit Heatpipe\r\nLED Indicator\r\n0dB Silent Cooling\r\nSuper Alloy Graphics Card', 'image/prod_6a26b796662e7.png', 'Available', 263, 0, '', '', 7),
(45, 4, 'ROG Strix GeForce RTX™ 4080 SUPER 16GB GDDR6X OC Edition', 'Incredible 4K performance and ray tracing capabilities.', 4950.00, 10, 'AI Performance: 865 AI TOPS\r\nPowered by NVIDIA DLSS3, ultra-efficient Ada Lovelace arch, and full ray tracing\r\n4th Generation Tensor Cores: Up to 4x performance with DLSS 3 vs. brute-force rendering \r\n3rd Generation RT Cores: Up to 2X ray tracing performance \r\nOC mode: 2670 MHz (OC mode)/ 2640 MHz (Default mode) \r\nAxial-tech fans scaled up for 23% more airflow\r\nNew patented vapor chamber with milled heatspreader for lower GPU temps\r\n3.5-slot design: massive fin array optimized for airflow from the three Axial-tech fans\r\nDiecast shroud, frame, and backplate add rigidity and are vented to further maximize airflow and heat dissipation\r\nDigital power control with high-current power stages and 15K capacitors to fuel maximum performance\r\nAuto-Extreme precision automated manufacturing for higher reliability\r\nGPU Tweak III software provides intuitive performance tweaking, thermal controls, and system monitoring', 'image/prod_6a26b78ea28d7.png', 'Available', 320, 0, '', '', 9),
(46, 4, 'AMD Radeon RX 7900 XTX 24GB', 'Raw rasterization monster, destroys 4K without breaking a sweat.', 4800.00, 8, 'Architecture: RDNA 3 (Navi 31 GPU)Stream Processors: 6,144 (96 Compute Units)Game Clock: 2,300 MHzBoost Clock: Up to 2,500 MHzMemory Capacity: 24GB GDDR6Memory Bus: 384-bitMemory Speed & Bandwidth: 20 Gbps effective / Up to 960 GB/sInfinity Cache: 96 MB (2nd Gen)Power Consumption (TDP): 355WRecommended PSU: 750W to 850W depending on board partnerPower Connectors: 2x or 3x 8-pin (depending on manufacturer model)Display Outputs: DisplayPort 2.1, HDMI 2.1a, USB Type-C', 'image/prod_6a26b784937e8.png', 'Available', 355, 0, '', '', 9),
(47, 4, 'ASUS Dual GeForce RTX™ 4070 Ti SUPER OC Edition 16GB GDDR6X', 'Perfect 1440p high-refresh rate card.', 6039.00, 14, 'AI Performance\r\n710\r\n\r\nBus Standard\r\nPCI Express 4.0\r\n\r\nOpenGL\r\nOpenGL®4.6\r\n\r\nVideo Memory\r\n16GB GDDR6X\r\n\r\nEngine Clock\r\nOC mode : 2655 MHz\r\nDefault mode : 2625 MHz (boost)\r\n\r\nCUDA Core\r\n8448\r\n\r\nMemory Speed\r\n21 Gbps\r\n\r\nMemory Interface\r\n256-bit\r\n\r\nResolution\r\nDigital Max Resolution 7680 x 4320\r\n\r\nInterface\r\nYes x 1 (Native HDMI 2.1a), Yes x 3 (Native DisplayPort 1.4a), HDCP Support Yes (2.3)', 'image/prod_6a26b77aec538.png', 'Available', 285, 0, '', '', 8),
(48, 4, 'AMD Radeon RX 7600 8GB', 'Budget king for entry-level 1080p gaming.', 1350.00, 13, 'Video Memory: 8GB GDDR6\r\nMemory Interface: 128-bit\r\nInterface Type: PCI Express 4.0\r\nOutput Ports: HDMI, DisplayPort (varies by manufacturer)\r\nArchitecture: RDNA 3\r\nPower Connector: 8-pin (varies by model)\r\nRecommended PSU: 550W or higher', 'image/prod_6a26b76fcdfc0.png', 'Available', 165, 0, '', '', 4),
(49, 5, 'WD Black SN850X 2TB Gen4 NVMe', 'Top-tier speeds up to 7300MB/s.', 780.00, 21, 'Capacity\r\n\r\n1 TB (Without Heatsink)\r\n\r\n\r\n\r\nForm Factor\r\n\r\nM.2 2280\r\n\r\n\r\n\r\nInterface\r\n\r\nPCIe Gen4 x4\r\n\r\n\r\n\r\nSequential Read Performance\r\n\r\n7300MB/s\r\n\r\n\r\n\r\nSequential Write Performance\r\n\r\n6300MB/s\r\n\r\n\r\n\r\nRandom Read\r\n\r\n8000004KB IOPS\r\n\r\n\r\n\r\nRandom Write\r\n\r\n11000004KB IOPS\r\n\r\n\r\n\r\nEndurance (TBW)\r\n\r\n600\r\n\r\n\r\n\r\nCompatibility\r\n\r\nComputer with M.2 (M-key) port, capable of taking M.2 2280 form factor\r\n\r\nWindows® 11, 10, 8.1\r\n\r\nPlayStation® 5 (Heatsink model only)\r\n\r\nNote: Compatibility may vary depending on user’s hardware configuration and operating system.\r\n\r\n\r\n\r\nDimensions (L x W x H)\r\n\r\n20mm x 22mm x 2.38mm', 'image/prod_6a26b73aa2a97.jpg', 'Available', 8, 0, '', '', 9),
(50, 5, 'Crucial P3 Plus 1TB Gen4 NVMe', 'Great balance of speed and affordability.', 280.00, 45, 'Digital Storage Capacity	1TB\r\nHard Disk Interface	NVMe\r\nConnectivity Technology	NVMe\r\nAdditional Features	Portable\r\nHard Disk Form Factor	2.5 Inches (6.4 cm)\r\nCompatible Devices	This drive is compatible with desktops & laptops that accept PCIe NVMe Gen 4.0 drives\r\nSpecific Uses For Product	Business, Gaming, Personal\r\nRead Speed	5000 Megabytes Per Second\r\nMedia Speed	4200 Megabytes Per Second\r\nCache Memory Installed Size	500\r\nData Transfer Rate	5000 Megabytes Per Second\r\nForm Factor	M 2\r\nHardware Connectivity	PCIE x 4\r\nHardware Platform	Linux, Mac, PC\r\nHard-Drive Size	500 GB\r\nItem Dimensions L x W x Thickness	3.15\"L x 0.87\"W x 0.09\"Th (8 x 2.2 x 0.2 cm)\r\nNumber of Items	1\r\nUnit Count	1.0 Count\r\nBrand	Crucial\r\nModel Number	CT500P3PSSD8\r\nHard Disk Description	Solid State Drive\r\nBuilt-In Media	P3 Plus PCIe 4.0 3D NAND NVMe M.2 SSD\r\nModel Name	Crucial P3 Plus NVMe SSD\r\nManufacturer	Crucial\r\nGlobal Trade Identification Number	00649528918826\r\nUPC	649528918826\r\nMfr Part Number	CT500P3PSSD8\r\nItem Type Name	NVMe M.2 SSD\r\nInstallation Type	Internal Hard Drive\r\nColor	Black\r\nEnclosure Material	Aluminum', 'image/prod_6a26b72f9beef.jpg', 'Available', 5, 0, '', '', 8),
(51, 5, 'Samsung 990 PRO 4TB NVMe', 'Massive fast storage for heavy video editors.', 1550.00, 8, 'Interface	PCIe Gen 4.0, x4, NVMe 2.0[5]\r\nForm Factor	M.2 (2280)\r\nStorage Memory	Samsung V-NAND 3-bit TLC\r\nController	Samsung In-house controller\r\nCapacity[6]	1TB\r\nDRAM              4GB LPDDR4\r\nSequential Read/Write Speed[7]	up to 7,450 MB/s, up to 6,900 MB/s\r\nRandom Read/Write Speed (QD32)[8]	up to 1,600K IOPS, 1,550K IOPS\r\nManagement Software	Samsung Magician Software\r\nData Encryption	AES 256-bit Full Disk Encryption, TCG/Opal V2.0,\r\n                                Encrypted Drive (IEEE1667)\r\nTotal Bytes Written	600TB', 'image/prod_6a26b727aebb2.jpg', 'Available', 10, 0, '', '', 10),
(52, 6, 'Corsair RM850e 850W 80+ Gold', 'Fully modular, ATX 3.0 ready.', 550.00, 19, 'Compatible devices	Personal Computer\r\nConnector type	EPS\r\nOutput wattage	850 Watts\r\nForm factor	ATX\r\nWattage	850 watts\r\nCooling method	Air\r\nItem dimensions L x W x H	15 x 14 x 8.6 centimetres\r\nItem weight	1.52 Kilograms', 'image/prod_6a26b71ca22a0.jpg', 'Available', 850, 0, '', '', 10),
(53, 6, 'Seasonic Focus GX-1000 1000W Gold', 'Legendary reliability for high-end builds.', 850.00, 12, 'Compatible Devices	Personal Computer\r\nConnector Type	ATX\r\nOutput Wattage	1000\r\nForm Factor	ATX\r\nWattage	1000 watts\r\nCooling Method	Air\r\nItem dimensions L x W x H	12.13 x 7.48 x 4.65 inches\r\nItem Weight	2.12 Kilograms', 'image/prod_6a26b71433386.jpg', 'Available', 1000, 0, '', '', 9),
(54, 6, 'MSI MAG A650BN 650W 80+ Bronze', 'Solid budget power supply.', 260.00, 30, 'Compatible Devices	Gaming Console\r\nConnector Type	ATX\r\nOutput Wattage	650\r\nForm Factor	ATX\r\nWattage	650 watts\r\nCooling Method	Air\r\nItem dimensions L x W x H	8.66 x 3.15 x 1.18 inches\r\nItem Weight	1 Kilograms', 'image/prod_6a26b7097ab2e.jpg', 'Available', 650, 0, '', '', 8),
(55, 6, 'FSP Hydro Ti PRO 1000W Titanium', 'Ultra-premium titanium efficiency.', 1150.00, 2, 'Complies with ATX12V V3.0 &EPS12V V2.92\r\nEfficiency ≧ 94% at typical load\r\n450V, 105°C Japanese bulk capacitors\r\nJapanese electrolytic capacitors\r\nIndustrial-grade design with conformal coating application\r\nEco semi-fanless fan control switch\r\n135mm fluid dynamic bearing (FDB) fan\r\nFully modular cabling design', 'image/prod_6a26b70036a69.jpg', 'Available', 1000, 0, '', '', 5),
(56, 7, 'Lian Li O11 Dynamic EVO Black', 'The iconic showcase dual-chamber case.', 750.00, 15, 'COLOR	Black	White	Harbor Grey\r\nDIMENSION	(D)465mm × (W)285mm × (H)459mm\r\nMATERIAL	4mm Aluminum\r\n4mm Tempered Glass\r\n1mm Steel Structure\r\nMOTHERBOARD SUPPORT	E-ATX (width: under 280mm)/ATX /M-ATX/ITX\r\nPSU SUPPORT	220mm\r\nFAN SUPPORT	Top: 120mm × 3 or 140mm × 2\r\nSide: 120mm × 3 or 140mm × 2\r\nBottom: 120mm × 3 or 140mm × 2\r\nRear: 120mm × 1\r\nOn Drive Cage: 60mm × 1\r\nRADIATOR SUPPORT	Top: 360mm × 1 or 280mm × 1\r\n*Total max thickness: 87.5mm\r\nSide: 360mm × 1 or 280mm × 1\r\n*Inner scenario total max thickness:\r\n83mm(120mm fan on top and bottom)\r\n63mm(140mm fan on top and bottom)\r\n*Outer scenario total max. thickness：\r\n120mm(120mm fan on top and bottom)\r\n100mm(140mm fan on top and bottom)\r\nBottom: 360mm × 1\r\n*Total max thickness: 87.5mm\r\nDRIVE SUPPORT	Bottom：2.5” SSD × 4 or 3.5” HDD × 2\r\nSide：2.5” SSD × 4 or 3.5” HDD × 2\r\nDrive Cage：2.5” SSD × 3 or 3.5” HDD × 2 + 2.5” SSD × 1\r\nCable Management Bar：2.5” SSD × 2\r\n*Only 9 sets of 2.5” SSD mounting pads are provided\r\nGPU LENGTH CLEARANCE	426 mm\r\nCPU HEIGHT CLEARANCE	167 mm\r\nEXPANSION SLOTS	8\r\nI/O PORTS	2 × USB 3.0\r\n1 × USB 3.1 TYPE-C,\r\n1 × HD AUDIO/ MIC\r\nLED Color and Mode button\r\nReset Button\r\nPower Button', 'image/prod_6a26b6f78e6f7.jpg', 'Available', 0, 0, '', '', 6),
(57, 7, 'Corsair 4000D Airflow Black', 'Classic high-airflow mid-tower.', 380.00, 24, 'Dimensions: 230mm(W) x 466mm(H) x 453mm(D)\r\nSide Panel: Tempered Glass \r\nBody Material: Steel  \r\nDrive Bay: \r\n3.5\" HDD x 2\r\n2.5\" HDD x 2\r\nFan Capacity:\r\n120mm or 140mm fan x 2 (Top)\r\n120mm fan x 3 / 140mm fan x 2 (Front) * 1 x Corsair AirGuide Fan included *\r\n120mm fan x 1 (Rear) * 1 x Corsair AirGuide Fan included *\r\nRadiator:\r\n120-280mm (Top)\r\n120-360mm (Front)\r\nCPU Cooler Height Cleareance: 170mm\r\nGPU Cleareance:\r\nLength: 360mm\r\nI/O Panel:\r\nUSB 3.0 x 1\r\nUSB 3.1 Type-C x 1\r\nAudio In & Out\r\nM/B Type: ATX\r\nPSU Type: ATX', 'image/prod_6a26b6eeaf9fe.jpg', 'Available', 0, 0, '', '', 8),
(58, 7, 'Montech X3 Mesh Black', 'Insane budget value, includes 6 RGB fans.', 220.00, 35, 'Dimensions (L*W*H)	370*210*480mm (Case) / 530*265*425mm (Carton)\r\nMB Support	ATX / Micro-ATX / Mini-ITX\r\nFront I/O	Power Button / Mic*1 / Audio*1 / USB2.0*2 / Reset Button / LED Button / USB3.0*1\r\nPCIe Slots	7\r\nCompatibility / Maximum	CPU Cooler	160mm\r\nGPU	305mm\r\nPSU	160mm ATX\r\nDrive Support / Maximum	3.5” HDD	2\r\n2.5” SSD	4\r\nPre-installed Fan (s)	Top	120mm*2 (RGB Molex Fans)\r\nFront	140mm*3 (RGB Molex Fans)\r\nRear	120mm*1 (RGB Molex Fan)\r\nFan Support	Top	120mm*2 / 140mm*2\r\nFront	120mm*3 / 140mm*3\r\nPSU Shroud	120mm*2\r\nRear	120mm*1\r\nRadiator Support	Top	120 / 240mm\r\nRear	120mm\r\nDust Filter	Top / Bottom', 'image/prod_6a26b6e41b78d.webp', 'Available', 0, 0, '', '', 5),
(59, 8, 'Thermalright Peerless Assassin 120 SE', 'The dual-tower air cooler that beats 240mm AIOs.', 160.00, 40, 'Dimensions: 120 mm (L) x 120 mm (W) x 25 mm (H)\r\nWeight: 120 g\r\nRated Speed: 1550 RPM ± 10%\r\nNoise Level: 25.6 dBA (max)\r\nAir Flow: 66.17 CFM (max)\r\nAir Pressure: 1.53 mm H2O (max)\r\nConnector: 4-pin PWM fan connector\r\nARGB Connector: 3-pin 5V\r\nBearing Type: S-FDB Bearing', 'image/prod_6a26b6dce781a.png', 'Available', 0, 0, '', '', 7),
(60, 8, 'Arctic Liquid Freezer III 360 AIO', 'Thick radiator, ultimate liquid cooling performance.', 520.00, 15, 'Speed\r\n600-3000 rpm\r\n\r\nStatic Pressure\r\n6.9 mmH2O\r\n\r\nBearing\r\nFluid Dynamic Bearing\r\n\r\nAirflow\r\n77 cfm | 131 m3/h', 'image/prod_6a26b6d34a7f4.jpg', 'Available', 15, 0, '', '', 5),
(61, 8, 'Deepcool AK620 Digital', 'Premium air cooling with a digital temp display.', 320.00, 16, 'Fan Dimensions: 120×120×25 mm\r\n\r\nFan Speed: 500~1850 RPM±10%\r\n\r\nFan Airflow: 68.99 CFM\r\n\r\nFan Air Pressure: 2.19 mmAq\r\n\r\nFan Noise: ≤28 dB(A)\r\n\r\nFan Connector: 4-pin PWM\r\n\r\nBearing Type: Fluid Dynamic Bearing\r\n\r\nFan Rated Voltage: 12 VDC\r\n\r\nFan Rated Current: 0.12 A\r\n\r\nFan Power Consumption: 1.44 W', 'image/prod_6a26b6cb45b95.webp', 'Available', 0, 0, '', '', 4);

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
-- Table structure for table `promo_codes`
--

CREATE TABLE `promo_codes` (
  `promo_id` int(11) NOT NULL,
  `code_name` varchar(50) NOT NULL COMMENT 'Promo code string',
  `target_category` enum('All','Components','Packages') NOT NULL DEFAULT 'All',
  `is_vip_only` tinyint(1) DEFAULT 1 COMMENT '1 for VIP, 0 for public',
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` datetime DEFAULT current_timestamp(),
  `discount_value` decimal(10,2) NOT NULL COMMENT '折扣数值',
  `discount_type` enum('Percentage','Fixed') NOT NULL DEFAULT 'Percentage' COMMENT '折扣模式',
  `min_spend` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '最低消费门槛',
  `max_cap` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '最高折抵上限'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `promo_codes`
--

INSERT INTO `promo_codes` (`promo_id`, `code_name`, `target_category`, `is_vip_only`, `status`, `created_at`, `discount_value`, `discount_type`, `min_spend`, `max_cap`) VALUES
(1, 'VIPCOMP15', 'Components', 1, 'Active', '2026-05-07 12:15:57', 15.00, 'Percentage', 100.00, 50.00),
(2, 'VIPPC20', 'Packages', 1, 'Active', '2026-05-07 12:15:57', 20.00, 'Percentage', 2000.00, 200.00),
(3, 'WELCOME10', 'All', 0, 'Active', '2026-05-07 12:15:57', 10.00, 'Percentage', 0.00, 15.00),
(4, 'SUMMER26', 'All', 0, 'Active', '2026-05-07 13:37:15', 26.00, 'Percentage', 50.00, 30.00),
(5, 'ELITEGAMER', 'Packages', 1, 'Active', '2026-05-07 13:37:15', 150.00, 'Fixed', 3000.00, 0.00),
(6, 'UPGRADE5', 'Components', 0, 'Active', '2026-05-07 13:37:15', 5.00, 'Percentage', 50.00, 20.00),
(7, 'VIPPARTS12', 'Components', 1, 'Active', '2026-05-07 13:37:15', 12.00, 'Percentage', 200.00, 100.00),
(8, 'EXPIRED50', 'All', 0, 'Inactive', '2026-05-07 13:37:15', 0.00, 'Percentage', 0.00, 0.00);

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
(22, 1, 'i', 6697.00, '2026-04-12 22:40:26'),
(24, 5, 'shengwing', 14229.00, '2026-05-01 14:59:50'),
(25, 5, 'c', 15107.00, '2026-05-01 15:24:25'),
(26, 5, 'io', 11787.00, '2026-05-01 16:18:23'),
(27, 5, 'kkkk', 10259.00, '2026-05-01 22:00:25'),
(28, 5, 'kkkk', 6427.00, '2026-05-02 22:28:17'),
(29, 5, 'ttt', 6427.00, '2026-05-02 22:28:32'),
(41, 6, 'Custom Rig (May 17, 2026)', 15947.00, '2026-05-18 01:01:17');

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
(2, 1, NULL, '日人人日r', '2331', '22/11', 'Credit Card', 1, '2026-04-11 17:06:45'),
(4, 6, 2, 'Ali Bin Abu', '6666', '12/30', 'Credit Card', 1, '2026-05-18 00:13:55'),
(5, 5, 2, 'GAN SHENG WING', '6666', '01/26', 'Credit Card', 1, '2026-05-18 16:45:44');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shopping_cart`
--

INSERT INTO `shopping_cart` (`cart_id`, `customer_id`, `product_id`, `pc_build`, `package_id`, `affiliate_id`, `quantity`, `added_at`) VALUES
(9, 3, NULL, 14, NULL, NULL, 1, '2026-04-06 09:17:24'),
(19, 1, 4, NULL, NULL, NULL, 1, '2026-04-29 23:38:35'),
(20, 1, NULL, NULL, 4, NULL, 2, '2026-04-30 00:06:55'),
(49, 6, NULL, 41, NULL, NULL, 1, '2026-05-18 01:01:17'),
(50, 6, NULL, 28, NULL, 5, 1, '2026-05-18 01:56:27'),
(51, 7, 48, NULL, NULL, NULL, 25, '2026-05-18 16:42:34');

-- --------------------------------------------------------

--
-- Table structure for table `used_vouchers`
--

CREATE TABLE `used_vouchers` (
  `used_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `promo_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `used_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(8, 5, 'Top-up', 500.00, 50, '2026-05-09 16:54:06'),
(9, 5, 'Payment', -45.00, 0, '2026-05-09 21:03:11'),
(10, 5, 'Top-up', 100000.00, 10000, '2026-05-09 21:04:18'),
(11, 6, 'Top-up', 500.00, 50, '2026-05-17 10:58:30'),
(12, 6, 'Top-up', 500.00, 50, '2026-05-18 00:32:41');

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
-- Indexes for table `community_comments`
--
ALTER TABLE `community_comments`
  ADD PRIMARY KEY (`comment_id`),
  ADD KEY `fk_community_comments_post` (`post_id`),
  ADD KEY `fk_community_comments_customer` (`customer_id`);

--
-- Indexes for table `community_likes`
--
ALTER TABLE `community_likes`
  ADD PRIMARY KEY (`like_id`),
  ADD UNIQUE KEY `unique_like` (`post_id`,`customer_id`),
  ADD KEY `fk_community_likes_customer` (`customer_id`);

--
-- Indexes for table `community_posts`
--
ALTER TABLE `community_posts`
  ADD PRIMARY KEY (`post_id`),
  ADD KEY `fk_community_posts_customer` (`customer_id`),
  ADD KEY `fk_community_posts_build` (`pc_build_id`);

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
  ADD KEY `fk_order_affiliate` (`affiliate_id`);

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
  ADD KEY `category_id` (`category_id`),
  ADD KEY `idx_builder_socket` (`socket_type`),
  ADD KEY `idx_builder_ram` (`ram_type`),
  ADD KEY `idx_builder_tier` (`performance_tier`);

--
-- Indexes for table `product_specifications`
--
ALTER TABLE `product_specifications`
  ADD PRIMARY KEY (`spec_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `idx_spec_search` (`spec_name`,`spec_value`);

--
-- Indexes for table `promo_codes`
--
ALTER TABLE `promo_codes`
  ADD PRIMARY KEY (`promo_id`),
  ADD UNIQUE KEY `code_name` (`code_name`);

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
  ADD KEY `fk_cart_affiliate` (`affiliate_id`);

--
-- Indexes for table `used_vouchers`
--
ALTER TABLE `used_vouchers`
  ADD PRIMARY KEY (`used_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `promo_id` (`promo_id`);

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
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `bank`
--
ALTER TABLE `bank`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `build_items`
--
ALTER TABLE `build_items`
  MODIFY `build_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=384;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `community_comments`
--
ALTER TABLE `community_comments`
  MODIFY `comment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `community_likes`
--
ALTER TABLE `community_likes`
  MODIFY `like_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `community_posts`
--
ALTER TABLE `community_posts`
  MODIFY `post_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `consultations`
--
ALTER TABLE `consultations`
  MODIFY `consultation_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `customer_addresses`
--
ALTER TABLE `customer_addresses`
  MODIFY `address_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `fpx_accounts`
--
ALTER TABLE `fpx_accounts`
  MODIFY `account_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `order_details`
--
ALTER TABLE `order_details`
  MODIFY `order_detail_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `packages`
--
ALTER TABLE `packages`
  MODIFY `package_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `package_items`
--
ALTER TABLE `package_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=378;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT for table `product_specifications`
--
ALTER TABLE `product_specifications`
  MODIFY `spec_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `promo_codes`
--
ALTER TABLE `promo_codes`
  MODIFY `promo_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `saved_builds`
--
ALTER TABLE `saved_builds`
  MODIFY `pc_build` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `saved_cards`
--
ALTER TABLE `saved_cards`
  MODIFY `card_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `shopping_cart`
--
ALTER TABLE `shopping_cart`
  MODIFY `cart_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `used_vouchers`
--
ALTER TABLE `used_vouchers`
  MODIFY `used_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

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
-- Constraints for table `community_comments`
--
ALTER TABLE `community_comments`
  ADD CONSTRAINT `community_comments_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `community_posts` (`post_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `community_comments_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_community_comments_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_community_comments_post` FOREIGN KEY (`post_id`) REFERENCES `community_posts` (`post_id`) ON DELETE CASCADE;

--
-- Constraints for table `community_likes`
--
ALTER TABLE `community_likes`
  ADD CONSTRAINT `community_likes_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `community_posts` (`post_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `community_likes_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_community_likes_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_community_likes_post` FOREIGN KEY (`post_id`) REFERENCES `community_posts` (`post_id`) ON DELETE CASCADE;

--
-- Constraints for table `community_posts`
--
ALTER TABLE `community_posts`
  ADD CONSTRAINT `community_posts_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `community_posts_ibfk_2` FOREIGN KEY (`pc_build_id`) REFERENCES `saved_builds` (`pc_build`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_community_posts_build` FOREIGN KEY (`pc_build_id`) REFERENCES `saved_builds` (`pc_build`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_community_posts_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE;

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
  ADD CONSTRAINT `fk_order_affiliate` FOREIGN KEY (`affiliate_id`) REFERENCES `customers` (`customer_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_order_details_build` FOREIGN KEY (`pc_build`) REFERENCES `saved_builds` (`pc_build`) ON DELETE SET NULL,
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
  ADD CONSTRAINT `fk_cart_affiliate` FOREIGN KEY (`affiliate_id`) REFERENCES `customers` (`customer_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_shopping_cart_pc_build` FOREIGN KEY (`pc_build`) REFERENCES `saved_builds` (`pc_build`) ON DELETE CASCADE,
  ADD CONSTRAINT `shopping_cart_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `shopping_cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `used_vouchers`
--
ALTER TABLE `used_vouchers`
  ADD CONSTRAINT `fk_used_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_used_promo` FOREIGN KEY (`promo_id`) REFERENCES `promo_codes` (`promo_id`) ON DELETE CASCADE;

--
-- Constraints for table `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  ADD CONSTRAINT `wallet_transactions_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
