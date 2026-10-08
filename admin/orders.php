<?php
$adminTitle = 'Kelola Pesanan Masuk';
require_once __DIR__ . '/../includes/admin_header.php';

$db = getDB();

$search = sanitize($_GET['q'] ?? '');
$statusFilter = sanitize($_GET['status'] ?? '');

$sql = "
    SELECT o.*, u.nama, u.email, p.metode_pembayaran 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    LEFT JOIN payments p ON o.id = p.order_id 
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $sql .= " AND (o.invoice LIKE ? OR u.nama LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($statusFilter)) {
    $sql .= " AND o.status_pesanan = ?";
    $params[] = $statusFilter;
}

$sql .= " ORDER BY o.id DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

$statuses = [
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
];
?>

<div class="space-y-6">
    
    <!-- Header -->
    <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm flex flex-col md:flex-row items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-extrabold text-slate-900">Manajemen Pesanan Masuk</h1>
            <p class="text-xs text-slate-500 mt-0.5">Kelola dan perbarui status pengiriman tanaman ke seluruh pembeli.</p>
        </div>
        <a href="reports.php" class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs transition">
            <i class="fa-solid fa-chart-line"></i> Rekap Laporan Penjualan
        </a>
    </div>

    <!-- Filter & Search Box -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-3">
        <form action="orders.php" method="GET" class="flex flex-col sm:flex-row items-center gap-3 w-full sm:w-auto">
            <div class="relative w-full sm:w-64">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3 text-slate-400 text-xs"></i>
                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Cari invoice / nama pembeli..." class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-200 text-xs focus:outline-none focus:border-brand-500">
            </div>

            <select name="status" class="w-full sm:w-auto px-3 py-2 rounded-xl border border-slate-200 text-xs focus:outline-none focus:border-brand-500 bg-white">
                <option value="">Semua Status Pesanan</option>
                <?php foreach ($statuses as $st): ?>
                    <option value="<?= $st ?>" <?= $statusFilter === $st ? 'selected' : '' ?>>
                        <?= $st ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="w-full sm:w-auto px-4 py-2 rounded-xl bg-slate-800 text-white text-xs font-semibold hover:bg-slate-900 transition">
                Filter
            </button>

            <?php if ($search || $statusFilter): ?>
                <a href="orders.php" class="text-xs text-slate-500 hover:text-slate-700">Reset</a>
            <?php endif; ?>
        </form>

        <span class="text-xs text-slate-400 font-medium">
            Total: <strong><?= count($orders) ?></strong> transaksi
        </span>
    </div>

    <!-- Orders Table -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100 text-slate-500 font-bold uppercase tracking-wider">
                        <th class="py-3.5 px-5">Nomor Pesanan / Invoice</th>
                        <th class="py-3.5 px-5">Nama Pembeli</th>
                        <th class="py-3.5 px-5">Tanggal Masuk</th>
                        <th class="py-3.5 px-5">Metode Pembayaran</th>
                        <th class="py-3.5 px-5 text-right">Total Tagihan</th>
                        <th class="py-3.5 px-5">Status Bayar</th>
                        <th class="py-3.5 px-5">Status Pesanan</th>
                        <th class="py-3.5 px-5 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (!empty($orders)): ?>
                        <?php foreach ($orders as $o): ?>
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="py-3.5 px-5">
                                    <span class="font-mono font-bold text-brand-700 text-xs block">
                                        <?= htmlspecialchars($o['invoice']) ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-5">
                                    <span class="font-bold text-slate-800 block"><?= htmlspecialchars($o['nama']) ?></span>
                                    <span class="text-[11px] text-slate-400"><?= htmlspecialchars($o['email']) ?></span>
                                </td>
                                <td class="py-3.5 px-5 text-slate-500">
                                    <?= date('d M Y, H:i', strtotime($o['tanggal'])) ?>
                                </td>
                                <td class="py-3.5 px-5 text-slate-600">
                                    <?= htmlspecialchars($o['metode_pembayaran'] ?? 'Midtrans Sandbox') ?>
                                </td>
                                <td class="py-3.5 px-5 text-right font-extrabold text-slate-900">
                                    <?= formatRupiah($o['total_pembayaran']) ?>
                                </td>
                                <td class="py-3.5 px-5">
                                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold <?= $o['status_pembayaran'] === 'Pembayaran Berhasil' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' ?>">
                                        <?= htmlspecialchars($o['status_pembayaran']) ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-5">
                                    <?php
                                        $badgeBg = 'bg-slate-100 text-slate-700';
                                        if ($o['status_pesanan'] === 'Selesai') $badgeBg = 'bg-emerald-100 text-emerald-800';
                                        elseif ($o['status_pesanan'] === 'Dikirim') $badgeBg = 'bg-blue-100 text-blue-800';
                                        elseif ($o['status_pesanan'] === 'Dikemas') $badgeBg = 'bg-indigo-100 text-indigo-800';
                                        elseif ($o['status_pesanan'] === 'Diproses') $badgeBg = 'bg-amber-100 text-amber-800';
                                        elseif (strpos($o['status_pesanan'], 'Pengembalian') !== false) $badgeBg = 'bg-rose-100 text-rose-800';
                                        elseif (strpos($o['status_pesanan'], 'Pengembalian') !== false) $badgeBg = 'bg-rose-100 text-rose-800';
                                        elseif ($o['status_pesanan'] === 'Dibatalkan') $badgeBg = 'bg-red-100 text-red-800';
                                    ?>
                                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold <?= $badgeBg ?>">
                                        <?= htmlspecialchars($o['status_pesanan']) ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-5 text-center whitespace-nowrap">
                                    <a href="order_detail.php?id=<?= $o['id'] ?>" class="px-3.5 py-1.5 rounded-lg bg-brand-50 text-brand-700 hover:bg-brand-100 font-bold transition">
                                        Kelola
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="py-10 text-center text-slate-400">
                                Tidak ada data pesanan yang ditemukan.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>

