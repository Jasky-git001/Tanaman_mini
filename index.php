<?php
$pageTitle = 'Beranda';
require_once __DIR__ . '/includes/header.php';

// Ambil Kategori dari Database
$categories = [];
$featuredProducts = [];

try {
    $db = getDB();
    // Kategori
    $stmtCat = $db->query("SELECT * FROM categories ORDER BY id ASC");
    $categories = $stmtCat->fetchAll();

    // Produk Terlaris (Bisa diurutkan berdasarkan rating atau id)
    $stmtProd = $db->query("
        SELECT p.*, c.nama_kategori, c.slug AS cat_slug 
        FROM products p 
        JOIN categories c ON p.category_id = c.id 
        ORDER BY p.rating DESC, p.id ASC 
        LIMIT 6
    ");
    $featuredProducts = $stmtProd->fetchAll();
} catch (Exception $e) {
    // Database fallback jika koneksi belum dibuat
    $dbError = $e->getMessage();
}
?>

<!-- Hero Section -->
<section class="relative bg-gradient-to-b from-brand-50/70 via-white to-slate-50 pt-10 pb-20 overflow-hidden">
    <!-- Decorative background elements -->
    <div class="absolute top-0 right-0 -mr-20 -mt-20 w-96 h-96 rounded-full bg-brand-100/60 blur-3xl pointer-events-none"></div>
    <div class="absolute bottom-0 left-0 -ml-20 -mb-20 w-80 h-80 rounded-full bg-emerald-100/50 blur-3xl pointer-events-none"></div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
            
            <!-- Left Hero Content -->
            <div class="lg:col-span-7 space-y-6 text-center lg:text-left">
                <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-brand-100 text-brand-800 text-xs font-semibold tracking-wide shadow-sm">
                    <i class="fa-solid fa-sparkles text-brand-600"></i>
                    <span>Toko Tanaman Hias Mini #1 Untuk Ruang Idaman</span>
                </div>

                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-slate-900 tracking-tight leading-[1.15]">
                    Hadirkan Kesegaran <span class="text-brand-600 underline decoration-brand-300 decoration-wavy underline-offset-8">Hijau</span> di Ruanganmu
                </h1>

                <p class="text-base sm:text-lg text-slate-600 max-w-2xl mx-auto lg:mx-0 leading-relaxed font-normal">
                    Tanaman hias mini berkualitas untuk mempercantik meja, kamar, dan ruang kerja. Temukan pesona alam mini yang menenangkan pikiran dan menyegarkan harimu.
                </p>

                <div class="pt-2 flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4">
                    <a href="products.php" class="w-full sm:w-auto inline-flex items-center justify-center gap-2.5 px-7 py-3.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-semibold text-base shadow-lg shadow-brand-600/25 hover:shadow-brand-600/35 transition transform hover:-translate-y-0.5">
                        <i class="fa-solid fa-bag-shopping"></i>
                        <span>Belanja Sekarang</span>
                    </a>
                    <a href="#kategori" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-semibold text-base transition">
                        <i class="fa-solid fa-layer-group text-slate-400"></i>
                        <span>Lihat Kategori</span>
                    </a>
                </div>

                <!-- Mini Stats Under Hero -->
                <div class="pt-8 border-t border-slate-200/80 grid grid-cols-3 gap-4 max-w-lg mx-auto lg:mx-0 text-center sm:text-left">
                    <div>
                        <p class="text-2xl sm:text-3xl font-extrabold text-brand-800">50+</p>
                        <p class="text-xs text-slate-500 font-medium">Varian Tanaman</p>
                    </div>
                    <div>
                        <p class="text-2xl sm:text-3xl font-extrabold text-brand-800">100%</p>
                        <p class="text-xs text-slate-500 font-medium">Garansi Segar</p>
                    </div>
                    <div>
                        <p class="text-2xl sm:text-3xl font-extrabold text-brand-800">4.9/5</p>
                        <p class="text-xs text-slate-500 font-medium">Rating Pelanggan</p>
                    </div>
                </div>
            </div>

            <!-- Right Hero Visual Banner -->
            <div class="lg:col-span-5 relative">
                <div class="relative mx-auto max-w-md bg-gradient-to-tr from-brand-600 to-emerald-400 rounded-3xl p-1 shadow-2xl shadow-brand-600/20">
                    <div class="bg-white rounded-[22px] overflow-hidden p-6 relative">
                        <div class="relative h-80 rounded-2xl overflow-hidden bg-slate-50 flex items-center justify-center border border-slate-100">
                            <img src="assets/images/products/monstera-mini.jpg" alt="Monstera Mini" class="w-full h-full object-cover">
                            <span class="absolute top-3 left-3 bg-brand-600 text-white text-xs font-bold px-3 py-1 rounded-full shadow">
                                Best Seller
                            </span>
                        </div>
                        <div class="mt-4 flex items-center justify-between">
                            <div>
                                <h3 class="font-bold text-slate-900 text-base">Monstera Mini Adansonii</h3>
                                <p class="text-xs text-slate-500">Koleksi Tanaman Meja Terfavorit</p>
                            </div>
                            <span class="text-brand-600 font-extrabold text-lg">Rp 45.000</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- Kategori Produk Section -->
<section class="py-16 bg-slate-50/60 border-t border-slate-200" id="kategori">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header Section -->
        <div class="text-center max-w-xl mx-auto mb-10">
            <span class="inline-block px-3 py-1 bg-emerald-50 text-emerald-600 text-xs font-bold uppercase tracking-wider rounded-full border border-emerald-200 mb-2">
                Koleksi Pilihan
            </span>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-1">
                Kategori Tanaman Hias Mini
            </h2>
            <p class="text-slate-500 text-sm mt-2">
                Pilih tanaman yang sesuai dengan karakter ruangan dan preferensi perawatannya.
            </p>
        </div>

        <!-- Grid Kategori -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 sm:gap-6">
            <?php if (!empty($categories)): ?>
                <?php foreach ($categories as $cat): ?>
                    <a href="products.php?category=<?= urlencode($cat['slug']) ?>" 
                       class="bg-white hover:bg-emerald-50/80 p-5 rounded-2xl border-2 border-slate-200/90 hover:border-emerald-400 shadow-sm hover:shadow-md transition-all duration-300 transform hover:-translate-y-1 flex flex-col items-center text-center justify-center group">
                        
                        <!-- Box Ikon (Berubah Hijau Solid saat Hover) -->
                        <div class="w-14 h-14 rounded-2xl bg-slate-100 text-emerald-600 group-hover:bg-emerald-600 group-hover:text-white flex items-center justify-center text-2xl shadow-xs transition-colors duration-300 mb-3">
                            <i class="fa-solid <?= htmlspecialchars($cat['icon']) ?>"></i>
                        </div>

                        <!-- Judul Kategori -->
                        <h3 class="font-bold text-slate-800 group-hover:text-emerald-800 text-sm sm:text-base transition-colors">
                            <?= htmlspecialchars($cat['nama_kategori']) ?>
                        </h3>

                        <!-- Teks 'Lihat Koleksi' -->
                        <span class="text-xs font-semibold text-slate-400 mt-1 group-hover:text-emerald-600 transition-colors inline-flex items-center gap-1">
                            Lihat Koleksi <i class="fa-solid fa-arrow-right text-[10px] transform group-hover:translate-x-1 transition-transform"></i>
                        </span>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Fallback kategori jika database belum diisi -->
                <div class="col-span-full text-center py-4 text-slate-400 text-sm">
                    Kategori tanaman akan muncul di sini setelah database diimpor.
                </div>
            <?php endif; ?>
        </div>

    </div>
</section>

<!-- Produk Terlaris Section -->
<section class="py-20 bg-slate-50" id="produk">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col md:flex-row md:items-end justify-between mb-12 gap-4">
            <div>
                <span class="text-brand-600 text-xs font-bold uppercase tracking-wider">Favorit Pembeli</span>
                <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-1">Produk Terlaris Minggu Ini</h2>
                <p class="text-slate-500 text-sm mt-1">Pilihan tanaman mini yang paling diminati untuk dekorasi ruang kerja dan kamar.</p>
            </div>
            <a href="products.php" class="inline-flex items-center gap-1.5 text-sm font-semibold text-brand-600 hover:text-brand-700 hover:underline">
                <span>Lihat Semua Produk</span>
                <i class="fa-solid fa-arrow-right text-xs"></i>
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <?php if (!empty($featuredProducts)): ?>
                <?php foreach ($featuredProducts as $prod): ?>
                    <a href="product_detail.php?id=<?= $prod['id'] ?>" class="bg-white rounded-2xl border border-slate-200/80 hover:border-brand-400 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 flex flex-col overflow-hidden group cursor-pointer">
                        
                        <!-- Product Image Area -->
                        <div class="relative h-60 bg-slate-100 overflow-hidden">
                            <img src="assets/images/products/<?= htmlspecialchars($prod['foto']) ?>" 
                                 alt="<?= htmlspecialchars($prod['nama_produk']) ?>" 
                                 class="w-full h-full object-cover group-hover:scale-105 transition duration-300"
                                 onerror="this.src='assets/images/products/default.jpg'">
                            
                            <!-- Category Badge -->
                            <span class="absolute top-3 left-3 bg-white/90 backdrop-blur-sm text-slate-700 text-[11px] font-semibold px-2.5 py-1 rounded-lg shadow-sm">
                                <?= htmlspecialchars($prod['nama_kategori']) ?>
                            </span>

                            <!-- Rating Badge (Hanya dari ulasan pembeli) -->
                            <div class="absolute bottom-3 left-3 bg-white/95 backdrop-blur-sm px-2.5 py-0.5 rounded-lg text-xs font-bold flex items-center gap-1 shadow-sm <?= (float)$prod['rating'] > 0 ? 'text-amber-500' : 'text-slate-400' ?>">
                                <i class="fa-solid fa-star text-[10px]"></i>
                                <span><?= (float)$prod['rating'] > 0 ? number_format((float)$prod['rating'], 1) : 'Belum ada ulasan' ?></span>
                            </div>
                        </div>

                        <!-- Product Content Info (Ringkas: Nama, Harga, Stok) -->
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
            <?php else: ?>
                <div class="col-span-full bg-white rounded-2xl p-10 text-center border border-slate-200">
                    <i class="fa-solid fa-seedling text-4xl text-brand-300 mb-3 block"></i>
                    <p class="text-slate-600 font-medium">Belum ada data produk di database.</p>
                    <p class="text-xs text-slate-400 mt-1">Silakan impor file <code>database/tanaman_mini.sql</code> melalui phpMyAdmin.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Keunggulan Toko Section -->
<section class="py-16 bg-slate-50/60 border-t border-slate-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header Section -->
        <div class="text-center max-w-xl mx-auto mb-12">
            <span class="inline-block px-3 py-1 bg-brand-50 text-brand-600 text-xs font-bold uppercase tracking-wider rounded-full border border-brand-200/80 mb-2">
                Mengapa Memilih Kami?
            </span>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 mt-1">
                Keunggulan Tanaman Hias Mini
            </h2>
        </div>

        <!-- Grid Cards dengan Border Elegan -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            
            <!-- 1. Kualitas -->
            <div class="bg-white p-6 sm:p-7 rounded-2xl border-2 border-slate-200/90 hover:border-brand-500 shadow-sm hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 relative overflow-hidden flex flex-col items-center text-center group">
                <div class="w-14 h-14 rounded-2xl bg-brand-100 text-brand-700 flex items-center justify-center text-2xl mb-4 group-hover:bg-brand-600 group-hover:text-white transition-colors duration-300 shadow-xs">
                    <i class="fa-solid fa-award"></i>
                </div>
                <h3 class="text-base font-bold text-slate-900 mb-2">Tanaman Berkualitas</h3>
                <p class="text-xs text-slate-500 leading-relaxed">
                    Setiap tanaman dipilih dengan teliti, dirawat secara higienis, dan dipastikan berakar sehat sebelum dikirim.
                </p>
            </div>

            <!-- 2. Harga Terjangkau -->
            <div class="bg-white p-6 sm:p-7 rounded-2xl border-2 border-slate-200/90 hover:border-brand-500 shadow-sm hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 relative overflow-hidden flex flex-col items-center text-center group">
                <div class="w-14 h-14 rounded-2xl bg-brand-100 text-brand-700 flex items-center justify-center text-2xl mb-4 group-hover:bg-brand-600 group-hover:text-white transition-colors duration-300 shadow-xs">
                    <i class="fa-solid fa-tags"></i>
                </div>
                <h3 class="text-base font-bold text-slate-900 mb-2">Harga Terjangkau</h3>
                <p class="text-xs text-slate-500 leading-relaxed">
                    Harga ramah bagi kantong mahasiswa dan pecinta tanaman pemula tanpa mengurangi kualitas dan estetika.
                </p>
            </div>

            <!-- 3. Pengemasan Aman -->
            <div class="bg-white p-6 sm:p-7 rounded-2xl border-2 border-slate-200/90 hover:border-brand-500 shadow-sm hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 relative overflow-hidden flex flex-col items-center text-center group">
                <div class="w-14 h-14 rounded-2xl bg-brand-100 text-brand-700 flex items-center justify-center text-2xl mb-4 group-hover:bg-brand-600 group-hover:text-white transition-colors duration-300 shadow-xs">
                    <i class="fa-solid fa-box-open"></i>
                </div>
                <h3 class="text-base font-bold text-slate-900 mb-2">Pengemasan Aman</h3>
                <p class="text-xs text-slate-500 leading-relaxed">
                    Pot diisolasi rapi, media tanam terlindung, serta dibalut kardus tebal & bubble wrap berlapis untuk keamanan maksimal.
                </p>
            </div>

            <!-- 4. Pengiriman Cepat -->
            <div class="bg-white p-6 sm:p-7 rounded-2xl border-2 border-slate-200/90 hover:border-brand-500 shadow-sm hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 relative overflow-hidden flex flex-col items-center text-center group">
                <div class="w-14 h-14 rounded-2xl bg-brand-100 text-brand-700 flex items-center justify-center text-2xl mb-4 group-hover:bg-brand-600 group-hover:text-white transition-colors duration-300 shadow-xs">
                    <i class="fa-solid fa-bolt-lightning"></i>
                </div>
                <h3 class="text-base font-bold text-slate-900 mb-2">Pengiriman Cepat</h3>
                <p class="text-xs text-slate-500 leading-relaxed">
                    Pesanan diproses di hari yang sama dengan pilihan ekspedisi terpercaya agar tanaman cepat sampai dalam kondisi segar.
                </p>
            </div>

        </div>
    </div>
</section>

<!-- Tentang Kami Section -->
<section class="py-16 bg-slate-100/70 border-t border-slate-200" id="tentang">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Main Card Container dengan Gradient & Accent Border Atas -->
        <div class="bg-gradient-to-br from-white via-slate-50 to-emerald-50/40 rounded-3xl p-6 sm:p-10 border-2 border-emerald-500/20 shadow-lg shadow-slate-200/60 relative overflow-hidden grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
            
            <!-- Garis Aksen Gradasi Hijau di Bagian Atas Container -->
            <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-emerald-500 via-teal-500 to-emerald-400"></div>

            <!-- Box Teks Kiri -->
            <div class="lg:col-span-8 space-y-4 bg-white p-6 sm:p-8 rounded-2xl border border-slate-200/90 shadow-sm relative">
                
                <div class="inline-flex items-center gap-2 px-3 py-1 bg-emerald-50 text-emerald-700 text-xs font-bold rounded-full border border-emerald-200">
                    <i class="fa-solid fa-seedling text-emerald-600"></i>
                    <span>Tentang Kami</span>
                </div>

                <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 leading-tight">
                    Mewujudkan Ruang Hijau & Kesegaran Alami di Tempat Anda
                </h2>

                <p class="text-slate-600 text-sm leading-relaxed">
                    <strong>Tanaman Hias Mini</strong> hadir untuk menghadirkan sentuhan alam yang estetik dan menenangkan di tengah padatnya aktivitas harian Anda. Kami percaya bahwa kehadiran tanaman hias tidak hanya mempercantik sudut ruangan, tetapi juga memberikan energi positif serta suasana baru di rumah maupun meja kerja Anda.
                </p>

                <p class="text-slate-600 text-sm leading-relaxed">
                    Dengan menghadirkan kurasi varian tanaman mini pilihan yang ramah perawatan, layanan belanja daring yang praktis, serta standar pengemasan yang aman, kami berkomitmen memberikan pengalaman belanja hijau yang menyenangkan bagi setiap pelanggan.
                </p>
            </div>

            <!-- Box Kartu Kanan (Menampilkan Logo yang Sama) -->
            <div class="lg:col-span-4 flex justify-center">
                <div class="p-6 sm:p-8 rounded-2xl bg-white border-2 border-emerald-500/30 text-center w-full shadow-md relative overflow-hidden">
                    
                    <!-- Hiasan Blur Halus di Sudut Kartu -->
                    <div class="absolute -right-8 -bottom-8 w-24 h-24 bg-emerald-100/60 rounded-full blur-xl pointer-events-none"></div>

                    <!-- Logo Kotak Hijau Mungil (Sesuai Gambar) -->
                    <div class="w-16 h-16 bg-emerald-600 text-white rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg shadow-emerald-600/30">
                        <i class="fa-solid fa-seedling text-3xl"></i>
                    </div>

                    <!-- Judul & Subjudul -->
                    <h3 class="font-extrabold text-slate-900 text-lg">Green Living & Urban Decor</h3>
                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">Koleksi Tanaman Hias Estetik & Ramah Perawatan</p>

                    <!-- Garis Pemisah -->
                    <div class="my-5 border-t border-slate-200"></div>

                    <!-- Badge Jaminan Kualitas -->
                    <div class="inline-flex items-center justify-center gap-2 bg-emerald-50 text-emerald-700 text-xs font-bold px-4 py-2 rounded-xl border border-emerald-200 w-full shadow-xs">
                        <i class="fa-solid fa-shield-halved text-emerald-600"></i>
                        <span>Kualitas & Garansi Kesegaran</span>
                    </div>

                </div>
            </div>

        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

