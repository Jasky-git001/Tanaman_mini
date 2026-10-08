<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_check.php';

$productId = (int)($_GET['id'] ?? 0);
if ($productId <= 0) {
    header("Location: products.php");
    exit;
}

$db = getDB();

// Pastikan status akses Admin vs Pembeli
$isAdmin = isAdminLoggedIn();
$isUser = isUserLoggedIn();
$userId = $isUser ? (int)$_SESSION['user_id'] : 0;

// Cek apakah user yang login sudah pernah membeli produk ini (pesanan lunas / diproses / dikirim / selesai)
$purchasedOrder = null;
$myReview = null;

$pendingOrder = null;
if ($isUser && $userId > 0) {
    // Hanya pesanan yang TELAH DIKONFIRMASI 'Selesai' oleh pembeli yang berhak memberikan Rating & Komentar
    $stmtPurchase = $db->prepare("
        SELECT o.id AS order_id, o.invoice, o.status_pesanan, o.tanggal 
        FROM orders o 
        JOIN order_details od ON o.id = od.order_id 
        WHERE o.user_id = ? 
          AND od.product_id = ? 
          AND o.status_pesanan = 'Selesai'
        ORDER BY o.id DESC 
        LIMIT 1
    ");
    $stmtPurchase->execute([$userId, $productId]);
    $purchasedOrder = $stmtPurchase->fetch();

    // Cek apakah ada pesanan untuk produk ini yang masih berjalan (Diproses / Dikemas / Dikirim / Pengembalian)
    if (!$purchasedOrder) {
        $stmtPend = $db->prepare("
            SELECT o.id AS order_id, o.invoice, o.status_pesanan 
            FROM orders o 
            JOIN order_details od ON o.id = od.order_id 
            WHERE o.user_id = ? 
              AND od.product_id = ? 
              AND o.status_pesanan IN ('Menunggu Pembayaran', 'Pembayaran Berhasil', 'Diproses', 'Dikemas', 'Dikirim', 'Pengembalian Diajukan')
            ORDER BY o.id DESC 
            LIMIT 1
        ");
        $stmtPend->execute([$userId, $productId]);
        $pendingOrder = $stmtPend->fetch();
    }

    // Cek apakah user sudah pernah memberi ulasan pada produk ini
    $stmtMyRev = $db->prepare("SELECT * FROM product_reviews WHERE user_id = ? AND product_id = ? LIMIT 1");
    $stmtMyRev->execute([$userId, $productId]);
    $myReview = $stmtMyRev->fetch();
}

// Handler pengiriman Rating & Komentar dari Pembeli yang sudah membeli
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if ($isAdmin) {
        setFlash('warning', 'Admin tidak diperkenankan memberikan rating atau ulasan produk.');
        header("Location: product_detail.php?id=$productId#ulasan");
        exit;
    }

    if (!$isUser || !$purchasedOrder) {
        setFlash('error', 'Anda harus membeli tanaman ini terlebih dahulu sebelum dapat memberikan rating dan komentar.');
        header("Location: product_detail.php?id=$productId#ulasan");
        exit;
    }

    $inputRating = (int)($_POST['rating'] ?? 5);
    if ($inputRating < 1) $inputRating = 1;
    if ($inputRating > 5) $inputRating = 5;
    $inputKomentar = sanitize($_POST['komentar'] ?? '');

    if (empty($inputKomentar)) {
        setFlash('error', 'Komentar ulasan tidak boleh kosong.');
        header("Location: product_detail.php?id=$productId#ulasan");
        exit;
    }

    try {
        $stmtSaveRev = $db->prepare("
            INSERT INTO product_reviews (product_id, user_id, order_id, rating, komentar) 
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE rating = VALUES(rating), komentar = VALUES(komentar), order_id = VALUES(order_id), created_at = CURRENT_TIMESTAMP
        ");
        $stmtSaveRev->execute([
            $productId,
            $userId,
            $purchasedOrder['order_id'],
            $inputRating,
            $inputKomentar
        ]);

        // Hitung ulang rata-rata rating produk secara otomatis
        syncProductRating($db, $productId);

        setFlash('success', 'Terima kasih! Penilaian rating dan komentar hasil pembelian Anda berhasil disimpan.');
        header("Location: product_detail.php?id=$productId#ulasan");
        exit;
    } catch (Exception $e) {
        setFlash('error', 'Gagal menyimpan ulasan: ' . $e->getMessage());
        header("Location: product_detail.php?id=$productId#ulasan");
        exit;
    }
}

// Sinkronisasi rating produk dari ulasan
syncProductRating($db, $productId);

// Ambil data produk terbaru
$stmt = $db->prepare("
    SELECT p.*, c.nama_kategori, c.slug AS cat_slug 
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    WHERE p.id = ?
");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    header("Location: products.php");
    exit;
}

// Ambil seluruh daftar ulasan & komentar pembeli untuk produk ini
$stmtReviews = $db->prepare("
    SELECT r.*, u.nama AS nama_pembeli, o.invoice 
    FROM product_reviews r 
    JOIN users u ON r.user_id = u.id 
    LEFT JOIN orders o ON r.order_id = o.id 
    WHERE r.product_id = ? 
    ORDER BY r.created_at DESC
");
$stmtReviews->execute([$productId]);
$reviews = $stmtReviews->fetchAll();
$reviewCount = count($reviews);

// Hitung distribusi bintang (5 sampai 1)
$starCounts = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
foreach ($reviews as $rev) {
    $rt = (int)$rev['rating'];
    if (isset($starCounts[$rt])) {
        $starCounts[$rt]++;
    }
}

// Total terjual dari pesanan yang berhasil
$stmtSold = $db->prepare("
    SELECT COALESCE(SUM(od.jumlah), 0) 
    FROM order_details od 
    JOIN orders o ON od.order_id = o.id 
    WHERE od.product_id = ? AND (o.status_pembayaran = 'Pembayaran Berhasil' OR o.status_pesanan IN ('Diproses', 'Dikemas', 'Dikirim', 'Selesai'))
");
$stmtSold->execute([$productId]);
$totalSold = (int)$stmtSold->fetchColumn();

$pageTitle = $product['nama_produk'];
require_once __DIR__ . '/includes/header.php';

// Ambil produk terkait dari kategori yang sama
$stmtRelated = $db->prepare("
    SELECT p.*, c.nama_kategori 
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    WHERE p.category_id = ? AND p.id != ? 
    LIMIT 4
");
$stmtRelated->execute([$product['category_id'], $productId]);
$relatedProducts = $stmtRelated->fetchAll();
?>

<!-- Breadcrumbs Header -->
<div class="bg-white border-b border-slate-200/80 py-4">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center gap-2 text-xs font-medium text-slate-400">
        <a href="index.php" class="hover:text-brand-600">Home</a>
        <span>/</span>
        <a href="products.php" class="hover:text-brand-600">Produk</a>
        <span>/</span>
        <a href="products.php?category=<?= urlencode($product['cat_slug']) ?>" class="hover:text-brand-600 capitalize"><?= htmlspecialchars($product['nama_kategori']) ?></a>
        <span>/</span>
        <span class="text-slate-800 truncate"><?= htmlspecialchars($product['nama_produk']) ?></span>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 space-y-12">

    <!-- Main Product Detail Card -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden p-6 sm:p-10">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
            
            <!-- Left: Product Image -->
            <div class="lg:col-span-6">
                <div class="h-96 sm:h-[460px] rounded-2xl bg-slate-100 overflow-hidden relative border border-slate-200/70 flex items-center justify-center group">
                    <img src="assets/images/products/<?= htmlspecialchars($product['foto']) ?>" 
                         alt="<?= htmlspecialchars($product['nama_produk']) ?>" 
                         class="w-full h-full object-cover group-hover:scale-105 transition duration-500"
                         onerror="this.src='assets/images/products/default.jpg'">
                    
                    <span class="absolute top-4 left-4 bg-white/90 backdrop-blur text-slate-800 text-xs font-semibold px-3 py-1.5 rounded-xl shadow-sm">
                        <?= htmlspecialchars($product['nama_kategori']) ?>
                    </span>
                </div>
            </div>

            <!-- Right: Product Information, Description & Two Purchase Options -->
            <div class="lg:col-span-6 flex flex-col">
                
                <!-- Rating & Sold Summary Bar -->
                <div class="flex flex-wrap items-center gap-3 mb-3 text-xs">
                    <a href="#ulasan" class="flex items-center gap-1.5 bg-amber-50 hover:bg-amber-100 border border-amber-200/80 px-3 py-1 rounded-full transition">
                        <div class="flex items-center text-amber-400">
                            <?php 
                                $stars = round((float)$product['rating']);
                                for ($i = 1; $i <= 5; $i++): 
                            ?>
                                <i class="fa-solid fa-star <?= $i <= $stars ? 'text-amber-400' : 'text-slate-200' ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <span class="font-extrabold text-slate-800">
                            <?= $reviewCount > 0 ? number_format((float)$product['rating'], 1) : '0.0' ?>
                        </span>
                        <span class="text-slate-500 font-medium">
                            (<?= $reviewCount ?> Ulasan Pembeli)
                        </span>
                    </a>
                    <span class="text-slate-300">•</span>
                    <span class="text-slate-600 font-medium">
                        <strong class="text-slate-900"><?= $totalSold ?></strong> pot terjual
                    </span>
                </div>

                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 leading-tight mb-4">
                    <?= htmlspecialchars($product['nama_produk']) ?>
                </h1>

                <!-- Price Box -->
                <div class="p-4 rounded-2xl bg-brand-50/70 border border-brand-100 mb-6 flex items-center justify-between">
                    <div>
                        <span class="text-xs text-slate-500 font-medium block">Harga Satuan</span>
                        <span class="text-3xl font-extrabold text-brand-700">
                            <?= formatRupiah($product['harga_jual']) ?>
                        </span>
                    </div>
                    <div class="text-right">
                        <span class="text-xs text-slate-500 font-medium block mb-0.5">Ketersediaan Stok</span>
                        <?php if ($product['stok'] > 0): ?>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Tersedia (<?= $product['stok'] ?> pot)
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-red-100 text-red-800">
                                <span class="w-2 h-2 rounded-full bg-red-500"></span> Stok Habis
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Description -->
                <div class="mb-6 space-y-2">
                    <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                        <i class="fa-solid fa-leaf text-brand-600"></i> Deskripsi Tanaman
                    </h3>
                    <p class="text-slate-600 text-sm leading-relaxed whitespace-pre-line">
                        <?= htmlspecialchars($product['deskripsi']) ?>
                    </p>
                </div>

                <!-- Quick Plant Care Guide Card -->
                <div class="grid grid-cols-3 gap-3 p-3.5 bg-slate-50 rounded-2xl border border-slate-100 text-center mb-6">
                    <div>
                        <i class="fa-solid fa-droplet text-brand-600 text-sm mb-1 block"></i>
                        <span class="text-[11px] font-bold text-slate-700 block">Penyiraman</span>
                        <span class="text-[10px] text-slate-500">1-2x seminggu</span>
                    </div>
                    <div>
                        <i class="fa-solid fa-sun text-amber-500 text-sm mb-1 block"></i>
                        <span class="text-[11px] font-bold text-slate-700 block">Pencahayaan</span>
                        <span class="text-[10px] text-slate-500">Teduh / Indoor</span>
                    </div>
                    <div>
                        <i class="fa-solid fa-ruler-combined text-blue-500 text-sm mb-1 block"></i>
                        <span class="text-[11px] font-bold text-slate-700 block">Ukuran Pot</span>
                        <span class="text-[10px] text-slate-500">Mini (8-10 cm)</span>
                    </div>
                </div>

                <!-- Product Action Area (Dua Opsi Pembelian: Tambah ke Keranjang & Langsung Beli) -->
                <div class="mt-auto pt-6 border-t border-slate-100">
                    <?php if ($isAdmin): ?>
                        <!-- Admin View: Hanya Pratinjau & Edit Produk (Tidak Bisa Beli/Checkout) -->
                        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 space-y-3">
                            <div class="flex items-center gap-2 text-amber-900 text-xs font-bold">
                                <i class="fa-solid fa-user-shield text-amber-600"></i>
                                <span>Mode Pratinjau Administrator (Tidak Dapat Checkout)</span>
                            </div>
                            <p class="text-xs text-amber-800 leading-relaxed">
                                Anda sedang melihat halaman detail produk sebagai Admin. Akun Admin tidak dapat membeli atau melakukan checkout barang.
                            </p>
                            <div class="flex flex-col sm:flex-row items-center gap-2.5 pt-1">
                                <a href="admin/product_edit.php?id=<?= $product['id'] ?>" class="w-full sm:flex-1 py-3 px-4 rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs shadow-sm transition text-center flex items-center justify-center gap-2">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                    <span>Edit Produk Ini</span>
                                </a>
                                <a href="admin/products.php" class="w-full sm:w-auto py-3 px-4 rounded-xl bg-white border border-amber-300 text-amber-800 hover:bg-amber-100 font-bold text-xs transition text-center flex items-center justify-center gap-2">
                                    <i class="fa-solid fa-arrow-left"></i>
                                    <span>Kelola Produk</span>
                                </a>
                            </div>
                        </div>

                    <?php elseif ($isUser): ?>
                        <!-- User View: Pilih Jumlah + 2 Opsi (Tambah ke Keranjang & Langsung Beli) -->
                        <form action="cart_action.php" method="POST" class="space-y-4">
                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">

                            <div class="flex items-center justify-between bg-slate-50 px-4 py-2.5 rounded-2xl border border-slate-200/80">
                                <span class="text-xs font-bold text-slate-700">Atur Jumlah Pembelian:</span>
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center border border-slate-200 rounded-xl bg-white shadow-sm">
                                        <button type="button" onclick="decrementQty()" class="w-8 h-8 flex items-center justify-center text-slate-600 hover:text-brand-600 hover:bg-slate-50 rounded-l-xl transition" <?= $product['stok'] <= 0 ? 'disabled' : '' ?>>
                                            <i class="fa-solid fa-minus text-xs"></i>
                                        </button>
                                        <input type="number" id="detailQty" name="jumlah" value="<?= $product['stok'] > 0 ? '1' : '0' ?>" min="1" max="<?= $product['stok'] ?>" class="w-11 text-center bg-transparent border-none text-sm font-extrabold text-slate-800 focus:outline-none" readonly>
                                        <button type="button" onclick="incrementQty(<?= $product['stok'] ?>)" class="w-8 h-8 flex items-center justify-center text-slate-600 hover:text-brand-600 hover:bg-slate-50 rounded-r-xl transition" <?= $product['stok'] <= 0 ? 'disabled' : '' ?>>
                                            <i class="fa-solid fa-plus text-xs"></i>
                                        </button>
                                    </div>
                                    <span class="text-xs text-slate-400">Stok: <?= $product['stok'] ?></span>
                                </div>
                            </div>

                            <?php if ($product['stok'] > 0): ?>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <!-- Opsi 1: Tambahkan ke dalam Keranjang terlebih dahulu -->
                                    <button type="submit" name="action" value="add" class="py-3.5 px-5 rounded-2xl border-2 border-brand-600 bg-brand-50/60 hover:bg-brand-100 text-brand-700 font-extrabold text-sm transition flex items-center justify-center gap-2">
                                        <i class="fa-solid fa-cart-plus"></i>
                                        <span>+ Keranjang</span>
                                    </button>

                                    <!-- Opsi 2: Langsung Beli (Menuju Checkout) -->
                                    <button type="submit" name="action" value="buy_now" class="py-3.5 px-5 rounded-2xl bg-brand-600 hover:bg-brand-700 text-white font-extrabold text-sm shadow-lg shadow-brand-600/25 hover:shadow-brand-600/35 transition flex items-center justify-center gap-2">
                                        <i class="fa-solid fa-bolt"></i>
                                        <span>Langsung Beli</span>
                                    </button>
                                </div>
                            <?php else: ?>
                                <button type="button" disabled class="w-full py-3.5 px-6 rounded-2xl bg-slate-200 text-slate-500 font-bold text-sm cursor-not-allowed">
                                    Stok Tanaman Sedang Habis
                                </button>
                            <?php endif; ?>
                        </form>

                    <?php else: ?>
                        <!-- Guest View: Tampilkan 2 Opsi yang mengarahkan ke Login Pembeli -->
                        <div class="space-y-3">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <a href="login.php?redirect=<?= urlencode('product_detail.php?id=' . $product['id']) ?>" class="py-3.5 px-5 rounded-2xl border-2 border-brand-600 bg-brand-50/60 hover:bg-brand-100 text-brand-700 font-extrabold text-sm transition flex items-center justify-center gap-2">
                                    <i class="fa-solid fa-cart-plus"></i>
                                    <span>+ Keranjang</span>
                                </a>
                                <a href="login.php?redirect=<?= urlencode('product_detail.php?id=' . $product['id']) ?>" class="py-3.5 px-5 rounded-2xl bg-brand-600 hover:bg-brand-700 text-white font-extrabold text-sm shadow-lg shadow-brand-600/25 transition flex items-center justify-center gap-2">
                                    <i class="fa-solid fa-bolt"></i>
                                    <span>Langsung Beli</span>
                                </a>
                            </div>
                            <p class="text-[11px] text-center text-slate-400">
                                <i class="fa-solid fa-circle-info mr-1"></i> Silakan masuk ke akun Pembeli untuk memasukkan ke keranjang atau membeli langsung.
                            </p>
                        </div>
                    <?php endif; ?>
                </div>

            </div>

        </div>
    </div>

    <!-- Section Rating & Komentar Hasil Pembelian -->
    <div id="ulasan" class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 sm:p-10 scroll-mt-24">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-6 border-b border-slate-100 mb-8">
            <div>
                <span class="text-brand-600 text-xs font-bold uppercase tracking-wider">Penilaian Terverifikasi</span>
                <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900 mt-0.5">
                    Rating & Komentar Hasil Pembelian (<?= $reviewCount ?>)
                </h2>
                <p class="text-xs text-slate-500 mt-1">
                    Penilaian murni diberikan oleh pelanggan yang telah membeli tanaman ini (bukan diisi oleh Admin).
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            
            <!-- Left Column: Skor Rata-rata & Form Ulasan Pembeli -->
            <div class="lg:col-span-5 space-y-6">
                
                <!-- Ringkasan Skor Bintang -->
                <div class="p-6 rounded-2xl bg-slate-50 border border-slate-200/70">
                    <div class="flex items-center gap-6">
                        <div class="text-center">
                            <span class="text-4xl font-extrabold text-slate-900 block">
                                <?= $reviewCount > 0 ? number_format((float)$product['rating'], 1) : '0.0' ?>
                            </span>
                            <div class="flex items-center justify-center text-amber-400 text-xs my-1.5">
                                <?php 
                                    $avgRound = round((float)$product['rating']);
                                    for ($i = 1; $i <= 5; $i++): 
                                ?>
                                    <i class="fa-solid fa-star <?= $i <= $avgRound ? 'text-amber-400' : 'text-slate-300' ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <span class="text-[11px] text-slate-500 font-medium"><?= $reviewCount ?> ulasan</span>
                        </div>

                        <!-- Bar Distribusi Bintang -->
                        <div class="flex-1 space-y-1.5">
                            <?php for ($star = 5; $star >= 1; $star--): ?>
                                <?php 
                                    $cnt = $starCounts[$star];
                                    $pct = $reviewCount > 0 ? round(($cnt / $reviewCount) * 100) : 0;
                                ?>
                                <div class="flex items-center gap-2 text-xs">
                                    <span class="w-8 font-bold text-slate-600 flex items-center gap-1">
                                        <?= $star ?> <i class="fa-solid fa-star text-amber-400 text-[10px]"></i>
                                    </span>
                                    <div class="flex-1 h-2 bg-slate-200 rounded-full overflow-hidden">
                                        <div class="h-full bg-amber-400 rounded-full" style="width: <?= $pct ?>%"></div>
                                    </div>
                                    <span class="w-6 text-right text-[11px] text-slate-400"><?= $cnt ?></span>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>

                <!-- Form Beri Rating & Komentar (Hanya muncul jika User sudah membeli produk ini) -->
                <?php if ($isUser && $purchasedOrder): ?>
                    <div class="p-6 rounded-2xl bg-brand-50/60 border border-brand-200">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="w-7 h-7 rounded-lg bg-brand-600 text-white flex items-center justify-center text-xs">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </span>
                            <div>
                                <h3 class="text-sm font-extrabold text-slate-900">
                                    <?= $myReview ? 'Perbarui Ulasan Pembelian Anda' : 'Beri Rating & Komentar Pembelian' ?>
                                </h3>
                                <p class="text-[11px] text-brand-700 font-medium">
                                    Terverifikasi dari pesanan <strong><?= htmlspecialchars($purchasedOrder['invoice']) ?></strong>
                                </p>
                            </div>
                        </div>

                        <form action="product_detail.php?id=<?= $product['id'] ?>#ulasan" method="POST" class="space-y-4 mt-4">
                            <input type="hidden" name="submit_review" value="1">
                            
                            <!-- Pilih Bintang Interaktif -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1.5">Pilih Rating Bintang:</label>
                                <?php $selectedRating = (int)($myReview['rating'] ?? 5); ?>
                                <input type="hidden" name="rating" id="ratingInput" value="<?= $selectedRating ?>">
                                <div class="flex items-center gap-2" id="starPicker">
                                    <?php for ($s = 1; $s <= 5; $s++): ?>
                                        <button type="button" onclick="setRating(<?= $s ?>)" data-star="<?= $s ?>" class="star-btn text-2xl transition transform hover:scale-110 focus:outline-none <?= $s <= $selectedRating ? 'text-amber-400' : 'text-slate-300' ?>">
                                            <i class="fa-solid fa-star"></i>
                                        </button>
                                    <?php endfor; ?>
                                    <span id="ratingLabel" class="ml-2 text-xs font-bold text-amber-700 bg-amber-100 px-2.5 py-1 rounded-lg">
                                        <?= $selectedRating ?> / 5 Bintang
                                    </span>
                                </div>
                            </div>

                            <!-- Input Komentar -->
                            <div>
                                <label for="komentar" class="block text-xs font-bold text-slate-700 mb-1.5">Komentar / Ulasan Anda:</label>
                                <textarea id="komentar" name="komentar" rows="3" required placeholder="Ceritakan pengalaman Anda mengenai kesegaran tanaman, pot, dan pengemasan..." class="w-full p-3 rounded-xl border border-slate-200 bg-white text-xs sm:text-sm focus:outline-none focus:border-brand-500"><?= htmlspecialchars($myReview['komentar'] ?? '') ?></textarea>
                            </div>

                            <button type="submit" class="w-full py-3 px-4 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs shadow-md shadow-brand-600/20 transition flex items-center justify-center gap-2">
                                <i class="fa-solid fa-paper-plane"></i>
                                <span><?= $myReview ? 'Simpan Perubahan Ulasan' : 'Kirim Rating & Komentar' ?></span>
                            </button>
                        </form>
                    </div>
                <?php elseif ($isUser && !$purchasedOrder && $pendingOrder): ?>
                    <div class="p-5 rounded-2xl bg-blue-50/80 border border-blue-200 text-xs text-slate-700 space-y-2.5">
                        <div class="font-extrabold text-blue-900 flex items-center gap-1.5">
                            <i class="fa-solid fa-truck-fast text-blue-600"></i>
                            <span>Status Pesanan Anda: <?= htmlspecialchars($pendingOrder['status_pesanan']) ?></span>
                        </div>
                        <?php if ($pendingOrder['status_pesanan'] === 'Dikirim'): ?>
                            <p class="leading-relaxed text-slate-600">
                                Pesanan <strong><?= htmlspecialchars($pendingOrder['invoice']) ?></strong> telah dikirim oleh Admin! Silakan konfirmasi <strong>Pesanan Selesai</strong> di halaman Riwayat Pesanan untuk membuka form Rating & Komentar ini.
                            </p>
                            <a href="orders.php" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-sm transition">
                                <i class="fa-solid fa-circle-check"></i> Konfirmasi Pesanan Selesai
                            </a>
                        <?php else: ?>
                            <p class="leading-relaxed text-slate-600">
                                Anda memiliki pesanan <strong><?= htmlspecialchars($pendingOrder['invoice']) ?></strong> (status: <strong><?= htmlspecialchars($pendingOrder['status_pesanan']) ?></strong>). Form Rating & Komentar baru akan terbuka setelah Admin mengirim barang dan Anda menekan tombol <strong>Pesanan Selesai</strong> di menu Riwayat Pesanan.
                            </p>
                            <a href="orders.php" class="inline-flex items-center gap-1.5 font-bold text-blue-700 hover:underline">
                                <i class="fa-solid fa-receipt"></i> Lihat Riwayat Pesanan
                            </a>
                        <?php endif; ?>
                    </div>
                <?php elseif ($isUser && !$purchasedOrder): ?>
                    <div class="p-5 rounded-2xl bg-slate-50 border border-slate-200 text-xs text-slate-600 space-y-1.5">
                        <div class="font-bold text-slate-800 flex items-center gap-1.5">
                            <i class="fa-solid fa-bag-shopping text-brand-600"></i> Ingin Memberikan Rating & Komentar?
                        </div>
                        <p class="leading-relaxed text-slate-500">
                            Form penilaian bintang dan komentar hanya terbuka apabila Anda telah membeli tanaman ini dan mengonfirmasi <strong>Pesanan Selesai</strong> setelah barang dikirim oleh Admin.
                        </p>
                    </div>
                <?php elseif (!$isAdmin): ?>
                    <div class="p-5 rounded-2xl bg-slate-50 border border-slate-200 text-xs text-slate-600 space-y-2">
                        <p class="leading-relaxed text-slate-500">
                            Sudah membeli tanaman ini? Silakan masuk ke akun Pembeli untuk memberikan rating dan komentar hasil pembelian Anda.
                        </p>
                        <a href="login.php?redirect=<?= urlencode('product_detail.php?id=' . $product['id'] . '#ulasan') ?>" class="inline-flex items-center gap-1.5 font-bold text-brand-600 hover:underline">
                            <i class="fa-solid fa-right-to-bracket"></i> Login Pembeli
                        </a>
                    </div>
                <?php endif; ?>

            </div>

            <!-- Right Column: Daftar Komentar & Ulasan Pembeli -->
            <div class="lg:col-span-7">
                <?php if (!empty($reviews)): ?>
                    <div class="space-y-4">
                        <?php foreach ($reviews as $rev): ?>
                            <div class="p-5 rounded-2xl border border-slate-200/80 bg-white hover:border-brand-200 transition shadow-sm">
                                <div class="flex items-start justify-between gap-4 mb-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-brand-100 text-brand-700 font-extrabold text-sm flex items-center justify-center flex-shrink-0">
                                            <?= strtoupper(substr($rev['nama_pembeli'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <h4 class="font-bold text-slate-900 text-sm"><?= htmlspecialchars($rev['nama_pembeli']) ?></h4>
                                                <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full">
                                                    <i class="fa-solid fa-circle-check text-[9px]"></i> Pembeli Terverifikasi
                                                </span>
                                            </div>
                                            <p class="text-[11px] text-slate-400 mt-0.5">
                                                <?= date('d M Y, H:i', strtotime($rev['created_at'])) ?> WIB
                                                <?php if (!empty($rev['invoice'])): ?>
                                                    • Pesanan <span class="font-mono"><?= htmlspecialchars($rev['invoice']) ?></span>
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Star badge -->
                                    <div class="flex items-center gap-0.5 text-amber-400 text-xs bg-amber-50 px-2.5 py-1 rounded-lg border border-amber-100">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fa-solid fa-star <?= $i <= (int)$rev['rating'] ? 'text-amber-400' : 'text-slate-200' ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>

                                <p class="text-xs sm:text-sm text-slate-700 leading-relaxed pl-13">
                                    <?= nl2br(htmlspecialchars($rev['komentar'])) ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="rounded-2xl border border-dashed border-slate-200 p-10 text-center bg-slate-50/50">
                        <div class="w-14 h-14 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3 text-xl">
                            <i class="fa-regular fa-comment-dots"></i>
                        </div>
                        <h3 class="text-sm font-bold text-slate-800">Belum Ada Ulasan & Komentar</h3>
                        <p class="text-xs text-slate-500 mt-1 max-w-md mx-auto leading-relaxed">
                            Tanaman ini belum memiliki penilaian atau komentar dari pembeli. Jadilah pelanggan pertama yang membeli dan membagikan ulasan!
                        </p>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <!-- Related Products Section -->
    <?php if (!empty($relatedProducts)): ?>
        <div>
            <div class="mb-6">
                <h2 class="text-xl font-extrabold text-slate-900">Tanaman Sejenis Lainnya</h2>
                <p class="text-xs text-slate-500 mt-1">Klik pada item tanaman untuk melihat deskripsi, ulasan, dan opsi pembelian.</p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php foreach ($relatedProducts as $rel): ?>
                    <a href="product_detail.php?id=<?= $rel['id'] ?>" class="bg-white rounded-2xl border border-slate-200/80 hover:border-brand-400 p-4 shadow-sm hover:shadow-lg hover:-translate-y-0.5 transition flex flex-col group">
                        <div class="relative h-44 rounded-xl bg-slate-100 overflow-hidden mb-3">
                            <img src="assets/images/products/<?= htmlspecialchars($rel['foto']) ?>" 
                                 alt="<?= htmlspecialchars($rel['nama_produk']) ?>" 
                                 class="w-full h-full object-cover group-hover:scale-105 transition"
                                 onerror="this.src='assets/images/products/default.jpg'">
                            <div class="absolute bottom-2 left-2 bg-white/95 backdrop-blur-sm px-2 py-0.5 rounded-md text-[11px] font-bold flex items-center gap-1 shadow-sm <?= (float)$rel['rating'] > 0 ? 'text-amber-500' : 'text-slate-400' ?>">
                                <i class="fa-solid fa-star text-[9px]"></i>
                                <span><?= (float)$rel['rating'] > 0 ? number_format((float)$rel['rating'], 1) : 'Belum dinilai' ?></span>
                            </div>
                        </div>
                        <h4 class="font-bold text-sm text-slate-900 group-hover:text-brand-600 line-clamp-1 mb-1">
                            <?= htmlspecialchars($rel['nama_produk']) ?>
                        </h4>
                        <span class="text-xs font-extrabold text-brand-700 mt-auto">
                            <?= formatRupiah($rel['harga_jual']) ?>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function incrementQty(max) {
    const input = document.getElementById('detailQty');
    if (!input) return;
    let val = parseInt(input.value) || 1;
    if (val < max) {
        input.value = val + 1;
    }
}
function decrementQty() {
    const input = document.getElementById('detailQty');
    if (!input) return;
    let val = parseInt(input.value) || 1;
    if (val > 1) {
        input.value = val - 1;
    }
}
function setRating(star) {
    const input = document.getElementById('ratingInput');
    const label = document.getElementById('ratingLabel');
    const buttons = document.querySelectorAll('#starPicker .star-btn');
    if (input) input.value = star;
    if (label) label.innerText = star + ' / 5 Bintang';
    buttons.forEach(btn => {
        const s = parseInt(btn.getAttribute('data-star'));
        if (s <= star) {
            btn.classList.remove('text-slate-300');
            btn.classList.add('text-amber-400');
        } else {
            btn.classList.remove('text-amber-400');
            btn.classList.add('text-slate-300');
        }
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>