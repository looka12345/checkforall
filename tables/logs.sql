-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 02, 2023 at 08:28 AM
-- Server version: 10.4.27-MariaDB
-- PHP Version: 8.2.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
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
  `confirmation_req` mediumblob DEFAULT NULL,
  `confirmation_resp` mediumblob DEFAULT NULL,
  `insert_time` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `php_stream_reservation_log`
--

CREATE TABLE `php_stream_reservation_log` (
  `id` int(11) NOT NULL,
  `hot_sites_id` int(11) NOT NULL,
  `hot_user_id` int(11) DEFAULT NULL,
  `reqResp` longtext CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `insert_time` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `php_stream_reservation_xml`
--

CREATE TABLE `php_stream_reservation_xml` (
  `id` int(11) NOT NULL,
  `hot_sites_id` int(3) NOT NULL,
  `property_id` varchar(60) DEFAULT NULL,
  `refer` varchar(60) NOT NULL,
  `status` varchar(30) NOT NULL,
  `xml` blob DEFAULT NULL,
  `insert_time` datetime NOT NULL DEFAULT current_timestamp(),
  `lastmodify_time` datetime DEFAULT NULL,
  `checkout` varchar(10) DEFAULT NULL,
  `processed` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `php_stream_reservation_xml`
--

INSERT INTO `php_stream_reservation_xml` (`id`, `hot_sites_id`, `property_id`, `refer`, `status`, `xml`, `insert_time`, `lastmodify_time`, `checkout`, `processed`) VALUES
(1, 34445, 'WDJR4GB', 'RES123', 'cancel', 0x78dad594cd8eda301080ef3c45e433db262101caa940da0a545194a5eaa1da831b0f495a278e1ca7dd08f1ee1d070236cb4abd9653986ffe673c87c1c0711c22a106f99baa5c942b466624fef0e8f923323cd14c28e09dfc5bb48e834f8b1ed48ab6312d5320b3c3a01369699241f26b55a2baeffafe83e73d78eed9e282bf34cae43ee9f0f1ec57244953d132692dbf94355cd564e69bbe72ce24602cf7a5709e026a7fefe5f873fbef272b9c51fd4e28caed6a1a89be742ae4eb6364d4410bd1946a017b2161479f31afc0bda5f3bd02798253d78a59d1b6805245a068ce6b3b22956cd3143f40624c1c43108e27d377d3c9781c06a3d1c86c256a668233901b5ae010c89a96e044020c1d78ae72d9465469ee866ffdd0ee75da40adcc3e1d8c86917d2e6b6538bf3ad694d30bb48276710b2c0cc14fb46202deebbf6f1251d85a55264a30cbb4316538c81ab323d349e83b5123335a38b1a0cc897396c21f21d8d0d9ac1d771284ee8d6d0a4bac3a1552cf6eae9787f4fcd87d3cf5f317a28851f5d52eb053f7eead736fbf6b2be89e48b4f87c43d178cb69b9144c3b59cc639bbfb6607777ccf3dd3b0ad735f382cb8e1f87ff548bf75fd462cd2bc1d3009c770f762b787e73275ee255a46fdad65b6e8d6af1ac35b23ee5b3ec4f961f5c39a3ed2df6aeb4829272a52bfdd8bd913ccdcefb85c91eff02350c4bce, '2023-02-02 10:18:37', '2023-02-02 05:48:37', '2022-11-12', 0),
(2, 34445, 'WDJR4GB', 'RES123', 'cancel', 0x78dad594cd8eda301080ef3c45e433db262101caa940da0a545194a5eaa1da831b0f495a278e1ca7dd08f1ee1d070236cb4abd9653986ffe673c87c1c0711c22a106f99baa5c942b466624fef0e8f923323cd14c28e09dfc5bb48e834f8b1ed48ab6312d5320b3c3a01369699241f26b55a2baeffafe83e73d78eed9e282bf34cae43ee9f0f1ec57244953d132692dbf94355cd564e69bbe72ce24602cf7a5709e026a7fefe5f873fbef272b9c51fd4e28caed6a1a89be742ae4eb6364d4410bd1946a017b2161479f31afc0bda5f3bd02798253d78a59d1b6805245a068ce6b3b22956cd3143f40624c1c43108e27d377d3c9781c06a3d1c86c256a668233901b5ae010c89a96e044020c1d78ae72d9465469ee866ffdd0ee75da40adcc3e1d8c86917d2e6b6538bf3ad694d30bb48276710b2c0cc14fb46202deebbf6f1251d85a55264a30cbb4316538c81ab323d349e83b5123335a38b1a0cc897396c21f21d8d0d9ac1d771284ee8d6d0a4bac3a1552cf6eae9787f4fcd87d3cf5f317a28851f5d52eb053f7eead736fbf6b2be89e48b4f87c43d178cb69b9144c3b59cc639bbfb6607777ccf3dd3b0ad735f382cb8e1f87ff548bf75fd462cd2bc1d3009c770f762b787e73275ee255a46fdad65b6e8d6af1ac35b23ee5b3ec4f961f5c39a3ed2df6aeb4829272a52bfdd8bd913ccdcefb85c91eff02350c4bce, '2023-02-02 10:19:25', '2023-02-02 05:49:25', '2022-11-12', 0),
(3, 34445, 'WDJR4GB', 'RES123', 'confirm', 0x78dad594cd8eda301080ef3c45e433db262101caa940da0a545194a5eaa1da831b0f495a278e1ca7dd08f1ee1d070236cb4abd9653986ffe673c87c1c0711c22a106f99baa5c942b466624fef0e8f923323cd14c28e09dfc5bb48e834f8b1ed48ab6312d5320b3c3a01369699241f26b55a2baeffafe83e73d78eed9e282bf34cae43ee9f0f1ec57244953d132692dbf94355cd564e69bbe72ce24602cf7a5709e026a7fefe5f873fbef272b9c51fd4e28caed6a1a89be742ae4eb6364d4410bd1946a017b2161479f31afc0bda5f3bd02798253d78a59d1b6805245a068ce6b3b22956cd3143f40624c1c43108e27d377d3c9781c06a3d1c86c256a668233901b5ae010c89a96e044020c1d78ae72d9465469ee866ffdd0ee75da40adcc3e1d8c86917d2e6b6538bf3ad694d30bb48276710b2c0cc14fb46202deebbf6f1251d85a55264a30cbb4316538c81ab323d349e83b5123335a38b1a0cc897396c21f21d8d0d9ac1d771284ee8d6d0a4bac3a1552cf6eae9787f4fcd87d3cf5f317a28851f5d52eb053f7eead736fbf6b2be89e48b4f87c43d178cb69b9144c3b59cc639bbfb6607777ccf3dd3b0ad735f382cb8e1f87ff548bf75fd462cd2bc1d3009c770f762b787e73275ee255a46fdad65b6e8d6af1ac35b23ee5b3ec4f961f5c39a3ed2df6aeb4829272a52bfdd8bd913ccdcefb85c91eff02350c4bce, '2023-02-02 10:22:20', '2023-02-02 05:52:20', '2022-11-12', 0),
(4, 34445, 'WDJR4GB', 'RES123', 'cancel', 0x78dad594cd8eda301080ef3c45e433db262101caa940da0a545194a5eaa1da831b0f495a278e1ca7dd08f1ee1d070236cb4abd9653986ffe673c87c1c0711c22a106f99baa5c942b466624fef0e8f923323cd14c28e09dfc5bb48e834f8b1ed48ab6312d5320b3c3a01369699241f26b55a2baeffafe83e73d78eed9e282bf34cae43ee9f0f1ec57244953d132692dbf94355cd564e69bbe72ce24602cf7a5709e026a7fefe5f873fbef272b9c51fd4e28caed6a1a89be742ae4eb6364d4410bd1946a017b2161479f31afc0bda5f3bd02798253d78a59d1b6805245a068ce6b3b22956cd3143f40624c1c43108e27d377d3c9781c06a3d1c86c256a668233901b5ae010c89a96e044020c1d78ae72d9465469ee866ffdd0ee75da40adcc3e1d8c86917d2e6b6538bf3ad694d30bb48276710b2c0cc14fb46202deebbf6f1251d85a55264a30cbb4316538c81ab323d349e83b5123335a38b1a0cc897396c21f21d8d0d9ac1d771284ee8d6d0a4bac3a1552cf6eae9787f4fcd87d3cf5f317a28851f5d52eb053f7eead736fbf6b2be89e48b4f87c43d178cb69b9144c3b59cc639bbfb6607777ccf3dd3b0ad735f382cb8e1f87ff548bf75fd462cd2bc1d3009c770f762b787e73275ee255a46fdad65b6e8d6af1ac35b23ee5b3ec4f961f5c39a3ed2df6aeb4829272a52bfdd8bd913ccdcefb85c91eff02350c4bce, '2023-02-02 10:26:32', '2023-02-02 05:56:32', '2022-11-12', 0),
(5, 34445, 'WDJR4GB', 'RES123', 'cancel', 0x78dad594cd8eda301080ef3c45e433db262101caa940da0a545194a5eaa1da831b0f495a278e1ca7dd08f1ee1d070236cb4abd9653986ffe673c87c1c0711c22a106f99baa5c942b466624fef0e8f923323cd14c28e09dfc5bb48e834f8b1ed48ab6312d5320b3c3a01369699241f26b55a2baeffafe83e73d78eed9e282bf34cae43ee9f0f1ec57244953d132692dbf94355cd564e69bbe72ce24602cf7a5709e026a7fefe5f873fbef272b9c51fd4e28caed6a1a89be742ae4eb6364d4410bd1946a017b2161479f31afc0bda5f3bd02798253d78a59d1b6805245a068ce6b3b22956cd3143f40624c1c43108e27d377d3c9781c06a3d1c86c256a668233901b5ae010c89a96e044020c1d78ae72d9465469ee866ffdd0ee75da40adcc3e1d8c86917d2e6b6538bf3ad694d30bb48276710b2c0cc14fb46202deebbf6f1251d85a55264a30cbb4316538c81ab323d349e83b5123335a38b1a0cc897396c21f21d8d0d9ac1d771284ee8d6d0a4bac3a1552cf6eae9787f4fcd87d3cf5f317a28851f5d52eb053f7eead736fbf6b2be89e48b4f87c43d178cb69b9144c3b59cc639bbfb6607777ccf3dd3b0ad735f382cb8e1f87ff548bf75fd462cd2bc1d3009c770f762b787e73275ee255a46fdad65b6e8d6af1ac35b23ee5b3ec4f961f5c39a3ed2df6aeb4829272a52bfdd8bd913ccdcefb85c91eff02350c4bce, '2023-02-02 11:20:17', '2023-02-02 06:50:17', '2022-11-12', 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `php_stream_reservation_confirmation`
--
ALTER TABLE `php_stream_reservation_confirmation`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `php_stream_reservation_log`
--
ALTER TABLE `php_stream_reservation_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `hot_sites_id` (`hot_sites_id`),
  ADD KEY `insert_time` (`insert_time`),
  ADD KEY `hot_user_id` (`hot_user_id`);

--
-- Indexes for table `php_stream_reservation_xml`
--
ALTER TABLE `php_stream_reservation_xml`
  ADD PRIMARY KEY (`id`),
  ADD KEY `hot_sites_id` (`hot_sites_id`),
  ADD KEY `refer` (`refer`),
  ADD KEY `lastmodify_time` (`lastmodify_time`),
  ADD KEY `processed` (`processed`),
  ADD KEY `property_id` (`property_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `php_stream_reservation_log`
--
ALTER TABLE `php_stream_reservation_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `php_stream_reservation_xml`
--
ALTER TABLE `php_stream_reservation_xml`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
