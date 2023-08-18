-- phpMyAdmin SQL Dump
-- version 4.9.1
-- https://www.phpmyadmin.net/
--
-- Host: 172.20.1.33
-- Generation Time: Jan 27, 2023 at 04:11 PM
-- Server version: 5.7.34-log
-- PHP Version: 7.3.29-1~deb10u1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `logs`
--

-- --------------------------------------------------------

--
-- Table structure for table `php_stream_reservation_log`
--

CREATE TABLE `php_stream_reservation_log` (
  `id` int(11) NOT NULL,
  `hot_sites_id` int(11) NOT NULL,
  `hot_user_id` int(11) DEFAULT NULL,
  `reqResp` longtext CHARACTER SET utf8,
  `insert_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `php_stream_reservation_log`
--
ALTER TABLE `php_stream_reservation_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `hot_sites_id` (`hot_sites_id`),
  ADD KEY `insert_time` (`insert_time`),
  ADD KEY `hot_user_id` (`hot_user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `php_stream_reservation_log`
--
ALTER TABLE `php_stream_reservation_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
