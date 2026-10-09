<?php
$adminTitle = 'Pengaturan Akun & Ganti Password';
require_once __DIR__ . '/../includes/admin_header.php';

$db = getDB();
$adminId = $_SESSION['admin_id'];

$stmt = $db->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->execute([$adminId]);
$admin = $stmt->fetch();

if (!$admin) {
    header("Location: logout.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $nama = sanitize($_POST['nama'] ?? '');
        $username = sanitize($_POST['username'] ?? '');

        if (empty($nama) || empty($username)) {
            $error = 'Nama dan username tidak boleh kosong.';
        } else {
            // Cek apakah username sudah dipakai akun admin lain
            $stmtCek = $db->prepare("SELECT id FROM admins WHERE username = ? AND id != ?");
            $stmtCek->execute([$username, $adminId]);
            if ($stmtCek->fetch()) {
                $error = 'Username sudah dipakai oleh akun lain.';
            } else {
                $stmtUp = $db->prepare("UPDATE admins SET nama = ?, username = ? WHERE id = ?");
                $stmtUp->execute([$nama, $username, $adminId]);
                $_SESSION['admin_nama'] = $nama;
                $_SESSION['admin_username'] = $username;
                $success = 'Data profil admin berhasil diperbarui.';
                $admin['nama'] = $nama;
                $admin['username'] = $username;
            }
        }
    } elseif ($action === 'change_password') {
        $oldPassword = $_POST['old_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (!password_verify($oldPassword, $admin['password'])) {
            $error = 'Password lama admin tidak cocok.';
        } elseif (strlen($newPassword) < 6) {
            $error = 'Password baru minimal 6 karakter.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'Konfirmasi password baru tidak cocok.';
        } else {
            $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
            $stmtPass = $db->prepare("UPDATE admins SET password = ? WHERE id = ?");
            $stmtPass->execute([$newHash, $adminId]);
            $success = 'Password admin berhasil diganti!';
            $admin['password'] = $newHash;
        }
    }
}
?>

<div class="space-y-6 max-w-4xl">
    
    <!-- Header -->
    <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-extrabold text-slate-900">Pengaturan Akun & Keamanan</h1>
            <p class="text-xs text-slate-500 mt-0.5">Kelola identitas akun pengelola dan perbarui password login admin.</p>
        </div>
        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-brand-50 text-brand-800 text-xs font-semibold border border-brand-200">
            <i class="fa-solid fa-shield-halved text-brand-600"></i>
            <span>Administrator Terverifikasi</span>
        </div>
    </div>

    <!-- Alert Messages -->
    <?php if (!empty($error)): ?>
        <div class="p-4 rounded-2xl bg-red-50 border border-red-200 text-red-700 text-xs flex items-center gap-3 shadow-sm">
            <i class="fa-solid fa-circle-exclamation text-base flex-shrink-0"></i>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs flex items-center gap-3 shadow-sm">
            <i class="fa-solid fa-circle-check text-base text-emerald-600 flex-shrink-0"></i>
            <span><?= htmlspecialchars($success) ?></span>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        
        <!-- Card 1: Data Akun Admin -->
        <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center gap-3 mb-5 pb-4 border-b border-slate-100">
                    <div class="w-10 h-10 rounded-xl bg-brand-100 text-brand-700 flex items-center justify-center text-lg">
                        <i class="fa-solid fa-user-gear"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Informasi Akun</h2>
                        <p class="text-[11px] text-slate-400">Nama tampilan dan username login</p>
                    </div>
                </div>

                <form action="profile.php" method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="update_profile">

                    <div>
                        <label for="nama" class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Lengkap Admin</label>
                        <input type="text" id="nama" name="nama" required value="<?= htmlspecialchars($admin['nama']) ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800 focus:outline-none focus:border-brand-500">
                    </div>

                    <div>
                        <label for="username" class="block text-xs font-semibold text-slate-700 mb-1.5">Username Login</label>
                        <input type="text" id="username" name="username" required value="<?= htmlspecialchars($admin['username']) ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800 focus:outline-none focus:border-brand-500">
                        <span class="text-[10px] text-slate-400 mt-1 block">Digunakan saat masuk ke halaman Login Admin.</span>
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs shadow-sm transition">
                            Simpan Perubahan Profil
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Card 2: Ganti Password Admin -->
        <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center gap-3 mb-5 pb-4 border-b border-slate-100">
                    <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center text-lg">
                        <i class="fa-solid fa-key"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Ganti Password Admin</h2>
                        <p class="text-[11px] text-slate-400">Perbarui kata sandi login secara berkala</p>
                    </div>
                </div>

                <form action="profile.php" method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="change_password">

                    <div>
                        <label for="old_password" class="block text-xs font-semibold text-slate-700 mb-1.5">Password Lama</label>
                        <input type="password" id="old_password" name="old_password" required placeholder="Masukkan password admin saat ini" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800 focus:outline-none focus:border-brand-500">
                    </div>

                    <div>
                        <label for="new_password" class="block text-xs font-semibold text-slate-700 mb-1.5">Password Baru</label>
                        <input type="password" id="new_password" name="new_password" required placeholder="Minimal 6 karakter" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800 focus:outline-none focus:border-brand-500">
                    </div>

                    <div>
                        <label for="confirm_password" class="block text-xs font-semibold text-slate-700 mb-1.5">Konfirmasi Password Baru</label>
                        <input type="password" id="confirm_password" name="confirm_password" required placeholder="Ketik ulang password baru" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs text-slate-800 focus:outline-none focus:border-brand-500">
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs shadow-sm transition">
                            Perbarui Password Admin
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>

</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>

