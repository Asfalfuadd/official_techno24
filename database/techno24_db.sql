-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 22, 2026 at 08:47 PM
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
-- Database: `techno24_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `agendas`
--

CREATE TABLE `agendas` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `location` varchar(150) DEFAULT NULL,
  `event_date` datetime DEFAULT NULL,
  `cost_per_member` decimal(12,2) NOT NULL DEFAULT 0.00,
  `target_participants` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `status` enum('ide','direncanakan','menunggu_konfirmasi','dikonfirmasi','berlangsung','selesai','dibatalkan') NOT NULL DEFAULT 'ide',
  `equipment` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `budget_notes` text DEFAULT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `agenda_participants`
--

CREATE TABLE `agenda_participants` (
  `agenda_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `response` enum('ikut','tidak') NOT NULL,
  `responded_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `albums`
--

CREATE TABLE `albums` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` varchar(500) DEFAULT NULL,
  `cover_photo_id` int(10) UNSIGNED DEFAULT NULL,
  `event_date` date DEFAULT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `album_photos`
--

CREATE TABLE `album_photos` (
  `album_id` int(10) UNSIGNED NOT NULL,
  `photo_id` int(10) UNSIGNED NOT NULL,
  `added_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(150) NOT NULL,
  `content` text NOT NULL,
  `category` enum('Akademik','Uang kas','Kegiatan kelas','Perubahan jadwal','Informasi umum') NOT NULL DEFAULT 'Informasi umum',
  `is_important` tinyint(1) NOT NULL DEFAULT 0,
  `is_pinned` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `published_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `action` varchar(60) NOT NULL,
  `detail` varchar(255) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `detail`, `ip`, `created_at`) VALUES
(1, 1, 'login', 'admin1', '::1', '2026-10-22 06:00:01'),
(2, 1, 'seed_members', 'dibuat: 22', '::1', '2026-10-22 06:00:42'),
(3, 1, 'login', 'admin1', '::1', '2026-10-22 06:04:02'),
(4, 11, 'login', 'asfal', '::1', '2026-10-22 06:05:29'),
(5, 11, 'change_password', '', '::1', '2026-10-22 06:06:48'),
(6, 11, 'login', 'asfal', '::1', '2026-10-22 06:16:04'),
(7, 1, 'login', 'admin1', '::1', '2026-10-22 06:24:26'),
(8, 11, 'login', 'asfal', '::1', '2026-10-22 06:25:56'),
(9, 1, 'login', 'admin1', '::1', '2026-10-22 06:26:08'),
(10, 1, 'login', 'admin1', '::1', '2026-10-22 06:30:18'),
(11, 1, 'login', 'admin1', '::1', '2026-10-22 06:32:12'),
(12, 1, 'login', 'admin1', '::1', '2026-10-22 06:33:16'),
(13, 1, 'user_create', 'admin2 (admin)', '::1', '2026-10-22 06:39:05'),
(14, 24, 'login', 'admin2', '::1', '2026-10-22 06:39:31'),
(15, 24, 'change_password', '', '::1', '2026-10-22 06:40:17'),
(16, 11, 'login', 'asfal', '::1', '2026-10-22 06:41:01'),
(17, 11, 'login', 'asfal', '::1', '2026-10-22 06:42:16'),
(18, 24, 'login', 'admin2', '::1', '2026-10-22 06:45:40'),
(19, 11, 'login', 'asfal', '::1', '2026-10-22 06:45:50'),
(20, 11, 'login', 'asfal', '::1', '2026-10-22 06:50:39'),
(21, 11, 'login', 'asfal', '::1', '2026-10-22 06:51:29'),
(22, 2, 'login', 'ilham', '::1', '2026-10-22 07:47:51'),
(23, 11, 'login', 'asfal', '::1', '2026-10-22 07:55:16'),
(24, 1, 'login', 'admin1', '::1', '2026-10-22 08:20:44'),
(25, 1, 'login', 'admin1', '::1', '2026-10-22 08:27:06'),
(26, 11, 'login', 'asfal', '::1', '2026-10-22 08:28:33'),
(27, 11, 'login', 'asfal', '::1', '2026-10-22 08:31:15'),
(28, 11, 'login', 'asfal', '::1', '2026-10-22 18:53:04'),
(29, 1, 'login', 'admin1', '::1', '2026-10-22 18:53:37'),
(30, 11, 'login', 'asfal', '::1', '2026-10-23 01:52:28'),
(31, 11, 'login', 'asfal', '::1', '2026-10-23 02:05:50'),
(32, 1, 'login', 'admin1', '::1', '2026-10-23 02:14:31');

-- --------------------------------------------------------

--
-- Table structure for table `cash_categories`
--

CREATE TABLE `cash_categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(60) NOT NULL,
  `type` enum('pemasukan','pengeluaran') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cash_categories`
--

INSERT INTO `cash_categories` (`id`, `name`, `type`) VALUES
(2, 'Donasi', 'pemasukan'),
(4, 'Kegiatan', 'pengeluaran'),
(5, 'Konsumsi', 'pengeluaran'),
(3, 'Lain-lain', 'pemasukan'),
(7, 'Lain-lain', 'pengeluaran'),
(6, 'Perlengkapan', 'pengeluaran'),
(1, 'Uang kas', 'pemasukan');

-- --------------------------------------------------------

--
-- Table structure for table `cash_payments`
--

CREATE TABLE `cash_payments` (
  `id` int(10) UNSIGNED NOT NULL,
  `period_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `paid_at` date NOT NULL,
  `method` varchar(60) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `status` enum('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  `verified_by` int(10) UNSIGNED DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `verify_note` varchar(255) DEFAULT NULL,
  `receipt_filename` varchar(100) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cash_periods`
--

CREATE TABLE `cash_periods` (
  `id` int(10) UNSIGNED NOT NULL,
  `label` varchar(60) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `due_date` date DEFAULT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cash_transactions`
--

CREATE TABLE `cash_transactions` (
  `id` int(10) UNSIGNED NOT NULL,
  `type` enum('pemasukan','pengeluaran') NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `tx_date` date NOT NULL,
  `category_id` int(10) UNSIGNED DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `receipt_filename` varchar(100) DEFAULT NULL,
  `status` enum('verified','void') NOT NULL DEFAULT 'verified',
  `verified_by` int(10) UNSIGNED DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `voided_by` int(10) UNSIGNED DEFAULT NULL,
  `voided_at` datetime DEFAULT NULL,
  `void_reason` varchar(255) DEFAULT NULL,
  `payment_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `diaries`
--

CREATE TABLE `diaries` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(150) NOT NULL,
  `content` text NOT NULL,
  `privacy` enum('pribadi','kelas') NOT NULL DEFAULT 'pribadi',
  `is_draft` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `attempted_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_history`
--

CREATE TABLE `login_history` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `ip` varchar(45) NOT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `logged_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_history`
--

INSERT INTO `login_history` (`id`, `user_id`, `ip`, `user_agent`, `logged_at`) VALUES
(1, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-22 06:00:01'),
(2, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-22 06:04:02'),
(3, 11, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-22 06:05:29'),
(4, 11, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-22 06:16:04'),
(5, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-22 06:24:26'),
(6, 11, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-22 06:25:56'),
(7, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-22 06:26:08'),
(8, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-22 06:30:18'),
(9, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-22 06:32:12'),
(10, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-22 06:33:16'),
(11, 24, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-22 06:39:31'),
(12, 11, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-22 06:41:01'),
(13, 11, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-22 06:42:16'),
(14, 24, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-22 06:45:40'),
(15, 11, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-22 06:45:50'),
(16, 11, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-22 06:50:39'),
(17, 11, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-22 06:51:29'),
(18, 2, '::1', 'curl/8.18.0', '2026-10-22 07:47:51'),
(19, 11, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-22 07:55:16'),
(20, 1, '::1', '', '2026-10-22 08:20:44'),
(21, 1, '::1', '', '2026-10-22 08:27:06'),
(22, 11, '::1', 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.0 Mobile/15E148 Safari/604.1', '2026-10-22 08:28:33'),
(23, 11, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-22 08:31:15'),
(24, 11, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-22 18:53:04'),
(25, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-22 18:53:37'),
(26, 11, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-23 01:52:28'),
(27, 11, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-23 02:05:50'),
(28, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36', '2026-10-23 02:14:31');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(150) NOT NULL,
  `message` varchar(255) DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_logs`
--

CREATE TABLE `password_reset_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `target_user_id` int(10) UNSIGNED NOT NULL,
  `reset_by` int(10) UNSIGNED DEFAULT NULL,
  `reset_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `photos`
--

CREATE TABLE `photos` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(150) NOT NULL,
  `caption` varchar(500) DEFAULT NULL,
  `filename` varchar(100) NOT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `polls`
--

CREATE TABLE `polls` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` varchar(500) DEFAULT NULL,
  `starts_at` datetime NOT NULL,
  `ends_at` datetime NOT NULL,
  `is_closed` tinyint(1) NOT NULL DEFAULT 0,
  `show_results` enum('selalu','setelah_memilih','setelah_selesai') NOT NULL DEFAULT 'setelah_memilih',
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `poll_options`
--

CREATE TABLE `poll_options` (
  `id` int(10) UNSIGNED NOT NULL,
  `poll_id` int(10) UNSIGNED NOT NULL,
  `label` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `poll_votes`
--

CREATE TABLE `poll_votes` (
  `id` int(10) UNSIGNED NOT NULL,
  `poll_id` int(10) UNSIGNED NOT NULL,
  `option_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `voted_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `schedules`
--

CREATE TABLE `schedules` (
  `id` int(10) UNSIGNED NOT NULL,
  `course_name` varchar(150) NOT NULL,
  `course_code` varchar(30) DEFAULT NULL,
  `lecturer` varchar(120) DEFAULT NULL,
  `day_of_week` tinyint(3) UNSIGNED NOT NULL COMMENT '1=Senin ... 7=Minggu',
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `room` varchar(60) DEFAULT NULL,
  `semester` varchar(30) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `k` varchar(50) NOT NULL,
  `v` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`k`, `v`) VALUES
('opening_balance', '0'),
('show_cash_to_members', '1');

-- --------------------------------------------------------

--
-- Table structure for table `stories`
--

CREATE TABLE `stories` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `media_filename` varchar(100) DEFAULT NULL,
  `caption` varchar(300) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `expires_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','bendahara','anggota') NOT NULL DEFAULT 'anggota',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `must_change_password` tinyint(1) NOT NULL DEFAULT 1,
  `bio` varchar(255) DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  `birthday_public` tinyint(1) NOT NULL DEFAULT 0,
  `profile_public` tinyint(1) NOT NULL DEFAULT 0,
  `avatar` varchar(100) DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `password_hash`, `role`, `is_active`, `must_change_password`, `bio`, `birthday`, `birthday_public`, `profile_public`, `avatar`, `last_login_at`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin1', '$2y$10$B3syWW8HmOwhH5P6ukNn2Ox8MSFggzg5TOuRrevp5gKbzyxE3aFEC', 'admin', 1, 0, NULL, NULL, 0, 0, NULL, '2026-10-23 02:14:31', '2026-10-22 05:59:14', '2026-10-23 02:14:31'),
(2, 'ILHAM', 'ilham', '$2y$10$ptchEkmkm4koXtC.FsWJGOO480VIDMP2OyX1ggvA0iamRue3vCed6', 'anggota', 1, 1, NULL, NULL, 0, 0, NULL, '2026-10-22 07:47:51', '2026-10-22 06:00:41', '2026-10-22 07:47:51'),
(3, 'WINATA', 'winata', '$2y$10$ARk4ZRaAIfqqJEauQmATiO0bLN8od4U23ANyg/5Vb.OImnKWOol3K', 'anggota', 1, 1, NULL, NULL, 0, 0, NULL, NULL, '2026-10-22 06:00:41', '2026-10-22 06:00:41'),
(4, 'LIAN', 'lian', '$2y$10$UMkCz5pZoP1PBBo2r7NMKOM9UyXZXj.jgLOXoORFNtEQ8Rlf1142y', 'anggota', 1, 1, NULL, NULL, 0, 0, NULL, NULL, '2026-10-22 06:00:41', '2026-10-22 06:00:41'),
(5, 'FAREL', 'farel', '$2y$10$WUh1isaDMCwquHLZRZbBke.t15dDEwqQI/lEpRDlWb3kLWcLUUQZ6', 'anggota', 1, 1, NULL, NULL, 0, 0, NULL, NULL, '2026-10-22 06:00:41', '2026-10-22 06:00:41'),
(6, 'IRUL', 'irul', '$2y$10$neX1DlnQCaX3M.21A6Dife8WsvqblHt8YQBvZdL97pveA10as8y4O', 'anggota', 1, 1, NULL, NULL, 0, 0, NULL, NULL, '2026-10-22 06:00:41', '2026-10-22 06:00:41'),
(7, 'ULOK', 'ulok', '$2y$10$yAtrqqL2JsRi8.YsXwNT8etnjH0Cy2lMxxepo4u13Ibk9yRFg3Kze', 'anggota', 1, 1, NULL, NULL, 0, 0, NULL, NULL, '2026-10-22 06:00:41', '2026-10-22 06:00:41'),
(8, 'DIKA', 'dika', '$2y$10$OLnnUkkI7LdGb3d0/aZVDe/C5urOavNec6d0.mZq2H/E7f041ry3e', 'anggota', 1, 1, NULL, NULL, 0, 0, NULL, NULL, '2026-10-22 06:00:41', '2026-10-22 06:00:41'),
(9, 'NABIL', 'nabil', '$2y$10$PhfLSdXLHHobMvhadrYele5fsYA8NA2Cw/VepbwftYo7FWq4ZENS6', 'anggota', 1, 1, NULL, NULL, 0, 0, NULL, NULL, '2026-10-22 06:00:41', '2026-10-22 06:00:41'),
(10, 'YAUMIL', 'yaumil', '$2y$10$o1p3HQZc6HS.6R7082bN0./bLiU6hvsOnQeMR8IfQEByNN0bKakV6', 'anggota', 1, 1, NULL, NULL, 0, 0, NULL, NULL, '2026-10-22 06:00:42', '2026-10-22 06:00:42'),
(11, 'ASFAL', 'asfal', '$2y$10$k4aV3fbTweQfU6Leko3LguOfNUdqqQtRdBlxdHhcHvMuTnTvD3p5G', 'anggota', 1, 0, NULL, NULL, 0, 0, NULL, '2026-10-23 02:05:50', '2026-10-22 06:00:42', '2026-10-23 02:05:50'),
(12, 'JALL', 'jall', '$2y$10$1kLSCEQ09uBx4oFrcrFe9.UYcf7h3wdDlnAgwOvdbLBAmhF.Z83VW', 'anggota', 1, 1, NULL, NULL, 0, 0, NULL, NULL, '2026-10-22 06:00:42', '2026-10-22 06:00:42'),
(13, 'MINA', 'mina', '$2y$10$O6ExZsXEglagVp17nFvUpunMxVUQWlFr8DMJckT/Q.GJP0Ufmzlre', 'anggota', 1, 1, NULL, NULL, 0, 0, NULL, NULL, '2026-10-22 06:00:42', '2026-10-22 06:00:42'),
(14, 'CACA', 'caca', '$2y$10$3Tb.7DzhpXyd8yjNlSKP2uV2eLVV8U2cI2HXeys1xM8yYh4nvRS1u', 'anggota', 1, 1, NULL, NULL, 0, 0, NULL, NULL, '2026-10-22 06:00:42', '2026-10-22 06:00:42'),
(15, 'DINDA', 'dinda', '$2y$10$ch70X9lYKvW891et0ifQNuPnLEsVHbwYDQSmbXEmD8pftR6QdsVzy', 'anggota', 1, 1, NULL, NULL, 0, 0, NULL, NULL, '2026-10-22 06:00:42', '2026-10-22 06:00:42'),
(16, 'ALDA', 'alda', '$2y$10$k/omdBTiZF9kOdXiqTKPMORDHER398BLJv/7GgJH4tRopZd0K27NW', 'anggota', 1, 1, NULL, NULL, 0, 0, NULL, NULL, '2026-10-22 06:00:42', '2026-10-22 06:00:42'),
(17, 'FATIMAH', 'fatimah', '$2y$10$70j6ZrJuOs8KAZN6cJBmSejwYQGuw91H8jOZsHH/Bvf2zqZSla/be', 'anggota', 1, 1, NULL, NULL, 0, 0, NULL, NULL, '2026-10-22 06:00:42', '2026-10-22 06:00:42'),
(18, 'OBY', 'oby', '$2y$10$FR4WQ1RM7fXleZZQR23gWOxRRbfnpQ8rV4OxbD8Gp4fikbC6lxBEO', 'anggota', 1, 1, NULL, NULL, 0, 0, NULL, NULL, '2026-10-22 06:00:42', '2026-10-22 06:00:42'),
(19, 'FARHAN', 'farhan', '$2y$10$a.B2xOq.II4KhAGYOaiPRO8OToZnLk4oXNPnSmFpvo7peoWRiideG', 'anggota', 1, 1, NULL, NULL, 0, 0, NULL, NULL, '2026-10-22 06:00:42', '2026-10-22 06:00:42'),
(20, 'SUSAN', 'susan', '$2y$10$/l8mzduVVDVGkjaOL8VawOKj/YK9oWl9rYnvwdXpRLT0jKToIke6S', 'anggota', 1, 1, NULL, NULL, 0, 0, NULL, NULL, '2026-10-22 06:00:42', '2026-10-22 06:00:42'),
(21, 'HARIS', 'haris', '$2y$10$aNaFYXjyiRo/EZeKhHTlaegtV.ZRhEJYnPEzokiKWnnoMILgGmm1i', 'anggota', 1, 1, NULL, NULL, 0, 0, NULL, NULL, '2026-10-22 06:00:42', '2026-10-22 06:00:42'),
(22, 'NURI', 'nuri', '$2y$10$BSjbGi/SpDwnjjM3ZBqzbuWfl.e13aqd/nvSRf7rs8TQ8IqhpXwh6', 'anggota', 1, 1, NULL, NULL, 0, 0, NULL, NULL, '2026-10-22 06:00:42', '2026-10-22 06:00:42'),
(23, 'NADYA', 'nadya', '$2y$10$W53l5xw7nOpKlRHiHfO2rOowGeChdJKnlSBPKLi8n10BwOpbDiAU.', 'anggota', 1, 1, NULL, NULL, 0, 0, NULL, NULL, '2026-10-22 06:00:42', '2026-10-22 06:00:42'),
(24, 'admin2', 'admin2', '$2y$10$hcrRcEW3oSdjFlTPkDP2SexudrdYHNu50r3CuaVTBjR7g2/f3MDM.', 'admin', 1, 0, NULL, NULL, 0, 0, NULL, '2026-10-22 06:45:40', '2026-10-22 06:39:05', '2026-10-22 06:45:40');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `agendas`
--
ALTER TABLE `agendas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_agenda_date` (`event_date`),
  ADD KEY `idx_agenda_status` (`status`),
  ADD KEY `fk_agenda_user` (`created_by`);

--
-- Indexes for table `agenda_participants`
--
ALTER TABLE `agenda_participants`
  ADD PRIMARY KEY (`agenda_id`,`user_id`),
  ADD KEY `fk_apart_user` (`user_id`);

--
-- Indexes for table `albums`
--
ALTER TABLE `albums`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_albums_date` (`event_date`),
  ADD KEY `fk_albums_user` (`user_id`),
  ADD KEY `fk_albums_cover` (`cover_photo_id`);

--
-- Indexes for table `album_photos`
--
ALTER TABLE `album_photos`
  ADD PRIMARY KEY (`album_id`,`photo_id`),
  ADD KEY `fk_ap_photo` (`photo_id`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ann_pub` (`is_pinned`,`published_at`),
  ADD KEY `fk_ann_user` (`created_by`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_audit_time` (`created_at`),
  ADD KEY `fk_audit_user` (`user_id`);

--
-- Indexes for table `cash_categories`
--
ALTER TABLE `cash_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_cat` (`name`,`type`);

--
-- Indexes for table `cash_payments`
--
ALTER TABLE `cash_payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_payment` (`period_id`,`user_id`),
  ADD KEY `idx_pay_status` (`status`),
  ADD KEY `fk_pay_user` (`user_id`),
  ADD KEY `fk_pay_verifier` (`verified_by`);

--
-- Indexes for table `cash_periods`
--
ALTER TABLE `cash_periods`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_period_label` (`label`),
  ADD KEY `fk_period_user` (`created_by`);

--
-- Indexes for table `cash_transactions`
--
ALTER TABLE `cash_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tx_date` (`tx_date`),
  ADD KEY `idx_tx_type` (`type`,`status`),
  ADD KEY `fk_tx_cat` (`category_id`),
  ADD KEY `fk_tx_creator` (`created_by`),
  ADD KEY `fk_tx_payment` (`payment_id`);

--
-- Indexes for table `diaries`
--
ALTER TABLE `diaries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_diary_user` (`user_id`,`created_at`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_attempt_user` (`username`,`attempted_at`),
  ADD KEY `idx_attempt_ip` (`ip`,`attempted_at`);

--
-- Indexes for table `login_history`
--
ALTER TABLE `login_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_lh_user` (`user_id`,`logged_at`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notif_user` (`user_id`,`is_read`,`created_at`);

--
-- Indexes for table `password_reset_logs`
--
ALTER TABLE `password_reset_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_prl_target` (`target_user_id`),
  ADD KEY `fk_prl_by` (`reset_by`);

--
-- Indexes for table `photos`
--
ALTER TABLE `photos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_photos_created` (`created_at`),
  ADD KEY `idx_photos_user` (`user_id`);

--
-- Indexes for table `polls`
--
ALTER TABLE `polls`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_poll_end` (`ends_at`),
  ADD KEY `fk_poll_user` (`created_by`);

--
-- Indexes for table `poll_options`
--
ALTER TABLE `poll_options`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_opt_poll` (`poll_id`);

--
-- Indexes for table `poll_votes`
--
ALTER TABLE `poll_votes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_vote` (`poll_id`,`user_id`),
  ADD KEY `fk_vote_opt` (`option_id`),
  ADD KEY `fk_vote_user` (`user_id`);

--
-- Indexes for table `schedules`
--
ALTER TABLE `schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sched_day` (`day_of_week`,`start_time`),
  ADD KEY `fk_sched_user` (`created_by`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`k`);

--
-- Indexes for table `stories`
--
ALTER TABLE `stories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_story_expires` (`expires_at`),
  ADD KEY `idx_story_user` (`user_id`,`created_at`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_users_username` (`username`),
  ADD KEY `idx_users_role` (`role`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `agendas`
--
ALTER TABLE `agendas`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `albums`
--
ALTER TABLE `albums`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `cash_categories`
--
ALTER TABLE `cash_categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `cash_payments`
--
ALTER TABLE `cash_payments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cash_periods`
--
ALTER TABLE `cash_periods`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cash_transactions`
--
ALTER TABLE `cash_transactions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `diaries`
--
ALTER TABLE `diaries`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `login_history`
--
ALTER TABLE `login_history`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_reset_logs`
--
ALTER TABLE `password_reset_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `photos`
--
ALTER TABLE `photos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `polls`
--
ALTER TABLE `polls`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `poll_options`
--
ALTER TABLE `poll_options`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `poll_votes`
--
ALTER TABLE `poll_votes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `schedules`
--
ALTER TABLE `schedules`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stories`
--
ALTER TABLE `stories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `agendas`
--
ALTER TABLE `agendas`
  ADD CONSTRAINT `fk_agenda_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `agenda_participants`
--
ALTER TABLE `agenda_participants`
  ADD CONSTRAINT `fk_apart_agenda` FOREIGN KEY (`agenda_id`) REFERENCES `agendas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_apart_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `albums`
--
ALTER TABLE `albums`
  ADD CONSTRAINT `fk_albums_cover` FOREIGN KEY (`cover_photo_id`) REFERENCES `photos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_albums_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `album_photos`
--
ALTER TABLE `album_photos`
  ADD CONSTRAINT `fk_ap_album` FOREIGN KEY (`album_id`) REFERENCES `albums` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ap_photo` FOREIGN KEY (`photo_id`) REFERENCES `photos` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `fk_ann_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `cash_payments`
--
ALTER TABLE `cash_payments`
  ADD CONSTRAINT `fk_pay_period` FOREIGN KEY (`period_id`) REFERENCES `cash_periods` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pay_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pay_verifier` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `cash_periods`
--
ALTER TABLE `cash_periods`
  ADD CONSTRAINT `fk_period_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `cash_transactions`
--
ALTER TABLE `cash_transactions`
  ADD CONSTRAINT `fk_tx_cat` FOREIGN KEY (`category_id`) REFERENCES `cash_categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_tx_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_tx_payment` FOREIGN KEY (`payment_id`) REFERENCES `cash_payments` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `diaries`
--
ALTER TABLE `diaries`
  ADD CONSTRAINT `fk_diary_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `login_history`
--
ALTER TABLE `login_history`
  ADD CONSTRAINT `fk_lh_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `password_reset_logs`
--
ALTER TABLE `password_reset_logs`
  ADD CONSTRAINT `fk_prl_by` FOREIGN KEY (`reset_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_prl_target` FOREIGN KEY (`target_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `photos`
--
ALTER TABLE `photos`
  ADD CONSTRAINT `fk_photos_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `polls`
--
ALTER TABLE `polls`
  ADD CONSTRAINT `fk_poll_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `poll_options`
--
ALTER TABLE `poll_options`
  ADD CONSTRAINT `fk_opt_poll` FOREIGN KEY (`poll_id`) REFERENCES `polls` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `poll_votes`
--
ALTER TABLE `poll_votes`
  ADD CONSTRAINT `fk_vote_opt` FOREIGN KEY (`option_id`) REFERENCES `poll_options` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_vote_poll` FOREIGN KEY (`poll_id`) REFERENCES `polls` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_vote_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `schedules`
--
ALTER TABLE `schedules`
  ADD CONSTRAINT `fk_sched_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `stories`
--
ALTER TABLE `stories`
  ADD CONSTRAINT `fk_story_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
