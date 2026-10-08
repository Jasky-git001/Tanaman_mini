<?php
// Konfigurasi Database untuk Render + Supabase
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'postgres');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'postgres');
define('DB_PORT', getenv('DB_PORT') ?: 5432); // Port default PostgreSQL

// ... (sisa kode helper lainnya tetap sama)

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            // Ubah DSN dari mysql ke pgsql
            $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            
            // HAPUS semua kode auto-migration MySQL di sini.
            // (Migrasi tabel product_reviews, alasan_pengembalian, dll. 
            //  harus dijalankan manual via SQL Editor Supabase)

        } catch (PDOException $e) {
            // ... (blok catch error tetap sama)
        }
    }
    return $pdo;
}