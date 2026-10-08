<?php
$adminTitle = 'Kelola Produk Tanaman';
require_once __DIR__ . '/../includes/admin_header.php';

$db = getDB();

$search = sanitize($_GET['q'] ?? '');
$categoryFilter = (int)($_GET['category_id'] ?? 0);

// Ambil Kategori untuk filter
$categories = $db->query("SELECT * FROM categories ORDER BY nama_kategori ASC")->fetchAll();

// Bangun query produk
$sql = "
    SELECT p.*, c.nama_kategori 
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

if ($categoryFilter > 0) {
    $sql .= " AND p.category_id = ?";
    $params[] = $categoryFilter;
}

$sql .= " ORDER BY p.id DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
?>

<div class="space-y-6">
    
    <!-- Top Action Bar -->
    <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm flex flex-col md:flex-row items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-extrabold text-slate-900">Daftar Produk Tanaman Hias Mini</h1>
            <p class="text-xs text-slate-500 mt-0.5">Kelola katalog, harga modal, harga jual, dan stok produk secara langsung.</p>
        </div>
        <a href="product_add.php" class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs shadow-md shadow-brand-600/20 transition">
            <i class="fa-solid fa-plus"></i> Tambah Tanaman Baru
        </a>
    </div>

    <!-- Filter & Search Box -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-3">
        <form action="products.php" method="GET" class="flex flex-col sm:flex-row items-center gap-3 w-full sm:w-auto">
            <div class="relative w-full sm:w-64">
                <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3 text-slate-400 text-xs"></i>
                <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Cari nama produk..." class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-200 text-xs focus:outline-none focus:border-brand-500">
            </div>

            <select name="category_id" class="w-full sm:w-auto px-3 py-2 rounded-xl border border-slate-200 text-xs focus:outline-none focus:border-brand-500 bg-white">
                <option value="0">Semua Kategori</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $categoryFilter == $cat['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['nama_kategori']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="w-full sm:w-auto px-4 py-2 rounded-xl bg-slate-800 text-white text-xs font-semibold hover:bg-slate-900 transition">
                Filter
            </button>

            <?php if ($search || $categoryFilter): ?>
                <a href="products.php" class="text-xs text-slate-500 hover:text-slate-700">Reset</a>
            <?php endif; ?>
        </form>

        <span class="text-xs text-slate-400 font-medium">
            Total: <strong><?= count($products) ?></strong> tanaman
        </span>
    </div>

    <!-- Products Table -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100 text-slate-500 font-bold uppercase tracking-wider">
                        <th class="py-3.5 px-5">Foto</th>
                        <th class="py-3.5 px-5">Nama Produk</th>
                        <th class="py-3.5 px-5">Kategori</th>
                        <th class="py-3.5 px-5 text-right">Harga Modal</th>
                        <th class="py-3.5 px-5 text-right">Harga Jual</th>
                        <th class="py-3.5 px-5 text-right">Margin / Laba</th>
                        <th class="py-3.5 px-5 text-center">Stok</th>
                        <th class="py-3.5 px-5 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (!empty($products)): ?>
                        <?php foreach ($products as $p): ?>
                            <?php $labaPerUnit = $p['harga_jual'] - $p['harga_modal']; ?>
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="py-3.5 px-5">
                                    <div class="w-12 h-12 rounded-xl bg-slate-100 overflow-hidden border border-slate-100 flex-shrink-0">
                                        <img src="../assets/images/products/<?= htmlspecialchars($p['foto']) ?>" 
                                             alt="<?= htmlspecialchars($p['nama_produk']) ?>" 
                                             class="w-full h-full object-cover"
                                             onerror="this.src='../assets/images/products/default.jpg'">
                                    </div>
                                </td>
                                <td class="py-3.5 px-5">
                                    <a href="../product_detail.php?id=<?= $p['id'] ?>" target="_blank" class="font-bold text-slate-900 hover:text-brand-600 block text-sm">
                                        <?= htmlspecialchars($p['nama_produk']) ?>
                                    </a>
                                    <span class="text-[11px] text-slate-400 line-clamp-1 max-w-xs">
                                        <?= htmlspecialchars($p['deskripsi']) ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-5">
                                    <span class="px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-slate-100 text-slate-700">
                                        <?= htmlspecialchars($p['nama_kategori']) ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-5 text-right text-slate-500 font-medium">
                                    <?= formatRupiah($p['harga_modal']) ?>
                                </td>
                                <td class="py-3.5 px-5 text-right font-extrabold text-slate-900">
                                    <?= formatRupiah($p['harga_jual']) ?>
                                </td>
                                <td class="py-3.5 px-5 text-right text-emerald-600 font-bold">
                                    +<?= formatRupiah($labaPerUnit) ?>
                                </td>
                                <td class="py-3.5 px-5 text-center">
                                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold <?= $p['stok'] > 5 ? 'bg-emerald-100 text-emerald-800' : ($p['stok'] > 0 ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800') ?>">
                                        <?= $p['stok'] > 0 ? $p['stok'] . ' pot' : 'Habis' ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-5 text-center whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5">
                                        <a href="product_edit.php?id=<?= $p['id'] ?>" class="p-2 rounded-lg bg-slate-100 hover:bg-brand-50 hover:text-brand-600 text-slate-600 transition" title="Edit Produk">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>
                                        <button type="button" 
                                                onclick="konfirmasiHapus('product_delete.php?id=<?= $p['id'] ?>', '<?= htmlspecialchars($p['nama_produk'], ENT_QUOTES) ?>')" 
                                                class="p-2 rounded-lg bg-slate-100 hover:bg-red-50 hover:text-red-600 text-slate-600 transition" 
                                                title="Hapus Produk">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="py-10 text-center text-slate-400">
                                Tidak ada data tanaman hias yang sesuai.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
<!-- CDN SweetAlert2 (pastikan ini ada agar fungsi Swal terbaca) -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function konfirmasiHapus(urlHapus, namaProduk) {
    Swal.fire({
        title: 'Hapus Produk?',
        text: `Produk "${namaProduk}" akan dihapus permanen dari katalog.`,
        icon: 'warning',
        iconColor: '#f59e0b',
        showCancelButton: true,
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
        reverseButtons: true,
        buttonsStyling: false,
        customClass: {
            popup: 'rounded-3xl p-6 shadow-xl border border-slate-100 bg-white font-sans',
            title: 'text-lg font-extrabold text-slate-900 -mb-1',
            htmlContainer: 'text-xs text-slate-500 mt-2',
            confirmButton: 'bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold py-2.5 px-5 rounded-xl shadow-sm transition mx-1 cursor-pointer',
            cancelButton: 'bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold py-2.5 px-5 rounded-xl transition mx-1 cursor-pointer'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = urlHapus;
        }
    });
}
</script>
<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>

