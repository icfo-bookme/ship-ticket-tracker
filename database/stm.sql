-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 12, 2025 at 12:10 PM
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
-- Database: `stm`
--

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `type` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `ticket_id`, `package_id`, `type`, `created_at`, `updated_at`) VALUES
(1, 82, 9, 'departure', '2025-11-11 11:32:05', '2025-11-11 11:32:05'),
(2, 82, 9, 'return', '2025-11-11 11:32:05', '2025-11-11 11:32:05'),
(3, 83, 9, 'departure', '2025-11-11 22:38:26', '2025-11-11 22:38:26'),
(4, 83, 9, 'return', '2025-11-11 22:38:26', '2025-11-11 22:38:26');

-- --------------------------------------------------------

--
-- Table structure for table `company`
--

CREATE TABLE `company` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `company`
--

INSERT INTO `company` (`id`, `name`, `created_at`, `updated_at`, `status`) VALUES
(1, 'BDships', '0000-00-00 00:00:00', '0000-00-00 00:00:00', 1),
(4, 'Tecnova', '2025-11-04 23:53:31', '2025-11-04 23:53:31', 1),
(5, 'BookMe', '2025-11-06 03:54:32', '2025-11-06 03:54:32', 1);

-- --------------------------------------------------------

--
-- Table structure for table `co_passengers`
--

CREATE TABLE `co_passengers` (
  `id` int(11) NOT NULL,
  `ship_ticket_sale_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `nid` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `co_passengers`
--

INSERT INTO `co_passengers` (`id`, `ship_ticket_sale_id`, `name`, `nid`, `created_at`, `updated_at`) VALUES
(1, 83, 'Aladdin Freeman', 'Ratione nisi nesciun', '2025-11-11 22:38:26', '2025-11-11 22:38:26'),
(2, 83, 'Xenos Baker', 'Cum a similique do d', '2025-11-11 22:38:26', '2025-11-11 22:38:26'),
(3, 83, 'Uriel Rosa', 'Vel similique tempor', '2025-11-11 22:38:26', '2025-11-11 22:38:26'),
(4, 83, 'Keiko Mckee', 'Magna qui quo lorem', '2025-11-11 22:38:26', '2025-11-11 22:38:26'),
(5, 83, 'Tashya Vaughn', 'Fuga Neque non sint', '2025-11-11 22:38:26', '2025-11-11 22:38:26'),
(6, 83, 'Brenna Rivers', 'Quos quas doloribus', '2025-11-11 22:38:26', '2025-11-11 22:38:26');

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `refunds`
--

CREATE TABLE `refunds` (
  `id` int(11) NOT NULL,
  `sales_id` int(11) NOT NULL,
  `refunded_number_of_tickets` int(11) NOT NULL,
  `refunded_amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` varchar(50) DEFAULT 'refunded'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `refunds`
--

INSERT INTO `refunds` (`id`, `sales_id`, `refunded_number_of_tickets`, `refunded_amount`, `created_at`, `updated_at`, `status`) VALUES
(1, 42, 676, 21.00, '2025-11-08 04:12:37', '2025-11-08 04:12:37', 'refunded'),
(2, 45, 215, 97.00, '2025-11-08 04:13:28', '2025-11-10 03:38:06', 'refunded'),
(3, 46, 821, 56.00, '2025-11-08 04:13:28', '2025-11-08 04:13:28', 'refunded'),
(4, 47, 232, 23.00, '2025-11-08 05:37:38', '2025-11-08 05:37:38', 'refunded'),
(5, 48, 23, 3.00, '2025-11-08 05:39:56', '2025-11-08 05:39:56', 'refunded'),
(6, 42, 33, 3.00, '2025-11-08 05:42:11', '2025-11-08 05:42:11', 'refunded'),
(7, 45, 33, 33.00, '2025-11-08 05:43:19', '2025-11-08 05:43:19', 'refunded'),
(8, 47, 222, 1.00, '2025-11-08 05:47:51', '2025-11-08 05:47:51', 'refunded'),
(9, 46, 821, 56.00, '2025-11-09 02:58:21', '2025-11-09 02:58:21', 'refunded'),
(10, 45, 215, 97.00, '2025-11-10 02:45:27', '2025-11-10 02:45:27', 'refunded'),
(11, 48, 13, 25.00, '2025-11-10 02:46:03', '2025-11-10 02:46:03', 'refunded');

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('MbArUGaMcwAreI7G8mACTniddSKWkl7AHkJw1hym', 5, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiQ3cwTE95dVlHeXZtOHp0Mmsyd01WMzBtRnRKUkNRRXlyMENMQ1NiRSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzU6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9zYWxlcy9wZW5kaW5nIjtzOjU6InJvdXRlIjtOO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aTo1O30=', 1762945790),
('SDCpE588NfViK6ZF8CuMgjhhi8ikjxTEi5KcXqhn', 3, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiN1BOZ28wWnRpVkI2N1ZIRndJU0hrVWFzQmY3UW9mRE9IcG02M0tOdiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzU6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9zYWxlcy9wZW5kaW5nIjtzOjU6InJvdXRlIjtOO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aTozO30=', 1762945791);

-- --------------------------------------------------------

--
-- Table structure for table `shipments`
--

CREATE TABLE `shipments` (
  `id` int(11) NOT NULL,
  `ticket_id` int(11) DEFAULT NULL,
  `shipment_id` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shipments`
--

INSERT INTO `shipments` (`id`, `ticket_id`, `shipment_id`, `created_at`, `updated_at`) VALUES
(1, 41, '45454', '2025-11-06 05:18:06', '2025-11-06 05:18:06'),
(2, 41, '6565', '2025-11-06 05:30:38', '2025-11-06 05:30:38'),
(3, 39, '234343', '2025-11-06 05:46:36', '2025-11-06 05:46:36'),
(4, 40, '456564', '2025-11-06 06:31:34', '2025-11-06 06:31:34'),
(5, 41, '232332', '2025-11-07 22:48:46', '2025-11-07 22:48:46'),
(6, 40, '7654674', '2025-11-07 22:51:55', '2025-11-07 22:51:55'),
(7, 41, '4543', '2025-11-07 23:04:01', '2025-11-07 23:04:01'),
(8, 41, '543254325', '2025-11-07 23:33:49', '2025-11-07 23:33:49'),
(9, 42, '543254325', '2025-11-07 23:34:25', '2025-11-07 23:34:25'),
(10, 42, '46555', '2025-11-08 01:20:39', '2025-11-08 01:20:39'),
(11, 45, '543543', '2025-11-08 03:42:09', '2025-11-08 03:42:09'),
(12, 46, '543543', '2025-11-08 03:42:18', '2025-11-08 03:42:18'),
(13, 47, '543543', '2025-11-08 03:42:23', '2025-11-08 03:42:23'),
(14, 48, '43243214', '2025-11-08 05:39:27', '2025-11-08 05:39:27'),
(15, 45, '1234', '2025-11-10 02:37:58', '2025-11-10 02:37:58'),
(16, 49, '1234', '2025-11-10 02:38:04', '2025-11-10 02:38:04'),
(17, 50, '1234', '2025-11-10 02:38:09', '2025-11-10 02:38:09'),
(18, 51, '23232', '2025-11-10 04:36:35', '2025-11-10 04:36:35'),
(19, 60, '1234', '2025-11-12 00:14:32', '2025-11-12 00:14:32');

-- --------------------------------------------------------

--
-- Table structure for table `ships`
--

CREATE TABLE `ships` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `route` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ships`
--

INSERT INTO `ships` (`id`, `name`, `route`, `created_at`, `updated_at`, `status`) VALUES
(6, 'MV Karnafuly Express', 'Cox\'sbazar - SaintMartin - Cox\'sbazar', '2025-11-04 03:23:16', '2025-11-06 03:53:18', 1),
(28, 'Bayone', 'Chittagong - SaintMartin - Chittagong', '2025-11-06 03:53:01', '2025-11-06 03:53:01', 1),
(29, 'MV Baro Awlia', 'Cox\'sbazar - SaintMartin - Cox\'sbazar', '2025-11-06 03:53:58', '2025-11-06 03:53:58', 1);

-- --------------------------------------------------------

--
-- Table structure for table `ship_packages`
--

CREATE TABLE `ship_packages` (
  `id` int(11) NOT NULL,
  `ship_id` int(11) NOT NULL,
  `name` varchar(250) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `price` decimal(10,2) DEFAULT 0.00,
  `round_trip_price` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ship_packages`
--

INSERT INTO `ship_packages` (`id`, `ship_id`, `name`, `created_at`, `updated_at`, `price`, `round_trip_price`) VALUES
(3, 6, 'Lavender', '2025-11-08 23:40:25', '2025-11-08 23:40:25', 0.00, 0.00),
(4, 6, 'Marigold', '2025-11-08 23:40:31', '2025-11-08 23:40:31', 0.00, 0.00),
(5, 6, 'Open Deck', '2025-11-08 23:40:48', '2025-11-08 23:40:48', 0.00, 0.00),
(6, 6, 'Gladiolus', '2025-11-08 23:41:02', '2025-11-08 23:41:02', 0.00, 0.00),
(7, 29, 'Mozarat Chair', '2025-11-08 23:41:41', '2025-11-08 23:41:41', 0.00, 0.00),
(8, 29, 'Riviera Business Chair', '2025-11-08 23:41:48', '2025-11-08 23:41:48', 0.00, 0.00),
(9, 28, 'Economy Class Chair', '2025-11-08 23:42:13', '2025-11-08 23:42:13', 0.00, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `ship_ticket_sales`
--

CREATE TABLE `ship_ticket_sales` (
  `id` int(11) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_mobile` varchar(20) NOT NULL,
  `sales_source` varchar(255) DEFAULT NULL,
  `ship_id` int(11) NOT NULL,
  `journey_date` date NOT NULL,
  `ticket_fee` decimal(10,2) NOT NULL,
  `payment_method` varchar(255) NOT NULL,
  `received_amount` decimal(10,2) NOT NULL,
  `due_amount` decimal(10,2) DEFAULT 0.00,
  `company_id` int(11) NOT NULL,
  `issued_date` date NOT NULL,
  `sold_by` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` varchar(100) DEFAULT 'pending',
  `nid` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `number_of_ticket` int(11) DEFAULT NULL,
  `return_date` date DEFAULT NULL,
  `ticket_category` varchar(255) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `address` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ship_ticket_sales`
--

INSERT INTO `ship_ticket_sales` (`id`, `customer_name`, `customer_mobile`, `sales_source`, `ship_id`, `journey_date`, `ticket_fee`, `payment_method`, `received_amount`, `due_amount`, `company_id`, `issued_date`, `sold_by`, `created_at`, `updated_at`, `status`, `nid`, `email`, `number_of_ticket`, `return_date`, `ticket_category`, `date_of_birth`, `address`) VALUES
(42, 'Mira Baxter', '01343434343', 'WhatsApp(016)', 28, '2025-11-06', 81.00, 'Cash', 21.00, 60.00, 1, '2025-11-06', 'Wyoming Caldwell', '2025-11-06 06:19:46', '2025-11-10 02:25:26', NULL, 'Voluptatem sit amet', 'bizofymo@mailinator.com', 676, '2025-11-06', 'Irure esse omnis nec', NULL, NULL),
(45, 'Brenna Alford', '95', 'Facebook', 28, '1970-09-22', 44.00, 'Bkash', 97.00, 0.00, 1, '2025-11-08', 'Wyoming Caldwell', '2025-11-07 23:28:14', '2025-11-12 01:55:26', NULL, 'Dolor 090', 'g@mailinator.com', 215, '2003-05-21', 'Proident reprehende', NULL, NULL),
(46, 'Chelsea Vasquez', '31', 'WhatsApp(019)', 29, '2002-05-24', 34.00, 'Nagad', 56.00, 0.00, 1, '2025-11-08', 'Wyoming Caldwell', '2025-11-07 23:51:14', '2025-11-09 02:58:21', 'refunded', 'Dolore enim cupidita', 'loxyzex@mailinator.com', 821, '2000-07-16', 'Alias sit omnis maio', NULL, NULL),
(48, 'Felicia Macias', '50', 'Facebook', 6, '2025-11-08', 73.00, 'Cash', 25.00, 48.00, 5, '2025-11-08', 'Leroy Hester', '2025-11-08 03:41:00', '2025-11-10 02:46:03', 'partial-refunded', 'Fuga Quam eum volup', 'johosug@mailinator.com', 213, '1973-12-02', 'Sint iusto vero repu', NULL, NULL),
(49, 'Raya Burks', '01323814588', 'Walk-in', 6, '1987-08-13', 53.00, 'Nagad', 60.00, 0.00, 5, '2025-11-09', 'Wyoming Caldwell', '2025-11-09 00:10:08', '2025-11-10 02:38:22', 'shipped', 'Est cupidatat nostr', 'fovur@mailinator.com', 152, '1983-01-24', 'Lavender', NULL, NULL),
(50, 'Louis Hancock', '01323283676', 'WhatsApp(018)', 28, '2005-06-05', 24.00, 'Nagad', 41.00, 0.00, 1, '2025-11-09', 'Wyoming Caldwell', '2025-11-09 02:10:22', '2025-11-10 02:38:26', 'shipped', 'Aut rem sunt in inv', 'sesycetezo@mailinator.com', 780, '2014-12-08', 'Economy Class Chair', '2004-10-29', NULL),
(51, 'Amaya Dennis', '01434848484', 'Walk-in', 28, '2004-04-23', 13.00, 'Cash', 85.00, 0.00, 4, '2025-11-09', 'Leroy Hester', '2025-11-09 02:33:09', '2025-11-10 04:36:35', 'shipment_id_entered', 'Quibusdam laborum et', 'vagifi@mailinator.com', 497, '1998-06-09', 'Economy Class Chair', '1977-04-07', 'Officia doloribus in'),
(52, 'Winter Barr', '67', 'Others', 29, '2004-09-12', 9000.00, 'Bkash', 37.00, 9143.00, 4, '2025-11-10', 'Leroy Hester', '2025-11-10 02:35:27', '2025-11-10 05:54:37', 'ticket-printed', 'Veritatis dolorem du', 'womenyzy@mailinator.com', 90, '2023-03-01', 'Mozarat Chair', '1993-05-24', 'Facilis sit quia ex'),
(53, 'Quamar Cohen', '90', 'Walk-in', 29, '1988-06-20', 98.00, 'Bank Transfer', 85.00, 13.00, 1, '2025-11-10', 'Leroy Hester', '2025-11-10 02:35:41', '2025-11-10 04:06:43', 'ticket-issued', 'Dolorum adipisci iru', 'nylatypos@mailinator.com', 513, '2025-03-20', 'Mozarat Chair', '1974-05-26', 'At esse nisi et id'),
(54, 'Axel Randolph', '55', 'Messenger', 28, '2001-03-08', 59.00, 'Cash', 64.00, 0.00, 4, '2025-11-10', 'Leroy Hester', '2025-11-10 04:05:51', '2025-11-10 04:06:02', 'payment-verified', 'Eius commodo vel ut', 'byzo@mailinator.com', 490, '1994-12-02', 'Economy Class Chair', '2019-11-01', 'Asperiores qui eveni'),
(55, 'Zia Randall', '89', 'WhatsApp(016)', 28, '1990-02-02', 37.00, 'Bank Transfer', 14.00, 23.00, 4, '2025-11-10', 'Graiden Compton', '2025-11-10 04:43:31', '2025-11-11 23:18:56', 'payment-verified', 'Officia qui qui lore', 'surucyxyq@mailinator.com', 776, '2024-07-20', 'Economy Class Chair', '2011-08-19', 'Fuga Qui tenetur a'),
(56, 'Hammett Tran', '01434343434', 'Walk-in', 28, '1998-06-13', 19.00, 'Bank Transfer', 35.00, 0.00, 5, '2025-11-10', 'Graiden Compton', '2025-11-10 05:23:07', '2025-11-11 23:24:34', 'payment-verified', 'Ipsam minima ut sunt', 'sovobahuke@mailinator.com', 632, '2013-09-19', 'Economy Class Chair', '1973-05-23', 'Eligendi tempora qui'),
(57, 'Brody Long', '34', 'WhatsApp(016)', 29, '2014-01-27', 48.00, 'Bank Transfer', 46.00, 2.00, 1, '2025-11-10', 'Graiden Compton', '2025-11-10 05:27:40', '2025-11-11 23:47:06', 'payment-verified', 'Aut rerum nihil cons', 'xisef@mailinator.com', 524, '1999-05-10', 'Mozarat Chair', '2012-08-25', 'Quia iste error cupi'),
(58, 'Harrison Harrison', '83', 'Walk-in', 28, '2025-03-17', 47.00, 'Bank Transfer', 63.00, 0.00, 4, '2025-11-10', 'Graiden Compton', '2025-11-10 05:27:48', '2025-11-11 23:47:44', 'payment-verified', 'Dolore doloremque vo', 'hoponuj@mailinator.com', 629, '1983-01-18', 'Economy Class Chair', '2010-03-09', 'Aut eum odio est vel'),
(59, 'Alden Banks', '44', 'Facebook', 28, '2003-05-09', 91.00, 'Bkash', 86.00, 6.82, 5, '2025-11-10', 'Graiden Compton', '2025-11-10 05:27:56', '2025-11-10 05:27:56', 'pending', 'Adipisci nulla ex qu', 'hisuzybom@mailinator.com', 244, '1987-12-31', 'Economy Class Chair', '2023-12-25', 'Eligendi repellendus'),
(60, 'Channing Hubbard', '22', 'WhatsApp(016)', 6, '2012-08-10', 53.00, 'Bank Transfer', 26.00, 27.00, 5, '2025-11-10', 'Graiden Compton', '2025-11-10 05:28:04', '2025-11-12 00:14:32', 'shipment_id_entered', 'Aut possimus autem', 'wozipot@mailinator.com', 18, '1971-01-02', 'Lavender', '1978-11-28', 'Voluptates praesenti'),
(61, 'Winter Shepherd', '77', 'WhatsApp(019)', 6, '2009-08-07', 72.00, 'Bank Transfer', 36.00, 36.00, 4, '2025-11-10', 'Graiden Compton', '2025-11-10 05:28:11', '2025-11-10 05:28:11', 'pending', 'Sint velit minima do', 'tunukileg@mailinator.com', 913, '1979-04-18', 'Lavender', '2022-05-30', 'Proident cupiditate'),
(62, 'Vaughan Kline', '89', 'WhatsApp(019)', 28, '1996-10-06', 85.00, 'Nagad', 13.00, 73.70, 1, '2025-11-10', 'Graiden Compton', '2025-11-10 05:28:19', '2025-11-10 05:28:19', 'pending', 'Animi sint a id ne', 'pogofu@mailinator.com', 557, '1979-07-22', 'Economy Class Chair', '1977-06-05', 'Facilis corporis et'),
(63, 'Leslie Key', '36', 'WhatsApp(016)', 29, '2001-02-04', 45.00, 'Cash', 32.00, 13.00, 1, '2025-11-10', 'Graiden Compton', '2025-11-10 05:28:27', '2025-11-10 05:28:27', 'pending', 'Irure pariatur Eum', 'xiry@mailinator.com', 452, '2025-03-17', 'Mozarat Chair', '2018-09-27', 'Ipsa adipisci in do'),
(64, 'Hasad Hampton', '10', 'WhatsApp(019)', 28, '1973-09-11', 94.00, 'Bank Transfer', 7.00, 87.00, 4, '2025-11-10', 'Graiden Compton', '2025-11-10 05:28:35', '2025-11-10 05:28:35', 'pending', 'Minus repellendus P', 'hyja@mailinator.com', 825, '1972-12-24', 'Economy Class Chair', '2010-10-09', 'Quam iure nulla atqu'),
(65, 'Chase Saunders', '24', 'WhatsApp(018)', 28, '1994-01-18', 47.00, 'Cash', 3.00, 44.00, 4, '2025-11-10', 'Graiden Compton', '2025-11-10 05:28:43', '2025-11-10 05:28:43', 'pending', 'Perferendis blanditi', 'huzykesolu@mailinator.com', 767, '1984-08-01', 'Economy Class Chair', '1988-07-28', 'Alias eiusmod odit d'),
(66, 'Dennis Henson', '25', 'WhatsApp(016)', 28, '1983-11-14', 79.00, 'Bkash', 50.00, 30.58, 4, '2025-11-10', 'Graiden Compton', '2025-11-10 05:28:50', '2025-11-10 05:28:50', 'pending', 'Ea neque aut maxime', 'sekezizine@mailinator.com', 51, '1988-01-13', 'Economy Class Chair', '2015-09-30', 'Animi veniam sed q'),
(67, 'Scarlet Cantu', '60', 'WhatsApp(018)', 28, '2009-02-19', 67.00, 'Bkash', 11.00, 57.34, 4, '2025-11-10', 'Graiden Compton', '2025-11-10 05:28:57', '2025-11-10 05:28:57', 'pending', 'Id eveniet omnis d', 'jitebezel@mailinator.com', 970, '1985-02-08', 'Economy Class Chair', '2024-04-05', 'Quia magna culpa er'),
(68, 'Whilemina Mccoy', '32', 'WhatsApp(018)', 6, '1999-12-23', 25.00, 'Cash', 13.00, 12.00, 1, '2025-11-10', 'Graiden Compton', '2025-11-10 05:29:06', '2025-11-10 05:29:06', 'pending', 'Sed qui non tempora', 'nosom@mailinator.com', 184, '1970-01-01', 'Marigold', '1984-01-07', 'Dolorum consequuntur'),
(69, 'Jeremy Gilliam', '24', 'Facebook', 6, '1977-04-10', 74.00, 'Nagad', 82.00, 0.00, 5, '2025-11-10', 'Graiden Compton', '2025-11-10 05:29:13', '2025-11-10 05:29:13', 'pending', 'Commodi rem quidem d', 'nesuxixy@mailinator.com', 565, '1997-10-29', 'Lavender', '1978-06-10', 'Fugit in ullam quis'),
(70, 'Evangeline Haley', '01455656565', 'WhatsApp(018)', 28, '1996-03-16', 43.00, 'Bank Transfer', 8.00, 35.00, 1, '2025-11-11', 'Leroy Hester', '2025-11-11 10:27:06', '2025-11-11 10:27:06', 'pending', 'Eligendi ipsa eum a', 'lovyq@mailinator.com', 20, '2025-11-11', NULL, '1977-06-15', NULL),
(71, 'Lewis Donovan', '01987654543', 'WhatsApp(018)', 28, '2006-08-21', 25.00, 'Bkash', 120.00, 0.00, 5, '2025-11-11', 'Leroy Hester', '2025-11-11 11:12:19', '2025-11-11 11:12:19', 'pending', 'Ducimus cupiditate', 'qisisuqig@mailinator.com', 20, '2012-11-28', NULL, '1978-07-17', NULL),
(72, 'Lenore Hensley', '01545454545', 'Walk-in', 6, '1988-02-25', 87.00, 'Nagad', 68.00, 0.00, 1, '2025-11-11', 'Leroy Hester', '2025-11-11 11:20:43', '2025-11-11 11:20:43', 'pending', 'Aut praesentium offi', 'boheve@mailinator.com', 10, '2016-09-22', NULL, '1984-04-19', NULL),
(73, 'Lenore Hensley', '01545454545', 'Walk-in', 28, '1988-02-25', 87.00, 'Nagad', 68.00, 0.00, 1, '2025-11-11', 'Leroy Hester', '2025-11-11 11:21:49', '2025-11-11 11:21:49', 'pending', 'Aut praesentium offi', 'boheve@mailinator.com', 10, '2016-09-22', NULL, '1984-04-19', NULL),
(74, 'Suki Gilliam', '01989898765', 'Walk-in', 29, '2023-03-24', 51.00, 'Cash', 19.00, 32.00, 4, '2025-11-11', 'Leroy Hester', '2025-11-11 11:23:00', '2025-11-11 11:23:00', 'pending', 'Dolorum facilis non', 'wocokap@mailinator.com', 20, '2008-08-10', NULL, '1980-09-21', NULL),
(75, 'Adele Dejesus', '01656575654', 'Others', 29, '2014-11-12', 43.00, 'Bank Transfer', 97.00, 0.00, 1, '2025-11-11', 'Leroy Hester', '2025-11-11 11:24:18', '2025-11-11 11:24:18', 'pending', 'Nam fugiat voluptat', 'xezecamo@mailinator.com', 80, '2025-06-16', NULL, '1981-05-14', NULL),
(76, 'Brittany Nicholson', '01656575654', 'Walk-in', 29, '2001-07-02', 84.00, 'Cash', 36.00, 48.00, 4, '2025-11-11', 'Leroy Hester', '2025-11-11 11:25:32', '2025-11-11 11:25:32', 'pending', 'Ut et assumenda exer', 'gonajice@mailinator.com', 20, '2010-12-06', NULL, '1983-07-08', NULL),
(77, 'Leila Mendoza', '01656575654', 'Walk-in', 28, '1977-03-04', 37.00, 'Nagad', 52.00, 0.00, 5, '2025-11-11', 'Leroy Hester', '2025-11-11 11:27:30', '2025-11-11 11:27:30', 'pending', 'Sunt modi quis nulla', 'moqutib@mailinator.com', 20, '2013-02-24', NULL, '1993-10-24', NULL),
(78, 'Noelani Camacho', '01656575654', 'WhatsApp(019)', 28, '1978-05-18', 50.00, 'Bank Transfer', 15.00, 35.00, 1, '2025-11-11', 'Leroy Hester', '2025-11-11 11:28:12', '2025-11-11 11:28:12', 'pending', 'Itaque sed magna per', 'tafetyne@mailinator.com', 15, '2018-08-10', NULL, '1996-08-04', NULL),
(79, 'Ulric Morrow', '01656575654', 'WhatsApp(018)', 6, '1971-11-22', 58.00, 'Bkash', 37.00, 22.16, 4, '2025-11-11', 'Leroy Hester', '2025-11-11 11:29:50', '2025-11-11 11:29:50', 'pending', 'Earum accusamus volu', 'govecec@mailinator.com', 10, '1981-11-29', NULL, '2024-06-26', NULL),
(80, 'Nicole Mcfarland', '01656575654', 'WhatsApp(016)', 28, '1992-06-07', 10.00, 'Nagad', 222.00, 0.00, 4, '2025-11-11', 'Leroy Hester', '2025-11-11 11:30:38', '2025-11-11 11:30:38', 'pending', 'Debitis perferendis', 'haqihuqydy@mailinator.com', 20, '1977-07-23', NULL, '1970-04-24', NULL),
(81, 'Julie Hoover', '01656575654', 'Others', 29, '2014-05-07', 96.00, 'Cash', 90.00, 6.00, 5, '2025-11-11', 'Leroy Hester', '2025-11-11 11:31:08', '2025-11-11 11:31:08', 'pending', 'Consequatur Cupidit', 'texiqyxax@mailinator.com', 20, '2007-08-28', NULL, '2004-01-19', NULL),
(82, 'Diana Best', '01656575654', 'Walk-in', 28, '1973-02-12', 99.00, 'Cash', 88.00, 11.00, 1, '2025-11-11', 'Leroy Hester', '2025-11-11 11:32:05', '2025-11-11 11:32:05', 'pending', 'Sit sit consectetu', 'kihajumuw@mailinator.com', 20, '2021-02-13', NULL, '1993-02-10', NULL),
(83, 'Nolan Sawyer', '01646464646', 'Messenger', 28, '1979-11-26', 12.00, 'Bkash', 33.00, 0.00, 4, '2025-11-12', 'Leroy Hester', '2025-11-11 22:38:26', '2025-11-11 22:38:26', 'pending', 'Quae doloribus volup', 'jiwi@mailinator.com', 20, '2004-06-16', NULL, '2017-08-06', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Calvin Nunez', 'bujetaqypi@mailinator.com', NULL, '$2y$12$bzKCoCIqUXh1OJsFiNOrt.1AGS1A7MalfSnLVUfpDzjEwdoJA5i.6', 'K0llAbPNkltEdv5CMnLtITRSDuORKPhX0dELWwRALAwHQTNZwrAm6nfeJwlb', '2025-11-02 23:06:50', '2025-11-02 23:06:50'),
(2, 'Wyoming Caldwell', 'quwuxijep@mailinator.com', NULL, '$2y$12$Tc.WkX8altSWs9kE5IOKb.1Bp.YZUU1yXYq9C5VSZFNoWakoKKr7C', '9pOrKrPJouEOOIaHfQzZmN7NH4aVMMfwDwQee200XMdXlGZ7mIwRiLueLQgw', '2025-11-04 04:40:56', '2025-11-04 04:40:56'),
(3, 'Leroy Hester', 'muhahin@mailinator.com', NULL, '$2y$12$m2xr9wlLODh3gppfc.xnS.VwQPXAcecqXceS2l7cX1xBrNtATvVj2', '6V5nnOAwRJnNteC102eXUtvKyB4FmIOnzKBg0LYZf5zS80nBrxM7Mxi79nGs', '2025-11-04 22:48:13', '2025-11-04 22:48:13'),
(4, 'Graiden Compton', 'jery@mailinator.com', NULL, '$2y$12$D/xOTQTPh35HYJM.lpviOOv0kCYx2X1cyOkl21FcdDNVA.Z/l0QHS', NULL, '2025-11-10 00:34:48', '2025-11-10 00:34:48'),
(5, 'Rose Riley', 'bubakam@mailinator.com', NULL, '$2y$12$yQTUdZevWgpWjoAjOc9CXOTEJGnmlJ391VPzPhHhAlFHsGA5CnlO6', NULL, '2025-11-12 00:50:04', '2025-11-12 00:50:04');

-- --------------------------------------------------------

--
-- Table structure for table `verify_tracker`
--

CREATE TABLE `verify_tracker` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `verified_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `ticket_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `verify_tracker`
--

INSERT INTO `verify_tracker` (`id`, `name`, `verified_by`, `created_at`, `updated_at`, `ticket_id`) VALUES
(1, 'Document A', 3, '2025-11-11 23:18:56', '2025-11-11 23:18:56', 0),
(2, 'payment-verified', 3, '2025-11-11 23:24:34', '2025-11-11 23:24:34', 56),
(3, 'payment-verified', 3, '2025-11-11 23:47:06', '2025-11-11 23:47:06', 57),
(4, 'payment-verified', 3, '2025-11-11 23:47:44', '2025-11-11 23:47:44', 58),
(5, 'payment-verified', 3, '2025-11-12 00:12:33', '2025-11-12 00:12:33', 60),
(6, 'ticket-issued', 3, '2025-11-12 00:12:45', '2025-11-12 00:12:45', 60),
(7, 'ticket-printed', 3, '2025-11-12 00:14:20', '2025-11-12 00:14:20', 60),
(8, 'shipment_id_entered', 3, '2025-11-12 00:14:32', '2025-11-12 00:14:32', 60);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `company`
--
ALTER TABLE `company`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `co_passengers`
--
ALTER TABLE `co_passengers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ship_ticket_sale_id` (`ship_ticket_sale_id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `refunds`
--
ALTER TABLE `refunds`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `shipments`
--
ALTER TABLE `shipments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ships`
--
ALTER TABLE `ships`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ship_packages`
--
ALTER TABLE `ship_packages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ship_ticket_sales`
--
ALTER TABLE `ship_ticket_sales`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- Indexes for table `verify_tracker`
--
ALTER TABLE `verify_tracker`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_verified_by` (`verified_by`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `company`
--
ALTER TABLE `company`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `co_passengers`
--
ALTER TABLE `co_passengers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `refunds`
--
ALTER TABLE `refunds`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `shipments`
--
ALTER TABLE `shipments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `ships`
--
ALTER TABLE `ships`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `ship_packages`
--
ALTER TABLE `ship_packages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `ship_ticket_sales`
--
ALTER TABLE `ship_ticket_sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=84;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `verify_tracker`
--
ALTER TABLE `verify_tracker`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `co_passengers`
--
ALTER TABLE `co_passengers`
  ADD CONSTRAINT `co_passengers_ibfk_1` FOREIGN KEY (`ship_ticket_sale_id`) REFERENCES `ship_ticket_sales` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `verify_tracker`
--
ALTER TABLE `verify_tracker`
  ADD CONSTRAINT `fk_verified_by` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
