<?php
ob_start();

$adminTitle = 'Edit Tanaman Hias';
require_once __DIR__ . '/../includes/admin_header.php';

$db = getDB();
$productId = (int)($_GET['id'] ?? 0);

if ($productId <= 0) {
    header("Location: products.php");
    exit;
}

$stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    setFlash('error', 'Produk tidak ditemukan.');
    header("Location: products.php");
    exit;
}

$categories = $db->query("SELECT * FROM categories ORDER BY nama_kategori ASC")->fetchAll();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $namaProduk = sanitize($_POST['nama_produk'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $deskripsi = sanitize($_POST['deskripsi'] ?? '');
    $hargaModal = (float)($_POST['harga_modal'] ?? 0);
    $hargaJual = (float)($_POST['harga_jual'] ?? 0);
    $stok = (int)($_POST['stok'] ?? 0);
    $rating = (float)($_POST['rating'] ?? 5.0);

    // Validasi
    if (empty($namaProduk)) $errors[] = 'Nama produk wajib diisi.';
    if ($categoryId <= 0) $errors[] = 'Kategori produk wajib dipilih.';
    if ($hargaModal <= 0) $errors[] = 'Harga modal harus lebih dari 0.';
    if ($hargaJual <= 0) $errors[] = 'Harga jual harus lebih dari 0.';
    if ($stok < 0) $errors[] = 'Stok tidak boleh bernilai negatif.';

    // Upload Foto Baru Jika Ada
    $fotoName = $product['foto'];
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $fileTmp = $_FILES['foto']['tmp_name'];
        $fileName = $_FILES['foto']['name'];
        $fileSize = $_FILES['foto']['size'];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExt = ['jpg', 'jpeg', 'png', 'webp', 'svg'];

        if (!in_array($ext, $allowedExt)) {
            $errors[] = 'Format foto harus berupa JPG, PNG, WEBP, atau SVG.';
        } elseif ($fileSize > 2 * 1024 * 1024) {
            $errors[] = 'Ukuran foto maksimal 2 MB.';
        } else {
            $newFileName = 'prod_' . time() . '_' . rand(100, 999) . '.' . $ext;
            $uploadDest = __DIR__ . '/../assets/images/products/' . $newFileName;
            if (move_uploaded_file($fileTmp, $uploadDest)) {
                $fotoName = $newFileName;
            } else {
                $errors[] = 'Gagal menyimpan foto baru.';
            }
        }
    }

    if (empty($errors)) {
        try {
            $stmtUpdate = $db->prepare("
                UPDATE products 
                SET category_id = ?, nama_produk = ?, deskripsi = ?, harga_modal = ?, harga_jual = ?, stok = ?, foto = ? 
                WHERE id = ?
            ");
            $stmtUpdate->execute([
                $categoryId,
                $namaProduk,
                $deskripsi,
                $hargaModal,
                $hargaJual,
                $stok,
                $fotoName,
                $productId
            ]);

            setFlash('success', 'Perubahan produk "' . $namaProduk . '" berhasil disimpan!');
            header("Location: products.php");
            exit;
        } catch (Exception $e) {
            $errors[] = 'Gagal menyimpan ke database: ' . $e->getMessage();
        }
    }
}
?>

<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-sm">
        
        <div class="flex items-center justify-between pb-6 border-b border-slate-100 mb-6">
            <div>
                <h1 class="text-xl font-extrabold text-slate-900">Edit Data Tanaman</h1>
                <p class="text-xs text-slate-500 mt-0.5">Perbarui harga, stok, deskripsi, atau foto tanaman ini.</p>
            </div>
            <a href="products.php" class="text-xs font-semibold text-slate-500 hover:text-slate-800 flex items-center gap-1.5">
                <i class="fa-solid fa-arrow-left"></i> Kembali
            </a>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="mb-6 p-4 rounded-2xl bg-red-50 border border-red-200 text-red-700 text-xs space-y-1">
                <div class="font-bold flex items-center gap-1.5 mb-1">
                    <i class="fa-solid fa-circle-exclamation"></i> Terjadi kesalahan:
                </div>
                <ul class="list-disc list-inside space-y-0.5">
                    <?php foreach ($errors as $err): ?>
                        <li><?= htmlspecialchars($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="product_edit.php?id=<?= $productId ?>" method="POST" enctype="multipart/form-data" class="space-y-5">
            
            <!-- Nama Produk -->
            <div>
                <label for="nama_produk" class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Tanaman Hias *</label>
                <input type="text" id="nama_produk" name="nama_produk" required value="<?= htmlspecialchars($product['nama_produk']) ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-brand-500">
            </div>

            <!-- Kategori & Rating -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="category_id" class="block text-xs font-semibold text-slate-700 mb-1.5">Kategori Tanaman *</label>
                    <select id="category_id" name="category_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-brand-500 bg-white">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $product['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['nama_kategori']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Rating Pembeli (Otomatis)</label>
                    <div class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-xs text-slate-600 flex items-center justify-between">
                        <span class="flex items-center gap-1.5 font-bold text-amber-600">
                            <i class="fa-solid fa-star text-amber-400"></i>
                            <?= (float)$product['rating'] > 0 ? number_format((float)$product['rating'], 1) . ' / 5.0' : 'Belum ada ulasan' ?>
                        </span>
                        <span class="text-[11px] text-slate-400">Dari ulasan hasil pembelian</span>
                    </div>
                </div>
            </div>

            <!-- Harga Modal, Harga Jual, Stok -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label for="harga_modal" class="block text-xs font-semibold text-slate-700 mb-1.5">Harga Modal (Rp) *</label>
                    <input type="number" id="harga_modal" name="harga_modal" required min="0" value="<?= (int)$product['harga_modal'] ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-brand-500">
                </div>
                <div>
                    <label for="harga_jual" class="block text-xs font-semibold text-slate-700 mb-1.5">Harga Jual (Rp) *</label>
                    <input type="number" id="harga_jual" name="harga_jual" required min="0" value="<?= (int)$product['harga_jual'] ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-brand-500">
                </div>
                <div>
                    <label for="stok" class="block text-xs font-semibold text-slate-700 mb-1.5">Jumlah Stok *</label>
                    <input type="number" id="stok" name="stok" required min="0" value="<?= (int)$product['stok'] ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-brand-500">
                </div>
            </div>

            <!-- Deskripsi -->
            <div>
                <label for="deskripsi" class="block text-xs font-semibold text-slate-700 mb-1.5">Deskripsi & Perawatan Tanaman</label>
                <textarea id="deskripsi" name="deskripsi" rows="3" class="w-full p-3 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-brand-500"><?= htmlspecialchars($product['deskripsi']) ?></textarea>
            </div>

            <!-- Current & New Photo -->
            <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200/80 space-y-3">
                <label class="block text-xs font-semibold text-slate-700">Foto Produk Saat Ini:</label>
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 rounded-xl bg-white overflow-hidden border border-slate-200 flex-shrink-0">
                        <img src="../assets/images/products/<?= htmlspecialchars($product['foto']) ?>" 
                             alt="Foto Produk" 
                             class="w-full h-full object-cover"
                             onerror="this.src='../assets/images/products/default.jpg'">
                    </div>
                    <div class="flex-1">
                        <span class="text-xs text-slate-500 block mb-1">Ganti Foto Baru (Opsional):</span>
                        <input type="file" id="foto" name="foto" accept="image/*" class="w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-brand-100 file:text-brand-800 hover:file:bg-brand-200 cursor-pointer">
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="pt-4 flex items-center justify-end gap-3">
                <a href="products.php" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 text-xs font-semibold transition">
                    Batal
                </a>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow-md shadow-brand-600/20 transition">
                    Simpan Perubahan
                </button>
            </div>

        </form>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>

