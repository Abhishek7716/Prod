-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 52.66.129.81:3945
-- Generation Time: Apr 04, 2025 at 09:46 AM
-- Server version: 8.0.41-0ubuntu0.22.04.1
-- PHP Version: 8.3.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ic_pop_ngx_master`
--

-- --------------------------------------------------------

--
-- Table structure for table `pops_roles`
--

CREATE TABLE `pops_roles` (
  `id` int NOT NULL,
  `role` varchar(30) NOT NULL,
  `permissions` text NOT NULL,
  `deleted` tinyint(1) NOT NULL,
  `created_on` int NOT NULL,
  `updated_on` int NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `pops_roles`
--

INSERT INTO `pops_roles` (`id`, `role`, `permissions`, `deleted`, `created_on`, `updated_on`) VALUES
(1, 'ADMIN MAKER', 'a:26:{s:14:\"sip_management\";s:1:\"1\";s:8:\"view_sip\";s:1:\"1\";s:15:\"sip_transaction\";s:1:\"1\";s:12:\"upcoming_sip\";s:1:\"1\";s:14:\"user_managment\";s:1:\"1\";s:6:\"access\";s:1:\"1\";s:11:\"permissions\";s:1:\"1\";s:23:\"subsequent_contribution\";s:1:\"1\";s:17:\"corporate_mapping\";s:1:\"1\";s:15:\"download_report\";s:1:\"1\";s:11:\"bulk_report\";s:1:\"1\";s:11:\"upload_pran\";s:1:\"1\";s:9:\"cron_logs\";s:1:\"1\";s:14:\"ckyc_converter\";s:1:\"1\";s:8:\"feedback\";s:1:\"1\";s:19:\"corporate_directory\";s:1:\"1\";s:13:\"charge_matrix\";s:1:\"1\";s:16:\"manage_corporate\";s:1:\"1\";s:16:\"corporate_action\";s:1:\"1\";s:35:\"contribution_history_for_operations\";s:1:\"1\";s:35:\"contribution_request_for_operations\";s:1:\"1\";s:33:\"pran_shifting_list_for_operations\";s:1:\"1\";s:42:\"corporate_modification_list_for_operations\";s:1:\"1\";s:3:\"nft\";s:1:\"1\";s:16:\"approval_history\";s:1:\"1\";s:15:\"approval_rights\";s:1:\"1\";}', 0, 0, 1718880124),
(10, 'OPERATIONS MAKER', 'a:10:{s:13:\"sip_managment\";i:1;s:17:\"corporate_mapping\";i:1;s:28:\"subsequent_contribution_list\";i:1;s:14:\"user_managment\";i:1;s:15:\"download_report\";i:1;s:11:\"upload_pran\";i:1;s:15:\"profile_details\";i:1;s:9:\"cron_logs\";i:1;s:14:\"ckyc_converter\";i:1;s:13:\"charge_matrix\";i:1;}', 0, 0, 1718880124),
(3, 'NODAL MAKER', 'a:6:{s:22:\"corporate_contribution\";s:1:\"1\";s:20:\"contribution_history\";s:1:\"1\";s:13:\"pran_shifting\";s:1:\"1\";s:15:\"request_summary\";s:1:\"1\";s:27:\"corporate_modification_list\";s:1:\"1\";s:18:\"pran_shifting_list\";s:1:\"1\";}', 0, 0, 1718880124),
(5, 'SUADMIN MAKER', 'a:18:{s:14:\"sip_management\";s:1:\"1\";s:8:\"view_sip\";s:1:\"1\";s:15:\"sip_transaction\";s:1:\"1\";s:14:\"user_managment\";s:1:\"1\";s:6:\"access\";s:1:\"1\";s:11:\"permissions\";s:1:\"1\";s:23:\"subsequent_contribution\";s:1:\"1\";s:17:\"corporate_mapping\";s:1:\"1\";s:15:\"download_report\";s:1:\"1\";s:11:\"bulk_report\";s:1:\"1\";s:11:\"upload_pran\";s:1:\"1\";s:9:\"cron_logs\";s:1:\"1\";s:14:\"ckyc_converter\";s:1:\"1\";s:8:\"feedback\";s:1:\"1\";s:19:\"corporate_directory\";s:1:\"1\";s:13:\"charge_matrix\";s:1:\"1\";s:16:\"manage_corporate\";s:1:\"1\";s:3:\"nft\";s:1:\"1\";}', 0, 0, 1718880124),
(7, 'LAM', 'a:3:{s:14:\"user_managment\";s:1:\"1\";s:6:\"access\";s:1:\"1\";s:11:\"permissions\";s:1:\"1\";}', 0, 0, 1718880124),
(2, 'ADMIN CHECKER', 'a:26:{s:14:\"sip_management\";s:1:\"1\";s:8:\"view_sip\";s:1:\"1\";s:15:\"sip_transaction\";s:1:\"1\";s:12:\"upcoming_sip\";s:1:\"1\";s:14:\"user_managment\";s:1:\"1\";s:6:\"access\";s:1:\"1\";s:11:\"permissions\";s:1:\"1\";s:23:\"subsequent_contribution\";s:1:\"1\";s:17:\"corporate_mapping\";s:1:\"1\";s:15:\"download_report\";s:1:\"1\";s:11:\"bulk_report\";s:1:\"1\";s:11:\"upload_pran\";s:1:\"1\";s:9:\"cron_logs\";s:1:\"1\";s:14:\"ckyc_converter\";s:1:\"1\";s:8:\"feedback\";s:1:\"1\";s:19:\"corporate_directory\";s:1:\"1\";s:13:\"charge_matrix\";s:1:\"1\";s:16:\"manage_corporate\";s:1:\"1\";s:16:\"corporate_action\";s:1:\"1\";s:35:\"contribution_history_for_operations\";s:1:\"1\";s:35:\"contribution_request_for_operations\";s:1:\"1\";s:33:\"pran_shifting_list_for_operations\";s:1:\"1\";s:42:\"corporate_modification_list_for_operations\";s:1:\"1\";s:3:\"nft\";s:1:\"1\";s:16:\"approval_history\";s:1:\"1\";s:15:\"approval_rights\";s:1:\"1\";}', 0, 0, 1718880124),
(8, 'ACCOUNTS MAKER', 'a:18:{s:14:\"sip_management\";s:1:\"1\";s:8:\"view_sip\";s:1:\"1\";s:15:\"sip_transaction\";s:1:\"1\";s:12:\"upcoming_sip\";s:1:\"1\";s:14:\"user_managment\";s:1:\"1\";s:6:\"access\";s:1:\"1\";s:11:\"permissions\";s:1:\"1\";s:23:\"subsequent_contribution\";s:1:\"1\";s:17:\"corporate_mapping\";s:1:\"1\";s:15:\"download_report\";s:1:\"1\";s:11:\"bulk_report\";s:1:\"1\";s:11:\"upload_pran\";s:1:\"1\";s:9:\"cron_logs\";s:1:\"1\";s:14:\"ckyc_converter\";s:1:\"1\";s:8:\"feedback\";s:1:\"1\";s:13:\"charge_matrix\";s:1:\"1\";s:16:\"manage_corporate\";s:1:\"1\";s:3:\"nft\";s:1:\"1\";}', 0, 0, 1718880124),
(4, 'NODAL CHECKER', 'a:6:{s:22:\"corporate_contribution\";s:1:\"1\";s:20:\"contribution_history\";s:1:\"1\";s:13:\"pran_shifting\";s:1:\"1\";s:15:\"request_summary\";s:1:\"1\";s:27:\"corporate_modification_list\";s:1:\"1\";s:18:\"pran_shifting_list\";s:1:\"1\";}', 0, 0, 1718880124),
(6, 'SUADMIN CHECKER', 'a:18:{s:14:\"sip_management\";s:1:\"1\";s:8:\"view_sip\";s:1:\"1\";s:15:\"sip_transaction\";s:1:\"1\";s:14:\"user_managment\";s:1:\"1\";s:6:\"access\";s:1:\"1\";s:11:\"permissions\";s:1:\"1\";s:23:\"subsequent_contribution\";s:1:\"1\";s:17:\"corporate_mapping\";s:1:\"1\";s:15:\"download_report\";s:1:\"1\";s:11:\"bulk_report\";s:1:\"1\";s:11:\"upload_pran\";s:1:\"1\";s:9:\"cron_logs\";s:1:\"1\";s:14:\"ckyc_converter\";s:1:\"1\";s:8:\"feedback\";s:1:\"1\";s:19:\"corporate_directory\";s:1:\"1\";s:13:\"charge_matrix\";s:1:\"1\";s:16:\"manage_corporate\";s:1:\"1\";s:3:\"nft\";s:1:\"1\";}', 0, 0, 1718880124),
(9, 'ACCOUNTS CHECKER', 'a:18:{s:14:\"sip_management\";s:1:\"1\";s:8:\"view_sip\";s:1:\"1\";s:15:\"sip_transaction\";s:1:\"1\";s:12:\"upcoming_sip\";s:1:\"1\";s:14:\"user_managment\";s:1:\"1\";s:6:\"access\";s:1:\"1\";s:11:\"permissions\";s:1:\"1\";s:23:\"subsequent_contribution\";s:1:\"1\";s:17:\"corporate_mapping\";s:1:\"1\";s:15:\"download_report\";s:1:\"1\";s:11:\"bulk_report\";s:1:\"1\";s:11:\"upload_pran\";s:1:\"1\";s:9:\"cron_logs\";s:1:\"1\";s:14:\"ckyc_converter\";s:1:\"1\";s:8:\"feedback\";s:1:\"1\";s:13:\"charge_matrix\";s:1:\"1\";s:16:\"manage_corporate\";s:1:\"1\";s:3:\"nft\";s:1:\"1\";}', 0, 0, 1718880124),
(11, 'OPERATIONS CHECKER', 'a:10:{s:13:\"sip_managment\";i:1;s:17:\"corporate_mapping\";i:1;s:28:\"subsequent_contribution_list\";i:1;s:14:\"user_managment\";i:1;s:15:\"download_report\";i:1;s:11:\"upload_pran\";i:1;s:15:\"profile_details\";i:1;s:9:\"cron_logs\";i:1;s:14:\"ckyc_converter\";i:1;s:13:\"charge_matrix\";i:1;}', 0, 0, 1718880124),
(0, 'ADMIN', 'a:26:{s:14:\"sip_management\";s:1:\"1\";s:8:\"view_sip\";s:1:\"1\";s:15:\"sip_transaction\";s:1:\"1\";s:12:\"upcoming_sip\";s:1:\"1\";s:14:\"user_managment\";s:1:\"1\";s:6:\"access\";s:1:\"1\";s:11:\"permissions\";s:1:\"1\";s:23:\"subsequent_contribution\";s:1:\"1\";s:17:\"corporate_mapping\";s:1:\"1\";s:15:\"download_report\";s:1:\"1\";s:11:\"bulk_report\";s:1:\"1\";s:11:\"upload_pran\";s:1:\"1\";s:9:\"cron_logs\";s:1:\"1\";s:14:\"ckyc_converter\";s:1:\"1\";s:8:\"feedback\";s:1:\"1\";s:19:\"corporate_directory\";s:1:\"1\";s:13:\"charge_matrix\";s:1:\"1\";s:16:\"manage_corporate\";s:1:\"1\";s:16:\"corporate_action\";s:1:\"1\";s:35:\"contribution_history_for_operations\";s:1:\"1\";s:35:\"contribution_request_for_operations\";s:1:\"1\";s:33:\"pran_shifting_list_for_operations\";s:1:\"1\";s:42:\"corporate_modification_list_for_operations\";s:1:\"1\";s:3:\"nft\";s:1:\"1\";s:16:\"approval_history\";s:1:\"1\";s:15:\"approval_rights\";s:1:\"1\";}', 0, 0, 1718880124);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `pops_roles`
--
ALTER TABLE `pops_roles`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `pops_roles`
--
ALTER TABLE `pops_roles`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
