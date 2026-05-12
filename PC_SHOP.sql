-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主机： 127.0.0.1
-- 生成日期： 2026-05-12 19:45:40
-- 服务器版本： 10.4.32-MariaDB
-- PHP 版本： 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 数据库： `pcshop`
--

-- --------------------------------------------------------

--
-- 表的结构 `admins`
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
-- 转存表中的数据 `admins`
--

INSERT INTO `admins` (`admin_id`, `username`, `password`, `email`, `role`, `created_at`) VALUES
(1, 'superadmin', 'password', 'boss@pcshop.com', 'SuperAdmin', '2026-04-29 21:17:21');

-- --------------------------------------------------------

--
-- 表的结构 `bank`
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
-- 转存表中的数据 `bank`
--

INSERT INTO `bank` (`id`, `bank_name`, `cardholder_name`, `card_number`, `cvc`, `fpx_username`, `fpx_password`, `balance`) VALUES
(1, 'Maybank', 'Ali Bin Abu', '1111222233334444', '123', NULL, NULL, 8303.00),
(2, 'Maybank', 'Gan Sheng Wing', '9999888877776666', '999', NULL, NULL, 99999.00);

-- --------------------------------------------------------

--
-- 表的结构 `build_items`
--

CREATE TABLE `build_items` (
  `build_item_id` int(11) NOT NULL,
  `pc_build` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `build_items`
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
(252, 30, 31, 1),
(253, 30, 4, 1),
(254, 30, 12, 1),
(255, 30, 15, 1),
(256, 30, 8, 1),
(257, 30, 49, 1),
(258, 30, 20, 1),
(259, 30, 57, 1),
(260, 30, 22, 1),
(261, 30, 24, 1),
(262, 30, 25, 1),
(263, 31, 2, 1),
(264, 31, 4, 1),
(265, 31, 12, 1),
(266, 31, 54, 1),
(267, 31, 8, 1),
(268, 31, 16, 1),
(269, 31, 19, 1),
(270, 31, 18, 1),
(271, 31, 25, 1),
(272, 31, 23, 1),
(273, 31, 21, 1);

-- --------------------------------------------------------

--
-- 表的结构 `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `category_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `categories`
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
-- 表的结构 `community_comments`
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
-- 表的结构 `community_likes`
--

CREATE TABLE `community_likes` (
  `like_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `community_likes`
--

INSERT INTO `community_likes` (`like_id`, `post_id`, `customer_id`, `created_at`) VALUES
(2, 2, 5, '2026-05-02 22:31:33'),
(3, 1, 5, '2026-05-02 22:32:28');

-- --------------------------------------------------------

--
-- 表的结构 `community_posts`
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
-- 转存表中的数据 `community_posts`
--

INSERT INTO `community_posts` (`post_id`, `customer_id`, `pc_build_id`, `title`, `content`, `post_type`, `views`, `created_at`) VALUES
(1, 5, NULL, 'test', 'hello', 'Discussion', 0, '2026-05-01 22:36:47'),
(2, 5, 28, 'god', '。。。', 'Showcase', 0, '2026-05-02 22:31:12');

-- --------------------------------------------------------

--
-- 表的结构 `consultations`
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
-- 表的结构 `customers`
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
-- 转存表中的数据 `customers`
--

INSERT INTO `customers` (`customer_id`, `username`, `first_name`, `last_name`, `email`, `password`, `phone_number`, `birthday`, `wallet_balance`, `reward_coins`, `membership_tier`, `vip_expiry_date`, `auto_renew`, `default_shipping_address`, `account_status`, `reset_token`, `reset_token_expire`, `pref_gamer`, `pref_creator`, `pref_student`, `pref_enthusiast`, `created_at`) VALUES
(1, 'Sheng Wing Gan', NULL, NULL, 'ganshengwing1126@gmail.com', '$2y$10$6Na3FQF8P0dNwtlqRJrf2u4YNNXIohV5YkSx/KBPJtzqAY3RFGldG', NULL, NULL, 99972177.99, 0, 'Standard', NULL, 0, NULL, 'Active', NULL, NULL, 0, 0, 0, 0, '2026-04-30 20:09:29'),
(2, 'Sheng Gan', NULL, NULL, 'ganshengwing11226@gmail.com', '$2y$10$vW1.TGCWwWQMw8qP57pjjuoePphWACuonBYU6YnK6u/Kkvhd7bJ4a', NULL, NULL, 0.00, 0, 'Standard', NULL, 0, NULL, 'Active', NULL, NULL, 0, 0, 0, 0, '2026-04-30 20:09:29'),
(3, 'Sheng Gan', NULL, NULL, 'ganshengwing1126@yahoo.com', '$2y$10$P2hmbbymdla9zNVO1rI4TO/4I4LcSUfDgSkBPHxkl79J3Rc9VEwgO', NULL, NULL, 0.00, 0, 'Standard', NULL, 0, NULL, 'Active', NULL, NULL, 0, 0, 0, 0, '2026-04-30 20:09:29'),
(5, 'MrSuhaimi', 'XUAN', 'YEOH', 'queit0126@gmail.com', '$2y$10$7xIGYUoYA838MBDwMys20.mgW.n0jcHAKOsGCgHOf2tnyq3iKa/xO', NULL, NULL, 100455.00, 10000, 'VIP', '2026-06-08 09:57:49', 1, NULL, 'Active', '242270', '2026-05-12 18:54:26', 7, 5, 10, 0, '2026-05-01 13:59:14'),
(6, 'kyy', '何桥月光下', '奈', 'UIS292@gmail.com', '$2y$10$DfU8a04xIV3OhjZ.wZy5rOyFXBfivjKW8rijnqlMi.EcyUt93Pxcu', NULL, NULL, 0.00, 500, 'VIP', '2026-06-08 15:33:29', 0, NULL, 'Active', NULL, NULL, 5, 1, 0, 0, '2026-05-09 21:32:45');

-- --------------------------------------------------------

--
-- 表的结构 `customer_addresses`
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
-- 转存表中的数据 `customer_addresses`
--

INSERT INTO `customer_addresses` (`address_id`, `customer_id`, `recipient_name`, `phone_number`, `address_line1`, `address_line2`, `city`, `state`, `postcode`, `country`, `full_address`, `is_default`, `created_at`) VALUES
(1, 1, '', '', '', NULL, '', '', '', 'Malaysia', 'No 123, Jalan Multimedia, 63100 Cyberjaya, Selangor', 0, '2026-04-09 13:23:19'),
(2, 1, '', '', '', NULL, '', '', '', 'Malaysia', 'Sheng Wing Gan | 0162058560\na0805, 205 Short Rd\n05602 Berlin, Johor', 1, '2026-04-09 17:17:21'),
(4, 5, 'YEOH XUAN MING', '0122222620', '68,JALAN UTAMA28 TAMAN MUTIARA RINI', '', 'Johor Bahru', 'Johor', '81300', 'Malaysia', '68,JALAN UTAMA28 TAMAN MUTIARA RINI, 81300 Johor Bahru, Johor', 0, '2026-05-01 21:35:32'),
(5, 5, 'YYEYY', '0123456789', '58,Jalan Udara 22,Taman Universiti', '', 'perak', 'sembilan', '81365', 'Malaysia', '58,Jalan Udara 22,Taman Universiti, 81365 perak, sembilan', 1, '2026-05-01 21:39:12');

-- --------------------------------------------------------

--
-- 表的结构 `fpx_accounts`
--

CREATE TABLE `fpx_accounts` (
  `account_id` int(11) NOT NULL,
  `bank_name` varchar(50) NOT NULL COMMENT 'Maybank, CIMB, RHB, Public Bank',
  `username` varchar(100) NOT NULL COMMENT '网银登录账号',
  `password` varchar(255) NOT NULL COMMENT '网银登录密码',
  `balance` decimal(10,2) NOT NULL DEFAULT 10000.00 COMMENT '账户余额'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `fpx_accounts`
--

INSERT INTO `fpx_accounts` (`account_id`, `bank_name`, `username`, `password`, `balance`) VALUES
(1, 'Maybank', 'ganshengwing', '123456', 88888.00),
(2, 'CIMB Clicks', 'gancimb', '123456', 20000.00),
(3, 'RHB', 'shengwing_rhb', '123456', 50000.00);

-- --------------------------------------------------------

--
-- 表的结构 `orders`
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
-- 转存表中的数据 `orders`
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
(16, 'My Custom Order', 5, '2026-05-02 22:30:30', 74860.00, 0, 0.00, 'YYEYY | 0123456789\\r\\n58,Jalan Udara 22,Taman Universiti, 81365 perak, sembilan', '', 'Pending'),
(17, 'My Custom Order', 5, '2026-05-02 22:31:55', 6427.00, 0, 0.00, 'YYEYY | 0123456789\\r\\n58,Jalan Udara 22,Taman Universiti, 81365 perak, sembilan', '', 'Pending'),
(18, 'My Custom Order', 5, '2026-05-09 16:55:10', 10204.00, 550, 55.00, 'YYEYY | 0123456789\\r\\n58,Jalan Udara 22,Taman Universiti, 81365 perak, sembilan', '', 'Pending'),
(19, 'My Custom Order', 5, '2026-05-09 21:03:11', 45.00, 0, 0.00, 'YYEYY | 0123456789\\r\\n58,Jalan Udara 22,Taman Universiti, 81365 perak, sembilan', '', 'Pending');

-- --------------------------------------------------------

--
-- 表的结构 `order_details`
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
-- 转存表中的数据 `order_details`
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
(21, 19, 24, NULL, NULL, NULL, 1, 45.00);

-- --------------------------------------------------------

--
-- 表的结构 `packages`
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
-- 转存表中的数据 `packages`
--

INSERT INTO `packages` (`package_id`, `package_name`, `description`, `price`, `image_url`, `target_persona`, `stock_status`, `score_gamer`, `score_creator`, `score_student`, `score_enthusiast`, `created_at`) VALUES
(1, 'Esports Predator V1', 'Intel i5-13400F + RTX 4060. The ultimate sweet spot for 1080p competitive gaming and esports titles like Valorant and CS2.', 0.00, 'https://via.placeholder.com/300x200/ff007f/FFF?text=Esports+Predator', 'Gamer', 'Available', 9, 3, 1, 4, '2026-04-09 10:59:36'),
(2, 'Studio Pro Workstation', 'AMD Ryzen 9 7950X + 64GB DDR5 + RTX 4080. Built for 3D rendering, video editing, and heavy creative workloads.', 0.00, 'https://via.placeholder.com/300x200/0078D4/FFF?text=Studio+Pro', 'Creator', 'Available', 7, 10, 0, 5, '2026-04-09 10:59:36'),
(3, 'Campus Starter Box', 'Intel Core i3-12100 + 16GB RAM + 512GB NVMe. Fast, reliable, and affordable. Perfect for assignments, web browsing, and media consumption.', 0.00, 'https://via.placeholder.com/300x200/00e676/000?text=Campus+Starter', 'Student', 'Available', 1, 2, 10, 0, '2026-04-09 10:59:36'),
(4, 'Neon Liquid Beast', 'Intel Core i9-14900K + RTX 4090 + Custom Hard-Tube Liquid Cooling. For those who demand absolute perfection and maximum RGB aesthetics.', 0.00, 'https://via.placeholder.com/300x200/8a2be2/FFF?text=Neon+Liquid+Beast', 'Enthusiast', 'Available', 10, 8, 0, 10, '2026-04-09 10:59:36'),
(7, 'AMD Sweet Spot 1440p', 'Ryzen 7800X3D + RX 7800 XT. The absolute most cost-effective 1440p high-refresh-rate gaming machine available today.', 0.00, 'https://via.placeholder.com/300x200/ef4444/FFF?text=AMD+Sweet+Spot', 'Gamer', 'Available', 10, 4, 1, 5, '2026-04-30 11:22:59'),
(8, 'The 4K Juggernaut', 'Intel i7-14700K + RTX 4080 SUPER. Zero compromises. Built inside the gorgeous Lian Li O11 Dynamic.', 0.00, 'https://via.placeholder.com/300x200/00e676/000?text=4K+Juggernaut', 'Enthusiast', 'Available', 9, 8, 1, 10, '2026-04-30 11:22:59'),
(9, 'Video Editor Pro Mac-Killer', 'Intel i7-14700K + 64GB DDR5 + RTX 4060 Ti + 2TB Gen4 SSD. Optimized entirely for Adobe Premiere and After Effects.', 0.00, 'https://via.placeholder.com/300x200/a855f7/FFF?text=Editor+Pro', 'Creator', 'Available', 5, 10, 2, 6, '2026-04-30 11:22:59'),
(10, 'Campus Budget Brawler', 'Ryzen 7600 + 32GB RAM + 1TB SSD. Powerful enough for coding, multitasking, and light 1080p eSports gaming on a tight budget.', 0.00, 'https://via.placeholder.com/300x200/facc15/000?text=Campus+Brawler', 'Student', 'Available', 5, 3, 10, 0, '2026-04-30 11:22:59'),
(11, 'NVIDIA 1080p Ultra Rig', 'Intel i5-12400F + RTX 4060 Ti + 16GB RAM. Max out every setting at 1080p with DLSS 3 Frame Gen support.', 0.00, 'https://via.placeholder.com/300x200/00f2fe/000?text=1080p+Ultra', 'Gamer', 'Available', 8, 4, 5, 2, '2026-04-30 11:22:59'),
(12, 'Red Team Flagship', 'Ryzen 7800X3D + RX 7900 XTX 24GB + 360mm AIO. Unadulterated rasterization power designed to destroy 4K gaming.', 0.00, 'https://via.placeholder.com/300x200/8b0000/FFF?text=Red+Flagship', 'Enthusiast', 'Available', 10, 6, 1, 9, '2026-04-30 11:22:59'),
(13, 'Entry Code Compiler', 'Intel i5-12400F + RX 7600 + 32GB RAM. A highly efficient machine focused on RAM and fast storage for programming.', 0.00, 'https://via.placeholder.com/300x200/facc15/000?text=Entry+Compiler', 'Student', 'Available', 4, 3, 9, 0, '2026-04-30 11:22:59'),
(14, 'Studio 3D Renderer', 'Ryzen 9 7950X + 64GB RAM + RTX 4070 Ti SUPER. 16 massive cores designed specifically for Blender and Cinema4D.', 0.00, 'https://via.placeholder.com/300x200/a855f7/FFF?text=3D+Renderer', 'Creator', 'Available', 7, 10, 1, 7, '2026-04-30 11:22:59'),
(15, 'Esports 360Hz Champion', 'Ryzen 7800X3D + RTX 4070 Ti SUPER. Built to push 300+ FPS in CS2, Valorant, and Apex Legends.', 0.00, 'https://via.placeholder.com/300x200/00f2fe/000?text=Esports+360Hz', 'Gamer', 'Available', 9, 5, 1, 6, '2026-04-30 11:22:59'),
(16, 'Liquid Nitrogen Concept', 'Intel i9-13900K + RTX 4080 SUPER + 4TB SSD + Titanium PSU. The peak of PC building excess.', 0.00, 'https://via.placeholder.com/300x200/FFF/000?text=Liquid+Nitrogen', 'Enthusiast', 'Available', 9, 9, 0, 10, '2026-04-30 11:22:59');

-- --------------------------------------------------------

--
-- 表的结构 `package_items`
--

CREATE TABLE `package_items` (
  `id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `package_items`
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
(32, 4, 20),
(33, 7, 27),
(34, 7, 33),
(35, 7, 38),
(36, 7, 44),
(37, 7, 49),
(38, 7, 52),
(39, 7, 57),
(40, 7, 59),
(41, 7, 21),
(42, 8, 28),
(43, 8, 34),
(44, 8, 38),
(45, 8, 45),
(46, 8, 49),
(47, 8, 53),
(48, 8, 56),
(49, 8, 60),
(50, 8, 22),
(51, 9, 28),
(52, 9, 36),
(53, 9, 39),
(54, 9, 43),
(55, 9, 49),
(56, 9, 52),
(57, 9, 57),
(58, 9, 60),
(59, 9, 22),
(60, 10, 29),
(61, 10, 33),
(62, 10, 38),
(63, 10, 48),
(64, 10, 50),
(65, 10, 54),
(66, 10, 58),
(67, 10, 59),
(68, 10, 21),
(69, 11, 30),
(70, 11, 36),
(71, 11, 40),
(72, 11, 43),
(73, 11, 50),
(74, 11, 54),
(75, 11, 58),
(76, 11, 61),
(77, 11, 21),
(78, 12, 27),
(79, 12, 37),
(80, 12, 38),
(81, 12, 46),
(82, 12, 49),
(83, 12, 53),
(84, 12, 56),
(85, 12, 60),
(86, 12, 22),
(87, 13, 30),
(88, 13, 36),
(89, 13, 40),
(90, 13, 48),
(91, 13, 50),
(92, 13, 54),
(93, 13, 58),
(94, 13, 59),
(95, 13, 21),
(96, 14, 31),
(97, 14, 37),
(98, 14, 39),
(99, 14, 47),
(100, 14, 49),
(101, 14, 53),
(102, 14, 57),
(103, 14, 60),
(104, 14, 22),
(105, 15, 27),
(106, 15, 33),
(107, 15, 38),
(108, 15, 47),
(109, 15, 49),
(110, 15, 52),
(111, 15, 57),
(112, 15, 61),
(113, 15, 21),
(114, 16, 32),
(115, 16, 34),
(116, 16, 41),
(117, 16, 45),
(118, 16, 51),
(119, 16, 55),
(120, 16, 56),
(121, 16, 60),
(122, 16, 22);

-- --------------------------------------------------------

--
-- 表的结构 `payments`
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
-- 转存表中的数据 `payments`
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
(12, 19, 'E-Wallet', NULL, 'Paid', '2026-05-09 21:03:11');

-- --------------------------------------------------------

--
-- 表的结构 `products`
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
  `is_package` tinyint(1) DEFAULT 0,
  `socket_type` varchar(20) DEFAULT NULL COMMENT 'For CPU and Motherboard (e.g., LGA1700, AM5)',
  `ram_type` varchar(20) DEFAULT NULL COMMENT 'For Motherboard and RAM (e.g., DDR4, DDR5)',
  `performance_tier` int(2) DEFAULT 0 COMMENT '1-10 scale for AI Bottleneck calculation'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `products`
--

INSERT INTO `products` (`product_id`, `category_id`, `product_name`, `description`, `price`, `stock_quantity`, `image_url`, `status`, `tdp_wattage`, `is_package`, `socket_type`, `ram_type`, `performance_tier`) VALUES
(1, 1, 'Intel Core i5-13400F', 'Mainstream Intel Processor. Keyword: LGA1700', 950.00, 10, NULL, 'Available', 65, 0, 'LGA1700', NULL, 5),
(2, 1, 'Intel Core i9-14900K', 'Enthusiast Intel Processor (High TDP). Keyword: LGA1700', 2800.00, 5, NULL, 'Available', 253, 0, 'LGA1700', NULL, 10),
(3, 1, 'AMD Ryzen 5 7600X', 'Solid AMD Ryzen Processor. Keyword: AM5', 1100.00, 10, NULL, 'Available', 105, 0, 'AM5', NULL, 6),
(4, 2, 'ASUS ROG STRIX Z790-F LGA1700 DDR5', 'High-end Intel board, supports DDR5 memory only.', 1450.00, 8, NULL, 'Available', 30, 0, 'LGA1700', 'DDR5', 0),
(5, 2, 'MSI PRO H610M-G LGA1700 DDR4', 'Budget Intel board, supports DDR4 memory only.', 450.00, 15, NULL, 'Available', 20, 0, 'LGA1700', 'DDR4', 0),
(6, 2, 'Gigabyte B650 AORUS ELITE AX AM5 DDR5', 'Premium AMD board, supports DDR5 memory only.', 1350.00, 5, NULL, 'Available', 30, 0, 'AM5', 'DDR5', 0),
(7, 3, 'Kingston Fury Beast 16GB DDR4 3200MHz', 'Reliable standard DDR4 memory module.', 200.00, 30, NULL, 'Available', 8, 0, NULL, 'DDR4', 0),
(8, 3, 'Corsair Vengeance 32GB DDR5 6000MHz', 'High-speed DDR5 memory module for gaming.', 650.00, 20, NULL, 'Available', 10, 0, NULL, 'DDR5', 0),
(9, 3, 'G.Skill Trident Z5 RGB 64GB DDR5', 'Enthusiast DDR5 memory kit for heavy workloads.', 1200.00, 10, NULL, 'Available', 15, 0, NULL, 'DDR5', 0),
(10, 4, 'NVIDIA GeForce GT 730 2GB', 'Basic display output only (Will cause severe bottleneck with high-end CPUs).', 250.00, 20, NULL, 'Available', 30, 0, NULL, NULL, 1),
(11, 4, 'NVIDIA RTX 4070 SUPER 12GB', 'Sweet spot for 1440p gaming and rendering.', 3100.00, 10, NULL, 'Available', 220, 0, NULL, NULL, 7),
(12, 4, 'NVIDIA RTX 4090 24GB', 'Ultimate flagship GPU (Requires massive power supply).', 8500.00, 2, NULL, 'Available', 450, 0, NULL, NULL, 10),
(13, 6, 'Corsair CV550 550W', 'Entry-level power supply (550W).', 220.00, 15, NULL, 'Available', 550, 0, NULL, NULL, 0),
(14, 6, 'FSP Hydro G Pro 850W', 'High-end gold certified power supply (850W).', 600.00, 10, NULL, 'Available', 850, 0, NULL, NULL, 0),
(15, 6, 'ASUS ROG Thor 1200W', 'Platinum overkill power supply (1200W).', 1500.00, 3, NULL, 'Available', 1200, 0, NULL, NULL, 0),
(16, 5, 'Samsung 990 PRO 1TB NVMe', 'Top-tier M.2 NVMe SSD.', 550.00, 15, NULL, 'Available', 5, 0, NULL, NULL, 0),
(17, 5, 'WD Blue SN570 500GB NVMe', 'Budget-friendly fast storage.', 200.00, 25, NULL, 'Available', 5, 0, NULL, NULL, 0),
(18, 7, 'NZXT H5 Flow Black', 'High airflow premium chassis.', 400.00, 10, NULL, 'Available', 0, 0, NULL, NULL, 0),
(19, 8, 'Deepcool AK400 Air Cooler', 'Efficient standard air cooler.', 150.00, 20, NULL, 'Available', 0, 0, NULL, NULL, 0),
(20, 8, 'NZXT Kraken 360 RGB AIO', 'Premium liquid cooler with LCD.', 850.00, 8, NULL, 'Available', 15, 0, NULL, NULL, 0),
(21, 9, 'Microsoft Windows 11 Home 64-bit', 'Standard edition for gamers and home users. USB Flash Drive included.', 549.00, 10, 'https://via.placeholder.com/280x180/0078D4/FFF?text=Windows+11+Home', 'Available', 0, 0, NULL, NULL, 0),
(22, 9, 'Microsoft Windows 11 Pro 64-bit', 'Advanced features for professionals and developers. BitLocker included.', 899.00, 8, 'https://via.placeholder.com/280x180/111/FFF?text=Windows+11+Pro', 'Available', 0, 0, NULL, NULL, 0),
(23, 10, 'Corsair iCUE AR120 RGB 120mm (3-Pack)', 'High performance cooling fans with customizable RGB lighting sync.', 229.00, 10, 'https://via.placeholder.com/280x180/FF007F/FFF?text=Corsair+RGB+Fans', 'Available', 5, 0, NULL, NULL, 0),
(24, 10, 'ARCTIC P12 PWM PST 120mm', 'Pressure-optimized quiet fan for excellent airflow and low noise.', 45.00, 9, 'https://via.placeholder.com/280x180/333/FFF?text=Arctic+P12', 'Available', 2, 0, NULL, NULL, 0),
(25, 11, 'ASUS TUF Gaming VG27AQ 27\" 165Hz', '27-inch WQHD (2560x1440) IPS gaming monitor with ultrafast 165Hz refresh rate.', 1299.00, 12, 'https://via.placeholder.com/280x180/000/FFF?text=ASUS+TUF+27', 'Available', 0, 0, NULL, NULL, 0),
(26, 11, 'AOC 24G2SP 24\" 165Hz IPS', '24-inch Full HD (1920x1080) gaming monitor, perfect for esports.', 649.00, 10, 'https://via.placeholder.com/280x180/ff0000/FFF?text=AOC+24G2', 'Available', 0, 0, NULL, NULL, 0),
(27, 1, 'AMD Ryzen 7 7800X3D', 'The undisputed king of gaming CPUs. 3D V-Cache technology. Keyword: AM5', 1850.00, 25, 'https://via.placeholder.com/280x200/ef4444/FFF?text=R7+7800X3D', 'Available', 120, 0, 'AM5', NULL, 9),
(28, 1, 'Intel Core i7-14700K', '20-core powerhouse for rendering and intense gaming. Keyword: LGA1700', 1980.00, 15, 'https://via.placeholder.com/280x200/0078D4/FFF?text=i7-14700K', 'Available', 253, 0, 'LGA1700', NULL, 9),
(29, 1, 'AMD Ryzen 5 7600', 'Incredible value for AM5 platform, excellent 1080p/1440p performer. Keyword: AM5', 1050.00, 40, 'https://via.placeholder.com/280x200/ef4444/FFF?text=R5+7600', 'Available', 65, 0, 'AM5', NULL, 6),
(30, 1, 'Intel Core i5-12400F', 'The ultimate budget 6-core processor. Keyword: LGA1700', 580.00, 50, 'https://via.placeholder.com/280x200/0078D4/FFF?text=i5-12400F', 'Available', 65, 0, 'LGA1700', NULL, 5),
(31, 1, 'AMD Ryzen 9 7950X', '16-core rendering monster for ultimate creators. Keyword: AM5', 2850.00, 10, 'https://via.placeholder.com/280x200/ef4444/FFF?text=R9+7950X', 'Available', 170, 0, 'AM5', NULL, 10),
(32, 1, 'Intel Core i9-13900K', 'Previous gen flagship, still an absolute beast. Keyword: LGA1700', 2650.00, 12, 'https://via.placeholder.com/280x200/0078D4/FFF?text=i9-13900K', 'Available', 253, 0, 'LGA1700', NULL, 9),
(33, 2, 'MSI MAG B650 TOMAHAWK WIFI', 'Premium AM5 board with heavy VRM heatsinks. DDR5 only.', 1150.00, 20, 'https://via.placeholder.com/280x200/111/FFF?text=B650+TOMAHAWK', 'Available', 25, 0, 'AM5', 'DDR5', 0),
(34, 2, 'ASUS ROG STRIX B760-A GAMING WIFI', 'High-end Intel B760 DDR5 motherboard with supreme aesthetics.', 1100.00, 18, 'https://via.placeholder.com/280x200/222/FFF?text=ROG+B760-A', 'Available', 25, 0, 'LGA1700', 'DDR5', 0),
(35, 2, 'Gigabyte B550M DS3H', 'Budget king for older AM4 DDR4 builds.', 420.00, 30, 'https://via.placeholder.com/280x200/333/FFF?text=B550M+DS3H', 'Available', 15, 0, 'AM4', 'DDR4', 0),
(36, 2, 'ASRock B760M PRO RS/D4', 'Solid budget board for Intel 12th/13th/14th gen. DDR4 only.', 650.00, 25, 'https://via.placeholder.com/280x200/444/FFF?text=B760M+PRO+RS', 'Available', 20, 0, 'LGA1700', 'DDR4', 0),
(37, 2, 'ASUS ROG CROSSHAIR X670E HERO', 'Flagship AM5 board for extreme overclocking.', 3200.00, 5, 'https://via.placeholder.com/280x200/000/FFF?text=ROG+X670E', 'Available', 35, 0, 'AM5', 'DDR5', 0),
(38, 3, 'Corsair Vengeance 32GB (2x16GB) DDR5 6000MHz CL30', 'The sweet spot speed and latency for Ryzen 7000 series.', 580.00, 40, 'https://via.placeholder.com/280x200/222/FFF?text=Vengeance+32GB+DDR5', 'Available', 10, 0, NULL, 'DDR5', 0),
(39, 3, 'G.Skill Trident Z5 Neo RGB 64GB (2x32GB) DDR5 6000MHz', 'High-capacity, high-speed RAM for video editing.', 1150.00, 15, 'https://via.placeholder.com/280x200/111/FFF?text=Trident+Z5+64GB', 'Available', 12, 0, NULL, 'DDR5', 0),
(40, 3, 'Kingston FURY Beast 16GB (2x8GB) DDR4 3200MHz', 'Reliable budget DDR4 kit.', 190.00, 60, 'https://via.placeholder.com/280x200/000/FFF?text=FURY+16GB+DDR4', 'Available', 5, 0, NULL, 'DDR4', 0),
(41, 3, 'Corsair Dominator Titanium 32GB DDR5 7200MHz', 'Ultra-premium high-frequency memory for Intel builds.', 850.00, 10, 'https://via.placeholder.com/280x200/FFF/000?text=Dominator+Titanium', 'Available', 12, 0, NULL, 'DDR5', 0),
(42, 3, 'TeamGroup T-Force Delta RGB 32GB DDR4 3600MHz', 'Flashy RGB DDR4 kit for mid-range systems.', 380.00, 25, 'https://via.placeholder.com/280x200/333/FFF?text=T-Force+Delta', 'Available', 8, 0, NULL, 'DDR4', 0),
(43, 4, 'NVIDIA RTX 4060 Ti 8GB Founders', 'Excellent 1080p Ultra gaming with DLSS 3 Frame Gen.', 1850.00, 20, 'https://via.placeholder.com/280x200/00e676/000?text=RTX+4060+Ti', 'Available', 160, 0, NULL, NULL, 5),
(44, 4, 'AMD Radeon RX 7800 XT 16GB', 'Unbeatable 1440p value, massive VRAM for textures.', 2550.00, 18, 'https://via.placeholder.com/280x200/ef4444/FFF?text=RX+7800+XT', 'Available', 263, 0, NULL, NULL, 7),
(45, 4, 'NVIDIA RTX 4080 SUPER 16GB', 'Incredible 4K performance and ray tracing capabilities.', 4950.00, 10, 'https://via.placeholder.com/280x200/00e676/000?text=RTX+4080+SUPER', 'Available', 320, 0, NULL, NULL, 9),
(46, 4, 'AMD Radeon RX 7900 XTX 24GB', 'Raw rasterization monster, destroys 4K without breaking a sweat.', 4800.00, 8, 'https://via.placeholder.com/280x200/ef4444/FFF?text=RX+7900+XTX', 'Available', 355, 0, NULL, NULL, 9),
(47, 4, 'NVIDIA RTX 4070 Ti SUPER 16GB', 'Perfect 1440p high-refresh rate card.', 4100.00, 15, 'https://via.placeholder.com/280x200/00e676/000?text=RTX+4070+Ti+S', 'Available', 285, 0, NULL, NULL, 8),
(48, 4, 'AMD Radeon RX 7600 8GB', 'Budget king for entry-level 1080p gaming.', 1350.00, 25, 'https://via.placeholder.com/280x200/ef4444/FFF?text=RX+7600', 'Available', 165, 0, NULL, NULL, 4),
(49, 5, 'WD Black SN850X 2TB Gen4 NVMe', 'Top-tier speeds up to 7300MB/s.', 780.00, 25, 'https://via.placeholder.com/280x200/000/FFF?text=WD+Black+2TB', 'Available', 8, 0, NULL, NULL, 0),
(50, 5, 'Crucial P3 Plus 1TB Gen4 NVMe', 'Great balance of speed and affordability.', 280.00, 45, 'https://via.placeholder.com/280x200/111/FFF?text=Crucial+1TB', 'Available', 5, 0, NULL, NULL, 0),
(51, 5, 'Samsung 990 PRO 4TB NVMe', 'Massive fast storage for heavy video editors.', 1550.00, 8, 'https://via.placeholder.com/280x200/222/FFF?text=Samsung+4TB', 'Available', 10, 0, NULL, NULL, 0),
(52, 6, 'Corsair RM850e 850W 80+ Gold', 'Fully modular, ATX 3.0 ready.', 550.00, 20, 'https://via.placeholder.com/280x200/111/FFF?text=RM850e', 'Available', 850, 0, NULL, NULL, 0),
(53, 6, 'Seasonic Focus GX-1000 1000W Gold', 'Legendary reliability for high-end builds.', 850.00, 12, 'https://via.placeholder.com/280x200/000/FFF?text=Focus+1000W', 'Available', 1000, 0, NULL, NULL, 0),
(54, 6, 'MSI MAG A650BN 650W 80+ Bronze', 'Solid budget power supply.', 260.00, 30, 'https://via.placeholder.com/280x200/333/FFF?text=MAG+650W', 'Available', 650, 0, NULL, NULL, 0),
(55, 6, 'FSP Hydro Ti PRO 1000W Titanium', 'Ultra-premium titanium efficiency.', 1150.00, 5, 'https://via.placeholder.com/280x200/222/FFF?text=FSP+1000W', 'Available', 1000, 0, NULL, NULL, 0),
(56, 7, 'Lian Li O11 Dynamic EVO Black', 'The iconic showcase dual-chamber case.', 750.00, 15, 'https://via.placeholder.com/280x200/000/FFF?text=O11+EVO', 'Available', 0, 0, NULL, NULL, 0),
(57, 7, 'Corsair 4000D Airflow Black', 'Classic high-airflow mid-tower.', 380.00, 25, 'https://via.placeholder.com/280x200/111/FFF?text=4000D', 'Available', 0, 0, NULL, NULL, 0),
(58, 7, 'Montech X3 Mesh Black', 'Insane budget value, includes 6 RGB fans.', 220.00, 35, 'https://via.placeholder.com/280x200/222/FFF?text=Montech+X3', 'Available', 0, 0, NULL, NULL, 0),
(59, 8, 'Thermalright Peerless Assassin 120 SE', 'The dual-tower air cooler that beats 240mm AIOs.', 160.00, 40, 'https://via.placeholder.com/280x200/333/FFF?text=Peerless+Assassin', 'Available', 0, 0, NULL, NULL, 0),
(60, 8, 'Arctic Liquid Freezer III 360 AIO', 'Thick radiator, ultimate liquid cooling performance.', 520.00, 15, 'https://via.placeholder.com/280x200/000/FFF?text=Arctic+360', 'Available', 15, 0, NULL, NULL, 0),
(61, 8, 'Deepcool AK620 Digital', 'Premium air cooling with a digital temp display.', 320.00, 20, 'https://via.placeholder.com/280x200/111/FFF?text=AK620+Digital', 'Available', 0, 0, NULL, NULL, 0);

-- --------------------------------------------------------

--
-- 表的结构 `product_specifications`
--

CREATE TABLE `product_specifications` (
  `spec_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `spec_name` varchar(100) NOT NULL,
  `spec_value` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- 表的结构 `promo_codes`
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
-- 转存表中的数据 `promo_codes`
--

INSERT INTO `promo_codes` (`promo_id`, `code_name`, `target_category`, `is_vip_only`, `status`, `created_at`, `discount_value`, `discount_type`, `min_spend`, `max_cap`) VALUES
(1, 'VIPCOMP15', 'Components', 1, 'Active', '2026-05-07 12:15:57', 0.00, 'Percentage', 0.00, 0.00),
(2, 'VIPPC20', 'Packages', 1, 'Active', '2026-05-07 12:15:57', 0.00, 'Percentage', 0.00, 0.00),
(3, 'WELCOME10', 'All', 0, 'Active', '2026-05-07 12:15:57', 0.00, 'Percentage', 0.00, 0.00),
(4, 'SUMMER26', 'All', 0, 'Active', '2026-05-07 13:37:15', 0.00, 'Percentage', 0.00, 0.00),
(5, 'ELITEGAMER', 'Packages', 1, 'Active', '2026-05-07 13:37:15', 0.00, 'Percentage', 0.00, 0.00),
(6, 'UPGRADE5', 'Components', 0, 'Active', '2026-05-07 13:37:15', 0.00, 'Percentage', 0.00, 0.00),
(7, 'VIPPARTS12', 'Components', 1, 'Active', '2026-05-07 13:37:15', 0.00, 'Percentage', 0.00, 0.00),
(8, 'EXPIRED50', 'All', 0, 'Inactive', '2026-05-07 13:37:15', 0.00, 'Percentage', 0.00, 0.00);

-- --------------------------------------------------------

--
-- 表的结构 `reviews`
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
-- 转存表中的数据 `reviews`
--

INSERT INTO `reviews` (`review_id`, `product_id`, `customer_id`, `rating`, `comment`, `review_date`) VALUES
(1, 1, 1, 5, 'henbang', '2026-04-09 23:29:14');

-- --------------------------------------------------------

--
-- 表的结构 `saved_builds`
--

CREATE TABLE `saved_builds` (
  `pc_build` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `build_name` varchar(100) DEFAULT 'My Custom PC',
  `total_price` decimal(10,2) DEFAULT 0.00,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `saved_builds`
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
(30, 6, 'godlike', 19203.00, '2026-05-11 01:04:45'),
(31, 6, 'error', 16837.00, '2026-05-11 01:10:28');

-- --------------------------------------------------------

--
-- 表的结构 `saved_cards`
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
-- 转存表中的数据 `saved_cards`
--

INSERT INTO `saved_cards` (`card_id`, `customer_id`, `bank_id`, `cardholder_name`, `last_four_digits`, `expiry_date`, `card_brand`, `is_default`, `created_at`) VALUES
(1, 1, NULL, '22', '5353', '11/24', 'Credit Card', 0, '2026-04-09 23:11:21'),
(2, 1, NULL, '日人人日r', '2331', '22/11', 'Credit Card', 1, '2026-04-11 17:06:45');

-- --------------------------------------------------------

--
-- 表的结构 `shopping_cart`
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
-- 转存表中的数据 `shopping_cart`
--

INSERT INTO `shopping_cart` (`cart_id`, `customer_id`, `product_id`, `pc_build`, `package_id`, `affiliate_id`, `quantity`, `added_at`) VALUES
(9, 3, NULL, 14, NULL, NULL, 1, '2026-04-06 09:17:24'),
(19, 1, 4, NULL, NULL, NULL, 1, '2026-04-29 23:38:35'),
(20, 1, NULL, NULL, 4, NULL, 2, '2026-04-30 00:06:55'),
(39, 5, 26, NULL, NULL, NULL, 1, '2026-05-09 21:11:22'),
(40, 6, NULL, 31, NULL, NULL, 1, '2026-05-11 01:10:28');

-- --------------------------------------------------------

--
-- 表的结构 `used_vouchers`
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
-- 表的结构 `wallet_transactions`
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
-- 转存表中的数据 `wallet_transactions`
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
(10, 5, 'Top-up', 100000.00, 10000, '2026-05-09 21:04:18');

--
-- 转储表的索引
--

--
-- 表的索引 `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- 表的索引 `bank`
--
ALTER TABLE `bank`
  ADD PRIMARY KEY (`id`);

--
-- 表的索引 `build_items`
--
ALTER TABLE `build_items`
  ADD PRIMARY KEY (`build_item_id`),
  ADD KEY `build_id` (`pc_build`),
  ADD KEY `product_id` (`product_id`);

--
-- 表的索引 `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`);

--
-- 表的索引 `community_comments`
--
ALTER TABLE `community_comments`
  ADD PRIMARY KEY (`comment_id`),
  ADD KEY `fk_community_comments_post` (`post_id`),
  ADD KEY `fk_community_comments_customer` (`customer_id`);

--
-- 表的索引 `community_likes`
--
ALTER TABLE `community_likes`
  ADD PRIMARY KEY (`like_id`),
  ADD UNIQUE KEY `unique_like` (`post_id`,`customer_id`),
  ADD KEY `fk_community_likes_customer` (`customer_id`);

--
-- 表的索引 `community_posts`
--
ALTER TABLE `community_posts`
  ADD PRIMARY KEY (`post_id`),
  ADD KEY `fk_community_posts_customer` (`customer_id`),
  ADD KEY `fk_community_posts_build` (`pc_build_id`);

--
-- 表的索引 `consultations`
--
ALTER TABLE `consultations`
  ADD PRIMARY KEY (`consultation_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- 表的索引 `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`customer_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- 表的索引 `customer_addresses`
--
ALTER TABLE `customer_addresses`
  ADD PRIMARY KEY (`address_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- 表的索引 `fpx_accounts`
--
ALTER TABLE `fpx_accounts`
  ADD PRIMARY KEY (`account_id`);

--
-- 表的索引 `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- 表的索引 `order_details`
--
ALTER TABLE `order_details`
  ADD PRIMARY KEY (`order_detail_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `fk_order_details_build` (`pc_build`),
  ADD KEY `fk_order_affiliate` (`affiliate_id`);

--
-- 表的索引 `packages`
--
ALTER TABLE `packages`
  ADD PRIMARY KEY (`package_id`);

--
-- 表的索引 `package_items`
--
ALTER TABLE `package_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `package_id` (`package_id`),
  ADD KEY `product_id` (`product_id`);

--
-- 表的索引 `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `order_id` (`order_id`);

--
-- 表的索引 `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `idx_builder_socket` (`socket_type`),
  ADD KEY `idx_builder_ram` (`ram_type`),
  ADD KEY `idx_builder_tier` (`performance_tier`);

--
-- 表的索引 `product_specifications`
--
ALTER TABLE `product_specifications`
  ADD PRIMARY KEY (`spec_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `idx_spec_search` (`spec_name`,`spec_value`);

--
-- 表的索引 `promo_codes`
--
ALTER TABLE `promo_codes`
  ADD PRIMARY KEY (`promo_id`),
  ADD UNIQUE KEY `code_name` (`code_name`);

--
-- 表的索引 `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- 表的索引 `saved_builds`
--
ALTER TABLE `saved_builds`
  ADD PRIMARY KEY (`pc_build`),
  ADD KEY `customer_id` (`customer_id`);

--
-- 表的索引 `saved_cards`
--
ALTER TABLE `saved_cards`
  ADD PRIMARY KEY (`card_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `fk_saved_cards_bank` (`bank_id`);

--
-- 表的索引 `shopping_cart`
--
ALTER TABLE `shopping_cart`
  ADD PRIMARY KEY (`cart_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `fk_shopping_cart_pc_build` (`pc_build`),
  ADD KEY `fk_cart_affiliate` (`affiliate_id`);

--
-- 表的索引 `used_vouchers`
--
ALTER TABLE `used_vouchers`
  ADD PRIMARY KEY (`used_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `promo_id` (`promo_id`);

--
-- 表的索引 `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- 在导出的表使用AUTO_INCREMENT
--

--
-- 使用表AUTO_INCREMENT `admins`
--
ALTER TABLE `admins`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用表AUTO_INCREMENT `bank`
--
ALTER TABLE `bank`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- 使用表AUTO_INCREMENT `build_items`
--
ALTER TABLE `build_items`
  MODIFY `build_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=274;

--
-- 使用表AUTO_INCREMENT `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- 使用表AUTO_INCREMENT `community_comments`
--
ALTER TABLE `community_comments`
  MODIFY `comment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `community_likes`
--
ALTER TABLE `community_likes`
  MODIFY `like_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- 使用表AUTO_INCREMENT `community_posts`
--
ALTER TABLE `community_posts`
  MODIFY `post_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- 使用表AUTO_INCREMENT `consultations`
--
ALTER TABLE `consultations`
  MODIFY `consultation_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `customers`
--
ALTER TABLE `customers`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- 使用表AUTO_INCREMENT `customer_addresses`
--
ALTER TABLE `customer_addresses`
  MODIFY `address_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- 使用表AUTO_INCREMENT `fpx_accounts`
--
ALTER TABLE `fpx_accounts`
  MODIFY `account_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- 使用表AUTO_INCREMENT `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- 使用表AUTO_INCREMENT `order_details`
--
ALTER TABLE `order_details`
  MODIFY `order_detail_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- 使用表AUTO_INCREMENT `packages`
--
ALTER TABLE `packages`
  MODIFY `package_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- 使用表AUTO_INCREMENT `package_items`
--
ALTER TABLE `package_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=123;

--
-- 使用表AUTO_INCREMENT `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- 使用表AUTO_INCREMENT `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- 使用表AUTO_INCREMENT `product_specifications`
--
ALTER TABLE `product_specifications`
  MODIFY `spec_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `promo_codes`
--
ALTER TABLE `promo_codes`
  MODIFY `promo_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- 使用表AUTO_INCREMENT `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用表AUTO_INCREMENT `saved_builds`
--
ALTER TABLE `saved_builds`
  MODIFY `pc_build` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- 使用表AUTO_INCREMENT `saved_cards`
--
ALTER TABLE `saved_cards`
  MODIFY `card_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- 使用表AUTO_INCREMENT `shopping_cart`
--
ALTER TABLE `shopping_cart`
  MODIFY `cart_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- 使用表AUTO_INCREMENT `used_vouchers`
--
ALTER TABLE `used_vouchers`
  MODIFY `used_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- 限制导出的表
--

--
-- 限制表 `build_items`
--
ALTER TABLE `build_items`
  ADD CONSTRAINT `build_items_ibfk_1` FOREIGN KEY (`pc_build`) REFERENCES `saved_builds` (`pc_build`) ON DELETE CASCADE,
  ADD CONSTRAINT `build_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- 限制表 `community_comments`
--
ALTER TABLE `community_comments`
  ADD CONSTRAINT `community_comments_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `community_posts` (`post_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `community_comments_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_community_comments_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_community_comments_post` FOREIGN KEY (`post_id`) REFERENCES `community_posts` (`post_id`) ON DELETE CASCADE;

--
-- 限制表 `community_likes`
--
ALTER TABLE `community_likes`
  ADD CONSTRAINT `community_likes_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `community_posts` (`post_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `community_likes_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_community_likes_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_community_likes_post` FOREIGN KEY (`post_id`) REFERENCES `community_posts` (`post_id`) ON DELETE CASCADE;

--
-- 限制表 `community_posts`
--
ALTER TABLE `community_posts`
  ADD CONSTRAINT `community_posts_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `community_posts_ibfk_2` FOREIGN KEY (`pc_build_id`) REFERENCES `saved_builds` (`pc_build`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_community_posts_build` FOREIGN KEY (`pc_build_id`) REFERENCES `saved_builds` (`pc_build`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_community_posts_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE;

--
-- 限制表 `consultations`
--
ALTER TABLE `consultations`
  ADD CONSTRAINT `consultations_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE;

--
-- 限制表 `customer_addresses`
--
ALTER TABLE `customer_addresses`
  ADD CONSTRAINT `customer_addresses_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE;

--
-- 限制表 `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE;

--
-- 限制表 `order_details`
--
ALTER TABLE `order_details`
  ADD CONSTRAINT `fk_order_affiliate` FOREIGN KEY (`affiliate_id`) REFERENCES `customers` (`customer_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_order_details_build` FOREIGN KEY (`pc_build`) REFERENCES `saved_builds` (`pc_build`) ON DELETE SET NULL,
  ADD CONSTRAINT `order_details_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_details_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE SET NULL;

--
-- 限制表 `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE;

--
-- 限制表 `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE CASCADE;

--
-- 限制表 `product_specifications`
--
ALTER TABLE `product_specifications`
  ADD CONSTRAINT `product_specifications_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- 限制表 `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE;

--
-- 限制表 `saved_builds`
--
ALTER TABLE `saved_builds`
  ADD CONSTRAINT `saved_builds_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE;

--
-- 限制表 `saved_cards`
--
ALTER TABLE `saved_cards`
  ADD CONSTRAINT `fk_saved_cards_bank` FOREIGN KEY (`bank_id`) REFERENCES `bank` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `saved_cards_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE;

--
-- 限制表 `shopping_cart`
--
ALTER TABLE `shopping_cart`
  ADD CONSTRAINT `fk_cart_affiliate` FOREIGN KEY (`affiliate_id`) REFERENCES `customers` (`customer_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_shopping_cart_pc_build` FOREIGN KEY (`pc_build`) REFERENCES `saved_builds` (`pc_build`) ON DELETE CASCADE,
  ADD CONSTRAINT `shopping_cart_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `shopping_cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- 限制表 `used_vouchers`
--
ALTER TABLE `used_vouchers`
  ADD CONSTRAINT `fk_used_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_used_promo` FOREIGN KEY (`promo_id`) REFERENCES `promo_codes` (`promo_id`) ON DELETE CASCADE;

--
-- 限制表 `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  ADD CONSTRAINT `wallet_transactions_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`customer_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
