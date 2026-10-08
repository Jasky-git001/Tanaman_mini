<?php
$adminTitle = 'Dashboard Ringkasan Bisnis';
require_once __DIR__ . '/../includes/admin_header.php';

$db = getDB();

// 1. Metrik Statistik
$totalProduk = (int)$db->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalUser = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalPesanan = (int)$db->query("SELECT COUNT(*) FROM orders")->fetchColumn();

// Omset Penjualan (Hanya pesanan yang sudah dibayar)
$totalPenjualan = (float)$db->query("
    SELECT COALESCE(SUM(total_harga), 0) 
    FROM orders 
    WHERE status_pembayaran = 'Pembayaran Berhasil'
")->fetchColumn();

// Total Modal Barang Terjual
$totalModal = (float)$db->query("
    SELECT COALESCE(SUM(od.harga_modal * od.jumlah), 0) 
    FROM order_details od 
    JOIN orders o ON od.order_id = o.id 
    WHERE o.status_pembayaran = 'Pembayaran Berhasil'
")->fetchColumn();

// Laba Bersih = Penjualan - Modal
$totalLaba = $totalPenjualan - $totalModal;

// 2. Produk Terlaris (Best Seller)
$stmtBest = $db->query("
    SELECT p.id, p.nama_produk, p.foto, p.stok, p.harga_jual, COALESCE(SUM(od.jumlah), 0) AS total_terjual 
    FROM order_details od 
    JOIN products p ON od.product_id = p.id 
    JOIN orders o ON od.order_id = o.id 
    WHERE o.status_pembayaran = 'Pembayaran Berhasil' 
    GROUP BY p.id 
    ORDER BY total_terjual DESC 
    LIMIT 5
");
$bestSellers = $stmtBest->fetchAll();

// 3. Pesanan Terbaru Masuk
$stmtRecent = $db->query("
    SELECT o.*, u.nama 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    ORDER BY o.id DESC 
    LIMIT 5
");
$recentOrders = $stmtRecent->fetchAll();

// 4. Data Penjualan 7 Hari Terakhir untuk Chart.js
$chartLabels = [];
$chartData = [];
for ($i = 6; $i >= 0; $i--) {
    $tgl = date('Y-m-d', strtotime("-$i days"));
    $chartLabels[] = date('d M', strtotime($tgl));

    $stmtDay = $db->prepare("
        SELECT COALESCE(SUM(total_harga), 0) 
        FROM orders 
        WHERE status_pembayaran = 'Pembayaran Berhasil' AND DATE(tanggal) = ?
    ");
    $stmtDay->execute([$tgl]);
    $chartData[] = (float)$stmtDay->fetchColumn();
}
?>

<div class="space-y-8">
    
    <!-- Welcome Header Banner -->
    <div class="bg-gradient-to-r from-brand-800 via-brand-700 to-emerald-600 rounded-3xl p-6 sm:p-8 text-white shadow-lg flex flex-col md:flex-row items-center justify-between gap-6">
        <div class="space-y-2 text-center md:text-left">
            <!-- Badge Header Dashboard Admin -->
            <span class="inline-block text-xs font-bold bg-white/20 px-3.5 py-1 rounded-full uppercase tracking-wider text-white">
                PANEL ADMINISTRASI UTAMA
            </span>
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                Halo, <?= htmlspecialchars($_SESSION['admin_nama'] ?? 'Admin') ?>! 👋
            </h1>
            <p class="text-xs sm:text-sm text-brand-100 max-w-xl leading-relaxed">
                Pantau performa penjualan tanaman hias mini, kelola stok tanaman, dan verifikasi transaksi pesanan pelanggan secara real-time.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="product_add.php" class="inline-flex items-center gap-2 px-5 py-3 rounded-2xl bg-white text-brand-800 hover:bg-brand-50 font-bold text-xs shadow-md transition">
                <i class="fa-solid fa-plus"></i> Tambah Produk Baru
            </a>
            <a href="reports.php" class="inline-flex items-center gap-2 px-5 py-3 rounded-2xl bg-brand-900/40 border border-white/20 text-white hover:bg-brand-900/60 font-bold text-xs transition">
                <i class="fa-solid fa-file-invoice-dollar"></i> Laporan
            </a>
        </div>
    </div>

    <!-- 6 KPI Stat Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
        
        <!-- Total Penjualan (Revenue) -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between text-slate-400 mb-2">
                <span class="text-xs font-bold uppercase tracking-wider">Total Penjualan</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-wallet"></i>
                </div>
            </div>
            <div>
                <span class="text-xl font-extrabold text-slate-900 block"><?= formatRupiah($totalPenjualan) ?></span>
                <span class="text-[11px] text-emerald-600 font-medium">Omset pesanan lunas</span>
            </div>
        </div>

        <!-- Total Modal -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between text-slate-400 mb-2">
                <span class="text-xs font-bold uppercase tracking-wider">Total Modal</span>
                <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-receipt"></i>
                </div>
            </div>
            <div>
                <span class="text-xl font-extrabold text-slate-900 block"><?= formatRupiah($totalModal) ?></span>
                <span class="text-[11px] text-amber-600 font-medium">HPP barang terjual</span>
            </div>
        </div>

        <!-- Total Laba Bersih -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between text-slate-400 mb-2">
                <span class="text-xs font-bold uppercase tracking-wider">Laba Bersih</span>
                <div class="w-8 h-8 rounded-lg bg-brand-50 text-brand-600 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-coins"></i>
                </div>
            </div>
            <div>
                <span class="text-xl font-extrabold text-brand-700 block"><?= formatRupiah($totalLaba) ?></span>
                <span class="text-[11px] text-brand-600 font-medium">Penjualan - Modal</span>
            </div>
        </div>

        <!-- Total Pesanan -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between text-slate-400 mb-2">
                <span class="text-xs font-bold uppercase tracking-wider">Pesanan</span>
                <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-box"></i>
                </div>
            </div>
            <div>
                <span class="text-xl font-extrabold text-slate-900 block"><?= $totalPesanan ?></span>
                <span class="text-[11px] text-blue-600 font-medium">Total transaksi masuk</span>
            </div>
        </div>

        <!-- Total Produk -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between text-slate-400 mb-2">
                <span class="text-xs font-bold uppercase tracking-wider">Produk</span>
                <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-seedling"></i>
                </div>
            </div>
            <div>
                <span class="text-xl font-extrabold text-slate-900 block"><?= $totalProduk ?></span>
                <span class="text-[11px] text-purple-600 font-medium">Varian tanaman hias</span>
            </div>
        </div>

        <!-- Total User -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between text-slate-400 mb-2">
                <span class="text-xs font-bold uppercase tracking-wider">Pengguna</span>
                <div class="w-8 h-8 rounded-lg bg-cyan-50 text-cyan-600 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-users"></i>
                </div>
            </div>
            <div>
                <span class="text-xl font-extrabold text-slate-900 block"><?= $totalUser ?></span>
                <span class="text-[11px] text-cyan-600 font-medium">Pembeli terdaftar</span>
            </div>
        </div>

    </div>

    <!-- Chart & Best Sellers Section -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- Left: Sales Trend Chart (7 days) -->
        <div class="lg:col-span-7 bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                        <i class="fa-solid fa-chart-area text-brand-600"></i> Tren Penjualan 7 Hari Terakhir
                    </h2>
                    <p class="text-xs text-slate-400 mt-0.5">Grafik pergerakan omset pesanan lunas</p>
                </div>
                <span class="text-xs font-semibold text-brand-600 bg-brand-50 px-2.5 py-1 rounded-lg">
                    Realtime Data
                </span>
            </div>

            <div class="h-64 sm:h-72">
                <canvas id="salesChart"></canvas>
            </div>
        </div>

        <!-- Right: Best Seller Products -->
        <div class="lg:col-span-5 bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm flex flex-col">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-fire text-amber-500"></i> Produk Terlaris
                </h2>
                <a href="products.php" class="text-xs text-brand-600 hover:underline font-semibold">Semua Produk</a>
            </div>

            <div class="divide-y divide-slate-100 flex-1">
                <?php if (!empty($bestSellers)): ?>
                    <?php foreach ($bestSellers as $bs): ?>
                        <div class="py-3 flex items-center gap-3">
                            <div class="w-11 h-11 rounded-xl bg-slate-100 overflow-hidden flex-shrink-0 border border-slate-100">
                                <img src="../assets/images/products/<?= htmlspecialchars($bs['foto']) ?>" 
                                     alt="<?= htmlspecialchars($bs['nama_produk']) ?>" 
                                     class="w-full h-full object-cover"
                                     onerror="this.src='../assets/images/products/default.jpg'">
                            </div>
                            <div class="flex-1 min-w-0">
                                <h4 class="text-xs font-bold text-slate-800 truncate"><?= htmlspecialchars($bs['nama_produk']) ?></h4>
                                <span class="text-[11px] text-slate-500"><?= formatRupiah($bs['harga_jual']) ?> • Stok: <?= $bs['stok'] ?></span>
                            </div>
                            <span class="text-xs font-bold bg-amber-50 text-amber-800 px-2.5 py-1 rounded-lg">
                                <?= $bs['total_terjual'] ?> terjual
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="py-8 text-center text-xs text-slate-400">
                        Belum ada riwayat penjualan produk.
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- Recent Orders Table -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-clock-rotate-left text-brand-600"></i> Pesanan Terbaru
                </h2>
                <p class="text-xs text-slate-400 mt-0.5">Daftar transaksi yang baru saja masuk</p>
            </div>
            <a href="orders.php" class="text-xs font-bold text-brand-600 hover:underline">
                Lihat Semua Pesanan &rarr;
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100 text-slate-500 font-bold uppercase tracking-wider">
                        <th class="py-3 px-5">Invoice</th>
                        <th class="py-3 px-5">Nama Pembeli</th>
                        <th class="py-3 px-5">Tanggal</th>
                        <th class="py-3 px-5">Total Bayar</th>
                        <th class="py-3 px-5">Pembayaran</th>
                        <th class="py-3 px-5">Status Pesanan</th>
                        <th class="py-3 px-5 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (!empty($recentOrders)): ?>
                        <?php foreach ($recentOrders as $ro): ?>
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="py-3.5 px-5 font-mono font-bold text-brand-700">
                                    <?= htmlspecialchars($ro['invoice']) ?>
                                </td>
                                <td class="py-3.5 px-5 font-semibold text-slate-800">
                                    <?= htmlspecialchars($ro['nama']) ?>
                                </td>
                                <td class="py-3.5 px-5 text-slate-500">
                                    <?= date('d M Y, H:i', strtotime($ro['tanggal'])) ?>
                                </td>
                                <td class="py-3.5 px-5 font-bold text-slate-900">
                                    <?= formatRupiah($ro['total_pembayaran']) ?>
                                </td>
                                <td class="py-3.5 px-5">
                                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold <?= $ro['status_pembayaran'] === 'Pembayaran Berhasil' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' ?>">
                                        <?= htmlspecialchars($ro['status_pembayaran']) ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-5">
                                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-slate-100 text-slate-700">
                                        <?= htmlspecialchars($ro['status_pesanan']) ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-5 text-center">
                                    <a href="order_detail.php?id=<?= $ro['id'] ?>" class="px-3 py-1 rounded-lg bg-brand-50 text-brand-700 hover:bg-brand-100 font-bold transition">
                                        Detail
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400">Belum ada pesanan yang tercatat.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Render Chart.js -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const ctx = document.getElementById('salesChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($chartLabels) ?>,
            datasets: [{
                label: 'Penjualan (Rp)',
                data: <?= json_encode($chartData) ?>,
                borderColor: '#16a34a',
                backgroundColor: 'rgba(22, 163, 74, 0.1)',
                borderWidth: 2.5,
                fill: true,
                tension: 0.35,
                pointBackgroundColor: '#15803d',
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'Rp ' + (value / 1000) + 'k';
                        },
                        font: { size: 10 }
                    },
                    grid: { color: '#f1f5f9' }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { size: 10 } }
                }
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>

