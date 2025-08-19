-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 19, 2025 at 03:06 AM
-- Server version: 8.0.34
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `livestock_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `disease_reports`
--

CREATE TABLE `disease_reports` (
  `id` int NOT NULL,
  `disease_name` varchar(100) NOT NULL,
  `date_reported` date NOT NULL,
  `symptoms` text,
  `livestock_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `livestock`
--

CREATE TABLE `livestock` (
  `id` int NOT NULL,
  `species` varchar(50) NOT NULL,
  `birth_date` date DEFAULT NULL,
  `owner_id` int DEFAULT NULL,
  `animal_id` varchar(25) DEFAULT NULL,
  `breed` varchar(25) DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `color` varchar(10) DEFAULT NULL,
  `weight` varchar(10) DEFAULT NULL,
  `location` varchar(10) DEFAULT NULL,
  `notes` varchar(500) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `livestock`
--

INSERT INTO `livestock` (`id`, `species`, `birth_date`, `owner_id`, `animal_id`, `breed`, `gender`, `color`, `weight`, `location`, `notes`, `created_at`) VALUES
(1, 'cattle', '2025-08-11', 7, '1485', 'hereford', 'male', 'black', '680', 'Windhoek', 'Healthy', '2025-08-11 12:27:46'),
(2, 'sheep', '2025-08-05', 8, '1486', 'Dorper', 'female', 'white', '100', '', 'heealthy', '2025-08-12 03:55:40'),
(3, 'goat', '2025-08-11', 8, '1488', 'boer', 'male', 'black', '140', 'swakopmund', 'greatest of all time', '2025-08-12 03:57:36'),
(4, 'chicken', '2025-08-12', 8, '1499', 'silkie', 'male', 'yellow', '30', 'Ohangwena', 'fast', '2025-08-12 12:30:29');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('farmer','vet','extension_officer','admin') NOT NULL,
  `Email` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `full_name` varchar(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `Email`, `phone`, `full_name`, `created_at`) VALUES
(4, 'farmer1', '$2y$10$V...hashedStringHere...', 'farmer', NULL, NULL, NULL, '2025-08-10 18:32:45'),
(5, 'vet1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'vet', NULL, NULL, NULL, '2025-08-10 18:32:45'),
(6, 'admin1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NULL, NULL, NULL, '2025-08-10 18:32:45'),
(7, 'jr_toivo', '$2y$10$R17JAW75.uXOd0hHbKOSc.dyZ4VXb.Dkw7AhJAD6A0VKLnJo1rgJq', 'extension_officer', 'toivotauno0321@gmail.com', '+264815605924', 'Tauno ', '2025-08-10 18:32:49'),
(8, 'MK', '$2y$10$IKVT7Wi1yNF7EVhcGZw6i.gUZQebuZXzRMY6WN7f6Sr12g11aNfRO', 'farmer', 'moanakalupeteka@gmail.com', '+264812345678', 'Moana Kalupeteka', '2025-08-11 20:14:52'),
(9, 'JB', '$2y$10$SG7j.CZdDZ.HYiBLYT.LieZDuLx1xq30YHpndT2Idx64aUWIX6Pf6', 'vet', 'jb@gmail.com', '+264818765432', 'James Bond', '2025-08-12 01:08:12');

-- --------------------------------------------------------

--
-- Table structure for table `vaccinations`
--

CREATE TABLE `vaccinations` (
  `id` int NOT NULL,
  `type` varchar(100) NOT NULL,
  `date_administered` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `next_due_date` date DEFAULT NULL,
  `batch_number` varchar(50) DEFAULT NULL,
  `notes` text,
  `administered_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `livestock_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `disease_reports`
--
ALTER TABLE `disease_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `livestock_id` (`livestock_id`);

--
-- Indexes for table `livestock`
--
ALTER TABLE `livestock`
  ADD PRIMARY KEY (`id`),
  ADD KEY `owner_id` (`owner_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `vaccinations`
--
ALTER TABLE `vaccinations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `livestock_id` (`livestock_id`),
  ADD KEY `idx_vaccinations_due_date` (`due_date`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `disease_reports`
--
ALTER TABLE `disease_reports`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `livestock`
--
ALTER TABLE `livestock`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `vaccinations`
--
ALTER TABLE `vaccinations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `disease_reports`
--
ALTER TABLE `disease_reports`
  ADD CONSTRAINT `disease_reports_ibfk_1` FOREIGN KEY (`livestock_id`) REFERENCES `livestock` (`id`);

--
-- Constraints for table `livestock`
--
ALTER TABLE `livestock`
  ADD CONSTRAINT `livestock_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `vaccinations`
--
ALTER TABLE `vaccinations`
  ADD CONSTRAINT `vaccinations_ibfk_1` FOREIGN KEY (`livestock_id`) REFERENCES `livestock` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
