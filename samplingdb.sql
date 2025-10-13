-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               8.0.30 - MySQL Community Server - GPL
-- Server OS:                    Win64
-- HeidiSQL Version:             12.1.0.6537
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for samplingdb
CREATE DATABASE IF NOT EXISTS `samplingdb` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `samplingdb`;

-- Dumping structure for table samplingdb.contoh
CREATE TABLE IF NOT EXISTS `contoh` (
  `id` int NOT NULL AUTO_INCREMENT,
  `formulir_id` int DEFAULT NULL,
  `nama_contoh` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `jenis_contoh` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `merek` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `kode` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `prosedur` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `parameter` text COLLATE utf8mb4_general_ci,
  `baku_mutu` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `catatan` text COLLATE utf8mb4_general_ci,
  `file_berita_acara` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `file_sppc` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `formulir_id` (`formulir_id`),
  CONSTRAINT `contoh_ibfk_1` FOREIGN KEY (`formulir_id`) REFERENCES `formulir` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table samplingdb.formulir
CREATE TABLE IF NOT EXISTS `formulir` (
  `id` int NOT NULL AUTO_INCREMENT,
  `jenis_laporan` enum('air','udara','kebisingan','getaran') COLLATE utf8mb4_general_ci NOT NULL,
  `perusahaan` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `alamat` text COLLATE utf8mb4_general_ci NOT NULL,
  `tanggal_mulai` date DEFAULT NULL,
  `tanggal_selesai` date DEFAULT NULL,
  `jenis_kegiatan` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pengambil_sampel` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sub_kontrak_nama` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tujuan_pemeriksaan` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tujuan_pemeriksaan_lainnya` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_by` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table samplingdb.laporan
CREATE TABLE IF NOT EXISTS `laporan` (
  `id` int NOT NULL AUTO_INCREMENT,
  `jenis_laporan` enum('air','udara','kebisingan','getaran') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'air',
  `form_id` int NOT NULL,
  `ppc_id` int NOT NULL,
  `ttd_ppc` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nama_ppc_tercetak` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `penyelia_id` int DEFAULT NULL,
  `waktu_verifikasi_penyelia` datetime DEFAULT NULL,
  `ttd_penyelia` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mt_id` int DEFAULT NULL,
  `waktu_persetujuan_mt` datetime DEFAULT NULL,
  `ttd_mt` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nama_mt_tercetak` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `penerima_id` int DEFAULT NULL,
  `waktu_penyelesaian_penerima` datetime DEFAULT NULL,
  `status` enum('Draft','Menunggu Verifikasi','Revisi PPC','Revisi Penyelia','Menunggu Persetujuan MT','Disetujui, Siap Dicetak','Selesai') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Draft',
  `catatan_revisi` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table samplingdb.riwayat_revisi
CREATE TABLE IF NOT EXISTS `riwayat_revisi` (
  `id` int NOT NULL AUTO_INCREMENT,
  `laporan_id` int NOT NULL,
  `revisi_oleh_id` int NOT NULL,
  `catatan_revisi` text COLLATE utf8mb4_general_ci,
  `tanggal_revisi_diminta` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `tanggal_diperbaiki` timestamp NULL DEFAULT NULL,
  `status_awal` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status_tujuan` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `laporan_id` (`laporan_id`),
  CONSTRAINT `riwayat_revisi_ibfk_1` FOREIGN KEY (`laporan_id`) REFERENCES `laporan` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table samplingdb.roles
CREATE TABLE IF NOT EXISTS `roles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama_role` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table samplingdb.users
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `nama_lengkap` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `role_id` int NOT NULL,
  `tanda_tangan` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `role_id` (`role_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
