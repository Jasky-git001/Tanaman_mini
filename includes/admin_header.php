<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth_check.php';

// Pastikan Admin sudah login
requireAdminLogin();

$flash = getFlash();
$currentAdminPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($adminTitle) ? htmlspecialchars($adminTitle) . ' - ' : '' ?>Admin Dashboard | Tanaman Hias Mini</title>
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
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom Style -->
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-slate-100 text-slate-800 flex min-h-screen">

    <!-- Admin Sidebar -->
    <aside class="w-64 bg-slate-900 text-slate-300 flex-shrink-0 hidden md:flex flex-col border-r border-slate-800">
        <!-- Brand -->
        <div class="h-16 flex items-center gap-3 px-6 bg-slate-950/60 border-b border-slate-800/80">
            <div class="w-9 h-9 rounded-lg bg-brand-500 flex items-center justify-center text-white shadow-md shadow-brand-500/30">
                <i class="fa-solid fa-leaf"></i>
            </div>
            <div>
                <h1 class="text-base font-bold text-white tracking-wide">Panel Admin</h1>
                <p class="text-[11px] text-slate-400">Tanaman Hias Mini</p>
            </div>
        </div>

        <!-- Navigation Menu -->
        <div class="flex-1 py-6 px-4 space-y-1.5 overflow-y-auto">
            <div class="px-3 pb-2 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Navigasi Utama</div>
            
            <a href="index.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition <?= $currentAdminPage == 'index.php' ? 'bg-brand-600 text-white shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' ?>">
                <i class="fa-solid fa-gauge-high w-5 text-center"></i>
                <span>Dashboard</span>
            </a>

            <a href="products.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition <?= in_array($currentAdminPage, ['products.php', 'product_add.php', 'product_edit.php']) ? 'bg-brand-600 text-white shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' ?>">
                <i class="fa-solid fa-seedling w-5 text-center"></i>
                <span>Kelola Produk</span>
            </a>

            <a href="orders.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition <?= in_array($currentAdminPage, ['orders.php', 'order_detail.php']) ? 'bg-brand-600 text-white shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' ?>">
                <i class="fa-solid fa-box-archive w-5 text-center"></i>
                <span>Kelola Pesanan</span>
            </a>

            <a href="users.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition <?= $currentAdminPage == 'users.php' ? 'bg-brand-600 text-white shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' ?>">
                <i class="fa-solid fa-users w-5 text-center"></i>
                <span>Kelola User</span>
            </a>

            <div class="pt-4 px-3 pb-2 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Laporan Bisnis</div>

            <a href="reports.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition <?= $currentAdminPage == 'reports.php' ? 'bg-brand-600 text-white shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' ?>">
                <i class="fa-solid fa-chart-line w-5 text-center"></i>
                <span>Laporan Penjualan</span>
            </a>

            <div class="pt-4 px-3 pb-2 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Pengaturan</div>

            <a href="profile.php" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition <?= $currentAdminPage == 'profile.php' ? 'bg-brand-600 text-white shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' ?>">
                <i class="fa-solid fa-key w-5 text-center"></i>
                <span>Ganti Password</span>
            </a>

            <div class="pt-4 px-3 pb-2 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Tautan Cepat</div>

            <a href="../index.php" target="_blank" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-400 hover:text-white hover:bg-slate-800/60 transition">
                <i class="fa-solid fa-arrow-up-right-from-square w-5 text-center"></i>
                <span>Kunjungi Website</span>
            </a>
        </div>

        <!-- Admin Profile & Logout Bottom -->
        <div class="p-4 border-t border-slate-800/80 bg-slate-950/40">
            <div class="flex items-center justify-between">
                <a href="profile.php" class="flex items-center gap-3 overflow-hidden group hover:opacity-90 transition">
                    <div class="w-9 h-9 rounded-lg bg-brand-700 text-white flex items-center justify-center font-bold text-sm flex-shrink-0 group-hover:bg-brand-600 transition">
                        A
                    </div>
                    <div class="overflow-hidden">
                        <p class="text-sm font-semibold text-white truncate group-hover:text-brand-300 transition"><?= htmlspecialchars($_SESSION['admin_nama'] ?? 'Administrator') ?></p>
                        <p class="text-[11px] text-emerald-400 flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span> Online
                        </p>
                    </div>
                </a>
                <div class="flex items-center gap-1">
                    <a href="profile.php" title="Pengaturan & Ganti Password" class="text-slate-400 hover:text-brand-400 p-2 rounded-lg hover:bg-slate-800 transition">
                        <i class="fa-solid fa-gear"></i>
                    </a>
                    <a href="logout.php" title="Keluar" class="text-slate-400 hover:text-red-400 p-2 rounded-lg hover:bg-slate-800 transition">
                        <i class="fa-solid fa-right-from-bracket"></i>
                    </a>
                </div>
            </div>
        </div>
    </aside>

    <!-- Main Content Area Wrapper -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
        
        <!-- Admin Topbar -->
        <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-4 sm:px-6 z-10">
            <div class="flex items-center gap-3">
                <!-- Mobile Toggle -->
                <button type="button" id="adminMobileBtn" class="md:hidden p-2 rounded-lg text-slate-600 hover:bg-slate-100">
                    <i class="fa-solid fa-bars text-lg"></i>
                </button>
                <h2 class="text-base sm:text-lg font-bold text-slate-800">
                    <?= $adminTitle ?? 'Dashboard' ?>
                </h2>
            </div>

            <div class="flex items-center gap-3">
                <span class="hidden sm:inline-flex text-xs text-slate-500 font-medium bg-slate-100 px-3 py-1.5 rounded-lg">
                    <i class="fa-regular fa-calendar mr-1.5 text-brand-600"></i> <?= date('d M Y') ?>
                </span>
                <a href="../index.php" target="_blank" class="text-xs font-semibold text-brand-700 bg-brand-50 hover:bg-brand-100 px-3 py-1.5 rounded-lg border border-brand-200 transition flex items-center gap-1.5">
                    <i class="fa-solid fa-store"></i> <span class="hidden sm:inline">Lihat Toko</span>
                </a>
            </div>
        </header>

        <!-- Admin Mobile Navigation (Hidden by default) -->
        <div id="adminMobileMenu" class="hidden md:hidden bg-slate-900 text-slate-300 px-4 py-3 border-b border-slate-800 space-y-1">
            <a href="index.php" class="block px-3 py-2 rounded-lg text-sm <?= $currentAdminPage == 'index.php' ? 'bg-brand-600 text-white' : 'hover:bg-slate-800' ?>">Dashboard</a>
            <a href="products.php" class="block px-3 py-2 rounded-lg text-sm <?= $currentAdminPage == 'products.php' ? 'bg-brand-600 text-white' : 'hover:bg-slate-800' ?>">Kelola Produk</a>
            <a href="orders.php" class="block px-3 py-2 rounded-lg text-sm <?= $currentAdminPage == 'orders.php' ? 'bg-brand-600 text-white' : 'hover:bg-slate-800' ?>">Kelola Pesanan</a>
            <a href="users.php" class="block px-3 py-2 rounded-lg text-sm <?= $currentAdminPage == 'users.php' ? 'bg-brand-600 text-white' : 'hover:bg-slate-800' ?>">Kelola User</a>
            <a href="reports.php" class="block px-3 py-2 rounded-lg text-sm <?= $currentAdminPage == 'reports.php' ? 'bg-brand-600 text-white' : 'hover:bg-slate-800' ?>">Laporan Penjualan</a>
            <a href="profile.php" class="block px-3 py-2 rounded-lg text-sm <?= $currentAdminPage == 'profile.php' ? 'bg-brand-600 text-white' : 'hover:bg-slate-800' ?>">Ganti Password</a>
            <a href="logout.php" class="block px-3 py-2 rounded-lg text-sm text-red-400 hover:bg-slate-800">Keluar</a>
        </div>

        <!-- Flash Alert in Admin -->
        <?php if ($flash): ?>
            <div class="mx-4 sm:mx-6 mt-4">
                <?php 
                    $bg = 'bg-brand-50 border-brand-200 text-brand-800';
                    $icon = 'fa-circle-check text-brand-600';
                    if ($flash['type'] === 'error') {
                        $bg = 'bg-red-50 border-red-200 text-red-800';
                        $icon = 'fa-circle-xmark text-red-600';
                    } elseif ($flash['type'] === 'warning') {
                        $bg = 'bg-amber-50 border-amber-200 text-amber-800';
                        $icon = 'fa-triangle-exclamation text-amber-600';
                    }
                ?>
                <div class="flex items-center gap-3 p-3.5 rounded-xl border <?= $bg ?> text-sm shadow-sm">
                    <i class="fa-solid <?= $icon ?> flex-shrink-0"></i>
                    <div class="flex-1 font-medium"><?= $flash['message'] ?></div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Page Content Scroll Area -->
        <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">

