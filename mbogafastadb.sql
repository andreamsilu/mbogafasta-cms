-- phpMyAdmin SQL Dump
-- version 5.2.1deb3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Apr 12, 2025 at 06:13 AM
-- Server version: 8.0.41-0ubuntu0.24.04.1
-- PHP Version: 8.3.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `mbogafastadb`
--

-- --------------------------------------------------------

--
-- Table structure for table `banners`
--

CREATE TABLE `banners` (
  `id` int NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `banners`
--

INSERT INTO `banners` (`id`, `image_path`, `title`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'uploads/banner.jpg', 'Special Offer', 'Get 50% off on your first order.', 1, '2025-01-07 16:45:16', '2025-01-07 17:29:45'),
(2, 'uploads/banner.jpg', 'Free Delivery', 'Free delivery on orders over 2000Tsh.', 1, '2025-01-07 16:45:16', '2025-01-07 17:29:59'),
(3, 'uploads/banner.jpg', 'New Arrivals', 'Check out our latest menu additions.', 1, '2025-01-07 16:45:16', '2025-01-07 17:30:11');

-- --------------------------------------------------------

--
-- Table structure for table `carts`
--

CREATE TABLE `carts` (
  `cart_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `status` enum('active','inactive','completed') DEFAULT 'active',
  `total_price` decimal(10,2) DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `carts`
--

INSERT INTO `carts` (`cart_id`, `user_id`, `status`, `total_price`, `created_at`, `updated_at`) VALUES
(1, 6, 'active', 0.00, '2025-01-28 09:39:27', '2025-01-28 09:39:27'),
(2, 18, 'active', 0.00, '2025-01-28 09:54:13', '2025-01-28 09:54:13');

-- --------------------------------------------------------

--
-- Table structure for table `cart_items`
--

CREATE TABLE `cart_items` (
  `cart_item_id` int UNSIGNED NOT NULL,
  `cart_id` int UNSIGNED NOT NULL,
  `product_id` int UNSIGNED NOT NULL,
  `quantity` int DEFAULT '1',
  `status` enum('0','1') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `cart_items`
--

INSERT INTO `cart_items` (`cart_item_id`, `cart_id`, `product_id`, `quantity`, `status`, `created_at`, `updated_at`) VALUES
(14, 1, 1, 2, '0', '2025-01-28 14:23:16', '2025-01-28 14:23:17'),
(15, 1, 3, 2, '0', '2025-01-28 14:23:22', '2025-01-28 14:23:27'),
(16, 1, 4, 1, '0', '2025-01-28 14:23:30', '2025-01-28 14:23:30');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int UNSIGNED NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `category_name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Italian', 'Italian cuisine featuring pasta, pizza, and more.', '2024-12-16 12:25:53', '2024-12-16 12:25:53'),
(2, 'Japanese', 'Traditional and modern Japanese dishes including sushi and ramen.', '2024-12-16 12:25:53', '2024-12-16 12:25:53'),
(3, 'Fast Food', 'Quick and convenient meals such as burgers, fries, and shakes.', '2024-12-16 12:25:53', '2024-12-16 12:25:53'),
(4, 'Vegan', 'Plant-based meals with healthy and sustainable ingredients.', '2024-12-16 12:25:53', '2024-12-16 12:25:53'),
(5, 'Fine Dining', 'Upscale restaurants offering luxurious and unique dining experiences.', '2024-12-16 12:25:53', '2024-12-16 12:25:53'),
(8, 'Food premier league', 'water food', '2025-02-13 08:20:44', '2025-02-13 08:20:44');

-- --------------------------------------------------------

--
-- Table structure for table `favorites`
--

CREATE TABLE `favorites` (
  `favorite_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `product_id` int UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `favorites`
--

INSERT INTO `favorites` (`favorite_id`, `user_id`, `product_id`, `created_at`, `updated_at`) VALUES
(1, 6, 1, '2025-01-25 10:36:37', '2025-01-25 10:36:37'),
(2, 6, 3, '2025-01-25 10:36:48', '2025-01-25 10:36:48'),
(3, 6, 4, '2025-01-25 20:53:48', '2025-01-25 20:53:48'),
(4, 18, 1, '2025-01-27 13:58:27', '2025-01-27 13:58:27'),
(5, 18, 3, '2025-01-27 13:58:30', '2025-01-27 13:58:30');

-- --------------------------------------------------------

--
-- Table structure for table `menu_items`
--

CREATE TABLE `menu_items` (
  `item_id` int UNSIGNED NOT NULL,
  `restaurant_id` int UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text,
  `price` decimal(10,2) NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `menu_items`
--

INSERT INTO `menu_items` (`item_id`, `restaurant_id`, `name`, `description`, `price`, `image_url`, `created_at`, `updated_at`) VALUES
(1, 1, 'Burger', 'Delicious beef burger', 12.50, 'https://example.com/burger.jpg', '2024-12-16 14:37:23', '2024-12-16 14:37:23'),
(2, 1, 'Pizza', 'Cheese pizza', 15.00, 'https://example.com/pizza.jpg', '2024-12-16 14:37:23', '2024-12-16 14:37:23'),
(3, 1, 'Soda', 'Soft drink', 2.00, 'https://example.com/soda.jpg', '2024-12-16 14:37:23', '2024-12-16 14:37:23'),
(4, 1, 'Fries', 'Crispy fries', 5.00, 'https://example.com/fries.jpg', '2024-12-16 14:37:23', '2024-12-16 14:37:23');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `restaurant_id` int UNSIGNED NOT NULL,
  `cart_id` int UNSIGNED DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `shipping_address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci,
  `order_status` enum('Pending','Processing','Completed','Cancelled','Delivered') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT 'Pending',
  `pay_method` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `user_id`, `restaurant_id`, `cart_id`, `total_amount`, `shipping_address`, `order_status`, `pay_method`, `created_at`, `updated_at`) VALUES
(2, 6, 1, NULL, 15.00, NULL, 'Pending', NULL, '2025-01-28 12:38:44', '2025-01-28 12:38:44'),
(3, 6, 1, NULL, 37.00, NULL, 'Delivered', NULL, '2025-01-28 12:40:37', '2025-02-14 02:17:27'),
(4, 6, 1, NULL, 63.00, NULL, 'Completed', NULL, '2025-01-28 12:48:24', '2025-02-14 02:14:49'),
(5, 6, 1, NULL, 117.00, NULL, 'Processing', NULL, '2025-01-28 12:49:54', '2025-02-14 02:14:35'),
(6, 18, 1, NULL, 37.00, NULL, 'Cancelled', NULL, '2025-01-28 12:54:57', '2025-02-14 02:14:59');

--
-- Triggers `orders`
--
DELIMITER $$
CREATE TRIGGER `after_order_created` AFTER INSERT ON `orders` FOR EACH ROW BEGIN
    
    DECLARE user_cart_id INT$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `order_item_id` int NOT NULL,
  `order_id` int UNSIGNED NOT NULL,
  `cart_item_id` int UNSIGNED DEFAULT NULL,
  `product_id` int UNSIGNED NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) GENERATED ALWAYS AS ((`quantity` * `unit_price`)) STORED,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`order_item_id`, `order_id`, `cart_item_id`, `product_id`, `quantity`, `unit_price`, `created_at`, `updated_at`) VALUES
(1, 3, NULL, 1, 1, 15.00, '2025-01-28 12:40:37', '2025-01-28 12:40:37'),
(2, 3, NULL, 2, 1, 10.00, '2025-01-28 12:40:37', '2025-01-28 12:40:37'),
(3, 3, NULL, 3, 1, 12.00, '2025-01-28 12:40:37', '2025-01-28 12:40:37'),
(4, 4, NULL, 4, 1, 18.00, '2025-01-28 12:48:24', '2025-01-28 12:48:24'),
(5, 4, NULL, 1, 3, 15.00, '2025-01-28 12:48:24', '2025-01-28 12:48:24'),
(7, 5, NULL, 1, 1, 15.00, '2025-01-28 12:49:54', '2025-01-28 12:49:54'),
(8, 5, NULL, 2, 3, 10.00, '2025-01-28 12:49:54', '2025-01-28 12:49:54'),
(9, 5, NULL, 3, 3, 12.00, '2025-01-28 12:49:54', '2025-01-28 12:49:54'),
(10, 5, NULL, 4, 2, 18.00, '2025-01-28 12:49:54', '2025-01-28 12:49:54'),
(14, 6, NULL, 1, 1, 15.00, '2025-01-28 12:54:57', '2025-01-28 12:54:57'),
(15, 6, NULL, 2, 1, 10.00, '2025-01-28 12:54:57', '2025-01-28 12:54:57'),
(16, 6, NULL, 3, 1, 12.00, '2025-01-28 12:54:57', '2025-01-28 12:54:57');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `token_id` int NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `token` varchar(255) NOT NULL,
  `createdAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `permission_id` smallint UNSIGNED NOT NULL,
  `permission_name` varchar(50) NOT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`permission_id`, `permission_name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'View Users', 'Allows viewing the list of users in the system.', '2024-12-16 12:27:44', '2024-12-16 12:27:44'),
(2, 'Edit Users', 'Grants the ability to edit user details.', '2024-12-16 12:27:44', '2024-12-16 12:27:44'),
(3, 'Delete Users', 'Allows deleting user accounts from the system.', '2024-12-16 12:27:44', '2024-12-16 12:27:44'),
(4, 'Manage Roles', 'Enables adding, editing, and deleting roles.', '2024-12-16 12:27:44', '2024-12-16 12:27:44'),
(5, 'Access Reports', 'Grants access to view and generate reports.', '2024-12-16 12:27:44', '2024-12-16 12:27:44'),
(6, 'Create Orders', 'Allows creating new orders in the system.', '2024-12-16 12:27:44', '2024-12-16 12:27:44'),
(7, 'Manage Inventory', 'Grants permission to add, update, and remove inventory items.', '2024-12-16 12:27:44', '2024-12-16 12:27:44'),
(8, 'View Transactions', 'Allows viewing transaction histories and details.', '2024-12-16 12:27:44', '2024-12-16 12:27:44');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` int UNSIGNED NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `description` text,
  `price` decimal(10,2) NOT NULL,
  `stock_quantity` int NOT NULL,
  `category_id` int UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `restaurant_id` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `product_name`, `description`, `price`, `stock_quantity`, `category_id`, `created_at`, `updated_at`, `restaurant_id`) VALUES
(1, 'Pizza', 'Delicious cheese and tomato pizza with a crispy crust.', 15.00, 50, 1, '2024-12-16 14:31:55', '2024-12-16 14:31:55', 1),
(2, 'Burger', 'Juicy beef burger with fresh lettuce, tomato, and cheese.', 10.00, 30, 2, '2024-12-16 14:31:55', '2024-12-16 14:31:55', 1),
(3, 'Sushi Roll', 'Fresh salmon sushi roll with avocado and cucumber.', 12.00, 20, 3, '2024-12-16 14:31:55', '2024-12-16 14:31:55', 2),
(4, 'Ramen', 'Traditional Japanese ramen with rich broth and noodles.', 18.00, 25, 2, '2024-12-16 14:31:55', '2024-12-16 14:31:55', 2),
(5, 'Spaghetti', 'Pasta with marinara sauce and grated parmesan.', 14.00, 40, 1, '2024-12-16 14:31:55', '2024-12-16 14:31:55', 3),
(6, 'Tacos', 'Soft tortillas with seasoned beef, cheese, and salsa.', 8.00, 35, 4, '2024-12-16 14:31:55', '2024-12-16 14:31:55', 3);

-- --------------------------------------------------------

--
-- Table structure for table `products_images`
--

CREATE TABLE `products_images` (
  `id` int UNSIGNED NOT NULL,
  `product_id` int UNSIGNED NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `products_images`
--

INSERT INTO `products_images` (`id`, `product_id`, `image_url`, `created_at`, `updated_at`) VALUES
(1, 1, 'uploads/foo1.jpg', '2025-01-09 10:31:55', '2025-01-09 10:31:55'),
(2, 2, 'uploads/food2.jpg', '2025-01-09 10:31:55', '2025-01-09 10:43:29'),
(5, 3, 'uploads/food3.jpg', '2025-01-09 10:45:59', '2025-01-09 10:45:59'),
(6, 4, 'uploads/food2.jpg', '2025-01-09 10:45:59', '2025-01-09 10:45:59'),
(7, 5, 'uploads/food2.jpg', '2025-01-09 10:45:59', '2025-01-09 10:45:59'),
(8, 6, 'uploads/food3.jpg', '2025-01-09 10:45:59', '2025-01-09 10:45:59');

-- --------------------------------------------------------

--
-- Table structure for table `restaurants`
--

CREATE TABLE `restaurants` (
  `restaurant_id` int UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text,
  `address` varchar(255) DEFAULT NULL,
  `latitude` decimal(9,6) NOT NULL,
  `longitude` decimal(9,6) NOT NULL,
  `rating` decimal(2,1) DEFAULT NULL,
  `manager_id` int UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `restaurants`
--

INSERT INTO `restaurants` (`restaurant_id`, `name`, `description`, `address`, `latitude`, `longitude`, `rating`, `manager_id`, `created_at`, `updated_at`) VALUES
(1, 'The Gourmet Kitchen', 'Fine dining restaurant offering a fusion of global cuisines.', '123 Culinary St, Food City, FC 45678', -6.792354, 39.208328, 4.8, 7, '2024-12-16 12:23:45', '2025-02-17 20:32:36'),
(2, 'Pasta Paradise', 'Authentic Italian pasta dishes in a cozy atmosphere.', '456 Noodle Ave, Food City, FC 45678', -3.386938, 36.682722, 4.5, 7, '2024-12-16 12:23:45', '2025-02-17 20:32:56'),
(3, 'Burger Haven', 'Casual dining serving gourmet burgers and shakes.', '789 Patty Rd, Food City, FC 45678', -2.516300, 32.927920, 4.2, 7, '2024-12-16 12:23:45', '2025-02-17 20:33:20'),
(4, 'Sushi Spot', 'Traditional Japanese sushi with a modern twist.', '321 Sashimi Blvd, Food City, FC 45678', -8.887388, 33.458761, 4.9, 7, '2024-12-16 12:23:45', '2025-02-17 20:33:44'),
(5, 'Vegan Vibes', 'Plant-based restaurant offering healthy and delicious meals.', '654 Green Way, Food City, FC 45678', -6.163914, 35.751780, 4.7, 7, '2024-12-16 12:23:45', '2025-02-17 20:34:04'),
(6, 'City Garden Catering', 'misosi fc', 'P. O. Box: 5896 Gerezani St Dar es Salaam Tanzania', -6.165914, 39.202649, 2.0, 7, '2025-02-13 08:09:52', '2025-02-17 20:34:23'),
(7, 'Darbrew Limited', 'misosi fc', '7/2 Morogoro Rd Dar es Salaam Tanzania', -6.795088, 39.211417, 2.0, 7, '2025-02-13 08:18:37', '2025-02-13 08:18:37');

-- --------------------------------------------------------

--
-- Table structure for table `restaurant_images`
--

CREATE TABLE `restaurant_images` (
  `id` int UNSIGNED NOT NULL,
  `restaurant_id` int UNSIGNED NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `restaurant_images`
--

INSERT INTO `restaurant_images` (`id`, `restaurant_id`, `image_url`, `created_at`, `updated_at`) VALUES
(1, 1, 'uploads/banner.jpg', '2025-01-09 11:20:55', '2025-01-09 11:20:55'),
(2, 4, 'uploads/banner.jpg', '2025-01-09 11:20:55', '2025-01-09 11:20:55'),
(5, 3, 'uploads/banner.jpg', '2025-01-09 11:21:49', '2025-01-09 11:21:49'),
(9, 2, 'uploads/banner.jpg', '2025-01-09 11:23:34', '2025-01-09 11:23:34'),
(10, 5, 'uploads/banner.jpg', '2025-01-09 11:23:34', '2025-01-09 11:23:34');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `review_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `restaurant_id` int UNSIGNED NOT NULL,
  `rating` tinyint UNSIGNED DEFAULT NULL,
  `comment` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `role_id` tinyint UNSIGNED NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`role_id`, `role_name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Admin', 'Has full access to the system, including user management and configuration settings.', '2024-12-16 12:20:57', '2024-12-16 12:20:57'),
(2, 'Customer', 'Can browse products, make purchases, and view order history.', '2024-12-16 12:20:57', '2024-12-16 12:20:57'),
(3, 'Manager', 'Oversees operations, manages staff, and monitors system activities.', '2024-12-16 12:20:57', '2024-12-16 12:20:57');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `role_id` tinyint UNSIGNED NOT NULL,
  `permission_id` smallint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`, `updated_at`) VALUES
(1, 1, '2024-12-16 12:29:44', '2024-12-16 12:29:44'),
(1, 2, '2024-12-16 12:29:44', '2024-12-16 12:29:44'),
(1, 3, '2024-12-16 12:29:44', '2024-12-16 12:29:44'),
(1, 4, '2024-12-16 12:29:44', '2024-12-16 12:29:44'),
(1, 5, '2024-12-16 12:29:44', '2024-12-16 12:29:44'),
(1, 6, '2024-12-16 12:29:44', '2024-12-16 12:29:44'),
(1, 7, '2024-12-16 12:29:44', '2024-12-16 12:29:44'),
(1, 8, '2024-12-16 12:29:44', '2024-12-16 12:29:44'),
(2, 1, '2024-12-16 12:29:44', '2024-12-16 12:29:44'),
(2, 5, '2024-12-16 12:29:44', '2024-12-16 12:29:44'),
(2, 6, '2024-12-16 12:29:44', '2024-12-16 12:29:44'),
(2, 7, '2024-12-16 12:29:44', '2024-12-16 12:29:44'),
(2, 8, '2024-12-16 12:29:44', '2024-12-16 12:29:44'),
(3, 6, '2024-12-16 12:29:44', '2024-12-16 12:29:44'),
(3, 8, '2024-12-16 12:29:44', '2024-12-16 12:29:44');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int UNSIGNED NOT NULL,
  `role_id` tinyint UNSIGNED DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `phone_number` varchar(15) DEFAULT NULL,
  `otp_code` varchar(10) DEFAULT NULL,
  `otp_expires_at` datetime DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT '0',
  `profile_pic` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `role_id`, `name`, `email`, `password_hash`, `phone_number`, `otp_code`, `otp_expires_at`, `is_verified`, `profile_pic`, `created_at`, `updated_at`) VALUES
(6, 3, 'Elia', 'elia@gmail.com', '$2a$10$A3.7nbugPRxW8Oh6P85h2uge5R/djdFfxme9KDEZdqAmRE9DoeytG', '0769289824', NULL, NULL, 0, 'uploads/logo.png', '2024-12-17 04:50:40', '2025-02-13 12:30:29'),
(7, 1, 'admin mbogafasta', 'admin@gmail.com', '$2a$10$A3.7nbugPRxW8Oh6P85h2uge5R/djdFfxme9KDEZdqAmRE9DoeytG', '0769289824', NULL, NULL, 0, '', '2024-12-19 14:11:21', '2025-02-13 12:22:17'),
(18, 2, 'Aneth', 'aneth@gmail.com', '$2a$10$vvlbBKUkLaZ/6MXGVq00qOeWu/2g/1Y69hn90un8m5y1xmbThv0bm', '0752289824', NULL, NULL, 0, NULL, '2025-01-24 11:35:58', '2025-01-24 11:35:58'),
(42, 2, 'Asia', 'asia@gmail.com', '$2a$10$jyHgp9FPauYXfCgokh6RaOH2lXYptyvF03MkD6NKKjkOQqXO3yo8i', '255754289824', '4319', '2025-03-16 23:46:21', 1, NULL, '2025-03-16 20:36:21', '2025-03-16 20:36:37'),
(43, 2, 'idriss', 'idriss@gmail.com', '$2a$10$.P8XL5pIFxiuj/3UhtFE5OgyKA5l/9XM5iddHyFmq0fs9050ySyPm', '255773381286', '6144', '2025-03-16 23:48:44', 0, NULL, '2025-03-16 20:38:43', '2025-03-16 20:38:43');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `banners`
--
ALTER TABLE `banners`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `carts`
--
ALTER TABLE `carts`
  ADD PRIMARY KEY (`cart_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`cart_item_id`),
  ADD KEY `cart_id` (`cart_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

--
-- Indexes for table `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`favorite_id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`product_id`),
  ADD KEY `user_id_2` (`user_id`),
  ADD KEY `product_id` (`product_id`) USING BTREE;

--
-- Indexes for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `restaurant_id` (`restaurant_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `restaurant_id` (`restaurant_id`),
  ADD KEY `cart_id` (`cart_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `cart_item_id` (`cart_item_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`token_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`permission_id`),
  ADD UNIQUE KEY `permission_name` (`permission_name`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `restaurant_id` (`restaurant_id`);

--
-- Indexes for table `products_images`
--
ALTER TABLE `products_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `restaurants`
--
ALTER TABLE `restaurants`
  ADD PRIMARY KEY (`restaurant_id`),
  ADD KEY `idx_location` (`latitude`,`longitude`),
  ADD KEY `manager_id` (`manager_id`);

--
-- Indexes for table `restaurant_images`
--
ALTER TABLE `restaurant_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `restaurant_id` (`restaurant_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `restaurant_id` (`restaurant_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`role_id`,`permission_id`),
  ADD KEY `permission_id` (`permission_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `role_id` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `banners`
--
ALTER TABLE `banners`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `carts`
--
ALTER TABLE `carts`
  MODIFY `cart_id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `cart_items`
--
ALTER TABLE `cart_items`
  MODIFY `cart_item_id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `favorites`
--
ALTER TABLE `favorites`
  MODIFY `favorite_id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `menu_items`
--
ALTER TABLE `menu_items`
  MODIFY `item_id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `order_item_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `token_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `permission_id` smallint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `product_id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `products_images`
--
ALTER TABLE `products_images`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `restaurants`
--
ALTER TABLE `restaurants`
  MODIFY `restaurant_id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `restaurant_images`
--
ALTER TABLE `restaurant_images`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` tinyint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `carts`
--
ALTER TABLE `carts`
  ADD CONSTRAINT `carts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `cart_items`
--
ALTER TABLE `cart_items`
  ADD CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`cart_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE;

--
-- Constraints for table `favorites`
--
ALTER TABLE `favorites`
  ADD CONSTRAINT `favorites_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `favorites_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `restaurants` (`restaurant_id`) ON DELETE CASCADE;

--
-- Constraints for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD CONSTRAINT `menu_items_ibfk_1` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`restaurant_id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`restaurant_id`),
  ADD CONSTRAINT `orders_ibfk_3` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`cart_id`) ON DELETE SET NULL;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`cart_item_id`) REFERENCES `cart_items` (`cart_item_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `order_items_ibfk_3` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);

--
-- Constraints for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD CONSTRAINT `password_reset_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`restaurant_id`);

--
-- Constraints for table `products_images`
--
ALTER TABLE `products_images`
  ADD CONSTRAINT `products_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`);

--
-- Constraints for table `restaurant_images`
--
ALTER TABLE `restaurant_images`
  ADD CONSTRAINT `restaurant_images_ibfk_1` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`restaurant_id`);

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants` (`restaurant_id`) ON DELETE CASCADE;

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`permission_id`) ON DELETE CASCADE;

--
-- Constraints for table `restaurants`
--
ALTER TABLE `restaurants`
  ADD CONSTRAINT `restaurants_ibfk_1` FOREIGN KEY (`manager_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
