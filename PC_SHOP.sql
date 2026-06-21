-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- 主机： 127.0.0.1
-- 生成日期： 2026-06-21 18:17:13
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
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expire` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `admins`
--

INSERT INTO `admins` (`admin_id`, `username`, `password`, `email`, `role`, `reset_token`, `reset_token_expire`, `created_at`) VALUES
(1, 'Alvis', 'Alvis@100884', 'chenweishen8733@gmail.com', 'SuperAdmin', NULL, NULL, '2026-04-29 21:17:21'),
(6, 'admin', '$2y$10$eEJCvFMxRls.uVHpHNhmE.RfF/tCcUAzzEO1j8tv9anwNH2UqEpje', 'admin123@gmail.com', 'Admin', NULL, NULL, '2026-06-16 17:19:24'),
(7, 'OC alvis', '$2y$10$FRlZ4BIls3e9qOk49vx1xe.IN8qveQxwVmYiJGeNi384ieZVBuDn6', 'ocalvis88@gmail.com', 'Admin', NULL, NULL, '2026-06-17 16:24:45');

-- --------------------------------------------------------

--
-- 表的结构 `admin_logs`
--

CREATE TABLE `admin_logs` (
  `log_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `role` varchar(20) NOT NULL,
  `action_event` varchar(255) NOT NULL DEFAULT 'System Login',
  `ip_address` varchar(45) NOT NULL,
  `login_time` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `admin_logs`
--

INSERT INTO `admin_logs` (`log_id`, `admin_id`, `username`, `role`, `action_event`, `ip_address`, `login_time`) VALUES
(1, 4, 'admin', 'Admin', 'System Login', '127.0.0.1', '2026-06-15 11:20:48'),
(2, 1, 'Alvis', 'SuperAdmin', 'System Login', '127.0.0.1', '2026-06-15 11:21:35'),
(3, 1, 'Alvis', 'SuperAdmin', 'Modified Blueprint ID: 12', '127.0.0.1', '2026-06-15 11:51:52'),
(4, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 54', '127.0.0.1', '2026-06-15 11:58:08'),
(5, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 61', '127.0.0.1', '2026-06-15 13:42:41'),
(6, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 60', '127.0.0.1', '2026-06-15 13:42:46'),
(7, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 61', '127.0.0.1', '2026-06-15 13:53:58'),
(8, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 60', '127.0.0.1', '2026-06-15 13:54:22'),
(9, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 59', '127.0.0.1', '2026-06-15 13:54:59'),
(10, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 58', '127.0.0.1', '2026-06-15 14:10:09'),
(11, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 58', '127.0.0.1', '2026-06-15 14:10:12'),
(12, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 58', '127.0.0.1', '2026-06-15 14:11:09'),
(13, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 57', '127.0.0.1', '2026-06-15 14:13:28'),
(14, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 56', '127.0.0.1', '2026-06-15 14:13:46'),
(15, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 56', '127.0.0.1', '2026-06-15 14:14:18'),
(16, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 57', '127.0.0.1', '2026-06-15 14:14:23'),
(17, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 57', '127.0.0.1', '2026-06-15 14:14:28'),
(18, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 56', '127.0.0.1', '2026-06-15 14:19:52'),
(19, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 56', '127.0.0.1', '2026-06-15 14:19:59'),
(20, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 55', '127.0.0.1', '2026-06-15 14:37:02'),
(21, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 54', '127.0.0.1', '2026-06-15 14:37:56'),
(22, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 53', '127.0.0.1', '2026-06-15 14:40:07'),
(23, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 54', '127.0.0.1', '2026-06-15 14:40:19'),
(24, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 53', '127.0.0.1', '2026-06-15 14:40:30'),
(25, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 52', '127.0.0.1', '2026-06-15 14:41:08'),
(26, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 51', '127.0.0.1', '2026-06-15 14:42:57'),
(27, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 50', '127.0.0.1', '2026-06-15 14:45:58'),
(28, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 49', '127.0.0.1', '2026-06-15 14:47:55'),
(29, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 48', '127.0.0.1', '2026-06-15 14:48:05'),
(30, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 47', '127.0.0.1', '2026-06-15 14:56:40'),
(31, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 46', '127.0.0.1', '2026-06-15 14:58:35'),
(32, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 47', '127.0.0.1', '2026-06-15 14:58:42'),
(33, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 46', '127.0.0.1', '2026-06-15 14:58:48'),
(34, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 45', '127.0.0.1', '2026-06-15 15:03:24'),
(35, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 44', '127.0.0.1', '2026-06-15 15:05:40'),
(36, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 43', '127.0.0.1', '2026-06-15 15:06:52'),
(37, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 42', '127.0.0.1', '2026-06-15 15:07:06'),
(38, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 41', '127.0.0.1', '2026-06-15 15:07:16'),
(39, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 40', '127.0.0.1', '2026-06-15 15:07:44'),
(40, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 39', '127.0.0.1', '2026-06-15 15:07:59'),
(41, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 40', '127.0.0.1', '2026-06-15 15:08:05'),
(42, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 38', '127.0.0.1', '2026-06-15 15:08:13'),
(43, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 37', '127.0.0.1', '2026-06-15 15:10:34'),
(44, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 36', '127.0.0.1', '2026-06-15 15:17:18'),
(45, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 35', '127.0.0.1', '2026-06-15 15:18:50'),
(46, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 34', '127.0.0.1', '2026-06-15 15:20:41'),
(47, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 33', '127.0.0.1', '2026-06-15 15:22:40'),
(48, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 32', '127.0.0.1', '2026-06-15 15:22:50'),
(49, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 31', '127.0.0.1', '2026-06-15 15:22:59'),
(50, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 30', '127.0.0.1', '2026-06-15 15:23:04'),
(51, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 29', '127.0.0.1', '2026-06-15 15:23:10'),
(52, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 27', '127.0.0.1', '2026-06-15 15:23:16'),
(53, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 26', '127.0.0.1', '2026-06-15 15:24:05'),
(54, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 25', '127.0.0.1', '2026-06-15 15:25:30'),
(55, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 24', '127.0.0.1', '2026-06-15 15:26:25'),
(56, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 23', '127.0.0.1', '2026-06-15 15:26:33'),
(57, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 22', '127.0.0.1', '2026-06-15 15:26:43'),
(58, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 21', '127.0.0.1', '2026-06-15 15:26:50'),
(59, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 20', '127.0.0.1', '2026-06-15 15:26:57'),
(60, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 19', '127.0.0.1', '2026-06-15 15:28:06'),
(61, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 18', '127.0.0.1', '2026-06-15 15:28:28'),
(62, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 17', '127.0.0.1', '2026-06-15 15:28:38'),
(63, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 16', '127.0.0.1', '2026-06-15 15:28:47'),
(64, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 15', '127.0.0.1', '2026-06-15 15:30:14'),
(65, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 14', '127.0.0.1', '2026-06-15 15:32:07'),
(66, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 13', '127.0.0.1', '2026-06-15 15:34:13'),
(67, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 12', '127.0.0.1', '2026-06-15 15:34:49'),
(68, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 11', '127.0.0.1', '2026-06-15 15:36:26'),
(69, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 10', '127.0.0.1', '2026-06-15 15:37:08'),
(70, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 9', '127.0.0.1', '2026-06-15 15:37:21'),
(71, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 8', '127.0.0.1', '2026-06-15 15:37:26'),
(72, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 7', '127.0.0.1', '2026-06-15 15:37:31'),
(73, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 6', '127.0.0.1', '2026-06-15 15:40:13'),
(74, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 5', '127.0.0.1', '2026-06-15 15:42:09'),
(75, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 4', '127.0.0.1', '2026-06-15 15:44:23'),
(76, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 3', '127.0.0.1', '2026-06-15 15:45:33'),
(77, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 3', '127.0.0.1', '2026-06-15 15:45:39'),
(78, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 2', '127.0.0.1', '2026-06-15 15:45:43'),
(79, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 1', '127.0.0.1', '2026-06-15 15:45:47'),
(80, 1, 'Alvis', 'SuperAdmin', 'System Login', '127.0.0.1', '2026-06-16 16:40:18'),
(81, 1, 'Alvis', 'SuperAdmin', 'Modified Product ID: 19', '127.0.0.1', '2026-06-16 16:42:59'),
(82, 1, 'Alvis', 'SuperAdmin', 'Added New Staff: admin', '127.0.0.1', '2026-06-16 17:19:24'),
(83, 6, 'admin', 'Admin', 'System Login', '127.0.0.1', '2026-06-16 17:19:33'),
(84, 1, 'Alvis', 'SuperAdmin', 'System Login', '127.0.0.1', '2026-06-16 17:27:17'),
(85, 1, 'Alvis', 'SuperAdmin', 'System Login', '127.0.0.1', '2026-06-17 13:48:38'),
(86, 1, 'Alvis', 'SuperAdmin', 'System Login', '127.0.0.1', '2026-06-17 16:24:13'),
(87, 1, 'Alvis', 'SuperAdmin', 'Added New Staff: OC alvis', '127.0.0.1', '2026-06-17 16:24:45'),
(88, 1, 'Alvis', 'SuperAdmin', 'System Login', '127.0.0.1', '2026-06-17 16:25:03'),
(89, 7, 'OC alvis', 'Admin', 'System Login', '127.0.0.1', '2026-06-17 16:25:24'),
(90, 1, 'Alvis', 'SuperAdmin', 'System Login', '127.0.0.1', '2026-06-17 16:28:03'),
(91, 7, 'OC alvis', 'Admin', 'System Login', '127.0.0.1', '2026-06-17 16:28:32'),
(92, 7, 'OC alvis', 'Admin', 'Modified Staff Profile ID: 7', '127.0.0.1', '2026-06-17 16:33:23'),
(93, 6, 'admin', 'Admin', 'System Login', '127.0.0.1', '2026-06-17 16:36:41'),
(94, 7, 'OC alvis', 'Admin', 'System Login', '127.0.0.1', '2026-06-17 16:42:25'),
(95, 1, 'Alvis', 'SuperAdmin', 'System Login', '127.0.0.1', '2026-06-17 17:04:03'),
(96, 1, 'Alvis', 'SuperAdmin', 'Modified Staff Profile ID: 6', '127.0.0.1', '2026-06-17 17:04:30'),
(97, 6, 'admin', 'Admin', 'System Login', '127.0.0.1', '2026-06-17 17:04:49'),
(98, 1, 'Alvis', 'SuperAdmin', 'System Login', '127.0.0.1', '2026-06-17 17:12:46'),
(99, 6, 'admin', 'Admin', 'System Login', '127.0.0.1', '2026-06-17 17:16:59'),
(100, 1, 'Alvis', 'SuperAdmin', 'System Login', '127.0.0.1', '2026-06-17 17:18:07'),
(101, 7, 'OC alvis', 'Admin', 'System Login', '127.0.0.1', '2026-06-17 17:33:01'),
(102, 7, 'OC alvis', 'Admin', 'Modified Staff Profile ID: 7', '127.0.0.1', '2026-06-17 17:39:44'),
(103, 1, 'Alvis', 'SuperAdmin', 'System Login', '127.0.0.1', '2026-06-17 17:43:34'),
(104, 1, 'Alvis', 'SuperAdmin', 'System Login', '127.0.0.1', '2026-06-18 11:32:34'),
(105, 6, 'admin', 'Admin', 'System Login', '127.0.0.1', '2026-06-18 11:36:26'),
(106, 1, 'Alvis', 'SuperAdmin', 'System Login', '127.0.0.1', '2026-06-18 11:39:20'),
(107, 1, 'Alvis', 'SuperAdmin', 'System Login', '127.0.0.1', '2026-06-18 13:45:13'),
(108, 1, 'Alvis', 'SuperAdmin', 'Modified Staff Profile ID: 1', '127.0.0.1', '2026-06-18 20:28:50'),
(109, 1, 'Alvis', 'SuperAdmin', 'Pinned Post ID: 3', '', '2026-06-18 21:05:54'),
(110, 1, 'Alvis', 'SuperAdmin', 'Unpinned Post ID: 3', '', '2026-06-18 21:05:55'),
(111, 1, 'Alvis', 'SuperAdmin', 'Censored Post ID: 3', '', '2026-06-18 21:07:57'),
(112, 1, 'Alvis', 'SuperAdmin', 'Censored Post ID: 1', '', '2026-06-18 21:25:54'),
(113, 1, 'Alvis', 'SuperAdmin', 'Censored Post ID: 2', '', '2026-06-18 21:25:57'),
(114, 1, 'Alvis', 'SuperAdmin', 'Censored Post ID: 2', '', '2026-06-18 21:36:49'),
(115, 1, 'Alvis', 'SuperAdmin', 'Pinned Post ID: 3', '', '2026-06-18 21:38:48'),
(116, 1, 'Alvis', 'SuperAdmin', 'Unpinned Post ID: 3', '', '2026-06-18 21:39:54'),
(117, 1, 'Alvis', 'SuperAdmin', 'Pinned Post ID: 1', '', '2026-06-18 21:39:56'),
(118, 1, 'Alvis', 'SuperAdmin', 'Unpinned Post ID: 1', '', '2026-06-18 21:39:59'),
(119, 1, 'Alvis', 'SuperAdmin', 'System Login', '127.0.0.1', '2026-06-18 21:46:48'),
(120, 1, 'Alvis', 'SuperAdmin', 'Pinned Post ID: 2', '', '2026-06-18 21:50:34'),
(121, 1, 'Alvis', 'SuperAdmin', 'Pinned Post ID: 3', '', '2026-06-18 21:50:38'),
(122, 1, 'Alvis', 'SuperAdmin', 'Unpinned Post ID: 3', '', '2026-06-18 21:51:14'),
(123, 1, 'Alvis', 'SuperAdmin', 'Unpinned Post ID: 2', '', '2026-06-18 21:51:16'),
(124, 1, 'Alvis', 'SuperAdmin', 'Changed User ID: 6 status to Banned', '', '2026-06-18 21:57:59'),
(125, 1, 'Alvis', 'SuperAdmin', 'System Login', '127.0.0.1', '2026-06-18 21:59:36'),
(126, 1, 'Alvis', 'SuperAdmin', 'Changed User ID: 6 status to Active', '', '2026-06-18 21:59:45'),
(127, 1, 'Alvis', 'SuperAdmin', 'Changed User ID: 6 status to Banned', '', '2026-06-18 21:59:53'),
(128, 1, 'Alvis', 'SuperAdmin', 'Changed User ID: 6 status to Active', '', '2026-06-18 22:01:47'),
(129, 1, 'Alvis', 'SuperAdmin', 'System Login', '127.0.0.1', '2026-06-18 22:02:24'),
(130, 1, 'Alvis', 'SuperAdmin', 'Changed User ID: 6 status to Banned', '', '2026-06-18 22:02:30'),
(131, 1, 'Alvis', 'SuperAdmin', 'Changed User ID: 6 status to Active', '', '2026-06-18 22:02:32'),
(132, 1, 'Alvis', 'SuperAdmin', 'Changed User ID: 6 status to Banned', '', '2026-06-18 22:12:09'),
(133, 1, 'Alvis', 'SuperAdmin', 'Changed User ID: 6 status to Active', '', '2026-06-18 22:13:14'),
(134, 1, 'Alvis', 'SuperAdmin', 'Changed User ID: 6 status to Banned', '', '2026-06-18 22:13:57'),
(135, 1, 'Alvis', 'SuperAdmin', 'Unbanned User ID: 6', '', '2026-06-18 22:18:48'),
(136, 1, 'Alvis', 'SuperAdmin', 'Muted User ID: 6', '', '2026-06-18 22:44:55'),
(137, 1, 'Alvis', 'SuperAdmin', 'Unmuted User ID: 6', '', '2026-06-18 23:07:24'),
(138, 1, 'Alvis', 'SuperAdmin', 'System Login', '127.0.0.1', '2026-06-20 02:10:17'),
(139, 1, 'Alvis', 'SuperAdmin', 'System Login', '127.0.0.1', '2026-06-20 15:08:32'),
(140, 1, 'Alvis', 'SuperAdmin', 'Muted User ID: 6', '', '2026-06-20 15:08:49'),
(141, 1, 'Alvis', 'SuperAdmin', 'Unmuted User ID: 6', '', '2026-06-20 15:43:23'),
(142, 1, 'Alvis', 'SuperAdmin', 'System Login', '127.0.0.1', '2026-06-20 22:04:50');

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
  `expiry_date` varchar(5) NOT NULL DEFAULT '12/30' COMMENT 'MM/YY Format',
  `fpx_username` varchar(100) DEFAULT NULL,
  `fpx_password` varchar(255) DEFAULT NULL,
  `balance` decimal(10,2) DEFAULT 50000.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `bank`
--

INSERT INTO `bank` (`id`, `bank_name`, `cardholder_name`, `card_number`, `cvc`, `expiry_date`, `fpx_username`, `fpx_password`, `balance`) VALUES
(1, 'Maybank', 'Ali Bin Abu', '1111222233334444', '123', '12/30', NULL, NULL, 8303.00),
(2, 'Maybank', 'Gan Sheng Wing', '9999888877776666', '999', '12/30', NULL, NULL, 928419.10),
(3, 'Maybank', 'FPX User 1', '0000', '000', '12/30', 'ganshengwing', '123456', 76101.00);

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
(9, 'Operating System', 'Primary system software that manages computer hardware and software resources'),
(10, 'Case Fans', 'electrical cooling units installed inside a computer case to circulate air'),
(11, 'Monitor', 'Converts visual data from a computer into an easily readable format');

-- --------------------------------------------------------

--
-- 表的结构 `community_comments`
--

CREATE TABLE `community_comments` (
  `comment_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `comment` text NOT NULL COMMENT 'User reply content',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `community_comments`
--

INSERT INTO `community_comments` (`comment_id`, `post_id`, `customer_id`, `comment`, `created_at`) VALUES
(1, 8, 6, 'NICE', '2026-06-20 15:44:30');

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
(3, 1, 5, '2026-05-02 22:32:28'),
(5, 2, 6, '2026-05-18 01:55:38'),
(6, 4, 6, '2026-06-18 22:54:25');

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
  `post_images` longtext DEFAULT NULL COMMENT 'Store image paths separated by commas',
  `post_type` enum('Showcase','Discussion','Question') DEFAULT 'Discussion',
  `views` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `is_pinned` tinyint(1) DEFAULT 0,
  `report_count` int(11) DEFAULT 0,
  `is_flagged` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `community_posts`
--

INSERT INTO `community_posts` (`post_id`, `customer_id`, `pc_build_id`, `title`, `content`, `post_images`, `post_type`, `views`, `created_at`, `is_pinned`, `report_count`, `is_flagged`) VALUES
(1, 5, NULL, 'test', 'hello', NULL, 'Discussion', 0, '2026-05-01 22:36:47', 0, 0, 0),
(2, 5, 28, 'god', '。。。', NULL, 'Showcase', 0, '2026-05-02 22:31:12', 0, 0, 0),
(3, 6, NULL, 'hi dear suhaimi', 'i just wanna say ur build is godlike ahahhaha', NULL, 'Discussion', 0, '2026-05-18 01:56:15', 0, 0, 0),
(4, 6, NULL, '3333', '3333', '[\"image\\/cmty_6a34064e79ba6_4355.png\",\"image\\/cmty_6a34064e7a0cf_2939.png\",\"image\\/cmty_6a34064e7a548_8811.png\",\"image\\/cmty_6a34064e7ae0e_8658.png\",\"image\\/cmty_6a34064e7b219_9907.png\",\"image\\/cmty_6a34064e7b63a_4140.png\"]', 'Discussion', 0, '2026-06-18 22:53:02', 0, 0, 0),
(5, 6, NULL, '22342让', '44444', NULL, 'Discussion', 0, '2026-06-18 23:06:35', 0, 0, 0),
(6, 11, NULL, '-', 'what&#039;s the good spec examples?', NULL, 'Discussion', 0, '2026-06-20 02:39:07', 0, 0, 0),
(7, 13, NULL, 'HI', 'FINALLY I AM PRO NOW', NULL, 'Question', 0, '2026-06-20 03:01:46', 0, 0, 0),
(8, 6, 41, 'SHOWCASES1', '-', NULL, 'Showcase', 0, '2026-06-20 15:43:58', 0, 0, 0);

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
  `lifetime_coins` int(11) DEFAULT 0,
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

INSERT INTO `customers` (`customer_id`, `username`, `first_name`, `last_name`, `email`, `password`, `phone_number`, `birthday`, `wallet_balance`, `reward_coins`, `lifetime_coins`, `membership_tier`, `vip_expiry_date`, `auto_renew`, `default_shipping_address`, `account_status`, `reset_token`, `reset_token_expire`, `pref_gamer`, `pref_creator`, `pref_student`, `pref_enthusiast`, `created_at`) VALUES
(1, 'Sheng Wing Gan', NULL, NULL, 'ganshengwing1126@gmail.com', '$2y$10$6Na3FQF8P0dNwtlqRJrf2u4YNNXIohV5YkSx/KBPJtzqAY3RFGldG', NULL, NULL, 99972177.99, 0, 0, 'Standard', NULL, 0, NULL, 'Active', NULL, NULL, 0, 0, 0, 0, '2026-04-30 20:09:29'),
(3, 'Sheng Gan', NULL, NULL, 'ganshengwing1126@yahoo.com', '$2y$10$P2hmbbymdla9zNVO1rI4TO/4I4LcSUfDgSkBPHxkl79J3Rc9VEwgO', NULL, NULL, 0.00, 6, 0, 'Standard', NULL, 0, NULL, 'Active', NULL, NULL, 0, 0, 0, 0, '2026-04-30 20:09:29'),
(5, 'MrSuhaimi', 'XUAN', 'YEOH', 'queit0126@gmail.com', '$2y$10$7xIGYUoYA838MBDwMys20.mgW.n0jcHAKOsGCgHOf2tnyq3iKa/xO', NULL, NULL, 110105.00, 1502, 500, 'VIP', '2026-07-08 09:57:49', 1, NULL, 'Active', '242270', '2026-05-12 18:54:26', 7, 5, 10, 0, '2026-05-01 13:59:14'),
(6, 'kskbl', '何桥月光下', '奈', 'UIS292@gmail.com', '$2y$10$DfU8a04xIV3OhjZ.wZy5rOyFXBfivjKW8rijnqlMi.EcyUt93Pxcu', '+60122222620', '2025-11-17', 7391.00, 3877, 3377, 'VIP', '2026-07-18 17:07:34', 1, NULL, 'Active', NULL, NULL, 27, 44, 13, 0, '2026-05-09 21:32:45'),
(7, 'XUANMING0126', NULL, NULL, 'chenweishen8733@gmail.com', '$2y$10$t1mb1tQakaIxjZZJG/2/RurpbpIpkGQ9mObmsvcM9AFz.I0ZskP3.', '', '2026-05-18', 0.00, 0, 0, 'Standard', NULL, 0, NULL, 'Active', NULL, NULL, 0, 0, 0, 0, '2026-05-18 16:39:41'),
(8, 'Alvis', NULL, NULL, 'ocalvis88@gmail.com', '$2y$10$JHAUBkQ2sgoDKHebVWIvNe7uUqsr3XUVHonZzXlpWy83oOcnjen4W', '', '2005-10-07', 105.00, 0, 0, 'Standard', NULL, 0, NULL, 'Active', NULL, NULL, 0, 0, 0, 0, '2026-05-21 22:15:47'),
(9, 'eee', NULL, NULL, 'qu22eit0126@gmail.com', '$2y$10$GjGIWiIk0yC5pcOWCjqiAutd8tFXSs7POpYJdJi/uvP/1j.C2.Pbu', NULL, NULL, 0.00, 0, 0, 'VIP', '2026-07-19 20:00:18', 1, NULL, 'Active', NULL, NULL, 0, 0, 0, 0, '2026-06-20 02:00:13'),
(10, 'prof', NULL, NULL, 'gg0126@gmail.com', '$2y$10$cHLLO2NbqdDqoItVVnEuNOFrNmUPIDUkHor54jt1KUgh5H5HHBX9G', NULL, NULL, 15500.00, 1550, 1050, 'Standard', NULL, 0, NULL, 'Active', NULL, NULL, 0, 0, 0, 0, '2026-06-20 02:02:47'),
(11, 'seed', NULL, NULL, 'queit01226@gmail.com', '$2y$10$8np7BO0qZJYzIgMbZdY3Uu5pkXtVgqaCS0QfZxhY7WfFtJ3wKNCWi', NULL, NULL, 982.00, 0, 2000, 'Standard', NULL, 0, NULL, 'Active', NULL, NULL, 0, 0, 0, 0, '2026-06-20 02:13:16'),
(12, 'test2', NULL, NULL, 'quei22t0126@gmail.com', '$2y$10$7ZHRydnpFcZn68JvfUoCe.ggRkm50NUhBllCpw/HXuifIH0EpmODS', NULL, NULL, 20000.00, 2000, 2000, 'Standard', NULL, 0, NULL, 'Active', NULL, NULL, 0, 0, 0, 0, '2026-06-20 02:52:55'),
(13, 'SSSSSS', NULL, NULL, 'queit01X26@gmail.com', '$2y$10$MPeiirADAFUCt7Eqopaa2euWmpm.ljMj3DPnT7BxyFexEaWAUx2r2', NULL, NULL, 5000.00, 500, 500, 'Standard', NULL, 0, NULL, 'Active', NULL, NULL, 0, 0, 0, 0, '2026-06-20 03:00:20'),
(14, 'kskbl22', NULL, NULL, 'queit220126@gmail.com', '$2y$10$Dhx7O886ykAP6haVez4b.ONmJNGnbKBdXn2Wwtbcg0tAdyxpO2LcW', NULL, NULL, 0.00, 0, 0, 'Standard', NULL, 0, NULL, 'Active', NULL, NULL, 0, 0, 0, 0, '2026-06-20 13:44:29'),
(15, 'QIYUE', NULL, NULL, 'queit1234@gmail.com', '$2y$10$EgRvryjdEeF8Ydq5JZ.boujJVBUWjRYlLY0A1RK6qZgzqbT1eXPPi', NULL, NULL, 0.00, 0, 0, 'Standard', NULL, 0, NULL, 'Active', NULL, NULL, 0, 0, 0, 0, '2026-06-20 20:32:12');

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
  `full_address` longtext NOT NULL,
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
(5, 5, 'YYEYY', '0123456789', '58,Jalan Udara 22,Taman Universiti', '', 'perak', 'sembilan', '81365', 'Malaysia', '58,Jalan Udara 22,Taman Universiti, 81365 perak, sembilan', 1, '2026-05-01 21:39:12'),
(8, 6, 'YEOH XUAN MING', '+60122222620', '68,JALAN UTAMA28 TAMAN MUTIARA RINI', '', 'Johor Bahru', 'Johor', '81300', 'Malaysia', '68,JALAN UTAMA28 TAMAN MUTIARA RINI, 81300 Johor Bahru, Johor', 1, '2026-05-18 02:03:47'),
(9, 7, 'Sheng Wing Gan', '+60162058560', '205 Short Rd', '', 'Berlin', 'VT', '81300', 'Malaysia', '205 Short Rd, 81300 Berlin, VT', 1, '2026-05-18 16:41:57'),
(10, 8, 'Alvis', '+601158534889', '2812, Jalan Sri Putri 10/2', '', 'Kulai', 'Johor', '81000', 'Malaysia', '2812, Jalan Sri Putri 10/2, 81000 Kulai, Johor', 1, '2026-05-22 21:43:08'),
(11, 10, 'XUAN MING YEOH', '+60122222620', '68,JALAN UTAMA28 TAMAN MUTIARA RINI', '', 'Johor Bahru', 'Johor', '81300', 'Malaysia', '68,JALAN UTAMA28 TAMAN MUTIARA RINI, 81300 Johor Bahru, Johor', 0, '2026-06-20 02:09:34'),
(12, 11, 'XUAN MING YEOH', '+60122222620', '68,JALAN UTAMA28 TAMAN MUTIARA RINI', '', 'Johor Bahru', 'Johor', '81300', 'Malaysia', '68,JALAN UTAMA28 TAMAN MUTIARA RINI, 81300 Johor Bahru, Johor', 0, '2026-06-20 02:15:24');

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
  `contact_number` varchar(20) DEFAULT NULL,
  `order_status` varchar(20) DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `orders`
--

INSERT INTO `orders` (`order_id`, `order_name`, `customer_id`, `order_date`, `total_amount`, `coins_used`, `discount_amount`, `shipping_address`, `contact_number`, `order_status`) VALUES
(7, 'My Custom Order', 1, '2026-04-09 19:20:44', 21680.00, 0, 0.00, 'Sheng Wing Gan | 0162058560\\r\\na0805, 205 Short Rd\\r\\n05602 Berlin, Johor', '', 'Completed'),
(8, 'My Custom Order', 1, '2026-04-09 19:22:37', 0.00, 1000000888, 99999999.99, 'Sheng Wing Gan | 0162058560\\r\\na0805, 205 Short Rd\\r\\n05602 Berlin, Johor', '', 'Cancelled'),
(9, 'My Custom Order', 1, '2026-04-09 19:30:08', 6697.00, 0, 0.00, 'Sheng Wing Gan | 0162058560\\r\\na0805, 205 Short Rd\\r\\n05602 Berlin, Johor', '', 'Pending'),
(10, 'My Custom Order', 1, '2026-04-09 19:35:48', 6692.00, 55, 5.00, 'Sheng Wing Gan | 0162058560\\r\\na0805, 205 Short Rd\\r\\n05602 Berlin, Johor', '', 'Pending'),
(11, 's', 1, '2026-04-09 23:11:35', 6047.00, 0, 0.00, 'Sheng Wing Gan | 0162058560\\r\\na0805, 205 Short Rd\\r\\n05602 Berlin, Johor', '', 'Pending'),
(12, 'My Custom Order', 1, '2026-04-09 23:28:54', 950.00, 0, 0.00, 'Gan Sheng Wing | 012-3456789\nMMU Cyberjaya', '012-3456789', 'Completed'),
(13, 'My Custom Order', 1, '2026-04-11 17:26:52', 6697.00, 0, 0.00, 'Sheng Wing Gan | 0162058560\\r\\na0805, 205 Short Rd\\r\\n05602 Berlin, Johor', '', 'Pending'),
(14, 'My Custom Order', 1, '2026-04-11 17:41:34', 6697.00, 0, 0.00, 'Sheng Wing Gan | 0162058560\\r\\na0805, 205 Short Rd\\r\\n05602 Berlin, Johor', '', 'Pending'),
(15, 'My Custom Order', 1, '2026-04-19 23:30:46', 6697.00, 0, 0.00, 'Sheng Wing Gan | 0162058560\\r\\na0805, 205 Short Rd\\r\\n05602 Berlin, Johor', '', 'Pending'),
(16, 'My Custom Order', 5, '2026-05-02 22:30:30', 74860.00, 0, 0.00, 'YYEYY | 0123456789\\r\\n58,Jalan Udara 22,Taman Universiti, 81365 perak, sembilan', '', 'Completed'),
(17, 'My Custom Order', 5, '2026-05-02 22:31:55', 6427.00, 0, 0.00, 'YYEYY | 0123456789\\r\\n58,Jalan Udara 22,Taman Universiti, 81365 perak, sembilan', '', 'Shipped'),
(18, 'My Custom Order', 5, '2026-05-09 16:55:10', 10204.00, 550, 55.00, 'YYEYY | 0123456789\\r\\n58,Jalan Udara 22,Taman Universiti, 81365 perak, sembilan', '', 'Pending'),
(19, 'My Custom Order', 5, '2026-05-09 21:03:11', 45.00, 0, 0.00, 'YYEYY | 0123456789\\r\\n58,Jalan Udara 22,Taman Universiti, 81365 perak, sembilan', '', 'Pending'),
(20, 'My Custom Order', 6, '2026-05-17 23:43:50', 10727.00, 0, 0.00, 'YYEYY | 01233226201323232\n68,JALAN UTAMA28 TAMAN MUTIARA RINI, 81300 Johor Bahru, Johor', NULL, 'Pending'),
(21, 'My Custom Order', 6, '2026-05-18 00:13:55', 10727.00, 0, 0.00, 'YYEYY | 01233226201323232\n68,JALAN UTAMA28 TAMAN MUTIARA RINI, 81300 Johor Bahru, Johor', NULL, 'Shipped'),
(22, 'My Custom Order', 6, '2026-05-18 00:28:23', 10672.00, 550, 55.00, 'YYEYY | 01233226201323232\n68,JALAN UTAMA28 TAMAN MUTIARA RINI, 81300 Johor Bahru, Johor', NULL, 'Shipped'),
(23, 'My Custom Order', 6, '2026-05-18 00:31:22', 11787.00, 0, 0.00, 'YYEYY | 01233226201323232\n68,JALAN UTAMA28 TAMAN MUTIARA RINI, 81300 Johor Bahru, Johor', NULL, 'Shipped'),
(24, 'My Custom Order', 5, '2026-05-18 16:45:44', 33750.00, 0, 0.00, 'YYEYY | 0123456789\n58,Jalan Udara 22,Taman Universiti, 81365 perak, sembilan', NULL, 'Shipped'),
(25, 'My Custom Order', 8, '2026-05-22 21:44:38', 2550.00, 0, 0.00, 'Alvis | +601158534889\n2812, Jalan Sri Putri 10/2, 81000 Kulai, Johor', NULL, 'Completed'),
(26, 'My Custom Order', 8, '2026-06-17 19:18:59', 45.00, 0, 0.00, 'Alvis | +601158534889\n2812, Jalan Sri Putri 10/2, 81000 Kulai, Johor', NULL, 'Shipped'),
(27, 'My Custom Order', 8, '2026-06-18 21:41:10', 350.00, 10000, 1000.00, 'Alvis | +601158534889\n2812, Jalan Sri Putri 10/2, 81000 Kulai, Johor', NULL, 'Completed'),
(28, 'My Custom Order', 5, '2026-06-20 01:42:45', 350.00, 10000, 1000.00, 'YYEYY | 0123456789\n58,Jalan Udara 22,Taman Universiti, 81365 perak, sembilan', NULL, 'Pending'),
(29, 'My Custom Order', 6, '2026-06-20 01:52:03', 465.00, 550, 55.00, 'YEOH XUAN MING | +60122222620\n68,JALAN UTAMA28 TAMAN MUTIARA RINI, 81300 Johor Bahru, Johor', NULL, 'Pending'),
(30, 'My Custom Order', 10, '2026-06-20 02:09:49', 1350.00, 0, 0.00, 'XUAN MING YEOH | +60122222620\n68,JALAN UTAMA28 TAMAN MUTIARA RINI, 81300 Johor Bahru, Johor', NULL, 'Completed'),
(31, 'My Custom Order', 11, '2026-06-20 02:15:56', 1350.00, 0, 0.00, 'XUAN MING YEOH | +60122222620\n68,JALAN UTAMA28 TAMAN MUTIARA RINI, 81300 Johor Bahru, Johor', NULL, 'Pending'),
(32, 'My Custom Order', 11, '2026-06-20 02:18:44', 19018.00, 2000, 200.00, 'XUAN MING YEOH | +60122222620\n68,JALAN UTAMA28 TAMAN MUTIARA RINI, 81300 Johor Bahru, Johor', NULL, 'Pending'),
(33, 'My Custom Order', 6, '2026-06-20 20:50:36', 8609.00, 0, 150.00, 'YEOH XUAN MING | +60122222620\n68,JALAN UTAMA28 TAMAN MUTIARA RINI, 81300 Johor Bahru, Johor', NULL, 'Pending'),
(34, 'My Custom Order', 6, '2026-06-20 21:54:48', 8759.00, 0, 0.00, 'YEOH XUAN MING | +60122222620\n68,JALAN UTAMA28 TAMAN MUTIARA RINI, 81300 Johor Bahru, Johor', NULL, 'Pending'),
(35, 'My Custom Order', 6, '2026-06-20 21:55:54', 6427.00, 0, 0.00, 'YEOH XUAN MING | +60122222620\n68,JALAN UTAMA28 TAMAN MUTIARA RINI, 81300 Johor Bahru, Johor', NULL, 'Cancelled');

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
  `unit_price` decimal(10,2) NOT NULL,
  `return_status` varchar(50) DEFAULT NULL,
  `return_reason` text DEFAULT NULL,
  `return_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `order_details`
--

INSERT INTO `order_details` (`order_detail_id`, `order_id`, `product_id`, `pc_build`, `package_id`, `affiliate_id`, `quantity`, `unit_price`, `return_status`, `return_reason`, `return_image`) VALUES
(1, 7, NULL, 9, NULL, NULL, 1, 4620.00, NULL, NULL, NULL),
(2, 7, NULL, 10, NULL, NULL, 1, 7820.00, NULL, NULL, NULL),
(3, 7, NULL, 11, NULL, NULL, 1, 4620.00, NULL, NULL, NULL),
(4, 7, NULL, 13, NULL, NULL, 1, 4620.00, NULL, NULL, NULL),
(5, 8, NULL, 15, NULL, NULL, 1, 10677.00, NULL, NULL, NULL),
(6, 9, NULL, 16, NULL, NULL, 1, 6697.00, NULL, NULL, NULL),
(7, 10, NULL, 17, NULL, NULL, 1, 6697.00, NULL, NULL, NULL),
(8, 11, NULL, 18, NULL, NULL, 1, 6047.00, NULL, NULL, NULL),
(9, 12, 1, NULL, NULL, NULL, 1, 950.00, NULL, NULL, NULL),
(10, 13, NULL, 20, NULL, NULL, 1, 6697.00, NULL, NULL, NULL),
(11, 14, NULL, 21, NULL, NULL, 1, 6697.00, NULL, NULL, NULL),
(12, 15, NULL, 22, NULL, NULL, 1, 6697.00, NULL, NULL, NULL),
(13, 16, NULL, NULL, 16, NULL, 2, 14419.00, NULL, NULL, NULL),
(14, 16, NULL, NULL, 14, NULL, 1, 14729.00, NULL, NULL, NULL),
(15, 16, NULL, 26, NULL, NULL, 1, 11787.00, NULL, NULL, NULL),
(16, 16, NULL, NULL, 3, NULL, 1, 2820.00, NULL, NULL, NULL),
(17, 16, NULL, NULL, 15, NULL, 1, 10259.00, NULL, NULL, NULL),
(18, 16, NULL, 29, NULL, NULL, 1, 6427.00, NULL, NULL, NULL),
(19, 17, NULL, 28, NULL, NULL, 1, 6427.00, NULL, NULL, NULL),
(20, 18, NULL, NULL, 15, NULL, 1, 10259.00, NULL, NULL, NULL),
(21, 19, 24, NULL, NULL, NULL, 1, 45.00, NULL, NULL, NULL),
(22, 20, NULL, NULL, NULL, NULL, 1, 10727.00, NULL, NULL, NULL),
(23, 21, NULL, NULL, NULL, NULL, 1, 10727.00, NULL, NULL, NULL),
(24, 22, NULL, NULL, NULL, NULL, 1, 10727.00, NULL, NULL, NULL),
(25, 23, NULL, NULL, NULL, NULL, 1, 11787.00, NULL, NULL, NULL),
(26, 24, 48, NULL, NULL, NULL, 25, 1350.00, NULL, NULL, NULL),
(27, 25, 44, NULL, NULL, NULL, 1, 2550.00, NULL, NULL, NULL),
(28, 26, 24, NULL, NULL, NULL, 1, 45.00, NULL, NULL, NULL),
(29, 27, 48, NULL, NULL, NULL, 1, 1350.00, NULL, NULL, NULL),
(30, 28, 48, NULL, NULL, NULL, 1, 1350.00, NULL, NULL, NULL),
(31, 29, 60, NULL, NULL, NULL, 1, 520.00, NULL, NULL, NULL),
(32, 30, 48, NULL, NULL, NULL, 1, 1350.00, NULL, NULL, NULL),
(33, 31, 48, NULL, NULL, NULL, 1, 1350.00, NULL, NULL, NULL),
(34, 32, NULL, NULL, 14, NULL, 1, 16668.00, NULL, NULL, NULL),
(35, 32, 44, NULL, NULL, NULL, 1, 2550.00, NULL, NULL, NULL),
(36, 33, NULL, NULL, 9, NULL, 1, 8759.00, NULL, NULL, NULL),
(37, 34, NULL, NULL, 9, NULL, 1, 8759.00, NULL, NULL, NULL),
(38, 35, NULL, 28, NULL, 5, 1, 6427.00, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- 表的结构 `packages`
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
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `packages`
--

INSERT INTO `packages` (`package_id`, `package_name`, `description`, `price`, `image_url`, `target_persona`, `stock_status`, `score_gamer`, `score_creator`, `score_student`, `score_enthusiast`, `created_at`) VALUES
(1, 'Esports Predator V1', 'Intel i5-13400F + RTX 4060. The ultimate sweet spot for 1080p competitive gaming and esports titles like Valorant and CS2.', 0.00, 'image/pkg_6a26b69ebfb83.png', 'Gamer', 'Available', 9, 3, 1, 4, '2026-04-09 18:59:36'),
(3, 'Campus Starter Box', 'Intel Core i3-12100 + 16GB RAM + 512GB NVMe. Fast, reliable, and affordable. Perfect for assignments, web browsing, and media consumption.', 0.00, 'image/pkg_6a26b698c725e.png', 'Student', 'Available', 1, 2, 10, 0, '2026-04-09 18:59:36'),
(4, 'Neon Liquid Beast', 'Intel Core i9-14900K + RTX 4090 + Custom Hard-Tube Liquid Cooling. For those who demand absolute perfection and maximum RGB aesthetics.', 0.00, 'image/pkg_6a26b690bf805.png', 'Enthusiast', 'Available', 10, 8, 0, 10, '2026-04-09 18:59:36'),
(7, 'AMD Sweet Spot 1440p', 'Ryzen 7800X3D + RX 7800 XT. The absolute most cost-effective 1440p high-refresh-rate gaming machine available today.', 0.00, 'image/pkg_6a26b687232a5.png', 'Gamer', 'Available', 10, 4, 1, 5, '2026-04-30 19:22:59'),
(8, 'The 4K Juggernaut', 'Intel i7-14700K + RTX 4080 SUPER. Zero compromises. Built inside the gorgeous Lian Li O11 Dynamic.', 0.00, 'image/pkg_6a26b67ec1402.png', 'Enthusiast', 'Available', 9, 8, 1, 10, '2026-04-30 19:22:59'),
(9, 'Video Editor Pro Mac-Killer', 'Intel i7-14700K + 64GB DDR5 + RTX 4060 Ti + 2TB Gen4 SSD. Optimized entirely for Adobe Premiere and After Effects.', 0.00, 'image/pkg_6a26b6775b696.png', 'Creator', 'Available', 5, 10, 2, 6, '2026-04-30 19:22:59'),
(10, 'Campus Budget Brawler', 'Ryzen 7600 + 32GB RAM + 1TB SSD. Powerful enough for coding, multitasking, and light 1080p eSports gaming on a tight budget.', 0.00, 'image/pkg_6a26b66d33484.png', 'Student', 'Available', 5, 3, 10, 0, '2026-04-30 19:22:59'),
(11, 'NVIDIA 1080p Ultra Rig', 'Intel i5-12400F + RTX 4060 Ti + 16GB RAM. Max out every setting at 1080p with DLSS 3 Frame Gen support.', 0.00, 'image/pkg_6a26b665349a3.png', 'Gamer', 'Available', 8, 4, 5, 2, '2026-04-30 19:22:59'),
(12, 'Red Team Flagship', 'Ryzen 7800X3D + RX 7900 XTX 24GB + 360mm AIO. Unadulterated rasterization power designed to destroy 4K gaming.', 14229.00, 'image/pkg_6a26b6582bee3.png', '', 'Available', 10, 6, 1, 9, '2026-04-30 19:22:59'),
(13, 'Entry Code Compiler', 'Intel i5-12400F + RX 7600 + 32GB RAM. A highly efficient machine focused on RAM and fast storage for programming.', 0.00, 'image/pkg_6a26b6503331a.png', 'Student', 'Available', 4, 3, 9, 0, '2026-04-30 19:22:59'),
(14, 'Studio 3D Renderer', 'Ryzen 9 7950X + 64GB RAM + RTX 4070 Ti SUPER. 16 massive cores designed specifically for Blender and Cinema4D.', 0.00, 'image/pkg_6a26b647d53f6.png', 'Creator', 'Available', 7, 10, 1, 7, '2026-04-30 19:22:59'),
(15, 'Esports 360Hz Champion', 'Ryzen 7800X3D + RTX 4070 Ti SUPER. Built to push 300+ FPS in CS2, Valorant, and Apex Legends.', 0.00, 'image/pkg_6a26b63ee8b49.png', 'Gamer', 'Available', 9, 5, 1, 6, '2026-04-30 19:22:59'),
(16, 'Liquid Nitrogen Concept', 'Intel i9-13900K + RTX 4080 SUPER + 4TB SSD + Titanium PSU. The peak of PC building excess.', 0.00, 'image/pkg_6a26b636477a7.png', 'Enthusiast', 'Available', 9, 9, 0, 10, '2026-04-30 19:22:59');

-- --------------------------------------------------------

--
-- 表的结构 `package_items`
--

CREATE TABLE `package_items` (
  `id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- 转存表中的数据 `package_items`
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
(377, 1, 19, 1),
(389, 12, 27, 1),
(390, 12, 37, 1),
(391, 12, 38, 1),
(392, 12, 46, 1),
(393, 12, 49, 1),
(394, 12, 53, 1),
(395, 12, 56, 1),
(396, 12, 60, 1),
(397, 12, 22, 1);

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
(12, 19, 'E-Wallet', NULL, 'Paid', '2026-05-09 21:03:11'),
(13, 20, 'Cash on Delivery', NULL, 'Pending', '2026-05-17 23:43:50'),
(14, 21, 'Visa ending in 6666', NULL, 'Paid', '2026-05-18 00:13:55'),
(15, 22, 'Credit Card ending in 6666', NULL, 'Paid', '2026-05-18 00:28:23'),
(16, 23, 'FPX - Maybank2U', NULL, 'Paid', '2026-05-18 00:31:22'),
(17, 24, 'Visa ending in 6666', NULL, 'Paid', '2026-05-18 16:45:44'),
(18, 25, 'Visa ending in 6666', NULL, 'Paid', '2026-05-22 21:44:38'),
(19, 26, 'E-Wallet', NULL, 'Paid', '2026-06-17 19:18:59'),
(20, 27, 'E-Wallet', NULL, 'Paid', '2026-06-18 21:41:10'),
(21, 28, 'E-Wallet', NULL, 'Paid', '2026-06-20 01:42:45'),
(22, 29, 'Credit Card ending in 6666', NULL, 'Paid', '2026-06-20 01:52:03'),
(23, 30, 'Credit Card ending in 6666', NULL, 'Paid', '2026-06-20 02:09:49'),
(24, 31, 'Credit Card ending in 6666', NULL, 'Paid', '2026-06-20 02:15:56'),
(25, 32, 'E-Wallet', NULL, 'Paid', '2026-06-20 02:18:44'),
(26, 33, 'E-Wallet', NULL, 'Paid', '2026-06-20 20:50:36'),
(27, 34, 'Credit Card ending in 6666', NULL, 'Paid', '2026-06-20 21:54:48'),
(28, 35, 'Credit Card ending in 6666', NULL, 'Paid', '2026-06-20 21:55:54');

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
-- 转存表中的数据 `products`
--

INSERT INTO `products` (`product_id`, `category_id`, `product_name`, `description`, `price`, `stock_quantity`, `specifications`, `image_url`, `status`, `tdp_wattage`, `is_package`, `socket_type`, `ram_type`, `performance_tier`) VALUES
(1, 1, 'Intel Core i5-13400F', 'Mainstream Intel Processor. Keyword: LGA1700', 950.00, 10, 'Architecture: Raptor Lake\r\nSocket: LGA 1700 (compatible with 600 and 700 series chipsets)\r\nClock Speeds: Up to 4.6 GHz Max Turbo\r\nCache: 20 MB Intel Smart Cache\r\nMemory Support: Supports both DDR4 and DDR5 RAM\r\nPower: 65W Processor Base Power', 'image/prod_6a26ba76b793d.jpg', 'Available', 65, 0, 'LGA1700', '', 5),
(2, 1, 'Intel Core i9-14900K', 'Enthusiast Intel Processor (High TDP). Keyword: LGA1700', 2800.00, 5, 'Total Cores: 24 (8 P-cores, 16 E-cores)\r\nTotal Threads: 32\r\nMax Turbo Frequency: Up to 6.0 GHz\r\nP-Core Base / Max Frequency: 3.2 GHz / 5.6 GHz\r\nE-Core Base / Max Frequency: 2.4 GHz / 4.4 GHz\r\nCache: 36 MB Intel Smart Cache (32 MB L2)\r\nProcessor Base Power: 125 W\r\nMaximum Turbo Power: 253 W\r\nLithography: Intel 7 (10 nm)\r\nSocket: LGA 1700\r\nMemory Support: Up to 192 GB DDR5 5600 MT/s or DDR4 3200 MT/s\r\nntegrated Graphics: Intel UHD Graphics 770', 'image/prod_6a26ba590e38e.jpg', 'Available', 253, 0, 'LGA1700', '', 10),
(3, 1, 'AMD Ryzen 5 7600X', 'Solid AMD Ryzen Processor. Keyword: AM5', 1100.00, 7, '- Architecture: Zen 4 (TSMC 5nm)\r\n- CPU Cores: Threads6 Cores / 12 Threads\r\n- Clock Speeds: 4.7 GHz Base / Up to 5.3 GHz Boost\r\n- Cache: 384 KB (L1), 6 MB (L2), 32 MB (L3)\r\n- Socket Type: AM5\r\n- TDP (Power): 105W\r\n- Memory Support: DDR5\r\n- Integrated Graphics: AMD Radeon Graphics (RDNA 2)\r\n- Overclocking: Fully Unlocked\r\n- Max. Temperature: 95°C', 'image/prod_6a26ba3344a68.jpg', 'Available', 105, 0, 'AM5', 'DDR5', 6),
(4, 2, 'ASUS ROG STRIX Z790-F LGA1700 DDR5', 'High-end Intel board, supports DDR5 memory only.', 1450.00, 8, '- CPU Socket: Intel® LGA 1700 • Supports 14th, 13th, & 12th Gen Intel® Core™, Pentium® Gold, and Celeron® Processors\r\n- Chipset: Intel Z790\r\n- Memory: 4x DDR5 DIMM, Max 192GB\r\n- Expansion Slots: 1x PCIe 5.0 x16 (SafeSlot)\r\n- Storage: Up to 5x M.2 slots (Gen 4) and 4x SATA 6Gb/s ports\r\n- Networking: Intel WiFi 7 (on WiFi II) or WiFi 6E (on original) + Intel 2.5Gb Ethernet\r\n- Audio: ROG SupremeFX 7.1 ALC4080 with Savitech AMP\r\n- Form Factor: ATX', 'image/prod_6a26ba2a01f63.png', 'Available', 30, 0, 'LGA1700', 'DDR5', 4),
(5, 2, 'MSI PRO H610M-G LGA1700 DDR4', 'Budget Intel board, supports DDR4 memory only.', 450.00, 15, '- Brand	msi: N/A\r\n- CPU Socket: LGA 1700\r\n- Compatible Devices: Personal Computer\r\n- Memory: 2x DDR4 DIMM slots\r\n- Chipset: Intel H610\r\n- Expansion: 1x PCIe 4.0 x16 slot  1x PCIe 3.0 x1 slot\r\n- Storage: 1x M.2 slot (PCIe 3.0 x4 / SATA) 4x SATA III 6Gb/s ports\r\n- Ethernet: Intel I219-V 1G LAN', 'image/prod_6a26ba1cab01c.jpg', 'Available', 20, 0, 'LGA1700', 'DDR4', 9),
(6, 2, 'Gigabyte B650 AORUS ELITE AX AM5 DDR5', 'Premium AMD board, supports DDR5 memory only.', 1350.00, 5, '- CPU Socket: AMD Socket AM5\r\n- Chipset: AMD B650\r\n- Memory: 4 x DDR5 DIMM slots\r\n- Expansion Slots: 1 x PCIe 4.0 x16 slot (with Ultra Durable Armor) 2 x PCIe 3.0 x1 slots\r\n- Storage: 1 x PCIe 5.0 x4 M.2 slot 2 x PCIe 4.0 x4 M.2 slots 4 x SATA 6Gb/s ports\r\n- Networking: Realtek 2.5GbE LAN chip AMD Wi-Fi 6E (RZ616) & Bluetooth 5.2\r\n- Audio: Realtek Audio CODEC\r\n- Form Factor: ATX (30.5cm x 24.4cm)', 'image/prod_6a26ba13d092e.png', 'Available', 30, 0, 'AM5', 'DDR5', 7),
(7, 3, 'Kingston Fury Beast 16GB DDR4 3200MHz', 'Reliable standard DDR4 memory module.', 200.00, 30, 'Capacity: 16GB\r\nMemory Type: DDR4 UDIMM\r\nTested Speed: 3200MT/s \r\nTested Latency: CL16 \r\nVoltage: 1.35V for the overclocked 3200MHz profile\r\nDefault Profile: JEDEC standard DDR4-2400 at 1.2V\r\nHeatsink: Stylish, low-profile black heat spreader\r\nLighting: Available in Dynamic RGB versions', 'image/prod_6a26ba0b6e2d9.webp', 'Available', 8, 0, '', 'DDR4', 5),
(8, 3, 'Corsair Vengeance 32GB DDR5 6000MHz', 'High-speed DDR5 memory module for gaming.', 650.00, 17, 'Capacity: 32GB (2 × 16GB DIMMs)\r\nSpeed: 6000 MT/s (PC5-48000)\r\nTested Latency: CL30\r\nTested Voltage: 1.40V\r\nProfile Support: Includes Intel XMP 3.0 and AMD EXPO for easy, stable memory overclocking\r\nForm Factor: 288-pin Desktop DIMM\r\nCooling: Solid aluminum heat spreader available in various colors', 'image/prod_6a26ba00c867e.jpg', 'Available', 10, 0, '', 'DDR5', 7),
(9, 3, 'G.Skill Trident Z5 RGB 64GB DDR5', 'Enthusiast DDR5 memory kit for heavy workloads.', 1200.00, 10, 'Capacity: 64GB (2x32GB) dual-channel kit\r\nMemory Type: DDR5 Unbuffered DIMM\r\nTested Speeds & Latencies:6000 MT/s: CL36 (36-36-36-96) at 1.35V or CL30 (30-40-40-96)6400 MT/s: CL32 (32-39-39-102) at 1.40V\r\nSPD Specs (Default): 4800 MT/s at 1.10V\r\nError Checking (ECC): Non-ECC\r\nLighting Control: Compatible with G.Skill\'s lighting control software and third-party motherboard sync (e.g., Asus Aura Sync, MSI Mystic Light).\r\nProfile Support: Optimized for Intel XMP 3.0 (Extreme Memory Profile); select models also feature AMD EXPO support.', 'image/prod_6a26b9f584806.jpg', 'Available', 15, 0, '', 'DDR5', 5),
(10, 4, 'NVIDIA GeForce GT 730 2GB', 'Basic display output only (Will cause severe bottleneck with high-end CPUs).', 250.00, 20, '- GPU Clock: ~902 MHz~700 MHz\r\n- Memory Size: 2GB 2GB\r\n- Memory Type: GDDR5   DDR3\r\n- Memory Bus: 64-bit  128-bit\r\n- Power Draw (TDP): 49 W', 'image/prod_6a26b9e805036.jpg', 'Available', 30, 0, '', '', 1),
(11, 4, 'NVIDIA RTX 4070 SUPER 12GB', 'Sweet spot for 1440p gaming and rendering.', 3100.00, 7, '- Architecture: NVIDIA Ada Lovelace\r\n- CUDA Cores: 7,168\r\n- Boost Clock: 2,475 MHz (varies by board partner)\r\n- Memory Size: 12 GB GDDR6X\r\n- Memory Bus: 192-bit\r\n- Memory Speed: 21 Gbps (1313 MHz)\r\n- Memory Bandwidth: 504 GB/s\r\n- L2 Cache: 48 MB\r\n- TDP (Power Draw)220 W ( PSU recommended): 220W\r\n- Power Supply Unit: 650 W', 'image/prod_6a26b9da3bc38.jpg', 'Available', 220, 0, '', '', 7),
(12, 4, 'NVIDIA RTX 4090 24GB', 'Ultimate flagship GPU (Requires massive power supply).', 8500.00, 2, '- NVIDIA CUDA® Cores: 16384\r\n- Standard Memory: 24 GB GDDR6X\r\n- Boost Clock (GHz): 2.52\r\n- Base Clock (GHz): 2.23\r\n- NVIDIA Architecture: Ada Lovelace', 'image/prod_6a26b9cf5edf3.jpg', 'Available', 450, 0, '', '', 10),
(13, 6, 'Corsair CV550 550W', 'Entry-level power supply (550W).', 220.00, 15, '- Total Wattage: 550 Watts\r\n- Efficiency Rating: 80 PLUS Bronze (up to 88% efficiency)\r\n- Form Factor: ATX12V 2.31\r\n- Cable Type: Non-Modular / Fixed (Black sleeved cables)\r\n- Fan Size: 120mm (thermally controlled, low noise)\r\n- Dimensions: 125mm (Length) x 150mm (Width) x 86mm (Height)', 'image/prod_6a26b9c688eea.jpg', 'Available', 550, 0, '', '', 7),
(14, 6, 'FSP Hydro G Pro 850W', 'High-end gold certified power supply (850W).', 600.00, 10, '- Output Power: 850W\r\n- Form Factor: ATX\r\n- Input Voltage: 100-240Vac\r\n- Input Current: 11-5.5A\r\n- Input Frequency: 50-60Hz\r\n- Efficiency: ≥90% at typical load\r\n- Fan Type: FDB Fan, 135 mm\r\n- Dimensions: Length: 150mm Weight: 150mm Height: 86 mm\r\n- Operation Temp.: 0-50℃\r\n- Protection: OCP, OVP, OPP, SCP, OTP', 'image/prod_6a26b9bd64091.jpg', 'Available', 850, 0, '', '', 7),
(15, 6, 'ASUS ROG Thor 1200W', 'Platinum overkill power supply (1200W).', 1500.00, 3, '- Dimensions: 19 x 15 x 8.6 Centimeter\r\n- Efficiency: 80Plus Platinum\r\n- MTBF: >120,000 hrs @ 25°C\r\n- Protection Features: OPP/OVP/UVP/SCP/OCP/OTP\r\n- Hazardous Materials: ROHS\r\n- AC Input Range: 100-240Vac\r\n- RGB Connector: Aura Sync\r\n- DC Output Voltage: +3.3V	+5V	+12V	-12V	+5Vsb\r\n- Total Output: 1200W', 'image/prod_6a26b9b1b55ad.png', 'Available', 1200, 0, '', '', 9),
(16, 5, 'Samsung 990 PRO 1TB NVMe', 'Top-tier M.2 NVMe SSD.', 550.00, 15, 'Form Factor: M.2 2280 (Single-Sided)\r\nInterface: PCIe Gen 4.0 x4 / NVMe 2.0\r\nNAND Type: Samsung V-NAND TLC\r\nController: In-house Samsung Controller\r\nCache Memory: 1GB LPDDR4\r\nSequential Read: Up to 7,450 MB/s\r\nSequential Write: Up to 6,900 MB/s\r\nRandom Read (QD32): Up to 1,200,000 IOPS\r\nRandom Write (QD32): Up to 1,550,000 IOPS', 'image/prod_6a26b9a7a5d1d.jpg', 'Available', 5, 0, '', '', 9),
(17, 5, 'WD Blue SN570 500GB NVMe', 'Budget-friendly fast storage.', 200.00, 25, 'Form Factor: M.2 2280 (22mm x 80mm)\r\nInterface: PCIe Gen3 x4 NVMe 1.4\r\nSequential Read: Up to 3,500 MB/s\r\nSequential Write: Up to 2,300 MB/s\r\nRandom Read/Write: 360K / 390K IOPS\r\nNAND Type: Kioxia 112-layer TLC\r\nController: WD 20-82-10048-A1 (Polaris MP16)', 'image/prod_6a26b99d198e7.png', 'Available', 5, 0, '', '', 7),
(18, 7, 'NZXT H5 Flow Black', 'High airflow premium chassis.', 400.00, 7, 'Form Factor: Compact ATX Mid-Tower\r\nMaterials: SGCC Steel and Dark Tinted Tempered Glass\r\nMotherboard Support: E-ATX (up to 277mm wide), ATX, Micro-ATX, Mini-ITX\r\nColor: Black\r\nDimensions: 465 mm(H) x 225mm(W) x 430 mm(D)\r\nWeight: 7.28 kg\r\nMax CPU Cooler Height: Up to 170 mm\r\nMax GPU Clearance: Up to 410 mm\r\nMax PSU Length: Up to 200 mm', 'image/prod_6a26b9934d288.webp', 'Available', 0, 0, '', '', 7),
(19, 8, 'Deepcool AK400 Air Cooler', 'Efficient standard air cooler.', 150.00, 20, '- Dimensions: 127×97×155 mm(L×W×H)\r\n- Heatsink Dimensions: 120×45×152 mm(L×W×H)\r\n- Net Weight: 661 g\r\n- Heatpipe: Ø6 mm×4 pcs\r\n- Fan Dimensions: 120×120×25 mm(L×W×H)\r\n- Fan Speed: 500~1850 RPM±10%\r\n- Fan Airflow: 66.47 CFM\r\n- Fan Air Pressure: 2.04 mmAq\r\n- Fan Noise: =29 dB(A)\r\n- Fan Connector: 4-pin PWM\r\n- Bearing Type: Fluid Dynamic Bearing\r\n- Fan Rated Voltage: 12 VDC\r\n- Fan Rated Current: 0.13 A\r\n- Fan Power Consumption: 1.56 W', 'image/prod_6a26b98980a9d.jpg', 'Available', 0, 0, '', '', 8),
(20, 8, 'NZXT Kraken 360 RGB AIO', 'Premium liquid cooler with LCD.', 850.00, 8, 'Radiator Dimensions: 397 × 120 × 27 mm\r\nRadiator Material: Aluminum\r\nCold Plate Material: Copper\r\nTubing Length: 420 mm (CIIR+EPDM rubber with nylon braided sleeve)\r\nPump Motor Speed: 3,100 ± 310 RPM\r\nFan Dimensions: 120 × 120 × 26 mm (Three fans or single-frame depending on SKU)\r\nRotational Speed: 500 – 2,400 ± 250 RPM\r\nAirflow: 75.05 CFM (per fan)\r\nStatic Pressure: 3.07 mm H₂O (per fan)\r\nNoise Level: 31.9 dBA (Max)\r\nBearing: Fluid Dynamic Bearing (FDB)', 'image/prod_6a26b980b43e5.jpg', 'Available', 15, 0, '', '', 7),
(21, 9, 'Microsoft Windows 11 Home 64-bit', 'Standard edition for gamers and home users. USB Flash Drive included.', 549.00, 5, 'Connectivity & Setup: Setting up Windows 11 Home for personal use strictly requires an active internet connection and a Microsoft Account.\r\nSecurity & Authentication: TPM 2.0 is mandatory for next-gen hardware tampering prevention. Windows Hello is supported for biometric logins (fingerprint or facial recognition)\r\nAdvanced Networking: Native support for Hyper-V, Firewall, and modern protocols including Wi-Fi 6, Wi-Fi 7 (when hardware is supported), and Bluetooth 5.3.\r\nAI Enhancements: Access to AI-powered features like Copilot, and for compatible Copilot+ PCs, enhanced hardware-accelerated NPU tasks', 'image/prod_6a26b977c660c.jpg', 'Available', 0, 0, '', '', 6),
(22, 9, 'Microsoft Windows 11 Pro 64-bit', 'Advanced features for professionals and developers. BitLocker included.', 899.00, 5, 'Security: BitLocker device encryption and Windows Information Protection (WIP).\r\nManagement & Remote Access: Domain join, Azure Active Directory, Group Policy, and Remote Desktop support.\r\nVirtualization: Hyper-V and Windows Sandbox for secure, virtualized environments.', 'image/prod_6a26b96a0432e.jpg', 'Available', 0, 0, '', '', 7),
(23, 10, 'Corsair iCUE AR120 RGB 120mm (3-Pack)', 'High performance cooling fans with customizable RGB lighting sync.', 229.00, 5, 'Fan Size: 120mm × 25mm\r\nBearing Type: Hydraulic bearing\r\nLighting: 8 Individually addressable RGB LEDs per fan\r\nRGB Control: Motherboard 3-pin ARGB header (adapter included) or Corsair iCUE controller\r\nFan Speed: 400 – 1,850 RPM (PWM Controlled)\r\nAirflow: Up to 59 CFM\r\nStatic Pressure: 2.83 mm H₂O\r\nNoise Level: 10.26 dBA to 27.3 dBA\r\nZero RPM Mode: Supported\r\nPower Connector: 4-pin PWM\r\nLighting Header: 3-pin +5V ARGB', 'image/prod_6a26b913d2ffa.jpg', 'Available', 5, 0, '', '', 7),
(24, 10, 'ARCTIC P12 PWM PST 120mm', 'Pressure-optimized quiet fan for excellent airflow and low noise.', 45.00, 8, '- Dimensions: 120 × 120 × 25 mm (Standard)\r\n- Fan Speed: 200 – 1,800 RPM (PWM controlled)\r\n- Zero RPM Mode: Yes (Stops spinning when PWM signal is < 5%)\r\n- Airflow: 56.3 CFM (95.7 m³/h)\r\n- Static Pressure: 2.20 mm H₂O\r\n- Noise Level: 0.3 Sone (extremely quiet)\r\n- Bearing Type: Fluid Dynamic Bearing (FDB)\r\n- Connector: 4-Pin Connector + 4-Pin Socket (for daisy-chaining)\r\n- Current / Voltage: 0.08 A / 12 V DC\r\n- Cable Length: 400 mm\r\n- Weight: 139g – 145g', 'image/prod_6a26b9081110e.jpg', 'Available', 2, 0, '', '', 7),
(25, 11, 'ASUS TUF Gaming VG27AQ 27\" 165Hz', '27-inch WQHD (2560x1440) IPS gaming monitor with ultrafast 165Hz refresh rate.', 1299.00, 7, '- Screen Size: 27 inch\r\n- Resolution: WQHD (2560 x 1440)\r\n- Refresh Rate: 165Hz\r\n- Color Accuracy: 100% sRGB color space', 'image/prod_6a26b8fe98825.png', 'Available', 0, 0, '', '', 6),
(26, 11, 'AOC 24G2SP 24\" 165Hz IPS', '24-inch Full HD (1920x1080) gaming monitor, perfect for esports.', 649.00, 10, '- Screen size (inch): 23.8\r\n- Panel resolution: 1920x1080\r\n- Panel type: IPS\r\n- Blue Light Technology: Yes', 'image/prod_6a26b8f22a7d0.png', 'Available', 0, 0, '', '', 4),
(27, 1, 'AMD Ryzen 7 7800X3D', 'The undisputed king of gaming CPUs. 3D V-Cache technology. Keyword: AM5', 1850.00, 24, 'Cores/Threads: 8 Cores / 16 Threads\r\nClock Speeds: 4.2 GHz Base / up to 5.0 GHz Boost\r\nCache: 96 MB L3 Cache\r\nSocket & Architecture: AM5, Zen 4 (TSMC 5nm)\r\nTDP: 120W (requires a capable cooler)\r\nMemory Support: Dual-channel DDR5', 'image/prod_6a26b8e5871c9.jpg', 'Available', 120, 0, 'AM5', '', 9),
(28, 1, 'Intel Core i7-14700K', '20-core powerhouse for rendering and intense gaming. Keyword: LGA1700', 1980.00, 13, 'Total Cores / Threads: 20 / 28\r\nMax Turbo Frequency: Up to 5.6 GHz\r\nCache: 33 MB Intel Smart Cache\r\nSocket Compatibility: LGA 1700 (Intel 600 and 700 series chipsets)\r\nMemory Support: Up to DDR5-5600 or DDR4-3200\r\nIntegrated Graphics: Intel UHD Graphics 770\r\nPower Draw: 125 W Base Power / 253 W Maximum Turbo Power', 'image/prod_6a26b8dc5752a.jpg', 'Available', 253, 0, 'LGA1700', '', 9),
(29, 1, 'AMD Ryzen 5 7600', 'Incredible value for AM5 platform, excellent 1080p/1440p performer. Keyword: AM5', 1050.00, 40, 'Cores/Threads: 6 Cores / 12 Threads\r\nClock Speed: 3.8 GHz Base / 5.1 GHz \r\nBoostCache: 6MB L2 + 32MB L3\r\nSocket Type: AM5 (Requires DDR5 memory)\r\nIntegrated Graphics: Yes (AMD Radeon Graphics, 2 cores at 2200 MHz)\r\nTDP (Power): 65 Watts', 'image/prod_6a26b8a6f3f34.jpg', 'Available', 65, 0, 'AM5', '', 6),
(30, 1, 'Intel Core i5-12400F', 'The ultimate budget 6-core processor. Keyword: LGA1700', 580.00, 49, 'Cores / Threads: 6 Cores / 12 Threads\r\nClock Speeds: 2.5 GHz Base, up to 4.4 GHz Turbo\r\nCache: 18 MB Intel Smart Cache\r\nPower (TDP): 65W Base, 117W Maximum Turbo\r\nSocket: LGA1700\r\nMemory Support: DDR4 (up to 3200 MT/s) and DDR5 (up to 4800 MT/s)\r\nIncluded Cooler: Yes', 'image/prod_6a26b89a9eda1.jpg', 'Available', 65, 0, 'LGA1700', '', 5),
(31, 1, 'AMD Ryzen 9 7950X', '16-core rendering monster for ultimate creators. Keyword: AM5', 2850.00, 9, 'Architecture: TSMC 5nm FinFET (Zen 4)\r\nCores/Threads: 16 Cores / 32 Threads\r\nClock Speeds: 4.5 GHz Base, up to 5.7 GHz Max Boost\r\nCache: 80MB total (1MB L1, 16MB L2, 64MB L3)\r\nSocket: AM5 (Requires DDR5 memory)\r\nIntegrated Graphics: AMD Radeon Graphics included\r\nDefault TDP:170W', 'image/prod_6a26b86371d66.jpg', 'Available', 170, 0, 'AM5', '', 10),
(32, 1, 'Intel Core i9-13900K', 'Previous gen flagship, still an absolute beast. Keyword: LGA1700', 2650.00, 12, 'Total Cores: 24 (8 Performance-cores, 16 Efficient-cores)\r\nTotal Threads: 32Max Turbo Frequency: Up to 5.80 GHz\r\nCache: 36 MB Intel Smart Cache\r\nLithography: Intel 7 (10nm)\r\nSocket Compatibility: LGA 1700\r\nProcessor Base Power: 125 W\r\nMaximum Turbo Power: 253 W\r\nUnlocked for Overclocking: Yes\r\nMax Memory Size: Up to 192 GB\r\nMemory Types: Up to DDR5 5600 MT/s, DDR4 3200 MT/s\r\nGPU Name: Intel UHD Graphics 770\r\nGraphics Max Dynamic Frequency: 1.65 GHz\r\nMax Resolution (HDMI): 4096 x 2160 @ 60Hz\r\nMax Resolution (DP): 7680 x 4320 @ 60Hz', 'image/prod_6a26b85ae8213.jpg', 'Available', 253, 0, 'LGA1700', '', 9),
(33, 2, 'MSI MAG B650 TOMAHAWK WIFI', 'Premium AM5 board with heavy VRM heatsinks. DDR5 only.', 1150.00, 16, '- CPU Socket: AMD Socket AM5\r\n- Chipset: AMD B650\r\n- Memory: 4x DDR5 slots (up to 128GB, speeds 6400+ MHz OC)\r\n- Expansion Slots: 2x PCIe 4.0 x16 (primary slot reinforced with Steel Armor) 1x PCIe 3.0 x1\r\n- Storage: 3x M.2 slots (PCIe 4.0 x4) 6x SATA 6Gb/s\r\n- Networking: Wi-Fi 6E, Bluetooth 5.2 (or 5.3 depending on revision), 2.5G LAN\r\n- Audio: Realtek ALC4080 Codec (7.1 Channel HD Audio)\r\n- Form Factor: ATX (24.38 cm × 30.48 cm)', 'image/prod_6a26b8483b6d8.jpg', 'Available', 25, 0, 'AM5', 'DDR5', 5),
(34, 2, 'ASUS ROG STRIX B760-A GAMING WIFI', 'High-end Intel B760 DDR5 motherboard with supreme aesthetics.', 1100.00, 18, '- CPU: Intel® Socket LGA1700 for Intel® Core™ 14th & 13th Gen Processors, Intel® Core™ 12th Gen, Pentium® Gold and Celeron® Processors\r\n- Chipset: Intel B760\r\n- Memory: 4 x DIMM, Max. 192GB, DDR5 (up to 7800+ MT/s OC)\r\n- Storage: 3 x M.2 slots (all PCIe 4.0 x4) and 4 x SATA 6Gb/s ports\r\n- Networking: Intel Wi-Fi 6E, Bluetooth 5.3, and Intel 2.5G Ethernet\r\n- Audio: ROG Supreme 7.1 Surround Sound High Definition Audio CODEC\r\n- Form Factor: ATX (30.5 cm x 24.4 cm)', 'image/prod_6a26b83ec2f84.png', 'Available', 25, 0, 'LGA1700', 'DDR5', 8),
(35, 2, 'Gigabyte B550M DS3H', 'Budget king for older AM4 DDR4 builds.', 420.00, 30, '- CPU Socket: AMD AM4 (Supports Ryzen 3000, 4000, and 5000 Series Processors)\r\n- Memory (RAM): 4x DDR4 DIMMs (Dual-Channel, up to 128GB usually supported)\r\n- Expansion Slots: 1x PCIe 4.0 x16 (for graphics card) 1x PCIe 3.0 x16 (runs at x4) 1x PCIe 3.0 x1Storage 1x M.2 (PCIe 4.0 x4/x3) 1x M.2 (PCIe 3.0 x2) 4x SATA 6Gb/s\r\n- Networking: Realtek GbE LAN (Gigabit Ethernet)\r\n- Audio: Realtek Audio Codec with high-end audio capacitors', 'image/prod_6a26b835c7b28.jpg', 'Available', 15, 0, 'AM4', 'DDR4', 8),
(36, 2, 'ASRock B760M PRO RS/D4', 'Solid budget board for Intel 12th/13th/14th gen. DDR4 only.', 650.00, 22, '- CPU Support: Intel® Core™ processors (14th, 13th, and 12th Gen)\r\n- Memory: 4 × DDR4 DIMM slots (Dual Channel) up to 128 GB\r\n- Expansion Slots: 2 × PCIe 4.0 x16 slots 1 × PCIe 4.0 x1 slot 1 × M.2 Key-E slot for Wi-Fi modules\r\n- Storage Connectors:: 4 × SATA3 6.0 Gb/s ports 2 × Hyper M.2 slots (both PCIe Gen4x4)', 'image/prod_6a26b82720acb.jpg', 'Available', 20, 0, 'LGA1700', 'DDR4', 6),
(37, 2, 'ASUS ROG CROSSHAIR X670E HERO', 'Flagship AM5 board for extreme overclocking.', 3200.00, 4, '- CPU Support: AMD Socket AM5 for Ryzen 7000 series processors\r\n- Chipset: AMD X670E\r\n- Memory: 4 × DDR5 DIMM slots (Max 128GB)\r\n- Storage: 2 × onboard PCIe Gen 5 x4 M.2 slots 2 × onboard PCIe Gen 4 x4 M.2 slots 1 × PCIe Gen 5 M.2 via bundled expansion card 6 × SATA 6Gb/s ports\r\n- Rear I/O Ports: • 2 × USB4 Type-C ports • 1 × USB 3.2 Gen 2x2 Type-C port • 9 × USB 3.2 Gen 2 Type-A ports • 1 × HDMI 2.1• BIOS FlashBack & Clear CMOS buttons', 'image/prod_6a26b7d768ae2.png', 'Available', 35, 0, 'AM5', 'DDR5', 10),
(38, 3, 'Corsair Vengeance 32GB (2x16GB) DDR5 6000MHz CL30', 'The sweet spot speed and latency for Ryzen 7000 series.', 580.00, 39, 'Capacity: 32GB (2 x 16GB DIMMs)\r\nTested Speed: 6000 MT/s (PC5-48000)\r\nTested Latency: CL30 (specifically 30-36-36-76)\r\nTested Voltage: 1.4VMemory Type: DDR5\r\nProfiles: AMD EXPO & Intel XMP\r\nForm Factor: 288-pin DIMM\r\nHeat Spreader: Solid aluminum', 'image/prod_6a26b7cdc7614.jpg', 'Available', 10, 0, '', 'DDR5', 9),
(39, 3, 'G.Skill Trident Z5 Neo RGB 64GB (2x32GB) DDR5 6000MHz', 'High-capacity, high-speed RAM for video editing.', 1150.00, 12, 'Capacity: 64GB (2 x 32GB)\r\nMemory Type: DDR5Tested Speed: 6000 MT/s (PC5-48000)\r\nLatency Timings: Available in ultra-low latency configurations, typically CL30 (30-40-40-96) or CL36 (36-36-36-96) depending on the specific model variationTested \r\nVoltage: 1.40V (for CL30 kits)\r\nLighting: Customizable RGB lighting across a streamlined dual-textured aluminum heat spreader', 'image/prod_6a26b7c554e34.webp', 'Available', 12, 0, '', 'DDR5', 7),
(40, 3, 'Kingston FURY Beast 16GB (2x8GB) DDR4 3200MHz', 'Reliable budget DDR4 kit.', 190.00, 59, '- CapacitiesKits of 2: 16GB\r\n- Frequencies: 3200MT/s\r\n- Latencies: CL16\r\n- Voltage: 1.35V\r\n- Operating Temperature: 0°C to 85°C\r\n- Dimensions: 133.35mm x 34.1mm x 7.2mm', 'image/prod_6a26b7bc84007.jpg', 'Available', 5, 0, '', 'DDR4', 5),
(41, 3, 'Corsair Dominator Titanium 32GB DDR5 7200MHz', 'Ultra-premium high-frequency memory for Intel builds.', 850.00, 10, 'Memory Type: DDR5\r\nForm Factor: U-DIMM\r\nColor: White\r\nHeat Spreader: Aluminum\r\nCapacity: 16GB per DIMM\r\nSpeed: 7200 MT/s\r\nTimings: 34-44-44-96 2T\r\nOperating Voltage: 1.45V\r\nDimensions: 135.9 x 56.75×7.7mm(LxHxW)\r\nWeight: 77 grams', 'image/prod_6a26b7b386114.jpg', 'Available', 12, 0, '', 'DDR5', 9),
(42, 3, 'TeamGroup T-Force Delta RGB 32GB DDR4 3600MHz', 'Flashy RGB DDR4 kit for mid-range systems.', 380.00, 25, 'Capacity: 32GB (2 x 16GB dual-channel kit)\r\nMemory Type: DDR4 UDIMM (Desktop)\r\nTested Speed: 3600 MHz (PC4-28800)\r\nCAS Latency: CL18 (18-22-22-42)Voltage: 1.35V\r\nForm Factor: 288-pin \r\nDIMMLighting: 120° ultra-wide-angle, full-frame RGB Force Flow effect\r\nHeatsink: Geometric asymmetric aluminum alloy heat spreader\r\nColors Available: Typically offered in Black or White\r\nDimensions: 49mm (1.93 inches) in total height', 'image/prod_6a26b7a8e455f.jpg', 'Available', 8, 0, '', 'DDR4', 8),
(43, 4, 'ASUS TUF Gaming GeForce RTX™ 4060 Ti 8GB GDDR6 OC Edition', 'Excellent 1080p Ultra gaming with DLSS 3 Frame Gen.', 1850.00, 17, '- Graphic Engine: NVIDIA® GeForce RTX™ 4060 Ti\r\n- AI Performance: 366\r\n- Bus Standard: PCI Express 4.0\r\n- OpenGL: OpenGL®4.6\r\n- Memory: 8GB GDDR6\r\n- Engine Clock: 2655MHz\r\n- CUDA Core: 4352\r\n- Memory Speed: 18 Gbps\r\n- Resolution: 7680 x 4320\r\n- Interface: Yes x 1 (Native HDMI 2.1a), Yes x 3 (Native DisplayPort 1.4a), HDCP Support Yes (2.3)', 'image/prod_6a26b7a00ec82.png', 'Available', 160, 0, '', '', 5),
(44, 4, 'AMD Radeon RX 7800 XT 16GB', 'Unbeatable 1440p value, massive VRAM for textures.', 2550.00, 16, '- lock: GPU / Memory\r\n- Game Clock: 2169 MHz / 19.5 Gbps\r\n- Memory: 16GB GDDR6\r\n- Memory Speed: 19.5 Gbps\r\n- Power Supply Unit: 700 W', 'image/prod_6a26b796662e7.png', 'Available', 263, 0, '', '', 7),
(45, 4, 'ROG Strix GeForce RTX™ 4080 SUPER 16GB GDDR6X OC Edition', 'Incredible 4K performance and ray tracing capabilities.', 4950.00, 10, '- Engine Clock: 2640 MHz\r\n- Memory: 16GB GDDR6X\r\n- Memory Speed: 23 Gbps\r\n- Resolution: 7680 × 4320\r\n- Dimensions: 357.6 x 149.3 x 70.1 mm\r\n- Power Supply Unit: 850W', 'image/prod_6a26b78ea28d7.png', 'Available', 320, 0, '', '', 9),
(46, 4, 'AMD Radeon RX 7900 XTX 24GB', 'Raw rasterization monster, destroys 4K without breaking a sweat.', 4800.00, 8, '- Stream Processors: 6,144 (96 Compute Units)\r\n- Memory: 24GB GDDR6\r\n- Power Supply Unit: 750W to 850W\r\n- Display Outputs: DisplayPort 2.1, HDMI 2.1a, USB Type-C', 'image/prod_6a26b784937e8.png', 'Available', 355, 0, '', '', 9),
(47, 4, 'ASUS Dual GeForce RTX™ 4070 Ti SUPER OC Edition 16GB GDDR6X', 'Perfect 1440p high-refresh rate card.', 6039.00, 13, '- AI Performance: 710\r\n- Bus Standard: PCI Express 4.0\r\n- OpenGL: OpenGL®4.6\r\n- Video Memory: 16GB GDDR6X\r\n- Default mode: 2625 MHz (boost)\r\n- CUDA Core: 8448\r\n- Memory Speed: 21 Gbps\r\n- Memory Interface: 256-bit\r\n- Resolution: Digital Max Resolution 7680 x 4320\r\n- Interface: Yes x 1 (Native HDMI 2.1a), Yes x 3 (Native DisplayPort 1.4a), HDCP Support Yes (2.3)', 'image/prod_6a26b77aec538.png', 'Available', 285, 0, '', '', 8),
(48, 4, 'AMD Radeon RX 7600 8GB', 'Budget king for entry-level 1080p gaming.', 1350.00, 9, 'Video Memory: 8GB GDDR6\r\nMemory Interface: 128-bit\r\nInterface Type: PCI Express 4.0\r\nOutput Ports: HDMI, DisplayPort (varies by manufacturer)\r\nArchitecture: RDNA 3\r\nPower Connector: 8-pin (varies by model)\r\nRecommended PSU: 550W or higher', 'image/prod_6a26b76fcdfc0.png', 'Available', 165, 0, '', '', 4),
(49, 5, 'WD Black SN850X 2TB Gen4 NVMe', 'Top-tier speeds up to 7300MB/s.', 780.00, 18, '- Capacity: 1 TB (Without Heatsink)\r\n- Form Factor: M.2 2280\r\n- Interface: PCIe Gen4 x4\r\n- Sequential Read Performance: 7300MB/s\r\n- Sequential Write Performance: 6300MB/s\r\n- Random Read: 8000004KB IOPS\r\n- Random Write: 11000004KB IOPS\r\n- Endurance (TBW): 600\r\n- Compatibility: Computer with M.2 (M-key) port\r\n- Dimensions: Length: 20mm Weight: 22mm Height: 2.38mm', 'image/prod_6a26b73aa2a97.jpg', 'Available', 8, 0, '', '', 9),
(50, 5, 'Crucial P3 Plus 1TB Gen4 NVMe', 'Great balance of speed and affordability.', 280.00, 44, '- Storage Capacity: 1TB\r\n- Hard Disk Interface: NVMe\r\n- Connectivity Technology: NVMe\r\n- Additional Features: Portable\r\n- Hard Disk: Form Factor	2.5 Inches (6.4 cm)\r\n- Compatible Devices: Desktops & Laptops that accept PCIe NVMe Gen 4.0 drives\r\n- Read Speed: 5000MB/s\r\n- Media Speed: 4200 MB/S\r\n- Data Transfer Rate: 5000 MB/s\r\n- Form Factor: M.2\r\n- Hardware Connectivity: PCIE x 4\r\n- Hardware Platform: Linux, Mac, PC\r\n- Hard-Drive Size: 500 GB\r\n- Item Dimensions: L x W x Thickness	3.15\r\n- Color: Black\r\n- Enclosure Material: Aluminum', 'image/prod_6a26b72f9beef.jpg', 'Available', 5, 0, '', '', 8),
(51, 5, 'Samsung 990 PRO 4TB NVMe', 'Massive fast storage for heavy video editors.', 1550.00, 8, '- Interface: PCIe Gen 4.0, x4, NVMe 2.0[5]\r\n- Form Factor: M.2 (2280)\r\n- Storage Memory: Samsung V-NAND 3-bit TLC\r\n- Controller: Samsung In-house controller\r\n- Capacity: 1TB\r\n- RAM: 4GB LPDDR4\r\n- Read/Write Speed[7]: up to 7,450 MB/s, up to 6,900 MB/s\r\n- Random Read/Write Speed: up to 1,600K IOPS, 1,550K IOPS', 'image/prod_6a26b727aebb2.jpg', 'Available', 10, 0, '', '', 10),
(52, 6, 'Corsair RM850e 850W 80+ Gold', 'Fully modular, ATX 3.0 ready.', 550.00, 17, '- Compatible devices: Personal Computer\r\n- Connector type: EPS\r\n- Output wattage: 850 Watts\r\n- Form factor: ATX\r\n- Cooling method: Air Item dimensions L x W x H	15 x 14 x 8.6 centimetres Item weight	1.52 Kilograms', 'image/prod_6a26b71ca22a0.jpg', 'Available', 850, 0, '', '', 10),
(53, 6, 'Seasonic Focus GX-1000 1000W Gold', 'Legendary reliability for high-end builds.', 850.00, 11, '- Compatible Devices: Personal Computer\r\n- Connector Type: ATX\r\n- Output Wattage: 1000 W\r\n- Form Factor: ATX\r\n- Cooling Method: Air Item dimensions L x W x H	12.13 x 7.48 x 4.65 inches Item Weight	2.12 Kilograms', 'image/prod_6a26b71433386.jpg', 'Available', 1000, 0, '', '', 9),
(54, 6, 'MSI MAG A650BN 650W 80+ Bronze', 'Solid budget power supply.', 260.00, 29, '- Compatible Devices: Gaming Console\r\n- Connector Type: ATX\r\n- Form Factor: ATX\r\n- Wattage: 650 watts\r\n- Cooling Method	Air: Item dimensions L x W x H	8.66 x 3.15 x 1.18 inches Item Weight	1 Kilograms', 'image/prod_6a26b7097ab2e.jpg', 'Available', 650, 0, '', '', 8),
(55, 6, 'FSP Hydro Ti PRO 1000W Titanium', 'Ultra-premium titanium efficiency.', 1150.00, 2, '- Max Power: 1000W\r\n- Form Factor: ATX\r\n- Efficiency Rating: 80 PLUS Titanium and Cybenetics Titanium ( ≥ 94% efficiency at typical loads)\r\n- AC Input: 100 – 240 VAC, 50 – 60 Hz\r\n- Cooling Fan: 135mm Fluid Dynamic Bearing (FDB) fan\r\n- Dimensions (W x H x D): Weight: 150 mm Height: 86 mm Distance: 150 mm', 'image/prod_6a26b70036a69.jpg', 'Available', 1000, 0, '', '', 5),
(56, 7, 'Lian Li O11 Dynamic EVO Black', 'The iconic showcase dual-chamber case.', 750.00, 15, '- COLOR: Black\r\n- DIMENSION: D)465mm × (W)285mm × (H)459mm\r\n- MATERIAL: 4mm Aluminum,4mm Tempered Glass,1mm Steel Structure\r\n- MOTHERBOARD SUPPORT: E-ATX (width under 280mm)/ATX /M-ATX/ITX\r\n- PSU SUPPORT: 220mm\r\n- FAN SUPPORT: Top 120mm × 3 or 140mm × 2 Side 120mm × 3 or 140mm × 2 Bottom 120mm × 3 or 140mm × 2 Rear 120mm × 1\r\n- On Drive Cage: 60mm × 1\r\n- RADIATOR SUPPORT: Top 360mm × 1 or 280mm × 1\r\n- Total max thickness: 87.5mm\r\n- Side: 360mm × 1 or 280mm × 1\r\n- Inner scenario total max thickness: 83mm(120mm fan on top and bottom) 63mm(140mm fan on top and bottom)\r\n- Outer scenario total max. thickness: 120mm(120mm fan on top and bottom) 100mm(140mm fan on top and bottom) Bottom 360mm × 1\r\n- Total max thickness: 87.5mm\r\n- DRIVE SUPPORT	Bottom：2.5” SSD × 4 or 3.5” HDD × 2: Side：2.5” SSD × 4 or 3.5” HDD × 2 Drive Cage：2.5” SSD × 3 or 3.5” HDD × 2 + 2.5” SSD × 1\r\n- Cable Management Bar: 2.5” SSD × 2 9 sets of 2.5” SSD mounting pads are provided\r\n- GPU LENGTH: 426 mm\r\n- CPU HEIGHT: 167 mm\r\n- SLOTS: 8\r\n- I/O PORTS: 2 × USB 3.0 1 × USB 3.1 TYPE-C 1 × HD AUDIO/ MIC\r\n- LED Color: Yes\r\n- Mode button: Yes\r\n- Reset Button: Yes\r\n- Power Button: Yes', 'image/prod_6a26b6f78e6f7.jpg', 'Available', 0, 0, '', '', 6),
(57, 7, 'Corsair 4000D Airflow Black', 'Classic high-airflow mid-tower.', 380.00, 21, '- Dimensions: 230mm(W) x 466mm(H) x 453mm(D)\r\n- Side Panel: Tempered Glass\r\n- Body Material: Steel\r\n- Drive Bay: 3.5\r\n- Fan Capacity: 120mm or 140mm fan x 2 (Top) 120mm fan x 3 / 140mm fan x 2 (Front) * 1 x Corsair AirGuide Fan included * 120mm fan x 1 (Rear) * 1 x Corsair AirGuide Fan included *\r\n- Radiator: 120-280mm (Top) 120-360mm (Front)\r\n- CPU Cooler Height: 170mm\r\n- GPU Length: 360mm\r\n- I/O Panel: USB 3.0 x 1 USB 3.1 Type-C x 1 Audio In & Out M/B Type ATX PSU Type ATX', 'image/prod_6a26b6eeaf9fe.jpg', 'Available', 0, 0, '', '', 8),
(58, 7, 'Montech X3 Mesh Black', 'Insane budget value, includes 6 RGB fans.', 220.00, 34, '- Dimensions: 370*210*480mm (Case) / 530*265*425mm (Carton)\r\n- MB Support: ATX / Micro-ATX / Mini-ITX\r\n- Front I/O: Power Button / Mic*1 / Audio*1 / USB2.0*2 / Reset Button / LED Button / USB3.0*1\r\n- PCIe Slots: 7\r\n- CPU Cooler: 160mm\r\n- GPU: 305mm\r\n- Power supply unit: 160mm ATX\r\n- Drive Support / Maximum: 3.5” HDD	22.5” SSD*4\r\n- Pre-installed Fan (s): Top	120mm*2 (RGB Molex Fans)\r\n- Front: 140mm*3 (RGB Molex Fans)\r\n- Rear: 120mm*1 (RGB Molex Fan)\r\n- Fan Support: Top	120mm*2 / 140mm*2 Front 120mm*3 / 140mm*3\r\n- PSU Shroud: Front 120mm*2 Rear 120mm*1\r\n- Radiator Support: Top	120 / 240mm Rear	120mm\r\n- Dust Filter: Top  / Bottom', 'image/prod_6a26b6e41b78d.webp', 'Available', 0, 0, '', '', 5),
(59, 8, 'Thermalright Peerless Assassin 120 SE', 'The dual-tower air cooler that beats 240mm AIOs.', 160.00, 40, 'Dimensions: 120 mm (L) x 120 mm (W) x 25 mm (H)\r\nWeight: 120 g\r\nRated Speed: 1550 RPM ± 10%\r\nNoise Level: 25.6 dBA (max)\r\nAir Flow: 66.17 CFM (max)\r\nAir Pressure: 1.53 mm H2O (max)\r\nConnector: 4-pin PWM fan connector\r\nARGB Connector: 3-pin 5V\r\nBearing Type: S-FDB Bearing', 'image/prod_6a26b6dce781a.png', 'Available', 0, 0, '', '', 7),
(60, 8, 'Arctic Liquid Freezer III 360 AIO', 'Thick radiator, ultimate liquid cooling performance.', 520.00, 11, '- Speed: 600-3000 rpm\r\n- Static Pressure: 6.9 mmH2O\r\n- Bearing: Fluid Dynamic Bearing\r\n- Airflow: 77 cfm | 131 m3/h', 'image/prod_6a26b6d34a7f4.jpg', 'Available', 15, 0, '', '', 5),
(61, 8, 'Deepcool AK620 Digital', 'Premium air cooling with a digital temp display.', 320.00, 15, '- Fan Dimensions: 120×120×25 mm\r\n- Fan Speed: 500~1850 RPM±10%\r\n- Fan Airflow: 68.99 CFM\r\n- Fan Air Pressure: 2.19 mmAq\r\n- Fan Noise: ≤28 dB(A)\r\n- Fan Connector: 4-pin PWM\r\n- Bearing Type: Fluid Dynamic Bearing\r\n- Fan Rated Voltage: 12 VDC\r\n- Fan Rated Current: 0.12 A\r\n- Fan Power Consumption: 1.44 W', 'image/prod_6a26b6cb45b95.webp', 'Available', 1, 0, '', '', 4);

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
(1, 'VIPCOMP15', 'Components', 1, 'Active', '2026-05-07 12:15:57', 15.00, 'Percentage', 100.00, 50.00),
(2, 'VIPPC20', 'Packages', 1, 'Active', '2026-05-07 12:15:57', 20.00, 'Percentage', 2000.00, 200.00),
(3, 'WELCOME10', 'All', 0, 'Active', '2026-05-07 12:15:57', 10.00, 'Percentage', 0.00, 15.00),
(4, 'SUMMER26', 'All', 0, 'Active', '2026-05-07 13:37:15', 26.00, 'Percentage', 50.00, 30.00),
(5, 'ELITEGAMER', 'Packages', 1, 'Active', '2026-05-07 13:37:15', 150.00, 'Fixed', 3000.00, 0.00),
(6, 'UPGRADE5', 'Components', 0, 'Active', '2026-05-07 13:37:15', 5.00, 'Percentage', 50.00, 20.00),
(7, 'VIPPARTS12', 'Components', 1, 'Active', '2026-05-07 13:37:15', 12.00, 'Percentage', 200.00, 100.00),
(8, 'EXPIRED50', 'All', 0, 'Inactive', '2026-05-07 13:37:15', 0.00, 'Percentage', 0.00, 0.00),
(12, 'FATHTERDAY2026', 'All', 0, 'Active', '2026-06-18 20:37:57', 10.00, 'Percentage', 500.00, 0.00),
(14, 'NEWATTEND', 'All', 1, 'Inactive', '2026-06-18 20:44:53', 10.00, 'Percentage', 0.00, 0.00);

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
(1, 1, 1, 5, 'henbang', '2026-04-09 23:29:14'),
(2, 48, 10, 5, 'works good.deserve to buy:)', '2026-06-20 02:11:13');

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
(41, 6, 'Custom Rig (May 17, 2026)', 15947.00, '2026-05-18 01:01:17');

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
(2, 1, NULL, '日人人日r', '2331', '22/11', 'Credit Card', 1, '2026-04-11 17:06:45'),
(4, 6, 2, 'Ali Bin Abu', '6666', '12/30', 'Credit Card', 1, '2026-05-18 00:13:55'),
(5, 5, 2, 'GAN SHENG WING', '6666', '01/26', 'Credit Card', 1, '2026-05-18 16:45:44'),
(6, 9, 2, 'Gan Sheng Wing', '6666', '12/30', 'Credit Card', 0, '2026-06-20 02:01:52'),
(7, 10, 2, 'Gan Sheng Wing', '6666', '12/30', 'Credit Card', 0, '2026-06-20 02:03:41'),
(8, 11, 2, 'Gan Sheng Wing', '6666', '12/30', 'Credit Card', 0, '2026-06-20 02:13:55'),
(9, 12, 2, 'Gan Sheng Wing', '6666', '12/30', 'Credit Card', 0, '2026-06-20 02:53:24'),
(10, 13, 2, 'Gan Sheng Wing', '6666', '12/30', 'Credit Card', 0, '2026-06-20 03:01:01');

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
(51, 7, 48, NULL, NULL, NULL, 25, '2026-05-18 16:42:34'),
(65, 11, 48, NULL, NULL, NULL, 1, '2026-06-20 02:32:32'),
(69, 15, 48, NULL, NULL, NULL, 1, '2026-06-20 20:32:21');

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

--
-- 转存表中的数据 `used_vouchers`
--

INSERT INTO `used_vouchers` (`used_id`, `customer_id`, `promo_id`, `order_id`, `used_at`) VALUES
(1, 6, 5, 33, '2026-06-20 12:50:36');

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
(10, 5, 'Top-up', 100000.00, 10000, '2026-05-09 21:04:18'),
(11, 6, 'Top-up', 500.00, 50, '2026-05-17 10:58:30'),
(12, 6, 'Top-up', 500.00, 50, '2026-05-18 00:32:41'),
(13, 8, 'Top-up', 500.00, 50, '2026-06-17 19:18:22'),
(14, 8, 'Payment', -45.00, 0, '2026-06-17 19:18:59'),
(15, 8, 'Payment', -350.00, 0, '2026-06-18 21:41:10'),
(16, 5, 'Payment', -350.00, 0, '2026-06-20 01:42:45'),
(17, 5, 'Top-up', 10000.00, 1000, '2026-06-20 01:49:38'),
(18, 6, 'Top-up', 5000.00, 500, '2026-06-20 01:59:22'),
(19, 10, 'Top-up', 5000.00, 500, '2026-06-20 02:04:01'),
(20, 10, 'Top-up', 500.00, 50, '2026-06-20 02:08:50'),
(21, 10, 'Top-up', 10000.00, 1000, '2026-06-20 02:12:21'),
(22, 11, 'Top-up', 5000.00, 500, '2026-06-20 02:14:14'),
(23, 11, 'Top-up', 5000.00, 500, '2026-06-20 02:14:58'),
(24, 11, 'Top-up', 10000.00, 1000, '2026-06-20 02:16:33'),
(25, 11, 'Payment', -19018.00, 0, '2026-06-20 02:18:44'),
(26, 12, 'Top-up', 20000.00, 2000, '2026-06-20 02:53:37'),
(27, 13, 'Top-up', 5000.00, 500, '2026-06-20 03:01:13'),
(28, 6, 'Top-up', 5000.00, 500, '2026-06-20 13:36:09'),
(29, 6, 'Top-up', 5000.00, 500, '2026-06-20 13:36:26'),
(30, 6, 'Payment', -8609.00, 0, '2026-06-20 20:50:36');

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
-- 表的索引 `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD PRIMARY KEY (`log_id`);

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
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- 使用表AUTO_INCREMENT `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=143;

--
-- 使用表AUTO_INCREMENT `bank`
--
ALTER TABLE `bank`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- 使用表AUTO_INCREMENT `build_items`
--
ALTER TABLE `build_items`
  MODIFY `build_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=384;

--
-- 使用表AUTO_INCREMENT `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- 使用表AUTO_INCREMENT `community_comments`
--
ALTER TABLE `community_comments`
  MODIFY `comment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用表AUTO_INCREMENT `community_likes`
--
ALTER TABLE `community_likes`
  MODIFY `like_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- 使用表AUTO_INCREMENT `community_posts`
--
ALTER TABLE `community_posts`
  MODIFY `post_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- 使用表AUTO_INCREMENT `consultations`
--
ALTER TABLE `consultations`
  MODIFY `consultation_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用表AUTO_INCREMENT `customers`
--
ALTER TABLE `customers`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- 使用表AUTO_INCREMENT `customer_addresses`
--
ALTER TABLE `customer_addresses`
  MODIFY `address_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- 使用表AUTO_INCREMENT `fpx_accounts`
--
ALTER TABLE `fpx_accounts`
  MODIFY `account_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- 使用表AUTO_INCREMENT `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- 使用表AUTO_INCREMENT `order_details`
--
ALTER TABLE `order_details`
  MODIFY `order_detail_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- 使用表AUTO_INCREMENT `packages`
--
ALTER TABLE `packages`
  MODIFY `package_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- 使用表AUTO_INCREMENT `package_items`
--
ALTER TABLE `package_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=398;

--
-- 使用表AUTO_INCREMENT `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- 使用表AUTO_INCREMENT `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- 使用表AUTO_INCREMENT `promo_codes`
--
ALTER TABLE `promo_codes`
  MODIFY `promo_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- 使用表AUTO_INCREMENT `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- 使用表AUTO_INCREMENT `saved_builds`
--
ALTER TABLE `saved_builds`
  MODIFY `pc_build` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- 使用表AUTO_INCREMENT `saved_cards`
--
ALTER TABLE `saved_cards`
  MODIFY `card_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- 使用表AUTO_INCREMENT `shopping_cart`
--
ALTER TABLE `shopping_cart`
  MODIFY `cart_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- 使用表AUTO_INCREMENT `used_vouchers`
--
ALTER TABLE `used_vouchers`
  MODIFY `used_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用表AUTO_INCREMENT `wallet_transactions`
--
ALTER TABLE `wallet_transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

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
