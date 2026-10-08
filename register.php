<?php
require_once __DIR__ . '/config/database.php';

// Jika user sudah login, redirect ke beranda
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$errors = [];
$formData = [
    'nama' => '',
    'email' => '',
    'no_hp' => '',
    'alamat' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['nama'] = sanitize($_POST['nama'] ?? '');
    $formData['email'] = sanitize($_POST['email'] ?? '');
    $formData['no_hp'] = sanitize($_POST['no_hp'] ?? '');
    $formData['alamat'] = sanitize($_POST['alamat'] ?? '');
    $password = $_POST['password'] ?? '';
    $konfirmasiPassword = $_POST['konfirmasi_password'] ?? '';

    // Validasi
    if (empty($formData['nama'])) {
        $errors[] = 'Nama lengkap wajib diisi.';
    }
    if (empty($formData['email']) || !filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email valid wajib diisi.';
    }
    if (empty($formData['no_hp'])) {
        $errors[] = 'Nomor HP wajib diisi.';
    }
    if (empty($formData['alamat'])) {
        $errors[] = 'Alamat pengiriman wajib diisi.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password minimal 6 karakter.';
    }
    if ($password !== $konfirmasiPassword) {
        $errors[] = 'Konfirmasi password tidak cocok.';
    }

    if (empty($errors)) {
        try {
            $db = getDB();

            // Cek apakah email sudah terdaftar
            $stmtCheck = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmtCheck->execute([$formData['email']]);
            if ($stmtCheck->fetch()) {
                $errors[] = 'Email sudah digunakan. Silakan gunakan email lain atau login.';
            } else {
                // Simpan user baru dengan enkripsi password
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                $stmtInsert = $db->prepare("
                    INSERT INTO users (nama, email, password, no_hp, alamat, role) 
                    VALUES (?, ?, ?, ?, ?, 'user')
                ");
                $stmtInsert->execute([
                    $formData['nama'],
                    $formData['email'],
                    $hashedPassword,
                    $formData['no_hp'],
                    $formData['alamat']
                ]);

                // Auto login setelah registrasi
                $newUserId = $db->lastInsertId();
                $_SESSION['user_id'] = $newUserId;
                $_SESSION['user_nama'] = $formData['nama'];
                $_SESSION['user_email'] = $formData['email'];

                setFlash('success', 'Pendaftaran berhasil! Selamat datang di Toko Tanaman Hias Mini.');
                header("Location: index.php");
                exit;
            }
        } catch (Exception $e) {
            $errors[] = 'Terjadi kesalahan sistem: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Daftar Akun Pembeli';
require_once __DIR__ . '/includes/header.php';
?>

<div class="py-12 px-4 sm:px-6 lg:px-8 max-w-lg mx-auto">
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-md p-6 sm:p-8">
        
        <!-- Header Info -->
        <div class="text-center mb-8">
            <div class="w-12 h-12 rounded-2xl bg-brand-100 text-brand-600 flex items-center justify-center mx-auto mb-3 text-xl">
                <i class="fa-solid fa-user-plus"></i>
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900">Buat Akun Baru</h1>
            <p class="text-xs text-slate-500 mt-1">Daftar akun untuk mulai berbelanja tanaman hias mini.</p>
        </div>

        <!-- Error Messages -->
        <?php if (!empty($errors)): ?>
            <div class="mb-6 p-4 rounded-2xl bg-red-50 border border-red-200 text-red-700 text-xs space-y-1">
                <div class="font-bold flex items-center gap-1.5 mb-1">
                    <i class="fa-solid fa-circle-exclamation"></i> Harap perbaiki kesalahan berikut:
                </div>
                <ul class="list-disc list-inside space-y-0.5">
                    <?php foreach ($errors as $err): ?>
                        <li><?= htmlspecialchars($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Registration Form -->
        <form action="register.php" method="POST" class="space-y-4">
            
            <!-- Nama Lengkap -->
            <div>
                <label for="nama" class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Lengkap</label>
                <div class="relative">
                    <i class="fa-solid fa-user absolute left-3.5 top-3 text-slate-400 text-xs"></i>
                    <input type="text" id="nama" name="nama" required value="<?= htmlspecialchars($formData['nama']) ?>" placeholder="Contoh: Budi Santoso" class="w-full pl-9 pr-3 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
                </div>
            </div>

            <!-- Email -->
            <div>
                <label for="email" class="block text-xs font-semibold text-slate-700 mb-1.5">Email Aktif</label>
                <div class="relative">
                    <i class="fa-solid fa-envelope absolute left-3.5 top-3 text-slate-400 text-xs"></i>
                    <input type="email" id="email" name="email" required value="<?= htmlspecialchars($formData['email']) ?>" placeholder="nama@email.com" class="w-full pl-9 pr-3 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
                </div>
            </div>

            <!-- Nomor HP -->
            <div>
                <label for="no_hp" class="block text-xs font-semibold text-slate-700 mb-1.5">Nomor Handphone / WhatsApp</label>
                <div class="relative">
                    <i class="fa-solid fa-phone absolute left-3.5 top-3 text-slate-400 text-xs"></i>
                    <input type="text" id="no_hp" name="no_hp" required value="<?= htmlspecialchars($formData['no_hp']) ?>" placeholder="08xxxxxxxxxx" class="w-full pl-9 pr-3 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
                </div>
            </div>

            <!-- Password -->
            <div>
                <label for="password" class="block text-xs font-semibold text-slate-700 mb-1.5">Password</label>
                <div class="relative">
                    <i class="fa-solid fa-lock absolute left-3.5 top-3 text-slate-400 text-xs"></i>
                    <input type="password" id="password" name="password" required placeholder="Minimal 6 karakter" class="w-full pl-9 pr-3 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
                </div>
            </div>

            <!-- Konfirmasi Password -->
            <div>
                <label for="konfirmasi_password" class="block text-xs font-semibold text-slate-700 mb-1.5">Ulangi Password</label>
                <div class="relative">
                    <i class="fa-solid fa-shield-halved absolute left-3.5 top-3 text-slate-400 text-xs"></i>
                    <input type="password" id="konfirmasi_password" name="konfirmasi_password" required placeholder="Ketik ulang password Anda" class="w-full pl-9 pr-3 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
                </div>
            </div>

            <!-- Alamat Lengkap -->
            <div>
                <label for="alamat" class="block text-xs font-semibold text-slate-700 mb-1.5">Alamat Pengiriman Lengkap</label>
                <textarea id="alamat" name="alamat" required rows="3" placeholder="Nama jalan, nomor rumah, RT/RW, kecamatan, kota, kode pos" class="w-full p-3 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500"><?= htmlspecialchars($formData['alamat']) ?></textarea>
            </div>

            <!-- Submit Button -->
            <div class="pt-2">
                <button type="submit" class="w-full py-3 px-4 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-sm shadow-md shadow-brand-600/25 transition">
                    Daftar Akun Sekarang
                </button>
            </div>
        </form>

        <!-- Footer Switch to Login -->
        <div class="mt-6 pt-6 border-t border-slate-100 text-center text-xs text-slate-500">
            Sudah memiliki akun? 
            <a href="login.php" class="font-bold text-brand-600 hover:underline">Masuk di sini</a>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

