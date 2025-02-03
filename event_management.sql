-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 02, 2025 at 11:29 PM
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
-- Database: `event_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendees`
--

CREATE TABLE `attendees` (
  `id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `registration_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('registered','cancelled') NOT NULL DEFAULT 'registered'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `attendees`
--

INSERT INTO `attendees` (`id`, `event_id`, `user_id`, `registration_date`, `status`) VALUES
(89, 15, 4, '2025-02-02 18:53:06', 'registered'),
(90, 15, 3, '2025-02-02 18:53:34', 'cancelled'),
(91, 15, 3, '2025-02-02 18:57:11', 'registered'),
(92, 15, 2, '2025-02-02 18:57:58', 'cancelled'),
(93, 16, 3, '2025-02-02 19:06:41', 'registered'),
(94, 16, 4, '2025-02-02 19:08:33', 'cancelled'),
(95, 16, 4, '2025-02-02 19:12:24', 'cancelled'),
(96, 16, 4, '2025-02-02 19:12:27', 'cancelled'),
(97, 16, 4, '2025-02-02 19:13:24', 'cancelled'),
(98, 16, 4, '2025-02-02 19:14:59', 'cancelled'),
(99, 16, 4, '2025-02-02 19:15:02', 'cancelled'),
(100, 16, 4, '2025-02-02 19:15:05', 'cancelled'),
(101, 16, 4, '2025-02-02 19:18:54', 'cancelled'),
(102, 15, 2, '2025-02-02 19:44:06', 'registered');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `event_date` datetime NOT NULL,
  `location` varchar(255) NOT NULL,
  `max_capacity` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `created_by`, `title`, `description`, `event_date`, `location`, `max_capacity`, `created_at`, `updated_at`) VALUES
(15, 2, 'event', 'some event', '2025-02-12 18:00:00', 'Bangladesh', 40, '2025-02-02 13:46:24', '2025-02-02 18:35:32'),
(16, 3, 'event 2', 'another event', '2025-06-20 13:06:00', 'Banani', 20, '2025-02-02 19:06:39', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `is_admin` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `is_admin`, `created_at`, `last_login`) VALUES
(2, 'admin', 'admin@example.com', '$argon2id$v=19$m=65536,t=4,p=3$S2t6M0J6SGJJSDVyRGVvdw$5ZcFFsiX5DL0pI0qsgOQvmwBerwdiHVfjkH+1PtAH9Y', 1, '2025-02-02 07:20:03', NULL),
(3, 'test', 'test@example.com', '$argon2id$v=19$m=65536,t=4,p=3$ZGNyVTlEMWJJR3VydHBISg$MsQNiN7FtUIeWpsteozGvZbg+I7iJs/m/f9LZSqfAjQ', 0, '2025-02-02 07:26:22', NULL),
(4, 'test12', 'test12@example.com', '$argon2id$v=19$m=65536,t=4,p=3$QTdWeTdHUE0uZXNLY1dpaA$tLStxt63Tpteyo6kDMGcxNv4vAcR+aDhbNoNLG3IMOA', 0, '2025-02-02 18:37:07', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendees`
--
ALTER TABLE `attendees`
  ADD PRIMARY KEY (`id`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendees`
--
ALTER TABLE `attendees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=103;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendees`
--
ALTER TABLE `attendees`
  ADD CONSTRAINT `attendees_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendees_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `events`
--
ALTER TABLE `events`
  ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
