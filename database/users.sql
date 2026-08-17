-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 17, 2026 at 04:43 AM
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
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `first_name`, `middle_name`, `last_name`, `username`, `password`) VALUES
(27, 'shan', 'raye', 'guzman', 'shan', '$2y$10$IeVe4mNWAwquf8x.OkEwROoPnX3OT45qcFzCV0G2XoYKMGSbN3tTS'),
(28, 'rere', 'rrr', 'rrrr', 'example', '$2y$10$1w0Fc/.Bcpg..nxYvB891eW8pWNpMQpgcCyR.11ovld4oPrzymgEu'),
(29, 'example1', 'example2', 'example3', 'example4', '$2y$10$6NG/FxmdAff5JTsG1Th9tObJw5d2jlLd8ACJ19jHDyHz4LRADGgBO'),
(30, 'example1', 'example2', 'example3', 'example4', '$2y$10$ddw7sCMEaAIb4yr1qYlmDOtkfYfPDsFxyQwiPh9Aq4R7MU5E3lQIS'),
(31, 'shan', 'raye', 'guzman', 'shans', '$2y$10$xr/j.2CcoftZzrx1WdlN8OIBLe3DL10elMoqH0ecnsQ8ic1sTASxq'),
(32, 'shan', 'raye', 'guzman', 'shans', '$2y$10$aKP0xFhzXj5XYc/4k7wxquWeIIRp/A362G5v.5r550eOYewOjAFSy'),
(33, 'shan', 'raye', 'guzman', 'shans', '$2y$10$tQ5CPut/rZzPfujIUHvJy.gn.un5nOQQrkcKu9jNaAGYVSH.akTvK'),
(34, 'shan', 'raye', 'guzman', 'shann', '$2y$10$QskPeP5kjMzejNWBdHyI7uIzAaoaiDVEWeGxey1Qfwf.41chaeJl2'),
(35, 'shan', 'raye', 'guzman', 'shann', '$2y$10$LJnrc2WNaNAWwwh8YWnS9OurH5hvRZltqYflsmKDs8u.Dp9KYc6BO'),
(36, 'shan', 'raye', 'guzman', 'shann', '$2y$10$M8/crQVRFfArEaVbVvL/y.ojJCs.kVtwTQA08L7PWrKNqHuPvwDly'),
(37, 'shan', 'raye', 'guzman', 'shann', '$2y$10$a9r34DesFURw8DO9uSZblO5HzghxOEaVXON7Ezo0h6i9vz.a2fzN2'),
(38, 'shan', 'raye', 'guzman', 'shann', '$2y$10$plvYL3BseciK1Bmc/w6HPO8rSHWkPGg1DEKKY9yTtmQcrBMbZk2xm'),
(39, 'shan', 'shan', 'shan', 'shan', '$2y$10$oUo8nnMwfsJurbp1YQgbNeo.I5tDlokZBbbtpylPzgyMlQ6rvSBPO'),
(40, 'SHAN]\\', 'SHAN', 'SHAN', 'SHAN', '$2y$10$N0Zzx0zgfmqzA9uixopQAO72NoW1cR/p.wsZP9cnZ2RLFIbheETgG'),
(41, 'akjsn', 'sjbda', 's', 's', '$2y$10$4pX.FMMLW.wLoJpzdcJi8e4vFVUv439nWzkUvC/fdb9Qgb0h5p13m'),
(42, 'shan\\', 'sahn', 'sahn', 'sahn', '$2y$10$UyKZstji1FoS.Fzx1B4BlewKUu9bH/Ci1Dsx/guHwSs0ZSm.ZsvZC'),
(43, 'shan', 'shan', 'shan', 'shanr', '$2y$10$435XBbzTV5n77MPqXt4tOO4kDtF.nPxNxA8/cb8fo.Jp22bqJql7i'),
(44, 'S', 'S', 'S', 'shanw', '$2y$10$bOUs40Z.J0F/8LBD0cS/WOjo1hUjyHHhX6xSmgvpx8ZVwAomR8kQy'),
(45, 'shan', 'rosello', 'guzman', 'shanraye', '$2y$10$MIDZX3uqSUm1OFi0Q6AIZe70TcWL6YP9nKk5lpHn4uYLppKF7mYXW'),
(46, 'shan', 'rosello', 'guzman', 'reiii', '$2y$10$Ob52Ulj1M0mXnyFR7fsqnefRYlPEqwg2mbh6WHz.uF2ArIDuOadke');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
