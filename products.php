<?php
$pageTitle = 'Katalog Produk';
require_once __DIR__ . '/includes/header.php';

$search = sanitize($_GET['q'] ?? '');
$categorySlug = sanitize($_GET['category'] ?? '');
$sortBy = sanitize($_GET['sort'] ?? 'latest');

$categories = [];
$products = [];

try {
    $db = getDB();

    // Ambil semua kategori untuk sidebar filter
    $stmtCat = $db->query("SELECT * FROM categories ORDER BY id ASC");
    $categories = $stmtCat->fetchAll();

    // Bangun Query Produk Dinamis
    $sql = "
        SELECT p.*, c.nama_kategori, c.slug AS cat_slug 
        FROM products p 
        JOIN categories c ON p.category_id = c.id 
        WHERE 1=1
    ";
    $params = [];

    if (!empty($search)) {
        $sql .= " AND (p.nama_produk LIKE ? OR p.deskripsi LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    if (!empty($categorySlug)) {
        $sql .= " AND c.slug = ?";
        $params[] = $categorySlug;
    }

    // Sorting
    switch ($sortBy) {
        case 'price_low':
            $sql .= " ORDER BY p.harga_jual ASC";
            break;
        case 'price_high':
            $sql .= " ORDER BY p.harga_jual DESC";
            break;
        case 'rating':
            $sql .= " ORDER BY p.rating DESC";
            break;
        case 'name':
            $sql .= " ORDER BY p.nama_produk ASC";
            break;
        default:
            $sql .= " ORDER BY p.id DESC";
            break;
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();

} catch (Exception $e) {
    $dbError = $e->getMessage();
}
?>

<div class="bg-white border-b border-slate-200/80 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900">Katalog Tanaman Hias Mini</h1>
                <p class="text-sm text-slate-500 mt-1">Jelajahi seluruh koleksi tanaman mini untuk meja kerja dan dekorasi rumah Anda.</p>
            </div>
            <!-- Breadcrumbs -->
            <div class="flex items-center gap-2 text-xs font-medium text-slate-400">
                <a href="index.php" class="hover:text-brand-600">Home</a>
                <span>/</span>
                <span class="text-slate-800">Katalog Produk</span>
                <?php if ($categorySlug): ?>
                    <span>/</span>
                    <span class="text-brand-600 font-semibold capitalize"><?= htmlspecialchars(str_replace('-', ' ', $categorySlug)) ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        
        <!-- Sidebar Filter -->
        <aside class="lg:col-span-1 space-y-6">
            <!-- Search Box -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                <h3 class="font-bold text-slate-900 text-sm mb-3 flex items-center gap-2">
                    <i class="fa-solid fa-magnifying-glass text-brand-600"></i> Cari Tanaman
                </h3>
                <form action="products.php" method="GET" class="relative">
                    <?php if ($categorySlug): ?>
                        <input type="hidden" name="category" value="<?= htmlspecialchars($categorySlug) ?>">
                    <?php endif; ?>
                    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Nama tanaman..." class="w-full pl-3 pr-10 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
                    <button type="submit" class="absolute right-2.5 top-2.5 text-slate-400 hover:text-brand-600">
                        <i class="fa-solid fa-arrow-right text-sm"></i>
                    </button>
                </form>
            </div>

            <!-- Categories Filter -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-bold text-slate-900 text-sm flex items-center gap-2">
                        <i class="fa-solid fa-layer-group text-brand-600"></i> Kategori
                    </h3>
                    <?php if ($categorySlug || $search): ?>
                        <a href="products.php" class="text-xs text-brand-600 hover:underline">Reset</a>
                    <?php endif; ?>
                </div>
                <div class="space-y-1.5">
                    <a href="products.php<?= $search ? '?q=' . urlencode($search) : '' ?>" class="flex items-center justify-between px-3 py-2 rounded-xl text-xs font-medium transition <?= empty($categorySlug) ? 'bg-brand-50 text-brand-700 font-semibold' : 'text-slate-600 hover:bg-slate-50' ?>">
                        <span>Semua Kategori</span>
                        <i class="fa-solid fa-chevron-right text-[10px] text-slate-400"></i>
                    </a>
                    <?php foreach ($categories as $cat): ?>
                        <a href="products.php?category=<?= urlencode($cat['slug']) ?><?= $search ? '&q=' . urlencode($search) : '' ?>" class="flex items-center justify-between px-3 py-2 rounded-xl text-xs font-medium transition <?= $categorySlug === $cat['slug'] ? 'bg-brand-50 text-brand-700 font-semibold' : 'text-slate-600 hover:bg-slate-50' ?>">
                            <span><?= htmlspecialchars($cat['nama_kategori']) ?></span>
                            <i class="fa-solid fa-chevron-right text-[10px] text-slate-400"></i>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Customer Care Callout -->
            <div class="bg-gradient-to-br from-brand-700 to-emerald-800 rounded-2xl p-5 text-white shadow-sm">
                <i class="fa-solid fa-comments text-2xl mb-2 text-brand-200"></i>
                <h4 class="font-bold text-sm">Butuh Rekomendasi?</h4>
                <p class="text-xs text-brand-100 mt-1 leading-relaxed">Bingung memilih tanaman mini yang cocok untuk kondisi mejamu? Hubungi CS kami di WhatsApp.</p>
                <a href="https://wa.me/6281234567890" target="_blank" class="mt-3 inline-flex items-center gap-2 text-xs font-bold bg-white text-brand-800 px-3 py-2 rounded-xl hover:bg-brand-50 transition">
                    <i class="fa-brands fa-whatsapp"></i> Chat WhatsApp
                </a>
            </div>
        </aside>

        <!-- Product Grid Area -->
        <section class="lg:col-span-3 space-y-6">
            
            <!-- Top Controls (Count & Sorting) -->
            <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <p class="text-xs sm:text-sm text-slate-500 font-medium">
                    Menampilkan <strong class="text-slate-800"><?= count($products) ?></strong> tanaman hias
                    <?php if ($search): ?> untuk pencarian "<em><?= htmlspecialchars($search) ?></em>"<?php endif; ?>
                </p>

                <!-- Sort dropdown -->
                <form action="products.php" method="GET" class="flex items-center gap-2">
                    <?php if ($search): ?><input type="hidden" name="q" value="<?= htmlspecialchars($search) ?>"><?php endif; ?>
                    <?php if ($categorySlug): ?><input type="hidden" name="category" value="<?= htmlspecialchars($categorySlug) ?>"><?php endif; ?>
                    <label for="sort" class="text-xs text-slate-500 font-medium whitespace-nowrap">Urutkan:</label>
                    <select name="sort" id="sort" onchange="this.form.submit()" class="text-xs bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 font-medium text-slate-700 focus:outline-none focus:border-brand-500">
                        <option value="latest" <?= $sortBy == 'latest' ? 'selected' : '' ?>>Terbaru</option>
                        <option value="price_low" <?= $sortBy == 'price_low' ? 'selected' : '' ?>>Harga: Rendah ke Tinggi</option>
                        <option value="price_high" <?= $sortBy == 'price_high' ? 'selected' : '' ?>>Harga: Tinggi ke Rendah</option>
                        <option value="rating" <?= $sortBy == 'rating' ? 'selected' : '' ?>>Rating Tertinggi</option>
                        <option value="name" <?= $sortBy == 'name' ? 'selected' : '' ?>>Nama (A-Z)</option>
                    </select>
                </form>
            </div>

            <!-- Products Grid -->
            <?php if (!empty($products)): ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($products as $prod): ?>
                        <a href="product_detail.php?id=<?= $prod['id'] ?>" class="bg-white rounded-2xl border border-slate-200/80 hover:border-brand-400 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 flex flex-col overflow-hidden group cursor-pointer">
                            
                            <!-- Image Area -->
                            <div class="relative h-56 bg-slate-100 overflow-hidden">
                                <img src="assets/images/products/<?= htmlspecialchars($prod['foto']) ?>" 
                                     alt="<?= htmlspecialchars($prod['nama_produk']) ?>" 
                                     class="w-full h-full object-cover group-hover:scale-105 transition duration-300"
                                     onerror="this.src='assets/images/products/default.jpg'">
                                
                                <span class="absolute top-3 left-3 bg-white/90 backdrop-blur-sm text-slate-700 text-[11px] font-semibold px-2.5 py-1 rounded-lg shadow-sm">
                                    <?= htmlspecialchars($prod['nama_kategori']) ?>
                                </span>

                                <div class="absolute bottom-3 left-3 bg-white/95 backdrop-blur-sm px-2.5 py-0.5 rounded-lg text-xs font-bold flex items-center gap-1 shadow-sm <?= (float)$prod['rating'] > 0 ? 'text-amber-500' : 'text-slate-400' ?>">
                                    <i class="fa-solid fa-star text-[10px]"></i>
                                    <span><?= (float)$prod['rating'] > 0 ? number_format((float)$prod['rating'], 1) : 'Belum ada ulasan' ?></span>
                                </div>
                            </div>

                            <!-- Content Info (Ringkas: Nama, Harga, Stok) -->
                            <div class="p-5 flex-1 flex flex-col">
                                <h3 class="font-bold text-slate-900 text-base group-hover:text-brand-600 transition line-clamp-2 mb-3">
                                    <?= htmlspecialchars($prod['nama_produk']) ?>
                                </h3>

                                <div class="mt-auto pt-3 border-t border-slate-100 flex items-center justify-between">
                                    <div>
                                        <span class="text-xs text-slate-400 block">Harga</span>
                                        <span class="text-base font-extrabold text-brand-700">
                                            <?= formatRupiah($prod['harga_jual']) ?>
                                        </span>
                                    </div>
                                    <span class="text-xs font-medium px-2.5 py-1 rounded-md <?= $prod['stok'] > 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' ?>">
                                        <?= $prod['stok'] > 0 ? 'Stok: ' . $prod['stok'] : 'Habis' ?>
                                    </span>
                                </div>

                                <div class="mt-3 pt-2.5 border-t border-dashed border-slate-100 flex items-center justify-between text-[11px] font-semibold text-slate-400 group-hover:text-brand-600 transition">
                                    <span>Klik untuk lihat detail, ulasan & beli</span>
                                    <i class="fa-solid fa-arrow-right text-[10px] transform group-hover:translate-x-1 transition"></i>
                                </div>
                            </div>

                        </a>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="bg-white rounded-2xl p-12 text-center border border-slate-200 shadow-sm">
                    <div class="w-16 h-16 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-4 text-2xl">
                        <i class="fa-solid fa-box-open"></i>
                    </div>
                    <h3 class="text-base font-bold text-slate-800">Tidak ada produk ditemukan</h3>
                    <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">Coba gunakan kata kunci pencarian lain atau pilih kategori yang berbeda.</p>
                    <a href="products.php" class="mt-4 inline-block px-4 py-2 rounded-xl bg-brand-600 text-white text-xs font-semibold">
                        Lihat Semua Koleksi
                    </a>
                </div>
            <?php endif; ?>

        </section>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

