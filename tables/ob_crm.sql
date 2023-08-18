-- phpMyAdmin SQL Dump
-- version 4.9.1
-- https://www.phpmyadmin.net/
--
-- Host: 172.20.1.33
-- Generation Time: Feb 28, 2023 at 08:54 AM
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
-- Table structure for table `ob_crm`
--

CREATE TABLE `ob_crm` (
  `id` int(11) UNSIGNED NOT NULL,
  `id_user` int(6) NOT NULL,
  `log_type` int(11) UNSIGNED NOT NULL,
  `insert_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `content` text,
  `source_type` smallint(6) NOT NULL DEFAULT '0',
  `codice` varchar(10) NOT NULL,
  `remote_addr` varchar(45) NOT NULL,
  `close_time` datetime DEFAULT NULL,
  `intercom` varchar(2048) DEFAULT NULL,
  `issue_id` int(11) DEFAULT NULL,
  `scheduled_notify` tinyint(1) NOT NULL DEFAULT '0',
  `scheduled_time` datetime DEFAULT NULL,
  `scheduled_note` varchar(255) DEFAULT NULL,
  `reason` int(11) UNSIGNED DEFAULT NULL,
  `solved` tinyint(1) NOT NULL DEFAULT '0',
  `termination_risk` tinyint(1) NOT NULL DEFAULT '0',
  `que_ans` varchar(60) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `ob_crm`
--

INSERT INTO `ob_crm` (`id`, `id_user`, `log_type`, `insert_time`, `content`, `source_type`, `codice`, `remote_addr`, `close_time`, `intercom`, `issue_id`, `scheduled_notify`, `scheduled_time`, `scheduled_note`, `reason`, `solved`, `termination_risk`, `que_ans`) VALUES
(1042490, 38409, 1, '2023-02-28 07:53:08', '28/02/23 08:54 octo_massimo\ntkt failure ma sincro ok', 3, '15736', '101.56.28.196', NULL, '', NULL, 1, NULL, NULL, NULL, 1, 0, NULL),
(1042489, 38409, 0, '2023-02-28 07:53:06', '4', 1, '15736', '101.56.28.196', '2023-02-28 08:53:06', NULL, NULL, 0, NULL, NULL, NULL, 1, 0, NULL),
(1042488, 45461, 0, '2023-02-28 07:50:40', '1', 1, '892774', '5.97.133.55', '2023-02-28 08:50:40', NULL, NULL, 0, NULL, NULL, NULL, 1, 0, NULL),
(1042487, 38409, 1, '2023-02-28 07:48:48', '28/02/23 08:52 octo_massimo\nriconnessione booking.com aveva portal connection disabled', 3, '503201', '101.56.28.196', NULL, '', NULL, 1, NULL, NULL, NULL, 1, 0, NULL),
(1042486, 38409, 0, '2023-02-28 07:48:45', '4', 1, '503201', '101.56.28.196', '2023-02-28 08:48:45', NULL, NULL, 0, NULL, NULL, NULL, 1, 0, NULL),
(1042485, 38409, 1, '2023-02-28 07:46:45', '28/02/23 08:48 octo_massimo\nparla di una prenotazione presente a luglio che non trovo, chiedo piu info', 3, '936029', '101.56.28.196', NULL, '', NULL, 1, NULL, NULL, NULL, 1, 0, NULL),
(1042484, 38409, 0, '2023-02-28 07:46:43', '3', 1, '936029', '101.56.28.196', '2023-02-28 08:46:43', NULL, NULL, 0, NULL, NULL, NULL, 1, 0, NULL),
(1042483, 38409, 1, '2023-02-28 07:38:32', '28/02/23 08:45 octo_massimo\nconnessione a traum info\nconnessione a expedia info', 3, '231825', '101.56.28.196', NULL, '', NULL, 1, NULL, NULL, NULL, 1, 0, NULL),
(1042482, 38409, 0, '2023-02-28 07:38:31', '3', 1, '231825', '101.56.28.196', '2023-02-28 08:38:31', NULL, NULL, 0, NULL, NULL, NULL, 1, 0, NULL),
(1042481, 38409, 1, '2023-02-28 07:35:49', '28/02/23 08:36 octo_massimo\nmetto su file\n\n28/02/23 08:36 octo_massimo\ntkt failure airbnb ma sincro ok', 3, '408227', '101.56.28.196', '2023-02-28 08:37:02', '', NULL, 1, NULL, NULL, 16, 1, 0, NULL),
(1042480, 38409, 0, '2023-02-28 07:35:46', '4', 1, '408227', '101.56.28.196', '2023-02-28 08:35:46', NULL, NULL, 0, NULL, NULL, NULL, 1, 0, NULL),
(1042479, 38409, 1, '2023-02-28 07:31:04', '28/02/23 08:32 octo_massimo\nconnessione expedia', 3, '892774', '101.56.28.196', '2023-02-28 08:32:39', NULL, NULL, 0, NULL, NULL, 17, 1, 0, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ob_crm`
--
ALTER TABLE `ob_crm`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ob_crm_idx_1` (`id_user`),
  ADD KEY `ob_crm_idx_2` (`issue_id`),
  ADD KEY `ob_crm_idx_3` (`reason`),
  ADD KEY `ob_crm_codice` (`codice`),
  ADD KEY `ob_crm_codice_insert_time` (`codice`,`insert_time`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ob_crm`
--
ALTER TABLE `ob_crm`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1042492;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `ob_crm`
--
ALTER TABLE `ob_crm`
  ADD CONSTRAINT `ob_crm_issue` FOREIGN KEY (`issue_id`) REFERENCES `ob_issue` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `ob_crm_reason` FOREIGN KEY (`reason`) REFERENCES `ob_reasons` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `ob_crm_user` FOREIGN KEY (`id_user`) REFERENCES `ob_users` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
