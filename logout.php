<?php
/**
 * Logout Pembeli & Admin
 */
require_once __DIR__ . '/config/database.php';

// Hapus Sesi User / Pembeli
unset($_SESSION['user_id']);
unset($_SESSION['user_nama']);
unset($_SESSION['user_email']);

// Hapus Sesi Admin (Agar jika admin logout dari header toko, sesinya terhapus bersih)
unset($_SESSION['admin_id']);
unset($_SESSION['admin_username']);
unset($_SESSION['admin_nama']);
unset($_SESSION['admin_logged_in']);

setFlash('info', 'Anda telah berhasil keluar dari akun.');
header("Location: index.php");
exit;
