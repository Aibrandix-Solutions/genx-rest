-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 11, 2025 at 11:38 AM
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
-- Database: `genx_rest`
--

-- --------------------------------------------------------

--
-- Table structure for table `areas`
--

CREATE TABLE `areas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `branch_id` bigint(20) UNSIGNED DEFAULT NULL,
  `area_name` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `areas`
--

INSERT INTO `areas` (`id`, `branch_id`, `area_name`, `created_at`, `updated_at`) VALUES
(1, 1, 'Ground Floor', '2025-11-02 23:23:52', '2025-11-02 23:23:52'),
(2, 2, 'Ground Floor', '2025-11-08 01:06:05', '2025-11-08 01:06:05'),
(3, 2, 'Ground Floor', '2025-11-08 02:13:56', '2025-11-08 02:13:56');

-- --------------------------------------------------------

--
-- Table structure for table `branches`
--

CREATE TABLE `branches` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `unique_hash` varchar(64) DEFAULT NULL,
  `restaurant_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(191) NOT NULL,
  `cloned_branch_name` varchar(191) DEFAULT NULL,
  `cloned_branch_id` varchar(191) DEFAULT NULL,
  `is_menu_clone` tinyint(1) NOT NULL DEFAULT 0,
  `is_item_categories_clone` tinyint(1) NOT NULL DEFAULT 0,
  `is_menu_items_clone` tinyint(1) NOT NULL DEFAULT 0,
  `is_item_modifiers_clone` tinyint(1) NOT NULL DEFAULT 0,
  `is_clone_reservation_settings` tinyint(1) NOT NULL DEFAULT 0,
  `is_clone_delivery_settings` tinyint(1) NOT NULL DEFAULT 0,
  `is_clone_kot_setting` tinyint(1) NOT NULL DEFAULT 0,
  `is_modifiers_groups_clone` tinyint(1) NOT NULL DEFAULT 0,
  `address` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `lat` decimal(10,7) DEFAULT NULL,
  `lng` decimal(10,7) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `branches`
--

INSERT INTO `branches` (`id`, `unique_hash`, `restaurant_id`, `name`, `cloned_branch_name`, `cloned_branch_id`, `is_menu_clone`, `is_item_categories_clone`, `is_menu_items_clone`, `is_item_modifiers_clone`, `is_clone_reservation_settings`, `is_clone_delivery_settings`, `is_clone_kot_setting`, `is_modifiers_groups_clone`, `address`, `created_at`, `updated_at`, `lat`, `lng`) VALUES
(1, '37b2a02b45aa322240c8', 1, 'Oluvil', NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 'Main Street, Oluvil 32360', '2025-11-02 00:36:11', '2025-11-03 23:14:24', 26.9125000, 75.7875000),
(2, '50fb7e28eb75a7fcdcd1', 1, 'Akkaraipattu', NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 'Main St, Akkaraipattu', '2025-11-02 00:36:11', '2025-11-03 23:15:17', 26.9125000, 75.7875000);

-- --------------------------------------------------------

--
-- Table structure for table `branch_delivery_settings`
--

CREATE TABLE `branch_delivery_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `branch_id` bigint(20) UNSIGNED NOT NULL,
  `max_radius` decimal(8,2) NOT NULL DEFAULT 5.00,
  `unit` enum('km','miles') NOT NULL DEFAULT 'km',
  `fee_type` varchar(191) NOT NULL DEFAULT 'fixed',
  `fixed_fee` decimal(8,2) DEFAULT NULL,
  `per_distance_rate` decimal(8,2) DEFAULT NULL,
  `free_delivery_over_amount` decimal(8,2) DEFAULT NULL,
  `free_delivery_within_radius` double DEFAULT NULL,
  `delivery_schedule_start` time DEFAULT NULL,
  `delivery_schedule_end` time DEFAULT NULL,
  `prep_time_minutes` int(11) NOT NULL DEFAULT 20,
  `additional_eta_buffer_time` int(11) DEFAULT NULL,
  `avg_delivery_speed_kmh` int(11) NOT NULL DEFAULT 30,
  `is_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(191) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(191) NOT NULL,
  `owner` varchar(191) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cart_header_images`
--

CREATE TABLE `cart_header_images` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `cart_header_setting_id` bigint(20) UNSIGNED NOT NULL,
  `image_path` varchar(191) NOT NULL,
  `alt_text` varchar(191) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cart_header_settings`
--

CREATE TABLE `cart_header_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `restaurant_id` bigint(20) UNSIGNED NOT NULL,
  `header_type` enum('text','image') NOT NULL DEFAULT 'text',
  `header_text` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cart_header_settings`
--

INSERT INTO `cart_header_settings` (`id`, `restaurant_id`, `header_type`, `header_text`, `created_at`, `updated_at`) VALUES
(1, 1, 'text', 'Ready to Satisfy Your Cravings? Place Your Order Now!', '2025-11-04 00:24:58', '2025-11-04 00:24:58');

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `cart_session_id` bigint(20) UNSIGNED DEFAULT NULL,
  `branch_id` bigint(20) UNSIGNED DEFAULT NULL,
  `menu_item_id` bigint(20) UNSIGNED DEFAULT NULL,
  `menu_item_variation_id` bigint(20) UNSIGNED DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(16,2) NOT NULL,
  `amount` decimal(16,2) NOT NULL,
  `tax_amount` decimal(16,2) DEFAULT NULL,
  `tax_percentage` decimal(8,4) DEFAULT NULL,
  `tax_breakup` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tax_breakup`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `kiosk_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cart_item_modifier_options`
--

CREATE TABLE `cart_item_modifier_options` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `cart_item_id` bigint(20) UNSIGNED DEFAULT NULL,
  `modifier_option_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cart_sessions`
--

CREATE TABLE `cart_sessions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `session_id` varchar(191) NOT NULL,
  `branch_id` bigint(20) UNSIGNED DEFAULT NULL,
  `order_id` bigint(20) UNSIGNED DEFAULT NULL,
  `order_type_id` bigint(20) UNSIGNED DEFAULT NULL,
  `placed_via` enum('pos','shop','kiosk') DEFAULT NULL,
  `order_type` varchar(191) NOT NULL,
  `sub_total` decimal(16,2) NOT NULL,
  `total` decimal(16,2) NOT NULL,
  `total_tax_amount` decimal(16,2) NOT NULL DEFAULT 0.00,
  `tax_mode` enum('order','item') NOT NULL DEFAULT 'order',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `kiosk_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cash_denominations`
--

CREATE TABLE `cash_denominations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `restaurant_id` bigint(20) UNSIGNED DEFAULT NULL,
  `branch_id` bigint(20) UNSIGNED DEFAULT NULL,
  `value` int(11) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cash_registers`
--

CREATE TABLE `cash_registers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `restaurant_id` bigint(20) UNSIGNED NOT NULL,
  `branch_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cash_registers`
--

INSERT INTO `cash_registers` (`id`, `restaurant_id`, `branch_id`, `name`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Default Register', 1, '2025-11-04 00:11:54', '2025-11-04 00:11:54');

-- --------------------------------------------------------

--
-- Table structure for table `cash_register_approvals`
--

CREATE TABLE `cash_register_approvals` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `cash_register_session_id` bigint(20) UNSIGNED NOT NULL,
  `approved_by` bigint(20) UNSIGNED NOT NULL,
  `approved_at` datetime NOT NULL,
  `manager_note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cash_register_counts`
--

CREATE TABLE `cash_register_counts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `cash_register_session_id` bigint(20) UNSIGNED NOT NULL,
  `cash_denomination_id` bigint(20) UNSIGNED NOT NULL,
  `count` int(11) NOT NULL DEFAULT 0,
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cash_register_global_settings`
--

CREATE TABLE `cash_register_global_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `purchase_code` varchar(191) DEFAULT NULL,
  `supported_until` timestamp NULL DEFAULT NULL,
  `purchased_on` timestamp NULL DEFAULT NULL,
  `license_type` varchar(20) DEFAULT NULL,
  `notify_update` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cash_register_global_settings`
--

INSERT INTO `cash_register_global_settings` (`id`, `purchase_code`, `supported_until`, `purchased_on`, `license_type`, `notify_update`, `created_at`, `updated_at`) VALUES
(1, NULL, NULL, NULL, NULL, 1, '2025-11-03 01:26:57', '2025-11-03 01:26:57');

-- --------------------------------------------------------

--
-- Table structure for table `cash_register_sessions`
--

CREATE TABLE `cash_register_sessions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `cash_register_id` bigint(20) UNSIGNED NOT NULL,
  `restaurant_id` bigint(20) UNSIGNED NOT NULL,
  `branch_id` bigint(20) UNSIGNED NOT NULL,
  `opened_by` bigint(20) UNSIGNED NOT NULL,
  `opened_at` datetime NOT NULL,
  `opening_float` decimal(12,2) NOT NULL DEFAULT 0.00,
  `closed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `closed_at` datetime DEFAULT NULL,
  `expected_cash` decimal(12,2) NOT NULL DEFAULT 0.00,
  `counted_cash` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discrepancy` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` varchar(191) NOT NULL DEFAULT 'open',
  `closing_note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cash_register_sessions`
--

INSERT INTO `cash_register_sessions` (`id`, `cash_register_id`, `restaurant_id`, `branch_id`, `opened_by`, `opened_at`, `opening_float`, `closed_by`, `approved_by`, `approved_at`, `closed_at`, `expected_cash`, `counted_cash`, `discrepancy`, `status`, `closing_note`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 2, '2025-11-04 05:42:12', 20000.00, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'open', NULL, '2025-11-04 00:12:12', '2025-11-04 00:12:12'),
(2, 1, 1, 1, 4, '2025-11-10 14:43:45', 50000.00, NULL, NULL, NULL, NULL, 0.00, 0.00, 0.00, 'open', NULL, '2025-11-10 09:13:45', '2025-11-10 09:13:45');

-- --------------------------------------------------------

--
-- Table structure for table `cash_register_settings`
--

CREATE TABLE `cash_register_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `restaurant_id` bigint(20) UNSIGNED NOT NULL,
  `force_open_after_login` tinyint(1) NOT NULL DEFAULT 0,
  `force_open_roles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`force_open_roles`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cash_register_settings`
--

INSERT INTO `cash_register_settings` (`id`, `restaurant_id`, `force_open_after_login`, `force_open_roles`, `created_at`, `updated_at`) VALUES
(1, 1, 1, '[\"3\",\"2\"]', '2025-11-04 00:13:34', '2025-11-04 00:14:41');

-- --------------------------------------------------------

--
-- Table structure for table `cash_register_transactions`
--

CREATE TABLE `cash_register_transactions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `cash_register_session_id` bigint(20) UNSIGNED NOT NULL,
  `restaurant_id` bigint(20) UNSIGNED NOT NULL,
  `branch_id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED DEFAULT NULL,
  `happened_at` datetime NOT NULL,
  `type` varchar(191) NOT NULL,
  `reference` varchar(191) DEFAULT NULL,
  `reason` varchar(191) DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `currency_code` varchar(191) DEFAULT NULL,
  `running_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cash_register_transactions`
--

INSERT INTO `cash_register_transactions` (`id`, `cash_register_session_id`, `restaurant_id`, `branch_id`, `order_id`, `happened_at`, `type`, `reference`, `reason`, `amount`, `currency_code`, `running_amount`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 5, '2025-11-04 05:47:04', 'cash_sale', '87b5fb3c-5df2-4020-90c9-3b6338b61177', 'POS cash sale', 8.40, NULL, 0.00, 2, '2025-11-04 00:17:04', '2025-11-04 00:17:04'),
(2, 1, 1, 1, 8, '2025-11-08 07:56:48', 'cash_sale', 'a2500d96-d0a3-450f-948d-5b7440970729', 'POS cash sale', 750.00, NULL, 0.00, 2, '2025-11-08 02:26:44', '2025-11-08 02:26:48'),
(3, 1, 1, 1, 15, '2025-11-08 09:55:49', 'cash_sale', 'a522fedf-b29a-48a5-81a4-19745933d7ac', 'POS cash sale', 45900.00, NULL, 0.00, 2, '2025-11-08 04:25:49', '2025-11-08 04:25:49'),
(4, 1, 1, 1, 16, '2025-11-08 10:40:04', 'cash_sale', '3dabb16a-ca05-42f2-80a8-35695855bee6', 'POS cash sale', 950.00, NULL, 0.00, 2, '2025-11-08 05:10:04', '2025-11-08 05:10:04'),
(5, 1, 1, 1, 19, '2025-11-10 09:34:17', 'cash_sale', 'de1295d6-f703-4430-826a-14d36c8fd248', 'POS cash sale', 1800.00, NULL, 0.00, 2, '2025-11-10 04:04:17', '2025-11-10 04:04:17');

-- --------------------------------------------------------

--
-- Table structure for table `contacts`
--

CREATE TABLE `contacts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `language_setting_id` bigint(20) UNSIGNED DEFAULT NULL,
  `email` varchar(191) DEFAULT NULL,
  `contact_company` varchar(191) DEFAULT NULL,
  `image` varchar(200) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `contacts`
--

INSERT INTO `contacts` (`id`, `language_setting_id`, `email`, `contact_company`, `image`, `address`, `created_at`, `updated_at`) VALUES
(1, 1, 'support@example.com', 'Bond Hobbs Inc', NULL, '957 Jamie Station, Lamontborough, SD 27319-9459', '2025-11-02 00:36:11', '2025-11-02 00:36:11');

-- --------------------------------------------------------

--
-- Table structure for table `countries`
--

CREATE TABLE `countries` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `countries_code` char(2) NOT NULL,
  `countries_name` varchar(191) NOT NULL,
  `phonecode` varchar(191) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `countries`
--

INSERT INTO `countries` (`id`, `countries_code`, `countries_name`, `phonecode`) VALUES
(1, 'AF', 'Afghanistan', '93'),
(2, 'AX', 'Åland Islands', '358'),
(3, 'AL', 'Albania', '355'),
(4, 'DZ', 'Algeria', '213'),
(5, 'AS', 'American Samoa', '1684'),
(6, 'AD', 'Andorra', '376'),
(7, 'AO', 'Angola', '244'),
(8, 'AI', 'Anguilla', '1264'),
(9, 'AQ', 'Antarctica', '0'),
(10, 'AG', 'Antigua and Barbuda', '1268'),
(11, 'AR', 'Argentina', '54'),
(12, 'AM', 'Armenia', '374'),
(13, 'AW', 'Aruba', '297'),
(14, 'AU', 'Australia', '61'),
(15, 'AT', 'Austria', '43'),
(16, 'AZ', 'Azerbaijan', '994'),
(17, 'BS', 'Bahamas', '1242'),
(18, 'BH', 'Bahrain', '973'),
(19, 'BD', 'Bangladesh', '880'),
(20, 'BB', 'Barbados', '1246'),
(21, 'BY', 'Belarus', '375'),
(22, 'BE', 'Belgium', '32'),
(23, 'BZ', 'Belize', '501'),
(24, 'BJ', 'Benin', '229'),
(25, 'BM', 'Bermuda', '1441'),
(26, 'BT', 'Bhutan', '975'),
(27, 'BO', 'Bolivia, Plurinational State of', '591'),
(28, 'BQ', 'Bonaire, Sint Eustatius and Saba', '599'),
(29, 'BA', 'Bosnia and Herzegovina', '387'),
(30, 'BW', 'Botswana', '267'),
(31, 'BV', 'Bouvet Island', '0'),
(32, 'BR', 'Brazil', '55'),
(33, 'IO', 'British Indian Ocean Territory', '246'),
(34, 'BN', 'Brunei Darussalam', '673'),
(35, 'BG', 'Bulgaria', '359'),
(36, 'BF', 'Burkina Faso', '226'),
(37, 'BI', 'Burundi', '257'),
(38, 'KH', 'Cambodia', '855'),
(39, 'CM', 'Cameroon', '237'),
(40, 'CA', 'Canada', '1'),
(41, 'CV', 'Cape Verde', '238'),
(42, 'KY', 'Cayman Islands', '1345'),
(43, 'CF', 'Central African Republic', '236'),
(44, 'TD', 'Chad', '235'),
(45, 'CL', 'Chile', '56'),
(46, 'CN', 'China', '86'),
(47, 'CX', 'Christmas Island', '61'),
(48, 'CC', 'Cocos (Keeling) Islands', '672'),
(49, 'CO', 'Colombia', '57'),
(50, 'KM', 'Comoros', '269'),
(51, 'CG', 'Congo', '242'),
(52, 'CD', 'Congo, the Democratic Republic of the', '242'),
(53, 'CK', 'Cook Islands', '682'),
(54, 'CR', 'Costa Rica', '506'),
(55, 'CI', 'Côte d\'Ivoire', '225'),
(56, 'HR', 'Croatia', '385'),
(57, 'CU', 'Cuba', '53'),
(58, 'CW', 'Curaçao', '599'),
(59, 'CY', 'Cyprus', '357'),
(60, 'CZ', 'Czech Republic', '420'),
(61, 'DK', 'Denmark', '45'),
(62, 'DJ', 'Djibouti', '253'),
(63, 'DM', 'Dominica', '1767'),
(64, 'DO', 'Dominican Republic', '1809'),
(65, 'EC', 'Ecuador', '593'),
(66, 'EG', 'Egypt', '20'),
(67, 'SV', 'El Salvador', '503'),
(68, 'GQ', 'Equatorial Guinea', '240'),
(69, 'ER', 'Eritrea', '291'),
(70, 'EE', 'Estonia', '372'),
(71, 'ET', 'Ethiopia', '251'),
(72, 'FK', 'Falkland Islands (Malvinas)', '500'),
(73, 'FO', 'Faroe Islands', '298'),
(74, 'FJ', 'Fiji', '679'),
(75, 'FI', 'Finland', '358'),
(76, 'FR', 'France', '33'),
(77, 'GF', 'French Guiana', '594'),
(78, 'PF', 'French Polynesia', '689'),
(79, 'TF', 'French Southern Territories', '0'),
(80, 'GA', 'Gabon', '241'),
(81, 'GM', 'Gambia', '220'),
(82, 'GE', 'Georgia', '995'),
(83, 'DE', 'Germany', '49'),
(84, 'GH', 'Ghana', '233'),
(85, 'GI', 'Gibraltar', '350'),
(86, 'GR', 'Greece', '30'),
(87, 'GL', 'Greenland', '299'),
(88, 'GD', 'Grenada', '1473'),
(89, 'GP', 'Guadeloupe', '590'),
(90, 'GU', 'Guam', '1671'),
(91, 'GT', 'Guatemala', '502'),
(92, 'GG', 'Guernsey', '44'),
(93, 'GN', 'Guinea', '224'),
(94, 'GW', 'Guinea-Bissau', '245'),
(95, 'GY', 'Guyana', '592'),
(96, 'HT', 'Haiti', '509'),
(97, 'HM', 'Heard Island and McDonald Islands', '0'),
(98, 'VA', 'Holy See (Vatican City State)', '39'),
(99, 'HN', 'Honduras', '504'),
(100, 'HK', 'Hong Kong', '852'),
(101, 'HU', 'Hungary', '36'),
(102, 'IS', 'Iceland', '354'),
(103, 'IN', 'India', '91'),
(104, 'ID', 'Indonesia', '62'),
(105, 'IR', 'Iran, Islamic Republic of', '98'),
(106, 'IQ', 'Iraq', '964'),
(107, 'IE', 'Ireland', '353'),
(108, 'IM', 'Isle of Man', '44'),
(109, 'IL', 'Israel', '972'),
(110, 'IT', 'Italy', '39'),
(111, 'JM', 'Jamaica', '1876'),
(112, 'JP', 'Japan', '81'),
(113, 'JE', 'Jersey', '44'),
(114, 'JO', 'Jordan', '962'),
(115, 'KZ', 'Kazakhstan', '7'),
(116, 'KE', 'Kenya', '254'),
(117, 'KI', 'Kiribati', '686'),
(118, 'KP', 'Korea, Democratic People\'s Republic of', '850'),
(119, 'KR', 'Korea, Republic of', '82'),
(120, 'KW', 'Kuwait', '965'),
(121, 'KG', 'Kyrgyzstan', '996'),
(122, 'LA', 'Lao People\'s Democratic Republic', '856'),
(123, 'LV', 'Latvia', '371'),
(124, 'LB', 'Lebanon', '961'),
(125, 'LS', 'Lesotho', '266'),
(126, 'LR', 'Liberia', '231'),
(127, 'LY', 'Libya', '218'),
(128, 'LI', 'Liechtenstein', '423'),
(129, 'LT', 'Lithuania', '370'),
(130, 'LU', 'Luxembourg', '352'),
(131, 'MO', 'Macao', '853'),
(132, 'MK', 'Macedonia, the Former Yugoslav Republic of', '389'),
(133, 'MG', 'Madagascar', '261'),
(134, 'MW', 'Malawi', '265'),
(135, 'MY', 'Malaysia', '60'),
(136, 'MV', 'Maldives', '960'),
(137, 'ML', 'Mali', '223'),
(138, 'MT', 'Malta', '356'),
(139, 'MH', 'Marshall Islands', '692'),
(140, 'MQ', 'Martinique', '596'),
(141, 'MR', 'Mauritania', '222'),
(142, 'MU', 'Mauritius', '230'),
(143, 'YT', 'Mayotte', '269'),
(144, 'MX', 'Mexico', '52'),
(145, 'FM', 'Micronesia, Federated States of', '691'),
(146, 'MD', 'Moldova, Republic of', '373'),
(147, 'MC', 'Monaco', '377'),
(148, 'MN', 'Mongolia', '976'),
(149, 'ME', 'Montenegro', '382'),
(150, 'MS', 'Montserrat', '1664'),
(151, 'MA', 'Morocco', '212'),
(152, 'MZ', 'Mozambique', '258'),
(153, 'MM', 'Myanmar', '95'),
(154, 'NA', 'Namibia', '264'),
(155, 'NR', 'Nauru', '674'),
(156, 'NP', 'Nepal', '977'),
(157, 'NL', 'Netherlands', '31'),
(158, 'NC', 'New Caledonia', '687'),
(159, 'NZ', 'New Zealand', '64'),
(160, 'NI', 'Nicaragua', '505'),
(161, 'NE', 'Niger', '227'),
(162, 'NG', 'Nigeria', '234'),
(163, 'NU', 'Niue', '683'),
(164, 'NF', 'Norfolk Island', '672'),
(165, 'MP', 'Northern Mariana Islands', '1670'),
(166, 'NO', 'Norway', '47'),
(167, 'OM', 'Oman', '968'),
(168, 'PK', 'Pakistan', '92'),
(169, 'PW', 'Palau', '680'),
(170, 'PS', 'Palestine, State of', '970'),
(171, 'PA', 'Panama', '507'),
(172, 'PG', 'Papua New Guinea', '675'),
(173, 'PY', 'Paraguay', '595'),
(174, 'PE', 'Peru', '51'),
(175, 'PH', 'Philippines', '63'),
(176, 'PN', 'Pitcairn', '0'),
(177, 'PL', 'Poland', '48'),
(178, 'PT', 'Portugal', '351'),
(179, 'PR', 'Puerto Rico', '1787'),
(180, 'QA', 'Qatar', '974'),
(181, 'RE', 'Réunion', '262'),
(182, 'RO', 'Romania', '40'),
(183, 'RU', 'Russian Federation', '7'),
(184, 'RW', 'Rwanda', '250'),
(185, 'BL', 'Saint Barthélemy', '590'),
(186, 'SH', 'Saint Helena, Ascension and Tristan da Cunha', '290'),
(187, 'KN', 'Saint Kitts and Nevis', '1869'),
(188, 'LC', 'Saint Lucia', '1758'),
(189, 'MF', 'Saint Martin (French part)', '590'),
(190, 'PM', 'Saint Pierre and Miquelon', '508'),
(191, 'VC', 'Saint Vincent and the Grenadines', '1784'),
(192, 'WS', 'Samoa', '684'),
(193, 'SM', 'San Marino', '378'),
(194, 'ST', 'Sao Tome and Principe', '239'),
(195, 'SA', 'Saudi Arabia', '966'),
(196, 'SN', 'Senegal', '221'),
(197, 'RS', 'Serbia', '381'),
(198, 'SC', 'Seychelles', '248'),
(199, 'SL', 'Sierra Leone', '232'),
(200, 'SG', 'Singapore', '65'),
(201, 'SX', 'Sint Maarten (Dutch part)', '1'),
(202, 'SK', 'Slovakia', '421'),
(203, 'SI', 'Slovenia', '386'),
(204, 'SB', 'Solomon Islands', '677'),
(205, 'SO', 'Somalia', '252'),
(206, 'ZA', 'South Africa', '27'),
(207, 'GS', 'South Georgia and the South Sandwich Islands', '0'),
(208, 'SS', 'South Sudan', '211'),
(209, 'ES', 'Spain', '34'),
(210, 'LK', 'Sri Lanka', '94'),
(211, 'SD', 'Sudan', '249'),
(212, 'SR', 'Suriname', '597'),
(213, 'SJ', 'Svalbard and Jan Mayen', '47'),
(214, 'SZ', 'Swaziland', '268'),
(215, 'SE', 'Sweden', '46'),
(216, 'CH', 'Switzerland', '41'),
(217, 'SY', 'Syrian Arab Republic', '963'),
(218, 'TW', 'Taiwan, Province of China', '886'),
(219, 'TJ', 'Tajikistan', '992'),
(220, 'TZ', 'Tanzania, United Republic of', '255'),
(221, 'TH', 'Thailand', '66'),
(222, 'TL', 'Timor-Leste', '670'),
(223, 'TG', 'Togo', '228'),
(224, 'TK', 'Tokelau', '690'),
(225, 'TO', 'Tonga', '676'),
(226, 'TT', 'Trinidad and Tobago', '1868'),
(227, 'TN', 'Tunisia', '216'),
(228, 'TR', 'Turkey', '90'),
(229, 'TM', 'Turkmenistan', '7370'),
(230, 'TC', 'Turks and Caicos Islands', '1649'),
(231, 'TV', 'Tuvalu', '688'),
(232, 'UG', 'Uganda', '256'),
(233, 'UA', 'Ukraine', '380'),
(234, 'AE', 'United Arab Emirates', '971'),
(235, 'GB', 'United Kingdom', '44'),
(236, 'US', 'United States', '1'),
(237, 'UM', 'United States Minor Outlying Islands', '1'),
(238, 'UY', 'Uruguay', '598'),
(239, 'UZ', 'Uzbekistan', '998'),
(240, 'VU', 'Vanuatu', '678'),
(241, 'VE', 'Venezuela, Bolivarian Republic of', '58'),
(242, 'VN', 'Viet Nam', '84'),
(243, 'VG', 'Virgin Islands, British', '1284'),
(244, 'VI', 'Virgin Islands, U.S.', '1340'),
(245, 'WF', 'Wallis and Futuna', '681'),
(246, 'EH', 'Western Sahara', '212'),
(247, 'YE', 'Yemen', '967'),
(248, 'ZM', 'Zambia', '260'),
(249, 'ZW', 'Zimbabwe', '263');

-- --------------------------------------------------------

--
-- Table structure for table `currencies`
--

CREATE TABLE `currencies` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `restaurant_id` bigint(20) UNSIGNED DEFAULT NULL,
  `currency_name` varchar(191) NOT NULL,
  `currency_code` varchar(191) NOT NULL,
  `currency_symbol` varchar(191) NOT NULL,
  `currency_position` enum('left','right','left_with_space','right_with_space') NOT NULL DEFAULT 'left',
  `no_of_decimal` int(10) UNSIGNED NOT NULL DEFAULT 2,
  `thousand_separator` varchar(191) DEFAULT ',',
  `decimal_separator` varchar(191) DEFAULT '.',
  `exchange_rate` decimal(16,2) DEFAULT NULL,
  `usd_price` decimal(16,2) DEFAULT NULL,
  `is_cryptocurrency` enum('yes','no') NOT NULL DEFAULT 'no'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `currencies`
--

INSERT INTO `currencies` (`id`, `restaurant_id`, `currency_name`, `currency_code`, `currency_symbol`, `currency_position`, `no_of_decimal`, `thousand_separator`, `decimal_separator`, `exchange_rate`, `usd_price`, `is_cryptocurrency`) VALUES
(1, 1, 'Dollars', 'USD', '$', 'left', 2, ',', '.', NULL, NULL, 'no'),
(2, 1, 'Rupee', 'INR', '₹', 'left', 2, ',', '.', NULL, NULL, 'no'),
(3, 1, 'Pounds', 'GBP', '£', 'left', 2, ',', '.', NULL, NULL, 'no'),
(4, 1, 'Euros', 'EUR', '€', 'left', 2, ',', '.', NULL, NULL, 'no'),
(5, 1, 'Lankan Rupess', 'LKR', 'Rs ', 'left', 2, ',', '.', NULL, NULL, 'no');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `restaurant_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(191) DEFAULT NULL,
  `phone` varchar(191) DEFAULT NULL,
  `phone_code` varchar(191) DEFAULT NULL,
  `email` varchar(191) DEFAULT NULL,
  `email_otp` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `delivery_address` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `restaurant_id`, `name`, `phone`, `phone_code`, `email`, `email_otp`, `created_at`, `updated_at`, `delivery_address`) VALUES
(1, 1, 'Fahad', NULL, NULL, NULL, NULL, '2025-11-10 04:06:12', '2025-11-10 04:06:12', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `customer_addresses`
--

CREATE TABLE `customer_addresses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED DEFAULT NULL,
  `label` varchar(191) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `lat` decimal(10,7) DEFAULT NULL,
  `lng` decimal(10,7) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `custom_menus`
--

CREATE TABLE `custom_menus` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `menu_name` varchar(191) NOT NULL,
  `menu_slug` varchar(191) NOT NULL,
  `menu_content` longtext DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `position` enum('header','footer') NOT NULL DEFAULT 'header',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `database_backups`
--

CREATE TABLE `database_backups` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `filename` varchar(191) NOT NULL,
  `file_path` varchar(191) NOT NULL,
  `file_size` varchar(191) DEFAULT NULL,
  `status` enum('completed','failed','in_progress') NOT NULL DEFAULT 'in_progress',
  `error_message` text DEFAULT NULL,
  `backup_type` enum('manual','scheduled') NOT NULL DEFAULT 'manual',
  `version` varchar(191) DEFAULT NULL,
  `stored_on` varchar(191) NOT NULL DEFAULT 'local',
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `database_backup_settings`
--

CREATE TABLE `database_backup_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `license_type` varchar(20) DEFAULT NULL,
  `purchase_code` varchar(191) DEFAULT NULL,
  `purchased_on` timestamp NULL DEFAULT NULL,
  `supported_until` timestamp NULL DEFAULT NULL,
  `notify_update` tinyint(1) NOT NULL DEFAULT 1,
  `is_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `frequency` enum('daily','weekly','monthly') NOT NULL DEFAULT 'daily',
  `backup_time` time NOT NULL DEFAULT '02:00:00',
  `retention_days` int(11) NOT NULL DEFAULT 30,
  `max_backups` int(11) NOT NULL DEFAULT 10,
  `include_files` tinyint(1) NOT NULL DEFAULT 0,
  `include_modules` tinyint(1) NOT NULL DEFAULT 0,
  `storage_location` enum('local','storage_setting') NOT NULL DEFAULT 'local',
  `storage_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`storage_config`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `database_backup_settings`
--

INSERT INTO `database_backup_settings` (`id`, `license_type`, `purchase_code`, `purchased_on`, `supported_until`, `notify_update`, `is_enabled`, `frequency`, `backup_time`, `retention_days`, `max_backups`, `include_files`, `include_modules`, `storage_location`, `storage_config`, `created_at`, `updated_at`) VALUES
(1, NULL, NULL, NULL, NULL, 1, 0, 'daily', '02:00:00', 30, 10, 0, 0, 'local', NULL, '2025-11-04 01:32:39', '2025-11-04 01:32:39');

-- --------------------------------------------------------

--
-- Table structure for table `delivery_executives`
--

CREATE TABLE `delivery_executives` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `branch_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `phone` varchar(191) DEFAULT NULL,
  `photo` varchar(191) DEFAULT NULL,
  `status` enum('available','on_delivery','inactive') NOT NULL DEFAULT 'available',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `delivery_fee_tiers`
--

CREATE TABLE `delivery_fee_tiers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `branch_id` bigint(20) UNSIGNED NOT NULL,
  `min_distance` double DEFAULT NULL,
  `max_distance` double DEFAULT NULL,
  `fee` decimal(8,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `delivery_platforms`
--

CREATE TABLE `delivery_platforms` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `branch_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(191) NOT NULL,
  `logo` varchar(191) DEFAULT NULL,
  `commission_type` enum('percent','fixed') NOT NULL DEFAULT 'percent',
  `commission_value` decimal(16,2) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `denominations`
--

CREATE TABLE `denominations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) NOT NULL,
  `name` varchar(191) NOT NULL,
  `value` decimal(10,2) NOT NULL,
  `type` enum('coin','note','bill') NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `branch_id` bigint(20) UNSIGNED DEFAULT NULL,
  `restaurant_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `desktop_applications`
--

CREATE TABLE `desktop_applications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `windows_file_path` varchar(191) DEFAULT NULL,
  `mac_file_path` varchar(191) DEFAULT NULL,
  `linux_file_path` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `desktop_applications`
--

INSERT INTO `desktop_applications` (`id`, `windows_file_path`, `mac_file_path`, `linux_file_path`, `created_at`, `updated_at`) VALUES
(1, 'https://envato.froid.works/app/download/windows', 'https://envato.froid.works/app/download/macos', 'https://envato.froid.works/app/download/linux', '2025-11-02 00:36:06', '2025-11-02 00:36:07');

-- --------------------------------------------------------

--
-- Table structure for table `email_settings`
--

CREATE TABLE `email_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `mail_from_name` varchar(191) DEFAULT NULL,
  `mail_from_email` varchar(191) DEFAULT NULL,
  `enable_queue` enum('yes','no') NOT NULL DEFAULT 'no',
  `mail_driver` enum('mail','smtp') NOT NULL DEFAULT 'mail',
  `smtp_host` varchar(191) DEFAULT NULL,
  `smtp_port` varchar(191) DEFAULT NULL,
  `smtp_encryption` varchar(191) DEFAULT NULL,
  `mail_username` varchar(191) DEFAULT NULL,
  `mail_password` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `email_verified` tinyint(1) NOT NULL DEFAULT 0,
  `verified` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `email_settings`
--

INSERT INTO `email_settings` (`id`, `mail_from_name`, `mail_from_email`, `enable_queue`, `mail_driver`, `smtp_host`, `smtp_port`, `smtp_encryption`, `mail_username`, `mail_password`, `created_at`, `updated_at`, `email_verified`, `verified`) VALUES
(1, 'TableTrack', 'from@email.com', 'no', 'smtp', 'smtp.gmail.com', '465', 'ssl', 'myemail@gmail.com', NULL, '2025-11-02 00:36:11', '2025-11-02 00:36:11', 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `expense_category_id` bigint(20) UNSIGNED DEFAULT NULL,
  `branch_id` bigint(20) UNSIGNED NOT NULL,
  `expense_title` varchar(191) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `expense_date` date NOT NULL,
  `payment_status` varchar(191) NOT NULL,
  `payment_date` date DEFAULT NULL,
  `payment_due_date` date DEFAULT NULL,
  `payment_method` varchar(191) DEFAULT NULL,
  `receipt_path` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expense_categories`
--

CREATE TABLE `expense_categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `branch_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `description` varchar(191) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `expense_categories`
--

INSERT INTO `expense_categories` (`id`, `branch_id`, `name`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 1, 'Rent', 'Monthly rent for restaurant space', 1, '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(2, 1, 'Utilities', 'Electricity, water, gas, and other utilities', 1, '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(3, 1, 'Salaries', 'Employee salaries and wages', 1, '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(4, 1, 'Ingredients', 'Food ingredients and raw materials', 1, '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(5, 1, 'Equipment', 'Kitchen equipment and appliances', 1, '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(6, 1, 'Marketing', 'Advertising and promotional expenses', 1, '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(7, 1, 'Insurance', 'Business insurance and liability coverage', 1, '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(8, 1, 'Maintenance', 'Repairs and maintenance costs', 1, '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(9, 1, 'Licenses', 'Business licenses and permits', 1, '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(10, 1, 'Miscellaneous', 'Other miscellaneous expenses', 1, '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(11, 2, 'Rent', 'Monthly rent for restaurant space', 1, '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(12, 2, 'Utilities', 'Electricity, water, gas, and other utilities', 1, '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(13, 2, 'Salaries', 'Employee salaries and wages', 1, '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(14, 2, 'Ingredients', 'Food ingredients and raw materials', 1, '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(15, 2, 'Equipment', 'Kitchen equipment and appliances', 1, '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(16, 2, 'Marketing', 'Advertising and promotional expenses', 1, '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(17, 2, 'Insurance', 'Business insurance and liability coverage', 1, '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(18, 2, 'Maintenance', 'Repairs and maintenance costs', 1, '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(19, 2, 'Licenses', 'Business licenses and permits', 1, '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(20, 2, 'Miscellaneous', 'Other miscellaneous expenses', 1, '2025-11-02 00:36:11', '2025-11-02 00:36:11');

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(191) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `file_storage`
--

CREATE TABLE `file_storage` (
  `id` int(10) UNSIGNED NOT NULL,
  `restaurant_id` bigint(20) UNSIGNED DEFAULT NULL,
  `path` varchar(191) NOT NULL,
  `filename` varchar(191) NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  `size` int(10) UNSIGNED NOT NULL,
  `storage_location` enum('local','aws_s3','digitalocean','wasabi','minio') NOT NULL DEFAULT 'local',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `file_storage`
--

INSERT INTO `file_storage` (`id`, `restaurant_id`, `path`, `filename`, `type`, `size`, `storage_location`, `created_at`, `updated_at`) VALUES
(1, 1, 'qrcodes', 'qrcode-branch-1-1.png', 'image/png', 2785, 'local', '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(2, 1, 'qrcodes', 'qrcode-branch-1-1.png', 'image/png', 2785, 'local', '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(3, 1, 'qrcodes', 'qrcode-branch-2-1.png', 'image/png', 2791, 'local', '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(4, 1, 'qrcodes', 'qrcode-branch-2-1.png', 'image/png', 2791, 'local', '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(5, 1, 'qrcodes', 'qrcode-1-t1.png', 'image/png', 4521, 'local', '2025-11-02 23:24:15', '2025-11-02 23:24:15'),
(6, 1, 'logo', '34b66a225d9e96407843ae519bc43f59.jpg', 'image/jpeg', 6742, 'local', '2025-11-03 23:29:28', '2025-11-03 23:29:28'),
(7, NULL, 'logo', '8505e6c5f35f411db1a4180a5c0ad724.png', 'image/png', 289225, 'local', '2025-11-04 01:10:14', '2025-11-04 01:10:14'),
(8, NULL, 'favicons/super-admin/', 'android-chrome-192x192.png', 'image/png', 18712, 'local', '2025-11-04 01:10:14', '2025-11-04 01:10:14'),
(9, NULL, 'favicons/super-admin/', 'android-chrome-512x512.png', 'image/png', 97532, 'local', '2025-11-04 01:10:14', '2025-11-04 01:10:14'),
(10, NULL, 'favicons/super-admin/', 'apple-touch-icon.png', 'image/png', 16860, 'local', '2025-11-04 01:10:14', '2025-11-04 01:10:14'),
(11, NULL, 'favicons/super-admin/', 'favicon-16x16.png', 'image/png', 469, 'local', '2025-11-04 01:10:14', '2025-11-04 01:10:14'),
(12, NULL, 'favicons/super-admin/', 'favicon-32x32.png', 'image/png', 1096, 'local', '2025-11-04 01:10:14', '2025-11-04 01:10:14'),
(13, NULL, 'favicons/super-admin/', 'favicon.ico', 'image/vnd.microsoft.icon', 15406, 'local', '2025-11-04 01:10:14', '2025-11-04 01:10:14'),
(14, 1, 'qrcodes', 'qrcode-2-101.png', 'image/png', 4806, 'local', '2025-11-08 02:14:15', '2025-11-08 02:14:15'),
(15, 1, 'item', 'df7e8585202f3dad2eb392807ac581d0.jpg', 'image/jpeg', 57102, 'local', '2025-11-08 02:24:04', '2025-11-08 02:24:04'),
(16, 1, 'item', '695db5003bf32d68cf2a21fb8f4b73cf.jpg', 'image/jpeg', 209652, 'local', '2025-11-08 04:20:56', '2025-11-08 04:20:56'),
(17, 1, 'payment_qr_code', 'eaf6f2562758569c3a6b9418c219a08b.png', 'image/png', 7851, 'local', '2025-11-08 04:55:01', '2025-11-08 04:55:01');

-- --------------------------------------------------------

--
-- Table structure for table `file_storage_settings`
--

CREATE TABLE `file_storage_settings` (
  `id` int(10) UNSIGNED NOT NULL,
  `filesystem` varchar(191) NOT NULL,
  `auth_keys` text DEFAULT NULL,
  `status` enum('enabled','disabled') NOT NULL DEFAULT 'disabled',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `file_storage_settings`
--

INSERT INTO `file_storage_settings` (`id`, `filesystem`, `auth_keys`, `status`, `created_at`, `updated_at`) VALUES
(1, 'local', NULL, 'enabled', '2025-11-02 00:35:59', '2025-11-02 00:35:59');

-- --------------------------------------------------------

--
-- Table structure for table `flags`
--

CREATE TABLE `flags` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `capital` varchar(191) DEFAULT NULL,
  `code` varchar(191) DEFAULT NULL,
  `continent` varchar(191) DEFAULT NULL,
  `name` varchar(191) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `flags`
--

INSERT INTO `flags` (`id`, `capital`, `code`, `continent`, `name`) VALUES
(1, 'Kabul', 'af', 'Asia', 'Afghanistan'),
(2, 'Mariehamn', 'ax', 'Europe', 'Aland Islands'),
(3, 'Tirana', 'al', 'Europe', 'Albania'),
(4, 'Algiers', 'dz', 'Africa', 'Algeria'),
(5, 'Pago Pago', 'as', 'Oceania', 'American Samoa'),
(6, 'Andorra la Vella', 'ad', 'Europe', 'Andorra'),
(7, 'Luanda', 'ao', 'Africa', 'Angola'),
(8, 'The Valley', 'ai', 'North America', 'Anguilla'),
(9, '', 'aq', '', 'Antarctica'),
(10, 'St. John\'s', 'ag', 'North America', 'Antigua and Barbuda'),
(11, 'Buenos Aires', 'ar', 'South America', 'Argentina'),
(12, 'Yerevan', 'am', 'Asia', 'Armenia'),
(13, 'Oranjestad', 'aw', 'South America', 'Aruba'),
(14, 'Georgetown', 'ac', 'Africa', 'Ascension Island'),
(15, 'Canberra', 'au', 'Oceania', 'Australia'),
(16, 'Vienna', 'at', 'Europe', 'Austria'),
(17, 'Baku', 'az', 'Asia', 'Azerbaijan'),
(18, 'Nassau', 'bs', 'North America', 'Bahamas'),
(19, 'Manama', 'bh', 'Asia', 'Bahrain'),
(20, 'Dhaka', 'bd', 'Asia', 'Bangladesh'),
(21, 'Bridgetown', 'bb', 'North America', 'Barbados'),
(22, 'Minsk', 'by', 'Europe', 'Belarus'),
(23, 'Brussels', 'be', 'Europe', 'Belgium'),
(24, 'Belmopan', 'bz', 'North America', 'Belize'),
(25, 'Porto-Novo', 'bj', 'Africa', 'Benin'),
(26, 'Hamilton', 'bm', 'North America', 'Bermuda'),
(27, 'Thimphu', 'bt', 'Asia', 'Bhutan'),
(28, 'Sucre', 'bo', 'South America', 'Bolivia'),
(29, 'Kralendijk', 'bq', 'South America', 'Bonaire, Sint Eustatius and Saba'),
(30, 'Sarajevo', 'ba', 'Europe', 'Bosnia and Herzegovina'),
(31, 'Gaborone', 'bw', 'Africa', 'Botswana'),
(32, '', 'bv', '', 'Bouvet Island'),
(33, 'Brasília', 'br', 'South America', 'Brazil'),
(34, 'Diego Garcia', 'io', 'Asia', 'British Indian Ocean Territory'),
(35, 'Bandar Seri Begawan', 'bn', 'Asia', 'Brunei Darussalam'),
(36, 'Sofia', 'bg', 'Europe', 'Bulgaria'),
(37, 'Ouagadougou', 'bf', 'Africa', 'Burkina Faso'),
(38, 'Bujumbura', 'bi', 'Africa', 'Burundi'),
(39, 'Praia', 'cv', 'Africa', 'Cabo Verde'),
(40, 'Phnom Penh', 'kh', 'Asia', 'Cambodia'),
(41, 'Yaoundé', 'cm', 'Africa', 'Cameroon'),
(42, 'Ottawa', 'ca', 'North America', 'Canada'),
(43, '', 'ic', '', 'Canary Islands'),
(44, '', 'es-ct', '', 'Catalonia'),
(45, 'George Town', 'ky', 'North America', 'Cayman Islands'),
(46, 'Bangui', 'cf', 'Africa', 'Central African Republic'),
(47, '', 'cefta', '', 'Central European Free Trade Agreement'),
(48, '', 'ea', '', 'Ceuta & Melilla'),
(49, 'N\'Djamena', 'td', 'Africa', 'Chad'),
(50, 'Santiago', 'cl', 'South America', 'Chile'),
(51, 'Beijing', 'cn', 'Asia', 'China'),
(52, 'Flying Fish Cove', 'cx', 'Asia', 'Christmas Island'),
(53, '', 'cp', '', 'Clipperton Island'),
(54, 'West Island', 'cc', 'Asia', 'Cocos (Keeling) Islands'),
(55, 'Bogotá', 'co', 'South America', 'Colombia'),
(56, 'Moroni', 'km', 'Africa', 'Comoros'),
(57, 'Avarua', 'ck', 'Oceania', 'Cook Islands'),
(58, 'San José', 'cr', 'North America', 'Costa Rica'),
(59, 'Zagreb', 'hr', 'Europe', 'Croatia'),
(60, 'Havana', 'cu', 'North America', 'Cuba'),
(61, 'Willemstad', 'cw', 'South America', 'Curaçao'),
(62, 'Nicosia', 'cy', 'Europe', 'Cyprus'),
(63, 'Prague', 'cz', 'Europe', 'Czech Republic'),
(64, 'Yamoussoukro', 'ci', 'Africa', 'Côte d\'Ivoire'),
(65, 'Kinshasa', 'cd', 'Africa', 'Democratic Republic of the Congo'),
(66, 'Copenhagen', 'dk', 'Europe', 'Denmark'),
(67, '', 'dg', '', 'Diego Garcia'),
(68, 'Djibouti', 'dj', 'Africa', 'Djibouti'),
(69, 'Roseau', 'dm', 'North America', 'Dominica'),
(70, 'Santo Domingo', 'do', 'North America', 'Dominican Republic'),
(71, 'Quito', 'ec', 'South America', 'Ecuador'),
(72, 'Cairo', 'eg', 'Africa', 'Egypt'),
(73, 'San Salvador', 'sv', 'North America', 'El Salvador'),
(74, 'London', 'gb-eng', 'Europe', 'England'),
(75, 'Malabo', 'gq', 'Africa', 'Equatorial Guinea'),
(76, 'Asmara', 'er', 'Africa', 'Eritrea'),
(77, 'Tallinn', 'ee', 'Europe', 'Estonia'),
(78, 'Lobamba, Mbabane', 'sz', 'Africa', 'Eswatini'),
(79, 'Addis Ababa', 'et', 'Africa', 'Ethiopia'),
(80, '', 'eu', '', 'Europe'),
(81, 'Stanley', 'fk', 'South America', 'Falkland Islands'),
(82, 'Tórshavn', 'fo', 'Europe', 'Faroe Islands'),
(83, 'Palikir', 'fm', 'Oceania', 'Federated States of Micronesia'),
(84, 'Suva', 'fj', 'Oceania', 'Fiji'),
(85, 'Helsinki', 'fi', 'Europe', 'Finland'),
(86, 'Paris', 'fr', 'Europe', 'France'),
(87, 'Cayenne', 'gf', 'South America', 'French Guiana'),
(88, 'Papeete', 'pf', 'Oceania', 'French Polynesia'),
(89, 'Saint-Pierre, Réunion', 'tf', 'Africa', 'French Southern Territories'),
(90, 'Libreville', 'ga', 'Africa', 'Gabon'),
(91, '', 'es-ga', '', 'Galicia'),
(92, 'Banjul', 'gm', 'Africa', 'Gambia'),
(93, 'Tbilisi', 'ge', 'Asia', 'Georgia'),
(94, 'Berlin', 'de', 'Europe', 'Germany'),
(95, 'Accra', 'gh', 'Africa', 'Ghana'),
(96, 'Gibraltar', 'gi', 'Europe', 'Gibraltar'),
(97, 'Athens', 'gr', 'Europe', 'Greece'),
(98, 'Nuuk', 'gl', 'North America', 'Greenland'),
(99, 'St. George\'s', 'gd', 'North America', 'Grenada'),
(100, 'Basse-Terre', 'gp', 'North America', 'Guadeloupe'),
(101, 'Hagåtña', 'gu', 'Oceania', 'Guam'),
(102, 'Guatemala City', 'gt', 'North America', 'Guatemala'),
(103, 'Saint Peter Port', 'gg', 'Europe', 'Guernsey'),
(104, 'Conakry', 'gn', 'Africa', 'Guinea'),
(105, 'Bissau', 'gw', 'Africa', 'Guinea-Bissau'),
(106, 'Georgetown', 'gy', 'South America', 'Guyana'),
(107, 'Port-au-Prince', 'ht', 'North America', 'Haiti'),
(108, '', 'hm', '', 'Heard Island and McDonald Islands'),
(109, 'Vatican City', 'va', 'Europe', 'Holy See'),
(110, 'Tegucigalpa', 'hn', 'North America', 'Honduras'),
(111, 'Hong Kong', 'hk', 'Asia', 'Hong Kong'),
(112, 'Budapest', 'hu', 'Europe', 'Hungary'),
(113, 'Reykjavik', 'is', 'Europe', 'Iceland'),
(114, 'New Delhi', 'in', 'Asia', 'India'),
(115, 'Jakarta', 'id', 'Asia', 'Indonesia'),
(116, 'Tehran', 'ir', 'Asia', 'Iran'),
(117, 'Baghdad', 'iq', 'Asia', 'Iraq'),
(118, 'Dublin', 'ie', 'Europe', 'Ireland'),
(119, 'Douglas', 'im', 'Europe', 'Isle of Man'),
(120, 'Jerusalem', 'il', 'Asia', 'Israel'),
(121, 'Rome', 'it', 'Europe', 'Italy'),
(122, 'Kingston', 'jm', 'North America', 'Jamaica'),
(123, 'Tokyo', 'jp', 'Asia', 'Japan'),
(124, 'Saint Helier', 'je', 'Europe', 'Jersey'),
(125, 'Amman', 'jo', 'Asia', 'Jordan'),
(126, 'Astana', 'kz', 'Asia', 'Kazakhstan'),
(127, 'Nairobi', 'ke', 'Africa', 'Kenya'),
(128, 'South Tarawa', 'ki', 'Oceania', 'Kiribati'),
(129, 'Pristina', 'xk', 'Europe', 'Kosovo'),
(130, 'Kuwait City', 'kw', 'Asia', 'Kuwait'),
(131, 'Bishkek', 'kg', 'Asia', 'Kyrgyzstan'),
(132, 'Vientiane', 'la', 'Asia', 'Laos'),
(133, 'Riga', 'lv', 'Europe', 'Latvia'),
(134, 'Beirut', 'lb', 'Asia', 'Lebanon'),
(135, 'Maseru', 'ls', 'Africa', 'Lesotho'),
(136, 'Monrovia', 'lr', 'Africa', 'Liberia'),
(137, 'Tripoli', 'ly', 'Africa', 'Libya'),
(138, 'Vaduz', 'li', 'Europe', 'Liechtenstein'),
(139, 'Vilnius', 'lt', 'Europe', 'Lithuania'),
(140, 'Luxembourg City', 'lu', 'Europe', 'Luxembourg'),
(141, 'Macau', 'mo', 'Asia', 'Macau'),
(142, 'Antananarivo', 'mg', 'Africa', 'Madagascar'),
(143, 'Lilongwe', 'mw', 'Africa', 'Malawi'),
(144, 'Kuala Lumpur', 'my', 'Asia', 'Malaysia'),
(145, 'Malé', 'mv', 'Asia', 'Maldives'),
(146, 'Bamako', 'ml', 'Africa', 'Mali'),
(147, 'Valletta', 'mt', 'Europe', 'Malta'),
(148, 'Majuro', 'mh', 'Oceania', 'Marshall Islands'),
(149, 'Fort-de-France', 'mq', 'North America', 'Martinique'),
(150, 'Nouakchott', 'mr', 'Africa', 'Mauritania'),
(151, 'Port Louis', 'mu', 'Africa', 'Mauritius'),
(152, 'Mamoudzou', 'yt', 'Africa', 'Mayotte'),
(153, 'Mexico City', 'mx', 'North America', 'Mexico'),
(154, 'Chișinău', 'md', 'Europe', 'Moldova'),
(155, 'Monaco', 'mc', 'Europe', 'Monaco'),
(156, 'Ulaanbaatar', 'mn', 'Asia', 'Mongolia'),
(157, 'Podgorica', 'me', 'Europe', 'Montenegro'),
(158, 'Little Bay, Brades, Plymouth', 'ms', 'North America', 'Montserrat'),
(159, 'Rabat', 'ma', 'Africa', 'Morocco'),
(160, 'Maputo', 'mz', 'Africa', 'Mozambique'),
(161, 'Naypyidaw', 'mm', 'Asia', 'Myanmar'),
(162, 'Windhoek', 'na', 'Africa', 'Namibia'),
(163, 'Yaren District', 'nr', 'Oceania', 'Nauru'),
(164, 'Kathmandu', 'np', 'Asia', 'Nepal'),
(165, 'Amsterdam', 'nl', 'Europe', 'Netherlands'),
(166, 'Nouméa', 'nc', 'Oceania', 'New Caledonia'),
(167, 'Wellington', 'nz', 'Oceania', 'New Zealand'),
(168, 'Managua', 'ni', 'North America', 'Nicaragua'),
(169, 'Niamey', 'ne', 'Africa', 'Niger'),
(170, 'Abuja', 'ng', 'Africa', 'Nigeria'),
(171, 'Alofi', 'nu', 'Oceania', 'Niue'),
(172, 'Kingston', 'nf', 'Oceania', 'Norfolk Island'),
(173, 'Pyongyang', 'kp', 'Asia', 'North Korea'),
(174, 'Skopje', 'mk', 'Europe', 'North Macedonia'),
(175, 'Belfast', 'gb-nir', 'Europe', 'Northern Ireland'),
(176, 'Saipan', 'mp', 'Oceania', 'Northern Mariana Islands'),
(177, 'Oslo', 'no', 'Europe', 'Norway'),
(178, 'Muscat', 'om', 'Asia', 'Oman'),
(179, 'Islamabad', 'pk', 'Asia', 'Pakistan'),
(180, 'Ngerulmud', 'pw', 'Oceania', 'Palau'),
(181, 'Panama City', 'pa', 'North America', 'Panama'),
(182, 'Port Moresby', 'pg', 'Oceania', 'Papua New Guinea'),
(183, 'Asunción', 'py', 'South America', 'Paraguay'),
(184, 'Lima', 'pe', 'South America', 'Peru'),
(185, 'Manila', 'ph', 'Asia', 'Philippines'),
(186, 'Adamstown', 'pn', 'Oceania', 'Pitcairn'),
(187, 'Warsaw', 'pl', 'Europe', 'Poland'),
(188, 'Lisbon', 'pt', 'Europe', 'Portugal'),
(189, 'San Juan', 'pr', 'North America', 'Puerto Rico'),
(190, 'Doha', 'qa', 'Asia', 'Qatar'),
(191, 'Brazzaville', 'cg', 'Africa', 'Republic of the Congo'),
(192, 'Bucharest', 'ro', 'Europe', 'Romania'),
(193, 'Moscow', 'ru', 'Europe', 'Russia'),
(194, 'Kigali', 'rw', 'Africa', 'Rwanda'),
(195, 'Saint-Denis', 're', 'Africa', 'Réunion'),
(196, 'Gustavia', 'bl', 'North America', 'Saint Barthélemy'),
(197, 'Jamestown', 'sh', 'Africa', 'Saint Helena, Ascension and Tristan da Cunha'),
(198, 'Basseterre', 'kn', 'North America', 'Saint Kitts and Nevis'),
(199, 'Castries', 'lc', 'North America', 'Saint Lucia'),
(200, 'Marigot', 'mf', 'North America', 'Saint Martin'),
(201, 'Saint-Pierre', 'pm', 'North America', 'Saint Pierre and Miquelon'),
(202, 'Kingstown', 'vc', 'North America', 'Saint Vincent and the Grenadines'),
(203, 'Apia', 'ws', 'Oceania', 'Samoa'),
(204, 'San Marino', 'sm', 'Europe', 'San Marino'),
(205, 'São Tomé', 'st', 'Africa', 'Sao Tome and Principe'),
(206, 'Riyadh', 'sa', 'Asia', 'Saudi Arabia'),
(207, 'Edinburgh', 'gb-sct', 'Europe', 'Scotland'),
(208, 'Dakar', 'sn', 'Africa', 'Senegal'),
(209, 'Belgrade', 'rs', 'Europe', 'Serbia'),
(210, 'Victoria', 'sc', 'Africa', 'Seychelles'),
(211, 'Freetown', 'sl', 'Africa', 'Sierra Leone'),
(212, 'Singapore', 'sg', 'Asia', 'Singapore'),
(213, 'Philipsburg', 'sx', 'North America', 'Sint Maarten'),
(214, 'Bratislava', 'sk', 'Europe', 'Slovakia'),
(215, 'Ljubljana', 'si', 'Europe', 'Slovenia'),
(216, 'Honiara', 'sb', 'Oceania', 'Solomon Islands'),
(217, 'Mogadishu', 'so', 'Africa', 'Somalia'),
(218, 'Pretoria', 'za', 'Africa', 'South Africa'),
(219, 'King Edward Point', 'gs', 'Antarctica', 'South Georgia and the South Sandwich Islands'),
(220, 'Seoul', 'kr', 'Asia', 'South Korea'),
(221, 'Juba', 'ss', 'Africa', 'South Sudan'),
(222, 'Madrid', 'es', 'Europe', 'Spain'),
(223, 'Sri Jayawardenepura Kotte, Colombo', 'lk', 'Asia', 'Sri Lanka'),
(224, 'Ramallah', 'ps', 'Asia', 'State of Palestine'),
(225, 'Khartoum', 'sd', 'Africa', 'Sudan'),
(226, 'Paramaribo', 'sr', 'South America', 'Suriname'),
(227, 'Longyearbyen', 'sj', 'Europe', 'Svalbard and Jan Mayen'),
(228, 'Stockholm', 'se', 'Europe', 'Sweden'),
(229, 'Bern', 'ch', 'Europe', 'Switzerland'),
(230, 'Damascus', 'sy', 'Asia', 'Syria'),
(231, 'Taipei', 'tw', 'Asia', 'Taiwan'),
(232, 'Dushanbe', 'tj', 'Asia', 'Tajikistan'),
(233, 'Dodoma', 'tz', 'Africa', 'Tanzania'),
(234, 'Bangkok', 'th', 'Asia', 'Thailand'),
(235, 'Dili', 'tl', 'Asia', 'Timor-Leste'),
(236, 'Lomé', 'tg', 'Africa', 'Togo'),
(237, 'Nukunonu, Atafu,Tokelau', 'tk', 'Oceania', 'Tokelau'),
(238, 'Nukuʻalofa', 'to', 'Oceania', 'Tonga'),
(239, 'Port of Spain', 'tt', 'South America', 'Trinidad and Tobago'),
(240, '', 'ta', '', 'Tristan da Cunha'),
(241, 'Tunis', 'tn', 'Africa', 'Tunisia'),
(242, 'Ankara', 'tr', 'Asia', 'Turkey'),
(243, 'Ashgabat', 'tm', 'Asia', 'Turkmenistan'),
(244, 'Cockburn Town', 'tc', 'North America', 'Turks and Caicos Islands'),
(245, 'Funafuti', 'tv', 'Oceania', 'Tuvalu'),
(246, 'Kampala', 'ug', 'Africa', 'Uganda'),
(247, 'Kiev', 'ua', 'Europe', 'Ukraine'),
(248, 'Abu Dhabi', 'ae', 'Asia', 'United Arab Emirates'),
(249, 'London', 'gb', 'Europe', 'United Kingdom'),
(250, '', 'un', '', 'United Nations'),
(251, 'Washington, D.C.', 'um', 'North America', 'United States Minor Outlying Islands'),
(252, 'Washington, D.C.', 'us', 'North America', 'United States of America'),
(253, '', 'xx', '', 'Unknown'),
(254, 'Montevideo', 'uy', 'South America', 'Uruguay'),
(255, 'Tashkent', 'uz', 'Asia', 'Uzbekistan'),
(256, 'Port Vila', 'vu', 'Oceania', 'Vanuatu'),
(257, 'Caracas', 've', 'South America', 'Venezuela'),
(258, 'Hanoi', 'vn', 'Asia', 'Vietnam'),
(259, 'Road Town', 'vg', 'North America', 'Virgin Islands (British)'),
(260, 'Charlotte Amalie', 'vi', 'North America', 'Virgin Islands (U.S.)'),
(261, 'Cardiff', 'gb-wls', 'Europe', 'Wales'),
(262, 'Mata-Utu', 'wf', 'Oceania', 'Wallis and Futuna'),
(263, 'Laayoune', 'eh', 'Africa', 'Western Sahara'),
(264, 'Sana\'a', 'ye', 'Asia', 'Yemen'),
(265, 'Lusaka', 'zm', 'Africa', 'Zambia'),
(266, 'Harare', 'zw', 'Africa', 'Zimbabwe');

-- --------------------------------------------------------

--
-- Table structure for table `flutterwave_payments`
--

CREATE TABLE `flutterwave_payments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `flutterwave_payment_id` varchar(191) DEFAULT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_status` enum('pending','completed','failed') NOT NULL DEFAULT 'pending',
  `payment_date` timestamp NULL DEFAULT NULL,
  `payment_error_response` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payment_error_response`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `front_details`
--

CREATE TABLE `front_details` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `language_setting_id` bigint(20) UNSIGNED DEFAULT NULL,
  `header_title` varchar(200) DEFAULT NULL,
  `header_description` text DEFAULT NULL,
  `image` varchar(200) DEFAULT NULL,
  `feature_with_image_heading` varchar(191) DEFAULT NULL,
  `review_heading` varchar(191) DEFAULT NULL,
  `feature_with_icon_heading` varchar(191) DEFAULT NULL,
  `comments_heading` varchar(191) DEFAULT NULL,
  `price_heading` varchar(191) DEFAULT NULL,
  `price_description` varchar(191) DEFAULT NULL,
  `faq_heading` varchar(191) DEFAULT NULL,
  `faq_description` text DEFAULT NULL,
  `contact_heading` text DEFAULT NULL,
  `footer_copyright_text` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `front_details`
--

INSERT INTO `front_details` (`id`, `language_setting_id`, `header_title`, `header_description`, `image`, `feature_with_image_heading`, `review_heading`, `feature_with_icon_heading`, `comments_heading`, `price_heading`, `price_description`, `faq_heading`, `faq_description`, `contact_heading`, `footer_copyright_text`, `created_at`, `updated_at`) VALUES
(1, 1, 'Restaurant POS software made simple!', 'Easily manage orders, menus, and tables in one place. Save time, reduce errors, and grow your business faster', NULL, 'Take Control of Your Restaurant', 'What Restaurant Owners Are Saying', 'Powerful Features Built to Elevate Your Restaurant Operations', NULL, 'Simple, Transparent Pricing', 'Get everything you need to manage your restaurant with one affordable plan.', 'Your questions, answered', 'Answers to the most frequently asked questions.', 'Contact', '© 2025 TableTrack. All Rights Reserved.', '2025-11-02 00:36:11', '2025-11-02 00:36:11');

-- --------------------------------------------------------

--
-- Table structure for table `front_faq_settings`
--

CREATE TABLE `front_faq_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `language_setting_id` bigint(20) UNSIGNED DEFAULT NULL,
  `question` text DEFAULT NULL,
  `answer` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `front_faq_settings`
--

INSERT INTO `front_faq_settings` (`id`, `language_setting_id`, `question`, `answer`, `created_at`, `updated_at`) VALUES
(1, 1, 'How can I contact customer support 1?', 'Our dedicated support team is available via email to assist you with any questions or technical issues.', NULL, NULL),
(2, 1, 'How can I contact customer support?', 'Our dedicated support team is available via email to assist you with any questions or technical issues.', NULL, NULL),
(3, 1, 'How can I contact customer support?', 'Our dedicated support team is available via email to assist you with any questions or technical issues.', NULL, NULL),
(4, 1, 'How can I contact customer support?', 'Our dedicated support team is available via email to assist you with any questions or technical issues.', NULL, NULL),
(5, 1, 'How can I contact customer support?', 'Our dedicated support team is available via email to assist you with any questions or technical issues.', NULL, NULL),
(6, 1, 'How can I contact customer support?', 'Our dedicated support team is available via email to assist you with any questions or technical issues.', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `front_features`
--

CREATE TABLE `front_features` (
  `id` int(10) UNSIGNED NOT NULL,
  `language_setting_id` bigint(20) UNSIGNED DEFAULT NULL,
  `title` varchar(191) NOT NULL,
  `description` longtext DEFAULT NULL,
  `image` longtext DEFAULT NULL,
  `icon` longtext DEFAULT NULL,
  `type` enum('image','icon','task','bills','team','apps') NOT NULL DEFAULT 'image',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `front_features`
--

INSERT INTO `front_features` (`id`, `language_setting_id`, `title`, `description`, `image`, `icon`, `type`, `created_at`, `updated_at`) VALUES
(1, 1, 'Streamline Order Management', 'Never lose track of an order again. All your customer orders—from dine-in to takeout—are organized and easily accessible in one place.\n                                Speed up service and keep your kitchen running smoothly.', NULL, NULL, 'image', NULL, NULL),
(2, 1, 'Optimize Table Reservations', 'Maximize seating efficiency with real-time table tracking and reservations. Reduce wait times and ensure no table sits empty during peak hours, improving customer experience and turnover.', NULL, NULL, 'image', NULL, NULL),
(3, 1, 'Effortless Menu Management', 'Easily add, edit, or remove items from your menu on the go. Highlight specials, update prices, and keep everything in sync across all platforms, so your staff and customers always see the latest offerings.', NULL, NULL, 'image', NULL, NULL),
(4, 1, 'QR Code Menu', 'Contactless Ordering Made Easy', '<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"16\" height=\"16\" fill=\"currentColor\"\n                            class=\"bi bi-qr-code-scan text-skin-base dark:text-skin-base size-6\" viewBox=\"0 0 16 16\">\n                            <path\n                                d=\"M0 .5A.5.5 0 0 1 .5 0h3a.5.5 0 0 1 0 1H1v2.5a.5.5 0 0 1-1 0zm12 0a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-1 0V1h-2.5a.5.5 0 0 1-.5-.5M.5 12a.5.5 0 0 1 .5.5V15h2.5a.5.5 0 0 1 0 1h-3a.5.5 0 0 1-.5-.5v-3a.5.5 0 0 1 .5-.5m15 0a.5.5 0 0 1 .5.5v3a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1 0-1H15v-2.5a.5.5 0 0 1 .5-.5M4 4h1v1H4z\" />\n                            <path d=\"M7 2H2v5h5zM3 3h3v3H3zm2 8H4v1h1z\" />\n                            <path d=\"M7 9H2v5h5zm-4 1h3v3H3zm8-6h1v1h-1z\" />\n                            <path\n                                d=\"M9 2h5v5H9zm1 1v3h3V3zM8 8v2h1v1H8v1h2v-2h1v2h1v-1h2v-1h-3V8zm2 2H9V9h1zm4 2h-1v1h-2v1h3zm-4 2v-1H8v1z\" />\n                            <path d=\"M12 9h2V8h-2z\" />\n                        </svg>', 'bi-qr-code', 'icon', NULL, NULL),
(5, 1, 'Payment Gateway Integration', 'Fast, Secure, and Flexible Payments using Stripe and Razorpay', '<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"16\" height=\"16\" fill=\"currentColor\"\n                        class=\"bi bi-qr-code-scan text-skin-base dark:text-skin-base size-6\" viewBox=\"0 0 16 16\">\n                        <path\n                            d=\"M2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2zm6.226 5.385c-.584 0-.937.164-.937.593 0 .468.607.674 1.36.93 1.228.415 2.844.963 2.851 2.993C11.5 11.868 9.924 13 7.63 13a7.7 7.7 0 0 1-3.009-.626V9.758c.926.506 2.095.88 3.01.88.617 0 1.058-.165 1.058-.671 0-.518-.658-.755-1.453-1.041C6.026 8.49 4.5 7.94 4.5 6.11 4.5 4.165 5.988 3 8.226 3a7.3 7.3 0 0 1 2.734.505v2.583c-.838-.45-1.896-.703-2.734-.703\" />\n                    </svg>', 'bi-credit-card', 'icon', NULL, NULL),
(6, 1, 'Staff Management', 'Separate login for every staff role with different permissions.', '<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"16\" height=\"16\" fill=\"currentColor\"\n                        class=\"bi bi-qr-code-scan text-skin-base dark:text-skin-base size-6\" viewBox=\"0 0 16 16\">\n                        <path\n                            d=\"M15 14s1 0 1-1-1-4-5-4-5 3-5 4 1 1 1 1zm-7.978-1L7 12.996c.001-.264.167-1.03.76-1.72C8.312 10.629 9.282 10 11 10c1.717 0 2.687.63 3.24 1.276.593.69.758 1.457.76 1.72l-.008.002-.014.002zM11 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4m3-2a3 3 0 1 1-6 0 3 3 0 0 1 6 0M6.936 9.28a6 6 0 0 0-1.23-.247A7 7 0 0 0 5 9c-4 0-5 3-5 4q0 1 1 1h4.216A2.24 2.24 0 0 1 5 13c0-1.01.377-2.042 1.09-2.904.243-.294.526-.569.846-.816M4.92 10A5.5 5.5 0 0 0 4 13H1c0-.26.164-1.03.76-1.724.545-.636 1.492-1.256 3.16-1.275ZM1.5 5.5a3 3 0 1 1 6 0 3 3 0 0 1-6 0m3-2a2 2 0 1 0 0 4 2 2 0 0 0 0-4\" />\n                    </svg>', 'bi-people', 'icon', NULL, NULL),
(7, 1, 'POS (Point of Sale)', 'Complete POS Integration', '<svg class=\"size-6 transition duration-75 text-skin-base dark:text-skin-base\" fill=\"currentColor\"\n                        viewBox=\"0 -0.5 25 25\" viewBox=\"0 0 24 24\" xmlns=\"http://www.w3.org/2000/svg\">\n                        <g id=\"SVGRepo_bgCarrier\" stroke-width=\"0\"></g>\n                        <g id=\"SVGRepo_tracerCarrier\" stroke-linecap=\"round\" stroke-linejoin=\"round\"></g>\n                        <g id=\"SVGRepo_iconCarrier\">\n                            <path fill-rule=\"evenodd\"\n                                d=\"M16,6 L20,6 C21.1045695,6 22,6.8954305 22,8 L22,16 C22,17.1045695 21.1045695,18 20,18 L16,18 L16,19.9411765 C16,21.0658573 15.1177541,22 14,22 L4,22 C2.88224586,22 2,21.0658573 2,19.9411765 L2,4.05882353 C2,2.93414267 2.88224586,2 4,2 L14,2 C15.1177541,2 16,2.93414267 16,4.05882353 L16,6 Z M20,11 L16,11 L16,16 L20,16 L20,11 Z M14,19.9411765 L14,4.05882353 C14,4.01396021 13.9868154,4 14,4 L4,4 C4.01318464,4 4,4.01396021 4,4.05882353 L4,19.9411765 C4,19.9860398 4.01318464,20 4,20 L14,20 C13.9868154,20 14,19.9860398 14,19.9411765 Z M5,19 L5,17 L7,17 L7,19 L5,19 Z M8,19 L8,17 L10,17 L10,19 L8,19 Z M11,19 L11,17 L13,17 L13,19 L11,19 Z M5,16 L5,14 L7,14 L7,16 L5,16 Z M8,16 L8,14 L10,14 L10,16 L8,16 Z M11,16 L11,14 L13,14 L13,16 L11,16 Z M13,5 L13,13 L5,13 L5,5 L13,5 Z M7,7 L7,11 L11,11 L11,7 L7,7 Z M20,9 L20,8 L16,8 L16,9 L20,9 Z\">\n                            </path>\n                        </g>\n                    </svg>', 'bi-pos', 'icon', NULL, NULL),
(8, 1, 'Custom Floor Plans', 'Design Your Restaurants Layout.', '<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"16\" height=\"16\" fill=\"currentColor\"\n                        class=\"bi bi-qr-code-scan text-skin-base dark:text-skin-base size-6\" viewBox=\"0 0 16 16\">\n                        <path\n                            d=\"M8.235 1.559a.5.5 0 0 0-.47 0l-7.5 4a.5.5 0 0 0 0 .882L3.188 8 .264 9.559a.5.5 0 0 0 0 .882l7.5 4a.5.5 0 0 0 .47 0l7.5-4a.5.5 0 0 0 0-.882L12.813 8l2.922-1.559a.5.5 0 0 0 0-.882zm3.515 7.008L14.438 10 8 13.433 1.562 10 4.25 8.567l3.515 1.874a.5.5 0 0 0 .47 0zM8 9.433 1.562 6 8 2.567 14.438 6z\" />\n                    </svg>', 'bi-grid-3x3-gap', 'icon', NULL, NULL),
(9, 1, 'Kitchen Order Tickets (KOT)', 'Efficient Kitchen Workflow.', '<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"16\" height=\"16\" fill=\"currentColor\"\n                        class=\"bi bi-qr-code-scan text-skin-base dark:text-skin-base size-6\" viewBox=\"0 0 16 16\">\n                        <path\n                            d=\"M3 4.5a.5.5 0 0 1 .5-.5h6a.5.5 0 1 1 0 1h-6a.5.5 0 0 1-.5-.5m0 2a.5.5 0 0 1 .5-.5h6a.5.5 0 1 1 0 1h-6a.5.5 0 0 1-.5-.5m0 2a.5.5 0 0 1 .5-.5h6a.5.5 0 1 1 0 1h-6a.5.5 0 0 1-.5-.5m0 2a.5.5 0 0 1 .5-.5h6a.5.5 0 0 1 0 1h-6a.5.5 0 0 1-.5-.5m0 2a.5.5 0 0 1 .5-.5h6a.5.5 0 0 1 0 1h-6a.5.5 0 0 1-.5-.5M11.5 4a.5.5 0 0 0 0 1h1a.5.5 0 0 0 0-1zm0 2a.5.5 0 0 0 0 1h1a.5.5 0 0 0 0-1zm0 2a.5.5 0 0 0 0 1h1a.5.5 0 0 0 0-1zm0 2a.5.5 0 0 0 0 1h1a.5.5 0 0 0 0-1zm0 2a.5.5 0 0 0 0 1h1a.5.5 0 0 0 0-1z\" />\n                        <path\n                            d=\"M2.354.646a.5.5 0 0 0-.801.13l-.5 1A.5.5 0 0 0 1 2v13H.5a.5.5 0 0 0 0 1h15a.5.5 0 0 0 0-1H15V2a.5.5 0 0 0-.053-.224l-.5-1a.5.5 0 0 0-.8-.13L13 1.293l-.646-.647a.5.5 0 0 0-.708 0L11 1.293l-.646-.647a.5.5 0 0 0-.708 0L9 1.293 8.354.646a.5.5 0 0 0-.708 0L7 1.293 6.354.646a.5.5 0 0 0-.708 0L5 1.293 4.354.646a.5.5 0 0 0-.708 0L3 1.293zm-.217 1.198.51.51a.5.5 0 0 0 .707 0L4 1.707l.646.647a.5.5 0 0 0 .708 0L6 1.707l.646.647a.5.5 0 0 0 .708 0L8 1.707l.646.647a.5.5 0 0 0 .708 0L10 1.707l.646.647a.5.5 0 0 0 .708 0L12 1.707l.646.647a.5.5 0 0 0 .708 0l.509-.51.137.274V15H2V2.118z\" />\n                    </svg>', 'bi-receipt', 'icon', NULL, NULL),
(10, 1, 'Bill Printing', 'Quick and Accurate Billing.', '<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"16\" height=\"16\" fill=\"currentColor\"\n                        class=\"bi bi-qr-code-scan text-skin-base dark:text-skin-base size-6\" viewBox=\"0 0 16 16\">\n                        <path d=\"M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1\" />\n                        <path\n                            d=\"M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1\" />\n                    </svg>', 'bi-printer', 'icon', NULL, NULL),
(11, 1, 'Reports', 'Data-Driven Decisions.', '<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"16\" height=\"16\" fill=\"currentColor\" class=\"bi bi-qr-code-scan text-skin-base dark:text-skin-base size-6\" viewBox=\"0 0 16 16\">\n                    <path fill-rule=\"evenodd\" d=\"M0 0h1v15h15v1H0zm10 3.5a.5.5 0 0 1 .5-.5h4a.5.5 0 0 1 .5.5v4a.5.5 0 0 1-1 0V4.9l-3.613 4.417a.5.5 0 0 1-.74.037L7.06 6.767l-3.656 5.027a.5.5 0 0 1-.808-.588l4-5.5a.5.5 0 0 1 .758-.06l2.609 2.61L13.445 4H10.5a.5.5 0 0 1-.5-.5\"></path>\n                    </svg>', 'bi-arrow-right-circle-fill', 'icon', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `front_review_settings`
--

CREATE TABLE `front_review_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `language_setting_id` bigint(20) UNSIGNED DEFAULT NULL,
  `reviews` text DEFAULT NULL,
  `reviewer_name` varchar(191) DEFAULT NULL,
  `reviewer_designation` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `front_review_settings`
--

INSERT INTO `front_review_settings` (`id`, `language_setting_id`, `reviews`, `reviewer_name`, `reviewer_designation`, `created_at`, `updated_at`) VALUES
(1, 1, '\" It has completely transformed how we operate. Managing orders, tables, and staff all from one platform has reduced our workload and made everything run more smoothly. \"', 'John Martin', 'Owner of Riverbend Bistro', NULL, NULL),
(2, 1, '\" The QR Code menu and payment integration have made a huge difference for us, especially after the pandemic. Customers love the ease, and we’ve seen faster table turnover.\"', 'Emily Thompson', 'Manager at Lakeside Grill', NULL, NULL),
(3, 1, '\" We are able to track every order in real time, keep our menu updated, and quickly manage payments. It is like having an extra set of hands in the restaurant.\"', 'Michael Scott', 'Owner of Downtown Eats', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `global_currencies`
--

CREATE TABLE `global_currencies` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `currency_name` varchar(191) NOT NULL,
  `currency_symbol` varchar(191) NOT NULL,
  `currency_code` varchar(191) NOT NULL,
  `exchange_rate` decimal(16,2) DEFAULT NULL,
  `usd_price` decimal(16,2) DEFAULT NULL,
  `is_cryptocurrency` enum('yes','no') NOT NULL DEFAULT 'no',
  `currency_position` enum('left','right','left_with_space','right_with_space') NOT NULL DEFAULT 'left',
  `no_of_decimal` int(10) UNSIGNED NOT NULL DEFAULT 2,
  `thousand_separator` varchar(191) DEFAULT NULL,
  `decimal_separator` varchar(191) DEFAULT NULL,
  `status` enum('enable','disable') NOT NULL DEFAULT 'enable',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `global_currencies`
--

INSERT INTO `global_currencies` (`id`, `currency_name`, `currency_symbol`, `currency_code`, `exchange_rate`, `usd_price`, `is_cryptocurrency`, `currency_position`, `no_of_decimal`, `thousand_separator`, `decimal_separator`, `status`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Dollars', '$', 'USD', NULL, NULL, 'no', 'left', 2, ',', '.', 'enable', '2025-11-02 00:35:57', '2025-11-02 00:35:57', NULL),
(2, 'Rupee', '₹', 'INR', NULL, NULL, 'no', 'left', 2, ',', '.', 'enable', '2025-11-02 00:35:57', '2025-11-02 00:35:57', NULL),
(3, 'Pounds', '£', 'GBP', NULL, NULL, 'no', 'left', 2, ',', '.', 'enable', '2025-11-02 00:35:57', '2025-11-02 00:35:57', NULL),
(4, 'Euros', '€', 'EUR', NULL, NULL, 'no', 'left', 2, ',', '.', 'enable', '2025-11-02 00:35:57', '2025-11-02 00:35:57', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `global_invoices`
--

CREATE TABLE `global_invoices` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `restaurant_id` bigint(20) UNSIGNED DEFAULT NULL,
  `currency_id` bigint(20) UNSIGNED DEFAULT NULL,
  `package_id` bigint(20) UNSIGNED DEFAULT NULL,
  `global_subscription_id` bigint(20) UNSIGNED DEFAULT NULL,
  `offline_method_id` bigint(20) UNSIGNED DEFAULT NULL,
  `signature` varchar(191) DEFAULT NULL,
  `token` varchar(191) DEFAULT NULL,
  `transaction_id` varchar(191) DEFAULT NULL,
  `reference_id` varchar(191) DEFAULT NULL,
  `event_id` varchar(191) DEFAULT NULL,
  `package_type` varchar(191) DEFAULT NULL,
  `sub_total` int(11) DEFAULT NULL,
  `total` int(11) DEFAULT NULL,
  `billing_frequency` varchar(191) DEFAULT NULL,
  `billing_interval` varchar(191) DEFAULT NULL,
  `recurring` enum('yes','no') DEFAULT NULL,
  `plan_id` varchar(191) DEFAULT NULL,
  `subscription_id` varchar(191) DEFAULT NULL,
  `invoice_id` varchar(191) DEFAULT NULL,
  `amount` decimal(16,2) DEFAULT NULL,
  `stripe_invoice_number` varchar(191) DEFAULT NULL,
  `pay_date` datetime DEFAULT NULL,
  `next_pay_date` datetime DEFAULT NULL,
  `gateway_name` varchar(191) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `m_payment_id` varchar(191) DEFAULT NULL,
  `pf_payment_id` varchar(191) DEFAULT NULL,
  `payfast_plan` varchar(191) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `global_invoices`
--

INSERT INTO `global_invoices` (`id`, `restaurant_id`, `currency_id`, `package_id`, `global_subscription_id`, `offline_method_id`, `signature`, `token`, `transaction_id`, `reference_id`, `event_id`, `package_type`, `sub_total`, `total`, `billing_frequency`, `billing_interval`, `recurring`, `plan_id`, `subscription_id`, `invoice_id`, `amount`, `stripe_invoice_number`, `pay_date`, `next_pay_date`, `gateway_name`, `status`, `created_at`, `updated_at`, `m_payment_id`, `pf_payment_id`, `payfast_plan`) VALUES
(1, 1, 1, 5, 1, NULL, NULL, NULL, 'SP5TAFQSPJ8MDCS', NULL, NULL, 'trial', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-02 06:06:11', '2025-12-02 06:06:11', 'offline', 'active', '2025-11-02 00:36:11', '2025-11-02 00:36:11', NULL, NULL, NULL),
(2, 1, 1, 3, 2, NULL, NULL, NULL, 'U0KW400DEW4X2IH', NULL, NULL, 'lifetime', NULL, 199, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-03 00:00:00', NULL, 'offline', NULL, '2025-11-02 23:48:55', '2025-11-02 23:48:55', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `global_settings`
--

CREATE TABLE `global_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `purchase_code` varchar(80) DEFAULT NULL,
  `supported_until` timestamp NULL DEFAULT NULL,
  `last_license_verified_at` timestamp NULL DEFAULT NULL,
  `email` varchar(191) DEFAULT NULL,
  `privacy_policy_link` varchar(191) DEFAULT NULL,
  `show_privacy_consent_checkbox` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `name` varchar(191) NOT NULL,
  `logo` varchar(191) DEFAULT NULL,
  `theme_hex` varchar(191) DEFAULT NULL,
  `theme_rgb` varchar(191) DEFAULT NULL,
  `locale` varchar(191) NOT NULL DEFAULT 'en',
  `license_type` varchar(191) DEFAULT NULL,
  `hide_cron_job` tinyint(1) NOT NULL DEFAULT 0,
  `last_cron_run` timestamp NULL DEFAULT NULL,
  `system_update` tinyint(1) NOT NULL DEFAULT 1,
  `purchased_on` timestamp NULL DEFAULT NULL,
  `timezone` varchar(191) DEFAULT 'Asia/Kolkata',
  `disable_landing_site` tinyint(1) NOT NULL DEFAULT 0,
  `landing_type` varchar(191) NOT NULL DEFAULT 'dynamic',
  `landing_site_type` enum('theme','custom') NOT NULL DEFAULT 'theme',
  `landing_site_url` varchar(191) DEFAULT NULL,
  `installed_url` tinytext DEFAULT NULL,
  `requires_approval_after_signup` tinyint(1) NOT NULL DEFAULT 0,
  `facebook_link` varchar(255) DEFAULT NULL,
  `instagram_link` varchar(255) DEFAULT NULL,
  `twitter_link` varchar(255) DEFAULT NULL,
  `yelp_link` varchar(255) DEFAULT NULL,
  `default_currency_id` bigint(20) UNSIGNED DEFAULT NULL,
  `show_logo_text` tinyint(1) NOT NULL DEFAULT 1,
  `meta_title` varchar(191) DEFAULT NULL,
  `meta_keyword` text DEFAULT NULL,
  `meta_description` longtext DEFAULT NULL,
  `upload_fav_icon_android_chrome_192` varchar(191) DEFAULT NULL,
  `upload_fav_icon_android_chrome_512` varchar(191) DEFAULT NULL,
  `upload_fav_icon_apple_touch_icon` varchar(191) DEFAULT NULL,
  `upload_favicon_16` varchar(191) DEFAULT NULL,
  `upload_favicon_32` varchar(191) DEFAULT NULL,
  `favicon` varchar(191) DEFAULT NULL,
  `hash` varchar(191) DEFAULT NULL,
  `webmanifest` varchar(191) DEFAULT NULL,
  `is_pwa_install_alert_show` varchar(191) NOT NULL DEFAULT '0',
  `google_map_api_key` varchar(191) DEFAULT NULL,
  `session_driver` enum('file','database') NOT NULL DEFAULT 'database',
  `enable_stripe` tinyint(1) NOT NULL DEFAULT 1,
  `enable_razorpay` tinyint(1) NOT NULL DEFAULT 1,
  `enable_flutterwave` tinyint(1) NOT NULL DEFAULT 1,
  `enable_payfast` tinyint(1) NOT NULL DEFAULT 1,
  `enable_paypal` tinyint(1) NOT NULL DEFAULT 1,
  `enable_paystack` tinyint(1) NOT NULL DEFAULT 1,
  `enable_xendit` tinyint(1) NOT NULL DEFAULT 1,
  `enable_paddle` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `global_settings`
--

INSERT INTO `global_settings` (`id`, `purchase_code`, `supported_until`, `last_license_verified_at`, `email`, `privacy_policy_link`, `show_privacy_consent_checkbox`, `created_at`, `updated_at`, `name`, `logo`, `theme_hex`, `theme_rgb`, `locale`, `license_type`, `hide_cron_job`, `last_cron_run`, `system_update`, `purchased_on`, `timezone`, `disable_landing_site`, `landing_type`, `landing_site_type`, `landing_site_url`, `installed_url`, `requires_approval_after_signup`, `facebook_link`, `instagram_link`, `twitter_link`, `yelp_link`, `default_currency_id`, `show_logo_text`, `meta_title`, `meta_keyword`, `meta_description`, `upload_fav_icon_android_chrome_192`, `upload_fav_icon_android_chrome_512`, `upload_fav_icon_apple_touch_icon`, `upload_favicon_16`, `upload_favicon_32`, `favicon`, `hash`, `webmanifest`, `is_pwa_install_alert_show`, `google_map_api_key`, `session_driver`, `enable_stripe`, `enable_razorpay`, `enable_flutterwave`, `enable_payfast`, `enable_paypal`, `enable_paystack`, `enable_xendit`, `enable_paddle`) VALUES
(1, NULL, NULL, NULL, NULL, NULL, 0, '2025-11-02 00:36:10', '2025-11-04 01:10:14', 'Genx Rest', '8505e6c5f35f411db1a4180a5c0ad724.png', '#2563EB', '37, 99, 235', 'en', NULL, 1, '2025-11-02 00:37:32', 1, NULL, 'Asia/Colombo', 0, 'dynamic', 'theme', NULL, 'http://localhost', 1, 'https://www.facebook.com/', 'https://www.instagram.com/', 'https://www.twitter.com/', NULL, 1, 1, NULL, NULL, NULL, 'android-chrome-192x192.png', 'android-chrome-512x512.png', 'apple-touch-icon.png', 'favicon-16x16.png', 'favicon-32x32.png', 'favicon.ico', '8f3b534c390054c6f7463351c4cb3bb9', NULL, '1', NULL, 'database', 1, 1, 1, 1, 1, 1, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `global_subscriptions`
--

CREATE TABLE `global_subscriptions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `restaurant_id` bigint(20) UNSIGNED DEFAULT NULL,
  `package_id` bigint(20) UNSIGNED DEFAULT NULL,
  `currency_id` bigint(20) UNSIGNED DEFAULT NULL,
  `package_type` varchar(191) DEFAULT NULL,
  `plan_type` varchar(191) DEFAULT NULL,
  `transaction_id` varchar(191) DEFAULT NULL,
  `name` varchar(191) DEFAULT NULL,
  `user_id` varchar(191) DEFAULT NULL,
  `quantity` varchar(191) DEFAULT NULL,
  `token` varchar(191) DEFAULT NULL,
  `razorpay_id` varchar(191) DEFAULT NULL,
  `razorpay_plan` varchar(191) DEFAULT NULL,
  `stripe_id` varchar(191) DEFAULT NULL,
  `stripe_status` varchar(191) DEFAULT NULL,
  `stripe_price` varchar(191) DEFAULT NULL,
  `gateway_name` varchar(191) DEFAULT NULL,
  `trial_ends_at` varchar(191) DEFAULT NULL,
  `subscription_status` enum('active','inactive') DEFAULT NULL,
  `ends_at` datetime DEFAULT NULL,
  `subscribed_on_date` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `subscription_id` varchar(191) DEFAULT NULL,
  `customer_id` varchar(191) DEFAULT NULL,
  `flutterwave_id` varchar(191) DEFAULT NULL,
  `flutterwave_payment_ref` varchar(191) DEFAULT NULL,
  `flutterwave_status` varchar(191) DEFAULT NULL,
  `flutterwave_customer_id` varchar(191) DEFAULT NULL,
  `payfast_plan` varchar(191) DEFAULT NULL,
  `payfast_status` varchar(191) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `global_subscriptions`
--

INSERT INTO `global_subscriptions` (`id`, `restaurant_id`, `package_id`, `currency_id`, `package_type`, `plan_type`, `transaction_id`, `name`, `user_id`, `quantity`, `token`, `razorpay_id`, `razorpay_plan`, `stripe_id`, `stripe_status`, `stripe_price`, `gateway_name`, `trial_ends_at`, `subscription_status`, `ends_at`, `subscribed_on_date`, `created_at`, `updated_at`, `subscription_id`, `customer_id`, `flutterwave_id`, `flutterwave_payment_ref`, `flutterwave_status`, `flutterwave_customer_id`, `payfast_plan`, `payfast_status`) VALUES
(1, 1, 5, 1, 'trial', NULL, 'SP5TAFQSPJ8MDCS', NULL, NULL, '1', NULL, NULL, NULL, NULL, NULL, NULL, 'offline', '2025-12-02 06:06:11', 'inactive', '2025-12-02 06:06:11', '2025-11-02 06:06:11', '2025-11-02 00:36:11', '2025-11-02 23:48:55', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 1, 3, 1, 'lifetime', NULL, 'U0KW400DEW4X2IH', NULL, NULL, '1', NULL, NULL, NULL, NULL, NULL, NULL, 'offline', NULL, 'active', NULL, '2025-11-03 00:00:00', '2025-11-02 23:48:55', '2025-11-02 23:48:55', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `inventory_global_settings`
--

CREATE TABLE `inventory_global_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `license_type` varchar(20) DEFAULT NULL,
  `purchase_code` varchar(191) DEFAULT NULL,
  `purchased_on` timestamp NULL DEFAULT NULL,
  `supported_until` timestamp NULL DEFAULT NULL,
  `notify_update` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inventory_global_settings`
--

INSERT INTO `inventory_global_settings` (`id`, `license_type`, `purchase_code`, `purchased_on`, `supported_until`, `notify_update`, `created_at`, `updated_at`) VALUES
(1, NULL, NULL, NULL, NULL, 1, '2025-11-02 22:52:28', '2025-11-02 22:52:28');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_items`
--

CREATE TABLE `inventory_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `branch_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `inventory_item_category_id` bigint(20) UNSIGNED NOT NULL,
  `unit_id` bigint(20) UNSIGNED NOT NULL,
  `threshold_quantity` decimal(16,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `preferred_supplier_id` bigint(20) UNSIGNED DEFAULT NULL,
  `reorder_quantity` decimal(16,2) NOT NULL DEFAULT 0.00,
  `unit_purchase_price` decimal(16,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_item_categories`
--

CREATE TABLE `inventory_item_categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `branch_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inventory_item_categories`
--

INSERT INTO `inventory_item_categories` (`id`, `branch_id`, `name`, `created_at`, `updated_at`) VALUES
(1, 1, 'Meat & Poultry', '2025-11-02 22:52:30', '2025-11-02 22:52:30'),
(2, 1, 'Seafood', '2025-11-02 22:52:30', '2025-11-02 22:52:30'),
(3, 1, 'Dairy & Eggs', '2025-11-02 22:52:30', '2025-11-02 22:52:30'),
(4, 1, 'Fresh Produce', '2025-11-02 22:52:30', '2025-11-02 22:52:30'),
(5, 1, 'Herbs & Spices', '2025-11-02 22:52:30', '2025-11-02 22:52:30'),
(6, 1, 'Dry Goods', '2025-11-02 22:52:30', '2025-11-02 22:52:30'),
(7, 1, 'Canned Goods', '2025-11-02 22:52:30', '2025-11-02 22:52:30'),
(8, 1, 'Beverages', '2025-11-02 22:52:30', '2025-11-02 22:52:30'),
(9, 1, 'Condiments & Sauces', '2025-11-02 22:52:30', '2025-11-02 22:52:30'),
(10, 1, 'Baking Supplies', '2025-11-02 22:52:30', '2025-11-02 22:52:30'),
(11, 1, 'Oils & Vinegars', '2025-11-02 22:52:30', '2025-11-02 22:52:30'),
(12, 1, 'Frozen Foods', '2025-11-02 22:52:30', '2025-11-02 22:52:30'),
(13, 1, 'Cleaning Supplies', '2025-11-02 22:52:30', '2025-11-02 22:52:30'),
(14, 1, 'Kitchen Equipment', '2025-11-02 22:52:30', '2025-11-02 22:52:30'),
(15, 1, 'Disposables', '2025-11-02 22:52:30', '2025-11-02 22:52:30'),
(16, 2, 'Meat & Poultry', '2025-11-02 22:52:30', '2025-11-02 22:52:30'),
(17, 2, 'Seafood', '2025-11-02 22:52:30', '2025-11-02 22:52:30'),
(18, 2, 'Dairy & Eggs', '2025-11-02 22:52:30', '2025-11-02 22:52:30'),
(19, 2, 'Fresh Produce', '2025-11-02 22:52:30', '2025-11-02 22:52:30'),
(20, 2, 'Herbs & Spices', '2025-11-02 22:52:30', '2025-11-02 22:52:30'),
(21, 2, 'Dry Goods', '2025-11-02 22:52:30', '2025-11-02 22:52:30'),
(22, 2, 'Canned Goods', '2025-11-02 22:52:30', '2025-11-02 22:52:30'),
(23, 2, 'Beverages', '2025-11-02 22:52:30', '2025-11-02 22:52:30'),
(24, 2, 'Condiments & Sauces', '2025-11-02 22:52:30', '2025-11-02 22:52:30'),
(25, 2, 'Baking Supplies', '2025-11-02 22:52:30', '2025-11-02 22:52:30'),
(26, 2, 'Oils & Vinegars', '2025-11-02 22:52:30', '2025-11-02 22:52:30'),
(27, 2, 'Frozen Foods', '2025-11-02 22:52:30', '2025-11-02 22:52:30'),
(28, 2, 'Cleaning Supplies', '2025-11-02 22:52:30', '2025-11-02 22:52:30'),
(29, 2, 'Kitchen Equipment', '2025-11-02 22:52:30', '2025-11-02 22:52:30'),
(30, 2, 'Disposables', '2025-11-02 22:52:30', '2025-11-02 22:52:30');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_movements`
--

CREATE TABLE `inventory_movements` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `branch_id` bigint(20) UNSIGNED NOT NULL,
  `inventory_item_id` bigint(20) UNSIGNED NOT NULL,
  `quantity` decimal(16,2) NOT NULL DEFAULT 0.00,
  `transaction_type` enum('in','out','waste','transfer') NOT NULL DEFAULT 'in',
  `waste_reason` enum('expiry','spoilage','customer_complaint','over_preparation','other') DEFAULT NULL,
  `added_by` bigint(20) UNSIGNED DEFAULT NULL,
  `supplier_id` bigint(20) UNSIGNED DEFAULT NULL,
  `transfer_branch_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `unit_purchase_price` decimal(16,2) NOT NULL DEFAULT 0.00,
  `expiration_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_settings`
--

CREATE TABLE `inventory_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `restaurant_id` bigint(20) UNSIGNED NOT NULL,
  `allow_auto_purchase` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inventory_settings`
--

INSERT INTO `inventory_settings` (`id`, `restaurant_id`, `allow_auto_purchase`, `created_at`, `updated_at`) VALUES
(1, 1, 0, '2025-11-02 22:52:30', '2025-11-02 22:52:30'),
(2, 1, 0, '2025-11-02 22:52:32', '2025-11-02 22:52:32'),
(3, 1, 0, '2025-11-02 23:56:27', '2025-11-02 23:56:27');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_stocks`
--

CREATE TABLE `inventory_stocks` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `branch_id` bigint(20) UNSIGNED NOT NULL,
  `inventory_item_id` bigint(20) UNSIGNED NOT NULL,
  `quantity` decimal(16,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `item_categories`
--

CREATE TABLE `item_categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `branch_id` bigint(20) UNSIGNED DEFAULT NULL,
  `category_name` text DEFAULT NULL,
  `sort_order` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `item_categories`
--

INSERT INTO `item_categories` (`id`, `branch_id`, `category_name`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 1, '{\"en\":\"Desserts\"}', 0, '2025-11-02 05:23:32', '2025-11-02 05:23:32'),
(2, 2, '{\"en\":\"Food\"}', 0, '2025-11-08 02:20:14', '2025-11-08 02:20:14');

-- --------------------------------------------------------

--
-- Table structure for table `item_modifiers`
--

CREATE TABLE `item_modifiers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `menu_item_id` bigint(20) UNSIGNED DEFAULT NULL,
  `menu_item_variation_id` bigint(20) UNSIGNED DEFAULT NULL,
  `modifier_group_id` bigint(20) UNSIGNED DEFAULT NULL,
  `is_required` tinyint(1) NOT NULL DEFAULT 0,
  `allow_multiple_selection` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(191) NOT NULL,
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
  `id` varchar(191) NOT NULL,
  `name` varchar(191) NOT NULL,
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
-- Table structure for table `kiosks`
--

CREATE TABLE `kiosks` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `branch_id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(64) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_seen_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kiosk_ads`
--

CREATE TABLE `kiosk_ads` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `heading` varchar(191) NOT NULL,
  `description` varchar(191) NOT NULL,
  `image` varchar(191) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kiosk_global_settings`
--

CREATE TABLE `kiosk_global_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `license_type` varchar(20) DEFAULT NULL,
  `purchase_code` varchar(191) DEFAULT NULL,
  `purchased_on` timestamp NULL DEFAULT NULL,
  `supported_until` timestamp NULL DEFAULT NULL,
  `notify_update` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `kiosk_global_settings`
--

INSERT INTO `kiosk_global_settings` (`id`, `license_type`, `purchase_code`, `purchased_on`, `supported_until`, `notify_update`, `created_at`, `updated_at`) VALUES
(1, NULL, NULL, NULL, NULL, 1, '2025-11-03 02:37:44', '2025-11-03 02:37:44');

-- --------------------------------------------------------

--
-- Table structure for table `kitchen_global_settings`
--

CREATE TABLE `kitchen_global_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `license_type` varchar(20) DEFAULT NULL,
  `purchase_code` varchar(191) DEFAULT NULL,
  `purchased_on` timestamp NULL DEFAULT NULL,
  `supported_until` timestamp NULL DEFAULT NULL,
  `notify_update` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `kitchen_global_settings`
--

INSERT INTO `kitchen_global_settings` (`id`, `license_type`, `purchase_code`, `purchased_on`, `supported_until`, `notify_update`, `created_at`, `updated_at`) VALUES
(1, NULL, NULL, NULL, NULL, 1, '2025-11-04 02:05:18', '2025-11-04 02:05:18');

-- --------------------------------------------------------

--
-- Table structure for table `kots`
--

CREATE TABLE `kots` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `branch_id` bigint(20) UNSIGNED DEFAULT NULL,
  `kitchen_place_id` bigint(20) UNSIGNED DEFAULT NULL,
  `kot_number` varchar(191) NOT NULL,
  `token_number` int(10) UNSIGNED DEFAULT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `order_type_id` bigint(20) UNSIGNED DEFAULT NULL,
  `transaction_id` varchar(191) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `status` enum('pending_confirmation','in_kitchen','food_ready','served','cancelled') NOT NULL DEFAULT 'in_kitchen',
  `cancel_reason_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cancel_reason_text` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `kots`
--

INSERT INTO `kots` (`id`, `branch_id`, `kitchen_place_id`, `kot_number`, `token_number`, `order_id`, `order_type_id`, `transaction_id`, `note`, `status`, `cancel_reason_id`, `cancel_reason_text`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, '2', NULL, 1, 1, NULL, NULL, 'in_kitchen', NULL, NULL, '2025-11-02 23:26:07', '2025-11-04 02:08:32'),
(2, 1, NULL, '4', NULL, 2, 1, 'TXN_690866325fe2d8.50372376_636337', NULL, 'in_kitchen', NULL, NULL, '2025-11-03 02:52:10', '2025-11-03 02:53:33'),
(3, 1, NULL, '6', NULL, 3, 1, 'TXN_6908689cf0a376.36200995_513349', NULL, 'food_ready', NULL, NULL, '2025-11-03 03:02:29', '2025-11-04 02:09:05'),
(4, 1, NULL, '8', NULL, 6, 3, NULL, NULL, 'food_ready', NULL, NULL, '2025-11-04 00:00:19', '2025-11-04 02:09:00'),
(5, 1, NULL, '10', NULL, 7, 1, NULL, NULL, 'served', NULL, NULL, '2025-11-04 00:08:47', '2025-11-04 02:08:51'),
(6, 2, 2, '1', NULL, 8, 4, NULL, NULL, 'served', NULL, NULL, '2025-11-08 02:26:13', '2025-11-08 04:46:57'),
(7, 2, 2, '2', NULL, 16, 4, NULL, NULL, 'served', NULL, NULL, '2025-11-08 04:48:45', '2025-11-08 04:58:27'),
(8, 1, 1, '11', NULL, 17, 1, NULL, NULL, 'pending_confirmation', NULL, NULL, '2025-11-08 22:32:20', '2025-11-08 22:32:20'),
(9, 1, 1, '12', NULL, 19, 1, NULL, NULL, 'served', NULL, NULL, '2025-11-10 03:51:10', '2025-11-10 09:15:13'),
(10, 1, 1, '13', NULL, 20, 1, NULL, NULL, 'served', NULL, NULL, '2025-11-10 09:14:48', '2025-11-10 09:19:15'),
(11, 1, 1, '14', NULL, 21, 1, NULL, NULL, 'served', NULL, NULL, '2025-11-10 09:16:26', '2025-11-10 09:19:06'),
(12, 1, 1, '15', NULL, 22, 1, NULL, NULL, 'served', NULL, NULL, '2025-11-10 09:17:04', '2025-11-10 09:18:58'),
(13, 1, 1, '16', NULL, 23, 1, NULL, NULL, 'pending_confirmation', NULL, NULL, '2025-11-10 10:13:40', '2025-11-10 10:13:40'),
(14, 1, 1, '17', NULL, 24, 1, NULL, NULL, 'in_kitchen', NULL, NULL, '2025-11-10 22:47:37', '2025-11-11 04:52:10'),
(15, 1, 1, '18', NULL, 25, 1, NULL, NULL, 'in_kitchen', NULL, NULL, '2025-11-11 01:04:44', '2025-11-11 04:52:16');

-- --------------------------------------------------------

--
-- Table structure for table `kot_cancel_reasons`
--

CREATE TABLE `kot_cancel_reasons` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `restaurant_id` bigint(20) UNSIGNED DEFAULT NULL,
  `reason` varchar(191) NOT NULL,
  `cancel_order` tinyint(1) NOT NULL DEFAULT 0,
  `cancel_kot` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `kot_cancel_reasons`
--

INSERT INTO `kot_cancel_reasons` (`id`, `restaurant_id`, `reason`, `cancel_order`, `cancel_kot`, `created_at`, `updated_at`) VALUES
(1, 1, 'Customer changed their mind', 1, 0, '2025-11-02 00:36:12', '2025-11-02 00:36:12'),
(2, 1, 'Customer requested to cancel', 1, 0, '2025-11-02 00:36:12', '2025-11-02 00:36:12'),
(3, 1, 'Payment issues', 1, 0, '2025-11-02 00:36:12', '2025-11-02 00:36:12'),
(4, 1, 'Customer no longer wants the order', 1, 0, '2025-11-02 00:36:12', '2025-11-02 00:36:12'),
(5, 1, 'Ingredient not available', 0, 1, '2025-11-02 00:36:12', '2025-11-02 00:36:12'),
(6, 1, 'Preparation time too long', 0, 1, '2025-11-02 00:36:12', '2025-11-02 00:36:12'),
(7, 1, 'Quality issue with ingredients', 0, 1, '2025-11-02 00:36:12', '2025-11-02 00:36:12'),
(8, 1, 'System error/Technical issue', 1, 1, '2025-11-02 00:36:12', '2025-11-02 00:36:12'),
(9, 1, 'Restaurant closing early', 1, 1, '2025-11-02 00:36:12', '2025-11-02 00:36:12'),
(10, 1, 'Other', 1, 1, '2025-11-02 00:36:12', '2025-11-02 00:36:12');

-- --------------------------------------------------------

--
-- Table structure for table `kot_items`
--

CREATE TABLE `kot_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `kot_id` bigint(20) UNSIGNED NOT NULL,
  `transaction_id` varchar(191) DEFAULT NULL,
  `menu_item_id` bigint(20) UNSIGNED NOT NULL,
  `menu_item_variation_id` bigint(20) UNSIGNED DEFAULT NULL,
  `note` text DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `status` enum('pending','cooking','ready') DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `kot_items`
--

INSERT INTO `kot_items` (`id`, `kot_id`, `transaction_id`, `menu_item_id`, `menu_item_variation_id`, `note`, `quantity`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, 1, NULL, '', 1, 'cooking', '2025-11-02 23:26:07', '2025-11-04 02:08:32'),
(2, 2, 'TXN_690866325fe2d8.50372376_636337', 1, NULL, '', 1, NULL, '2025-11-03 02:52:10', '2025-11-03 02:52:10'),
(3, 3, 'TXN_6908689cf0a376.36200995_513349', 1, NULL, '', 1, 'ready', '2025-11-03 03:02:29', '2025-11-04 02:09:05'),
(4, 4, NULL, 1, NULL, '', 2, 'ready', '2025-11-04 00:00:19', '2025-11-04 02:09:00'),
(5, 5, NULL, 1, NULL, '', 1, 'ready', '2025-11-04 00:08:47', '2025-11-04 02:08:23'),
(6, 6, NULL, 2, NULL, '', 15, 'ready', '2025-11-08 02:26:13', '2025-11-08 04:44:57'),
(7, 7, NULL, 3, NULL, '', 1, 'ready', '2025-11-08 04:48:45', '2025-11-08 04:58:04'),
(8, 7, NULL, 2, NULL, '', 1, 'ready', '2025-11-08 04:48:45', '2025-11-08 04:58:04'),
(9, 8, NULL, 1, NULL, '', 2, NULL, '2025-11-08 22:32:20', '2025-11-08 22:32:20'),
(10, 9, NULL, 1, NULL, '', 3, 'ready', '2025-11-10 03:51:10', '2025-11-10 03:52:22'),
(11, 10, NULL, 1, NULL, '', 1, 'ready', '2025-11-10 09:14:48', '2025-11-10 09:15:54'),
(12, 11, NULL, 1, NULL, '', 2, 'ready', '2025-11-10 09:16:26', '2025-11-10 09:17:48'),
(13, 12, NULL, 1, NULL, '', 1, 'ready', '2025-11-10 09:17:04', '2025-11-10 09:17:54'),
(14, 13, NULL, 1, NULL, '', 4, NULL, '2025-11-10 10:13:40', '2025-11-10 10:13:40'),
(15, 14, NULL, 1, NULL, '', 1, 'cooking', '2025-11-10 22:47:37', '2025-11-11 04:52:10'),
(16, 15, NULL, 1, NULL, '', 2, 'cooking', '2025-11-11 01:04:44', '2025-11-11 04:52:16');

-- --------------------------------------------------------

--
-- Table structure for table `kot_item_modifier_options`
--

CREATE TABLE `kot_item_modifier_options` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `kot_item_id` bigint(20) UNSIGNED NOT NULL,
  `modifier_option_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kot_places`
--

CREATE TABLE `kot_places` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `printer_id` bigint(20) UNSIGNED DEFAULT NULL,
  `branch_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(191) NOT NULL,
  `type` varchar(191) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `kot_places`
--

INSERT INTO `kot_places` (`id`, `printer_id`, `branch_id`, `name`, `type`, `is_active`, `is_default`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Default Kitchen', 'food', 1, 1, '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(2, 2, 2, 'Default Kitchen', 'food', 1, 1, '2025-11-02 00:36:11', '2025-11-02 00:36:11');

-- --------------------------------------------------------

--
-- Table structure for table `kot_settings`
--

CREATE TABLE `kot_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `branch_id` bigint(20) UNSIGNED NOT NULL,
  `default_status` enum('pending','cooking') NOT NULL DEFAULT 'pending',
  `enable_item_level_status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `kot_settings`
--

INSERT INTO `kot_settings` (`id`, `branch_id`, `default_status`, `enable_item_level_status`, `created_at`, `updated_at`) VALUES
(1, 1, 'pending', 1, '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(2, 1, 'pending', 1, '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(3, 2, 'pending', 1, '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(4, 2, 'pending', 1, '2025-11-02 00:36:11', '2025-11-02 00:36:11');

-- --------------------------------------------------------

--
-- Table structure for table `language_settings`
--

CREATE TABLE `language_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `language_code` varchar(191) NOT NULL,
  `language_name` varchar(191) NOT NULL,
  `flag_code` varchar(191) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `is_rtl` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `language_settings`
--

INSERT INTO `language_settings` (`id`, `language_code`, `language_name`, `flag_code`, `active`, `is_rtl`, `created_at`, `updated_at`) VALUES
(1, 'en', 'English', 'gb', 1, 0, NULL, NULL),
(2, 'ar', 'Arabic', 'sa', 0, 1, NULL, NULL),
(3, 'de', 'German', 'de', 0, 0, NULL, NULL),
(4, 'es', 'Spanish', 'es', 0, 0, NULL, NULL),
(5, 'et', 'Estonian', 'et', 0, 0, NULL, NULL),
(6, 'fa', 'Farsi', 'ir', 0, 1, NULL, NULL),
(7, 'fr', 'French', 'fr', 0, 0, NULL, NULL),
(8, 'el', 'Greek', 'gr', 0, 0, NULL, NULL),
(9, 'it', 'Italian', 'it', 0, 0, NULL, NULL),
(10, 'nl', 'Dutch', 'nl', 0, 0, NULL, NULL),
(11, 'pl', 'Polish', 'pl', 0, 0, NULL, NULL),
(12, 'pt', 'Portuguese', 'pt', 0, 0, NULL, NULL),
(13, 'pt-br', 'Portuguese (Brazil)', 'br', 0, 0, NULL, NULL),
(14, 'ro', 'Romanian', 'ro', 0, 0, NULL, NULL),
(15, 'ru', 'Russian', 'ru', 0, 0, NULL, NULL),
(16, 'tr', 'Turkish', 'tr', 0, 0, NULL, NULL),
(17, 'zh-CN', 'Chinese (S)', 'cn', 0, 0, NULL, NULL),
(18, 'zh-TW', 'Chinese (T)', 'cn', 0, 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `ltm_translations`
--

CREATE TABLE `ltm_translations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `status` int(11) NOT NULL DEFAULT 0,
  `locale` varchar(191) NOT NULL,
  `group` varchar(191) NOT NULL,
  `key` text NOT NULL,
  `value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- --------------------------------------------------------

--
-- Table structure for table `menus`
--

CREATE TABLE `menus` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `branch_id` bigint(20) UNSIGNED DEFAULT NULL,
  `menu_name` text DEFAULT NULL,
  `sort_order` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `menus`
--

INSERT INTO `menus` (`id`, `branch_id`, `menu_name`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 1, '{\"en\":\"Lunch\"}', 0, '2025-11-02 05:27:31', '2025-11-02 05:27:31'),
(2, 1, '{\"en\":\"Lunch\"}', 0, '2025-11-02 23:24:25', '2025-11-02 23:24:25'),
(3, 2, '{\"en\":\"Breakfast\"}', 0, '2025-11-08 02:14:24', '2025-11-08 02:14:24');

-- --------------------------------------------------------

--
-- Table structure for table `menu_items`
--

CREATE TABLE `menu_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `branch_id` bigint(20) UNSIGNED DEFAULT NULL,
  `kot_place_id` bigint(20) UNSIGNED DEFAULT NULL,
  `item_name` varchar(191) NOT NULL,
  `image` varchar(191) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `type` enum('veg','non-veg','egg','drink','other','halal') DEFAULT NULL,
  `price` decimal(16,2) DEFAULT NULL,
  `menu_id` bigint(20) UNSIGNED NOT NULL,
  `item_category_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `preparation_time` int(11) DEFAULT NULL,
  `is_available` tinyint(1) NOT NULL DEFAULT 1,
  `show_on_customer_site` tinyint(1) NOT NULL DEFAULT 1,
  `in_stock` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `tax_inclusive` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `menu_items`
--

INSERT INTO `menu_items` (`id`, `branch_id`, `kot_place_id`, `item_name`, `image`, `description`, `type`, `price`, `menu_id`, `item_category_id`, `created_at`, `updated_at`, `preparation_time`, `is_available`, `show_on_customer_site`, `in_stock`, `sort_order`, `tax_inclusive`) VALUES
(1, 1, 1, 'Noodles', NULL, '', 'veg', 2.00, 1, 1, '2025-11-02 23:25:42', '2025-11-03 23:58:25', 10, 1, 1, 1, 0, 0),
(2, 2, 2, 'Paratta', 'df7e8585202f3dad2eb392807ac581d0.jpg', '', 'halal', 50.00, 3, 2, '2025-11-08 02:24:03', '2025-11-08 02:24:04', 3, 1, 1, 1, 0, 0),
(3, 2, 2, 'Fried Rice', '695db5003bf32d68cf2a21fb8f4b73cf.jpg', '', 'non-veg', 900.00, 3, 2, '2025-11-08 04:20:56', '2025-11-08 04:20:56', 10, 1, 1, 1, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `menu_item_prices`
--

CREATE TABLE `menu_item_prices` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `menu_item_id` bigint(20) UNSIGNED NOT NULL,
  `order_type_id` bigint(20) UNSIGNED NOT NULL,
  `delivery_app_id` bigint(20) UNSIGNED DEFAULT NULL,
  `menu_item_variation_id` bigint(20) UNSIGNED DEFAULT NULL,
  `calculated_price` decimal(16,2) NOT NULL,
  `override_price` decimal(16,2) DEFAULT NULL,
  `final_price` decimal(16,2) NOT NULL DEFAULT 0.00,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `menu_item_prices`
--

INSERT INTO `menu_item_prices` (`id`, `menu_item_id`, `order_type_id`, `delivery_app_id`, `menu_item_variation_id`, `calculated_price`, `override_price`, `final_price`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 1, NULL, NULL, 600.00, NULL, 600.00, 1, '2025-11-03 23:58:25', '2025-11-03 23:58:25'),
(2, 1, 2, NULL, NULL, 650.00, NULL, 650.00, 1, '2025-11-03 23:58:25', '2025-11-03 23:58:25'),
(3, 1, 3, NULL, NULL, 600.00, NULL, 600.00, 1, '2025-11-03 23:58:25', '2025-11-03 23:58:25'),
(4, 2, 4, NULL, NULL, 50.00, NULL, 50.00, 1, '2025-11-08 02:24:04', '2025-11-08 02:24:04'),
(5, 2, 5, NULL, NULL, 50.00, NULL, 50.00, 1, '2025-11-08 02:24:04', '2025-11-08 02:24:04'),
(6, 2, 6, NULL, NULL, 50.00, NULL, 50.00, 1, '2025-11-08 02:24:04', '2025-11-08 02:24:04'),
(7, 3, 4, NULL, NULL, 900.00, NULL, 900.00, 1, '2025-11-08 04:20:57', '2025-11-08 04:20:57'),
(8, 3, 5, NULL, NULL, 900.00, NULL, 900.00, 1, '2025-11-08 04:20:57', '2025-11-08 04:20:57'),
(9, 3, 6, NULL, NULL, 900.00, NULL, 900.00, 1, '2025-11-08 04:20:57', '2025-11-08 04:20:57');

-- --------------------------------------------------------

--
-- Table structure for table `menu_item_tax`
--

CREATE TABLE `menu_item_tax` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `menu_item_id` bigint(20) UNSIGNED NOT NULL,
  `tax_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `menu_item_translations`
--

CREATE TABLE `menu_item_translations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `menu_item_id` bigint(20) UNSIGNED NOT NULL,
  `locale` varchar(191) NOT NULL,
  `item_name` varchar(191) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `menu_item_translations`
--

INSERT INTO `menu_item_translations` (`id`, `menu_item_id`, `locale`, `item_name`, `description`) VALUES
(1, 1, 'en', 'Noodles', ''),
(2, 2, 'en', 'Paratta', ''),
(3, 3, 'en', 'Fried Rice', '');

-- --------------------------------------------------------

--
-- Table structure for table `menu_item_variations`
--

CREATE TABLE `menu_item_variations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `variation` varchar(191) NOT NULL,
  `price` decimal(16,2) NOT NULL,
  `menu_item_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(191) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2014_04_02_193005_create_translations_table', 1),
(5, '2024_01_01_create_printers_table', 1),
(6, '2024_03_13_000002_create_expense_categories_table', 1),
(7, '2024_07_01_060651_add_two_factor_columns_to_users_table', 1),
(8, '2024_07_01_060707_create_personal_access_tokens_table', 1),
(9, '2024_07_02_064204_create_menus_table', 1),
(10, '2024_07_12_070634_create_areas_table', 1),
(11, '2024_07_16_103816_create_orders_table', 1),
(12, '2024_07_21_083459_add_user_type_column', 1),
(13, '2024_07_24_131631_create_payments_table', 1),
(14, '2024_07_31_081306_add_email_otp_column', 1),
(15, '2024_08_02_061808_create_countries_table', 1),
(16, '2024_08_02_071637_create_restaurant_settings_table', 1),
(17, '2024_08_04_104258_create_razorpay_payments_table', 1),
(18, '2024_08_05_092258_create_stripe_payments_table', 1),
(19, '2024_08_05_110157_create_payment_gateway_credentials_table', 1),
(20, '2024_08_13_033139_create_global_settings_table', 1),
(21, '2024_08_13_073129_update_settings_add_envato_key', 1),
(22, '2024_08_13_073129_update_settings_add_support_key', 1),
(23, '2024_08_14_073129_update_settings_add_email', 1),
(24, '2024_08_14_073129_update_settings_add_last_verified_key', 1),
(25, '2024_09_13_081726_create_modules_table', 1),
(26, '2024_09_14_130619_create_permission_tables', 1),
(27, '2024_09_27_071339_create_reservations_table', 1),
(28, '2024_10_02_090924_create_email_settings_table', 1),
(29, '2024_10_03_073837_create_notification_settings_table', 1),
(30, '2024_10_11_100539_create_branches_table', 1),
(31, '2024_10_14_121135_create_onboarding_steps_table', 1),
(32, '2024_10_15_071238_add_restaurant_hash_column', 1),
(33, '2024_10_15_071238_storage', 1),
(34, '2024_10_15_100639_create_restaurant_payments_table', 1),
(35, '2024_10_27_101326_create_packages_table', 1),
(36, '2024_11_02_112920_create_language_settings_table', 1),
(37, '2024_11_02_120314_create_flags_table', 1),
(38, '2024_11_02_120314_email_settings_table', 1),
(39, '2024_11_08_071617_add_customer_login_required_column', 1),
(40, '2024_11_08_093032_create_superadmin_payment_gateways_table', 1),
(41, '2024_11_08_133506_add_stripe_column_for_license', 1),
(42, '2024_11_12_055119_create_delivery_executives_table', 1),
(43, '2024_11_12_055632_add_order_types_column', 1),
(44, '2024_11_12_060500_create_order_histories_table', 1),
(45, '2024_11_12_060500_global_license_type_table', 1),
(46, '2024_11_12_060500_global_purchase_on_table', 1),
(47, '2024_11_12_060500_global_setting_timezone_table', 1),
(48, '2024_11_17_052707_currency_position', 1),
(49, '2024_11_17_052707_move_qr_code', 1),
(50, '2024_11_19_113852_add_is_active_to_restaurants_table', 1),
(51, '2024_11_20_114816_add_staff_welcome_email_notification', 1),
(52, '2024_11_25_061322_create_pusher_settings_table', 1),
(53, '2024_11_26_090216_create_global_currencies_table', 1),
(54, '2024_12_03_085842_add_about_us_column', 1),
(55, '2024_12_03_104817_add_currency_id_packages', 1),
(56, '2024_12_04_080223_add_allow_customer_delivery_orders', 1),
(57, '2024_12_04_115601_add_preparation_time_column', 1),
(58, '2024_12_11_110000_create_tables_for_subscription_table', 1),
(59, '2024_12_11_131225_add_disable_landing_site_columns', 1),
(60, '2024_12_12_090840_create_waiter_requests_table', 1),
(61, '2024_12_13_090840_add_domain_global_setting', 1),
(62, '2024_12_16_080201_create_lifetime_subscriptions_for_paid_restaurants', 1),
(63, '2024_12_23_124452_add_payment_enabled_columns_to_payment_settings_table', 1),
(64, '2024_12_27_054246_add_table_reservation_default_status_to_restaurants_table', 1),
(65, '2024_12_30_074018_create_split_orders_table', 1),
(66, '2024_12_30_200942_create_restaurant_settings_table', 1),
(67, '2025_01_03_050139_add_social_media_links_to_reataurants_table', 1),
(68, '2025_01_03_093938_add_social_media_links_to_global_settings_table', 1),
(69, '2025_01_06_111550_create_receipt_settings_table', 1),
(70, '2025_01_09_073145_generate_qr_codes_for_existing_branches', 1),
(71, '2025_01_09_115652_update_receipt_settings_for_existing_restaurants', 1),
(72, '2025_01_10_064103_add_table_required_column_to_customer_settings_table', 1),
(73, '2025_01_10_100552_insert_to_file_storage_settings_default_values', 1),
(74, '2025_01_11_063817_add_default_currency_column', 1),
(75, '2025_01_15_000000_create_cart_header_settings_table', 1),
(76, '2025_01_15_000001_create_cart_header_images_table', 1),
(77, '2025_01_16_125322_add_is_enabled_to_menu_items_table', 1),
(78, '2025_01_16_131100_regenrate_qr_codes', 1),
(79, '2025_01_20_000000_add_restaurant_id_to_roles', 1),
(80, '2025_01_20_071544_add_branch_limit_to_packages_table', 1),
(81, '2025_01_20_091630_update_item_type', 1),
(82, '2025_01_20_125429_add_discount_columns_to_orders_table', 1),
(83, '2025_01_21_064139_add_show_logo_text_column', 1),
(84, '2025_01_21_064256_add_offline_payment', 1),
(85, '2025_01_21_132218_fix_user_roles', 1),
(86, '2025_01_22_114720_add_show_tax_to_receipt_setting', 1),
(87, '2025_01_23_065746_create_modifier_groups_table', 1),
(88, '2025_01_23_085333_create_restaurant_taxes_table', 1),
(89, '2025_01_23_090554_create_modifier_options_table', 1),
(90, '2025_01_23_094318_create_item_modifiers_table', 1),
(91, '2025_01_23_121154_create_order_item_modifier_options_table', 1),
(92, '2025_01_27_065822_add_balance_column_to_payment', 1),
(93, '2025_01_28_111039_add_allow_dine_in_orders_to_restaurant', 1),
(94, '2025_01_30_050755_add_yelp_icon_to_global_settings', 1),
(95, '2025_01_30_055744_add_yelp_link_to_restaurants', 1),
(96, '2025_01_30_100556_fix_package_price_length', 1),
(97, '2025_01_30_104043_add_meta_data_to_global_settings', 1),
(98, '2025_01_31_000001_create_predefined_amounts_table', 1),
(99, '2025_02_03_062109_add_is_cash_payment_enabled_to_payment', 1),
(100, '2025_02_04_140538_add_transaction_id_kot', 1),
(101, '2025_02_15_121956_add_hide_new_orders_option_to_restaurant', 1),
(102, '2025_02_17_052801_create_restaurant_charges_settings_table', 1),
(103, '2025_02_17_093729_add_favicon_to_restaurant', 1),
(104, '2025_02_19_091730_update_menu_name_to_json', 1),
(105, '2025_02_20_095321_add_waiter_request_options_to_restaurant', 1),
(106, '2025_02_21_051534_add_hash_to_global_settings_table', 1),
(107, '2025_02_21_102116_add_column_to_settings', 1),
(108, '2025_02_24_063827_add_payment_qr_to_receipt_settings', 1),
(109, '2025_02_24_111946_add_permissions_to_customers', 1),
(110, '2025_03_04_114535_add_is_enabled_to_restaurant_charges', 1),
(111, '2025_03_10_055100_add_tip_column_to_orders_table', 1),
(112, '2025_03_10_100727_add_is_pwa_intall_alert_show_column_in_restaurants_table', 1),
(113, '2025_03_17_090450_add_meta_title_to_global_settings', 1),
(114, '2025_03_18_044410_create_expenses_table', 1),
(115, '2025_03_19_092459_create_custom_menus_table', 1),
(116, '2025_03_19_103047_update_additional_modules', 1),
(117, '2025_03_24_084350_add_show_payments_column_to_receipt_settings_table', 1),
(118, '2025_04_01_050059_add_branch_id_to_expense_category', 1),
(119, '2025_04_01_051356_add_branch_id_to_expenses', 1),
(120, '2025_04_02_071911_update_kot_status_enum', 1),
(121, '2025_04_07_112351_add_payment_recived_status_to_orders_table', 1),
(122, '2025_04_08_063624_update_meta_keywords', 1),
(123, '2025_04_10_065753_add_flutterwave_payment_gateway_columns_and_tables', 1),
(124, '2025_04_15_084543_create_front_details_table', 1),
(125, '2025_04_22_065157_create_front_reviews_setting_table', 1),
(126, '2025_04_22_091055_create_branch_delivery_settings_table', 1),
(127, '2025_04_22_091146_create_customer_addresses_table', 1),
(128, '2025_04_22_091223_create_delivery_fee_tiers_table', 1),
(129, '2025_04_22_091258_add_delivery_columns_to_orders_table', 1),
(130, '2025_04_29_102014_add_landing_type_column_in_global_settings_table', 1),
(131, '2025_04_29_114538_add_front_data_in_front_details_table', 1),
(132, '2025_05_14_094039_update_printers_settings_columns_to_printers_table', 1),
(133, '2025_05_15_071027_create_kot_places_table', 1),
(134, '2025_05_23_124746_add_in_stock_column', 1),
(135, '2025_05_26_105151_relocate_map_api_key_to_superadmin_settings', 1),
(136, '2025_05_26_114443_modify_kot_places_table', 1),
(137, '2025_05_30_081624_add_show_item_on_customer_site_to_menu_items', 1),
(138, '2025_06_02_081928_add_session_driver_column_to_global_settings', 1),
(139, '2025_06_02_112147_add_columns_to_superadmin_payment_gateways_table', 1),
(140, '2025_06_02_112903_add_paypal_payment_column_to_payment_gateway_credentials', 1),
(141, '2025_06_02_113108_create_paypal_payments_table', 1),
(142, '2025_06_02_114326_add_paypal_payment_in_payment_method_to_payments', 1),
(143, '2025_06_03_095923_add_status_column_kot_item', 1),
(144, '2025_06_04_065130_add_columns_payfast_in_superadmin_payment_gateways_table', 1),
(145, '2025_06_05_063256_add_sort_order_columns_in_menu_and_items', 1),
(146, '2025_06_05_112055_create_kot_settings_table', 1),
(147, '2025_06_06_050159_add_payfast_payment_column_to_payment_gateway_credentials', 1),
(148, '2025_06_06_051204_create_payfast_payments_table', 1),
(149, '2025_06_10_093131_change_delete_cascade_for_orders', 1),
(150, '2025_06_11_061716_add_uuid_to_orders_table', 1),
(151, '2025_06_11_062354_add_columns_paystack_in_superadmin_payment_gateways_table', 1),
(152, '2025_06_13_112612_add_phone_to_users', 1),
(153, '2025_06_13_113200_add_column_paystack_payments_to_payment_gateway_credentials', 1),
(154, '2025_06_13_113240_create_paystack_payments_table', 1),
(155, '2025_06_16_104533_add_note_columns_to_kot_items_and_order_items', 1),
(156, '2025_06_18_112425_add_payment_gateways_to_restaurants_table', 1),
(157, '2025_06_19_070518_add_position_to_custom_menus_table', 1),
(158, '2025_06_20_060452_add_columns_to_branch_table', 1),
(159, '2025_06_20_092521_add_others_type_to_payments_table', 1),
(160, '2025_06_23_101041_create_kot_cancel_reasons_table', 1),
(161, '2025_06_23_120021_update_kot_place_id_in_menu_items', 1),
(162, '2025_06_24_092521_disable_printer', 1),
(163, '2025_06_24_092811_add_column_cancel_kot_reason_to_kots_table', 1),
(164, '2025_06_24_102830_update_enum_status_to_kots_table', 1),
(165, '2025_06_25_094311_add_column_cancellation_reason_to_orders_table', 1),
(166, '2025_06_26_060831_add_custom_delivery_options_to_restaurants_table', 1),
(167, '2025_06_27_084541_insert_sample_kot_cancel_reasons_data', 1),
(168, '2025_07_01_112529_create_print_jobs_table', 1),
(169, '2025_07_01_133114_add_placed_via_column_orders_table', 1),
(170, '2025_07_02_090709_create_order_types_table', 1),
(171, '2025_07_02_105440_add_translations_columns_for_modifier_group', 1),
(172, '2025_07_02_114040_add_unique_hash_to_branches_table', 1),
(173, '2025_07_03_123829_update_kot_place_id_for_cloned_menu_items', 1),
(174, '2025_07_04_064350_update_order_type_id_in_orders', 1),
(175, '2025_07_04_081809_add_tax_mode_to_restaurants_table', 1),
(176, '2025_07_04_131541_create_desktop_applications_table', 1),
(177, '2025_07_07_070122_add_pusher_broadcast_to_pusher_settings_table', 1),
(178, '2025_07_07_110131_create_menu_item_taxes_table', 1),
(179, '2025_07_14_082950_add_columns_to_restaurants_table', 1),
(180, '2025_07_14_124125_add_pick_up_date_range_in_restaurants_table', 1),
(181, '2025_07_17_122331_create_order_number_settings', 1),
(182, '2025_07_29_063129_modify_item_type_in-menus', 1),
(183, '2025_07_29_082605_add_show_halal_and_veg_option_to_restaurants', 1),
(184, '2025_07_30_125616_add_tax_mode_to_orders', 1),
(185, '2025_08_01_114055_add_reservation_column_to_restaurants_table', 1),
(186, '2025_08_04_131541_create_desktop_applications_update_table', 1),
(187, '2025_08_05_081541_modify_split_orders_table_add_bank_transfer', 1),
(188, '2025_08_06_065323_change_payment_method_to_string_in_payments_table', 1),
(189, '2025_08_07_033322_add_column_disable_slot_minutes_to_restaurants_table', 1),
(190, '2025_08_08_115502_add_variation_id_to_item_modifiers', 1),
(191, '2025_08_12_133228_change_package_description_length', 1),
(192, '2025_08_13_060315_rename_payfast_columns_in_superadmin_payment_gateways_table', 1),
(193, '2025_08_13_110934_add_default_expense_categories_to_existing_branches', 1),
(194, '2025_08_16_110310_add_slot_time_difference_to_reservations', 1),
(195, '2025_08_19_071639_fix_tax_percent_to_unlimited_decimal', 1),
(196, '2025_08_19_131541_create_desktop_applications_mac_update_table', 1),
(197, '2025_08_20_000001_add_quantity_to_split_order_items', 1),
(198, '2025_08_21_100452_add_html_content_print_job', 1),
(199, '2025_08_25_050939_add_hide_menu_item_image_columns_to_restaurants_table', 1),
(200, '2025_08_25_060934_add_xendit_payment_gateway_to_payment_gateway_credentials_table', 1),
(201, '2025_08_25_061405_add_xendit_to_global_settings_table', 1),
(202, '2025_08_25_061500_create_xendit_payments_table', 1),
(203, '2025_08_25_062000_add_xendit_webhook_verification_tokens', 1),
(204, '2025_08_29_091315_add_phone_code_to_customers_table', 1),
(205, '2025_09_02_085025_add_xendit_payment_column_to_superadmin_payment_gateways_table', 1),
(206, '2025_09_02_113846_add_xendit_payments_column_to_packages_table', 1),
(207, '2025_09_02_130000_create_otps_table', 1),
(208, '2025_09_11_094443_remove_phone_unique', 1),
(209, '2025_09_15_100452_remove_extra_content_print_job', 1),
(210, '2025_09_17_094034_create_cart_session_tables', 1),
(211, '2025_09_18_083624_add_table_lock_columns_and_settings', 1),
(212, '2025_09_25_063220_add_xendit_webhook_token_to_superadmin_payment_gateways', 1),
(213, '2025_09_26_115847_add_token_number_to_orders_table', 1),
(214, '2025_09_26_115854_add_enable_token_number_to_order_types_table', 1),
(215, '2025_09_29_095519_create_delivery_platforms_table', 1),
(216, '2025_10_01_064424_create_menu_item_prices_table', 1),
(217, '2025_10_07_070000_add_reference_id_to_payment_tables', 1),
(218, '2025_10_07_094006_add_token_number_to_kots_table', 1),
(219, '2025_10_07_094018_remove_token_number_from_orders_table', 1),
(220, '2025_10_08_095954_add_columns_paddle_payment_keys_to_superadmin_payment_gateways', 1),
(221, '2025_10_08_102000_add_paddle_client_token_columns_to_superadmin_payment_gateways', 1),
(222, '2025_10_09_041734_add_enable_paddle_to_global_settings_table', 1),
(223, '2025_10_09_065853_remove_payload_from_print_jobs', 1),
(224, '2025_10_09_084200_add_package_id_to_restaurant_payments_table', 1),
(225, '2025_10_09_091500_add_paddle_price_ids_to_packages_table', 1),
(226, '2025_10_10_100000_add_paddle_webhook_secret_to_superadmin_payment_gateways', 1),
(227, '2025_10_10_122321_add_privacy_policy_link_to_global_settings_table', 1),
(228, '2025_10_14_000001_create_modifier_option_prices_table', 1),
(229, '2025_10_14_071228_add_consent_fields_to_users_table', 1),
(230, '2025_10_15_045419_sms_count_packages', 1),
(231, '2025_10_17_074528_add_delivery_app_id_orders_table', 1),
(232, '2025_01_01_121040_inventory_global_settings', 2),
(233, '2025_02_06_095827_create_invetory_module_table', 2),
(234, '2025_02_12_create_purchase_orders_tables', 2),
(235, '2025_03_02_181226_create_inventory_settings_table', 2),
(236, '2025_03_05_100018_create_inventory_settings_table', 2),
(237, '2025_03_19_113535_soft_delete_supplier', 2),
(238, '2025_03_20_113535_permission_supplier', 2),
(239, '2025_07_07_000000_add_variation_support_to_recipes_table', 2),
(240, '2025_07_07_000001_add_modifier_option_support_to_recipes_table', 2),
(241, '2025_07_07_000002_make_menu_item_id_nullable_in_recipes_table', 2),
(242, '2025_09_01_073008_create_global_settings_table', 3),
(243, '2025_09_16_000001_create_cash_register_module', 3),
(244, '2025_09_16_000001_create_cash_registers_tables', 3),
(245, '2025_09_18_000002_add_approval_columns_to_cash_register_sessions', 3),
(246, '2025_09_20_000003_create_denominations_table', 3),
(247, '2025_09_26_043532_create_cash_register_settings_table', 3),
(248, '2025_09_29_120001_add_order_id_to_cash_register_transactions_table', 3),
(249, '2025_10_08_070529_add_open_register_permission_to_cash_register_module', 3),
(250, '2025_10_08_080000_rename_cash_register_permissions', 3),
(251, '2025_10_10_120000_drop_currency_from_denominations', 3),
(252, '2025_08_01_164051_create_kiosk_setting_tables', 4),
(253, '2025_09_26_164051_create_kiosk_tables', 4),
(254, '2025_09_26_164052_create_kiosk_promos_tables', 4),
(255, '2025_01_15_000000_create_database_backups_table', 5),
(256, '2025_01_15_000001_create_database_backup_settings_table', 5),
(257, '2025_01_01_121040_kitchen_global_settings', 6),
(258, '2025_05_19_163251_add_permission_to_kot_places', 6),
(259, '2025_06_12_094512_add_kitchen_place_id_to_kot', 6),
(260, '2025_06_16_121348_update_kot_place_id_in_menu_items', 6),
(261, '2025_11_08_083639_add_show_currency_prefix_to_receipt_settings_table', 7),
(262, '2025_11_09_051648_add_performance_indexes_to_tables', 8),
(263, '2025_11_10_151540_add_kitchen_id_to_users_table', 9),
(264, '2025_11_11_add_manage_kitchens_permission', 10);

-- --------------------------------------------------------

--
-- Table structure for table `model_has_permissions`
--

CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(191) NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `model_has_roles`
--

CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(191) NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `model_has_roles`
--

INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES
(1, 'App\\Models\\User', 1),
(2, 'App\\Models\\User', 2),
(5, 'App\\Models\\User', 4),
(6, 'App\\Models\\User', 3);

-- --------------------------------------------------------

--
-- Table structure for table `modifier_groups`
--

CREATE TABLE `modifier_groups` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `branch_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `modifier_group_translations`
--

CREATE TABLE `modifier_group_translations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `modifier_group_id` bigint(20) UNSIGNED NOT NULL,
  `locale` varchar(191) NOT NULL,
  `name` varchar(191) DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `modifier_options`
--

CREATE TABLE `modifier_options` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `modifier_group_id` bigint(20) UNSIGNED NOT NULL,
  `name` text DEFAULT NULL,
  `price` decimal(16,2) NOT NULL,
  `is_available` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_preselected` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `modifier_option_prices`
--

CREATE TABLE `modifier_option_prices` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `modifier_group_id` bigint(20) UNSIGNED NOT NULL,
  `modifier_option_id` bigint(20) UNSIGNED DEFAULT NULL,
  `order_type_id` bigint(20) UNSIGNED DEFAULT NULL,
  `delivery_app_id` bigint(20) UNSIGNED DEFAULT NULL,
  `calculated_price` decimal(16,2) NOT NULL,
  `override_price` decimal(16,2) DEFAULT NULL,
  `final_price` decimal(16,2) NOT NULL DEFAULT 0.00,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `modules`
--

CREATE TABLE `modules` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `modules`
--

INSERT INTO `modules` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'Menu', NULL, NULL),
(2, 'Menu Item', NULL, NULL),
(3, 'Item Category', NULL, NULL),
(4, 'Area', NULL, NULL),
(5, 'Table', NULL, NULL),
(6, 'Reservation', NULL, NULL),
(7, 'KOT', NULL, NULL),
(8, 'Order', NULL, NULL),
(9, 'Customer', NULL, NULL),
(10, 'Staff', NULL, NULL),
(11, 'Payment', NULL, NULL),
(12, 'Report', NULL, NULL),
(13, 'Settings', NULL, NULL),
(14, 'Delivery Executive', NULL, NULL),
(15, 'Waiter Request', NULL, NULL),
(16, 'Expense', NULL, NULL),
(17, 'Inventory', '2025-11-02 22:52:30', '2025-11-02 22:52:30'),
(18, 'Cash Register', '2025-11-03 01:26:57', '2025-11-03 01:26:57'),
(19, 'Kiosk', '2025-11-03 02:37:44', '2025-11-03 02:37:44'),
(20, 'Kitchen', '2025-11-04 02:05:18', '2025-11-04 02:05:18');

-- --------------------------------------------------------

--
-- Table structure for table `notification_settings`
--

CREATE TABLE `notification_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `restaurant_id` bigint(20) UNSIGNED DEFAULT NULL,
  `type` varchar(191) NOT NULL,
  `send_email` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notification_settings`
--

INSERT INTO `notification_settings` (`id`, `restaurant_id`, `type`, `send_email`, `created_at`, `updated_at`) VALUES
(1, 1, 'order_received', 1, NULL, NULL),
(2, 1, 'reservation_confirmed', 1, NULL, NULL),
(3, 1, 'new_reservation', 1, NULL, NULL),
(4, 1, 'order_bill_sent', 1, NULL, NULL),
(5, 1, 'staff_welcome', 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `offline_payment_methods`
--

CREATE TABLE `offline_payment_methods` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `restaurant_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `offline_plan_changes`
--

CREATE TABLE `offline_plan_changes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `restaurant_id` bigint(20) UNSIGNED DEFAULT NULL,
  `package_id` bigint(20) UNSIGNED NOT NULL,
  `package_type` varchar(191) NOT NULL,
  `amount` decimal(16,2) DEFAULT NULL,
  `pay_date` date DEFAULT NULL,
  `next_pay_date` date DEFAULT NULL,
  `invoice_id` bigint(20) UNSIGNED DEFAULT NULL,
  `offline_method_id` bigint(20) UNSIGNED DEFAULT NULL,
  `file_name` varchar(191) DEFAULT NULL,
  `status` enum('verified','pending','rejected') NOT NULL DEFAULT 'pending',
  `remark` text DEFAULT NULL,
  `description` mediumtext NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `onboarding_steps`
--

CREATE TABLE `onboarding_steps` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `branch_id` bigint(20) UNSIGNED NOT NULL,
  `add_area_completed` tinyint(1) NOT NULL DEFAULT 0,
  `add_table_completed` tinyint(1) NOT NULL DEFAULT 0,
  `add_menu_completed` tinyint(1) NOT NULL DEFAULT 0,
  `add_menu_items_completed` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `onboarding_steps`
--

INSERT INTO `onboarding_steps` (`id`, `branch_id`, `add_area_completed`, `add_table_completed`, `add_menu_completed`, `add_menu_items_completed`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 1, 1, '2025-11-02 00:36:11', '2025-11-02 23:25:42'),
(2, 1, 0, 0, 0, 0, '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(3, 2, 1, 1, 1, 0, '2025-11-02 00:36:11', '2025-11-08 02:14:25'),
(4, 2, 0, 0, 0, 0, '2025-11-02 00:36:11', '2025-11-02 00:36:11');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) DEFAULT NULL,
  `branch_id` bigint(20) UNSIGNED DEFAULT NULL,
  `order_number` varchar(191) NOT NULL,
  `formatted_order_number` varchar(191) DEFAULT NULL,
  `date_time` datetime NOT NULL,
  `table_id` bigint(20) UNSIGNED DEFAULT NULL,
  `customer_id` bigint(20) UNSIGNED DEFAULT NULL,
  `number_of_pax` int(11) DEFAULT NULL,
  `waiter_id` bigint(20) UNSIGNED DEFAULT NULL,
  `status` enum('draft','kot','billed','paid','canceled','payment_due','ready','out_for_delivery','delivered','pending_verification') NOT NULL DEFAULT 'kot',
  `placed_via` enum('pos','shop','kiosk') DEFAULT NULL,
  `sub_total` decimal(16,2) NOT NULL,
  `tip_amount` decimal(16,2) DEFAULT 0.00,
  `total_tax_amount` decimal(16,2) DEFAULT 0.00,
  `tax_mode` varchar(191) DEFAULT NULL,
  `tip_note` text DEFAULT NULL,
  `total` decimal(16,2) NOT NULL,
  `amount_paid` decimal(16,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `order_type_id` bigint(20) UNSIGNED DEFAULT NULL,
  `delivery_app_id` bigint(20) UNSIGNED DEFAULT NULL,
  `custom_order_type_name` varchar(191) DEFAULT NULL,
  `order_type` varchar(191) DEFAULT NULL,
  `pickup_date` datetime DEFAULT NULL,
  `delivery_executive_id` bigint(20) UNSIGNED DEFAULT NULL,
  `delivery_address` text DEFAULT NULL,
  `delivery_time` datetime DEFAULT NULL,
  `estimated_delivery_time` datetime DEFAULT NULL,
  `split_type` enum('even','custom','items') DEFAULT NULL,
  `discount_type` varchar(191) DEFAULT NULL,
  `discount_value` decimal(16,2) DEFAULT NULL,
  `discount_amount` decimal(16,2) DEFAULT NULL,
  `order_status` varchar(191) NOT NULL DEFAULT 'placed',
  `delivery_fee` decimal(8,2) NOT NULL DEFAULT 0.00,
  `customer_lat` decimal(10,7) DEFAULT NULL,
  `customer_lng` decimal(10,7) DEFAULT NULL,
  `is_within_radius` tinyint(1) NOT NULL DEFAULT 0,
  `delivery_started_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `estimated_eta_min` int(11) DEFAULT NULL,
  `estimated_eta_max` int(11) DEFAULT NULL,
  `cancel_reason_id` bigint(20) UNSIGNED DEFAULT NULL,
  `cancel_reason_text` varchar(191) DEFAULT NULL,
  `reservation_id` bigint(20) UNSIGNED DEFAULT NULL,
  `kiosk_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `uuid`, `branch_id`, `order_number`, `formatted_order_number`, `date_time`, `table_id`, `customer_id`, `number_of_pax`, `waiter_id`, `status`, `placed_via`, `sub_total`, `tip_amount`, `total_tax_amount`, `tax_mode`, `tip_note`, `total`, `amount_paid`, `created_at`, `updated_at`, `order_type_id`, `delivery_app_id`, `custom_order_type_name`, `order_type`, `pickup_date`, `delivery_executive_id`, `delivery_address`, `delivery_time`, `estimated_delivery_time`, `split_type`, `discount_type`, `discount_value`, `discount_amount`, `order_status`, `delivery_fee`, `customer_lat`, `customer_lng`, `is_within_radius`, `delivery_started_at`, `delivered_at`, `estimated_eta_min`, `estimated_eta_max`, `cancel_reason_id`, `cancel_reason_text`, `reservation_id`, `kiosk_id`) VALUES
(1, 'd4f60f31-c43a-48dd-a4b4-3162849b93d3', 1, '1', NULL, '2025-11-03 04:56:07', NULL, NULL, 1, 2, 'kot', 'pos', 2.00, 0.00, 0.00, 'order', NULL, 2.10, 0.00, '2025-11-02 23:26:07', '2025-11-02 23:27:58', 1, NULL, 'Dine In', 'dine_in', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'served', 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, '8fb04f34-6a67-481f-b5f7-40a35a048e7e', 1, '2', NULL, '2025-11-03 08:23:46', 1, NULL, NULL, NULL, 'billed', 'shop', 2.00, 0.00, 0.10, 'order', NULL, 2.10, 0.00, '2025-11-03 02:52:10', '2025-11-03 02:53:54', 1, NULL, 'Dine In', 'dine_in', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'served', 0.00, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(3, '6fb48019-56c4-4db7-8633-3ca0d1ea2cac', 1, '3', NULL, '2025-11-03 08:32:28', 1, NULL, NULL, NULL, 'kot', 'shop', 2.00, 0.00, 0.00, 'order', NULL, 2.10, 0.00, '2025-11-03 03:02:28', '2025-11-03 03:02:29', 1, NULL, 'Dine In', 'dine_in', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'placed', 0.00, NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(4, '12246424-0671-42cd-a728-84f020b3cace', 1, '4', NULL, '2025-11-04 04:33:47', NULL, NULL, 1, 2, 'billed', 'pos', 2.00, 0.00, 0.10, 'order', NULL, 2.10, 0.00, '2025-11-03 23:03:51', '2025-11-03 23:03:53', 1, NULL, 'Dine In', 'dine_in', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'preparing', 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(5, '87b5fb3c-5df2-4020-90c9-3b6338b61177', 1, '5', NULL, '2025-11-04 04:43:05', NULL, NULL, 1, 2, 'paid', 'pos', 8.00, 0.00, 0.40, 'order', NULL, 8.40, 8.40, '2025-11-03 23:13:05', '2025-11-04 00:17:04', 1, NULL, 'Dine In', 'dine_in', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'served', 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(6, 'd8231cb9-29d8-41ea-b576-7d83c6e7d474', 1, '6', NULL, '2025-11-04 05:34:12', NULL, NULL, 1, 2, 'paid', 'pos', 1200.00, 0.00, 60.00, 'order', NULL, 1260.00, 1260.00, '2025-11-04 00:00:19', '2025-11-04 00:10:21', 3, NULL, 'Pickup', 'pickup', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'delivered', 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(7, '2bae9a35-e5fc-4d05-8a8a-119cc014f09d', 1, '7', NULL, '2025-11-04 05:38:47', NULL, NULL, 1, 2, 'paid', 'pos', 600.00, 0.00, 0.00, 'order', NULL, 600.00, 600.00, '2025-11-04 00:08:47', '2025-11-04 00:11:25', 1, NULL, 'Dine In', 'dine_in', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'served', 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(8, 'a2500d96-d0a3-450f-948d-5b7440970729', 2, '1', NULL, '2025-11-08 07:56:12', 2, NULL, 1, 2, 'paid', 'pos', 750.00, 0.00, 0.00, 'order', NULL, 750.00, 750.00, '2025-11-08 02:26:12', '2025-11-08 02:26:48', 4, NULL, 'Dine In', 'dine_in', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'served', 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(9, 'd1300964-64ac-416f-acfe-26190f07d4e8', 2, '2', NULL, '2025-11-08 07:57:51', NULL, NULL, NULL, 2, 'paid', 'pos', 500.00, 100.00, 0.00, 'order', '', 600.00, 600.00, '2025-11-08 02:27:51', '2025-11-08 02:29:00', 4, NULL, 'Dine In', 'dine_in', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'served', 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(10, 'c38fe554-6748-4a3e-ba5f-598eb7bd8d7b', 2, '3', NULL, '2025-11-08 08:42:52', NULL, NULL, 1, 2, 'billed', 'pos', 100.00, 0.00, 0.00, 'order', NULL, 100.00, 0.00, '2025-11-08 03:12:52', '2025-11-08 03:12:52', 4, NULL, 'Dine In', 'dine_in', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'preparing', 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(11, '9d399c18-18c0-4f37-ba28-9ee82ba8b019', 2, '4', NULL, '2025-11-08 09:03:36', NULL, NULL, 1, 2, 'billed', 'pos', 50.00, 0.00, 0.00, 'order', NULL, 50.00, 0.00, '2025-11-08 03:33:36', '2025-11-08 03:33:36', 4, NULL, 'Dine In', 'dine_in', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'preparing', 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(12, '40f2a034-0698-4cf6-b69f-290ab72116cf', 2, '5', NULL, '2025-11-08 09:17:16', NULL, NULL, 1, 2, 'billed', 'pos', 150.00, 0.00, 0.00, 'order', NULL, 150.00, 0.00, '2025-11-08 03:47:16', '2025-11-08 03:47:16', 4, NULL, 'Dine In', 'dine_in', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'preparing', 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(13, '4ff56517-165a-4007-b64c-7eba7b9f60e7', 1, '8', NULL, '2025-11-08 09:27:23', NULL, NULL, 1, 2, 'billed', 'pos', 600.00, 0.00, 0.00, 'order', NULL, 600.00, 0.00, '2025-11-08 03:57:23', '2025-11-08 03:57:23', 1, NULL, 'Dine In', 'dine_in', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'preparing', 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(14, '995a4435-bc6f-4a53-9f17-eedb794f731a', 2, '6', NULL, '2025-11-08 09:38:30', NULL, NULL, 1, 2, 'billed', 'pos', 2550.00, 0.00, 0.00, 'order', NULL, 2550.00, 0.00, '2025-11-08 04:08:30', '2025-11-08 04:08:30', 4, NULL, 'Dine In', 'dine_in', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'preparing', 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(15, 'a522fedf-b29a-48a5-81a4-19745933d7ac', 2, '7', NULL, '2025-11-08 09:51:58', NULL, NULL, 1, 2, 'paid', 'pos', 45900.00, 0.00, 0.00, 'order', NULL, 45900.00, 45900.00, '2025-11-08 04:21:58', '2025-11-08 04:25:48', 4, NULL, 'Dine In', 'dine_in', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'preparing', 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(16, '3dabb16a-ca05-42f2-80a8-35695855bee6', 2, '8', NULL, '2025-11-08 10:35:00', NULL, NULL, 1, 2, 'paid', 'pos', 950.00, 0.00, 0.00, 'order', NULL, 950.00, 950.00, '2025-11-08 04:48:45', '2025-11-08 05:10:04', 4, NULL, 'Dine In', 'dine_in', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'served', 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(17, '819ebda0-47d1-4ece-bee0-054ed9d6e943', 1, '9', NULL, '2025-11-09 04:02:14', NULL, NULL, 1, 2, 'kot', 'pos', 1200.00, 0.00, 0.00, 'order', NULL, 1200.00, 0.00, '2025-11-08 22:32:18', '2025-11-08 22:32:18', 1, NULL, 'Dine In', 'dine_in', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'preparing', 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(18, '44beebb9-01ae-4070-90d8-1938c504d310', 1, '10', NULL, '2025-11-09 04:51:26', NULL, NULL, 1, 2, 'billed', 'pos', 600.00, 0.00, 0.00, 'order', NULL, 600.00, 0.00, '2025-11-08 23:21:26', '2025-11-08 23:21:26', 1, NULL, 'Dine In', 'dine_in', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'preparing', 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(19, 'de1295d6-f703-4430-826a-14d36c8fd248', 1, '11', NULL, '2025-11-10 09:33:39', NULL, NULL, 1, 2, 'paid', 'pos', 1800.00, 0.00, 0.00, 'order', NULL, 1800.00, 1800.00, '2025-11-10 03:51:09', '2025-11-10 04:04:17', 1, NULL, 'Dine In', 'dine_in', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'served', 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(20, '52b2a031-d02b-4558-9127-4fcc6e41a449', 1, '12', NULL, '2025-11-10 14:44:47', NULL, NULL, 1, 2, 'kot', 'pos', 600.00, 0.00, 0.00, 'order', NULL, 600.00, 0.00, '2025-11-10 09:14:48', '2025-11-10 09:14:48', 1, NULL, 'Dine In', 'dine_in', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'preparing', 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(21, '0972eb06-824e-4c2a-b972-7888a0b27440', 1, '13', NULL, '2025-11-10 14:46:26', NULL, NULL, NULL, 2, 'kot', 'pos', 1200.00, 0.00, 0.00, 'order', NULL, 1200.00, 0.00, '2025-11-10 09:16:26', '2025-11-10 09:16:26', 1, NULL, 'Dine In', 'dine_in', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'preparing', 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(22, '83282320-72dc-4932-a954-c3f2ba67b44d', 1, '14', NULL, '2025-11-10 14:47:04', NULL, NULL, NULL, 2, 'kot', 'pos', 600.00, 0.00, 0.00, 'order', NULL, 600.00, 0.00, '2025-11-10 09:17:04', '2025-11-10 09:17:04', 1, NULL, 'Dine In', 'dine_in', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'preparing', 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(23, 'ed5b07e8-4cbc-4bc0-a56c-8a2dce358e23', 1, '15', NULL, '2025-11-10 15:43:39', NULL, NULL, 1, 2, 'kot', 'pos', 2400.00, 0.00, 0.00, 'order', NULL, 2400.00, 0.00, '2025-11-10 10:13:40', '2025-11-10 10:13:40', 1, NULL, 'Dine In', 'dine_in', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'preparing', 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(24, 'efd9aaf6-cb9c-4e9a-9e51-bfefd97c8bab', 1, '16', NULL, '2025-11-11 04:17:30', NULL, NULL, 1, 4, 'kot', 'pos', 600.00, 0.00, 0.00, 'order', NULL, 600.00, 0.00, '2025-11-10 22:47:34', '2025-11-10 22:47:34', 1, NULL, 'Dine In', 'dine_in', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'preparing', 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(25, '9e117aeb-34d1-4fde-a897-66533702fcda', 1, '17', NULL, '2025-11-11 06:34:43', NULL, NULL, 1, 2, 'kot', 'pos', 1200.00, 0.00, 0.00, 'order', NULL, 1200.00, 0.00, '2025-11-11 01:04:43', '2025-11-11 01:04:43', 1, NULL, 'Dine In', 'dine_in', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'preparing', 0.00, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_charges`
--

CREATE TABLE `order_charges` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `charge_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_histories`
--

CREATE TABLE `order_histories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `status` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `branch_id` bigint(20) UNSIGNED DEFAULT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `transaction_id` varchar(191) DEFAULT NULL,
  `menu_item_id` bigint(20) UNSIGNED NOT NULL,
  `menu_item_variation_id` bigint(20) UNSIGNED DEFAULT NULL,
  `note` text DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(16,2) NOT NULL,
  `amount` decimal(16,2) NOT NULL,
  `tax_amount` decimal(15,2) DEFAULT NULL,
  `tax_percentage` decimal(8,4) DEFAULT NULL,
  `tax_breakup` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tax_breakup`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `branch_id`, `order_id`, `transaction_id`, `menu_item_id`, `menu_item_variation_id`, `note`, `quantity`, `price`, `amount`, `tax_amount`, `tax_percentage`, `tax_breakup`, `created_at`, `updated_at`) VALUES
(2, 1, 2, NULL, 1, NULL, NULL, 1, 2.00, 2.00, NULL, NULL, NULL, '2025-11-03 02:53:46', '2025-11-03 02:53:46'),
(3, 1, 3, 'TXN_6908689cf0a376.36200995_513349', 1, NULL, '', 1, 2.00, 2.00, NULL, NULL, NULL, '2025-11-03 03:02:29', '2025-11-03 03:02:29'),
(4, 1, 4, NULL, 1, NULL, '', 1, 2.00, 2.00, NULL, NULL, NULL, '2025-11-03 23:03:53', '2025-11-03 23:03:53'),
(5, 1, 5, NULL, 1, NULL, '', 4, 2.00, 8.00, NULL, NULL, NULL, '2025-11-03 23:13:05', '2025-11-03 23:13:05'),
(6, 1, 6, NULL, 1, NULL, NULL, 2, 600.00, 1200.00, NULL, NULL, NULL, '2025-11-04 00:04:12', '2025-11-04 00:04:12'),
(7, 1, 7, NULL, 1, NULL, NULL, 1, 600.00, 600.00, NULL, NULL, NULL, '2025-11-04 00:08:47', '2025-11-04 00:08:47'),
(8, 2, 8, NULL, 2, NULL, NULL, 15, 50.00, 750.00, NULL, NULL, NULL, '2025-11-08 02:26:13', '2025-11-08 02:26:13'),
(9, 2, 9, NULL, 2, NULL, '', 10, 50.00, 500.00, NULL, NULL, NULL, '2025-11-08 02:27:51', '2025-11-08 02:27:51'),
(10, 2, 10, NULL, 2, NULL, '', 2, 50.00, 100.00, NULL, NULL, NULL, '2025-11-08 03:12:52', '2025-11-08 03:12:52'),
(11, 2, 11, NULL, 2, NULL, '', 1, 50.00, 50.00, NULL, NULL, NULL, '2025-11-08 03:33:36', '2025-11-08 03:33:36'),
(12, 2, 12, NULL, 2, NULL, '', 3, 50.00, 150.00, NULL, NULL, NULL, '2025-11-08 03:47:16', '2025-11-08 03:47:16'),
(13, 1, 13, NULL, 1, NULL, '', 1, 600.00, 600.00, NULL, NULL, NULL, '2025-11-08 03:57:23', '2025-11-08 03:57:23'),
(14, 2, 14, NULL, 2, NULL, '', 51, 50.00, 2550.00, NULL, NULL, NULL, '2025-11-08 04:08:30', '2025-11-08 04:08:30'),
(15, 2, 15, NULL, 3, NULL, '', 51, 900.00, 45900.00, NULL, NULL, NULL, '2025-11-08 04:21:58', '2025-11-08 04:21:58'),
(16, 2, 16, NULL, 3, NULL, NULL, 1, 900.00, 900.00, NULL, NULL, NULL, '2025-11-08 05:05:01', '2025-11-08 05:05:01'),
(17, 2, 16, NULL, 2, NULL, NULL, 1, 50.00, 50.00, NULL, NULL, NULL, '2025-11-08 05:05:01', '2025-11-08 05:05:01'),
(18, 1, 18, NULL, 1, NULL, '', 1, 600.00, 600.00, NULL, NULL, NULL, '2025-11-08 23:21:26', '2025-11-08 23:21:26'),
(19, 1, 19, NULL, 1, NULL, NULL, 3, 600.00, 1800.00, NULL, NULL, NULL, '2025-11-10 04:03:39', '2025-11-10 04:03:39');

-- --------------------------------------------------------

--
-- Table structure for table `order_item_modifier_options`
--

CREATE TABLE `order_item_modifier_options` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_item_id` bigint(20) UNSIGNED NOT NULL,
  `modifier_option_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_number_settings`
--

CREATE TABLE `order_number_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `branch_id` bigint(20) UNSIGNED NOT NULL,
  `enable_feature` tinyint(1) NOT NULL DEFAULT 0,
  `prefix` varchar(191) NOT NULL DEFAULT 'ORD',
  `digits` tinyint(3) UNSIGNED NOT NULL DEFAULT 3,
  `separator` varchar(191) NOT NULL DEFAULT '-',
  `include_date` tinyint(1) NOT NULL DEFAULT 0,
  `show_year` tinyint(1) NOT NULL DEFAULT 0,
  `show_month` tinyint(1) NOT NULL DEFAULT 0,
  `show_day` tinyint(1) NOT NULL DEFAULT 0,
  `show_time` tinyint(1) NOT NULL DEFAULT 0,
  `reset_daily` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_places`
--

CREATE TABLE `order_places` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `printer_id` bigint(20) UNSIGNED DEFAULT NULL,
  `branch_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(191) NOT NULL,
  `type` varchar(191) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_places`
--

INSERT INTO `order_places` (`id`, `printer_id`, `branch_id`, `name`, `type`, `is_active`, `is_default`, `created_at`, `updated_at`) VALUES
(1, NULL, 1, 'Default POS Terminal', 'vegetarian', 1, 1, '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(2, NULL, 2, 'Default POS Terminal', 'vegetarian', 1, 1, '2025-11-02 00:36:11', '2025-11-02 00:36:11');

-- --------------------------------------------------------

--
-- Table structure for table `order_taxes`
--

CREATE TABLE `order_taxes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `tax_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_types`
--

CREATE TABLE `order_types` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `branch_id` bigint(20) UNSIGNED DEFAULT NULL,
  `order_type_name` varchar(191) NOT NULL,
  `slug` varchar(191) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `enable_token_number` tinyint(1) NOT NULL DEFAULT 0,
  `type` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_types`
--

INSERT INTO `order_types` (`id`, `branch_id`, `order_type_name`, `slug`, `is_active`, `is_default`, `enable_token_number`, `type`, `created_at`, `updated_at`) VALUES
(1, 1, 'Dine In', 'dine_in', 1, 1, 0, 'dine_in', '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(2, 1, 'Delivery', 'delivery', 1, 1, 0, 'delivery', '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(3, 1, 'Pickup', 'pickup', 1, 1, 0, 'pickup', '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(4, 2, 'Dine In', 'dine_in', 1, 1, 0, 'dine_in', '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(5, 2, 'Delivery', 'delivery', 1, 1, 0, 'delivery', '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(6, 2, 'Pickup', 'pickup', 1, 1, 0, 'pickup', '2025-11-02 00:36:11', '2025-11-02 00:36:11');

-- --------------------------------------------------------

--
-- Table structure for table `otps`
--

CREATE TABLE `otps` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `identifier` varchar(191) NOT NULL,
  `token` varchar(191) NOT NULL,
  `type` varchar(191) NOT NULL DEFAULT 'login',
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `used` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `packages`
--

CREATE TABLE `packages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `package_name` varchar(191) NOT NULL,
  `price` decimal(16,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `currency_id` bigint(20) UNSIGNED DEFAULT NULL,
  `description` text NOT NULL,
  `annual_price` decimal(16,2) DEFAULT NULL,
  `monthly_price` decimal(16,2) DEFAULT NULL,
  `monthly_status` varchar(191) DEFAULT '1',
  `annual_status` varchar(191) DEFAULT '1',
  `stripe_annual_plan_id` varchar(191) DEFAULT NULL,
  `stripe_monthly_plan_id` varchar(191) DEFAULT NULL,
  `razorpay_annual_plan_id` varchar(191) DEFAULT NULL,
  `razorpay_monthly_plan_id` varchar(191) DEFAULT NULL,
  `flutterwave_annual_plan_id` varchar(191) DEFAULT NULL,
  `flutterwave_monthly_plan_id` varchar(191) DEFAULT NULL,
  `paystack_annual_plan_id` varchar(191) DEFAULT NULL,
  `paystack_monthly_plan_id` varchar(191) DEFAULT NULL,
  `xendit_annual_plan_id` varchar(191) DEFAULT NULL,
  `xendit_monthly_plan_id` varchar(191) DEFAULT NULL,
  `paddle_annual_price_id` varchar(191) DEFAULT NULL,
  `paddle_monthly_price_id` varchar(191) DEFAULT NULL,
  `paddle_lifetime_price_id` varchar(191) DEFAULT NULL,
  `stripe_lifetime_plan_id` varchar(191) DEFAULT NULL,
  `razorpay_lifetime_plan_id` varchar(191) DEFAULT NULL,
  `billing_cycle` tinyint(3) UNSIGNED DEFAULT NULL,
  `sort_order` int(10) UNSIGNED DEFAULT NULL,
  `is_private` tinyint(1) NOT NULL DEFAULT 0,
  `is_free` tinyint(1) NOT NULL DEFAULT 0,
  `is_recommended` tinyint(1) NOT NULL DEFAULT 0,
  `package_type` varchar(191) NOT NULL DEFAULT 'standard',
  `trial_status` tinyint(1) DEFAULT NULL,
  `trial_days` int(11) DEFAULT NULL,
  `trial_notification_before_days` int(11) DEFAULT NULL,
  `trial_message` varchar(191) DEFAULT NULL,
  `additional_features` longtext DEFAULT NULL,
  `branch_limit` int(11) DEFAULT -1,
  `sms_count` int(11) NOT NULL DEFAULT 0,
  `carry_forward_sms` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `packages`
--

INSERT INTO `packages` (`id`, `package_name`, `price`, `created_at`, `updated_at`, `currency_id`, `description`, `annual_price`, `monthly_price`, `monthly_status`, `annual_status`, `stripe_annual_plan_id`, `stripe_monthly_plan_id`, `razorpay_annual_plan_id`, `razorpay_monthly_plan_id`, `flutterwave_annual_plan_id`, `flutterwave_monthly_plan_id`, `paystack_annual_plan_id`, `paystack_monthly_plan_id`, `xendit_annual_plan_id`, `xendit_monthly_plan_id`, `paddle_annual_price_id`, `paddle_monthly_price_id`, `paddle_lifetime_price_id`, `stripe_lifetime_plan_id`, `razorpay_lifetime_plan_id`, `billing_cycle`, `sort_order`, `is_private`, `is_free`, `is_recommended`, `package_type`, `trial_status`, `trial_days`, `trial_notification_before_days`, `trial_message`, `additional_features`, `branch_limit`, `sms_count`, `carry_forward_sms`) VALUES
(1, 'Default', 0.00, '2025-11-02 00:36:10', '2025-11-02 00:36:10', 1, 'Its a default package and cannot be deleted', NULL, NULL, '0', '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 12, 1, 0, 1, 0, 'default', NULL, NULL, NULL, NULL, NULL, -1, 0, 0),
(2, 'Subscription Package', 0.00, '2025-11-02 00:36:10', '2025-11-02 00:36:10', 1, 'This is a subscription package', 100.00, 10.00, '1', '1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 10, 2, 0, 0, 1, 'standard', NULL, NULL, NULL, NULL, NULL, -1, 0, 0),
(3, 'Life Time', 199.00, '2025-11-02 00:36:10', '2025-11-04 02:06:50', 1, 'This is a lifetime access package', NULL, NULL, '0', '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 3, 0, 0, 1, 'lifetime', NULL, NULL, NULL, NULL, '[\"Change Branch\",\"Export Report\",\"Table Reservation\",\"Payment Gateway Integration\",\"Theme Setting\",\"Customer Display\"]', -1, -1, 0),
(4, 'Private Package', 0.00, '2025-11-02 00:36:10', '2025-11-02 00:36:10', 1, 'This is a private package', 50.00, 5.00, '1', '1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 12, 4, 1, 0, 0, 'standard', NULL, NULL, NULL, NULL, NULL, -1, 0, 0),
(5, 'Trial Package', 0.00, '2025-11-02 00:36:10', '2025-11-02 00:36:10', 1, 'This is a trial package', NULL, NULL, '0', '0', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, 1, 0, 'trial', 1, 30, 5, '30 Days Free Trial', '[\"Change Branch\",\"Export Report\",\"Table Reservation\",\"Payment Gateway Integration\",\"Theme Setting\",\"Customer Display\"]', -1, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `package_modules`
--

CREATE TABLE `package_modules` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `package_id` bigint(20) UNSIGNED DEFAULT NULL,
  `module_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `package_modules`
--

INSERT INTO `package_modules` (`id`, `package_id`, `module_id`, `created_at`, `updated_at`) VALUES
(1, 1, 1, NULL, NULL),
(2, 1, 2, NULL, NULL),
(3, 1, 3, NULL, NULL),
(4, 1, 4, NULL, NULL),
(5, 1, 5, NULL, NULL),
(6, 1, 6, NULL, NULL),
(7, 1, 7, NULL, NULL),
(8, 1, 8, NULL, NULL),
(9, 1, 9, NULL, NULL),
(10, 1, 10, NULL, NULL),
(11, 1, 11, NULL, NULL),
(12, 1, 12, NULL, NULL),
(13, 1, 13, NULL, NULL),
(14, 1, 14, NULL, NULL),
(15, 1, 15, NULL, NULL),
(16, 1, 16, NULL, NULL),
(17, 2, 1, NULL, NULL),
(18, 2, 2, NULL, NULL),
(19, 2, 3, NULL, NULL),
(20, 2, 4, NULL, NULL),
(21, 2, 5, NULL, NULL),
(22, 2, 6, NULL, NULL),
(23, 2, 7, NULL, NULL),
(24, 2, 8, NULL, NULL),
(25, 2, 9, NULL, NULL),
(26, 2, 10, NULL, NULL),
(27, 2, 11, NULL, NULL),
(28, 2, 12, NULL, NULL),
(29, 2, 13, NULL, NULL),
(30, 2, 14, NULL, NULL),
(31, 2, 15, NULL, NULL),
(32, 2, 16, NULL, NULL),
(33, 3, 1, NULL, NULL),
(34, 3, 2, NULL, NULL),
(35, 3, 3, NULL, NULL),
(36, 3, 4, NULL, NULL),
(37, 3, 5, NULL, NULL),
(38, 3, 6, NULL, NULL),
(39, 3, 7, NULL, NULL),
(40, 3, 8, NULL, NULL),
(41, 3, 9, NULL, NULL),
(42, 3, 10, NULL, NULL),
(43, 3, 11, NULL, NULL),
(44, 3, 12, NULL, NULL),
(45, 3, 13, NULL, NULL),
(46, 3, 14, NULL, NULL),
(47, 3, 15, NULL, NULL),
(48, 3, 16, NULL, NULL),
(49, 4, 1, NULL, NULL),
(50, 4, 2, NULL, NULL),
(51, 4, 3, NULL, NULL),
(52, 4, 4, NULL, NULL),
(53, 4, 5, NULL, NULL),
(54, 4, 6, NULL, NULL),
(55, 4, 7, NULL, NULL),
(56, 4, 8, NULL, NULL),
(57, 4, 9, NULL, NULL),
(58, 4, 10, NULL, NULL),
(59, 4, 11, NULL, NULL),
(60, 4, 12, NULL, NULL),
(61, 4, 13, NULL, NULL),
(62, 4, 14, NULL, NULL),
(63, 4, 15, NULL, NULL),
(64, 4, 16, NULL, NULL),
(65, 5, 1, NULL, NULL),
(66, 5, 2, NULL, NULL),
(67, 5, 3, NULL, NULL),
(68, 5, 4, NULL, NULL),
(69, 5, 5, NULL, NULL),
(70, 5, 6, NULL, NULL),
(71, 5, 7, NULL, NULL),
(72, 5, 8, NULL, NULL),
(73, 5, 9, NULL, NULL),
(74, 5, 10, NULL, NULL),
(75, 5, 11, NULL, NULL),
(76, 5, 12, NULL, NULL),
(77, 5, 13, NULL, NULL),
(78, 5, 14, NULL, NULL),
(79, 5, 15, NULL, NULL),
(80, 5, 16, NULL, NULL),
(81, 3, 17, NULL, NULL),
(82, 3, 18, NULL, NULL),
(83, 3, 19, NULL, NULL),
(84, 1, 19, NULL, NULL),
(85, 3, 20, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(191) NOT NULL,
  `token` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payfast_payments`
--

CREATE TABLE `payfast_payments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `payfast_payment_id` varchar(191) DEFAULT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_status` enum('pending','completed','failed') NOT NULL DEFAULT 'pending',
  `payment_date` timestamp NULL DEFAULT NULL,
  `payment_error_response` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payment_error_response`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `branch_id` bigint(20) UNSIGNED DEFAULT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `payment_method` varchar(191) DEFAULT 'cash',
  `amount` decimal(16,2) NOT NULL,
  `balance` decimal(16,2) DEFAULT 0.00,
  `transaction_id` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `branch_id`, `order_id`, `payment_method`, `amount`, `balance`, `transaction_id`, `created_at`, `updated_at`) VALUES
(1, 1, 6, 'cash', 1260.00, 0.00, NULL, '2025-11-04 00:04:27', '2025-11-04 00:04:27'),
(2, 1, 7, 'cash', 600.00, 0.00, NULL, '2025-11-04 00:11:22', '2025-11-04 00:11:22'),
(3, 1, 5, 'cash', 8.40, 0.00, NULL, '2025-11-04 00:17:04', '2025-11-04 00:17:04'),
(4, 2, 8, 'cash', 750.00, 250.00, NULL, '2025-11-08 02:26:43', '2025-11-08 02:26:43'),
(5, 2, 9, 'card', 600.00, 0.00, NULL, '2025-11-08 02:29:00', '2025-11-08 02:29:00'),
(6, 2, 15, 'cash', 45900.00, 100.00, NULL, '2025-11-08 04:25:48', '2025-11-08 04:25:48'),
(7, 2, 16, 'cash', 950.00, 50.00, NULL, '2025-11-08 05:10:04', '2025-11-08 05:10:04'),
(8, 1, 19, 'cash', 1800.00, 200.00, NULL, '2025-11-10 04:04:17', '2025-11-10 04:04:17');

-- --------------------------------------------------------

--
-- Table structure for table `payment_gateway_credentials`
--

CREATE TABLE `payment_gateway_credentials` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `restaurant_id` bigint(20) UNSIGNED DEFAULT NULL,
  `razorpay_key` text DEFAULT NULL,
  `razorpay_secret` text DEFAULT NULL,
  `razorpay_status` tinyint(1) NOT NULL DEFAULT 0,
  `stripe_key` text DEFAULT NULL,
  `stripe_secret` text DEFAULT NULL,
  `stripe_status` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `is_dine_in_payment_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `is_delivery_payment_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `is_pickup_payment_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `is_cash_payment_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `is_qr_payment_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `is_offline_payment_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `offline_payment_detail` varchar(191) DEFAULT NULL,
  `qr_code_image` varchar(191) DEFAULT NULL,
  `flutterwave_status` tinyint(1) NOT NULL DEFAULT 0,
  `flutterwave_mode` enum('test','live') NOT NULL DEFAULT 'test',
  `test_flutterwave_key` varchar(191) DEFAULT NULL,
  `test_flutterwave_secret` varchar(191) DEFAULT NULL,
  `test_flutterwave_hash` varchar(191) DEFAULT NULL,
  `live_flutterwave_key` varchar(191) DEFAULT NULL,
  `live_flutterwave_secret` varchar(191) DEFAULT NULL,
  `live_flutterwave_hash` varchar(191) DEFAULT NULL,
  `flutterwave_webhook_secret_hash` varchar(191) DEFAULT NULL,
  `paypal_client_id` varchar(191) DEFAULT NULL,
  `paypal_secret` varchar(191) DEFAULT NULL,
  `paypal_status` tinyint(1) NOT NULL DEFAULT 0,
  `paypal_mode` enum('sandbox','live') NOT NULL DEFAULT 'sandbox',
  `sandbox_paypal_client_id` varchar(191) DEFAULT NULL,
  `sandbox_paypal_secret` varchar(191) DEFAULT NULL,
  `payfast_merchant_id` varchar(191) DEFAULT NULL,
  `payfast_merchant_key` varchar(191) DEFAULT NULL,
  `payfast_passphrase` varchar(191) DEFAULT NULL,
  `payfast_mode` enum('sandbox','live') NOT NULL DEFAULT 'sandbox',
  `payfast_status` tinyint(1) NOT NULL DEFAULT 0,
  `test_payfast_merchant_id` varchar(191) DEFAULT NULL,
  `test_payfast_merchant_key` varchar(191) DEFAULT NULL,
  `test_payfast_passphrase` varchar(191) DEFAULT NULL,
  `paystack_key` varchar(191) DEFAULT NULL,
  `paystack_secret` varchar(191) DEFAULT NULL,
  `paystack_merchant_email` varchar(191) DEFAULT NULL,
  `paystack_status` tinyint(1) NOT NULL DEFAULT 0,
  `paystack_mode` enum('sandbox','live') NOT NULL DEFAULT 'sandbox',
  `test_paystack_key` varchar(191) DEFAULT NULL,
  `test_paystack_secret` varchar(191) DEFAULT NULL,
  `test_paystack_merchant_email` varchar(191) DEFAULT NULL,
  `paystack_payment_url` varchar(191) DEFAULT 'https://api.paystack.co',
  `xendit_status` tinyint(1) NOT NULL DEFAULT 0,
  `xendit_mode` enum('sandbox','live') NOT NULL DEFAULT 'sandbox',
  `test_xendit_public_key` varchar(191) DEFAULT NULL,
  `test_xendit_secret_key` varchar(191) DEFAULT NULL,
  `live_xendit_public_key` varchar(191) DEFAULT NULL,
  `live_xendit_secret_key` varchar(191) DEFAULT NULL,
  `test_xendit_webhook_token` varchar(191) DEFAULT NULL,
  `live_xendit_webhook_token` varchar(191) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payment_gateway_credentials`
--

INSERT INTO `payment_gateway_credentials` (`id`, `restaurant_id`, `razorpay_key`, `razorpay_secret`, `razorpay_status`, `stripe_key`, `stripe_secret`, `stripe_status`, `created_at`, `updated_at`, `is_dine_in_payment_enabled`, `is_delivery_payment_enabled`, `is_pickup_payment_enabled`, `is_cash_payment_enabled`, `is_qr_payment_enabled`, `is_offline_payment_enabled`, `offline_payment_detail`, `qr_code_image`, `flutterwave_status`, `flutterwave_mode`, `test_flutterwave_key`, `test_flutterwave_secret`, `test_flutterwave_hash`, `live_flutterwave_key`, `live_flutterwave_secret`, `live_flutterwave_hash`, `flutterwave_webhook_secret_hash`, `paypal_client_id`, `paypal_secret`, `paypal_status`, `paypal_mode`, `sandbox_paypal_client_id`, `sandbox_paypal_secret`, `payfast_merchant_id`, `payfast_merchant_key`, `payfast_passphrase`, `payfast_mode`, `payfast_status`, `test_payfast_merchant_id`, `test_payfast_merchant_key`, `test_payfast_passphrase`, `paystack_key`, `paystack_secret`, `paystack_merchant_email`, `paystack_status`, `paystack_mode`, `test_paystack_key`, `test_paystack_secret`, `test_paystack_merchant_email`, `paystack_payment_url`, `xendit_status`, `xendit_mode`, `test_xendit_public_key`, `test_xendit_secret_key`, `live_xendit_public_key`, `live_xendit_secret_key`, `test_xendit_webhook_token`, `live_xendit_webhook_token`) VALUES
(1, 1, NULL, NULL, 0, NULL, NULL, 0, '2025-11-02 00:36:11', '2025-11-02 00:36:11', 0, 0, 0, 0, 0, 0, NULL, NULL, 0, 'test', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 'sandbox', NULL, NULL, NULL, NULL, NULL, 'sandbox', 0, NULL, NULL, NULL, NULL, NULL, NULL, 0, 'sandbox', NULL, NULL, NULL, 'https://api.paystack.co', 0, 'sandbox', NULL, NULL, NULL, NULL, NULL, NULL),
(2, 1, NULL, NULL, 0, NULL, NULL, 0, '2025-11-02 00:36:11', '2025-11-02 00:36:11', 0, 0, 0, 0, 0, 0, NULL, NULL, 0, 'test', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 'sandbox', NULL, NULL, NULL, NULL, NULL, 'sandbox', 0, NULL, NULL, NULL, NULL, NULL, NULL, 0, 'sandbox', NULL, NULL, NULL, 'https://api.paystack.co', 0, 'sandbox', NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `paypal_payments`
--

CREATE TABLE `paypal_payments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `paypal_payment_id` varchar(191) DEFAULT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_status` enum('pending','completed','failed') NOT NULL DEFAULT 'pending',
  `payment_date` timestamp NULL DEFAULT NULL,
  `payment_error_response` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payment_error_response`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `paystack_payments`
--

CREATE TABLE `paystack_payments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `paystack_payment_id` varchar(191) DEFAULT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_status` enum('pending','completed','failed') NOT NULL DEFAULT 'pending',
  `payment_date` timestamp NULL DEFAULT NULL,
  `payment_error_response` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payment_error_response`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `guard_name` varchar(191) NOT NULL,
  `module_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `guard_name`, `module_id`, `created_at`, `updated_at`) VALUES
(1, 'Create Menu', 'web', 1, NULL, NULL),
(2, 'Show Menu', 'web', 1, NULL, NULL),
(3, 'Update Menu', 'web', 1, NULL, NULL),
(4, 'Delete Menu', 'web', 1, NULL, NULL),
(5, 'Create Menu Item', 'web', 2, NULL, NULL),
(6, 'Show Menu Item', 'web', 2, NULL, NULL),
(7, 'Update Menu Item', 'web', 2, NULL, NULL),
(8, 'Delete Menu Item', 'web', 2, NULL, NULL),
(9, 'Create Item Category', 'web', 3, NULL, NULL),
(10, 'Show Item Category', 'web', 3, NULL, NULL),
(11, 'Update Item Category', 'web', 3, NULL, NULL),
(12, 'Delete Item Category', 'web', 3, NULL, NULL),
(13, 'Create Area', 'web', 4, NULL, NULL),
(14, 'Show Area', 'web', 4, NULL, NULL),
(15, 'Update Area', 'web', 4, NULL, NULL),
(16, 'Delete Area', 'web', 4, NULL, NULL),
(17, 'Create Table', 'web', 5, NULL, NULL),
(18, 'Show Table', 'web', 5, NULL, NULL),
(19, 'Update Table', 'web', 5, NULL, NULL),
(20, 'Delete Table', 'web', 5, NULL, NULL),
(21, 'Create Reservation', 'web', 6, NULL, NULL),
(22, 'Show Reservation', 'web', 6, NULL, NULL),
(23, 'Update Reservation', 'web', 6, NULL, NULL),
(24, 'Delete Reservation', 'web', 6, NULL, NULL),
(25, 'Manage KOT', 'web', 7, NULL, NULL),
(26, 'Create Order', 'web', 8, NULL, NULL),
(27, 'Show Order', 'web', 8, NULL, NULL),
(28, 'Update Order', 'web', 8, NULL, NULL),
(29, 'Delete Order', 'web', 8, NULL, NULL),
(30, 'Create Customer', 'web', 9, NULL, NULL),
(31, 'Show Customer', 'web', 9, NULL, NULL),
(32, 'Update Customer', 'web', 9, NULL, NULL),
(33, 'Delete Customer', 'web', 9, NULL, NULL),
(34, 'Create Staff Member', 'web', 10, NULL, NULL),
(35, 'Show Staff Member', 'web', 10, NULL, NULL),
(36, 'Update Staff Member', 'web', 10, NULL, NULL),
(37, 'Delete Staff Member', 'web', 10, NULL, NULL),
(38, 'Create Delivery Executive', 'web', 14, NULL, NULL),
(39, 'Show Delivery Executive', 'web', 14, NULL, NULL),
(40, 'Update Delivery Executive', 'web', 14, NULL, NULL),
(41, 'Delete Delivery Executive', 'web', 14, NULL, NULL),
(42, 'Show Payments', 'web', 11, NULL, NULL),
(43, 'Show Reports', 'web', 12, NULL, NULL),
(44, 'Manage Settings', 'web', 13, NULL, NULL),
(45, 'Manage Waiter Request', 'web', 15, NULL, NULL),
(46, 'Create Expense', 'web', 16, NULL, NULL),
(47, 'Show Expense', 'web', 16, NULL, NULL),
(48, 'Update Expense', 'web', 16, NULL, NULL),
(49, 'Delete Expense', 'web', 16, NULL, NULL),
(50, 'Create Expense Category', 'web', 16, NULL, NULL),
(51, 'Show Expense Category', 'web', 16, NULL, NULL),
(52, 'Update Expense Category', 'web', 16, NULL, NULL),
(53, 'Delete Expense Category', 'web', 16, NULL, NULL),
(54, 'Create Inventory Item', 'web', 17, NULL, NULL),
(55, 'Show Inventory Item', 'web', 17, NULL, NULL),
(56, 'Update Inventory Item', 'web', 17, NULL, NULL),
(57, 'Delete Inventory Item', 'web', 17, NULL, NULL),
(58, 'Create Inventory Movement', 'web', 17, NULL, NULL),
(59, 'Show Inventory Movement', 'web', 17, NULL, NULL),
(60, 'Update Inventory Movement', 'web', 17, NULL, NULL),
(61, 'Delete Inventory Movement', 'web', 17, NULL, NULL),
(62, 'Show Inventory Stock', 'web', 17, NULL, NULL),
(63, 'Create Unit', 'web', 17, NULL, NULL),
(64, 'Show Unit', 'web', 17, NULL, NULL),
(65, 'Update Unit', 'web', 17, NULL, NULL),
(66, 'Delete Unit', 'web', 17, NULL, NULL),
(67, 'Create Recipe', 'web', 17, NULL, NULL),
(68, 'Show Recipe', 'web', 17, NULL, NULL),
(69, 'Update Recipe', 'web', 17, NULL, NULL),
(70, 'Delete Recipe', 'web', 17, NULL, NULL),
(71, 'Create Purchase Order', 'web', 17, NULL, NULL),
(72, 'Show Purchase Order', 'web', 17, NULL, NULL),
(73, 'Update Purchase Order', 'web', 17, NULL, NULL),
(74, 'Delete Purchase Order', 'web', 17, NULL, NULL),
(75, 'Show Inventory Report', 'web', 17, NULL, NULL),
(76, 'Update Inventory Settings', 'web', 17, NULL, NULL),
(77, 'Show Supplier', 'web', 17, NULL, NULL),
(78, 'Create Supplier', 'web', 17, NULL, NULL),
(79, 'Update Supplier', 'web', 17, NULL, NULL),
(80, 'Delete Supplier', 'web', 17, NULL, NULL),
(81, 'Manage Cash Register Settings', 'web', 18, '2025-11-03 01:26:57', '2025-11-03 01:26:59'),
(82, 'View Cash Register Reports', 'web', 18, '2025-11-03 01:26:57', '2025-11-03 01:26:57'),
(83, 'Manage Cash Denominations', 'web', 18, '2025-11-03 01:26:57', '2025-11-03 01:26:59'),
(84, 'Approve Cash Register', 'web', 18, '2025-11-03 01:26:57', '2025-11-03 01:26:57'),
(85, 'Open Cash Register', 'web', 18, '2025-11-03 01:26:58', '2025-11-03 01:26:59'),
(86, 'Show Kitchen Place', 'web', 20, '2025-11-04 02:05:18', '2025-11-04 02:05:18'),
(87, 'Create Kitchen Place', 'web', 20, '2025-11-04 02:05:18', '2025-11-04 02:05:18'),
(88, 'Update Kitchen Place', 'web', 20, '2025-11-04 02:05:18', '2025-11-04 02:05:18'),
(89, 'Delete Kitchen Place', 'web', 20, '2025-11-04 02:05:18', '2025-11-04 02:05:18');

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(191) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `predefined_amounts`
--

CREATE TABLE `predefined_amounts` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `restaurant_id` bigint(20) UNSIGNED NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `predefined_amounts`
--

INSERT INTO `predefined_amounts` (`id`, `restaurant_id`, `amount`, `created_at`, `updated_at`) VALUES
(1, 1, 100.00, '2025-11-02 00:36:11', '2025-11-08 22:18:01'),
(2, 1, 500.00, '2025-11-02 00:36:11', '2025-11-08 22:18:02'),
(3, 1, 1000.00, '2025-11-02 00:36:11', '2025-11-08 22:18:02'),
(4, 1, 5000.00, '2025-11-02 00:36:11', '2025-11-08 22:18:02');

-- --------------------------------------------------------

--
-- Table structure for table `printers`
--

CREATE TABLE `printers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `restaurant_id` bigint(20) UNSIGNED DEFAULT NULL,
  `branch_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(191) NOT NULL,
  `printing_choice` varchar(191) DEFAULT NULL,
  `kots` text DEFAULT NULL,
  `orders` text DEFAULT NULL,
  `print_format` varchar(191) DEFAULT NULL,
  `invoice_qr_code` int(11) DEFAULT NULL,
  `open_cash_drawer` enum('yes','no') DEFAULT NULL,
  `ipv4_address` varchar(191) DEFAULT NULL,
  `thermal_or_nonthermal` varchar(191) DEFAULT NULL,
  `share_name` varchar(191) DEFAULT NULL,
  `type` enum('network','windows','linux','default') DEFAULT NULL,
  `profile` enum('default','simple','SP2000','TEP-200M','P822D') DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `char_per_line` int(11) DEFAULT NULL,
  `ip_address` varchar(191) DEFAULT NULL,
  `port` int(11) DEFAULT NULL,
  `path` varchar(191) DEFAULT NULL,
  `printer_name` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `printers`
--

INSERT INTO `printers` (`id`, `restaurant_id`, `branch_id`, `name`, `printing_choice`, `kots`, `orders`, `print_format`, `invoice_qr_code`, `open_cash_drawer`, `ipv4_address`, `thermal_or_nonthermal`, `share_name`, `type`, `profile`, `is_active`, `is_default`, `char_per_line`, `ip_address`, `port`, `path`, `printer_name`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Default Thermal Printer', 'browserPopupPrint', '[1]', '[1]', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(2, 1, 2, 'Default Thermal Printer', 'browserPopupPrint', '[2]', '[2]', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, NULL, NULL, NULL, NULL, NULL, '2025-11-02 00:36:11', '2025-11-02 00:36:11');

-- --------------------------------------------------------

--
-- Table structure for table `print_jobs`
--

CREATE TABLE `print_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `printer_id` bigint(20) UNSIGNED DEFAULT NULL,
  `restaurant_id` bigint(20) UNSIGNED DEFAULT NULL,
  `branch_id` bigint(20) UNSIGNED DEFAULT NULL,
  `status` varchar(191) NOT NULL DEFAULT 'pending',
  `error` text DEFAULT NULL,
  `response_printer` varchar(191) DEFAULT NULL,
  `image_filename` varchar(191) DEFAULT NULL,
  `printed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `print_jobs`
--

INSERT INTO `print_jobs` (`id`, `printer_id`, `restaurant_id`, `branch_id`, `status`, `error`, `response_printer`, `image_filename`, `printed_at`, `created_at`, `updated_at`) VALUES
(1, 2, 1, 1, 'pending', NULL, NULL, 'x-report-1.png', NULL, '2025-11-10 08:00:56', '2025-11-10 08:00:56');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

CREATE TABLE `purchase_orders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `po_number` varchar(191) NOT NULL,
  `branch_id` bigint(20) UNSIGNED NOT NULL,
  `supplier_id` bigint(20) UNSIGNED NOT NULL,
  `order_date` date NOT NULL,
  `expected_delivery_date` date DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('draft','sent','received','partially_received','cancelled') NOT NULL DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order_items`
--

CREATE TABLE `purchase_order_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `purchase_order_id` bigint(20) UNSIGNED NOT NULL,
  `inventory_item_id` bigint(20) UNSIGNED NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `received_quantity` decimal(10,2) NOT NULL DEFAULT 0.00,
  `unit_price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pusher_settings`
--

CREATE TABLE `pusher_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `beamer_status` tinyint(1) NOT NULL DEFAULT 0,
  `instance_id` varchar(191) DEFAULT NULL,
  `beam_secret` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `pusher_broadcast` tinyint(1) NOT NULL DEFAULT 0,
  `pusher_app_id` varchar(191) DEFAULT NULL,
  `pusher_key` varchar(191) DEFAULT NULL,
  `pusher_secret` varchar(191) DEFAULT NULL,
  `pusher_cluster` varchar(191) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pusher_settings`
--

INSERT INTO `pusher_settings` (`id`, `beamer_status`, `instance_id`, `beam_secret`, `created_at`, `updated_at`, `pusher_broadcast`, `pusher_app_id`, `pusher_key`, `pusher_secret`, `pusher_cluster`) VALUES
(1, 0, NULL, NULL, '2025-11-02 00:35:57', '2025-11-02 00:35:57', 0, NULL, NULL, NULL, NULL),
(2, 0, NULL, NULL, '2025-11-02 00:36:11', '2025-11-02 00:36:11', 0, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `razorpay_payments`
--

CREATE TABLE `razorpay_payments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `payment_date` datetime DEFAULT NULL,
  `amount` decimal(16,2) DEFAULT NULL,
  `payment_status` enum('pending','requested','declined','completed') NOT NULL DEFAULT 'pending',
  `payment_error_response` text DEFAULT NULL,
  `razorpay_order_id` varchar(191) DEFAULT NULL,
  `razorpay_payment_id` varchar(191) DEFAULT NULL,
  `razorpay_signature` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `receipt_settings`
--

CREATE TABLE `receipt_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `restaurant_id` bigint(20) UNSIGNED DEFAULT NULL,
  `show_customer_name` tinyint(1) NOT NULL DEFAULT 0,
  `show_customer_address` tinyint(1) NOT NULL DEFAULT 0,
  `show_table_number` tinyint(1) NOT NULL DEFAULT 0,
  `payment_qr_code` varchar(191) DEFAULT NULL,
  `show_payment_qr_code` tinyint(1) NOT NULL DEFAULT 0,
  `show_waiter` tinyint(1) NOT NULL DEFAULT 0,
  `show_total_guest` tinyint(1) NOT NULL DEFAULT 0,
  `show_restaurant_logo` tinyint(1) NOT NULL DEFAULT 0,
  `show_tax` tinyint(1) NOT NULL DEFAULT 0,
  `show_payment_details` tinyint(1) NOT NULL DEFAULT 1,
  `show_order_type` tinyint(1) DEFAULT 0,
  `show_currency_prefix` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `receipt_settings`
--

INSERT INTO `receipt_settings` (`id`, `restaurant_id`, `show_customer_name`, `show_customer_address`, `show_table_number`, `payment_qr_code`, `show_payment_qr_code`, `show_waiter`, `show_total_guest`, `show_restaurant_logo`, `show_tax`, `show_payment_details`, `show_order_type`, `show_currency_prefix`, `created_at`, `updated_at`) VALUES
(1, 1, 0, 0, 0, 'eaf6f2562758569c3a6b9418c219a08b.png', 0, 0, 0, 1, 0, 1, 0, 0, '2025-11-02 00:36:11', '2025-11-08 05:11:54');

-- --------------------------------------------------------

--
-- Table structure for table `recipes`
--

CREATE TABLE `recipes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `menu_item_id` bigint(20) UNSIGNED DEFAULT NULL,
  `menu_item_variation_id` bigint(20) UNSIGNED DEFAULT NULL,
  `modifier_option_id` bigint(20) UNSIGNED DEFAULT NULL,
  `inventory_item_id` bigint(20) UNSIGNED NOT NULL,
  `quantity` decimal(16,2) NOT NULL DEFAULT 0.00,
  `unit_id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `branch_id` bigint(20) UNSIGNED DEFAULT NULL,
  `table_id` bigint(20) UNSIGNED DEFAULT NULL,
  `customer_id` bigint(20) UNSIGNED DEFAULT NULL,
  `reservation_date_time` datetime NOT NULL,
  `party_size` int(11) NOT NULL,
  `special_requests` text DEFAULT NULL,
  `reservation_status` enum('Pending','Confirmed','Checked_In','Cancelled','No_Show') NOT NULL DEFAULT 'Confirmed',
  `reservation_slot_type` enum('Breakfast','Lunch','Dinner') NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `slot_time_difference` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`id`, `branch_id`, `table_id`, `customer_id`, `reservation_date_time`, `party_size`, `special_requests`, `reservation_status`, `reservation_slot_type`, `created_at`, `updated_at`, `slot_time_difference`) VALUES
(1, 1, 1, 1, '2025-11-10 20:00:00', 4, NULL, 'Checked_In', 'Dinner', '2025-11-10 04:06:12', '2025-11-10 04:13:13', 60);

-- --------------------------------------------------------

--
-- Table structure for table `reservation_settings`
--

CREATE TABLE `reservation_settings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `branch_id` bigint(20) UNSIGNED DEFAULT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `time_slot_start` time NOT NULL,
  `time_slot_end` time NOT NULL,
  `time_slot_difference` int(11) NOT NULL,
  `slot_type` enum('Breakfast','Lunch','Dinner') NOT NULL,
  `available` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reservation_settings`
--

INSERT INTO `reservation_settings` (`id`, `branch_id`, `day_of_week`, `time_slot_start`, `time_slot_end`, `time_slot_difference`, `slot_type`, `available`, `created_at`, `updated_at`) VALUES
(1, 1, 'Monday', '08:00:00', '11:00:00', 30, 'Breakfast', 1, '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(2, 1, 'Monday', '12:00:00', '17:00:00', 60, 'Lunch', 1, '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(3, 1, 'Monday', '18:00:00', '22:00:00', 60, 'Dinner', 1, '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(4, 1, 'Tuesday', '08:00:00', '11:00:00', 30, 'Breakfast', 1, '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(5, 1, 'Tuesday', '12:00:00', '17:00:00', 60, 'Lunch', 1, '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(6, 1, 'Tuesday', '18:00:00', '22:00:00', 60, 'Dinner', 1, '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(7, 1, 'Wednesday', '08:00:00', '11:00:00', 30, 'Breakfast', 1, '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(8, 1, 'Wednesday', '12:00:00', '17:00:00', 60, 'Lunch', 1, '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(9, 1, 'Wednesday', '18:00:00', '22:00:00', 60, 'Dinner', 1, '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(10, 1, 'Thursday', '08:00:00', '11:00:00', 30, 'Breakfast', 1, '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(11, 1, 'Thursday', '12:00:00', '17:00:00', 60, 'Lunch', 1, '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(12, 1, 'Thursday', '18:00:00', '22:00:00', 60, 'Dinner', 1, '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(13, 1, 'Friday', '08:00:00', '11:00:00', 30, 'Breakfast', 1, '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(14, 1, 'Friday', '12:00:00', '17:00:00', 60, 'Lunch', 1, '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(15, 1, 'Friday', '18:00:00', '22:00:00', 60, 'Dinner', 1, '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(16, 1, 'Saturday', '08:00:00', '11:00:00', 30, 'Breakfast', 1, '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(17, 1, 'Saturday', '12:00:00', '17:00:00', 60, 'Lunch', 1, '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(18, 1, 'Saturday', '18:00:00', '22:00:00', 60, 'Dinner', 1, '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(19, 1, 'Sunday', '08:00:00', '11:00:00', 30, 'Breakfast', 1, '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(20, 1, 'Sunday', '12:00:00', '17:00:00', 60, 'Lunch', 1, '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(21, 1, 'Sunday', '18:00:00', '22:00:00', 60, 'Dinner', 1, '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(22, 2, 'Monday', '08:00:00', '11:00:00', 30, 'Breakfast', 1, '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(23, 2, 'Monday', '12:00:00', '17:00:00', 60, 'Lunch', 1, '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(24, 2, 'Monday', '18:00:00', '22:00:00', 60, 'Dinner', 1, '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(25, 2, 'Tuesday', '08:00:00', '11:00:00', 30, 'Breakfast', 1, '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(26, 2, 'Tuesday', '12:00:00', '17:00:00', 60, 'Lunch', 1, '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(27, 2, 'Tuesday', '18:00:00', '22:00:00', 60, 'Dinner', 1, '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(28, 2, 'Wednesday', '08:00:00', '11:00:00', 30, 'Breakfast', 1, '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(29, 2, 'Wednesday', '12:00:00', '17:00:00', 60, 'Lunch', 1, '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(30, 2, 'Wednesday', '18:00:00', '22:00:00', 60, 'Dinner', 1, '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(31, 2, 'Thursday', '08:00:00', '11:00:00', 30, 'Breakfast', 1, '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(32, 2, 'Thursday', '12:00:00', '17:00:00', 60, 'Lunch', 1, '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(33, 2, 'Thursday', '18:00:00', '22:00:00', 60, 'Dinner', 1, '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(34, 2, 'Friday', '08:00:00', '11:00:00', 30, 'Breakfast', 1, '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(35, 2, 'Friday', '12:00:00', '17:00:00', 60, 'Lunch', 1, '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(36, 2, 'Friday', '18:00:00', '22:00:00', 60, 'Dinner', 1, '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(37, 2, 'Saturday', '08:00:00', '11:00:00', 30, 'Breakfast', 1, '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(38, 2, 'Saturday', '12:00:00', '17:00:00', 60, 'Lunch', 1, '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(39, 2, 'Saturday', '18:00:00', '22:00:00', 60, 'Dinner', 1, '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(40, 2, 'Sunday', '08:00:00', '11:00:00', 30, 'Breakfast', 1, '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(41, 2, 'Sunday', '12:00:00', '17:00:00', 60, 'Lunch', 1, '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(42, 2, 'Sunday', '18:00:00', '22:00:00', 60, 'Dinner', 1, '2025-11-02 00:36:11', '2025-11-02 00:36:11'),
(43, 1, 'Monday', '08:00:00', '11:00:00', 30, 'Breakfast', 1, '2025-11-02 00:36:12', '2025-11-02 00:36:12'),
(44, 1, 'Monday', '12:00:00', '17:00:00', 60, 'Lunch', 1, '2025-11-02 00:36:12', '2025-11-02 00:36:12'),
(45, 1, 'Monday', '18:00:00', '22:00:00', 60, 'Dinner', 1, '2025-11-02 00:36:12', '2025-11-02 00:36:12'),
(46, 1, 'Tuesday', '08:00:00', '11:00:00', 30, 'Breakfast', 1, '2025-11-02 00:36:12', '2025-11-02 00:36:12'),
(47, 1, 'Tuesday', '12:00:00', '17:00:00', 60, 'Lunch', 1, '2025-11-02 00:36:12', '2025-11-02 00:36:12'),
(48, 1, 'Tuesday', '18:00:00', '22:00:00', 60, 'Dinner', 1, '2025-11-02 00:36:12', '2025-11-02 00:36:12'),
(49, 1, 'Wednesday', '08:00:00', '11:00:00', 30, 'Breakfast', 1, '2025-11-02 00:36:12', '2025-11-02 00:36:12'),
(50, 1, 'Wednesday', '12:00:00', '17:00:00', 60, 'Lunch', 1, '2025-11-02 00:36:12', '2025-11-02 00:36:12'),
(51, 1, 'Wednesday', '18:00:00', '22:00:00', 60, 'Dinner', 1, '2025-11-02 00:36:12', '2025-11-02 00:36:12'),
(52, 1, 'Thursday', '08:00:00', '11:00:00', 30, 'Breakfast', 1, '2025-11-02 00:36:12', '2025-11-02 00:36:12'),
(53, 1, 'Thursday', '12:00:00', '17:00:00', 60, 'Lunch', 1, '2025-11-02 00:36:12', '2025-11-02 00:36:12'),
(54, 1, 'Thursday', '18:00:00', '22:00:00', 60, 'Dinner', 1, '2025-11-02 00:36:12', '2025-11-02 00:36:12'),
(55, 1, 'Friday', '08:00:00', '11:00:00', 30, 'Breakfast', 1, '2025-11-02 00:36:12', '2025-11-02 00:36:12'),
(56, 1, 'Friday', '12:00:00', '17:00:00', 60, 'Lunch', 1, '2025-11-02 00:36:12', '2025-11-02 00:36:12'),
(57, 1, 'Friday', '18:00:00', '22:00:00', 60, 'Dinner', 1, '2025-11-02 00:36:12', '2025-11-02 00:36:12'),
(58, 1, 'Saturday', '08:00:00', '11:00:00', 30, 'Breakfast', 1, '2025-11-02 00:36:12', '2025-11-02 00:36:12'),
(59, 1, 'Saturday', '12:00:00', '17:00:00', 60, 'Lunch', 1, '2025-11-02 00:36:12', '2025-11-02 00:36:12'),
(60, 1, 'Saturday', '18:00:00', '22:00:00', 60, 'Dinner', 1, '2025-11-02 00:36:12', '2025-11-02 00:36:12'),
(61, 1, 'Sunday', '08:00:00', '11:00:00', 30, 'Breakfast', 1, '2025-11-02 00:36:12', '2025-11-02 00:36:12'),
(62, 1, 'Sunday', '12:00:00', '17:00:00', 60, 'Lunch', 1, '2025-11-02 00:36:12', '2025-11-02 00:36:12'),
(63, 1, 'Sunday', '18:00:00', '22:00:00', 60, 'Dinner', 1, '2025-11-02 00:36:12', '2025-11-02 00:36:12');

-- --------------------------------------------------------

--
-- Table structure for table `restaurants`
--

CREATE TABLE `restaurants` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `hash` varchar(191) DEFAULT NULL,
  `address` varchar(191) DEFAULT NULL,
  `phone_number` varchar(191) DEFAULT NULL,
  `phone_code` varchar(191) DEFAULT NULL,
  `email` varchar(191) DEFAULT NULL,
  `timezone` varchar(191) NOT NULL,
  `theme_hex` varchar(191) NOT NULL,
  `theme_rgb` varchar(191) NOT NULL,
  `logo` varchar(191) DEFAULT NULL,
  `country_id` bigint(20) UNSIGNED NOT NULL,
  `hide_new_orders` tinyint(1) NOT NULL DEFAULT 0,
  `hide_new_reservations` tinyint(1) NOT NULL DEFAULT 0,
  `hide_new_waiter_request` tinyint(1) NOT NULL DEFAULT 0,
  `currency_id` bigint(20) UNSIGNED DEFAULT NULL,
  `license_type` enum('free','paid') NOT NULL DEFAULT 'free',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `customer_login_required` tinyint(1) NOT NULL DEFAULT 0,
  `about_us` longtext DEFAULT NULL,
  `allow_customer_delivery_orders` tinyint(1) NOT NULL DEFAULT 1,
  `allow_customer_pickup_orders` tinyint(1) NOT NULL DEFAULT 1,
  `pickup_days_range` int(11) DEFAULT 7,
  `allow_customer_orders` tinyint(1) NOT NULL DEFAULT 1,
  `allow_dine_in_orders` tinyint(1) NOT NULL DEFAULT 1,
  `show_veg` tinyint(1) NOT NULL DEFAULT 1,
  `show_halal` tinyint(1) NOT NULL DEFAULT 0,
  `package_id` bigint(20) UNSIGNED DEFAULT NULL,
  `package_type` varchar(191) DEFAULT NULL,
  `status` enum('active','inactive','license_expired') NOT NULL DEFAULT 'active',
  `license_expire_on` datetime DEFAULT NULL,
  `trial_ends_at` datetime DEFAULT NULL,
  `license_updated_at` datetime DEFAULT NULL,
  `subscription_updated_at` datetime DEFAULT NULL,
  `stripe_id` varchar(191) DEFAULT NULL,
  `pm_type` varchar(191) DEFAULT NULL,
  `pm_last_four` varchar(4) DEFAULT NULL,
  `is_waiter_request_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `default_table_reservation_status` varchar(191) NOT NULL DEFAULT 'Confirmed',
  `disable_slot_minutes` int(11) NOT NULL DEFAULT 30,
  `approval_status` enum('Pending','Approved','Rejected') NOT NULL DEFAULT 'Approved',
  `rejection_reason` text DEFAULT NULL,
  `facebook_link` varchar(255) DEFAULT NULL,
  `instagram_link` varchar(255) DEFAULT NULL,
  `twitter_link` varchar(255) DEFAULT NULL,
  `yelp_link` varchar(255) DEFAULT NULL,
  `table_required` tinyint(1) NOT NULL DEFAULT 0,
  `show_logo_text` tinyint(1) NOT NULL DEFAULT 1,
  `meta_keyword` text DEFAULT NULL,
  `meta_description` longtext DEFAULT NULL,
  `upload_fav_icon_android_chrome_192` varchar(191) DEFAULT NULL,
  `upload_fav_icon_android_chrome_512` varchar(191) DEFAULT NULL,
  `upload_fav_icon_apple_touch_icon` varchar(191) DEFAULT NULL,
  `upload_favicon_16` varchar(191) DEFAULT NULL,
  `upload_favicon_32` varchar(191) DEFAULT NULL,
  `favicon` varchar(191) DEFAULT NULL,
  `is_waiter_request_enabled_on_desktop` tinyint(1) NOT NULL DEFAULT 1,
  `is_waiter_request_enabled_on_mobile` tinyint(1) NOT NULL DEFAULT 1,
  `is_waiter_request_enabled_open_by_qr` tinyint(1) NOT NULL DEFAULT 0,
  `webmanifest` varchar(191) DEFAULT NULL,
  `enable_tip_shop` tinyint(1) NOT NULL DEFAULT 1,
  `enable_tip_pos` tinyint(1) NOT NULL DEFAULT 1,
  `is_pwa_install_alert_show` tinyint(1) NOT NULL DEFAULT 0,
  `auto_confirm_orders` tinyint(1) NOT NULL DEFAULT 0,
  `show_order_type_options` tinyint(1) NOT NULL DEFAULT 1,
  `hide_menu_item_image_on_pos` tinyint(1) NOT NULL DEFAULT 0,
  `hide_menu_item_image_on_customer_site` tinyint(1) NOT NULL DEFAULT 0,
  `tax_mode` enum('order','item') NOT NULL DEFAULT 'order',
  `tax_inclusive` tinyint(1) NOT NULL DEFAULT 0,
  `customer_site_language` varchar(191) DEFAULT NULL,
  `enable_admin_reservation` tinyint(1) NOT NULL DEFAULT 1,
  `enable_customer_reservation` tinyint(1) NOT NULL DEFAULT 1,
  `minimum_party_size` int(11) NOT NULL DEFAULT 1,
  `table_lock_timeout_minutes` int(11) NOT NULL DEFAULT 10
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `restaurants`
--

INSERT INTO `restaurants` (`id`, `name`, `hash`, `address`, `phone_number`, `phone_code`, `email`, `timezone`, `theme_hex`, `theme_rgb`, `logo`, `country_id`, `hide_new_orders`, `hide_new_reservations`, `hide_new_waiter_request`, `currency_id`, `license_type`, `is_active`, `created_at`, `updated_at`, `customer_login_required`, `about_us`, `allow_customer_delivery_orders`, `allow_customer_pickup_orders`, `pickup_days_range`, `allow_customer_orders`, `allow_dine_in_orders`, `show_veg`, `show_halal`, `package_id`, `package_type`, `status`, `license_expire_on`, `trial_ends_at`, `license_updated_at`, `subscription_updated_at`, `stripe_id`, `pm_type`, `pm_last_four`, `is_waiter_request_enabled`, `default_table_reservation_status`, `disable_slot_minutes`, `approval_status`, `rejection_reason`, `facebook_link`, `instagram_link`, `twitter_link`, `yelp_link`, `table_required`, `show_logo_text`, `meta_keyword`, `meta_description`, `upload_fav_icon_android_chrome_192`, `upload_fav_icon_android_chrome_512`, `upload_fav_icon_apple_touch_icon`, `upload_favicon_16`, `upload_favicon_32`, `favicon`, `is_waiter_request_enabled_on_desktop`, `is_waiter_request_enabled_on_mobile`, `is_waiter_request_enabled_open_by_qr`, `webmanifest`, `enable_tip_shop`, `enable_tip_pos`, `is_pwa_install_alert_show`, `auto_confirm_orders`, `show_order_type_options`, `hide_menu_item_image_on_pos`, `hide_menu_item_image_on_customer_site`, `tax_mode`, `tax_inclusive`, `customer_site_language`, `enable_admin_reservation`, `enable_customer_reservation`, `minimum_party_size`, `table_lock_timeout_minutes`) VALUES
(1, 'Mr Chai', 'mr-chai', 'Main Street, Oluvil 32360', '074 394 2464', '94', 'mrchai@gmail.com', 'Asia/Colombo', '#F97316', '249, 115, 22', '34b66a225d9e96407843ae519bc43f59.jpg', 210, 0, 0, 0, 5, 'free', 1, '2025-11-02 00:36:11', '2025-11-04 00:25:10', 1, '<p class=\"text-lg text-gray-600 mb-6\">\n          Welcome to our restaurant, where great food and good vibes come together! We\'re a local, family-owned spot that loves bringing people together over delicious meals and unforgettable moments. Whether you\'re here for a quick bite, a family dinner, or a celebration, we\'re all about making your time with us special.\n        </p>\n        <p class=\"text-lg text-gray-600 mb-6\">\n          Our menu is packed with dishes made from fresh, quality ingredients because we believe food should taste as\n          good as it makes you feel. From our signature dishes to seasonal specials, there\'s always something to excite\n          your taste buds.\n        </p>\n        <p class=\"text-lg text-gray-600 mb-6\">\n          But we\'re not just about the food—we\'re about community. We love seeing familiar faces and welcoming new ones.\n          Our team is a fun, friendly bunch dedicated to serving you with a smile and making sure every visit feels like\n          coming home.\n        </p>\n        <p class=\"text-lg text-gray-600\">\n          So, come on in, grab a seat, and let us take care of the rest. We can\'t wait to share our love of food with\n          you!\n        </p>\n        <p class=\"text-lg text-gray-800 font-semibold mt-6\">See you soon! 🍽️✨</p>', 1, 1, 7, 1, 1, 1, 0, 3, 'lifetime', 'active', NULL, NULL, '2025-11-02 06:06:11', '2025-11-02 06:06:11', NULL, NULL, NULL, 1, 'Confirmed', 30, 'Approved', NULL, 'https://www.facebook.com/', 'https://www.instagram.com/', 'https://www.twitter.com/', NULL, 0, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 0, NULL, 1, 1, 1, 0, 1, 0, 0, 'order', 0, 'en', 1, 1, 1, 10);

-- --------------------------------------------------------

--
-- Table structure for table `restaurant_charges`
--

CREATE TABLE `restaurant_charges` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `restaurant_id` bigint(20) UNSIGNED DEFAULT NULL,
  `charge_name` varchar(191) NOT NULL,
  `charge_type` enum('percent','fixed') NOT NULL DEFAULT 'fixed',
  `charge_value` decimal(16,2) DEFAULT NULL,
  `order_types` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Supported order types: DineIn, Delivery, PickUp' CHECK (json_valid(`order_types`)),
  `is_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `restaurant_payments`
--

CREATE TABLE `restaurant_payments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `restaurant_id` bigint(20) UNSIGNED NOT NULL,
  `amount` decimal(16,2) NOT NULL,
  `status` enum('pending','paid','failed') NOT NULL DEFAULT 'pending',
  `payment_source` enum('official_site','app_sumo') NOT NULL DEFAULT 'official_site',
  `razorpay_order_id` varchar(191) DEFAULT NULL,
  `razorpay_payment_id` varchar(191) DEFAULT NULL,
  `razorpay_signature` varchar(191) DEFAULT NULL,
  `transaction_id` varchar(191) DEFAULT NULL,
  `reference_id` varchar(191) DEFAULT NULL,
  `payment_date_time` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `stripe_payment_intent` varchar(191) DEFAULT NULL,
  `stripe_session_id` text DEFAULT NULL,
  `package_id` bigint(20) UNSIGNED DEFAULT NULL,
  `package_type` varchar(191) DEFAULT NULL,
  `currency_id` varchar(191) DEFAULT NULL,
  `flutterwave_transaction_id` varchar(191) DEFAULT NULL,
  `flutterwave_payment_ref` varchar(191) DEFAULT NULL,
  `paypal_payment_id` varchar(191) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `restaurant_taxes`
--

CREATE TABLE `restaurant_taxes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `restaurant_id` bigint(20) UNSIGNED NOT NULL,
  `tax_id` varchar(191) DEFAULT NULL,
  `tax_name` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `display_name` varchar(191) DEFAULT NULL,
  `guard_name` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `restaurant_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `display_name`, `guard_name`, `created_at`, `updated_at`, `restaurant_id`) VALUES
(1, 'Super Admin', 'Super Admin', 'web', '2025-11-02 00:36:10', '2025-11-02 00:36:10', NULL),
(2, 'Admin_1', 'Admin', 'web', '2025-11-02 00:36:11', '2025-11-02 00:36:11', 1),
(3, 'Branch Head_1', 'Branch Head', 'web', '2025-11-02 00:36:11', '2025-11-02 00:36:11', 1),
(4, 'Waiter_1', 'Waiter', 'web', '2025-11-02 00:36:11', '2025-11-02 00:36:11', 1),
(5, 'Chef_1', 'Chef', 'web', '2025-11-02 00:36:11', '2025-11-02 00:36:11', 1),
(6, 'Cashier_1', 'Cashier', 'web', '2025-11-10 04:39:28', '2025-11-10 04:39:28', 1);

-- --------------------------------------------------------

--
-- Table structure for table `role_has_permissions`
--

CREATE TABLE `role_has_permissions` (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_has_permissions`
--

INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES
(1, 2),
(1, 3),
(2, 2),
(2, 3),
(3, 2),
(3, 3),
(4, 2),
(4, 3),
(5, 2),
(5, 3),
(6, 2),
(6, 3),
(7, 2),
(7, 3),
(8, 2),
(8, 3),
(9, 2),
(9, 3),
(10, 2),
(10, 3),
(11, 2),
(11, 3),
(12, 2),
(12, 3),
(13, 2),
(13, 3),
(14, 2),
(14, 3),
(15, 2),
(15, 3),
(16, 2),
(16, 3),
(17, 2),
(17, 3),
(17, 6),
(18, 2),
(18, 3),
(18, 6),
(19, 2),
(19, 3),
(19, 6),
(20, 2),
(20, 3),
(20, 6),
(21, 2),
(21, 3),
(21, 6),
(22, 2),
(22, 3),
(22, 6),
(23, 2),
(23, 3),
(23, 6),
(24, 2),
(24, 3),
(24, 6),
(25, 2),
(25, 3),
(25, 5),
(25, 6),
(26, 2),
(26, 3),
(26, 6),
(27, 2),
(27, 3),
(27, 6),
(28, 2),
(28, 3),
(28, 6),
(29, 2),
(29, 3),
(29, 6),
(30, 2),
(30, 3),
(30, 6),
(31, 2),
(31, 3),
(31, 6),
(32, 2),
(32, 3),
(32, 6),
(33, 2),
(33, 3),
(33, 6),
(34, 2),
(34, 3),
(35, 2),
(35, 3),
(36, 2),
(36, 3),
(37, 2),
(37, 3),
(38, 2),
(38, 3),
(39, 2),
(39, 3),
(40, 2),
(40, 3),
(41, 2),
(41, 3),
(42, 2),
(42, 3),
(43, 2),
(43, 3),
(44, 2),
(44, 3),
(45, 2),
(45, 3),
(46, 2),
(46, 3),
(47, 2),
(47, 3),
(48, 2),
(48, 3),
(49, 2),
(49, 3),
(50, 2),
(50, 3),
(51, 2),
(51, 3),
(52, 2),
(52, 3),
(53, 2),
(53, 3),
(54, 2),
(54, 3),
(55, 2),
(55, 3),
(56, 2),
(56, 3),
(57, 2),
(57, 3),
(58, 2),
(58, 3),
(59, 2),
(59, 3),
(60, 2),
(60, 3),
(61, 2),
(61, 3),
(62, 2),
(62, 3),
(63, 2),
(63, 3),
(64, 2),
(64, 3),
(65, 2),
(65, 3),
(66, 2),
(66, 3),
(67, 2),
(67, 3),
(68, 2),
(68, 3),
(69, 2),
(69, 3),
(70, 2),
(70, 3),
(71, 2),
(71, 3),
(72, 2),
(72, 3),
(73, 2),
(73, 3),
(74, 2),
(74, 3),
(75, 2),
(75, 3),
(76, 2),
(76, 3),
(77, 2),
(77, 3),
(78, 2),
(78, 3),
(79, 2),
(79, 3),
(80, 2),
(80, 3),
(81, 2),
(81, 3),
(82, 2),
(82, 3),
(83, 2),
(83, 3),
(84, 2),
(84, 3),
(85, 2),
(85, 3),
(86, 2),
(86, 3),
(86, 5),
(87, 2),
(87, 3),
(88, 2),
(88, 3),
(89, 2),
(89, 3);

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(191) NOT NULL,
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
('5to1IozPnut3OgbHn6btq36JJT4nsgd93RjE2g5C', 4, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'YToxMzp7czo2OiJfdG9rZW4iO3M6NDA6InRlRUg2NHhGNFZvM0ZjaHY1eVVyMFNpUkxuQUMzcmw1cldYbjdwM0IiO3M6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6MjA6ImNoZWNrX21pZ3JhdGVfc3RhdHVzIjtzOjQ6Ikdvb2QiO3M6MTU6ImN1c3RvbWVyX2lzX3J0bCI7aTowO3M6NDoidXNlciI7TzoxNToiQXBwXE1vZGVsc1xVc2VyIjozNjp7czoxMzoiACoAY29ubmVjdGlvbiI7czo1OiJteXNxbCI7czo4OiIAKgB0YWJsZSI7czo1OiJ1c2VycyI7czoxMzoiACoAcHJpbWFyeUtleSI7czoyOiJpZCI7czoxMDoiACoAa2V5VHlwZSI7czozOiJpbnQiO3M6MTI6ImluY3JlbWVudGluZyI7YjoxO3M6NzoiACoAd2l0aCI7YTowOnt9czoxMjoiACoAd2l0aENvdW50IjthOjA6e31zOjE5OiJwcmV2ZW50c0xhenlMb2FkaW5nIjtiOjA7czoxMDoiACoAcGVyUGFnZSI7aToxNTtzOjY6ImV4aXN0cyI7YjoxO3M6MTg6Indhc1JlY2VudGx5Q3JlYXRlZCI7YjowO3M6Mjg6IgAqAGVzY2FwZVdoZW5DYXN0aW5nVG9TdHJpbmciO2I6MDtzOjEzOiIAKgBhdHRyaWJ1dGVzIjthOjI1OntzOjI6ImlkIjtpOjQ7czoxMzoicmVzdGF1cmFudF9pZCI7aToxO3M6OToiYnJhbmNoX2lkIjtpOjE7czo0OiJuYW1lIjtzOjY6IkphcmF0aCI7czo1OiJlbWFpbCI7czoxNToiamFyYXRoQG1haWwuY29tIjtzOjEyOiJwaG9uZV9udW1iZXIiO047czoxMDoicGhvbmVfY29kZSI7TjtzOjI2OiJ0ZXJtc19hbmRfcHJpdmFjeV9hY2NlcHRlZCI7aTowO3M6MjU6Im1hcmtldGluZ19lbWFpbHNfYWNjZXB0ZWQiO2k6MDtzOjE3OiJlbWFpbF92ZXJpZmllZF9hdCI7TjtzOjg6InBhc3N3b3JkIjtzOjYwOiIkMnkkMTIkNnZXZ2N3dTI0UU52b3dIb2puNnV3ZTVKbmtIa1ovUThMNWJoRC9BNy9Fck5tYmoxLzcxaE8iO3M6MTc6InR3b19mYWN0b3Jfc2VjcmV0IjtOO3M6MjU6InR3b19mYWN0b3JfcmVjb3ZlcnlfY29kZXMiO047czoyMzoidHdvX2ZhY3Rvcl9jb25maXJtZWRfYXQiO047czoxNDoicmVtZW1iZXJfdG9rZW4iO047czoxNToiY3VycmVudF90ZWFtX2lkIjtOO3M6MTg6InByb2ZpbGVfcGhvdG9fcGF0aCI7TjtzOjEwOiJjcmVhdGVkX2F0IjtzOjE5OiIyMDI1LTExLTEwIDEzOjA0OjQ5IjtzOjEwOiJ1cGRhdGVkX2F0IjtzOjE5OiIyMDI1LTExLTEwIDEzOjA0OjQ5IjtzOjY6ImxvY2FsZSI7czoyOiJlbiI7czo5OiJzdHJpcGVfaWQiO047czo3OiJwbV90eXBlIjtOO3M6MTI6InBtX2xhc3RfZm91ciI7TjtzOjEzOiJ0cmlhbF9lbmRzX2F0IjtOO3M6MTA6ImtpdGNoZW5faWQiO2k6MTt9czoxMToiACoAb3JpZ2luYWwiO2E6MjU6e3M6MjoiaWQiO2k6NDtzOjEzOiJyZXN0YXVyYW50X2lkIjtpOjE7czo5OiJicmFuY2hfaWQiO2k6MTtzOjQ6Im5hbWUiO3M6NjoiSmFyYXRoIjtzOjU6ImVtYWlsIjtzOjE1OiJqYXJhdGhAbWFpbC5jb20iO3M6MTI6InBob25lX251bWJlciI7TjtzOjEwOiJwaG9uZV9jb2RlIjtOO3M6MjY6InRlcm1zX2FuZF9wcml2YWN5X2FjY2VwdGVkIjtpOjA7czoyNToibWFya2V0aW5nX2VtYWlsc19hY2NlcHRlZCI7aTowO3M6MTc6ImVtYWlsX3ZlcmlmaWVkX2F0IjtOO3M6ODoicGFzc3dvcmQiO3M6NjA6IiQyeSQxMiQ2dldnY3d1MjRRTnZvd0hvam42dXdlNUpua0hrWi9ROEw1YmhEL0E3L0VyTm1iajEvNzFoTyI7czoxNzoidHdvX2ZhY3Rvcl9zZWNyZXQiO047czoyNToidHdvX2ZhY3Rvcl9yZWNvdmVyeV9jb2RlcyI7TjtzOjIzOiJ0d29fZmFjdG9yX2NvbmZpcm1lZF9hdCI7TjtzOjE0OiJyZW1lbWJlcl90b2tlbiI7TjtzOjE1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aGVuQ2FzdGluZ1RvU3RyaW5nIjtiOjA7czoxMzoiACoAYXR0cmlidXRlcyI7YTowOnt9czoxMToiACoAb3JpZ2luYWwiO2E6MDp7fXM6MTA6IgAqAGNoYW5nZXMiO2E6MDp7fXM6MTE6IgAqAHByZXZpb3VzIjthOjA6e31zOjg6IgAqAGNhc3RzIjthOjc6e3M6MTc6ImVtYWlsX3ZlcmlmaWVkX2F0IjtzOjg6ImRhdGV0aW1lIjtzOjg6InBhc3N3b3JkIjtzOjY6Imhhc2hlZCI7czoxMzoicmVzdGF1cmFudF9pZCI7czo3OiJpbnRlZ2VyIjtzOjk6ImJyYW5jaF9pZCI7czo3OiJpbnRlZ2VyIjtzOjEwOiJraXRjaGVuX2lkIjtzOjc6ImludGVnZXIiO3M6MjY6InRlcm1zX2FuZF9wcml2YWN5X2FjY2VwdGVkIjtzOjc6ImJvb2xlYW4iO3M6MjU6Im1hcmtldGluZ19lbWFpbHNfYWNjZXB0ZWQiO3M6NzoiYm9vbGVhbiI7fXM6MTc6IgAqAGNsYXNzQ2FzdENhY2hlIjthOjA6e31zOjIxOiIAKgBhdHRyaWJ1dGVDYXN0Q2FjaGUiO2E6MDp7fXM6MTM6IgAqAGRhdGVGb3JtYXQiO047czoxMDoiACoAYXBwZW5kcyI7YToxOntpOjA7czoxNzoicHJvZmlsZV9waG90b191cmwiO31zOjE5OiIAKgBkaXNwYXRjaGVzRXZlbnRzIjthOjA6e31zOjE0OiIAKgBvYnNlcnZhYmxlcyI7YTowOnt9czoxMjoiACoAcmVsYXRpb25zIjthOjA6e31zOjEwOiIAKgB0b3VjaGVzIjthOjA6e31zOjI3OiIAKgByZWxhdGlvbkF1dG9sb2FkQ2FsbGJhY2siO047czoyNjoiACoAcmVsYXRpb25BdXRvbG9hZENvbnRleHQiO047czoxMDoidGltZXN0YW1wcyI7YjoxO3M6MTM6InVzZXNVbmlxdWVJZHMiO2I6MDtzOjk6IgAqAGhpZGRlbiI7YTo0OntpOjA7czo4OiJwYXNzd29yZCI7aToxO3M6MTQ6InJlbWVtYmVyX3Rva2VuIjtpOjI7czoyNToidHdvX2ZhY3Rvcl9yZWNvdmVyeV9jb2RlcyI7aTozO3M6MTc6InR3b19mYWN0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b3VudHJ5X2lkIjtpOjIxMDtzOjE1OiJoaWRlX25ld19vcmRlcnMiO2k6MDtzOjIxOiJoaWRlX25ld19yZXNlcnZhdGlvbnMiO2k6MDtzOjIzOiJoaWRlX25ld193YWl0ZXJfcmVxdWVzdCI7aTowO3M6MTE6ImN1cnJlbmN5X2lkIjtpOjU7czoxMjoibGljZW5zZV90eXBlIjtzOjQ6ImZyZWUiO3M6OToiaXNfYWN0aXZlIjtpOjE7czoxMDoiY3JlYXRlZF9hdCI7czoxOToiMjAyNS0xMS0wMiAwNjowNjoxMSI7czoxMDoidXBkYXRlZF9hdCI7czoxOToiMjAyNS0xMS0wNCAwNTo1NToxMCI7czoyMzoiY3VzdG9tZXJfbG9naW5fcmVxdWlyZWQiO2k6MTtzOjg6ImFib3V0X3VzIjtzOjEzMDQ6IjxwIGNsYXNzPSJ0ZXh0LWxnIHRleHQtZ3JheS02MDAgbWItNiI+CiAgICAgICAgICBXZWxjb21lIHRvIG91ciByZXN0YXVyYW50LCB3aGVyZSBncmVhdCBmb29kIGFuZCBnb29kIHZpYmVzIGNvbWUgdG9nZXRoZXIhIFdlJ3JlIGEgbG9jYWwsIGZhbWlseS1vd25lZCBzcG90IHRoYXQgbG92ZXMgYnJpbmdpbmcgcGVvcGxlIHRvZ2V0aGVyIG92ZXIgZGVsaWNpb3VzIG1lYWxzIGFuZCB1bmZvcmdldHRhYmxlIG1vbWVudHMuIFdoZXRoZXIgeW91J3JlIGhlcmUgZm9yIGEgcXVpY2sgYml0ZSwgYSBmYW1pbHkgZGlubmVyLCBvciBhIGNlbGVicmF0aW9uLCB3ZSdyZSBhbGwgYWJvdXQgbWFraW5nIHlvdXIgdGltZSB3aXRoIHVzIHNwZWNpYWwuCiAgICAgICAgPC9wPgogICAgICAgIDxwIGNsYXNzPSJ0ZXh0LWxnIHRleHQtZ3JheS02MDAgbWItNiI+CiAgICAgICAgICBPdXIgbWVudSBpcyBwYWNrZWQgd2l0aCBkaXNoZXMgbWFkZSBmcm9tIGZyZXNoLCBxdWFsaXR5IGluZ3JlZGllbnRzIGJlY2F1c2Ugd2UgYmVsaWV2ZSBmb29kIHNob3VsZCB0YXN0ZSBhcwogICAgICAgICAgZ29vZCBhcyBpdCBtYWtlcyB5b3UgZmVlbC4gRnJvbSBvdXIgc2lnbmF0dXJlIGRpc2hlcyB0byBzZWFzb25hbCBzcGVjaWFscywgdGhlcmUncyBhbHdheXMgc29tZXRoaW5nIHRvIGV4Y2l0ZQogICAgICAgICAgeW91ciB0YXN0ZSBidWRzLgogICAgICAgIDwvcD4KICAgICAgICA8cCBjbGFzcz0idGV4dC1sZyB0ZXh0LWdyYXktNjAwIG1iLTYiPgogICAgICAgICAgQnV0IHdlJ3JlIG5vdCBqdXN0IGFib3V0IHRoZSBmb29k4oCUd2UncmUgYWJvdXQgY29tbXVuaXR5LiBXZSBsb3ZlIHNlZWluZyBmYW1pbGlhciBmYWNlcyBhbmQgd2VsY29taW5nIG5ldyBvbmVzLgogICAgICAgICAgT3VyIHRlYW0gaXMgYSBmdW4sIGZyaWVuZGx5IGJ1bmNoIGRlZGljYXRlZCB0byBzZXJ2aW5nIHlvdSB3aXRoIGEgc21pbGUgYW5kIG1ha2luZyBzdXJlIGV2ZXJ5IHZpc2l0IGZlZWxzIGxpa2UKICAgICAgICAgIGNvbWluZyBob21lLgogICAgICAgIDwvcD4KICAgICAgICA8cCBjbGFzcz0idGV4dC1sZyB0ZXh0LWdyYXktNjAwIj4KICAgICAgICAgIFNvLCBjb21lIG9uIGluLCBncmFiIGEgc2VhdCwgYW5kIGxldCB1cyB0YWtlIGNhcmUgb2YgdGhlIHJlc3QuIFdlIGNhbid0IHdhaXQgdG8gc2hhcmUgb3VyIGxvdmUgb2YgZm9vZCB3aXRoCiAgICAgICAgICB5b3UhCiAgICAgICAgPC9wPgogICAgICAgIDxwIGNsYXNzPSJ0ZXh0LWxnIHRleHQtZ3JheS04MDAgZm9udC1zZW1pYm9sZCBtdC02Ij5TZWUgeW91IHNvb24hIPCfjb3vuI/inKg8L3A+IjtzOjMwOiJhbGxvd19jdXN0b21lcl9kZWxpdmVyeV9vcmRlcnMiO2k6MTtzOjI4OiJhbGxvd19jdXN0b21lcl9waWNrdXBfb3JkZXJzIjtpOjE7czoxNzoicGlja3VwX2RheXNfcmFuZ2UiO2k6NztzOjIxOiJhbGxvd19jdXN0b21lcl9vcmRlcnMiO2k6MTtzOjIwOiJhbGxvd19kaW5lX2luX29yZGVycyI7aToxO3M6ODoic2hvd192ZWciO2k6MTtzOjEwOiJzaG93X2hhbGFsIjtpOjA7czoxMDoicGFja2FnZV9pZCI7aTozO3M6MTI6InBhY2thZ2VfdHlwZSI7czo4OiJsaWZldGltZSI7czo2OiJzdGF0dXMiO3M6NjoiYWN0aXZlIjtzOjE3OiJsaWNlbnNlX2V4cGlyZV9vbiI7TjtzOjEzOiJ0cmlhbF9lbmRzX2F0IjtOO3M6MTg6ImxpY2Vuc2VfdXBkYXRlZF9hdCI7czoxOToiMjAyNS0xMS0wMiAwNjowNjoxMSI7czoyMzoic3Vic2NyaXB0aW9uX3VwZGF0ZWRfYXQiO3M6MTk6IjIwMjUtMTEtMDIgMDY6MDY6MTEiO3M6OToic3RyaXBlX2lkIjtOO3M6NzoicG1fdHlwZSI7TjtzOjEyOiJwbV9sYXN0X2ZvdXIiO047czoyNToiaXNfd2FpdGVyX3JlcXVlc3RfZW5hYmxlZCI7aToxO3M6MzI6ImRlZmF1bHRfdGFibGVfcmVzZXJ2YXRpb25fc3RhdHVzIjtzOjk6IkNvbmZpcm1lZCI7czoyMDoiZGlzYWJsZV9zbG90X21pbnV0ZXMiO2k6MzA7czoxNToiYXBwcm92YWxfc3RhdHVzIjtzOjg6IkFwcHJvdmVkIjtzOjE2OiJyZWplY3Rpb25fcmVhc29uIjtOO3M6MTM6ImZhY2Vib29rX2xpbmsiO3M6MjU6Imh0dHBzOi8vd3d3LmZhY2Vib29rLmNvbS8iO3M6MTQ6Imluc3RhZ3JhbV9saW5rIjtzOjI2OiJodHRwczovL3d3dy5pbnN0YWdyYW0uY29tLyI7czoxMjoidHdpdHRlcl9saW5rIjtzOjI0OiJodHRwczovL3d3dy50d2l0dGVyLmNvbS8iO3M6OToieWVscF9saW5rIjtOO3M6MTQ6InRhYmxlX3JlcXVpcmVkIjtpOjA7czoxNDoic2hvd19sb2dvX3RleHQiO2k6MTtzOjEyOiJtZXRhX2tleXdvcmQiO047czoxNjoibWV0YV9kZXNjcmlwdGlvbiI7TjtzOjM0OiJ1cGxvYWRfZmF2X2ljb25fYW5kcm9pZF9jaHJvbWVfMTkyIjtOO3M6MzQ6InVwbG9hZF9mYXZfaWNvbl9hbmRyb2lkX2Nocm9tZV81MTIiO047czozMjoidXBsb2FkX2Zhdl9pY29uX2FwcGxlX3RvdWNoX2ljb24iO047czoxNzoidXBsb2FkX2Zhdmljb25fMTYiO047czoxNzoidXBsb2FkX2Zhdmljb25fMzIiO047czo3OiJmYXZpY29uIjtOO3M6MzY6ImlzX3dhaXRlcl9yZXF1ZXN0X2VuYWJsZWRfb25fZGVza3RvcCI7aToxO3M6MzU6ImlzX3dhaXRlcl9yZXF1ZXN0X2VuYWJsZWRfb25fbW9iaWxlIjtpOjE7czozNjoiaXNfd2FpdGVyX3JlcXVlc3RfZW5hYmxlZF9vcGVuX2J5X3FyIjtpOjA7czoxMToid2VibWFuaWZlc3QiO047czoxNToiZW5hYmxlX3RpcF9zaG9wIjtpOjE7czoxNDoiZW5hYmxlX3RpcF9wb3MiO2k6MTtzOjI1OiJpc19wd2FfaW5zdGFsbF9hbGVydF9zaG93IjtpOjE7czoxOToiYXV0b19jb25maXJtX29yZGVycyI7aTowO3M6MjM6InNob3dfb3JkZXJfdHlwZV9vcHRpb25zIjtpOjE7czoyNzoiaGlkZV9tZW51X2l0ZW1faW1hZ2Vfb25fcG9zIjtpOjA7czozNzoiaGlkZV9tZW51X2l0ZW1faW1hZ2Vfb25fY3VzdG9tZXJfc2l0ZSI7aTowO3M6ODoidGF4X21vZGUiO3M6NToib3JkZXIiO3M6MTM6InRheF9pbmNsdXNpdmUiO2k6MDtzOjIyOiJjdXN0b21lcl9zaXRlX2xhbmd1YWdlIjtzOjI6ImVuIjtzOjI0OiJlbmFibGVfYWRtaW5fcmVzZXJ2YXRpb24iO2k6MTtzOjI3OiJlbmFibGVfY3VzdG9tZXJfcmVzZXJ2YXRpb24iO2k6MTtzOjE4OiJtaW5pbXVtX3BhcnR5X3NpemUiO2k6MTtzOjI2OiJ0YWJsZV9sb2NrX3RpbWVvdXRfbWludXRlcyI7aToxMDt9czoxMToiACoAb3JpZ2luYWwiO2E6NzY6e3M6MjoiaWQiO2k6MTtzOjQ6Im5hbWUiO3M6NzoiTXIgQ2hhaSI7czo0OiJoYXNoIjtzOjc6Im1yLWNoYWkiO3M6NzoiYWRkcmVzcyI7czoyNToiTWFpbiBTdHJlZXQsIE9sdXZpbCAzMjM2MCI7czoxMjoicGhvbmVfbnVtYmVyIjtzOjEyOiIwNzQgMzk0IDI0NjQiO3M6MTA6InBob25lX2NvZGUiO3M6MjoiOTQiO3M6NToiZW1haWwiO3M6MTY6Im1yY2hhaUBnbWFpbC5jb20iO3M6ODoidGltZXpvbmUiO3M6MTI6IkFzaWEvQ29sb21ibyI7czo5OiJ0aGVtZV9oZXgiO3M6NzoiI0Y5NzMxNiI7czo5OiJ0aGVtZV9yZ2IiO3M6MTI6IjI0OSwgMTE1LCAyMiI7czo0OiJsb2dvIjtzOjM2OiIzNGI2NmEyMjVkOWU5NjQwNzg0M2FlNTE5YmM0M2Y1OS5qcGciO3M6MTA6ImNvdW50cnlfaWQiO2k6MjEwO3M6MTU6ImhpZGVfbmV3X29yZGVycyI7aTowO3M6MjE6ImhpZGVfbmV3X3Jlc2VydmF0aW9ucyI7aTowO3M6MjM6ImhpZGVfbmV3X3dhaXRlcl9yZXF1ZXN0IjtpOjA7czoxMToiY3VycmVuY3lfaWQiO2k6NTtzOjEyOiJsaWNlbnNlX3R5cGUiO3M6NDoiZnJlZSI7czo5OiJpc19hY3RpdmUiO2k6MTtzOjEwOiJjcmVhdGVkX2F0IjtzOjE5OiIyMDI1LTExLTAyIDA2OjA2OjExIjtzOjEwOiJ1cGRhdGVkX2F0IjtzOjE5OiIyMDI1LTExLTA0IDA1OjU1OjEwIjtzOjIzOiJjdXN0b21lcl9sb2dpbl9yZXF1aXJlZCI7aToxO3M6ODoiYWJvdXRfdXMiO3M6MTMwNDoiPHAgY2xhc3M9InRleHQtbGcgdGV4dC1ncmF5LTYwMCBtYi02Ij4KICAgICAgICAgIFdlbGNvbWUgdG8gb3VyIHJlc3RhdXJhbnQsIHdoZXJlIGdyZWF0IGZvb2QgYW5kIGdvb2QgdmliZXMgY29tZSB0b2dldGhlciEgV2UncmUgYSBsb2NhbCwgZmFtaWx5LW93bmVkIHNwb3QgdGhhdCBsb3ZlcyBicmluZ2luZyBwZW9wbGUgdG9nZXRoZXIgb3ZlciBkZWxpY2lvdXMgbWVhbHMgYW5kIHVuZm9yZ2V0dGFibGUgbW9tZW50cy4gV2hldGhlciB5b3UncmUgaGVyZSBmb3IgYSBxdWljayBiaXRlLCBhIGZhbWlseSBkaW5uZXIsIG9yIGEgY2VsZWJyYXRpb24sIHdlJ3JlIGFsbCBhYm91dCBtYWtpbmcgeW91ciB0aW1lIHdpdGggdXMgc3BlY2lhbC4KICAgICAgICA8L3A+CiAgICAgICAgPHAgY2xhc3M9InRleHQtbGcgdGV4dC1ncmF5LTYwMCBtYi02Ij4KICAgICAgICAgIE91ciBtZW51IGlzIHBhY2tlZCB3aXRoIGRpc2hlcyBtYWRlIGZyb20gZnJlc2gsIHF1YWxpdHkgaW5ncmVkaWVudHMgYmVjYXVzZSB3ZSBiZWxpZXZlIGZvb2Qgc2hvdWxkIHRhc3RlIGFzCiAgICAgICAgICBnb29kIGFzIGl0IG1ha2VzIHlvdSBmZWVsLiBGcm9tIG91ciBzaWduYXR1cmUgZGlzaGVzIHRvIHNlYXNvbmFsIHNwZWNpYWxzLCB0aGVyZSdzIGFsd2F5cyBzb21ldGhpbmcgdG8gZXhjaXRlCiAgICAgICAgICB5b3VyIHRhc3RlIGJ1ZHMuCiAgICAgICAgPC9wPgogICAgICAgIDxwIGNsYXNzPSJ0ZXh0LWxnIHRleHQtZ3JheS02MDAgbWItNiI+CiAgICAgICAgICBCdXQgd2UncmUgbm90IGp1c3QgYWJvdXQgdGhlIGZvb2TigJR3ZSdyZSBhYm91dCBjb21tdW5pdHkuIFdlIGxvdmUgc2VlaW5nIGZhbWlsaWFyIGZhY2VzIGFuZCB3ZWxjb21pbmcgbmV3IG9uZXMuCiAgICAgICAgICBPdXIgdGVhbSBpcyBhIGZ1biwgZnJpZW5kbHkgYnVuY2ggZGVkaWNhdGVkIHRvIHNlcnZpbmcgeW91IHdpdGggYSBzbWlsZSBhbmQgbWFraW5nIHN1cmUgZXZlcnkgdmlzaXQgZmVlbHMgbGlrZQogICAgICAgICAgY29taW5nIGhvbWUuCiAgICAgICAgPC9wPgogICAgICAgIDxwIGNsYXNzPSJ0ZXh0LWxnIHRleHQtZ3JheS02MDAiPgogICAgICAgICAgU28sIGNvbWUgb24gaW4sIGdyYWIgYSBzZWF0LCBhbmQgbGV0IHVzIHRha2UgY2FyZSBvZiB0aGUgcmVzdC4gV2UgY2FuJ3Qgd2FpdCB0byBzaGFyZSBvdXIgbG92ZSBvZiBmb29kIHdpdGgKICAgICAgICAgIHlvdSEKICAgICAgICA8L3A+CiAgICAgICAgPHAgY2xhc3M9InRleHQtbGcgdGV4dC1ncmF5LTgwMCBmb250LXNlbWlib2xkIG10LTYiPlNlZSB5b3Ugc29vbiEg8J+Nve+4j+KcqDwvcD4iO3M6MzA6ImFsbG93X2N1c3RvbWVyX2RlbGl2ZXJ5X29yZGVycyI7aToxO3M6Mjg6ImFsbG93X2N1c3RvbWVyX3BpY2t1cF9vcmRlcnMiO2k6MTtzOjE3OiJwaWNrdXBfZGF5c19yYW5nZSI7aTo3O3M6MjE6ImFsbG93X2N1c3RvbWVyX29yZGVycyI7aToxO3M6MjA6ImFsbG93X2RpbmVfaW5fb3JkZXJzIjtpOjE7czo4OiJzaG93X3ZlZyI7aToxO3M6MTA6InNob3dfaGFsYWwiO2k6MDtzOjEwOiJwYWNrYWdlX2lkIjtpOjM7czoxMjoicGFja2FnZV90eXBlIjtzOjg6ImxpZmV0aW1lIjtzOjY6InN0YXR1cyI7czo2OiJhY3RpdmUiO3M6MTc6ImxpY2Vuc2VfZXhwaXJlX29uIjtOO3M6MTM6InRyaWFsX2VuZHNfYXQiO047czoxODoibGljZW5zZV91cGRhdGVkX2F0IjtzOjE5OiIyMDI1LTExLTAyIDA2OjA2OjExIjtzOjIzOiJzdWJzY3JpcHRpb25fdXBkYXRlZF9hdCI7czoxOToiMjAyNS0xMS0wMiAwNjowNjoxMSI7czo5OiJzdHJpcGVfaWQiO047czo3OiJwbV90eXBlIjtOO3M6MTI6InBtX2xhc3RfZm91ciI7TjtzOjI1OiJpc193YWl0ZXJfcmVxdWVzdF9lbmFibGVkIjtpOjE7czozMjoiZGVmYXVsdF90YWJsZV9yZXNlcnZhdGlvbl9zdGF0dXMiO3M6OToiQ29uZmlybWVkIjtzOjIwOiJkaXNhYmxlX3Nsb3RfbWludXRlcyI7aTozMDtzOjE1OiJhcHByb3ZhbF9zdGF0dXMiO3M6ODoiQXBwcm92ZWQiO3M6MTY6InJlamVjdGlvbl9yZWFzb24iO047czoxMzoiZmFjZWJvb2tfbGluayI7czoyNToiaHR0cHM6Ly93d3cuZmFjZWJvb2suY29tLyI7czoxNDoiaW5zdGFncmFtX2xpbmsiO3M6MjY6Imh0dHBzOi8vd3d3Lmluc3RhZ3JhbS5jb20vIjtzOjEyOiJ0d2l0dGVyX2xpbmsiO3M6MjQ6Imh0dHBzOi8vd3d3LnR3aXR0ZXIuY29tLyI7czo5OiJ5ZWxwX2xpbmsiO047czoxNDoidGFibGVfcmVxdWlyZWQiO2k6MDtzOjE0OiJzaG93X2xvZ29fdGV4dCI7aToxO3M6MTI6Im1ldGFfa2V5d29yZCI7TjtzOjE2OiJtZXRhX2Rlc2NyaXB0aW9uIjtOO3M6MzQ6InVwbG9hZF9mYXZfaWNvbl9hbmRyb2lkX2Nocm9tZV8xOTIiO047czozNDoidXBsb2FkX2Zhdl9pY29uX2FuZHJvaWRfY2hyb21lXzUxMiI7TjtzOjMyOiJ1cGxvYWRfZmF2X2ljb25fYXBwbGVfdG91Y2hfaWNvbiI7TjtzOjE3OiJ1cGxvYWRfZmF2aWNvbl8xNiI7TjtzOjE3OiJ1cGxvYWRfZmF2aWNvbl8zMiI7TjtzOjc6ImZhdmljb24iO047czozNjoiaXNfd2FpdGVyX3JlcXVlc3RfZW5hYmxlZF9vbl9kZXNrdG9wIjtpOjE7czozNToiaXNfd2FpdGVyX3JlcXVlc3RfZW5hYmxlZF9vbl9tb2JpbGUiO2k6MTtzOjM2OiJpc193YWl0ZXJfcmVxdWVzdF9lbmFibGVkX29wZW5fYnlfcXIiO2k6MDtzOjExOiJ3ZWJtYW5pZmVzdCI7TjtzOjE1OiJlbmFibGVfdGlwX3Nob3AiO2k6MTtzOjE0OiJlbmFibGVfdGlwX3BvcyI7aToxO3M6MjU6ImlzX3B3YV9pbnN0YWxsX2FsZXJ0X3Nob3ciO2k6MTtzOjE5OiJhdXRvX2NvbmZpcm1fb3JkZXJzIjtpOjA7czoyMzoic2hvd19vcmRlcl90eXBlX29wdGlvbnMiO2k6MTtzOjI3OiJoaWRlX21lbnVfaXRlbV9pbWFnZV9vbl9wb3MiO2k6MDtzOjM3OiJoaWRlX21lbnVfaXRlbV9pbWFnZV9vbl9jdXN0b21lcl9zaXRlIjtpOjA7czo4OiJ0YXhfbW9kZSI7czo1OiJvcmRlciI7czoxMzoidGF4X2luY2x1c2l2ZSI7aTowO3M6MjI6ImN1c3RvbWVyX3NpdGVfbGFuZ3VhZ2UiO3M6MjoiZW4iO3M6MjQ6ImVuYWJsZV9hZG1pbl9yZXNlcnZhdGlvbiI7aToxO3M6Mjc6ImVuYWJsZV9jdXN0b21lcl9yZXNlcnZhdGlvbiI7aToxO3M6MTg6Im1pbmltdW1fcGFydHlfc2l6ZSI7aToxO3M6MjY6InRhYmxlX2xvY2tfdGltZW91dF9taW51dGVzIjtpOjEwO31zOjEwOiIAKgBjaGFuZ2VzIjthOjA6e31zOjExOiIAKgBwcmV2aW91cyI7YTowOnt9czo4OiIAKgBjYXN0cyI7YToxMDp7czoxNzoibGljZW5zZV9leHBpcmVfb24iO3M6ODoiZGF0ZXRpbWUiO3M6MTU6InRyaWFsX2V4cGlyZV9vbiI7czo4OiJkYXRldGltZSI7czoxODoibGljZW5zZV91cGRhdGVkX2F0IjtzOjg6ImRhdGV0aW1lIjtzOjIzOiJzdWJzY3JpcHRpb25fdXBkYXRlZF9hdCI7czo4OiJkYXRldGltZSI7czoxMDoiY3JlYXRlZF9hdCI7czo4OiJkYXRldGltZSI7czoxMDoidXBkYXRlZF9hdCI7czo4OiJkYXRldGltZSI7czoyMzoiY3VzdG9tX2RlbGl2ZXJ5X29wdGlvbnMiO3M6NToiYXJyYXkiO3M6OToiaXNfYWN0aXZlIjtzOjc6ImJvb2xlYW4iO3M6MjQ6ImVuYWJsZV9hZG1pbl9yZXNlcnZhdGlvbiI7czo3OiJib29sZWFuIjtzOjI3OiJlbmFibGVfY3VzdG9tZXJfcmVzZXJ2YXRpb24iO3M6NzoiYm9vbGVhbiI7fXM6MTc6IgAqAGNsYXNzQ2FzdENhY2hlIjthOjA6e31zOjIxOiIAKgBhdHRyaWJ1dGVDYXN0Q2FjaGUiO2E6MDp7fXM6MTM6IgAqAGRhdGVGb3JtYXQiO047czoxMDoiACoAYXBwZW5kcyI7YToxOntpOjA7czo4OiJsb2dvX3VybCI7fXM6MTk6IgAqAGRpc3BhdGNoZXNFdmVudHMiO2E6MDp7fXM6MTQ6IgAqAG9ic2VydmFibGVzIjthOjA6e31zOjEyOiIAKgByZWxhdGlvbnMiO2E6MDp7fXM6MTA6IgAqAHRvdWNoZXMiO2E6MDp7fXM6Mjc6IgAqAHJlbGF0aW9uQXV0b2xvYWRDYWxsYmFjayI7TjtzOjI2OiIAKgByZWxhdGlvbkF1dG9sb2FkQ29udGV4dCI7TjtzOjEwOiJ0aW1lc3RhbXBzIjtiOjE7czoxMzoidXNlc1VuaXF1ZUlkcyI7YjowO3M6OToiACoAaGlkZGVuIjthOjA6e31zOjEwOiIAKgB2aXNpYmxlIjthOjA6e31zOjExOiIAKgBmaWxsYWJsZSI7YTowOnt9czoxMDoiACoAZ3VhcmRlZCI7YToxOntpOjA7czoyOiJpZCI7fXM6MTc6ImN1c3RvbWVySXBBZGRyZXNzIjtOO3M6MjQ6ImVzdGltYXRpb25CaWxsaW5nQWRkcmVzcyI7YTowOnt9czoxMzoiY29sbGVjdFRheElkcyI7YjowO3M6ODoiY291cG9uSWQiO047czoxNToicHJvbW90aW9uQ29kZUlkIjtOO3M6MTk6ImFsbG93UHJvbW90aW9uQ29kZXMiO2I6MDt9czoxMToicGVybWlzc2lvbnMiO086Mzk6IklsbHVtaW5hdGVcRGF0YWJhc2VcRWxvcXVlbnRcQ29sbGVjdGlvbiI6Mjp7czo4OiIAKgBpdGVtcyI7YTowOnt9czoyODoiACoAZXNjYXBlV2hlbkNhc3RpbmdUb1N0cmluZyI7YjowO319czoxMDoiACoAdG91Y2hlcyI7YTowOnt9czoyNzoiACoAcmVsYXRpb25BdXRvbG9hZENhbGxiYWNrIjtOO3M6MjY6IgAqAHJlbGF0aW9uQXV0b2xvYWRDb250ZXh0IjtOO3M6MTA6InRpbWVzdGFtcHMiO2I6MTtzOjEzOiJ1c2VzVW5pcXVlSWRzIjtiOjA7czo5OiIAKgBoaWRkZW4iO2E6NDp7aTowO3M6ODoicGFzc3dvcmQiO2k6MTtzOjE0OiJyZW1lbWJlcl90b2tlbiI7aToyO3M6MjU6InR3b19mYWN0b3JfcmVjb3ZlcnlfY29kZXMiO2k6MztzOjE3OiJ0d29fZmFjdG9yX3NlY3JldCI7fXM6MTA6IgAqAHZpc2libGUiO2E6MDp7fXM6MTE6IgAqAGZpbGxhYmxlIjthOjExOntpOjA7czo0OiJuYW1lIjtpOjE7czo1OiJlbWFpbCI7aToyO3M6ODoicGFzc3dvcmQiO2k6MztzOjk6ImJyYW5jaF9pZCI7aTo0O3M6MTM6InJlc3RhdXJhbnRfaWQiO2k6NTtzOjEwOiJraXRjaGVuX2lkIjtpOjY7czo2OiJsb2NhbGUiO2k6NztzOjEyOiJwaG9uZV9udW1iZXIiO2k6ODtzOjEwOiJwaG9uZV9jb2RlIjtpOjk7czoyNjoidGVybXNfYW5kX3ByaXZhY3lfYWNjZXB0ZWQiO2k6MTA7czoyNToibWFya2V0aW5nX2VtYWlsc19hY2NlcHRlZCI7fXM6MTA6IgAqAGd1YXJkZWQiO2E6MTp7aTowO3M6MToiKiI7fXM6MTk6IgAqAGF1dGhQYXNzd29yZE5hbWUiO3M6ODoicGFzc3dvcmQiO3M6MjA6IgAqAHJlbWVtYmVyVG9rZW5OYW1lIjtzOjE0OiJyZW1lbWJlcl90b2tlbiI7czoxNDoiACoAYWNjZXNzVG9rZW4iO047fXM6OToiX3ByZXZpb3VzIjthOjE6e3M6MzoidXJsIjtzOjI3OiJodHRwOi8vZ2VueC5sb2NhbC9kYXNoYm9hcmQiO31zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aTo0O3M6MTY6InJvbGVfcGVybWlzc2lvbnMiO2E6Mjp7aTowO3M6MTA6Ik1hbmFnZSBLT1QiO2k6MTtzOjE4OiJTaG93IEtpdGNoZW4gUGxhY2UiO31zOjEwOiJyZXN0YXVyYW50IjtPOjIxOiJBcHBcTW9kZWxzXFJlc3RhdXJhbnQiOjM5OntzOjEzOiIAKgBjb25uZWN0aW9uIjtzOjU6Im15c3FsIjtzOjg6IgAqAHRhYmxlIjtzOjExOiJyZXN0YXVyYW50cyI7czoxMzoiACoAcHJpbWFyeUtleSI7czoyOiJpZCI7czoxMDoiACoAa2V5VHlwZSI7czozOiJpbnQiO3M6MTI6ImluY3JlbWVudGluZyI7YjoxO3M6NzoiACoAd2l0aCI7YTowOnt9czoxMjoiACoAd2l0aENvdW50IjthOjA6e31zOjE5OiJwcmV2ZW50c0xhenlMb2FkaW5nIjtiOjA7czoxMDoiACoAcGVyUGFnZSI7aToxNTtzOjY6ImV4aXN0cyI7YjoxO3M6MTg6Indhc1JlY2VudGx5Q3JlYXRlZCI7YjowO3M6Mjg6IgAqAGVzY2FwZVdoZW5DYXN0aW5nVG9TdHJpbmciO2I6MDtzOjEzOiIAKgBhdHRyaWJ1dGVzIjthOjc2OntzOjI6ImlkIjtpOjE7czo0OiJuYW1lIjtzOjc6Ik1yIENoYWkiO3M6NDoiaGFzaCI7czo3OiJtci1jaGFpIjtzOjc6ImFkZHJlc3MiO3M6MjU6Ik1haW4gU3RyZWV0LCBPbHV2aWwgMzIzNjAiO3M6MTI6InBob25lX251bWJlciI7czoxMjoiMDc0IDM5NCAyNDY0IjtzOjEwOiJwaG9uZV9jb2RlIjtpOjk0O3M6NToiZW1haWwiO3M6MTY6Im1yY2hhaUBnbWFpbC5jb20iO3M6ODoidGltZXpvbmUiO3M6MTI6IkFzaWEvQ29sb21ibyI7czo5OiJ0aGVtZV9oZXgiO3M6NzoiI0Y5NzMxNiI7czo5OiJ0aGVtZV9yZ2IiO3M6MTI6IjI0OSwgMTE1LCAyMiI7czo0OiJsb2dvIjtzOjM2OiIzNGI2NmEyMjVkOWU5NjQwNzg0M2FlNTE5YmM0M2Y1OS5qcGciO3M6MTA6ImNvdW50cnlfaWQiO2k6MjEwO3M6MTU6ImhpZGVfbmV3X29yZGVycyI7aTowO3M6MjE6ImhpZGVfbmV3X3Jlc2VydmF0aW9ucyI7aTowO3M6MjM6ImhpZGVfbmV3X3dhaXRlcl9yZXF1ZXN0IjtpOjA7czoxMToiY3VycmVuY3lfaWQiO2k6NTtzOjEyOiJsaWNlbnNlX3R5cGUiO3M6NDoiZnJlZSI7czo5OiJpc19hY3RpdmUiO2k6MTtzOjEwOiJjcmVhdGVkX2F0IjtzOjE5OiIyMDI1LTExLTAyIDA2OjA2OjExIjtzOjEwOiJ1cGRhdGVkX2F0IjtzOjE5OiIyMDI1LTExLTA0IDA1OjU1OjEwIjtzOjIzOiJjdXN0b21lcl9sb2dpbl9yZXF1aXJlZCI7aToxO3M6ODoiYWJvdXRfdXMiO3M6MTMwNDoiPHAgY2xhc3M9InRleHQtbGcgdGV4dC1ncmF5LTYwMCBtYi02Ij4KICAgICAgICAgIFdlbGNvbWUgdG8gb3VyIHJlc3RhdXJhbnQsIHdoZXJlIGdyZWF0IGZvb2QgYW5kIGdvb2QgdmliZXMgY29tZSB0b2dldGhlciEgV2UncmUgYSBsb2NhbCwgZmFtaWx5LW93bmVkIHNwb3QgdGhhdCBsb3ZlcyBicmluZ2luZyBwZW9wbGUgdG9nZXRoZXIgb3ZlciBkZWxpY2lvdXMgbWVhbHMgYW5kIHVuZm9yZ2V0dGFibGUgbW9tZW50cy4gV2hldGhlciB5b3UncmUgaGVyZSBmb3IgYSBxdWljayBiaXRlLCBhIGZhbWlseSBkaW5uZXIsIG9yIGEgY2VsZWJyYXRpb24sIHdlJ3JlIGFsbCBhYm91dCBtYWtpbmcgeW91ciB0aW1lIHdpdGggdXMgc3BlY2lhbC4KICAgICAgICA8L3A+CiAgICAgICAgPHAgY2xhc3M9InRleHQtbGcgdGV4dC1ncmF5LTYwMCBtYi02Ij4KICAgICAgICAgIE91ciBtZW51IGlzIHBhY2tlZCB3aXRoIGRpc2hlcyBtYWRlIGZyb20gZnJlc2gsIHF1YWxpdHkgaW5ncmVkaWVudHMgYmVjYXVzZSB3ZSBiZWxpZXZlIGZvb2Qgc2hvdWxkIHRhc3RlIGFzCiAgICAgICAgICBnb29kIGFzIGl0IG1ha2VzIHlvdSBmZWVsLiBGcm9tIG91ciBzaWduYXR1cmUgZGlzaGVzIHRvIHNlYXNvbmFsIHNwZWNpYWxzLCB0aGVyZSdzIGFsd2F5cyBzb21ldGhpbmcgdG8gZXhjaXRlCiAgICAgICAgICB5b3VyIHRhc3RlIGJ1ZHMuCiAgICAgICAgPC9wPgogICAgICAgIDxwIGNsYXNzPSJ0ZXh0LWxnIHRleHQtZ3JheS02MDAgbWItNiI+CiAgICAgICAgICBCdXQgd2UncmUgbm90IGp1c3QgYWJvdXQgdGhlIGZvb2TigJR3ZSdyZSBhYm91dCBjb21tdW5pdHkuIFdlIGxvdmUgc2VlaW5nIGZhbWlsaWFyIGZhY2VzIGFuZCB3ZWxjb21pbmcgbmV3IG9uZXMuCiAgICAgICAgICBPdXIgdGVhbSBpcyBhIGZ1biwgZnJpZW5kbHkgYnVuY2ggZGVkaWNhdGVkIHRvIHNlcnZpbmcgeW91IHdpdGggYSBzbWlsZSBhbmQgbWFraW5nIHN1cmUgZXZlcnkgdmlzaXQgZmVlbHMgbGlrZQogICAgICAgICAgY29taW5nIGhvbWUuCiAgICAgICAgPC9wPgogICAgICAgIDxwIGNsYXNzPSJ0ZXh0LWxnIHRleHQtZ3JheS02MDAiPgogICAgICAgICAgU28sIGNvbWUgb24gaW4sIGdyYWIgYSBzZWF0LCBhbmQgbGV0IHVzIHRha2UgY2FyZSBvZiB0aGUgcmVzdC4gV2UgY2FuJ3Qgd2FpdCB0byBzaGFyZSBvdXIgbG92ZSBvZiBmb29kIHdpdGgKICAgICAgICAgIHlvdSEKICAgICAgICA8L3A+CiAgICAgICAgPHAgY2xhc3M9InRleHQtbGcgdGV4dC1ncmF5LTgwMCBmb250LXNlbWlib2xkIG10LTYiPlNlZSB5b3Ugc29vbiEg8J+Nve+4j+KcqDwvcD4iO3M6MzA6ImFsbG93X2N1c3RvbWVyX2RlbGl2ZXJ5X29yZGVycyI7aToxO3M6Mjg6ImFsbG93X2N1c3RvbWVyX3BpY2t1cF9vcmRlcnMiO2k6MTtzOjE3OiJwaWNrdXBfZGF5c19yYW5nZSI7aTo3O3M6MjE6ImFsbG93X2N1c3RvbWVyX29yZGVycyI7aToxO3M6MjA6ImFsbG93X2RpbmVfaW5fb3JkZXJzIjtpOjE7czo4OiJzaG93X3ZlZyI7aToxO3M6MTA6InNob3dfaGFsYWwiO2k6MDtzOjEwOiJwYWNrYWdlX2lkIjtpOjM7czoxMjoicGFja2FnZV90eXBlIjtzOjg6ImxpZmV0aW1lIjtzOjY6InN0YXR1cyI7czo2OiJhY3RpdmUiO3M6MTc6ImxpY2Vuc2VfZXhwaXJlX29uIjtOO3M6MTM6InRyaWFsX2VuZHNfYXQiO047czoxODoibGljZW5zZV91cGRhdGVkX2F0IjtzOjE5OiIyMDI1LTExLTAyIDA2OjA2OjExIjtzOjIzOiJzdWJzY3JpcHRpb25fdXBkYXRlZF9hdCI7czoxOToiMjAyNS0xMS0wMiAwNjowNjoxMSI7czo5OiJzdHJpcGVfaWQiO047czo3OiJwbV90eXBlIjtOO3M6MTI6InBtX2xhc3RfZm91ciI7TjtzOjI1OiJpc193YWl0ZXJfcmVxdWVzdF9lbmFibGVkIjtpOjE7czozMjoiZGVmYXVsdF90YWJsZV9yZXNlcnZhdGlvbl9zdGF0dXMiO3M6OToiQ29uZmlybWVkIjtzOjIwOiJkaXNhYmxlX3Nsb3RfbWludXRlcyI7aTozMDtzOjE1OiJhcHByb3ZhbF9zdGF0dXMiO3M6ODoiQXBwcm92ZWQiO3M6MTY6InJlamVjdGlvbl9yZWFzb24iO047czoxMzoiZmFjZWJvb2tfbGluayI7czoyNToiaHR0cHM6Ly93d3cuZmFjZWJvb2suY29tLyI7czoxNDoiaW5zdGFncmFtX2xpbmsiO3M6MjY6Imh0dHBzOi8vd3d3Lmluc3RhZ3JhbS5jb20vIjtzOjEyOiJ0d2l0dGVyX2xpbmsiO3M6MjQ6Imh0dHBzOi8vd3d3LnR3aXR0ZXIuY29tLyI7czo5OiJ5ZWxwX2xpbmsiO047czoxNDoidGFibGVfcmVxdWlyZWQiO2k6MDtzOjE0OiJzaG93X2xvZ29fdGV4dCI7aToxO3M6MTI6Im1ldGFfa2V5d29yZCI7TjtzOjE2OiJtZXRhX2Rlc2NyaXB0aW9uIjtOO3M6MzQ6InVwbG9hZF9mYXZfaWNvbl9hbmRyb2lkX2Nocm9tZV8xOTIiO047czozNDoidXBsb2FkX2Zhdl9pY29uX2FuZHJvaWRfY2hyb21lXzUxMiI7TjtzOjMyOiJ1cGxvYWRfZmF2X2ljb25fYXBwbGVfdG91Y2hfaWNvbiI7TjtzOjE3OiJ1cGxvYWRfZmF2aWNvbl8xNiI7TjtzOjE3OiJ1cGxvYWRfZmF2aWNvbl8zMiI7TjtzOjc6ImZhdmljb24iO047czozNjoiaXNfd2FpdGVyX3JlcXVlc3RfZW5hYmxlZF9vbl9kZXNrdG9wIjtpOjE7czozNToiaXNfd2FpdGVyX3JlcXVlc3RfZW5hYmxlZF9vbl9tb2JpbGUiO2k6MTtzOjM2OiJpc193YWl0ZXJfcmVxdWVzdF9lbmFibGVkX29wZW5fYnlfcXIiO2k6MDtzOjExOiJ3ZWJtYW5pZmVzdCI7TjtzOjE1OiJlbmFibGVfdGlwX3Nob3AiO2k6MTtzOjE0OiJlbmFibGVfdGlwX3BvcyI7aToxO3M6MjU6ImlzX3B3YV9pbnN0YWxsX2FsZXJ0X3Nob3ciO2k6MTtzOjE5OiJhdXRvX2NvbmZpcm1fb3JkZXJzIjtpOjA7czoyMzoic2hvd19vcmRlcl90eXBlX29wdGlvbnMiO2k6MTtzOjI3OiJoaWRlX21lbnVfaXRlbV9pbWFnZV9vbl9wb3MiO2k6MDtzOjM3OiJoaWRlX21lbnVfaXRlbV9pbWFnZV9vbl9jdXN0b21lcl9zaXRlIjtpOjA7czo4OiJ0YXhfbW9kZSI7czo1OiJvcmRlciI7czoxMzoidGF4X2luY2x1c2l2ZSI7aTowO3M6MjI6ImN1c3RvbWVyX3NpdGVfbGFuZ3VhZ2UiO3M6MjoiZW4iO3M6MjQ6ImVuYWJsZV9hZG1pbl9yZXNlcnZhdGlvbiI7aToxO3M6Mjc6ImVuYWJsZV9jdXN0b21lcl9yZXNlcnZhdGlvbiI7aToxO3M6MTg6Im1pbmltdW1fcGFydHlfc2l6ZSI7aToxO3M6MjY6InRhYmxlX2xvY2tfdGltZW91dF9taW51dGVzIjtpOjEwO31zOjExOiIAKgBvcmlnaW5hbCI7YTo3Njp7czoyOiJpZCI7aToxO3M6NDoibmFtZSI7czo3OiJNciBDaGFpIjtzOjQ6Imhhc2giO3M6NzoibXItY2hhaSI7czo3OiJhZGRyZXNzIjtzOjI1OiJNYWluIFN0cmVldCwgT2x1dmlsIDMyMzYwIjtzOjEyOiJwaG9uZV9udW1iZXIiO3M6MTI6IjA3NCAzOTQgMjQ2NCI7czoxMDoicGhvbmVfY29kZSI7czoyOiI5NCI7czo1OiJlbWFpbCI7czoxNjoibXJjaGFpQGdtYWlsLmNvbSI7czo4OiJ0aW1lem9uZSI7czoxMjoiQXNpYS9Db2xvbWJvIjtzOjk6InRoZW1lX2hleCI7czo3OiIjRjk3MzE2IjtzOjk6InRoZW1lX3JnYiI7czoxMjoiMjQ5LCAxMTUsIDIyIjtzOjQ6ImxvZ28iO3M6MzY6IjM0YjY2YTIyNWQ5ZTk2NDA3ODQzYWU1MTliYzQzZjU5LmpwZyI7czoxMDoiY291bnRyeV9pZCI7aToyMTA7czoxNToiaGlkZV9uZXdfb3JkZXJzIjtpOjA7czoyMToiaGlkZV9uZXdfcmVzZXJ2YXRpb25zIjtpOjA7czoyMzoiaGlkZV9uZXdfd2FpdGVyX3JlcXVlc3QiO2k6MDtzOjExOiJjdXJyZW5jeV9pZCI7aTo1O3M6MTI6ImxpY2Vuc2VfdHlwZSI7czo0OiJmcmVlIjtzOjk6ImlzX2FjdGl2ZSI7aToxO3M6MTA6ImNyZWF0ZWRfYXQiO3M6MTk6IjIwMjUtMTEtMDIgMDY6MDY6MTEiO3M6MTA6InVwZGF0ZWRfYXQiO3M6MTk6IjIwMjUtMTEtMDQgMDU6NTU6MTAiO3M6MjM6ImN1c3RvbWVyX2xvZ2luX3JlcXVpcmVkIjtpOjE7czo4OiJhYm91dF91cyI7czoxMzA0OiI8cCBjbGFzcz0idGV4dC1sZyB0ZXh0LWdyYXktNjAwIG1iLTYiPgogICAgICAgICAgV2VsY29tZSB0byBvdXIgcmVzdGF1cmFudCwgd2hlcmUgZ3JlYXQgZm9vZCBhbmQgZ29vZCB2aWJlcyBjb21lIHRvZ2V0aGVyISBXZSdyZSBhIGxvY2FsLCBmYW1pbHktb3duZWQgc3BvdCB0aGF0IGxvdmVzIGJyaW5naW5nIHBlb3BsZSB0b2dldGhlciBvdmVyIGRlbGljaW91cyBtZWFscyBhbmQgdW5mb3JnZXR0YWJsZSBtb21lbnRzLiBXaGV0aGVyIHlvdSdyZSBoZXJlIGZvciBhIHF1aWNrIGJpdGUsIGEgZmFtaWx5IGRpbm5lciwgb3IgYSBjZWxlYnJhdGlvbiwgd2UncmUgYWxsIGFib3V0IG1ha2luZyB5b3VyIHRpbWUgd2l0aCB1cyBzcGVjaWFsLgogICAgICAgIDwvcD4KICAgICAgICA8cCBjbGFzcz0idGV4dC1sZyB0ZXh0LWdyYXktNjAwIG1iLTYiPgogICAgICAgICAgT3VyIG1lbnUgaXMgcGFja2VkIHdpdGggZGlzaGVzIG1hZGUgZnJvbSBmcmVzaCwgcXVhbGl0eSBpbmdyZWRpZW50cyBiZWNhdXNlIHdlIGJlbGlldmUgZm9vZCBzaG91bGQgdGFzdGUgYXMKICAgICAgICAgIGdvb2QgYXMgaXQgbWFrZXMgeW91IGZlZWwuIEZyb20gb3VyIHNpZ25hdHVyZSBkaXNoZXMgdG8gc2Vhc29uYWwgc3BlY2lhbHMsIHRoZXJlJ3MgYWx3YXlzIHNvbWV0aGluZyB0byBleGNpdGUKICAgICAgICAgIHlvdXIgdGFzdGUgYnVkcy4KICAgICAgICA8L3A+CiAgICAgICAgPHAgY2xhc3M9InRleHQtbGcgdGV4dC1ncmF5LTYwMCBtYi02Ij4KICAgICAgICAgIEJ1dCB3ZSdyZSBub3QganVzdCBhYm91dCB0aGUgZm9vZOKAlHdlJ3JlIGFib3V0IGNvbW11bml0eS4gV2UgbG92ZSBzZWVpbmcgZmFtaWxpYXIgZmFjZXMgYW5kIHdlbGNvbWluZyBuZXcgb25lcy4KICAgICAgICAgIE91ciB0ZWFtIGlzIGEgZnVuLCBmcmllbmRseSBidW5jaCBkZWRpY2F0ZWQgdG8gc2VydmluZyB5b3Ugd2l0aCBhIHNtaWxlIGFuZCBtYWtpbmcgc3VyZSBldmVyeSB2aXNpdCBmZWVscyBsaWtlCiAgICAgICAgICBjb21pbmcgaG9tZS4KICAgICAgICA8L3A+CiAgICAgICAgPHAgY2xhc3M9InRleHQtbGcgdGV4dC1ncmF5LTYwMCI+CiAgICAgICAgICBTbywgY29tZSBvbiBpbiwgZ3JhYiBhIHNlYXQsIGFuZCBsZXQgdXMgdGFrZSBjYXJlIG9mIHRoZSByZXN0LiBXZSBjYW4ndCB3YWl0IHRvIHNoYXJlIG91ciBsb3ZlIG9mIGZvb2Qgd2l0aAogICAgICAgICAgeW91IQogICAgICAgIDwvcD4KICAgICAgICA8cCBjbGFzcz0idGV4dC1sZyB0ZXh0LWdyYXktODAwIGZvbnQtc2VtaWJvbGQgbXQtNiI+U2VlIHlvdSBzb29uISDwn42977iP4pyoPC9wPiI7czozMDoiYWxsb3dfY3VzdG9tZXJfZGVsaXZlcnlfb3JkZXJzIjtpOjE7czoyODoiYWxsb3dfY3VzdG9tZXJfcGlja3VwX29yZGVycyI7aToxO3M6MTc6InBpY2t1cF9kYXlzX3JhbmdlIjtpOjc7czoyMToiYWxsb3dfY3VzdG9tZXJfb3JkZXJzIjtpOjE7czoyMDoiYWxsb3dfZGluZV9pbl9vcmRlcnMiO2k6MTtzOjg6InNob3dfdmVnIjtpOjE7czoxMDoic2hvd19oYWxhbCI7aTowO3M6MTA6InBhY2thZ2VfaWQiO2k6MztzOjEyOiJwYWNrYWdlX3R5cGUiO3M6ODoibGlmZXRpbWUiO3M6Njoic3RhdHVzIjtzOjY6ImFjdGl2ZSI7czoxNzoibGljZW5zZV9leHBpcmVfb24iO047czoxMzoidHJpYWxfZW5kc19hdCI7TjtzOjE4OiJsaWNlbnNlX3VwZGF0ZWRfYXQiO3M6MTk6IjIwMjUtMTEtMDIgMDY6MDY6MTEiO3M6MjM6InN1YnNjcmlwdGlvbl91cGRhdGVkX2F0IjtzOjE5OiIyMDI1LTExLTAyIDA2OjA2OjExIjtzOjk6InN0cmlwZV9pZCI7TjtzOjc6InBtX3R5cGUiO047czoxMjoicG1fbGFzdF9mb3VyIjtOO3M6MjU6ImlzX3dhaXRlcl9yZXF1ZXN0X2VuYWJsZWQiO2k6MTtzOjMyOiJkZWZhdWx0X3RhYmxlX3Jlc2VydmF0aW9uX3N0YXR1cyI7czo5OiJDb25maXJtZWQiO3M6MjA6ImRpc2FibGVfc2xvdF9taW51dGVzIjtpOjMwO3M6MTU6ImFwcHJvdmFsX3N0YXR1cyI7czo4OiJBcHByb3ZlZCI7czoxNjoicmVqZWN0aW9uX3JlYXNvbiI7TjtzOjEzOiJmYWNlYm9va19saW5rIjtzOjI1OiJodHRwczovL3d3dy5mYWNlYm9vay5jb20vIjtzOjE0OiJpbnN0YWdyYW1fbGluayI7czoyNjoiaHR0cHM6Ly93d3cuaW5zdGFncmFtLmNvbS8iO3M6MTI6InR3aXR0ZXJfbGluayI7czoyNDoiaHR0cHM6Ly93d3cudHdpdHRlci5jb20vIjtzOjk6InllbHBfbGluayI7TjtzOjE0OiJ0YWJsZV9yZXF1aXJlZCI7aTowO3M6MTQ6InNob3dfbG9nb190ZXh0IjtpOjE7czoxMjoibWV0YV9rZXl3b3JkIjtOO3M6MTY6Im1ldGFfZGVzY3JpcHRpb24iO047czozNDoidXBsb2FkX2Zhdl9pY29uX2FuZHJvaWRfY2hyb21lXzE5MiI7TjtzOjM0OiJ1cGxvYWRfZmF2X2ljb25fYW5kcm9pZF9jaHJvbWVfNTEyIjtOO3M6MzI6InVwbG9hZF9mYXZfaWNvbl9hcHBsZV90b3VjaF9pY29uIjtOO3M6MTc6InVwbG9hZF9mYXZpY29uXzE2IjtOO3M6MTc6InVwbG9hZF9mYXZpY29uXzMyIjtOO3M6NzoiZmF2aWNvbiI7TjtzOjM2OiJpc193YWl0ZXJfcmVxdWVzdF9lbmFibGVkX29uX2Rlc2t0b3AiO2k6MTtzOjM1OiJpc193YWl0ZXJfcmVxdWVzdF9lbmFibGVkX29uX21vYmlsZSI7aToxO3M6MzY6ImlzX3dhaXRlcl9yZXF1ZXN0X2VuYWJsZWRfb3Blbl9ieV9xciI7aTowO3M6MTE6IndlYm1hbmlmZXN0IjtOO3M6MTU6ImVuYWJsZV90aXBfc2hvcCI7aToxO3M6MTQ6ImVuYWJsZV90aXBfcG9zIjtpOjE7czoyNToiaXNfcHdhX2luc3RhbGxfYWxlcnRfc2hvdyI7aToxO3M6MTk6ImF1dG9fY29uZmlybV9vcmRlcnMiO2k6MDtzOjIzOiJzaG93X29yZGVyX3R5cGVfb3B0aW9ucyI7aToxO3M6Mjc6ImhpZGVfbWVudV9pdGVtX2ltYWdlX29uX3BvcyI7aTowO3M6Mzc6ImhpZGVfbWVudV9pdGVtX2ltYWdlX29uX2N1c3RvbWVyX3NpdGUiO2k6MDtzOjg6InRheF9tb2RlIjtzOjU6Im9yZGVyIjtzOjEzOiJ0YXhfaW5jbHVzaXZlIjtpOjA7czoyMjoiY3VzdG9tZXJfc2l0ZV9sYW5ndWFnZSI7czoyOiJlbiI7czoyNDoiZW5hYmxlX2FkbWluX3Jlc2VydmF0aW9uIjtpOjE7czoyNzoiZW5hYmxlX2N1c3RvbWVyX3Jlc2VydmF0aW9uIjtpOjE7czoxODoibWluaW11bV9wYXJ0eV9zaXplIjtpOjE7czoyNjoidGFibGVfbG9ja190aW1lb3V0X21pbnV0ZXMiO2k6MTA7fXM6MTA6IgAqAGNoYW5nZXMiO2E6MDp7fXM6MTE6IgAqAHByZXZpb3VzIjthOjA6e31zOjg6IgAqAGNhc3RzIjthOjEwOntzOjE3OiJsaWNlbnNlX2V4cGlyZV9vbiI7czo4OiJkYXRldGltZSI7czoxNToidHJpYWxfZXhwaXJlX29uIjtzOjg6ImRhdGV0aW1lIjtzOjE4OiJsaWNlbnNlX3VwZGF0ZWRfYXQiO3M6ODoiZGF0ZXRpbWUiO3M6MjM6InN1YnNjcmlwdGlvbl91cGRhdGVkX2F0IjtzOjg6ImRhdGV0aW1lIjtzOjEwOiJjcmVhdGVkX2F0IjtzOjg6ImRhdGV0aW1lIjtzOjEwOiJ1cGRhdGVkX2F0IjtzOjg6ImRhdGV0aW1lIjtzOjIzOiJjdXN0b21fZGVsaXZlcnlfb3B0aW9ucyI7czo1OiJhcnJheSI7czo5OiJpc19hY3RpdmUiO3M6NzoiYm9vbGVhbiI7czoyNDoiZW5hYmxlX2FkbWluX3Jlc2VydmF0aW9uIjtzOjc6ImJvb2xlYW4iO3M6Mjc6ImVuYWJsZV9jdXN0b21lcl9yZXNlcnZhdGlvbiI7czo3OiJib29sZWFuIjt9czoxNzoiACoAY2xhc3NDYXN0Q2FjaGUiO2E6MDp7fXM6MjE6IgAqAGF0dHJpYnV0ZUNhc3RDYWNoZSI7YTowOnt9czoxMzoiACoAZGF0ZUZvcm1hdCI7TjtzOjEwOiIAKgBhcHBlbmRzIjthOjE6e2k6MDtzOjg6ImxvZ29fdXJsIjt9czoxOToiACoAZGlzcGF0Y2hlc0V2ZW50cyI7YTowOnt9czoxNDoiACoAb2JzZXJ2YWJsZXMiO2E6MDp7fXM6MTI6IgAqAHJlbGF0aW9ucyI7YToxOntzOjc6InBhY2thZ2UiO086MTg6IkFwcFxNb2RlbHNcUGFja2FnZSI6MzM6e3M6MTM6IgAqAGNvbm5lY3Rpb24iO3M6NToibXlzcWwiO3M6ODoiACoAdGFibGUiO3M6ODoicGFja2FnZXMiO3M6MTM6IgAqAHByaW1hcnlLZXkiO3M6MjoiaWQiO3M6MTA6IgAqAGtleVR5cGUiO3M6MzoiaW50IjtzOjEyOiJpbmNyZW1lbnRpbmciO2I6MTtzOjc6IgAqAHdpdGgiO2E6MDp7fXM6MTI6IgAqAHdpdGhDb3VudCI7YTowOnt9czoxOToicHJldmVudHNMYXp5TG9hZGluZyI7YjowO3M6MTA6IgAqAHBlclBhZ2UiO2k6MTU7czo2OiJleGlzdHMiO2I6MTtzOjE4OiJ3YXNSZWNlbnRseUNyZWF0ZWQiO2I6MDtzOjI4OiIAKgBlc2NhcGVXaGVuQ2FzdGluZ1RvU3RyaW5nIjtiOjA7czoxMzoiACoAYXR0cmlidXRlcyI7YTo0MDp7czoyOiJpZCI7aTozO3M6MTI6InBhY2thZ2VfbmFtZSI7czo5OiJMaWZlIFRpbWUiO3M6NToicHJpY2UiO3M6NjoiMTk5LjAwIjtzOjEwOiJjcmVhdGVkX2F0IjtzOjE5OiIyMDI1LTExLTAyIDA2OjA2OjEwIjtzOjEwOiJ1cGRhdGVkX2F0IjtzOjE5OiIyMDI1LTExLTA0IDA3OjM2OjUwIjtzOjExOiJjdXJyZW5jeV9pZCI7aToxO3M6MTE6ImRlc2NyaXB0aW9uIjtzOjMzOiJUaGlzIGlzIGEgbGlmZXRpbWUgYWNjZXNzIHBhY2thZ2UiO3M6MTI6ImFubnVhbF9wcmljZSI7TjtzOjEzOiJtb250aGx5X3ByaWNlIjtOO3M6MTQ6Im1vbnRobHlfc3RhdHVzIjtpOjA7czoxMzoiYW5udWFsX3N0YXR1cyI7aTowO3M6MjE6InN0cmlwZV9hbm51YWxfcGxhbl9pZCI7TjtzOjIyOiJzdHJpcGVfbW9udGhseV9wbGFuX2lkIjtOO3M6MjM6InJhem9ycGF5X2FubnVhbF9wbGFuX2lkIjtOO3M6MjQ6InJhem9ycGF5X21vbnRobHlfcGxhbl9pZCI7TjtzOjI2OiJmbHV0dGVyd2F2ZV9hbm51YWxfcGxhbl9pZCI7TjtzOjI3OiJmbHV0dGVyd2F2ZV9tb250aGx5X3BsYW5faWQiO047czoyMzoicGF5c3RhY2tfYW5udWFsX3BsYW5faWQiO047czoyNDoicGF5c3RhY2tfbW9udGhseV9wbGFuX2lkIjtOO3M6MjE6InhlbmRpdF9hbm51YWxfcGxhbl9pZCI7TjtzOjIyOiJ4ZW5kaXRfbW9udGhseV9wbGFuX2lkIjtOO3M6MjI6InBhZGRsZV9hbm51YWxfcHJpY2VfaWQiO047czoyMzoicGFkZGxlX21vbnRobHlfcHJpY2VfaWQiO047czoyNDoicGFkZGxlX2xpZmV0aW1lX3ByaWNlX2lkIjtOO3M6MjM6InN0cmlwZV9saWZldGltZV9wbGFuX2lkIjtOO3M6MjU6InJhem9ycGF5X2xpZmV0aW1lX3BsYW5faWQiO047czoxMzoiYmlsbGluZ19jeWNsZSI7aTowO3M6MTA6InNvcnRfb3JkZXIiO2k6MztzOjEwOiJpc19wcml2YXRlIjtpOjA7czo3OiJpc19mcmVlIjtpOjA7czoxNDoiaXNfcmVjb21tZW5kZWQiO2k6MTtzOjEyOiJwYWNrYWdlX3R5cGUiO3M6ODoibGlmZXRpbWUiO3M6MTI6InRyaWFsX3N0YXR1cyI7TjtzOjEwOiJ0cmlhbF9kYXlzIjtOO3M6MzA6InRyaWFsX25vdGlmaWNhdGlvbl9iZWZvcmVfZGF5cyI7TjtzOjEzOiJ0cmlhbF9tZXNzYWdlIjtOO3M6MTk6ImFkZGl0aW9uYWxfZmVhdHVyZXMiO3M6MTE4OiJbIkNoYW5nZSBCcmFuY2giLCJFeHBvcnQgUmVwb3J0IiwiVGFibGUgUmVzZXJ2YXRpb24iLCJQYXltZW50IEdhdGV3YXkgSW50ZWdyYXRpb24iLCJUaGVtZSBTZXR0aW5nIiwiQ3VzdG9tZXIgRGlzcGxheSJdIjtzOjEyOiJicmFuY2hfbGltaXQiO2k6LTE7czo5OiJzbXNfY291bnQiO2k6LTE7czoxNzoiY2FycnlfZm9yd2FyZF9zbXMiO2k6MDt9czoxMToiACoAb3JpZ2luYWwiO2E6NDA6e3M6MjoiaWQiO2k6MztzOjEyOiJwYWNrYWdlX25hbWUiO3M6OToiTGlmZSBUaW1lIjtzOjU6InByaWNlIjtzOjY6IjE5OS4wMCI7czoxMDoiY3JlYXRlZF9hdCI7czoxOToiMjAyNS0xMS0wMiAwNjowNjoxMCI7czoxMDoidXBkYXRlZF9hdCI7czoxOToiMjAyNS0xMS0wNCAwNzozNjo1MCI7czoxMToiY3VycmVuY3lfaWQiO2k6MTtzOjExOiJkZXNjcmlwdGlvbiI7czozMzoiVGhpcyBpcyBhIGxpZmV0aW1lIGFjY2VzcyBwYWNrYWdlIjtzOjEyOiJhbm51YWxfcHJpY2UiO047czoxMzoibW9udGhseV9wcmljZSI7TjtzOjE0OiJtb250aGx5X3N0YXR1cyI7czoxOiIwIjtzOjEzOiJhbm51YWxfc3RhdHVzIjtzOjE6IjAiO3M6MjE6InN0cmlwZV9hbm51YWxfcGxhbl9pZCI7TjtzOjIyOiJzdHJpcGVfbW9udGhseV9wbGFuX2lkIjtOO3M6MjM6InJhem9ycGF5X2FubnVhbF9wbGFuX2lkIjtOO3M6MjQ6InJhem9ycGF5X21vbnRobHlfcGxhbl9pZCI7TjtzOjI2OiJmbHV0dGVyd2F2ZV9hbm51YWxfcGxhbl9pZCI7TjtzOjI3OiJmbHV0dGVyd2F2ZV9tb250aGx5X3BsYW5faWQiO047czoyMzoicGF5c3RhY2tfYW5udWFsX3BsYW5faWQiO047czoyNDoicGF5c3RhY2tfbW9udGhseV9wbGFuX2lkIjtOO3M6MjE6InhlbmRpdF9hbm51YWxfcGxhbl9pZCI7TjtzOjIyOiJ4ZW5kaXRfbW9udGhseV9wbGFuX2lkIjtOO3M6MjI6InBhZGRsZV9hbm51YWxfcHJpY2VfaWQiO047czoyMzoicGFkZGxlX21vbnRobHlfcHJpY2VfaWQiO047czoyNDoicGFkZGxlX2xpZmV0aW1lX3ByaWNlX2lkIjtOO3M6MjM6InN0cmlwZV9saWZldGltZV9wbGFuX2lkIjtOO3M6MjU6InJhem9ycGF5X2xpZmV0aW1lX3BsYW5faWQiO047czoxMzoiYmlsbGluZ19jeWNsZSI7aTowO3M6MTA6InNvcnRfb3JkZXIiO2k6MztzOjEwOiJpc19wcml2YXRlIjtpOjA7czo3OiJpc19mcmVlIjtpOjA7czoxNDoiaXNfcmVjb21tZW5kZWQiO2k6MTtzOjEyOiJwYWNrYWdlX3R5cGUiO3M6ODoibGlmZXRpbWUiO3M6MTI6InRyaWFsX3N0YXR1cyI7TjtzOjEwOiJ0cmlhbF9kYXlzIjtOO3M6MzA6InRyaWFsX25vdGlmaWNhdGlvbl9iZWZvcmVfZGF5cyI7TjtzOjEzOiJ0cmlhbF9tZXNzYWdlIjtOO3M6MTk6ImFkZGl0aW9uYWxfZmVhdHVyZXMiO3M6MTE4OiJbIkNoYW5nZSBCcmFuY2giLCJFeHBvcnQgUmVwb3J0IiwiVGFibGUgUmVzZXJ2YXRpb24iLCJQYXltZW50IEdhdGV3YXkgSW50ZWdyYXRpb24iLCJUaGVtZSBTZXR0aW5nIiwiQ3VzdG9tZXIgRGlzcGxheSJdIjtzOjEyOiJicmFuY2hfbGltaXQiO2k6LTE7czo5OiJzbXNfY291bnQiO2k6LTE7czoxNzoiY2FycnlfZm9yd2FyZF9zbXMiO2k6MDt9czoxMDoiACoAY2hhbmdlcyI7YTowOnt9czoxMToiACoAcHJldmlvdXMiO2E6MDp7fXM6ODoiACoAY2FzdHMiO2E6Mzp7czoxMjoicGFja2FnZV90eXBlIjtzOjIxOiJBcHBcRW51bXNcUGFja2FnZVR5cGUiO3M6MTA6InRyaWFsX2RheXMiO3M6NzoiaW50ZWdlciI7czozMDoidHJpYWxfbm90aWZpY2F0aW9uX2JlZm9yZV9kYXlzIjtzOjc6ImludGVnZXIiO31zOjE3OiIAKgBjbGFzc0Nhc3RDYWNoZSI7YTowOnt9czoyMToiACoAYXR0cmlidXRlQ2FzdENhY2hlIjthOjA6e31zOjEzOiIAKgBkYXRlRm9ybWF0IjtOO3M6MTA6IgAqAGFwcGVuZHMiO2E6MDp7fXM6MTk6IgAqAGRpc3BhdGNoZXNFdmVudHMiO2E6MDp7fXM6MTQ6IgAqAG9ic2VydmFibGVzIjthOjA6e31zOjEyOiIAKgByZWxhdGlvbnMiO2E6MDp7fXM6MTA6IgAqAHRvdWNoZXMiO2E6MDp7fXM6Mjc6IgAqAHJlbGF0aW9uQXV0b2xvYWRDYWxsYmFjayI7TjtzOjI2OiIAKgByZWxhdGlvbkF1dG9sb2FkQ29udGV4dCI7TjtzOjEwOiJ0aW1lc3RhbXBzIjtiOjE7czoxMzoidXNlc1VuaXF1ZUlkcyI7YjowO3M6OToiACoAaGlkZGVuIjthOjA6e31zOjEwOiIAKgB2aXNpYmxlIjthOjA6e31zOjExOiIAKgBmaWxsYWJsZSI7YTowOnt9czoxMDoiACoAZ3VhcmRlZCI7YToxOntpOjA7czoyOiJpZCI7fX19czoxMDoiACoAdG91Y2hlcyI7YTowOnt9czoyNzoiACoAcmVsYXRpb25BdXRvbG9hZENhbGxiYWNrIjtOO3M6MjY6IgAqAHJlbGF0aW9uQXV0b2xvYWRDb250ZXh0IjtOO3M6MTA6InRpbWVzdGFtcHMiO2I6MTtzOjEzOiJ1c2VzVW5pcXVlSWRzIjtiOjA7czo5OiIAKgBoaWRkZW4iO2E6MDp7fXM6MTA6IgAqAHZpc2libGUiO2E6MDp7fXM6MTE6IgAqAGZpbGxhYmxlIjthOjA6e31zOjEwOiIAKgBndWFyZGVkIjthOjE6e2k6MDtzOjI6ImlkIjt9czoxNzoiY3VzdG9tZXJJcEFkZHJlc3MiO047czoyNDoiZXN0aW1hdGlvbkJpbGxpbmdBZGRyZXNzIjthOjA6e31zOjEzOiJjb2xsZWN0VGF4SWRzIjtiOjA7czo4OiJjb3Vwb25JZCI7TjtzOjE1OiJwcm9tb3Rpb25Db2RlSWQiO047czoxOToiYWxsb3dQcm9tb3Rpb25Db2RlcyI7YjowO31zOjY6ImJyYW5jaCI7TzoxNzoiQXBwXE1vZGVsc1xCcmFuY2giOjMzOntzOjEzOiIAKgBjb25uZWN0aW9uIjtzOjU6Im15c3FsIjtzOjg6IgAqAHRhYmxlIjtzOjg6ImJyYW5jaGVzIjtzOjEzOiIAKgBwcmltYXJ5S2V5IjtzOjI6ImlkIjtzOjEwOiIAKgBrZXlUeXBlIjtzOjM6ImludCI7czoxMjoiaW5jcmVtZW50aW5nIjtiOjE7czo3OiIAKgB3aXRoIjthOjA6e31zOjEyOiIAKgB3aXRoQ291bnQiO2E6MDp7fXM6MTk6InByZXZlbnRzTGF6eUxvYWRpbmciO2I6MDtzOjEwOiIAKgBwZXJQYWdlIjtpOjE1O3M6NjoiZXhpc3RzIjtiOjE7czoxODoid2FzUmVjZW50bHlDcmVhdGVkIjtiOjA7czoyODoiACoAZXNjYXBlV2hlbkNhc3RpbmdUb1N0cmluZyI7YjowO3M6MTM6IgAqAGF0dHJpYnV0ZXMiO2E6MTk6e3M6MjoiaWQiO2k6MTtzOjExOiJ1bmlxdWVfaGFzaCI7czoyMDoiMzdiMmEwMmI0NWFhMzIyMjQwYzgiO3M6MTM6InJlc3RhdXJhbnRfaWQiO2k6MTtzOjQ6Im5hbWUiO3M6NjoiT2x1dmlsIjtzOjE4OiJjbG9uZWRfYnJhbmNoX25hbWUiO047czoxNjoiY2xvbmVkX2JyYW5jaF9pZCI7TjtzOjEzOiJpc19tZW51X2Nsb25lIjtpOjA7czoyNDoiaXNfaXRlbV9jYXRlZ29yaWVzX2Nsb25lIjtpOjA7czoxOToiaXNfbWVudV9pdGVtc19jbG9uZSI7aTowO3M6MjM6ImlzX2l0ZW1fbW9kaWZpZXJzX2Nsb25lIjtpOjA7czoyOToiaXNfY2xvbmVfcmVzZXJ2YXRpb25fc2V0dGluZ3MiO2k6MDtzOjI2OiJpc19jbG9uZV9kZWxpdmVyeV9zZXR0aW5ncyI7aTowO3M6MjA6ImlzX2Nsb25lX2tvdF9zZXR0aW5nIjtpOjA7czoyNToiaXNfbW9kaWZpZXJzX2dyb3Vwc19jbG9uZSI7aTowO3M6NzoiYWRkcmVzcyI7czoyNToiTWFpbiBTdHJlZXQsIE9sdXZpbCAzMjM2MCI7czoxMDoiY3JlYXRlZF9hdCI7czoxOToiMjAyNS0xMS0wMiAwNjowNjoxMSI7czoxMDoidXBkYXRlZF9hdCI7czoxOToiMjAyNS0xMS0wNCAwNDo0NDoyNCI7czozOiJsYXQiO3M6MTA6IjI2LjkxMjUwMDAiO3M6MzoibG5nIjtzOjEwOiI3NS43ODc1MDAwIjt9czoxMToiACoAb3JpZ2luYWwiO2E6MTk6e3M6MjoiaWQiO2k6MTtzOjExOiJ1bmlxdWVfaGFzaCI7czoyMDoiMzdiMmEwMmI0NWFhMzIyMjQwYzgiO3M6MTM6InJlc3RhdXJhbnRfaWQiO2k6MTtzOjQ6Im5hbWUiO3M6NjoiT2x1dmlsIjtzOjE4OiJjbG9uZWRfYnJhbmNoX25hbWUiO047czoxNjoiY2xvbmVkX2JyYW5jaF9pZCI7TjtzOjEzOiJpc19tZW51X2Nsb25lIjtpOjA7czoyNDoiaXNfaXRlbV9jYXRlZ29yaWVzX2Nsb25lIjtpOjA7czoxOToiaXNfbWVudV9pdGVtc19jbG9uZSI7aTowO3M6MjM6ImlzX2l0ZW1fbW9kaWZpZXJzX2Nsb25lIjtpOjA7czoyOToiaXNfY2xvbmVfcmVzZXJ2YXRpb25fc2V0dGluZ3MiO2k6MDtzOjI2OiJpc19jbG9uZV9kZWxpdmVyeV9zZXR0aW5ncyI7aTowO3M6MjA6ImlzX2Nsb25lX2tvdF9zZXR0aW5nIjtpOjA7czoyNToiaXNfbW9kaWZpZXJzX2dyb3Vwc19jbG9uZSI7aTowO3M6NzoiYWRkcmVzcyI7czoyNToiTWFpbiBTdHJlZXQsIE9sdXZpbCAzMjM2MCI7czoxMDoiY3JlYXRlZF9hdCI7czoxOToiMjAyNS0xMS0wMiAwNjowNjoxMSI7czoxMDoidXBkYXRlZF9hdCI7czoxOToiMjAyNS0xMS0wNCAwNDo0NDoyNCI7czozOiJsYXQiO3M6MTA6IjI2LjkxMjUwMDAiO3M6MzoibG5nIjtzOjEwOiI3NS43ODc1MDAwIjt9czoxMDoiACoAY2hhbmdlcyI7YTowOnt9czoxMToiACoAcHJldmlvdXMiO2E6MDp7fXM6ODoiACoAY2FzdHMiO2E6Mjp7czozOiJsYXQiO3M6NToiZmxvYXQiO3M6MzoibG5nIjtzOjU6ImZsb2F0Ijt9czoxNzoiACoAY2xhc3NDYXN0Q2FjaGUiO2E6MDp7fXM6MjE6IgAqAGF0dHJpYnV0ZUNhc3RDYWNoZSI7YTowOnt9czoxMzoiACoAZGF0ZUZvcm1hdCI7TjtzOjEwOiIAKgBhcHBlbmRzIjthOjA6e31zOjE5OiIAKgBkaXNwYXRjaGVzRXZlbnRzIjthOjA6e31zOjE0OiIAKgBvYnNlcnZhYmxlcyI7YTowOnt9czoxMjoiACoAcmVsYXRpb25zIjthOjA6e31zOjEwOiIAKgB0b3VjaGVzIjthOjA6e31zOjI3OiIAKgByZWxhdGlvbkF1dG9sb2FkQ2FsbGJhY2siO047czoyNjoiACoAcmVsYXRpb25BdXRvbG9hZENvbnRleHQiO047czoxMDoidGltZXN0YW1wcyI7YjoxO3M6MTM6InVzZXNVbmlxdWVJZHMiO2I6MDtzOjk6IgAqAGhpZGRlbiI7YTowOnt9czoxMDoiACoAdmlzaWJsZSI7YTowOnt9czoxMToiACoAZmlsbGFibGUiO2E6OTp7aTowO3M6NDoibmFtZSI7aToxO3M6NzoiYWRkcmVzcyI7aToyO3M6NToicGhvbmUiO2k6MztzOjU6ImVtYWlsIjtpOjQ7czoxMzoicmVzdGF1cmFudF9pZCI7aTo1O3M6OToiaXNfYWN0aXZlIjtpOjY7czoxMToidW5pcXVlX2hhc2giO2k6NztzOjM6ImxhdCI7aTo4O3M6MzoibG5nIjt9czoxMDoiACoAZ3VhcmRlZCI7YToxOntpOjA7czoyOiJpZCI7fX1zOjE3OiJwYXNzd29yZF9oYXNoX3dlYiI7czo2MDoiJDJ5JDEyJDZ2V2djd3UyNFFOdm93SG9qbjZ1d2U1Sm5rSGtaL1E4TDViaEQvQTcvRXJObWJqMS83MWhPIjtzOjg6InRpbWV6b25lIjtzOjEyOiJBc2lhL0NvbG9tYm8iO3M6NToiaXNSdGwiO2I6MDt9', 1762855266);
INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('LT3GwbxkGwXPFzLk30rwXsu8c8xtFUfhe7pNtQ1t', 2, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'YToxOTp7czo2OiJfdG9rZW4iO3M6NDA6Im1YU1BpNldOZ01xY2VkQm5oNTdWSkwwdnpUcEYwMzh1anhrY2VhZlIiO3M6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fXM6MjA6ImNoZWNrX21pZ3JhdGVfc3RhdHVzIjtzOjQ6Ikdvb2QiO3M6MTU6ImN1c3RvbWVyX2lzX3J0bCI7aTowO3M6NDoidXNlciI7TzoxNToiQXBwXE1vZGVsc1xVc2VyIjozNjp7czoxMzoiACoAY29ubmVjdGlvbiI7czo1OiJteXNxbCI7czo4OiIAKgB0YWJsZSI7czo1OiJ1c2VycyI7czoxMzoiACoAcHJpbWFyeUtleSI7czoyOiJpZCI7czoxMDoiACoAa2V5VHlwZSI7czozOiJpbnQiO3M6MTI6ImluY3JlbWVudGluZyI7YjoxO3M6NzoiACoAd2l0aCI7YTowOnt9czoxMjoiACoAd2l0aENvdW50IjthOjA6e31zOjE5OiJwcmV2ZW50c0xhenlMb2FkaW5nIjtiOjA7czoxMDoiACoAcGVyUGFnZSI7aToxNTtzOjY6ImV4aXN0cyI7YjoxO3M6MTg6Indhc1JlY2VudGx5Q3JlYXRlZCI7YjowO3M6Mjg6IgAqAGVzY2FwZVdoZW5DYXN0aW5nVG9TdHJpbmciO2I6MDtzOjEzOiIAKgBhdHRyaWJ1dGVzIjthOjI1OntzOjI6ImlkIjtpOjI7czoxMzoicmVzdGF1cmFudF9pZCI7aToxO3M6OToiYnJhbmNoX2lkIjtOO3M6NDoibmFtZSI7czoxMzoiSm9obiBEdXJhaXJhaiI7czo1OiJlbWFpbCI7czoxNDoiYWRtaW5AbWFpbC5jb20iO3M6MTI6InBob25lX251bWJlciI7TjtzOjEwOiJwaG9uZV9jb2RlIjtOO3M6MjY6InRlcm1zX2FuZF9wcml2YWN5X2FjY2VwdGVkIjtpOjA7czoyNToibWFya2V0aW5nX2VtYWlsc19hY2NlcHRlZCI7aTowO3M6MTc6ImVtYWlsX3ZlcmlmaWVkX2F0IjtOO3M6ODoicGFzc3dvcmQiO3M6NjA6IiQyeSQxMiR5WVVQT2NVOVkvQzFrTEhac25GQUkuekRaSXJrT2NJM0pyUGtRNjVvRzdtTnovQWQvQmJHUyI7czoxNzoidHdvX2ZhY3Rvcl9zZWNyZXQiO047czoyNToidHdvX2ZhY3Rvcl9yZWNvdmVyeV9jb2RlcyI7TjtzOjIzOiJ0d29fZmFjdG9yX2NvbmZpcm1lZF9hdCI7TjtzOjE0OiJyZW1lbWJlcl90b2tlbiI7TjtzOjE1OiJjdXJyZW50X3RlYW1faWQiO047czoxODoicHJvZmlsZV9waG90b19wYXRoIjtOO3M6MTA6ImNyZWF0ZWRfYXQiO3M6MTk6IjIwMjUtMTEtMDIgMDY6MDY6MTIiO3M6MTA6InVwZGF0ZWRfYXQiO3M6MTk6IjIwMjUtMTEtMTAgMTM6MDg6MTUiO3M6NjoibG9jYWxlIjtzOjI6ImVuIjtzOjk6InN0cmlwZV9pZCI7TjtzOjc6InBtX3R5cGUiO047czoxMjoicG1fbGFzdF9mb3VyIjtOO3M6MTM6InRyaWFsX2VuZHNfYXQiO047czoxMDoia2l0Y2hlbl9pZCI7Tjt9czoxMToiACoAb3JpZ2luYWwiO2E6MjU6e3M6MjoiaWQiO2k6MjtzOjEzOiJyZXN0YXVyYW50X2lkIjtpOjE7czo5OiJicmFuY2hfaWQiO047czo0OiJuYW1lIjtzOjEzOiJKb2huIER1cmFpcmFqIjtzOjU6ImVtYWlsIjtzOjE0OiJhZG1pbkBtYWlsLmNvbSI7czoxMjoicGhvbmVfbnVtYmVyIjtOO3M6MTA6InBob25lX2NvZGUiO047czoyNjoidGVybXNfYW5kX3ByaXZhY3lfYWNjZXB0ZWQiO2k6MDtzOjI1OiJtYXJrZXRpbmdfZW1haWxzX2FjY2VwdGVkIjtpOjA7czoxNzoiZW1haWxfdmVyaWZpZWRfYXQiO047czo4OiJwYXNzd29yZCI7czo2MDoiJDJ5JDEyJHlZVVBPY1U5WS9DMWtMSFpzbkZBSS56RFpJcmtPY0kzSnJQa1E2NW9HN21Oei9BZC9CYkdTIjtzOjE3OiJ0d29fZmFjdG9yX3NlY3JldCI7TjtzOjI1OiJ0d29fZmFjdG9yX3JlY292ZXJ5X2NvZGVzIjtOO3M6MjM6InR3b19mYWN0b3JfY29uZmlybWVkX2F0IjtOO3M6MTQ6InJlbWVtYmVyX3Rva2VuIjtOO3M6MTU6ImN1cnJlbnRfdGVhbV9pZCI7TjtzOjE4OiJwcm9maWxlX3Bob3RvX3BhdGgiO047czoxMDoiY3JlYXRlZF9hdCI7czoxOToiMjAyNS0xMS0wMiAwNjowNjoxMiI7czoxMDoidXBkYXRlZF9hdCI7czoxOToiMjAyNS0xMS0xMCAxMzowODoxNSI7czo2OiJsb2NhbGUiO3M6MjoiZW4iO3M6OToic3RyaXBlX2lkIjtOO3M6NzoicG1fdHlwZSI7TjtzOjEyOiJwbV9sYXN0X2ZvdXIiO047czoxMzoidHJpYWxfZW5kc19hdCI7TjtzOjEwOiJraXRjaGVuX2lkIjtOO31zOjEwOiIAKgBjaGFuZ2VzIjthOjA6e31zOjExOiIAKgBwcmV2aW91cyI7YTowOnt9czo4OiIAKgBjYXN0cyI7YTo3OntzOjE3OiJlbWFpbF92ZXJpZmllZF9hdCI7czo4OiJkYXRldGltZSI7czo4OiJwYXNzd29yZCI7czo2OiJoYXNoZWQiO3M6MTM6InJlc3RhdXJhbnRfaWQiO3M6NzoiaW50ZWdlciI7czo5OiJicmFuY2hfaWQiO3M6NzoiaW50ZWdlciI7czoxMDoia2l0Y2hlbl9pZCI7czo3OiJpbnRlZ2VyIjtzOjI2OiJ0ZXJtc19hbmRfcHJpdmFjeV9hY2NlcHRlZCI7czo3OiJib29sZWFuIjtzOjI1OiJtYXJrZXRpbmdfZW1haWxzX2FjY2VwdGVkIjtzOjc6ImJvb2xlYW4iO31zOjE3OiIAKgBjbGFzc0Nhc3RDYWNoZSI7YTowOnt9czoyMToiACoAYXR0cmlidXRlQ2FzdENhY2hlIjthOjA6e31zOjEzOiIAKgBkYXRlRm9ybWF0IjtOO3M6MTA6IgAqAGFwcGVuZHMiO2E6MTp7aTowO3M6MTc6InByb2ZpbGVfcGhvdG9fdXJsIjt9czoxOToiACoAZGlzcGF0Y2hlc0V2ZW50cyI7YTowOnt9czoxNDoiACoAb2JzZXJ2YWJsZXMiO2E6MDp7fXM6MTI6IgAqAHJlbGF0aW9ucyI7YTozOntzOjEwOiJyZXN0YXVyYW50IjtPOjIxOiJBcHBcTW9kZWxzXFJlc3RhdXJhbnQiOjM5OntzOjEzOiIAKgBjb25uZWN0aW9uIjtzOjU6Im15c3FsIjtzOjg6IgAqAHRhYmxlIjtzOjExOiJyZXN0YXVyYW50cyI7czoxMzoiACoAcHJpbWFyeUtleSI7czoyOiJpZCI7czoxMDoiACoAa2V5VHlwZSI7czozOiJpbnQiO3M6MTI6ImluY3JlbWVudGluZyI7YjoxO3M6NzoiACoAd2l0aCI7YTowOnt9czoxMjoiACoAd2l0aENvdW50IjthOjA6e31zOjE5OiJwcmV2ZW50c0xhenlMb2FkaW5nIjtiOjA7czoxMDoiACoAcGVyUGFnZSI7aToxNTtzOjY6ImV4aXN0cyI7YjoxO3M6MTg6Indhc1JlY2VudGx5Q3JlYXRlZCI7YjowO3M6Mjg6IgAqAGVzY2FwZVdoZW5DYXN0aW5nVG9TdHJpbmciO2I6MDtzOjEzOiIAKgBhdHRyaWJ1dGVzIjthOjc2OntzOjI6ImlkIjtpOjE7czo0OiJuYW1lIjtzOjc6Ik1yIENoYWkiO3M6NDoiaGFzaCI7czo3OiJtci1jaGFpIjtzOjc6ImFkZHJlc3MiO3M6MjU6Ik1haW4gU3RyZWV0LCBPbHV2aWwgMzIzNjAiO3M6MTI6InBob25lX251bWJlciI7czoxMjoiMDc0IDM5NCAyNDY0IjtzOjEwOiJwaG9uZV9jb2RlIjtpOjk0O3M6NToiZW1haWwiO3M6MTY6Im1yY2hhaUBnbWFpbC5jb20iO3M6ODoidGltZXpvbmUiO3M6MTI6IkFzaWEvQ29sb21ibyI7czo5OiJ0aGVtZV9oZXgiO3M6NzoiI0Y5NzMxNiI7czo5OiJ0aGVtZV9yZ2IiO3M6MTI6IjI0OSwgMTE1LCAyMiI7czo0OiJsb2dvIjtzOjM2OiIzNGI2NmEyMjVkOWU5NjQwNzg0M2FlNTE5YmM0M2Y1OS5qcGciO3M6MTA6ImNvdW50cnlfaWQiO2k6MjEwO3M6MTU6ImhpZGVfbmV3X29yZGVycyI7aTowO3M6MjE6ImhpZGVfbmV3X3Jlc2VydmF0aW9ucyI7aTowO3M6MjM6ImhpZGVfbmV3X3dhaXRlcl9yZXF1ZXN0IjtpOjA7czoxMToiY3VycmVuY3lfaWQiO2k6NTtzOjEyOiJsaWNlbnNlX3R5cGUiO3M6NDoiZnJlZSI7czo5OiJpc19hY3RpdmUiO2k6MTtzOjEwOiJjcmVhdGVkX2F0IjtzOjE5OiIyMDI1LTExLTAyIDA2OjA2OjExIjtzOjEwOiJ1cGRhdGVkX2F0IjtzOjE5OiIyMDI1LTExLTA0IDA1OjU1OjEwIjtzOjIzOiJjdXN0b21lcl9sb2dpbl9yZXF1aXJlZCI7aToxO3M6ODoiYWJvdXRfdXMiO3M6MTMwNDoiPHAgY2xhc3M9InRleHQtbGcgdGV4dC1ncmF5LTYwMCBtYi02Ij4KICAgICAgICAgIFdlbGNvbWUgdG8gb3VyIHJlc3RhdXJhbnQsIHdoZXJlIGdyZWF0IGZvb2QgYW5kIGdvb2QgdmliZXMgY29tZSB0b2dldGhlciEgV2UncmUgYSBsb2NhbCwgZmFtaWx5LW93bmVkIHNwb3QgdGhhdCBsb3ZlcyBicmluZ2luZyBwZW9wbGUgdG9nZXRoZXIgb3ZlciBkZWxpY2lvdXMgbWVhbHMgYW5kIHVuZm9yZ2V0dGFibGUgbW9tZW50cy4gV2hldGhlciB5b3UncmUgaGVyZSBmb3IgYSBxdWljayBiaXRlLCBhIGZhbWlseSBkaW5uZXIsIG9yIGEgY2VsZWJyYXRpb24sIHdlJ3JlIGFsbCBhYm91dCBtYWtpbmcgeW91ciB0aW1lIHdpdGggdXMgc3BlY2lhbC4KICAgICAgICA8L3A+CiAgICAgICAgPHAgY2xhc3M9InRleHQtbGcgdGV4dC1ncmF5LTYwMCBtYi02Ij4KICAgICAgICAgIE91ciBtZW51IGlzIHBhY2tlZCB3aXRoIGRpc2hlcyBtYWRlIGZyb20gZnJlc2gsIHF1YWxpdHkgaW5ncmVkaWVudHMgYmVjYXVzZSB3ZSBiZWxpZXZlIGZvb2Qgc2hvdWxkIHRhc3RlIGFzCiAgICAgICAgICBnb29kIGFzIGl0IG1ha2VzIHlvdSBmZWVsLiBGcm9tIG91ciBzaWduYXR1cmUgZGlzaGVzIHRvIHNlYXNvbmFsIHNwZWNpYWxzLCB0aGVyZSdzIGFsd2F5cyBzb21ldGhpbmcgdG8gZXhjaXRlCiAgICAgICAgICB5b3VyIHRhc3RlIGJ1ZHMuCiAgICAgICAgPC9wPgogICAgICAgIDxwIGNsYXNzPSJ0ZXh0LWxnIHRleHQtZ3JheS02MDAgbWItNiI+CiAgICAgICAgICBCdXQgd2UncmUgbm90IGp1c3QgYWJvdXQgdGhlIGZvb2TigJR3ZSdyZSBhYm91dCBjb21tdW5pdHkuIFdlIGxvdmUgc2VlaW5nIGZhbWlsaWFyIGZhY2VzIGFuZCB3ZWxjb21pbmcgbmV3IG9uZXMuCiAgICAgICAgICBPdXIgdGVhbSBpcyBhIGZ1biwgZnJpZW5kbHkgYnVuY2ggZGVkaWNhdGVkIHRvIHNlcnZpbmcgeW91IHdpdGggYSBzbWlsZSBhbmQgbWFraW5nIHN1cmUgZXZlcnkgdmlzaXQgZmVlbHMgbGlrZQogICAgICAgICAgY29taW5nIGhvbWUuCiAgICAgICAgPC9wPgogICAgICAgIDxwIGNsYXNzPSJ0ZXh0LWxnIHRleHQtZ3JheS02MDAiPgogICAgICAgICAgU28sIGNvbWUgb24gaW4sIGdyYWIgYSBzZWF0LCBhbmQgbGV0IHVzIHRha2UgY2FyZSBvZiB0aGUgcmVzdC4gV2UgY2FuJ3Qgd2FpdCB0byBzaGFyZSBvdXIgbG92ZSBvZiBmb29kIHdpdGgKICAgICAgICAgIHlvdSEKICAgICAgICA8L3A+CiAgICAgICAgPHAgY2xhc3M9InRleHQtbGcgdGV4dC1ncmF5LTgwMCBmb250LXNlbWlib2xkIG10LTYiPlNlZSB5b3Ugc29vbiEg8J+Nve+4j+KcqDwvcD4iO3M6MzA6ImFsbG93X2N1c3RvbWVyX2RlbGl2ZXJ5X29yZGVycyI7aToxO3M6Mjg6ImFsbG93X2N1c3RvbWVyX3BpY2t1cF9vcmRlcnMiO2k6MTtzOjE3OiJwaWNrdXBfZGF5c19yYW5nZSI7aTo3O3M6MjE6ImFsbG93X2N1c3RvbWVyX29yZGVycyI7aToxO3M6MjA6ImFsbG93X2RpbmVfaW5fb3JkZXJzIjtpOjE7czo4OiJzaG93X3ZlZyI7aToxO3M6MTA6InNob3dfaGFsYWwiO2k6MDtzOjEwOiJwYWNrYWdlX2lkIjtpOjM7czoxMjoicGFja2FnZV90eXBlIjtzOjg6ImxpZmV0aW1lIjtzOjY6InN0YXR1cyI7czo2OiJhY3RpdmUiO3M6MTc6ImxpY2Vuc2VfZXhwaXJlX29uIjtOO3M6MTM6InRyaWFsX2VuZHNfYXQiO047czoxODoibGljZW5zZV91cGRhdGVkX2F0IjtzOjE5OiIyMDI1LTExLTAyIDA2OjA2OjExIjtzOjIzOiJzdWJzY3JpcHRpb25fdXBkYXRlZF9hdCI7czoxOToiMjAyNS0xMS0wMiAwNjowNjoxMSI7czo5OiJzdHJpcGVfaWQiO047czo3OiJwbV90eXBlIjtOO3M6MTI6InBtX2xhc3RfZm91ciI7TjtzOjI1OiJpc193YWl0ZXJfcmVxdWVzdF9lbmFibGVkIjtpOjE7czozMjoiZGVmYXVsdF90YWJsZV9yZXNlcnZhdGlvbl9zdGF0dXMiO3M6OToiQ29uZmlybWVkIjtzOjIwOiJkaXNhYmxlX3Nsb3RfbWludXRlcyI7aTozMDtzOjE1OiJhcHByb3ZhbF9zdGF0dXMiO3M6ODoiQXBwcm92ZWQiO3M6MTY6InJlamVjdGlvbl9yZWFzb24iO047czoxMzoiZmFjZWJvb2tfbGluayI7czoyNToiaHR0cHM6Ly93d3cuZmFjZWJvb2suY29tLyI7czoxNDoiaW5zdGFncmFtX2xpbmsiO3M6MjY6Imh0dHBzOi8vd3d3Lmluc3RhZ3JhbS5jb20vIjtzOjEyOiJ0d2l0dGVyX2xpbmsiO3M6MjQ6Imh0dHBzOi8vd3d3LnR3aXR0ZXIuY29tLyI7czo5OiJ5ZWxwX2xpbmsiO047czoxNDoidGFibGVfcmVxdWlyZWQiO2k6MDtzOjE0OiJzaG93X2xvZ29fdGV4dCI7aToxO3M6MTI6Im1ldGFfa2V5d29yZCI7TjtzOjE2OiJtZXRhX2Rlc2NyaXB0aW9uIjtOO3M6MzQ6InVwbG9hZF9mYXZfaWNvbl9hbmRyb2lkX2Nocm9tZV8xOTIiO047czozNDoidXBsb2FkX2Zhdl9pY29uX2FuZHJvaWRfY2hyb21lXzUxMiI7TjtzOjMyOiJ1cGxvYWRfZmF2X2ljb25fYXBwbGVfdG91Y2hfaWNvbiI7TjtzOjE3OiJ1cGxvYWRfZmF2aWNvbl8xNiI7TjtzOjE3OiJ1cGxvYWRfZmF2aWNvbl8zMiI7TjtzOjc6ImZhdmljb24iO047czozNjoiaXNfd2FpdGVyX3JlcXVlc3RfZW5hYmxlZF9vbl9kZXNrdG9wIjtpOjE7czozNToiaXNfd2FpdGVyX3JlcXVlc3RfZW5hYmxlZF9vbl9tb2JpbGUiO2k6MTtzOjM2OiJpc193YWl0ZXJfcmVxdWVzdF9lbmFibGVkX29wZW5fYnlfcXIiO2k6MDtzOjExOiJ3ZWJtYW5pZmVzdCI7TjtzOjE1OiJlbmFibGVfdGlwX3Nob3AiO2k6MTtzOjE0OiJlbmFibGVfdGlwX3BvcyI7aToxO3M6MjU6ImlzX3B3YV9pbnN0YWxsX2FsZXJ0X3Nob3ciO2k6MTtzOjE5OiJhdXRvX2NvbmZpcm1fb3JkZXJzIjtpOjA7czoyMzoic2hvd19vcmRlcl90eXBlX29wdGlvbnMiO2k6MTtzOjI3OiJoaWRlX21lbnVfaXRlbV9pbWFnZV9vbl9wb3MiO2k6MDtzOjM3OiJoaWRlX21lbnVfaXRlbV9pbWFnZV9vbl9jdXN0b21lcl9zaXRlIjtpOjA7czo4OiJ0YXhfbW9kZSI7czo1OiJvcmRlciI7czoxMzoidGF4X2luY2x1c2l2ZSI7aTowO3M6MjI6ImN1c3RvbWVyX3NpdGVfbGFuZ3VhZ2UiO3M6MjoiZW4iO3M6MjQ6ImVuYWJsZV9hZG1pbl9yZXNlcnZhdGlvbiI7aToxO3M6Mjc6ImVuYWJsZV9jdXN0b21lcl9yZXNlcnZhdGlvbiI7aToxO3M6MTg6Im1pbmltdW1fcGFydHlfc2l6ZSI7aToxO3M6MjY6InRhYmxlX2xvY2tfdGltZW91dF9taW51dGVzIjtpOjEwO31zOjExOiIAKgBvcmlnaW5hbCI7YTo3Njp7czoyOiJpZCI7aToxO3M6NDoibmFtZSI7czo3OiJNciBDaGFpIjtzOjQ6Imhhc2giO3M6NzoibXItY2hhaSI7czo3OiJhZGRyZXNzIjtzOjI1OiJNYWluIFN0cmVldCwgT2x1dmlsIDMyMzYwIjtzOjEyOiJwaG9uZV9udW1iZXIiO3M6MTI6IjA3NCAzOTQgMjQ2NCI7czoxMDoicGhvbmVfY29kZSI7czoyOiI5NCI7czo1OiJlbWFpbCI7czoxNjoibXJjaGFpQGdtYWlsLmNvbSI7czo4OiJ0aW1lem9uZSI7czoxMjoiQXNpYS9Db2xvbWJvIjtzOjk6InRoZW1lX2hleCI7czo3OiIjRjk3MzE2IjtzOjk6InRoZW1lX3JnYiI7czoxMjoiMjQ5LCAxMTUsIDIyIjtzOjQ6ImxvZ28iO3M6MzY6IjM0YjY2YTIyNWQ5ZTk2NDA3ODQzYWU1MTliYzQzZjU5LmpwZyI7czoxMDoiY291bnRyeV9pZCI7aToyMTA7czoxNToiaGlkZV9uZXdfb3JkZXJzIjtpOjA7czoyMToiaGlkZV9uZXdfcmVzZXJ2YXRpb25zIjtpOjA7czoyMzoiaGlkZV9uZXdfd2FpdGVyX3JlcXVlc3QiO2k6MDtzOjExOiJjdXJyZW5jeV9pZCI7aTo1O3M6MTI6ImxpY2Vuc2VfdHlwZSI7czo0OiJmcmVlIjtzOjk6ImlzX2FjdGl2ZSI7aToxO3M6MTA6ImNyZWF0ZWRfYXQiO3M6MTk6IjIwMjUtMTEtMDIgMDY6MDY6MTEiO3M6MTA6InVwZGF0ZWRfYXQiO3M6MTk6IjIwMjUtMTEtMDQgMDU6NTU6MTAiO3M6MjM6ImN1c3RvbWVyX2xvZ2luX3JlcXVpcmVkIjtpOjE7czo4OiJhYm91dF91cyI7czoxMzA0OiI8cCBjbGFzcz0idGV4dC1sZyB0ZXh0LWdyYXktNjAwIG1iLTYiPgogICAgICAgICAgV2VsY29tZSB0byBvdXIgcmVzdGF1cmFudCwgd2hlcmUgZ3JlYXQgZm9vZCBhbmQgZ29vZCB2aWJlcyBjb21lIHRvZ2V0aGVyISBXZSdyZSBhIGxvY2FsLCBmYW1pbHktb3duZWQgc3BvdCB0aGF0IGxvdmVzIGJyaW5naW5nIHBlb3BsZSB0b2dldGhlciBvdmVyIGRlbGljaW91cyBtZWFscyBhbmQgdW5mb3JnZXR0YWJsZSBtb21lbnRzLiBXaGV0aGVyIHlvdSdyZSBoZXJlIGZvciBhIHF1aWNrIGJpdGUsIGEgZmFtaWx5IGRpbm5lciwgb3IgYSBjZWxlYnJhdGlvbiwgd2UncmUgYWxsIGFib3V0IG1ha2luZyB5b3VyIHRpbWUgd2l0aCB1cyBzcGVjaWFsLgogICAgICAgIDwvcD4KICAgICAgICA8cCBjbGFzcz0idGV4dC1sZyB0ZXh0LWdyYXktNjAwIG1iLTYiPgogICAgICAgICAgT3VyIG1lbnUgaXMgcGFja2VkIHdpdGggZGlzaGVzIG1hZGUgZnJvbSBmcmVzaCwgcXVhbGl0eSBpbmdyZWRpZW50cyBiZWNhdXNlIHdlIGJlbGlldmUgZm9vZCBzaG91bGQgdGFzdGUgYXMKICAgICAgICAgIGdvb2QgYXMgaXQgbWFrZXMgeW91IGZlZWwuIEZyb20gb3VyIHNpZ25hdHVyZSBkaXNoZXMgdG8gc2Vhc29uYWwgc3BlY2lhbHMsIHRoZXJlJ3MgYWx3YXlzIHNvbWV0aGluZyB0byBleGNpdGUKICAgICAgICAgIHlvdXIgdGFzdGUgYnVkcy4KICAgICAgICA8L3A+CiAgICAgICAgPHAgY2xhc3M9InRleHQtbGcgdGV4dC1ncmF5LTYwMCBtYi02Ij4KICAgICAgICAgIEJ1dCB3ZSdyZSBub3QganVzdCBhYm91dCB0aGUgZm9vZOKAlHdlJ3JlIGFib3V0IGNvbW11bml0eS4gV2UgbG92ZSBzZWVpbmcgZmFtaWxpYXIgZmFjZXMgYW5kIHdlbGNvbWluZyBuZXcgb25lcy4KICAgICAgICAgIE91ciB0ZWFtIGlzIGEgZnVuLCBmcmllbmRseSBidW5jaCBkZWRpY2F0ZWQgdG8gc2VydmluZyB5b3Ugd2l0aCBhIHNtaWxlIGFuZCBtYWtpbmcgc3VyZSBldmVyeSB2aXNpdCBmZWVscyBsaWtlCiAgICAgICAgICBjb21pbmcgaG9tZS4KICAgICAgICA8L3A+CiAgICAgICAgPHAgY2xhc3M9InRleHQtbGcgdGV4dC1ncmF5LTYwMCI+CiAgICAgICAgICBTbywgY29tZSBvbiBpbiwgZ3JhYiBhIHNlYXQsIGFuZCBsZXQgdXMgdGFrZSBjYXJlIG9mIHRoZSByZXN0LiBXZSBjYW4ndCB3YWl0IHRvIHNoYXJlIG91ciBsb3ZlIG9mIGZvb2Qgd2l0aAogICAgICAgICAgeW91IQogICAgICAgIDwvcD4KICAgICAgICA8cCBjbGFzcz0idGV4dC1sZyB0ZXh0LWdyYXktODAwIGZvbnQtc2VtaWJvbGQgbXQtNiI+U2VlIHlvdSBzb29uISDwn42977iP4pyoPC9wPiI7czozMDoiYWxsb3dfY3VzdG9tZXJfZGVsaXZlcnlfb3JkZXJzIjtpOjE7czoyODoiYWxsb3dfY3VzdG9tZXJfcGlja3VwX29yZGVycyI7aToxO3M6MTc6InBpY2t1cF9kYXlzX3JhbmdlIjtpOjc7czoyMToiYWxsb3dfY3VzdG9tZXJfb3JkZXJzIjtpOjE7czoyMDoiYWxsb3dfZGluZV9pbl9vcmRlcnMiO2k6MTtzOjg6InNob3dfdmVnIjtpOjE7czoxMDoic2hvd19oYWxhbCI7aTowO3M6MTA6InBhY2thZ2VfaWQiO2k6MztzOjEyOiJwYWNrYWdlX3R5cGUiO3M6ODoibGlmZXRpbWUiO3M6Njoic3RhdHVzIjtzOjY6ImFjdGl2ZSI7czoxNzoibGljZW5zZV9leHBpcmVfb24iO047czoxMzoidHJpYWxfZW5kc19hdCI7TjtzOjE4OiJsaWNlbnNlX3VwZGF0ZWRfYXQiO3M6MTk6IjIwMjUtMTEtMDIgMDY6MDY6MTEiO3M6MjM6InN1YnNjcmlwdGlvbl91cGRhdGVkX2F0IjtzOjE5OiIyMDI1LTExLTAyIDA2OjA2OjExIjtzOjk6InN0cmlwZV9pZCI7TjtzOjc6InBtX3R5cGUiO047czoxMjoicG1fbGFzdF9mb3VyIjtOO3M6MjU6ImlzX3dhaXRlcl9yZXF1ZXN0X2VuYWJsZWQiO2k6MTtzOjMyOiJkZWZhdWx0X3RhYmxlX3Jlc2VydmF0aW9uX3N0YXR1cyI7czo5OiJDb25maXJtZWQiO3M6MjA6ImRpc2FibGVfc2xvdF9taW51dGVzIjtpOjMwO3M6MTU6ImFwcHJvdmFsX3N0YXR1cyI7czo4OiJBcHByb3ZlZCI7czoxNjoicmVqZWN0aW9uX3JlYXNvbiI7TjtzOjEzOiJmYWNlYm9va19saW5rIjtzOjI1OiJodHRwczovL3d3dy5mYWNlYm9vay5jb20vIjtzOjE0OiJpbnN0YWdyYW1fbGluayI7czoyNjoiaHR0cHM6Ly93d3cuaW5zdGFncmFtLmNvbS8iO3M6MTI6InR3aXR0ZXJfbGluayI7czoyNDoiaHR0cHM6Ly93d3cudHdpdHRlci5jb20vIjtzOjk6InllbHBfbGluayI7TjtzOjE0OiJ0YWJsZV9yZXF1aXJlZCI7aTowO3M6MTQ6InNob3dfbG9nb190ZXh0IjtpOjE7czoxMjoibWV0YV9rZXl3b3JkIjtOO3M6MTY6Im1ldGFfZGVzY3JpcHRpb24iO047czozNDoidXBsb2FkX2Zhdl9pY29uX2FuZHJvaWRfY2hyb21lXzE5MiI7TjtzOjM0OiJ1cGxvYWRfZmF2X2ljb25fYW5kcm9pZF9jaHJvbWVfNTEyIjtOO3M6MzI6InVwbG9hZF9mYXZfaWNvbl9hcHBsZV90b3VjaF9pY29uIjtOO3M6MTc6InVwbG9hZF9mYXZpY29uXzE2IjtOO3M6MTc6InVwbG9hZF9mYXZpY29uXzMyIjtOO3M6NzoiZmF2aWNvbiI7TjtzOjM2OiJpc193YWl0ZXJfcmVxdWVzdF9lbmFibGVkX29uX2Rlc2t0b3AiO2k6MTtzOjM1OiJpc193YWl0ZXJfcmVxdWVzdF9lbmFibGVkX29uX21vYmlsZSI7aToxO3M6MzY6ImlzX3dhaXRlcl9yZXF1ZXN0X2VuYWJsZWRfb3Blbl9ieV9xciI7aTowO3M6MTE6IndlYm1hbmlmZXN0IjtOO3M6MTU6ImVuYWJsZV90aXBfc2hvcCI7aToxO3M6MTQ6ImVuYWJsZV90aXBfcG9zIjtpOjE7czoyNToiaXNfcHdhX2luc3RhbGxfYWxlcnRfc2hvdyI7aToxO3M6MTk6ImF1dG9fY29uZmlybV9vcmRlcnMiO2k6MDtzOjIzOiJzaG93X29yZGVyX3R5cGVfb3B0aW9ucyI7aToxO3M6Mjc6ImhpZGVfbWVudV9pdGVtX2ltYWdlX29uX3BvcyI7aTowO3M6Mzc6ImhpZGVfbWVudV9pdGVtX2ltYWdlX29uX2N1c3RvbWVyX3NpdGUiO2k6MDtzOjg6InRheF9tb2RlIjtzOjU6Im9yZGVyIjtzOjEzOiJ0YXhfaW5jbHVzaXZlIjtpOjA7czoyMjoiY3VzdG9tZXJfc2l0ZV9sYW5ndWFnZSI7czoyOiJlbiI7czoyNDoiZW5hYmxlX2FkbWluX3Jlc2VydmF0aW9uIjtpOjE7czoyNzoiZW5hYmxlX2N1c3RvbWVyX3Jlc2VydmF0aW9uIjtpOjE7czoxODoibWluaW11bV9wYXJ0eV9zaXplIjtpOjE7czoyNjoidGFibGVfbG9ja190aW1lb3V0X21pbnV0ZXMiO2k6MTA7fXM6MTA6IgAqAGNoYW5nZXMiO2E6MDp7fXM6MTE6IgAqAHByZXZpb3VzIjthOjA6e31zOjg6IgAqAGNhc3RzIjthOjEwOntzOjE3OiJsaWNlbnNlX2V4cGlyZV9vbiI7czo4OiJkYXRldGltZSI7czoxNToidHJpYWxfZXhwaXJlX29uIjtzOjg6ImRhdGV0aW1lIjtzOjE4OiJsaWNlbnNlX3VwZGF0ZWRfYXQiO3M6ODoiZGF0ZXRpbWUiO3M6MjM6InN1YnNjcmlwdGlvbl91cGRhdGVkX2F0IjtzOjg6ImRhdGV0aW1lIjtzOjEwOiJjcmVhdGVkX2F0IjtzOjg6ImRhdGV0aW1lIjtzOjEwOiJ1cGRhdGVkX2F0IjtzOjg6ImRhdGV0aW1lIjtzOjIzOiJjdXN0b21fZGVsaXZlcnlfb3B0aW9ucyI7czo1OiJhcnJheSI7czo5OiJpc19hY3RpdmUiO3M6NzoiYm9vbGVhbiI7czoyNDoiZW5hYmxlX2FkbWluX3Jlc2VydmF0aW9uIjtzOjc6ImJvb2xlYW4iO3M6Mjc6ImVuYWJsZV9jdXN0b21lcl9yZXNlcnZhdGlvbiI7czo3OiJib29sZWFuIjt9czoxNzoiACoAY2xhc3NDYXN0Q2FjaGUiO2E6MDp7fXM6MjE6IgAqAGF0dHJpYnV0ZUNhc3RDYWNoZSI7YTowOnt9czoxMzoiACoAZGF0ZUZvcm1hdCI7TjtzOjEwOiIAKgBhcHBlbmRzIjthOjE6e2k6MDtzOjg6ImxvZ29fdXJsIjt9czoxOToiACoAZGlzcGF0Y2hlc0V2ZW50cyI7YTowOnt9czoxNDoiACoAb2JzZXJ2YWJsZXMiO2E6MDp7fXM6MTI6IgAqAHJlbGF0aW9ucyI7YToxOntzOjg6ImJyYW5jaGVzIjtPOjM5OiJJbGx1bWluYXRlXERhdGFiYXNlXEVsb3F1ZW50XENvbGxlY3Rpb24iOjI6e3M6ODoiACoAaXRlbXMiO2E6Mjp7aTowO086MTc6IkFwcFxNb2RlbHNcQnJhbmNoIjozMzp7czoxMzoiACoAY29ubmVjdGlvbiI7czo1OiJteXNxbCI7czo4OiIAKgB0YWJsZSI7czo4OiJicmFuY2hlcyI7czoxMzoiACoAcHJpbWFyeUtleSI7czoyOiJpZCI7czoxMDoiACoAa2V5VHlwZSI7czozOiJpbnQiO3M6MTI6ImluY3JlbWVudGluZyI7YjoxO3M6NzoiACoAd2l0aCI7YTowOnt9czoxMjoiACoAd2l0aENvdW50IjthOjA6e31zOjE5OiJwcmV2ZW50c0xhenlMb2FkaW5nIjtiOjA7czoxMDoiACoAcGVyUGFnZSI7aToxNTtzOjY6ImV4aXN0cyI7YjoxO3M6MTg6Indhc1JlY2VudGx5Q3JlYXRlZCI7YjowO3M6Mjg6IgAqAGVzY2FwZVdoZW5DYXN0aW5nVG9TdHJpbmciO2I6MDtzOjEzOiIAKgBhdHRyaWJ1dGVzIjthOjE5OntzOjI6ImlkIjtpOjE7czoxMToidW5pcXVlX2hhc2giO3M6MjA6IjM3YjJhMDJiNDVhYTMyMjI0MGM4IjtzOjEzOiJyZXN0YXVyYW50X2lkIjtpOjE7czo0OiJuYW1lIjtzOjY6Ik9sdXZpbCI7czoxODoiY2xvbmVkX2JyYW5jaF9uYW1lIjtOO3M6MTY6ImNsb25lZF9icmFuY2hfaWQiO047czoxMzoiaXNfbWVudV9jbG9uZSI7aTowO3M6MjQ6ImlzX2l0ZW1fY2F0ZWdvcmllc19jbG9uZSI7aTowO3M6MTk6ImlzX21lbnVfaXRlbXNfY2xvbmUiO2k6MDtzOjIzOiJpc19pdGVtX21vZGlmaWVyc19jbG9uZSI7aTowO3M6Mjk6ImlzX2Nsb25lX3Jlc2VydmF0aW9uX3NldHRpbmdzIjtpOjA7czoyNjoiaXNfY2xvbmVfZGVsaXZlcnlfc2V0dGluZ3MiO2k6MDtzOjIwOiJpc19jbG9uZV9rb3Rfc2V0dGluZyI7aTowO3M6MjU6ImlzX21vZGlmaWVyc19ncm91cHNfY2xvbmUiO2k6MDtzOjc6ImFkZHJlc3MiO3M6MjU6Ik1haW4gU3RyZWV0LCBPbHV2aWwgMzIzNjAiO3M6MTA6ImNyZWF0ZWRfYXQiO3M6MTk6IjIwMjUtMTEtMDIgMDY6MDY6MTEiO3M6MTA6InVwZGF0ZWRfYXQiO3M6MTk6IjIwMjUtMTEtMDQgMDQ6NDQ6MjQiO3M6MzoibGF0IjtzOjEwOiIyNi45MTI1MDAwIjtzOjM6ImxuZyI7czoxMDoiNzUuNzg3NTAwMCI7fXM6MTE6IgAqAG9yaWdpbmFsIjthOjE5OntzOjI6ImlkIjtpOjE7czoxMToidW5p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YXVyYW50X2lkIjtpOjE7czo0OiJuYW1lIjtzOjEyOiJBa2thcmFpcGF0dHUiO3M6MTg6ImNsb25lZF9icmFuY2hfbmFtZSI7TjtzOjE2OiJjbG9uZWRfYnJhbmNoX2lkIjtOO3M6MTM6ImlzX21lbnVfY2xvbmUiO2k6MDtzOjI0OiJpc19pdGVtX2NhdGVnb3JpZXNfY2xvbmUiO2k6MDtzOjE5OiJpc19tZW51X2l0ZW1zX2Nsb25lIjtpOjA7czoyMzoiaXNfaXRlbV9tb2RpZmllcnNfY2xvbmUiO2k6MDtzOjI5OiJpc19jbG9uZV9yZXNlcnZhdGlvbl9zZXR0aW5ncyI7aTowO3M6MjY6ImlzX2Nsb25lX2RlbGl2ZXJ5X3NldHRpbmdzIjtpOjA7czoyMDoiaXNfY2xvbmVfa290X3NldHRpbmciO2k6MDtzOjI1OiJpc19tb2RpZmllcnNfZ3JvdXBzX2Nsb25lIjtpOjA7czo3OiJhZGRyZXNzIjtzOjIxOiJNYWluIFN0LCBBa2thcmFpcGF0dHUiO3M6MTA6ImNyZWF0ZWRfYXQiO3M6MTk6IjIwMjUtMTEtMDIgMDY6MDY6MTEiO3M6MTA6InVwZGF0ZWRfYXQiO3M6MTk6IjIwMjUtMTEtMDQgMDQ6NDU6MTciO3M6MzoibGF0IjtzOjEwOiIyNi45MTI1MDAwIjtzOjM6ImxuZyI7czoxMDoiNzUuNzg3NTAwMCI7fXM6MTE6IgAqAG9yaWdpbmFsIjthOjE5OntzOjI6ImlkIjtpOjI7czoxMToidW5pcXVlX2hhc2giO3M6MjA6IjUwZmI3ZTI4ZWI3NWE3ZmNkY2QxIjtzOjEzOiJyZXN0YXVyYW50X2lkIjtpOjE7czo0OiJuYW1lIjtzOjEyOiJBa2thcmFpcGF0dHUiO3M6MTg6ImNsb25lZF9icmFuY2hfbmFtZSI7TjtzOjE2OiJjbG9uZWRfYnJhbmNoX2lkIjtOO3M6MTM6ImlzX21lbnVfY2xvbmUiO2k6MDtzOjI0OiJpc19pdGVtX2NhdGVnb3JpZXNfY2xvbmUiO2k6MDtzOjE5OiJpc19tZW51X2l0ZW1zX2Nsb25lIjtpOjA7czoyMzoiaXNfaXRlbV9tb2RpZmllcnNfY2xvbmUiO2k6MDtzOjI5OiJpc19jbG9u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OjM6InVybCI7czozNToiaHR0cDovL2dlbngubG9jYWwvc2V0dGluZ3M/dGFiPXJvbGUiO31zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToyO3M6MTY6InJvbGVfcGVybWlzc2lvbnMiO2E6ODk6e2k6MDtzOjExOiJDcmVhdGUgTWVudSI7aToxO3M6OToiU2hvdyBNZW51IjtpOjI7czoxMToiVXBkYXRlIE1lbnUiO2k6MztzOjExOiJEZWxldGUgTWVudSI7aTo0O3M6MTY6IkNyZWF0ZSBNZW51IEl0ZW0iO2k6NTtzOjE0OiJTaG93IE1lbnUgSXRlbSI7aTo2O3M6MTY6IlVwZGF0ZSBNZW51IEl0ZW0iO2k6NztzOjE2OiJEZWxldGUgTWVudSBJdGVtIjtpOjg7czoyMDoiQ3JlYXRlIEl0ZW0gQ2F0ZWdvcnkiO2k6OTtzOjE4OiJTaG93IEl0ZW0gQ2F0ZWdvcnkiO2k6MTA7czoyMDoiVXBkYXRlIEl0ZW0gQ2F0ZWdvcnkiO2k6MTE7czoyMDoiRGVsZXRlIEl0ZW0gQ2F0ZWdvcnkiO2k6MTI7czoxMToiQ3JlYXRlIEFyZWEiO2k6MTM7czo5OiJTaG93IEFyZWEiO2k6MTQ7czoxMToiVXBkYXRlIEFyZWEiO2k6MTU7czoxMToiRGVsZXRlIEFyZWEiO2k6MTY7czoxMjoiQ3JlYXRlIFRhYmxlIjtpOjE3O3M6MTA6IlNob3cgVGFibGUiO2k6MTg7czoxMjoiVXBkYXRlIFRhYmxlIjtpOjE5O3M6MTI6IkRlbGV0ZSBUYWJsZSI7aToyMDtzOjE4OiJDcmVhdGUgUmVzZXJ2YXRpb24iO2k6MjE7czoxNjoiU2hvdyBSZXNlcnZhdGlvbiI7aToyMjtzOjE4OiJVcGRhdGUgUmVzZXJ2YXRpb24iO2k6MjM7czoxODoiRGVsZXRlIFJlc2VydmF0aW9uIjtpOjI0O3M6MTA6Ik1hbmFnZSBLT1QiO2k6MjU7czoxMjoiQ3JlYXRlIE9yZGVyIjtpOjI2O3M6MTA6IlNob3cgT3JkZXIiO2k6Mjc7czoxMjoiVXBkYXRlIE9yZGVyIjtpOjI4O3M6MTI6IkRlbGV0ZSBPcmRlciI7aToyOTtzOjE1OiJDcmVhdGUgQ3VzdG9tZXIiO2k6MzA7czoxMzoiU2hvdyBDdXN0b21lciI7aTozMTtzOjE1OiJVcGRhdGUgQ3VzdG9tZXIiO2k6MzI7czoxNToiRGVsZXRlIEN1c3RvbWVyIjtpOjMzO3M6MTk6IkNyZWF0ZSBTdGFmZiBNZW1iZXIiO2k6MzQ7czoxNzoiU2hvdyBTdGFmZiBNZW1iZXIiO2k6MzU7czoxOToiVXBkYXRlIFN0YWZmIE1lbWJlciI7aTozNjtzOjE5OiJEZWxldGUgU3RhZmYgTWVtYmVyIjtpOjM3O3M6MjU6IkNyZWF0ZSBEZWxpdmVyeSBFeGVjdXRpdmUiO2k6Mzg7czoyMzoiU2hvdyBEZWxpdmVyeSBFeGVjdXRpdmUiO2k6Mzk7czoyNToiVXBkYXRlIERlbGl2ZXJ5IEV4ZWN1dGl2ZSI7aTo0MDtzOjI1OiJEZWxldGUgRGVsaXZlcnkgRXhlY3V0aXZlIjtpOjQxO3M6MTM6IlNob3cgUGF5bWVudHMiO2k6NDI7czoxMjoiU2hvdyBSZXBvcnRzIjtpOjQzO3M6MTU6Ik1hbmFnZSBTZXR0aW5ncyI7aTo0NDtzOjIxOiJNYW5hZ2UgV2FpdGVyIFJlcXVlc3QiO2k6NDU7czoxNDoiQ3JlYXRlIEV4cGVuc2UiO2k6NDY7czoxMjoiU2hvdyBFeHBlbnNlIjtpOjQ3O3M6MTQ6IlVwZGF0ZSBFeHBlbnNlIjtpOjQ4O3M6MTQ6IkRlbGV0ZSBFeHBlbnNlIjtpOjQ5O3M6MjM6IkNyZWF0ZSBFeHBlbnNlIENhdGVnb3J5IjtpOjUwO3M6MjE6IlNob3cgRXhwZW5zZSBDYXRlZ29yeSI7aTo1MTtzOjIzOiJVcGRhdGUgRXhwZW5zZSBDYXRlZ29yeSI7aTo1MjtzOjIzOiJEZWxldGUgRXhwZW5zZSBDYXRlZ29yeSI7aTo1MztzOjIxOiJDcmVhdGUgSW52ZW50b3J5IEl0ZW0iO2k6NTQ7czoxOToiU2hvdyBJbnZlbnRvcnkgSXRlbSI7aTo1NTtzOjIxOiJVcGRhdGUgSW52ZW50b3J5IEl0ZW0iO2k6NTY7czoyMToiRGVsZXRlIEludmVudG9yeSBJdGVtIjtpOjU3O3M6MjU6IkNyZWF0ZSBJbnZlbnRvcnkgTW92ZW1lbnQiO2k6NTg7czoyMzoiU2hvdyBJbnZlbnRvcnkgTW92ZW1lbnQiO2k6NTk7czoyNToiVXBkYXRlIEludmVudG9yeSBNb3ZlbWVudCI7aTo2MDtzOjI1OiJEZWxldGUgSW52ZW50b3J5IE1vdmVtZW50IjtpOjYxO3M6MjA6IlNob3cgSW52ZW50b3J5IFN0b2NrIjtpOjYyO3M6MTE6IkNyZWF0ZSBVbml0IjtpOjYzO3M6OToiU2hvdyBVbml0IjtpOjY0O3M6MTE6IlVwZGF0ZSBVbml0IjtpOjY1O3M6MTE6IkRlbGV0ZSBVbml0IjtpOjY2O3M6MTM6IkNyZWF0ZSBSZWNpcGUiO2k6Njc7czoxMToiU2hvdyBSZWNpcGUiO2k6Njg7czoxMzoiVXBkYXRlIFJlY2lwZSI7aTo2OTtzOjEzOiJEZWxldGUgUmVjaXBlIjtpOjcwO3M6MjE6IkNyZWF0ZSBQdXJjaGFzZSBPcmRlciI7aTo3MTtzOjE5OiJTaG93IFB1cmNoYXNlIE9yZGVyIjtpOjcyO3M6MjE6IlVwZGF0ZSBQdXJjaGFzZSBPcmRlciI7aTo3MztzOjIxOiJEZWxldGUgUHVyY2hhc2UgT3JkZXIiO2k6NzQ7czoyMToiU2hvdyBJbnZlbnRvcnkgUmVwb3J0IjtpOjc1O3M6MjU6IlVwZGF0ZSBJbnZlbnRvcnkgU2V0dGluZ3MiO2k6NzY7czoxMzoiU2hvdyBTdXBwbGllciI7aTo3NztzOjE1OiJDcmVhdGUgU3VwcGxpZXIiO2k6Nzg7czoxNToiVXBkYXRlIFN1cHBsaWVyIjtpOjc5O3M6MTU6IkRlbGV0ZSBTdXBwbGllciI7aTo4MDtzOjI5OiJNYW5hZ2UgQ2FzaCBSZWdpc3RlciBTZXR0aW5ncyI7aTo4MTtzOjI2OiJWaWV3IENhc2ggUmVnaXN0ZXIgUmVwb3J0cyI7aTo4MjtzOjI1OiJNYW5hZ2UgQ2FzaCBEZW5vbWluYXRpb25zIjtpOjgzO3M6MjE6IkFwcHJvdmUgQ2FzaCBSZWdpc3RlciI7aTo4NDtzOjE4OiJPcGVuIENhc2ggUmVnaXN0ZXIiO2k6ODU7czoxODoiU2hvdyBLaXRjaGVuIFBsYWNlIjtpOjg2O3M6MjA6IkNyZWF0ZSBLaXRjaGVuIFBsYWNlIjtpOjg3O3M6MjA6IlVwZGF0ZSBLaXRjaGVuIFBsYWNlIjtpOjg4O3M6MjA6IkRlbGV0ZSBLaXRjaGVuIFBsYWNlIjt9czoxMDoicmVzdGF1cmFudCI7TzoyMToiQXBwXE1vZGVsc1xSZXN0YXVyYW50IjozOTp7czoxMzoiACoAY29ubmVjdGlvbiI7czo1OiJteXNxbCI7czo4OiIAKgB0YWJsZSI7czoxMToicmVzdGF1cmFudHMiO3M6MTM6IgAqAHByaW1hcnlLZXkiO3M6MjoiaWQiO3M6MTA6IgAqAGtleVR5cGUiO3M6MzoiaW50IjtzOjEyOiJpbmNyZW1lbnRpbmciO2I6MTtzOjc6IgAqAHdpdGgiO2E6MDp7fXM6MTI6IgAqAHdpdGhDb3VudCI7YTowOnt9czoxOToicHJldmVudHNMYXp5TG9hZGluZyI7YjowO3M6MTA6IgAqAHBlclBhZ2UiO2k6MTU7czo2OiJleGlzdHMiO2I6MTtzOjE4OiJ3YXNSZWNlbnRseUNyZWF0ZWQiO2I6MDtzOjI4OiIAKgBlc2NhcGVXaGVuQ2FzdGluZ1RvU3RyaW5nIjtiOjA7czoxMzoiACoAYXR0cmlidXRlcyI7YTo3Njp7czoyOiJpZCI7aToxO3M6NDoibmFtZSI7czo3OiJNciBDaGFpIjtzOjQ6Imhhc2giO3M6NzoibXItY2hhaSI7czo3OiJhZGRyZXNzIjtzOjI1OiJNYWluIFN0cmVldCwgT2x1dmlsIDMyMzYwIjtzOjEyOiJwaG9uZV9udW1iZXIiO3M6MTI6IjA3NCAzOTQgMjQ2NCI7czoxMDoicGhvbmVfY29kZSI7aTo5NDtzOjU6ImVtYWlsIjtzOjE2OiJtcmNoYWlAZ21haWwuY29tIjtzOjg6InRpbWV6b25lIjtzOjEyOiJBc2lhL0NvbG9tYm8iO3M6OToidGhlbWVfaGV4IjtzOjc6IiNGOTczMTYiO3M6OToidGhlbWVfcmdiIjtzOjEyOiIyNDksIDExNSwgMjIiO3M6NDoibG9nbyI7czozNjoiMzRiNjZhMjI1ZDllOTY0MDc4NDNhZTUxOWJjNDNmNTkuanBnIjtzOjEwOiJjb3VudHJ5X2lkIjtpOjIxMDtzOjE1OiJoaWRlX25ld19vcmRlcnMiO2k6MDtzOjIxOiJoaWRlX25ld19yZXNlcnZhdGlvbnMiO2k6MDtzOjIzOiJoaWRlX25ld193YWl0ZXJfcmVxdWVzdCI7aTowO3M6MTE6ImN1cnJlbmN5X2lkIjtpOjU7czoxMjoibGljZW5zZV90eXBlIjtzOjQ6ImZyZWUiO3M6OToiaXNfYWN0aXZlIjtpOjE7czoxMDoiY3JlYXRlZF9hdCI7czoxOToiMjAyNS0xMS0wMiAwNjowNjoxMSI7czoxMDoidXBkYXRlZF9hdCI7czoxOToiMjAyNS0xMS0wNCAwNTo1NToxMCI7czoyMzoiY3VzdG9tZXJfbG9naW5fcmVxdWlyZWQiO2k6MTtzOjg6ImFib3V0X3VzIjtzOjEzMDQ6IjxwIGNsYXNzPSJ0ZXh0LWxnIHRleHQtZ3JheS02MDAgbWItNiI+CiAgICAgICAgICBXZWxjb21lIHRvIG91ciByZXN0YXVyYW50LCB3aGVyZSBncmVhdCBmb29kIGFuZCBnb29kIHZpYmVzIGNvbWUgdG9nZXRoZXIhIFdlJ3JlIGEgbG9jYWwsIGZhbWlseS1vd25lZCBzcG90IHRoYXQgbG92ZXMgYnJpbmdpbmcgcGVvcGxlIHRvZ2V0aGVyIG92ZXIgZGVsaWNpb3VzIG1lYWxzIGFuZCB1bmZvcmdldHRhYmxlIG1vbWVudHMuIFdoZXRoZXIgeW91J3JlIGhlcmUgZm9yIGEgcXVpY2sgYml0ZSwgYSBmYW1pbHkgZGlubmVyLCBvciBhIGNlbGVicmF0aW9uLCB3ZSdyZSBhbGwgYWJvdXQgbWFraW5nIHlvdXIgdGltZSB3aXRoIHVzIHNwZWNpYWwuCiAgICAgICAgPC9wPgogICAgICAgIDxwIGNsYXNzPSJ0ZXh0LWxnIHRleHQtZ3JheS02MDAgbWItNiI+CiAgICAgICAgICBPdXIgbWVudSBpcyBwYWNrZWQgd2l0aCBkaXNoZXMgbWFkZSBmcm9tIGZyZXNoLCBxdWFsaXR5IGluZ3JlZGllbnRzIGJlY2F1c2Ugd2UgYmVsaWV2ZSBmb29kIHNob3VsZCB0YXN0ZSBhcwogICAgICAgICAgZ29vZCBhcyBpdCBtYWtlcyB5b3UgZmVlbC4gRnJvbSBvdXIgc2lnbmF0dXJlIGRpc2hlcyB0byBzZWFzb25hbCBzcGVjaWFscywgdGhlcmUncyBhbHdheXMgc29tZXRoaW5nIHRvIGV4Y2l0ZQogICAgICAgICAgeW91ciB0YXN0ZSBidWRzLgogICAgICAgIDwvcD4KICAgICAgICA8cCBjbGFzcz0idGV4dC1sZyB0ZXh0LWdyYXktNjAwIG1iLTYiPgogICAgICAgICAgQnV0IHdlJ3JlIG5vdCBqdXN0IGFib3V0IHRoZSBmb29k4oCUd2UncmUgYWJvdXQgY29tbXVuaXR5LiBXZSBsb3ZlIHNlZWluZyBmYW1pbGlhciBmYWNlcyBhbmQgd2VsY29taW5nIG5ldyBvbmVzLgogICAgICAgICAgT3VyIHRlYW0gaXMgYSBmdW4sIGZyaWVuZGx5IGJ1bmNoIGRlZGljYXRlZCB0byBzZXJ2aW5nIHlvdSB3aXRoIGEgc21pbGUgYW5kIG1ha2luZyBzdXJlIGV2ZXJ5IHZpc2l0IGZlZWxzIGxpa2UKICAgICAgICAgIGNvbWluZyBob21lLgogICAgICAgIDwvcD4KICAgICAgICA8cCBjbGFzcz0idGV4dC1sZyB0ZXh0LWdyYXktNjAwIj4KICAgICAgICAgIFNvLCBjb21lIG9uIGluLCBncmFiIGEgc2VhdCwgYW5kIGxldCB1cyB0YWtlIGNhcmUgb2YgdGhlIHJlc3QuIFdlIGNhbid0IHdhaXQgdG8gc2hhcmUgb3VyIGxvdmUgb2YgZm9vZCB3aXRoCiAgICAgICAgICB5b3UhCiAgICAgICAgPC9wPgogICAgICAgIDxwIGNsYXNzPSJ0ZXh0LWxnIHRleHQtZ3JheS04MDAgZm9udC1zZW1pYm9sZCBtdC02Ij5TZWUgeW91IHNvb24hIPCfjb3vuI/inKg8L3A+IjtzOjMwOiJhbGxvd19jdXN0b21lcl9kZWxpdmVyeV9vcmRlcnMiO2k6MTtzOjI4OiJhbGxvd19jdXN0b21lcl9waWNrdXBfb3JkZXJzIjtpOjE7czoxNzoicGlja3VwX2RheXNfcmFuZ2UiO2k6NztzOjIxOiJhbGxvd19jdXN0b21lcl9vcmRlcnMiO2k6MTtzOjIwOiJhbGxvd19kaW5lX2luX29yZGVycyI7aToxO3M6ODoic2hvd192ZWciO2k6MTtzOjEwOiJzaG93X2hhbGFsIjtpOjA7czoxMDoicGFja2FnZV9pZCI7aTozO3M6MTI6InBhY2thZ2VfdHlwZSI7czo4OiJsaWZldGltZSI7czo2OiJzdGF0dXMiO3M6NjoiYWN0aXZlIjtzOjE3OiJsaWNlbnNlX2V4cGlyZV9vbiI7TjtzOjEzOiJ0cmlhbF9lbmRzX2F0IjtOO3M6MTg6ImxpY2Vuc2VfdXBkYXRlZF9hdCI7czoxOToiMjAyNS0xMS0wMiAwNjowNjoxMSI7czoyMzoic3Vic2NyaXB0aW9uX3VwZGF0ZWRfYXQiO3M6MTk6IjIwMjUtMTEtMDIgMDY6MDY6MTEiO3M6OToic3RyaXBlX2lkIjtOO3M6NzoicG1fdHlwZSI7TjtzOjEyOiJwbV9sYXN0X2ZvdXIiO047czoyNToiaXNfd2FpdGVyX3JlcXVlc3RfZW5hYmxlZCI7aToxO3M6MzI6ImRlZmF1bHRfdGFibGVfcmVzZXJ2YXRpb25fc3RhdHVzIjtzOjk6IkNvbmZpcm1lZCI7czoyMDoiZGlzYWJsZV9zbG90X21pbnV0ZXMiO2k6MzA7czoxNToiYXBwcm92YWxfc3RhdHVzIjtzOjg6IkFwcHJvdmVkIjtzOjE2OiJyZWplY3Rpb25fcmVhc29uIjtOO3M6MTM6ImZhY2Vib29rX2xpbmsiO3M6MjU6Imh0dHBzOi8vd3d3LmZhY2Vib29rLmNvbS8iO3M6MTQ6Imluc3RhZ3JhbV9saW5rIjtzOjI2OiJodHRwczovL3d3dy5pbnN0YWdyYW0uY29tLyI7czoxMjoidHdpdHRlcl9saW5rIjtzOjI0OiJodHRwczovL3d3dy50d2l0dGVyLmNvbS8iO3M6OToieWVscF9saW5rIjtOO3M6MTQ6InRhYmxlX3JlcXVpcmVkIjtpOjA7czoxNDoic2hvd19sb2dvX3RleHQiO2k6MTtzOjEyOiJtZXRhX2tleXdvcmQiO047czoxNjoibWV0YV9kZXNjcmlwdGlvbiI7TjtzOjM0OiJ1cGxvYWRfZmF2X2ljb25fYW5kcm9pZF9jaHJvbWVfMTkyIjtOO3M6MzQ6InVwbG9hZF9mYXZfaWNvbl9hbmRyb2lkX2Nocm9tZV81MTIiO047czozMjoidXBsb2FkX2Zhdl9pY29uX2FwcGxlX3RvdWNoX2ljb24iO047czoxNzoidXBsb2FkX2Zhdmljb25fMTYiO047czoxNzoidXBsb2FkX2Zhdmljb25fMzIiO047czo3OiJmYXZpY29uIjtOO3M6MzY6ImlzX3dhaXRlcl9yZXF1ZXN0X2VuYWJsZWRfb25fZGVza3RvcCI7aToxO3M6MzU6ImlzX3dhaXRlcl9yZXF1ZXN0X2VuYWJsZWRfb25fbW9iaWxlIjtpOjE7czozNjoiaXNfd2FpdGVyX3JlcXVlc3RfZW5hYmxlZF9vcGVuX2J5X3FyIjtpOjA7czoxMToid2VibWFuaWZlc3QiO047czoxNToiZW5hYmxlX3RpcF9zaG9wIjtpOjE7czoxNDoiZW5hYmxlX3RpcF9wb3MiO2k6MTtzOjI1OiJpc19wd2FfaW5zdGFsbF9hbGVydF9zaG93IjtpOjE7czoxOToiYXV0b19jb25maXJtX29yZGVycyI7aTowO3M6MjM6InNob3dfb3JkZXJfdHlwZV9vcHRpb25zIjtpOjE7czoyNzoiaGlkZV9tZW51X2l0ZW1faW1hZ2Vfb25fcG9zIjtpOjA7czozNzoiaGlkZV9tZW51X2l0ZW1faW1hZ2Vfb25fY3VzdG9tZXJfc2l0ZSI7aTowO3M6ODoidGF4X21vZGUiO3M6NToib3JkZXIiO3M6MTM6InRheF9pbmNsdXNpdmUiO2k6MDtzOjIyOiJjdXN0b21lcl9zaXRlX2xhbmd1YWdlIjtzOjI6ImVuIjtzOjI0OiJlbmFibGVfYWRtaW5fcmVzZXJ2YXRpb24iO2k6MTtzOjI3OiJlbmFibGVfY3VzdG9tZXJfcmVzZXJ2YXRpb24iO2k6MTtzOjE4OiJtaW5pbXVtX3BhcnR5X3NpemUiO2k6MTtzOjI2OiJ0YWJsZV9sb2NrX3RpbWVvdXRfbWludXRlcyI7aToxMDt9czoxMToiACoAb3JpZ2luYWwiO2E6NzY6e3M6MjoiaWQiO2k6MTtzOjQ6Im5hbWUiO3M6NzoiTXIgQ2hhaSI7czo0OiJoYXNoIjtzOjc6Im1yLWNoYWkiO3M6NzoiYWRkcmVzcyI7czoyNToiTWFpbiBTdHJlZXQsIE9sdXZpbCAzMjM2MCI7czoxMjoicGhvbmVfbnVtYmVyIjtzOjEyOiIwNzQgMzk0IDI0NjQiO3M6MTA6InBob25lX2NvZGUiO3M6MjoiOTQiO3M6NToiZW1haWwiO3M6MTY6Im1yY2hhaUBnbWFpbC5jb20iO3M6ODoidGltZXpvbmUiO3M6MTI6IkFzaWEvQ29sb21ibyI7czo5OiJ0aGVtZV9oZXgiO3M6NzoiI0Y5NzMxNiI7czo5OiJ0aGVtZV9yZ2IiO3M6MTI6IjI0OSwgMTE1LCAyMiI7czo0OiJsb2dvIjtzOjM2OiIzNGI2NmEyMjVkOWU5NjQwNzg0M2FlNTE5YmM0M2Y1OS5qcGciO3M6MTA6ImNvdW50cnlfaWQiO2k6MjEwO3M6MTU6ImhpZGVfbmV3X29yZGVycyI7aTowO3M6MjE6ImhpZGVfbmV3X3Jlc2VydmF0aW9ucyI7aTowO3M6MjM6ImhpZGVfbmV3X3dhaXRlcl9yZXF1ZXN0IjtpOjA7czoxMToiY3VycmVuY3lfaWQiO2k6NTtzOjEyOiJsaWNlbnNlX3R5cGUiO3M6NDoiZnJlZSI7czo5OiJpc19hY3RpdmUiO2k6MTtzOjEwOiJjcmVhdGVkX2F0IjtzOjE5OiIyMDI1LTExLTAyIDA2OjA2OjExIjtzOjEwOiJ1cGRhdGVkX2F0IjtzOjE5OiIyMDI1LTExLTA0IDA1OjU1OjEwIjtzOjIzOiJjdXN0b21lcl9sb2dpbl9yZXF1aXJlZCI7aToxO3M6ODoiYWJvdXRfdXMiO3M6MTMwNDoiPHAgY2xhc3M9InRleHQtbGcgdGV4dC1ncmF5LTYwMCBtYi02Ij4KICAgICAgICAgIFdlbGNvbWUgdG8gb3VyIHJlc3RhdXJhbnQsIHdoZXJlIGdyZWF0IGZvb2QgYW5kIGdvb2QgdmliZXMgY29tZSB0b2dldGhlciEgV2UncmUgYSBsb2NhbCwgZmFtaWx5LW93bmVkIHNwb3QgdGhhdCBsb3ZlcyBicmluZ2luZyBwZW9wbGUgdG9nZXRoZXIgb3ZlciBkZWxpY2lvdXMgbWVhbHMgYW5kIHVuZm9yZ2V0dGFibGUgbW9tZW50cy4gV2hldGhlciB5b3UncmUgaGVyZSBmb3IgYSBxdWljayBiaXRlLCBhIGZhbWlseSBkaW5uZXIsIG9yIGEgY2VsZWJyYXRpb24sIHdlJ3JlIGFsbCBhYm91dCBtYWtpbmcgeW91ciB0aW1lIHdpdGggdXMgc3BlY2lhbC4KICAgICAgICA8L3A+CiAgICAgICAgPHAgY2xhc3M9InRleHQtbGcgdGV4dC1ncmF5LTYwMCBtYi02Ij4KICAgICAgICAgIE91ciBtZW51IGlzIHBhY2tlZCB3aXRoIGRpc2hlcyBtYWRlIGZyb20gZnJlc2gsIHF1YWxpdHkgaW5ncmVkaWVudHMgYmVjYXVzZSB3ZSBiZWxpZXZlIGZvb2Qgc2hvdWxkIHRhc3RlIGFzCiAgICAgICAgICBnb29kIGFzIGl0IG1ha2VzIHlvdSBmZWVsLiBGcm9tIG91ciBzaWduYXR1cmUgZGlzaGVzIHRvIHNlYXNvbmFsIHNwZWNpYWxzLCB0aGVyZSdzIGFsd2F5cyBzb21ldGhpbmcgdG8gZXhjaXRlCiAgICAgICAgICB5b3VyIHRhc3RlIGJ1ZHMuCiAgICAgICAgPC9wPgogICAgICAgIDxwIGNsYXNzPSJ0ZXh0LWxnIHRleHQtZ3JheS02MDAgbWItNiI+CiAgICAgICAgICBCdXQgd2UncmUgbm90IGp1c3QgYWJvdXQgdGhlIGZvb2TigJR3ZSdyZSBhYm91dCBjb21tdW5pdHkuIFdlIGxvdmUgc2VlaW5nIGZhbWlsaWFyIGZhY2VzIGFuZCB3ZWxjb21pbmcgbmV3IG9uZXMuCiAgICAgICAgICBPdXIgdGVhbSBpcyBhIGZ1biwgZnJpZW5kbHkgYnVuY2ggZGVkaWNhdGVkIHRvIHNlcnZpbmcgeW91IHdpdGggYSBzbWlsZSBhbmQgbWFraW5nIHN1cmUgZXZlcnkgdmlzaXQgZmVlbHMgbGlrZQogICAgICAgICAgY29taW5nIGhvbWUuCiAgICAgICAgPC9wPgogICAgICAgIDxwIGNsYXNzPSJ0ZXh0LWxnIHRleHQtZ3JheS02MDAiPgogICAgICAgICAgU28sIGNvbWUgb24gaW4sIGdyYWIgYSBzZWF0LCBhbmQgbGV0IHVzIHRha2UgY2FyZSBvZiB0aGUgcmVzdC4gV2UgY2FuJ3Qgd2FpdCB0byBzaGFyZSBvdXIgbG92ZSBvZiBmb29kIHdpdGgKICAgICAgICAgIHlvdSEKICAgICAgICA8L3A+CiAgICAgICAgPHAgY2xhc3M9InRleHQtbGcgdGV4dC1ncmF5LTgwMCBmb250LXNlbWlib2xkIG10LTYiPlNlZSB5b3Ugc29vbiEg8J+Nve+4j+KcqDwvcD4iO3M6MzA6ImFsbG93X2N1c3RvbWVyX2RlbGl2ZXJ5X29yZGVycyI7aToxO3M6Mjg6ImFsbG93X2N1c3RvbWVyX3BpY2t1cF9vcmRlcnMiO2k6MTtzOjE3OiJwaWNrdXBfZGF5c19yYW5nZSI7aTo3O3M6MjE6ImFsbG93X2N1c3RvbWVyX29yZGVycyI7aToxO3M6MjA6ImFsbG93X2RpbmVfaW5fb3JkZXJzIjtpOjE7czo4OiJzaG93X3ZlZyI7aToxO3M6MTA6InNob3dfaGFsYWwiO2k6MDtzOjEwOiJwYWNrYWdlX2lkIjtpOjM7czoxMjoicGFja2FnZV90eXBlIjtzOjg6ImxpZmV0aW1lIjtzOjY6InN0YXR1cyI7czo2OiJhY3RpdmUiO3M6MTc6ImxpY2Vuc2VfZXhwaXJlX29uIjtOO3M6MTM6InRyaWFsX2VuZHNfYXQiO047czoxODoibGljZW5zZV91cGRhdGVkX2F0IjtzOjE5OiIyMDI1LTExLTAyIDA2OjA2OjExIjtzOjIzOiJzdWJzY3JpcHRpb25fdXBkYXRlZF9hdCI7czoxOToiMjAyNS0xMS0wMiAwNjowNjoxMSI7czo5OiJzdHJpcGVfaWQiO047czo3OiJwbV90eXBlIjtOO3M6MTI6InBtX2xhc3RfZm91ciI7TjtzOjI1OiJpc193YWl0ZXJfcmVxdWVzdF9lbmFibGVkIjtpOjE7czozMjoiZGVmYXVsdF90YWJsZV9yZXNlcnZhdGlvbl9zdGF0dXMiO3M6OToiQ29uZmlybWVkIjtzOjIwOiJkaXNhYmxlX3Nsb3RfbWludXRlcyI7aTozMDtzOjE1OiJhcHByb3ZhbF9zdGF0dXMiO3M6ODoiQXBwcm92ZWQiO3M6MTY6InJlamVjdGlvbl9yZWFzb24iO047czoxMzoiZmFjZWJvb2tfbGluayI7czoyNToiaHR0cHM6Ly93d3cuZmFjZWJvb2suY29tLyI7czoxNDoiaW5zdGFncmFtX2xpbmsiO3M6MjY6Imh0dHBzOi8vd3d3Lmluc3RhZ3JhbS5jb20vIjtzOjEyOiJ0d2l0dGVyX2xpbmsiO3M6MjQ6Imh0dHBzOi8vd3d3LnR3aXR0ZXIuY29tLyI7czo5OiJ5ZWxwX2xpbmsiO047czoxNDoidGFibGVfcmVxdWlyZWQiO2k6MDtzOjE0OiJzaG93X2xvZ29fdGV4dCI7aToxO3M6MTI6Im1ldGFfa2V5d29yZCI7TjtzOjE2OiJtZXRhX2Rlc2NyaXB0aW9uIjtOO3M6MzQ6InVwbG9hZF9mYXZfaWNvbl9hbmRyb2lkX2Nocm9tZV8xOTIiO047czozNDoidXBsb2FkX2Zhdl9pY29uX2FuZHJvaWRfY2hyb21lXzUxMiI7TjtzOjMyOiJ1cGxvYWRfZmF2X2ljb25fYXBwbGVfdG91Y2hfaWNvbiI7TjtzOjE3OiJ1cGxvYWRfZmF2aWNvbl8xNiI7TjtzOjE3OiJ1cGxvYWRfZmF2aWNvbl8zMiI7TjtzOjc6ImZhdmljb24iO047czozNjoiaXNfd2FpdGVyX3JlcXVlc3RfZW5hYmxlZF9vbl9kZXNrdG9wIjtpOjE7czozNToiaXNfd2FpdGVyX3JlcXVlc3RfZW5hYmxlZF9vbl9tb2JpbGUiO2k6MTtzOjM2OiJpc193YWl0ZXJfcmVxdWVzdF9lbmFibGVkX29wZW5fYnlfcXIiO2k6MDtzOjExOiJ3ZWJtYW5pZmVzdCI7TjtzOjE1OiJlbmFibGVfdGlwX3Nob3AiO2k6MTtzOjE0OiJlbmFibGVfdGlwX3BvcyI7aToxO3M6MjU6ImlzX3B3YV9pbnN0YWxsX2FsZXJ0X3Nob3ciO2k6MTtzOjE5OiJhdXRvX2NvbmZpcm1fb3JkZXJzIjtpOjA7czoyMzoic2hvd19vcmRlcl90eXBlX29wdGlvbnMiO2k6MTtzOjI3OiJoaWRlX21lbnVfaXRlbV9pbWFnZV9vbl9wb3MiO2k6MDtzOjM3OiJoaWRlX21lbnVfaXRlbV9pbWFnZV9vbl9jdXN0b21lcl9zaXRlIjtpOjA7czo4OiJ0YXhfbW9kZSI7czo1OiJvcmRlciI7czoxMzoidGF4X2luY2x1c2l2ZSI7aTowO3M6MjI6ImN1c3RvbWVyX3NpdGVfbGFuZ3VhZ2UiO3M6MjoiZW4iO3M6MjQ6ImVuYWJsZV9hZG1pbl9yZXNlcnZhdGlvbiI7aToxO3M6Mjc6ImVuYWJsZV9jdXN0b21lcl9yZXNlcnZhdGlvbiI7aToxO3M6MTg6Im1pbmltdW1fcGFydHlfc2l6ZSI7aToxO3M6MjY6InRhYmxlX2xvY2tfdGltZW91dF9taW51dGVzIjtpOjEwO31zOjEwOiIAKgBjaGFuZ2VzIjthOjA6e31zOjExOiIAKgBwcmV2aW91cyI7YTowOnt9czo4OiIAKgBjYXN0cyI7YToxMDp7czoxNzoibGljZW5zZV9leHBpcmVfb24iO3M6ODoiZGF0ZXRpbWUiO3M6MTU6InRyaWFsX2V4cGlyZV9vbiI7czo4OiJkYXRldGltZSI7czoxODoibGljZW5zZV91cGRhdGVkX2F0IjtzOjg6ImRhdGV0aW1lIjtzOjIzOiJzdWJzY3JpcHRpb25fdXBkYXRlZF9hdCI7czo4OiJkYXRldGltZSI7czoxMDoiY3JlYXRlZF9hdCI7czo4OiJkYXRldGltZSI7czoxMDoidXBkYXRlZF9hdCI7czo4OiJkYXRldGltZSI7czoyMzoiY3VzdG9tX2RlbGl2ZXJ5X29wdGlvbnMiO3M6NToiYXJyYXkiO3M6OToiaXNfYWN0aXZlIjtzOjc6ImJvb2xlYW4iO3M6MjQ6ImVuYWJsZV9hZG1pbl9yZXNlcnZhdGlvbiI7czo3OiJib29sZWFuIjtzOjI3OiJlbmFibGVfY3VzdG9tZXJfcmVzZXJ2YXRpb24iO3M6NzoiYm9vbGVhbiI7fXM6MTc6IgAqAGNsYXNzQ2FzdENhY2hlIjthOjA6e31zOjIxOiIAKgBhdHRyaWJ1dGVDYXN0Q2FjaGUiO2E6MDp7fXM6MTM6IgAqAGRhdGVGb3JtYXQiO047czoxMDoiACoAYXBwZW5kcyI7YToxOntpOjA7czo4OiJsb2dvX3VybCI7fXM6MTk6IgAqAGRpc3BhdGNoZXNFdmVudHMiO2E6MDp7fXM6MTQ6IgAqAG9ic2VydmFibGVzIjthOjA6e31zOjEyOiIAKgByZWxhdGlvbnMiO2E6NDp7czo4OiJicmFuY2hlcyI7TzozOToiSWxsdW1pbmF0ZVxEYXRhYmFzZVxFbG9xdWVudFxDb2xsZWN0aW9uIjoyOntzOjg6IgAqAGl0ZW1zIjthOjI6e2k6MDtPOjE3OiJBcHBcTW9kZWxzXEJyYW5jaCI6MzM6e3M6MTM6IgAqAGNvbm5lY3Rpb24iO3M6NToibXlzcWwiO3M6ODoiACoAdGFibGUiO3M6ODoiYnJhbmNoZXMiO3M6MTM6IgAqAHByaW1hcnlLZXkiO3M6MjoiaWQiO3M6MTA6IgAqAGtleVR5cGUiO3M6MzoiaW50IjtzOjEyOiJpbmNyZW1lbnRpbmciO2I6MTtzOjc6IgAqAHdpdGgiO2E6MDp7fXM6MTI6IgAqAHdpdGhDb3VudCI7YTowOnt9czoxOToicHJldmVudHNMYXp5TG9hZGluZyI7YjowO3M6MTA6IgAqAHBlclBhZ2UiO2k6MTU7czo2OiJleGlzdHMiO2I6MTtzOjE4OiJ3YXNSZWNlbnRseUNyZWF0ZWQiO2I6MDtzOjI4OiIAKgBlc2NhcGVXaGVuQ2FzdGluZ1RvU3RyaW5nIjtiOjA7czoxMzoiACoAYXR0cmlidXRlcyI7YToxOTp7czoyOiJpZCI7aToxO3M6MTE6InVuaXF1ZV9oYXNoIjtzOjIwOiIzN2IyYTAyYjQ1YWEzMjIyNDBjOCI7czoxMzoicmVzdGF1cmFudF9pZCI7aToxO3M6NDoibmFtZSI7czo2OiJPbHV2aWwiO3M6MTg6ImNsb25lZF9icmFuY2hfbmFtZSI7TjtzOjE2OiJjbG9uZWRfYnJhbmNoX2lkIjtOO3M6MTM6ImlzX21lbnVfY2xvbmUiO2k6MDtzOjI0OiJpc19pdGVtX2NhdGVnb3JpZXNfY2xvbmUiO2k6MDtzOjE5OiJpc19tZW51X2l0ZW1zX2Nsb25lIjtpOjA7czoyMzoiaXNfaXRlbV9tb2RpZmllcnNfY2xvbmUiO2k6MDtzOjI5OiJpc19jbG9uZV9yZXNlcnZhdGlvbl9zZXR0aW5ncyI7aTowO3M6MjY6ImlzX2Nsb25lX2RlbGl2ZXJ5X3NldHRpbmdzIjtpOjA7czoyMDoiaXNfY2xvbmVfa290X3NldHRpbmciO2k6MDtzOjI1OiJpc19tb2RpZmllcnNfZ3JvdXBzX2Nsb25lIjtpOjA7czo3OiJhZGRyZXNzIjtzOjI1OiJNYWluIFN0cmVldCwgT2x1dmlsIDMyMzYwIjtzOjEwOiJjcmVhdGVkX2F0IjtzOjE5OiIyMDI1LTExLTAyIDA2OjA2OjExIjtzOjEwOiJ1cGRhdGVkX2F0IjtzOjE5OiIyMDI1LTExLTA0IDA0OjQ0OjI0IjtzOjM6ImxhdCI7czoxMDoiMjYuOTEyNTAwMCI7czozOiJsbmciO3M6MTA6Ijc1Ljc4NzUwMDAiO31zOjExOiIAKgBvcmlnaW5hbCI7YToxOTp7czoyOiJpZCI7aToxO3M6MTE6InVuaXF1ZV9oYXNoIjtzOjIwOiIzN2IyYTAyYjQ1YWEzMjIyNDBjOCI7czoxMzoicmVzdGF1cmFudF9pZCI7aToxO3M6NDoibmFtZSI7czo2OiJPbHV2aWwiO3M6MTg6ImNsb25lZF9icmFuY2hfbmFtZSI7TjtzOjE2OiJjbG9uZWRfYnJhbmNoX2lkIjtOO3M6MTM6ImlzX21lbnVfY2xvbmUiO2k6MDtzOjI0OiJpc19pdGVtX2NhdGVnb3JpZXNfY2xvbmUiO2k6MDtzOjE5OiJpc19tZW51X2l0ZW1zX2Nsb25lIjtpOjA7czoyMzoiaXNfaXRlbV9tb2RpZmllcnNfY2xvbmUiO2k6MDtzOjI5OiJpc19jbG9uZV9yZXNlcnZhdGlvbl9zZXR0aW5ncyI7aTowO3M6MjY6ImlzX2Nsb25lX2RlbGl2ZXJ5X3NldHRpbmdzIjtpOjA7czoyMDoiaXNfY2xvbmVfa290X3NldHRpbmciO2k6MDtzOjI1OiJpc19tb2RpZmllcnNfZ3JvdXBzX2Nsb25lIjtpOjA7czo3OiJhZGRyZXNzIjtzOjI1OiJNYWluIFN0cmVldCwgT2x1dmlsIDMyMzYwIjtzOjEwOiJjcmVhdGVkX2F0IjtzOjE5OiIyMDI1LTExLTAyIDA2OjA2OjExIjtzOjEwOiJ1cGRhdGVkX2F0IjtzOjE5OiIyMDI1LTExLTA0IDA0OjQ0OjI0IjtzOjM6ImxhdCI7czoxMDoiMjYuOTEyNTAwMCI7czozOiJsbmciO3M6MTA6Ijc1Ljc4NzUwMDAiO31zOjEwOiIAKgBjaGFuZ2VzIjthOjA6e31zOjExOiIAKgBwcmV2aW91cyI7YTowOnt9czo4OiIAKgBjYXN0cyI7YToyOntzOjM6ImxhdCI7czo1OiJmbG9hdCI7czozOiJsbmciO3M6NToiZmxvYXQiO31zOjE3OiIAKgBjbGFzc0Nhc3RDYWNoZSI7YTowOnt9czoyMToiACoAYXR0cmlidXRlQ2FzdENhY2hlIjthOjA6e31zOjEzOiIAKgBkYXRlRm9ybWF0IjtOO3M6MTA6IgAqAGFwcGVuZHMiO2E6MDp7fXM6MTk6IgAqAGRpc3BhdGNoZXNFdmVudHMiO2E6MDp7fXM6MTQ6IgAqAG9ic2VydmFibGVzIjthOjA6e31zOjEyOiIAKgByZWxhdGlvbnMiO2E6MDp7fXM6MTA6IgAqAHRvdWNoZXMiO2E6MDp7fXM6Mjc6IgAqAHJlbGF0aW9uQXV0b2xvYWRDYWxsYmFjayI7TjtzOjI2OiIAKgByZWxhdGlvbkF1dG9sb2FkQ29udGV4dCI7TjtzOjEwOiJ0aW1lc3RhbXBzIjtiOjE7czoxMzoidXNlc1VuaXF1ZUlkcyI7YjowO3M6OToiACoAaGlkZGVuIjthOjA6e31zOjEwOiIAKgB2aXNpYmxlIjthOjA6e31zOjExOiIAKgBmaWxsYWJsZSI7YTo5OntpOjA7czo0OiJuYW1lIjtpOjE7czo3OiJhZGRyZXNzIjtpOjI7czo1OiJwaG9uZSI7aTozO3M6NToiZW1haWwiO2k6NDtzOjEzOiJyZXN0YXVyYW50X2lkIjtpOjU7czo5OiJpc19hY3RpdmUiO2k6NjtzOjExOiJ1bmlxdWVfaGFzaCI7aTo3O3M6MzoibGF0IjtpOjg7czozOiJsbmciO31zOjEwOiIAKgBndWFyZGVkIjthOjE6e2k6MDtzOjI6ImlkIjt9fWk6MTtPOjE3OiJBcHBcTW9kZWxzXEJyYW5jaCI6MzM6e3M6MTM6IgAqAGNvbm5lY3Rpb24iO3M6NToibXlzcWwiO3M6ODoiACoAdGFibGUiO3M6ODoiYnJhbmNoZXMiO3M6MTM6IgAqAHByaW1hcnlLZXkiO3M6MjoiaWQiO3M6MTA6IgAqAGtleVR5cGUiO3M6MzoiaW50IjtzOjEyOiJpbmNyZW1lbnRpbmciO2I6MTtzOjc6IgAqAHdpdGgiO2E6MDp7fXM6MTI6IgAqAHdpdGhDb3VudCI7YTowOnt9czoxOToicHJldmVudHNMYXp5TG9hZGluZyI7YjowO3M6MTA6IgAqAHBlclBhZ2UiO2k6MTU7czo2OiJleGlzdHMiO2I6MTtzOjE4OiJ3YXNSZWNlbnRseUNyZWF0ZWQiO2I6MDtzOjI4OiIAKgBlc2NhcGVXaGVuQ2FzdGluZ1RvU3RyaW5nIjtiOjA7czoxMzoiACoAYXR0cmlidXRlcyI7YToxOTp7czoyOiJpZCI7aToyO3M6MTE6InVuaXF1ZV9oYXNoIjtzOjIwOiI1MGZiN2UyOGViNzVhN2ZjZGNkMSI7czoxMzoicmVzdGF1cmFudF9pZCI7aToxO3M6NDoibmFtZSI7czoxMjoiQWtrYXJhaXBhdHR1IjtzOjE4OiJjbG9uZWRfYnJhbmNoX25hbWUiO047czoxNjoiY2xvbmVkX2JyYW5jaF9pZCI7TjtzOjEzOiJpc19tZW51X2Nsb25lIjtpOjA7czoyNDoiaXNfaXRlbV9jYXRlZ29yaWVzX2Nsb25lIjtpOjA7czoxOToiaXNfbWVudV9pdGVtc19jbG9uZSI7aTowO3M6MjM6ImlzX2l0ZW1fbW9kaWZpZXJzX2Nsb25lIjtpOjA7czoyOToiaXNfY2xvbmVfcmVzZXJ2YXRpb25fc2V0dGluZ3MiO2k6MDtzOjI2OiJpc19jbG9uZV9kZWxpdmVyeV9zZXR0aW5ncyI7aTowO3M6MjA6ImlzX2Nsb25lX2tvdF9zZXR0aW5nIjtpOjA7czoyNToiaXNfbW9kaWZpZXJzX2dyb3Vwc19jbG9uZSI7aTowO3M6NzoiYWRkcmVzcyI7czoyMToiTWFpbiBTdCwgQWtrYXJhaXBhdHR1IjtzOjEwOiJjcmVhdGVkX2F0IjtzOjE5OiIyMDI1LTExLTAyIDA2OjA2OjExIjtzOjEwOiJ1cGRhdGVkX2F0IjtzOjE5OiIyMDI1LTExLTA0IDA0OjQ1OjE3IjtzOjM6ImxhdCI7czoxMDoiMjYuOTEyNTAwMCI7czozOiJsbmciO3M6MTA6Ijc1Ljc4NzUwMDAiO31zOjExOiIAKgBvcmlnaW5hbCI7YToxOTp7czoyOiJpZCI7aToyO3M6MTE6InVuaXF1ZV9oYXNoIjtzOjIwOiI1MGZiN2UyOGViNzVhN2ZjZGNkMSI7czoxMzoicmVzdGF1cmFudF9pZCI7aToxO3M6NDoibmFtZSI7czoxMjoiQWtrYXJhaXBhdHR1IjtzOjE4OiJjbG9uZWRfYnJhbmNoX25hbWUi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ZSI7czo0OiJmcmVlIjtzOjk6ImlzX2FjdGl2ZSI7aToxO3M6MTA6ImNyZWF0ZWRfYXQiO3M6MTk6IjIwMjUtMTEtMDIgMDY6MDY6MTEiO3M6MTA6InVwZGF0ZWRfYXQiO3M6MTk6IjIwMjUtMTEtMDQgMDU6NTU6MTAiO3M6MjM6ImN1c3RvbWVyX2xvZ2luX3JlcXVpcmVkIjtpOjE7czo4OiJhYm91dF91cyI7czoxMzA0OiI8cCBjbGFzcz0idGV4dC1sZyB0ZXh0LWdyYXktNjAwIG1iLTYiPgogICAgICAgICAgV2VsY29tZSB0byBvdXIgcmVzdGF1cmFudCwgd2hlcmUgZ3JlYXQgZm9vZCBhbmQgZ29vZCB2aWJlcyBjb21lIHRvZ2V0aGVyISBXZSdyZSBhIGxvY2FsLCBmYW1pbHktb3duZWQgc3BvdCB0aGF0IGxvdmVzIGJyaW5naW5nIHBlb3BsZSB0b2dldGhlciBvdmVyIGRlbGljaW91cyBtZWFscyBhbmQgdW5mb3JnZXR0YWJsZSBtb21lbnRzLiBXaGV0aGVyIHlvdSdyZSBoZXJlIGZvciBhIHF1aWNrIGJpdGUsIGEgZmFtaWx5IGRpbm5lciwgb3IgYSBjZWxlYnJhdGlvbiwgd2UncmUgYWxsIGFib3V0IG1ha2luZyB5b3VyIHRpbWUgd2l0aCB1cyBzcGVjaWFsLgogICAgICAgIDwvcD4KICAgICAgICA8cCBjbGFzcz0idGV4dC1sZyB0ZXh0LWdyYXktNjAwIG1iLTYiPgogICAgICAgICAgT3VyIG1lbnUgaXMgcGFja2VkIHdpdGggZGlzaGVzIG1hZGUgZnJvbSBmcmVzaCwgcXVhbGl0eSBpbmdyZWRpZW50cyBiZWNhdXNlIHdlIGJlbGlldmUgZm9vZCBzaG91bGQgdGFzdGUgYXMKICAgICAgICAgIGdvb2QgYXMgaXQgbWFrZXMgeW91IGZlZWwuIEZyb20gb3VyIHNpZ25hdHVyZSBkaXNoZXMgdG8gc2Vhc29uYWwgc3BlY2lhbHMsIHRoZXJlJ3MgYWx3YXlzIHNvbWV0aGluZyB0byBleGNpdGUKICAgICAgICAgIHlvdXIgdGFzdGUgYnVkcy4KICAgICAgICA8L3A+CiAgICAgICAgPHAgY2xhc3M9InRleHQtbGcgdGV4dC1ncmF5LTYwMCBtYi02Ij4KICAgICAgICAgIEJ1dCB3ZSdyZSBub3QganVzdCBhYm91dCB0aGUgZm9vZOKAlHdlJ3JlIGFib3V0IGNvbW11bml0eS4gV2UgbG92ZSBzZWVpbmcgZmFtaWxpYXIgZmFjZXMgYW5kIHdlbGNvbWluZyBuZXcgb25lcy4KICAgICAgICAgIE91ciB0ZWFtIGlzIGEgZnVuLCBmcmllbmRseSBidW5jaCBkZWRpY2F0ZWQgdG8gc2VydmluZyB5b3Ugd2l0aCBhIHNtaWxlIGFuZCBtYWtpbmcgc3VyZSBldmVyeSB2aXNpdCBmZWVscyBsaWtlCiAgICAgICAgICBjb21pbmcgaG9tZS4KICAgICAgICA8L3A+CiAgICAgICAgPHAgY2xhc3M9InRleHQtbGcgdGV4dC1ncmF5LTYwMCI+CiAgICAgICAgICBTbywgY29tZSBvbiBpbiwgZ3JhYiBhIHNlYXQsIGFuZCBsZXQgdXMgdGFrZSBjYXJlIG9mIHRoZSByZXN0LiBXZSBjYW4ndCB3YWl0IHRvIHNoYXJlIG91ciBsb3ZlIG9mIGZvb2Qgd2l0aAogICAgICAgICAgeW91IQogICAgICAgIDwvcD4KICAgICAgICA8cCBjbGFzcz0idGV4dC1sZyB0ZXh0LWdyYXktODAwIGZvbnQtc2VtaWJvbGQgbXQtNiI+U2VlIHlvdSBzb29uISDwn42977iP4pyoPC9wPiI7czozMDoiYWxsb3dfY3VzdG9tZXJfZGVsaXZlcnlfb3JkZXJzIjtpOjE7czoyODoiYWxsb3dfY3VzdG9tZXJfcGlja3VwX29yZGVycyI7aToxO3M6MTc6InBpY2t1cF9kYXlzX3JhbmdlIjtpOjc7czoyMToiYWxsb3dfY3VzdG9tZXJfb3JkZXJzIjtpOjE7czoyMDoiYWxsb3dfZGluZV9pbl9vcmRlcnMiO2k6MTtzOjg6InNob3dfdmVnIjtpOjE7czoxMDoic2hvd19oYWxhbCI7aTowO3M6MTA6InBhY2thZ2VfaWQiO2k6MztzOjEyOiJwYWNrYWdlX3R5cGUiO3M6ODoibGlmZXRpbWUiO3M6Njoic3RhdHVzIjtzOjY6ImFjdGl2ZSI7czoxNzoibGljZW5zZV9leHBpcmVfb24iO047czoxMzoidHJpYWxfZW5kc19hdCI7TjtzOjE4OiJsaWNlbnNlX3VwZGF0ZWRfYXQiO3M6MTk6IjIwMjUtMTEtMDIgMDY6MDY6MTEiO3M6MjM6InN1YnNjcmlwdGlvbl91cGRhdGVkX2F0IjtzOjE5OiIyMDI1LTExLTAyIDA2OjA2OjExIjtzOjk6InN0cmlwZV9pZCI7TjtzOjc6InBtX3R5cGUiO047czoxMjoicG1fbGFzdF9mb3VyIjtOO3M6MjU6ImlzX3dhaXRlcl9yZXF1ZXN0X2VuYWJsZWQiO2k6MTtzOjMyOiJkZWZhdWx0X3RhYmxlX3Jlc2VydmF0aW9uX3N0YXR1cyI7czo5OiJDb25maXJtZWQiO3M6MjA6ImRpc2FibGVfc2xvdF9taW51dGVzIjtpOjMwO3M6MTU6ImFwcHJvdmFsX3N0YXR1cyI7czo4OiJBcHByb3ZlZCI7czoxNjoicmVqZWN0aW9uX3JlYXNvbiI7TjtzOjEzOiJmYWNlYm9va19saW5rIjtzOjI1OiJodHRwczovL3d3dy5mYWNlYm9vay5jb20vIjtzOjE0OiJpbnN0YWdyYW1fbGluayI7czoyNjoiaHR0cHM6Ly93d3cuaW5zdGFncmFtLmNvbS8iO3M6MTI6InR3aXR0ZXJfbGluayI7czoyNDoiaHR0cHM6Ly93d3cudHdpdHRlci5jb20vIjtzOjk6InllbHBfbGluayI7TjtzOjE0OiJ0YWJsZV9yZXF1aXJlZCI7aTowO3M6MTQ6InNob3dfbG9nb190ZXh0IjtpOjE7czoxMjoibWV0YV9rZXl3b3JkIjtOO3M6MTY6Im1ldGFfZGVzY3JpcHRpb24iO047czozNDoidXBsb2FkX2Zhdl9pY29uX2FuZHJvaWRfY2hyb21lXzE5MiI7TjtzOjM0OiJ1cGxvYWRfZmF2X2ljb25fYW5kcm9pZF9jaHJvbWVfNTEyIjtOO3M6MzI6InVwbG9hZF9mYXZfaWNvbl9hcHBsZV90b3VjaF9pY29uIjtOO3M6MTc6InVwbG9hZF9mYXZpY29uXzE2IjtOO3M6MTc6InVwbG9hZF9mYXZpY29uXzMyIjtOO3M6NzoiZmF2aWNvbiI7TjtzOjM2OiJpc193YWl0ZXJfcmVxdWVzdF9lbmFibGVkX29uX2Rlc2t0b3AiO2k6MTtzOjM1OiJpc193YWl0ZXJfcmVxdWVzdF9lbmFibGVkX29uX21vYmlsZSI7aToxO3M6MzY6ImlzX3dhaXRlcl9yZXF1ZXN0X2VuYWJsZWRfb3Blbl9ieV9xciI7aTowO3M6MTE6IndlYm1hbmlmZXN0IjtOO3M6MTU6ImVuYWJsZV90aXBfc2hvcCI7aToxO3M6MTQ6ImVuYWJsZV90aXBfcG9zIjtpOjE7czoyNToiaXNfcHdhX2luc3RhbGxfYWxlcnRfc2hvdyI7aToxO3M6MTk6ImF1dG9fY29uZmlybV9vcmRlcnMiO2k6MDtzOjIzOiJzaG93X29yZGVyX3R5cGVfb3B0aW9ucyI7aToxO3M6Mjc6ImhpZGVfbWVudV9pdGVtX2ltYWdlX29uX3BvcyI7aTowO3M6Mzc6ImhpZGVfbWVudV9pdGVtX2ltYWdlX29uX2N1c3RvbWVyX3NpdGUiO2k6MDtzOjg6InRheF9tb2RlIjtzOjU6Im9yZGVyIjtzOjEzOiJ0YXhfaW5jbHVzaXZlIjtpOjA7czoyMjoiY3VzdG9tZXJfc2l0ZV9sYW5ndWFnZSI7czoyOiJlbiI7czoyNDoiZW5hYmxlX2FkbWluX3Jlc2VydmF0aW9uIjtpOjE7czoyNzoiZW5hYmxlX2N1c3RvbWVyX3Jlc2VydmF0aW9uIjtpOjE7czoxODoibWluaW11bV9wYXJ0eV9zaXplIjtpOjE7czoyNjoidGFibGVfbG9ja190aW1lb3V0X21pbnV0ZXMiO2k6MTA7fXM6MTE6IgAqAG9yaWdpbmFsIjthOjc2OntzOjI6ImlkIjtpOjE7czo0OiJuYW1lIjtzOjc6Ik1yIENoYWkiO3M6NDoiaGFzaCI7czo3OiJtci1jaGFpIjtzOjc6ImFkZHJlc3MiO3M6MjU6Ik1haW4gU3RyZWV0LCBPbHV2aWwgMzIzNjAiO3M6MTI6InBob25lX251bWJlciI7czoxMjoiMDc0IDM5NCAyNDY0IjtzOjEwOiJwaG9uZV9jb2RlIjtzOjI6Ijk0IjtzOjU6ImVtYWlsIjtzOjE2OiJtcmNoYWlAZ21haWwuY29tIjtzOjg6InRpbWV6b25lIjtzOjEyOiJBc2lhL0NvbG9tYm8iO3M6OToidGhlbWVfaGV4IjtzOjc6IiNGOTczMTYiO3M6OToidGhlbWVfcmdiIjtzOjEyOiIyNDksIDExNSwgMjIiO3M6NDoibG9nbyI7czozNjoiMzRiNjZhMjI1ZDllOTY0MDc4NDNhZTUxOWJjNDNmNTkuanBnIjtzOjEwOiJjb3VudHJ5X2lkIjtpOjIxMDtzOjE1OiJoaWRlX25ld19vcmRlcnMiO2k6MDtzOjIxOiJoaWRlX25ld19yZXNlcnZhdGlvbnMiO2k6MDtzOjIzOiJoaWRlX25ld193YWl0ZXJfcmVxdWVzdCI7aTowO3M6MTE6ImN1cnJlbmN5X2lkIjtpOjU7czoxMjoibGljZW5zZV90eXBlIjtzOjQ6ImZyZWUiO3M6OToiaXNfYWN0aXZlIjtpOjE7czoxMDoiY3JlYXRlZF9hdCI7czoxOToiMjAyNS0xMS0wMiAwNjowNjoxMSI7czoxMDoidXBkYXRlZF9hdCI7czoxOToiMjAyNS0xMS0wNCAwNTo1NToxMCI7czoyMzoiY3VzdG9tZXJfbG9naW5fcmVxdWlyZWQiO2k6MTtzOjg6ImFib3V0X3VzIjtzOjEzMDQ6IjxwIGNsYXNzPSJ0ZXh0LWxnIHRleHQtZ3JheS02MDAgbWItNiI+CiAgICAgICAgICBXZWxjb21lIHRvIG91ciByZXN0YXVyYW50LCB3aGVyZSBncmVhdCBmb29kIGFuZCBnb29kIHZpYmVzIGNvbWUgdG9nZXRoZXIhIFdlJ3JlIGEgbG9jYWwsIGZhbWlseS1vd25lZCBzcG90IHRoYXQgbG92ZXMgYnJpbmdpbmcgcGVvcGxlIHRvZ2V0aGVyIG92ZXIgZGVsaWNpb3VzIG1lYWxzIGFuZCB1bmZvcmdldHRhYmxlIG1vbWVudHMuIFdoZXRoZXIgeW91J3JlIGhlcmUgZm9yIGEgcXVpY2sgYml0ZSwgYSBmYW1pbHkgZGlubmVyLCBvciBhIGNlbGVicmF0aW9uLCB3ZSdyZSBhbGwgYWJvdXQgbWFraW5nIHlvdXIgdGltZSB3aXRoIHVzIHNwZWNpYWwuCiAgICAgICAgPC9wPgogICAgICAgIDxwIGNsYXNzPSJ0ZXh0LWxnIHRleHQtZ3JheS02MDAgbWItNiI+CiAgICAgICAgICBPdXIgbWVudSBpcyBwYWNrZWQgd2l0aCBkaXNoZXMgbWFkZSBmcm9tIGZyZXNoLCBxdWFsaXR5IGluZ3JlZGllbnRzIGJlY2F1c2Ugd2UgYmVsaWV2ZSBmb29kIHNob3VsZCB0YXN0ZSBhcwogICAgICAgICAgZ29vZCBhcyBpdCBtYWtlcyB5b3UgZmVlbC4gRnJvbSBvdXIgc2lnbmF0dXJlIGRpc2hlcyB0byBzZWFzb25hbCBzcGVjaWFscywgdGhlcmUncyBhbHdheXMgc29tZXRoaW5nIHRvIGV4Y2l0ZQogICAgICAgICAgeW91ciB0YXN0ZSBidWRzLgogICAgICAgIDwvcD4KICAgICAgICA8cCBjbGFzcz0idGV4dC1sZyB0ZXh0LWdyYXktNjAwIG1iLTYiPgogICAgICAgICAgQnV0IHdlJ3JlIG5vdCBqdXN0IGFib3V0IHRoZSBmb29k4oCUd2UncmUgYWJvdXQgY29tbXVuaXR5LiBXZSBsb3ZlIHNlZWluZyBmYW1pbGlhciBmYWNlcyBhbmQgd2VsY29taW5nIG5ldyBvbmVzLgogICAgICAgICAgT3VyIHRlYW0gaXMgYSBmdW4sIGZyaWVuZGx5IGJ1bmNoIGRlZGljYXRlZCB0byBzZXJ2aW5nIHlvdSB3aXRoIGEgc21pbGUgYW5kIG1ha2luZyBzdXJlIGV2ZXJ5IHZpc2l0IGZlZWxzIGxpa2UKICAgICAgICAgIGNvbWluZyBob21lLgogICAgICAgIDwvcD4KICAgICAgICA8cCBjbGFzcz0idGV4dC1sZyB0ZXh0LWdyYXktNjAwIj4KICAgICAgICAgIFNvLCBjb21lIG9uIGluLCBncmFiIGEgc2VhdCwgYW5kIGxldCB1cyB0YWtlIGNhcmUgb2YgdGhlIHJlc3QuIFdlIGNhbid0IHdhaXQgdG8gc2hhcmUgb3VyIGxvdmUgb2YgZm9vZCB3aXRoCiAgICAgICAgICB5b3UhCiAgICAgICAgPC9wPgogICAgICAgIDxwIGNsYXNzPSJ0ZXh0LWxnIHRleHQtZ3JheS04MDAgZm9udC1zZW1pYm9sZCBtdC02Ij5TZWUgeW91IHNvb24hIPCfjb3vuI/inKg8L3A+IjtzOjMwOiJhbGxvd19jdXN0b21lcl9kZWxpdmVyeV9vcmRlcnMiO2k6MTtzOjI4OiJhbGxvd19jdXN0b21lcl9waWNrdXBfb3JkZXJzIjtpOjE7czoxNzoicGlja3VwX2RheXNfcmFuZ2UiO2k6NztzOjIxOiJhbGxvd19jdXN0b21lcl9vcmRlcnMiO2k6MTtzOjIwOiJhbGxvd19kaW5lX2luX29yZGVycyI7aToxO3M6ODoic2hvd192ZWciO2k6MTtzOjEwOiJzaG93X2hhbGFsIjtpOjA7czoxMDoicGFja2FnZV9pZCI7aTozO3M6MTI6InBhY2thZ2VfdHlwZSI7czo4OiJsaWZldGltZSI7czo2OiJzdGF0dXMiO3M6NjoiYWN0aXZlIjtzOjE3OiJsaWNlbnNlX2V4cGlyZV9vbiI7TjtzOjEzOiJ0cmlhbF9lbmRzX2F0IjtOO3M6MTg6ImxpY2Vuc2VfdXBkYXRlZF9hdCI7czoxOToiMjAyNS0xMS0wMiAwNjowNjoxMSI7czoyMzoic3Vic2NyaXB0aW9uX3VwZGF0ZWRfYXQiO3M6MTk6IjIwMjUtMTEtMDIgMDY6MDY6MTEiO3M6OToic3RyaXBlX2lkIjtOO3M6NzoicG1fdHlwZSI7TjtzOjEyOiJwbV9sYXN0X2ZvdXIiO047czoyNToiaXNfd2FpdGVyX3JlcXVlc3RfZW5hYmxlZCI7aToxO3M6MzI6ImRlZmF1bHRfdGFibGVfcmVzZXJ2YXRpb25fc3RhdHVzIjtzOjk6IkNvbmZpcm1lZCI7czoyMDoiZGlzYWJsZV9zbG90X21pbnV0ZXMiO2k6MzA7czoxNToiYXBwcm92YWxfc3RhdHVzIjtzOjg6IkFwcHJvdmVkIjtzOjE2OiJyZWplY3Rpb25fcmVhc29uIjtOO3M6MTM6ImZhY2Vib29rX2xpbmsiO3M6MjU6Imh0dHBzOi8vd3d3LmZhY2Vib29rLmNvbS8iO3M6MTQ6Imluc3RhZ3JhbV9saW5rIjtzOjI2OiJodHRwczovL3d3dy5pbnN0YWdyYW0uY29tLyI7czoxMjoidHdpdHRlcl9saW5rIjtzOjI0OiJodHRwczovL3d3dy50d2l0dGVyLmNvbS8iO3M6OToieWVscF9saW5rIjtOO3M6MTQ6InRhYmxlX3JlcXVpcmVkIjtpOjA7czoxNDoic2hvd19sb2dvX3RleHQiO2k6MTtzOjEyOiJtZXRhX2tleXdvcmQiO047czoxNjoibWV0YV9kZXNjcmlwdGlvbiI7TjtzOjM0OiJ1cGxvYWRfZmF2X2ljb25fYW5kcm9pZF9jaHJvbWVfMTkyIjtOO3M6MzQ6InVwbG9hZF9mYXZfaWNvbl9hbmRyb2lkX2Nocm9tZV81MTIiO047czozMjoidXBsb2FkX2Zhdl9pY29uX2FwcGxlX3RvdWNoX2ljb24iO047czoxNzoidXBsb2FkX2Zhdmljb25fMTYiO047czoxNzoidXBsb2FkX2Zhdmljb25fMzIiO047czo3OiJmYXZpY29uIjtOO3M6MzY6ImlzX3dhaXRlcl9yZXF1ZXN0X2VuYWJsZWRfb25fZGVza3RvcCI7aToxO3M6MzU6ImlzX3dhaXRlcl9yZXF1ZXN0X2VuYWJsZWRfb25fbW9iaWxlIjtpOjE7czozNjoiaXNfd2FpdGVyX3JlcXVlc3RfZW5hYmxlZF9vcGVuX2J5X3FyIjtpOjA7czoxMToid2VibWFuaWZlc3QiO047czoxNToiZW5hYmxlX3RpcF9zaG9wIjtpOjE7czoxNDoiZW5hYmxlX3RpcF9wb3MiO2k6MTtzOjI1OiJpc19wd2FfaW5zdGFsbF9hbGVydF9zaG93IjtpOjE7czoxOToiYXV0b19jb25maXJtX29yZGVycyI7aTowO3M6MjM6InNob3dfb3JkZXJfdHlwZV9vcHRpb25zIjtpOjE7czoyNzoiaGlkZV9tZW51X2l0ZW1faW1hZ2Vfb25fcG9zIjtpOjA7czozNzoiaGlkZV9tZW51X2l0ZW1faW1hZ2Vfb25fY3VzdG9tZXJfc2l0ZSI7aTowO3M6ODoidGF4X21vZGUiO3M6NToib3JkZXIiO3M6MTM6InRheF9pbmNsdXNpdmUiO2k6MDtzOjIyOiJjdXN0b21lcl9zaXRlX2xhbmd1YWdlIjtzOjI6ImVuIjtzOjI0OiJlbmFibGVfYWRtaW5fcmVzZXJ2YXRpb24iO2k6MTtzOjI3OiJlbmFibGVfY3VzdG9tZXJfcmVzZXJ2YXRpb24iO2k6MTtzOjE4OiJtaW5pbXVtX3BhcnR5X3NpemUiO2k6MTtzOjI2OiJ0YWJsZV9sb2NrX3RpbWVvdXRfbWludXRlcyI7aToxMDt9czoxMDoiACoAY2hhbmdlcyI7YTowOnt9czoxMToiACoAcHJldmlvdXMiO2E6MDp7fXM6ODoiACoAY2FzdHMiO2E6MTA6e3M6MTc6ImxpY2Vuc2VfZXhwaXJlX29uIjtzOjg6ImRhdGV0aW1lIjtzOjE1OiJ0cmlhbF9leHBpcmVfb24iO3M6ODoiZGF0ZXRpbWUiO3M6MTg6ImxpY2Vuc2VfdXBkYXRlZF9hdCI7czo4OiJkYXRldGltZSI7czoyMzoic3Vic2NyaXB0aW9uX3VwZGF0ZWRfYXQiO3M6ODoiZGF0ZXRpbWUiO3M6MTA6ImNyZWF0ZWRfYXQiO3M6ODoiZGF0ZXRpbWUiO3M6MTA6InVwZGF0ZWRfYXQiO3M6ODoiZGF0ZXRpbWUiO3M6MjM6ImN1c3RvbV9kZWxpdmVyeV9vcHRpb25zIjtzOjU6ImFycmF5IjtzOjk6ImlzX2FjdGl2ZSI7czo3OiJib29sZWFuIjtzOjI0OiJlbmFibGVfYWRtaW5fcmVzZXJ2YXRpb24iO3M6NzoiYm9vbGVhbiI7czoyNzoiZW5hYmxlX2N1c3RvbWVyX3Jlc2VydmF0aW9uIjtzOjc6ImJvb2xlYW4iO31zOjE3OiIAKgBjbGFzc0Nhc3RDYWNoZSI7YTowOnt9czoyMToiACoAYXR0cmlidXRlQ2FzdENhY2hlIjthOjA6e31zOjEzOiIAKgBkYXRlRm9ybWF0IjtOO3M6MTA6IgAqAGFwcGVuZHMiO2E6MTp7aTowO3M6ODoibG9nb191cmwiO31zOjE5OiIAKgBkaXNwYXRjaGVzRXZlbnRzIjthOjA6e31zOjE0OiIAKgBvYnNlcnZhYmxlcyI7YTowOnt9czoxMjoiACoAcmVsYXRpb25zIjthOjE6e3M6ODoiY3VycmVuY3kiO086MTk6IkFwcFxNb2RlbHNcQ3VycmVuY3kiOjMzOntzOjEzOiIAKgBjb25uZWN0aW9uIjtzOjU6Im15c3FsIjtzOjg6IgAqAHRhYmxlIjtzOjEwOiJjdXJyZW5jaWVzIjtzOjEzOiIAKgBwcmltYXJ5S2V5IjtzOjI6ImlkIjtzOjEwOiIAKgBrZXlUeXBlIjtzOjM6ImludCI7czoxMjoiaW5jcmVtZW50aW5nIjtiOjE7czo3OiIAKgB3aXRoIjthOjA6e31zOjEyOiIAKgB3aXRoQ291bnQiO2E6MDp7fXM6MTk6InByZXZlbnRzTGF6eUxvYWRpbmciO2I6MDtzOjEwOiIAKgBwZXJQYWdlIjtpOjE1O3M6NjoiZXhpc3RzIjtiOjE7czoxODoid2FzUmVjZW50bHlDcmVhdGVkIjtiOjA7czoyODoiACoAZXNjYXBlV2hlbkNhc3RpbmdUb1N0cmluZyI7YjowO3M6MTM6IgAqAGF0dHJpYnV0ZXMiO2E6MTI6e3M6MjoiaWQiO2k6NTtzOjEzOiJyZXN0YXVyYW50X2lkIjtpOjE7czoxMzoiY3VycmVuY3lfbmFtZSI7czoxMzoiTGFua2FuIFJ1cGVzcyI7czoxMzoiY3VycmVuY3lfY29kZSI7czozOiJMS1IiO3M6MTU6ImN1cnJlbmN5X3N5bWJvbCI7czozOiJScyAiO3M6MTc6ImN1cnJlbmN5X3Bvc2l0aW9uIjtzOjQ6ImxlZnQiO3M6MTM6Im5vX29mX2RlY2ltYWwiO2k6MjtzOjE4OiJ0aG91c2FuZF9zZXBhcmF0b3IiO3M6MToiLCI7czoxNzoiZGVjaW1hbF9zZXBhcmF0b3IiO3M6MToiLiI7czoxMzoiZXhjaGFuZ2VfcmF0ZSI7TjtzOjk6InVzZF9wcmljZSI7TjtzOjE3OiJpc19jcnlwdG9jdXJyZW5jeSI7czoyOiJubyI7fXM6MTE6IgAqAG9yaWdpbmFsIjthOjEyOntzOjI6ImlkIjtpOjU7czoxMzoicmVzdGF1cmFudF9pZCI7aToxO3M6MTM6ImN1cnJlbmN5X25hbWUiO3M6MTM6IkxhbmthbiBSdXBlc3MiO3M6MTM6ImN1cnJlbmN5X2NvZGUiO3M6MzoiTEtSIjtzOjE1OiJjdXJyZW5jeV9zeW1ib2wiO3M6MzoiUnMgIjtzOjE3OiJjdXJyZW5jeV9wb3NpdGlvbiI7czo0OiJsZWZ0IjtzOjEzOiJub19vZl9kZWNpbWFsIjtpOjI7czoxODoidGhvdXNhbmRfc2VwYXJhdG9yIjtzOjE6IiwiO3M6MTc6ImRlY2ltYWxfc2VwYXJhdG9yIjtzOjE6Ii4iO3M6MTM6ImV4Y2hhbmdlX3JhdGUiO047czo5OiJ1c2RfcHJpY2UiO047czoxNzoiaXNfY3J5cHRvY3VycmVuY3kiO3M6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cmlwdGlvbiI7czozMzoiVGhpcyBpcyBhIGxpZmV0aW1lIGFjY2VzcyBwYWNrYWdlIjtzOjEyOiJhbm51YWxfcHJpY2UiO047czoxMzoibW9udGhseV9wcmljZSI7TjtzOjE0OiJtb250aGx5X3N0YXR1cyI7aTowO3M6MTM6ImFubnVhbF9zdGF0dXMiO2k6MDtzOjIxOiJzdHJpcGVfYW5udWFsX3BsYW5faWQiO047czoyMjoic3RyaXBlX21vbnRobHlfcGxhbl9pZCI7TjtzOjIzOiJyYXpvcnBheV9hbm51YWxfcGxhbl9pZCI7TjtzOjI0OiJyYXpvcnBheV9tb250aGx5X3BsYW5faWQiO047czoyNjoiZmx1dHRlcndhdmVfYW5udWFsX3BsYW5faWQiO047czoyNzoiZmx1dHRlcndhdmVfbW9udGhseV9wbGFuX2lkIjtOO3M6MjM6InBheXN0YWNrX2FubnVhbF9wbGFuX2lkIjtOO3M6MjQ6InBheXN0YWNrX21vbnRobHlfcGxhbl9pZCI7TjtzOjIxOiJ4ZW5kaXRfYW5udWFsX3BsYW5faWQiO047czoyMjoieGVuZGl0X21vbnRobHlfcGxhbl9pZCI7TjtzOjIyOiJwYWRkbGVfYW5udWFsX3ByaWNlX2lkIjtOO3M6MjM6InBhZGRsZV9tb250aGx5X3ByaWNlX2lkIjtOO3M6MjQ6InBhZGRsZV9saWZldGltZV9wcmljZV9pZCI7TjtzOjIzOiJzdHJpcGVfbGlmZXRpbWVfcGxhbl9pZCI7TjtzOjI1OiJyYXpvcnBheV9saWZldGltZV9wbGFuX2lkIjtOO3M6MTM6ImJpbGxpbmdfY3ljbGUiO2k6MDtzOjEwOiJzb3J0X29yZGVyIjtpOjM7czoxMDoiaXNfcHJpdmF0ZSI7aTowO3M6NzoiaXNfZnJlZSI7aTowO3M6MTQ6ImlzX3JlY29tbWVuZGVkIjtpOjE7czoxMjoicGFja2FnZV90eXBlIjtzOjg6ImxpZmV0aW1lIjtzOjEyOiJ0cmlhbF9zdGF0dXMiO047czoxMDoidHJpYWxfZGF5cyI7TjtzOjMwOiJ0cmlhbF9ub3RpZmljYXRpb25fYmVmb3JlX2RheXMiO047czoxMzoidHJpYWxfbWVzc2FnZSI7TjtzOjE5OiJhZGRpdGlvbmFsX2ZlYXR1cmVzIjtzOjExODoiWyJDaGFuZ2UgQnJhbmNoIiwiRXhwb3J0IFJlcG9ydCIsIlRhYmxlIFJlc2VydmF0aW9uIiwiUGF5bWVudCBHYXRld2F5IEludGVncmF0aW9uIiwiVGhlbWUgU2V0dGluZyIsIkN1c3RvbWVyIERpc3BsYXkiXSI7czoxMjoiYnJhbmNoX2xpbWl0IjtpOi0xO3M6OToic21zX2NvdW50IjtpOi0xO3M6MTc6ImNhcnJ5X2ZvcndhcmRfc21zIjtpOjA7fXM6MTE6IgAqAG9yaWdpbmFsIjthOjQwOntzOjI6ImlkIjtpOjM7czoxMjoicGFja2FnZV9uYW1lIjtzOjk6IkxpZmUgVGltZSI7czo1OiJwcmljZSI7czo2OiIxOTkuMDAiO3M6MTA6ImNyZWF0ZWRfYXQiO3M6MTk6IjIwMjUtMTEtMDIgMDY6MDY6MTAiO3M6MTA6InVwZGF0ZWRfYXQiO3M6MTk6IjIwMjUtMTEtMDQgMDc6MzY6NTAiO3M6MTE6ImN1cnJlbmN5X2lkIjtpOjE7czoxMToiZGVzY3JpcHRpb24iO3M6MzM6IlRoaXMgaXMgYSBsaWZldGltZSBhY2Nlc3MgcGFja2FnZSI7czoxMjoiYW5udWFsX3ByaWNlIjtOO3M6MTM6Im1vbnRobHlfcHJpY2UiO047czoxNDoibW9udGhseV9zdGF0dXMiO3M6MToiMCI7czoxMzoiYW5udWFsX3N0YXR1cyI7czoxOiIwIjtzOjIxOiJzdHJpcGVfYW5udWFsX3BsYW5faWQiO047czoyMjoic3RyaXBlX21vbnRobHlfcGxhbl9pZCI7TjtzOjIzOiJyYXpvcnBheV9hbm51YWxfcGxhbl9pZCI7TjtzOjI0OiJyYXpvcnBheV9tb250aGx5X3BsYW5faWQiO047czoyNjoiZmx1dHRlcndhdmVfYW5udWFsX3BsYW5faWQiO047czoyNzoiZmx1dHRlcndhdmVfbW9udGhseV9wbGFuX2lkIjtOO3M6MjM6InBheXN0YWNrX2FubnVhbF9wbGFuX2lkIjtOO3M6MjQ6InBheXN0YWNrX21vbnRobHlfcGxhbl9pZCI7TjtzOjIxOiJ4ZW5kaXRfYW5udWFsX3BsYW5faWQiO047czoyMjoieGVuZGl0X21vbnRobHlfcGxhbl9pZCI7TjtzOjIyOiJwYWRkbGVfYW5udWFsX3ByaWNlX2lkIjtOO3M6MjM6InBhZGRsZV9tb250aGx5X3ByaWNlX2lkIjtOO3M6MjQ6InBhZGRsZV9saWZldGltZV9wcmljZV9pZCI7TjtzOjIzOiJzdHJpcGVfbGlmZXRpbWVfcGxhbl9pZCI7TjtzOjI1OiJyYXpvcnBheV9saWZldGltZV9wbGFuX2lkIjtOO3M6MTM6ImJpbGxpbmdfY3ljbGUiO2k6MDtzOjEwOiJzb3J0X29yZGVyIjtpOjM7czoxMDoiaXNfcHJpdmF0ZSI7aTowO3M6NzoiaXNfZnJlZSI7aTowO3M6MTQ6ImlzX3JlY29tbWVuZGVkIjtpOjE7czoxMjoicGFja2FnZV90eXBlIjtzOjg6ImxpZmV0aW1lIjtzOjEyOiJ0cmlhbF9zdGF0dXMiO047czoxMDoidHJpYWxfZGF5cyI7TjtzOjMwOiJ0cmlhbF9ub3RpZmljYXRpb25fYmVmb3JlX2RheXMiO047czoxMzoidHJpYWxfbWVzc2FnZSI7TjtzOjE5OiJhZGRpdGlvbmFsX2ZlYXR1cmVzIjtzOjExODoiWyJDaGFuZ2UgQnJhbmNoIiwiRXhwb3J0IFJlcG9ydCIsIlRhYmxlIFJlc2VydmF0aW9uIiwiUGF5bWVudCBHYXRld2F5IEludGVncmF0aW9uIiwiVGhlbWUgU2V0dGluZyIsIkN1c3RvbWVyIERpc3BsYXkiXSI7czoxMjoiYnJhbmNoX2xpbWl0IjtpOi0xO3M6OToic21zX2NvdW50IjtpOi0xO3M6MTc6ImNhcnJ5X2ZvcndhcmRfc21zIjtpOjA7fXM6MTA6IgAqAGNoYW5nZXMiO2E6MDp7fXM6MTE6IgAqAHByZXZpb3VzIjthOjA6e31zOjg6IgAqAGNhc3RzIjthOjM6e3M6MTI6InBhY2thZ2VfdHlwZSI7czoyMToiQXBwXEVudW1zXFBhY2thZ2VUeXBlIjtzOjEwOiJ0cmlhbF9kYXlzIjtzOjc6ImludGVnZXIiO3M6MzA6InRyaWFsX25vdGlmaWNhdGlvbl9iZWZvcmVfZGF5cyI7czo3OiJpbnRlZ2VyIjt9czoxNzoiACoAY2xhc3NDYXN0Q2FjaGUiO2E6MDp7fXM6MjE6IgAqAGF0dHJpYnV0ZUNhc3RDYWNoZSI7YTowOnt9czoxMzoiACoAZGF0ZUZvcm1hdCI7TjtzOjEwOiIAKgBhcHBlbmRzIjthOjA6e31zOjE5OiIAKgBkaXNwYXRjaGVzRXZlbnRzIjthOjA6e31zOjE0OiIAKgBvYnNlcnZhYmxlcyI7YTowOnt9czoxMjoiACoAcmVsYXRpb25zIjthOjA6e31zOjEwOiIAKgB0b3VjaGVzIjthOjA6e31zOjI3OiIAKgByZWxhdGlvbkF1dG9sb2FkQ2FsbGJhY2siO047czoyNjoiACoAcmVsYXRpb25BdXRvbG9hZENvbnRleHQiO047czoxMDoidGltZXN0YW1wcyI7YjoxO3M6MTM6InVzZXNVbmlxdWVJZHMiO2I6MDtzOjk6IgAqAGhpZGRlbiI7YTowOnt9czoxMDoiACoAdmlzaWJsZSI7YTowOnt9czoxMToiACoAZmlsbGFibGUiO2E6MDp7fXM6MTA6IgAqAGd1YXJkZWQiO2E6MTp7aTowO3M6MjoiaWQiO319czoxNDoicmVjZWlwdFNldHRpbmciO086MjU6IkFwcFxNb2RlbHNcUmVjZWlwdFNldHRpbmciOjMzOntzOjEzOiIAKgBjb25uZWN0aW9uIjtzOjU6Im15c3FsIjtzOjg6IgAqAHRhYmxlIjtzOjE2OiJyZWNlaXB0X3NldHRpbmdzIjtzOjEzOiIAKgBwcmltYXJ5S2V5IjtzOjI6ImlkIjtzOjEwOiIAKgBrZXlUeXBlIjtzOjM6ImludCI7czoxMjoiaW5jcmVtZW50aW5nIjtiOjE7czo3OiIAKgB3aXRoIjthOjA6e31zOjEyOiIAKgB3aXRoQ291bnQiO2E6MDp7fXM6MTk6InByZXZlbnRzTGF6eUxvYWRpbmciO2I6MDtzOjEwOiIAKgBwZXJQYWdlIjtpOjE1O3M6NjoiZXhpc3RzIjtiOjE7czoxODoid2FzUmVjZW50bHlDcmVhdGVkIjtiOjA7czoyODoiACoAZXNjYXBlV2hlbkNhc3RpbmdUb1N0cmluZyI7YjowO3M6MTM6IgAqAGF0dHJpYnV0ZXMiO2E6MTY6e3M6MjoiaWQiO2k6MTtzOjEzOiJyZXN0YXVyYW50X2lkIjtpOjE7czoxODoic2hvd19jdXN0b21lcl9uYW1lIjtpOjA7czoyMToic2hvd19jdXN0b21lcl9hZGRyZXNzIjtpOjA7czoxNzoic2hvd190YWJsZV9udW1iZXIiO2k6MDtzOjE1OiJwYXltZW50X3FyX2NvZGUiO3M6MzY6ImVhZjZmMjU2Mjc1ODU2OWMzYTZiOTQxOGMyMTlhMDhiLnBuZyI7czoyMDoic2hvd19wYXltZW50X3FyX2NvZGUiO2k6MDtzOjExOiJzaG93X3dhaXRlciI7aTowO3M6MTY6InNob3dfdG90YWxfZ3Vlc3QiO2k6MDtzOjIwOiJzaG93X3Jlc3RhdXJhbnRfbG9nbyI7aToxO3M6ODoic2hvd190YXgiO2k6MDtzOjIwOiJzaG93X3BheW1lbnRfZGV0YWlscyI7aToxO3M6MTU6InNob3dfb3JkZXJfdHlwZSI7aTowO3M6MjA6InNob3dfY3VycmVuY3lfcHJlZml4IjtpOjA7czoxMDoiY3JlYXRlZF9hdCI7czoxOToiMjAyNS0xMS0wMiAwNjowNjoxMSI7czoxMDoidXBkYXRlZF9hdCI7czoxOToiMjAyNS0xMS0wOCAxMDo0MTo1NCI7fXM6MTE6IgAqAG9yaWdpbmFsIjthOjE2OntzOjI6ImlkIjtpOjE7czoxMzoicmVzdGF1cmFudF9pZCI7aToxO3M6MTg6InNob3dfY3VzdG9tZXJfbmFtZSI7aTowO3M6MjE6InNob3dfY3VzdG9tZXJfYWRkcmVzcyI7aTowO3M6MTc6InNob3dfdGFibGVfbnVtYmVyIjtpOjA7czoxNToicGF5bWVudF9xcl9jb2RlIjtzOjM2OiJlYWY2ZjI1NjI3NTg1NjljM2E2Yjk0MThjMjE5YTA4Yi5wbmciO3M6MjA6InNob3dfcGF5bWVudF9xcl9jb2RlIjtpOjA7czoxMToic2hvd193YWl0ZXIiO2k6MDtzOjE2OiJzaG93X3RvdGFsX2d1ZXN0IjtpOjA7czoyMDoic2hvd19yZXN0YXVyYW50X2xvZ28iO2k6MTtzOjg6InNob3dfdGF4IjtpOjA7czoyMDoic2hvd19wYXltZW50X2RldGFpbHMiO2k6MTtzOjE1OiJzaG93X29yZGVyX3R5cGUiO2k6MDtzOjIwOiJzaG93X2N1cnJlbmN5X3ByZWZpeCI7aTowO3M6MTA6ImNyZWF0ZWRfYXQiO3M6MTk6IjIwMjUtMTEtMDIgMDY6MDY6MTEiO3M6MTA6InVwZGF0ZWRfYXQiO3M6MTk6IjIwMjUtMTEtMDggMTA6NDE6NTQiO31zOjEwOiIAKgBjaGFuZ2VzIjthOjA6e31zOjExOiIAKgBwcmV2aW91cyI7YTowOnt9czo4OiIAKgBjYXN0cyI7YTowOnt9czoxNzoiACoAY2xhc3NDYXN0Q2FjaGUiO2E6MDp7fXM6MjE6IgAqAGF0dHJpYnV0ZUNhc3RDYWNoZSI7YTowOnt9czoxMzoiACoAZGF0ZUZvcm1hdCI7TjtzOjEwOiIAKgBhcHBlbmRzIjthOjE6e2k6MDtzOjE5OiJwYXltZW50X3FyX2NvZGVfdXJsIjt9czoxOToiACoAZGlzcGF0Y2hlc0V2ZW50cyI7YTowOnt9czoxNDoiACoAb2JzZXJ2YWJsZXMiO2E6MDp7fXM6MTI6IgAqAHJlbGF0aW9ucyI7YTowOnt9czoxMDoiACoAdG91Y2hlcyI7YTowOnt9czoyNzoiACoAcmVsYXRpb25BdXRvbG9hZENhbGxiYWNrIjtOO3M6MjY6IgAqAHJlbGF0aW9uQXV0b2xvYWRDb250ZXh0IjtOO3M6MTA6InRpbWVzdGFtcHMiO2I6MTtzOjEzOiJ1c2VzVW5pcXVlSWRzIjtiOjA7czo5OiIAKgBoaWRkZW4iO2E6MDp7fXM6MTA6IgAqAHZpc2libGUiO2E6MDp7fXM6MTE6IgAqAGZpbGxhYmxlIjthOjA6e31zOjEwOiIAKgBndWFyZGVkIjthOjE6e2k6MDtzOjI6ImlkIjt9fX1zOjEwOiIAKgB0b3VjaGVzIjthOjA6e31zOjI3OiIAKgByZWxhdGlvbkF1dG9sb2FkQ2FsbGJhY2siO047czoyNjoiACoAcmVsYXRpb25BdXRvbG9hZENvbnRleHQiO047czoxMDoidGltZXN0YW1wcyI7YjoxO3M6MTM6InVzZXNVbmlxdWVJZHMiO2I6MDtzOjk6IgAqAGhpZGRlbiI7YTowOnt9czoxMDoiACoAdmlzaWJsZSI7YTowOnt9czoxMToiACoAZmlsbGFibGUiO2E6MDp7fXM6MTA6IgAqAGd1YXJkZWQiO2E6MTp7aTowO3M6MjoiaWQiO31zOjE3OiJjdXN0b21lcklwQWRkcmVzcyI7TjtzOjI0OiJlc3RpbWF0aW9uQmlsbGluZ0FkZHJlc3MiO2E6MDp7fXM6MTM6ImNvbGxlY3RUYXhJZHMiO2I6MDtzOjg6ImNvdXBvbklkIjtOO3M6MTU6InByb21vdGlvbkNvZGVJZCI7TjtzOjE5OiJhbGxvd1Byb21vdGlvbkNvZGVzIjtiOjA7fXM6NjoiYnJhbmNoIjtPOjE3OiJBcHBcTW9kZWxzXEJyYW5jaCI6MzM6e3M6MTM6IgAqAGNvbm5lY3Rpb24iO3M6NToibXlzcWwiO3M6ODoiACoAdGFibGUiO3M6ODoiYnJhbmNoZXMiO3M6MTM6IgAqAHByaW1hcnlLZXkiO3M6MjoiaWQiO3M6MTA6IgAqAGtleVR5cGUiO3M6MzoiaW50IjtzOjEyOiJpbmNyZW1lbnRpbmciO2I6MTtzOjc6IgAqAHdpdGgiO2E6MDp7fXM6MTI6IgAqAHdpdGhDb3VudCI7YTowOnt9czoxOToicHJldmVudHNMYXp5TG9hZGluZyI7YjowO3M6MTA6IgAqAHBlclBhZ2UiO2k6MTU7czo2OiJleGlzdHMiO2I6MTtzOjE4OiJ3YXNSZWNlbnRseUNyZWF0ZWQiO2I6MDtzOjI4OiIAKgBlc2NhcGVXaGVuQ2FzdGluZ1RvU3RyaW5nIjtiOjA7czoxMzoiACoAYXR0cmlidXRlcyI7YToxOTp7czoyOiJpZCI7aToyO3M6MTE6InVuaXF1ZV9oYXNoIjtzOjIwOiI1MGZiN2UyOGViNzVhN2ZjZGNkMSI7czoxMzoicmVzdGF1cmFudF9pZCI7aToxO3M6NDoibmFtZSI7czoxMjoiQWtrYXJhaXBhdHR1IjtzOjE4OiJjbG9uZWRfYnJhbmNoX25hbWUiO047czoxNjoiY2xvbmVkX2JyYW5jaF9pZCI7TjtzOjEzOiJpc19tZW51X2Nsb25lIjtpOjA7czoyNDoiaXNfaXRlbV9jYXRlZ29yaWVzX2Nsb25lIjtpOjA7czoxOToiaXNfbWVudV9pdGVtc19jbG9uZSI7aTowO3M6MjM6ImlzX2l0ZW1fbW9kaWZpZXJzX2Nsb25lIjtpOjA7czoyOToiaXNfY2xvbmVfcmVzZXJ2YXRpb25fc2V0dGluZ3MiO2k6MDtzOjI2OiJpc19jbG9uZV9kZWxpdmVyeV9zZXR0aW5ncyI7aTowO3M6MjA6ImlzX2Nsb25lX2tvdF9zZXR0aW5nIjtpOjA7czoyNToiaXNfbW9kaWZpZXJzX2dyb3Vwc19jbG9uZSI7aTowO3M6NzoiYWRkcmVzcyI7czoyMToiTWFpbiBTdCwgQWtrYXJhaXBhdHR1IjtzOjEwOiJjcmVhdGVkX2F0IjtzOjE5OiIyMDI1LTExLTAyIDA2OjA2OjExIjtzOjEwOiJ1cGRhdGVkX2F0IjtzOjE5OiIyMDI1LTExLTA0IDA0OjQ1OjE3IjtzOjM6ImxhdCI7czoxMDoiMjYuOTEyNTAwMCI7czozOiJsbmciO3M6MTA6Ijc1Ljc4NzUwMDAiO31zOjExOiIAKgBvcmlnaW5hbCI7YToxOTp7czoyOiJpZCI7aToyO3M6MTE6InVuaXF1ZV9oYXNoIjtzOjIwOiI1MGZiN2UyOGViNzVhN2ZjZGNkMSI7czoxMzoicmVzdGF1cmFudF9pZCI7aToxO3M6NDoibmFtZSI7czoxMjoiQWtrYXJhaXBhdHR1IjtzOjE4OiJjbG9uZWRfYnJhbmNoX25hbWUiO047czoxNjoiY2xvbmVkX2JyYW5jaF9pZCI7TjtzOjEzOiJpc19tZW51X2Nsb25lIjtpOjA7czoyNDoiaXNfaXRlbV9jYXRlZ29yaWVzX2Nsb25lIjtpOjA7czoxOToiaXNfbWVudV9pdGVtc19jbG9uZSI7aTowO3M6MjM6ImlzX2l0ZW1fbW9kaWZpZXJzX2Nsb25lIjtpOjA7czoyOToiaXNfY2xvbmVfcmVzZXJ2YXRpb25fc2V0dGluZ3MiO2k6MDtzOjI2OiJpc19jbG9uZV9kZWxpdmVyeV9zZXR0aW5ncyI7aTowO3M6MjA6ImlzX2Nsb25lX2tvdF9zZXR0aW5nIjtpOjA7czoyNToiaXNfbW9kaWZpZXJzX2dyb3Vwc19jbG9uZSI7aTowO3M6NzoiYWRkcmVzcyI7czoyMToiTWFpbiBTdCwgQWtrYXJhaXBhdHR1IjtzOjEwOiJjcmVhdGVkX2F0IjtzOjE5OiIyMDI1LTExLTAyIDA2OjA2OjExIjtzOjEwOiJ1cGRhdGVk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ZCI7YjowO3M6Mjg6IgAqAGVzY2FwZVdoZW5DYXN0aW5nVG9TdHJpbmciO2I6MDtzOjEzOiIAKgBhdHRyaWJ1dGVzIjthOjc2OntzOjI6ImlkIjtpOjE7czo0OiJuYW1lIjtzOjc6Ik1yIENoYWkiO3M6NDoiaGFzaCI7czo3OiJtci1jaGFpIjtzOjc6ImFkZHJlc3MiO3M6MjU6Ik1haW4gU3RyZWV0LCBPbHV2aWwgMzIzNjAiO3M6MTI6InBob25lX251bWJlciI7czoxMjoiMDc0IDM5NCAyNDY0IjtzOjEwOiJwaG9uZV9jb2RlIjtpOjk0O3M6NToiZW1haWwiO3M6MTY6Im1yY2hhaUBnbWFpbC5jb20iO3M6ODoidGltZXpvbmUiO3M6MTI6IkFzaWEvQ29sb21ibyI7czo5OiJ0aGVtZV9oZXgiO3M6NzoiI0Y5NzMxNiI7czo5OiJ0aGVtZV9yZ2IiO3M6MTI6IjI0OSwgMTE1LCAyMiI7czo0OiJsb2dvIjtzOjM2OiIzNGI2NmEyMjVkOWU5NjQwNzg0M2FlNTE5YmM0M2Y1OS5qcGciO3M6MTA6ImNvdW50cnlfaWQiO2k6MjEwO3M6MTU6ImhpZGVfbmV3X29yZGVycyI7aTowO3M6MjE6ImhpZGVfbmV3X3Jlc2VydmF0aW9ucyI7aTowO3M6MjM6ImhpZGVfbmV3X3dhaXRlcl9yZXF1ZXN0IjtpOjA7czoxMToiY3VycmVuY3lfaWQiO2k6NTtzOjEyOiJsaWNlbnNlX3R5cGUiO3M6NDoiZnJlZSI7czo5OiJpc19hY3RpdmUiO2k6MTtzOjEwOiJjcmVhdGVkX2F0IjtzOjE5OiIyMDI1LTExLTAyIDA2OjA2OjExIjtzOjEwOiJ1cGRhdGVkX2F0IjtzOjE5OiIyMDI1LTExLTA0IDA1OjU1OjEwIjtzOjIzOiJjdXN0b21lcl9sb2dpbl9yZXF1aXJlZCI7aToxO3M6ODoiYWJvdXRfdXMiO3M6MTMwNDoiPHAgY2xhc3M9InRleHQtbGcgdGV4dC1ncmF5LTYwMCBtYi02Ij4KICAgICAgICAgIFdlbGNvbWUgdG8gb3VyIHJlc3RhdXJhbnQsIHdoZXJlIGdyZWF0IGZvb2QgYW5kIGdvb2QgdmliZXMgY29tZSB0b2dldGhlciEgV2UncmUgYSBsb2NhbCwgZmFtaWx5LW93bmVkIHNwb3QgdGhhdCBsb3ZlcyBicmluZ2luZyBwZW9wbGUgdG9nZXRoZXIgb3ZlciBkZWxpY2lvdXMgbWVhbHMgYW5kIHVuZm9yZ2V0dGFibGUgbW9tZW50cy4gV2hldGhlciB5b3UncmUgaGVyZSBmb3IgYSBxdWljayBiaXRlLCBhIGZhbWlseSBkaW5uZXIsIG9yIGEgY2VsZWJyYXRpb24sIHdlJ3JlIGFsbCBhYm91dCBtYWtpbmcgeW91ciB0aW1lIHdpdGggdXMgc3BlY2lhbC4KICAgICAgICA8L3A+CiAgICAgICAgPHAgY2xhc3M9InRleHQtbGcgdGV4dC1ncmF5LTYwMCBtYi02Ij4KICAgICAgICAgIE91ciBtZW51IGlzIHBhY2tlZCB3aXRoIGRpc2hlcyBtYWRlIGZyb20gZnJlc2gsIHF1YWxpdHkgaW5ncmVkaWVudHMgYmVjYXVzZSB3ZSBiZWxpZXZlIGZvb2Qgc2hvdWxkIHRhc3RlIGFzCiAgICAgICAgICBnb29kIGFzIGl0IG1ha2VzIHlvdSBmZWVsLiBGcm9tIG91ciBzaWduYXR1cmUgZGlzaGVzIHRvIHNlYXNvbmFsIHNwZWNpYWxzLCB0aGVyZSdzIGFsd2F5cyBzb21ldGhpbmcgdG8gZXhjaXRlCiAgICAgICAgICB5b3VyIHRhc3RlIGJ1ZHMuCiAgICAgICAgPC9wPgogICAgICAgIDxwIGNsYXNzPSJ0ZXh0LWxnIHRleHQtZ3JheS02MDAgbWItNiI+CiAgICAgICAgICBCdXQgd2UncmUgbm90IGp1c3QgYWJvdXQgdGhlIGZvb2TigJR3ZSdyZSBhYm91dCBjb21tdW5pdHkuIFdlIGxvdmUgc2VlaW5nIGZhbWlsaWFyIGZhY2VzIGFuZCB3ZWxjb21pbmcgbmV3IG9uZXMuCiAgICAgICAgICBPdXIgdGVhbSBpcyBhIGZ1biwgZnJpZW5kbHkgYnVuY2ggZGVkaWNhdGVkIHRvIHNlcnZpbmcgeW91IHdpdGggYSBzbWlsZSBhbmQgbWFraW5nIHN1cmUgZXZlcnkgdmlzaXQgZmVlbHMgbGlrZQogICAgICAgICAgY29taW5nIGhvbWUuCiAgICAgICAgPC9wPgogICAgICAgIDxwIGNsYXNzPSJ0ZXh0LWxnIHRleHQtZ3JheS02MDAiPgogICAgICAgICAgU28sIGNvbWUgb24gaW4sIGdyYWIgYSBzZWF0LCBhbmQgbGV0IHVzIHRha2UgY2FyZSBvZiB0aGUgcmVzdC4gV2UgY2FuJ3Qgd2FpdCB0byBzaGFyZSBvdXIgbG92ZSBvZiBmb29kIHdpdGgKICAgICAgICAgIHlvdSEKICAgICAgICA8L3A+CiAgICAgICAgPHAgY2xhc3M9InRleHQtbGcgdGV4dC1ncmF5LTgwMCBmb250LXNlbWlib2xkIG10LTYiPlNlZSB5b3Ugc29vbiEg8J+Nve+4j+KcqDwvcD4iO3M6MzA6ImFsbG93X2N1c3RvbWVyX2RlbGl2ZXJ5X29yZGVycyI7aToxO3M6Mjg6ImFsbG93X2N1c3RvbWVyX3BpY2t1cF9vcmRlcnMiO2k6MTtzOjE3OiJwaWNrdXBfZGF5c19yYW5nZSI7aTo3O3M6MjE6ImFsbG93X2N1c3RvbWVyX29yZGVycyI7aToxO3M6MjA6ImFsbG93X2RpbmVfaW5fb3JkZXJzIjtpOjE7czo4OiJzaG93X3ZlZyI7aToxO3M6MTA6InNob3dfaGFsYWwiO2k6MDtzOjEwOiJwYWNrYWdlX2lkIjtpOjM7czoxMjoicGFja2FnZV90eXBlIjtzOjg6ImxpZmV0aW1lIjtzOjY6InN0YXR1cyI7czo2OiJhY3RpdmUiO3M6MTc6ImxpY2Vuc2VfZXhwaXJlX29uIjtOO3M6MTM6InRyaWFsX2VuZHNfYXQiO047czoxODoibGljZW5zZV91cGRhdGVkX2F0IjtzOjE5OiIyMDI1LTExLTAyIDA2OjA2OjExIjtzOjIzOiJzdWJzY3JpcHRpb25fdXBkYXRlZF9hdCI7czoxOToiMjAyNS0xMS0wMiAwNjowNjoxMSI7czo5OiJzdHJpcGVfaWQiO047czo3OiJwbV90eXBlIjtOO3M6MTI6InBtX2xhc3RfZm91ciI7TjtzOjI1OiJpc193YWl0ZXJfcmVxdWVzdF9lbmFibGVkIjtpOjE7czozMjoiZGVmYXVsdF90YWJsZV9yZXNlcnZhdGlvbl9zdGF0dXMiO3M6OToiQ29uZmlybWVkIjtzOjIwOiJkaXNhYmxlX3Nsb3RfbWludXRlcyI7aTozMDtzOjE1OiJhcHByb3ZhbF9zdGF0dXMiO3M6ODoiQXBwcm92ZWQiO3M6MTY6InJlamVjdGlvbl9yZWFzb24iO047czoxMzoiZmFjZWJvb2tfbGluayI7czoyNToiaHR0cHM6Ly93d3cuZmFjZWJvb2suY29tLyI7czoxNDoiaW5zdGFncmFtX2xpbmsiO3M6MjY6Imh0dHBzOi8vd3d3Lmluc3RhZ3JhbS5jb20vIjtzOjEyOiJ0d2l0dGVyX2xpbmsiO3M6MjQ6Imh0dHBzOi8vd3d3LnR3aXR0ZXIuY29tLyI7czo5OiJ5ZWxwX2xpbmsiO047czoxNDoidGFibGVfcmVxdWlyZWQiO2k6MDtzOjE0OiJzaG93X2xvZ29fdGV4dCI7aToxO3M6MTI6Im1ldGFfa2V5d29yZCI7TjtzOjE2OiJtZXRhX2Rlc2NyaXB0aW9uIjtOO3M6MzQ6InVwbG9hZF9mYXZfaWNvbl9hbmRyb2lkX2Nocm9tZV8xOTIiO047czozNDoidXBsb2FkX2Zhdl9pY29uX2FuZHJvaWRfY2hyb21lXzUxMiI7TjtzOjMyOiJ1cGxvYWRfZmF2X2ljb25fYXBwbGVfdG91Y2hfaWNvbiI7TjtzOjE3OiJ1cGxvYWRfZmF2aWNvbl8xNiI7TjtzOjE3OiJ1cGxvYWRfZmF2aWNvbl8zMiI7TjtzOjc6ImZhdmljb24iO047czozNjoiaXNfd2FpdGVyX3JlcXVlc3RfZW5hYmxlZF9vbl9kZXNrdG9wIjtpOjE7czozNToiaXNfd2FpdGVyX3JlcXVlc3RfZW5hYmxlZF9vbl9tb2JpbGUiO2k6MTtzOjM2OiJpc193YWl0ZXJfcmVxdWVzdF9lbmFibGVkX29wZW5fYnlfcXIiO2k6MDtzOjExOiJ3ZWJtYW5pZmVzdCI7TjtzOjE1OiJlbmFibGVfdGlwX3Nob3AiO2k6MTtzOjE0OiJlbmFibGVfdGlwX3BvcyI7aToxO3M6MjU6ImlzX3B3YV9pbnN0YWxsX2FsZXJ0X3Nob3ciO2k6MTtzOjE5OiJhdXRvX2NvbmZpcm1fb3JkZXJzIjtpOjA7czoyMzoic2hvd19vcmRlcl90eXBlX29wdGlvbnMiO2k6MTtzOjI3OiJoaWRlX21lbnVfaXRlbV9pbWFnZV9vbl9wb3MiO2k6MDtzOjM3OiJoaWRlX21lbnVfaXRlbV9pbWFnZV9vbl9jdXN0b21lcl9zaXRlIjtpOjA7czo4OiJ0YXhfbW9kZSI7czo1OiJvcmRlciI7czoxMzoidGF4X2luY2x1c2l2ZSI7aTowO3M6MjI6ImN1c3RvbWVyX3NpdGVfbGFuZ3VhZ2UiO3M6MjoiZW4iO3M6MjQ6ImVuYWJsZV9hZG1pbl9yZXNlcnZhdGlvbiI7aToxO3M6Mjc6ImVuYWJsZV9jdXN0b21lcl9yZXNlcnZhdGlvbiI7aToxO3M6MTg6Im1pbmltdW1fcGFydHlfc2l6ZSI7aToxO3M6MjY6InRhYmxlX2xvY2tfdGltZW91dF9taW51dGVzIjtpOjEwO31zOjExOiIAKgBvcmlnaW5hbCI7YTo3Njp7czoyOiJpZCI7aToxO3M6NDoibmFtZSI7czo3OiJNciBDaGFpIjtzOjQ6Imhhc2giO3M6NzoibXItY2hhaSI7czo3OiJhZGRyZXNzIjtzOjI1OiJNYWluIFN0cmVldCwgT2x1dmlsIDMyMzYwIjtzOjEyOiJwaG9uZV9udW1iZXIiO3M6MTI6IjA3NCAzOTQgMjQ2NCI7czoxMDoicGhvbmVfY29kZSI7czoyOiI5NCI7czo1OiJlbWFpbCI7czoxNjoibXJjaGFpQGdtYWlsLmNvbSI7czo4OiJ0aW1lem9uZSI7czoxMjoiQXNpYS9Db2xvbWJvIjtzOjk6InRoZW1lX2hleCI7czo3OiIjRjk3MzE2IjtzOjk6InRoZW1lX3JnYiI7czoxMjoiMjQ5LCAxMTUsIDIyIjtzOjQ6ImxvZ28iO3M6MzY6IjM0YjY2YTIyNWQ5ZTk2NDA3ODQzYWU1MTliYzQzZjU5LmpwZyI7czoxMDoiY291bnRyeV9pZCI7aToyMTA7czoxNToiaGlkZV9uZXdfb3JkZXJzIjtpOjA7czoyMToiaGlkZV9uZXdfcmVzZXJ2YXRpb25zIjtpOjA7czoyMzoiaGlkZV9uZXdfd2FpdGVyX3JlcXVlc3QiO2k6MDtzOjExOiJjdXJyZW5jeV9pZCI7aTo1O3M6MTI6ImxpY2Vuc2VfdHlwZSI7czo0OiJmcmVlIjtzOjk6ImlzX2FjdGl2ZSI7aToxO3M6MTA6ImNyZWF0ZWRfYXQiO3M6MTk6IjIwMjUtMTEtMDIgMDY6MDY6MTEiO3M6MTA6InVwZGF0ZWRfYXQiO3M6MTk6IjIwMjUtMTEtMDQgMDU6NTU6MTAiO3M6MjM6ImN1c3RvbWVyX2xvZ2luX3JlcXVpcmVkIjtpOjE7czo4OiJhYm91dF91cyI7czoxMzA0OiI8cCBjbGFzcz0idGV4dC1sZyB0ZXh0LWdyYXktNjAwIG1iLTYiPgogICAgICAgICAgV2VsY29tZSB0byBvdXIgcmVzdGF1cmFudCwgd2hlcmUgZ3JlYXQgZm9vZCBhbmQgZ29vZCB2aWJlcyBjb21lIHRvZ2V0aGVyISBXZSdyZSBhIGxvY2FsLCBmYW1pbHktb3duZWQgc3BvdCB0aGF0IGxvdmVzIGJyaW5naW5nIHBlb3BsZSB0b2dldGhlciBvdmVyIGRlbGljaW91cyBtZWFscyBhbmQgdW5mb3JnZXR0YWJsZSBtb21lbnRzLiBXaGV0aGVyIHlvdSdyZSBoZXJlIGZvciBhIHF1aWNrIGJpdGUsIGEgZmFtaWx5IGRpbm5lciwgb3IgYSBjZWxlYnJhdGlvbiwgd2UncmUgYWxsIGFib3V0IG1ha2luZyB5b3VyIHRpbWUgd2l0aCB1cyBzcGVjaWFsLgogICAgICAgIDwvcD4KICAgICAgICA8cCBjbGFzcz0idGV4dC1sZyB0ZXh0LWdyYXktNjAwIG1iLTYiPgogICAgICAgICAgT3VyIG1lbnUgaXMgcGFja2VkIHdpdGggZGlzaGVzIG1hZGUgZnJvbSBmcmVzaCwgcXVhbGl0eSBpbmdyZWRpZW50cyBiZWNhdXNlIHdlIGJlbGlldmUgZm9vZCBzaG91bGQgdGFzdGUgYXMKICAgICAgICAgIGdvb2QgYXMgaXQgbWFrZXMgeW91IGZlZWwuIEZyb20gb3VyIHNpZ25hdHVyZSBkaXNoZXMgdG8gc2Vhc29uYWwgc3BlY2lhbHMsIHRoZXJlJ3MgYWx3YXlzIHNvbWV0aGluZyB0byBleGNpdGUKICAgICAgICAgIHlvdXIgdGFzdGUgYnVkcy4KICAgICAgICA8L3A+CiAgICAgICAgPHAgY2xhc3M9InRleHQtbGcgdGV4dC1ncmF5LTYwMCBtYi02Ij4KICAgICAgICAgIEJ1dCB3ZSdyZSBub3QganVzdCBhYm91dCB0aGUgZm9vZOKAlHdlJ3JlIGFib3V0IGNvbW11bml0eS4gV2UgbG92ZSBzZWVpbmcgZmFtaWxpYXIgZmFjZXMgYW5kIHdlbGNvbWluZyBuZXcgb25lcy4KICAgICAgICAgIE91ciB0ZWFtIGlzIGEgZnVuLCBmcmllbmRseSBidW5jaCBkZWRpY2F0ZWQgdG8gc2VydmluZyB5b3Ugd2l0aCBhIHNtaWxlIGFuZCBtYWtpbmcgc3VyZSBldmVyeSB2aXNpdCBmZWVscyBsaWtlCiAgICAgICAgICBjb21pbmcgaG9tZS4KICAgICAgICA8L3A+CiAgICAgICAgPHAgY2xhc3M9InRleHQtbGcgdGV4dC1ncmF5LTYwMCI+CiAgICAgICAgICBTbywgY29tZSBvbiBpbiwgZ3JhYiBhIHNlYXQsIGFuZCBsZXQgdXMgdGFrZSBjYXJlIG9mIHRoZSByZXN0LiBXZSBjYW4ndCB3YWl0IHRvIHNoYXJlIG91ciBsb3ZlIG9mIGZvb2Qgd2l0aAogICAgICAgICAgeW91IQogICAgICAgIDwvcD4KICAgICAgICA8cCBjbGFzcz0idGV4dC1sZyB0ZXh0LWdyYXktODAwIGZvbnQtc2VtaWJvbGQgbXQtNiI+U2VlIHlvdSBzb29uISDwn42977iP4pyoPC9wPiI7czozMDoiYWxsb3dfY3VzdG9tZXJfZGVsaXZlcnlfb3JkZXJzIjtpOjE7czoyODoiYWxsb3dfY3VzdG9tZXJfcGlja3VwX29yZGVycyI7aToxO3M6MTc6InBpY2t1cF9kYXlzX3JhbmdlIjtpOjc7czoyMToiYWxsb3dfY3VzdG9tZXJfb3JkZXJzIjtpOjE7czoyMDoiYWxsb3dfZGluZV9pbl9vcmRlcnMiO2k6MTtzOjg6InNob3dfdmVnIjtpOjE7czoxMDoic2hvd19oYWxhbCI7aTowO3M6MTA6InBhY2thZ2VfaWQiO2k6MztzOjEyOiJwYWNrYWdlX3R5cGUiO3M6ODoibGlmZXRpbWUiO3M6Njoic3RhdHVzIjtzOjY6ImFjdGl2ZSI7czoxNzoibGljZW5zZV9leHBpcmVfb24iO047czoxMzoidHJpYWxfZW5kc19hdCI7TjtzOjE4OiJsaWNlbnNlX3VwZGF0ZWRfYXQiO3M6MTk6IjIwMjUtMTEtMDIgMDY6MDY6MTEiO3M6MjM6InN1YnNjcmlwdGlvbl91cGRhdGVkX2F0IjtzOjE5OiIyMDI1LTExLTAyIDA2OjA2OjExIjtzOjk6InN0cmlwZV9pZCI7TjtzOjc6InBtX3R5cGUiO047czoxMjoicG1fbGFzdF9mb3VyIjtOO3M6MjU6ImlzX3dhaXRlcl9yZXF1ZXN0X2VuYWJsZWQiO2k6MTtzOjMyOiJkZWZhdWx0X3RhYmxlX3Jlc2VydmF0aW9uX3N0YXR1cyI7czo5OiJDb25maXJtZWQiO3M6MjA6ImRpc2FibGVfc2xvdF9taW51dGVzIjtpOjMwO3M6MTU6ImFwcHJvdmFsX3N0YXR1cyI7czo4OiJBcHByb3ZlZCI7czoxNjoicmVqZWN0aW9uX3JlYXNvbiI7TjtzOjEzOiJmYWNlYm9va19saW5rIjtzOjI1OiJodHRwczovL3d3dy5mYWNlYm9vay5jb20vIjtzOjE0OiJpbnN0YWdyYW1fbGluayI7czoyNjoiaHR0cHM6Ly93d3cuaW5zdGFncmFtLmNvbS8iO3M6MTI6InR3aXR0ZXJfbGluayI7czoyNDoiaHR0cHM6Ly93d3cudHdpdHRlci5jb20vIjtzOjk6InllbHBfbGluayI7TjtzOjE0OiJ0YWJsZV9yZXF1aXJlZCI7aTowO3M6MTQ6InNob3dfbG9nb190ZXh0IjtpOjE7czoxMjoibWV0YV9rZXl3b3JkIjtOO3M6MTY6Im1ldGFfZGVzY3JpcHRpb24iO047czozNDoidXBsb2FkX2Zhdl9pY29uX2FuZHJvaWRfY2hyb21lXzE5MiI7TjtzOjM0OiJ1cGxvYWRfZmF2X2ljb25fYW5kcm9pZF9jaHJvbWVfNTEyIjtOO3M6MzI6InVwbG9hZF9mYXZfaWNvbl9hcHBsZV90b3VjaF9pY29uIjtOO3M6MTc6InVwbG9hZF9mYXZpY29uXzE2IjtOO3M6MTc6InVwbG9hZF9mYXZpY29uXzMyIjtOO3M6NzoiZmF2aWNvbiI7TjtzOjM2OiJpc193YWl0ZXJfcmVxdWVzdF9lbmFibGVkX29uX2Rlc2t0b3AiO2k6MTtzOjM1OiJpc193YWl0ZXJfcmVxdWVzdF9lbmFibGVkX29uX21vYmlsZSI7aToxO3M6MzY6ImlzX3dhaXRlcl9yZXF1ZXN0X2VuYWJsZWRfb3Blbl9ieV9xciI7aTowO3M6MTE6IndlYm1hbmlmZXN0IjtOO3M6MTU6ImVuYWJsZV90aXBfc2hvcCI7aToxO3M6MTQ6ImVuYWJsZV90aXBfcG9zIjtpOjE7czoyNToiaXNfcHdhX2luc3RhbGxfYWxlcnRfc2hvdyI7aToxO3M6MTk6ImF1dG9fY29uZmlybV9vcmRlcnMiO2k6MDtzOjIzOiJzaG93X29yZGVyX3R5cGVfb3B0aW9ucyI7aToxO3M6Mjc6ImhpZGVfbWVudV9pdGVtX2ltYWdlX29uX3BvcyI7aTowO3M6Mzc6ImhpZGVfbWVudV9pdGVtX2ltYWdlX29uX2N1c3RvbWVyX3NpdGUiO2k6MDtzOjg6InRheF9tb2RlIjtzOjU6Im9yZGVyIjtzOjEzOiJ0YXhfaW5jbHVzaXZlIjtpOjA7czoyMjoiY3VzdG9tZXJfc2l0ZV9sYW5ndWFnZSI7czoyOiJlbiI7czoyNDoiZW5hYmxlX2FkbWluX3Jlc2VydmF0aW9uIjtpOjE7czoyNzoiZW5hYmxlX2N1c3RvbWVyX3Jlc2VydmF0aW9uIjtpOjE7czoxODoibWluaW11bV9wYXJ0eV9zaXplIjtpOjE7czoyNjoidGFibGVfbG9ja190aW1lb3V0X21pbnV0ZXMiO2k6MTA7fXM6MTA6IgAqAGNoYW5nZXMiO2E6MDp7fXM6MTE6IgAqAHByZXZpb3VzIjthOjA6e31zOjg6IgAqAGNhc3RzIjthOjEwOntzOjE3OiJsaWNlbnNlX2V4cGlyZV9vbiI7czo4OiJkYXRldGltZSI7czoxNToidHJpYWxfZXhwaXJlX29uIjtzOjg6ImRhdGV0aW1lIjtzOjE4OiJsaWNlbnNlX3VwZGF0ZWRfYXQiO3M6ODoiZGF0ZXRpbWUiO3M6MjM6InN1YnNjcmlwdGlvbl91cGRhdGVkX2F0IjtzOjg6ImRhdGV0aW1lIjtzOjEwOiJjcmVhdGVkX2F0IjtzOjg6ImRhdGV0aW1lIjtzOjEwOiJ1cGRhdGVkX2F0IjtzOjg6ImRhdGV0aW1lIjtzOjIzOiJjdXN0b21fZGVsaXZlcnlfb3B0aW9ucyI7czo1OiJhcnJheSI7czo5OiJpc19hY3RpdmUiO3M6NzoiYm9vbGVhbiI7czoyNDoiZW5hYmxlX2FkbWluX3Jlc2VydmF0aW9uIjtzOjc6ImJvb2xlYW4iO3M6Mjc6ImVuYWJsZV9jdXN0b21lcl9yZXNlcnZhdGlvbiI7czo3OiJib29sZWFuIjt9czoxNzoiACoAY2xhc3NDYXN0Q2FjaGUiO2E6MDp7fXM6MjE6IgAqAGF0dHJpYnV0ZUNhc3RDYWNoZSI7YTowOnt9czoxMzoiACoAZGF0ZUZvcm1hdCI7TjtzOjEwOiIAKgBhcHBlbmRzIjthOjE6e2k6MDtzOjg6ImxvZ29fdXJsIjt9czoxOToiACoAZGlzcGF0Y2hlc0V2ZW50cyI7YTowOnt9czoxNDoiACoAb2JzZXJ2YWJsZXMiO2E6MDp7fXM6MTI6IgAqAHJlbGF0aW9ucyI7YTowOnt9czoxMDoiACoAdG91Y2hlcyI7YTowOnt9czoyNzoiACoAcmVsYXRpb25BdXRvbG9hZENhbGxiYWNrIjtOO3M6MjY6IgAqAHJlbGF0aW9uQXV0b2xvYWRDb250ZXh0IjtOO3M6MTA6InRpbWVzdGFtcHMiO2I6MTtzOjEzOiJ1c2VzVW5pcXVlSWRzIjtiOjA7czo5OiIAKgBoaWRkZW4iO2E6MDp7fXM6MTA6IgAqAHZpc2libGUiO2E6MDp7fXM6MTE6IgAqAGZpbGxhYmxlIjthOjA6e31zOjEwOiIAKgBndWFyZGVkIjthOjE6e2k6MDtzOjI6ImlkIjt9czoxNzoiY3VzdG9tZXJJcEFkZHJlc3MiO047czoyNDoiZXN0aW1hdGlvbkJpbGxpbmdBZGRyZXNzIjthOjA6e31zOjEzOiJjb2xsZWN0VGF4SWRzIjtiOjA7czo4OiJjb3Vwb25JZCI7TjtzOjE1OiJwcm9tb3Rpb25Db2RlSWQiO047czoxOToiYWxsb3dQcm9tb3Rpb25Db2RlcyI7YjowO319czoxMDoiACoAdG91Y2hlcyI7YTowOnt9czoyNzoiACoAcmVsYXRpb25BdXRvbG9hZENhbGxiYWNrIjtOO3M6MjY6IgAqAHJlbGF0aW9uQXV0b2xvYWRDb250ZXh0IjtOO3M6MTA6InRpbWVzdGFtcHMiO2I6MDtzOjEzOiJ1c2VzVW5pcXVlSWRzIjtiOjA7czo5OiIAKgBoaWRkZW4iO2E6MDp7fXM6MTA6IgAqAHZpc2libGUiO2E6MDp7fXM6MTE6IgAqAGZpbGxhYmxlIjthOjA6e31zOjEwOiIAKgBndWFyZGVkIjthOjE6e2k6MDtzOjE6IioiO319czo4OiJjdXJyZW5jeSI7czozOiJScyAiO3M6NToiaXNSdGwiO2I6MDtzOjg6ImJyYW5jaGVzIjtyOjkxNDtzOjIzOiJjdXJyZW5jeV9mb3JtYXRfc2V0dGluZyI7cjoxMDg1O3M6MTc6InRvZGF5X29yZGVyX2NvdW50IjtpOjA7czoyODoiYWN0aXZlX3dhaXRlcl9yZXF1ZXN0c19jb3VudCI7aTowO30=', 1762857499);

-- --------------------------------------------------------

--
-- Table structure for table `split_orders`
--

CREATE TABLE `split_orders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `amount` decimal(16,2) NOT NULL,
  `status` enum('pending','paid') NOT NULL DEFAULT 'pending',
  `payment_method` enum('cash','upi','card','bank_transfer','due','stripe','razorpay') NOT NULL DEFAULT 'cash',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `split_order_items`
--

CREATE TABLE `split_order_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `split_order_id` bigint(20) UNSIGNED NOT NULL,
  `order_item_id` bigint(20) UNSIGNED NOT NULL,
  `quantity` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stripe_payments`
--

CREATE TABLE `stripe_payments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `payment_date` datetime DEFAULT NULL,
  `amount` decimal(16,2) DEFAULT NULL,
  `payment_status` enum('pending','requested','declined','completed') NOT NULL DEFAULT 'pending',
  `payment_error_response` text DEFAULT NULL,
  `stripe_payment_intent` varchar(191) DEFAULT NULL,
  `stripe_session_id` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `superadmin_payment_gateways`
--

CREATE TABLE `superadmin_payment_gateways` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `razorpay_type` enum('test','live') NOT NULL DEFAULT 'test',
  `test_razorpay_key` text DEFAULT NULL,
  `test_razorpay_secret` text DEFAULT NULL,
  `razorpay_test_webhook_key` text DEFAULT NULL,
  `live_razorpay_key` text DEFAULT NULL,
  `live_razorpay_secret` text DEFAULT NULL,
  `razorpay_live_webhook_key` text DEFAULT NULL,
  `razorpay_status` tinyint(1) NOT NULL DEFAULT 0,
  `stripe_type` enum('test','live') NOT NULL DEFAULT 'test',
  `test_stripe_key` text DEFAULT NULL,
  `test_stripe_secret` text DEFAULT NULL,
  `stripe_test_webhook_key` text DEFAULT NULL,
  `live_stripe_key` text DEFAULT NULL,
  `live_stripe_secret` text DEFAULT NULL,
  `stripe_live_webhook_key` text DEFAULT NULL,
  `stripe_status` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `flutterwave_status` tinyint(1) NOT NULL DEFAULT 0,
  `flutterwave_type` enum('test','live') NOT NULL DEFAULT 'test',
  `test_flutterwave_key` text DEFAULT NULL,
  `test_flutterwave_secret` text DEFAULT NULL,
  `test_flutterwave_hash` text DEFAULT NULL,
  `flutterwave_test_webhook_key` text DEFAULT NULL,
  `live_flutterwave_key` text DEFAULT NULL,
  `live_flutterwave_secret` text DEFAULT NULL,
  `live_flutterwave_hash` text DEFAULT NULL,
  `flutterwave_live_webhook_key` text DEFAULT NULL,
  `live_paypal_client_id` varchar(191) DEFAULT NULL,
  `live_paypal_secret` varchar(191) DEFAULT NULL,
  `test_paypal_client_id` varchar(191) DEFAULT NULL,
  `test_paypal_secret` varchar(191) DEFAULT NULL,
  `paypal_status` tinyint(1) NOT NULL DEFAULT 0,
  `paypal_mode` enum('sandbox','live') NOT NULL DEFAULT 'sandbox',
  `live_payfast_merchant_id` varchar(191) DEFAULT NULL,
  `live_payfast_merchant_key` varchar(191) DEFAULT NULL,
  `live_payfast_passphrase` varchar(191) DEFAULT NULL,
  `test_payfast_merchant_id` varchar(191) DEFAULT NULL,
  `test_payfast_merchant_key` varchar(191) DEFAULT NULL,
  `test_payfast_passphrase` varchar(191) DEFAULT NULL,
  `payfast_mode` enum('sandbox','live') NOT NULL DEFAULT 'sandbox',
  `payfast_status` tinyint(1) NOT NULL DEFAULT 0,
  `live_paystack_key` varchar(191) DEFAULT NULL,
  `live_paystack_secret` varchar(191) DEFAULT NULL,
  `live_paystack_merchant_email` varchar(191) DEFAULT NULL,
  `test_paystack_key` varchar(191) DEFAULT NULL,
  `test_paystack_secret` varchar(191) DEFAULT NULL,
  `test_paystack_merchant_email` varchar(191) DEFAULT NULL,
  `paystack_payment_url` varchar(191) DEFAULT 'https://api.paystack.co',
  `paystack_status` tinyint(1) NOT NULL DEFAULT 0,
  `paystack_mode` enum('sandbox','live') NOT NULL DEFAULT 'sandbox',
  `xendit_status` tinyint(1) NOT NULL DEFAULT 0,
  `xendit_mode` enum('sandbox','live') NOT NULL DEFAULT 'sandbox',
  `test_xendit_public_key` varchar(191) DEFAULT NULL,
  `test_xendit_secret_key` varchar(191) DEFAULT NULL,
  `live_xendit_public_key` varchar(191) DEFAULT NULL,
  `live_xendit_secret_key` varchar(191) DEFAULT NULL,
  `test_xendit_webhook_token` varchar(191) DEFAULT NULL,
  `live_xendit_webhook_token` varchar(191) DEFAULT NULL,
  `paddle_status` tinyint(1) NOT NULL DEFAULT 0,
  `paddle_mode` enum('sandbox','live') NOT NULL DEFAULT 'sandbox',
  `test_paddle_vendor_id` varchar(191) DEFAULT NULL,
  `test_paddle_api_key` text DEFAULT NULL,
  `test_paddle_public_key` varchar(191) DEFAULT NULL,
  `test_paddle_client_token` text DEFAULT NULL,
  `live_paddle_vendor_id` varchar(191) DEFAULT NULL,
  `live_paddle_api_key` text DEFAULT NULL,
  `live_paddle_public_key` varchar(191) DEFAULT NULL,
  `live_paddle_client_token` text DEFAULT NULL,
  `paddle_webhook_secret` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `superadmin_payment_gateways`
--

INSERT INTO `superadmin_payment_gateways` (`id`, `razorpay_type`, `test_razorpay_key`, `test_razorpay_secret`, `razorpay_test_webhook_key`, `live_razorpay_key`, `live_razorpay_secret`, `razorpay_live_webhook_key`, `razorpay_status`, `stripe_type`, `test_stripe_key`, `test_stripe_secret`, `stripe_test_webhook_key`, `live_stripe_key`, `live_stripe_secret`, `stripe_live_webhook_key`, `stripe_status`, `created_at`, `updated_at`, `flutterwave_status`, `flutterwave_type`, `test_flutterwave_key`, `test_flutterwave_secret`, `test_flutterwave_hash`, `flutterwave_test_webhook_key`, `live_flutterwave_key`, `live_flutterwave_secret`, `live_flutterwave_hash`, `flutterwave_live_webhook_key`, `live_paypal_client_id`, `live_paypal_secret`, `test_paypal_client_id`, `test_paypal_secret`, `paypal_status`, `paypal_mode`, `live_payfast_merchant_id`, `live_payfast_merchant_key`, `live_payfast_passphrase`, `test_payfast_merchant_id`, `test_payfast_merchant_key`, `test_payfast_passphrase`, `payfast_mode`, `payfast_status`, `live_paystack_key`, `live_paystack_secret`, `live_paystack_merchant_email`, `test_paystack_key`, `test_paystack_secret`, `test_paystack_merchant_email`, `paystack_payment_url`, `paystack_status`, `paystack_mode`, `xendit_status`, `xendit_mode`, `test_xendit_public_key`, `test_xendit_secret_key`, `live_xendit_public_key`, `live_xendit_secret_key`, `test_xendit_webhook_token`, `live_xendit_webhook_token`, `paddle_status`, `paddle_mode`, `test_paddle_vendor_id`, `test_paddle_api_key`, `test_paddle_public_key`, `test_paddle_client_token`, `live_paddle_vendor_id`, `live_paddle_api_key`, `live_paddle_public_key`, `live_paddle_client_token`, `paddle_webhook_secret`) VALUES
(1, 'test', NULL, NULL, NULL, NULL, NULL, NULL, 0, 'test', NULL, NULL, NULL, NULL, NULL, NULL, 0, '2025-11-02 00:35:56', '2025-11-02 00:35:56', 0, 'test', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 'sandbox', NULL, NULL, NULL, NULL, NULL, NULL, 'sandbox', 0, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.paystack.co', 0, 'sandbox', 0, 'sandbox', NULL, NULL, NULL, NULL, NULL, NULL, 0, 'sandbox', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 'test', NULL, NULL, NULL, NULL, NULL, NULL, 0, 'test', NULL, NULL, NULL, NULL, NULL, NULL, 0, '2025-11-02 00:36:11', '2025-11-02 00:36:11', 0, 'test', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, 'sandbox', NULL, NULL, NULL, NULL, NULL, NULL, 'sandbox', 0, NULL, NULL, NULL, NULL, NULL, NULL, 'https://api.paystack.co', 0, 'sandbox', 0, 'sandbox', NULL, NULL, NULL, NULL, NULL, NULL, 0, 'sandbox', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `restaurant_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `phone` varchar(191) DEFAULT NULL,
  `email` varchar(191) DEFAULT NULL,
  `address` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tables`
--

CREATE TABLE `tables` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `branch_id` bigint(20) UNSIGNED DEFAULT NULL,
  `table_code` varchar(191) NOT NULL,
  `hash` varchar(191) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `available_status` enum('available','reserved','running') NOT NULL DEFAULT 'available',
  `area_id` bigint(20) UNSIGNED NOT NULL,
  `seating_capacity` tinyint(3) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tables`
--

INSERT INTO `tables` (`id`, `branch_id`, `table_code`, `hash`, `status`, `available_status`, `area_id`, `seating_capacity`, `created_at`, `updated_at`) VALUES
(1, 1, 'T1', 'd03465da7743dcb640c69d1a12b20aa7', 'active', 'running', 1, 4, '2025-11-02 23:24:15', '2025-11-03 02:53:46'),
(2, 2, '101', '5469a6af7d440e0966a5a09979fc1987', 'active', 'available', 2, 4, '2025-11-08 02:14:13', '2025-11-08 02:14:13');

-- --------------------------------------------------------

--
-- Table structure for table `table_sessions`
--

CREATE TABLE `table_sessions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `branch_id` bigint(20) UNSIGNED NOT NULL,
  `table_id` bigint(20) UNSIGNED NOT NULL,
  `locked_by_user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `locked_at` datetime DEFAULT NULL,
  `last_activity_at` datetime DEFAULT NULL,
  `session_token` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `table_sessions`
--

INSERT INTO `table_sessions` (`id`, `branch_id`, `table_id`, `locked_by_user_id`, `locked_at`, `last_activity_at`, `session_token`, `created_at`, `updated_at`) VALUES
(1, 1, 1, NULL, NULL, NULL, 'I1PCVa88z2okYDy4qXYIHHge4u01twmm', '2025-11-03 02:53:45', '2025-11-03 02:53:45'),
(2, 2, 2, NULL, NULL, NULL, NULL, '2025-11-08 02:26:02', '2025-11-08 02:26:44');

-- --------------------------------------------------------

--
-- Table structure for table `taxes`
--

CREATE TABLE `taxes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `restaurant_id` bigint(20) UNSIGNED DEFAULT NULL,
  `tax_name` varchar(191) NOT NULL,
  `tax_percent` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `units`
--

CREATE TABLE `units` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `branch_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) NOT NULL,
  `symbol` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `units`
--

INSERT INTO `units` (`id`, `branch_id`, `name`, `symbol`, `created_at`, `updated_at`) VALUES
(1, 1, 'Kilogram', 'kg', '2025-11-02 22:52:30', '2025-11-02 22:52:30'),
(2, 1, 'Gram', 'g', '2025-11-02 22:52:30', '2025-11-02 22:52:30'),
(3, 1, 'Liter', 'L', '2025-11-02 22:52:30', '2025-11-02 22:52:30'),
(4, 1, 'Milliliter', 'ml', '2025-11-02 22:52:30', '2025-11-02 22:52:30'),
(5, 1, 'Piece', 'pc', '2025-11-02 22:52:30', '2025-11-02 22:52:30'),
(6, 1, 'Box', 'box', '2025-11-02 22:52:30', '2025-11-02 22:52:30'),
(7, 1, 'Dozen', 'dz', '2025-11-02 22:52:30', '2025-11-02 22:52:30'),
(8, 1, 'Bottle', 'btl', '2025-11-02 22:52:30', '2025-11-02 22:52:30'),
(9, 1, 'Package', 'pkg', '2025-11-02 22:52:30', '2025-11-02 22:52:30'),
(10, 1, 'Can', 'can', '2025-11-02 22:52:30', '2025-11-02 22:52:30'),
(11, 2, 'Kilogram', 'kg', '2025-11-02 22:52:30', '2025-11-02 22:52:30'),
(12, 2, 'Gram', 'g', '2025-11-02 22:52:30', '2025-11-02 22:52:30'),
(13, 2, 'Liter', 'L', '2025-11-02 22:52:30', '2025-11-02 22:52:30'),
(14, 2, 'Milliliter', 'ml', '2025-11-02 22:52:30', '2025-11-02 22:52:30'),
(15, 2, 'Piece', 'pc', '2025-11-02 22:52:30', '2025-11-02 22:52:30'),
(16, 2, 'Box', 'box', '2025-11-02 22:52:30', '2025-11-02 22:52:30'),
(17, 2, 'Dozen', 'dz', '2025-11-02 22:52:30', '2025-11-02 22:52:30'),
(18, 2, 'Bottle', 'btl', '2025-11-02 22:52:30', '2025-11-02 22:52:30'),
(19, 2, 'Package', 'pkg', '2025-11-02 22:52:30', '2025-11-02 22:52:30'),
(20, 2, 'Can', 'can', '2025-11-02 22:52:30', '2025-11-02 22:52:30');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `restaurant_id` bigint(20) UNSIGNED DEFAULT NULL,
  `branch_id` bigint(20) UNSIGNED DEFAULT NULL,
  `name` varchar(191) NOT NULL,
  `email` varchar(191) NOT NULL,
  `phone_number` varchar(191) DEFAULT NULL,
  `phone_code` varchar(191) DEFAULT NULL,
  `terms_and_privacy_accepted` tinyint(1) NOT NULL DEFAULT 0,
  `marketing_emails_accepted` tinyint(1) NOT NULL DEFAULT 0,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(191) NOT NULL,
  `two_factor_secret` text DEFAULT NULL,
  `two_factor_recovery_codes` text DEFAULT NULL,
  `two_factor_confirmed_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `current_team_id` bigint(20) UNSIGNED DEFAULT NULL,
  `profile_photo_path` varchar(2048) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `locale` varchar(191) NOT NULL DEFAULT 'en',
  `stripe_id` varchar(191) DEFAULT NULL,
  `pm_type` varchar(191) DEFAULT NULL,
  `pm_last_four` varchar(4) DEFAULT NULL,
  `trial_ends_at` timestamp NULL DEFAULT NULL,
  `kitchen_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `restaurant_id`, `branch_id`, `name`, `email`, `phone_number`, `phone_code`, `terms_and_privacy_accepted`, `marketing_emails_accepted`, `email_verified_at`, `password`, `two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at`, `remember_token`, `current_team_id`, `profile_photo_path`, `created_at`, `updated_at`, `locale`, `stripe_id`, `pm_type`, `pm_last_four`, `trial_ends_at`, `kitchen_id`) VALUES
(1, NULL, NULL, 'AI Genx', 'superadmin@example.com', '12345677', '93', 0, 0, NULL, '$2y$12$QXJ3HMJMllaZxy3RzNjMiOEwXgmn/Aws0UZg79e4lVnbcMxWlaCgq', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-02 00:36:11', '2025-11-04 01:06:46', 'en', NULL, NULL, NULL, NULL, NULL),
(2, 1, NULL, 'John Durairaj', 'admin@mail.com', NULL, NULL, 0, 0, NULL, '$2y$12$yYUPOcU9Y/C1kLHZsnFAI.zDZIrkOcI3JrPkQ65oG7mNz/Ad/BbGS', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-02 00:36:12', '2025-11-10 07:38:15', 'en', NULL, NULL, NULL, NULL, NULL),
(3, 1, 1, 'Waiter Ajith', 'waiter@mail.com', NULL, NULL, 0, 0, NULL, '$2y$12$xydrXjFsuxqLK6OJ1U0KK.msf9iTQ7HF8JNP8InJCeKJTFl1/6uU.', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-02 00:36:12', '2025-11-10 07:37:18', 'en', NULL, NULL, NULL, NULL, NULL),
(4, 1, 1, 'Jarath', 'jarath@mail.com', NULL, NULL, 0, 0, NULL, '$2y$12$6vWgcwu24QNvowHojn6uwe5JnkHkZ/Q8L5bhD/A7/ErNmbj1/71hO', NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-10 07:34:49', '2025-11-10 07:34:49', 'en', NULL, NULL, NULL, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `waiter_requests`
--

CREATE TABLE `waiter_requests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `branch_id` bigint(20) UNSIGNED NOT NULL,
  `table_id` bigint(20) UNSIGNED NOT NULL,
  `status` enum('pending','completed') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `waiter_requests`
--

INSERT INTO `waiter_requests` (`id`, `branch_id`, `table_id`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'completed', '2025-11-03 02:41:10', '2025-11-03 03:05:27');

-- --------------------------------------------------------

--
-- Table structure for table `xendit_payments`
--

CREATE TABLE `xendit_payments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `payment_date` datetime DEFAULT NULL,
  `amount` decimal(16,2) DEFAULT NULL,
  `payment_status` enum('pending','requested','declined','completed') NOT NULL DEFAULT 'pending',
  `payment_error_response` text DEFAULT NULL,
  `xendit_payment_id` varchar(191) DEFAULT NULL,
  `xendit_invoice_id` varchar(191) DEFAULT NULL,
  `xendit_external_id` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `areas`
--
ALTER TABLE `areas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `areas_branch_id_foreign` (`branch_id`);

--
-- Indexes for table `branches`
--
ALTER TABLE `branches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `branches_unique_hash_unique` (`unique_hash`),
  ADD KEY `branches_restaurant_id_foreign` (`restaurant_id`);

--
-- Indexes for table `branch_delivery_settings`
--
ALTER TABLE `branch_delivery_settings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `branch_delivery_settings_branch_id_foreign` (`branch_id`);

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
-- Indexes for table `cart_header_images`
--
ALTER TABLE `cart_header_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cart_header_images_cart_header_setting_id_foreign` (`cart_header_setting_id`);

--
-- Indexes for table `cart_header_settings`
--
ALTER TABLE `cart_header_settings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cart_header_settings_restaurant_id_foreign` (`restaurant_id`);

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cart_items_cart_session_id_foreign` (`cart_session_id`),
  ADD KEY `cart_items_branch_id_foreign` (`branch_id`),
  ADD KEY `cart_items_menu_item_id_foreign` (`menu_item_id`),
  ADD KEY `cart_items_menu_item_variation_id_foreign` (`menu_item_variation_id`),
  ADD KEY `cart_items_kiosk_id_foreign` (`kiosk_id`);

--
-- Indexes for table `cart_item_modifier_options`
--
ALTER TABLE `cart_item_modifier_options`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cart_item_modifier_options_cart_item_id_foreign` (`cart_item_id`),
  ADD KEY `cart_item_modifier_options_modifier_option_id_foreign` (`modifier_option_id`);

--
-- Indexes for table `cart_sessions`
--
ALTER TABLE `cart_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cart_sessions_branch_id_foreign` (`branch_id`),
  ADD KEY `cart_sessions_order_id_foreign` (`order_id`),
  ADD KEY `cart_sessions_order_type_id_foreign` (`order_type_id`),
  ADD KEY `cart_sessions_kiosk_id_foreign` (`kiosk_id`);

--
-- Indexes for table `cash_denominations`
--
ALTER TABLE `cash_denominations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cash_registers`
--
ALTER TABLE `cash_registers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cash_register_approvals`
--
ALTER TABLE `cash_register_approvals`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cash_register_counts`
--
ALTER TABLE `cash_register_counts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cash_register_global_settings`
--
ALTER TABLE `cash_register_global_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cash_register_sessions`
--
ALTER TABLE `cash_register_sessions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cash_register_settings`
--
ALTER TABLE `cash_register_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cash_register_settings_restaurant_id_unique` (`restaurant_id`);

--
-- Indexes for table `cash_register_transactions`
--
ALTER TABLE `cash_register_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cash_register_transactions_order_id_index` (`order_id`);

--
-- Indexes for table `contacts`
--
ALTER TABLE `contacts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contacts_language_setting_id_foreign` (`language_setting_id`);

--
-- Indexes for table `countries`
--
ALTER TABLE `countries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `countries_countries_code_index` (`countries_code`);

--
-- Indexes for table `currencies`
--
ALTER TABLE `currencies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `currencies_restaurant_id_foreign` (`restaurant_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `customers_email_unique` (`email`),
  ADD KEY `customers_restaurant_id_foreign` (`restaurant_id`);

--
-- Indexes for table `customer_addresses`
--
ALTER TABLE `customer_addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_addresses_customer_id_foreign` (`customer_id`);

--
-- Indexes for table `custom_menus`
--
ALTER TABLE `custom_menus`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `custom_menus_menu_slug_unique` (`menu_slug`);

--
-- Indexes for table `database_backups`
--
ALTER TABLE `database_backups`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `database_backup_settings`
--
ALTER TABLE `database_backup_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `delivery_executives`
--
ALTER TABLE `delivery_executives`
  ADD PRIMARY KEY (`id`),
  ADD KEY `delivery_executives_branch_id_foreign` (`branch_id`);

--
-- Indexes for table `delivery_fee_tiers`
--
ALTER TABLE `delivery_fee_tiers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `delivery_fee_tiers_branch_id_foreign` (`branch_id`);

--
-- Indexes for table `delivery_platforms`
--
ALTER TABLE `delivery_platforms`
  ADD PRIMARY KEY (`id`),
  ADD KEY `delivery_platforms_branch_id_foreign` (`branch_id`);

--
-- Indexes for table `denominations`
--
ALTER TABLE `denominations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `denominations_uuid_unique` (`uuid`),
  ADD UNIQUE KEY `unique_denomination_per_branch` (`value`,`type`,`branch_id`,`restaurant_id`),
  ADD KEY `denominations_branch_id_restaurant_id_index` (`branch_id`,`restaurant_id`),
  ADD KEY `denominations_type_is_active_index` (`type`,`is_active`),
  ADD KEY `denominations_value_type_index` (`value`,`type`),
  ADD KEY `denominations_restaurant_id_foreign` (`restaurant_id`);

--
-- Indexes for table `desktop_applications`
--
ALTER TABLE `desktop_applications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `email_settings`
--
ALTER TABLE `email_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `expenses_expense_category_id_foreign` (`expense_category_id`),
  ADD KEY `expenses_branch_id_foreign` (`branch_id`);

--
-- Indexes for table `expense_categories`
--
ALTER TABLE `expense_categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `expense_categories_branch_id_foreign` (`branch_id`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `file_storage`
--
ALTER TABLE `file_storage`
  ADD PRIMARY KEY (`id`),
  ADD KEY `file_storage_restaurant_id_foreign` (`restaurant_id`);

--
-- Indexes for table `file_storage_settings`
--
ALTER TABLE `file_storage_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `flags`
--
ALTER TABLE `flags`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `flutterwave_payments`
--
ALTER TABLE `flutterwave_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `flutterwave_payments_order_id_foreign` (`order_id`);

--
-- Indexes for table `front_details`
--
ALTER TABLE `front_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `front_details_language_setting_id_foreign` (`language_setting_id`);

--
-- Indexes for table `front_faq_settings`
--
ALTER TABLE `front_faq_settings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `front_faq_settings_language_setting_id_foreign` (`language_setting_id`);

--
-- Indexes for table `front_features`
--
ALTER TABLE `front_features`
  ADD PRIMARY KEY (`id`),
  ADD KEY `front_features_language_setting_id_foreign` (`language_setting_id`);

--
-- Indexes for table `front_review_settings`
--
ALTER TABLE `front_review_settings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `front_review_settings_language_setting_id_foreign` (`language_setting_id`);

--
-- Indexes for table `global_currencies`
--
ALTER TABLE `global_currencies`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `global_invoices`
--
ALTER TABLE `global_invoices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `global_invoices_restaurant_id_foreign` (`restaurant_id`),
  ADD KEY `global_invoices_currency_id_foreign` (`currency_id`),
  ADD KEY `global_invoices_package_id_foreign` (`package_id`),
  ADD KEY `global_invoices_global_subscription_id_foreign` (`global_subscription_id`),
  ADD KEY `global_invoices_offline_method_id_foreign` (`offline_method_id`);

--
-- Indexes for table `global_settings`
--
ALTER TABLE `global_settings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `global_settings_default_currency_id_foreign` (`default_currency_id`);

--
-- Indexes for table `global_subscriptions`
--
ALTER TABLE `global_subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `global_subscriptions_restaurant_id_foreign` (`restaurant_id`),
  ADD KEY `global_subscriptions_package_id_foreign` (`package_id`),
  ADD KEY `global_subscriptions_currency_id_foreign` (`currency_id`);

--
-- Indexes for table `inventory_global_settings`
--
ALTER TABLE `inventory_global_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `inventory_items_branch_id_foreign` (`branch_id`),
  ADD KEY `inventory_items_inventory_item_category_id_foreign` (`inventory_item_category_id`),
  ADD KEY `inventory_items_unit_id_foreign` (`unit_id`),
  ADD KEY `inventory_items_preferred_supplier_id_foreign` (`preferred_supplier_id`);

--
-- Indexes for table `inventory_item_categories`
--
ALTER TABLE `inventory_item_categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `inventory_item_categories_branch_id_foreign` (`branch_id`);

--
-- Indexes for table `inventory_movements`
--
ALTER TABLE `inventory_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `inventory_movements_branch_id_foreign` (`branch_id`),
  ADD KEY `inventory_movements_inventory_item_id_foreign` (`inventory_item_id`),
  ADD KEY `inventory_movements_added_by_foreign` (`added_by`),
  ADD KEY `inventory_movements_supplier_id_foreign` (`supplier_id`),
  ADD KEY `inventory_movements_transfer_branch_id_foreign` (`transfer_branch_id`);

--
-- Indexes for table `inventory_settings`
--
ALTER TABLE `inventory_settings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `inventory_settings_restaurant_id_foreign` (`restaurant_id`);

--
-- Indexes for table `inventory_stocks`
--
ALTER TABLE `inventory_stocks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `inventory_stocks_branch_id_foreign` (`branch_id`),
  ADD KEY `inventory_stocks_inventory_item_id_foreign` (`inventory_item_id`);

--
-- Indexes for table `item_categories`
--
ALTER TABLE `item_categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_categories_branch_id_foreign` (`branch_id`);

--
-- Indexes for table `item_modifiers`
--
ALTER TABLE `item_modifiers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_modifiers_menu_item_id_foreign` (`menu_item_id`),
  ADD KEY `item_modifiers_modifier_group_id_foreign` (`modifier_group_id`),
  ADD KEY `item_modifiers_menu_item_variation_id_foreign` (`menu_item_variation_id`);

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
-- Indexes for table `kiosks`
--
ALTER TABLE `kiosks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kiosks_code_unique` (`code`),
  ADD KEY `kiosks_branch_id_foreign` (`branch_id`);

--
-- Indexes for table `kiosk_ads`
--
ALTER TABLE `kiosk_ads`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `kiosk_global_settings`
--
ALTER TABLE `kiosk_global_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `kitchen_global_settings`
--
ALTER TABLE `kitchen_global_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `kots`
--
ALTER TABLE `kots`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kots_order_id_foreign` (`order_id`),
  ADD KEY `kots_branch_id_foreign` (`branch_id`),
  ADD KEY `kots_cancel_reason_id_foreign` (`cancel_reason_id`),
  ADD KEY `kots_order_type_id_foreign` (`order_type_id`),
  ADD KEY `kots_kitchen_place_id_foreign` (`kitchen_place_id`);

--
-- Indexes for table `kot_cancel_reasons`
--
ALTER TABLE `kot_cancel_reasons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kot_cancel_reasons_restaurant_id_foreign` (`restaurant_id`);

--
-- Indexes for table `kot_items`
--
ALTER TABLE `kot_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kot_items_kot_id_foreign` (`kot_id`),
  ADD KEY `kot_items_menu_item_id_foreign` (`menu_item_id`),
  ADD KEY `kot_items_menu_item_variation_id_foreign` (`menu_item_variation_id`);

--
-- Indexes for table `kot_item_modifier_options`
--
ALTER TABLE `kot_item_modifier_options`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kot_item_modifier_options_kot_item_id_foreign` (`kot_item_id`),
  ADD KEY `kot_item_modifier_options_modifier_option_id_foreign` (`modifier_option_id`);

--
-- Indexes for table `kot_places`
--
ALTER TABLE `kot_places`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kot_places_branch_id_foreign` (`branch_id`),
  ADD KEY `kot_places_printer_id_foreign` (`printer_id`);

--
-- Indexes for table `kot_settings`
--
ALTER TABLE `kot_settings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kot_settings_branch_id_foreign` (`branch_id`);

--
-- Indexes for table `language_settings`
--
ALTER TABLE `language_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ltm_translations`
--
ALTER TABLE `ltm_translations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `menus`
--
ALTER TABLE `menus`
  ADD PRIMARY KEY (`id`),
  ADD KEY `menus_branch_id_foreign` (`branch_id`);

--
-- Indexes for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_branch_available` (`branch_id`,`is_available`),
  ADD KEY `idx_menu_items_category` (`item_category_id`),
  ADD KEY `idx_menu_items_menu_id` (`menu_id`);

--
-- Indexes for table `menu_item_prices`
--
ALTER TABLE `menu_item_prices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `menu_item_prices_menu_item_id_foreign` (`menu_item_id`),
  ADD KEY `menu_item_prices_order_type_id_foreign` (`order_type_id`),
  ADD KEY `menu_item_prices_delivery_app_id_foreign` (`delivery_app_id`),
  ADD KEY `menu_item_prices_menu_item_variation_id_foreign` (`menu_item_variation_id`);

--
-- Indexes for table `menu_item_tax`
--
ALTER TABLE `menu_item_tax`
  ADD PRIMARY KEY (`id`),
  ADD KEY `menu_item_tax_menu_item_id_foreign` (`menu_item_id`),
  ADD KEY `menu_item_tax_tax_id_foreign` (`tax_id`);

--
-- Indexes for table `menu_item_translations`
--
ALTER TABLE `menu_item_translations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `menu_item_translations_menu_item_id_locale_unique` (`menu_item_id`,`locale`),
  ADD KEY `menu_item_translations_locale_index` (`locale`);

--
-- Indexes for table `menu_item_variations`
--
ALTER TABLE `menu_item_variations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `menu_item_variations_menu_item_id_foreign` (`menu_item_id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  ADD KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  ADD KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`);

--
-- Indexes for table `modifier_groups`
--
ALTER TABLE `modifier_groups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `modifier_groups_branch_id_foreign` (`branch_id`);

--
-- Indexes for table `modifier_group_translations`
--
ALTER TABLE `modifier_group_translations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `modifier_group_translations_modifier_group_id_locale_unique` (`modifier_group_id`,`locale`),
  ADD KEY `modifier_group_translations_locale_index` (`locale`);

--
-- Indexes for table `modifier_options`
--
ALTER TABLE `modifier_options`
  ADD PRIMARY KEY (`id`),
  ADD KEY `modifier_options_modifier_group_id_foreign` (`modifier_group_id`);

--
-- Indexes for table `modifier_option_prices`
--
ALTER TABLE `modifier_option_prices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `modifier_option_prices_modifier_group_id_foreign` (`modifier_group_id`),
  ADD KEY `modifier_option_prices_modifier_option_id_foreign` (`modifier_option_id`),
  ADD KEY `modifier_option_prices_order_type_id_foreign` (`order_type_id`),
  ADD KEY `modifier_option_prices_delivery_app_id_foreign` (`delivery_app_id`);

--
-- Indexes for table `modules`
--
ALTER TABLE `modules`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notification_settings`
--
ALTER TABLE `notification_settings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `notification_settings_restaurant_id_foreign` (`restaurant_id`);

--
-- Indexes for table `offline_payment_methods`
--
ALTER TABLE `offline_payment_methods`
  ADD PRIMARY KEY (`id`),
  ADD KEY `offline_payment_methods_restaurant_id_foreign` (`restaurant_id`);

--
-- Indexes for table `offline_plan_changes`
--
ALTER TABLE `offline_plan_changes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `offline_plan_changes_restaurant_id_foreign` (`restaurant_id`),
  ADD KEY `offline_plan_changes_package_id_foreign` (`package_id`),
  ADD KEY `offline_plan_changes_invoice_id_foreign` (`invoice_id`),
  ADD KEY `offline_plan_changes_offline_method_id_foreign` (`offline_method_id`);

--
-- Indexes for table `onboarding_steps`
--
ALTER TABLE `onboarding_steps`
  ADD PRIMARY KEY (`id`),
  ADD KEY `onboarding_steps_branch_id_foreign` (`branch_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `orders_uuid_unique` (`uuid`),
  ADD KEY `orders_customer_id_foreign` (`customer_id`),
  ADD KEY `orders_delivery_executive_id_foreign` (`delivery_executive_id`),
  ADD KEY `orders_cancel_reason_id_foreign` (`cancel_reason_id`),
  ADD KEY `idx_branch_date` (`branch_id`,`date_time`),
  ADD KEY `orders_order_type_id_foreign` (`order_type_id`),
  ADD KEY `orders_reservation_id_foreign` (`reservation_id`),
  ADD KEY `orders_delivery_app_id_foreign` (`delivery_app_id`),
  ADD KEY `orders_kiosk_id_foreign` (`kiosk_id`),
  ADD KEY `idx_orders_status` (`status`),
  ADD KEY `idx_orders_created_at` (`created_at`),
  ADD KEY `idx_orders_table_id` (`table_id`),
  ADD KEY `idx_orders_waiter_id` (`waiter_id`),
  ADD KEY `idx_orders_waiter_date` (`waiter_id`,`created_at`),
  ADD KEY `idx_orders_date_time` (`date_time`);

--
-- Indexes for table `order_charges`
--
ALTER TABLE `order_charges`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_charges_order_id_foreign` (`order_id`),
  ADD KEY `order_charges_charge_id_foreign` (`charge_id`);

--
-- Indexes for table `order_histories`
--
ALTER TABLE `order_histories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_histories_order_id_foreign` (`order_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_items_menu_item_variation_id_foreign` (`menu_item_variation_id`),
  ADD KEY `order_items_branch_id_foreign` (`branch_id`),
  ADD KEY `idx_order_items_order_id` (`order_id`),
  ADD KEY `idx_order_items_menu_item` (`menu_item_id`);

--
-- Indexes for table `order_item_modifier_options`
--
ALTER TABLE `order_item_modifier_options`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_item_modifier_options_order_item_id_foreign` (`order_item_id`),
  ADD KEY `order_item_modifier_options_modifier_option_id_foreign` (`modifier_option_id`);

--
-- Indexes for table `order_number_settings`
--
ALTER TABLE `order_number_settings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_number_settings_branch_id_foreign` (`branch_id`);

--
-- Indexes for table `order_places`
--
ALTER TABLE `order_places`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_places_branch_id_foreign` (`branch_id`),
  ADD KEY `order_places_printer_id_foreign` (`printer_id`);

--
-- Indexes for table `order_taxes`
--
ALTER TABLE `order_taxes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_taxes_order_id_foreign` (`order_id`),
  ADD KEY `order_taxes_tax_id_foreign` (`tax_id`);

--
-- Indexes for table `order_types`
--
ALTER TABLE `order_types`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_types_branch_id_foreign` (`branch_id`);

--
-- Indexes for table `otps`
--
ALTER TABLE `otps`
  ADD PRIMARY KEY (`id`),
  ADD KEY `otps_identifier_type_index` (`identifier`,`type`),
  ADD KEY `otps_token_index` (`token`);

--
-- Indexes for table `packages`
--
ALTER TABLE `packages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `packages_currency_id_foreign` (`currency_id`);

--
-- Indexes for table `package_modules`
--
ALTER TABLE `package_modules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `package_modules_package_id_foreign` (`package_id`),
  ADD KEY `package_modules_module_id_foreign` (`module_id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `payfast_payments`
--
ALTER TABLE `payfast_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payfast_payments_order_id_foreign` (`order_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payments_branch_id_foreign` (`branch_id`),
  ADD KEY `idx_payments_order_id` (`order_id`),
  ADD KEY `idx_payments_created_at` (`created_at`);

--
-- Indexes for table `payment_gateway_credentials`
--
ALTER TABLE `payment_gateway_credentials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payment_gateway_credentials_restaurant_id_foreign` (`restaurant_id`);

--
-- Indexes for table `paypal_payments`
--
ALTER TABLE `paypal_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `paypal_payments_order_id_foreign` (`order_id`);

--
-- Indexes for table `paystack_payments`
--
ALTER TABLE `paystack_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `paystack_payments_order_id_foreign` (`order_id`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`),
  ADD KEY `permissions_module_id_foreign` (`module_id`);

--
-- Indexes for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- Indexes for table `predefined_amounts`
--
ALTER TABLE `predefined_amounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `predefined_amounts_restaurant_id_foreign` (`restaurant_id`);

--
-- Indexes for table `printers`
--
ALTER TABLE `printers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `printers_restaurant_id_foreign` (`restaurant_id`),
  ADD KEY `printers_branch_id_foreign` (`branch_id`);

--
-- Indexes for table `print_jobs`
--
ALTER TABLE `print_jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `print_jobs_printer_id_foreign` (`printer_id`),
  ADD KEY `print_jobs_restaurant_id_foreign` (`restaurant_id`),
  ADD KEY `print_jobs_branch_id_foreign` (`branch_id`);

--
-- Indexes for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `purchase_orders_po_number_unique` (`po_number`),
  ADD KEY `purchase_orders_branch_id_foreign` (`branch_id`),
  ADD KEY `purchase_orders_supplier_id_foreign` (`supplier_id`),
  ADD KEY `purchase_orders_created_by_foreign` (`created_by`);

--
-- Indexes for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `purchase_order_items_purchase_order_id_foreign` (`purchase_order_id`),
  ADD KEY `purchase_order_items_inventory_item_id_foreign` (`inventory_item_id`);

--
-- Indexes for table `pusher_settings`
--
ALTER TABLE `pusher_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `razorpay_payments`
--
ALTER TABLE `razorpay_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `razorpay_payments_order_id_foreign` (`order_id`);

--
-- Indexes for table `receipt_settings`
--
ALTER TABLE `receipt_settings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `receipt_settings_restaurant_id_foreign` (`restaurant_id`);

--
-- Indexes for table `recipes`
--
ALTER TABLE `recipes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `recipes_menu_item_id_foreign` (`menu_item_id`),
  ADD KEY `recipes_inventory_item_id_foreign` (`inventory_item_id`),
  ADD KEY `recipes_unit_id_foreign` (`unit_id`),
  ADD KEY `recipes_menu_item_variation_id_foreign` (`menu_item_variation_id`),
  ADD KEY `recipes_modifier_option_id_foreign` (`modifier_option_id`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reservations_customer_id_foreign` (`customer_id`),
  ADD KEY `reservations_branch_id_foreign` (`branch_id`),
  ADD KEY `idx_reservations_table_id` (`table_id`),
  ADD KEY `idx_reservations_date` (`reservation_date_time`);

--
-- Indexes for table `reservation_settings`
--
ALTER TABLE `reservation_settings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reservation_settings_branch_id_foreign` (`branch_id`);

--
-- Indexes for table `restaurants`
--
ALTER TABLE `restaurants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `restaurant_settings_country_id_foreign` (`country_id`),
  ADD KEY `restaurant_settings_currency_id_foreign` (`currency_id`),
  ADD KEY `restaurants_package_id_foreign` (`package_id`);

--
-- Indexes for table `restaurant_charges`
--
ALTER TABLE `restaurant_charges`
  ADD PRIMARY KEY (`id`),
  ADD KEY `restaurant_charges_restaurant_id_foreign` (`restaurant_id`),
  ADD KEY `restaurant_charges_charge_name_index` (`charge_name`);

--
-- Indexes for table `restaurant_payments`
--
ALTER TABLE `restaurant_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `restaurant_payments_restaurant_id_foreign` (`restaurant_id`),
  ADD KEY `restaurant_payments_package_id_foreign` (`package_id`);

--
-- Indexes for table `restaurant_taxes`
--
ALTER TABLE `restaurant_taxes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `restaurant_taxes_restaurant_id_foreign` (`restaurant_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`),
  ADD KEY `roles_restaurant_id_foreign` (`restaurant_id`);

--
-- Indexes for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD PRIMARY KEY (`permission_id`,`role_id`),
  ADD KEY `role_has_permissions_role_id_foreign` (`role_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `split_orders`
--
ALTER TABLE `split_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `split_orders_order_id_foreign` (`order_id`);

--
-- Indexes for table `split_order_items`
--
ALTER TABLE `split_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `split_order_items_split_order_id_foreign` (`split_order_id`),
  ADD KEY `split_order_items_order_item_id_foreign` (`order_item_id`);

--
-- Indexes for table `stripe_payments`
--
ALTER TABLE `stripe_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `stripe_payments_order_id_foreign` (`order_id`);

--
-- Indexes for table `superadmin_payment_gateways`
--
ALTER TABLE `superadmin_payment_gateways`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `suppliers_restaurant_id_foreign` (`restaurant_id`);

--
-- Indexes for table `tables`
--
ALTER TABLE `tables`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tables_branch_id_foreign` (`branch_id`),
  ADD KEY `idx_tables_available_status` (`available_status`),
  ADD KEY `idx_tables_area` (`area_id`);

--
-- Indexes for table `table_sessions`
--
ALTER TABLE `table_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `table_sessions_session_token_unique` (`session_token`),
  ADD KEY `table_sessions_branch_id_foreign` (`branch_id`),
  ADD KEY `table_sessions_locked_by_user_id_foreign` (`locked_by_user_id`),
  ADD KEY `table_sessions_table_id_locked_by_user_id_index` (`table_id`,`locked_by_user_id`),
  ADD KEY `table_sessions_last_activity_at_index` (`last_activity_at`);

--
-- Indexes for table `taxes`
--
ALTER TABLE `taxes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `taxes_restaurant_id_foreign` (`restaurant_id`);

--
-- Indexes for table `units`
--
ALTER TABLE `units`
  ADD PRIMARY KEY (`id`),
  ADD KEY `units_branch_id_foreign` (`branch_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD KEY `users_restaurant_id_foreign` (`restaurant_id`),
  ADD KEY `users_stripe_id_index` (`stripe_id`),
  ADD KEY `idx_branch_email` (`branch_id`,`email`),
  ADD KEY `users_kitchen_id_foreign` (`kitchen_id`);

--
-- Indexes for table `waiter_requests`
--
ALTER TABLE `waiter_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `waiter_requests_branch_id_foreign` (`branch_id`),
  ADD KEY `waiter_requests_table_id_foreign` (`table_id`);

--
-- Indexes for table `xendit_payments`
--
ALTER TABLE `xendit_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `xendit_payments_order_id_foreign` (`order_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `areas`
--
ALTER TABLE `areas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `branches`
--
ALTER TABLE `branches`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `branch_delivery_settings`
--
ALTER TABLE `branch_delivery_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cart_header_images`
--
ALTER TABLE `cart_header_images`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cart_header_settings`
--
ALTER TABLE `cart_header_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cart_item_modifier_options`
--
ALTER TABLE `cart_item_modifier_options`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cart_sessions`
--
ALTER TABLE `cart_sessions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cash_denominations`
--
ALTER TABLE `cash_denominations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cash_registers`
--
ALTER TABLE `cash_registers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `cash_register_approvals`
--
ALTER TABLE `cash_register_approvals`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cash_register_counts`
--
ALTER TABLE `cash_register_counts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cash_register_global_settings`
--
ALTER TABLE `cash_register_global_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `cash_register_sessions`
--
ALTER TABLE `cash_register_sessions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `cash_register_settings`
--
ALTER TABLE `cash_register_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `cash_register_transactions`
--
ALTER TABLE `cash_register_transactions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `contacts`
--
ALTER TABLE `contacts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `countries`
--
ALTER TABLE `countries`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=250;

--
-- AUTO_INCREMENT for table `currencies`
--
ALTER TABLE `currencies`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `customer_addresses`
--
ALTER TABLE `customer_addresses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `custom_menus`
--
ALTER TABLE `custom_menus`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `database_backups`
--
ALTER TABLE `database_backups`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `database_backup_settings`
--
ALTER TABLE `database_backup_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `delivery_executives`
--
ALTER TABLE `delivery_executives`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `delivery_fee_tiers`
--
ALTER TABLE `delivery_fee_tiers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `delivery_platforms`
--
ALTER TABLE `delivery_platforms`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `denominations`
--
ALTER TABLE `denominations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `desktop_applications`
--
ALTER TABLE `desktop_applications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `email_settings`
--
ALTER TABLE `email_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `expense_categories`
--
ALTER TABLE `expense_categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `file_storage`
--
ALTER TABLE `file_storage`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `file_storage_settings`
--
ALTER TABLE `file_storage_settings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `flags`
--
ALTER TABLE `flags`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=267;

--
-- AUTO_INCREMENT for table `flutterwave_payments`
--
ALTER TABLE `flutterwave_payments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `front_details`
--
ALTER TABLE `front_details`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `front_faq_settings`
--
ALTER TABLE `front_faq_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `front_features`
--
ALTER TABLE `front_features`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `front_review_settings`
--
ALTER TABLE `front_review_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `global_currencies`
--
ALTER TABLE `global_currencies`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `global_invoices`
--
ALTER TABLE `global_invoices`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `global_settings`
--
ALTER TABLE `global_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `global_subscriptions`
--
ALTER TABLE `global_subscriptions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `inventory_global_settings`
--
ALTER TABLE `inventory_global_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `inventory_items`
--
ALTER TABLE `inventory_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_item_categories`
--
ALTER TABLE `inventory_item_categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `inventory_movements`
--
ALTER TABLE `inventory_movements`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_settings`
--
ALTER TABLE `inventory_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `inventory_stocks`
--
ALTER TABLE `inventory_stocks`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `item_categories`
--
ALTER TABLE `item_categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `item_modifiers`
--
ALTER TABLE `item_modifiers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kiosks`
--
ALTER TABLE `kiosks`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kiosk_ads`
--
ALTER TABLE `kiosk_ads`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kiosk_global_settings`
--
ALTER TABLE `kiosk_global_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `kitchen_global_settings`
--
ALTER TABLE `kitchen_global_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `kots`
--
ALTER TABLE `kots`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `kot_cancel_reasons`
--
ALTER TABLE `kot_cancel_reasons`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `kot_items`
--
ALTER TABLE `kot_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `kot_item_modifier_options`
--
ALTER TABLE `kot_item_modifier_options`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kot_places`
--
ALTER TABLE `kot_places`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `kot_settings`
--
ALTER TABLE `kot_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `language_settings`
--
ALTER TABLE `language_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `ltm_translations`
--
ALTER TABLE `ltm_translations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `menus`
--
ALTER TABLE `menus`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `menu_items`
--
ALTER TABLE `menu_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `menu_item_prices`
--
ALTER TABLE `menu_item_prices`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `menu_item_tax`
--
ALTER TABLE `menu_item_tax`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `menu_item_translations`
--
ALTER TABLE `menu_item_translations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `menu_item_variations`
--
ALTER TABLE `menu_item_variations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=265;

--
-- AUTO_INCREMENT for table `modifier_groups`
--
ALTER TABLE `modifier_groups`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `modifier_group_translations`
--
ALTER TABLE `modifier_group_translations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `modifier_options`
--
ALTER TABLE `modifier_options`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `modifier_option_prices`
--
ALTER TABLE `modifier_option_prices`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `modules`
--
ALTER TABLE `modules`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `notification_settings`
--
ALTER TABLE `notification_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `offline_payment_methods`
--
ALTER TABLE `offline_payment_methods`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `offline_plan_changes`
--
ALTER TABLE `offline_plan_changes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `onboarding_steps`
--
ALTER TABLE `onboarding_steps`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `order_charges`
--
ALTER TABLE `order_charges`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_histories`
--
ALTER TABLE `order_histories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `order_item_modifier_options`
--
ALTER TABLE `order_item_modifier_options`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_number_settings`
--
ALTER TABLE `order_number_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_places`
--
ALTER TABLE `order_places`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `order_taxes`
--
ALTER TABLE `order_taxes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `order_types`
--
ALTER TABLE `order_types`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `otps`
--
ALTER TABLE `otps`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `packages`
--
ALTER TABLE `packages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `package_modules`
--
ALTER TABLE `package_modules`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=86;

--
-- AUTO_INCREMENT for table `payfast_payments`
--
ALTER TABLE `payfast_payments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `payment_gateway_credentials`
--
ALTER TABLE `payment_gateway_credentials`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `paypal_payments`
--
ALTER TABLE `paypal_payments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `paystack_payments`
--
ALTER TABLE `paystack_payments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=92;

--
-- AUTO_INCREMENT for table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `predefined_amounts`
--
ALTER TABLE `predefined_amounts`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `printers`
--
ALTER TABLE `printers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `print_jobs`
--
ALTER TABLE `print_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pusher_settings`
--
ALTER TABLE `pusher_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `razorpay_payments`
--
ALTER TABLE `razorpay_payments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `receipt_settings`
--
ALTER TABLE `receipt_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `recipes`
--
ALTER TABLE `recipes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `reservation_settings`
--
ALTER TABLE `reservation_settings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- AUTO_INCREMENT for table `restaurants`
--
ALTER TABLE `restaurants`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `restaurant_charges`
--
ALTER TABLE `restaurant_charges`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `restaurant_payments`
--
ALTER TABLE `restaurant_payments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `restaurant_taxes`
--
ALTER TABLE `restaurant_taxes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `split_orders`
--
ALTER TABLE `split_orders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `split_order_items`
--
ALTER TABLE `split_order_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stripe_payments`
--
ALTER TABLE `stripe_payments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `superadmin_payment_gateways`
--
ALTER TABLE `superadmin_payment_gateways`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tables`
--
ALTER TABLE `tables`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `table_sessions`
--
ALTER TABLE `table_sessions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `taxes`
--
ALTER TABLE `taxes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `units`
--
ALTER TABLE `units`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `waiter_requests`
--
ALTER TABLE `waiter_requests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `xendit_payments`
--
ALTER TABLE `xendit_payments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `areas`
--
ALTER TABLE `areas`
  ADD CONSTRAINT `areas_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `branches`
--
ALTER TABLE `branches`
  ADD CONSTRAINT `branches_restaurant_id_foreign` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `branch_delivery_settings`
--
ALTER TABLE `branch_delivery_settings`
  ADD CONSTRAINT `branch_delivery_settings_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `cart_header_images`
--
ALTER TABLE `cart_header_images`
  ADD CONSTRAINT `cart_header_images_cart_header_setting_id_foreign` FOREIGN KEY (`cart_header_setting_id`) REFERENCES `cart_header_settings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cart_header_settings`
--
ALTER TABLE `cart_header_settings`
  ADD CONSTRAINT `cart_header_settings_restaurant_id_foreign` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_items_cart_session_id_foreign` FOREIGN KEY (`cart_session_id`) REFERENCES `cart_sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_items_kiosk_id_foreign` FOREIGN KEY (`kiosk_id`) REFERENCES `kiosks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_items_menu_item_id_foreign` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_items_menu_item_variation_id_foreign` FOREIGN KEY (`menu_item_variation_id`) REFERENCES `menu_item_variations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cart_item_modifier_options`
--
ALTER TABLE `cart_item_modifier_options`
  ADD CONSTRAINT `cart_item_modifier_options_cart_item_id_foreign` FOREIGN KEY (`cart_item_id`) REFERENCES `cart_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_item_modifier_options_modifier_option_id_foreign` FOREIGN KEY (`modifier_option_id`) REFERENCES `modifier_options` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cart_sessions`
--
ALTER TABLE `cart_sessions`
  ADD CONSTRAINT `cart_sessions_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_sessions_kiosk_id_foreign` FOREIGN KEY (`kiosk_id`) REFERENCES `kiosks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_sessions_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_sessions_order_type_id_foreign` FOREIGN KEY (`order_type_id`) REFERENCES `order_types` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cash_register_settings`
--
ALTER TABLE `cash_register_settings`
  ADD CONSTRAINT `cash_register_settings_restaurant_id_foreign` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `contacts`
--
ALTER TABLE `contacts`
  ADD CONSTRAINT `contacts_language_setting_id_foreign` FOREIGN KEY (`language_setting_id`) REFERENCES `language_settings` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `currencies`
--
ALTER TABLE `currencies`
  ADD CONSTRAINT `currencies_restaurant_id_foreign` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `customers`
--
ALTER TABLE `customers`
  ADD CONSTRAINT `customers_restaurant_id_foreign` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `customer_addresses`
--
ALTER TABLE `customer_addresses`
  ADD CONSTRAINT `customer_addresses_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `delivery_executives`
--
ALTER TABLE `delivery_executives`
  ADD CONSTRAINT `delivery_executives_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `delivery_fee_tiers`
--
ALTER TABLE `delivery_fee_tiers`
  ADD CONSTRAINT `delivery_fee_tiers_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `delivery_platforms`
--
ALTER TABLE `delivery_platforms`
  ADD CONSTRAINT `delivery_platforms_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `denominations`
--
ALTER TABLE `denominations`
  ADD CONSTRAINT `denominations_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `denominations_restaurant_id_foreign` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `expenses_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `expenses_expense_category_id_foreign` FOREIGN KEY (`expense_category_id`) REFERENCES `expense_categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `expense_categories`
--
ALTER TABLE `expense_categories`
  ADD CONSTRAINT `expense_categories_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `file_storage`
--
ALTER TABLE `file_storage`
  ADD CONSTRAINT `file_storage_restaurant_id_foreign` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `flutterwave_payments`
--
ALTER TABLE `flutterwave_payments`
  ADD CONSTRAINT `flutterwave_payments_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `front_details`
--
ALTER TABLE `front_details`
  ADD CONSTRAINT `front_details_language_setting_id_foreign` FOREIGN KEY (`language_setting_id`) REFERENCES `language_settings` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `front_faq_settings`
--
ALTER TABLE `front_faq_settings`
  ADD CONSTRAINT `front_faq_settings_language_setting_id_foreign` FOREIGN KEY (`language_setting_id`) REFERENCES `language_settings` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `front_features`
--
ALTER TABLE `front_features`
  ADD CONSTRAINT `front_features_language_setting_id_foreign` FOREIGN KEY (`language_setting_id`) REFERENCES `language_settings` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `front_review_settings`
--
ALTER TABLE `front_review_settings`
  ADD CONSTRAINT `front_review_settings_language_setting_id_foreign` FOREIGN KEY (`language_setting_id`) REFERENCES `language_settings` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `global_invoices`
--
ALTER TABLE `global_invoices`
  ADD CONSTRAINT `global_invoices_currency_id_foreign` FOREIGN KEY (`currency_id`) REFERENCES `global_currencies` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `global_invoices_global_subscription_id_foreign` FOREIGN KEY (`global_subscription_id`) REFERENCES `global_subscriptions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `global_invoices_offline_method_id_foreign` FOREIGN KEY (`offline_method_id`) REFERENCES `offline_payment_methods` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `global_invoices_package_id_foreign` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `global_invoices_restaurant_id_foreign` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `global_settings`
--
ALTER TABLE `global_settings`
  ADD CONSTRAINT `global_settings_default_currency_id_foreign` FOREIGN KEY (`default_currency_id`) REFERENCES `global_currencies` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `global_subscriptions`
--
ALTER TABLE `global_subscriptions`
  ADD CONSTRAINT `global_subscriptions_currency_id_foreign` FOREIGN KEY (`currency_id`) REFERENCES `global_currencies` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `global_subscriptions_package_id_foreign` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `global_subscriptions_restaurant_id_foreign` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD CONSTRAINT `inventory_items_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `inventory_items_inventory_item_category_id_foreign` FOREIGN KEY (`inventory_item_category_id`) REFERENCES `inventory_item_categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `inventory_items_preferred_supplier_id_foreign` FOREIGN KEY (`preferred_supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `inventory_items_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `inventory_item_categories`
--
ALTER TABLE `inventory_item_categories`
  ADD CONSTRAINT `inventory_item_categories_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `inventory_movements`
--
ALTER TABLE `inventory_movements`
  ADD CONSTRAINT `inventory_movements_added_by_foreign` FOREIGN KEY (`added_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `inventory_movements_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `inventory_movements_inventory_item_id_foreign` FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `inventory_movements_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `inventory_movements_transfer_branch_id_foreign` FOREIGN KEY (`transfer_branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `inventory_settings`
--
ALTER TABLE `inventory_settings`
  ADD CONSTRAINT `inventory_settings_restaurant_id_foreign` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory_stocks`
--
ALTER TABLE `inventory_stocks`
  ADD CONSTRAINT `inventory_stocks_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `inventory_stocks_inventory_item_id_foreign` FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `item_categories`
--
ALTER TABLE `item_categories`
  ADD CONSTRAINT `item_categories_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `item_modifiers`
--
ALTER TABLE `item_modifiers`
  ADD CONSTRAINT `item_modifiers_menu_item_id_foreign` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `item_modifiers_menu_item_variation_id_foreign` FOREIGN KEY (`menu_item_variation_id`) REFERENCES `menu_item_variations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `item_modifiers_modifier_group_id_foreign` FOREIGN KEY (`modifier_group_id`) REFERENCES `modifier_groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `kiosks`
--
ALTER TABLE `kiosks`
  ADD CONSTRAINT `kiosks_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `kots`
--
ALTER TABLE `kots`
  ADD CONSTRAINT `kots_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `kots_cancel_reason_id_foreign` FOREIGN KEY (`cancel_reason_id`) REFERENCES `kot_cancel_reasons` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `kots_kitchen_place_id_foreign` FOREIGN KEY (`kitchen_place_id`) REFERENCES `kot_places` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `kots_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `kots_order_type_id_foreign` FOREIGN KEY (`order_type_id`) REFERENCES `order_types` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `kot_cancel_reasons`
--
ALTER TABLE `kot_cancel_reasons`
  ADD CONSTRAINT `kot_cancel_reasons_restaurant_id_foreign` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `kot_items`
--
ALTER TABLE `kot_items`
  ADD CONSTRAINT `kot_items_kot_id_foreign` FOREIGN KEY (`kot_id`) REFERENCES `kots` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `kot_items_menu_item_id_foreign` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `kot_items_menu_item_variation_id_foreign` FOREIGN KEY (`menu_item_variation_id`) REFERENCES `menu_item_variations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `kot_item_modifier_options`
--
ALTER TABLE `kot_item_modifier_options`
  ADD CONSTRAINT `kot_item_modifier_options_kot_item_id_foreign` FOREIGN KEY (`kot_item_id`) REFERENCES `kot_items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `kot_item_modifier_options_modifier_option_id_foreign` FOREIGN KEY (`modifier_option_id`) REFERENCES `modifier_options` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `kot_places`
--
ALTER TABLE `kot_places`
  ADD CONSTRAINT `kot_places_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `kot_places_printer_id_foreign` FOREIGN KEY (`printer_id`) REFERENCES `printers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `kot_settings`
--
ALTER TABLE `kot_settings`
  ADD CONSTRAINT `kot_settings_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `menus`
--
ALTER TABLE `menus`
  ADD CONSTRAINT `menus_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD CONSTRAINT `menu_items_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `menu_items_item_category_id_foreign` FOREIGN KEY (`item_category_id`) REFERENCES `item_categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `menu_items_menu_id_foreign` FOREIGN KEY (`menu_id`) REFERENCES `menus` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `menu_item_prices`
--
ALTER TABLE `menu_item_prices`
  ADD CONSTRAINT `menu_item_prices_delivery_app_id_foreign` FOREIGN KEY (`delivery_app_id`) REFERENCES `delivery_platforms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `menu_item_prices_menu_item_id_foreign` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `menu_item_prices_menu_item_variation_id_foreign` FOREIGN KEY (`menu_item_variation_id`) REFERENCES `menu_item_variations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `menu_item_prices_order_type_id_foreign` FOREIGN KEY (`order_type_id`) REFERENCES `order_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `menu_item_tax`
--
ALTER TABLE `menu_item_tax`
  ADD CONSTRAINT `menu_item_tax_menu_item_id_foreign` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `menu_item_tax_tax_id_foreign` FOREIGN KEY (`tax_id`) REFERENCES `taxes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `menu_item_translations`
--
ALTER TABLE `menu_item_translations`
  ADD CONSTRAINT `menu_item_translations_menu_item_id_foreign` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `menu_item_variations`
--
ALTER TABLE `menu_item_variations`
  ADD CONSTRAINT `menu_item_variations_menu_item_id_foreign` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `modifier_groups`
--
ALTER TABLE `modifier_groups`
  ADD CONSTRAINT `modifier_groups_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `modifier_group_translations`
--
ALTER TABLE `modifier_group_translations`
  ADD CONSTRAINT `modifier_group_translations_modifier_group_id_foreign` FOREIGN KEY (`modifier_group_id`) REFERENCES `modifier_groups` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `modifier_options`
--
ALTER TABLE `modifier_options`
  ADD CONSTRAINT `modifier_options_modifier_group_id_foreign` FOREIGN KEY (`modifier_group_id`) REFERENCES `modifier_groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `modifier_option_prices`
--
ALTER TABLE `modifier_option_prices`
  ADD CONSTRAINT `modifier_option_prices_delivery_app_id_foreign` FOREIGN KEY (`delivery_app_id`) REFERENCES `delivery_platforms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `modifier_option_prices_modifier_group_id_foreign` FOREIGN KEY (`modifier_group_id`) REFERENCES `modifier_groups` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `modifier_option_prices_modifier_option_id_foreign` FOREIGN KEY (`modifier_option_id`) REFERENCES `modifier_options` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `modifier_option_prices_order_type_id_foreign` FOREIGN KEY (`order_type_id`) REFERENCES `order_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `notification_settings`
--
ALTER TABLE `notification_settings`
  ADD CONSTRAINT `notification_settings_restaurant_id_foreign` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `offline_payment_methods`
--
ALTER TABLE `offline_payment_methods`
  ADD CONSTRAINT `offline_payment_methods_restaurant_id_foreign` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `offline_plan_changes`
--
ALTER TABLE `offline_plan_changes`
  ADD CONSTRAINT `offline_plan_changes_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `global_invoices` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `offline_plan_changes_offline_method_id_foreign` FOREIGN KEY (`offline_method_id`) REFERENCES `offline_payment_methods` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `offline_plan_changes_package_id_foreign` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `offline_plan_changes_restaurant_id_foreign` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `onboarding_steps`
--
ALTER TABLE `onboarding_steps`
  ADD CONSTRAINT `onboarding_steps_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `orders_cancel_reason_id_foreign` FOREIGN KEY (`cancel_reason_id`) REFERENCES `kot_cancel_reasons` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `orders_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `orders_delivery_app_id_foreign` FOREIGN KEY (`delivery_app_id`) REFERENCES `delivery_platforms` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_delivery_executive_id_foreign` FOREIGN KEY (`delivery_executive_id`) REFERENCES `delivery_executives` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_kiosk_id_foreign` FOREIGN KEY (`kiosk_id`) REFERENCES `kiosks` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_order_type_id_foreign` FOREIGN KEY (`order_type_id`) REFERENCES `order_types` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_reservation_id_foreign` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_table_id_foreign` FOREIGN KEY (`table_id`) REFERENCES `tables` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `orders_waiter_id_foreign` FOREIGN KEY (`waiter_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_charges`
--
ALTER TABLE `order_charges`
  ADD CONSTRAINT `order_charges_charge_id_foreign` FOREIGN KEY (`charge_id`) REFERENCES `restaurant_charges` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_charges_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_histories`
--
ALTER TABLE `order_histories`
  ADD CONSTRAINT `order_histories_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `order_items_menu_item_id_foreign` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_menu_item_variation_id_foreign` FOREIGN KEY (`menu_item_variation_id`) REFERENCES `menu_item_variations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_item_modifier_options`
--
ALTER TABLE `order_item_modifier_options`
  ADD CONSTRAINT `order_item_modifier_options_modifier_option_id_foreign` FOREIGN KEY (`modifier_option_id`) REFERENCES `modifier_options` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `order_item_modifier_options_order_item_id_foreign` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `order_number_settings`
--
ALTER TABLE `order_number_settings`
  ADD CONSTRAINT `order_number_settings_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `order_places`
--
ALTER TABLE `order_places`
  ADD CONSTRAINT `order_places_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_places_printer_id_foreign` FOREIGN KEY (`printer_id`) REFERENCES `printers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_taxes`
--
ALTER TABLE `order_taxes`
  ADD CONSTRAINT `order_taxes_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_taxes_tax_id_foreign` FOREIGN KEY (`tax_id`) REFERENCES `taxes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_types`
--
ALTER TABLE `order_types`
  ADD CONSTRAINT `order_types_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `packages`
--
ALTER TABLE `packages`
  ADD CONSTRAINT `packages_currency_id_foreign` FOREIGN KEY (`currency_id`) REFERENCES `global_currencies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `package_modules`
--
ALTER TABLE `package_modules`
  ADD CONSTRAINT `package_modules_module_id_foreign` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `package_modules_package_id_foreign` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `payfast_payments`
--
ALTER TABLE `payfast_payments`
  ADD CONSTRAINT `payfast_payments_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `payments_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payment_gateway_credentials`
--
ALTER TABLE `payment_gateway_credentials`
  ADD CONSTRAINT `payment_gateway_credentials_restaurant_id_foreign` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `paypal_payments`
--
ALTER TABLE `paypal_payments`
  ADD CONSTRAINT `paypal_payments_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `paystack_payments`
--
ALTER TABLE `paystack_payments`
  ADD CONSTRAINT `paystack_payments_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `permissions`
--
ALTER TABLE `permissions`
  ADD CONSTRAINT `permissions_module_id_foreign` FOREIGN KEY (`module_id`) REFERENCES `modules` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `predefined_amounts`
--
ALTER TABLE `predefined_amounts`
  ADD CONSTRAINT `predefined_amounts_restaurant_id_foreign` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `printers`
--
ALTER TABLE `printers`
  ADD CONSTRAINT `printers_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `printers_restaurant_id_foreign` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `print_jobs`
--
ALTER TABLE `print_jobs`
  ADD CONSTRAINT `print_jobs_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `print_jobs_printer_id_foreign` FOREIGN KEY (`printer_id`) REFERENCES `printers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `print_jobs_restaurant_id_foreign` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD CONSTRAINT `purchase_orders_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_orders_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `purchase_orders_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`);

--
-- Constraints for table `purchase_order_items`
--
ALTER TABLE `purchase_order_items`
  ADD CONSTRAINT `purchase_order_items_inventory_item_id_foreign` FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_items` (`id`),
  ADD CONSTRAINT `purchase_order_items_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `razorpay_payments`
--
ALTER TABLE `razorpay_payments`
  ADD CONSTRAINT `razorpay_payments_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `receipt_settings`
--
ALTER TABLE `receipt_settings`
  ADD CONSTRAINT `receipt_settings_restaurant_id_foreign` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `recipes`
--
ALTER TABLE `recipes`
  ADD CONSTRAINT `recipes_inventory_item_id_foreign` FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `recipes_menu_item_id_foreign` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `recipes_menu_item_variation_id_foreign` FOREIGN KEY (`menu_item_variation_id`) REFERENCES `menu_item_variations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `recipes_modifier_option_id_foreign` FOREIGN KEY (`modifier_option_id`) REFERENCES `modifier_options` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `recipes_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `reservations_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `reservations_table_id_foreign` FOREIGN KEY (`table_id`) REFERENCES `tables` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `reservation_settings`
--
ALTER TABLE `reservation_settings`
  ADD CONSTRAINT `reservation_settings_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `restaurants`
--
ALTER TABLE `restaurants`
  ADD CONSTRAINT `restaurant_settings_country_id_foreign` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `restaurant_settings_currency_id_foreign` FOREIGN KEY (`currency_id`) REFERENCES `currencies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `restaurants_package_id_foreign` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `restaurant_charges`
--
ALTER TABLE `restaurant_charges`
  ADD CONSTRAINT `restaurant_charges_restaurant_id_foreign` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `restaurant_payments`
--
ALTER TABLE `restaurant_payments`
  ADD CONSTRAINT `restaurant_payments_package_id_foreign` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `restaurant_payments_restaurant_id_foreign` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `restaurant_taxes`
--
ALTER TABLE `restaurant_taxes`
  ADD CONSTRAINT `restaurant_taxes_restaurant_id_foreign` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `roles`
--
ALTER TABLE `roles`
  ADD CONSTRAINT `roles_restaurant_id_foreign` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `split_orders`
--
ALTER TABLE `split_orders`
  ADD CONSTRAINT `split_orders_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`);

--
-- Constraints for table `split_order_items`
--
ALTER TABLE `split_order_items`
  ADD CONSTRAINT `split_order_items_order_item_id_foreign` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `split_order_items_split_order_id_foreign` FOREIGN KEY (`split_order_id`) REFERENCES `split_orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stripe_payments`
--
ALTER TABLE `stripe_payments`
  ADD CONSTRAINT `stripe_payments_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD CONSTRAINT `suppliers_restaurant_id_foreign` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tables`
--
ALTER TABLE `tables`
  ADD CONSTRAINT `tables_area_id_foreign` FOREIGN KEY (`area_id`) REFERENCES `areas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tables_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `table_sessions`
--
ALTER TABLE `table_sessions`
  ADD CONSTRAINT `table_sessions_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `table_sessions_locked_by_user_id_foreign` FOREIGN KEY (`locked_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `table_sessions_table_id_foreign` FOREIGN KEY (`table_id`) REFERENCES `tables` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `taxes`
--
ALTER TABLE `taxes`
  ADD CONSTRAINT `taxes_restaurant_id_foreign` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `units`
--
ALTER TABLE `units`
  ADD CONSTRAINT `units_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `users_kitchen_id_foreign` FOREIGN KEY (`kitchen_id`) REFERENCES `kot_places` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `users_restaurant_id_foreign` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `waiter_requests`
--
ALTER TABLE `waiter_requests`
  ADD CONSTRAINT `waiter_requests_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `waiter_requests_table_id_foreign` FOREIGN KEY (`table_id`) REFERENCES `tables` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `xendit_payments`
--
ALTER TABLE `xendit_payments`
  ADD CONSTRAINT `xendit_payments_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
