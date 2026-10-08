<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_check.php';
requireUserLogin();

$db = getDB();
$userId = $_SESSION['user_id'];

$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: logout.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'update_profile';

    if ($action === 'update_profile') {
        $nama = sanitize($_POST['nama'] ?? '');
        $no_hp = sanitize($_POST['no_hp'] ?? '');
        $alamat = sanitize($_POST['alamat'] ?? '');

        if (empty($nama) || empty($no_hp) || empty($alamat)) {
            $error = 'Nama, nomor HP, dan alamat tidak boleh kosong.';
        } else {
            $stmtUpdate = $db->prepare("UPDATE users SET nama = ?, no_hp = ?, alamat = ? WHERE id = ?");
            $stmtUpdate->execute([$nama, $no_hp, $alamat, $userId]);
            $_SESSION['user_nama'] = $nama;
            $success = 'Data profil berhasil diperbarui.';
            // Refresh
            $user['nama'] = $nama;
            $user['no_hp'] = $no_hp;
            $user['alamat'] = $alamat;
        }
    } elseif ($action === 'change_password') {
        $oldPassword = $_POST['old_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (!password_verify($oldPassword, $user['password'])) {
            $error = 'Password lama Anda tidak cocok.';
        } elseif (strlen($newPassword) < 6) {
            $error = 'Password baru minimal 6 karakter.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'Konfirmasi password baru tidak cocok.';
        } else {
            $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
            $stmtPass = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmtPass->execute([$newHash, $userId]);
            $success = 'Password akun Anda berhasil diganti.';
            $user['password'] = $newHash;
        }
    }
}

$pageTitle = 'Profil Saya';
require_once __DIR__ . '/includes/header.php';
?>

<div class="bg-white border-b border-slate-200/80 py-6">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-extrabold text-slate-900">Pengaturan Akun & Profil</h1>
        <p class="text-xs text-slate-500 mt-1">Kelola data informasi diri dan alamat pengiriman pesanan Anda.</p>
    </div>
</div>

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    
    <?php if ($error): ?>
        <div class="mb-6 p-4 rounded-2xl bg-red-50 border border-red-200 text-red-700 text-xs flex items-center gap-2">
            <i class="fa-solid fa-circle-exclamation text-sm flex-shrink-0"></i>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="mb-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs flex items-center gap-2">
            <i class="fa-solid fa-circle-check text-sm flex-shrink-0"></i>
            <span><?= htmlspecialchars($success) ?></span>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        
        <!-- Sidebar Profile Card -->
        <div class="md:col-span-1">
            <div class="bg-white rounded-3xl border border-slate-200/80 p-6 text-center shadow-sm">
                <div class="w-20 h-20 rounded-full bg-brand-100 text-brand-700 flex items-center justify-center text-3xl font-extrabold mx-auto mb-4 border-2 border-brand-200">
                    <?= strtoupper(substr($user['nama'], 0, 1)) ?>
                </div>
                <h3 class="font-bold text-slate-900 text-base"><?= htmlspecialchars($user['nama']) ?></h3>
                <p class="text-xs text-slate-500 mb-4"><?= htmlspecialchars($user['email']) ?></p>
                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-brand-50 text-brand-700">
                    <i class="fa-solid fa-check-circle text-[10px]"></i> Pelanggan Terverifikasi
                </span>
                <div class="mt-6 pt-6 border-t border-slate-100 text-left text-xs text-slate-500 space-y-2">
                    <div><strong>Bergabung:</strong> <?= date('d M Y', strtotime($user['created_at'])) ?></div>
                    <div><strong>Nomor HP:</strong> <?= htmlspecialchars($user['no_hp']) ?></div>
                </div>
            </div>
        </div>

        <!-- Form Updates -->
        <div class="md:col-span-2 space-y-8">
            
            <!-- Update Info Form -->
            <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-sm">
                <h2 class="text-base font-bold text-slate-900 mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-id-card text-brand-600"></i> Informasi Pribadi & Pengiriman
                </h2>
                <form action="profile.php" method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="update_profile">

                    <div>
                        <label for="nama" class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Lengkap</label>
                        <input type="text" id="nama" name="nama" required value="<?= htmlspecialchars($user['nama']) ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-brand-500">
                    </div>

                    <div>
                        <label for="email" class="block text-xs font-semibold text-slate-700 mb-1.5">Email (Tidak dapat diubah)</label>
                        <input type="email" id="email" value="<?= htmlspecialchars($user['email']) ?>" disabled class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-100 text-slate-500 text-sm cursor-not-allowed">
                    </div>

                    <div>
                        <label for="no_hp" class="block text-xs font-semibold text-slate-700 mb-1.5">Nomor Handphone / WhatsApp</label>
                        <input type="text" id="no_hp" name="no_hp" required value="<?= htmlspecialchars($user['no_hp']) ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-brand-500">
                    </div>

                    <div>
                        <label for="alamat" class="block text-xs font-semibold text-slate-700 mb-1.5">Alamat Pengiriman Default</label>
                        <textarea id="alamat" name="alamat" required rows="3" class="w-full p-3 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-brand-500"><?= htmlspecialchars($user['alamat']) ?></textarea>
                    </div>

                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold transition shadow-sm">
                        Simpan Perubahan
                    </button>
                </form>
            </div>

            <!-- Change Password Form -->
            <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-sm">
                <h2 class="text-base font-bold text-slate-900 mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-key text-brand-600"></i> Ganti Password
                </h2>
                <form action="profile.php" method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="change_password">

                    <div>
                        <label for="old_password" class="block text-xs font-semibold text-slate-700 mb-1.5">Password Lama</label>
                        <input type="password" id="old_password" name="old_password" required placeholder="Masukkan password lama Anda" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-brand-500">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="new_password" class="block text-xs font-semibold text-slate-700 mb-1.5">Password Baru</label>
                            <input type="password" id="new_password" name="new_password" required placeholder="Minimal 6 karakter" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-brand-500">
                        </div>
                        <div>
                            <label for="confirm_password" class="block text-xs font-semibold text-slate-700 mb-1.5">Ulangi Password Baru</label>
                            <input type="password" id="confirm_password" name="confirm_password" required placeholder="Ulangi password baru" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-brand-500">
                        </div>
                    </div>

                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold transition shadow-sm">
                        Update Password
                    </button>
                </form>
            </div>

        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

