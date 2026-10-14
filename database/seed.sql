-- ============================================================
-- Seed Data - Sistem Informasi Gudang Bantuan Bencana
-- Generated: 2026-10-14 11:00:57
-- JANGAN commit file ini ke repository publik!
-- ============================================================

USE `gudang_bencana`;

-- Users
INSERT IGNORE INTO `users` (`username`,`password_hash`,`role`,`full_name`,`email`,`is_active`) VALUES
('admin', '$2y$12$jg89L5CpR3a5a9gW2Wmq5.F.AItkJVKCP6e6pdqc8QM1N9D0ixZxe', 'admin', 'Administrator Sistem', 'admin@sigudang.id', 1),
('staff1', '$2y$12$zX3vvNleayhHYDDFb7wqtehbbmukmLH18tXdxwp7O1nQrZC7ZRzTG', 'staff', 'Budi Santoso', 'budi@sigudang.id', 1),
('staff2', '$2y$12$sN0i5XMDhpFRrw6NxY3/cObOcohREe5XmK5SJCYbfP8f31cN1HSxe', 'staff', 'Siti Rahayu', 'siti@sigudang.id', 1);

-- User Profiles (One-to-One dengan users via user_id UNIQUE)
INSERT IGNORE INTO `user_profiles` (`user_id`,`full_name`,`phone`,`address`) VALUES
(1, 'Administrator Sistem', '081234567890', 'Kantor BPBD / Gudang Pusat Logistik'),
(2, 'Budi Santoso', '081234567891', 'Gudang Posko Lapangan 1'),
(3, 'Siti Rahayu', '081234567892', 'Gudang Posko Lapangan 2');

-- Categories
INSERT IGNORE INTO `categories` (`name`,`description`) VALUES
('Pangan', 'Bahan makanan dan minuman untuk korban bencana'),
('Sandang', 'Pakaian dan perlengkapan berpakaian'),
('Kesehatan', 'Obat-obatan dan alat kesehatan'),
('Peralatan', 'Peralatan rumah tangga dan tenda pengungsian'),
('Higienitas', 'Sabun, sampo, dan perlengkapan kebersihan diri');

-- Suppliers
INSERT IGNORE INTO `suppliers` (`name`,`type`,`contact`,`email`,`address`,`description`,`is_public`) VALUES
('Palang Merah Indonesia', 'NGO', '021-7992325', 'info@pmi.or.id', 'Jakarta Selatan', 'Organisasi kemanusiaan nasional', 1),
('BNPB - Badan Nasional', 'Pemerintah', '021-5703867', 'info@bnpb.go.id', 'Jakarta Timur', 'Badan Nasional Penanggulangan Bencana', 1),
('PT. Indah Karya', 'Perusahaan', '021-4545678', 'csr@indahkarya.co.id', 'Bandung', 'Donasi CSR perusahaan manufaktur', 1),
('Yayasan Peduli Sesama', 'Yayasan', '021-8891234', 'info@yps.or.id', 'Yogyakarta', 'Yayasan sosial kemanusiaan', 1),
('Bank Mandiri (CSR)', 'Perusahaan', '021-5299700', 'csr@bankmandiri.co.id', 'Jakarta Pusat', 'Program CSR Bank Mandiri', 0);

-- Distribution Targets
INSERT IGNORE INTO `distribution_targets` (`name`,`location`,`description`) VALUES
('Posko Cianjur', 'Cianjur, Jawa Barat', 'Posko bantuan gempa Cianjur'),
('Pengungsian Palu', 'Palu, Sulawesi Tengah', 'Pengungsian pasca gempa Palu'),
('Pos Banjir Bekasi', 'Bekasi, Jawa Barat', 'Posko banjir Bekasi'),
('Pengungsian Lombok', 'Lombok, NTB', 'Pengungsian gempa Lombok'),
('Pos Gunung Semeru', 'Lumajang, Jawa Timur', 'Posko erupsi Gunung Semeru');

-- Products
INSERT IGNORE INTO `products` (`category_id`,`sku`,`name`,`unit`,`price`,`stock`,`minimum_stock`,`description`,`is_public`) VALUES
(1,'PRD001','Beras Premium 5kg','karung',75000,1250,200,'Beras kualitas premium untuk pengungsi',1),
(1,'PRD002','Mi Instan (karton 40pcs)','karton',95000,480,100,'Mi instan berbagai rasa',1),
(1,'PRD003','Air Mineral 600ml (karton)','karton',45000,2300,300,'Air mineral dalam kemasan',1),
(1,'PRD004','Minyak Goreng 2L','botol',35000,380,80,'Minyak goreng kemasan',1),
(1,'PRD005','Telur Ayam (tray 30butir)','tray',62000,150,50,'Telur ayam segar',1),
(2,'PRD006','Selimut Tebal','lembar',85000,430,100,'Selimut untuk pengungsi',1),
(2,'PRD007','Pakaian Dewasa Campur','set',55000,300,80,'Paket pakaian dewasa layak pakai',1),
(2,'PRD008','Pakaian Anak','set',45000,220,60,'Paket pakaian anak',1),
(3,'PRD009','Obat P3K Kit','kotak',125000,180,50,'Kotak P3K lengkap',1),
(3,'PRD010','Parasetamol 500mg (box)','box',28000,420,100,'Parasetamol kemasan box',1),
(3,'PRD011','Masker Medis (box 50pcs)','box',35000,650,150,'Masker medis 3-ply',1),
(4,'PRD012','Tenda Pengungsian','unit',1500000,45,10,'Tenda kapasitas 6 orang',1),
(4,'PRD013','Terpal 4x6m','lembar',75000,120,30,'Terpal tebal warna biru',1),
(5,'PRD014','Sabun Mandi (lusin)','lusin',48000,280,80,'Sabun mandi batang',1),
(5,'PRD015','Pasta Gigi','buah',15000,550,100,'Pasta gigi 120gr',1);

-- Product-Supplier Relations
INSERT IGNORE INTO `product_suppliers` (`product_id`,`supplier_id`,`supplier_price`) VALUES
(1,1,72000),(1,2,70000),(2,1,92000),(3,3,43000),(4,3,33000),
(6,1,82000),(6,4,80000),(9,1,120000),(9,2,118000),(12,2,1450000),
(13,3,72000),(14,4,46000),(15,4,14000),(10,1,26000),(11,1,33000);

-- Stock Movements
INSERT INTO `stock_movements` (`product_id`,`user_id`,`supplier_id`,`distribution_target_id`,`type`,`quantity`,`date`,`reference`,`notes`,`is_public`) VALUES
(1,1,1,NULL,'IN',500,'2026-01-05','REF-2026-001','Donasi awal masuk gudang',1),
(1,1,2,NULL,'IN',300,'2026-01-10','REF-2026-002','Pengiriman kedua dari PMI',1),
(1,2,NULL,1,'OUT',200,'2026-01-15','TRX-2026-001','Distribusi ke Posko Cianjur',1),
(2,1,1,NULL,'IN',200,'2026-01-05','REF-2026-003','Donasi mi instan',1),
(2,2,NULL,2,'OUT',80,'2026-01-20','TRX-2026-002','Distribusi ke Pengungsian Palu',1),
(3,1,3,NULL,'IN',1000,'2026-01-05','REF-2026-004','Donasi air mineral',1),
(3,2,NULL,1,'OUT',500,'2026-01-18','TRX-2026-003','Air mineral ke Cianjur',1),
(6,1,1,NULL,'IN',200,'2026-01-08','REF-2026-005','Selimut dari PMI',1),
(6,2,NULL,3,'OUT',100,'2026-01-25','TRX-2026-004','Selimut ke Bekasi',1),
(9,1,1,NULL,'IN',100,'2026-02-01','REF-2026-006','P3K kit dari PMI',1),
(9,2,NULL,4,'OUT',50,'2026-02-10','TRX-2026-005','P3K ke Lombok',1),
(12,1,2,NULL,'IN',30,'2026-02-05','REF-2026-007','Tenda dari BNPB',1),
(12,2,NULL,5,'OUT',15,'2026-02-15','TRX-2026-006','Tenda ke Semeru',1),
(10,1,1,NULL,'IN',200,'2026-02-10','REF-2026-008','Obat dari PMI',1),
(11,1,1,NULL,'IN',300,'2026-02-12','REF-2026-009','Masker dari PMI',1),
(11,2,NULL,1,'OUT',100,'2026-03-01','TRX-2026-007','Masker ke Cianjur',1),
(4,1,3,NULL,'IN',150,'2026-03-05','REF-2026-010','Minyak goreng',1),
(4,2,NULL,2,'OUT',80,'2026-03-10','TRX-2026-008','Minyak ke Palu',1),
(14,1,4,NULL,'IN',150,'2026-03-08','REF-2026-011','Sabun dari Yayasan',1),
(14,2,NULL,3,'OUT',70,'2026-03-15','TRX-2026-009','Sabun ke Bekasi',1),
(7,1,4,NULL,'IN',200,'2026-03-10','REF-2026-012','Pakaian dewasa',1),
(7,2,NULL,4,'OUT',100,'2026-03-20','TRX-2026-010','Pakaian ke Lombok',1),
(8,1,4,NULL,'IN',150,'2026-03-12','REF-2026-013','Pakaian anak',1),
(8,2,NULL,5,'OUT',70,'2026-03-22','TRX-2026-011','Pakaian ke Semeru',1),
(1,2,NULL,4,'OUT',150,'2026-04-01','TRX-2026-012','Beras ke Lombok',1),
(2,2,NULL,5,'OUT',60,'2026-04-05','TRX-2026-013','Mi ke Semeru',1),
(5,1,3,NULL,'IN',100,'2026-04-08','REF-2026-014','Telur donasi',1),
(5,2,NULL,1,'OUT',60,'2026-04-12','TRX-2026-014','Telur ke Cianjur',1),
(13,1,3,NULL,'IN',80,'2026-04-15','REF-2026-015','Terpal dari perusahaan',1),
(13,2,NULL,2,'OUT',40,'2026-04-20','TRX-2026-015','Terpal ke Palu',1);

-- Selesai
