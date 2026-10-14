-- ============================================================
-- Sistem Informasi Gudang Bantuan Bencana (SIGudang)
-- ALL-IN-ONE DATABASE SCRIPT (SCHEMA + SEED DATA)
-- Cukup import file ini 1 kali saja di phpMyAdmin / HeidiSQL
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+07:00";

-- 1. BUAT DATABASE
CREATE DATABASE IF NOT EXISTS `gudang_bencana`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `gudang_bencana`;

-- 2. HAPUS TABEL LAMA JIKA ADA (BERSIH & AMAN)
DROP TABLE IF EXISTS `stock_movements`;
DROP TABLE IF EXISTS `product_suppliers`;
DROP TABLE IF EXISTS `distribution_targets`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `suppliers`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `user_profiles`;
DROP TABLE IF EXISTS `users`;

-- ============================================================
-- TABEL 1: users (Akun Admin & Staff)
-- ============================================================
CREATE TABLE `users` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username`      VARCHAR(80)  NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role`          ENUM('admin','staff') NOT NULL DEFAULT 'staff',
  `full_name`     VARCHAR(150) NOT NULL DEFAULT '',
  `email`         VARCHAR(150)          DEFAULT NULL,
  `is_active`     TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABEL 1B: user_profiles (Relasi One-to-One dengan users)
-- Memisahkan kredensial login dan profil (FK + UNIQUE)
-- Sesuai Capaian Pembelajaran Relational Database (Slide 5 & 6)
-- ============================================================
CREATE TABLE `user_profiles` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED NOT NULL,
  `full_name`  VARCHAR(150) NOT NULL,
  `phone`      VARCHAR(50)           DEFAULT NULL,
  `address`    TEXT                  DEFAULT NULL,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_profile_user_id` (`user_id`),
  CONSTRAINT `fk_user_profile_user` FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABEL 2: categories (Kategori Logistik)
-- ============================================================
CREATE TABLE `categories` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(100) NOT NULL,
  `description` TEXT                  DEFAULT NULL,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cat_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABEL 3: products (Barang Bantuan Logistik)
-- ============================================================
CREATE TABLE `products` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id`   INT UNSIGNED NOT NULL,
  `sku`           VARCHAR(50)  NOT NULL,
  `name`          VARCHAR(200) NOT NULL,
  `unit`          VARCHAR(30)  NOT NULL DEFAULT 'pcs',
  `price`         DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `stock`         INT          NOT NULL DEFAULT 0,
  `minimum_stock` INT          NOT NULL DEFAULT 0,
  `description`   TEXT                  DEFAULT NULL,
  `is_public`     TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sku` (`sku`),
  KEY `fk_prod_cat` (`category_id`),
  CONSTRAINT `fk_prod_cat` FOREIGN KEY (`category_id`)
    REFERENCES `categories` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABEL 4: suppliers (Pemasok / Donatur Lembaga)
-- ============================================================
CREATE TABLE `suppliers` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(200) NOT NULL,
  `type`        VARCHAR(100) NOT NULL DEFAULT 'Lembaga',
  `contact`     VARCHAR(150)          DEFAULT NULL,
  `email`       VARCHAR(150)          DEFAULT NULL,
  `address`     TEXT                  DEFAULT NULL,
  `description` TEXT                  DEFAULT NULL,
  `is_public`   TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABEL 5: product_suppliers (Relasi Many-to-Many Produk-Supplier)
-- ============================================================
CREATE TABLE `product_suppliers` (
  `product_id`     INT UNSIGNED  NOT NULL,
  `supplier_id`    INT UNSIGNED  NOT NULL,
  `supplier_price` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `created_at`     DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`product_id`, `supplier_id`),
  KEY `fk_ps_supplier` (`supplier_id`),
  CONSTRAINT `fk_ps_product`  FOREIGN KEY (`product_id`)
    REFERENCES `products`  (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_ps_supplier` FOREIGN KEY (`supplier_id`)
    REFERENCES `suppliers` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABEL 6: distribution_targets (Titik Posko & Penerima Bencana)
-- ============================================================
CREATE TABLE `distribution_targets` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(200) NOT NULL,
  `location`    VARCHAR(200)          DEFAULT NULL,
  `description` TEXT                  DEFAULT NULL,
  `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABEL 7: stock_movements (Transaksi Mutasi Stok IN / OUT)
-- ============================================================
CREATE TABLE `stock_movements` (
  `id`                     INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `product_id`             INT UNSIGNED  NOT NULL,
  `user_id`                INT UNSIGNED  NOT NULL,
  `supplier_id`            INT UNSIGNED           DEFAULT NULL,
  `distribution_target_id` INT UNSIGNED           DEFAULT NULL,
  `type`                   ENUM('IN','OUT')    NOT NULL,
  `quantity`               INT           NOT NULL,
  `date`                   DATE          NOT NULL,
  `reference`              VARCHAR(100)           DEFAULT NULL,
  `notes`                  TEXT                   DEFAULT NULL,
  `is_public`              TINYINT(1)    NOT NULL DEFAULT 1,
  `created_at`             DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_sm_product`  (`product_id`),
  KEY `fk_sm_user`     (`user_id`),
  KEY `fk_sm_supplier` (`supplier_id`),
  KEY `fk_sm_dist`     (`distribution_target_id`),
  KEY `idx_sm_date`    (`date`),
  KEY `idx_sm_type`    (`type`),
  CONSTRAINT `fk_sm_product`   FOREIGN KEY (`product_id`)
    REFERENCES `products` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_sm_user`      FOREIGN KEY (`user_id`)
    REFERENCES `users`    (`id`) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT `fk_sm_supplier`  FOREIGN KEY (`supplier_id`)
    REFERENCES `suppliers` (`id`) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `fk_sm_dist`      FOREIGN KEY (`distribution_target_id`)
    REFERENCES `distribution_targets` (`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- DATA AWAL (SEED DATA)
-- ============================================================

-- 1. Pengguna (Password default: 'admin123' untuk admin, 'staff123' untuk staff)
INSERT INTO `users` (`id`, `username`, `password_hash`, `role`, `full_name`, `email`, `is_active`) VALUES
(1, 'admin', '$2y$12$jg89L5CpR3a5a9gW2Wmq5.F.AItkJVKCP6e6pdqc8QM1N9D0ixZxe', 'admin', 'Administrator Sistem', 'admin@sigudang.id', 1),
(2, 'staff1', '$2y$12$zX3vvNleayhHYDDFb7wqtehbbmukmLH18tXdxwp7O1nQrZC7ZRzTG', 'staff', 'Budi Santoso', 'budi@sigudang.id', 1),
(3, 'staff2', '$2y$12$sN0i5XMDhpFRrw6NxY3/cObOcohREe5XmK5SJCYbfP8f31cN1HSxe', 'staff', 'Siti Rahayu', 'siti@sigudang.id', 1);

-- 1B. Profil Pengguna (One-to-One dengan users via user_id UNIQUE)
INSERT INTO `user_profiles` (`id`, `user_id`, `full_name`, `phone`, `address`) VALUES
(1, 1, 'Administrator Sistem', '081234567890', 'Kantor BPBD / Gudang Pusat Logistik'),
(2, 2, 'Budi Santoso', '081234567891', 'Gudang Posko Lapangan 1'),
(3, 3, 'Siti Rahayu', '081234567892', 'Gudang Posko Lapangan 2');

-- 2. Kategori Logistik
INSERT INTO `categories` (`id`, `name`, `description`) VALUES
(1, 'Pangan', 'Bahan makanan dan minuman untuk korban bencana'),
(2, 'Sandang', 'Pakaian dan perlengkapan berpakaian'),
(3, 'Kesehatan', 'Obat-obatan dan alat kesehatan'),
(4, 'Peralatan', 'Peralatan rumah tangga dan tenda pengungsian'),
(5, 'Higienitas', 'Sabun, sampo, dan perlengkapan kebersihan diri');

-- 3. Supplier / Lembaga Donatur
INSERT INTO `suppliers` (`id`, `name`, `type`, `contact`, `email`, `address`, `description`, `is_public`) VALUES
(1, 'Palang Merah Indonesia', 'NGO', '021-7992325', 'info@pmi.or.id', 'Jakarta Selatan', 'Organisasi kemanusiaan nasional', 1),
(2, 'BNPB - Badan Nasional', 'Pemerintah', '021-5703867', 'info@bnpb.go.id', 'Jakarta Timur', 'Badan Nasional Penanggulangan Bencana', 1),
(3, 'PT. Indah Karya', 'Perusahaan', '021-4545678', 'csr@indahkarya.co.id', 'Bandung', 'Donasi CSR perusahaan manufaktur', 1),
(4, 'Yayasan Peduli Sesama', 'Yayasan', '021-8891234', 'info@yps.or.id', 'Yogyakarta', 'Yayasan sosial kemanusiaan', 1),
(5, 'Bank Mandiri (CSR)', 'Perusahaan', '021-5299700', 'csr@bankmandiri.co.id', 'Jakarta Pusat', 'Program CSR Bank Mandiri', 0);

-- 4. Titik Posko Bencana
INSERT INTO `distribution_targets` (`id`, `name`, `location`, `description`) VALUES
(1, 'Posko Cianjur', 'Cianjur, Jawa Barat', 'Posko bantuan gempa Cianjur'),
(2, 'Pengungsian Palu', 'Palu, Sulawesi Tengah', 'Pengungsian pasca gempa Palu'),
(3, 'Pos Banjir Bekasi', 'Bekasi, Jawa Barat', 'Posko banjir Bekasi'),
(4, 'Pengungsian Lombok', 'Lombok, NTB', 'Pengungsian gempa Lombok'),
(5, 'Pos Gunung Semeru', 'Lumajang, Jawa Timur', 'Posko erupsi Gunung Semeru');

-- 5. Produk Logistik Bantuan
INSERT INTO `products` (`id`, `category_id`, `sku`, `name`, `unit`, `price`, `stock`, `minimum_stock`, `description`, `is_public`) VALUES
(1, 1, 'PRD001', 'Beras Premium 5kg', 'karung', 75000.00, 1250, 200, 'Beras kualitas premium untuk pengungsi', 1),
(2, 1, 'PRD002', 'Mi Instan (karton 40pcs)', 'karton', 95000.00, 480, 100, 'Mi instan berbagai rasa', 1),
(3, 1, 'PRD003', 'Air Mineral 600ml (karton)', 'karton', 45000.00, 2300, 300, 'Air mineral dalam kemasan', 1),
(4, 1, 'PRD004', 'Minyak Goreng 2L', 'botol', 35000.00, 380, 80, 'Minyak goreng kemasan', 1),
(5, 1, 'PRD005', 'Telur Ayam (tray 30butir)', 'tray', 62000.00, 150, 50, 'Telur ayam segar', 1),
(6, 2, 'PRD006', 'Selimut Tebal', 'lembar', 85000.00, 430, 100, 'Selimut untuk pengungsi', 1),
(7, 2, 'PRD007', 'Pakaian Dewasa Campur', 'set', 55000.00, 300, 80, 'Paket pakaian dewasa layak pakai', 1),
(8, 2, 'PRD008', 'Pakaian Anak', 'set', 45000.00, 220, 60, 'Paket pakaian anak', 1),
(9, 3, 'PRD009', 'Obat P3K Kit', 'kotak', 125000.00, 180, 50, 'Kotak P3K lengkap', 1),
(10, 3, 'PRD010', 'Parasetamol 500mg (box)', 'box', 28000.00, 420, 100, 'Parasetamol kemasan box', 1),
(11, 3, 'PRD011', 'Masker Medis (box 50pcs)', 'box', 35000.00, 650, 150, 'Masker medis 3-ply', 1),
(12, 4, 'PRD012', 'Tenda Pengungsian', 'unit', 1500000.00, 45, 10, 'Tenda kapasitas 6 orang', 1),
(13, 4, 'PRD013', 'Terpal 4x6m', 'lembar', 75000.00, 120, 30, 'Terpal tebal warna biru', 1),
(14, 5, 'PRD014', 'Sabun Mandi (lusin)', 'lusin', 48000.00, 280, 80, 'Sabun mandi batang', 1),
(15, 5, 'PRD015', 'Pasta Gigi', 'buah', 15000.00, 550, 100, 'Pasta gigi 120gr', 1);

-- 6. Relasi Produk - Supplier (Many to Many)
INSERT INTO `product_suppliers` (`product_id`, `supplier_id`, `supplier_price`) VALUES
(1, 1, 72000.00), (1, 2, 70000.00), (2, 1, 92000.00), (3, 3, 43000.00), (4, 3, 33000.00),
(6, 1, 82000.00), (6, 4, 80000.00), (9, 1, 120000.00), (9, 2, 118000.00), (12, 2, 1450000.00),
(13, 3, 72000.00), (14, 4, 46000.00), (15, 4, 14000.00), (10, 1, 26000.00), (11, 1, 33000.00);

-- 7. Histori Transaksi Mutasi Logistik
INSERT INTO `stock_movements` (`product_id`, `user_id`, `supplier_id`, `distribution_target_id`, `type`, `quantity`, `date`, `reference`, `notes`, `is_public`) VALUES
(1, 1, 1, NULL, 'IN', 500, '2026-01-05', 'REF-2026-001', 'Donasi awal masuk gudang', 1),
(1, 1, 2, NULL, 'IN', 300, '2026-01-10', 'REF-2026-002', 'Pengiriman kedua dari PMI', 1),
(1, 2, NULL, 1, 'OUT', 200, '2026-01-15', 'TRX-2026-001', 'Distribusi ke Posko Cianjur', 1),
(2, 1, 1, NULL, 'IN', 200, '2026-01-05', 'REF-2026-003', 'Donasi mi instan', 1),
(2, 2, NULL, 2, 'OUT', 80, '2026-01-20', 'TRX-2026-002', 'Distribusi ke Pengungsian Palu', 1),
(3, 1, 3, NULL, 'IN', 1000, '2026-01-05', 'REF-2026-004', 'Donasi air mineral', 1),
(3, 2, NULL, 1, 'OUT', 500, '2026-01-18', 'TRX-2026-003', 'Air mineral ke Cianjur', 1),
(6, 1, 1, NULL, 'IN', 200, '2026-01-08', 'REF-2026-005', 'Selimut dari PMI', 1),
(6, 2, NULL, 3, 'OUT', 100, '2026-01-25', 'TRX-2026-004', 'Selimut ke Bekasi', 1),
(9, 1, 1, NULL, 'IN', 100, '2026-02-01', 'REF-2026-006', 'P3K kit dari PMI', 1),
(9, 2, NULL, 4, 'OUT', 50, '2026-02-10', 'TRX-2026-005', 'P3K ke Lombok', 1),
(12, 1, 2, NULL, 'IN', 30, '2026-02-05', 'REF-2026-007', 'Tenda dari BNPB', 1),
(12, 2, NULL, 5, 'OUT', 15, '2026-02-15', 'TRX-2026-006', 'Tenda ke Semeru', 1),
(10, 1, 1, NULL, 'IN', 200, '2026-02-10', 'REF-2026-008', 'Obat dari PMI', 1),
(11, 1, 1, NULL, 'IN', 300, '2026-02-12', 'REF-2026-009', 'Masker dari PMI', 1),
(11, 2, NULL, 1, 'OUT', 100, '2026-03-01', 'TRX-2026-007', 'Masker ke Cianjur', 1),
(4, 1, 3, NULL, 'IN', 150, '2026-03-05', 'REF-2026-010', 'Minyak goreng', 1),
(4, 2, NULL, 2, 'OUT', 80, '2026-03-10', 'TRX-2026-008', 'Minyak ke Palu', 1),
(14, 1, 4, NULL, 'IN', 150, '2026-03-08', 'REF-2026-011', 'Sabun dari Yayasan', 1),
(14, 2, NULL, 3, 'OUT', 70, '2026-03-15', 'TRX-2026-009', 'Sabun ke Bekasi', 1),
(7, 1, 4, NULL, 'IN', 200, '2026-03-10', 'REF-2026-012', 'Pakaian dewasa', 1),
(7, 2, NULL, 4, 'OUT', 100, '2026-03-20', 'TRX-2026-010', 'Pakaian ke Lombok', 1),
(8, 1, 4, NULL, 'IN', 150, '2026-03-12', 'REF-2026-013', 'Pakaian anak', 1),
(8, 2, NULL, 5, 'OUT', 70, '2026-03-22', 'TRX-2026-011', 'Pakaian ke Semeru', 1),
(1, 2, NULL, 4, 'OUT', 150, '2026-04-01', 'TRX-2026-012', 'Beras ke Lombok', 1),
(2, 2, NULL, 5, 'OUT', 60, '2026-04-05', 'TRX-2026-013', 'Mi ke Semeru', 1),
(5, 1, 3, NULL, 'IN', 100, '2026-04-08', 'REF-2026-014', 'Telur donasi', 1),
(5, 2, NULL, 1, 'OUT', 60, '2026-04-12', 'TRX-2026-014', 'Telur ke Cianjur', 1),
(13, 1, 3, NULL, 'IN', 80, '2026-04-15', 'REF-2026-015', 'Terpal dari perusahaan', 1),
(13, 2, NULL, 2, 'OUT', 40, '2026-04-20', 'TRX-2026-015', 'Terpal ke Palu', 1);
