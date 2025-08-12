-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 12, 2025 at 02:16 AM
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
(1, 'cattle', '2025-08-11', 7, '1485', 'hereford', 'male', 'black', '680', 'Windhoek', 'Healthy', '2025-08-11 12:27:46');

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
(5, 'vet1', '$2y$10$V...hashedStringHere...', 'vet', NULL, NULL, NULL, '2025-08-10 18:32:45'),
(6, 'admin1', '$2y$10$V...hashedStringHere...', 'admin', NULL, NULL, NULL, '2025-08-10 18:32:45'),
(7, 'jr_toivo', '$2y$10$R17JAW75.uXOd0hHbKOSc.dyZ4VXb.Dkw7AhJAD6A0VKLnJo1rgJq', 'extension_officer', 'toivotauno0321@gmail.com', '+264815605924', 'Tauno ', '2025-08-10 18:32:49'),
(8, 'MK', '$2y$10$IKVT7Wi1yNF7EVhcGZw6i.gUZQebuZXzRMY6WN7f6Sr12g11aNfRO', 'farmer', 'moanakalupeteka@gmail.com', '+264812345678', 'Moana Kalupeteka', '2025-08-11 20:14:52');

-- --------------------------------------------------------

--
-- Table structure for table `vaccinations`
--

CREATE TABLE `vaccinations` (
  `id` int NOT NULL,
  `type` varchar(100) NOT NULL,
  `date_administered` date NOT NULL,
  `due_date` date DEFAULT NULL,
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
  ADD KEY `livestock_id` (`livestock_id`);

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

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
