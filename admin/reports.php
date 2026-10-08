<?php
$adminTitle = 'Laporan Penjualan & Laba';
require_once __DIR__ . '/../includes/admin_header.php';

$db = getDB();
$period = sanitize($_GET['period'] ?? 'month');
$startDate = sanitize($_GET['start_date'] ?? '');
$endDate = sanitize($_GET['end_date'] ?? '');

// Jika user mengisi kedua tanggal, otomatis set periode ke 'custom'
if (!empty($startDate) && !empty($endDate)) {
    $period = 'custom';
}

$whereSql = "WHERE o.status_pembayaran = 'Pembayaran Berhasil'";
$periodLabel = 'Bulan Ini';

switch ($period) {
    case 'custom':
        $whereSql .= " AND DATE(o.tanggal) BETWEEN '$startDate' AND '$endDate'";
        $periodLabel = 'Kustom (' . date('d/m/Y', strtotime($startDate)) . ' - ' . date('d/m/Y', strtotime($endDate)) . ')';
        break;
    case 'day':
        $whereSql .= " AND DATE(o.tanggal) = CURDATE()";
        $periodLabel = 'Hari Ini (' . date('d M Y') . ')';
        break;
    case 'week':
        $whereSql .= " AND o.tanggal >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        $periodLabel = '7 Hari Terakhir';
        break;
    case 'year':
        $whereSql .= " AND YEAR(o.tanggal) = YEAR(CURDATE())";
        $periodLabel = 'Tahun ' . date('Y');
        break;
    case 'all':
        $periodLabel = 'Semua Waktu';
        break;
    case 'month':
    default:
        $whereSql .= " AND MONTH(o.tanggal) = MONTH(CURDATE()) AND YEAR(o.tanggal) = YEAR(CURDATE())";
        $periodLabel = 'Bulan Ini (' . date('F Y') . ')';
        break;
}

// Parameter untuk tombol Export Excel
$exportParams = "period=" . urlencode($period) . "&start_date=" . urlencode($startDate) . "&end_date=" . urlencode($endDate);

// Query total-total (tetap sama seperti sebelumnya...)
// ...

// 1. Total Transaksi
$stmtTrx = $db->query("SELECT COUNT(*) FROM orders o $whereSql");
$totalTransaksi = (int)$stmtTrx->fetchColumn();

// 2. Total Produk Terjual
$stmtItemsSold = $db->query("
    SELECT COALESCE(SUM(od.jumlah), 0) 
    FROM order_details od 
    JOIN orders o ON od.order_id = o.id 
    $whereSql
");
$totalProdukTerjual = (int)$stmtItemsSold->fetchColumn();

// 3. Total Pendapatan / Penjualan Produk (Omset)
$stmtOmset = $db->query("
    SELECT COALESCE(SUM(od.subtotal), 0) 
    FROM order_details od 
    JOIN orders o ON od.order_id = o.id 
    $whereSql
");
$totalPendapatan = (float)$stmtOmset->fetchColumn();

// 4. Total Modal
$stmtModal = $db->query("
    SELECT COALESCE(SUM(od.harga_modal * od.jumlah), 0) 
    FROM order_details od 
    JOIN orders o ON od.order_id = o.id 
    $whereSql
");
$totalModal = (float)$stmtModal->fetchColumn();

// 5. Total Laba Bersih
$totalLaba = $totalPendapatan - $totalModal;

// 6. Produk Paling Laris (Best Seller) di Periode Ini
$stmtBest = $db->query("
    SELECT p.nama_produk, SUM(od.jumlah) AS terjual 
    FROM order_details od 
    JOIN products p ON od.product_id = p.id 
    JOIN orders o ON od.order_id = o.id 
    $whereSql 
    GROUP BY p.id 
    ORDER BY terjual DESC 
    LIMIT 1
");
$bestSeller = $stmtBest->fetch();

// 7. Data Rincian Transaksi untuk Tabel Laporan
$stmtDetails = $db->query("
    SELECT o.invoice, o.tanggal, o.status_pesanan, u.nama AS pembeli, 
           od.jumlah, od.harga, od.harga_modal, od.subtotal, 
           (od.harga - od.harga_modal) * od.jumlah AS laba_item, 
           pr.nama_produk 
    FROM order_details od 
    JOIN orders o ON od.order_id = o.id 
    JOIN users u ON o.user_id = u.id 
    JOIN products pr ON od.product_id = pr.id 
    $whereSql 
    ORDER BY o.id DESC, od.id ASC
");
$reportRows = $stmtDetails->fetchAll();
?>

<div class="space-y-6">
    
    <!-- Top Action Bar -->
    <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm flex flex-col md:flex-row items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-extrabold text-slate-900">Laporan Penjualan & Laba Bisnis</h1>
            <p class="text-xs text-slate-500 mt-0.5">Analisis pendapatan, modal, dan keuntungan penjualan tanaman hias mini.</p>
        </div>

        <div class="flex items-center gap-3">
            <!-- Tombol Export Excel membawa parameter tanggal custom -->
            <a href="export_excel.php?<?= $exportParams ?>" class="inline-flex items-center gap-2 px-5 py-3 rounded-2xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-md shadow-emerald-600/20 transition">
                <i class="fa-solid fa-file-excel text-sm"></i>
                <span>Export Excel (.xls)</span>
            </a>
        </div>
    </div>

    <!-- Filter Box (Preset + Custom Date Input) -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm flex flex-col gap-3">
        
        <!-- Tombol Cepat (Preset) -->
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-xs font-bold text-slate-500 px-1">Periode Cepat:</span>
            <a href="reports.php?period=day" class="px-4 py-1.5 rounded-xl text-xs font-bold transition <?= $period === 'day' ? 'bg-brand-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                Hari Ini
            </a>
            <a href="reports.php?period=week" class="px-4 py-1.5 rounded-xl text-xs font-bold transition <?= $period === 'week' ? 'bg-brand-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                Minggu Ini (7 Hari)
            </a>
            <a href="reports.php?period=month" class="px-4 py-1.5 rounded-xl text-xs font-bold transition <?= $period === 'month' ? 'bg-brand-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                Bulan Ini
            </a>
            <a href="reports.php?period=year" class="px-4 py-1.5 rounded-xl text-xs font-bold transition <?= $period === 'year' ? 'bg-brand-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                Tahun Ini
            </a>
            <a href="reports.php?period=all" class="px-4 py-1.5 rounded-xl text-xs font-bold transition <?= $period === 'all' ? 'bg-brand-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                Semua Waktu
            </a>
        </div>

        <!-- Form Input Tanggal Mulai & Selesai -->
        <form method="GET" action="reports.php" class="flex flex-wrap items-center gap-2 pt-3 border-t border-slate-100">
            <input type="hidden" name="period" value="custom">
            
            <span class="text-xs font-bold text-slate-500 px-1">Rentang Tanggal Custom:</span>
            
            <div class="flex items-center gap-2">
                <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>" required class="px-3 py-1.5 border border-slate-200 rounded-xl text-xs text-slate-700 focus:ring-2 focus:ring-brand-500 focus:outline-none">
                <span class="text-xs text-slate-400">s/d</span>
                <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>" required class="px-3 py-1.5 border border-slate-200 rounded-xl text-xs text-slate-700 focus:ring-2 focus:ring-brand-500 focus:outline-none">
            </div>

            <button type="submit" class="px-4 py-1.5 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs rounded-xl transition">
                <i class="fa-solid fa-filter mr-1"></i> Filter Tanggal
            </button>

            <?php if ($period === 'custom'): ?>
                <a href="reports.php?period=month" class="text-xs text-rose-600 hover:underline font-bold ml-2">
                    Reset Filter
                </a>
            <?php endif; ?>
        </form>

    </div>
    <!-- Summary KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
        
        <!-- Periode Aktif -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col justify-between">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Periode</span>
            <span class="text-sm font-extrabold text-brand-700 mt-1"><?= $periodLabel ?></span>
        </div>

        <!-- Total Transaksi -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col justify-between">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Transaksi</span>
            <span class="text-xl font-extrabold text-slate-900 mt-1"><?= $totalTransaksi ?></span>
        </div>

        <!-- Total Produk Terjual -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col justify-between">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Produk Terjual</span>
            <span class="text-xl font-extrabold text-slate-900 mt-1"><?= $totalProdukTerjual ?> <span class="text-xs font-normal text-slate-400">pot</span></span>
        </div>

        <!-- Total Pendapatan -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col justify-between">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Pendapatan</span>
            <span class="text-lg font-extrabold text-slate-900 mt-1"><?= formatRupiah($totalPendapatan) ?></span>
        </div>

        <!-- Total Modal -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col justify-between">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Modal (HPP)</span>
            <span class="text-lg font-extrabold text-amber-700 mt-1"><?= formatRupiah($totalModal) ?></span>
        </div>

        <!-- Total Laba Bersih -->
        <div class="bg-emerald-50 p-5 rounded-2xl border border-emerald-200 shadow-sm flex flex-col justify-between">
            <span class="text-[11px] font-bold text-emerald-800 uppercase tracking-wider">Total Laba Bersih</span>
            <span class="text-lg font-extrabold text-emerald-700 mt-1"><?= formatRupiah($totalLaba) ?></span>
        </div>

    </div>

    <!-- Highlight Best Seller Badge -->
    <?php if ($bestSeller): ?>
        <div class="bg-gradient-to-r from-amber-500/10 via-amber-400/10 to-brand-500/10 border border-amber-200 rounded-2xl p-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-500 text-white flex items-center justify-center text-lg shadow-sm">
                    <i class="fa-solid fa-trophy"></i>
                </div>
                <div>
                    <span class="text-[11px] font-bold text-amber-900 uppercase tracking-wider">Produk Terlaris (Best Seller) Periode Ini</span>
                    <h3 class="font-extrabold text-slate-900 text-sm"><?= htmlspecialchars($bestSeller['nama_produk']) ?></h3>
                </div>
            </div>
            <span class="px-3 py-1 bg-amber-100 text-amber-900 font-extrabold text-xs rounded-full">
                Terjual <?= $bestSeller['terjual'] ?> pot
            </span>
        </div>
    <?php endif; ?>

    <!-- Table of Report Rows -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-900">Rincian Item Penjualan (Data Export Excel)</h3>
            <span class="text-xs text-slate-400">Total Baris: <?= count($reportRows) ?></span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100 text-slate-500 font-bold uppercase tracking-wider">
                        <th class="py-3 px-4">No</th>
                        <th class="py-3 px-4">Invoice</th>
                        <th class="py-3 px-4">Tanggal</th>
                        <th class="py-3 px-4">Nama Pembeli</th>
                        <th class="py-3 px-4">Produk</th>
                        <th class="py-3 px-4 text-center">Jumlah</th>
                        <th class="py-3 px-4 text-right">Harga Jual</th>
                        <th class="py-3 px-4 text-right">Modal</th>
                        <th class="py-3 px-4 text-right">Total Penjualan</th>
                        <th class="py-3 px-4 text-right">Laba</th>
                        <th class="py-3 px-4 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (!empty($reportRows)): ?>
                        <?php $no = 1; foreach ($reportRows as $r): ?>
                            <tr class="hover:bg-slate-50/50">
                                <td class="py-3 px-4 text-slate-400"><?= $no++ ?></td>
                                <td class="py-3 px-4 font-mono font-bold text-brand-700"><?= htmlspecialchars($r['invoice']) ?></td>
                                <td class="py-3 px-4 text-slate-500"><?= date('d/m/Y H:i', strtotime($r['tanggal'])) ?></td>
                                <td class="py-3 px-4 font-semibold text-slate-800"><?= htmlspecialchars($r['pembeli']) ?></td>
                                <td class="py-3 px-4 font-medium text-slate-800"><?= htmlspecialchars($r['nama_produk']) ?></td>
                                <td class="py-3 px-4 text-center font-bold text-slate-700"><?= $r['jumlah'] ?></td>
                                <td class="py-3 px-4 text-right text-slate-600"><?= formatRupiah($r['harga']) ?></td>
                                <td class="py-3 px-4 text-right text-slate-500"><?= formatRupiah($r['harga_modal']) ?></td>
                                <td class="py-3 px-4 text-right font-bold text-slate-900"><?= formatRupiah($r['subtotal']) ?></td>
                                <td class="py-3 px-4 text-right font-bold text-emerald-600">+<?= formatRupiah($r['laba_item']) ?></td>
                                <td class="py-3 px-4 text-center">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700">
                                        <?= htmlspecialchars($r['status_pesanan']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="11" class="py-10 text-center text-slate-400">
                                Tidak ada data penjualan pada periode ini.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
