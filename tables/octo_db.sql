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
-- Database: `octo_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `backup_assignment`
--

CREATE TABLE `backup_assignment` (
  `id` int(11) NOT NULL,
  `codice` varchar(10) NOT NULL,
  `siteNm` varchar(64) NOT NULL COMMENT 'site name',
  `db_sites_id` int(11) DEFAULT NULL,
  `extRoomID` varchar(64) NOT NULL COMMENT 'external room id from hot_site_room',
  `room_idcr` int(11) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `backup_assignment`
--

INSERT INTO `backup_assignment` (`id`, `codice`, `siteNm`, `db_sites_id`, `extRoomID`, `room_idcr`, `timestamp`) VALUES
(5545, '333', 'hfbnghm', 334, 'hey there', 433, '2023-01-12 13:22:41');

-- --------------------------------------------------------

--
-- Table structure for table `clienti`
--

CREATE TABLE `clienti` (
  `num` int(11) NOT NULL,
  `codice` varchar(10) NOT NULL,
  `codicefree` varchar(20) NOT NULL DEFAULT '',
  `nome` varchar(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `sig` varchar(6) NOT NULL DEFAULT '',
  `nomeresp` varchar(150) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `responsabile` varchar(150) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `responsabile2` varchar(30) NOT NULL DEFAULT 'viola',
  `tel` varchar(60) DEFAULT NULL,
  `fax` varchar(14) DEFAULT NULL,
  `capienza` smallint(6) NOT NULL DEFAULT 0,
  `cell` varchar(30) DEFAULT NULL,
  `password` varchar(50) NOT NULL DEFAULT '',
  `mail` varchar(150) DEFAULT NULL,
  `mail_2` varchar(100) NOT NULL DEFAULT '',
  `mfisc` varchar(50) NOT NULL DEFAULT '',
  `web` varchar(200) DEFAULT NULL,
  `visite` int(6) NOT NULL DEFAULT 0,
  `session` varchar(30) NOT NULL DEFAULT '',
  `piva` varchar(60) DEFAULT NULL,
  `cfisc` varchar(20) NOT NULL DEFAULT '',
  `indirizzo` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `civico` varchar(8) DEFAULT NULL,
  `regione` varchar(13) NOT NULL DEFAULT '' COMMENT 'last mail sent',
  `provincia` varchar(50) DEFAULT NULL,
  `nazione` char(2) DEFAULT NULL,
  `areag` varchar(20) NOT NULL DEFAULT '1' COMMENT 'IF YES DISPALY MULTIPLE BOOK BUTTON',
  `citta` varchar(150) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `locality` varchar(50) DEFAULT NULL,
  `cap` varchar(10) DEFAULT NULL,
  `note` text CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `note2` text CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `intestazione` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `intestazioneind` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `intestazionecit` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `intestazionenaz` char(2) DEFAULT NULL,
  `modifica` varchar(20) NOT NULL DEFAULT '',
  `carta` int(1) NOT NULL DEFAULT 1,
  `noanti` varchar(9) NOT NULL DEFAULT '0' COMMENT 'if 1 free user',
  `percentuale` char(3) DEFAULT NULL,
  `datains` timestamp NOT NULL DEFAULT current_timestamp(),
  `rating` smallint(6) NOT NULL DEFAULT 0,
  `flag_invitati` char(1) DEFAULT NULL,
  `sat` text DEFAULT NULL,
  `depo` int(1) NOT NULL DEFAULT 0 COMMENT 'used by payamit',
  `imp_depo` varchar(10) NOT NULL DEFAULT '',
  `dafa` int(4) NOT NULL DEFAULT 0 COMMENT 'total number of room',
  `daymail` tinyint(2) DEFAULT NULL COMMENT 'no more used was for ready to welcome',
  `datavisto` varchar(10) NOT NULL DEFAULT '',
  `cap_fisc` varchar(6) NOT NULL DEFAULT '',
  `citta_fisc` varchar(50) NOT NULL DEFAULT '',
  `cod_fiscale` varchar(20) NOT NULL DEFAULT '',
  `accountpaypal` varchar(50) DEFAULT NULL,
  `accountpayplug` varchar(50) NOT NULL DEFAULT '',
  `geocode` int(1) NOT NULL DEFAULT 0 COMMENT 'if 1 PMS enabled',
  `fatt_mensile` char(1) NOT NULL DEFAULT '' COMMENT 'if 1 show mappind edit also show mappind read only',
  `garanzia` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'if 1 mother of fatturare network',
  `pulizie` varchar(10) NOT NULL DEFAULT '',
  `colazione` varchar(9) NOT NULL DEFAULT '' COMMENT 'mettere un alfanumerico per permettere link altre strutture',
  `cancpol` smallint(6) NOT NULL DEFAULT 1,
  `message` text DEFAULT NULL,
  `anticipo` varchar(30) DEFAULT NULL,
  `pay` tinyint(1) NOT NULL DEFAULT 0,
  `payplug` int(1) NOT NULL DEFAULT 0,
  `bon` int(11) NOT NULL DEFAULT 0,
  `beneficiary` varchar(255) DEFAULT NULL,
  `iban` varchar(200) DEFAULT NULL,
  `validuntil` int(2) NOT NULL DEFAULT 0,
  `cc` tinyint(1) NOT NULL DEFAULT 1,
  `stile` varchar(20) NOT NULL DEFAULT '',
  `mincheckin` varchar(10) NOT NULL DEFAULT '',
  `maxcheckin` varchar(10) NOT NULL DEFAULT '',
  `versione` int(1) NOT NULL DEFAULT 1,
  `versione2` int(1) NOT NULL DEFAULT 0,
  `octosocial` int(1) NOT NULL DEFAULT 0,
  `scadenza1` varchar(20) NOT NULL DEFAULT '',
  `scadenza2` varchar(20) NOT NULL DEFAULT '',
  `scadenza3` varchar(20) NOT NULL DEFAULT '',
  `scadenza4` varchar(20) NOT NULL DEFAULT '',
  `upgrade` int(1) NOT NULL DEFAULT 0,
  `cartacredito` varchar(20) NOT NULL DEFAULT '',
  `scadenzamese` varchar(4) NOT NULL DEFAULT '',
  `scadenzaanno` varchar(4) NOT NULL DEFAULT '',
  `cvv` varchar(4) NOT NULL DEFAULT '',
  `visa` int(1) NOT NULL DEFAULT 1,
  `mastercard` int(1) NOT NULL DEFAULT 1,
  `amex` int(1) NOT NULL DEFAULT 1,
  `diners` int(1) NOT NULL DEFAULT 0,
  `logo` varchar(150) NOT NULL DEFAULT '',
  `calendario` tinyint(1) NOT NULL DEFAULT 1,
  `calendario2` tinyint(1) NOT NULL DEFAULT 1,
  `extra` text DEFAULT NULL,
  `accesso` datetime DEFAULT NULL,
  `icalnome` varchar(255) DEFAULT '',
  `ip` varchar(60) NOT NULL DEFAULT '',
  `main` varchar(50) NOT NULL DEFAULT '' COMMENT 'network BE',
  `main_a` varchar(50) NOT NULL DEFAULT '' COMMENT 'Autoaccess',
  `main_b` varchar(50) NOT NULL DEFAULT '' COMMENT 'network name for api be',
  `active` tinyint(1) NOT NULL DEFAULT 0,
  `active2` int(1) NOT NULL DEFAULT 0,
  `ifnoavails` tinyint(1) NOT NULL DEFAULT 0,
  `update_resa` smallint(2) NOT NULL DEFAULT 1,
  `autofetch` smallint(2) NOT NULL DEFAULT 1,
  `codpromo` varchar(11) NOT NULL DEFAULT '',
  `cancel_flag` int(2) NOT NULL DEFAULT 1,
  `currency` char(3) NOT NULL DEFAULT 'EUR',
  `running_status` int(2) NOT NULL DEFAULT 0,
  `starttime` datetime DEFAULT NULL,
  `standmail` text DEFAULT NULL,
  `nfatt` int(11) NOT NULL DEFAULT 1 COMMENT 'n° of receipt for billing',
  `nric` int(5) NOT NULL DEFAULT 1,
  `nric2` int(5) NOT NULL DEFAULT 0 COMMENT 'fatture generiche',
  `stax` decimal(10,2) NOT NULL DEFAULT 0.00,
  `stax2` smallint(6) NOT NULL DEFAULT 99,
  `stax3` smallint(6) NOT NULL DEFAULT 0,
  `ok` varchar(40) NOT NULL DEFAULT '1',
  `dmin` int(1) NOT NULL DEFAULT 0,
  `dispres` int(11) DEFAULT NULL,
  `rateTypeLevel` int(1) NOT NULL DEFAULT 0,
  `auto_close` int(1) NOT NULL DEFAULT 0,
  `day_before` int(1) NOT NULL DEFAULT 0 COMMENT 'day before autoclose applied if choosen',
  `hwrateLevel` int(1) NOT NULL DEFAULT 1,
  `paga` varchar(10) NOT NULL DEFAULT '' COMMENT 'montthy payng',
  `facto` tinyint(1) NOT NULL DEFAULT 0,
  `resadays` tinyint(1) NOT NULL DEFAULT 0,
  `confirm_now` int(1) NOT NULL DEFAULT 1,
  `bookXml_RoomLevel` int(1) NOT NULL DEFAULT 1,
  `credit` decimal(10,2) NOT NULL DEFAULT 0.00,
  `hourly_credit` decimal(10,2) DEFAULT 0.00,
  `client_gmt` varchar(4) NOT NULL DEFAULT '0' COMMENT 'not used!!! check on clmps ',
  `tasse` decimal(5,2) NOT NULL DEFAULT 0.00,
  `webpack` tinyint(1) NOT NULL DEFAULT 1,
  `scadenzawebpack` varchar(10) NOT NULL DEFAULT '' COMMENT 'scadenza realplanning',
  `easy` int(1) NOT NULL DEFAULT 0 COMMENT 'if 1 di Arco if 2 discoveroom',
  `tripCsvActive` int(1) NOT NULL DEFAULT 0,
  `trivagoActive` int(1) NOT NULL DEFAULT 0,
  `Googlehpaactive` tinyint(1) NOT NULL DEFAULT 1,
  `fatturare_network` varchar(30) NOT NULL DEFAULT '',
  `zoho_contact_id` int(2) DEFAULT NULL COMMENT 'status 7 TOP CUSTOMER  1 demo, 2 import,3 prova,4 paga, 5 prova fallita 6 figlio',
  `lastupdate` bigint(20) DEFAULT NULL,
  `intercom_id` varchar(12) DEFAULT NULL,
  `churn` varchar(10) DEFAULT NULL,
  `password_change_time` bigint(20) DEFAULT NULL,
  `cartacreditotoken` varchar(100) DEFAULT NULL,
  `email_lang` smallint(6) DEFAULT NULL,
  `migrate_user` int(11) DEFAULT NULL,
  `bicswift` varchar(11) DEFAULT NULL,
  `noshowpolicy` smallint(6) NOT NULL DEFAULT 1,
  `cash` tinyint(1) NOT NULL DEFAULT 1,
  `cash_advance` smallint(6) NOT NULL DEFAULT 999,
  `vies_ok` int(1) NOT NULL DEFAULT 0,
  `timezone` varchar(40) NOT NULL DEFAULT 'Europe/Rome',
  `category` smallint(6) NOT NULL DEFAULT 0,
  `cancel_expired` tinyint(1) NOT NULL DEFAULT 1,
  `autoaccessvalid` tinyint(1) NOT NULL DEFAULT 0,
  `networkcompact` tinyint(1) NOT NULL DEFAULT 1,
  `minstaycheckin` tinyint(1) NOT NULL DEFAULT 1,
  `phone_mobile` varchar(20) DEFAULT NULL,
  `date_format` varchar(20) DEFAULT NULL,
  `tax_included` tinyint(1) DEFAULT 1 COMMENT 'Specify if this accomodation has taxes included or they have to be added',
  `payoff_days` int(3) DEFAULT 0,
  `payoff_percent` int(3) DEFAULT 0,
  `bankname` varchar(100) DEFAULT NULL,
  `bankrouting` varchar(100) DEFAULT NULL,
  `deposit_ota` datetime DEFAULT NULL,
  `dylog_id` varchar(10) DEFAULT NULL,
  `referral` int(2) DEFAULT NULL,
  `prefer_logo` tinyint(1) DEFAULT 1 COMMENT 'Prefer logo of the accomodation instead of the octorate logo in voucher/reservation list',
  `payoff_notref` tinyint(1) DEFAULT 0,
  `company_type` int(2) DEFAULT NULL,
  `be_stop_sales` tinyint(1) DEFAULT 1,
  `city_tax_type` int(1) UNSIGNED DEFAULT 0,
  `city_tax_max` decimal(10,2) DEFAULT NULL,
  `deposit_no_refundable` tinyint(1) DEFAULT 1,
  `gdpr_no` datetime DEFAULT NULL,
  `gdpr_sent` int(11) NOT NULL DEFAULT 0,
  `prefer_lang` tinyint(1) DEFAULT 1,
  `history` tinyint(1) NOT NULL DEFAULT 1,
  `payoff_start` datetime DEFAULT NULL,
  `homeaway_json` text CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `octorate_head` int(11) DEFAULT NULL,
  `head` int(11) DEFAULT NULL,
  `head_auth` tinyint(1) DEFAULT 0,
  `master_c` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'true if master account for calendar',
  `maxcheckout` int(2) DEFAULT NULL,
  `sepa_token` varchar(100) DEFAULT NULL,
  `sepa_iban` varchar(30) DEFAULT NULL,
  `whitelabel_id` int(11) DEFAULT NULL,
  `phone2` varchar(20) DEFAULT NULL,
  `piperno` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'if 2 show realplanning on app',
  `city_tax_net` int(11) DEFAULT NULL,
  `sales_email` varchar(100) DEFAULT NULL,
  `civic_number` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci COMMENT='Anagrafica affiliati';

--
-- Dumping data for table `clienti`
--

INSERT INTO `clienti` (`num`, `codice`, `codicefree`, `nome`, `sig`, `nomeresp`, `responsabile`, `responsabile2`, `tel`, `fax`, `capienza`, `cell`, `password`, `mail`, `mail_2`, `mfisc`, `web`, `visite`, `session`, `piva`, `cfisc`, `indirizzo`, `civico`, `regione`, `provincia`, `nazione`, `areag`, `citta`, `locality`, `cap`, `note`, `note2`, `intestazione`, `intestazioneind`, `intestazionecit`, `intestazionenaz`, `modifica`, `carta`, `noanti`, `percentuale`, `datains`, `rating`, `flag_invitati`, `sat`, `depo`, `imp_depo`, `dafa`, `daymail`, `datavisto`, `cap_fisc`, `citta_fisc`, `cod_fiscale`, `accountpaypal`, `accountpayplug`, `geocode`, `fatt_mensile`, `garanzia`, `pulizie`, `colazione`, `cancpol`, `message`, `anticipo`, `pay`, `payplug`, `bon`, `beneficiary`, `iban`, `validuntil`, `cc`, `stile`, `mincheckin`, `maxcheckin`, `versione`, `versione2`, `octosocial`, `scadenza1`, `scadenza2`, `scadenza3`, `scadenza4`, `upgrade`, `cartacredito`, `scadenzamese`, `scadenzaanno`, `cvv`, `visa`, `mastercard`, `amex`, `diners`, `logo`, `calendario`, `calendario2`, `extra`, `accesso`, `icalnome`, `ip`, `main`, `main_a`, `main_b`, `active`, `active2`, `ifnoavails`, `update_resa`, `autofetch`, `codpromo`, `cancel_flag`, `currency`, `running_status`, `starttime`, `standmail`, `nfatt`, `nric`, `nric2`, `stax`, `stax2`, `stax3`, `ok`, `dmin`, `dispres`, `rateTypeLevel`, `auto_close`, `day_before`, `hwrateLevel`, `paga`, `facto`, `resadays`, `confirm_now`, `bookXml_RoomLevel`, `credit`, `hourly_credit`, `client_gmt`, `tasse`, `webpack`, `scadenzawebpack`, `easy`, `tripCsvActive`, `trivagoActive`, `Googlehpaactive`, `fatturare_network`, `zoho_contact_id`, `lastupdate`, `intercom_id`, `churn`, `password_change_time`, `cartacreditotoken`, `email_lang`, `migrate_user`, `bicswift`, `noshowpolicy`, `cash`, `cash_advance`, `vies_ok`, `timezone`, `category`, `cancel_expired`, `autoaccessvalid`, `networkcompact`, `minstaycheckin`, `phone_mobile`, `date_format`, `tax_included`, `payoff_days`, `payoff_percent`, `bankname`, `bankrouting`, `deposit_ota`, `dylog_id`, `referral`, `prefer_logo`, `payoff_notref`, `company_type`, `be_stop_sales`, `city_tax_type`, `city_tax_max`, `deposit_no_refundable`, `gdpr_no`, `gdpr_sent`, `prefer_lang`, `history`, `payoff_start`, `homeaway_json`, `octorate_head`, `head`, `head_auth`, `master_c`, `maxcheckout`, `sepa_token`, `sepa_iban`, `whitelabel_id`, `phone2`, `piperno`, `city_tax_net`, `sales_email`, `civic_number`) VALUES
(1020, '333', 'twenty ', 'Lorem ipsum', 'ashu', 'Varanasi ', 'worker', 'viola', '9919568777', '56755665', 0, '8947679899', 'amit123', 'kghhfhf123@gmail.com', 'fngbbmgh12@gmail.com', 'akashi', 'www.maheshhotel.com', 0, 'first', 'namish', 'raman', 'blah blah', 'well', 'Uttar Pradesh', 'namastey', 'ok', '1', 'India', 'Mahmoorganj', 'Nathupur', NULL, NULL, NULL, NULL, NULL, NULL, 'one', 1, 'relax', NULL, '2023-01-02 12:47:10', 0, NULL, NULL, 0, 'ten', 5, NULL, '', '', '', '', 'rajesh@', '', 9, '', 0, 'wonder', '', 1, 'i hate you', NULL, 1, 0, 80, NULL, NULL, 22, 1, 'new', 'one', 'ten', 1, 0, 5, '', '', '', '', 0, '', '', '', 'twen', 1, 1, 1, 2, '', 1, 1, NULL, '0000-00-00 00:00:00', '', '', '', 'name', 'ritik', 1, 0, 0, 1, 1, 'bdfhf', 1, 'EUR', 0, NULL, NULL, 1, 1, 0, '0.00', 99, 0, 'ok', 0, NULL, 0, 0, 0, 1, '', 0, 0, 1, 1, '0.00', '0.00', '0', '0.00', 1, '', 0, 0, 0, 1, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 1, 999, 0, 'Europe/Rome', 0, 1, 0, 1, 1, NULL, NULL, 1, 0, 0, NULL, NULL, NULL, NULL, NULL, 1, 0, NULL, 1, 0, NULL, 1, NULL, 0, 1, 1, NULL, NULL, NULL, NULL, 0, 0, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `conferme`
--

CREATE TABLE `conferme` (
  `numero` int(11) NOT NULL COMMENT 'incremental number of reservation is auto increment',
  `voucher` varchar(9) NOT NULL DEFAULT '0',
  `data` varchar(10) NOT NULL DEFAULT '' COMMENT 'when reservation was registered',
  `modifica` varchar(10) NOT NULL DEFAULT '',
  `pag` varchar(6) NOT NULL DEFAULT '0',
  `ok` char(2) NOT NULL DEFAULT '',
  `first_name` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  `last_name` varchar(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  `cognome` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  `codice` varchar(30) NOT NULL DEFAULT '' COMMENT 'code of hotel on enginebooking',
  `codicepren` varchar(25) NOT NULL DEFAULT '' COMMENT 'reservation code assigned from us ddmma+cod you assign at the moment of import',
  `orario` varchar(90) NOT NULL DEFAULT '',
  `fat` varchar(6) NOT NULL DEFAULT '',
  `pax` varchar(20) NOT NULL DEFAULT '' COMMENT 'number of people of reservation if not provided name of room',
  `Total` varchar(255) NOT NULL DEFAULT '' COMMENT 'price',
  `notti` varchar(4) NOT NULL DEFAULT '' COMMENT 'number of night',
  `debito` varchar(12) NOT NULL DEFAULT '',
  `sca` varchar(5) NOT NULL DEFAULT '',
  `emessa` varchar(10) NOT NULL DEFAULT '',
  `pagato` int(10) NOT NULL DEFAULT 0,
  `saldato` char(1) NOT NULL DEFAULT '1',
  `commenti` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `partenza` varchar(10) NOT NULL DEFAULT '',
  `mailhotel` varchar(40) NOT NULL DEFAULT '',
  `datahotel` varchar(10) NOT NULL DEFAULT '',
  `submit_by` varchar(50) NOT NULL DEFAULT '',
  `mcsi` smallint(1) NOT NULL DEFAULT 0,
  `mhsi` smallint(6) NOT NULL DEFAULT 0,
  `periodo` varchar(222) NOT NULL DEFAULT '',
  `lingua` int(1) DEFAULT NULL,
  `country` varchar(60) DEFAULT NULL,
  `mandatac` smallint(1) NOT NULL DEFAULT 0,
  `mandatah` int(11) NOT NULL DEFAULT 0,
  `ricevutah` smallint(1) NOT NULL DEFAULT 0 COMMENT 'default set 2 for confirmed reservation',
  `ricevutac` smallint(1) NOT NULL DEFAULT 0,
  `nodisp` int(1) NOT NULL DEFAULT 0,
  `num` varchar(8) NOT NULL DEFAULT '',
  `feed` int(1) NOT NULL DEFAULT 0,
  `telefono` varchar(90) NOT NULL DEFAULT '',
  `prefix` varchar(5) NOT NULL DEFAULT '',
  `smsc` int(1) NOT NULL DEFAULT 0,
  `provenienza` varchar(20) NOT NULL DEFAULT '',
  `numpren` varchar(6) NOT NULL DEFAULT '',
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `segnala` varchar(10) NOT NULL DEFAULT '',
  `segnala2` varchar(10) NOT NULL DEFAULT '',
  `storia` varchar(30) NOT NULL DEFAULT '',
  `timestamp` timestamp NULL DEFAULT current_timestamp(),
  `fatturabile` int(1) NOT NULL DEFAULT 0,
  `datascarico` varchar(15) NOT NULL DEFAULT '',
  `tesoreria` varchar(20) NOT NULL DEFAULT '0' COMMENT 'extra of resa',
  `datascarico2` varchar(12) NOT NULL DEFAULT '',
  `provenienza2` varchar(50) NOT NULL DEFAULT '',
  `sito` varchar(100) NOT NULL DEFAULT '',
  `sito2` varchar(100) NOT NULL DEFAULT '',
  `dalc` varchar(20) NOT NULL DEFAULT '' COMMENT 'arrival time',
  `alc` varchar(20) NOT NULL DEFAULT '',
  `room_id` varchar(255) NOT NULL DEFAULT '',
  `room_id_ext` varchar(255) NOT NULL DEFAULT '',
  `room_id_pms` varchar(200) NOT NULL DEFAULT '0',
  `roomnome_ext` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `date_1x` int(11) NOT NULL DEFAULT 0,
  `date_2x` int(11) NOT NULL DEFAULT 0,
  `date_1` varchar(12) NOT NULL DEFAULT '',
  `date_2` varchar(12) NOT NULL DEFAULT '',
  `refer` varchar(255) NOT NULL DEFAULT '',
  `refer_disp` varchar(255) NOT NULL DEFAULT '',
  `LastModifyDateTime` varchar(60) NOT NULL DEFAULT '',
  `xml_rsp` varchar(10) NOT NULL DEFAULT '',
  `xml_rsp1` varchar(10) NOT NULL DEFAULT '',
  `resv_status` varchar(5) DEFAULT NULL,
  `res_fetch_date` varchar(15) NOT NULL DEFAULT '',
  `numerocarta` varchar(50) NOT NULL DEFAULT '',
  `scadenza` varchar(40) NOT NULL DEFAULT '',
  `cvv` varchar(10) NOT NULL DEFAULT '',
  `cartType` varchar(255) NOT NULL DEFAULT '',
  `nomecarta` varchar(255) DEFAULT NULL,
  `bot_run_date` date NOT NULL DEFAULT '2000-01-01',
  `date_1_old` varchar(12) NOT NULL DEFAULT '',
  `date_2_old` varchar(12) NOT NULL DEFAULT '',
  `update_Flag` int(11) NOT NULL DEFAULT 0,
  `update_error` tinyint(2) NOT NULL DEFAULT 0,
  `launch_ext` int(1) NOT NULL DEFAULT 0,
  `colore` varchar(12) DEFAULT NULL,
  `reminder` varchar(10) NOT NULL DEFAULT '',
  `group_time` datetime NOT NULL DEFAULT '2000-01-01 00:00:00',
  `status` varchar(20) NOT NULL DEFAULT '',
  `mail` int(1) NOT NULL DEFAULT 0,
  `bon` tinyint(1) NOT NULL DEFAULT 0,
  `room_count` varchar(300) NOT NULL DEFAULT '',
  `resvModificationDateTime` datetime NOT NULL DEFAULT '2000-01-01 00:00:00',
  `expedia_conf_flg` int(1) NOT NULL DEFAULT 0 COMMENT 'store expedia confirmation done correctly or not, if 1 then not confirmed',
  `notifystart` datetime NOT NULL DEFAULT '2000-01-01 00:00:00',
  `notifyend` datetime NOT NULL DEFAULT '2000-01-01 00:00:00',
  `notifysucc` varchar(255) NOT NULL DEFAULT '',
  `cc_encrypt` int(1) NOT NULL DEFAULT 0 COMMENT 'to save CC  encrypted flag',
  `quantity` int(1) NOT NULL DEFAULT 1,
  `check-in` varchar(20) NOT NULL DEFAULT '',
  `check-out` varchar(20) NOT NULL DEFAULT '',
  `document` int(1) NOT NULL DEFAULT 0,
  `document_number` varchar(20) NOT NULL DEFAULT '',
  `nationality` varchar(20) DEFAULT NULL,
  `nationality_full` varchar(255) NOT NULL DEFAULT '',
  `border_status` varchar(50) DEFAULT NULL,
  `original_pmsroom` varchar(255) NOT NULL DEFAULT '',
  `region` varchar(20) NOT NULL DEFAULT '',
  `region_full` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '' COMMENT 'This field contains the reservation ADDRESS',
  `noshow` varchar(20) DEFAULT NULL,
  `notifycount` int(5) NOT NULL DEFAULT 0,
  `split` varchar(9) NOT NULL DEFAULT '',
  `from_codice` int(11) DEFAULT NULL,
  `tokenid` varchar(60) NOT NULL DEFAULT '',
  `ccs_token` varchar(60) DEFAULT NULL,
  `secondary_card_id` int(11) DEFAULT NULL,
  `cc_invalid` int(1) NOT NULL DEFAULT 0,
  `hot_device` datetime NOT NULL DEFAULT '2000-01-01 00:00:00',
  `flightnum` varchar(100) NOT NULL DEFAULT '',
  `card_processor` smallint(6) NOT NULL DEFAULT 0,
  `completed` tinyint(1) DEFAULT 0 COMMENT 'True if the user has marked this reservation as done',
  `ccs_registered` tinyint(1) DEFAULT NULL COMMENT 'Associated 2nd ccs user with this resa',
  `city` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Customer city',
  `zip` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT 'Customer zip code',
  `note_time` tinyint(1) NOT NULL DEFAULT 1,
  `bkp_from_codice` varchar(250) DEFAULT NULL,
  `agenzia` tinytext DEFAULT NULL,
  `privacy_removed` tinyint(1) DEFAULT NULL,
  `group_id` int(11) UNSIGNED DEFAULT NULL,
  `id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci COMMENT='Alternative Card to use for payments';

--
-- Dumping data for table `conferme`
--

INSERT INTO `conferme` (`numero`, `voucher`, `data`, `modifica`, `pag`, `ok`, `first_name`, `last_name`, `cognome`, `codice`, `codicepren`, `orario`, `fat`, `pax`, `Total`, `notti`, `debito`, `sca`, `emessa`, `pagato`, `saldato`, `commenti`, `partenza`, `mailhotel`, `datahotel`, `submit_by`, `mcsi`, `mhsi`, `periodo`, `lingua`, `country`, `mandatac`, `mandatah`, `ricevutah`, `ricevutac`, `nodisp`, `num`, `feed`, `telefono`, `prefix`, `smsc`, `provenienza`, `numpren`, `note`, `segnala`, `segnala2`, `storia`, `timestamp`, `fatturabile`, `datascarico`, `tesoreria`, `datascarico2`, `provenienza2`, `sito`, `sito2`, `dalc`, `alc`, `room_id`, `room_id_ext`, `room_id_pms`, `roomnome_ext`, `date_1x`, `date_2x`, `date_1`, `date_2`, `refer`, `refer_disp`, `LastModifyDateTime`, `xml_rsp`, `xml_rsp1`, `resv_status`, `res_fetch_date`, `numerocarta`, `scadenza`, `cvv`, `cartType`, `nomecarta`, `bot_run_date`, `date_1_old`, `date_2_old`, `update_Flag`, `update_error`, `launch_ext`, `colore`, `reminder`, `group_time`, `status`, `mail`, `bon`, `room_count`, `resvModificationDateTime`, `expedia_conf_flg`, `notifystart`, `notifyend`, `notifysucc`, `cc_encrypt`, `quantity`, `check-in`, `check-out`, `document`, `document_number`, `nationality`, `nationality_full`, `border_status`, `original_pmsroom`, `region`, `region_full`, `noshow`, `notifycount`, `split`, `from_codice`, `tokenid`, `ccs_token`, `secondary_card_id`, `cc_invalid`, `hot_device`, `flightnum`, `card_processor`, `completed`, `ccs_registered`, `city`, `zip`, `note_time`, `bkp_from_codice`, `agenzia`, `privacy_removed`, `group_id`, `id`) VALUES
(35, 'three@', 'secret', 'private', 'though', 'ok', 'Rajesh', 'Srivastava', 'hehehe', '333', 'infinite', 'percent', 'true', 'true', 'ninety nine', 'two', 'debited', 'gg', 'akash', 0, '1', 'b fg gchgvbdf', 'high', 'cvbfng', ' nnvn', ' fv nv n', 0, 0, 'cv ngjn', 2, 'btjvdfmvvnb', 0, 0, 0, 0, 0, '  bvbbbv', 0, 'vb ghgnngn', 'ghnn', 0, 'nggghvngh', 'nhgn', 'xcvffcvc', ' vccnvb ng', 'dfbnv', 'mhmghgn', '2023-01-02 13:17:09', 0, 'bncbv', '0', 'gmhnvbnh', 'v bvbcvbcv vgnvb', ' cvfxgcngxfbcv', 'vb vnv vv ', 'nbvmhbbnvb', ' gnvv', 'b vnnb vbv ', 'vnghnv h  vb', 'ngg', 'jmbjb ', 0, 0, 'hnnnnn', 'hmmm', 'hjhgjcgh', 'v chnbv cvv', ' nngngvb', ' vbngg', 'yjghng', 'nggvv', ' nbmbbmb', 'v bv bv', 'ndhdhff', 'ngghmm', 'gnfghgh', 'bdbdbdf', '2000-01-01', 'hhhhhhh', 'cvvvvv', 0, 0, 0, NULL, '  v bb', '2000-01-01 00:00:00', 'cffcccc', 0, 0, 'mnnnn', '2000-01-01 00:00:00', 0, '2000-01-01 00:00:00', '2000-01-01 00:00:00', 'fvghhh', 0, 1, 'hghghbh', 'gnnghhg', 0, 'nhggghg', 'indian', ' fgnvngfvg', ' bvbcff', 'bbfdfdd', 'bbfbfdf', 'bfbdffd', '  bbvbvv', 0, ' vbvvbv ', 677, 'bnvbvbvb', ' vvvbvv', 53, 0, '2000-01-01 00:00:00', 'vbvbvhh', 0, 0, 1, 'vvvhhh', 'hhhhh', 1, ' vbbnbm', 'bfbgfhfggf', 1, 4555, 5664);

-- --------------------------------------------------------

--
-- Table structure for table `conferme_text`
--

CREATE TABLE `conferme_text` (
  `id` int(11) NOT NULL,
  `codice` varchar(30) NOT NULL,
  `refer` varchar(60) NOT NULL,
  `numero` int(5) NOT NULL,
  `xml_rsp` longtext DEFAULT NULL,
  `xml_rsp1` longtext DEFAULT NULL,
  `summary` text DEFAULT NULL,
  `flight_detail` varchar(500) NOT NULL DEFAULT '',
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `in_time` int(11) NOT NULL DEFAULT 0,
  `flightnum` varchar(200) NOT NULL DEFAULT '',
  `date_1` varchar(100) NOT NULL DEFAULT '',
  `arrival_time` varchar(200) NOT NULL DEFAULT '',
  `delay_time` varchar(200) NOT NULL DEFAULT '',
  `x_daybef_templ` varchar(30) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `conferme_text`
--

INSERT INTO `conferme_text` (`id`, `codice`, `refer`, `numero`, `xml_rsp`, `xml_rsp1`, `summary`, `flight_detail`, `timestamp`, `in_time`, `flightnum`, `date_1`, `arrival_time`, `delay_time`, `x_daybef_templ`) VALUES
(5444, '333', 'reference ', 35, 'bfhfff', 'nghjghmhggb', ' vbvbvbvbv', 'fhhhhg', '2023-01-12 13:22:00', 0, 'fgfggjgg', 'hhfhh', 'hghghfgng', 'bgfhfhbgf', 'hgfghg');

-- --------------------------------------------------------

--
-- Table structure for table `hot_room_type`
--

CREATE TABLE `hot_room_type` (
  `room_id` int(11) NOT NULL,
  `room_id_free` varchar(6) NOT NULL DEFAULT '',
  `client_id` varchar(10) NOT NULL,
  `room_type` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `room_com` varchar(50) DEFAULT NULL,
  `room_desc1` text CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `room_desc2` text CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `room_desc3` text CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `room_desc4` text CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `room_desc5` text CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `room_desc6` text CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `room_desc7` text CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `room_desc8` text CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `room_desc9` text CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `room_desc10` text CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `commission` varchar(5) NOT NULL DEFAULT '0',
  `prop_roomids` varchar(12) DEFAULT NULL,
  `no_members` int(2) NOT NULL DEFAULT 1,
  `no_members_dorm` int(1) NOT NULL DEFAULT 0,
  `isdorm` int(1) NOT NULL DEFAULT 0,
  `default_price` decimal(12,2) UNSIGNED NOT NULL DEFAULT 0.00,
  `default_room_num` char(3) DEFAULT NULL,
  `default_min_stay` int(2) NOT NULL DEFAULT 1,
  `added_time` datetime NOT NULL DEFAULT '2000-01-01 00:00:00',
  `photo` varchar(50) DEFAULT NULL,
  `impo` int(2) NOT NULL DEFAULT 99,
  `icalnomer` varchar(20) DEFAULT NULL,
  `qty` int(2) NOT NULL DEFAULT 1,
  `be` int(1) NOT NULL DEFAULT 1,
  `ca` int(1) NOT NULL DEFAULT 1,
  `pms` int(1) NOT NULL DEFAULT 0,
  `stat` int(1) NOT NULL DEFAULT 1,
  `master` tinyint(1) DEFAULT NULL,
  `virtual` mediumtext DEFAULT NULL,
  `virtual_pms` text DEFAULT NULL,
  `suite` varchar(200) DEFAULT NULL,
  `norefundable` tinyint(1) DEFAULT 0,
  `ratechk_min` varchar(11) DEFAULT NULL,
  `breakfast` tinyint(1) DEFAULT 0,
  `private` tinyint(1) DEFAULT NULL,
  `gender` varchar(2) DEFAULT NULL,
  `infant_y` int(1) DEFAULT NULL,
  `infant_p` varchar(5) DEFAULT NULL,
  `child_y` int(1) DEFAULT NULL,
  `child_p` varchar(5) DEFAULT NULL,
  `child_n` int(1) DEFAULT NULL,
  `event` int(1) DEFAULT NULL,
  `resa_match` varchar(100) DEFAULT NULL,
  `autorevenue_price_min` int(15) NOT NULL DEFAULT 0,
  `autorevenue_price_max` int(15) NOT NULL DEFAULT 0,
  `autorevenue_active` int(1) NOT NULL DEFAULT 0,
  `autorevenue_max_inc` varchar(5) NOT NULL DEFAULT '0',
  `airbnb_cal_id` varchar(64) NOT NULL DEFAULT '',
  `HK` int(1) NOT NULL DEFAULT 0 COMMENT '1 clean | 2 dirty',
  `HK_datetime` timestamp NULL DEFAULT NULL,
  `HK_comment` text DEFAULT NULL,
  `HK_clean` int(1) NOT NULL DEFAULT 0,
  `HK_cleanweek` varchar(20) DEFAULT NULL,
  `HK_linen` int(1) NOT NULL DEFAULT 0,
  `HK_Name` varchar(20) DEFAULT NULL,
  `overbooking` int(1) NOT NULL DEFAULT 0,
  `SB` int(2) DEFAULT NULL COMMENT 'single bed',
  `DB` int(2) DEFAULT NULL COMMENT 'double beds',
  `EB` int(2) DEFAULT NULL COMMENT 'extra beds',
  `amenities` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `roomsize` int(11) DEFAULT NULL,
  `group_by` int(11) DEFAULT NULL,
  `hide_rooms` text DEFAULT NULL,
  `CK_Responsible` varchar(20) DEFAULT NULL,
  `show_octosite` tinyint(1) NOT NULL DEFAULT 1,
  `mail` text DEFAULT NULL,
  `room_names` text CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `place` int(10) UNSIGNED DEFAULT NULL,
  `room_desc_tr` text CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `labels` text CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `room_group` smallint(2) DEFAULT NULL,
  `property_type` smallint(2) DEFAULT NULL,
  `status` smallint(2) DEFAULT NULL,
  `rule_aggregate` int(11) DEFAULT NULL,
  `last_update` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `tree_x` smallint(6) DEFAULT NULL,
  `tree_y` smallint(6) DEFAULT NULL,
  `infants` int(1) NOT NULL DEFAULT 0,
  `max_adults` int(1) DEFAULT NULL,
  `basic_name` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `rate_plan_id` int(11) UNSIGNED DEFAULT NULL,
  `xml_import_status` tinyint(1) DEFAULT NULL,
  `manual_resa` tinyint(1) NOT NULL DEFAULT 1,
  `room_desc_ro` text CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `rms` tinyint(1) DEFAULT NULL,
  `rms_derived` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci KEY_BLOCK_SIZE=8;

--
-- Dumping data for table `hot_room_type`
--

INSERT INTO `hot_room_type` (`room_id`, `room_id_free`, `client_id`, `room_type`, `room_com`, `room_desc1`, `room_desc2`, `room_desc3`, `room_desc4`, `room_desc5`, `room_desc6`, `room_desc7`, `room_desc8`, `room_desc9`, `room_desc10`, `commission`, `prop_roomids`, `no_members`, `no_members_dorm`, `isdorm`, `default_price`, `default_room_num`, `default_min_stay`, `added_time`, `photo`, `impo`, `icalnomer`, `qty`, `be`, `ca`, `pms`, `stat`, `master`, `virtual`, `virtual_pms`, `suite`, `norefundable`, `ratechk_min`, `breakfast`, `private`, `gender`, `infant_y`, `infant_p`, `child_y`, `child_p`, `child_n`, `event`, `resa_match`, `autorevenue_price_min`, `autorevenue_price_max`, `autorevenue_active`, `autorevenue_max_inc`, `airbnb_cal_id`, `HK`, `HK_datetime`, `HK_comment`, `HK_clean`, `HK_cleanweek`, `HK_linen`, `HK_Name`, `overbooking`, `SB`, `DB`, `EB`, `amenities`, `roomsize`, `group_by`, `hide_rooms`, `CK_Responsible`, `show_octosite`, `mail`, `room_names`, `place`, `room_desc_tr`, `labels`, `room_group`, `property_type`, `status`, `rule_aggregate`, `last_update`, `tree_x`, `tree_y`, `infants`, `max_adults`, `basic_name`, `rate_plan_id`, `xml_import_status`, `manual_resa`, `room_desc_ro`, `rms`, `rms_derived`) VALUES
(433, '755', '786', 'mnnkm', 'hgfggh', 'vgghvhgvhgv', 'jhbmnhbkjb', 'jhkljlk', 'hjbjhbnkj', 'yuguj', 'hjbkbk', 'jkoilkj', 'jknlknlkn', 'kjnknkljlk', 'jknjnknn', '0', 'jnjn', 1, 0, 0, '0.00', 'yes', 1, '2000-01-01 00:00:00', 'hguygjhugjb', 99, ',m m n', 1, 1, 1, 0, 1, 1, 'kjnkljhn', 'kjnnjk', 'jknlknlkn', 0, 'njnjj', 0, 1, 'm', 1, '1', 2, '2', 1, 1, 'celebration', 0, 0, 0, '0', 'gdfgdgfd', 0, '2023-01-18 14:26:00', 'gfnhmgn', 0, 'rdhnfcn', 0, 'hfjgnfgn', 0, 44, 21, 52, 0, 663, 253, 'gffbncgn', 'ututy', 1, 'htfdfhn', 'xbbcbcvcbcb', 233, 'chfngrd', 'ngcnvbnvhb', 1, 2, 1, 5, '2023-01-09 14:26:00', 14, 23, 0, 8, 'ghbdnmgm', 2114, 1, 1, 'rehfdfhjgyj', 1, 1),
(442, '755', '333', 'mnnkm', 'hgfggh', 'vgghvhgvhgv', 'jhbmnhbkjb', 'jhkljlk', 'hjbjhbnkj', 'yuguj', 'hjbkbk', 'jkoilkj', 'jknlknlkn', 'kjnknkljlk', 'jknjnknn', '0', 'jnjn', 1, 0, 0, '0.00', 'yes', 1, '2000-01-01 00:00:00', 'hguygjhugjb', 99, ',m m n', 1, 1, 1, 0, 1, 1, 'kjnkljhn', 'kjnnjk', 'jknlknlkn', 0, 'njnjj', 1, 1, 'm', 1, '1', 2, '2', 1, 1, 'celebration', 0, 0, 0, '0', 'gdfgdgfd', 0, '2023-01-18 14:26:00', 'gfnhmgn', 0, 'rdhnfcn', 0, 'hfjgnfgn', 0, 44, 21, 52, 0, 663, 253, 'gffbncgn', 'ututy', 1, 'htfdfhn', 'xbbcbcvcbcb', 233, 'chfngrd', 'ngcnvbnvhb', 1, 2, 1, 5, '2023-01-12 09:03:05', 14, 23, 0, 8, 'ghbdnmgm', 2114, 1, 1, 'rehfdfhjgyj', 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `hot_sites`
--

CREATE TABLE `hot_sites` (
  `sites_id` int(12) NOT NULL,
  `sites_name` varchar(255) NOT NULL,
  `sites_url` varchar(255) NOT NULL,
  `site_displayname` varchar(255) NOT NULL,
  `allowCurrDate` enum('Y','N') NOT NULL DEFAULT 'Y',
  `def_user` varchar(255) NOT NULL,
  `def_pasw` varchar(2048) DEFAULT NULL,
  `password_expire` datetime DEFAULT NULL,
  `def_hotelid` varchar(255) NOT NULL,
  `def_orgid` varchar(255) NOT NULL,
  `type_xml` enum('Y','N') NOT NULL,
  `review_yn` enum('Y','N') NOT NULL DEFAULT 'N',
  `channeldisp` tinyint(1) NOT NULL DEFAULT 1,
  `affi_url` varchar(255) NOT NULL,
  `contact` varchar(100) NOT NULL,
  `resubmit` int(15) NOT NULL DEFAULT 2,
  `maxEnddt` int(3) NOT NULL DEFAULT 365,
  `notification_yn` int(1) NOT NULL DEFAULT 0,
  `mapcont` varchar(255) NOT NULL,
  `mapchk` int(1) NOT NULL DEFAULT 0,
  `default_color` varchar(20) NOT NULL DEFAULT '0,0,0',
  `need_hotelid` int(1) NOT NULL DEFAULT 1,
  `encrypted` int(1) NOT NULL DEFAULT 0,
  `encrypt_pass` varchar(30) NOT NULL DEFAULT 'violet@Favete450!q',
  `log_active` int(1) NOT NULL DEFAULT 0,
  `def_user_machine_1` varchar(65) DEFAULT NULL,
  `def_pass_machine_1` varchar(65) DEFAULT NULL,
  `def_user_machine_2` varchar(65) DEFAULT NULL,
  `def_pass_machine_2` varchar(65) DEFAULT NULL,
  `logo` varchar(200) DEFAULT NULL,
  `octorate` tinyint(1) NOT NULL DEFAULT 0,
  `show_filter` tinyint(1) NOT NULL DEFAULT 1,
  `request_pass` smallint(6) NOT NULL DEFAULT 0,
  `notify_tech` tinyint(1) NOT NULL DEFAULT 1,
  `mail_usr_notify` text DEFAULT NULL,
  `mail_connect` text DEFAULT NULL,
  `stream` varchar(50) DEFAULT NULL,
  `require_listing_page` tinyint(1) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `cancel_after_expired` tinyint(1) NOT NULL DEFAULT 0,
  `available` tinyint(1) DEFAULT NULL,
  `secondary_password` varchar(40) DEFAULT NULL,
  `cancel_resa` tinyint(1) NOT NULL DEFAULT 0,
  `ota_rynair_oauth` text DEFAULT NULL,
  `ota_rynair_expire` datetime NOT NULL DEFAULT '2000-01-01 00:00:00',
  `icon` varchar(20) DEFAULT NULL,
  `message_pull` tinyint(1) DEFAULT 0,
  `stopsell_allowed` tinyint(1) NOT NULL DEFAULT 0,
  `calendar_internal` tinyint(1) NOT NULL DEFAULT 0,
  `user_manageable` tinyint(1) DEFAULT 1,
  `mapping_rule_filter` varchar(50) DEFAULT NULL,
  `request_filter` tinyint(1) NOT NULL DEFAULT 0,
  `enum_content` int(1) DEFAULT NULL,
  `content` tinyint(1) DEFAULT 0,
  `support_rate` tinyint(1) DEFAULT NULL,
  `supported_pricing` int(10) UNSIGNED DEFAULT NULL,
  `new_channel` tinyint(1) NOT NULL DEFAULT 0,
  `calendar_values` int(3) DEFAULT NULL,
  `request_oauth` tinyint(1) DEFAULT 0,
  `commission_invoiced` tinyint(1) DEFAULT 0,
  `new_channel_php` tinyint(1) NOT NULL DEFAULT 0,
  `site_active` tinyint(1) NOT NULL DEFAULT 1,
  `need_IP_register` tinyint(1) NOT NULL DEFAULT 0,
  `api_source_id` int(11) DEFAULT NULL,
  `new_pull` tinyint(1) NOT NULL DEFAULT 0,
  `pull_oauth` tinyint(1) NOT NULL DEFAULT 0,
  `pull_delay` smallint(6) NOT NULL DEFAULT 60,
  `pull_global_delay` smallint(6) NOT NULL DEFAULT 0,
  `pull_global_time` datetime DEFAULT NULL,
  `pull_limit` smallint(6) NOT NULL DEFAULT 20,
  `ota_access_token` text DEFAULT NULL,
  `ota_access_token_expiry` datetime NOT NULL DEFAULT '2000-01-01 00:00:00',
  `content_features` int(11) DEFAULT NULL,
  `sites_endpoint` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `hot_sites`
--

INSERT INTO `hot_sites` (`sites_id`, `sites_name`, `sites_url`, `site_displayname`, `allowCurrDate`, `def_user`, `def_pasw`, `password_expire`, `def_hotelid`, `def_orgid`, `type_xml`, `review_yn`, `channeldisp`, `affi_url`, `contact`, `resubmit`, `maxEnddt`, `notification_yn`, `mapcont`, `mapchk`, `default_color`, `need_hotelid`, `encrypted`, `encrypt_pass`, `log_active`, `def_user_machine_1`, `def_pass_machine_1`, `def_user_machine_2`, `def_pass_machine_2`, `logo`, `octorate`, `show_filter`, `request_pass`, `notify_tech`, `mail_usr_notify`, `mail_connect`, `stream`, `require_listing_page`, `parent_id`, `cancel_after_expired`, `available`, `secondary_password`, `cancel_resa`, `ota_rynair_oauth`, `ota_rynair_expire`, `icon`, `message_pull`, `stopsell_allowed`, `calendar_internal`, `user_manageable`, `mapping_rule_filter`, `request_filter`, `enum_content`, `content`, `support_rate`, `supported_pricing`, `new_channel`, `calendar_values`, `request_oauth`, `commission_invoiced`, `new_channel_php`, `site_active`, `need_IP_register`, `api_source_id`, `new_pull`, `pull_oauth`, `pull_delay`, `pull_global_delay`, `pull_global_time`, `pull_limit`, `ota_access_token`, `ota_access_token_expiry`, `content_features`, `sites_endpoint`) VALUES
(34445, 'Hooper', 'hfhgxgfhg', 'nnbcbm', 'Y', 'vncvnbbm', 'nmkhkh', '2023-01-20 13:05:48', 'fhfdhgj', 'hotel1@@456', 'N', 'N', 1, 'dfgfg', 'jhfkkhfk', 2, 365, 0, 'fhgfjg', 0, '0,0,0', 1, 0, 'violet@Favete450!q', 0, 'fgfnmchbm', 'nghhjh', 'ggnggh', 'hfgj', 'gbmmn', 0, 1, 0, 1, 'hgjgg', 'hgghj', 'hhhh', 1, 333, 0, 6, 'hjgykjl', 0, 'ygggjgj', '2000-01-01 00:00:00', 'ygjgjhk', 0, 0, 0, 1, 'ghgjgu', 0, 4, 0, 6, 677, 0, 45, 0, 0, 0, 1, 0, 6555, 0, 0, 60, 0, '2023-01-19 13:05:48', 20, 'ygukhjg', '2000-01-01 00:00:00', 33, 'jhjhhh'),
(34446, 'Ctrip', 'ngggggj', 'Rudraksh', 'Y', 'nggjghghh', 'bfbfggfngh', '0000-00-00 00:00:00', 'gmhghjmbj', 'hotel1@@986', 'N', 'N', 1, 'nhgnghgv', 'bngnnnvv', 2, 365, 0, 'nnbnbvb', 0, '0,0,0', 1, 0, 'violet@Favete450!q', 0, ' bnmbb', 'bnvngvbmb', 'mhmhnmb', 'ghjvhhjbk', ' bnbhbnmbn', 0, 1, 0, 1, 'ghmbnhvbmb', 'vngjnghghhg', ' vnbvvbhhbnnb', 0, 344, 0, 0, 'ngjyghg', 0, 'jfggngbbnn', '2000-01-01 00:00:00', 'nggfmgg', 0, 0, 0, 1, 'gvnbbbmbn', 0, 1, 0, 1, 44, 0, 787, 0, 0, 0, 1, 0, 644, 0, 0, 60, 0, '0000-00-00 00:00:00', 20, 'ngnnngnghngh', '2000-01-01 00:00:00', 446, 'gngngvmhmhj');

-- --------------------------------------------------------

--
-- Table structure for table `hot_sites_map`
--

CREATE TABLE `hot_sites_map` (
  `ID` int(10) UNSIGNED NOT NULL,
  `site_int_id` int(11) NOT NULL COMMENT 'room_id of hot_room_type',
  `site_ext_id` int(10) UNSIGNED NOT NULL COMMENT 'id of hot_site_rooms',
  `ext_site_id` int(10) UNSIGNED NOT NULL COMMENT 'id of hot_sites_user',
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `hot_sites_map`
--

INSERT INTO `hot_sites_map` (`ID`, `site_int_id`, `site_ext_id`, `ext_site_id`, `timestamp`) VALUES
(6775, 442, 1224, 6666, '2023-01-12 08:51:25');

-- --------------------------------------------------------

--
-- Table structure for table `hot_sites_user`
--

CREATE TABLE `hot_sites_user` (
  `ID` int(10) UNSIGNED NOT NULL,
  `sites_asso_id` varchar(10) NOT NULL,
  `sites_id` int(2) NOT NULL,
  `sites_user` varchar(100) DEFAULT NULL,
  `sites_pass` varchar(100) DEFAULT NULL,
  `user_org_id` text DEFAULT NULL,
  `hotel_id` text DEFAULT NULL,
  `interface` smallint(1) DEFAULT NULL,
  `added` datetime DEFAULT NULL,
  `sites_asso_id_free` varchar(20) DEFAULT NULL,
  `pricing_method_id` int(1) NOT NULL DEFAULT 1,
  `commission` float DEFAULT NULL,
  `active` int(1) NOT NULL DEFAULT 1,
  `security_answer` varchar(255) DEFAULT NULL,
  `updating` int(11) NOT NULL DEFAULT 0,
  `resa` int(11) NOT NULL DEFAULT 0,
  `email_scrapping` int(1) DEFAULT NULL,
  `notification_resa` int(1) NOT NULL DEFAULT 0,
  `resa_alot_chk` int(1) NOT NULL DEFAULT 0,
  `resa_mail` int(1) NOT NULL DEFAULT 1,
  `site_version` int(11) NOT NULL DEFAULT 1,
  `conv_curr` char(3) DEFAULT NULL,
  `commission2` decimal(5,2) NOT NULL DEFAULT 0.00,
  `note` longtext DEFAULT NULL,
  `remindDate` varchar(10) DEFAULT NULL,
  `colormap` varchar(10) DEFAULT NULL,
  `ignrFlag` int(1) DEFAULT NULL,
  `ignrFlag2` int(1) DEFAULT NULL,
  `color_channel` varchar(20) DEFAULT NULL,
  `roomnotmap_resa` int(1) NOT NULL DEFAULT 0,
  `commission_fixed` tinyint(1) NOT NULL DEFAULT 0,
  `commission_round` tinyint(1) NOT NULL DEFAULT 0,
  `listing_url_page` varchar(255) DEFAULT NULL,
  `user_owner` int(11) DEFAULT NULL,
  `oauth` text DEFAULT NULL,
  `last_pull` datetime DEFAULT NULL,
  `cron_status` text DEFAULT NULL,
  `pull_unmapped` tinyint(1) NOT NULL DEFAULT 1,
  `calendar_values` int(11) NOT NULL DEFAULT 255,
  `oauth_expire` datetime DEFAULT NULL,
  `oauth_retry` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `updated_listing_airbnb` datetime DEFAULT NULL,
  `disable_price` tinyint(1) NOT NULL DEFAULT 0,
  `disable_restriction` tinyint(1) NOT NULL DEFAULT 0,
  `homeaway_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`homeaway_json`)),
  `last_message` datetime DEFAULT NULL,
  `messages` tinyint(1) NOT NULL DEFAULT 0,
  `last_synch` datetime DEFAULT NULL,
  `mapping_rules` text DEFAULT NULL,
  `devel` tinyint(1) DEFAULT 0,
  `avail_perc` smallint(6) NOT NULL DEFAULT 100,
  `new_channel` tinyint(1) NOT NULL DEFAULT 0,
  `enable_notify` tinyint(1) NOT NULL DEFAULT 1,
  `last_notify` datetime DEFAULT NULL,
  `fix` timestamp NULL DEFAULT NULL,
  `content` tinyint(1) DEFAULT 0,
  `show_resa_both` tinyint(1) DEFAULT 0,
  `last_push` datetime DEFAULT NULL,
  `log_migrate` tinyint(1) NOT NULL DEFAULT 0,
  `php_7_import` tinyint(1) DEFAULT 1,
  `contract_signed` tinyint(1) DEFAULT NULL,
  `agency` int(10) DEFAULT NULL,
  `last_sync_review` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `hot_sites_user`
--

INSERT INTO `hot_sites_user` (`ID`, `sites_asso_id`, `sites_id`, `sites_user`, `sites_pass`, `user_org_id`, `hotel_id`, `interface`, `added`, `sites_asso_id_free`, `pricing_method_id`, `commission`, `active`, `security_answer`, `updating`, `resa`, `email_scrapping`, `notification_resa`, `resa_alot_chk`, `resa_mail`, `site_version`, `conv_curr`, `commission2`, `note`, `remindDate`, `colormap`, `ignrFlag`, `ignrFlag2`, `color_channel`, `roomnotmap_resa`, `commission_fixed`, `commission_round`, `listing_url_page`, `user_owner`, `oauth`, `last_pull`, `cron_status`, `pull_unmapped`, `calendar_values`, `oauth_expire`, `oauth_retry`, `updated_listing_airbnb`, `disable_price`, `disable_restriction`, `homeaway_json`, `last_message`, `messages`, `last_synch`, `mapping_rules`, `devel`, `avail_perc`, `new_channel`, `enable_notify`, `last_notify`, `fix`, `content`, `show_resa_both`, `last_push`, `log_migrate`, `php_7_import`, `contract_signed`, `agency`, `last_sync_review`) VALUES
(6666, '333', 34445, 'dvdffbng', 'ffghjhgghm', 'cbngvnvmhjb', 'bnvgnfn', 1, '0000-00-00 00:00:00', 'vfbngnvb', 1, 44.05, 1, 'bvnvnmbhmbmh', 0, 0, 1, 0, 0, 1, 1, 'yes', '55.20', '{}', 'cbfbc', 'bbvnvm', 1, 1, 'vfgnbvcnm', 0, 0, 0, 'dvdbngcv', 3435, ' nbvbn', '0000-00-00 00:00:00', 'vfgffghgn', 1, 255, '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 0, 0, '{}', '0000-00-00 00:00:00', 0, '0000-00-00 00:00:00', 'bffbfg', 0, 100, 0, 1, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 0, 0, '0000-00-00 00:00:00', 0, 1, 1, 344, '0000-00-00 00:00:00');

--
-- Triggers `hot_sites_user`
--
DELIMITER $$
CREATE TRIGGER `lock_hot_sites_user_update` BEFORE UPDATE ON `hot_sites_user` FOR EACH ROW IF OLD.sites_id = 274 AND OLD.updating = TRUE AND NEW.updating = FALSE
        AND EXISTS(SELECT DISTINCT owner_codice
                   FROM enginebb.ob_oauth_resource
                   WHERE api_id = 121
                     AND owner_codice = OLD.sites_asso_id) THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'TraceMe: Cannot update locked record';
    END IF
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `hot_site_rooms`
--

CREATE TABLE `hot_site_rooms` (
  `ID` int(10) UNSIGNED NOT NULL,
  `site_id` int(10) UNSIGNED DEFAULT NULL,
  `user_id` varchar(10) DEFAULT NULL,
  `site_room_name` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `site_room_id` varchar(255) DEFAULT NULL,
  `site_room_occupancy` int(4) NOT NULL DEFAULT 0,
  `manageable` enum('Yes','No') NOT NULL DEFAULT 'Yes',
  `initalia_dependent_rate` int(1) NOT NULL DEFAULT 0,
  `added` datetime DEFAULT NULL,
  `single_price` varchar(10) DEFAULT NULL,
  `double_price` varchar(10) DEFAULT NULL,
  `triple_price` varchar(10) DEFAULT NULL,
  `quadrupple_price` varchar(9) DEFAULT NULL,
  `quadExtra1_price` varchar(9) DEFAULT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `portal_notified` tinyint(1) NOT NULL DEFAULT 0,
  `is_real_room` tinyint(1) NOT NULL DEFAULT 0,
  `calendar_values` int(11) NOT NULL DEFAULT 255,
  `portal_json` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `hot_site_rooms`
--

INSERT INTO `hot_site_rooms` (`ID`, `site_id`, `user_id`, `site_room_name`, `site_room_id`, `site_room_occupancy`, `manageable`, `initalia_dependent_rate`, `added`, `single_price`, `double_price`, `triple_price`, `quadrupple_price`, `quadExtra1_price`, `details`, `portal_notified`, `is_real_room`, `calendar_values`, `portal_json`) VALUES
(1224, 6666, '333', 'tytttt', '221:6000', 0, 'Yes', 0, '2023-01-17 19:49:35', 'vcvbv', 'vbbnm', 'khh', 'dfbfgbdn', 'gffg', '{}', 0, 0, 255, 'ghtfbfnm');

-- --------------------------------------------------------

--
-- Table structure for table `ob_reservations`
--

CREATE TABLE `ob_reservations` (
  `id` int(11) NOT NULL,
  `payment_type` smallint(6) NOT NULL,
  `childs` smallint(6) NOT NULL DEFAULT 0,
  `discount` int(11) DEFAULT NULL,
  `deposit` decimal(10,2) NOT NULL DEFAULT 0.00,
  `breakfast` smallint(6) NOT NULL DEFAULT 0,
  `payment_expiration` datetime DEFAULT NULL,
  `room_id` int(11) DEFAULT NULL,
  `cancel_time` datetime DEFAULT NULL,
  `cancel_ip` varchar(45) DEFAULT NULL,
  `referral` varchar(10) DEFAULT NULL,
  `cleaning` decimal(10,2) NOT NULL DEFAULT 0.00,
  `json` text DEFAULT NULL,
  `conversation` varchar(100) DEFAULT NULL,
  `ota_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `infants` int(1) NOT NULL DEFAULT 0,
  `ota_id` int(11) UNSIGNED DEFAULT NULL COMMENT 'The internal OTA this reservation cames from',
  `rate_plan_id` int(11) UNSIGNED DEFAULT NULL,
  `booking_included` tinyint(4) NOT NULL DEFAULT 0,
  `proposal_rate` tinyint(1) NOT NULL DEFAULT 0,
  `deposit_status` tinyint(1) DEFAULT NULL,
  `deposit_payback_mode` tinyint(1) DEFAULT NULL,
  `company_collect` tinyint(1) NOT NULL DEFAULT 0,
  `api_id` int(11) DEFAULT NULL,
  `octobook_notify_time` datetime DEFAULT NULL,
  `room_locked` tinyint(1) DEFAULT 0,
  `door_code` varchar(20) DEFAULT NULL,
  `rate_plan_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `agency_fee` tinyint(3) DEFAULT NULL,
  `widget_id` int(11) DEFAULT NULL,
  `thread` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `ob_reservations`
--

INSERT INTO `ob_reservations` (`id`, `payment_type`, `childs`, `discount`, `deposit`, `breakfast`, `payment_expiration`, `room_id`, `cancel_time`, `cancel_ip`, `referral`, `cleaning`, `json`, `conversation`, `ota_fee`, `infants`, `ota_id`, `rate_plan_id`, `booking_included`, `proposal_rate`, `deposit_status`, `deposit_payback_mode`, `company_collect`, `api_id`, `octobook_notify_time`, `room_locked`, `door_code`, `rate_plan_price`, `agency_fee`, `widget_id`, `thread`) VALUES
(223, 22, 0, 55, '4646.44', 0, '2023-01-11 20:11:15', 433, '2023-01-15 20:11:15', 'htjfgjj', 'ritikcode', '333.56', 'hngnmbnm', 'hello there', '33489.56', 0, 546, 2114, 0, 0, 1, 0, 0, 343, '2023-01-16 20:11:15', 0, 'grhdhftj', '0.00', 112, 3556, 4657);

-- --------------------------------------------------------

--
-- Table structure for table `ob_rules_derived`
--

CREATE TABLE `ob_rules_derived` (
  `room_id` int(11) NOT NULL,
  `parent_id` int(11) NOT NULL,
  `avail` tinyint(1) NOT NULL DEFAULT 1,
  `stay` tinyint(1) NOT NULL DEFAULT 0,
  `restrictions` tinyint(1) NOT NULL DEFAULT 0,
  `stop` tinyint(1) NOT NULL DEFAULT 0,
  `price` smallint(6) NOT NULL DEFAULT 0,
  `price_val` decimal(10,2) NOT NULL DEFAULT 0.00,
  `price_round` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `ob_rules_derived`
--

INSERT INTO `ob_rules_derived` (`room_id`, `parent_id`, `avail`, `stay`, `restrictions`, `stop`, `price`, `price_val`, `price_round`) VALUES
(442, 442, 1, 0, 0, 0, 0, '0.00', 0);

-- --------------------------------------------------------

--
-- Table structure for table `reservation_detl_as`
--

CREATE TABLE `reservation_detl_as` (
  `numero` int(15) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `codice` varchar(30) DEFAULT NULL,
  `refer` varchar(255) DEFAULT NULL,
  `hotelId` varchar(30) DEFAULT NULL,
  `summary` text CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `room_id_ext` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `reservation_detl_as`
--

INSERT INTO `reservation_detl_as` (`numero`, `timestamp`, `codice`, `refer`, `hotelId`, `summary`, `room_id_ext`) VALUES
(35, '2023-01-02 14:51:08', '333', 'ritikcode', '444', 'hngnmh', 'pleasure');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `backup_assignment`
--
ALTER TABLE `backup_assignment`
  ADD PRIMARY KEY (`id`),
  ADD KEY `codice` (`codice`),
  ADD KEY `room_idcr` (`room_idcr`),
  ADD KEY `backup_assignment_1` (`db_sites_id`,`extRoomID`);

--
-- Indexes for table `clienti`
--
ALTER TABLE `clienti`
  ADD PRIMARY KEY (`num`),
  ADD UNIQUE KEY `codice` (`codice`),
  ADD KEY `citta` (`citta`),
  ADD KEY `running_status` (`running_status`),
  ADD KEY `versione` (`versione`),
  ADD KEY `update_resa` (`update_resa`),
  ADD KEY `cancel_flag` (`cancel_flag`),
  ADD KEY `autofetch` (`autofetch`),
  ADD KEY `versione_2` (`versione`,`autofetch`),
  ADD KEY `main_a` (`main_a`),
  ADD KEY `mail` (`mail`),
  ADD KEY `main` (`main`),
  ADD KEY `zoho_account_id` (`fatturare_network`),
  ADD KEY `garanzia` (`garanzia`),
  ADD KEY `clienti_payoff_percent` (`payoff_percent`),
  ADD KEY `clienti_deposit_ota` (`deposit_ota`),
  ADD KEY `clienti_gdpr` (`gdpr_sent`),
  ADD KEY `clienti_piva` (`piva`),
  ADD KEY `clienti_cfisc` (`cfisc`),
  ADD KEY `clienti_wlid` (`whitelabel_id`),
  ADD KEY `clienti_codpromo` (`codpromo`),
  ADD KEY `clienti_fatt_x` (`garanzia`,`fatturare_network`);

--
-- Indexes for table `conferme`
--
ALTER TABLE `conferme`
  ADD PRIMARY KEY (`numero`),
  ADD KEY `codice` (`codice`),
  ADD KEY `ricevutah` (`ricevutah`),
  ADD KEY `sito` (`sito`),
  ADD KEY `room_id_ext` (`room_id_ext`),
  ADD KEY `refer` (`refer`),
  ADD KEY `update_Flag` (`update_Flag`),
  ADD KEY `res_fetch_date` (`res_fetch_date`),
  ADD KEY `date_2x` (`date_2x`),
  ADD KEY `date_1x` (`date_1x`),
  ADD KEY `room_id_pms` (`room_id_pms`),
  ADD KEY `date_1` (`date_1`),
  ADD KEY `room_id` (`room_id`),
  ADD KEY `notifysucc` (`notifysucc`),
  ADD KEY `saldato` (`saldato`),
  ADD KEY `timestamp` (`timestamp`),
  ADD KEY `resvModificationDateTime` (`resvModificationDateTime`),
  ADD KEY `check-in` (`check-in`),
  ADD KEY `check-out` (`check-out`),
  ADD KEY `voucher` (`voucher`),
  ADD KEY `tokenid` (`tokenid`),
  ADD KEY `codice_2` (`codice`,`update_Flag`),
  ADD KEY `conferme_codicepren_idx` (`codicepren`),
  ADD KEY `status` (`status`),
  ADD KEY `refer_disp` (`refer_disp`),
  ADD KEY `conferme_from_codice` (`from_codice`),
  ADD KEY `conferme_codice_refer` (`codice`,`refer`) USING HASH,
  ADD KEY `notifyend` (`notifyend`),
  ADD KEY `conferme_ccstoken` (`ccs_token`),
  ADD KEY `conferme_group_id_idx` (`group_id`);

--
-- Indexes for table `conferme_text`
--
ALTER TABLE `conferme_text`
  ADD PRIMARY KEY (`id`),
  ADD KEY `refer` (`refer`),
  ADD KEY `codice` (`codice`),
  ADD KEY `flightnum` (`flightnum`),
  ADD KEY `date_1` (`date_1`);

--
-- Indexes for table `hot_room_type`
--
ALTER TABLE `hot_room_type`
  ADD PRIMARY KEY (`room_id`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `pms` (`pms`),
  ADD KEY `be` (`be`),
  ADD KEY `hot_room_type_resa_match` (`resa_match`),
  ADD KEY `hot_room_type_group` (`group_by`),
  ADD KEY `resa_match` (`resa_match`),
  ADD KEY `hot_room_type_aggr` (`rule_aggregate`),
  ADD KEY `ob_room_rate_plan` (`rate_plan_id`);

--
-- Indexes for table `hot_sites`
--
ALTER TABLE `hot_sites`
  ADD PRIMARY KEY (`sites_id`),
  ADD UNIQUE KEY `sites_id` (`sites_id`),
  ADD UNIQUE KEY `hot_sites_sites_name_unique` (`sites_name`),
  ADD KEY `sites_name` (`sites_name`),
  ADD KEY `hot_sites_disabled` (`channeldisp`),
  ADD KEY `hot_sites_hidden` (`octorate`),
  ADD KEY `hot_sites_parent` (`parent_id`),
  ADD KEY `hot_sites_pull` (`new_pull`),
  ADD KEY `hot_sites_global` (`pull_global_delay`);

--
-- Indexes for table `hot_sites_map`
--
ALTER TABLE `hot_sites_map`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `site_int_id` (`site_int_id`,`ext_site_id`),
  ADD KEY `ext_site_id` (`ext_site_id`),
  ADD KEY `site_ext_id` (`site_ext_id`);

--
-- Indexes for table `hot_sites_user`
--
ALTER TABLE `hot_sites_user`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `sites_asso_id` (`sites_asso_id`),
  ADD KEY `sites_id` (`sites_id`),
  ADD KEY `sites_user` (`sites_user`),
  ADD KEY `active` (`active`),
  ADD KEY `updating` (`updating`),
  ADD KEY `resa` (`resa`),
  ADD KEY `resa_alot_chk` (`resa_alot_chk`),
  ADD KEY `hotel_id` (`hotel_id`(767)),
  ADD KEY `hot_sites_user_owner` (`user_owner`),
  ADD KEY `hot_sites_user_pull` (`last_pull`),
  ADD KEY `site_oauth_expire` (`oauth_expire`),
  ADD KEY `agency_fk` (`agency`);

--
-- Indexes for table `hot_site_rooms`
--
ALTER TABLE `hot_site_rooms`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `user_id_2` (`user_id`,`site_room_id`),
  ADD KEY `hot_site_rooms_portalcon` (`site_id`),
  ADD KEY `site_room_id` (`site_room_id`);

--
-- Indexes for table `ob_reservations`
--
ALTER TABLE `ob_reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ob_reservations_referral` (`referral`),
  ADD KEY `ob_reservation_conversation` (`conversation`),
  ADD KEY `ob_res_rate_plan` (`rate_plan_id`),
  ADD KEY `ob_reservations_notify_time_idx` (`octobook_notify_time`),
  ADD KEY `ob_reservations_chat_thread` (`thread`);

--
-- Indexes for table `ob_rules_derived`
--
ALTER TABLE `ob_rules_derived`
  ADD PRIMARY KEY (`room_id`),
  ADD KEY `ob_rules_derived_1` (`room_id`),
  ADD KEY `ob_rules_derived_2` (`parent_id`);

--
-- Indexes for table `reservation_detl_as`
--
ALTER TABLE `reservation_detl_as`
  ADD PRIMARY KEY (`numero`),
  ADD KEY `room_id_ext` (`room_id_ext`),
  ADD KEY `refer` (`refer`),
  ADD KEY `codice` (`codice`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `backup_assignment`
--
ALTER TABLE `backup_assignment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5546;

--
-- AUTO_INCREMENT for table `clienti`
--
ALTER TABLE `clienti`
  MODIFY `num` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1021;

--
-- AUTO_INCREMENT for table `conferme`
--
ALTER TABLE `conferme`
  MODIFY `numero` int(11) NOT NULL AUTO_INCREMENT COMMENT 'incremental number of reservation is auto increment', AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `conferme_text`
--
ALTER TABLE `conferme_text`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5445;

--
-- AUTO_INCREMENT for table `hot_room_type`
--
ALTER TABLE `hot_room_type`
  MODIFY `room_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=443;

--
-- AUTO_INCREMENT for table `hot_sites`
--
ALTER TABLE `hot_sites`
  MODIFY `sites_id` int(12) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34448;

--
-- AUTO_INCREMENT for table `hot_sites_map`
--
ALTER TABLE `hot_sites_map`
  MODIFY `ID` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6776;

--
-- AUTO_INCREMENT for table `hot_sites_user`
--
ALTER TABLE `hot_sites_user`
  MODIFY `ID` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6667;

--
-- AUTO_INCREMENT for table `hot_site_rooms`
--
ALTER TABLE `hot_site_rooms`
  MODIFY `ID` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1225;

--
-- AUTO_INCREMENT for table `reservation_detl_as`
--
ALTER TABLE `reservation_detl_as`
  MODIFY `numero` int(15) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `backup_assignment`
--
ALTER TABLE `backup_assignment`
  ADD CONSTRAINT `backup_assignment_ibfk_1` FOREIGN KEY (`room_idcr`) REFERENCES `hot_room_type` (`room_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `hot_sites_map`
--
ALTER TABLE `hot_sites_map`
  ADD CONSTRAINT `hot_sites_map_ibfk_3` FOREIGN KEY (`ext_site_id`) REFERENCES `hot_sites_user` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `hot_sites_map_ibfk_4` FOREIGN KEY (`site_int_id`) REFERENCES `hot_room_type` (`room_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `hot_sites_map_ibfk_5` FOREIGN KEY (`site_ext_id`) REFERENCES `hot_site_rooms` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `hot_site_rooms`
--
ALTER TABLE `hot_site_rooms`
  ADD CONSTRAINT `hot_site_rooms_ibfk_1` FOREIGN KEY (`site_id`) REFERENCES `hot_sites_user` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;

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
