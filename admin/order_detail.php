<?php
ob_start();

$adminTitle = 'Detail & Kelola Status Pesanan';
require_once __DIR__ . '/../includes/admin_header.php';

$db = getDB();
$orderId = (int)($_GET['id'] ?? 0);

if ($orderId <= 0) {
    header("Location: orders.php");
    exit;
}

// Handler Update Status Pesanan oleh Admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $statusPesananBaru = sanitize($_POST['status_pesanan'] ?? '');
    $statusBayarBaru = sanitize($_POST['status_pembayaran'] ?? '');

    try {
        $stmtUpdate = $db->prepare("
            UPDATE orders 
            SET status_pesanan = ?, status_pembayaran = ? 
            WHERE id = ?
        ");
        $stmtUpdate->execute([$statusPesananBaru, $statusBayarBaru, $orderId]);
        
        // Jika pembayaran diubah menjadi Pembayaran Berhasil dan belum ada paid_at, update log
        if ($statusBayarBaru === 'Pembayaran Berhasil') {
            $stmtPay = $db->prepare("UPDATE payments SET status = 'settlement', paid_at = COALESCE(paid_at, NOW()) WHERE order_id = ?");
            $stmtPay->execute([$orderId]);
        }

        setFlash('success', 'Status pesanan berhasil diperbarui menjadi: ' . $statusPesananBaru);
        header("Location: order_detail.php?id=" . $orderId);
        exit;
    } catch (Exception $e) {
        $error = 'Gagal memperbarui status: ' . $e->getMessage();
    }
}

// Query Order & User
$stmt = $db->prepare("
    SELECT o.*, u.nama, u.email, u.no_hp, p.metode_pembayaran, p.transaction_id, p.paid_at 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    LEFT JOIN payments p ON o.id = p.order_id 
    WHERE o.id = ?
");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    setFlash('error', 'Pesanan tidak ditemukan.');
    header("Location: orders.php");
    exit;
}

// Query Item Produk
$stmtItems = $db->prepare("
    SELECT od.*, pr.nama_produk, pr.foto, c.nama_kategori 
    FROM order_details od 
    JOIN products pr ON od.product_id = pr.id 
    JOIN categories c ON pr.category_id = c.id 
    WHERE od.order_id = ?
");
$stmtItems->execute([$orderId]);
$items = $stmtItems->fetchAll();

// Hitung total modal & laba pesanan ini
$orderModal = 0;
foreach ($items as $it) {
    $orderModal += ($it['harga_modal'] * $it['jumlah']);
}
$orderLaba = $order['total_harga'] - $orderModal;

// Admin mengelola proses hingga 'Dikirim' (serta memproses jika ada 'Pengembalian Diajukan').
// Status 'Selesai' dikonfirmasi langsung oleh Pembeli setelah barang dikirim.
$statuses = [
    'Menunggu Pembayaran',
    'Diproses',
    'Dikemas',
    'Dikirim',
    'Pengembalian Diajukan',
    'Pengembalian Disetujui',
    'Pengembalian Ditolak',
    'Dibatalkan'
];
if ($order['status_pesanan'] === 'Selesai') {
    $statuses[] = 'Selesai';
}
?>

<div class="max-w-5xl mx-auto space-y-6">
    
    <!-- Top Action Bar -->
    <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <span class="text-xs font-bold text-brand-600 uppercase tracking-wider">Detail Pesanan</span>
                <span class="text-slate-300">•</span>
                <span class="font-mono font-bold text-slate-800 text-sm sm:text-base"><?= htmlspecialchars($order['invoice']) ?></span>
            </div>
            <p class="text-xs text-slate-400 mt-0.5">Dipesan pada <?= date('d M Y, H:i', strtotime($order['tanggal'])) ?> WIB</p>
        </div>

        <div class="flex items-center gap-3">
            <a href="../invoice.php?order_id=<?= $order['id'] ?>" target="_blank" class="px-4 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 font-bold text-xs flex items-center gap-1.5 transition">
                <i class="fa-solid fa-file-invoice text-brand-600"></i> Lihat Invoice (PDF)
            </a>
            <a href="orders.php" class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition">
                Kembali
            </a>
        </div>
    </div>

    <!-- Alert Pengajuan Pengembalian dari Pembeli (Jika Ada) -->
    <?php if (!empty($order['alasan_pengembalian']) || strpos($order['status_pesanan'], 'Pengembalian') !== false): ?>
        <div class="bg-rose-50 rounded-3xl border border-rose-200 p-6 shadow-sm space-y-3">
            <div class="flex items-center justify-between flex-wrap gap-2">
                <h3 class="text-sm font-extrabold text-rose-900 flex items-center gap-2">
                    <i class="fa-solid fa-rotate-left text-rose-600"></i>
                    <span>Pengajuan Pengembalian Barang dari Pembeli</span>
                </h3>
                <span class="px-3 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800 border border-rose-300">
                    <?= htmlspecialchars($order['status_pesanan']) ?>
                </span>
            </div>
            <p class="text-xs text-rose-800 bg-white/80 p-3.5 rounded-xl border border-rose-100">
                <strong>Alasan Pembeli:</strong> "<?= htmlspecialchars($order['alasan_pengembalian'] ?? 'Tidak ada catatan alasan.') ?>"
            </p>
            <?php if ($order['status_pesanan'] === 'Pengembalian Diajukan'): ?>
                <div class="flex flex-wrap items-center gap-2.5 pt-1">
                    <form action="order_detail.php?id=<?= $orderId ?>" method="POST">
                        <input type="hidden" name="update_status" value="1">
                        <input type="hidden" name="status_pembayaran" value="<?= htmlspecialchars($order['status_pembayaran']) ?>">
                        <input type="hidden" name="status_pesanan" value="Pengembalian Disetujui">
                        <button type="submit" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-sm transition">
                            <i class="fa-solid fa-check mr-1"></i> Setujui Pengembalian
                        </button>
                    </form>
                    <form action="order_detail.php?id=<?= $orderId ?>" method="POST">
                        <input type="hidden" name="update_status" value="1">
                        <input type="hidden" name="status_pembayaran" value="<?= htmlspecialchars($order['status_pembayaran']) ?>">
                        <input type="hidden" name="status_pesanan" value="Pengembalian Ditolak">
                        <button type="submit" class="px-4 py-2 rounded-xl bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 font-bold text-xs transition">
                            <i class="fa-solid fa-xmark mr-1"></i> Tolak Pengembalian
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Status Updater Box -->
    <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4">
            <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-sliders text-brand-600"></i> Perbarui Status Pengiriman (Admin)
            </h2>
            <span class="text-[11px] text-blue-700 bg-blue-50 border border-blue-200 px-3 py-1 rounded-lg font-medium">
                <i class="fa-solid fa-circle-info mr-1"></i> Pilih <strong>Dikirim</strong> agar Pembeli dapat menekan <strong>Pesanan Selesai</strong> atau <strong>Ajukan Pengembalian</strong>
            </span>
        </div>

        <form action="order_detail.php?id=<?= $orderId ?>" method="POST" class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
            <input type="hidden" name="update_status" value="1">

            <div>
                <label for="status_pesanan" class="block text-xs font-semibold text-slate-700 mb-1.5">Status Alur Pesanan</label>
                <select id="status_pesanan" name="status_pesanan" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-800 focus:outline-none focus:border-brand-500 bg-white">
                    <?php foreach ($statuses as $st): ?>
                        <option value="<?= $st ?>" <?= $order['status_pesanan'] === $st ? 'selected' : '' ?>>
                            <?= $st ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="status_pembayaran" class="block text-xs font-semibold text-slate-700 mb-1.5">Status Pembayaran</label>
                <select id="status_pembayaran" name="status_pembayaran" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-800 focus:outline-none focus:border-brand-500 bg-white">
                    <option value="Menunggu Pembayaran" <?= $order['status_pembayaran'] === 'Menunggu Pembayaran' ? 'selected' : '' ?>>Menunggu Pembayaran</option>
                    <option value="Pembayaran Berhasil" <?= $order['status_pembayaran'] === 'Pembayaran Berhasil' ? 'selected' : '' ?>>Pembayaran Berhasil</option>
                    <option value="Gagal" <?= $order['status_pembayaran'] === 'Gagal' ? 'selected' : '' ?>>Gagal</option>
                    <option value="Kadaluarsa" <?= $order['status_pembayaran'] === 'Kadaluarsa' ? 'selected' : '' ?>>Kadaluarsa</option>
                </select>
            </div>

            <div>
                <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs shadow-md shadow-brand-600/20 transition">
                    Simpan Perubahan Status
                </button>
            </div>
        </form>
    </div>

    <!-- Customer & Shipping Information -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        
        <!-- Buyer Profile -->
        <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm space-y-3">
            <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3 flex items-center gap-2">
                <i class="fa-solid fa-user text-brand-600"></i> Data Pembeli
            </h3>
            <div class="text-xs space-y-2 text-slate-600">
                <div class="flex justify-between">
                    <span class="text-slate-400">Nama Pembeli:</span>
                    <strong class="text-slate-800"><?= htmlspecialchars($order['nama']) ?></strong>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Email:</span>
                    <span class="text-slate-800"><?= htmlspecialchars($order['email']) ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Nomor WhatsApp/HP:</span>
                    <span class="text-slate-800"><?= htmlspecialchars($order['no_hp']) ?></span>
                </div>
            </div>
        </div>

        <!-- Shipping & Payment -->
        <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm space-y-3">
            <h3 class="text-sm font-bold text-slate-900 border-b border-slate-100 pb-3 flex items-center gap-2">
                <i class="fa-solid fa-truck text-brand-600"></i> Pengiriman & Pembayaran
            </h3>
            <div class="text-xs space-y-2 text-slate-600">
                <div>
                    <span class="text-slate-400 block mb-0.5">Alamat Tujuan:</span>
                    <p class="text-slate-800 font-medium"><?= nl2br(htmlspecialchars($order['alamat_pengiriman'])) ?></p>
                </div>
                <?php if (!empty($order['catatan'])): ?>
                    <div>
                        <span class="text-slate-400 block mb-0.5">Catatan Pembeli:</span>
                        <p class="text-amber-700 italic">"<?= htmlspecialchars($order['catatan']) ?>"</p>
                    </div>
                <?php endif; ?>
                <div class="pt-2 border-t border-slate-100 flex justify-between">
                    <span class="text-slate-400">Metode Bayar:</span>
                    <strong class="text-slate-800"><?= htmlspecialchars($order['metode_pembayaran'] ?? 'Midtrans Sandbox') ?></strong>
                </div>
            </div>
        </div>

    </div>

    <!-- Purchased Products Table -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-100">
            <h3 class="text-sm font-bold text-slate-900">Rincian Barang & Kalkulasi Keuntungan</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100 text-slate-500 font-bold uppercase tracking-wider">
                        <th class="py-3 px-5">Produk</th>
                        <th class="py-3 px-5 text-center">Jumlah</th>
                        <th class="py-3 px-5 text-right">Modal / Unit</th>
                        <th class="py-3 px-5 text-right">Harga Jual</th>
                        <th class="py-3 px-5 text-right">Subtotal</th>
                        <th class="py-3 px-5 text-right">Laba Produk</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($items as $it): ?>
                        <?php 
                            $profitItem = ($it['harga'] - $it['harga_modal']) * $it['jumlah']; 
                        ?>
                        <tr class="hover:bg-slate-50/50">
                            <td class="py-3.5 px-5 flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-slate-100 overflow-hidden border border-slate-100 flex-shrink-0">
                                    <img src="../assets/images/products/<?= htmlspecialchars($it['foto']) ?>" 
                                         alt="<?= htmlspecialchars($it['nama_produk']) ?>" 
                                         class="w-full h-full object-cover"
                                         onerror="this.src='../assets/images/products/default.jpg'">
                                </div>
                                <div>
                                    <span class="font-bold text-slate-800 block text-xs"><?= htmlspecialchars($it['nama_produk']) ?></span>
                                    <span class="text-[10px] text-slate-400"><?= htmlspecialchars($it['nama_kategori']) ?></span>
                                </div>
                            </td>
                            <td class="py-3.5 px-5 text-center font-bold text-slate-800"><?= $it['jumlah'] ?></td>
                            <td class="py-3.5 px-5 text-right text-slate-500"><?= formatRupiah($it['harga_modal']) ?></td>
                            <td class="py-3.5 px-5 text-right font-medium text-slate-800"><?= formatRupiah($it['harga']) ?></td>
                            <td class="py-3.5 px-5 text-right font-bold text-slate-900"><?= formatRupiah($it['subtotal']) ?></td>
                            <td class="py-3.5 px-5 text-right font-bold text-emerald-600">+<?= formatRupiah($profitItem) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Summary & Profit Box -->
        <div class="p-6 bg-slate-50/70 border-t border-slate-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-xs text-emerald-800">
                <span class="font-bold block"><i class="fa-solid fa-coins mr-1"></i> Keuntungan Bersih dari Pesanan Ini:</span>
                <span class="text-base font-extrabold text-emerald-700"><?= formatRupiah($orderLaba) ?></span>
                <span class="text-[10px] text-emerald-600 block">(Total Penjualan <?= formatRupiah($order['total_harga']) ?> - Total Modal <?= formatRupiah($orderModal) ?>)</span>
            </div>

            <div class="w-full sm:w-64 space-y-1.5 text-xs">
                <div class="flex justify-between text-slate-600">
                    <span>Subtotal Produk</span>
                    <span class="font-bold text-slate-800"><?= formatRupiah($order['total_harga']) ?></span>
                </div>
                <div class="flex justify-between text-slate-600">
                    <span>Ongkos Kirim</span>
                    <span class="font-bold text-slate-800"><?= formatRupiah($order['ongkir']) ?></span>
                </div>
                <div class="flex justify-between text-sm pt-2 border-t border-slate-200 text-slate-900 font-extrabold">
                    <span>Total Pembayaran</span>
                    <span class="text-brand-700 text-base"><?= formatRupiah($order['total_pembayaran']) ?></span>
                </div>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>

