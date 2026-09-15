-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Sep 14, 2026 at 09:44 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `unsaid`
--

-- --------------------------------------------------------

--
-- Table structure for table `administrators`
--

CREATE TABLE `administrators` (
  `id` int(11) NOT NULL,
  `username` varchar(30) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `administrators`
--

INSERT INTO `administrators` (`id`, `username`, `password`, `created_at`) VALUES
(1, 'zacuia', '$2y$10$R6lrd5.gLs4dOdeh9JNgEe7jyD3SCxBj9TZL.BXqd2vJKgCde43v2', '2026-09-09 20:40:38');

-- --------------------------------------------------------

--
-- Table structure for table `attachments`
--

CREATE TABLE `attachments` (
  `id` int(11) NOT NULL,
  `message_id` int(11) NOT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `file_type` varchar(20) NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `link_url` varchar(500) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `banned_senders`
--

CREATE TABLE `banned_senders` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `banned_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `profile_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `sender_ip` varchar(45) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `profile_id`, `message`, `is_read`, `sender_ip`, `created_at`, `updated_at`) VALUES
(2, 2, 'kinginamo', 1, '::1', '2026-09-14 19:43:51', '2026-09-14 19:52:28');

-- --------------------------------------------------------

--
-- Table structure for table `profiles`
--

CREATE TABLE `profiles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `display_name` varchar(50) NOT NULL,
  `bio` varchar(300) DEFAULT '',
  `profile_image` varchar(255) DEFAULT NULL,
  `page_title` varchar(100) NOT NULL DEFAULT 'Got something to say?',
  `message_placeholder` varchar(150) NOT NULL DEFAULT 'Write something anonymously...',
  `button_text` varchar(50) NOT NULL DEFAULT 'Send anonymously',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `profiles`
--

INSERT INTO `profiles` (`id`, `user_id`, `display_name`, `bio`, `profile_image`, `page_title`, `message_placeholder`, `button_text`, `created_at`, `updated_at`) VALUES
(2, 2, 'David', '', 'ca7b419667245888eb62f816bcf6fb50.jpg', 'Got something to say? (Mag esmeringhoy na ko diha)', 'Write something anonymously...', 'Send anonymously', '2026-09-10 07:19:27', '2026-09-10 07:22:31');

-- --------------------------------------------------------

--
-- Table structure for table `registration_logs`
--

CREATE TABLE `registration_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `device` varchar(100) DEFAULT NULL,
  `browser` varchar(100) DEFAULT NULL,
  `operating_system` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `registration_logs`
--

INSERT INTO `registration_logs` (`id`, `user_id`, `ip_address`, `country`, `device`, `browser`, `operating_system`, `created_at`) VALUES
(2, 2, '::1', 'Local', 'Desktop', 'Chrome', 'Windows', '2026-09-10 07:19:27');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` int(11) NOT NULL,
  `message_id` int(11) DEFAULT NULL,
  `profile_id` int(11) NOT NULL,
  `reported_message` text NOT NULL,
  `sender_ip` varchar(45) DEFAULT NULL,
  `status` enum('pending','reviewed') NOT NULL DEFAULT 'pending',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reports`
--

INSERT INTO `reports` (`id`, `message_id`, `profile_id`, `reported_message`, `sender_ip`, `status`, `created_at`) VALUES
(1, 2, 2, 'kinginamo', '::1', 'reviewed', '2026-09-14 19:43:57');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(30) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `created_at`, `updated_at`) VALUES
(2, 'david', '$2y$10$JsOiFTjEcqqZYvj/HMR0V.f4eEA04q..XvCZcAJxDReAGGoDQsEci', '2026-09-10 07:19:27', '2026-09-10 07:19:27');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `administrators`
--
ALTER TABLE `administrators`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `attachments`
--
ALTER TABLE `attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_attachments_message` (`message_id`);

--
-- Indexes for table `banned_senders`
--
ALTER TABLE `banned_senders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ip_address` (`ip_address`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_messages_profile` (`profile_id`);

--
-- Indexes for table `profiles`
--
ALTER TABLE `profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `registration_logs`
--
ALTER TABLE `registration_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_logs_user` (`user_id`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_reports_message` (`message_id`),
  ADD KEY `fk_reports_profile` (`profile_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `administrators`
--
ALTER TABLE `administrators`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `attachments`
--
ALTER TABLE `attachments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `banned_senders`
--
ALTER TABLE `banned_senders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `profiles`
--
ALTER TABLE `profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `registration_logs`
--
ALTER TABLE `registration_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attachments`
--
ALTER TABLE `attachments`
  ADD CONSTRAINT `fk_attachments_message` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `fk_messages_profile` FOREIGN KEY (`profile_id`) REFERENCES `profiles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `profiles`
--
ALTER TABLE `profiles`
  ADD CONSTRAINT `fk_profiles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `registration_logs`
--
ALTER TABLE `registration_logs`
  ADD CONSTRAINT `fk_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `fk_reports_message` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_reports_profile` FOREIGN KEY (`profile_id`) REFERENCES `profiles` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
