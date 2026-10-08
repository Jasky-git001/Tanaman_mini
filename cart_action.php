<?php
/**
 * Handler Aksi Keranjang Belanja (Tambah, Langsung Beli, Ubah Qty, Hapus)
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_check.php';

// Blokir Admin agar tidak dapat menambah ke keranjang atau checkout
if (isAdminLoggedIn()) {
    setFlash('warning', 'Mode Administrator aktif: Admin tidak dapat menambahkan produk ke keranjang atau membeli barang.');
    header("Location: index.php");
    exit;
}

// Pastikan user login
if (!isUserLoggedIn()) {
    setFlash('warning', 'Silakan login sebagai Pembeli terlebih dahulu untuk melanjutkan pembelian.');
    $productId = (int)($_POST['product_id'] ?? $_GET['product_id'] ?? 0);
    $redirect = $productId > 0 ? "product_detail.php?id=$productId" : 'products.php';
    header("Location: login.php?redirect=" . urlencode($redirect));
    exit;
}

$db = getDB();
$userId = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    if ($action === 'add' || $action === 'buy_now') {
        $productId = (int)($_POST['product_id'] ?? 0);
        $jumlah = max(1, (int)($_POST['jumlah'] ?? 1));

        // Cek produk dan stok
        $stmtProd = $db->prepare("SELECT id, nama_produk, stok FROM products WHERE id = ?");
        $stmtProd->execute([$productId]);
        $product = $stmtProd->fetch();

        if (!$product) {
            setFlash('error', 'Produk tidak ditemukan.');
            header("Location: products.php");
            exit;
        }

        if ($product['stok'] <= 0) {
            setFlash('error', 'Maaf, stok ' . $product['nama_produk'] . ' sedang habis.');
            header("Location: product_detail.php?id=$productId");
            exit;
        }

        if ($jumlah > $product['stok']) {
            $jumlah = $product['stok'];
        }

        // Jika opsi "Langsung Beli" (buy_now), langsung simpan ke session khusus & arahkan ke halaman checkout
        if ($action === 'buy_now') {
            $_SESSION['buy_now_item'] = [
                'product_id' => $productId,
                'jumlah' => $jumlah
            ];
            header("Location: checkout.php?mode=buy_now");
            exit;
        }

        // Jika opsi "Tambah ke Keranjang" (add)
        $stmtCart = $db->prepare("SELECT id, jumlah FROM carts WHERE user_id = ? AND product_id = ?");
        $stmtCart->execute([$userId, $productId]);
        $existing = $stmtCart->fetch();

        if ($existing) {
            $newJumlah = $existing['jumlah'] + $jumlah;
            if ($newJumlah > $product['stok']) {
                $newJumlah = $product['stok'];
                setFlash('warning', 'Jumlah disesuaikan dengan sisa stok maksimal yang tersedia (' . $product['stok'] . ').');
            } else {
                setFlash('success', 'Jumlah ' . $product['nama_produk'] . ' di keranjang berhasil ditambah!');
            }
            $stmtUpdate = $db->prepare("UPDATE carts SET jumlah = ? WHERE id = ?");
            $stmtUpdate->execute([$newJumlah, $existing['id']]);
        } else {
            $stmtInsert = $db->prepare("INSERT INTO carts (user_id, product_id, jumlah) VALUES (?, ?, ?)");
            $stmtInsert->execute([$userId, $productId, $jumlah]);
            setFlash('success', $product['nama_produk'] . ' berhasil ditambahkan ke keranjang belanja!');
        }

        header("Location: cart.php");
        exit;

    } elseif ($action === 'update') {
        $cartId = (int)($_POST['cart_id'] ?? 0);
        $delta = (int)($_POST['delta'] ?? 0); // +1 atau -1

        $stmtGet = $db->prepare("
            SELECT c.*, p.stok, p.nama_produk 
            FROM carts c 
            JOIN products p ON c.product_id = p.id 
            WHERE c.id = ? AND c.user_id = ?
        ");
        $stmtGet->execute([$cartId, $userId]);
        $item = $stmtGet->fetch();

        if ($item) {
            $newQty = $item['jumlah'] + $delta;
            if ($newQty <= 0) {
                $stmtDel = $db->prepare("DELETE FROM carts WHERE id = ?");
                $stmtDel->execute([$cartId]);
                setFlash('info', $item['nama_produk'] . ' dihapus dari keranjang.');
            } elseif ($newQty > $item['stok']) {
                setFlash('warning', 'Maksimal stok yang tersedia adalah ' . $item['stok']);
            } else {
                $stmtUp = $db->prepare("UPDATE carts SET jumlah = ? WHERE id = ?");
                $stmtUp->execute([$newQty, $cartId]);
            }
        }
        header("Location: cart.php");
        exit;

    } elseif ($action === 'remove') {
        $cartId = (int)($_GET['id'] ?? 0);
        $stmtDel = $db->prepare("DELETE FROM carts WHERE id = ? AND user_id = ?");
        $stmtDel->execute([$cartId, $userId]);
        setFlash('info', 'Item berhasil dihapus dari keranjang.');
        header("Location: cart.php");
        exit;

    } elseif ($action === 'clear') {
        $stmtClear = $db->prepare("DELETE FROM carts WHERE user_id = ?");
        $stmtClear->execute([$userId]);
        setFlash('info', 'Keranjang belanja dikosongkan.');
        header("Location: cart.php");
        exit;
    }
} catch (Exception $e) {
    setFlash('error', 'Terjadi kesalahan: ' . $e->getMessage());
    header("Location: cart.php");
    exit;
}

header("Location: cart.php");
exit;