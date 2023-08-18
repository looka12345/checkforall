-- phpMyAdmin SQL Dump
-- version 4.9.1
-- https://www.phpmyadmin.net/
--
-- Host: 172.20.1.33
-- Generation Time: Feb 28, 2023 at 08:56 AM
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
-- Table structure for table `ob_users`
--

CREATE TABLE `ob_users` (
  `id` int(6) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(64) NOT NULL,
  `mfa_key` varchar(64) DEFAULT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '1',
  `firstname` varchar(100) DEFAULT NULL,
  `lastname` varchar(100) DEFAULT NULL,
  `email` varchar(200) DEFAULT NULL,
  `codice` varchar(50) DEFAULT NULL,
  `user_type` smallint(6) NOT NULL DEFAULT '0',
  `perm_room` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `perm_resa` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `perm_avail` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `perm_sets` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `perm_bill` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `perm_cash_closing` tinyint(4) NOT NULL DEFAULT '0',
  `perm_cancel_cash_closing` tinyint(4) NOT NULL DEFAULT '0',
  `perm_users` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `perm_housekeeping` tinyint(1) UNSIGNED NOT NULL DEFAULT '1',
  `perm_checkin` tinyint(1) UNSIGNED NOT NULL DEFAULT '1',
  `userlevel` tinyint(4) NOT NULL DEFAULT '100',
  `last_login` int(11) DEFAULT NULL,
  `password_change_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `showtimeline` tinyint(1) NOT NULL DEFAULT '1',
  `perm_stats` tinyint(1) NOT NULL DEFAULT '0',
  `default_table_rows` smallint(6) NOT NULL DEFAULT '10',
  `show_network` tinyint(1) NOT NULL DEFAULT '1',
  `network_list` text,
  `perm_super` tinyint(1) NOT NULL DEFAULT '0',
  `codpromo` varchar(10) DEFAULT NULL,
  `perm_invoices` smallint(6) NOT NULL DEFAULT '0',
  `perm_admin` smallint(6) NOT NULL DEFAULT '0',
  `perm_admin_codpromo` int(1) NOT NULL DEFAULT '1',
  `perm_admin_stats` smallint(6) NOT NULL DEFAULT '0',
  `tutorial_video` smallint(6) NOT NULL DEFAULT '2',
  `enabled_widgets` smallint(5) UNSIGNED NOT NULL DEFAULT '39' COMMENT 'enabled widgets (binary converted value 01 mapped by a java enum)',
  `google_oauth_fresh` varchar(250) DEFAULT NULL COMMENT 'Token (Refresh) used by google OAuth 2.0',
  `perm_support` tinyint(4) NOT NULL DEFAULT '0',
  `perm_subscription` tinyint(4) NOT NULL DEFAULT '0',
  `department` varchar(20) DEFAULT NULL,
  `perm_assign_codpromo` tinyint(4) NOT NULL DEFAULT '0',
  `trace_history` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Trace activity of this user',
  `photo_url` varchar(250) DEFAULT NULL,
  `reservation_group` tinyint(1) DEFAULT '1',
  `perm_admin_users` smallint(6) NOT NULL DEFAULT '0',
  `perm_price` tinyint(3) UNSIGNED NOT NULL DEFAULT '1',
  `perm_access_be` tinyint(1) DEFAULT '0',
  `bookingEngine` tinyint(1) DEFAULT '0',
  `perm_admin_settings` int(1) DEFAULT '0' COMMENT 'Can do specific tasks, like delete accomodations, edit some invoices, etc...',
  `labs_user` tinyint(1) DEFAULT '0',
  `calendar_show` int(11) NOT NULL DEFAULT '463',
  `perm_admin_dylog` int(1) DEFAULT '0',
  `resa_export` bigint(20) UNSIGNED DEFAULT NULL,
  `reseller_id` int(11) DEFAULT NULL,
  `perm_source` smallint(6) NOT NULL DEFAULT '2',
  `perm_admin_text` smallint(6) NOT NULL DEFAULT '0',
  `edit_langs` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `layout_menu` smallint(6) NOT NULL DEFAULT '0',
  `theme_dark` tinyint(1) NOT NULL DEFAULT '0',
  `perm_reservation_agency_id` int(11) DEFAULT NULL,
  `perm_admin_api` tinyint(1) NOT NULL DEFAULT '0',
  `perm_licence` tinyint(4) NOT NULL DEFAULT '2',
  `perm_chat` tinyint(4) NOT NULL DEFAULT '1',
  `experienced` tinyint(1) NOT NULL DEFAULT '0',
  `show_widgets` tinyint(1) DEFAULT '1',
  `hide_read_threads` tinyint(1) DEFAULT NULL,
  `show_archived_threads` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `ob_users`
--

INSERT INTO `ob_users` (`id`, `username`, `password`, `mfa_key`, `enabled`, `firstname`, `lastname`, `email`, `codice`, `user_type`, `perm_room`, `perm_resa`, `perm_avail`, `perm_sets`, `perm_bill`, `perm_cash_closing`, `perm_cancel_cash_closing`, `perm_users`, `perm_housekeeping`, `perm_checkin`, `userlevel`, `last_login`, `password_change_time`, `showtimeline`, `perm_stats`, `default_table_rows`, `show_network`, `network_list`, `perm_super`, `codpromo`, `perm_invoices`, `perm_admin`, `perm_admin_codpromo`, `perm_admin_stats`, `tutorial_video`, `enabled_widgets`, `google_oauth_fresh`, `perm_support`, `perm_subscription`, `department`, `perm_assign_codpromo`, `trace_history`, `photo_url`, `reservation_group`, `perm_admin_users`, `perm_price`, `perm_access_be`, `bookingEngine`, `perm_admin_settings`, `labs_user`, `calendar_show`, `perm_admin_dylog`, `resa_export`, `reseller_id`, `perm_source`, `perm_admin_text`, `edit_langs`, `layout_menu`, `theme_dark`, `perm_reservation_agency_id`, `perm_admin_api`, `perm_licence`, `perm_chat`, `experienced`, `show_widgets`, `hide_read_threads`, `show_archived_threads`) VALUES
(1, 'octocm', '1f0375dc7cc49737cc1f802fa127404208b01e10e84d38d1e1bc3fca8a177467', '6NNUF55YS7NZMULFRIBUQ6ZVOUSMZJ2I', 1, 'Octorate', 'Channel Manager', 'server@octorate.com', NULL, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 100, NULL, '2019-03-20 23:00:00', 1, 0, 10, 1, NULL, 0, NULL, 0, 0, 2, 0, 2, 39, NULL, 0, 0, NULL, 0, 1, NULL, 1, 0, 1, 0, 0, 0, 1, 4559, 0, NULL, NULL, 2, 2, 0, 0, 0, NULL, 0, 0, 2, 0, 1, NULL, NULL),
(2, 'octophp', 'xyz', 'CTR7HACXFXTCGGKY7GZLYVO3EG3LNTQR', 1, 'Octorate', 'PHP Service', 'server@octorate.com', NULL, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1, 100, NULL, '2018-11-13 13:22:37', 1, 0, 10, 1, NULL, 0, NULL, 0, 0, 2, 0, 2, 39, NULL, 0, 0, NULL, 0, 1, NULL, 1, 0, 1, 0, 0, 0, 1, 4559, 0, NULL, NULL, 2, 2, 0, 0, 0, NULL, 0, 0, 2, 0, 1, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ob_users`
--
ALTER TABLE `ob_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `ob_users_codice` (`codice`),
  ADD KEY `ob_users_type` (`user_type`),
  ADD KEY `ob_users_support` (`perm_support`),
  ADD KEY `ob_users_assign_codpromo` (`perm_assign_codpromo`),
  ADD KEY `ob_users_reseller` (`reseller_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ob_users`
--
ALTER TABLE `ob_users`
  MODIFY `id` int(6) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52295;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
