<?php
/**
 * Konfigurasi Database & Helper Global
 * Proyek: Tanaman Hias Mini - PostgreSQL/Supabase & MySQL
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================================
// Load .env sederhana (untuk development lokal / docker)
// ============================================================
function loadEnv($path) {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, "\"'\t ");
        putenv("$key=$value");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}
loadEnv(__DIR__ . '/../.env');

// ============================================================
// Konfigurasi Database
// ============================================================
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: 6543);
define('DB_USER', getenv('DB_USER') ?: 'postgres');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'postgres');

/**
 * Mendapatkan koneksi PDO ke database
 * @return PDO|null
 */
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            // Deteksi otomatis driver database (MySQL jika port 3306, selain itu PostgreSQL)
            $driver = ((int)DB_PORT === 3306) ? 'mysql' : 'pgsql';

            if ($driver === 'mysql') {
                $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            } else {
                // Untuk Supabase / cloud PostgreSQL di luar localhost, aktifkan sslmode=require
                $ssl = (DB_HOST !== 'localhost' && DB_HOST !== '127.0.0.1') ? ';sslmode=require' : '';
                $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . $ssl;
            }

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

        } catch (PDOException $e) {
            die("
                <div style='font-family: sans-serif; max-width: 600px; margin: 50px auto; padding: 25px; border: 1px solid #f87171; background: #fef2f2; border-radius: 8px;'>
                    <h3 style='color: #b91c1c; margin-top: 0;'>Koneksi Database Gagal!</h3>
                    <p>Sistem tidak dapat terhubung ke database <strong>" . htmlspecialchars(DB_NAME) . "</strong> (" . htmlspecialchars(DB_HOST) . ").</p>
                    <p style='color: #4b5563; font-size: 14px;'><strong>Pesan Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
                </div>
            ");
        }
    }
    return $pdo;
}

// ============================================================
// Fungsi Helper Global
// ============================================================

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
    try {
        $db = getDB();
        if (!$db) return 0;
        $stmt = $db->prepare("SELECT SUM(jumlah) AS total FROM carts WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $res = $stmt->fetch();
        return (int)($res['total'] ?? 0);
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Sinkronisasi nilai rata-rata rating produk dari tabel product_reviews (ulasan asli pembeli)
 */
function syncProductRating($db, $productId) {
    try {
        $stmt = $db->prepare("SELECT COALESCE(ROUND(AVG(rating), 1), 0.0) AS avg_rating FROM product_reviews WHERE product_id = ?");
        $stmt->execute([(int)$productId]);
        $avg = (float)($stmt->fetchColumn() ?? 0.0);

        $stmtUp = $db->prepare("UPDATE products SET rating = ? WHERE id = ?");
        $stmtUp->execute([$avg, (int)$productId]);
        return $avg;
    } catch (Exception $e) {
        return 0.0;
    }
}