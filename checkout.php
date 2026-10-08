<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_check.php';
requireUserLogin();

$db = getDB();
$userId = $_SESSION['user_id'];

// Ambil user
$stmtUser = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmtUser->execute([$userId]);
$user = $stmtUser->fetch();

// Cek apakah mode Pembelian Langsung (Buy Now) atau Checkout Keranjang
$checkoutMode = ($_GET['mode'] ?? $_POST['checkout_mode'] ?? '') === 'buy_now' ? 'buy_now' : 'cart';
$cartItems = [];

if ($checkoutMode === 'buy_now' && !empty($_SESSION['buy_now_item']['product_id'])) {
    $bnProductId = (int)$_SESSION['buy_now_item']['product_id'];
    $bnQty = max(1, (int)$_SESSION['buy_now_item']['jumlah']);

    $stmtDirect = $db->prepare("
        SELECT id AS product_id, nama_produk, harga_modal, harga_jual, stok, foto 
        FROM products 
        WHERE id = ?
    ");
    $stmtDirect->execute([$bnProductId]);
    $directProd = $stmtDirect->fetch();

    if ($directProd && $directProd['stok'] > 0) {
        $directProd['jumlah'] = min($bnQty, (int)$directProd['stok']);
        $cartItems[] = $directProd;
    }
} else {
    $checkoutMode = 'cart';
    $stmtCart = $db->prepare("
        SELECT c.*, p.nama_produk, p.harga_modal, p.harga_jual, p.stok, p.foto 
        FROM carts c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = ?
    ");
    $stmtCart->execute([$userId]);
    $cartItems = $stmtCart->fetchAll();
}

if (empty($cartItems)) {
    setFlash('warning', 'Tidak ada produk untuk di-checkout.');
    header("Location: products.php");
    exit;
}

// Hitung total
$totalHarga = 0;
foreach ($cartItems as $item) {
    $totalHarga += ($item['harga_jual'] * $item['jumlah']);
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $alamatPengiriman = sanitize($_POST['alamat_pengiriman'] ?? '');
    $catatan = sanitize($_POST['catatan'] ?? '');
    $metodePembayaran = sanitize($_POST['metode_pembayaran'] ?? 'Midtrans Sandbox');
    $opsiOngkir = (float)($_POST['ongkir'] ?? 15000);

    if (empty($alamatPengiriman)) {
        $error = 'Alamat pengiriman wajib diisi.';
    } else {
        try {
            // Validasi stok ulang
            foreach ($cartItems as $item) {
                $stmtCheck = $db->prepare("SELECT stok, nama_produk FROM products WHERE id = ?");
                $stmtCheck->execute([$item['product_id']]);
                $cur = $stmtCheck->fetch();
                if ($cur['stok'] < $item['jumlah']) {
                    throw new Exception("Stok untuk produk " . $cur['nama_produk'] . " tidak mencukupi (tersisa " . $cur['stok'] . "). Silakan sesuaikan keranjang Anda.");
                }
            }

            $db->beginTransaction();

            // Buat nomor invoice unik
            $invoice = 'INV-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
            $totalPembayaran = $totalHarga + $opsiOngkir;

            // Simpan ke tabel orders
            $stmtOrder = $db->prepare("
                INSERT INTO orders (user_id, invoice, total_harga, ongkir, total_pembayaran, status_pembayaran, status_pesanan, alamat_pengiriman, catatan) 
                VALUES (?, ?, ?, ?, ?, 'Menunggu Pembayaran', 'Menunggu Pembayaran', ?, ?)
            ");
            $stmtOrder->execute([
                $userId,
                $invoice,
                $totalHarga,
                $opsiOngkir,
                $totalPembayaran,
                $alamatPengiriman,
                $catatan
            ]);
            $orderId = $db->lastInsertId();

            // Simpan ke order_details
            $stmtDetail = $db->prepare("
                INSERT INTO order_details (order_id, product_id, jumlah, harga, harga_modal, subtotal) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            foreach ($cartItems as $item) {
                $subtotal = $item['harga_jual'] * $item['jumlah'];
                $stmtDetail->execute([
                    $orderId,
                    $item['product_id'],
                    $item['jumlah'],
                    $item['harga_jual'],
                    $item['harga_modal'],
                    $subtotal
                ]);
            }

            // Simpan log awal payment
            $stmtPay = $db->prepare("
                INSERT INTO payments (order_id, metode_pembayaran, status) 
                VALUES (?, ?, 'pending')
            ");
            $stmtPay->execute([$orderId, $metodePembayaran]);

            if ($checkoutMode === 'buy_now') {
                unset($_SESSION['buy_now_item']);
            } else {
                $stmtClearCart = $db->prepare("DELETE FROM carts WHERE user_id = ?");
                $stmtClearCart->execute([$userId]);
            }

            $db->commit();

            // Arahkan ke halaman pembayaran sandbox
            header("Location: payment.php?order_id=" . $orderId);
            exit;

        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $error = $e->getMessage();
        }
    }
}

$pageTitle = 'Checkout Pesanan';
require_once __DIR__ . '/includes/header.php';
?>

<div class="bg-white border-b border-slate-200/80 py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-extrabold text-slate-900">Checkout Pesanan</h1>
        <p class="text-xs text-slate-500 mt-1">Lengkapi alamat pengiriman dan pilih metode pembayaran Anda.</p>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    
    <?php if ($error): ?>
        <div class="mb-6 p-4 rounded-2xl bg-red-50 border border-red-200 text-red-700 text-xs flex items-center gap-2">
            <i class="fa-solid fa-circle-exclamation text-sm flex-shrink-0"></i>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
    <?php endif; ?>

    <form action="checkout.php<?= $checkoutMode === 'buy_now' ? '?mode=buy_now' : '' ?>" method="POST" id="checkoutForm">
        <input type="hidden" name="checkout_mode" value="<?= htmlspecialchars($checkoutMode) ?>">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            
            <!-- Left: Checkout Details Form -->
            <div class="lg:col-span-7 space-y-6">
                
                <!-- Data Pembeli Card -->
                <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm">
                    <h2 class="text-base font-bold text-slate-900 mb-4 flex items-center gap-2">
                        <i class="fa-solid fa-user-check text-brand-600"></i> Informasi Pembeli
                    </h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                        <div class="p-3 bg-slate-50 rounded-xl">
                            <span class="text-slate-400 block">Nama Lengkap</span>
                            <span class="font-bold text-slate-800 text-sm"><?= htmlspecialchars($user['nama']) ?></span>
                        </div>
                        <div class="p-3 bg-slate-50 rounded-xl">
                            <span class="text-slate-400 block">Email</span>
                            <span class="font-bold text-slate-800 text-sm"><?= htmlspecialchars($user['email']) ?></span>
                        </div>
                        <div class="p-3 bg-slate-50 rounded-xl sm:col-span-2">
                            <span class="text-slate-400 block">Nomor WhatsApp / HP</span>
                            <span class="font-bold text-slate-800 text-sm"><?= htmlspecialchars($user['no_hp']) ?></span>
                        </div>
                    </div>
                </div>

                <!-- Alamat Pengiriman -->
                <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm">
                    <h2 class="text-base font-bold text-slate-900 mb-4 flex items-center gap-2">
                        <i class="fa-solid fa-location-dot text-brand-600"></i> Alamat Pengiriman
                    </h2>
                    <div class="space-y-4">
                        <div>
                            <label for="alamat_pengiriman" class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Alamat Tujuan Lengkap (Bisa diedit khusus pesanan ini)
                            </label>
                            <textarea id="alamat_pengiriman" name="alamat_pengiriman" required rows="3" class="w-full p-3.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-brand-500"><?= htmlspecialchars($user['alamat']) ?></textarea>
                        </div>
                        <div>
                            <label for="catatan" class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Catatan Khusus untuk Toko (Opsional)
                            </label>
                            <input type="text" id="catatan" name="catatan" placeholder="Contoh: Tolong bungkus ekstra tebal / taruh di depan pagar" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-brand-500">
                        </div>
                    </div>
                </div>

                <!-- Opsi Pengiriman & Ekspedisi -->
                <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm">
                    <h2 class="text-base font-bold text-slate-900 mb-4 flex items-center gap-2">
                        <i class="fa-solid fa-truck-fast text-brand-600"></i> Opsi Ekspedisi Khusus Tanaman
                    </h2>
                    <div class="space-y-3">
                        <label class="flex items-center justify-between p-4 rounded-2xl border border-brand-500 bg-brand-50/40 cursor-pointer">
                            <div class="flex items-center gap-3">
                                <input type="radio" name="ongkir" value="15000" checked onchange="updateSummary(15000)" class="text-brand-600 focus:ring-brand-500">
                                <div>
                                    <span class="text-sm font-bold text-slate-800 block">J&T / SiCepat Reguler (Pengemasan Botani)</span>
                                    <span class="text-xs text-slate-500">Estimasi 2-3 hari tiba • Dilengkapi moss basah & bubble</span>
                                </div>
                            </div>
                            <span class="text-sm font-extrabold text-brand-700">Rp 15.000</span>
                        </label>
                        <label class="flex items-center justify-between p-4 rounded-2xl border border-slate-200 hover:border-brand-300 bg-white cursor-pointer transition">
                            <div class="flex items-center gap-3">
                                <input type="radio" name="ongkir" value="25000" onchange="updateSummary(25000)" class="text-brand-600 focus:ring-brand-500">
                                <div>
                                    <span class="text-sm font-bold text-slate-800 block">Next Day / Kilat Express</span>
                                    <span class="text-xs text-slate-500">Estimasi 1 hari tiba • Sangat direkomendasikan untuk luar pulau</span>
                                </div>
                            </div>
                            <span class="text-sm font-extrabold text-slate-800">Rp 25.000</span>
                        </label>
                    </div>
                </div>

                <!-- Metode Pembayaran -->
                <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm">
                    <h2 class="text-base font-bold text-slate-900 mb-4 flex items-center gap-2">
                        <i class="fa-solid fa-wallet text-brand-600"></i> Metode Pembayaran
                    </h2>
                    <div class="space-y-3">
                        <label class="flex items-center justify-between p-4 rounded-2xl border border-brand-500 bg-brand-50/40 cursor-pointer">
                            <div class="flex items-center gap-3">
                                <input type="radio" name="metode_pembayaran" value="Midtrans Sandbox" checked class="text-brand-600 focus:ring-brand-500">
                                <div>
                                    <span class="text-sm font-bold text-slate-800 block">Midtrans Sandbox Payment Gateway</span>
                                    <span class="text-xs text-slate-500">Mendukung QRIS (GoPay/ShopeePay/Dana), Virtual Account BCA, Mandiri, BRI</span>
                                </div>
                            </div>
                            <span class="text-xs font-bold text-brand-700 bg-brand-100 px-2.5 py-1 rounded-full">Sandbox Mode</span>
                        </label>
                    </div>
                </div>

            </div>

            <!-- Right: Order Summary -->
            <div class="lg:col-span-5">
                <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm space-y-6 sticky top-24">
                    <h2 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3 flex items-center gap-2">
                        <i class="fa-solid fa-basket-shopping text-brand-600"></i> Rincian Pesanan
                    </h2>

                    <!-- Items List Mini -->
                    <div class="divide-y divide-slate-100 max-h-72 overflow-y-auto pr-1">
                        <?php foreach ($cartItems as $item): ?>
                            <div class="py-3 flex items-center gap-3">
                                <div class="w-12 h-12 rounded-xl bg-slate-100 overflow-hidden flex-shrink-0 border border-slate-100">
                                    <img src="assets/images/products/<?= htmlspecialchars($item['foto']) ?>" 
                                         alt="<?= htmlspecialchars($item['nama_produk']) ?>" 
                                         class="w-full h-full object-cover"
                                         onerror="this.src='assets/images/products/default.jpg'">
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-xs font-bold text-slate-900 truncate"><?= htmlspecialchars($item['nama_produk']) ?></h4>
                                    <p class="text-[11px] text-slate-500"><?= $item['jumlah'] ?> x <?= formatRupiah($item['harga_jual']) ?></p>
                                </div>
                                <span class="text-xs font-bold text-slate-800">
                                    <?= formatRupiah($item['harga_jual'] * $item['jumlah']) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Cost Breakdown -->
                    <div class="pt-4 border-t border-slate-100 space-y-2.5 text-xs text-slate-600">
                        <div class="flex justify-between">
                            <span>Subtotal Produk</span>
                            <span class="font-bold text-slate-800" id="textSubtotal"><?= formatRupiah($totalHarga) ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span>Biaya Pengiriman</span>
                            <span class="font-bold text-slate-800" id="textOngkir">Rp 15.000</span>
                        </div>
                        <div class="pt-3 border-t border-slate-100 flex justify-between text-sm">
                            <span class="font-bold text-slate-900">Total Pembayaran</span>
                            <span class="font-extrabold text-brand-700 text-lg" id="textTotal"><?= formatRupiah($totalHarga + 15000) ?></span>
                        </div>
                    </div>

                    <!-- Place Order CTA -->
                    <button type="submit" class="w-full py-4 px-4 rounded-2xl bg-brand-600 hover:bg-brand-700 text-white font-extrabold text-sm shadow-lg shadow-brand-600/30 transition flex items-center justify-center gap-2">
                        <i class="fa-solid fa-lock"></i>
                        <span>Bayar Sekarang</span>
                    </button>

                    <p class="text-[11px] text-center text-slate-400">
                        Dengan menyelesaikan pesanan, Anda menyetujui ketentuan transaksi.
                    </p>
                </div>
            </div>

        </div>
    </form>
</div>

<script>
const baseSubtotal = <?= (float)$totalHarga ?>;

function formatRupiahJs(number) {
    return 'Rp ' + number.toLocaleString('id-ID');
}

function updateSummary(ongkir) {
    document.getElementById('textOngkir').innerText = formatRupiahJs(ongkir);
    document.getElementById('textTotal').innerText = formatRupiahJs(baseSubtotal + ongkir);
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

