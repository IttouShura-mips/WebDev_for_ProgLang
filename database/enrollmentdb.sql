-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 13, 2026 at 03:16 PM
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
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) DEFAULT 'admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `full_name`, `username`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'System Administrator', 'admin', 'admin@icf.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'superadmin', '2026-08-11 13:41:01'),
(2, 'shan', 'shan', 'j@gmail.com', '$2y$10$9RDaREvstGPPH0795nJgIO5P48FwQkVpyCDmjiPUmzGXuXXL1Dw.S', 'admin', '2026-08-17 06:24:33'),
(3, 'shan', 'username', 'emi@gmail.com', '$2y$10$zbVUa.5SOgyefnt3IrvhnuyCEimAhMRY4c6v23HU6P8yImslVxk1u', 'admin', '2026-08-17 06:40:37'),
(4, 'shan', 'shsan', 's@gmail.com', '$2y$10$qtmzvktGqpCoSO13jdFapu7CdG4AeZCnmbh7wAmGKeKGN4x3JuEOS', 'admin', '2026-08-17 06:42:15');

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

-- --------------------------------------------------------

--
-- Table structure for table `curriculums`
--

CREATE TABLE `curriculums` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `year_level` varchar(100) DEFAULT NULL,
  `academic_year` varchar(50) DEFAULT NULL,
  `total_units` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `curriculum_semesters`
--

CREATE TABLE `curriculum_semesters` (
  `id` int(11) NOT NULL,
  `curriculum_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `curriculum_subjects`
--

CREATE TABLE `curriculum_subjects` (
  `id` int(11) NOT NULL,
  `semester_id` int(11) NOT NULL,
  `prereq` varchar(100) DEFAULT 'None',
  `code` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `lec` decimal(4,1) DEFAULT 0.0,
  `lab` decimal(4,1) DEFAULT 0.0,
  `units` decimal(4,1) DEFAULT 0.0,
  `sort_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `enrolled`
--

CREATE TABLE `enrolled` (
  `student_id` int(11) NOT NULL,
  `student_code` varchar(20) DEFAULT NULL,
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `subjects_json` text DEFAULT NULL,
  `assessment_json` text DEFAULT NULL,
  `year_level` varchar(50) DEFAULT '',
  `block` varchar(50) DEFAULT '',
  `initial_payment_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `initial_payment_method` varchar(50) DEFAULT '',
  `initial_payment_date` date DEFAULT NULL,
  `initial_payment_or` varchar(30) DEFAULT '',
  `initial_payment_gcash` varchar(30) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `enrolled`
--

INSERT INTO `enrolled` (`student_id`, `student_code`, `firstname`, `middlename`, `lastname`, `suffix`, `gender`, `birthday`, `birthplace`, `citizenship`, `civilstatus`, `employment`, `mother`, `mphone_number`, `father`, `fphone_number`, `guardian`, `gphone_number`, `course`, `major`, `school_address`, `academic_year`, `scholarship`, `full_address`, `mobile_number`, `email`, `ip_address`, `last_login`, `status`, `enrollment_status`, `payment_proof`, `requirements_files`, `created_at`, `subjects_json`, `assessment_json`, `year_level`, `block`, `initial_payment_amount`, `initial_payment_method`, `initial_payment_date`, `initial_payment_or`, `initial_payment_gcash`) VALUES
(1, '2026-0014', 'Shan Ray', 'Rosello', 'Guzman', '', 'Male', '2006-06-01', 'Paniqui, Tarlac', 'Filipino', 'Single', 'Unemployed', 'Maria ozawa', '09123456789', 'Mang Kanor', '09157955411', 'Mang kanor', '09157955411', 'BSCS', 'English', 'burgos', '2025-2026', 'ICF', 'Mang juan', '09123456789', 'Mangjuan@gmail.com', NULL, NULL, 'offline', 'approved', NULL, NULL, '2026-08-12 04:21:04', '[\r\n  {\"code\":\"CS101\",\"description\":\"Introduction to Computing\",\"units\":3,\"date\":\"MON\",\"time\":\"07:30 AM - 09:00 AM\",\"room\":\"Room 201\",\"block\":\"A\"},\r\n  {\"code\":\"CS102\",\"description\":\"Computer Programming 1\",\"units\":3,\"date\":\"TUE\",\"time\":\"09:00 AM - 10:30 AM\",\"room\":\"Lab 1\",\"block\":\"A\"},\r\n  {\"code\":\"GE101\",\"description\":\"Understanding the Self\",\"units\":3,\"date\":\"WED\",\"time\":\"10:30 AM - 12:00 PM\",\"room\":\"Room 105\",\"block\":\"A\"},\r\n  {\"code\":\"PE101\",\"description\":\"Physical Education 1\",\"units\":2,\"date\":\"THU\",\"time\":\"01:00 PM - 02:30 PM\",\"room\":\"Gym\",\"block\":\"A\"},\r\n  {\"code\":\"NSTP101\",\"description\":\"NSTP 1\",\"units\":3,\"date\":\"FRI\",\"time\":\"02:30 PM - 04:00 PM\",\"room\":\"Room 301\",\"block\":\"A\"}\r\n]', '{\r\n  \"tuition_fee\":\"12000.00\",\r\n  \"academic\":\"1500.00\",\r\n  \"computer\":\"800.00\",\r\n  \"misc_fee\":\"1200.00\",\r\n  \"nstp\":\"500.00\",\r\n  \"others\":\"0.00\",\r\n  \"total\":\"16000.00\"\r\n}', '', '', 0.00, '', NULL, '', ''),
(2, '2026-0014', 'Shan Ray', 'Rosello', 'Guzman', '', 'Male', '2004-06-25', 'Paniqui, Tarlac', 'Filipino', 'Single', 'Employed', 'CINDERELLA MAGANGCONG', '09389987162', 'BILAL MIPANGA', '0926716622', 'KENJJIE', '009737832', 'ACT', 'None', 'burgos', '2026 - 2027', 'NONE', 'Mang juan', '09123456789', 'gshanraye@yahoo.com', NULL, NULL, 'offline', 'approved', NULL, NULL, '2026-08-12 04:21:04', '[\r\n  {\"code\":\"CS101\",\"description\":\"Introduction to Computing\",\"units\":3,\"date\":\"MON\",\"time\":\"07:30 AM - 09:00 AM\",\"room\":\"Room 201\",\"block\":\"A\"},\r\n  {\"code\":\"CS102\",\"description\":\"Computer Programming 1\",\"units\":3,\"date\":\"TUE\",\"time\":\"09:00 AM - 10:30 AM\",\"room\":\"Lab 1\",\"block\":\"A\"},\r\n  {\"code\":\"GE101\",\"description\":\"Understanding the Self\",\"units\":3,\"date\":\"WED\",\"time\":\"10:30 AM - 12:00 PM\",\"room\":\"Room 105\",\"block\":\"A\"},\r\n  {\"code\":\"PE101\",\"description\":\"Physical Education 1\",\"units\":2,\"date\":\"THU\",\"time\":\"01:00 PM - 02:30 PM\",\"room\":\"Gym\",\"block\":\"A\"},\r\n  {\"code\":\"NSTP101\",\"description\":\"NSTP 1\",\"units\":3,\"date\":\"FRI\",\"time\":\"02:30 PM - 04:00 PM\",\"room\":\"Room 301\",\"block\":\"A\"}\r\n]', '{\r\n  \"tuition_fee\":\"12000.00\",\r\n  \"academic\":\"1500.00\",\r\n  \"computer\":\"800.00\",\r\n  \"misc_fee\":\"1200.00\",\r\n  \"nstp\":\"500.00\",\r\n  \"others\":\"0.00\",\r\n  \"total\":\"16000.00\"\r\n}', '', '', 0.00, '', NULL, '', ''),
(3, NULL, 'jairus', 'guirao', 'alfonso', 'jr', 'Male', '1998-07-07', 'Paniqui, Tarlac', 'Filipino', 'Married', 'Unemployed', 'teresa', '', 'johnny sins', '', 'mami oni', '', 'BSCS', 'English', 'burgos', '2026 - 2027', 'NONE', 'Mang juan', '09123456789', 'gshanraye@yahoo.com', NULL, NULL, 'offline', 'declined', NULL, NULL, '2026-08-12 04:21:04', NULL, NULL, '', '', 0.00, '', NULL, '', ''),
(5, '2026-0014', 'Shan Ray', 'Rosello', 'Guzman', '', 'Male', '2006-09-16', 'l', 'k', 'Single', 'Employed', 'll', 'l', 'l', 'lll', 'l', 'l', 'Bachelor of Technical Vocational Teacher Education', 'N/A', 'l', 'l', 'ICF', 'l2', 'l', 'e@gnail.com', NULL, NULL, 'offline', 'approved', NULL, NULL, '2026-08-12 04:21:04', '[\r\n  {\"code\":\"CS101\",\"description\":\"Introduction to Computing\",\"units\":3,\"date\":\"MON\",\"time\":\"07:30 AM - 09:00 AM\",\"room\":\"Room 201\",\"block\":\"A\"},\r\n  {\"code\":\"CS102\",\"description\":\"Computer Programming 1\",\"units\":3,\"date\":\"TUE\",\"time\":\"09:00 AM - 10:30 AM\",\"room\":\"Lab 1\",\"block\":\"A\"},\r\n  {\"code\":\"GE101\",\"description\":\"Understanding the Self\",\"units\":3,\"date\":\"WED\",\"time\":\"10:30 AM - 12:00 PM\",\"room\":\"Room 105\",\"block\":\"A\"},\r\n  {\"code\":\"PE101\",\"description\":\"Physical Education 1\",\"units\":2,\"date\":\"THU\",\"time\":\"01:00 PM - 02:30 PM\",\"room\":\"Gym\",\"block\":\"A\"},\r\n  {\"code\":\"NSTP101\",\"description\":\"NSTP 1\",\"units\":3,\"date\":\"FRI\",\"time\":\"02:30 PM - 04:00 PM\",\"room\":\"Room 301\",\"block\":\"A\"}\r\n]', '{\r\n  \"tuition_fee\":\"12000.00\",\r\n  \"academic\":\"1500.00\",\r\n  \"computer\":\"800.00\",\r\n  \"misc_fee\":\"1200.00\",\r\n  \"nstp\":\"500.00\",\r\n  \"others\":\"0.00\",\r\n  \"total\":\"16000.00\"\r\n}', '', '', 0.00, '', NULL, '', ''),
(9, NULL, 'shan', 'rosello', 'raye', '', 'male', '2014-06-13', 'mhgjfhgd', 'njhgfbfcv', '', '', '8cvbcvnbm', '09157955411', 'sdfgfdchtfw34', '09157955411', 'qr3w4ts5dht', '09157955411', 'Bachelor of Science in Computer Science', 'na', 'wq5e243tey5hr6ftcg', '2026-2027', 'academic', '123qrsgrfcxf', '09157955411', 'j@gmail.com', NULL, NULL, 'offline', 'pending', NULL, NULL, '2026-08-12 04:21:04', NULL, NULL, '', '', 0.00, '', NULL, '', ''),
(10, '2026-0015', 'shan', 'rosello', 'guzman', '', 'male', '2016-06-04', 'gjyf', 'jkbgui', '', '', 'hkjfghfxv', '09157955411', 'jlikugjyh', '09157955411', 'okjihugythg', '09157955411', 'Bachelor of Science in Computer Science', 'na', 'jiuyhfctfyui', '2026-2027', 'jihuyg', 'khkjg', '09157955411', 'k@gmail.com', NULL, NULL, 'offline', 'approved', NULL, NULL, '2026-08-12 05:49:15', NULL, NULL, '', '', 0.00, '', NULL, '', ''),
(11, '2026-0014', 'Shan Ray', 'Rosello', 'Guzman', '', 'male', '2024-10-04', 'asd', 'Szdxc', '', '', 'zsadfg', '', 'dfdcv', '', 'Xzcxv', '', 'Bachelor of Science in Tourism Management', 'english', 'jhb', '2027-2028', 'jionjkh', 'jkhjjkjbnjiokklnjk', '', 'zj@gmail.com', NULL, NULL, 'offline', 'approved', NULL, NULL, '2026-08-15 14:01:23', '[\r\n  {\"code\":\"CS101\",\"description\":\"Introduction to Computing\",\"units\":3,\"date\":\"MON\",\"time\":\"07:30 AM - 09:00 AM\",\"room\":\"Room 201\",\"block\":\"A\"},\r\n  {\"code\":\"CS102\",\"description\":\"Computer Programming 1\",\"units\":3,\"date\":\"TUE\",\"time\":\"09:00 AM - 10:30 AM\",\"room\":\"Lab 1\",\"block\":\"A\"},\r\n  {\"code\":\"GE101\",\"description\":\"Understanding the Self\",\"units\":3,\"date\":\"WED\",\"time\":\"10:30 AM - 12:00 PM\",\"room\":\"Room 105\",\"block\":\"A\"},\r\n  {\"code\":\"PE101\",\"description\":\"Physical Education 1\",\"units\":2,\"date\":\"THU\",\"time\":\"01:00 PM - 02:30 PM\",\"room\":\"Gym\",\"block\":\"A\"},\r\n  {\"code\":\"NSTP101\",\"description\":\"NSTP 1\",\"units\":3,\"date\":\"FRI\",\"time\":\"02:30 PM - 04:00 PM\",\"room\":\"Room 301\",\"block\":\"A\"}\r\n]', '{\r\n  \"tuition_fee\":\"12000.00\",\r\n  \"academic\":\"1500.00\",\r\n  \"computer\":\"800.00\",\r\n  \"misc_fee\":\"1200.00\",\r\n  \"nstp\":\"500.00\",\r\n  \"others\":\"0.00\",\r\n  \"total\":\"16000.00\"\r\n}', '', '', 0.00, '', NULL, '', ''),
(12, '2026-0007', 'Shan Raye', 'Rosello', 'Guzman', '', 'male', '2006-06-01', 'Tarlac', 'Filipino', '', '', 'abdjbjldbalbdasd', '', 'sbofabjfbsbda', '', 'aslkndlkahslidhlijsd', '', 'Bachelor of Science in Computer Science', 'na', 'pob norte, paniqui tarlac', '2026-2027', 'none', 'Abogado dos, paniqui, tarlac', '', 's@gmail.com', NULL, NULL, 'offline', 'approved', NULL, NULL, '2026-08-17 02:37:12', NULL, NULL, '', '', 0.00, '', NULL, '', ''),
(13, '2026-0011', 'WDAESRDTFGHWDEFRETR', 'ADSFD', 'SDFDGD', 'SD', 'male', '2010-07-18', 'WQDEFREG', 'DEFSR', '', '', 'ADWEFSRDGF', '', 'WDD', '', 'WDEWRE', '', 'Bachelor of Science in Computer Science', 'na', 'lknAIURGVBIQEWU', '2026-2027', 'none', 'lkdnlwndnwlkdnlqwndkqw', '', 'e@gmail.com', NULL, NULL, 'offline', 'approved', NULL, NULL, '2026-08-17 03:16:34', NULL, NULL, '', '', 0.00, '', NULL, '', ''),
(14, '2026-0010', 'ligfdxfghdyftuiyugfgfxcv', 'vccgjkghhvbcvcn', 'lkjgghfyuktg', 'j', 'male', '2011-06-17', 'mkjgfxcv', 'klhikugjyfgchbfxv', 'Single', 'Employed', ',nkjghfgfxvc', '09207497149', ',jkguyjfthcfb', '09639207491', 'jrstdtrtujghnb', '09639207491', 'Bachelor of Science in Computer Science', 'na', 'lkhutiytsreastyurtig', '2026-2027', 'none', 'mljhjycgbfc', '09639207491', 'j22@gmail.com', '::1', NULL, 'offline', 'approved', NULL, '[\"uploads\\/requirements\\/6a82893b7c5a2_images.jpg\"]', '2026-08-17 04:08:27', NULL, NULL, '', '', 0.00, '', NULL, '', ''),
(15, '2026-0006', 'Shan', 'bdakjsbdjk', 'raye', 'sjfbabjf', 'male', '2009-06-01', 'dWAESRGDTHFYG', 'wezq23kbln', 'Single', 'Employed', 'dewz,', '09203901930', 'dexwkrdefsw', '09203901930', 'qWAZSESdk', '09203901930', 'Bachelor of Science in Computer Science', 'na', 'aswderftfyhgfjbuk', '2026-2027', 'none', 'aswedzjkbhgcftdr', '09203901930', 'jkk@gmail.com', '::1', NULL, 'offline', 'approved', NULL, '[\"uploads\\/requirements\\/6a828ab70844e_images.jpg\"]', '2026-08-17 04:14:47', NULL, NULL, '', '', 0.00, '', NULL, '', ''),
(16, '2026-0009', 'qw', 'few', 'eq3tr5yfujkbwerwefrg', '', 'male', '2026-08-23', '2', 'qa3resg', 'Single', 'Employed', 'qweargstrdyhg', '09203901930', 'qewregrtrewaresrgdtfgh', '09203901930', 'werewsrgdtf', '09203901930', 'Bachelor of Technical Vocational Teacher Education', 'mathematics', 'AFWAEGSRDHTYGUHKJ', '2026-2027', 'academic', 'WDQEFRGDTFHGJ', '09203901930', 'k@gmail.com', '::1', NULL, 'offline', 'approved', NULL, NULL, '2026-08-17 04:17:34', NULL, NULL, '', '', 0.00, '', NULL, '', ''),
(17, '2026-0008', 'wakjefuihrghrfhweuif', 'dsajkhdsakhdsa', 'asohadwjkhdsajkdsajbkdsa', 'qwe', 'male', '2022-03-02', 'dasjkasdjkbsad', 'dajbsadkbhsad', 'Single', 'Unemployed', 'saclnkdscnljdcs', '09157955411', 'efnkleadnljdwa', '09157955411', 'alnkadelnjldwa', '09157955411', 'Bachelor of Science in Computer Science', 'na', 'dwalnjadwlnjdwaljbadw', '2026-2027', 'none', 'ajbsaljbdsasa', '09157955411', 'j@gmail.com', '::1', NULL, 'offline', 'approved', NULL, '[\"uploads\\/requirements\\/6a828c98dea38_images.jpg\"]', '2026-08-17 04:22:48', NULL, NULL, '', '', 0.00, '', NULL, '', ''),
(18, '2026-0005', 'qkwfekauhoie', 'awodhioawh', 'awdwhowhadoiwsh', 'aldhoiwhdoi', 'male', '2009-09-16', 'ajkbdfoueahd', 'ousgzdoicgd', 'Single', 'Employed', 'opqwjhepiweepoiw', '09157955411', 'selkfheidhwahwa', '09157955411', 'oj', '09157955411', 'Bachelor of Science in Public Administration', 'na', 'alkhoidhwiod', '2026-2027', 'academic', 'ahdoiqhawdi', '09157955411', 'k@gmail.com', '::1', NULL, 'offline', 'approved', NULL, '[\"uploads\\/requirements\\/6a828e9045e89_images.jpg\"]', '2026-08-17 04:31:12', NULL, NULL, '', '', 0.00, '', NULL, '', ''),
(19, '2026-0003', 'ss', '', 's', '', 'male', '2023-09-04', '091092', 'slhakhj', 'Single', 'Employed', 'asefsrgdthfvwaefsr', '09333333333', 'aesrdfg', '09333333333', 'aesrfdg', '09333333333', 'Bachelor of Science in Computer Science', 'na', '3333333333333333', '2026-2027', 'academic', '33333333333333', '09333333333', '1@gmail.com', '::1', NULL, 'offline', 'approved', NULL, '[\"uploads\\/requirements\\/6a828eed45a1b_images.jpg\"]', '2026-08-17 04:32:45', NULL, NULL, '', '', 0.00, '', NULL, '', ''),
(20, '2026-0002', 'Almadin', 'Nor', 'Mipanga', '', 'male', '2004-06-27', 'samlkle', 'Filipino', 'Single', 'Unemployed', 'sample', '09343243242', 'SAMPLE', '09343243243', 'SADM', '09324242324', 'Bachelor of Science in Computer Science', 'na', 'SAMPLE TET', '2026-2027', 'none', 'SDSAFFDSG', '09234234234', 'DMIPANGA@GMAIL.COM', '::1', NULL, 'offline', 'approved', NULL, '[\"uploads\\/requirements\\/6a8e87fca6b52_IMG_6020.jpg\"]', '2026-08-26 06:30:20', NULL, NULL, '', '', 0.00, '', NULL, '', ''),
(21, '2026-0001', 'sdfdgb', '', 'sdfgbv', '', 'male', '1999-04-21', 'sdfesfefef', 'sefsefesf', 'Single', 'Employed', 'ehfuiwehf', '09157955411', 'esfesfe', '09993094033', 'efsfefsef', '09432432423', 'Bachelor of Science in Public Administration', 'english', 'nlknlknlkn', '2028-2029', 'academic', '34324324324324', '09324342343', 'k@gmail.com', '::1', NULL, 'offline', 'approved', NULL, '[\"uploads\\/requirements\\/6a9558a759e47_Canva_link.docx\"]', '2026-08-31 10:34:15', NULL, NULL, '', '', 0.00, '', NULL, '', ''),
(22, '2026-0004', 'Shan Raye', 'Rosello', 'Guzman', '', 'male', '2006-06-01', 'Tarlac, Paniqui', 'Filo', 'Single', 'Unemployed', 'ljbgyufuyfyfuyf', '09132121323', 'bjguigiugiugipu', '09231232131', 'jhfyufgyguihgouygu', '09213123123', 'Bachelor of Science in Computer Science', 'na', 'hiuweghfuigewaifdsgfiug', '2026-2027', 'none', 'bqwdglsajkdbLCDdgidlseikbfy', '09123123123', 'Sweetkendl081@gmail.com', '::1', NULL, 'offline', 'approved', NULL, '[\"uploads\\/requirements\\/6a961f81488df_Student_Handbook.docx\"]', '2026-09-01 00:42:41', NULL, NULL, '', '', 0.00, '', NULL, '', ''),
(23, '2026-0012', 'kennjie', 'jeremias', 'hiyao', '', 'male', '2001-07-14', 'paniqui', 'filipino', 'Single', 'Unemployed', 'angie', '09444646643', 'joseph', '09544464787', 'amy', '09478789798', 'Bachelor of Science in Computer Science', 'na', 'gffhfhgugjgjhghghghg', '2026-2027', 'tyryryryryr', 'ryghjgjhfhfh', '09454545343', 'sweetkendl081@gmail.com', '::1', NULL, 'offline', 'approved', NULL, '[\"uploads\\/requirements\\/6a9e50e4dbc13_Apartrent_Apartment_Management_System.pdf\"]', '2026-09-07 05:51:32', NULL, NULL, '', '', 0.00, '', NULL, '', ''),
(24, '2026-0013', 'sample', 'samplee', 'sample', '', 'male', '2010-11-14', 'paiwui', 'filipino', 'Single', 'Employed', 'sample', '09989083243', 'smaple', '09232423234', 'sample', '09234234234', 'Bachelor of Secondary Education', 'english', 'sapniqui', '2026-2027', 'academic', 'paniqui', '09675776543', 'sweetkendl081@gmail.com', '::1', NULL, 'offline', 'approved', NULL, NULL, '2026-09-07 06:09:33', NULL, NULL, '', '', 0.00, '', NULL, '', ''),
(25, '2026-0016', 'Shan Raye', 'Rosello', 'Guzman', '', 'male', '2006-06-01', 'kggjygjhvaeae', 'efwefse', 'Single', 'Employed', 'wefeaewfeaw', '09234324324', 'awefawefwa', '09324234324', 'efawefawef', '09324234324', 'Bachelor of Science in Computer Science', 'na', 'rawefdfesfawe', '2026-2027', 'tes', 'eafwefaefaw', '09432143241', 'sweetkendl081@gmail.com', '::1', NULL, 'offline', 'approved', NULL, '[\"uploads\\/requirements\\/6aa69993da795_GUZMAN__SHAN_RAYE_-_ICF_LOGO.png\"]', '2026-09-13 12:39:47', '[{\"code\":\"wqeqwewkj\",\"description\":\"qweqweqwe\",\"units\":2,\"day_time\":\"e123123\",\"room\":\"123123\",\"block\":\"213123\"},{\"code\":\"wdasdsadsa\",\"description\":\"asdsadsa\",\"units\":16,\"day_time\":\"213213213\",\"room\":\"213123\",\"block\":\"1231232\"}]', '{\"tuition_fee\":\"9.00\",\"academic\":\"60.56\",\"computer\":\"5,656.55\",\"misc_fee\":\"56.56\",\"nstp\":\"5,656.56\",\"others\":\"656.56\",\"total\":\"\\u20b1 12,095.79\"}', '', '', 0.00, '', NULL, '', ''),
(26, '2026-0014', 'Shan Ray', 'Rosello', 'Guzman', '', 'male', '2006-08-11', 'Paniqui,Tarlac', 'Filipino', 'Single', 'Unemployed', 'Angie Jeremias', '09565656565', 'Joseph Hiyao', '09565465464', 'Amy Garcia', '09565645656', 'Bachelor of Science in Computer Science', 'na', 'Purok 6 Brgy Canan,Paniqui,Tarlac', '2026-2027', 'none', 'Purok 6 Brgy Canan,Paniqui,Tarlac', '09942395815', 'kenhiyao@gmail.com', '::1', NULL, 'offline', 'approved', NULL, '[\"uploads\\/requirements\\/6ac5d7467bae8_ICFLOGO.jpg\"]', '2026-10-07 05:23:18', '[\r\n  {\"code\":\"CS101\",\"description\":\"Introduction to Computing\",\"units\":3,\"date\":\"MON\",\"time\":\"07:30 AM - 09:00 AM\",\"room\":\"Room 201\",\"block\":\"A\"},\r\n  {\"code\":\"CS102\",\"description\":\"Computer Programming 1\",\"units\":3,\"date\":\"TUE\",\"time\":\"09:00 AM - 10:30 AM\",\"room\":\"Lab 1\",\"block\":\"A\"},\r\n  {\"code\":\"GE101\",\"description\":\"Understanding the Self\",\"units\":3,\"date\":\"WED\",\"time\":\"10:30 AM - 12:00 PM\",\"room\":\"Room 105\",\"block\":\"A\"},\r\n  {\"code\":\"PE101\",\"description\":\"Physical Education 1\",\"units\":2,\"date\":\"THU\",\"time\":\"01:00 PM - 02:30 PM\",\"room\":\"Gym\",\"block\":\"A\"},\r\n  {\"code\":\"NSTP101\",\"description\":\"NSTP 1\",\"units\":3,\"date\":\"FRI\",\"time\":\"02:30 PM - 04:00 PM\",\"room\":\"Room 301\",\"block\":\"A\"}\r\n]', '{\r\n  \"tuition_fee\":\"12000.00\",\r\n  \"academic\":\"1500.00\",\r\n  \"computer\":\"800.00\",\r\n  \"misc_fee\":\"1200.00\",\r\n  \"nstp\":\"500.00\",\r\n  \"others\":\"0.00\",\r\n  \"total\":\"16000.00\"\r\n}', '', '', 0.00, '', NULL, '', ''),
(27, '2026-0014', 'Shan Ray', 'Rosello', 'Guzman', '', 'male', '2004-06-25', 'PANIQUI', 'FILIPINO', 'Single', 'Unemployed', 'CINDERELLA MAGANGCONG', '09696876876', 'BILAL MIPANGA', '09876878785', 'AHMAD AMIN MIPANGA', '09858658655', 'Bachelor of Science in Computer Science', 'na', 'PALARCA ST SITIO VEGA POB NORTE', '2026-2027', 'none', '1294 SITIO VEGA', '09087287897', 'dmipanga25@gmail.com', '::1', NULL, 'offline', 'approved', NULL, NULL, '2026-10-07 05:53:29', '[\r\n  {\"code\":\"CS101\",\"description\":\"Introduction to Computing\",\"units\":3,\"date\":\"MON\",\"time\":\"07:30 AM - 09:00 AM\",\"room\":\"Room 201\",\"block\":\"A\"},\r\n  {\"code\":\"CS102\",\"description\":\"Computer Programming 1\",\"units\":3,\"date\":\"TUE\",\"time\":\"09:00 AM - 10:30 AM\",\"room\":\"Lab 1\",\"block\":\"A\"},\r\n  {\"code\":\"GE101\",\"description\":\"Understanding the Self\",\"units\":3,\"date\":\"WED\",\"time\":\"10:30 AM - 12:00 PM\",\"room\":\"Room 105\",\"block\":\"A\"},\r\n  {\"code\":\"PE101\",\"description\":\"Physical Education 1\",\"units\":2,\"date\":\"THU\",\"time\":\"01:00 PM - 02:30 PM\",\"room\":\"Gym\",\"block\":\"A\"},\r\n  {\"code\":\"NSTP101\",\"description\":\"NSTP 1\",\"units\":3,\"date\":\"FRI\",\"time\":\"02:30 PM - 04:00 PM\",\"room\":\"Room 301\",\"block\":\"A\"}\r\n]', '{\r\n  \"tuition_fee\":\"12000.00\",\r\n  \"academic\":\"1500.00\",\r\n  \"computer\":\"800.00\",\r\n  \"misc_fee\":\"1200.00\",\r\n  \"nstp\":\"500.00\",\r\n  \"others\":\"0.00\",\r\n  \"total\":\"16000.00\"\r\n}', '', '', 0.00, '', NULL, '', ''),
(28, '2026-0014', 'Shan Ray', 'Rosello', 'Guzman', '', 'male', '2006-06-01', 'Tarlac', 'Filipino', 'Single', 'Unemployed', 'ma. carlota r. guzman', '09234324324', 'Romans D. Guzman', '09234324324', 'ma. carlota r. guzman', '09324234324', 'Bachelor of Science in Computer Science', 'na', 'paniqui, tarlac', '2027-2028', 'none', 'Abogado Dos, Paniqui Tarlac', '09234234324', 'sweetkendl081@gmail.com', '::1', NULL, 'offline', 'approved', NULL, '[\"uploads\\/requirements\\/6acb78cadffda_GUZMAN__SHAN_RAYE_-_ICF_LOGO.png\"]', '2026-10-11 11:53:46', '[\r\n  {\"code\":\"CS101\",\"description\":\"Introduction to Computing\",\"units\":3,\"date\":\"MON\",\"time\":\"07:30 AM - 09:00 AM\",\"room\":\"Room 201\",\"block\":\"A\"},\r\n  {\"code\":\"CS102\",\"description\":\"Computer Programming 1\",\"units\":3,\"date\":\"TUE\",\"time\":\"09:00 AM - 10:30 AM\",\"room\":\"Lab 1\",\"block\":\"A\"},\r\n  {\"code\":\"GE101\",\"description\":\"Understanding the Self\",\"units\":3,\"date\":\"WED\",\"time\":\"10:30 AM - 12:00 PM\",\"room\":\"Room 105\",\"block\":\"A\"},\r\n  {\"code\":\"PE101\",\"description\":\"Physical Education 1\",\"units\":2,\"date\":\"THU\",\"time\":\"01:00 PM - 02:30 PM\",\"room\":\"Gym\",\"block\":\"A\"},\r\n  {\"code\":\"NSTP101\",\"description\":\"NSTP 1\",\"units\":3,\"date\":\"FRI\",\"time\":\"02:30 PM - 04:00 PM\",\"room\":\"Room 301\",\"block\":\"A\"}\r\n]', '{\r\n  \"tuition_fee\":\"12000.00\",\r\n  \"academic\":\"1500.00\",\r\n  \"computer\":\"800.00\",\r\n  \"misc_fee\":\"1200.00\",\r\n  \"nstp\":\"500.00\",\r\n  \"others\":\"0.00\",\r\n  \"total\":\"16000.00\"\r\n}', '', '', 0.00, '', NULL, '', ''),
(29, '2026-0014', 'Shan Ray', 'Rosello', 'Guzman', '', 'male', '2006-06-01', 'Tarlac', 'Filipino', 'Single', 'Unemployed', 'Ma. Carlota R. Guzman', '09324823984', 'Ma. Carlota R. Guzman', '09324083402', 'Ma. Carlota R. Guzman', '09342343234', 'Bachelor of Science in Computer Science', 'na', 'PobNorte', '2026-2027', 'none', 'Abogado dos, Paniqui, Tarlac', '09809809890', 'sweetkendl081@gmail.com', '::1', NULL, 'offline', 'approved', NULL, '[\"uploads\\/requirements\\/6ace0dff42f3a_1000327781.jpg\"]', '2026-10-13 10:54:55', '[\r\n  {\"code\":\"CS101\",\"description\":\"Introduction to Computing\",\"units\":3,\"date\":\"MON\",\"time\":\"07:30 AM - 09:00 AM\",\"room\":\"Room 201\",\"block\":\"A\"},\r\n  {\"code\":\"CS102\",\"description\":\"Computer Programming 1\",\"units\":3,\"date\":\"TUE\",\"time\":\"09:00 AM - 10:30 AM\",\"room\":\"Lab 1\",\"block\":\"A\"},\r\n  {\"code\":\"GE101\",\"description\":\"Understanding the Self\",\"units\":3,\"date\":\"WED\",\"time\":\"10:30 AM - 12:00 PM\",\"room\":\"Room 105\",\"block\":\"A\"},\r\n  {\"code\":\"PE101\",\"description\":\"Physical Education 1\",\"units\":2,\"date\":\"THU\",\"time\":\"01:00 PM - 02:30 PM\",\"room\":\"Gym\",\"block\":\"A\"},\r\n  {\"code\":\"NSTP101\",\"description\":\"NSTP 1\",\"units\":3,\"date\":\"FRI\",\"time\":\"02:30 PM - 04:00 PM\",\"room\":\"Room 301\",\"block\":\"A\"}\r\n]', '{\r\n  \"tuition_fee\":\"12000.00\",\r\n  \"academic\":\"1500.00\",\r\n  \"computer\":\"800.00\",\r\n  \"misc_fee\":\"1200.00\",\r\n  \"nstp\":\"500.00\",\r\n  \"others\":\"0.00\",\r\n  \"total\":\"16000.00\"\r\n}', '', '', 0.00, '', NULL, '', ''),
(30, '2026-0014', 'Shan Ray', 'Rosello', 'Guzman', '', 'male', '2008-07-18', 'liuagadliba', 'aksbduvfu', 'Single', 'Employed', 'aklsjeidgfb', '09980980989', 'askdhhgf', '09908098908', 'alsdhfgk', '09980989080', 'Bachelor of Science in Computer Science', 'na', 'albsiofai', '2026-2027', 'none', 'asldhjfbakjevof', '09098908098', 'sweetkendl081@gmail.com', '::1', NULL, 'offline', 'approved', NULL, '[\"uploads\\/requirements\\/6ace1319f071e_1000327781.jpg\"]', '2026-10-13 11:16:41', '[\r\n  {\"code\":\"CS101\",\"description\":\"Introduction to Computing\",\"units\":3,\"date\":\"MON\",\"time\":\"07:30 AM - 09:00 AM\",\"room\":\"Room 201\",\"block\":\"A\"},\r\n  {\"code\":\"CS102\",\"description\":\"Computer Programming 1\",\"units\":3,\"date\":\"TUE\",\"time\":\"09:00 AM - 10:30 AM\",\"room\":\"Lab 1\",\"block\":\"A\"},\r\n  {\"code\":\"GE101\",\"description\":\"Understanding the Self\",\"units\":3,\"date\":\"WED\",\"time\":\"10:30 AM - 12:00 PM\",\"room\":\"Room 105\",\"block\":\"A\"},\r\n  {\"code\":\"PE101\",\"description\":\"Physical Education 1\",\"units\":2,\"date\":\"THU\",\"time\":\"01:00 PM - 02:30 PM\",\"room\":\"Gym\",\"block\":\"A\"},\r\n  {\"code\":\"NSTP101\",\"description\":\"NSTP 1\",\"units\":3,\"date\":\"FRI\",\"time\":\"02:30 PM - 04:00 PM\",\"room\":\"Room 301\",\"block\":\"A\"}\r\n]', '{\r\n  \"tuition_fee\":\"12000.00\",\r\n  \"academic\":\"1500.00\",\r\n  \"computer\":\"800.00\",\r\n  \"misc_fee\":\"1200.00\",\r\n  \"nstp\":\"500.00\",\r\n  \"others\":\"0.00\",\r\n  \"total\":\"16000.00\"\r\n}', '', '', 0.00, 'ewallet', '2026-10-13', 'OR-20261013-79ABC', '09834083403');

-- --------------------------------------------------------

--
-- Table structure for table `instructors`
--

CREATE TABLE `instructors` (
  `id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `department` varchar(50) NOT NULL,
  `status` enum('active','former') DEFAULT 'active',
  `profile_pic` varchar(255) DEFAULT '',
  `gender` varchar(20) DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `citizenship` varchar(50) DEFAULT NULL,
  `civil_status` varchar(30) DEFAULT NULL,
  `employment` varchar(30) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `instructors`
--

INSERT INTO `instructors` (`id`, `full_name`, `department`, `status`, `profile_pic`, `gender`, `birthday`, `citizenship`, `civil_status`, `employment`, `email`, `phone`, `address`, `created_at`) VALUES
(1, 'EXAMPOL', 'CICS', 'active', '', 'Male', NULL, 'Filipino', 'Single', 'Full-Time', 'EXAMPOL@gmail.com', '2138-128-3018', 'enifnaoinejifaoijb', '2026-09-23 04:59:07');

-- --------------------------------------------------------

--
-- Table structure for table `instructor_schedules`
--

CREATE TABLE `instructor_schedules` (
  `id` int(11) NOT NULL,
  `instructor_id` int(11) NOT NULL,
  `day` varchar(50) DEFAULT NULL,
  `time` varchar(100) DEFAULT NULL,
  `room` varchar(50) DEFAULT NULL,
  `subject` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `instructor_subjects`
--

CREATE TABLE `instructor_subjects` (
  `id` int(11) NOT NULL,
  `instructor_id` int(11) NOT NULL,
  `code` varchar(50) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `units` decimal(4,1) DEFAULT 0.0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `instructor_subjects`
--

INSERT INTO `instructor_subjects` (`id`, `instructor_id`, `code`, `title`, `units`) VALUES
(1, 1, '23123', 'CICS', 3.0);

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

-- --------------------------------------------------------

--
-- Table structure for table `staff_users`
--

CREATE TABLE `staff_users` (
  `id` int(11) NOT NULL,
  `user_id` varchar(20) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `role` varchar(50) DEFAULT 'Instructor',
  `ip_address` varchar(45) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `status` enum('online','offline') DEFAULT 'offline',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_assessments`
--

CREATE TABLE `student_assessments` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `tuition_fee` decimal(10,2) DEFAULT 14070.00,
  `academic_fee` decimal(10,2) DEFAULT 1200.00,
  `computer_fee` decimal(10,2) DEFAULT 1850.00,
  `misc_fee` decimal(10,2) DEFAULT 2100.00,
  `nstp_fee` decimal(10,2) DEFAULT 0.00,
  `others_fee` decimal(10,2) DEFAULT 420.00,
  `total` decimal(10,2) DEFAULT 19640.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_assessments`
--

INSERT INTO `student_assessments` (`id`, `student_id`, `tuition_fee`, `academic_fee`, `computer_fee`, `misc_fee`, `nstp_fee`, `others_fee`, `total`, `created_at`) VALUES
(1, 15, 14070.00, 1200.00, 1850.00, 2100.00, 0.00, 420.00, 19640.00, '2026-09-07 04:18:42'),
(2, 12, 14070.00, 1200.00, 1850.00, 2100.00, 0.00, 420.00, 19640.00, '2026-09-07 04:30:52');

-- --------------------------------------------------------

--
-- Table structure for table `student_curriculum_assignments`
--

CREATE TABLE `student_curriculum_assignments` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `curriculum_code` varchar(50) DEFAULT NULL,
  `curriculum_title` varchar(255) DEFAULT NULL,
  `subjects_json` longtext DEFAULT NULL,
  `schedule_json` longtext DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_curriculum_assignments`
--

INSERT INTO `student_curriculum_assignments` (`id`, `student_id`, `curriculum_code`, `curriculum_title`, `subjects_json`, `schedule_json`, `created_at`) VALUES
(1, 15, 'BSCS-2024', 'Bachelor of Science in Computer Science', '[{\"name\":\"1st Year - 1st Semester\",\"subjects\":[{\"code\":\"GECCOED101YG1\",\"title\":\"Understanding the Self\",\"lec\":3,\"lab\":0,\"units\":3},{\"code\":\"GECPCOM101YG1\",\"title\":\"Purposive Communication\",\"lec\":3,\"lab\":0,\"units\":3},{\"code\":\"GECMATH101YG1\",\"title\":\"Mathematics in the Modern World\",\"lec\":3,\"lab\":0,\"units\":3},{\"code\":\"GECRPHIL101YG1\",\"title\":\"Readings in Philippine History\",\"lec\":3,\"lab\":0,\"units\":3},{\"code\":\"GECETHIC101YG1\",\"title\":\"Ethics\",\"lec\":3,\"lab\":0,\"units\":3},{\"code\":\"GECSCTS101YG1\",\"title\":\"Science, Technology and Society\",\"lec\":3,\"lab\":0,\"units\":3},{\"code\":\"CC1\",\"title\":\"Introduction to Computing\",\"lec\":2,\"lab\":3,\"units\":3},{\"code\":\"PC1\",\"title\":\"Computer Programming 1\",\"lec\":2,\"lab\":3,\"units\":3},{\"code\":\"NSTP101YG1\",\"title\":\"CWTS/ROTC 1\",\"lec\":3,\"lab\":0,\"units\":3},{\"code\":\"PE1\",\"title\":\"Physical Activities Towards Health and Fitness 1\",\"lec\":2,\"lab\":0,\"units\":2}]},{\"name\":\"1st Year - 2nd Semester\",\"subjects\":[{\"code\":\"GECRIZAL101YG1\",\"title\":\"Rizal\'s Life and Works\",\"lec\":3,\"lab\":0,\"units\":3},{\"code\":\"GECARTAP101YG2\",\"title\":\"Art Appreciation\",\"lec\":3,\"lab\":0,\"units\":3},{\"code\":\"CC2\",\"title\":\"Computer Programming 2\",\"lec\":2,\"lab\":3,\"units\":3},{\"code\":\"CC3\",\"title\":\"Discrete Structures\",\"lec\":3,\"lab\":0,\"units\":3},{\"code\":\"PC2\",\"title\":\"Data Structures and Algorithms\",\"lec\":2,\"lab\":3,\"units\":3},{\"code\":\"PCOMPEI101\",\"title\":\"Web Systems and Technologies 1\",\"lec\":2,\"lab\":3,\"units\":3},{\"code\":\"PC3\",\"title\":\"Information Assurance and Security 1\",\"lec\":2,\"lab\":3,\"units\":3},{\"code\":\"NSTP102YG1\",\"title\":\"CWTS/ROTC 2\",\"lec\":3,\"lab\":0,\"units\":3},{\"code\":\"PE2\",\"title\":\"Physical Activities Towards Health and Fitness 2\",\"lec\":2,\"lab\":0,\"units\":2}]},{\"name\":\"2nd Year - 1st Semester\",\"subjects\":[{\"code\":\"GECFIL101YG1\",\"title\":\"Komunikasyon sa Akademikong Filipino\",\"lec\":3,\"lab\":0,\"units\":3},{\"code\":\"CC4\",\"title\":\"Algorithms and Complexity\",\"lec\":3,\"lab\":0,\"units\":3},{\"code\":\"COMPP101YG2\",\"title\":\"Information Management\",\"lec\":3,\"lab\":0,\"units\":3},{\"code\":\"PC4\",\"title\":\"Networks and Communication 1\",\"lec\":2,\"lab\":3,\"units\":3},{\"code\":\"PC5\",\"title\":\"Object-Oriented Programming\",\"lec\":2,\"lab\":3,\"units\":3},{\"code\":\"PC6\",\"title\":\"Information Assurance and Security 2\",\"lec\":2,\"lab\":3,\"units\":3},{\"code\":\"PE3\",\"title\":\"Physical Activities Towards Health and Fitness 3\",\"lec\":2,\"lab\":0,\"units\":2}]},{\"name\":\"2nd Year - 2nd Semester\",\"subjects\":[{\"code\":\"GECFIL102YG1\",\"title\":\"Filipino sa Iba\'t Ibang Larangan\",\"lec\":3,\"lab\":0,\"units\":3},{\"code\":\"CC5\",\"title\":\"Applications Development\",\"lec\":3,\"lab\":0,\"units\":3},{\"code\":\"CC6\",\"title\":\"Social and Professional Issues\",\"lec\":3,\"lab\":0,\"units\":3},{\"code\":\"PC7\",\"title\":\"Automata Theory and Formal Languages\",\"lec\":3,\"lab\":0,\"units\":3},{\"code\":\"PC8\",\"title\":\"Architecture and Organization\",\"lec\":3,\"lab\":0,\"units\":3},{\"code\":\"PC9\",\"title\":\"Software Engineering 1\",\"lec\":3,\"lab\":0,\"units\":3},{\"code\":\"PE4\",\"title\":\"Physical Activities Towards Health and Fitness 4\",\"lec\":2,\"lab\":0,\"units\":2}]},{\"name\":\"3rd Year - 1st Semester\",\"subjects\":[{\"code\":\"GECSCP101YG3\",\"title\":\"The Contemporary World\",\"lec\":3,\"lab\":0,\"units\":3},{\"code\":\"PCOMINT101YG3\",\"title\":\"Technopreneurship\",\"lec\":3,\"lab\":0,\"units\":3},{\"code\":\"PC10\",\"title\":\"Information Assurance and Security\",\"lec\":3,\"lab\":0,\"units\":3},{\"code\":\"PC11\",\"title\":\"Programming Languages\",\"lec\":3,\"lab\":0,\"units\":3},{\"code\":\"PC12\",\"title\":\"Software Engineering 2\",\"lec\":3,\"lab\":0,\"units\":3},{\"code\":\"PC13\",\"title\":\"Intelligent Systems\",\"lec\":3,\"lab\":0,\"units\":3}]},{\"name\":\"3rd Year - 2nd Semester\",\"subjects\":[{\"code\":\"CCSC1013YG2\",\"title\":\"Multimedia\",\"lec\":3,\"lab\":0,\"units\":3}]},{\"name\":\"4th Year - 1st Semester\",\"subjects\":[{\"code\":\"CCSC141YG1\",\"title\":\"Human Computer Interaction\",\"lec\":3,\"lab\":0,\"units\":3},{\"code\":\"CCSC154YG1\",\"title\":\"Operating Systems\",\"lec\":3,\"lab\":0,\"units\":3},{\"code\":\"CCSC155YG1\",\"title\":\"Social Issues and Professional Ethics\",\"lec\":3,\"lab\":0,\"units\":3},{\"code\":\"CCSC174YG1\",\"title\":\"Thesis Writing 1\",\"lec\":3,\"lab\":0,\"units\":3}]},{\"name\":\"4th Year - 2nd Semester\",\"subjects\":[{\"code\":\"CCSC158YG2\",\"title\":\"Networks and Communication\",\"lec\":3,\"lab\":0,\"units\":3},{\"code\":\"CCSC184YG2\",\"title\":\"Thesis Writing 2\",\"lec\":3,\"lab\":0,\"units\":3}]}]', '[{\"day\":\"MON / WED\",\"time\":\"7:30 AM - 9:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"GECCOED101YG1\",\"description\":\"Understanding the Self\"},{\"day\":\"MON / WED\",\"time\":\"8:30 AM - 10:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"GECPCOM101YG1\",\"description\":\"Purposive Communication\"},{\"day\":\"MON / WED\",\"time\":\"9:30 AM - 11:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"GECMATH101YG1\",\"description\":\"Mathematics in the Modern World\"},{\"day\":\"MON / WED\",\"time\":\"10:30 AM - 12:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"GECRPHIL101YG1\",\"description\":\"Readings in Philippine History\"},{\"day\":\"MON / WED\",\"time\":\"11:30 AM - 13:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"GECETHIC101YG1\",\"description\":\"Ethics\"},{\"day\":\"MON / WED\",\"time\":\"7:30 AM - 9:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"GECSCTS101YG1\",\"description\":\"Science, Technology and Society\"},{\"day\":\"MON / WED\",\"time\":\"8:30 AM - 10:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"CC1\",\"description\":\"Introduction to Computing\"},{\"day\":\"MON / WED\",\"time\":\"9:30 AM - 11:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"PC1\",\"description\":\"Computer Programming 1\"},{\"day\":\"MON / WED\",\"time\":\"10:30 AM - 12:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"NSTP101YG1\",\"description\":\"CWTS/ROTC 1\"},{\"day\":\"MON / WED\",\"time\":\"11:30 AM - 13:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"PE1\",\"description\":\"Physical Activities Towards Health and Fitness 1\"},{\"day\":\"TUE / THU\",\"time\":\"7:30 AM - 9:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"GECRIZAL101YG1\",\"description\":\"Rizal\'s Life and Works\"},{\"day\":\"TUE / THU\",\"time\":\"8:30 AM - 10:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"GECARTAP101YG2\",\"description\":\"Art Appreciation\"},{\"day\":\"TUE / THU\",\"time\":\"9:30 AM - 11:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"CC2\",\"description\":\"Computer Programming 2\"},{\"day\":\"TUE / THU\",\"time\":\"10:30 AM - 12:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"CC3\",\"description\":\"Discrete Structures\"},{\"day\":\"TUE / THU\",\"time\":\"11:30 AM - 13:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"PC2\",\"description\":\"Data Structures and Algorithms\"},{\"day\":\"TUE / THU\",\"time\":\"7:30 AM - 9:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"PCOMPEI101\",\"description\":\"Web Systems and Technologies 1\"},{\"day\":\"TUE / THU\",\"time\":\"8:30 AM - 10:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"PC3\",\"description\":\"Information Assurance and Security 1\"},{\"day\":\"TUE / THU\",\"time\":\"9:30 AM - 11:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"NSTP102YG1\",\"description\":\"CWTS/ROTC 2\"},{\"day\":\"TUE / THU\",\"time\":\"10:30 AM - 12:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"PE2\",\"description\":\"Physical Activities Towards Health and Fitness 2\"},{\"day\":\"MON / WED\",\"time\":\"7:30 AM - 9:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"GECFIL101YG1\",\"description\":\"Komunikasyon sa Akademikong Filipino\"},{\"day\":\"MON / WED\",\"time\":\"8:30 AM - 10:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"CC4\",\"description\":\"Algorithms and Complexity\"},{\"day\":\"MON / WED\",\"time\":\"9:30 AM - 11:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"COMPP101YG2\",\"description\":\"Information Management\"},{\"day\":\"MON / WED\",\"time\":\"10:30 AM - 12:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"PC4\",\"description\":\"Networks and Communication 1\"},{\"day\":\"MON / WED\",\"time\":\"11:30 AM - 13:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"PC5\",\"description\":\"Object-Oriented Programming\"},{\"day\":\"MON / WED\",\"time\":\"7:30 AM - 9:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"PC6\",\"description\":\"Information Assurance and Security 2\"},{\"day\":\"MON / WED\",\"time\":\"8:30 AM - 10:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"PE3\",\"description\":\"Physical Activities Towards Health and Fitness 3\"},{\"day\":\"TUE / THU\",\"time\":\"7:30 AM - 9:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"GECFIL102YG1\",\"description\":\"Filipino sa Iba\'t Ibang Larangan\"},{\"day\":\"TUE / THU\",\"time\":\"8:30 AM - 10:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"CC5\",\"description\":\"Applications Development\"},{\"day\":\"TUE / THU\",\"time\":\"9:30 AM - 11:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"CC6\",\"description\":\"Social and Professional Issues\"},{\"day\":\"TUE / THU\",\"time\":\"10:30 AM - 12:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"PC7\",\"description\":\"Automata Theory and Formal Languages\"},{\"day\":\"TUE / THU\",\"time\":\"11:30 AM - 13:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"PC8\",\"description\":\"Architecture and Organization\"},{\"day\":\"TUE / THU\",\"time\":\"7:30 AM - 9:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"PC9\",\"description\":\"Software Engineering 1\"},{\"day\":\"TUE / THU\",\"time\":\"8:30 AM - 10:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"PE4\",\"description\":\"Physical Activities Towards Health and Fitness 4\"},{\"day\":\"MON / WED\",\"time\":\"7:30 AM - 9:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"GECSCP101YG3\",\"description\":\"The Contemporary World\"},{\"day\":\"MON / WED\",\"time\":\"8:30 AM - 10:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"PCOMINT101YG3\",\"description\":\"Technopreneurship\"},{\"day\":\"MON / WED\",\"time\":\"9:30 AM - 11:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"PC10\",\"description\":\"Information Assurance and Security\"},{\"day\":\"MON / WED\",\"time\":\"10:30 AM - 12:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"PC11\",\"description\":\"Programming Languages\"},{\"day\":\"MON / WED\",\"time\":\"11:30 AM - 13:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"PC12\",\"description\":\"Software Engineering 2\"},{\"day\":\"MON / WED\",\"time\":\"7:30 AM - 9:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"PC13\",\"description\":\"Intelligent Systems\"},{\"day\":\"TUE / THU\",\"time\":\"7:30 AM - 9:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"CCSC1013YG2\",\"description\":\"Multimedia\"},{\"day\":\"MON / WED\",\"time\":\"7:30 AM - 9:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"CCSC141YG1\",\"description\":\"Human Computer Interaction\"},{\"day\":\"MON / WED\",\"time\":\"8:30 AM - 10:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"CCSC154YG1\",\"description\":\"Operating Systems\"},{\"day\":\"MON / WED\",\"time\":\"9:30 AM - 11:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"CCSC155YG1\",\"description\":\"Social Issues and Professional Ethics\"},{\"day\":\"MON / WED\",\"time\":\"10:30 AM - 12:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"CCSC174YG1\",\"description\":\"Thesis Writing 1\"},{\"day\":\"TUE / THU\",\"time\":\"7:30 AM - 9:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"CCSC158YG2\",\"description\":\"Networks and Communication\"},{\"day\":\"TUE / THU\",\"time\":\"8:30 AM - 10:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"CCSC184YG2\",\"description\":\"Thesis Writing 2\"}]', '2026-09-07 04:18:42'),
(2, 12, 'BSCS-2024', 'Bachelor of Science in Computer Science', '[{\"name\":\"1st Year - 1st Semester\",\"subjects\":[{\"code\":\"GECCOED101YG1\",\"title\":\"Understanding the Self\",\"lec\":3,\"lab\":0,\"units\":3},{\"code\":\"GECPCOM101YG1\",\"title\":\"Purposive Communication\",\"lec\":3,\"lab\":0,\"units\":3},{\"code\":\"GECMATH101YG1\",\"title\":\"Mathematics in the Modern World\",\"lec\":3,\"lab\":0,\"units\":3},{\"code\":\"GECRPHIL101YG1\",\"title\":\"Readings in Philippine History\",\"lec\":3,\"lab\":0,\"units\":3},{\"code\":\"GECETHIC101YG1\",\"title\":\"Ethics\",\"lec\":3,\"lab\":0,\"units\":3},{\"code\":\"GECSCTS101YG1\",\"title\":\"Science, Technology and Society\",\"lec\":3,\"lab\":0,\"units\":3},{\"code\":\"CC1\",\"title\":\"Introduction to Computing\",\"lec\":2,\"lab\":3,\"units\":3},{\"code\":\"PC1\",\"title\":\"Computer Programming 1\",\"lec\":2,\"lab\":3,\"units\":3},{\"code\":\"NSTP101YG1\",\"title\":\"CWTS/ROTC 1\",\"lec\":3,\"lab\":0,\"units\":3},{\"code\":\"PE1\",\"title\":\"Physical Activities Towards Health and Fitness 1\",\"lec\":2,\"lab\":0,\"units\":2}]},{\"name\":\"1st Year - 2nd Semester\",\"subjects\":[{\"code\":\"GECRIZAL101YG1\",\"title\":\"Rizal\'s Life and Works\",\"lec\":3,\"lab\":0,\"units\":3},{\"code\":\"GECARTAP101YG2\",\"title\":\"Art Appreciation\",\"lec\":3,\"lab\":0,\"units\":3},{\"code\":\"CC2\",\"title\":\"Computer Programming 2\",\"lec\":2,\"lab\":3,\"units\":3},{\"code\":\"CC3\",\"title\":\"Discrete Structures\",\"lec\":3,\"lab\":0,\"units\":3},{\"code\":\"PC2\",\"title\":\"Data Structures and Algorithms\",\"lec\":2,\"lab\":3,\"units\":3},{\"code\":\"PCOMPEI101\",\"title\":\"Web Systems and Technologies 1\",\"lec\":2,\"lab\":3,\"units\":3},{\"code\":\"PC3\",\"title\":\"Information Assurance and Security 1\",\"lec\":2,\"lab\":3,\"units\":3},{\"code\":\"NSTP102YG1\",\"title\":\"CWTS/ROTC 2\",\"lec\":3,\"lab\":0,\"units\":3},{\"code\":\"PE2\",\"title\":\"Physical Activities Towards Health and Fitness 2\",\"lec\":2,\"lab\":0,\"units\":2}]},{\"name\":\"2nd Year - 1st Semester\",\"subjects\":[{\"code\":\"GECFIL101YG1\",\"title\":\"Komunikasyon sa Akademikong Filipino\",\"lec\":3,\"lab\":0,\"units\":3},{\"code\":\"CC4\",\"title\":\"Algorithms and Complexity\",\"lec\":3,\"lab\":0,\"units\":3},{\"code\":\"COMPP101YG2\",\"title\":\"Information Management\",\"lec\":3,\"lab\":0,\"units\":3},{\"code\":\"PC4\",\"title\":\"Networks and Communication 1\",\"lec\":2,\"lab\":3,\"units\":3},{\"code\":\"PC5\",\"title\":\"Object-Oriented Programming\",\"lec\":2,\"lab\":3,\"units\":3},{\"code\":\"PC6\",\"title\":\"Information Assurance and Security 2\",\"lec\":2,\"lab\":3,\"units\":3},{\"code\":\"PE3\",\"title\":\"Physical Activities Towards Health and Fitness 3\",\"lec\":2,\"lab\":0,\"units\":2}]},{\"name\":\"2nd Year - 2nd Semester\",\"subjects\":[{\"code\":\"GECFIL102YG1\",\"title\":\"Filipino sa Iba\'t Ibang Larangan\",\"lec\":3,\"lab\":0,\"units\":3},{\"code\":\"CC5\",\"title\":\"Applications Development\",\"lec\":3,\"lab\":0,\"units\":3},{\"code\":\"CC6\",\"title\":\"Social and Professional Issues\",\"lec\":3,\"lab\":0,\"units\":3},{\"code\":\"PC7\",\"title\":\"Automata Theory and Formal Languages\",\"lec\":3,\"lab\":0,\"units\":3},{\"code\":\"PC8\",\"title\":\"Architecture and Organization\",\"lec\":3,\"lab\":0,\"units\":3},{\"code\":\"PC9\",\"title\":\"Software Engineering 1\",\"lec\":3,\"lab\":0,\"units\":3},{\"code\":\"PE4\",\"title\":\"Physical Activities Towards Health and Fitness 4\",\"lec\":2,\"lab\":0,\"units\":2}]},{\"name\":\"3rd Year - 1st Semester\",\"subjects\":[{\"code\":\"GECSCP101YG3\",\"title\":\"The Contemporary World\",\"lec\":3,\"lab\":0,\"units\":3},{\"code\":\"PCOMINT101YG3\",\"title\":\"Technopreneurship\",\"lec\":3,\"lab\":0,\"units\":3},{\"code\":\"PC10\",\"title\":\"Information Assurance and Security\",\"lec\":3,\"lab\":0,\"units\":3},{\"code\":\"PC11\",\"title\":\"Programming Languages\",\"lec\":3,\"lab\":0,\"units\":3},{\"code\":\"PC12\",\"title\":\"Software Engineering 2\",\"lec\":3,\"lab\":0,\"units\":3},{\"code\":\"PC13\",\"title\":\"Intelligent Systems\",\"lec\":3,\"lab\":0,\"units\":3}]},{\"name\":\"3rd Year - 2nd Semester\",\"subjects\":[{\"code\":\"CCSC1013YG2\",\"title\":\"Multimedia\",\"lec\":3,\"lab\":0,\"units\":3}]},{\"name\":\"4th Year - 1st Semester\",\"subjects\":[{\"code\":\"CCSC141YG1\",\"title\":\"Human Computer Interaction\",\"lec\":3,\"lab\":0,\"units\":3},{\"code\":\"CCSC154YG1\",\"title\":\"Operating Systems\",\"lec\":3,\"lab\":0,\"units\":3},{\"code\":\"CCSC155YG1\",\"title\":\"Social Issues and Professional Ethics\",\"lec\":3,\"lab\":0,\"units\":3},{\"code\":\"CCSC174YG1\",\"title\":\"Thesis Writing 1\",\"lec\":3,\"lab\":0,\"units\":3}]},{\"name\":\"4th Year - 2nd Semester\",\"subjects\":[{\"code\":\"CCSC158YG2\",\"title\":\"Networks and Communication\",\"lec\":3,\"lab\":0,\"units\":3},{\"code\":\"CCSC184YG2\",\"title\":\"Thesis Writing 2\",\"lec\":3,\"lab\":0,\"units\":3}]}]', '[{\"day\":\"MON / WED\",\"time\":\"7:30 AM - 9:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"GECCOED101YG1\",\"description\":\"Understanding the Self\"},{\"day\":\"MON / WED\",\"time\":\"8:30 AM - 10:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"GECPCOM101YG1\",\"description\":\"Purposive Communication\"},{\"day\":\"MON / WED\",\"time\":\"9:30 AM - 11:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"GECMATH101YG1\",\"description\":\"Mathematics in the Modern World\"},{\"day\":\"MON / WED\",\"time\":\"10:30 AM - 12:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"GECRPHIL101YG1\",\"description\":\"Readings in Philippine History\"},{\"day\":\"MON / WED\",\"time\":\"11:30 AM - 13:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"GECETHIC101YG1\",\"description\":\"Ethics\"},{\"day\":\"MON / WED\",\"time\":\"7:30 AM - 9:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"GECSCTS101YG1\",\"description\":\"Science, Technology and Society\"},{\"day\":\"MON / WED\",\"time\":\"8:30 AM - 10:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"CC1\",\"description\":\"Introduction to Computing\"},{\"day\":\"MON / WED\",\"time\":\"9:30 AM - 11:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"PC1\",\"description\":\"Computer Programming 1\"},{\"day\":\"MON / WED\",\"time\":\"10:30 AM - 12:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"NSTP101YG1\",\"description\":\"CWTS/ROTC 1\"},{\"day\":\"MON / WED\",\"time\":\"11:30 AM - 13:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"PE1\",\"description\":\"Physical Activities Towards Health and Fitness 1\"},{\"day\":\"TUE / THU\",\"time\":\"7:30 AM - 9:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"GECRIZAL101YG1\",\"description\":\"Rizal\'s Life and Works\"},{\"day\":\"TUE / THU\",\"time\":\"8:30 AM - 10:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"GECARTAP101YG2\",\"description\":\"Art Appreciation\"},{\"day\":\"TUE / THU\",\"time\":\"9:30 AM - 11:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"CC2\",\"description\":\"Computer Programming 2\"},{\"day\":\"TUE / THU\",\"time\":\"10:30 AM - 12:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"CC3\",\"description\":\"Discrete Structures\"},{\"day\":\"TUE / THU\",\"time\":\"11:30 AM - 13:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"PC2\",\"description\":\"Data Structures and Algorithms\"},{\"day\":\"TUE / THU\",\"time\":\"7:30 AM - 9:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"PCOMPEI101\",\"description\":\"Web Systems and Technologies 1\"},{\"day\":\"TUE / THU\",\"time\":\"8:30 AM - 10:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"PC3\",\"description\":\"Information Assurance and Security 1\"},{\"day\":\"TUE / THU\",\"time\":\"9:30 AM - 11:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"NSTP102YG1\",\"description\":\"CWTS/ROTC 2\"},{\"day\":\"TUE / THU\",\"time\":\"10:30 AM - 12:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"PE2\",\"description\":\"Physical Activities Towards Health and Fitness 2\"},{\"day\":\"MON / WED\",\"time\":\"7:30 AM - 9:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"GECFIL101YG1\",\"description\":\"Komunikasyon sa Akademikong Filipino\"},{\"day\":\"MON / WED\",\"time\":\"8:30 AM - 10:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"CC4\",\"description\":\"Algorithms and Complexity\"},{\"day\":\"MON / WED\",\"time\":\"9:30 AM - 11:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"COMPP101YG2\",\"description\":\"Information Management\"},{\"day\":\"MON / WED\",\"time\":\"10:30 AM - 12:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"PC4\",\"description\":\"Networks and Communication 1\"},{\"day\":\"MON / WED\",\"time\":\"11:30 AM - 13:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"PC5\",\"description\":\"Object-Oriented Programming\"},{\"day\":\"MON / WED\",\"time\":\"7:30 AM - 9:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"PC6\",\"description\":\"Information Assurance and Security 2\"},{\"day\":\"MON / WED\",\"time\":\"8:30 AM - 10:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"PE3\",\"description\":\"Physical Activities Towards Health and Fitness 3\"},{\"day\":\"TUE / THU\",\"time\":\"7:30 AM - 9:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"GECFIL102YG1\",\"description\":\"Filipino sa Iba\'t Ibang Larangan\"},{\"day\":\"TUE / THU\",\"time\":\"8:30 AM - 10:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"CC5\",\"description\":\"Applications Development\"},{\"day\":\"TUE / THU\",\"time\":\"9:30 AM - 11:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"CC6\",\"description\":\"Social and Professional Issues\"},{\"day\":\"TUE / THU\",\"time\":\"10:30 AM - 12:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"PC7\",\"description\":\"Automata Theory and Formal Languages\"},{\"day\":\"TUE / THU\",\"time\":\"11:30 AM - 13:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"PC8\",\"description\":\"Architecture and Organization\"},{\"day\":\"TUE / THU\",\"time\":\"7:30 AM - 9:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"PC9\",\"description\":\"Software Engineering 1\"},{\"day\":\"TUE / THU\",\"time\":\"8:30 AM - 10:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"PE4\",\"description\":\"Physical Activities Towards Health and Fitness 4\"},{\"day\":\"MON / WED\",\"time\":\"7:30 AM - 9:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"GECSCP101YG3\",\"description\":\"The Contemporary World\"},{\"day\":\"MON / WED\",\"time\":\"8:30 AM - 10:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"PCOMINT101YG3\",\"description\":\"Technopreneurship\"},{\"day\":\"MON / WED\",\"time\":\"9:30 AM - 11:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"PC10\",\"description\":\"Information Assurance and Security\"},{\"day\":\"MON / WED\",\"time\":\"10:30 AM - 12:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"PC11\",\"description\":\"Programming Languages\"},{\"day\":\"MON / WED\",\"time\":\"11:30 AM - 13:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"PC12\",\"description\":\"Software Engineering 2\"},{\"day\":\"MON / WED\",\"time\":\"7:30 AM - 9:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"PC13\",\"description\":\"Intelligent Systems\"},{\"day\":\"TUE / THU\",\"time\":\"7:30 AM - 9:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"CCSC1013YG2\",\"description\":\"Multimedia\"},{\"day\":\"MON / WED\",\"time\":\"7:30 AM - 9:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"CCSC141YG1\",\"description\":\"Human Computer Interaction\"},{\"day\":\"MON / WED\",\"time\":\"8:30 AM - 10:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"CCSC154YG1\",\"description\":\"Operating Systems\"},{\"day\":\"MON / WED\",\"time\":\"9:30 AM - 11:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"CCSC155YG1\",\"description\":\"Social Issues and Professional Ethics\"},{\"day\":\"MON / WED\",\"time\":\"10:30 AM - 12:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"CCSC174YG1\",\"description\":\"Thesis Writing 1\"},{\"day\":\"TUE / THU\",\"time\":\"7:30 AM - 9:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"CCSC158YG2\",\"description\":\"Networks and Communication\"},{\"day\":\"TUE / THU\",\"time\":\"8:30 AM - 10:00 AM\",\"room\":\"208 / CL1\",\"subject\":\"CCSC184YG2\",\"description\":\"Thesis Writing 2\"}]', '2026-09-07 04:30:52');

-- --------------------------------------------------------

--
-- Table structure for table `student_payments`
--

CREATE TABLE `student_payments` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `or_number` varchar(50) DEFAULT NULL,
  `payment_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `student_code` varchar(20) NOT NULL DEFAULT '',
  `purpose` varchar(50) NOT NULL DEFAULT 'Partial Payment',
  `gcash_number` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_payments`
--

INSERT INTO `student_payments` (`id`, `student_id`, `amount`, `or_number`, `payment_date`, `created_at`, `student_code`, `purpose`, `gcash_number`) VALUES
(2, 0, 1500.00, 'OR-20261013-MANUAL', '2026-10-13', '2026-10-13 11:50:48', 'YOUR_STUDENT_CODE_HE', 'Initial Downpayment', ''),
(4, 0, 1500.00, 'OR-20261013-TEST01', '2026-10-13', '2026-10-13 12:40:40', '2026-0014', 'Initial Downpayment', '0917-123-4567');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `username` varchar(50) NOT NULL,
  `student_code` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `first_name`, `middle_name`, `last_name`, `username`, `student_code`, `password`) VALUES
(27, 'shan', 'raye', 'guzman', 'shan', '2026-0015', '$2y$10$IeVe4mNWAwquf8x.OkEwROoPnX3OT45qcFzCV0G2XoYKMGSbN3tTS'),
(28, 'rere', 'rrr', 'rrrr', 'example', NULL, '$2y$10$1w0Fc/.Bcpg..nxYvB891eW8pWNpMQpgcCyR.11ovld4oPrzymgEu'),
(29, 'example1', 'example2', 'example3', 'example4', NULL, '$2y$10$6NG/FxmdAff5JTsG1Th9tObJw5d2jlLd8ACJ19jHDyHz4LRADGgBO'),
(30, 'example1', 'example2', 'example3', 'example4', NULL, '$2y$10$ddw7sCMEaAIb4yr1qYlmDOtkfYfPDsFxyQwiPh9Aq4R7MU5E3lQIS'),
(31, 'shan', 'raye', 'guzman', 'shans', '2026-0015', '$2y$10$xr/j.2CcoftZzrx1WdlN8OIBLe3DL10elMoqH0ecnsQ8ic1sTASxq'),
(32, 'shan', 'raye', 'guzman', 'shans', '2026-0015', '$2y$10$aKP0xFhzXj5XYc/4k7wxquWeIIRp/A362G5v.5r550eOYewOjAFSy'),
(33, 'shan', 'raye', 'guzman', 'shans', '2026-0015', '$2y$10$tQ5CPut/rZzPfujIUHvJy.gn.un5nOQQrkcKu9jNaAGYVSH.akTvK'),
(34, 'shan', 'raye', 'guzman', 'shann', '2026-0015', '$2y$10$QskPeP5kjMzejNWBdHyI7uIzAaoaiDVEWeGxey1Qfwf.41chaeJl2'),
(35, 'shan', 'raye', 'guzman', 'shann', '2026-0015', '$2y$10$LJnrc2WNaNAWwwh8YWnS9OurH5hvRZltqYflsmKDs8u.Dp9KYc6BO'),
(36, 'shan', 'raye', 'guzman', 'shann', '2026-0015', '$2y$10$M8/crQVRFfArEaVbVvL/y.ojJCs.kVtwTQA08L7PWrKNqHuPvwDly'),
(37, 'shan', 'raye', 'guzman', 'shann', '2026-0015', '$2y$10$a9r34DesFURw8DO9uSZblO5HzghxOEaVXON7Ezo0h6i9vz.a2fzN2'),
(38, 'shan', 'raye', 'guzman', 'shann', '2026-0015', '$2y$10$plvYL3BseciK1Bmc/w6HPO8rSHWkPGg1DEKKY9yTtmQcrBMbZk2xm'),
(39, 'shan', 'shan', 'shan', 'shan', NULL, '$2y$10$oUo8nnMwfsJurbp1YQgbNeo.I5tDlokZBbbtpylPzgyMlQ6rvSBPO'),
(40, 'SHAN]\\', 'SHAN', 'SHAN', 'SHAN', NULL, '$2y$10$N0Zzx0zgfmqzA9uixopQAO72NoW1cR/p.wsZP9cnZ2RLFIbheETgG'),
(41, 'akjsn', 'sjbda', 's', 's', NULL, '$2y$10$4pX.FMMLW.wLoJpzdcJi8e4vFVUv439nWzkUvC/fdb9Qgb0h5p13m'),
(42, 'shan\\', 'sahn', 'sahn', 'sahn', NULL, '$2y$10$UyKZstji1FoS.Fzx1B4BlewKUu9bH/Ci1Dsx/guHwSs0ZSm.ZsvZC'),
(43, 'shan', 'shan', 'shan', 'shanr', NULL, '$2y$10$435XBbzTV5n77MPqXt4tOO4kDtF.nPxNxA8/cb8fo.Jp22bqJql7i'),
(44, 'S', 'S', 'S', 'shanw', NULL, '$2y$10$bOUs40Z.J0F/8LBD0cS/WOjo1hUjyHHhX6xSmgvpx8ZVwAomR8kQy'),
(45, 'shan', 'rosello', 'guzman', 'shanraye', '2026-0015', '$2y$10$MIDZX3uqSUm1OFi0Q6AIZe70TcWL6YP9nKk5lpHn4uYLppKF7mYXW'),
(46, 'shan', 'rosello', 'guzman', 'reiii', '2026-0015', '$2y$10$Ob52Ulj1M0mXnyFR7fsqnefRYlPEqwg2mbh6WHz.uF2ArIDuOadke'),
(47, 'shan', 'shan', 'shan\'', 'shan', NULL, '$2y$10$w8X2wHbBlfHIJfAZqiN3MOCtL2JegpmcMFGUvnJ4VXeWRos/6v9S.'),
(48, 'shan', 'shan', 'shan\'', 'shan', NULL, '$2y$10$YXKk9KwmHU7uqTLe5QXZ9ucH6tHhDc6LshnAYfUc9wSMokQIOow0q'),
(49, 'shan', 'shan', 'shan\'', 'shan', NULL, '$2y$10$E4sDnuJofnvm3OJ4Zwu0WOoSju0/LIn0PXLo16J6Jlc8LtBJv1KBS'),
(50, 'sdfdgb', '', 'sdfgbv', '2026-0001', '2026-0001', '$2y$10$QqPJETeWPzs1nioB/V4QcOQDrfgDLphQ4rBvH3psuJkSqcvA1Yj4S'),
(51, 'Almadin', 'Nor', 'Mipanga', '2026-0002', '2026-0014', '$2y$10$Opd6YE9Mv2SyXIh6xeT7EOfyju7zDWLNUaJsdW5mAH9XJ7qnG7JMy'),
(52, 'ss', '', 's', '2026-0003', '2026-0003', '$2y$10$sPukutg2gyNBGrnRmC7bc.Wzb3HPqGwY.UkfFNe5lzQ7LEbFK9jjW'),
(53, 'Shan Raye', 'Rosello', 'Guzman', '2026-0004', '2026-0014', '$2y$10$qyIDTbTfB6dgKnsTUi26KuzGuVZ.CgdasXE0siNtXSidEg.sDAHxK'),
(54, 'qkwfekauhoie', 'awodhioawh', 'awdwhowhadoiwsh', '2026-0005', '2026-0005', '$2y$10$bKpN2/RnIH5OEH78w28Ej.OgHKtnmxH4dOqHC3n8qawIRcdOMw6gC'),
(55, 'Shan', 'bdakjsbdjk', 'raye', '2026-0006', '2026-0006', '$2y$10$5nL86cPXF6G/F6UmC7MbzOfhfiZ0aY6gd04WA5aeR8IO8Vv7gpapC'),
(56, 'Shan Raye', 'Rosello', 'Guzman', '2026-0007', '2026-0014', '$2y$10$bE8wXjJKacfhInhQf/xhl.5fVYkl.SwXTR.MNpNBnbYppJUPBn9Cm'),
(57, 'wakjefuihrghrfhweuif', 'dsajkhdsakhdsa', 'asohadwjkhdsajkdsajbkdsa', '2026-0008', '2026-0008', '$2y$10$vY2cY.R4m8Ls0j24eeH/4.2kfno2QA9LF3Omnp32oKU7eWfTu6jvC'),
(58, 'qw', 'few', 'eq3tr5yfujkbwerwefrg', '2026-0009', '2026-0009', '$2y$10$qZQQFPJqLxAx6nEOIWAJwOdn9Ql1boLG7MSu80B7kzOGb2MzeR99G'),
(59, 'ligfdxfghdyftuiyugfgfxcv', 'vccgjkghhvbcvcn', 'lkjgghfyuktg', '2026-0010', '2026-0010', '$2y$10$LROqRVJWz91M.19U4k/coOvUVm/lRinioKfgAk3QjTIvWwQi0t1/O'),
(60, 'WDAESRDTFGHWDEFRETR', 'ADSFD', 'SDFDGD', '2026-0011', '2026-0011', '$2y$10$DoddiGLJYoomhVsmUPKXhO/70ZOBPO7eI/1eJFjUCif2qgMVboYaO'),
(61, 'kennjie', 'jeremias', 'hiyao', '2026-0012', '2026-0012', '$2y$10$URlL.ASnvqTDk2SgJYwJwuj7QuwU5TsCMMmVlCR7D55KVwHG2Zoqe'),
(62, 'sample', 'samplee', 'sample', '2026-0013', '2026-0013', '$2y$10$oXaBQirXJ1yGLaSHugKyxeZSflb9TrVzmr8HWKdYaYoPoyCM80vta'),
(65, 'Shan Raye', 'Rosello', 'Guzman', 'shan', '2026-0014', '$2y$10$nb.JMIrnoHV/hIoYrUW7VOhjWZHY4CSgn20MEubInm9C.VbKeOlHi'),
(66, 'Kennjie', 'Jeremias', 'Hiyao', 'hiyao', '2026-0012', '$2y$10$WUR36lc1yl6a/PJfUwpCoOkp2uKnkGj4ffceL.gMsxcWbCq8Rf.ay'),
(67, 'Shan Raye', 'Rosello', 'Guzman', 'hello', '2026-0014', '$2y$10$ck5ACpQOZCBzzjXn7ZjAEOYA//BecqFeVDfPQapeBtZd3JB7jByqy'),
(68, 'ALMADIN', 'NOR', 'MIPANGA', 'mipanga', '2026-0014', '$2y$10$fu9prUysD9o2cmyqkrJB1uQXMF4SOCcew5RczOfWdSzEKuDQ3WkLu'),
(69, 'Shan Raye', 'Rosello', 'Guzman', 'shan', '2026-0014', '$2y$10$ernmQ.x70KafyemP07XtM.aG58.dQ79R0x9BPGjWlXo9bTOsN.Thq'),
(70, 'ALMADIN', 'NOR', 'MIPANGA', 'sgan', '2026-0014', '$2y$10$I5VHsXAXMEINlI0VUgXlQeXiBMPoCV2G/xdKnSv6QkLFMUCnFKmke'),
(71, 'l', 'l', 'l', 'rei', '2026-0014', '$2y$10$zm06CIoVJpGadPQ76Bd/Ou/7.5oEdahlZpLmjmu8NleDBHkXWGUMq'),
(72, 'Shan Ray', 'Rosello', 'Guzman', 'reiyuki', '2026-0014', '$2y$10$7r67sDNa7.c2PK8/NInhEe8.A7E4aKbJOGsUXaF.0eFblX1xzbpo6'),
(73, 'hoiuyoiuyoui', 'dwqugduqwi', 'oiuyo', 'meimei', NULL, '$2y$10$ABRfX1HXTiZWAqE2O9dcH.H8wN0Prj2USCeyZDUKwX0.XMUuSSbmy');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `composed_mail`
--
ALTER TABLE `composed_mail`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `curriculums`
--
ALTER TABLE `curriculums`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `curriculum_semesters`
--
ALTER TABLE `curriculum_semesters`
  ADD PRIMARY KEY (`id`),
  ADD KEY `curriculum_id` (`curriculum_id`);

--
-- Indexes for table `curriculum_subjects`
--
ALTER TABLE `curriculum_subjects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `semester_id` (`semester_id`);

--
-- Indexes for table `enrolled`
--
ALTER TABLE `enrolled`
  ADD PRIMARY KEY (`student_id`);

--
-- Indexes for table `instructors`
--
ALTER TABLE `instructors`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `instructor_schedules`
--
ALTER TABLE `instructor_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `instructor_id` (`instructor_id`);

--
-- Indexes for table `instructor_subjects`
--
ALTER TABLE `instructor_subjects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `instructor_id` (`instructor_id`);

--
-- Indexes for table `received_mail`
--
ALTER TABLE `received_mail`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `staff_users`
--
ALTER TABLE `staff_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `student_assessments`
--
ALTER TABLE `student_assessments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_student_assess` (`student_id`);

--
-- Indexes for table `student_curriculum_assignments`
--
ALTER TABLE `student_curriculum_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_student` (`student_id`);

--
-- Indexes for table `student_payments`
--
ALTER TABLE `student_payments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `composed_mail`
--
ALTER TABLE `composed_mail`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `curriculums`
--
ALTER TABLE `curriculums`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `curriculum_semesters`
--
ALTER TABLE `curriculum_semesters`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `curriculum_subjects`
--
ALTER TABLE `curriculum_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `enrolled`
--
ALTER TABLE `enrolled`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `instructors`
--
ALTER TABLE `instructors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `instructor_schedules`
--
ALTER TABLE `instructor_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `instructor_subjects`
--
ALTER TABLE `instructor_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `received_mail`
--
ALTER TABLE `received_mail`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `staff_users`
--
ALTER TABLE `staff_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_assessments`
--
ALTER TABLE `student_assessments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `student_curriculum_assignments`
--
ALTER TABLE `student_curriculum_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `student_payments`
--
ALTER TABLE `student_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `curriculum_semesters`
--
ALTER TABLE `curriculum_semesters`
  ADD CONSTRAINT `curriculum_semesters_ibfk_1` FOREIGN KEY (`curriculum_id`) REFERENCES `curriculums` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `curriculum_subjects`
--
ALTER TABLE `curriculum_subjects`
  ADD CONSTRAINT `curriculum_subjects_ibfk_1` FOREIGN KEY (`semester_id`) REFERENCES `curriculum_semesters` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `instructor_schedules`
--
ALTER TABLE `instructor_schedules`
  ADD CONSTRAINT `instructor_schedules_ibfk_1` FOREIGN KEY (`instructor_id`) REFERENCES `instructors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `instructor_subjects`
--
ALTER TABLE `instructor_subjects`
  ADD CONSTRAINT `instructor_subjects_ibfk_1` FOREIGN KEY (`instructor_id`) REFERENCES `instructors` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
