-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 17, 2026 at 07:50 AM
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
-- Database: `enrollmentdb`
--

-- --------------------------------------------------------

--
-- Table structure for table `received_mail`
--

CREATE TABLE `received_mail` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `sender_name` varchar(100) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `date_received` date NOT NULL,
  `status` enum('unread','read','urgent') DEFAULT 'unread',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `received_mail`
--

INSERT INTO `received_mail` (`id`, `email`, `sender_name`, `subject`, `message`, `date_received`, `status`, `created_at`) VALUES
(1, 'ajkbdk@gmail.com', 'yjdvade', 'kajebdk', 'haebdkbw', '2026-08-16', 'read', '2026-08-16 09:16:25'),
(2, 'yuruyry@gmail.com', 'ryuruyuyrruyryruy', 'ruryuruyrryr', 'yuruyryur', '2026-08-16', 'read', '2026-08-16 09:49:46'),
(3, 'a@gmail.com', 'adbhwio', 'abksdbjaa', 'awbdjaboiw', '2026-08-17', 'read', '2026-08-17 05:01:52'),
(4, 'shan@gmail.com', 'shan', 'admission', 'example', '2026-08-17', 'read', '2026-08-17 05:24:48'),
(5, 's@gmail.com', 'shan', 'admission', 'exmaple', '2026-08-17', 'read', '2026-08-17 05:34:17');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `received_mail`
--
ALTER TABLE `received_mail`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `received_mail`
--
ALTER TABLE `received_mail`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
