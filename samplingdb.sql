-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 09, 2025 at 06:06 AM
-- Server version: 10.4.27-MariaDB
-- PHP Version: 8.0.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `samplingdb`
--

-- --------------------------------------------------------

--
-- Struktur tabel untuk `roles`
--
CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_role` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Isi data untuk tabel `roles`
--
INSERT INTO `roles` (`id`, `nama_role`) VALUES
(1, 'Petugas Pengambil Contoh'),
(2, 'Penyelia'),
(3, 'Manajer Teknis'),
(4, 'Penerima Contoh');

-- --------------------------------------------------------

--
-- Struktur tabel untuk `laporan`
--
CREATE TABLE `laporan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `jenis_laporan` enum('udara','air') NOT NULL,
  `form_id` int(11) NOT NULL,
  `ppc_id` int(11) NOT NULL,
  `penyelia_id` int(11) DEFAULT NULL,
  `mt_id` int(11) DEFAULT NULL,
  `penerima_id` int(11) DEFAULT NULL,
  `status` enum('Draft','Menunggu Verifikasi','Revisi PPC','Menunggu Persetujuan MT','Revisi Penyelia','Disetahui, Siap Dicetak','Selesai') NOT NULL DEFAULT 'Draft',
  `catatan_revisi` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur tabel untuk `contoh_air`
--
CREATE TABLE `contoh_air` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `formulir_id` int(11) DEFAULT NULL,
  `nama_contoh` varchar(100) DEFAULT NULL,
  `jenis_contoh` varchar(100) DEFAULT NULL,
  `merek` varchar(100) DEFAULT NULL,
  `kode` varchar(50) DEFAULT NULL,
  `prosedur` varchar(150) DEFAULT NULL,
  `parameter` text DEFAULT NULL,
  `baku_mutu` varchar(150) DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `formulir_id` (`formulir_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur tabel untuk `contoh_udara`
--
CREATE TABLE `contoh_udara` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `formulir_id` int(11) DEFAULT NULL,
  `nama_contoh` varchar(100) DEFAULT NULL,
  `jenis_contoh` varchar(100) DEFAULT NULL,
  `merek` varchar(100) DEFAULT NULL,
  `kode` varchar(50) DEFAULT NULL,
  `prosedur` varchar(150) DEFAULT NULL,
  `parameter` text DEFAULT NULL,
  `baku_mutu` varchar(150) DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `formulir_id` (`formulir_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur tabel untuk `formulir_air`
--
DROP TABLE IF EXISTS `formulir_air`;
CREATE TABLE `formulir_air` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `perusahaan` varchar(150) NOT NULL,
  `alamat` text NOT NULL,
  `tanggal` date NOT NULL,
  `dokumen` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur tabel untuk `formulir_udara`
--
DROP TABLE IF EXISTS `formulir_udara`;
CREATE TABLE `formulir_udara` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `perusahaan` varchar(150) NOT NULL,
  `alamat` text NOT NULL,
  `tanggal` date NOT NULL,
  `dokumen` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur tabel untuk `users`
--
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) DEFAULT NULL,
  `role_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `role_id` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Constraints for dumped tables
--

--
-- Constraints for table `contoh_air`
--
ALTER TABLE `contoh_air`
  ADD CONSTRAINT `contoh_air_ibfk_1` FOREIGN KEY (`formulir_id`) REFERENCES `formulir_air` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `contoh_udara`
--
ALTER TABLE `contoh_udara`
  ADD CONSTRAINT `contoh_udara_ibfk_1` FOREIGN KEY (`formulir_id`) REFERENCES `formulir_udara` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;