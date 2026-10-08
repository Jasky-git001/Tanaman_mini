<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';
$cartCount = getCartCount();
$flash = getFlash();
$base_path = $base_path ?? '';
$currentPage = basename($_SERVER['PHP_SELF']);

// Cek apakah yang login adalah Admin
$isAdmin = function_exists('isAdminLoggedIn') ? isAdminLoggedIn() : (isset($_SESSION['admin_logged_in']) || isset($_SESSION['admin_id']));
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : '' ?>Tanaman Hias Mini | Toko Online Tanaman Hias</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#f0fdf4',
                            100: '#dcfce7',
                            200: '#bbf7d0',
                            300: '#86efac',
                            400: '#4ade80',
                            500: '#22c55e',
                            600: '#16a34a',
                            700: '#15803d',
                            800: '#166534',
                            900: '#14532d',
                        }
                    }
                }
            }
        }
    </script>
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Custom Style -->
    <link rel="stylesheet" href="<?= $base_path ?>assets/css/style.css">
    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-slate-50 text-slate-800 flex flex-col min-h-screen">

    <!-- Top Announcement Bar -->
    <div class="bg-brand-900 text-brand-100 text-xs py-2 px-4 text-center font-medium flex items-center justify-center gap-2">
        <i class="fa-solid fa-truck-fast"></i>
        <span>Pengiriman Aman ke Seluruh Indonesia • Garansi Tanaman Segar Sampai Rumah</span>
    </div>

    <!-- Main Navigation Bar -->
    <nav class="bg-white border-b border-slate-100 sticky top-0 z-50 shadow-sm" id="navbar">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-18 py-3">
                
                <!-- Logo & Brand Name -->
                <a href="<?= $base_path ?>index.php" class="flex items-center gap-3 group">
                    <div class="w-10 h-10 rounded-xl bg-brand-600 flex items-center justify-center text-white shadow-md shadow-brand-500/20 group-hover:bg-brand-700 transition">
                        <i class="fa-solid fa-seedling text-lg"></i>
                    </div>
                    <div>
                        <span class="text-xl font-extrabold text-slate-900 tracking-tight block leading-tight">Tanaman Hias <span class="text-brand-600">Mini</span></span>
                        <span class="text-[11px] text-slate-500 block font-normal -mt-0.5">Green Living & Urban Decor</span>
                    </div>
                </a>

                <!-- Desktop Navigation Links -->
                <div class="hidden md:flex items-center space-x-1 lg:space-x-2 text-sm font-medium">
                    <a href="<?= $base_path ?>index.php" class="px-3.5 py-2 rounded-lg transition <?= $currentPage == 'index.php' ? 'text-brand-600 font-semibold bg-brand-50' : 'text-slate-600 hover:text-brand-600 hover:bg-slate-50' ?>">Home</a>
                    <a href="<?= $base_path ?>products.php" class="px-3.5 py-2 rounded-lg transition <?= $currentPage == 'products.php' ? 'text-brand-600 font-semibold bg-brand-50' : 'text-slate-600 hover:text-brand-600 hover:bg-slate-50' ?>">Produk</a>
                    <a href="<?= $base_path ?>index.php#tentang" class="px-3.5 py-2 rounded-lg text-slate-600 hover:text-brand-600 hover:bg-slate-50 transition">Tentang Kami</a>
                    <a href="<?= $base_path ?>index.php#kontak" class="px-3.5 py-2 rounded-lg text-slate-600 hover:text-brand-600 hover:bg-slate-50 transition">Kontak</a>
                    <?php if (!$isAdmin && isset($_SESSION['user_id'])): ?>
                        <a href="<?= $base_path ?>orders.php" class="px-3.5 py-2 rounded-lg transition <?= $currentPage == 'orders.php' ? 'text-brand-600 font-semibold bg-brand-50' : 'text-slate-600 hover:text-brand-600 hover:bg-slate-50' ?>">Riwayat Pesanan</a>
                    <?php endif; ?>
                </div>

                <!-- Right Header Actions (Cart & Auth) -->
                <div class="flex items-center space-x-3">
                    <?php if (!$isAdmin): ?>
                        <!-- Cart Button (Hanya untuk Pembeli / Guest, tidak untuk Admin) -->
                        <a href="<?= $base_path ?>cart.php" class="relative p-2.5 rounded-xl text-slate-700 hover:text-brand-600 hover:bg-slate-100 transition flex items-center justify-center" title="Keranjang Belanja">
                            <i class="fa-solid fa-bag-shopping text-xl"></i>
                            <?php if ($cartCount > 0): ?>
                                <span class="absolute -top-1 -right-1 bg-brand-600 text-white text-[11px] font-bold w-5 h-5 rounded-full flex items-center justify-center shadow">
                                    <?= $cartCount ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    <?php endif; ?>

                    <!-- Auth State: Admin vs User vs Guest -->
                    <?php if ($isAdmin): ?>
                        <!-- Admin Action (Mode Pratinjau Etalase - Tanpa Akses Checkout) -->
                        <div class="hidden md:flex items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 text-[11px] font-bold text-amber-800 bg-amber-50 border border-amber-200 px-3 py-1.5 rounded-lg">
                                <i class="fa-solid fa-eye text-amber-600"></i> Mode Pratinjau Admin
                            </span>
                            <a href="<?= $base_path ?>admin/index.php" class="inline-flex items-center gap-2 text-xs font-bold text-white bg-amber-600 hover:bg-amber-700 px-3.5 py-2 rounded-xl transition shadow-sm">
                                <i class="fa-solid fa-user-shield"></i>
                                <span>Panel Admin</span>
                            </a>
                            <a href="<?= $base_path ?>logout.php" class="text-xs font-bold text-rose-600 hover:text-rose-700 px-3 py-2 rounded-lg hover:bg-rose-50 transition">
                                Logout
                            </a>
                        </div>
                    <?php elseif (isset($_SESSION['user_id'])): ?>
                        <!-- User Profile Dropdown -->
                        <div class="relative">
                            <button id="userMenuBtn" type="button" class="flex items-center gap-2 text-sm font-semibold text-slate-700 hover:text-brand-600 p-1.5 rounded-xl border border-slate-200 hover:border-brand-300 transition bg-slate-50/50">
                                <div class="w-8 h-8 rounded-lg bg-brand-100 text-brand-700 flex items-center justify-center font-bold">
                                    <?= strtoupper(substr($_SESSION['user_nama'] ?? 'U', 0, 1)) ?>
                                </div>
                                <span class="hidden sm:inline max-w-[120px] truncate"><?= htmlspecialchars($_SESSION['user_nama'] ?? 'User') ?></span>
                                <i class="fa-solid fa-chevron-down text-xs text-slate-400"></i>
                            </button>

                            <!-- Dropdown Menu -->
                            <div id="userDropdown" class="hidden absolute right-0 mt-2 w-52 bg-white rounded-xl shadow-lg border border-slate-100 py-2 z-50">
                                <div class="px-4 py-2 border-b border-slate-100">
                                    <p class="text-xs text-slate-400">Login sebagai</p>
                                    <p class="text-sm font-bold text-slate-800 truncate"><?= htmlspecialchars($_SESSION['user_nama'] ?? '') ?></p>
                                    <p class="text-xs text-slate-500 truncate"><?= htmlspecialchars($_SESSION['user_email'] ?? '') ?></p>
                                </div>
                                <a href="<?= $base_path ?>profile.php" class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 hover:text-brand-600 transition">
                                    <i class="fa-regular fa-user text-slate-400 w-4"></i> Profil Saya
                                </a>
                                <a href="<?= $base_path ?>orders.php" class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 hover:text-brand-600 transition">
                                    <i class="fa-solid fa-receipt text-slate-400 w-4"></i> Riwayat Pesanan
                                </a>
                                <div class="border-t border-slate-100 my-1"></div>
                                <a href="<?= $base_path ?>logout.php" class="flex items-center gap-2 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition">
                                    <i class="fa-solid fa-arrow-right-from-bracket text-red-500 w-4"></i> Keluar
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Guest Buttons -->
                        <div class="hidden sm:flex items-center space-x-2">
                            <a href="<?= $base_path ?>login.php" class="text-sm font-semibold text-slate-700 hover:text-brand-600 px-3.5 py-2 rounded-lg hover:bg-slate-100 transition">
                                Masuk
                            </a>
                            <a href="<?= $base_path ?>register.php" class="text-sm font-semibold text-white bg-brand-600 hover:bg-brand-700 px-4 py-2 rounded-xl shadow-sm hover:shadow transition">
                                Daftar
                            </a>
                        </div>
                    <?php endif; ?>

                    <!-- Mobile Menu Hamburger -->
                    <button id="mobileMenuBtn" type="button" class="md:hidden p-2 rounded-lg text-slate-600 hover:bg-slate-100 focus:outline-none">
                        <i class="fa-solid fa-bars text-xl"></i>
                    </button>
                </div>
            </div>

            <!-- Mobile Navigation Menu -->
            <div id="mobileMenu" class="hidden md:hidden py-3 border-t border-slate-100 space-y-1">
                <a href="<?= $base_path ?>index.php" class="block px-3 py-2 rounded-lg text-base font-medium text-slate-700 hover:bg-brand-50 hover:text-brand-600">Home</a>
                <a href="<?= $base_path ?>products.php" class="block px-3 py-2 rounded-lg text-base font-medium text-slate-700 hover:bg-brand-50 hover:text-brand-600">Produk</a>
                <a href="<?= $base_path ?>index.php#tentang" class="block px-3 py-2 rounded-lg text-base font-medium text-slate-700 hover:bg-brand-50 hover:text-brand-600">Tentang Kami</a>
                <a href="<?= $base_path ?>index.php#kontak" class="block px-3 py-2 rounded-lg text-base font-medium text-slate-700 hover:bg-brand-50 hover:text-brand-600">Kontak</a>
                
                <?php if ($isAdmin): ?>
                    <a href="<?= $base_path ?>admin/index.php" class="block px-3 py-2 rounded-lg text-base font-semibold text-amber-800 bg-amber-50 hover:bg-amber-100">
                        <i class="fa-solid fa-user-shield mr-2"></i> Panel Admin
                    </a>
                    <a href="<?= $base_path ?>logout.php" class="block px-3 py-2 rounded-lg text-base font-medium text-red-600 hover:bg-red-50">Logout</a>
                <?php elseif (isset($_SESSION['user_id'])): ?>
                    <a href="<?= $base_path ?>orders.php" class="block px-3 py-2 rounded-lg text-base font-medium text-slate-700 hover:bg-brand-50 hover:text-brand-600">Riwayat Pesanan</a>
                    <a href="<?= $base_path ?>profile.php" class="block px-3 py-2 rounded-lg text-base font-medium text-slate-700 hover:bg-brand-50 hover:text-brand-600">Profil Saya</a>
                    <a href="<?= $base_path ?>logout.php" class="block px-3 py-2 rounded-lg text-base font-medium text-red-600 hover:bg-red-50">Keluar</a>
                <?php else: ?>
                    <div class="pt-2 border-t border-slate-100 flex gap-2">
                        <a href="<?= $base_path ?>login.php" class="w-1/2 text-center py-2.5 rounded-lg border border-slate-200 text-slate-700 font-semibold text-sm">Masuk</a>
                        <a href="<?= $base_path ?>register.php" class="w-1/2 text-center py-2.5 rounded-lg bg-brand-600 text-white font-semibold text-sm">Daftar</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Flash Alert Notification Banner -->
    <?php if ($flash): ?>
        <div id="flashNotification" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <?php 
                $bgClass = 'bg-brand-50 border-brand-200 text-brand-800';
                $iconClass = 'fa-circle-check text-brand-600';
                if ($flash['type'] === 'error') {
                    $bgClass = 'bg-red-50 border-red-200 text-red-800';
                    $iconClass = 'fa-circle-xmark text-red-600';
                } elseif ($flash['type'] === 'warning') {
                    $bgClass = 'bg-amber-50 border-amber-200 text-amber-800';
                    $iconClass = 'fa-triangle-exclamation text-amber-600';
                } elseif ($flash['type'] === 'info') {
                    $bgClass = 'bg-blue-50 border-blue-200 text-blue-800';
                    $iconClass = 'fa-circle-info text-blue-600';
                }
            ?>
            <div class="flex items-center gap-3 p-4 rounded-xl border <?= $bgClass ?> shadow-sm">
                <i class="fa-solid <?= $iconClass ?> text-lg flex-shrink-0"></i>
                <div class="text-sm font-medium flex-1"><?= $flash['message'] ?></div>
                <button type="button" onclick="document.getElementById('flashNotification').remove()" class="text-slate-400 hover:text-slate-600">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>
    <?php endif; ?>

    <!-- Main Content Container Wrapper -->
    <main class="flex-grow">
