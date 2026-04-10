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

-- Dumping structure for table bprs-procurement.cache
CREATE TABLE IF NOT EXISTS `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table bprs-procurement.cache: ~6 rows (approximately)
INSERT INTO `cache` (`key`, `value`, `expiration`) VALUES
	('e_procurement_cache_7719a1c782a1ba91c031a682a0a2f8658209adbf', 'i:1;', 1763692154),
	('e_procurement_cache_7719a1c782a1ba91c031a682a0a2f8658209adbf:timer', 'i:1763692154;', 1763692154),
	('e_procurement_cache_92cfceb39d57d914ed8b14d0e37643de0797ae56', 'i:1;', 1759981373),
	('e_procurement_cache_92cfceb39d57d914ed8b14d0e37643de0797ae56:timer', 'i:1759981373;', 1759981373),
	('e_procurement_cache_livewire-rate-limiter:056fc329aaaa757d31db450f525da23fde4d1b36', 'i:1;', 1770888793),
	('e_procurement_cache_livewire-rate-limiter:056fc329aaaa757d31db450f525da23fde4d1b36:timer', 'i:1770888793;', 1770888793),
	('e_procurement_cache_spatie.permission.cache', 'a:3:{s:5:"alias";a:4:{s:1:"a";s:2:"id";s:1:"b";s:4:"name";s:1:"c";s:10:"guard_name";s:1:"r";s:5:"roles";}s:11:"permissions";a:12:{i:0;a:4:{s:1:"a";i:1;s:1:"b";s:14:"buat pengajuan";s:1:"c";s:3:"web";s:1:"r";a:9:{i:0;i:1;i:1;i:2;i:2;i:3;i:3;i:4;i:4;i:5;i:5;i:8;i:6;i:9;i:7;i:10;i:8;i:12;}}i:1;a:4:{s:1:"a";i:2;s:1:"b";s:21:"lihat semua pengajuan";s:1:"c";s:3:"web";s:1:"r";a:3:{i:0;i:9;i:1;i:10;i:2;i:12;}}i:2;a:4:{s:1:"a";i:3;s:1:"b";s:18:"kelola master data";s:1:"c";s:3:"web";s:1:"r";a:1:{i:0;i:12;}}i:3;a:4:{s:1:"a";i:4;s:1:"b";s:16:"approval manager";s:1:"c";s:3:"web";s:1:"r";a:2:{i:0;i:3;i:1;i:12;}}i:4;a:4:{s:1:"a";i:5;s:1:"b";s:14:"approval kadiv";s:1:"c";s:3:"web";s:1:"r";a:3:{i:0;i:4;i:1;i:8;i:2;i:12;}}i:5;a:4:{s:1:"a";i:6;s:1:"b";s:14:"rekomendasi it";s:1:"c";s:3:"web";s:1:"r";a:2:{i:0;i:5;i:1;i:12;}}i:6;a:4:{s:1:"a";i:7;s:1:"b";s:15:"survei harga ga";s:1:"c";s:3:"web";s:1:"r";a:2:{i:0;i:6;i:1;i:12;}}i:7;a:4:{s:1:"a";i:8;s:1:"b";s:15:"approval budget";s:1:"c";s:3:"web";s:1:"r";a:2:{i:0;i:7;i:1;i:12;}}i:8;a:4:{s:1:"a";i:9;s:1:"b";s:23:"finalisasi keputusan ga";s:1:"c";s:3:"web";s:1:"r";a:2:{i:0;i:8;i:1;i:12;}}i:9;a:4:{s:1:"a";i:10;s:1:"b";s:29:"approval direktur operasional";s:1:"c";s:3:"web";s:1:"r";a:2:{i:0;i:9;i:1;i:12;}}i:10;a:4:{s:1:"a";i:11;s:1:"b";s:23:"approval direktur utama";s:1:"c";s:3:"web";s:1:"r";a:2:{i:0;i:10;i:1;i:12;}}i:11;a:4:{s:1:"a";i:12;s:1:"b";s:14:"pencairan dana";s:1:"c";s:3:"web";s:1:"r";a:2:{i:0;i:11;i:1;i:12;}}}s:5:"roles";a:12:{i:0;a:3:{s:1:"a";i:1;s:1:"b";s:5:"Staff";s:1:"c";s:3:"web";}i:1;a:3:{s:1:"a";i:2;s:1:"b";s:11:"Team Leader";s:1:"c";s:3:"web";}i:2;a:3:{s:1:"a";i:3;s:1:"b";s:7:"Manager";s:1:"c";s:3:"web";}i:3;a:3:{s:1:"a";i:4;s:1:"b";s:13:"Kepala Divisi";s:1:"c";s:3:"web";}i:4;a:3:{s:1:"a";i:5;s:1:"b";s:16:"Kepala Divisi IT";s:1:"c";s:3:"web";}i:5;a:3:{s:1:"a";i:8;s:1:"b";s:16:"Kepala Divisi GA";s:1:"c";s:3:"web";}i:6;a:3:{s:1:"a";i:9;s:1:"b";s:20:"Direktur Operasional";s:1:"c";s:3:"web";}i:7;a:3:{s:1:"a";i:10;s:1:"b";s:14:"Direktur Utama";s:1:"c";s:3:"web";}i:8;a:3:{s:1:"a";i:12;s:1:"b";s:11:"Super Admin";s:1:"c";s:3:"web";}i:9;a:3:{s:1:"a";i:6;s:1:"b";s:15:"General Affairs";s:1:"c";s:3:"web";}i:10;a:3:{s:1:"a";i:7;s:1:"b";s:13:"Tim Budgeting";s:1:"c";s:3:"web";}i:11;a:3:{s:1:"a";i:11;s:1:"b";s:25:"Kepala Divisi Operasional";s:1:"c";s:3:"web";}}}', 1770975143);

-- Dumping structure for table bprs-procurement.cache_locks
CREATE TABLE IF NOT EXISTS `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table bprs-procurement.cache_locks: ~0 rows (approximately)

-- Dumping structure for table bprs-procurement.divisis
CREATE TABLE IF NOT EXISTS `divisis` (
  `id_divisi` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nama_divisi` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_kantor` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_divisi`),
  KEY `divisis_id_kantor_foreign` (`id_kantor`),
  CONSTRAINT `divisis_id_kantor_foreign` FOREIGN KEY (`id_kantor`) REFERENCES `kantors` (`id_kantor`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table bprs-procurement.divisis: ~10 rows (approximately)
INSERT INTO `divisis` (`id_divisi`, `nama_divisi`, `id_kantor`, `created_at`, `updated_at`) VALUES
	(1, 'Bisnis', 1, '2025-09-19 00:15:52', '2025-09-19 00:15:52'),
	(2, 'Corporate Secretary', 1, '2025-09-19 00:15:52', '2025-09-19 00:15:52'),
	(3, 'Pengurus', 1, '2025-09-19 00:15:52', '2025-09-19 00:15:52'),
	(4, 'HR, GA dan Legal', 1, '2025-09-19 00:15:52', '2025-09-19 00:15:52'),
	(5, 'IT, MIS & Product Development', 1, '2025-09-19 00:15:52', '2025-09-19 00:15:52'),
	(6, 'Manajemen Risiko', 1, '2025-09-19 00:15:52', '2025-09-19 00:15:52'),
	(7, 'Operasional', 1, '2025-09-19 00:15:52', '2025-09-19 00:15:52'),
	(8, 'Remedial & Collection', 1, '2025-09-19 00:15:52', '2025-09-19 00:15:52'),
	(9, 'Satuan Kerja Audit Internal (SKAI)', 1, '2025-09-19 00:15:52', '2025-09-19 00:15:52'),
	(10, 'Satuan Kerja Kepatuhan & APU-PPT', 1, '2025-09-19 00:15:52', '2025-09-19 00:15:52');

-- Dumping structure for table bprs-procurement.jabatans
CREATE TABLE IF NOT EXISTS `jabatans` (
  `id_jabatan` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nama_jabatan` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type_jabatan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_kantor` bigint unsigned NOT NULL,
  `id_divisi` bigint unsigned NOT NULL,
  `acc_jabatan_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_jabatan`),
  KEY `jabatans_id_kantor_foreign` (`id_kantor`),
  KEY `jabatans_id_divisi_foreign` (`id_divisi`),
  KEY `jabatans_acc_jabatan_id_foreign` (`acc_jabatan_id`),
  CONSTRAINT `jabatans_acc_jabatan_id_foreign` FOREIGN KEY (`acc_jabatan_id`) REFERENCES `jabatans` (`id_jabatan`) ON DELETE SET NULL,
  CONSTRAINT `jabatans_id_divisi_foreign` FOREIGN KEY (`id_divisi`) REFERENCES `divisis` (`id_divisi`) ON DELETE CASCADE,
  CONSTRAINT `jabatans_id_kantor_foreign` FOREIGN KEY (`id_kantor`) REFERENCES `kantors` (`id_kantor`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table bprs-procurement.jabatans: ~39 rows (approximately)
INSERT INTO `jabatans` (`id_jabatan`, `nama_jabatan`, `type_jabatan`, `id_kantor`, `id_divisi`, `acc_jabatan_id`, `created_at`, `updated_at`) VALUES
	(1, 'Account Officer UMKM', NULL, 1, 1, 3, '2026-02-09 01:45:15', '2026-02-09 01:45:15'),
	(2, 'Funding Officer Retail', NULL, 1, 1, 5, '2026-02-09 01:45:15', '2026-02-09 01:45:15'),
	(3, 'Manager Pembiayaan', NULL, 1, 1, 7, '2026-02-09 01:45:15', '2026-02-09 01:45:15'),
	(4, 'Account Officer Retail', NULL, 1, 1, 3, '2026-02-09 01:45:15', '2026-02-09 01:45:15'),
	(5, 'Team Leader Funding', NULL, 1, 1, 7, '2026-02-09 01:45:15', '2026-02-09 01:45:15'),
	(6, 'Funding Officer Corporate', NULL, 1, 1, 5, '2026-02-09 01:45:15', '2026-02-09 01:45:15'),
	(7, 'Kepala Divisi Bisnis', NULL, 1, 1, 12, '2026-02-09 01:45:15', '2026-02-09 01:45:15'),
	(8, 'Team Leader ZIS, WAF & Social Media', NULL, 1, 1, 7, '2026-02-09 01:45:15', '2026-02-09 01:45:15'),
	(9, 'Staff Admin Corporate Secretary', NULL, 1, 2, 10, '2026-02-09 01:45:15', '2026-02-09 01:45:15'),
	(10, 'Kepala Divisi Corporate Secretary', NULL, 1, 2, 12, '2026-02-09 01:45:15', '2026-02-09 01:45:15'),
	(11, 'Staf Design Grafis', NULL, 1, 2, 10, '2026-02-09 01:45:15', '2026-02-09 01:45:15'),
	(12, 'Direktur Utama', NULL, 1, 3, NULL, '2026-02-09 01:45:15', '2026-02-09 01:45:15'),
	(13, 'Direktur Operasional & Kepatuhan', NULL, 1, 3, NULL, '2026-02-09 01:45:15', '2026-02-09 01:45:15'),
	(14, 'Staff Appraisal', NULL, 1, 4, 21, '2026-02-09 01:45:15', '2026-02-09 01:45:15'),
	(15, 'Office Boy', NULL, 1, 4, 19, '2026-02-09 01:45:15', '2026-02-09 01:45:15'),
	(16, 'Staff Admin Legal', NULL, 1, 4, 21, '2026-02-09 01:45:15', '2026-02-09 01:45:15'),
	(17, 'Security', NULL, 1, 4, 19, '2026-02-09 01:45:15', '2026-02-09 01:45:15'),
	(18, 'Driver', NULL, 1, 4, 19, '2026-02-09 01:45:15', '2026-02-09 01:45:15'),
	(19, 'Staff GA', NULL, 1, 4, 22, '2026-02-09 01:45:15', '2026-02-09 01:45:15'),
	(20, 'Staff HRD', NULL, 1, 4, 22, '2026-02-09 01:45:15', '2026-02-09 01:45:15'),
	(21, 'Manager Legal', NULL, 1, 4, 22, '2026-02-09 01:45:15', '2026-02-09 01:45:15'),
	(22, 'Kepala Divisi HR, GA dan Legal', NULL, 1, 4, 13, '2026-02-09 01:45:15', '2026-02-09 01:45:15'),
	(23, 'Staff IT & MIS', NULL, 1, 5, 24, '2026-02-09 01:45:15', '2026-02-09 01:45:15'),
	(24, 'Team Leader IT & MIS', NULL, 1, 5, 25, '2026-02-09 01:45:15', '2026-02-09 01:45:15'),
	(25, 'Kadiv IT, MIS & Product Development', NULL, 1, 5, 13, '2026-02-09 01:45:15', '2026-02-09 01:45:15'),
	(26, 'System Administrator', NULL, 1, 5, NULL, '2026-02-09 01:45:15', '2026-02-09 01:45:15'),
	(27, 'Pejabat Eksekutif Manajemen Risiko', NULL, 1, 6, 13, '2026-02-09 01:45:15', '2026-02-09 01:45:15'),
	(28, 'Staff Accounting', NULL, 1, 7, 31, '2026-02-09 01:45:15', '2026-02-09 01:45:15'),
	(29, 'Staff Operasional', NULL, 1, 7, 31, '2026-02-09 01:45:15', '2026-02-09 01:45:15'),
	(30, 'Teller', NULL, 1, 7, 33, '2026-02-09 01:45:15', '2026-02-09 01:45:15'),
	(31, 'Kepala Divisi Operasional', NULL, 1, 7, 13, '2026-02-09 01:45:15', '2026-02-09 01:45:15'),
	(32, 'Customer Service', NULL, 1, 7, 33, '2026-02-09 01:45:15', '2026-02-09 01:45:15'),
	(33, 'Head Teller & Customer Service', NULL, 1, 7, 31, '2026-02-09 01:45:15', '2026-02-09 01:45:15'),
	(34, 'Staff Asset Management Unit (AMU)', NULL, 1, 8, NULL, '2026-02-09 01:45:15', '2026-02-09 01:45:15'),
	(35, 'Staff Collection & Remedial', NULL, 1, 8, 36, '2026-02-09 01:45:15', '2026-02-09 01:45:15'),
	(36, 'Team Leader Collection & Remedial', NULL, 1, 8, NULL, '2026-02-09 01:45:15', '2026-02-09 01:45:15'),
	(37, 'Staff Satuan Kerja Audit Internal (SKAI)', NULL, 1, 9, 38, '2026-02-09 01:45:15', '2026-02-09 01:45:15'),
	(38, 'Kepala Satuan Kerja Audit Internal (SKAI)', NULL, 1, 9, 12, '2026-02-09 01:45:15', '2026-02-09 01:45:15'),
	(39, 'Kepala Satuan Kerja Kepatuhan & APU-PPT', NULL, 1, 10, 13, '2026-02-09 01:45:15', '2026-02-09 01:45:15');

-- Dumping structure for table bprs-procurement.kantors
CREATE TABLE IF NOT EXISTS `kantors` (
  `id_kantor` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nama_kantor` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `alamat_kantor` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `kode_kantor` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_kantor`),
  UNIQUE KEY `kantors_kode_kantor_unique` (`kode_kantor`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table bprs-procurement.kantors: ~0 rows (approximately)
INSERT INTO `kantors` (`id_kantor`, `nama_kantor`, `alamat_kantor`, `kode_kantor`, `created_at`, `updated_at`) VALUES
	(1, 'Kantor Pusat', 'Alamat Kantor Pusat Default', '01', '2025-09-19 00:15:52', '2025-09-19 00:15:52');

-- Dumping structure for table bprs-procurement.migrations
CREATE TABLE IF NOT EXISTS `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=54 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table bprs-procurement.migrations: ~51 rows (approximately)
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
	(1, '2025_06_16_072620_create_kantors_table', 1),
	(2, '2025_06_16_072621_create_divisis_table', 1),
	(3, '2025_06_16_072621_create_jabatans_table', 1),
	(4, '2025_06_16_072623_create_users_and_sessions_tables', 1),
	(5, '2025_06_16_073801_create_cache_table', 1),
	(6, '2025_06_17_030154_create_permission_tables', 1),
	(7, '2025_06_17_031646_create_pengajuans_table', 1),
	(8, '2025_06_17_031648_create_pengajuan_items_table', 1),
	(9, '2025_06_17_043444_add_nilai_to_pengajuans_table', 1),
	(10, '2025_06_18_064512_add_rekomendasi_it_to_pengajuans_table', 1),
	(11, '2025_06_18_064513_add_harga_final_to_pengajuan_items_table', 1),
	(12, '2025_06_18_064515_create_survei_hargas_table', 1),
	(13, '2025_06_18_075130_add_budget_fields_to_pengajuans_table', 1),
	(14, '2025_06_18_091540_add_kadiv_ga_decision_to_pengajuans_table', 1),
	(15, '2025_06_20_060300_add_tipe_survei_to_survei_hargas_table', 1),
	(16, '2025_06_23_073301_add_dual_budget_status_to_pengajuans_table', 1),
	(17, '2025_06_23_092544_add_approval_history_to_pengajuans_table', 1),
	(18, '2025_06_24_063938_add_payment_options_to_pengajuans_table', 1),
	(19, '2025_06_25_024831_add_payment_details_to_survei_hargas_table', 1),
	(20, '2025_06_25_032159_remove_payment_options_from_pengajuans_table', 1),
	(21, '2025_07_01_101709_add_metode_pembayaran_to_survei_hargas_table', 1),
	(22, '2025_07_01_110355_add_disbursed_by_to_pengajuans_table', 1),
	(23, '2025_07_02_035710_add_bukti_columns_to_survei_hargas_table', 1),
	(24, '2025_07_02_041622_add_bukti_penyelesaian_to_survei_hargas_table', 1),
	(25, '2025_07_02_072936_add_is_final_to_survei_hargas_table', 1),
	(26, '2025_07_07_033440_add_kadiv_ops_budget_approved_by_to_pengajuans_table', 1),
	(27, '2025_07_09_030702_update_tax_logic_in_survei_hargas_table', 1),
	(28, '2025_07_09_065357_add_rincian_harga_to_survei_hargas_table', 1),
	(29, '2025_07_11_022425_modify_bukti_path_to_nullable_in_survei_hargas_table', 1),
	(30, '2025_07_11_073834_add_approval_columns_to_pengajuan_table', 1),
	(31, '2025_07_23_100000_create_revisi_hargas_table', 1),
	(32, '2025_07_25_070950_add_budget_review_to_revisi_hargas_table', 1),
	(33, '2025_07_28_084113_add_kadiv_ga_approval_to_revisi_hargas_table', 1),
	(34, '2025_07_29_081238_add_validation_fields_to_revisi_hargas_table', 1),
	(35, '2025_07_29_093635_remove_catatan_validasi_from_revisi_hargas_table', 1),
	(36, '2025_07_30_064606_add_approval_fields_to_revisi_hargas_table', 1),
	(37, '2025_08_05_073612_vendor_pembayaran_table', 1),
	(38, '2025_08_05_073658_create_vendor_pembayaran_table', 1),
	(39, '2025_08_05_073740_modify_survei_hargas_remove_payment_columns', 1),
	(40, '2025_08_06_032923_update_pengajuans_budget_columns', 1),
	(41, '2025_08_06_083559_remove_is_final_from_survei_hargas', 1),
	(42, '2025_08_06_083635_add_is_final_to_vendor_pembayaran', 1),
	(43, '2025_08_11_083212_add_bukti_pajak_to_vendor_pembayaran_table', 1),
	(44, '2025_08_13_045354_add_harga_awal_to_revisi_hargas_table', 1),
	(45, '2025_08_15_020338_change_bukti_penyelesaian_to_json_in_vendor_pembayaran_table', 1),
	(46, '2025_08_20_040815_add_approval_timestamps_to_pengajuans_table', 1),
	(47, '2025_08_20_041938_add_it_recommended_at_to_pengajuan_table', 1),
	(48, '2025_08_20_063618_add_ga_surveyed_at_to_pengajuan_table', 1),
	(49, '2025_08_21_022322_kadiv_ops_budget_approved_at', 1),
	(50, '2025_08_21_043223_add_approval_timestamps_to_pengajuans_table', 1),
	(51, '2025_08_21_072256_add_actual_payment_dates_to_vendor_pembayaran_table', 1),
	(52, '2025_09_29_071408_add_kadiv_ops_decision_to_pengajuans_table', 2),
	(53, '2025_12_19_024428_add_rejection_fields_to_pengajuan', 3);

-- Dumping structure for table bprs-procurement.model_has_permissions
CREATE TABLE IF NOT EXISTS `model_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table bprs-procurement.model_has_permissions: ~0 rows (approximately)

-- Dumping structure for table bprs-procurement.model_has_roles
CREATE TABLE IF NOT EXISTS `model_has_roles` (
  `role_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table bprs-procurement.model_has_roles: ~54 rows (approximately)
INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES
	(12, 'App\\Models\\User', 1),
	(1, 'App\\Models\\User', 2),
	(1, 'App\\Models\\User', 3),
	(3, 'App\\Models\\User', 4),
	(1, 'App\\Models\\User', 5),
	(1, 'App\\Models\\User', 6),
	(1, 'App\\Models\\User', 7),
	(2, 'App\\Models\\User', 8),
	(1, 'App\\Models\\User', 9),
	(1, 'App\\Models\\User', 10),
	(1, 'App\\Models\\User', 11),
	(1, 'App\\Models\\User', 12),
	(1, 'App\\Models\\User', 13),
	(4, 'App\\Models\\User', 14),
	(2, 'App\\Models\\User', 15),
	(1, 'App\\Models\\User', 16),
	(1, 'App\\Models\\User', 17),
	(4, 'App\\Models\\User', 18),
	(1, 'App\\Models\\User', 19),
	(10, 'App\\Models\\User', 20),
	(9, 'App\\Models\\User', 21),
	(1, 'App\\Models\\User', 22),
	(1, 'App\\Models\\User', 23),
	(1, 'App\\Models\\User', 24),
	(1, 'App\\Models\\User', 25),
	(1, 'App\\Models\\User', 26),
	(1, 'App\\Models\\User', 27),
	(1, 'App\\Models\\User', 28),
	(6, 'App\\Models\\User', 29),
	(1, 'App\\Models\\User', 30),
	(1, 'App\\Models\\User', 31),
	(1, 'App\\Models\\User', 32),
	(1, 'App\\Models\\User', 33),
	(1, 'App\\Models\\User', 34),
	(3, 'App\\Models\\User', 35),
	(1, 'App\\Models\\User', 36),
	(8, 'App\\Models\\User', 37),
	(1, 'App\\Models\\User', 38),
	(2, 'App\\Models\\User', 39),
	(5, 'App\\Models\\User', 40),
	(4, 'App\\Models\\User', 41),
	(7, 'App\\Models\\User', 42),
	(1, 'App\\Models\\User', 43),
	(1, 'App\\Models\\User', 44),
	(7, 'App\\Models\\User', 44),
	(11, 'App\\Models\\User', 45),
	(1, 'App\\Models\\User', 46),
	(1, 'App\\Models\\User', 47),
	(1, 'App\\Models\\User', 48),
	(7, 'App\\Models\\User', 48),
	(1, 'App\\Models\\User', 49),
	(1, 'App\\Models\\User', 50),
	(2, 'App\\Models\\User', 51),
	(1, 'App\\Models\\User', 52),
	(4, 'App\\Models\\User', 53),
	(4, 'App\\Models\\User', 54);

-- Dumping structure for table bprs-procurement.pengajuans
CREATE TABLE IF NOT EXISTS `pengajuans` (
  `id_pengajuan` bigint unsigned NOT NULL AUTO_INCREMENT,
  `kode_pengajuan` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_user_pemohon` bigint unsigned NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_nilai` decimal(15,2) DEFAULT NULL,
  `catatan_revisi` text COLLATE utf8mb4_unicode_ci,
  `rekomendasi_it_tipe` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rekomendasi_it_catatan` text COLLATE utf8mb4_unicode_ci,
  `status_budget` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `catatan_budget` text COLLATE utf8mb4_unicode_ci,
  `kadiv_ga_decision_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kadiv_ga_catatan` text COLLATE utf8mb4_unicode_ci,
  `manager_approved_by` bigint unsigned DEFAULT NULL,
  `manager_approved_at` timestamp NULL DEFAULT NULL,
  `kadiv_approved_by` bigint unsigned DEFAULT NULL,
  `kadiv_approved_at` timestamp NULL DEFAULT NULL,
  `it_recommended_by` bigint unsigned DEFAULT NULL,
  `it_recommended_at` timestamp NULL DEFAULT NULL,
  `ga_surveyed_by` bigint unsigned DEFAULT NULL,
  `ga_surveyed_at` timestamp NULL DEFAULT NULL,
  `budget_approved_by` bigint unsigned DEFAULT NULL,
  `budget_approved_at` timestamp NULL DEFAULT NULL,
  `kadiv_ops_budget_approved_by` bigint unsigned DEFAULT NULL,
  `kadiv_ops_budget_approved_at` timestamp NULL DEFAULT NULL,
  `kadiv_ga_approved_by` bigint unsigned DEFAULT NULL,
  `kadiv_ga_approved_at` timestamp NULL DEFAULT NULL,
  `direktur_operasional_approved_by` bigint unsigned DEFAULT NULL,
  `direktur_operasional_approved_at` timestamp NULL DEFAULT NULL,
  `direktur_operasional_decision_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `direktur_operasional_catatan` text COLLATE utf8mb4_unicode_ci,
  `kadiv_ops_decision_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kadiv_ops_catatan` text COLLATE utf8mb4_unicode_ci,
  `direktur_utama_approved_by` bigint unsigned DEFAULT NULL,
  `direktur_utama_approved_at` timestamp NULL DEFAULT NULL,
  `direktur_utama_decision_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `direktur_utama_catatan` text COLLATE utf8mb4_unicode_ci,
  `disbursed_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `alasan_penolakan` text COLLATE utf8mb4_unicode_ci,
  `manager_rejected_at` datetime DEFAULT NULL,
  `kadiv_rejected_at` datetime DEFAULT NULL,
  `kadiv_ga_rejected_at` datetime DEFAULT NULL,
  `direktur_operasional_rejected_at` datetime DEFAULT NULL,
  `direktur_utama_rejected_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id_pengajuan`),
  UNIQUE KEY `pengajuans_kode_pengajuan_unique` (`kode_pengajuan`),
  KEY `pengajuans_id_user_pemohon_foreign` (`id_user_pemohon`),
  KEY `pengajuans_manager_approved_by_foreign` (`manager_approved_by`),
  KEY `pengajuans_kadiv_approved_by_foreign` (`kadiv_approved_by`),
  KEY `pengajuans_it_recommended_by_foreign` (`it_recommended_by`),
  KEY `pengajuans_ga_surveyed_by_foreign` (`ga_surveyed_by`),
  KEY `pengajuans_budget_approved_by_foreign` (`budget_approved_by`),
  KEY `pengajuans_kadiv_ga_approved_by_foreign` (`kadiv_ga_approved_by`),
  KEY `pengajuans_direktur_operasional_approved_by_foreign` (`direktur_operasional_approved_by`),
  KEY `pengajuans_direktur_utama_approved_by_foreign` (`direktur_utama_approved_by`),
  KEY `pengajuans_disbursed_by_foreign` (`disbursed_by`),
  KEY `pengajuans_kadiv_ops_budget_approved_by_foreign` (`kadiv_ops_budget_approved_by`),
  CONSTRAINT `pengajuans_budget_approved_by_foreign` FOREIGN KEY (`budget_approved_by`) REFERENCES `users` (`id_user`) ON DELETE SET NULL,
  CONSTRAINT `pengajuans_direktur_operasional_approved_by_foreign` FOREIGN KEY (`direktur_operasional_approved_by`) REFERENCES `users` (`id_user`) ON DELETE SET NULL,
  CONSTRAINT `pengajuans_direktur_utama_approved_by_foreign` FOREIGN KEY (`direktur_utama_approved_by`) REFERENCES `users` (`id_user`) ON DELETE SET NULL,
  CONSTRAINT `pengajuans_disbursed_by_foreign` FOREIGN KEY (`disbursed_by`) REFERENCES `users` (`id_user`) ON DELETE SET NULL,
  CONSTRAINT `pengajuans_ga_surveyed_by_foreign` FOREIGN KEY (`ga_surveyed_by`) REFERENCES `users` (`id_user`) ON DELETE SET NULL,
  CONSTRAINT `pengajuans_id_user_pemohon_foreign` FOREIGN KEY (`id_user_pemohon`) REFERENCES `users` (`id_user`),
  CONSTRAINT `pengajuans_it_recommended_by_foreign` FOREIGN KEY (`it_recommended_by`) REFERENCES `users` (`id_user`) ON DELETE SET NULL,
  CONSTRAINT `pengajuans_kadiv_approved_by_foreign` FOREIGN KEY (`kadiv_approved_by`) REFERENCES `users` (`id_user`) ON DELETE SET NULL,
  CONSTRAINT `pengajuans_kadiv_ga_approved_by_foreign` FOREIGN KEY (`kadiv_ga_approved_by`) REFERENCES `users` (`id_user`) ON DELETE SET NULL,
  CONSTRAINT `pengajuans_kadiv_ops_budget_approved_by_foreign` FOREIGN KEY (`kadiv_ops_budget_approved_by`) REFERENCES `users` (`id_user`),
  CONSTRAINT `pengajuans_manager_approved_by_foreign` FOREIGN KEY (`manager_approved_by`) REFERENCES `users` (`id_user`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table bprs-procurement.pengajuans: ~18 rows (approximately)
INSERT INTO `pengajuans` (`id_pengajuan`, `kode_pengajuan`, `id_user_pemohon`, `status`, `total_nilai`, `catatan_revisi`, `rekomendasi_it_tipe`, `rekomendasi_it_catatan`, `status_budget`, `catatan_budget`, `kadiv_ga_decision_type`, `kadiv_ga_catatan`, `manager_approved_by`, `manager_approved_at`, `kadiv_approved_by`, `kadiv_approved_at`, `it_recommended_by`, `it_recommended_at`, `ga_surveyed_by`, `ga_surveyed_at`, `budget_approved_by`, `budget_approved_at`, `kadiv_ops_budget_approved_by`, `kadiv_ops_budget_approved_at`, `kadiv_ga_approved_by`, `kadiv_ga_approved_at`, `direktur_operasional_approved_by`, `direktur_operasional_approved_at`, `direktur_operasional_decision_type`, `direktur_operasional_catatan`, `kadiv_ops_decision_type`, `kadiv_ops_catatan`, `direktur_utama_approved_by`, `direktur_utama_approved_at`, `direktur_utama_decision_type`, `direktur_utama_catatan`, `disbursed_by`, `created_at`, `updated_at`, `alasan_penolakan`, `manager_rejected_at`, `kadiv_rejected_at`, `kadiv_ga_rejected_at`, `direktur_operasional_rejected_at`, `direktur_utama_rejected_at`) VALUES
	(1, 'REQ/01/C00524/20250919/001', 38, 'Selesai', 29000000.00, 'Catatan Keputusan Kepala Divisi (Kadiv IT, MIS & Product Development)', 'Pembelian Baru', 'Catatan Rekomendasi IT', 'Budget Tersedia', 'Budget Tersedia Catata Budget Control', 'Setuju', 'Keputusan Kadiv GA', NULL, NULL, 40, '2025-09-19 01:23:42', 40, '2025-09-19 01:24:53', 29, '2025-09-19 01:43:55', 42, '2025-09-19 01:50:43', 45, '2025-09-19 01:51:29', 37, '2025-09-19 01:52:07', 21, '2025-09-19 01:52:40', 'Disetujui', 'DIrektur Operasional Catatan', NULL, NULL, NULL, NULL, NULL, NULL, 42, '2025-09-19 00:57:36', '2025-09-19 02:21:27', NULL, NULL, NULL, NULL, NULL, NULL),
	(2, 'REQ/01/C00424/20250923/001', 29, 'Menunggu Pelunasan', 500000.00, 'Coba Barang Non IT Persetujuan Atasan  (Kepala Divisi HR, GA dan Legal)', NULL, NULL, 'Budget Tersedia', 'Coba Catatan Budget  23/09/2025 ', 'Setuju', 'Persetujuan Kepala Divisi GA', NULL, NULL, 37, '2025-09-22 19:11:53', NULL, NULL, 29, '2025-09-22 19:59:29', 42, '2025-09-22 20:00:28', 45, '2025-09-22 20:00:56', 37, '2025-09-22 21:17:05', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-22 19:11:26', '2025-09-22 21:17:05', NULL, NULL, NULL, NULL, NULL, NULL),
	(3, 'REQ/01/C00524/20250924/001', 38, 'Menunggu Pelunasan', 500000.00, 'Approve kepala divisi coba 1 (Kadiv IT, MIS & Product Development)', NULL, NULL, 'Budget Tersedia', 'Catatan Budget Tersedia', 'Setuju', 'Catatan Kadiv GA setelah di revisi', NULL, NULL, 40, '2025-09-23 22:04:26', NULL, NULL, 29, '2025-09-28 21:21:06', 42, '2025-09-28 23:16:58', 45, '2025-09-28 23:50:44', 37, '2025-09-28 21:23:36', NULL, NULL, '', '', 'Setuju', 'Catatan Kadiv Operasional', NULL, NULL, NULL, NULL, NULL, '2025-09-23 20:25:59', '2025-09-28 23:50:44', NULL, NULL, NULL, NULL, NULL, NULL),
	(4, 'REQ/01/C00524/20250924/002', 38, 'Selesai', 10000000.00, 'Approve kepala divisi coba 2 (Kadiv IT, MIS & Product Development)', NULL, NULL, 'Budget Tersedia', 'BUDGET TERSEDIA BUDGET CONTROL', 'Setuju', 'GASSS KADIV GA', NULL, NULL, 40, '2025-09-23 22:04:50', NULL, NULL, 29, '2025-10-05 20:32:48', 42, '2025-10-05 20:50:50', 45, '2025-10-05 21:23:09', 37, '2025-10-05 20:43:55', NULL, NULL, NULL, NULL, 'Setuju', 'CATATAN KEPUTUSAN KEPALA DIVISI OPERASIONAL', NULL, NULL, NULL, NULL, 42, '2025-09-23 20:26:14', '2025-10-08 20:42:57', NULL, NULL, NULL, NULL, NULL, NULL),
	(5, 'REQ/01/C00524/20250924/003', 38, 'Menunggu Persetujuan Kepala Divisi', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-23 22:07:11', '2025-09-23 22:07:11', NULL, NULL, NULL, NULL, NULL, NULL),
	(6, 'REQ/01/C00524/20250924/004', 38, 'Selesai', 15000000.00, 'Keputusan Kepala Divisi Coba 4 (Kadiv IT, MIS & Product Development)', NULL, NULL, 'Budget Tersedia', 'VENDOR COBA 4 BUDGET CONTROL', 'Setuju', 'VENDOR COBA 4 CATATAN KEPALA DIVISI GA', NULL, NULL, 40, '2025-09-24 00:18:30', NULL, NULL, 29, '2025-10-05 22:08:01', 42, '2025-10-05 22:08:44', 45, '2025-10-05 22:09:08', 37, '2025-10-05 22:08:27', 21, '2025-10-06 02:07:13', 'Disetujui', 'CATATA DIREKTUR OPERASIONAL COBA 4', 'Setuju', 'VENDOR COBA 4 KEPALA DIVISI OPERASIONAL', NULL, NULL, NULL, NULL, 42, '2025-09-23 22:07:42', '2025-10-08 20:43:15', NULL, NULL, NULL, NULL, NULL, NULL),
	(7, 'REQ/01/C00524/20250924/005', 38, 'Proses Survei Harga GA', NULL, 'dasdasdas (Kadiv IT, MIS & Product Development)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 40, '2026-02-09 01:47:10', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-23 23:47:09', '2026-02-09 01:47:10', NULL, NULL, NULL, NULL, NULL, NULL),
	(8, 'REQ/01/C00424/20251117/001', 29, 'Menunggu Pencarian Dana', 1000000.00, 'COBA PAJAK INCLUDE KADIV (Kepala Divisi HR, GA dan Legal)', NULL, NULL, 'Budget Tersedia', 'COBA PAJAK INCLUDE (x1)', 'Setuju', 'INCLUDE CATATAN KADIV GA', NULL, NULL, 37, '2025-11-17 00:24:46', NULL, NULL, 29, '2025-11-17 00:28:02', 42, '2025-11-17 00:33:07', 45, '2025-11-17 00:34:00', 37, '2025-11-17 00:31:45', NULL, NULL, NULL, NULL, 'Setuju', 'JOSSS', NULL, NULL, NULL, NULL, NULL, '2025-11-17 00:22:45', '2025-11-17 00:34:00', NULL, NULL, NULL, NULL, NULL, NULL),
	(9, 'REQ/01/C00424/20251117/002', 29, 'Menunggu Pelunasan', 550000.00, 'COBA PAJAK EXCLUDE catatan kadiv (Kepala Divisi HR, GA dan Legal)', 'Pembelian Baru', 'COBA PAJAK EXCLUDE IT', 'Budget Tersedia', 'COBA PAJAK EXCLUDE (x1)', 'Setuju', 'EXCLUDE CATATAN KADIV GA', NULL, NULL, 37, '2025-11-17 00:24:31', 40, '2025-11-17 00:25:54', 29, '2025-11-17 00:30:24', 42, '2025-11-17 00:32:47', 45, '2025-11-17 00:33:51', 37, '2025-11-17 00:31:30', NULL, NULL, NULL, NULL, 'Setuju', 'JOSSS', NULL, NULL, NULL, NULL, NULL, '2025-11-17 00:23:07', '2025-11-17 00:33:51', NULL, NULL, NULL, NULL, NULL, NULL),
	(10, 'REQ/01/C00424/20251121/001', 29, 'Menunggu Persetujuan Budget', NULL, 'gas (Kepala Divisi HR, GA dan Legal)', NULL, NULL, NULL, NULL, 'Setuju', 'gas\n', NULL, NULL, 37, '2025-11-20 18:44:46', NULL, NULL, 29, '2025-11-20 18:45:59', NULL, NULL, NULL, NULL, 37, '2025-11-20 19:16:50', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-20 18:44:01', '2025-11-20 19:16:50', NULL, NULL, NULL, NULL, NULL, NULL),
	(11, 'REQ/01/C00424/20251121/002', 29, 'Menunggu Approval Kadiv GA', NULL, 'asd (Kepala Divisi HR, GA dan Legal)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 37, '2025-11-20 19:17:59', NULL, NULL, 29, '2025-11-20 19:48:15', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-20 19:15:54', '2025-11-20 19:48:15', NULL, NULL, NULL, NULL, NULL, NULL),
	(12, 'REQ/01/C00524/20260209/001', 38, 'Menunggu Rekomendasi IT', NULL, 'vxcvcx (Kadiv IT, MIS & Product Development)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 40, '2026-02-09 01:47:01', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-09 01:45:57', '2026-02-09 01:47:01', NULL, NULL, NULL, NULL, NULL, NULL),
	(13, 'REQ/01/C00524/20260209/002', 38, 'Menunggu Rekomendasi IT', NULL, 'qweqwe (Kadiv IT, MIS & Product Development)', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 40, '2026-02-09 01:46:53', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-09 01:46:17', '2026-02-09 01:46:53', NULL, NULL, NULL, NULL, NULL, NULL),
	(17, 'TEST-DEL/V1vFx', 1, 'Draft', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-12 02:35:54', '2026-02-12 02:35:54', NULL, NULL, NULL, NULL, NULL, NULL),
	(18, 'TEST-DEL/LPfTv', 1, 'Draft', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-12 02:36:09', '2026-02-12 02:36:09', NULL, NULL, NULL, NULL, NULL, NULL),
	(19, 'TEST-DEL/nocCq', 1, 'Draft', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-12 02:36:23', '2026-02-12 02:36:23', NULL, NULL, NULL, NULL, NULL, NULL),
	(21, 'SLS/20260212/DPDIN', 1, 'Selesai', 1533308.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-12 02:38:46', '2026-02-12 02:38:46', NULL, NULL, NULL, NULL, NULL, NULL),
	(22, 'SLS/20260212/G7FGE', 1, 'Selesai', 1773086.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-12 02:38:46', '2026-02-12 02:38:46', NULL, NULL, NULL, NULL, NULL, NULL),
	(23, 'REJ/20260212/ZJZ5Z', 1, 'Ditolak oleh Manager', 1465264.00, 'Ditolak karena anggaran belum tersedia atau alasan teknis.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-12 02:38:47', '2026-02-12 02:38:47', NULL, NULL, NULL, NULL, NULL, NULL),
	(24, 'REJ/20260212/LVIOU', 1, 'Ditolak oleh Direktur Utama', 837190.00, 'Ditolak karena anggaran belum tersedia atau alasan teknis.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-12 02:38:47', '2026-02-12 02:38:47', NULL, NULL, NULL, NULL, NULL, NULL),
	(25, 'REJ/20260212/EOOFC', 1, 'Ditolak oleh Kepala Divisi GA', 1827118.00, 'Ditolak karena anggaran belum tersedia atau alasan teknis.', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-12 02:38:47', '2026-02-12 02:38:47', NULL, NULL, NULL, NULL, NULL, NULL);

-- Dumping structure for table bprs-procurement.pengajuan_items
CREATE TABLE IF NOT EXISTS `pengajuan_items` (
  `id_item` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_pengajuan` bigint unsigned NOT NULL,
  `kategori_barang` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_barang` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kuantitas` int NOT NULL,
  `spesifikasi` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `justifikasi` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `harga_final` decimal(15,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_item`),
  KEY `pengajuan_items_id_pengajuan_foreign` (`id_pengajuan`),
  CONSTRAINT `pengajuan_items_id_pengajuan_foreign` FOREIGN KEY (`id_pengajuan`) REFERENCES `pengajuans` (`id_pengajuan`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table bprs-procurement.pengajuan_items: ~16 rows (approximately)
INSERT INTO `pengajuan_items` (`id_item`, `id_pengajuan`, `kategori_barang`, `nama_barang`, `kuantitas`, `spesifikasi`, `justifikasi`, `harga_final`, `created_at`, `updated_at`) VALUES
	(1, 1, 'Barang IT', 'Laptop Lenovo', 1, 'Spesifikasi\nProcessor : Intel Core i9-14900HX, 24C (8P + 16E) / 32T, P-core 2.2 / 5.8GHz, E-core 1.6 / 4.1GHz, 36MB\nAI PC Category : AI-Powered Gaming PC\nGraphics : NVIDIA GeForce RTX 5060 8GB GDDR7\nChipset : Intel HM770 Chipset\nMemory : 32GB (2x16GB) SO-DIMM DDR5-5600\nStorage : 1TB SSD M.2 2242 PCIe 4.0×4 NVMe\nDisplay : 16″ WQXGA (2560×1600) IPS 500nits Anti-glare, 100% DCI-P3, 240Hz, DisplayHDR 400, Dolby Vision, G-SYNC, Low Blue Light, High Gaming Performance', 'Kebutuhan Staff IT', NULL, '2025-09-19 00:57:36', '2025-09-19 00:57:36'),
	(2, 2, 'Barang Non-IT', 'Coba Barang Non IT', 1, 'Coba Barang Non IT', 'Coba Barang Non IT', NULL, '2025-09-22 19:11:26', '2025-09-22 19:11:26'),
	(3, 3, 'Barang Non-IT', 'Coba 1', 1, 'Coba 1', 'Coba 1', NULL, '2025-09-23 20:25:59', '2025-09-23 20:25:59'),
	(4, 4, 'Barang Non-IT', 'Coba 2', 1, 'Coba 2', 'Coba 2', NULL, '2025-09-23 20:26:14', '2025-09-23 20:26:14'),
	(5, 5, 'Barang Non-IT', 'coba 3', 1, 'coba 3', 'coba 3', NULL, '2025-09-23 22:07:11', '2025-09-23 22:07:11'),
	(6, 6, 'Barang Non-IT', 'coba 4', 1, 'coba 4', 'coba 4', NULL, '2025-09-23 22:07:42', '2025-09-23 22:07:42'),
	(7, 7, 'Barang Non-IT', 'Coba 5', 1, 'Coba 5', 'Coba 5', NULL, '2025-09-23 23:47:09', '2025-09-23 23:47:09'),
	(8, 8, 'Barang Non-IT', 'COBA PAJAK INCLUDE', 1, 'COBA PAJAK INCLUDE', 'COBA PAJAK INCLUDE', NULL, '2025-11-17 00:22:45', '2025-11-17 00:22:45'),
	(9, 9, 'Barang IT', 'COBA PAJAK EXCLUDE', 1, 'COBA PAJAK EXCLUDE', 'COBA PAJAK EXCLUDE', NULL, '2025-11-17 00:23:07', '2025-11-17 00:23:07'),
	(10, 10, 'Barang Non-IT', 'coba revisi barang', 5, 'coba revisi barang', 'coba revisi barang GA', NULL, '2025-11-20 18:44:01', '2025-11-20 18:44:01'),
	(11, 11, 'Barang Non-IT', 'asdfghjkl', 5, 'dawdawd', 'awdawd', NULL, '2025-11-20 19:15:54', '2025-11-20 19:15:54'),
	(12, 12, 'Barang IT', 'dawdaw', 1, 'dawdaw', 'awdaw', NULL, '2026-02-09 01:45:57', '2026-02-09 01:45:57'),
	(13, 13, 'Barang IT', 'bnmbnmbn', 1, 'bnmbnm', 'bnmbnm', NULL, '2026-02-09 01:46:17', '2026-02-09 01:46:17'),
	(17, 21, 'Barang', 'Laptop Asus ROG (Project Selesai 1)', 6, 'Spesifikasi dummy untuk barang selesai', 'Justifikasi dummy', NULL, '2026-02-12 02:38:46', '2026-02-12 02:38:46'),
	(18, 22, 'Barang', 'Kursi Kantor Ergonomis (Project Selesai 2)', 8, 'Spesifikasi dummy untuk barang selesai', 'Justifikasi dummy', NULL, '2026-02-12 02:38:46', '2026-02-12 02:38:46'),
	(19, 23, 'Barang', 'Handphone Marketing (Ditolak Manager)', 1, 'Spesifikasi dummy untuk barang ditolak', 'Urgensi dummy', NULL, '2026-02-12 02:38:47', '2026-02-12 02:38:47'),
	(20, 24, 'Barang', 'PC All-in-One (Ditolak Direktur)', 1, 'Spesifikasi dummy untuk barang ditolak', 'Urgensi dummy', NULL, '2026-02-12 02:38:47', '2026-02-12 02:38:47'),
	(21, 25, 'Barang', 'Printer Inkjet (Ditolak Kadiv GA)', 10, 'Spesifikasi dummy untuk barang ditolak', 'Urgensi dummy', NULL, '2026-02-12 02:38:47', '2026-02-12 02:38:47');

-- Dumping structure for table bprs-procurement.permissions
CREATE TABLE IF NOT EXISTS `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table bprs-procurement.permissions: ~12 rows (approximately)
INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
	(1, 'buat pengajuan', 'web', '2025-09-19 00:15:52', '2025-09-19 00:15:52'),
	(2, 'lihat semua pengajuan', 'web', '2025-09-19 00:15:52', '2025-09-19 00:15:52'),
	(3, 'kelola master data', 'web', '2025-09-19 00:15:52', '2025-09-19 00:15:52'),
	(4, 'approval manager', 'web', '2025-09-19 00:15:52', '2025-09-19 00:15:52'),
	(5, 'approval kadiv', 'web', '2025-09-19 00:15:52', '2025-09-19 00:15:52'),
	(6, 'rekomendasi it', 'web', '2025-09-19 00:15:52', '2025-09-19 00:15:52'),
	(7, 'survei harga ga', 'web', '2025-09-19 00:15:52', '2025-09-19 00:15:52'),
	(8, 'approval budget', 'web', '2025-09-19 00:15:52', '2025-09-19 00:15:52'),
	(9, 'finalisasi keputusan ga', 'web', '2025-09-19 00:15:52', '2025-09-19 00:15:52'),
	(10, 'approval direktur operasional', 'web', '2025-09-19 00:15:52', '2025-09-19 00:15:52'),
	(11, 'approval direktur utama', 'web', '2025-09-19 00:15:52', '2025-09-19 00:15:52'),
	(12, 'pencairan dana', 'web', '2025-09-19 00:15:52', '2025-09-19 00:15:52');

-- Dumping structure for table bprs-procurement.revisi_hargas
CREATE TABLE IF NOT EXISTS `revisi_hargas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `survei_harga_id` bigint unsigned NOT NULL,
  `harga_awal` decimal(15,2) DEFAULT NULL,
  `harga_revisi` decimal(15,2) DEFAULT NULL,
  `opsi_pajak` enum('Pajak Sama','Pajak Berbeda') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pajak Sama',
  `kondisi_pajak` enum('Tidak Ada Pajak','Pajak ditanggung BPRS','Pajak ditanggung Vendor') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jenis_pajak` enum('PPh 21','PPh 23') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `npwp_nik` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nama_pemilik_pajak` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nominal_pajak` decimal(15,2) DEFAULT NULL,
  `alasan_revisi` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `bukti_revisi` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `revisi_budget_status_pengadaan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `revisi_budget_catatan_pengadaan` text COLLATE utf8mb4_unicode_ci,
  `revisi_budget_status_perbaikan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `revisi_budget_catatan_perbaikan` text COLLATE utf8mb4_unicode_ci,
  `tanggal_revisi` timestamp NOT NULL,
  `direvisi_oleh` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `revisi_budget_approved_by` bigint unsigned DEFAULT NULL,
  `revisi_kadiv_ga_decision_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `revisi_kadiv_ga_catatan` text COLLATE utf8mb4_unicode_ci,
  `revisi_direktur_operasional_decision_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `revisi_direktur_operasional_catatan` text COLLATE utf8mb4_unicode_ci,
  `revisi_direktur_operasional_approved_by` bigint unsigned DEFAULT NULL,
  `revisi_direktur_utama_decision_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `revisi_direktur_utama_catatan` text COLLATE utf8mb4_unicode_ci,
  `revisi_direktur_utama_approved_by` bigint unsigned DEFAULT NULL,
  `revisi_kadiv_ga_approved_by` bigint unsigned DEFAULT NULL,
  `revisi_budget_validated_by` bigint unsigned DEFAULT NULL,
  `revisi_budget_validated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `revisi_hargas_survei_harga_id_foreign` (`survei_harga_id`),
  KEY `revisi_hargas_direvisi_oleh_foreign` (`direvisi_oleh`),
  KEY `revisi_hargas_revisi_budget_approved_by_foreign` (`revisi_budget_approved_by`),
  KEY `revisi_hargas_revisi_kadiv_ga_approved_by_foreign` (`revisi_kadiv_ga_approved_by`),
  KEY `revisi_hargas_revisi_budget_validated_by_foreign` (`revisi_budget_validated_by`),
  KEY `revisi_hargas_revisi_direktur_operasional_approved_by_foreign` (`revisi_direktur_operasional_approved_by`),
  KEY `revisi_hargas_revisi_direktur_utama_approved_by_foreign` (`revisi_direktur_utama_approved_by`),
  CONSTRAINT `revisi_hargas_direvisi_oleh_foreign` FOREIGN KEY (`direvisi_oleh`) REFERENCES `users` (`id_user`),
  CONSTRAINT `revisi_hargas_revisi_budget_approved_by_foreign` FOREIGN KEY (`revisi_budget_approved_by`) REFERENCES `users` (`id_user`),
  CONSTRAINT `revisi_hargas_revisi_budget_validated_by_foreign` FOREIGN KEY (`revisi_budget_validated_by`) REFERENCES `users` (`id_user`) ON DELETE SET NULL,
  CONSTRAINT `revisi_hargas_revisi_direktur_operasional_approved_by_foreign` FOREIGN KEY (`revisi_direktur_operasional_approved_by`) REFERENCES `users` (`id_user`) ON DELETE SET NULL,
  CONSTRAINT `revisi_hargas_revisi_direktur_utama_approved_by_foreign` FOREIGN KEY (`revisi_direktur_utama_approved_by`) REFERENCES `users` (`id_user`) ON DELETE SET NULL,
  CONSTRAINT `revisi_hargas_revisi_kadiv_ga_approved_by_foreign` FOREIGN KEY (`revisi_kadiv_ga_approved_by`) REFERENCES `users` (`id_user`),
  CONSTRAINT `revisi_hargas_survei_harga_id_foreign` FOREIGN KEY (`survei_harga_id`) REFERENCES `survei_hargas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table bprs-procurement.revisi_hargas: ~0 rows (approximately)

-- Dumping structure for table bprs-procurement.roles
CREATE TABLE IF NOT EXISTS `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table bprs-procurement.roles: ~12 rows (approximately)
INSERT INTO `roles` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
	(1, 'Staff', 'web', '2025-09-19 00:15:52', '2025-09-19 00:15:52'),
	(2, 'Team Leader', 'web', '2025-09-19 00:15:52', '2025-09-19 00:15:52'),
	(3, 'Manager', 'web', '2025-09-19 00:15:52', '2025-09-19 00:15:52'),
	(4, 'Kepala Divisi', 'web', '2025-09-19 00:15:52', '2025-09-19 00:15:52'),
	(5, 'Kepala Divisi IT', 'web', '2025-09-19 00:15:52', '2025-09-19 00:15:52'),
	(6, 'General Affairs', 'web', '2025-09-19 00:15:52', '2025-09-19 00:15:52'),
	(7, 'Tim Budgeting', 'web', '2025-09-19 00:15:52', '2025-09-19 00:15:52'),
	(8, 'Kepala Divisi GA', 'web', '2025-09-19 00:15:52', '2025-09-19 00:15:52'),
	(9, 'Direktur Operasional', 'web', '2025-09-19 00:15:52', '2025-09-19 00:15:52'),
	(10, 'Direktur Utama', 'web', '2025-09-19 00:15:52', '2025-09-19 00:15:52'),
	(11, 'Kepala Divisi Operasional', 'web', '2025-09-19 00:15:52', '2025-09-19 00:15:52'),
	(12, 'Super Admin', 'web', '2025-09-19 00:15:52', '2025-09-19 00:15:52');

-- Dumping structure for table bprs-procurement.role_has_permissions
CREATE TABLE IF NOT EXISTS `role_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table bprs-procurement.role_has_permissions: ~32 rows (approximately)
INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES
	(1, 1),
	(1, 2),
	(1, 3),
	(4, 3),
	(1, 4),
	(5, 4),
	(1, 5),
	(6, 5),
	(7, 6),
	(8, 7),
	(1, 8),
	(5, 8),
	(9, 8),
	(1, 9),
	(2, 9),
	(10, 9),
	(1, 10),
	(2, 10),
	(11, 10),
	(12, 11),
	(1, 12),
	(2, 12),
	(3, 12),
	(4, 12),
	(5, 12),
	(6, 12),
	(7, 12),
	(8, 12),
	(9, 12),
	(10, 12),
	(11, 12),
	(12, 12);

-- Dumping structure for table bprs-procurement.sessions
CREATE TABLE IF NOT EXISTS `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_foreign` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`),
  CONSTRAINT `sessions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table bprs-procurement.sessions: ~2 rows (approximately)
INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
	('AUgGaYHG93NwEpyfWSO42LjfhTXPHROlVtlzuCyG', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'YTo1OntzOjY6Il90b2tlbiI7czo0MDoiMjhDOTNnNmliNGNVWGVlM3ZOUmlNbzZwZEU1b2RsbUozdDZZcGhBSCI7czozOiJ1cmwiO2E6MDp7fXM6OToiX3ByZXZpb3VzIjthOjE6e3M6MzoidXJsIjtzOjU0OiJodHRwOi8vYnBycy1wcm9jdXJlbWVudC50ZXN0L2FkbWluL2FsbC1wZW5nYWp1YW4tdXNlcnMiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToxO30=', 1770888776),
	('lwsklRYoUEtSeJLSXfTjKsryy4DLsRXlWV6Czw5n', 29, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoialNqaHpFSkNUTzZLTThlUlBDTHVNRUFpNkMyWDBLd3UzMWVyUThSWSI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6NTI6Imh0dHA6Ly9icHJzLXByb2N1cmVtZW50LnRlc3QvYWRtaW4vcGVuZ2FqdWFuLWRpdG9sYWsiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjUwOiJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToyOTt9', 1770889160);

-- Dumping structure for table bprs-procurement.survei_hargas
CREATE TABLE IF NOT EXISTS `survei_hargas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_item` bigint unsigned NOT NULL,
  `tipe_survei` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_vendor` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `harga` decimal(15,2) NOT NULL,
  `rincian_harga` text COLLATE utf8mb4_unicode_ci,
  `kondisi_pajak` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Tidak Ada Pajak',
  `jenis_pajak` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `npwp_nik` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nama_pemilik_pajak` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nominal_pajak` decimal(15,2) DEFAULT NULL,
  `bukti_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `survei_hargas_id_item_foreign` (`id_item`),
  CONSTRAINT `survei_hargas_id_item_foreign` FOREIGN KEY (`id_item`) REFERENCES `pengajuan_items` (`id_item`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table bprs-procurement.survei_hargas: ~11 rows (approximately)
INSERT INTO `survei_hargas` (`id`, `id_item`, `tipe_survei`, `nama_vendor`, `harga`, `rincian_harga`, `kondisi_pajak`, `jenis_pajak`, `npwp_nik`, `nama_pemilik_pajak`, `nominal_pajak`, `bukti_path`, `created_at`, `updated_at`) VALUES
	(5, 1, '2a. Komputer & Hardware Sistem Informasi', 'COBA VENDOR 1', 29000000.00, 'DETAIL RINCIAN VENDOR 1', 'Tidak Ada Pajak', NULL, NULL, NULL, 0.00, NULL, '2025-09-19 01:43:55', '2025-09-19 01:43:55'),
	(6, 1, '2a. Komputer & Hardware Sistem Informasi', 'DETAIL RINCIAN VENDOR 2', 30000000.00, 'DETAIL RINCIAN VENDOR 2', 'Tidak Ada Pajak', NULL, NULL, NULL, 0.00, NULL, '2025-09-19 01:43:55', '2025-09-19 01:43:55'),
	(7, 1, '2a. Komputer & Hardware Sistem Informasi', 'COBA VENDOR 3', 50000000.00, 'DETAIL RINCIAN VENDOR 3', 'Tidak Ada Pajak', NULL, NULL, NULL, 0.00, NULL, '2025-09-19 01:43:55', '2025-09-19 01:43:55'),
	(8, 2, '2b. Peralatan atau Mesin Kantor', 'Coba Barang Non IT', 500000.00, 'Coba Barang Non IT Detail Rincian', 'Tidak Ada Pajak', NULL, NULL, NULL, 0.00, 'REQ_01_C00424_20250923_001/REQ_01_C00424_20250923_001_bukti_survei__{timestamp}_NSFme.png', '2025-09-22 19:59:28', '2025-09-22 19:59:28'),
	(9, 3, '2c. Kendaraan Bermotor', 'Coba Vendor 1 ', 500000.00, 'Detail Rincian Vendor 1', 'Tidak Ada Pajak', NULL, NULL, NULL, 0.00, 'REQ_01_C00524_20250924_001/REQ_01_C00524_20250924_001_bukti_survei__{timestamp}_QO0bs.jfif', '2025-09-28 21:21:06', '2025-09-28 21:21:06'),
	(10, 4, '2d. Perlengkapan Kantor Lainnya', 'VENDOR COBA 2', 10000000.00, 'VENDOR COBA 2 DETAIL', 'Tidak Ada Pajak', NULL, NULL, NULL, 0.00, 'REQ_01_C00524_20250924_002/REQ_01_C00524_20250924_002_bukti_survei__{timestamp}_WhwQ7.jfif', '2025-10-05 20:32:48', '2025-10-05 20:32:48'),
	(11, 6, '2d. Perlengkapan Kantor Lainnya', 'VENDOR COBA 4', 15000000.00, 'VENDOR COBA 4 DETAIL RINCIAN', 'Tidak Ada Pajak', NULL, NULL, NULL, 0.00, 'REQ_01_C00524_20250924_004/REQ_01_C00524_20250924_004_bukti_survei__{timestamp}_d52VE.jfif', '2025-10-05 22:08:01', '2025-10-05 22:08:01'),
	(12, 8, '1d. Lainnya (Aktiva Tidak Berwujud)', 'COBA PAJAK INCLUDE', 1000000.00, 'COBA PAJAK INCLUDE', 'Pajak ditanggung Vendor (Include)', 'PPh 21', '2141561464', 'MUHAMMAD ILHAM', 500000.00, 'REQ_01_C00424_20251117_001/REQ_01_C00424_20251117_001_bukti_survei__{timestamp}_l7rN1.pdf', '2025-11-17 00:28:02', '2025-11-17 00:28:02'),
	(13, 9, '2c. Kendaraan Bermotor', 'COBA PAJAK EXCLUDE', 520000.00, 'COBA PAJAK EXCLUDE', 'Pajak ditanggung Perusahaan (Exclude)', 'PPh 23', '234234234', 'ILHAM PRATAMA', 30000.00, 'REQ_01_C00424_20251117_002/REQ_01_C00424_20251117_002_bukti_survei__{timestamp}_RiXUp.pdf', '2025-11-17 00:30:24', '2025-11-17 00:30:24'),
	(14, 10, '2b. Peralatan atau Mesin Kantor', 'C00424', 1000000.00, 'C00424C00424C00424C00424C00424C00424C00424', 'Tidak Ada Pajak', NULL, NULL, NULL, 0.00, 'REQ_01_C00424_20251121_001/REQ_01_C00424_20251121_001_bukti_survei__{timestamp}_K2TFd.jpg', '2025-11-20 18:45:59', '2025-11-20 18:45:59'),
	(17, 11, '2e. Lainnya (Aktiva Berwujud)', 'awdawd', 1000000.00, 'awdawdawd', 'Tidak Ada Pajak', NULL, NULL, NULL, 0.00, 'REQ_01_C00424_20251121_002/REQ_01_C00424_20251121_002_bukti_survei__{timestamp}_rDkKc.pdf', '2025-11-20 19:48:15', '2025-11-20 19:48:15');

-- Dumping structure for table bprs-procurement.users
CREATE TABLE IF NOT EXISTS `users` (
  `id_user` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nama_user` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nik_user` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_kantor` bigint unsigned DEFAULT NULL,
  `id_divisi` bigint unsigned DEFAULT NULL,
  `id_jabatan` bigint unsigned DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_user`),
  UNIQUE KEY `users_nik_user_unique` (`nik_user`),
  KEY `users_id_kantor_foreign` (`id_kantor`),
  KEY `users_id_divisi_foreign` (`id_divisi`),
  KEY `users_id_jabatan_foreign` (`id_jabatan`),
  CONSTRAINT `users_id_divisi_foreign` FOREIGN KEY (`id_divisi`) REFERENCES `divisis` (`id_divisi`) ON DELETE SET NULL,
  CONSTRAINT `users_id_jabatan_foreign` FOREIGN KEY (`id_jabatan`) REFERENCES `jabatans` (`id_jabatan`) ON DELETE SET NULL,
  CONSTRAINT `users_id_kantor_foreign` FOREIGN KEY (`id_kantor`) REFERENCES `kantors` (`id_kantor`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table bprs-procurement.users: ~54 rows (approximately)
INSERT INTO `users` (`id_user`, `nama_user`, `nik_user`, `password`, `id_kantor`, `id_divisi`, `id_jabatan`, `remember_token`, `created_at`, `updated_at`) VALUES
	(1, 'Super Admin', 'superadmin', '$2y$12$BfwsOdG2X53MgjgT9LPYNuTMofeN7mHGuSsvG7aMF9Cj2lbB3Dg72', 1, 5, 26, NULL, '2026-02-09 01:45:16', '2026-02-09 01:45:16'),
	(2, 'Ahmad Mustofa Salim', 'C00125', '$2y$12$xw/WGzO.zaNVz1zWXL41Duic6m3y6wm9l90ArnQtzQjpfHfaoOTB.', 1, 1, 1, NULL, '2026-02-09 01:45:16', '2026-02-09 01:45:16'),
	(3, 'Dwi Ari Zufriyani', '431891', '$2y$12$uQqYXbFsYh9mIuHL6Nx75.KGK5QpHJo3lYtt22oTegVb/rs0o1ZbS', 1, 1, 2, NULL, '2026-02-09 01:45:16', '2026-02-09 01:45:16'),
	(4, 'Setio Ariyanto', '451881', '$2y$12$fgjVrhMYAQoo54UNY2bWAOP2dG0U0dmObG5ctqeAlMvpctLOJu6b.', 1, 1, 3, NULL, '2026-02-09 01:45:16', '2026-02-09 01:45:16'),
	(5, 'Nuryasin', 'C00324', '$2y$12$2s7oyAcqCLttlKsJnyN9C...whvjBuxkT9usjn8t5ObqBsSkLyvSO', 1, 1, 1, NULL, '2026-02-09 01:45:17', '2026-02-09 01:45:17'),
	(6, 'Hani Budi Lestari', 'C00124', '$2y$12$vdguYGskqQIAVRFWPjPxfOOxzhCaPXp09A3tdN4ftISaniqolbqhK', 1, 1, 1, NULL, '2026-02-09 01:45:17', '2026-02-09 01:45:17'),
	(7, 'Muchammad Erwin Setyawan', '120877', '$2y$12$dV5d6jZ87LUgWuHUvVvrlejGr3V3vkEcSymjgf/UcLK4u0x13HuV6', 1, 1, 4, NULL, '2026-02-09 01:45:17', '2026-02-09 01:45:17'),
	(8, 'Anna Maria Sandri', '501974', '$2y$12$gsmUpvVTGJp5qOJ9Lkeb/.YlniopP9EWpyQhn9/lJNq2cXhkc6cwO', 1, 1, 5, NULL, '2026-02-09 01:45:17', '2026-02-09 01:45:17'),
	(9, 'Ardi Alamsyah Sutedja', '872193', '$2y$12$mIDqpBTSHS4MVSulyffuB.TICsdhIyAlzy3xPYusZ8RZ0TbrG1dyy', 1, 1, 1, NULL, '2026-02-09 01:45:17', '2026-02-09 01:45:17'),
	(10, 'Imam Suwandi', '982297', '$2y$12$I8JgtefczMsEbtGOVspP1edvRLxBi9ct8p/rS5lhjiGiQ8xGhXWUO', 1, 1, 1, NULL, '2026-02-09 01:45:18', '2026-02-09 01:45:18'),
	(11, 'Inneke Febrihardianti Syamsi', '72397', '$2y$12$DAN9QbrwPIS0aFq0GbDGG.qWZyPq4t.N/kMaH6opPTKqjmU.IiqE6', 1, 1, 6, NULL, '2026-02-09 01:45:18', '2026-02-09 01:45:18'),
	(12, 'Hafizon Ramadhan', '62396', '$2y$12$cJHiq0v8CFEfqjGo4JLKFeJ9UkatXdq2rd0AvqRh3lmgYUsE2IVha', 1, 1, 2, NULL, '2026-02-09 01:45:18', '2026-02-09 01:45:18'),
	(13, 'Belinda Paramasiddha Jannah', '812195', '$2y$12$NoO8WmyffQGQXwtqZ9fDZ.pkHvBuiuQhdoE8mRw53aL8Pbn2YIQGe', 1, 1, 4, NULL, '2026-02-09 01:45:18', '2026-02-09 01:45:18'),
	(14, 'Faradays Muhammad', '42383', '$2y$12$oL9mcGcVEJa6k7jTnMdElOJUha18fioZkUNX0YuFs0Y4F65SHBpiS', 1, 1, 7, NULL, '2026-02-09 01:45:19', '2026-02-09 01:45:19'),
	(15, 'Andris Arisyandi', '732079', '$2y$12$VIZrYiwZPpWQFqAwonSlJ./NTVh/uG03D/Ao93qQR7t9yrxtr18ae', 1, 1, 8, NULL, '2026-02-09 01:45:19', '2026-02-09 01:45:19'),
	(16, 'Tariman', '922295', '$2y$12$eceb/A73z2nQ90JAScKqReqQ9ry5NG8IDjMG93EbJpEMH6B5ugoEW', 1, 1, 2, NULL, '2026-02-09 01:45:19', '2026-02-09 01:45:19'),
	(17, 'Syafiqunnur', '621995', '$2y$12$uyIEkASuyvBRR35j8vRF7.oyVz.C2nHXB3O1YxPh6XQPWLW8YuHkW', 1, 2, 9, NULL, '2026-02-09 01:45:19', '2026-02-09 01:45:19'),
	(18, 'Fitri Rini Farida', '511978', '$2y$12$SiDWHXc12/FuG79MzSIvCOoxzAgPztFsPVLNLhr0j03mPr6MkghBi', 1, 2, 10, NULL, '2026-02-09 01:45:19', '2026-02-09 01:45:19'),
	(19, 'Pudra Fanki Amrillah', '32393', '$2y$12$0jQk2n7TaALjVEO7ElSQ.ORgUT3U8cOkbIlHYllbx5MhmlbWL/Jya', 1, 2, 11, NULL, '2026-02-09 01:45:20', '2026-02-09 01:45:20'),
	(20, 'Kholid', '882180', '$2y$12$KRg2Z44nWeTvaTO/W.UOH.w9bHOBEyshzfNxyaTJo0o3Napvp3WVW', 1, 3, 12, NULL, '2026-02-09 01:45:20', '2026-02-09 01:45:20'),
	(21, 'Mushoniful Agustian', '561974', '$2y$12$W94Jd6yGohUunx5Grdaw2.CpyksJw5iMCWiUJencpjPvAlNfNJwMC', 1, 3, 13, NULL, '2026-02-09 01:45:20', '2026-02-09 01:45:20'),
	(22, 'Afif Ridho Rahmanto', '932298', '$2y$12$Ors3jrWrlYVnZV14TM8R4evtyDVTDT/HmdfmlDbtXl1La.qTw5/zK', 1, 4, 14, NULL, '2026-02-09 01:45:20', '2026-02-09 01:45:20'),
	(23, 'Catur Eko Wahyono', '942295', '$2y$12$uCKShvGQdWQFb5bUt8vOHOU0jxEfTvazKelTKOxk/wrGZZTPOPsye', 1, 4, 15, NULL, '2026-02-09 01:45:20', '2026-02-09 01:45:20'),
	(24, 'Winda Salsabilla Kris Daldiri', '902291', '$2y$12$rtnyEFn946rcj9gBZftfpeP6YsEXE3Z92bLclO0rbjgdZ9ethW206', 1, 4, 16, NULL, '2026-02-09 01:45:21', '2026-02-09 01:45:21'),
	(25, 'Amrozi Arya Bima', '892196', '$2y$12$hp89/bDvSd5x6.JdRj0z.eE/wYVMZ7VQNJlYYP66C7AvBPzHMmJhS', 1, 4, 17, NULL, '2026-02-09 01:45:21', '2026-02-09 01:45:21'),
	(26, 'Christianto Bambang Suwarjo', '181592', '$2y$12$dc0bmJqHRigt0O4.4RRNlOCYjOjdabPnUjTQjTkck4GJZZJ22p6e.', 1, 4, 18, NULL, '2026-02-09 01:45:21', '2026-02-09 01:45:21'),
	(27, 'Rinto Widodo', '972291', '$2y$12$Ru44yAQJZxkQT6Wa6D7.LeDJJy4tm1FeSN5qfjZYdFotTK2/NGk4G', 1, 4, 15, NULL, '2026-02-09 01:45:21', '2026-02-09 01:45:21'),
	(28, 'Erwanto', '201685', '$2y$12$7ybUOS36C9LeFztZPFs0YuV5k2GLkhWMSWiZWSqi/Ik81ijE3rBe2', 1, 4, 17, NULL, '2026-02-09 01:45:22', '2026-02-09 01:45:22'),
	(29, 'Achmad Syihab Arya Satya', 'C00424', '$2y$12$bj8.8xPMh186dcZjO32QEuJdJY.tXWgbvzczwIQ5vPKb5Vr26nNc2', 1, 4, 19, NULL, '2026-02-09 01:45:22', '2026-02-09 01:45:22'),
	(30, 'Muhammad Sanhaji', 'C00823', '$2y$12$.tjd02YwtwACfXqkxnwt4u1bry51HEmrh6MYUxAI5wKZdyEHhIX9O', 1, 4, 20, NULL, '2026-02-09 01:45:22', '2026-02-09 01:45:22'),
	(31, 'Untung Sugana', '571977', '$2y$12$qQUpnVhJcwmyEdNFpDdRZe99zQImrouTOvxIBAVOEZaZ2Pryp63uW', 1, 4, 17, NULL, '2026-02-09 01:45:22', '2026-02-09 01:45:22'),
	(32, 'Pandu Fitri Andika', '772085', '$2y$12$AYcuzRnqFSjrsgA2Qdq9q.8OWw5SxH3V2CJgmftxye8rPGWVfHa/u', 1, 4, 17, NULL, '2026-02-09 01:45:22', '2026-02-09 01:45:22'),
	(33, 'Sugeng Setyanto', '22590', '$2y$12$AeW1wfhpEO/Mgom380eYWe.CONwZ87nwEbfjld253kzBn2lfgx9Ta', 1, 4, 15, NULL, '2026-02-09 01:45:23', '2026-02-09 01:45:23'),
	(34, 'Budi Pramana', '601980', '$2y$12$GLfGLAAEsTK.FuinADGWgu9ftjVRys1LFj3UbhfkNSJl6Kp5ENtZG', 1, 4, 15, NULL, '2026-02-09 01:45:23', '2026-02-09 01:45:23'),
	(35, 'Arryanto Hendratama', '692078', '$2y$12$XFw8GxOjeiFUUH30Enjgz.QaatrqB1AaiL/rakZXWpxSh93LWLLP.', 1, 4, 21, NULL, '2026-02-09 01:45:23', '2026-02-09 01:45:23'),
	(36, 'Satya Puguh Toh Jiwo', '682089', '$2y$12$H9TKjJxeZLQRYHafPQmgu.e2y4tdZwKU1/yhXi7oG6t8uz2hcaQAG', 1, 4, 18, NULL, '2026-02-09 01:45:23', '2026-02-09 01:45:23'),
	(37, 'Teddy Sutrisna', '411881', '$2y$12$gtRkYNn72C56lhOtL1ReuOo26EirNtBlvyOsF2mb/con2wThdBq7C', 1, 4, 22, NULL, '2026-02-09 01:45:23', '2026-02-09 01:45:23'),
	(38, 'Muhammad Ilham Pratama', 'C00524', '$2y$12$sWHovG9QcY7Hc.fIR0KkeODu1PhgWfxptoQHAmey4J4tRwYQKQCTe', 1, 5, 23, NULL, '2026-02-09 01:45:24', '2026-02-09 01:45:24'),
	(39, 'Alfian Akbar Prasetya', '551996', '$2y$12$yBFb9LG3ib8fKCJj.B24p.C0aQZa.ZzOPUzuIYR94A0nYKQ4vh0ky', 1, 5, 24, NULL, '2026-02-09 01:45:24', '2026-02-09 01:45:24'),
	(40, 'Prabawa Rahmat Ismail', '251785', '$2y$12$OD2BVdfSLlcTA7Kh3e1fmOb8Gb096oi16dkubFDBh9ijV01MgGGUW', 1, 5, 25, NULL, '2026-02-09 01:45:24', '2026-02-09 01:45:24'),
	(41, 'Chandra Widya Mahardika', '82490', '$2y$12$yEztq9K1k0dBC5yuEvZs9OHszBVGGNJRQhpQA64WTdTrgB0ocmlvO', 1, 6, 27, NULL, '2026-02-09 01:45:24', '2026-02-09 01:45:24'),
	(42, 'Eis Kristina Yusanti', '171676', '$2y$12$hE6BwjzW.df9qhbg7fTYEOR0dz.KdfwvTLTNP7Imt45CZ9Srka5g.', 1, 7, 28, NULL, '2026-02-09 01:45:25', '2026-02-09 01:45:25'),
	(43, 'Rianita Indah Dwi Arum', '381894', '$2y$12$IDwQiSn1QJNHIFRBmIq.luUFqW1zGssmaGWS7.lc/m/OgZDbF5TkC', 1, 7, 29, NULL, '2026-02-09 01:45:25', '2026-02-09 01:45:25'),
	(44, 'Jihan Satya Meinisa', '531996', '$2y$12$GBXd.2UA1nw0FcHmyJdQ5OeMiGQOI5716GHM0fB08KIHO2XKGYWke', 1, 7, 30, NULL, '2026-02-09 01:45:25', '2026-02-09 01:45:25'),
	(45, 'Nurten Novita Sari', '70981', '$2y$12$ExbUT0o2jgC.DquAkdI4k.NGYVzfQGjhvJiifMyfJMYlgZqaYX9OK', 1, 7, 31, NULL, '2026-02-09 01:45:25', '2026-02-09 01:45:25'),
	(46, 'Pratiwi Budi Setyaningsih', '12592', '$2y$12$5KObXvDn7tWUZcdZC5kY/.D9joUchcpYbg15VBhurFm5qjJB5J9hW', 1, 7, 28, NULL, '2026-02-09 01:45:25', '2026-02-09 01:45:25'),
	(47, 'Nadhofa Aulia Nur Arifien', '842196', '$2y$12$myq5LG224ojFIzR8NPf0z.16Ou.mhuR4Cvhe/JUpm44H3RoxjyLT.', 1, 7, 32, NULL, '2026-02-09 01:45:26', '2026-02-09 01:45:26'),
	(48, 'Partiyah', '161596', '$2y$12$NlGeBQ.LI3fpPLMSwIWn8.7NyGWQvBvaqg1R72IbwY514WWUYufKy', 1, 7, 33, NULL, '2026-02-09 01:45:26', '2026-02-09 01:45:26'),
	(49, 'Imam Muhajirin', '661993', '$2y$12$.jZFV5aNN1x5IMsI/09aruhd9duH2Zi6HdcYpZxh..m7WhJGUcl.m', 1, 7, 29, NULL, '2026-02-09 01:45:26', '2026-02-09 01:45:26'),
	(50, 'Heriyanto', '361876', '$2y$12$LFARj7t/Yw6KA/AcQF39seQfyqle/2.M0z1qdQd8m1esqbJxUC.6a', 1, 8, 35, NULL, '2026-02-09 01:45:26', '2026-02-09 01:45:26'),
	(51, 'Fahrul Hijar', '611980', '$2y$12$.94lK4toa4ooFiLpLDiVve270TjYBGwsV5sUfEJibjm/2sd2mqsGm', 1, 8, 36, NULL, '2026-02-09 01:45:26', '2026-02-09 01:45:26'),
	(52, 'Rio Dewangga', '952291', '$2y$12$RHuX9udtp10qS7YVUc.FKujJNLAVQhpMzN0f7s9n8YamsSDWmSehW', 1, 9, 37, NULL, '2026-02-09 01:45:27', '2026-02-09 01:45:27'),
	(53, 'Sesilia Lilies Andriani', '802174', '$2y$12$IS0vRt3hss1oD/AYrZQvZOUxtbRHQcUYJeu3mjsUZptJLs.8sRZI6', 1, 9, 38, NULL, '2026-02-09 01:45:27', '2026-02-09 01:45:27'),
	(54, 'Winasista Salarina', '2292', '$2y$12$kDN4dNA5OJCGQmN9CapOwufueFR/5hqioVHD5DYPiFDX7Uo/U9GEK', 1, 10, 39, NULL, '2026-02-09 01:45:27', '2026-02-09 01:45:27');

-- Dumping structure for table bprs-procurement.vendor_pembayaran
CREATE TABLE IF NOT EXISTS `vendor_pembayaran` (
  `id_pembayaran` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_pengajuan` bigint unsigned NOT NULL,
  `nama_vendor` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `metode_pembayaran` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `opsi_pembayaran` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nominal_dp` decimal(15,2) DEFAULT '0.00',
  `is_final` tinyint(1) NOT NULL DEFAULT '0',
  `tanggal_dp` date DEFAULT NULL,
  `tanggal_dp_aktual` date DEFAULT NULL,
  `tanggal_pelunasan` date DEFAULT NULL,
  `tanggal_pelunasan_aktual` date DEFAULT NULL,
  `nama_rekening` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `no_rekening` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nama_bank` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bukti_dp` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bukti_pelunasan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bukti_pajak` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bukti_penyelesaian` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_pembayaran`),
  UNIQUE KEY `unique_pengajuan_vendor` (`id_pengajuan`,`nama_vendor`),
  CONSTRAINT `vendor_pembayaran_id_pengajuan_foreign` FOREIGN KEY (`id_pengajuan`) REFERENCES `pengajuans` (`id_pengajuan`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table bprs-procurement.vendor_pembayaran: ~11 rows (approximately)
INSERT INTO `vendor_pembayaran` (`id_pembayaran`, `id_pengajuan`, `nama_vendor`, `metode_pembayaran`, `opsi_pembayaran`, `nominal_dp`, `is_final`, `tanggal_dp`, `tanggal_dp_aktual`, `tanggal_pelunasan`, `tanggal_pelunasan_aktual`, `nama_rekening`, `no_rekening`, `nama_bank`, `bukti_dp`, `bukti_pelunasan`, `bukti_pajak`, `bukti_penyelesaian`, `created_at`, `updated_at`) VALUES
	(7, 1, 'COBA VENDOR 1', 'Transfer', 'Bisa DP', 9000000.00, 1, '2025-09-19', '2025-09-19', '2025-09-22', '2025-09-19', 'NAMA VENDOR 1', '789456789', 'NAMA BANK VENDOR 1', 'REQ_01_C00524_20250919_001/REQ_01_C00524_20250919_001_bukti_dp_{timestamp}_R8Vh5.jpg', 'REQ_01_C00524_20250919_001/REQ_01_C00524_20250919_001_bukti_pelunasan_{timestamp}_OgBkW.jpg', NULL, '[{"file_path": "REQ_01_C00524_20250919_001/REQ_01_C00524_20250919_001_bukti_penyelesaian_{timestamp}_IBF5C.jpg"}]', '2025-09-19 01:43:55', '2025-09-19 02:21:27'),
	(8, 1, 'DETAIL RINCIAN VENDOR 2', 'Tunai', 'Langsung Lunas', 0.00, 0, NULL, NULL, '2025-09-19', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-19 01:43:55', '2025-09-19 01:52:07'),
	(9, 1, 'COBA VENDOR 3', 'Tunai', 'Langsung Lunas', 0.00, 0, NULL, NULL, '2025-09-19', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-19 01:43:55', '2025-09-19 01:52:07'),
	(10, 2, 'Coba Barang Non IT', 'Tunai', 'Langsung Lunas', 0.00, 1, NULL, NULL, '2025-09-23', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-22 19:59:28', '2025-09-22 21:17:05'),
	(11, 3, 'Coba Vendor 1 ', 'Tunai', 'Langsung Lunas', 0.00, 1, NULL, NULL, '2025-09-29', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-28 21:21:06', '2025-09-28 23:50:44'),
	(12, 4, 'VENDOR COBA 2', 'Transfer', 'Bisa DP', 3000000.00, 1, '2025-10-06', '2025-10-09', '2025-10-10', '2025-10-09', 'ILHAM', '0601010005', 'BCA', 'REQ_01_C00524_20250924_002/REQ_01_C00524_20250924_002_bukti_dp_{timestamp}_cEsMb.jfif', 'REQ_01_C00524_20250924_002/REQ_01_C00524_20250924_002_bukti_pelunasan_{timestamp}_sn3Co.jfif', NULL, '[{"file_path": "REQ_01_C00524_20250924_002/REQ_01_C00524_20250924_002_bukti_penyelesaian_{timestamp}_nLPrm.jfif"}]', '2025-10-05 20:32:48', '2025-10-08 20:42:57'),
	(13, 6, 'VENDOR COBA 4', 'Transfer', 'Bisa DP', 5000000.00, 1, '2025-10-06', '2025-10-09', '2025-10-17', '2025-10-09', 'ILHAM GANTENG', '06010100000005', 'BCA', 'REQ_01_C00524_20250924_004/REQ_01_C00524_20250924_004_bukti_dp_{timestamp}_NNeD7.jfif', 'REQ_01_C00524_20250924_004/REQ_01_C00524_20250924_004_bukti_pelunasan_{timestamp}_0fQak.jfif', NULL, '[{"file_path": "REQ_01_C00524_20250924_004/REQ_01_C00524_20250924_004_bukti_penyelesaian_{timestamp}_BBSG3.jfif"}]', '2025-10-05 22:08:01', '2025-10-08 20:43:15'),
	(14, 8, 'COBA PAJAK INCLUDE', 'Transfer', 'Bisa DP', 200000.00, 1, '2025-11-17', NULL, '2025-11-13', NULL, 'MUHAMMAD ILHAM', '1234567890', 'BCA', NULL, NULL, NULL, NULL, '2025-11-17 00:28:02', '2025-11-17 00:34:00'),
	(15, 9, 'COBA PAJAK EXCLUDE', 'Transfer', 'Langsung Lunas', 0.00, 1, NULL, NULL, '2025-11-17', NULL, 'ILHAM PRATAMA', '65465465416541', 'BCA', NULL, NULL, NULL, NULL, '2025-11-17 00:30:24', '2025-11-17 00:33:51'),
	(16, 10, 'C00424', 'Tunai', 'Langsung Lunas', 0.00, 1, NULL, NULL, '2025-11-21', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-20 18:45:59', '2025-11-20 18:45:59'),
	(19, 11, 'awdawd', 'Tunai', 'Bisa DP', 3000000.00, 1, '2025-11-21', NULL, '2025-11-21', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-20 19:48:15', '2025-11-20 19:48:15');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
