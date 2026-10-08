-- ========================================================================
-- Database: tanaman_mini_db
-- Dibuat untuk Proyek Digital Entrepreneurship: Toko Tanaman Hias Mini
-- ========================================================================

CREATE DATABASE IF NOT EXISTS `tanaman_mini_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `tanaman_mini_db`;

-- --------------------------------------------------------
-- 1. Tabel users (Data Pembeli)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `no_hp` VARCHAR(20) NOT NULL,
  `alamat` TEXT NOT NULL,
  `role` ENUM('user') DEFAULT 'user',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 2. Tabel admins (Data Administrator)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `nama` VARCHAR(100) NOT NULL DEFAULT 'Administrator',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 3. Tabel categories (Kategori Tanaman)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nama_kategori` VARCHAR(50) NOT NULL,
  `slug` VARCHAR(50) NOT NULL UNIQUE,
  `icon` VARCHAR(50) DEFAULT 'fa-leaf'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 4. Tabel products (Data Produk Tanaman Hias Mini)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT NOT NULL,
  `nama_produk` VARCHAR(150) NOT NULL,
  `deskripsi` TEXT NOT NULL,
  `harga_modal` DECIMAL(12,2) NOT NULL,
  `harga_jual` DECIMAL(12,2) NOT NULL,
  `stok` INT NOT NULL DEFAULT 0,
  `foto` VARCHAR(255) NOT NULL DEFAULT 'default.jpg',
  `rating` DECIMAL(2,1) DEFAULT 4.9,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 5. Tabel carts (Keranjang Belanja)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `carts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `jumlah` INT NOT NULL DEFAULT 1,
  UNIQUE KEY `unique_user_product` (`user_id`, `product_id`),
  CONSTRAINT `fk_carts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_carts_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 6. Tabel orders (Pesanan Pembeli)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `invoice` VARCHAR(50) NOT NULL UNIQUE,
  `total_harga` DECIMAL(12,2) NOT NULL,
  `ongkir` DECIMAL(12,2) NOT NULL DEFAULT 15000.00,
  `total_pembayaran` DECIMAL(12,2) NOT NULL,
  `status_pembayaran` ENUM('Menunggu Pembayaran', 'Pembayaran Berhasil', 'Gagal', 'Kadaluarsa') DEFAULT 'Menunggu Pembayaran',
  `status_pesanan` ENUM('Menunggu Pembayaran', 'Pembayaran Berhasil', 'Diproses', 'Dikemas', 'Dikirim', 'Selesai', 'Dibatalkan') DEFAULT 'Menunggu Pembayaran',
  `alamat_pengiriman` TEXT NOT NULL,
  `catatan` TEXT DEFAULT NULL,
  `tanggal` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 7. Tabel order_details (Rincian Produk di Pesanan)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `order_details` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `jumlah` INT NOT NULL,
  `harga` DECIMAL(12,2) NOT NULL,
  `harga_modal` DECIMAL(12,2) NOT NULL,
  `subtotal` DECIMAL(12,2) NOT NULL,
  CONSTRAINT `fk_details_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_details_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- 8. Tabel payments (Log Transaksi Pembayaran / Midtrans)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `metode_pembayaran` VARCHAR(50) NOT NULL,
  `transaction_id` VARCHAR(100) DEFAULT NULL,
  `status` VARCHAR(50) NOT NULL,
  `paid_at` DATETIME DEFAULT NULL,
  CONSTRAINT `fk_payments_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========================================================================
-- DATA AWAL (SEEDING)
-- ========================================================================

-- Kategori Tanaman
INSERT INTO `categories` (`id`, `nama_kategori`, `slug`, `icon`) VALUES
(1, 'Sukulen', 'sukulen', 'fa-seedling'),
(2, 'Kaktus', 'kaktus', 'fa-sun'),
(3, 'Tanaman Meja', 'tanaman-meja', 'fa-laptop-house'),
(4, 'Tanaman Indoor', 'tanaman-indoor', 'fa-couch'),
(5, 'Tanaman Mini', 'tanaman-mini', 'fa-spa')
ON DUPLICATE KEY UPDATE `nama_kategori`=VALUES(`nama_kategori`);

-- Akun Admin Default: username=admin, password=admin123
INSERT INTO `admins` (`id`, `username`, `password`, `nama`) VALUES
(1, 'admin', '$2y$10$EWusesSPqsTxNbCijA6LIeJVK3emmToytDK37JMPfB/BJKqPy1Tmm', 'Admin Tanaman Hias')
ON DUPLICATE KEY UPDATE `username`=VALUES(`username`);

-- Akun Pembeli Demo: email=user@gmail.com, password=user123
INSERT INTO `users` (`id`, `nama`, `email`, `password`, `no_hp`, `alamat`, `role`) VALUES
(1, 'Ahmad Zaki Pratama', 'user@gmail.com', '$2y$10$XdXQLdjonJ2XvqyiV5dRE.b8de7kmJa4tRXwNTnVQ4J0drfD2R5Wa', '081234567890', 'Jl. Dharmawangsa Indah No. 12, Gubeng, Surabaya', 'user'),
(2, 'Nabila Maharani', 'nabila@gmail.com', '$2y$10$XdXQLdjonJ2XvqyiV5dRE.b8de7kmJa4tRXwNTnVQ4J0drfD2R5Wa', '085799881122', 'Jl. Tebet Barat Dalam Raya No. 45, Jakarta Selatan', 'user')
ON DUPLICATE KEY UPDATE `email`=VALUES(`email`);

-- Data Produk Tanaman Hias Mini
INSERT INTO `products` (`id`, `category_id`, `nama_produk`, `deskripsi`, `harga_modal`, `harga_jual`, `stok`, `foto`, `rating`) VALUES
(1, 3, 'Monstera Mini (Adansonii Janda Bolong)', 'Monstera Adansonii berukuran mini di pot keramik putih ukuran 10cm. Sangat cocok diletakkan di sudut meja kerja atau rak hias.', 25000.00, 45000.00, 24, 'monstera-mini.svg', 5.0),
(2, 2, 'Kaktus Koboi Mini Pot Terakota', 'Kaktus koboi mini dengan karakter batang tegak dan elegan. Sangat mudah dirawat dan hemat air, cocok untuk hiasan meja komputer.', 18000.00, 35000.00, 30, 'kaktus-koboi.svg', 4.9),
(3, 1, 'Sukulen Echeveria Elegant Blue', 'Sukulen bentuk kelopak bunga mawar berwarna kebiruan yang memikat. Sudah termasuk pot semen minimalis dan batu hias.', 15000.00, 28000.00, 45, 'sukulen-echeveria.svg', 4.8),
(4, 4, 'Lidah Mertua Mini (Sansevieria Pagoda)', 'Sansevieria mini pagoda yang ampuh menyerap polusi dan radiasi elektromagnetik di sekitar meja kerja.', 20000.00, 38000.00, 20, 'sansevieria-mini.svg', 5.0),
(5, 3, 'Pilea Peperomioides (Chinese Money Plant)', 'Tanaman pembawa keberuntungan dengan daun bulat seperti koin. Sangat hits untuk dekorasi interior Skandinavia.', 30000.00, 55000.00, 15, 'pilea-mini.svg', 4.9),
(6, 5, 'Fittonia Pink Mini (Nerve Plant)', 'Fittonia dengan corak urat daun berwarna pink cerah. Membawa nuansa hidup dan manis pada ruang belajar atau kamar.', 17000.00, 32000.00, 25, 'fittonia-pink.svg', 4.7),
(7, 4, 'Sirih Gading Mini Pot Gantung/Meja', 'Sirih gading varigata yang segar dan mudah tumbuh. Tahan pada ruangan ber-AC dan minim cahaya.', 15000.00, 29000.00, 35, 'sirih-gading.svg', 4.8),
(8, 3, 'Calathea Maranta Mini (Prayer Plant)', 'Calathea mini dengan corak garis artistik. Daunnya bergerak aktif mengikuti ritme siang dan malam.', 28000.00, 50000.00, 18, 'calathea-mini.svg', 4.9)
ON DUPLICATE KEY UPDATE `nama_produk`=VALUES(`nama_produk`);

-- Data Pesanan Awal untuk Statistik Dashboard & Laporan Penjualan
INSERT INTO `orders` (`id`, `user_id`, `invoice`, `total_harga`, `ongkir`, `total_pembayaran`, `status_pembayaran`, `status_pesanan`, `alamat_pengiriman`, `catatan`, `tanggal`) VALUES
(1, 1, 'INV-20260901-0001', 80000.00, 15000.00, 95000.00, 'Pembayaran Berhasil', 'Selesai', 'Jl. Dharmawangsa Indah No. 12, Gubeng, Surabaya', 'Tolong pilihkan yang daunnya rimbun ya kak', '2026-09-01 10:15:00'),
(2, 2, 'INV-20260905-0002', 110000.00, 15000.00, 125000.00, 'Pembayaran Berhasil', 'Dikirim', 'Jl. Tebet Barat Dalam Raya No. 45, Jakarta Selatan', 'Packing bubble wrap tebal', '2026-09-05 14:30:00'),
(3, 1, 'INV-20260910-0003', 73000.00, 15000.00, 88000.00, 'Pembayaran Berhasil', 'Diproses', 'Jl. Dharmawangsa Indah No. 12, Gubeng, Surabaya', 'Bonus kartu ucapan jika bisa', '2026-09-10 09:20:00')
ON DUPLICATE KEY UPDATE `invoice`=VALUES(`invoice`);

-- Data Detail Pesanan (Rincian Produk & Modal untuk Hitung Laba)
INSERT INTO `order_details` (`id`, `order_id`, `product_id`, `jumlah`, `harga`, `harga_modal`, `subtotal`) VALUES
(1, 1, 1, 1, 45000.00, 25000.00, 45000.00),
(2, 1, 2, 1, 35000.00, 18000.00, 35000.00),
(3, 2, 5, 2, 55000.00, 30000.00, 110000.00),
(4, 3, 3, 1, 28000.00, 15000.00, 28000.00),
(5, 3, 1, 1, 45000.00, 25000.00, 45000.00)
ON DUPLICATE KEY UPDATE `id`=VALUES(`id`);

-- Data Log Pembayaran
INSERT INTO `payments` (`id`, `order_id`, `metode_pembayaran`, `transaction_id`, `status`, `paid_at`) VALUES
(1, 1, 'BCA Virtual Account (Midtrans)', 'MID-TRX-20260901-001', 'settlement', '2026-09-01 10:18:22'),
(2, 2, 'GoPay / QRIS (Midtrans)', 'MID-TRX-20260905-002', 'settlement', '2026-09-05 14:32:05'),
(3, 3, 'Mandiri Bill (Midtrans)', 'MID-TRX-20260910-003', 'settlement', '2026-09-10 09:25:40')
ON DUPLICATE KEY UPDATE `id`=VALUES(`id`);
