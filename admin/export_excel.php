<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_check.php';
requireAdminLogin();

$db = getDB();
$period = sanitize($_GET['period'] ?? 'month');
$startDate = sanitize($_GET['start_date'] ?? '');
$endDate = sanitize($_GET['end_date'] ?? '');

if (!empty($startDate) && !empty($endDate)) {
    $period = 'custom';
}

$whereSql = "WHERE o.status_pembayaran = 'Pembayaran Berhasil'";
$periodLabel = 'Bulan Ini';

switch ($period) {
    case 'custom':
        $whereSql .= " AND DATE(o.tanggal) BETWEEN '$startDate' AND '$endDate'";
        $periodLabel = 'Kustom (' . date('d/m/Y', strtotime($startDate)) . ' s/d ' . date('d/m/Y', strtotime($endDate)) . ')';
        break;
    case 'day':
        $whereSql .= " AND DATE(o.tanggal) = CURDATE()";
        $periodLabel = 'Hari Ini (' . date('d-m-Y') . ')';
        break;
    case 'week':
        $whereSql .= " AND o.tanggal >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        $periodLabel = '7 Hari Terakhir';
        break;
    case 'year':
        $whereSql .= " AND YEAR(o.tanggal) = YEAR(CURDATE())";
        $periodLabel = 'Tahun ' . date('Y');
        break;
    case 'all':
        $periodLabel = 'Semua Waktu';
        break;
    case 'month':
    default:
        $whereSql .= " AND MONTH(o.tanggal) = MONTH(CURDATE()) AND YEAR(o.tanggal) = YEAR(CURDATE())";
        $periodLabel = 'Bulan Ini (' . date('F Y') . ')';
        break;
}


// Query data item penjualan
$stmtDetails = $db->query("
    SELECT o.invoice, o.tanggal, o.status_pesanan, u.nama AS pembeli, 
           od.jumlah, od.harga, od.harga_modal, od.subtotal, 
           (od.harga - od.harga_modal) * od.jumlah AS laba_item, 
           pr.nama_produk 
    FROM order_details od 
    JOIN orders o ON od.order_id = o.id 
    JOIN users u ON o.user_id = u.id 
    JOIN products pr ON od.product_id = pr.id 
    $whereSql 
    ORDER BY o.id DESC, od.id ASC
");
$rows = $stmtDetails->fetchAll();

// Header HTTP untuk unduhan file Excel (.xls)
$fileName = 'Laporan_Penjualan_Tanaman_Mini_' . date('Ymd_His') . '.xls';
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$fileName\"");
header("Pragma: no-cache");
header("Expires: 0");

$totalSemuaPenjualan = 0;
$totalSemuaModal = 0;
$totalSemuaLaba = 0;
$totalSemuaJumlah = 0;
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <style>
        body { font-family: Arial, sans-serif; font-size: 11pt; }
        .title { font-size: 16pt; font-weight: bold; color: #15803d; }
        .subtitle { font-size: 11pt; color: #475569; }
        th { background-color: #16a34a; color: #ffffff; font-weight: bold; text-align: center; border: 1px solid #0f766e; padding: 8px; }
        td { border: 1px solid #cbd5e1; padding: 6px; }
        .number { text-align: right; }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .footer-total { background-color: #f0fdf4; font-weight: bold; border-top: 2px solid #16a34a; }
    </style>
</head>
<body>
    <table>
        <tr>
            <td colspan="11" class="title">LAPORAN REKAPITULASI PENJUALAN & LABA</td>
        </tr>
        <tr>
            <td colspan="11" class="subtitle">Toko: Tanaman Hias Mini</td>
        </tr>
        <tr>
            <td colspan="11" class="subtitle">Periode: <?= htmlspecialchars($periodLabel) ?> | Waktu Unduh: <?= date('d/m/Y H:i') ?> WIB</td>
        </tr>
        <tr><td colspan="11"></td></tr>
        <thead>
            <tr>
                <th>No</th>
                <th>Invoice</th>
                <th>Tanggal</th>
                <th>Nama Pembeli</th>
                <th>Produk</th>
                <th>Jumlah</th>
                <th>Harga Jual (Rp)</th>
                <th>Harga Modal (Rp)</th>
                <th>Total Penjualan (Rp)</th>
                <th>Laba Bersih (Rp)</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($rows)): ?>
                <?php $no = 1; foreach ($rows as $r): ?>
                    <?php 
                        $totalSemuaPenjualan += $r['subtotal'];
                        $totalSemuaModal += ($r['harga_modal'] * $r['jumlah']);
                        $totalSemuaLaba += $r['laba_item'];
                        $totalSemuaJumlah += $r['jumlah'];
                    ?>
                    <tr>
                        <td class="center"><?= $no++ ?></td>
                        <td class="center" style="mso-number-format:'\@';"><?= htmlspecialchars($r['invoice']) ?></td>
                        <td class="center"><?= date('d/m/Y H:i', strtotime($r['tanggal'])) ?></td>
                        <td><?= htmlspecialchars($r['pembeli']) ?></td>
                        <td><?= htmlspecialchars($r['nama_produk']) ?></td>
                        <td class="center"><?= $r['jumlah'] ?></td>
                        <td class="number"><?= number_format($r['harga'], 0, ',', '.') ?></td>
                        <td class="number"><?= number_format($r['harga_modal'], 0, ',', '.') ?></td>
                        <td class="number bold"><?= number_format($r['subtotal'], 0, ',', '.') ?></td>
                        <td class="number bold" style="color: #15803d;"><?= number_format($r['laba_item'], 0, ',', '.') ?></td>
                        <td class="center"><?= htmlspecialchars($r['status_pesanan']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <!-- Baris Total -->
                <tr class="footer-total">
                    <td colspan="5" class="center bold">TOTAL KESELURUHAN</td>
                    <td class="center bold"><?= $totalSemuaJumlah ?></td>
                    <td></td>
                    <td class="number bold"><?= number_format($totalSemuaModal, 0, ',', '.') ?></td>
                    <td class="number bold"><?= number_format($totalSemuaPenjualan, 0, ',', '.') ?></td>
                    <td class="number bold" style="color: #15803d;"><?= number_format($totalSemuaLaba, 0, ',', '.') ?></td>
                    <td></td>
                </tr>
            <?php else: ?>
                <tr>
                    <td colspan="11" class="center">Tidak ada transaksi pada periode yang dipilih.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
