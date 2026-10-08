<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
requireAdminLogin();

$productId = (int)($_GET['id'] ?? 0);
if ($productId <= 0) {
    header("Location: products.php");
    exit;
}

$db = getDB();

try {
    // Cek apakah produk ada
    $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();

    if (!$product) {
        setFlash('error', 'Produk tidak ditemukan.');
        header("Location: products.php");
        exit;
    }

    // Cek apakah produk sudah pernah dipesan (Foreign Key protection)
    $stmtCheck = $db->prepare("SELECT COUNT(*) FROM order_details WHERE product_id = ?");
    $stmtCheck->execute([$productId]);
    $orderCount = (int)$stmtCheck->fetchColumn();

    if ($orderCount > 0) {
        setFlash('warning', 'Produk "' . $product['nama_produk'] . '" tidak dapat dihapus karena telah memiliki ' . $orderCount . ' data transaksi penjualan. Anda dapat mengubah stoknya menjadi 0 untuk menonaktifkannya.');
        header("Location: products.php");
        exit;
    }

    // Hapus file foto jika bukan default/seed svg
    $foto = $product['foto'];
    if (!empty($foto) && strpos($foto, 'prod_') === 0) {
        $filePath = __DIR__ . '/../assets/images/products/' . $foto;
        if (file_exists($filePath)) {
            @unlink($filePath);
        }
    }

    // Hapus dari database
    $stmtDel = $db->prepare("DELETE FROM products WHERE id = ?");
    $stmtDel->execute([$productId]);

    setFlash('success', 'Produk "' . $product['nama_produk'] . '" berhasil dihapus dari sistem.');

} catch (Exception $e) {
    setFlash('error', 'Gagal menghapus produk: ' . $e->getMessage());
}

header("Location: products.php");
exit;

