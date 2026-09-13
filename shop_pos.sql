-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Sep 13, 2026 at 06:57 PM
-- Server version: 8.4.3
-- PHP Version: 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `shop_pos`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `module` varchar(80) NOT NULL,
  `record_id` bigint UNSIGNED DEFAULT NULL,
  `description` varchar(500) NOT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `module`, `record_id`, `description`, `old_values`, `new_values`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'user_registered', 'auth', 1, 'Initial Administrator/Owner account registered.', NULL, '{\"role\": \"owner\", \"email\": \"victormunene207@gmail.com\", \"username\": \"munene\", \"full_name\": \"Victor Munene\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-13 15:15:31'),
(2, 1, 'login', 'auth', 1, 'User signed in successfully.', NULL, '{\"username\": \"munene\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-13 15:15:54'),
(3, 1, 'login', 'auth', 1, 'User signed in successfully.', NULL, '{\"username\": \"munene\"}', '192.168.100.9', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Mobile Safari/537.36', '2026-09-13 15:20:51'),
(4, 1, 'user_updated', 'users', 1, 'Staff account updated for Victor Munene.', '{\"email\": \"victormunene207@gmail.com\", \"status\": \"active\", \"role_id\": 1, \"username\": \"munene\", \"branch_id\": 1, \"full_name\": \"Victor Munene\"}', '{\"email\": \"victormunene207@gmail.com\", \"status\": \"active\", \"role_id\": 1, \"username\": \"munene\", \"branch_id\": 1, \"full_name\": \"Victor Munene\", \"password_changed\": false}', '192.168.100.9', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Mobile Safari/537.36', '2026-09-13 15:33:41'),
(5, 1, 'category_created', 'products', 1, 'Category created: Carpets.', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-13 15:54:15'),
(6, 1, 'product_created', 'products', 1, 'Product created: Marvel Carpet.', NULL, '{\"sku\": \"001\", \"opening_stock\": \"20.000\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-13 15:55:38'),
(7, 1, 'customer_created', 'customers', 1, 'Customer created: Victor Munene.', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-13 15:56:44'),
(8, 1, 'sale_created', 'sales', 1, 'Sale completed: SAL-000001.', NULL, '{\"cogs\": \"1500.00\", \"total\": \"3000.00\", \"credit\": \"0.00\", \"gross_profit\": \"1500.00\", \"cash_received\": \"3000.00\"}', '192.168.100.9', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Mobile Safari/537.36', '2026-09-13 16:00:11'),
(9, 1, 'stock_adjusted', 'inventory', 3, 'Stock adjusted for Marvel Carpet.', '{\"stock\": \"19.000\"}', '{\"stock\": \"21.000\", \"reason\": \"Adjusted stock\"}', '192.168.100.9', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Mobile Safari/537.36', '2026-09-13 16:05:58'),
(10, 1, 'sale_held', 'sales', 2, 'Sale held: SAL-000002.', NULL, '{\"total\": \"3000.00\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-13 16:17:41'),
(11, 1, 'customer_created', 'customers', 2, 'Customer created: Victor Munene.', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-13 16:19:16'),
(12, 1, 'sale_created', 'sales', 3, 'Sale completed: SAL-000003.', NULL, '{\"cogs\": \"1500.00\", \"total\": \"3000.00\", \"credit\": \"1000.00\", \"gross_profit\": \"1500.00\", \"cash_received\": \"2000.00\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-13 16:27:15'),
(13, 1, 'logout', 'auth', 1, 'User signed out.', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-13 16:29:22'),
(14, 1, 'user_updated', 'users', 1, 'Staff account updated for Victor Munene.', '{\"email\": \"victormunene207@gmail.com\", \"status\": \"active\", \"role_id\": 1, \"username\": \"munene\", \"branch_id\": 1, \"full_name\": \"Victor Munene\"}', '{\"email\": \"victormunene207@gmail.com\", \"status\": \"active\", \"role_id\": 1, \"username\": \"munene\", \"branch_id\": 1, \"full_name\": \"Victor Munene\", \"password_changed\": true}', '192.168.100.9', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Mobile Safari/537.36', '2026-09-13 17:25:36'),
(15, 1, 'login', 'auth', 1, 'User signed in successfully.', NULL, '{\"username\": \"munene\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-13 17:26:10'),
(16, 1, 'product_created', 'products', 2, 'Product created: Fluffy Carpets (3m).', NULL, '{\"sku\": \"002\", \"opening_stock\": \"15.000\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-13 17:33:30'),
(17, 1, 'user_created', 'users', 2, 'Staff account created for munene vic.', NULL, '{\"email\": \"vdebmunene207@gmail.com\", \"status\": \"active\", \"role_id\": 3, \"username\": \"vic\", \"branch_id\": 1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-13 17:36:06'),
(18, 1, 'logout', 'auth', 1, 'User signed out.', NULL, NULL, '192.168.100.9', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Mobile Safari/537.36', '2026-09-13 17:50:08'),
(19, 2, 'login', 'auth', 2, 'User signed in successfully.', NULL, '{\"username\": \"vic\"}', '192.168.100.9', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Mobile Safari/537.36', '2026-09-13 17:51:22'),
(20, 2, 'customer_payment_recorded', 'credit', 1, 'Customer payment recorded: CPY-000001.', NULL, '{\"amount\": \"1000.00\", \"method\": \"Cash\", \"customer_id\": 1}', '192.168.100.9', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Mobile Safari/537.36', '2026-09-13 17:55:54'),
(21, 2, 'sale_created', 'sales', 4, 'Sale completed: SAL-000004.', NULL, '{\"cogs\": \"1000.00\", \"total\": \"2500.00\", \"credit\": \"0.00\", \"gross_profit\": \"1500.00\", \"cash_received\": \"2500.00\"}', '192.168.100.9', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Mobile Safari/537.36', '2026-09-13 17:58:08'),
(22, 1, 'login', 'auth', 1, 'User signed in successfully.', NULL, '{\"username\": \"munene\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-13 20:31:47'),
(23, 1, 'user_updated', 'users', 2, 'Staff account updated for munene vic.', '{\"email\": \"vdebmunene207@gmail.com\", \"status\": \"active\", \"role_id\": 3, \"username\": \"vic\", \"branch_id\": 1, \"full_name\": \"munene vic\"}', '{\"email\": \"vdebmunene207@gmail.com\", \"status\": \"active\", \"role_id\": 2, \"username\": \"vic\", \"branch_id\": 1, \"full_name\": \"munene vic\", \"password_changed\": false}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-13 21:22:19'),
(24, 1, 'logout', 'auth', 1, 'User signed out.', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-13 21:22:29'),
(25, 2, 'login', 'auth', 2, 'User signed in successfully.', NULL, '{\"username\": \"vic\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-13 21:22:47'),
(26, 2, 'supplier_created', 'suppliers', 1, 'Supplier created: vimarktech.', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-13 21:24:57'),
(27, 2, 'purchase_created', 'purchases', 1, 'Purchase created: PUR-000001.', NULL, '{\"total\": \"15000.00\", \"supplier_id\": 1}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-13 21:25:42'),
(28, 2, 'logout', 'auth', 2, 'User signed out.', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-13 21:27:35'),
(29, 1, 'login', 'auth', 1, 'User signed in successfully.', NULL, '{\"username\": \"munene\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-13 21:28:27'),
(30, 1, 'sale_created', 'sales', 5, 'Sale completed: SAL-000005.', NULL, '{\"cogs\": \"2000.00\", \"total\": \"5000.00\", \"credit\": \"1000.00\", \"gross_profit\": \"3000.00\", \"cash_received\": \"4000.00\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-13 21:34:24'),
(31, 1, 'customer_payment_recorded', 'credit', 2, 'Customer payment recorded: CPY-000002.', NULL, '{\"amount\": \"500.00\", \"method\": \"Cash\", \"customer_id\": 2}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Safari/537.36', '2026-09-13 21:35:58');

-- --------------------------------------------------------

--
-- Table structure for table `branches`
--

CREATE TABLE `branches` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `code` varchar(30) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `branches`
--

INSERT INTO `branches` (`id`, `name`, `code`, `address`, `phone`, `email`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Main Shop', 'MAIN', NULL, NULL, NULL, 1, '2026-09-13 15:04:44', '2026-09-13 15:04:44');

-- --------------------------------------------------------

--
-- Table structure for table `cash_movements`
--

CREATE TABLE `cash_movements` (
  `id` bigint UNSIGNED NOT NULL,
  `cash_session_id` bigint UNSIGNED DEFAULT NULL,
  `branch_id` bigint UNSIGNED NOT NULL,
  `movement_type` enum('opening','sale','customer_payment','expense','cash_in','cash_out','adjustment','closing') NOT NULL,
  `reference_type` varchar(60) DEFAULT NULL,
  `reference_id` bigint UNSIGNED DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_by` bigint UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `cash_movements`
--

INSERT INTO `cash_movements` (`id`, `cash_session_id`, `branch_id`, `movement_type`, `reference_type`, `reference_id`, `amount`, `description`, `created_by`, `created_at`) VALUES
(1, NULL, 1, 'sale', 'sale', 1, 2000.00, 'Cash sale SAL-000001', 1, '2026-09-13 16:00:11'),
(2, NULL, 1, 'sale', 'sale', 1, 1000.00, 'Cash sale SAL-000001', 1, '2026-09-13 16:00:11'),
(3, NULL, 1, 'sale', 'sale', 3, 2000.00, 'Cash sale SAL-000003', 1, '2026-09-13 16:27:15'),
(4, NULL, 1, 'customer_payment', 'customer_payment', 1, 1000.00, 'Customer payment CPY-000001', 2, '2026-09-13 17:55:54'),
(5, NULL, 1, 'sale', 'sale', 4, 2000.00, 'Cash sale SAL-000004', 2, '2026-09-13 17:58:08'),
(6, NULL, 1, 'sale', 'sale', 5, 4000.00, 'Cash sale SAL-000005', 1, '2026-09-13 21:34:24'),
(7, NULL, 1, 'customer_payment', 'customer_payment', 2, 500.00, 'Customer payment CPY-000002', 1, '2026-09-13 21:35:58');

-- --------------------------------------------------------

--
-- Table structure for table `cash_registers`
--

CREATE TABLE `cash_registers` (
  `id` bigint UNSIGNED NOT NULL,
  `branch_id` bigint UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cash_sessions`
--

CREATE TABLE `cash_sessions` (
  `id` bigint UNSIGNED NOT NULL,
  `cash_register_id` bigint UNSIGNED NOT NULL,
  `opened_by` bigint UNSIGNED NOT NULL,
  `opened_at` datetime NOT NULL,
  `opening_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `closed_by` bigint UNSIGNED DEFAULT NULL,
  `closed_at` datetime DEFAULT NULL,
  `expected_closing_amount` decimal(15,2) DEFAULT NULL,
  `counted_closing_amount` decimal(15,2) DEFAULT NULL,
  `variance_amount` decimal(15,2) DEFAULT NULL,
  `status` enum('open','closed') NOT NULL DEFAULT 'open',
  `notes` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` bigint UNSIGNED NOT NULL,
  `parent_id` bigint UNSIGNED DEFAULT NULL,
  `name` varchar(120) NOT NULL,
  `slug` varchar(140) NOT NULL,
  `description` text,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_by` bigint UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `parent_id`, `name`, `slug`, `description`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, NULL, 'Carpets', 'carpets', NULL, 'active', 1, '2026-09-13 15:54:15', '2026-09-13 15:54:15');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `notes` text,
  `credit_limit` decimal(15,2) NOT NULL DEFAULT '0.00',
  `account_balance` decimal(15,2) NOT NULL DEFAULT '0.00',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_by` bigint UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `name`, `phone`, `email`, `address`, `notes`, `credit_limit`, `account_balance`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Victor Munene', '0711529618', 'victormunene207@gmail.com', '0711529618', NULL, 0.00, 0.00, 'active', 1, '2026-09-13 15:56:44', '2026-09-13 17:55:54'),
(2, 'Victor Munene', '0711529618', 'victormunene207@gmail.com', '0711529618', NULL, 0.00, 500.00, 'active', 1, '2026-09-13 16:19:16', '2026-09-13 21:35:58');

-- --------------------------------------------------------

--
-- Table structure for table `customer_ledger`
--

CREATE TABLE `customer_ledger` (
  `id` bigint UNSIGNED NOT NULL,
  `customer_id` bigint UNSIGNED NOT NULL,
  `entry_date` datetime NOT NULL,
  `entry_type` enum('opening_balance','credit_sale','payment','sale_return','adjustment_debit','adjustment_credit') NOT NULL,
  `sale_id` bigint UNSIGNED DEFAULT NULL,
  `customer_payment_id` bigint UNSIGNED DEFAULT NULL,
  `reference_no` varchar(120) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `debit_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `credit_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `balance_after` decimal(15,2) NOT NULL DEFAULT '0.00',
  `created_by` bigint UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `customer_ledger`
--

INSERT INTO `customer_ledger` (`id`, `customer_id`, `entry_date`, `entry_type`, `sale_id`, `customer_payment_id`, `reference_no`, `description`, `debit_amount`, `credit_amount`, `balance_after`, `created_by`, `created_at`) VALUES
(1, 1, '2026-09-13 16:27:15', 'credit_sale', 3, NULL, 'SAL-000003', 'Credit sale SAL-000003', 1000.00, 0.00, 1000.00, 1, '2026-09-13 16:27:15'),
(2, 1, '2026-09-13 17:55:54', 'payment', NULL, 1, 'CPY-000001', 'Customer debt payment', 0.00, 1000.00, 0.00, 2, '2026-09-13 17:55:54'),
(3, 2, '2026-09-13 21:34:24', 'credit_sale', 5, NULL, 'SAL-000005', 'Credit sale SAL-000005', 1000.00, 0.00, 1000.00, 1, '2026-09-13 21:34:24'),
(4, 2, '2026-09-13 21:35:58', 'payment', NULL, 2, 'CPY-000002', 'Customer debt payment', 0.00, 500.00, 500.00, 1, '2026-09-13 21:35:58');

-- --------------------------------------------------------

--
-- Table structure for table `customer_payments`
--

CREATE TABLE `customer_payments` (
  `id` bigint UNSIGNED NOT NULL,
  `customer_id` bigint UNSIGNED NOT NULL,
  `payment_no` varchar(60) NOT NULL,
  `payment_method_id` bigint UNSIGNED NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `reference_no` varchar(120) DEFAULT NULL,
  `payment_date` datetime NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `received_by` bigint UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `customer_payments`
--

INSERT INTO `customer_payments` (`id`, `customer_id`, `payment_no`, `payment_method_id`, `amount`, `reference_no`, `payment_date`, `notes`, `received_by`, `created_at`) VALUES
(1, 1, 'CPY-000001', 1, 1000.00, NULL, '2026-09-13 17:55:54', NULL, 2, '2026-09-13 17:55:54'),
(2, 2, 'CPY-000002', 1, 500.00, NULL, '2026-09-13 21:35:58', NULL, 1, '2026-09-13 21:35:58');

-- --------------------------------------------------------

--
-- Table structure for table `customer_payment_allocations`
--

CREATE TABLE `customer_payment_allocations` (
  `id` bigint UNSIGNED NOT NULL,
  `customer_payment_id` bigint UNSIGNED NOT NULL,
  `sale_id` bigint UNSIGNED NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `customer_payment_allocations`
--

INSERT INTO `customer_payment_allocations` (`id`, `customer_payment_id`, `sale_id`, `amount`, `created_at`) VALUES
(1, 1, 3, 1000.00, '2026-09-13 17:55:54'),
(2, 2, 5, 500.00, '2026-09-13 21:35:58');

-- --------------------------------------------------------

--
-- Table structure for table `document_sequences`
--

CREATE TABLE `document_sequences` (
  `id` bigint UNSIGNED NOT NULL,
  `sequence_key` varchar(80) NOT NULL,
  `prefix` varchar(20) NOT NULL,
  `current_value` bigint UNSIGNED NOT NULL DEFAULT '0',
  `padding` tinyint UNSIGNED NOT NULL DEFAULT '6',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `document_sequences`
--

INSERT INTO `document_sequences` (`id`, `sequence_key`, `prefix`, `current_value`, `padding`, `updated_at`) VALUES
(1, 'sale', 'SAL-', 5, 6, '2026-09-13 21:34:24'),
(2, 'purchase', 'PUR-', 1, 6, '2026-09-13 21:25:42'),
(3, 'customer_payment', 'CPY-', 2, 6, '2026-09-13 21:35:58'),
(4, 'expense', 'EXP-', 0, 6, '2026-09-13 15:04:44'),
(5, 'return', 'RET-', 0, 6, '2026-09-13 15:04:44');

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` bigint UNSIGNED NOT NULL,
  `branch_id` bigint UNSIGNED NOT NULL,
  `expense_category_id` bigint UNSIGNED NOT NULL,
  `payment_method_id` bigint UNSIGNED NOT NULL,
  `expense_no` varchar(60) NOT NULL,
  `expense_date` datetime NOT NULL,
  `description` varchar(255) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `reference_no` varchar(120) DEFAULT NULL,
  `receipt_path` varchar(255) DEFAULT NULL,
  `status` enum('active','voided') NOT NULL DEFAULT 'active',
  `created_by` bigint UNSIGNED NOT NULL,
  `voided_by` bigint UNSIGNED DEFAULT NULL,
  `voided_at` datetime DEFAULT NULL,
  `void_reason` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expense_categories`
--

CREATE TABLE `expense_categories` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `expense_categories`
--

INSERT INTO `expense_categories` (`id`, `name`, `description`, `is_active`, `created_at`) VALUES
(1, 'Transport', 'Transport and travel expenses', 1, '2026-09-13 15:04:44'),
(2, 'Electricity', 'Electricity bills and related charges', 1, '2026-09-13 15:04:44'),
(3, 'Water', 'Water bills and related charges', 1, '2026-09-13 15:04:44'),
(4, 'Rent', 'Shop or premises rent', 1, '2026-09-13 15:04:44'),
(5, 'Salaries', 'Employee salaries and wages', 1, '2026-09-13 15:04:44'),
(6, 'Delivery', 'Customer or supplier delivery costs', 1, '2026-09-13 15:04:44'),
(7, 'Internet', 'Internet and connectivity costs', 1, '2026-09-13 15:04:44'),
(8, 'Repairs', 'Repairs and maintenance costs', 1, '2026-09-13 15:04:44'),
(9, 'Advertising', 'Marketing and advertising costs', 1, '2026-09-13 15:04:44'),
(10, 'Packaging', 'Packaging materials and costs', 1, '2026-09-13 15:04:44'),
(11, 'Other', 'Other operating expenses', 1, '2026-09-13 15:04:44');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_methods`
--

CREATE TABLE `payment_methods` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(80) NOT NULL,
  `code` varchar(40) NOT NULL,
  `method_type` enum('cash','mpesa','bank','card','credit','other') NOT NULL,
  `requires_reference` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` smallint UNSIGNED NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `payment_methods`
--

INSERT INTO `payment_methods` (`id`, `name`, `code`, `method_type`, `requires_reference`, `is_active`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'Cash', 'CASH', 'cash', 0, 1, 10, '2026-09-13 15:04:44', '2026-09-13 15:04:44'),
(2, 'M-Pesa', 'MPESA', 'mpesa', 1, 1, 20, '2026-09-13 15:04:44', '2026-09-13 15:04:44'),
(3, 'Bank', 'BANK', 'bank', 1, 1, 30, '2026-09-13 15:04:44', '2026-09-13 15:04:44'),
(4, 'Card', 'CARD', 'card', 1, 1, 40, '2026-09-13 15:04:44', '2026-09-13 15:04:44'),
(5, 'Credit', 'CREDIT', 'credit', 0, 1, 50, '2026-09-13 15:04:44', '2026-09-13 15:04:44'),
(6, 'Other', 'OTHER', 'other', 1, 1, 60, '2026-09-13 15:04:44', '2026-09-13 15:04:44');

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint UNSIGNED NOT NULL,
  `module` varchar(80) NOT NULL,
  `name` varchar(120) NOT NULL,
  `slug` varchar(150) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `module`, `name`, `slug`, `description`, `created_at`) VALUES
(1, 'dashboard', 'View dashboard', 'dashboard.view', 'View business dashboard and KPIs', '2026-09-13 15:04:44'),
(2, 'pos', 'Use POS', 'pos.use', 'Open and use point of sale', '2026-09-13 15:04:44'),
(3, 'pos', 'Apply sale discount', 'pos.discount', 'Apply permitted line or sale discount', '2026-09-13 15:04:44'),
(4, 'pos', 'Override selling price', 'pos.price_override', 'Change selling price during sale', '2026-09-13 15:04:44'),
(5, 'pos', 'Hold sale', 'pos.hold', 'Hold and resume an incomplete sale', '2026-09-13 15:04:44'),
(6, 'pos', 'Void sale', 'pos.void', 'Void a completed sale', '2026-09-13 15:04:44'),
(7, 'sales', 'View sales', 'sales.view', 'View sales history', '2026-09-13 15:04:44'),
(8, 'sales', 'View sale details', 'sales.view_details', 'View individual sale details', '2026-09-13 15:04:44'),
(9, 'products', 'View products', 'products.view', 'View products', '2026-09-13 15:04:44'),
(10, 'products', 'Create products', 'products.create', 'Create products', '2026-09-13 15:04:44'),
(11, 'products', 'Update products', 'products.update', 'Update products', '2026-09-13 15:04:44'),
(12, 'products', 'Deactivate products', 'products.deactivate', 'Deactivate products', '2026-09-13 15:04:44'),
(13, 'inventory', 'View inventory', 'inventory.view', 'View stock and movement history', '2026-09-13 15:04:44'),
(14, 'inventory', 'Adjust stock', 'inventory.adjust', 'Create controlled stock adjustments', '2026-09-13 15:04:44'),
(15, 'inventory', 'View low stock', 'inventory.low_stock', 'View low stock alerts', '2026-09-13 15:04:44'),
(16, 'purchases', 'View purchases', 'purchases.view', 'View purchase records', '2026-09-13 15:04:44'),
(17, 'purchases', 'Create purchases', 'purchases.create', 'Create purchase orders/records', '2026-09-13 15:04:44'),
(18, 'purchases', 'Receive purchases', 'purchases.receive', 'Receive purchased stock', '2026-09-13 15:04:44'),
(19, 'purchases', 'Record purchase payments', 'purchases.pay', 'Record supplier purchase payments', '2026-09-13 15:04:44'),
(20, 'suppliers', 'View suppliers', 'suppliers.view', 'View suppliers', '2026-09-13 15:04:44'),
(21, 'suppliers', 'Manage suppliers', 'suppliers.manage', 'Create and edit suppliers', '2026-09-13 15:04:44'),
(22, 'customers', 'View customers', 'customers.view', 'View customers', '2026-09-13 15:04:44'),
(23, 'customers', 'Create customers', 'customers.create', 'Create customers', '2026-09-13 15:04:44'),
(24, 'customers', 'Update customers', 'customers.update', 'Update customers', '2026-09-13 15:04:44'),
(25, 'credit', 'View debtors', 'credit.view', 'View customer balances and statements', '2026-09-13 15:04:44'),
(26, 'credit', 'Create credit sale', 'credit.sell', 'Allow credit sale where customer rules permit', '2026-09-13 15:04:44'),
(27, 'credit', 'Record customer payment', 'credit.record_payment', 'Record debt repayment', '2026-09-13 15:04:44'),
(28, 'expenses', 'View expenses', 'expenses.view', 'View expenses', '2026-09-13 15:04:44'),
(29, 'expenses', 'Create expenses', 'expenses.create', 'Record expenses', '2026-09-13 15:04:44'),
(30, 'expenses', 'Edit expenses', 'expenses.edit', 'Edit permitted expense records', '2026-09-13 15:04:44'),
(31, 'expenses', 'Void expenses', 'expenses.void', 'Void expense records', '2026-09-13 15:04:44'),
(32, 'reports', 'View sales reports', 'reports.sales', 'View sales reports', '2026-09-13 15:04:44'),
(33, 'reports', 'View profit reports', 'reports.profit', 'View gross and net profit reports', '2026-09-13 15:04:44'),
(34, 'reports', 'View inventory reports', 'reports.inventory', 'View inventory reports', '2026-09-13 15:04:44'),
(35, 'reports', 'View purchase reports', 'reports.purchases', 'View purchase reports', '2026-09-13 15:04:44'),
(36, 'reports', 'View expense reports', 'reports.expenses', 'View expense reports', '2026-09-13 15:04:44'),
(37, 'reports', 'View customer reports', 'reports.customers', 'View customer and debtor reports', '2026-09-13 15:04:44'),
(38, 'reports', 'View payment reports', 'reports.payments', 'View payment reports', '2026-09-13 15:04:44'),
(39, 'reports', 'Export reports', 'reports.export', 'Export PDF/CSV reports', '2026-09-13 15:04:44'),
(40, 'users', 'View users', 'users.view', 'View users', '2026-09-13 15:04:44'),
(41, 'users', 'Create users', 'users.create', 'Create users', '2026-09-13 15:04:44'),
(42, 'users', 'Update users', 'users.update', 'Update users', '2026-09-13 15:04:44'),
(43, 'users', 'Manage roles', 'roles.manage', 'Manage roles', '2026-09-13 15:04:44'),
(44, 'users', 'Manage permissions', 'permissions.manage', 'Manage permission assignments', '2026-09-13 15:04:44'),
(45, 'audit', 'View audit log', 'audit.view', 'View audit/activity log', '2026-09-13 15:04:44'),
(46, 'settings', 'Manage business settings', 'settings.business', 'Manage business details', '2026-09-13 15:04:44'),
(47, 'settings', 'Manage system settings', 'settings.system', 'Manage system configuration', '2026-09-13 15:04:44');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` bigint UNSIGNED NOT NULL,
  `category_id` bigint UNSIGNED NOT NULL,
  `unit_id` bigint UNSIGNED NOT NULL,
  `default_supplier_id` bigint UNSIGNED DEFAULT NULL,
  `name` varchar(180) NOT NULL,
  `sku` varchar(80) NOT NULL,
  `barcode` varchar(120) DEFAULT NULL,
  `description` text,
  `buying_price` decimal(15,2) NOT NULL DEFAULT '0.00',
  `average_cost` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `selling_price` decimal(15,2) NOT NULL DEFAULT '0.00',
  `minimum_stock` decimal(15,3) NOT NULL DEFAULT '0.000',
  `current_stock` decimal(15,3) NOT NULL DEFAULT '0.000',
  `image_path` varchar(255) DEFAULT NULL,
  `track_stock` tinyint(1) NOT NULL DEFAULT '1',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_by` bigint UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `category_id`, `unit_id`, `default_supplier_id`, `name`, `sku`, `barcode`, `description`, `buying_price`, `average_cost`, `selling_price`, `minimum_stock`, `current_stock`, `image_path`, `track_stock`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 4, NULL, 'Marvel Carpet', '001', NULL, '3metres', 1500.00, 1500.0000, 3000.00, 5.000, 20.000, 'uploads/products/44faae87fcbfe72179ed04662f1a4ec1.png', 1, 'active', 1, '2026-09-13 15:55:38', '2026-09-13 16:27:15'),
(2, 1, 1, NULL, 'Fluffy Carpets (3m)', '002', NULL, NULL, 1000.00, 1000.0000, 2500.00, 5.000, 12.000, NULL, 1, 'active', 1, '2026-09-13 17:33:30', '2026-09-13 21:34:24');

-- --------------------------------------------------------

--
-- Table structure for table `purchases`
--

CREATE TABLE `purchases` (
  `id` bigint UNSIGNED NOT NULL,
  `branch_id` bigint UNSIGNED NOT NULL,
  `supplier_id` bigint UNSIGNED NOT NULL,
  `purchase_no` varchar(60) NOT NULL,
  `invoice_reference` varchar(120) DEFAULT NULL,
  `purchase_date` datetime NOT NULL,
  `status` enum('draft','ordered','partially_received','received','cancelled') NOT NULL DEFAULT 'draft',
  `subtotal` decimal(15,2) NOT NULL DEFAULT '0.00',
  `discount_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `amount_paid` decimal(15,2) NOT NULL DEFAULT '0.00',
  `balance_due` decimal(15,2) NOT NULL DEFAULT '0.00',
  `payment_status` enum('unpaid','partial','paid') NOT NULL DEFAULT 'unpaid',
  `notes` text,
  `created_by` bigint UNSIGNED NOT NULL,
  `received_by` bigint UNSIGNED DEFAULT NULL,
  `received_at` datetime DEFAULT NULL,
  `cancelled_by` bigint UNSIGNED DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `cancel_reason` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `purchases`
--

INSERT INTO `purchases` (`id`, `branch_id`, `supplier_id`, `purchase_no`, `invoice_reference`, `purchase_date`, `status`, `subtotal`, `discount_amount`, `total_amount`, `amount_paid`, `balance_due`, `payment_status`, `notes`, `created_by`, `received_by`, `received_at`, `cancelled_by`, `cancelled_at`, `cancel_reason`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'PUR-000001', NULL, '2026-09-13 00:00:00', 'ordered', 15000.00, 0.00, 15000.00, 0.00, 15000.00, 'unpaid', NULL, 2, NULL, NULL, NULL, NULL, NULL, '2026-09-13 21:25:42', '2026-09-13 21:25:42');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_items`
--

CREATE TABLE `purchase_items` (
  `id` bigint UNSIGNED NOT NULL,
  `purchase_id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `quantity` decimal(15,3) NOT NULL,
  `quantity_received` decimal(15,3) NOT NULL DEFAULT '0.000',
  `unit_cost` decimal(15,4) NOT NULL,
  `discount_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `line_total` decimal(15,2) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `purchase_items`
--

INSERT INTO `purchase_items` (`id`, `purchase_id`, `product_id`, `quantity`, `quantity_received`, `unit_cost`, `discount_amount`, `line_total`, `created_at`) VALUES
(1, 1, 1, 10.000, 0.000, 1500.0000, 0.00, 15000.00, '2026-09-13 21:25:42');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_payments`
--

CREATE TABLE `purchase_payments` (
  `id` bigint UNSIGNED NOT NULL,
  `purchase_id` bigint UNSIGNED NOT NULL,
  `payment_method_id` bigint UNSIGNED NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `reference_no` varchar(120) DEFAULT NULL,
  `paid_at` datetime NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_by` bigint UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `returns`
--

CREATE TABLE `returns` (
  `id` bigint UNSIGNED NOT NULL,
  `branch_id` bigint UNSIGNED NOT NULL,
  `return_no` varchar(60) NOT NULL,
  `return_type` enum('customer','supplier') NOT NULL,
  `sale_id` bigint UNSIGNED DEFAULT NULL,
  `purchase_id` bigint UNSIGNED DEFAULT NULL,
  `customer_id` bigint UNSIGNED DEFAULT NULL,
  `supplier_id` bigint UNSIGNED DEFAULT NULL,
  `return_date` datetime NOT NULL,
  `status` enum('draft','completed','voided') NOT NULL DEFAULT 'draft',
  `total_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `refund_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `notes` text,
  `created_by` bigint UNSIGNED NOT NULL,
  `voided_by` bigint UNSIGNED DEFAULT NULL,
  `voided_at` datetime DEFAULT NULL,
  `void_reason` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `return_items`
--

CREATE TABLE `return_items` (
  `id` bigint UNSIGNED NOT NULL,
  `return_id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `sale_item_id` bigint UNSIGNED DEFAULT NULL,
  `purchase_item_id` bigint UNSIGNED DEFAULT NULL,
  `quantity` decimal(15,3) NOT NULL,
  `unit_price` decimal(15,2) NOT NULL,
  `cost_price` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `line_total` decimal(15,2) NOT NULL,
  `stock_action` enum('restock','writeoff','none') NOT NULL DEFAULT 'restock',
  `reason` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `slug`, `description`, `is_system`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Administrator / Owner', 'owner', 'Full system access and business oversight.', 1, 1, '2026-09-13 15:04:44', '2026-09-13 15:04:44'),
(2, 'Manager', 'manager', 'Operational management access without full system administration.', 1, 1, '2026-09-13 15:04:44', '2026-09-13 15:04:44'),
(3, 'Cashier', 'cashier', 'POS, sales and permitted customer operations.', 1, 1, '2026-09-13 15:04:44', '2026-09-13 15:04:44'),
(4, 'Stock Manager', 'stock-manager', 'Inventory, products, suppliers and purchasing operations.', 1, 1, '2026-09-13 15:04:44', '2026-09-13 15:04:44');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `role_id` bigint UNSIGNED NOT NULL,
  `permission_id` bigint UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`) VALUES
(1, 1, '2026-09-13 15:04:44'),
(1, 2, '2026-09-13 15:04:44'),
(1, 3, '2026-09-13 15:04:44'),
(1, 4, '2026-09-13 15:04:44'),
(1, 5, '2026-09-13 15:04:44'),
(1, 6, '2026-09-13 15:04:44'),
(1, 7, '2026-09-13 15:04:44'),
(1, 8, '2026-09-13 15:04:44'),
(1, 9, '2026-09-13 15:04:44'),
(1, 10, '2026-09-13 15:04:44'),
(1, 11, '2026-09-13 15:04:44'),
(1, 12, '2026-09-13 15:04:44'),
(1, 13, '2026-09-13 15:04:44'),
(1, 14, '2026-09-13 15:04:44'),
(1, 15, '2026-09-13 15:04:44'),
(1, 16, '2026-09-13 15:04:44'),
(1, 17, '2026-09-13 15:04:44'),
(1, 18, '2026-09-13 15:04:44'),
(1, 19, '2026-09-13 15:04:44'),
(1, 20, '2026-09-13 15:04:44'),
(1, 21, '2026-09-13 15:04:44'),
(1, 22, '2026-09-13 15:04:44'),
(1, 23, '2026-09-13 15:04:44'),
(1, 24, '2026-09-13 15:04:44'),
(1, 25, '2026-09-13 15:04:44'),
(1, 26, '2026-09-13 15:04:44'),
(1, 27, '2026-09-13 15:04:44'),
(1, 28, '2026-09-13 15:04:44'),
(1, 29, '2026-09-13 15:04:44'),
(1, 30, '2026-09-13 15:04:44'),
(1, 31, '2026-09-13 15:04:44'),
(1, 32, '2026-09-13 15:04:44'),
(1, 33, '2026-09-13 15:04:44'),
(1, 34, '2026-09-13 15:04:44'),
(1, 35, '2026-09-13 15:04:44'),
(1, 36, '2026-09-13 15:04:44'),
(1, 37, '2026-09-13 15:04:44'),
(1, 38, '2026-09-13 15:04:44'),
(1, 39, '2026-09-13 15:04:44'),
(1, 40, '2026-09-13 15:04:44'),
(1, 41, '2026-09-13 15:04:44'),
(1, 42, '2026-09-13 15:04:44'),
(1, 43, '2026-09-13 15:04:44'),
(1, 44, '2026-09-13 15:04:44'),
(1, 45, '2026-09-13 15:04:44'),
(1, 46, '2026-09-13 15:04:44'),
(1, 47, '2026-09-13 15:04:44'),
(2, 1, '2026-09-13 15:04:44'),
(2, 2, '2026-09-13 15:04:44'),
(2, 3, '2026-09-13 15:04:44'),
(2, 5, '2026-09-13 15:04:44'),
(2, 6, '2026-09-13 15:04:44'),
(2, 7, '2026-09-13 15:04:44'),
(2, 8, '2026-09-13 15:04:44'),
(2, 9, '2026-09-13 15:04:44'),
(2, 10, '2026-09-13 15:04:44'),
(2, 11, '2026-09-13 15:04:44'),
(2, 12, '2026-09-13 15:04:44'),
(2, 13, '2026-09-13 15:04:44'),
(2, 14, '2026-09-13 15:04:44'),
(2, 15, '2026-09-13 15:04:44'),
(2, 16, '2026-09-13 15:04:44'),
(2, 17, '2026-09-13 15:04:44'),
(2, 18, '2026-09-13 15:04:44'),
(2, 19, '2026-09-13 15:04:44'),
(2, 20, '2026-09-13 15:04:44'),
(2, 21, '2026-09-13 15:04:44'),
(2, 22, '2026-09-13 15:04:44'),
(2, 23, '2026-09-13 15:04:44'),
(2, 24, '2026-09-13 15:04:44'),
(2, 25, '2026-09-13 15:04:44'),
(2, 26, '2026-09-13 15:04:44'),
(2, 27, '2026-09-13 15:04:44'),
(2, 28, '2026-09-13 15:04:44'),
(2, 29, '2026-09-13 15:04:44'),
(2, 30, '2026-09-13 15:04:44'),
(2, 31, '2026-09-13 15:04:44'),
(2, 32, '2026-09-13 15:04:44'),
(2, 33, '2026-09-13 15:04:44'),
(2, 34, '2026-09-13 15:04:44'),
(2, 35, '2026-09-13 15:04:44'),
(2, 36, '2026-09-13 15:04:44'),
(2, 37, '2026-09-13 15:04:44'),
(2, 38, '2026-09-13 15:04:44'),
(2, 39, '2026-09-13 15:04:44'),
(2, 45, '2026-09-13 15:04:44'),
(3, 1, '2026-09-13 15:04:44'),
(3, 2, '2026-09-13 15:04:44'),
(3, 3, '2026-09-13 15:04:44'),
(3, 5, '2026-09-13 15:04:44'),
(3, 7, '2026-09-13 15:04:44'),
(3, 8, '2026-09-13 15:04:44'),
(3, 9, '2026-09-13 15:04:44'),
(3, 22, '2026-09-13 15:04:44'),
(3, 23, '2026-09-13 15:04:44'),
(3, 24, '2026-09-13 15:04:44'),
(3, 25, '2026-09-13 15:04:44'),
(3, 26, '2026-09-13 15:04:44'),
(3, 27, '2026-09-13 15:04:44'),
(4, 1, '2026-09-13 15:04:44'),
(4, 9, '2026-09-13 15:04:44'),
(4, 10, '2026-09-13 15:04:44'),
(4, 11, '2026-09-13 15:04:44'),
(4, 12, '2026-09-13 15:04:44'),
(4, 13, '2026-09-13 15:04:44'),
(4, 14, '2026-09-13 15:04:44'),
(4, 15, '2026-09-13 15:04:44'),
(4, 16, '2026-09-13 15:04:44'),
(4, 17, '2026-09-13 15:04:44'),
(4, 18, '2026-09-13 15:04:44'),
(4, 19, '2026-09-13 15:04:44'),
(4, 20, '2026-09-13 15:04:44'),
(4, 21, '2026-09-13 15:04:44'),
(4, 34, '2026-09-13 15:04:44'),
(4, 35, '2026-09-13 15:04:44');

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` bigint UNSIGNED NOT NULL,
  `branch_id` bigint UNSIGNED NOT NULL,
  `sale_no` varchar(60) NOT NULL,
  `customer_id` bigint UNSIGNED DEFAULT NULL,
  `sale_date` datetime NOT NULL,
  `status` enum('held','completed','voided') NOT NULL DEFAULT 'held',
  `payment_status` enum('unpaid','partial','paid') NOT NULL DEFAULT 'unpaid',
  `subtotal` decimal(15,2) NOT NULL DEFAULT '0.00',
  `discount_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `cogs_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `gross_profit` decimal(15,2) NOT NULL DEFAULT '0.00',
  `amount_paid` decimal(15,2) NOT NULL DEFAULT '0.00',
  `balance_due` decimal(15,2) NOT NULL DEFAULT '0.00',
  `change_due` decimal(15,2) NOT NULL DEFAULT '0.00',
  `notes` text,
  `created_by` bigint UNSIGNED NOT NULL,
  `completed_at` datetime DEFAULT NULL,
  `voided_by` bigint UNSIGNED DEFAULT NULL,
  `voided_at` datetime DEFAULT NULL,
  `void_reason` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `branch_id`, `sale_no`, `customer_id`, `sale_date`, `status`, `payment_status`, `subtotal`, `discount_amount`, `total_amount`, `cogs_amount`, `gross_profit`, `amount_paid`, `balance_due`, `change_due`, `notes`, `created_by`, `completed_at`, `voided_by`, `voided_at`, `void_reason`, `created_at`, `updated_at`) VALUES
(1, 1, 'SAL-000001', NULL, '2026-09-13 16:00:11', 'completed', 'paid', 3000.00, 0.00, 3000.00, 1500.00, 1500.00, 3000.00, 0.00, 0.00, NULL, 1, '2026-09-13 16:00:11', NULL, NULL, NULL, '2026-09-13 16:00:11', '2026-09-13 16:00:11'),
(2, 1, 'SAL-000002', NULL, '2026-09-13 16:17:41', 'held', 'unpaid', 3000.00, 0.00, 3000.00, 1500.00, 1500.00, 0.00, 3000.00, 0.00, NULL, 1, NULL, NULL, NULL, NULL, '2026-09-13 16:17:41', '2026-09-13 16:17:41'),
(3, 1, 'SAL-000003', 1, '2026-09-13 16:27:15', 'completed', 'paid', 3000.00, 0.00, 3000.00, 1500.00, 1500.00, 3000.00, 0.00, 0.00, NULL, 1, '2026-09-13 16:27:15', NULL, NULL, NULL, '2026-09-13 16:27:15', '2026-09-13 17:55:54'),
(4, 1, 'SAL-000004', NULL, '2026-09-13 17:58:08', 'completed', 'paid', 2500.00, 0.00, 2500.00, 1000.00, 1500.00, 2500.00, 0.00, 0.00, NULL, 2, '2026-09-13 17:58:08', NULL, NULL, NULL, '2026-09-13 17:58:08', '2026-09-13 17:58:08'),
(5, 1, 'SAL-000005', 2, '2026-09-13 21:34:24', 'completed', 'partial', 5000.00, 0.00, 5000.00, 2000.00, 3000.00, 4500.00, 500.00, 0.00, NULL, 1, '2026-09-13 21:34:24', NULL, NULL, NULL, '2026-09-13 21:34:24', '2026-09-13 21:35:58');

-- --------------------------------------------------------

--
-- Table structure for table `sale_items`
--

CREATE TABLE `sale_items` (
  `id` bigint UNSIGNED NOT NULL,
  `sale_id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `product_name_snapshot` varchar(180) NOT NULL,
  `sku_snapshot` varchar(80) NOT NULL,
  `unit_snapshot` varchar(20) NOT NULL,
  `quantity` decimal(15,3) NOT NULL,
  `unit_price` decimal(15,2) NOT NULL,
  `cost_price` decimal(15,4) NOT NULL,
  `discount_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `line_total` decimal(15,2) NOT NULL,
  `cogs_amount` decimal(15,2) NOT NULL,
  `profit_amount` decimal(15,2) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `sale_items`
--

INSERT INTO `sale_items` (`id`, `sale_id`, `product_id`, `product_name_snapshot`, `sku_snapshot`, `unit_snapshot`, `quantity`, `unit_price`, `cost_price`, `discount_amount`, `line_total`, `cogs_amount`, `profit_amount`, `created_at`) VALUES
(1, 1, 1, 'Marvel Carpet', '001', 'm', 1.000, 3000.00, 1500.0000, 0.00, 3000.00, 1500.00, 1500.00, '2026-09-13 16:00:11'),
(2, 2, 1, 'Marvel Carpet', '001', 'm', 1.000, 3000.00, 1500.0000, 0.00, 3000.00, 1500.00, 1500.00, '2026-09-13 16:17:41'),
(3, 3, 1, 'Marvel Carpet', '001', 'm', 1.000, 3000.00, 1500.0000, 0.00, 3000.00, 1500.00, 1500.00, '2026-09-13 16:27:15'),
(4, 4, 2, 'Fluffy Carpets (3m)', '002', 'pc', 1.000, 2500.00, 1000.0000, 0.00, 2500.00, 1000.00, 1500.00, '2026-09-13 17:58:08'),
(5, 5, 2, 'Fluffy Carpets (3m)', '002', 'pc', 2.000, 2500.00, 1000.0000, 0.00, 5000.00, 2000.00, 3000.00, '2026-09-13 21:34:24');

-- --------------------------------------------------------

--
-- Table structure for table `sale_payments`
--

CREATE TABLE `sale_payments` (
  `id` bigint UNSIGNED NOT NULL,
  `sale_id` bigint UNSIGNED NOT NULL,
  `payment_method_id` bigint UNSIGNED NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `reference_no` varchar(120) DEFAULT NULL,
  `paid_at` datetime NOT NULL,
  `received_by` bigint UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `sale_payments`
--

INSERT INTO `sale_payments` (`id`, `sale_id`, `payment_method_id`, `amount`, `reference_no`, `paid_at`, `received_by`, `created_at`) VALUES
(1, 1, 1, 2000.00, NULL, '2026-09-13 16:00:11', 1, '2026-09-13 16:00:11'),
(2, 1, 1, 1000.00, NULL, '2026-09-13 16:00:11', 1, '2026-09-13 16:00:11'),
(3, 3, 1, 2000.00, NULL, '2026-09-13 16:27:15', 1, '2026-09-13 16:27:15'),
(4, 3, 5, 1000.00, NULL, '2026-09-13 16:27:15', 1, '2026-09-13 16:27:15'),
(5, 4, 1, 2000.00, NULL, '2026-09-13 17:58:08', 2, '2026-09-13 17:58:08'),
(6, 4, 2, 500.00, 'UYTUGFABVG', '2026-09-13 17:58:08', 2, '2026-09-13 17:58:08'),
(7, 5, 1, 4000.00, NULL, '2026-09-13 21:34:24', 1, '2026-09-13 21:34:24'),
(8, 5, 5, 1000.00, NULL, '2026-09-13 21:34:24', 1, '2026-09-13 21:34:24');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` bigint UNSIGNED NOT NULL,
  `setting_key` varchar(120) NOT NULL,
  `setting_value` text,
  `setting_type` enum('string','integer','decimal','boolean','json') NOT NULL DEFAULT 'string',
  `is_public` tinyint(1) NOT NULL DEFAULT '0',
  `updated_by` bigint UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `is_public`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 'business.name', 'Wambowa Carpets', 'string', 1, NULL, '2026-09-13 15:04:44', '2026-09-13 15:04:44'),
(2, 'business.currency', 'KES', 'string', 1, NULL, '2026-09-13 15:04:44', '2026-09-13 15:04:44'),
(3, 'business.currency_symbol', 'KSh', 'string', 1, NULL, '2026-09-13 15:04:44', '2026-09-13 15:04:44'),
(4, 'business.timezone', 'Africa/Nairobi', 'string', 1, NULL, '2026-09-13 15:04:44', '2026-09-13 15:04:44'),
(5, 'ui.default_theme', 'light', 'string', 1, NULL, '2026-09-13 15:04:44', '2026-09-13 15:04:44'),
(6, 'ui.allow_theme_toggle', '1', 'boolean', 1, NULL, '2026-09-13 15:04:44', '2026-09-13 15:04:44'),
(7, 'inventory.allow_negative_stock', '0', 'boolean', 0, NULL, '2026-09-13 15:04:44', '2026-09-13 15:04:44'),
(8, 'inventory.costing_method', 'weighted_average', 'string', 0, NULL, '2026-09-13 15:04:44', '2026-09-13 15:04:44'),
(9, 'sales.allow_overpayment', '0', 'boolean', 0, NULL, '2026-09-13 15:04:44', '2026-09-13 15:04:44'),
(10, 'security.session_timeout_minutes', '60', 'integer', 0, NULL, '2026-09-13 15:04:44', '2026-09-13 15:04:44');

-- --------------------------------------------------------

--
-- Table structure for table `stock_movements`
--

CREATE TABLE `stock_movements` (
  `id` bigint UNSIGNED NOT NULL,
  `branch_id` bigint UNSIGNED NOT NULL,
  `product_id` bigint UNSIGNED NOT NULL,
  `movement_type` enum('opening_stock','purchase','sale','customer_return','supplier_return','damage','loss','adjustment_in','adjustment_out','void_reversal','transfer_in','transfer_out') NOT NULL,
  `reference_type` varchar(60) DEFAULT NULL,
  `reference_id` bigint UNSIGNED DEFAULT NULL,
  `reference_no` varchar(120) DEFAULT NULL,
  `quantity_change` decimal(15,3) NOT NULL,
  `unit_cost` decimal(15,4) NOT NULL DEFAULT '0.0000',
  `stock_before` decimal(15,3) NOT NULL,
  `stock_after` decimal(15,3) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `created_by` bigint UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `stock_movements`
--

INSERT INTO `stock_movements` (`id`, `branch_id`, `product_id`, `movement_type`, `reference_type`, `reference_id`, `reference_no`, `quantity_change`, `unit_cost`, `stock_before`, `stock_after`, `reason`, `created_by`, `created_at`) VALUES
(1, 1, 1, 'opening_stock', 'product', 1, '001', 20.000, 1500.0000, 0.000, 20.000, 'Opening stock', 1, '2026-09-13 15:55:38'),
(2, 1, 1, 'sale', 'sale', 1, 'SAL-000001', -1.000, 1500.0000, 20.000, 19.000, 'POS sale', 1, '2026-09-13 16:00:11'),
(3, 1, 1, 'adjustment_in', 'adjustment', NULL, 'ADJ-20260913-160558-1', 2.000, 1500.0000, 19.000, 21.000, 'Adjusted stock', 1, '2026-09-13 16:05:58'),
(4, 1, 1, 'sale', 'sale', 3, 'SAL-000003', -1.000, 1500.0000, 21.000, 20.000, 'POS sale', 1, '2026-09-13 16:27:15'),
(5, 1, 2, 'opening_stock', 'product', 2, '002', 15.000, 1000.0000, 0.000, 15.000, 'Opening stock', 1, '2026-09-13 17:33:30'),
(6, 1, 2, 'sale', 'sale', 4, 'SAL-000004', -1.000, 1000.0000, 15.000, 14.000, 'POS sale', 2, '2026-09-13 17:58:08'),
(7, 1, 2, 'sale', 'sale', 5, 'SAL-000005', -2.000, 1000.0000, 14.000, 12.000, 'POS sale', 1, '2026-09-13 21:34:24');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `contact_person` varchar(150) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `alternate_phone` varchar(50) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `tax_number` varchar(80) DEFAULT NULL,
  `notes` text,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_by` bigint UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `name`, `contact_person`, `phone`, `alternate_phone`, `email`, `address`, `tax_number`, `notes`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'vimarktech', 'Victor Munene', '0711529618', '0100148124', 'victormunene207@gmail.com', 'Moi Avenue Nairobi, opposite BIHI Towers', NULL, NULL, 'active', 2, '2026-09-13 21:24:57', '2026-09-13 21:24:57');

-- --------------------------------------------------------

--
-- Table structure for table `units`
--

CREATE TABLE `units` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(80) NOT NULL,
  `short_name` varchar(20) NOT NULL,
  `allows_decimal` tinyint(1) NOT NULL DEFAULT '0',
  `decimal_places` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `units`
--

INSERT INTO `units` (`id`, `name`, `short_name`, `allows_decimal`, `decimal_places`, `is_active`, `created_at`) VALUES
(1, 'Piece', 'pc', 0, 0, 1, '2026-09-13 15:04:44'),
(2, 'Roll', 'roll', 1, 3, 1, '2026-09-13 15:04:44'),
(3, 'Box', 'box', 1, 3, 1, '2026-09-13 15:04:44'),
(4, 'Metre', 'm', 1, 3, 1, '2026-09-13 15:04:44'),
(5, 'Square Metre', 'sqm', 1, 3, 1, '2026-09-13 15:04:44'),
(6, 'Kilogram', 'kg', 1, 3, 1, '2026-09-13 15:04:44'),
(7, 'Other', 'other', 1, 3, 1, '2026-09-13 15:04:44');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint UNSIGNED NOT NULL,
  `role_id` bigint UNSIGNED NOT NULL,
  `branch_id` bigint UNSIGNED DEFAULT NULL,
  `full_name` varchar(150) NOT NULL,
  `username` varchar(80) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `status` enum('active','inactive','locked') NOT NULL DEFAULT 'active',
  `failed_login_attempts` smallint UNSIGNED NOT NULL DEFAULT '0',
  `locked_until` datetime DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `last_login_ip` varchar(45) DEFAULT NULL,
  `password_changed_at` datetime DEFAULT NULL,
  `created_by` bigint UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `role_id`, `branch_id`, `full_name`, `username`, `email`, `phone`, `password_hash`, `status`, `failed_login_attempts`, `locked_until`, `last_login_at`, `last_login_ip`, `password_changed_at`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Victor Munene', 'munene', 'victormunene207@gmail.com', '0711529618', '$2y$10$0lx9ck2FrrwfYSn.fM0Czu7.1ijO0scd6e04tu3o/L8O6DP/HJlki', 'active', 0, NULL, '2026-09-13 21:28:27', '::1', '2026-09-13 17:25:36', NULL, '2026-09-13 15:15:31', '2026-09-13 21:28:27'),
(2, 2, 1, 'munene vic', 'vic', 'vdebmunene207@gmail.com', '0100148124', '$2y$10$JdvCjqf02tqxjUL2b1.j7u9pZ4roOHm/TPyV2JNzhYvxvn/XWE42W', 'active', 0, NULL, '2026-09-13 21:22:47', '::1', '2026-09-13 17:36:06', 1, '2026-09-13 17:36:06', '2026-09-13 21:22:47');

-- --------------------------------------------------------

--
-- Table structure for table `user_preferences`
--

CREATE TABLE `user_preferences` (
  `user_id` bigint UNSIGNED NOT NULL,
  `theme` enum('light','dark') NOT NULL DEFAULT 'light',
  `sidebar_collapsed` tinyint(1) NOT NULL DEFAULT '0',
  `table_page_size` smallint UNSIGNED NOT NULL DEFAULT '25',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `user_preferences`
--

INSERT INTO `user_preferences` (`user_id`, `theme`, `sidebar_collapsed`, `table_page_size`, `created_at`, `updated_at`) VALUES
(1, 'dark', 0, 25, '2026-09-13 15:15:31', '2026-09-13 21:28:30'),
(2, 'dark', 0, 25, '2026-09-13 17:36:06', '2026-09-13 21:27:26');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_audit_user_date` (`user_id`,`created_at`),
  ADD KEY `idx_audit_module_date` (`module`,`created_at`),
  ADD KEY `idx_audit_record` (`module`,`record_id`);

--
-- Indexes for table `branches`
--
ALTER TABLE `branches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `idx_branches_active` (`is_active`);

--
-- Indexes for table `cash_movements`
--
ALTER TABLE `cash_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_cash_movements_user` (`created_by`),
  ADD KEY `idx_cash_movements_session` (`cash_session_id`),
  ADD KEY `idx_cash_movements_branch_date` (`branch_id`,`created_at`),
  ADD KEY `idx_cash_movements_reference` (`reference_type`,`reference_id`);

--
-- Indexes for table `cash_registers`
--
ALTER TABLE `cash_registers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_cash_register_branch_name` (`branch_id`,`name`);

--
-- Indexes for table `cash_sessions`
--
ALTER TABLE `cash_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_cash_sessions_register` (`cash_register_id`),
  ADD KEY `fk_cash_sessions_opened_by` (`opened_by`),
  ADD KEY `fk_cash_sessions_closed_by` (`closed_by`),
  ADD KEY `idx_cash_sessions_status` (`status`),
  ADD KEY `idx_cash_sessions_opened_at` (`opened_at`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `fk_categories_created_by` (`created_by`),
  ADD KEY `idx_categories_status` (`status`),
  ADD KEY `idx_categories_parent` (`parent_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_customers_created_by` (`created_by`),
  ADD KEY `idx_customers_name` (`name`),
  ADD KEY `idx_customers_phone` (`phone`),
  ADD KEY `idx_customers_status` (`status`),
  ADD KEY `idx_customers_balance` (`account_balance`);

--
-- Indexes for table `customer_ledger`
--
ALTER TABLE `customer_ledger`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_customer_ledger_payment` (`customer_payment_id`),
  ADD KEY `fk_customer_ledger_user` (`created_by`),
  ADD KEY `idx_customer_ledger_customer_date` (`customer_id`,`entry_date`),
  ADD KEY `idx_customer_ledger_sale` (`sale_id`);

--
-- Indexes for table `customer_payments`
--
ALTER TABLE `customer_payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payment_no` (`payment_no`),
  ADD KEY `fk_customer_payments_method` (`payment_method_id`),
  ADD KEY `fk_customer_payments_user` (`received_by`),
  ADD KEY `idx_customer_payments_customer` (`customer_id`),
  ADD KEY `idx_customer_payments_date` (`payment_date`);

--
-- Indexes for table `customer_payment_allocations`
--
ALTER TABLE `customer_payment_allocations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_customer_payment_sale` (`customer_payment_id`,`sale_id`),
  ADD KEY `idx_customer_payment_alloc_sale` (`sale_id`);

--
-- Indexes for table `document_sequences`
--
ALTER TABLE `document_sequences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sequence_key` (`sequence_key`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `expense_no` (`expense_no`),
  ADD KEY `fk_expenses_branch` (`branch_id`),
  ADD KEY `fk_expenses_payment_method` (`payment_method_id`),
  ADD KEY `fk_expenses_created_by` (`created_by`),
  ADD KEY `fk_expenses_voided_by` (`voided_by`),
  ADD KEY `idx_expenses_date` (`expense_date`),
  ADD KEY `idx_expenses_category` (`expense_category_id`),
  ADD KEY `idx_expenses_status` (`status`);

--
-- Indexes for table `expense_categories`
--
ALTER TABLE `expense_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token_hash` (`token_hash`),
  ADD KEY `fk_password_reset_user` (`user_id`),
  ADD KEY `idx_password_reset_expiry` (`expires_at`);

--
-- Indexes for table `payment_methods`
--
ALTER TABLE `payment_methods`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `idx_payment_methods_active` (`is_active`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_permissions_module` (`module`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD UNIQUE KEY `barcode` (`barcode`),
  ADD KEY `fk_products_unit` (`unit_id`),
  ADD KEY `fk_products_created_by` (`created_by`),
  ADD KEY `idx_products_name` (`name`),
  ADD KEY `idx_products_category` (`category_id`),
  ADD KEY `idx_products_supplier` (`default_supplier_id`),
  ADD KEY `idx_products_status` (`status`),
  ADD KEY `idx_products_stock` (`current_stock`,`minimum_stock`);

--
-- Indexes for table `purchases`
--
ALTER TABLE `purchases`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `purchase_no` (`purchase_no`),
  ADD KEY `fk_purchases_branch` (`branch_id`),
  ADD KEY `fk_purchases_created_by` (`created_by`),
  ADD KEY `fk_purchases_received_by` (`received_by`),
  ADD KEY `fk_purchases_cancelled_by` (`cancelled_by`),
  ADD KEY `idx_purchases_date` (`purchase_date`),
  ADD KEY `idx_purchases_supplier` (`supplier_id`),
  ADD KEY `idx_purchases_status` (`status`),
  ADD KEY `idx_purchases_payment_status` (`payment_status`);

--
-- Indexes for table `purchase_items`
--
ALTER TABLE `purchase_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_purchase_items_purchase` (`purchase_id`),
  ADD KEY `idx_purchase_items_product` (`product_id`);

--
-- Indexes for table `purchase_payments`
--
ALTER TABLE `purchase_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_purchase_payments_method` (`payment_method_id`),
  ADD KEY `fk_purchase_payments_user` (`created_by`),
  ADD KEY `idx_purchase_payments_purchase` (`purchase_id`),
  ADD KEY `idx_purchase_payments_date` (`paid_at`);

--
-- Indexes for table `returns`
--
ALTER TABLE `returns`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `return_no` (`return_no`),
  ADD KEY `fk_returns_branch` (`branch_id`),
  ADD KEY `fk_returns_customer` (`customer_id`),
  ADD KEY `fk_returns_supplier` (`supplier_id`),
  ADD KEY `fk_returns_created_by` (`created_by`),
  ADD KEY `fk_returns_voided_by` (`voided_by`),
  ADD KEY `idx_returns_date` (`return_date`),
  ADD KEY `idx_returns_type` (`return_type`),
  ADD KEY `idx_returns_sale` (`sale_id`),
  ADD KEY `idx_returns_purchase` (`purchase_id`);

--
-- Indexes for table `return_items`
--
ALTER TABLE `return_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_return_items_sale_item` (`sale_item_id`),
  ADD KEY `fk_return_items_purchase_item` (`purchase_item_id`),
  ADD KEY `idx_return_items_return` (`return_id`),
  ADD KEY `idx_return_items_product` (`product_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`role_id`,`permission_id`),
  ADD KEY `fk_role_permissions_permission` (`permission_id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sale_no` (`sale_no`),
  ADD KEY `fk_sales_branch` (`branch_id`),
  ADD KEY `fk_sales_voided_by` (`voided_by`),
  ADD KEY `idx_sales_date` (`sale_date`),
  ADD KEY `idx_sales_customer` (`customer_id`),
  ADD KEY `idx_sales_user` (`created_by`),
  ADD KEY `idx_sales_status` (`status`),
  ADD KEY `idx_sales_payment_status` (`payment_status`);

--
-- Indexes for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sale_items_sale` (`sale_id`),
  ADD KEY `idx_sale_items_product` (`product_id`);

--
-- Indexes for table `sale_payments`
--
ALTER TABLE `sale_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_sale_payments_user` (`received_by`),
  ADD KEY `idx_sale_payments_sale` (`sale_id`),
  ADD KEY `idx_sale_payments_date` (`paid_at`),
  ADD KEY `idx_sale_payments_method` (`payment_method_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `fk_settings_updated_by` (`updated_by`);

--
-- Indexes for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_stock_movements_user` (`created_by`),
  ADD KEY `idx_stock_movements_product_date` (`product_id`,`created_at`),
  ADD KEY `idx_stock_movements_branch_date` (`branch_id`,`created_at`),
  ADD KEY `idx_stock_movements_type` (`movement_type`),
  ADD KEY `idx_stock_movements_reference` (`reference_type`,`reference_id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_suppliers_created_by` (`created_by`),
  ADD KEY `idx_suppliers_name` (`name`),
  ADD KEY `idx_suppliers_status` (`status`);

--
-- Indexes for table `units`
--
ALTER TABLE `units`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `short_name` (`short_name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_users_created_by` (`created_by`),
  ADD KEY `idx_users_role` (`role_id`),
  ADD KEY `idx_users_branch` (`branch_id`),
  ADD KEY `idx_users_status` (`status`);

--
-- Indexes for table `user_preferences`
--
ALTER TABLE `user_preferences`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `branches`
--
ALTER TABLE `branches`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `cash_movements`
--
ALTER TABLE `cash_movements`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `cash_registers`
--
ALTER TABLE `cash_registers`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cash_sessions`
--
ALTER TABLE `cash_sessions`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `customer_ledger`
--
ALTER TABLE `customer_ledger`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `customer_payments`
--
ALTER TABLE `customer_payments`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `customer_payment_allocations`
--
ALTER TABLE `customer_payment_allocations`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `document_sequences`
--
ALTER TABLE `document_sequences`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `expense_categories`
--
ALTER TABLE `expense_categories`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_methods`
--
ALTER TABLE `payment_methods`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `purchases`
--
ALTER TABLE `purchases`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `purchase_items`
--
ALTER TABLE `purchase_items`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `purchase_payments`
--
ALTER TABLE `purchase_payments`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `returns`
--
ALTER TABLE `returns`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `return_items`
--
ALTER TABLE `return_items`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `sale_items`
--
ALTER TABLE `sale_items`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `sale_payments`
--
ALTER TABLE `sale_payments`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `stock_movements`
--
ALTER TABLE `stock_movements`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `units`
--
ALTER TABLE `units`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `fk_audit_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `cash_movements`
--
ALTER TABLE `cash_movements`
  ADD CONSTRAINT `fk_cash_movements_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cash_movements_session` FOREIGN KEY (`cash_session_id`) REFERENCES `cash_sessions` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cash_movements_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `cash_registers`
--
ALTER TABLE `cash_registers`
  ADD CONSTRAINT `fk_cash_registers_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `cash_sessions`
--
ALTER TABLE `cash_sessions`
  ADD CONSTRAINT `fk_cash_sessions_closed_by` FOREIGN KEY (`closed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cash_sessions_opened_by` FOREIGN KEY (`opened_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cash_sessions_register` FOREIGN KEY (`cash_register_id`) REFERENCES `cash_registers` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `fk_categories_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_categories_parent` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `fk_customers_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `customer_ledger`
--
ALTER TABLE `customer_ledger`
  ADD CONSTRAINT `fk_customer_ledger_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_customer_ledger_payment` FOREIGN KEY (`customer_payment_id`) REFERENCES `customer_payments` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_customer_ledger_sale` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_customer_ledger_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `customer_payments`
--
ALTER TABLE `customer_payments`
  ADD CONSTRAINT `fk_customer_payments_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_customer_payments_method` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_customer_payments_user` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `customer_payment_allocations`
--
ALTER TABLE `customer_payment_allocations`
  ADD CONSTRAINT `fk_customer_payment_alloc_payment` FOREIGN KEY (`customer_payment_id`) REFERENCES `customer_payments` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_customer_payment_alloc_sale` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `fk_expenses_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_expenses_category` FOREIGN KEY (`expense_category_id`) REFERENCES `expense_categories` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_expenses_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_expenses_payment_method` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_expenses_voided_by` FOREIGN KEY (`voided_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD CONSTRAINT `fk_password_reset_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_products_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_products_supplier` FOREIGN KEY (`default_supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_products_unit` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `purchases`
--
ALTER TABLE `purchases`
  ADD CONSTRAINT `fk_purchases_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_purchases_cancelled_by` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_purchases_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_purchases_received_by` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_purchases_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `purchase_items`
--
ALTER TABLE `purchase_items`
  ADD CONSTRAINT `fk_purchase_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_purchase_items_purchase` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `purchase_payments`
--
ALTER TABLE `purchase_payments`
  ADD CONSTRAINT `fk_purchase_payments_method` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_purchase_payments_purchase` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_purchase_payments_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `returns`
--
ALTER TABLE `returns`
  ADD CONSTRAINT `fk_returns_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_returns_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_returns_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_returns_purchase` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_returns_sale` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_returns_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_returns_voided_by` FOREIGN KEY (`voided_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `return_items`
--
ALTER TABLE `return_items`
  ADD CONSTRAINT `fk_return_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_return_items_purchase_item` FOREIGN KEY (`purchase_item_id`) REFERENCES `purchase_items` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_return_items_return` FOREIGN KEY (`return_id`) REFERENCES `returns` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_return_items_sale_item` FOREIGN KEY (`sale_item_id`) REFERENCES `sale_items` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `fk_role_permissions_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_role_permissions_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `fk_sales_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sales_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sales_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sales_voided_by` FOREIGN KEY (`voided_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD CONSTRAINT `fk_sale_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sale_items_sale` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `sale_payments`
--
ALTER TABLE `sale_payments`
  ADD CONSTRAINT `fk_sale_payments_method` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sale_payments_sale` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sale_payments_user` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `settings`
--
ALTER TABLE `settings`
  ADD CONSTRAINT `fk_settings_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD CONSTRAINT `fk_stock_movements_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_stock_movements_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_stock_movements_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD CONSTRAINT `fk_suppliers_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_users_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `user_preferences`
--
ALTER TABLE `user_preferences`
  ADD CONSTRAINT `fk_user_preferences_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
