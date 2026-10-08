<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_check.php';
requireUserLogin();

$db = getDB();
$userId = $_SESSION['user_id'];
$orderId = (int)($_GET['order_id'] ?? 0);

$stmt = $db->prepare("
    SELECT o.*, p.metode_pembayaran, p.paid_at 
    FROM orders o 
    LEFT JOIN payments p ON o.id = p.order_id 
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->execute([$orderId, $userId]);
$order = $stmt->fetch();

if (!$order) {
    header("Location: orders.php");
    exit;
}

$pageTitle = 'Pembayaran Berhasil';
require_once __DIR__ . '/includes/header.php';
?>

<div class="py-16 px-4 sm:px-6 lg:px-8 max-w-xl mx-auto">
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-lg p-8 text-center space-y-6">
        
        <!-- Success Icon -->
        <div class="w-20 h-20 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-4xl mx-auto shadow-inner">
            <i class="fa-solid fa-circle-check"></i>
        </div>

        <div>
            <span class="text-xs font-bold uppercase tracking-wider text-emerald-600 bg-emerald-50 px-3 py-1 rounded-full">
                Transaksi Sukses
            </span>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-3">Pembayaran Berhasil!</h1>
            <p class="text-xs text-slate-500 mt-2 leading-relaxed">
                Terima kasih atas pesanan Anda. Tim Tanaman Hias Mini sedang menyiapkan tanaman pilihan Anda dengan penuh kasih.
            </p>
        </div>

        <!-- Receipt Card Summary -->
        <div class="bg-slate-50 rounded-2xl p-5 border border-slate-100 text-left space-y-3 text-xs">
            <div class="flex justify-between pb-2 border-b border-slate-200/60">
                <span class="text-slate-500">Nomor Invoice</span>
                <span class="font-mono font-bold text-slate-800"><?= htmlspecialchars($order['invoice']) ?></span>
            </div>
            <div class="flex justify-between pb-2 border-b border-slate-200/60">
                <span class="text-slate-500">Waktu Pembayaran</span>
                <span class="font-medium text-slate-800"><?= date('d M Y, H:i', strtotime($order['paid_at'] ?? $order['tanggal'])) ?> WIB</span>
            </div>
            <div class="flex justify-between pb-2 border-b border-slate-200/60">
                <span class="text-slate-500">Metode Pembayaran</span>
                <span class="font-medium text-slate-800"><?= htmlspecialchars($order['metode_pembayaran'] ?? 'Midtrans Sandbox') ?></span>
            </div>
            <div class="flex justify-between pb-2 border-b border-slate-200/60">
                <span class="text-slate-500">Status Pesanan</span>
                <span class="inline-flex items-center gap-1 font-bold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded-md">
                    <i class="fa-solid fa-clock-rotate-left text-[10px]"></i> Diproses Toko
                </span>
            </div>
            <div class="flex justify-between pt-1 text-sm">
                <span class="font-bold text-slate-900">Total Dibayar</span>
                <span class="font-extrabold text-brand-700 text-base"><?= formatRupiah($order['total_pembayaran']) ?></span>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="pt-2 space-y-3">
            <a href="invoice.php?order_id=<?= $order['id'] ?>" target="_blank" class="w-full py-3.5 px-5 rounded-2xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-sm shadow-md shadow-brand-600/25 transition flex items-center justify-center gap-2">
                <i class="fa-solid fa-file-invoice"></i>
                <span>Lihat & Download Invoice (PDF)</span>
            </a>

            <div class="grid grid-cols-2 gap-3">
                <a href="orders.php" class="py-2.5 px-4 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold text-xs transition text-center">
                    Riwayat Pesanan
                </a>
                <a href="products.php" class="py-2.5 px-4 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold text-xs transition text-center">
                    Belanja Lagi
                </a>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

