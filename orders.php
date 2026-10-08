<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_check.php';
requireUserLogin();

$db = getDB();
$userId = (int)$_SESSION['user_id'];

// Handler Aksi User pada Pesanan yang berstatus 'Dikirim' (Pesanan Selesai / Ajukan Pengembalian)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_action'])) {
    $act = $_POST['order_action'];
    $targetOrderId = (int)($_POST['order_id'] ?? 0);

    // Pastikan pesanan milik user ini dan sedang berstatus 'Dikirim'
    $stmtVerify = $db->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ? LIMIT 1");
    $stmtVerify->execute([$targetOrderId, $userId]);
    $targetOrder = $stmtVerify->fetch();

    if (!$targetOrder) {
        setFlash('error', 'Pesanan tidak ditemukan.');
        header("Location: orders.php");
        exit;
    }

    if ($targetOrder['status_pesanan'] !== 'Dikirim') {
        setFlash('warning', 'Aksi ini hanya dapat dilakukan pada pesanan yang telah dikirim oleh Admin.');
        header("Location: orders.php");
        exit;
    }

    if ($act === 'complete_order') {
        // Konfirmasi Pesanan Diterima / Selesai oleh Pembeli -> Membuka akses Rating & Komentar
        $stmtUp = $db->prepare("UPDATE orders SET status_pesanan = 'Selesai' WHERE id = ? AND user_id = ?");
        $stmtUp->execute([$targetOrderId, $userId]);

        setFlash('success', 'Pesanan #' . $targetOrder['invoice'] . ' telah dikonfirmasi Selesai! Sekarang Anda dapat memberikan Rating & Komentar pada tanaman yang dibeli.');
        header("Location: orders.php");
        exit;

    } elseif ($act === 'request_return') {
        // Ajukan Pengembalian Barang oleh Pembeli
        $alasan = sanitize($_POST['alasan_pengembalian'] ?? '');
        if (empty($alasan)) {
            setFlash('error', 'Harap tuliskan alasan pengembalian barang.');
            header("Location: orders.php");
            exit;
        }

        $stmtRet = $db->prepare("UPDATE orders SET status_pesanan = 'Pengembalian Diajukan', alasan_pengembalian = ? WHERE id = ? AND user_id = ?");
        $stmtRet->execute([$alasan, $targetOrderId, $userId]);

        setFlash('warning', 'Pengajuan pengembalian untuk pesanan #' . $targetOrder['invoice'] . ' telah dikirim ke Admin untuk ditinjau.');
        header("Location: orders.php");
        exit;
    }
}

// Ambil riwayat pesanan user
$stmt = $db->prepare("
    SELECT o.*, p.metode_pembayaran 
    FROM orders o 
    LEFT JOIN payments p ON o.id = p.order_id 
    WHERE o.user_id = ?
    ORDER BY o.id DESC
");
$stmt->execute([$userId]);
$orders = $stmt->fetchAll();

$pageTitle = 'Riwayat Pesanan';
require_once __DIR__ . '/includes/header.php';
?>

<div class="bg-white border-b border-slate-200/80 py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-extrabold text-slate-900">Riwayat Pesanan Saya</h1>
        <p class="text-xs text-slate-500 mt-1">Pantau status pengiriman tanaman, konfirmasi pesanan diterima, ajukan pengembalian, atau berikan penilaian rating.</p>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <?php if (!empty($orders)): ?>
        <div class="space-y-6">
            <?php foreach ($orders as $ord): ?>
                <?php
                    // Ambil detail item per pesanan + cek apakah user sudah memberi ulasan pada produk tersebut
                    $stmtItems = $db->prepare("
                        SELECT od.*, pr.nama_produk, pr.foto,
                               (SELECT id FROM product_reviews r WHERE r.user_id = ? AND r.product_id = od.product_id LIMIT 1) AS review_id
                        FROM order_details od 
                        JOIN products pr ON od.product_id = pr.id 
                        WHERE od.order_id = ?
                    ");
                    $stmtItems->execute([$userId, $ord['id']]);
                    $items = $stmtItems->fetchAll();

                    // Warna badge status pesanan
                    $statusClass = 'bg-slate-200 text-slate-800';
                    if ($ord['status_pesanan'] === 'Selesai') {
                        $statusClass = 'bg-emerald-100 text-emerald-800 border border-emerald-200';
                    } elseif ($ord['status_pesanan'] === 'Dikirim') {
                        $statusClass = 'bg-blue-100 text-blue-800 border border-blue-200';
                    } elseif ($ord['status_pesanan'] === 'Dikemas' || $ord['status_pesanan'] === 'Diproses') {
                        $statusClass = 'bg-indigo-50 text-indigo-700 border border-indigo-200';
                    } elseif (strpos($ord['status_pesanan'], 'Pengembalian') !== false) {
                        $statusClass = 'bg-rose-100 text-rose-800 border border-rose-200';
                    } elseif ($ord['status_pesanan'] === 'Dibatalkan') {
                        $statusClass = 'bg-red-100 text-red-800';
                    }
                ?>
                <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
                    
                    <!-- Header Order Card -->
                    <div class="p-5 sm:p-6 bg-slate-50/70 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="space-y-1">
                            <div class="flex items-center gap-3">
                                <span class="font-mono font-bold text-slate-900 text-sm sm:text-base"><?= htmlspecialchars($ord['invoice']) ?></span>
                                <span class="text-xs text-slate-400">• <?= date('d M Y, H:i', strtotime($ord['tanggal'])) ?> WIB</span>
                            </div>
                            <p class="text-xs text-slate-500">
                                Metode: <?= htmlspecialchars($ord['metode_pembayaran'] ?? 'Midtrans Sandbox') ?>
                            </p>
                        </div>

                        <!-- Status Badges -->
                        <div class="flex flex-wrap items-center gap-2">
                            <!-- Payment status -->
                            <?php if ($ord['status_pembayaran'] === 'Pembayaran Berhasil'): ?>
                                <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                    <i class="fa-solid fa-check text-[10px] mr-1"></i> Lunas
                                </span>
                            <?php else: ?>
                                <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800">
                                    <i class="fa-regular fa-clock text-[10px] mr-1"></i> Menunggu Pembayaran
                                </span>
                            <?php endif; ?>

                            <!-- Order Status -->
                            <span class="px-3 py-1 rounded-full text-xs font-bold <?= $statusClass ?>">
                                <?php if ($ord['status_pesanan'] === 'Dikirim'): ?>
                                    <i class="fa-solid fa-truck-fast text-[10px] mr-1"></i>
                                <?php elseif ($ord['status_pesanan'] === 'Selesai'): ?>
                                    <i class="fa-solid fa-circle-check text-[10px] mr-1"></i>
                                <?php elseif (strpos($ord['status_pesanan'], 'Pengembalian') !== false): ?>
                                    <i class="fa-solid fa-rotate-left text-[10px] mr-1"></i>
                                <?php endif; ?>
                                <?= htmlspecialchars($ord['status_pesanan']) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Items List -->
                    <div class="p-5 sm:p-6 divide-y divide-slate-100">
                        <?php foreach ($items as $it): ?>
                            <div class="py-3.5 first:pt-0 last:pb-0 flex flex-col sm:flex-row sm:items-center gap-4">
                                <a href="product_detail.php?id=<?= $it['product_id'] ?>" class="w-14 h-14 rounded-xl bg-slate-100 overflow-hidden flex-shrink-0 border border-slate-100">
                                    <img src="assets/images/products/<?= htmlspecialchars($it['foto']) ?>" 
                                         alt="<?= htmlspecialchars($it['nama_produk']) ?>" 
                                         class="w-full h-full object-cover"
                                         onerror="this.src='assets/images/products/default.jpg'">
                                </a>
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-bold text-slate-800 text-sm truncate">
                                        <a href="product_detail.php?id=<?= $it['product_id'] ?>" class="hover:text-brand-600 transition">
                                            <?= htmlspecialchars($it['nama_produk']) ?>
                                        </a>
                                    </h4>
                                    <p class="text-xs text-slate-400"><?= $it['jumlah'] ?> pot x <?= formatRupiah($it['harga']) ?></p>
                                </div>
                                <div class="flex sm:flex-col items-center sm:items-end justify-between gap-2 text-right">
                                    <span class="text-xs font-bold text-slate-800"><?= formatRupiah($it['subtotal']) ?></span>
                                    
                                    <!-- TOMBOL RATING HANYA MUNCUL KETIKA PESANAN TELAH DIKONFIRMASI 'Selesai' OLEH USER -->
                                    <?php if ($ord['status_pesanan'] === 'Selesai'): ?>
                                        <a href="product_detail.php?id=<?= $it['product_id'] ?>#ulasan" class="inline-flex items-center gap-1.5 text-[11px] font-bold <?= $it['review_id'] ? 'text-emerald-700 bg-emerald-50 border-emerald-200 hover:bg-emerald-100' : 'text-amber-700 bg-amber-50 border-amber-200 hover:bg-amber-100' ?> border px-3 py-1.5 rounded-xl transition shadow-sm">
                                            <i class="fa-solid fa-star <?= $it['review_id'] ? 'text-emerald-500' : 'text-amber-400' ?> text-[10px]"></i>
                                            <span><?= $it['review_id'] ? 'Lihat / Edit Ulasan' : 'Beri Rating & Komentar' ?></span>
                                        </a>
                                    <?php elseif ($ord['status_pesanan'] === 'Dikirim'): ?>
                                        <span class="text-[11px] text-blue-600 font-semibold">
                                            <i class="fa-solid fa-circle-info mr-1"></i> Konfirmasi selesai di bawah untuk beri rating
                                        </span>
                                    <?php elseif (in_array($ord['status_pesanan'], ['Diproses', 'Dikemas'])): ?>
                                        <span class="text-[11px] text-slate-400 italic">
                                            Menunggu pengiriman oleh Admin
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Banner Konfirmasi Penerimaan / Pengembalian (MUNCUL SAAT ADMIN MEMILIH OPSI 'Dikirim') -->
                    <?php if ($ord['status_pesanan'] === 'Dikirim'): ?>
                        <div class="mx-5 sm:mx-6 mb-5 p-4 sm:p-5 rounded-2xl bg-blue-50/80 border border-blue-200 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center flex-shrink-0 text-sm shadow-sm">
                                    <i class="fa-solid fa-box-open"></i>
                                </div>
                                <div>
                                    <h4 class="text-xs sm:text-sm font-extrabold text-slate-900">
                                        Admin Telah Mengirimkan Pesanan Anda!
                                    </h4>
                                    <p class="text-xs text-slate-600 mt-0.5 leading-relaxed">
                                        Silakan pilih <strong>Pesanan Selesai</strong> jika tanaman telah diterima dengan baik (untuk membuka fitur penilaian <strong>Rating & Komentar</strong>), atau pilih <strong>Ajukan Pengembalian</strong> jika terdapat kendala pada tanaman.
                                    </p>
                                </div>
                            </div>

                            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5 flex-shrink-0">
                                <!-- Opsi 1: Ajukan Pengembalian -->
                                <button type="button" onclick="confirmReturnOrder(<?= (int)$ord['id'] ?>, '<?= htmlspecialchars($ord['invoice'], ENT_QUOTES) ?>')" class="px-4 py-2.5 rounded-xl border border-rose-300 bg-white hover:bg-rose-50 text-rose-700 font-bold text-xs transition flex items-center justify-center gap-1.5">
                                    <i class="fa-solid fa-rotate-left"></i>
                                    <span>Ajukan Pengembalian</span>
                                </button>

                                <!-- Opsi 2: Pesanan Selesai (Barang Diterima) -->
                                <button type="button" onclick="confirmCompleteOrder(<?= (int)$ord['id'] ?>, '<?= htmlspecialchars($ord['invoice'], ENT_QUOTES) ?>')" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs shadow-md shadow-emerald-600/20 transition flex items-center justify-center gap-1.5">
                                    <i class="fa-solid fa-circle-check"></i>
                                    <span>Pesanan Selesai</span>
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Info Alasan Pengembalian jika User mengajukan pengembalian -->
                    <?php if (!empty($ord['alasan_pengembalian']) && strpos($ord['status_pesanan'], 'Pengembalian') !== false): ?>
                        <div class="mx-5 sm:mx-6 mb-5 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-xs text-rose-900 space-y-1">
                            <div class="font-bold flex items-center gap-1.5 text-rose-800">
                                <i class="fa-solid fa-rotate-left"></i> Status Pengajuan Pengembalian: <?= htmlspecialchars($ord['status_pesanan']) ?>
                            </div>
                            <p class="text-rose-700">
                                <strong>Alasan Anda:</strong> "<?= htmlspecialchars($ord['alasan_pengembalian']) ?>"
                            </p>
                        </div>
                    <?php endif; ?>

                    <!-- Footer Order Card with Totals & Actions -->
                    <div class="p-5 sm:p-6 bg-slate-50/40 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="text-center sm:text-left">
                            <span class="text-xs text-slate-400 block">Total Tagihan (Termasuk Ongkir <?= formatRupiah($ord['ongkir']) ?>)</span>
                            <span class="text-base font-extrabold text-brand-700"><?= formatRupiah($ord['total_pembayaran']) ?></span>
                        </div>

                        <div class="flex items-center gap-3 w-full sm:w-auto">
                            <?php if ($ord['status_pembayaran'] === 'Menunggu Pembayaran'): ?>
                                <a href="payment.php?order_id=<?= $ord['id'] ?>" class="flex-1 sm:flex-initial px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs shadow-sm transition text-center flex items-center justify-center gap-2">
                                    <i class="fa-solid fa-wallet"></i>
                                    <span>Bayar Sekarang (Sandbox)</span>
                                </a>
                            <?php else: ?>
                                <a href="invoice.php?order_id=<?= $ord['id'] ?>" target="_blank" class="flex-1 sm:flex-initial px-4 py-2.5 rounded-xl border border-slate-200 hover:bg-white text-slate-700 font-bold text-xs transition text-center flex items-center justify-center gap-1.5 shadow-sm">
                                    <i class="fa-solid fa-print text-brand-600"></i>
                                    <span>Download Invoice</span>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-3xl border border-slate-200/80 p-12 text-center max-w-lg mx-auto shadow-sm">
            <div class="w-16 h-16 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-4 text-2xl">
                <i class="fa-solid fa-receipt"></i>
            </div>
            <h2 class="text-lg font-bold text-slate-800">Belum Ada Riwayat Pesanan</h2>
            <p class="text-xs text-slate-500 mt-1">Anda belum melakukan transaksi pembelian tanaman hias.</p>
            <a href="products.php" class="mt-4 inline-block px-5 py-2.5 rounded-xl bg-brand-600 text-white text-xs font-bold hover:bg-brand-700 transition">
                Mulai Belanja Sekarang
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- Hidden Form untuk Submit Konfirmasi Selesai & Pengembalian -->
<form id="orderActionForm" action="orders.php" method="POST" class="hidden">
    <input type="hidden" name="order_action" id="formOrderAction">
    <input type="hidden" name="order_id" id="formOrderId">
    <input type="hidden" name="alasan_pengembalian" id="formAlasanPengembalian">
</form>

<script>
function confirmCompleteOrder(orderId, invoice) {
    Swal.fire({
        title: 'Konfirmasi Pesanan Diterima?',
        html: '<p class="text-sm text-slate-600">Pastikan tanaman pada pesanan <strong>' + invoice + '</strong> telah diterima dengan baik.<br><br>Setelah menekan <strong>Pesanan Selesai</strong>, Anda dapat memberikan <strong>Rating & Komentar</strong> untuk tanaman ini.</p>',
        icon: 'question',
        iconColor: '#16a34a',
        showCancelButton: true,
        confirmButtonColor: '#16a34a',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i class="fa-solid fa-circle-check mr-1.5"></i> Ya, Pesanan Selesai',
        cancelButtonText: 'Batal',
        reverseButtons: true,
        customClass: {
            popup: 'rounded-3xl p-6',
            confirmButton: 'rounded-xl px-5 py-2.5 text-xs font-bold shadow-md',
            cancelButton: 'rounded-xl px-5 py-2.5 text-xs font-semibold'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('formOrderAction').value = 'complete_order';
            document.getElementById('formOrderId').value = orderId;
            document.getElementById('orderActionForm').submit();
        }
    });
}

function confirmReturnOrder(orderId, invoice) {
    Swal.fire({
        title: 'Ajukan Pengembalian Barang',
        html: '<p class="text-xs text-slate-500 mb-3">Tuliskan kendala atau alasan pengembalian untuk pesanan <strong>' + invoice + '</strong> (misal: tanaman rusak/layu, pot pecah, atau tidak sesuai pesanan):</p>',
        input: 'textarea',
        inputPlaceholder: 'Contoh: Pot keramik retak saat sampai dan daun tanaman patah...',
        inputAttributes: {
            'aria-label': 'Tuliskan alasan pengembalian'
        },
        icon: 'warning',
        iconColor: '#e11d48',
        showCancelButton: true,
        confirmButtonColor: '#e11d48',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i class="fa-solid fa-rotate-left mr-1.5"></i> Kirim Pengajuan Pengembalian',
        cancelButtonText: 'Batal',
        reverseButtons: true,
        customClass: {
            popup: 'rounded-3xl p-6',
            confirmButton: 'rounded-xl px-5 py-2.5 text-xs font-bold shadow-md',
            cancelButton: 'rounded-xl px-5 py-2.5 text-xs font-semibold'
        },
        preConfirm: (alasan) => {
            if (!alasan || !alasan.trim()) {
                Swal.showValidationMessage('Harap isi alasan pengembalian barang terlebih dahulu!');
                return false;
            }
            return alasan.trim();
        }
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            document.getElementById('formOrderAction').value = 'request_return';
            document.getElementById('formOrderId').value = orderId;
            document.getElementById('formAlasanPengembalian').value = result.value;
            document.getElementById('orderActionForm').submit();
        }
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>