-- phpMyAdmin SQL Dump
-- version 4.9.1
-- https://www.phpmyadmin.net/
--
-- Host: 172.20.1.33
-- Generation Time: Jan 12, 2023 at 12:38 PM
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
-- Database: `enginebb`
--

-- --------------------------------------------------------

--
-- Table structure for table `ob_rules_derived`
--

CREATE TABLE `ob_rules_derived` (
  `room_id` int(11) NOT NULL,
  `parent_id` int(11) NOT NULL,
  `avail` tinyint(1) NOT NULL DEFAULT '1',
  `stay` tinyint(1) NOT NULL DEFAULT '0',
  `restrictions` tinyint(1) NOT NULL DEFAULT '0',
  `stop` tinyint(1) NOT NULL DEFAULT '0',
  `price` smallint(6) NOT NULL DEFAULT '0',
  `price_val` decimal(10,2) NOT NULL DEFAULT '0.00',
  `price_round` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ob_rules_derived`
--
ALTER TABLE `ob_rules_derived`
  ADD PRIMARY KEY (`room_id`),
  ADD KEY `ob_rules_derived_1` (`room_id`),
  ADD KEY `ob_rules_derived_2` (`parent_id`);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `ob_rules_derived`
--
ALTER TABLE `ob_rules_derived`
  ADD CONSTRAINT `ob_rules_derived_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `hot_room_type` (`room_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `ob_rules_derived_ibfk_2` FOREIGN KEY (`parent_id`) REFERENCES `hot_room_type` (`room_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
