-- phpMyAdmin SQL Dump
-- version 4.9.1
-- https://www.phpmyadmin.net/
--
-- Host: 172.20.1.33
-- Generation Time: Jan 27, 2023 at 04:09 PM
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
-- Table structure for table `php_stream_reservation_confirmation`
--

CREATE TABLE `php_stream_reservation_confirmation` (
  `id` int(11) NOT NULL,
  `confirmation_req` mediumblob,
  `confirmation_resp` mediumblob,
  `insert_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `php_stream_reservation_confirmation`
--
ALTER TABLE `php_stream_reservation_confirmation`
  ADD PRIMARY KEY (`id`);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `php_stream_reservation_confirmation`
--
ALTER TABLE `php_stream_reservation_confirmation`
  ADD CONSTRAINT `php_stream_reservation_confirmation_ibfk_1` FOREIGN KEY (`id`) REFERENCES `php_stream_reservation_xml` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
