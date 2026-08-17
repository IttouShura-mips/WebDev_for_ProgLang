-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 17, 2026 at 04:39 AM
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
-- Table structure for table `enrolled`
--

CREATE TABLE `enrolled` (
  `student_id` int(11) NOT NULL,
  `firstname` varchar(25) NOT NULL,
  `middlename` varchar(25) NOT NULL,
  `lastname` varchar(25) NOT NULL,
  `suffix` varchar(25) NOT NULL,
  `gender` varchar(10) NOT NULL,
  `birthday` date NOT NULL,
  `birthplace` varchar(200) NOT NULL,
  `citizenship` varchar(50) NOT NULL,
  `civilstatus` varchar(25) NOT NULL,
  `employment` varchar(50) NOT NULL,
  `mother` varchar(200) NOT NULL,
  `mphone_number` varchar(11) NOT NULL,
  `father` varchar(200) NOT NULL,
  `fphone_number` varchar(15) NOT NULL,
  `guardian` varchar(200) NOT NULL,
  `gphone_number` varchar(15) NOT NULL,
  `course` varchar(150) NOT NULL,
  `major` varchar(25) NOT NULL,
  `school_address` varchar(200) NOT NULL,
  `academic_year` varchar(200) NOT NULL,
  `scholarship` varchar(50) NOT NULL,
  `full_address` varchar(200) NOT NULL,
  `mobile_number` varchar(11) NOT NULL,
  `email` varchar(200) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `status` enum('online','offline') DEFAULT 'offline',
  `enrollment_status` enum('pending','approved','declined') DEFAULT 'pending',
  `payment_proof` varchar(255) DEFAULT NULL,
  `requirements_files` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `enrolled`
--

INSERT INTO `enrolled` (`student_id`, `firstname`, `middlename`, `lastname`, `suffix`, `gender`, `birthday`, `birthplace`, `citizenship`, `civilstatus`, `employment`, `mother`, `mphone_number`, `father`, `fphone_number`, `guardian`, `gphone_number`, `course`, `major`, `school_address`, `academic_year`, `scholarship`, `full_address`, `mobile_number`, `email`, `ip_address`, `last_login`, `status`, `enrollment_status`, `payment_proof`, `requirements_files`, `created_at`) VALUES
(1, 'Shan Raye', 'Rosello', 'Guzman', '', 'Male', '2006-06-01', 'Paniqui, Tarlac', 'Filipino', 'Single', 'Unemployed', 'Maria ozawa', '09123456789', 'Mang Kanor', '09157955411', 'Mang kanor', '09157955411', 'BSCS', 'English', 'burgos', '2025-2026', 'ICF', 'Mang juan', '09123456789', 'Mangjuan@gmail.com', NULL, NULL, 'offline', 'pending', NULL, NULL, '2026-08-12 04:21:04'),
(2, 'ALMADIN', 'NOR', 'MIPANGA', '', 'Male', '2004-06-25', 'Paniqui, Tarlac', 'Filipino', 'Single', 'Employed', 'CINDERELLA MAGANGCONG', '09389987162', 'BILAL MIPANGA', '0926716622', 'KENJJIE', '009737832', 'ACT', 'None', 'burgos', '2026 - 2027', 'NONE', 'Mang juan', '09123456789', 'gshanraye@yahoo.com', NULL, NULL, 'offline', 'pending', NULL, NULL, '2026-08-12 04:21:04'),
(3, 'jairus', 'guirao', 'alfonso', 'jr', 'Male', '1998-07-07', 'Paniqui, Tarlac', 'Filipino', 'Married', 'Unemployed', 'teresa', '', 'johnny sins', '', 'mami oni', '', 'BSCS', 'English', 'burgos', '2026 - 2027', 'NONE', 'Mang juan', '09123456789', 'gshanraye@yahoo.com', NULL, NULL, 'offline', 'pending', NULL, NULL, '2026-08-12 04:21:04'),
(5, 'l', 'l', 'l', '', 'Male', '2006-09-16', 'l', 'k', 'Single', 'Employed', 'll', 'l', 'l', 'lll', 'l', 'l', 'Bachelor of Technical Vocational Teacher Education', 'N/A', 'l', 'l', 'ICF', 'l2', 'l', 'e@gnail.com', NULL, NULL, 'offline', 'pending', NULL, NULL, '2026-08-12 04:21:04'),
(9, 'shan', 'rosello', 'raye', '', 'male', '2014-06-13', 'mhgjfhgd', 'njhgfbfcv', '', '', '8cvbcvnbm', '09157955411', 'sdfgfdchtfw34', '09157955411', 'qr3w4ts5dht', '09157955411', 'Bachelor of Science in Computer Science', 'na', 'wq5e243tey5hr6ftcg', '2026-2027', 'academic', '123qrsgrfcxf', '09157955411', 'j@gmail.com', NULL, NULL, 'offline', 'pending', NULL, NULL, '2026-08-12 04:21:04'),
(10, 'shan', 'rosello', 'guzman', '', 'male', '2016-06-04', 'gjyf', 'jkbgui', '', '', 'hkjfghfxv', '09157955411', 'jlikugjyh', '09157955411', 'okjihugythg', '09157955411', 'Bachelor of Science in Computer Science', 'na', 'jiuyhfctfyui', '2026-2027', 'jihuyg', 'khkjg', '09157955411', 'k@gmail.com', NULL, NULL, 'offline', 'pending', NULL, NULL, '2026-08-12 05:49:15'),
(11, 'asd', '', 'asd', '', 'male', '2024-10-04', 'asd', 'Szdxc', '', '', 'zsadfg', '', 'dfdcv', '', 'Xzcxv', '', 'Bachelor of Science in Tourism Management', 'english', 'jhb', '2027-2028', 'jionjkh', 'jkhjjkjbnjiokklnjk', '', 'zj@gmail.com', NULL, NULL, 'offline', 'pending', NULL, NULL, '2026-08-15 14:01:23'),
(12, 'Shan Raye', 'Rosello', 'Guzman', '', 'male', '2006-06-01', 'Tarlac', 'Filipino', '', '', 'abdjbjldbalbdasd', '', 'sbofabjfbsbda', '', 'aslkndlkahslidhlijsd', '', 'Bachelor of Science in Computer Science', 'na', 'pob norte, paniqui tarlac', '2026-2027', 'none', 'Abogado dos, paniqui, tarlac', '', 's@gmail.com', NULL, NULL, 'offline', 'pending', NULL, NULL, '2026-08-17 02:37:12');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `enrolled`
--
ALTER TABLE `enrolled`
  ADD PRIMARY KEY (`student_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `enrolled`
--
ALTER TABLE `enrolled`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
