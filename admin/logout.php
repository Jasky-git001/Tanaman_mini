<?php
require_once __DIR__ . '/../config/database.php';

// Hapus Sesi Admin secara menyeluruh
unset($_SESSION['admin_id']);
unset($_SESSION['admin_username']);
unset($_SESSION['admin_nama']);
unset($_SESSION['admin_logged_in']);

setFlash('info', 'Anda telah keluar dari sesi Admin.');
header("Location: login.php");
exit;
