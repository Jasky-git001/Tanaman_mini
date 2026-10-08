<?php
// Mengaktifkan buffer output agar fungsi header() tidak bentrok dengan admin_header.php
ob_start();

$adminTitle = 'Tambah Tanaman Hias Baru';
require_once __DIR__ . '/../includes/admin_header.php';

$db = getDB();
$categories = $db->query("SELECT * FROM categories ORDER BY nama_kategori ASC")->fetchAll();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $namaProduk = sanitize($_POST['nama_produk'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $deskripsi = sanitize($_POST['deskripsi'] ?? '');
    $hargaModal = (float)($_POST['harga_modal'] ?? 0);
    $hargaJual = (float)($_POST['harga_jual'] ?? 0);
    $stok = (int)($_POST['stok'] ?? 0);
    // Rating awal otomatis 0.0 (dihitung dari ulasan pembeli)
    $rating = 0.0;

    // Validasi
    if (empty($namaProduk)) $errors[] = 'Nama produk wajib diisi.';
    if ($categoryId <= 0) $errors[] = 'Kategori produk wajib dipilih.';
    if ($hargaModal <= 0) $errors[] = 'Harga modal harus lebih dari 0.';
    if ($hargaJual <= 0) $errors[] = 'Harga jual harus lebih dari 0.';
    if ($hargaJual < $hargaModal) $errors[] = 'Peringatan: Harga jual lebih rendah daripada harga modal.';
    if ($stok < 0) $errors[] = 'Stok tidak boleh bernilai negatif.';

    // Upload Foto
    $fotoName = 'default.jpg';
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
                $errors[] = 'Gagal mengunggah foto ke folder server.';
            }
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $db->prepare("
                INSERT INTO products (category_id, nama_produk, deskripsi, harga_modal, harga_jual, stok, foto, rating) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $categoryId,
                $namaProduk,
                $deskripsi,
                $hargaModal,
                $hargaJual,
                $stok,
                $fotoName,
                $rating
            ]);

            setFlash('success', 'Produk "' . $namaProduk . '" berhasil ditambahkan ke katalog!');
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
                <h1 class="text-xl font-extrabold text-slate-900">Tambah Tanaman Hias Baru</h1>
                <p class="text-xs text-slate-500 mt-0.5">Lengkapi formulir di bawah ini untuk menambahkan produk ke etalase toko.</p>
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

        <form action="product_add.php" method="POST" enctype="multipart/form-data" class="space-y-5">
            
            <!-- Nama Produk -->
            <div>
                <label for="nama_produk" class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Tanaman Hias *</label>
                <input type="text" id="nama_produk" name="nama_produk" required value="<?= htmlspecialchars($_POST['nama_produk'] ?? '') ?>" placeholder="Contoh: Monstera Deliciosa Mini Pot Semen" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-brand-500">
            </div>

            <!-- Kategori & Rating -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="category_id" class="block text-xs font-semibold text-slate-700 mb-1.5">Kategori Tanaman *</label>
                    <select id="category_id" name="category_id" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-brand-500 bg-white">
                        <option value="">-- Pilih Kategori --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= (isset($_POST['category_id']) && $_POST['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['nama_kategori']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Sistem Penilaian (Rating)</label>
                    <div class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-xs text-slate-500 flex items-center gap-2">
                        <i class="fa-solid fa-star text-amber-400"></i>
                        <span>Otomatis dari ulasan pembeli (Awal: <strong>Belum ada ulasan</strong>)</span>
                    </div>
                </div>
            </div>

            <!-- Harga Modal & Harga Jual -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label for="harga_modal" class="block text-xs font-semibold text-slate-700 mb-1.5">Harga Modal / Beli (Rp) *</label>
                    <input type="number" id="harga_modal" name="harga_modal" required min="0" value="<?= htmlspecialchars($_POST['harga_modal'] ?? '') ?>" placeholder="20000" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-brand-500">
                    <span class="text-[10px] text-slate-400">Untuk kalkulasi laba bersih</span>
                </div>
                <div>
                    <label for="harga_jual" class="block text-xs font-semibold text-slate-700 mb-1.5">Harga Jual (Rp) *</label>
                    <input type="number" id="harga_jual" name="harga_jual" required min="0" value="<?= htmlspecialchars($_POST['harga_jual'] ?? '') ?>" placeholder="35000" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-brand-500">
                    <span class="text-[10px] text-slate-400">Harga etalase pembeli</span>
                </div>
                <div>
                    <label for="stok" class="block text-xs font-semibold text-slate-700 mb-1.5">Jumlah Stok (Pot) *</label>
                    <input type="number" id="stok" name="stok" required min="0" value="<?= htmlspecialchars($_POST['stok'] ?? '20') ?>" placeholder="20" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-brand-500">
                </div>
            </div>

            <!-- Deskripsi -->
            <div>
                <label for="deskripsi" class="block text-xs font-semibold text-slate-700 mb-1.5">Deskripsi & Perawatan Tanaman</label>
                <textarea id="deskripsi" name="deskripsi" rows="3" placeholder="Informasi ukuran, pot yang didapat, kebutuhan sinar matahari dan frekuensi penyiraman..." class="w-full p-3 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-brand-500"><?= htmlspecialchars($_POST['deskripsi'] ?? '') ?></textarea>
            </div>

            <!-- Upload Foto -->
            <div>
                <label for="foto" class="block text-xs font-semibold text-slate-700 mb-1.5">Foto Produk (JPG, PNG, WEBP, SVG - Max 2MB)</label>
                <input type="file" id="foto" name="foto" accept="image/*" class="w-full text-xs text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100 cursor-pointer border border-slate-200 rounded-xl p-1">
                <p class="text-[11px] text-slate-400 mt-1">Jika tidak diisi, sistem akan otomatis menggunakan foto default.</p>
            </div>

            <!-- Action Buttons -->
            <div class="pt-4 flex items-center justify-end gap-3">
                <a href="products.php" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 text-xs font-semibold transition">
                    Batal
                </a>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow-md shadow-brand-600/20 transition">
                    Simpan Produk
                </button>
            </div>

        </form>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>

