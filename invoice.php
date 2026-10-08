<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_check.php';

// Pastikan yang mengakses adalah User pemilik pesanan ATAU Admin
if (!isUserLoggedIn() && !isAdminLoggedIn()) {
    header("Location: login.php");
    exit;
}

$orderId = (int)($_GET['order_id'] ?? 0);
if ($orderId <= 0) {
    die("ID Pesanan tidak valid.");
}

$db = getDB();

// Query detail pesanan
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
    die("Pesanan tidak ditemukan.");
}

// Keamanan: Jika bukan admin, pastikan user hanya bisa melihat pesanannya sendiri
if (!isAdminLoggedIn() && $order['user_id'] != $_SESSION['user_id']) {
    die("Akses ditolak. Anda tidak memiliki izin untuk melihat invoice ini.");
}

// Ambil item produk
$stmtItems = $db->prepare("
    SELECT od.*, pr.nama_produk, c.nama_kategori 
    FROM order_details od 
    JOIN products pr ON od.product_id = pr.id 
    JOIN categories c ON pr.category_id = c.id 
    WHERE od.order_id = ?
");
$stmtItems->execute([$orderId]);
$items = $stmtItems->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - <?= htmlspecialchars($order['invoice']) ?> | Tanaman Hias Mini</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Custom Style with Print Support -->
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; padding: 0 !important; }
            .invoice-box { border: none !important; box-shadow: none !important; padding: 0 !important; max-width: 100% !important; border-radius: 0 !important; }
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 min-h-screen flex flex-col justify-center items-center py-3 px-3">

    <!-- Top Action Bar (No Print) -->
    <div class="w-full max-w-xl mb-2 no-print flex items-center justify-between">
        <a href="javascript:void(0)" onclick="window.location.href = document.referrer ? document.referrer : 'orders.php';" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-600 hover:text-slate-900 bg-white px-3 py-1.5 rounded-lg border border-slate-200 shadow-xs transition cursor-pointer">
            <i class="fa-solid fa-arrow-left text-[10px]"></i> Kembali
        </a>
        
        <div class="flex items-center gap-2">
            <button onclick="window.print()" class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-800 hover:text-slate-900 bg-white px-3.5 py-1.5 rounded-lg border border-slate-200 shadow-xs transition cursor-pointer">
                <i class="fa-solid fa-file-arrow-down text-xs text-brand-600"></i> Download / Cetak
            </button>
        </div>
    </div>

    <!-- Invoice Paper Container -->
    <div class="w-full max-w-xl bg-white rounded-2xl border border-slate-200 shadow-sm p-4 sm:p-5 invoice-box relative overflow-hidden">
        
        <!-- Paid Stamp Watermark -->
        <?php if ($order['status_pembayaran'] === 'Pembayaran Berhasil'): ?>
            <div class="absolute right-4 top-14 rotate-[-12deg] pointer-events-none opacity-20 border-2 border-emerald-600 text-emerald-600 font-extrabold text-[11px] px-2.5 py-0.5 rounded-md uppercase tracking-widest">
                LUNAS / PAID
            </div>
        <?php endif; ?>

        <!-- Invoice Header -->
        <div class="flex justify-between items-start gap-3 border-b border-slate-200 pb-3">
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-lg bg-brand-600 flex items-center justify-center text-white text-xs">
                        <i class="fa-solid fa-seedling"></i>
                    </div>
                    <div>
                        <h1 class="text-sm font-extrabold text-slate-900 leading-none">Tanaman Hias <span class="text-brand-600">Mini</span></h1>
                        <p class="text-[9px] text-slate-400">E-Commerce Tanaman Hias Mini</p>
                    </div>
                </div>
                <p class="text-[10px] text-slate-500 leading-tight">
                    Jl. Raya Kampus Digital No. 88, Univ Pamulang, Tangerang<br>
                    WA: +62 812-3456-7890 • Email: halo@tanamanmini.local
                </p>
            </div>

            <div class="text-right space-y-0.5">
                <span class="text-[9px] font-bold text-brand-600 uppercase tracking-wider block">INVOICE PEMBAYARAN</span>
                <p class="text-xs font-bold text-slate-900 font-mono tracking-tight"><?= htmlspecialchars($order['invoice']) ?></p>
                <p class="text-[10px] text-slate-500">Tanggal: <?= date('d M Y', strtotime($order['tanggal'])) ?></p>
                <div class="pt-0.5">
                    <span class="inline-block text-[9px] font-bold px-2 py-0.5 rounded-full <?= $order['status_pembayaran'] === 'Pembayaran Berhasil' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' ?>">
                        <?= htmlspecialchars($order['status_pembayaran']) ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Bill To & Payment Info -->
        <div class="grid grid-cols-2 gap-3 py-2.5 border-b border-slate-200 text-[10px] leading-snug">
            <div>
                <span class="font-bold text-slate-400 uppercase tracking-wider block mb-0.5 text-[9px]">Tagihan Kepada:</span>
                <h4 class="font-bold text-slate-900 text-[11px] mb-0.5"><?= htmlspecialchars($order['nama']) ?></h4>
                <p class="text-slate-600"><?= htmlspecialchars($order['email']) ?></p>
                <p class="text-slate-600"><?= htmlspecialchars($order['no_hp']) ?></p>
                <p class="text-slate-600 mt-1 leading-tight">
                    <strong>Alamat:</strong> <?= htmlspecialchars($order['alamat_pengiriman']) ?>
                </p>
                <?php if (!empty($order['catatan'])): ?>
                    <p class="text-slate-500 mt-0.5 italic text-[9px]">
                        <strong>Catatan:</strong> "<?= htmlspecialchars($order['catatan']) ?>"
                    </p>
                <?php endif; ?>
            </div>

            <div class="space-y-1 text-right text-[10px]">
                <span class="font-bold text-slate-400 uppercase tracking-wider block mb-0.5 text-[9px]">Informasi Transaksi:</span>
                <div>
                    <span class="text-slate-500">Metode:</span>
                    <strong class="text-slate-800 font-semibold"><?= htmlspecialchars($order['metode_pembayaran'] ?? 'Midtrans Sandbox') ?></strong>
                </div>
                <?php if (!empty($order['transaction_id'])): ?>
                    <div>
                        <span class="text-slate-500">ID Transaksi:</span>
                        <code class="text-slate-800 text-[9px]"><?= htmlspecialchars($order['transaction_id']) ?></code>
                    </div>
                <?php endif; ?>
                <div>
                    <span class="text-slate-500">Status Pesanan:</span>
                    <strong class="text-slate-800 font-semibold"><?= htmlspecialchars($order['status_pesanan']) ?></strong>
                </div>
                <div>
                    <span class="text-slate-500">Waktu Bayar:</span>
                    <strong class="text-slate-800 font-semibold"><?= !empty($order['paid_at']) ? date('d M Y, H:i', strtotime($order['paid_at'])) . ' WIB' : '-' ?></strong>
                </div>
            </div>
        </div>

        <!-- Order Items Table -->
        <div class="py-2">
            <table class="w-full text-left text-[11px] border-collapse">
                <thead>
                    <tr class="border-b border-slate-200 text-slate-500 uppercase tracking-wider font-bold text-[9px]">
                        <th class="py-1 px-1.5">No</th>
                        <th class="py-1 px-1.5">Deskripsi Produk</th>
                        <th class="py-1 px-1.5 text-center">Jumlah</th>
                        <th class="py-1 px-1.5 text-right">Harga</th>
                        <th class="py-1 px-1.5 text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php $no = 1; foreach ($items as $it): ?>
                        <tr>
                            <td class="py-1.5 px-1.5 text-slate-400 font-medium"><?= $no++ ?></td>
                            <td class="py-1.5 px-1.5">
                                <span class="font-bold text-slate-800 block text-[11px]"><?= htmlspecialchars($it['nama_produk']) ?></span>
                                <span class="text-[9px] text-slate-400">Kategori: <?= htmlspecialchars($it['nama_kategori']) ?></span>
                            </td>
                            <td class="py-1.5 px-1.5 text-center font-bold text-slate-700"><?= $it['jumlah'] ?></td>
                            <td class="py-1.5 px-1.5 text-right text-slate-600"><?= formatRupiah($it['harga']) ?></td>
                            <td class="py-1.5 px-1.5 text-right font-bold text-slate-900"><?= formatRupiah($it['subtotal']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Totals Calculation -->
        <div class="border-t border-slate-200 pt-2 flex justify-end">
            <div class="w-48 sm:w-52 space-y-0.5 text-[10px]">
                <div class="flex justify-between text-slate-600">
                    <span>Subtotal Produk</span>
                    <span class="font-bold text-slate-800"><?= formatRupiah($order['total_harga']) ?></span>
                </div>
                <div class="flex justify-between text-slate-600">
                    <span>Ongkos Kirim</span>
                    <span class="font-bold text-slate-800"><?= formatRupiah($order['ongkir']) ?></span>
                </div>
                <div class="flex justify-between text-[11px] pt-1 border-t border-slate-200 text-slate-900 font-extrabold">
                    <span>Total Pembayaran</span>
                    <span class="text-brand-700 text-xs font-extrabold"><?= formatRupiah($order['total_pembayaran']) ?></span>
                </div>
            </div>
        </div>

        <!-- Bottom Terms / Sign -->
        <div class="mt-3.5 pt-2.5 border-t border-slate-100 flex justify-between items-center text-[9px] text-slate-400">
            <div>
                <p class="font-semibold text-slate-600">Terima kasih telah berbelanja di Tanaman Hias Mini!</p>
                <p>Invoice ini sah dan diterbitkan secara digital oleh sistem.</p>
            </div>
            <div class="text-center">
                <p class="text-slate-500 font-medium text-[9px]">Pengelola Toko,</p>
                <div class="h-5 flex items-center justify-center font-serif text-slate-700 italic text-[11px]">
                    Tanaman Hias Mini
                </div>
                <p class="text-[8px] text-slate-400 border-t border-slate-200 pt-0.5">Tanaman Hias Mini</p>
            </div>
        </div>

    </div>

</body>
</html>

