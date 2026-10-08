<?php
$adminTitle = 'Kelola Pengguna & Pelanggan';
require_once __DIR__ . '/../includes/admin_header.php';

$db = getDB();
$search = sanitize($_GET['q'] ?? '');

$sql = "
    SELECT u.*, 
           COUNT(o.id) AS total_order, 
           COALESCE(SUM(CASE WHEN o.status_pembayaran = 'Pembayaran Berhasil' THEN o.total_pembayaran ELSE 0 END), 0) AS total_belanja 
    FROM users u 
    LEFT JOIN orders o ON u.id = o.user_id 
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $sql .= " AND (u.nama LIKE ? OR u.email LIKE ? OR u.no_hp LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " GROUP BY u.id ORDER BY u.id DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();
?>

<div class="space-y-6">
    
    <!-- Top Action Bar -->
    <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm flex flex-col md:flex-row items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-extrabold text-slate-900">Daftar Pengguna / Pelanggan Terdaftar</h1>
            <p class="text-xs text-slate-500 mt-0.5">Informasi akun pembeli, data kontak, dan akumulasi transaksi belanja.</p>
        </div>
        <span class="text-xs font-bold text-slate-600 bg-slate-100 px-4 py-2 rounded-xl">
            Total Pengguna: <strong><?= count($users) ?></strong> orang
        </span>
    </div>

    <!-- Search Box -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm flex items-center justify-between">
        <form action="users.php" method="GET" class="relative w-full sm:w-80">
            <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3 text-slate-400 text-xs"></i>
            <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Cari nama, email, nomor HP..." class="w-full pl-9 pr-3 py-2 rounded-xl border border-slate-200 text-xs focus:outline-none focus:border-brand-500">
        </form>

        <?php if ($search): ?>
            <a href="users.php" class="text-xs text-slate-500 hover:text-slate-700 font-medium">Reset Pencarian</a>
        <?php endif; ?>
    </div>

    <!-- Users Table -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100 text-slate-500 font-bold uppercase tracking-wider">
                        <th class="py-3.5 px-5">ID</th>
                        <th class="py-3.5 px-5">Nama Lengkap</th>
                        <th class="py-3.5 px-5">Kontak (Email & HP)</th>
                        <th class="py-3.5 px-5">Alamat Pengiriman</th>
                        <th class="py-3.5 px-5 text-center">Total Pesanan</th>
                        <th class="py-3.5 px-5 text-right">Total Belanja (Lunas)</th>
                        <th class="py-3.5 px-5">Tanggal Daftar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (!empty($users)): ?>
                        <?php foreach ($users as $u): ?>
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="py-3.5 px-5 font-mono text-slate-400 font-bold">
                                    #<?= $u['id'] ?>
                                </td>
                                <td class="py-3.5 px-5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-brand-100 text-brand-700 flex items-center justify-center font-extrabold text-xs flex-shrink-0">
                                            <?= strtoupper(substr($u['nama'], 0, 1)) ?>
                                        </div>
                                        <span class="font-bold text-slate-900"><?= htmlspecialchars($u['nama']) ?></span>
                                    </div>
                                </td>
                                <td class="py-3.5 px-5">
                                    <span class="text-slate-800 font-medium block"><?= htmlspecialchars($u['email']) ?></span>
                                    <span class="text-[11px] text-slate-400 flex items-center gap-1 mt-0.5">
                                        <i class="fa-brands fa-whatsapp text-emerald-500"></i> <?= htmlspecialchars($u['no_hp']) ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-5 max-w-xs truncate text-slate-600" title="<?= htmlspecialchars($u['alamat']) ?>">
                                    <?= htmlspecialchars($u['alamat']) ?>
                                </td>
                                <td class="py-3.5 px-5 text-center">
                                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-slate-100 text-slate-700">
                                        <?= $u['total_order'] ?> pesanan
                                    </span>
                                </td>
                                <td class="py-3.5 px-5 text-right font-extrabold text-brand-700">
                                    <?= formatRupiah($u['total_belanja']) ?>
                                </td>
                                <td class="py-3.5 px-5 text-slate-500 whitespace-nowrap">
                                    <?= date('d M Y', strtotime($u['created_at'])) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="py-10 text-center text-slate-400">
                                Tidak ada data pengguna yang terdaftar.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>

