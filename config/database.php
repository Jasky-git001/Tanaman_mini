<?php
/**
 * Konfigurasi Database & Helper Global
 * Proyek: Tanaman Hias Mini - Digital Entrepreneurship
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Konfigurasi Database XAMPP Default
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'tanaman_mini_db');
define('DB_PORT', 3306);

/**
 * Mendapatkan koneksi PDO ke database
 * @return PDO|null
 */
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

            // Auto-migration untuk fitur Rating Pembeli, Gambar JPG, dan Status Pengembalian Pesanan
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `product_reviews` (
                  `id` INT AUTO_INCREMENT PRIMARY KEY,
                  `product_id` INT NOT NULL,
                  `user_id` INT NOT NULL,
                  `order_id` INT DEFAULT NULL,
                  `rating` TINYINT NOT NULL,
                  `komentar` TEXT NOT NULL,
                  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                  UNIQUE KEY `unique_user_product_review` (`user_id`, `product_id`),
                  CONSTRAINT `fk_reviews_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
                  CONSTRAINT `fk_reviews_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");

            // Pastikan kolom status_pesanan mendukung Pengembalian dan kolom alasan_pengembalian tersedia
            $pdo->exec("
                ALTER TABLE `orders` 
                MODIFY COLUMN `status_pesanan` ENUM(
                    'Menunggu Pembayaran',
                    'Pembayaran Berhasil',
                    'Diproses',
                    'Dikemas',
                    'Dikirim',
                    'Selesai',
                    'Pengembalian Diajukan',
                    'Pengembalian Disetujui',
                    'Pengembalian Ditolak',
                    'Dibatalkan'
                ) DEFAULT 'Menunggu Pembayaran'
            ");

            $colCheck = $pdo->query("SHOW COLUMNS FROM `orders` LIKE 'alasan_pengembalian'")->fetch();
            if (!$colCheck) {
                $pdo->exec("ALTER TABLE `orders` ADD COLUMN `alasan_pengembalian` TEXT DEFAULT NULL AFTER `catatan`");
            }

            // Pastikan ekstensi foto produk menggunakan .jpg
            $pdo->exec("UPDATE `products` SET `foto` = REPLACE(`foto`, '.svg', '.jpg') WHERE `foto` LIKE '%.svg'");

            // Hapus ulasan yang berasal dari pesanan yang statusnya BELUM 'Selesai' agar konsisten
            $pdo->exec("
                DELETE r FROM `product_reviews` r
                LEFT JOIN `orders` o ON r.`order_id` = o.`id`
                WHERE o.`status_pesanan` IS NULL OR o.`status_pesanan` != 'Selesai'
            ");

            // Seed ulasan awal hanya untuk Order #1 (yang statusnya memang sudah 'Selesai')
            $revCountCheck = (int)$pdo->query("SELECT COUNT(*) FROM `product_reviews`")->fetchColumn();
            if ($revCountCheck === 0) {
                $ord1 = $pdo->query("SELECT id FROM `orders` WHERE id = 1 AND status_pesanan = 'Selesai'")->fetch();
                if ($ord1) {
                    $pdo->exec("
                        INSERT IGNORE INTO `product_reviews` (`id`, `product_id`, `user_id`, `order_id`, `rating`, `komentar`, `created_at`) VALUES
                        (1, 1, 1, 1, 5, 'Monstera mininya segar banget waktu sampai! Daunnya rimbun sesuai request dan pot keramiknya rapi untuk di meja kerja.', '2026-09-02 14:20:00'),
                        (2, 2, 1, 1, 5, 'Kaktus koboinya sehat, pengemasan aman banget pakai bubble wrap tebal dan moss. Sangat puas!', '2026-09-02 14:25:00')
                    ");
                }
            }

            // Sinkronisasi seluruh rating produk dengan rata-rata ulasan dari pesanan yang sudah Selesai
            $pdo->exec("UPDATE `products` p SET p.`rating` = COALESCE((SELECT ROUND(AVG(r.`rating`), 1) FROM `product_reviews` r WHERE r.`product_id` = p.`id`), 0.0)");

        } catch (PDOException $e) {
            die("
                <div style='font-family: sans-serif; max-width: 600px; margin: 50px auto; padding: 25px; border: 1px solid #f87171; background: #fef2f2; border-radius: 8px;'>
                    <h3 style='color: #b91c1c; margin-top: 0;'>Koneksi Database Gagal!</h3>
                    <p>Sistem tidak dapat terhubung ke database <strong>" . DB_NAME . "</strong>.</p>
                    <p style='color: #4b5563; font-size: 14px;'><strong>Pesan Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
                    <hr style='border: none; border-top: 1px solid #fca5a5; margin: 15px 0;'>
                    <p style='font-size: 14px;'><strong>Petunjuk Mahasiswa:</strong></p>
                    <ol style='font-size: 14px; color: #374151; padding-left: 20px;'>
                        <li>Buka <strong>XAMPP Control Panel</strong> dan klik <strong>Start</strong> pada MySQL dan Apache.</li>
                        <li>Buka browser ke <code>http://localhost/phpmyadmin</code>.</li>
                        <li>Buat database baru bernama <code>" . DB_NAME . "</code>.</li>
                        <li>Impor file <code>database/tanaman_mini.sql</code> ke dalam database tersebut.</li>
                    </ol>
                </div>
            ");
        }
    }
    return $pdo;
}

/**
 * Format angka ke mata uang Rupiah
 */
function formatRupiah($angka) {
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}

/**
 * Sanitasi input teks
 */
function sanitize($data) {
    return htmlspecialchars(trim($data ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Set pesan notifikasi flash
 */
function setFlash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Ambil dan bersihkan pesan flash
 */
function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Menghitung total item di keranjang user yang sedang login (Admin tidak memiliki keranjang)
 */
function getCartCount() {
    if (isset($_SESSION['admin_id']) || !isset($_SESSION['user_id'])) {
        return 0;
    }
    $db = getDB();
    $stmt = $db->prepare("SELECT SUM(jumlah) AS total FROM carts WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $res = $stmt->fetch();
    return (int)($res['total'] ?? 0);
}

/**
 * Sinkronisasi nilai rata-rata rating produk dari tabel product_reviews (ulasan asli pembeli)
 */
function syncProductRating($db, $productId) {
    $stmt = $db->prepare("SELECT COALESCE(ROUND(AVG(rating), 1), 0.0) AS avg_rating FROM product_reviews WHERE product_id = ?");
    $stmt->execute([(int)$productId]);
    $avg = (float)($stmt->fetchColumn() ?? 0.0);

    $stmtUp = $db->prepare("UPDATE products SET rating = ? WHERE id = ?");
    $stmtUp->execute([$avg, (int)$productId]);
    return $avg;
}