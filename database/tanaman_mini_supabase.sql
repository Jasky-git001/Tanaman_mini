-- ========================================================================
-- Schema Database untuk Supabase (PostgreSQL)
-- Proyek Digital Entrepreneurship: Toko Tanaman Hias Mini
-- Jalankan script ini di SQL Editor pada Dashboard Supabase Anda
-- ========================================================================

-- 1. Tabel users (Data Pembeli)
CREATE TABLE IF NOT EXISTS users (
  id SERIAL PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  no_hp VARCHAR(20) NOT NULL,
  alamat TEXT NOT NULL,
  role VARCHAR(20) DEFAULT 'user',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Tabel admins (Data Administrator)
CREATE TABLE IF NOT EXISTS admins (
  id SERIAL PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  nama VARCHAR(100) NOT NULL DEFAULT 'Administrator',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. Tabel categories (Kategori Tanaman)
CREATE TABLE IF NOT EXISTS categories (
  id SERIAL PRIMARY KEY,
  nama_kategori VARCHAR(50) NOT NULL,
  slug VARCHAR(50) NOT NULL UNIQUE,
  icon VARCHAR(50) DEFAULT 'fa-leaf'
);

-- 4. Tabel products (Data Produk Tanaman Hias Mini)
CREATE TABLE IF NOT EXISTS products (
  id SERIAL PRIMARY KEY,
  category_id INT NOT NULL,
  nama_produk VARCHAR(150) NOT NULL,
  deskripsi TEXT NOT NULL,
  harga_modal DECIMAL(12,2) NOT NULL,
  harga_jual DECIMAL(12,2) NOT NULL,
  stok INT NOT NULL DEFAULT 0,
  foto VARCHAR(255) NOT NULL DEFAULT 'default.jpg',
  rating DECIMAL(2,1) DEFAULT 4.9,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE CASCADE ON UPDATE CASCADE
);

-- 5. Tabel carts (Keranjang Belanja)
CREATE TABLE IF NOT EXISTS carts (
  id SERIAL PRIMARY KEY,
  user_id INT NOT NULL,
  product_id INT NOT NULL,
  jumlah INT NOT NULL DEFAULT 1,
  CONSTRAINT unique_user_product UNIQUE (user_id, product_id),
  CONSTRAINT fk_carts_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
  CONSTRAINT fk_carts_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE
);

-- 6. Tabel orders (Pesanan Pembeli)
CREATE TABLE IF NOT EXISTS orders (
  id SERIAL PRIMARY KEY,
  user_id INT NOT NULL,
  invoice VARCHAR(50) NOT NULL UNIQUE,
  total_harga DECIMAL(12,2) NOT NULL,
  ongkir DECIMAL(12,2) NOT NULL DEFAULT 15000.00,
  total_pembayaran DECIMAL(12,2) NOT NULL,
  status_pembayaran VARCHAR(50) DEFAULT 'Menunggu Pembayaran',
  status_pesanan VARCHAR(50) DEFAULT 'Menunggu Pembayaran',
  alamat_pengiriman TEXT NOT NULL,
  catatan TEXT DEFAULT NULL,
  alasan_pengembalian TEXT DEFAULT NULL,
  tanggal TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE RESTRICT
);

-- 7. Tabel order_details (Rincian Produk di Pesanan)
CREATE TABLE IF NOT EXISTS order_details (
  id SERIAL PRIMARY KEY,
  order_id INT NOT NULL,
  product_id INT NOT NULL,
  jumlah INT NOT NULL,
  harga DECIMAL(12,2) NOT NULL,
  harga_modal DECIMAL(12,2) NOT NULL,
  subtotal DECIMAL(12,2) NOT NULL,
  CONSTRAINT fk_details_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE,
  CONSTRAINT fk_details_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE RESTRICT
);

-- 8. Tabel payments (Log Transaksi Pembayaran / Midtrans)
CREATE TABLE IF NOT EXISTS payments (
  id SERIAL PRIMARY KEY,
  order_id INT NOT NULL,
  metode_pembayaran VARCHAR(50) NOT NULL,
  transaction_id VARCHAR(100) DEFAULT NULL,
  status VARCHAR(50) NOT NULL,
  paid_at TIMESTAMP DEFAULT NULL,
  CONSTRAINT fk_payments_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE
);

-- 9. Tabel product_reviews (Ulasan Produk)
CREATE TABLE IF NOT EXISTS product_reviews (
  id SERIAL PRIMARY KEY,
  product_id INT NOT NULL,
  user_id INT NOT NULL,
  order_id INT DEFAULT NULL,
  rating SMALLINT NOT NULL,
  komentar TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT unique_user_product_review UNIQUE (user_id, product_id),
  CONSTRAINT fk_reviews_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE,
  CONSTRAINT fk_reviews_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
);

-- ========================================================================
-- DATA AWAL (SEEDING)
-- ========================================================================

-- Kategori Tanaman
INSERT INTO categories (id, nama_kategori, slug, icon) VALUES
(1, 'Sukulen', 'sukulen', 'fa-seedling'),
(2, 'Kaktus', 'kaktus', 'fa-sun'),
(3, 'Tanaman Meja', 'tanaman-meja', 'fa-laptop-house'),
(4, 'Tanaman Indoor', 'tanaman-indoor', 'fa-couch'),
(5, 'Tanaman Mini', 'tanaman-mini', 'fa-spa')
ON CONFLICT (id) DO UPDATE SET nama_kategori = EXCLUDED.nama_kategori;

-- Akun Admin Default: username=admin, password=admin123
INSERT INTO admins (id, username, password, nama) VALUES
(1, 'admin', '$2y$10$EWusesSPqsTxNbCijA6LIeJVK3emmToytDK37JMPfB/BJKqPy1Tmm', 'Admin Tanaman Hias')
ON CONFLICT (id) DO UPDATE SET username = EXCLUDED.username;

-- Akun Pembeli Demo: email=user@gmail.com, password=user123
INSERT INTO users (id, nama, email, password, no_hp, alamat, role) VALUES
(1, 'Ahmad Zaki Pratama', 'user@gmail.com', '$2y$10$XdXQLdjonJ2XvqyiV5dRE.b8de7kmJa4tRXwNTnVQ4J0drfD2R5Wa', '081234567890', 'Jl. Dharmawangsa Indah No. 12, Gubeng, Surabaya', 'user'),
(2, 'Nabila Maharani', 'nabila@gmail.com', '$2y$10$XdXQLdjonJ2XvqyiV5dRE.b8de7kmJa4tRXwNTnVQ4J0drfD2R5Wa', '085799881122', 'Jl. Tebet Barat Dalam Raya No. 45, Jakarta Selatan', 'user')
ON CONFLICT (id) DO UPDATE SET email = EXCLUDED.email;

-- Data Produk Tanaman Hias Mini
INSERT INTO products (id, category_id, nama_produk, deskripsi, harga_modal, harga_jual, stok, foto, rating) VALUES
(1, 3, 'Monstera Mini (Adansonii Janda Bolong)', 'Monstera Adansonii berukuran mini di pot keramik putih ukuran 10cm. Sangat cocok diletakkan di sudut meja kerja atau rak hias.', 25000.00, 45000.00, 24, 'monstera-mini.jpg', 5.0),
(2, 2, 'Kaktus Koboi Mini Pot Terakota', 'Kaktus koboi mini dengan karakter batang tegak dan elegan. Sangat mudah dirawat dan hemat air, cocok untuk hiasan meja komputer.', 18000.00, 35000.00, 30, 'kaktus-koboi.jpg', 4.9),
(3, 1, 'Sukulen Echeveria Elegant Blue', 'Sukulen bentuk kelopak bunga mawar berwarna kebiruan yang memikat. Sudah termasuk pot semen minimalis dan batu hias.', 15000.00, 28000.00, 45, 'sukulen-echeveria.jpg', 4.8),
(4, 4, 'Lidah Mertua Mini (Sansevieria Pagoda)', 'Sansevieria mini pagoda yang ampuh menyerap polusi dan radiasi elektromagnetik di sekitar meja kerja.', 20000.00, 38000.00, 20, 'sansevieria-mini.jpg', 5.0),
(5, 3, 'Pilea Peperomioides (Chinese Money Plant)', 'Tanaman pembawa keberuntungan dengan daun bulat seperti koin. Sangat hits untuk dekorasi interior Skandinavia.', 30000.00, 55000.00, 15, 'pilea-mini.jpg', 4.9),
(6, 5, 'Fittonia Pink Mini (Nerve Plant)', 'Fittonia dengan corak urat daun berwarna pink cerah. Membawa nuansa hidup dan manis pada ruang belajar atau kamar.', 17000.00, 32000.00, 25, 'fittonia-pink.jpg', 4.7),
(7, 4, 'Sirih Gading Mini Pot Gantung/Meja', 'Sirih gading varigata yang segar dan mudah tumbuh. Tahan pada ruangan ber-AC dan minim cahaya.', 15000.00, 29000.00, 35, 'sirih-gading.jpg', 4.8),
(8, 3, 'Calathea Maranta Mini (Prayer Plant)', 'Calathea mini dengan corak garis artistik. Daunnya bergerak aktif mengikuti ritme siang dan malam.', 28000.00, 50000.00, 18, 'calathea-mini.jpg', 4.9)
ON CONFLICT (id) DO UPDATE SET nama_produk = EXCLUDED.nama_produk;

-- Sinkronisasi sequence autoincrement ID PostgreSQL
SELECT setval(pg_get_serial_sequence('categories', 'id'), COALESCE(MAX(id), 1)) FROM categories;
SELECT setval(pg_get_serial_sequence('admins', 'id'), COALESCE(MAX(id), 1)) FROM admins;
SELECT setval(pg_get_serial_sequence('users', 'id'), COALESCE(MAX(id), 1)) FROM users;
SELECT setval(pg_get_serial_sequence('products', 'id'), COALESCE(MAX(id), 1)) FROM products;
SELECT setval(pg_get_serial_sequence('carts', 'id'), COALESCE(MAX(id), 1)) FROM carts;
SELECT setval(pg_get_serial_sequence('orders', 'id'), COALESCE(MAX(id), 1)) FROM orders;
SELECT setval(pg_get_serial_sequence('order_details', 'id'), COALESCE(MAX(id), 1)) FROM order_details;
SELECT setval(pg_get_serial_sequence('payments', 'id'), COALESCE(MAX(id), 1)) FROM payments;
SELECT setval(pg_get_serial_sequence('product_reviews', 'id'), COALESCE(MAX(id), 1)) FROM product_reviews;

