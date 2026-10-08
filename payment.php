<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_check.php';
requireUserLogin();

$db = getDB();
$userId = $_SESSION['user_id'];
$orderId = (int)($_GET['order_id'] ?? 0);

if ($orderId <= 0) {
    header("Location: orders.php");
    exit;
}

// Ambil data pesanan
$stmt = $db->prepare("
    SELECT o.*, u.nama, u.email, u.no_hp 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->execute([$orderId, $userId]);
$order = $stmt->fetch();

if (!$order) {
    setFlash('error', 'Pesanan tidak ditemukan.');
    header("Location: orders.php");
    exit;
}

// Jika pesanan sudah dibayar, langsung alihkan ke halaman sukses
if ($order['status_pembayaran'] === 'Pembayaran Berhasil') {
    header("Location: order_success.php?order_id=" . $order['id']);
    exit;
}

// Handler Pembayaran Sandbox (Simulasi 1-Klik Berhasil)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simulate_payment'])) {
    $metode = sanitize($_POST['channel'] ?? 'BCA Virtual Account (Midtrans)');
    $trxId = 'MID-SB-' . date('YmdHis') . '-' . rand(100, 999);

    try {
        $db->beginTransaction();

        // 1. Update status pesanan & pembayaran
        $stmtUpdateOrder = $db->prepare("
            UPDATE orders 
            SET status_pembayaran = 'Pembayaran Berhasil', status_pesanan = 'Diproses' 
            WHERE id = ?
        ");
        $stmtUpdateOrder->execute([$orderId]);

        // 2. Update log payment
        $stmtUpdatePay = $db->prepare("
            UPDATE payments 
            SET metode_pembayaran = ?, transaction_id = ?, status = 'settlement', paid_at = NOW() 
            WHERE order_id = ?
        ");
        $stmtUpdatePay->execute([$metode, $trxId, $orderId]);

        // 3. Potong stok produk di database
        $stmtDetails = $db->prepare("SELECT product_id, jumlah FROM order_details WHERE order_id = ?");
        $stmtDetails->execute([$orderId]);
        $details = $stmtDetails->fetchAll();

        foreach ($details as $d) {
            $stmtStock = $db->prepare("UPDATE products SET stok = GREATEST(0, stok - ?) WHERE id = ?");
            $stmtStock->execute([$d['jumlah'], $d['product_id']]);
        }

        $db->commit();

        setFlash('success', 'Pembayaran sebesar ' . formatRupiah($order['total_pembayaran']) . ' berhasil diverifikasi oleh Midtrans Sandbox!');
        header("Location: order_success.php?order_id=" . $orderId);
        exit;

    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $paymentError = 'Gagal memproses pembayaran: ' . $e->getMessage();
    }
}

$pageTitle = 'Pembayaran Pesanan';
require_once __DIR__ . '/includes/header.php';
?>

<div class="py-12 px-4 sm:px-6 lg:px-8 max-w-3xl mx-auto">
    
    <!-- Breadcrumbs / Top info -->
    <div class="text-center mb-8">
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-blue-50 text-blue-700 text-xs font-bold mb-2">
            <i class="fa-solid fa-code"></i> Midtrans Snap Sandbox
        </span>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900">Selesaikan Pembayaran Anda</h1>
        <p class="text-xs text-slate-500 mt-1">Invoice: <strong class="text-slate-800"><?= htmlspecialchars($order['invoice']) ?></strong></p>
    </div>

    <?php if (isset($paymentError)): ?>
        <div class="mb-6 p-4 rounded-2xl bg-red-50 border border-red-200 text-red-700 text-xs">
            <?= htmlspecialchars($paymentError) ?>
        </div>
    <?php endif; ?>

    <!-- Midtrans Snap Sandbox Simulated UI -->
    <div class="bg-white rounded-3xl border border-slate-200/90 shadow-xl overflow-hidden">
        
        <!-- Header Midtrans Card -->
        <div class="bg-slate-900 text-white p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-brand-600 flex items-center justify-center text-white text-lg">
                    <i class="fa-solid fa-seedling"></i>
                </div>
                <div>
                    <h2 class="font-extrabold text-sm sm:text-base">Tanaman Hias Mini Official</h2>
                    <p class="text-xs text-slate-400">Order ID: <?= htmlspecialchars($order['invoice']) ?></p>
                </div>
            </div>
            <div class="text-left sm:text-right">
                <span class="text-xs text-slate-400 block">Total Tagihan</span>
                <span class="text-xl sm:text-2xl font-extrabold text-emerald-400">
                    <?= formatRupiah($order['total_pembayaran']) ?>
                </span>
            </div>
        </div>

        <div class="p-6 sm:p-8 space-y-6">
            
            <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 text-xs flex items-start gap-3">
                <i class="fa-solid fa-circle-info text-amber-600 text-base mt-0.5 flex-shrink-0"></i>
                <div>
                    <strong class="font-bold block mb-0.5">Mode Simulasi Transaksi:</strong>
                    <p class="text-slate-600">
                        Ini adalah lingkungan simulasi resmi untuk demonstrasi sistem e-commerce. Tidak ada uang sungguhan yang didebit. Pilih saluran bayar di bawah ini lalu klik tombol simulasi bayar.
                    </p>
                </div>
            </div>

            <!-- Tab Channels Selection -->
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-3">
                    Pilih Metode Pembayaran
                </label>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" id="channelOptions">
                    <!-- Option 1: QRIS -->
                    <label class="flex items-center gap-3 p-4 rounded-2xl border border-slate-200 hover:border-brand-500 bg-slate-50/50 cursor-pointer transition">
                        <input type="radio" name="payment_channel" value="QRIS (GoPay/ShopeePay)" checked class="text-brand-600 focus:ring-brand-500" onclick="showChannel('qris')">
                        <div class="flex-1">
                            <span class="font-bold text-slate-800 text-xs block">QRIS / Instant E-Wallet</span>
                            <span class="text-[11px] text-slate-400">GoPay, OVO, Dana, ShopeePay</span>
                        </div>
                        <i class="fa-solid fa-qrcode text-lg text-slate-500"></i>
                    </label>

                    <!-- Option 2: BCA VA -->
                    <label class="flex items-center gap-3 p-4 rounded-2xl border border-slate-200 hover:border-brand-500 bg-slate-50/50 cursor-pointer transition">
                        <input type="radio" name="payment_channel" value="BCA Virtual Account (Midtrans)" class="text-brand-600 focus:ring-brand-500" onclick="showChannel('bca')">
                        <div class="flex-1">
                            <span class="font-bold text-slate-800 text-xs block">BCA Virtual Account</span>
                            <span class="text-[11px] text-slate-400">Transfer Otomatis 24 Jam</span>
                        </div>
                        <i class="fa-solid fa-building-columns text-lg text-slate-500"></i>
                    </label>

                    <!-- Option 3: Mandiri Bill -->
                    <label class="flex items-center gap-3 p-4 rounded-2xl border border-slate-200 hover:border-brand-500 bg-slate-50/50 cursor-pointer transition">
                        <input type="radio" name="payment_channel" value="Mandiri Bill Payment (Midtrans)" class="text-brand-600 focus:ring-brand-500" onclick="showChannel('mandiri')">
                        <div class="flex-1">
                            <span class="font-bold text-slate-800 text-xs block">Mandiri Bill Payment</span>
                            <span class="text-[11px] text-slate-400">Livin' by Mandiri & ATM</span>
                        </div>
                        <i class="fa-solid fa-building-columns text-lg text-slate-500"></i>
                    </label>

                    <!-- Option 4: BRI VA -->
                    <label class="flex items-center gap-3 p-4 rounded-2xl border border-slate-200 hover:border-brand-500 bg-slate-50/50 cursor-pointer transition">
                        <input type="radio" name="payment_channel" value="BRI Virtual Account (BRIVA)" class="text-brand-600 focus:ring-brand-500" onclick="showChannel('bri')">
                        <div class="flex-1">
                            <span class="font-bold text-slate-800 text-xs block">BRI Virtual Account (BRIVA)</span>
                            <span class="text-[11px] text-slate-400">BRImo & ATM BRI</span>
                        </div>
                        <i class="fa-solid fa-building-columns text-lg text-slate-500"></i>
                    </label>
                </div>
            </div>

            <!-- Detail Display Per Channel -->
            <div class="p-5 rounded-2xl bg-slate-50 border border-slate-200/80 text-center">

                <!-- QRIS Box (Template Resmi Standar QRIS Nasional) -->
                <div id="box-qris" class="channel-box space-y-4">
                    <p class="text-xs font-semibold text-slate-600 text-center">
                        Scan QRIS menggunakan aplikasi e-Wallet atau Mobile Banking favoritmu:
                    </p>

                    <!-- Card Cetakan QRIS Resmi -->
                    <div class="max-w-[340px] mx-auto bg-white rounded-2xl border border-slate-300 shadow-xl overflow-hidden relative p-5 text-slate-900 font-sans">
                        
                        <!-- Aksen Elemen Dekorasi Merah Khas QRIS -->
                        <div class="absolute left-0 top-16 w-7 h-28 bg-[#e60012] [clip-path:polygon(0_0,100%_50%,0_100%)] pointer-events-none"></div>
                        <div class="absolute right-0 bottom-0 w-20 h-16 bg-[#e60012] [clip-path:polygon(100%_0,100%_100%,0_100%)] pointer-events-none"></div>

                        <!-- Header: Logo QRIS & GPN -->
                        <div class="flex items-center justify-between border-b-2 border-slate-100 pb-2.5 relative z-10">
                            <!-- Logo QRIS -->
                            <div class="text-left">
                                <span class="text-xl font-black italic tracking-tighter text-slate-900 block leading-none">QRIS</span>
                                <span class="text-[6.5px] font-bold text-slate-600 uppercase tracking-tight block mt-0.5">
                                    QR Code Standar Pembayaran Nasional
                                </span>
                            </div>
                            <!-- Logo GPN -->
                            <div class="text-right flex items-center gap-1">
                                <div class="w-4 h-4 bg-[#e60012] rounded-full flex items-center justify-center text-white text-[8px] font-bold">
                                    <i class="fa-solid fa-shield"></i>
                                </div>
                                <span class="text-xs font-black text-[#002d62] tracking-wider">GPN</span>
                            </div>
                        </div>

                        <!-- Merchant Name & NMID -->
                        <div class="text-center my-3 relative z-10">
                            <h3 class="text-sm sm:text-base font-black text-slate-900 tracking-tight uppercase">
                                TANAMAN HIAS MINI
                            </h3>
                            <p class="text-[11px] font-semibold text-slate-600 tracking-wide mt-0.5">
                                NMID: ID102026998877
                            </p>
                        </div>

                        <!-- Matrix QR Code Asli/Simulasi -->
                        <div class="relative z-10 bg-white p-2 border border-slate-200 rounded-xl shadow-xs w-48 h-48 mx-auto flex items-center justify-center my-2">
                            <!-- Menggunakan generator QR otomatis / ganti src ke lokasi gambar QR Anda -->
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=00020101021126580014ID.CO.QRIS.WWW01189360081500000000005204581253033605802ID5918TANAMANHIASMINI6006BOGOR61051630062070703A01630489A1" 
                                alt="QRIS Code" 
                                class="w-full h-full object-contain">
                        </div>

                        <!-- Footer Slogan QRIS -->
                        <div class="text-center mt-3 relative z-10 space-y-0.5">
                            <h4 class="text-xs font-black text-slate-800 tracking-wider uppercase">
                                SATU QRIS UNTUK SEMUA
                            </h4>
                            <p class="text-[9.5px] text-slate-500 font-medium">
                                Cek aplikasi penyelenggara di: <span class="text-slate-700 underline font-semibold">www.aspi-qris.id</span>
                            </p>
                        </div>

                        <!-- Metadata Cetak Bawah -->
                        <div class="flex justify-between items-end text-[8px] text-slate-400 font-mono mt-4 pt-2 border-t border-slate-100 relative z-10">
                            <div>
                                <p>Dicetak oleh: 93600815</p>
                                <p>Versi cetak: 1.1.03.08.2022</p>
                            </div>
                        </div>

                    </div>

                    <p class="text-[11px] text-slate-400 text-center">Masa berlaku simulasi: 24 jam</p>
                </div>

                <!-- BCA Box -->
                <div id="box-bca" class="channel-box hidden space-y-2">
                    <span class="text-xs text-slate-500 block">Nomor Virtual Account BCA:</span>
                    <div class="inline-flex items-center gap-3 bg-white px-5 py-2.5 rounded-xl border border-slate-200 text-lg font-mono font-extrabold text-brand-700">
                        <span>8801 8291 0029 4811</span>
                        <i class="fa-regular fa-copy text-xs text-slate-400 cursor-pointer" title="Salin"></i>
                    </div>
                    <p class="text-[11px] text-slate-400">Atas Nama: <strong>Midtrans - Tanaman Hias Mini</strong></p>
                </div>

                <!-- Mandiri Box -->
                <div id="box-mandiri" class="channel-box hidden space-y-2">
                    <span class="text-xs text-slate-500 block">Kode Perusahaan & Nomor Tagihan Mandiri:</span>
                    <div class="inline-flex items-center gap-3 bg-white px-5 py-2.5 rounded-xl border border-slate-200 text-lg font-mono font-extrabold text-brand-700">
                        <span>70012 - 9918273645</span>
                    </div>
                    <p class="text-[11px] text-slate-400">Atas Nama: <strong>Midtrans - Tanaman Hias Mini</strong></p>
                </div>

                <!-- BRI Box -->
                <div id="box-bri" class="channel-box hidden space-y-2">
                    <span class="text-xs text-slate-500 block">Nomor BRIVA:</span>
                    <div class="inline-flex items-center gap-3 bg-white px-5 py-2.5 rounded-xl border border-slate-200 text-lg font-mono font-extrabold text-brand-700">
                        <span>12938 0002 9182 7364</span>
                    </div>
                    <p class="text-[11px] text-slate-400">Atas Nama: <strong>Midtrans - Tanaman Hias Mini</strong></p>
                </div>
            </div>

            <!-- Submit Simulation Form -->
            <form action="payment.php?order_id=<?= $orderId ?>" method="POST">
                <input type="hidden" name="simulate_payment" value="1">
                <input type="hidden" id="selectedChannelInput" name="channel" value="QRIS (GoPay/ShopeePay)">
                
                <button type="submit" class="w-full py-4 px-6 rounded-2xl bg-brand-600 hover:bg-brand-700 text-white font-extrabold text-sm shadow-lg shadow-brand-600/30 transition flex items-center justify-center gap-2">
                    <i class="fa-solid fa-circle-check text-lg"></i>
                    <span>Lanjutkan Pembayaran</span>
                </button>
            </form>

            <div class="text-center pt-2">
                <a href="orders.php" class="text-xs text-slate-500 hover:text-slate-700 font-medium">
                    Bayar Nanti (Kembali ke Riwayat Pesanan)
                </a>
            </div>

        </div>

    </div>
</div>

<script>
function showChannel(key) {
    document.querySelectorAll('.channel-box').forEach(el => el.classList.add('hidden'));
    const target = document.getElementById('box-' + key);
    if (target) {
        target.classList.remove('hidden');
    }
    const radio = document.querySelector('input[name="payment_channel"]:checked');
    if (radio) {
        document.getElementById('selectedChannelInput').value = radio.value;
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

