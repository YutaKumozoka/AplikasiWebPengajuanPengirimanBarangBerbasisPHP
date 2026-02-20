-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: pengiriman_db
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `barang`
--

DROP TABLE IF EXISTS `barang`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `barang` (
  `id` int(11) NOT NULL,
  `kode_barang` varchar(50) NOT NULL,
  `nama_barang` varchar(100) NOT NULL,
  `harga` decimal(10,2) NOT NULL,
  `stok` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `jenis_barang` varchar(50) DEFAULT 'Umum'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `barang`
--

LOCK TABLES `barang` WRITE;
/*!40000 ALTER TABLE `barang` DISABLE KEYS */;
INSERT INTO `barang` VALUES (1,'BRG001','Laptop ASUS X441',7500000.00,75,'0000-00-00 00:00:00','Umum'),(2,'BRG002','Mouse Wireless Logitech',250000.00,28,'2025-12-15 01:30:00','Umum'),(3,'BRG003','Keyboard Mechanical RGB',850000.00,25,'2025-12-15 01:30:00','Umum'),(4,'BRG004','Monitor 24 inch LG',1800000.00,1,'2025-12-15 01:30:00','Umum'),(5,'BRG005','Printer Epson L3150',2300000.00,10,'2025-12-15 01:30:00','Umum'),(6,'BRG006','Harddisk External 1TB',850000.00,10,'2025-12-15 01:30:00','Umum'),(7,'BRG007','Webcam HD 1080p',350000.00,30,'2025-12-15 01:30:00','Umum'),(8,'BRG008','Speaker Bluetooth JBL',1200000.00,20,'0000-00-00 00:00:00','Umum'),(9,'BRG009','Router TP-Link Archer',550000.00,19,'0000-00-00 00:00:00','Umum'),(10,'BRG010','Tablet Samsung Galaxy',3500000.00,15,'2025-12-15 01:30:00','Umum');
/*!40000 ALTER TABLE `barang` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `detail_penjualan`
--

DROP TABLE IF EXISTS `detail_penjualan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `detail_penjualan` (
  `id` int(11) NOT NULL,
  `penjualan_id` int(11) NOT NULL,
  `barang_id` int(11) NOT NULL,
  `qty` int(11) NOT NULL,
  `harga` decimal(10,2) NOT NULL,
  `subtotal` decimal(15,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detail_penjualan`
--

LOCK TABLES `detail_penjualan` WRITE;
/*!40000 ALTER TABLE `detail_penjualan` DISABLE KEYS */;
INSERT INTO `detail_penjualan` VALUES (0,1,1,3,7500000.00,22500000.00,'2026-01-26 16:24:58'),(0,1,2,5,250000.00,1250000.00,'2026-01-26 16:24:58'),(0,1,3,2,850000.00,1700000.00,'2026-01-26 16:24:58'),(0,1,8,1,1200000.00,1200000.00,'2026-01-26 16:24:58'),(0,2,4,3,1800000.00,5400000.00,'2026-01-26 16:24:58'),(0,2,5,1,2300000.00,2300000.00,'2026-01-26 16:24:58'),(0,2,7,2,350000.00,700000.00,'2026-01-26 16:24:58'),(0,3,1,1,7500000.00,7500000.00,'2026-01-26 16:24:58'),(0,3,2,1,250000.00,250000.00,'2026-01-26 16:24:58'),(0,3,3,1,850000.00,850000.00,'2026-01-26 16:24:58'),(0,4,6,10,850000.00,8500000.00,'2026-01-26 16:24:58'),(0,4,9,5,550000.00,2750000.00,'2026-01-26 16:24:58'),(0,4,10,2,3500000.00,7000000.00,'2026-01-26 16:24:58'),(0,4,7,3,350000.00,1050000.00,'2026-01-26 16:24:58'),(0,5,1,5,7500000.00,37500000.00,'2026-01-26 16:24:58'),(0,5,4,2,1800000.00,3600000.00,'2026-01-26 16:24:58'),(0,6,2,4,250000.00,1000000.00,'2026-01-26 16:24:58'),(0,6,3,2,850000.00,1700000.00,'2026-01-26 16:24:58'),(0,6,7,3,350000.00,1050000.00,'2026-01-26 16:24:58'),(0,6,8,2,1200000.00,2400000.00,'2026-01-26 16:24:58'),(0,7,4,5,1800000.00,9000000.00,'2026-01-26 16:24:58'),(0,7,5,2,2300000.00,4600000.00,'2026-01-26 16:24:58'),(0,7,10,1,3500000.00,3500000.00,'2026-01-26 16:24:58'),(0,8,1,6,7500000.00,45000000.00,'2026-01-26 16:24:58'),(0,9,5,3,2300000.00,6900000.00,'2026-01-26 16:24:58'),(0,9,6,2,850000.00,1700000.00,'2026-01-26 16:24:58'),(0,9,9,1,550000.00,550000.00,'2026-01-26 16:24:58'),(0,10,4,2,1800000.00,3600000.00,'2026-01-26 16:24:58'),(0,10,8,1,1200000.00,1200000.00,'2026-01-26 16:24:58'),(0,10,2,3,250000.00,750000.00,'2026-01-26 16:24:58'),(0,10,7,2,350000.00,700000.00,'2026-01-26 16:24:58'),(0,10,6,2,850000.00,1700000.00,'2026-01-26 16:27:00');
/*!40000 ALTER TABLE `detail_penjualan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gudang`
--

DROP TABLE IF EXISTS `gudang`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gudang` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) NOT NULL,
  `alamat` text DEFAULT NULL,
  `telepon` varchar(20) DEFAULT NULL,
  `status` enum('aktif','nonaktif') DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gudang`
--

LOCK TABLES `gudang` WRITE;
/*!40000 ALTER TABLE `gudang` DISABLE KEYS */;
INSERT INTO `gudang` VALUES (1,'Gudang Wahidin','Jl. Wahidin Putra Petir, No. 17, Cirebon','(021) 1234567','aktif','2026-02-02 12:37:01');
/*!40000 ALTER TABLE `gudang` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pengiriman_barang`
--

DROP TABLE IF EXISTS `pengiriman_barang`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pengiriman_barang` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_pengajuan` varchar(50) NOT NULL,
  `nama_pengirim` varchar(100) NOT NULL,
  `alamat_pengirim` text DEFAULT NULL,
  `telepon_pengirim` varchar(20) DEFAULT NULL,
  `nama_penerima` varchar(100) NOT NULL,
  `alamat_tujuan` text NOT NULL,
  `telepon_penerima` varchar(20) DEFAULT NULL,
  `jenis_barang` varchar(100) DEFAULT NULL,
  `barang_id` int(11) DEFAULT NULL,
  `total_qty` int(11) DEFAULT 0,
  `keterangan` text DEFAULT NULL,
  `status` enum('Menunggu','Diproses','Dikirim','Selesai','Dibatalkan') DEFAULT 'Menunggu',
  `foto_barang` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `no_pengajuan` (`no_pengajuan`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pengiriman_barang`
--

LOCK TABLES `pengiriman_barang` WRITE;
/*!40000 ALTER TABLE `pengiriman_barang` DISABLE KEYS */;
INSERT INTO `pengiriman_barang` VALUES (23,'PJ-20260203111141','GUDANG Wahidin','Jl. Wahidin Putra Petir, No. 17, Cirebon','(021) 1234-5678','Toko Tuparev','Jl. Tuparev Gundala. No. 45, Cirebon','0218471','Elektronik',NULL,10,'0','Selesai','pengiriman_20260203111240_bf394653.jpeg','2026-02-03 04:12:40','2026-02-03 04:30:05'),(24,'PJ-20260203135309','GUDANG Wahidin','Jl. Wahidin Putra Petir, No. 17, Cirebon','(021) 1234-5678','Subroto','Kampus 2 UMC, Watubelah','0881212','Elektronik',NULL,6,'0','Selesai','pengiriman_20260203135404_0b259424.jpeg','2026-02-03 06:54:04','2026-02-03 06:56:19'),(25,'PJ-20260203141138','GUDANG Wahidin','Jl. Wahidin Putra Petir, No. 17, Cirebon','(021) 1234-5678','Budi','Toko Komputer Budi','0812345','Perlengkapan Komputer',NULL,10,'0','Selesai','pengiriman_20260203141344_364c576f.jpeg','2026-02-03 07:13:44','2026-02-03 07:15:06'),(26,'PJ-20260203141917','GUDANG Wahidin','Jl. Wahidin Putra Petir, No. 17, Cirebon','(021) 1234-5678','Toko Tuparev','Jl. Tuparev, No.45, Cirebon','08121231','Perlengkapan Komputer',NULL,6,'0','Selesai','pengiriman_20260203142014_5dbc837f.jpeg','2026-02-03 07:20:14','2026-02-03 07:41:07'),(27,'PJ-20260203144118','GUDANG Wahidin','Jl. Wahidin Putra Petir, No. 17, Cirebon','(021) 1234-5678','Santoso','Jl. Tuparev, No.89, Cirebon','01249835','Elektronik',NULL,0,'0','Selesai','pengiriman_20260203144217_234773cd.jpeg','2026-02-03 07:42:17','2026-02-03 07:46:05'),(28,'PJ-20260203144649','GUDANG Wahidin','Jl. Wahidin Putra Petir, No. 17, Cirebon','(021) 1234-5678','Budi','Toko Komputer Budi','0812345','Perangkat Kantor',NULL,6,'0','Selesai','pengiriman_20260203144811_29a8957b.jpeg','2026-02-03 07:48:11','2026-02-03 07:49:33'),(29,'PJ-20260203145013','GUDANG Wahidin','Jl. Wahidin Putra Petir, No. 17, Cirebon','(021) 1234-5678','Herman','Toko Herman','012345','Perlengkapan Komputer',NULL,7,'0','Selesai','pengiriman_20260203145127_3a116e65.jpeg','2026-02-03 07:51:27','2026-02-03 08:02:23');
/*!40000 ALTER TABLE `pengiriman_barang` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pengiriman_detail`
--

DROP TABLE IF EXISTS `pengiriman_detail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pengiriman_detail` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pengiriman_id` int(11) NOT NULL,
  `barang_id` int(11) NOT NULL,
  `qty` int(11) NOT NULL DEFAULT 1,
  `harga` double NOT NULL,
  `subtotal` double NOT NULL,
  `qty_diajukan` int(11) DEFAULT 0,
  `qty_disetujui` int(11) DEFAULT 0,
  `status` enum('menunggu','disetujui','ditolak') DEFAULT 'menunggu',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=66 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pengiriman_detail`
--

LOCK TABLES `pengiriman_detail` WRITE;
/*!40000 ALTER TABLE `pengiriman_detail` DISABLE KEYS */;
INSERT INTO `pengiriman_detail` VALUES (12,4,1,1,7500000,7500000,0,0,'menunggu'),(13,4,9,1,550000,550000,0,0,'menunggu'),(19,10,4,1,1800000,1800000,0,0,'menunggu'),(20,10,9,1,550000,550000,0,0,'menunggu'),(21,12,1,15,7500000,112500000,0,0,'menunggu'),(29,12,2,1,250000,250000,0,0,'menunggu'),(34,13,2,5,250000,1250000,0,0,'menunggu'),(36,14,1,5,7500000,37500000,0,0,'menunggu'),(37,15,1,5,7500000,37500000,0,0,'menunggu'),(39,16,1,5,7500000,37500000,0,0,'menunggu'),(45,18,1,3,7500000,22500000,0,0,'menunggu'),(47,19,1,5,7500000,37500000,0,0,'ditolak'),(48,21,1,1,0,0,2,0,'menunggu'),(49,21,4,1,0,0,2,0,'menunggu'),(50,22,1,1,7500000,15000000,2,2,'disetujui'),(51,22,5,1,2300000,4600000,2,2,'disetujui'),(52,23,1,1,7500000,37500000,5,5,'disetujui'),(53,23,2,1,250000,1250000,5,5,'disetujui'),(54,24,1,1,7500000,37500000,5,5,'disetujui'),(55,24,9,1,550000,550000,1,1,'disetujui'),(56,25,6,1,850000,4250000,5,5,'disetujui'),(57,25,4,1,1800000,9000000,5,5,'disetujui'),(58,26,2,1,250000,1250000,5,5,'disetujui'),(59,26,6,1,850000,850000,1,1,'disetujui'),(60,27,1,1,7500000,37500000,5,5,'disetujui'),(61,27,2,1,250000,1250000,5,5,'disetujui'),(62,28,1,1,7500000,15000000,2,2,'disetujui'),(63,28,4,1,1800000,7200000,4,4,'disetujui'),(64,29,1,1,7500000,22500000,3,3,'disetujui'),(65,29,6,1,850000,3400000,4,4,'disetujui');
/*!40000 ALTER TABLE `pengiriman_detail` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `penjualan`
--

DROP TABLE IF EXISTS `penjualan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `penjualan` (
  `id` int(11) NOT NULL,
  `no_faktur` varchar(50) NOT NULL,
  `tanggal` date NOT NULL,
  `nama_pelanggan` varchar(100) DEFAULT NULL,
  `status_bayar` enum('Lunas','Belum Lunas','DP') DEFAULT 'Belum Lunas',
  `metode_bayar` varchar(50) DEFAULT NULL,
  `diskon` decimal(5,2) DEFAULT 0.00,
  `pajak` decimal(5,2) DEFAULT 0.00,
  `total_bayar` decimal(15,2) DEFAULT 0.00,
  `keterangan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `penjualan`
--

LOCK TABLES `penjualan` WRITE;
/*!40000 ALTER TABLE `penjualan` DISABLE KEYS */;
INSERT INTO `penjualan` VALUES (1,'FAK-202601-001','2026-01-05','PT. Maju Jaya Abadi','Lunas','Transfer Bank',5.00,11.00,25650000.00,'Pembelian untuk kantor cabang','2026-01-05 02:15:00'),(2,'FAK-202601-002','2026-01-07','CV. Sejahtera Bersama','Lunas','Kredit',3.00,11.00,9350000.00,'PO: 123/SPK/2026','2026-01-07 07:30:00'),(3,'FAK-202601-003','2026-01-10','Budi Santoso','Lunas','Cash',0.00,11.00,4950000.00,'Walk-in customer','2026-01-10 04:45:00'),(4,'FAK-202601-004','2026-01-12','Sari Ayu Store','DP','Transfer Bank',7.00,11.00,17500000.00,'DP 50%, pelunasan 2 minggu','2026-01-12 09:20:00'),(5,'FAK-202601-005','2026-01-15','PT. Teknologi Indonesia','Lunas','Transfer Bank',10.00,11.00,40500000.00,'Project kantor pusat','2026-01-15 03:00:00'),(6,'FAK-202601-006','2026-01-18','Andi Wijaya','Lunas','Cash',2.00,11.00,6500000.00,'-','2026-01-18 06:15:00'),(7,'FAK-202601-007','2026-01-20','Toko Elektronik Murah','Belum Lunas','Kredit',5.00,11.00,14250000.00,'Tempo 30 hari','2026-01-20 08:45:00'),(8,'FAK-202601-008','2026-01-22','Dinas Pendidikan Kota','Lunas','Transfer Bank',0.00,0.00,45000000.00,'Proyek sekolah - pembayaran termin 1','2026-01-22 02:30:00'),(9,'FAK-202601-009','2026-01-24','Rumah Sakit Sehat','Lunas','Transfer Bank',8.00,11.00,9200000.00,'Pengadaan alat kesehatan','2026-01-24 04:20:00'),(10,'FAK-202601-010','2026-01-26','Kafe Digital','Lunas','Kredit',4.00,11.00,7950000.00,'Pembelian peralatan kafe','2026-01-26 07:10:00'),(0,'FAK-20260126-143','2026-01-26','Ohim','Belum Lunas','Kredit',0.00,11.00,0.00,'masih nyicil aliass ngutanggg','2026-01-26 16:37:14'),(0,'FAK-20260126-143','2026-01-26','Ohim','Belum Lunas','Lunas',11.00,20.00,0.00,'hehe','2026-01-26 16:39:00');
/*!40000 ALTER TABLE `penjualan` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('toko','gudang') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'toko','$2a$12$0tICac4e4PMQ9KUbjnCvpuqK..VSxUWJMfVJJ9YGVWQNnHBx5CQ0S','toko','2026-01-26 13:27:44'),(2,'gudang','$2a$12$0tICac4e4PMQ9KUbjnCvpuqK..VSxUWJMfVJJ9YGVWQNnHBx5CQ0S','gudang','2026-01-29 07:50:53');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-02-15  0:29:45
