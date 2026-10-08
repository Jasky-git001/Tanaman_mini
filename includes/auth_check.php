<?php
/**
 * Middleware Autentikasi dan Otorisasi Sederhana
 * Proyek: Tanaman Hias Mini
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Memeriksa apakah User (Pembeli) sudah login.
 * Jika Admin mencoba mengakses halaman transaksi pembeli, blokir akses.
 */
function requireUserLogin() {
    if (isAdminLoggedIn()) {
        $_SESSION['flash'] = [
            'type' => 'warning',
            'message' => 'Mode Pratinjau Admin aktif: Akun Administrator tidak dapat mengakses keranjang atau melakukan checkout barang.'
        ];
        header("Location: index.php");
        exit;
    }

    if (!isset($_SESSION['user_id'])) {
        $_SESSION['flash'] = [
            'type' => 'warning',
            'message' => 'Silakan login sebagai Pembeli terlebih dahulu untuk mengakses keranjang dan melakukan transaksi.'
        ];
        $currentUrl = urlencode($_SERVER['REQUEST_URI'] ?? 'index.php');
        header("Location: login.php?redirect=" . $currentUrl);
        exit;
    }
}

/**
 * Memeriksa apakah Admin sudah login.
 * Jika belum, alihkan ke halaman login admin.
 */
function requireAdminLogin() {
    if (!isset($_SESSION['admin_id'])) {
        $_SESSION['flash'] = [
            'type' => 'error',
            'message' => 'Anda harus login sebagai Admin untuk mengakses panel ini.'
        ];
        header("Location: login.php");
        exit;
    }
}

/**
 * Memeriksa status login saat ini (Sesi Pembeli dan Admin dipisahkan secara tegas)
 */
function isUserLoggedIn() {
    return isset($_SESSION['user_id']) && !isset($_SESSION['admin_id']);
}

function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}