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
-- Table structure for table `composed_mail`
--

CREATE TABLE `composed_mail` (
  `id` int(11) NOT NULL,
  `sender` varchar(50) DEFAULT 'ICFAdmin',
  `email` varchar(100) NOT NULL,
  `receiver` varchar(100) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `date_sent` date NOT NULL,
  `status` enum('Pending Send','Sent','Cancelled') DEFAULT 'Pending Send',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `composed_mail`
--

INSERT INTO `composed_mail` (`id`, `sender`, `email`, `receiver`, `subject`, `message`, `date_sent`, `status`, `created_at`) VALUES
(1, 'ICFAdmin', 'j@gmail.com', 'rei', 'enrollment approve', 'approve', '2026-08-12', 'Sent', '2026-08-12 04:06:01'),
(2, 'ICFAdmin', 's@gmail.com', 'rei', 'enrollment approve', 'approved', '2026-08-12', 'Sent', '2026-08-12 05:25:51');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `composed_mail`
--
ALTER TABLE `composed_mail`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `composed_mail`
--
ALTER TABLE `composed_mail`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
