-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 22, 2026 at 01:09 PM
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
-- Database: `urbaneats_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_log`
--

INSERT INTO `audit_log` (`log_id`, `user_id`, `action`, `details`, `ip_address`, `created_at`) VALUES
(1, 1, 'USER_REGISTERED', 'New user account created successfully', '::1', '2026-10-21 18:50:12'),
(2, 1, 'LOGIN_SUCCESS', 'User logged in successfully', '::1', '2026-10-21 18:50:23'),
(3, 2, 'USER_REGISTERED', 'New user account created successfully', '::1', '2026-10-21 18:52:23'),
(4, 2, 'LOGIN_SUCCESS', 'User logged in successfully', '::1', '2026-10-21 18:52:45'),
(5, 3, 'USER_REGISTERED', 'New user account created successfully', '::1', '2026-10-21 18:54:38'),
(6, 3, 'LOGIN_SUCCESS', 'User logged in successfully', '::1', '2026-10-21 18:55:19'),
(7, 1, 'LOGIN_SUCCESS', 'User logged in successfully', '::1', '2026-10-21 18:58:16'),
(8, 2, 'LOGIN_SUCCESS', 'User logged in successfully', '::1', '2026-10-21 19:14:29'),
(9, 2, 'LOGIN_SUCCESS', 'User logged in successfully', '::1', '2026-10-22 03:25:03'),
(10, 1, 'LOGIN_SUCCESS', 'User logged in successfully', '::1', '2026-10-22 03:25:50'),
(11, 1, 'PASSWORD_CHANGE', 'User changed account password', '::1', '2026-10-22 03:26:40'),
(12, 1, 'LOGIN_SUCCESS', 'User logged in successfully', '::1', '2026-10-22 03:28:56'),
(13, 2, 'LOGIN_SUCCESS', 'User logged in successfully', '::1', '2026-10-22 03:29:40'),
(14, 3, 'LOGIN_SUCCESS', 'User logged in successfully', '::1', '2026-10-22 03:30:57'),
(15, 1, 'LOGIN_SUCCESS', 'User logged in successfully', '::1', '2026-10-22 03:38:11'),
(16, 2, 'LOGIN_SUCCESS', 'User logged in successfully', '::1', '2026-10-22 03:54:32'),
(17, 1, 'LOGIN_SUCCESS', 'User logged in successfully', '::1', '2026-10-22 03:57:32'),
(18, 3, 'LOGIN_SUCCESS', 'User logged in successfully', '::1', '2026-10-22 03:58:58'),
(19, 1, 'LOGIN_SUCCESS', 'User logged in successfully', '::1', '2026-10-22 04:25:33'),
(20, 1, 'USER_STATUS_TOGGLE', 'Toggled status for user ID: 3', '::1', '2026-10-22 04:25:59'),
(21, 2, 'LOGIN_SUCCESS', 'User logged in successfully', '::1', '2026-10-22 08:38:49'),
(22, 1, 'LOGIN_SUCCESS', 'User logged in successfully', '::1', '2026-10-22 08:39:43'),
(23, 7, 'LOGIN_SUCCESS', 'User logged in successfully', '::1', '2026-10-22 11:08:36');

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `attempt_id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `email` varchar(100) NOT NULL,
  `attempt_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_attempts`
--

INSERT INTO `login_attempts` (`attempt_id`, `ip_address`, `email`, `attempt_time`) VALUES
(1, '::1', 'kulasuriyanurani@gmail.com', '2026-10-21 18:28:41'),
(2, '::1', 'nurani@gmail.com', '2026-10-21 18:29:18'),
(3, '::1', 'kulasuriyanurani@gmail.com', '2026-10-21 18:29:20'),
(4, '::1', 'kulasuriyanurani@gmail.com', '2026-10-21 18:29:22'),
(5, '::1', 'kulasuriyanurani@gmail.com', '2026-10-21 18:33:48'),
(6, '::1', 'kulasuriyanurani@gmail.com', '2026-10-21 18:50:15'),
(7, '::1', 'kulasuriyanurani@gmail.com', '2026-10-21 18:58:02'),
(8, '::1', 'kulasuriyanurani@gmail.com', '2026-10-22 03:25:36'),
(9, '::1', 'kulasuriyanurani@gmail.com', '2026-10-22 03:57:14'),
(10, '::1', 'samadhi@urbaneats.com', '2026-10-22 10:43:38'),
(11, '::1', 'samadhi@urbaneats.com', '2026-10-22 10:44:06');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`role_id`, `role_name`) VALUES
(3, 'Administrator'),
(1, 'Customer'),
(2, 'Kitchen Staff');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `email`, `phone`, `password_hash`, `role_id`, `is_active`, `created_at`) VALUES
(1, 'nurani', 'kulasuriyanurani@gmail.com', '0778905771', '$2y$10$TaUt38EGjIzdh2xyNzKMlOBbGC7SqZM6ajjjULGKXDdR3orw92uSq', 3, 1, '2026-10-21 18:50:12'),
(2, 'test', 'test@gmail.com', '0778674534', '$2y$10$eq/ZhsVTiOLj0w2nH5xheOulCokdiaXSRPp1gChRw1VRM3iY.K1o6', 1, 1, '2026-10-21 18:52:23'),
(3, 'vishmi', 'vishmi@gmail.com', '0774568790', '$2y$10$ooTj1Jkcd9iocReaJMjGtOy3GFc0UOshJF3p7tgtor.6hpOyWGSKO', 2, 0, '2026-10-21 18:54:38'),
(7, 'Samadhi Hansika', 'samadhi@urbaneats.com', '', '$2y$10$iNNtDQewNDDbIGKguRi6F.smX5s2ZUaIXbW6VXv6E2XJqaKA9l0BS', 2, 1, '2026-10-22 11:07:54'),
(8, 'Sanduni Thilanka', 'sanduni@urbaneats.com', '', '$2y$10$ODiiU4a1.BV17ZRbZyApp.zKrDOIV1e7IFDyQ0XccWpJThdKxWEGG', 2, 1, '2026-10-22 11:07:54'),
(9, 'Sasuni Ishanga', 'sasuni@urbaneats.com', '', '$2y$10$QYEY9EIYx46yyJPN8GNUnOnV7YRzSsC.uFeo/e7F1fWZHL6w0cyOa', 2, 1, '2026-10-22 11:07:54');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`attempt_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

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
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `attempt_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD CONSTRAINT `audit_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
