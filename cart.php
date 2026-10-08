<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_check.php';
requireUserLogin();

$db = getDB();
$userId = $_SESSION['user_id'];

// Ambil data keranjang dengan join produk
$stmt = $db->prepare("
    SELECT c.id AS cart_id, c.jumlah, p.id AS product_id, p.nama_produk, p.harga_jual, p.stok, p.foto, cat.nama_kategori 
    FROM carts c 
    JOIN products p ON c.product_id = p.id 
    JOIN categories cat ON p.category_id = cat.id 
    WHERE c.user_id = ?
    ORDER BY c.id DESC
");
$stmt->execute([$userId]);
$cartItems = $stmt->fetchAll();

// Hitung total belanja
$totalHarga = 0;
foreach ($cartItems as $item) {
    $totalHarga += ($item['harga_jual'] * $item['jumlah']);
}
$ongkir = !empty($cartItems) ? 15000 : 0;
$totalPembayaran = $totalHarga + $ongkir;

$pageTitle = 'Keranjang Belanja';
require_once __DIR__ . '/includes/header.php';
?>

<div class="bg-white border-b border-slate-200/80 py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-extrabold text-slate-900">Keranjang Belanja</h1>
        <p class="text-xs text-slate-500 mt-1">Periksa kembali tanaman hias mini pilihan Anda sebelum melanjutkan pembayaran.</p>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <?php if (!empty($cartItems)): ?>
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            
            <!-- Items Table List -->
            <div class="lg:col-span-8">
                <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                        <span class="text-sm font-bold text-slate-800">Daftar Produk (<?= count($cartItems) ?> item)</span>
                        <button type="button" onclick="confirmClearCart()" class="text-xs text-red-600 hover:text-white bg-red-50 hover:bg-red-600 border border-red-200 hover:border-red-600 px-3.5 py-2 rounded-xl font-bold flex items-center gap-1.5 transition shadow-sm">
                            <i class="fa-solid fa-trash-can text-[11px]"></i> Kosongkan Keranjang
                        </button>
                    </div>

                    <div class="divide-y divide-slate-100">
                        <?php foreach ($cartItems as $item): ?>
                            <?php $subtotal = $item['harga_jual'] * $item['jumlah']; ?>
                            <div class="p-5 sm:p-6 flex flex-col sm:flex-row items-center gap-4 sm:gap-6">
                                
                                <!-- Product Image -->
                                <div class="w-20 h-20 rounded-2xl bg-slate-100 overflow-hidden flex-shrink-0 border border-slate-100">
                                    <img src="assets/images/products/<?= htmlspecialchars($item['foto']) ?>" 
                                         alt="<?= htmlspecialchars($item['nama_produk']) ?>" 
                                         class="w-full h-full object-cover"
                                         onerror="this.src='assets/images/products/default.jpg'">
                                </div>

                                <!-- Product Info -->
                                <div class="flex-1 text-center sm:text-left">
                                    <span class="text-[11px] font-semibold text-brand-600 bg-brand-50 px-2 py-0.5 rounded-md">
                                        <?= htmlspecialchars($item['nama_kategori']) ?>
                                    </span>
                                    <h3 class="font-bold text-slate-900 text-base mt-1">
                                        <a href="product_detail.php?id=<?= $item['product_id'] ?>" class="hover:text-brand-600">
                                            <?= htmlspecialchars($item['nama_produk']) ?>
                                        </a>
                                    </h3>
                                    <p class="text-xs text-slate-400 mt-0.5">
                                        Harga Satuan: <span class="font-semibold text-slate-700"><?= formatRupiah($item['harga_jual']) ?></span>
                                    </p>
                                </div>

                                <!-- Quantity Controls -->
                                <div class="flex items-center gap-1.5 border border-slate-200 rounded-xl bg-slate-50 p-1">
                                    <form action="cart_action.php" method="POST">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="cart_id" value="<?= $item['cart_id'] ?>">
                                        <input type="hidden" name="delta" value="-1">
                                        <button type="submit" class="w-7 h-7 rounded-lg bg-white hover:bg-slate-100 text-slate-600 flex items-center justify-center text-xs shadow-sm transition">
                                            <i class="fa-solid fa-minus"></i>
                                        </button>
                                    </form>

                                    <span class="w-8 text-center text-xs font-bold text-slate-800">
                                        <?= $item['jumlah'] ?>
                                    </span>

                                    <form action="cart_action.php" method="POST">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="cart_id" value="<?= $item['cart_id'] ?>">
                                        <input type="hidden" name="delta" value="1">
                                        <button type="submit" <?= $item['jumlah'] >= $item['stok'] ? 'disabled' : '' ?> class="w-7 h-7 rounded-lg bg-white hover:bg-slate-100 disabled:opacity-40 text-slate-600 flex items-center justify-center text-xs shadow-sm transition">
                                            <i class="fa-solid fa-plus"></i>
                                        </button>
                                    </form>
                                </div>

                                <!-- Subtotal & Delete -->
                                <div class="text-right sm:w-32 flex sm:flex-col items-center sm:items-end justify-between w-full sm:w-auto">
                                    <div>
                                        <span class="text-[10px] text-slate-400 block sm:inline">Subtotal</span>
                                        <span class="text-sm font-extrabold text-brand-700 block">
                                            <?= formatRupiah($subtotal) ?>
                                        </span>
                                    </div>
                                    <button type="button" onclick="confirmRemoveItem(<?= (int)$item['cart_id'] ?>, '<?= htmlspecialchars(addslashes($item['nama_produk']), ENT_QUOTES) ?>')" class="text-xs text-red-500 hover:text-red-700 sm:mt-2 p-1.5 rounded-lg hover:bg-red-50 transition" title="Hapus Produk">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </div>

                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="p-5 bg-slate-50/70 border-t border-slate-100 flex items-center justify-between">
                        <a href="products.php" class="text-xs font-semibold text-brand-700 hover:underline flex items-center gap-1.5">
                            <i class="fa-solid fa-arrow-left"></i> Tambah Tanaman Lain
                        </a>
                        <span class="text-xs text-slate-400 font-medium">Stok pesanan akan dipesan saat checkout</span>
                    </div>
                </div>
            </div>

            <!-- Checkout Summary Card -->
            <div class="lg:col-span-4">
                <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 space-y-5 sticky top-24">
                    <h2 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3 flex items-center gap-2">
                        <i class="fa-solid fa-receipt text-brand-600"></i> Ringkasan Belanja
                    </h2>

                    <div class="space-y-3 text-xs text-slate-600">
                        <div class="flex items-center justify-between">
                            <span>Total Harga Barang</span>
                            <span class="font-bold text-slate-800"><?= formatRupiah($totalHarga) ?></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span>Estimasi Ongkos Kirim</span>
                            <span class="font-bold text-slate-800"><?= formatRupiah($ongkir) ?></span>
                        </div>
                        <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-sm">
                            <span class="font-bold text-slate-900">Total Pembayaran</span>
                            <span class="font-extrabold text-brand-700 text-base"><?= formatRupiah($totalPembayaran) ?></span>
                        </div>
                    </div>

                    <div class="pt-2">
                        <a href="checkout.php" class="w-full py-3.5 px-4 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-sm shadow-md shadow-brand-600/25 flex items-center justify-center gap-2 transition">
                            <span>Lanjut ke Checkout</span>
                            <i class="fa-solid fa-arrow-right text-xs"></i>
                        </a>
                    </div>

                    <div class="p-3 bg-brand-50/60 rounded-xl border border-brand-100 text-[11px] text-brand-900 space-y-1">
                        <div class="font-bold flex items-center gap-1">
                            <i class="fa-solid fa-shield-halved text-brand-600"></i> Keamanan Belanja
                        </div>
                        <p class="text-slate-500 leading-normal">
                            Transaksi dilindungi sistem sandbox payment simulator terintegrasi.
                        </p>
                    </div>
                </div>
            </div>

        </div>
    <?php else: ?>
        <!-- Empty Cart State -->
        <div class="bg-white rounded-3xl border border-slate-200/80 p-12 text-center max-w-lg mx-auto shadow-sm">
            <div class="w-20 h-20 rounded-full bg-brand-50 text-brand-600 flex items-center justify-center text-3xl mx-auto mb-4">
                <i class="fa-solid fa-cart-shopping"></i>
            </div>
            <h2 class="text-xl font-extrabold text-slate-900">Keranjang Belanja Masih Kosong</h2>
            <p class="text-xs text-slate-500 mt-2 leading-relaxed">
                Anda belum menambahkan tanaman hias mini ke dalam keranjang. Yuk temukan tanaman favorit untuk mempercantik ruanganmu!
            </p>
            <div class="mt-6">
                <a href="products.php" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs shadow-md shadow-brand-600/20 transition">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <span>Jelajahi Produk Sekarang</span>
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function confirmClearCart() {
    Swal.fire({
        title: 'Kosongkan Keranjang?',
        html: '<p class="text-sm text-slate-600">Seluruh tanaman hias pilihan Anda di dalam keranjang akan dihapus.</p>',
        icon: 'warning',
        iconColor: '#ef4444',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i class="fa-solid fa-trash-can mr-1.5"></i> Ya, Kosongkan!',
        cancelButtonText: 'Batal',
        reverseButtons: true,
        customClass: {
            popup: 'rounded-3xl p-6',
            confirmButton: 'rounded-xl px-5 py-2.5 text-xs font-bold shadow-md',
            cancelButton: 'rounded-xl px-5 py-2.5 text-xs font-semibold'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'cart_action.php?action=clear';
        }
    });
}

function confirmRemoveItem(cartId, productName) {
    Swal.fire({
        title: 'Hapus Tanaman?',
        html: '<p class="text-sm text-slate-600">Hapus <strong>' + productName + '</strong> dari keranjang belanja Anda?</p>',
        icon: 'question',
        iconColor: '#f59e0b',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i class="fa-solid fa-trash-can mr-1.5"></i> Hapus',
        cancelButtonText: 'Batal',
        reverseButtons: true,
        customClass: {
            popup: 'rounded-3xl p-6',
            confirmButton: 'rounded-xl px-5 py-2.5 text-xs font-bold shadow-md',
            cancelButton: 'rounded-xl px-5 py-2.5 text-xs font-semibold'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'cart_action.php?action=remove&id=' + cartId;
        }
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

