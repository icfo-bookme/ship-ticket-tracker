-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 08, 2025 at 01:36 PM
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

--
-- Dumping data for table `cache`
--

INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
('laravel-cache-bixipurun@mailinator.com|127.0.0.1', 'i:1;', 1762318136),
('laravel-cache-bixipurun@mailinator.com|127.0.0.1:timer', 'i:1762318136;', 1762318136);

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
(2, 45, 215, 97.00, '2025-11-08 04:13:28', '2025-11-08 04:13:28', 'refunded'),
(3, 46, 821, 56.00, '2025-11-08 04:13:28', '2025-11-08 04:13:28', 'refunded'),
(4, 47, 232, 23.00, '2025-11-08 05:37:38', '2025-11-08 05:37:38', 'refunded'),
(5, 48, 23, 3.00, '2025-11-08 05:39:56', '2025-11-08 05:39:56', 'refunded'),
(6, 42, 33, 3.00, '2025-11-08 05:42:11', '2025-11-08 05:42:11', 'refunded'),
(7, 45, 33, 33.00, '2025-11-08 05:43:19', '2025-11-08 05:43:19', 'refunded'),
(8, 47, 222, 1.00, '2025-11-08 05:47:51', '2025-11-08 05:47:51', 'refunded');

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
('HkHKed5urjOM2MMSTh9LZHL15rHzQB97i2yoX0ek', 2, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:144.0) Gecko/20100101 Firefox/144.0', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoid3BIaWpLV3NmNDAzd2ZRR0gzdW1lYUJ4UlFkdTNrN25VOHpuQmFSdCI7czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MjtzOjk6Il9wcmV2aW91cyI7YToyOntzOjM6InVybCI7czo0NjoiaHR0cDovLzEyNy4wLjAuMTo4MDAwL3NoaXAtdGlja2V0LXNhbGVzL2NyZWF0ZSI7czo1OiJyb3V0ZSI7czoyNDoic2hpcC10aWNrZXQtc2FsZXMuY3JlYXRlIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1762604204),
('pBJoWVs1bbEUnIZQe0eqZ1RMCb8OIGnGL73tEnli', 3, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoiOXFoMjkxNXBZTVZNUFJLOHE5QWtNNjVUM2U3REJZckFmYTNKZDdzUSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NDY6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9zaGlwLXRpY2tldC1zYWxlcy9jcmVhdGUiO3M6NToicm91dGUiO3M6MjQ6InNoaXAtdGlja2V0LXNhbGVzLmNyZWF0ZSI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6NTA6ImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjtpOjM7fQ==', 1762605254);

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
(14, 48, '43243214', '2025-11-08 05:39:27', '2025-11-08 05:39:27');

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
  `ticket_category` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ship_ticket_sales`
--

INSERT INTO `ship_ticket_sales` (`id`, `customer_name`, `customer_mobile`, `sales_source`, `ship_id`, `journey_date`, `ticket_fee`, `payment_method`, `received_amount`, `due_amount`, `company_id`, `issued_date`, `sold_by`, `created_at`, `updated_at`, `status`, `nid`, `email`, `number_of_ticket`, `return_date`, `ticket_category`) VALUES
(42, 'Mira Baxter', '01343434343', 'WhatsApp(016)', 6, '2025-11-06', 81.00, 'Cash', 21.00, 60.00, 1, '2025-11-06', 'Wyoming Caldwell', '2025-11-06 06:19:46', '2025-11-08 05:42:11', 'partial-refunded', 'Voluptatem sit amet', 'bizofymo@mailinator.com', 676, '2025-11-06', 'Irure esse omnis nec'),
(45, 'Brenna Alford', '95', 'Facebook', 6, '1970-09-22', 44.00, 'Bkash', 97.00, 0.00, 1, '2025-11-08', 'Wyoming Caldwell', '2025-11-07 23:28:14', '2025-11-08 05:43:19', 'partial-refunded', 'Dolor 8777', 'g@mailinator.com', 215, '2003-05-21', 'Proident reprehende'),
(46, 'Chelsea Vasquez', '31', 'WhatsApp(019)', 29, '2002-05-24', 34.00, 'Nagad', 56.00, 0.00, 1, '2025-11-08', 'Wyoming Caldwell', '2025-11-07 23:51:14', '2025-11-08 11:40:55', 'shipped', 'Dolore enim cupidita', 'loxyzex@mailinator.com', 821, '2000-07-16', 'Alias sit omnis maio'),
(47, 'Karen Wood', '27', 'Others', 28, '2000-02-29', 35.00, 'Nagad', 31.00, 4.70, 4, '2025-11-08', 'Leroy Hester', '2025-11-08 03:40:48', '2025-11-08 05:47:51', 'partial-refunded', 'Eos et consequat N', 'cyxuni@mailinator.com', 982, '1990-05-30', 'Autem deleniti id v'),
(48, 'Felicia Macias', '50', 'Facebook', 6, '2025-11-08', 73.00, 'Cash', 25.00, 48.00, 5, '2025-11-08', 'Leroy Hester', '2025-11-08 03:41:00', '2025-11-08 11:41:05', 'shipped', 'Fuga Quam eum volup', 'johosug@mailinator.com', 213, '1973-12-02', 'Sint iusto vero repu');

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
(3, 'Leroy Hester', 'muhahin@mailinator.com', NULL, '$2y$12$m2xr9wlLODh3gppfc.xnS.VwQPXAcecqXceS2l7cX1xBrNtATvVj2', NULL, '2025-11-04 22:48:13', '2025-11-04 22:48:13');

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
-- Indexes for table `company`
--
ALTER TABLE `company`
  ADD PRIMARY KEY (`id`);

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
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `company`
--
ALTER TABLE `company`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `shipments`
--
ALTER TABLE `shipments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `ships`
--
ALTER TABLE `ships`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `ship_ticket_sales`
--
ALTER TABLE `ship_ticket_sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
