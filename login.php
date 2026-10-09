<?php
require_once __DIR__ . '/config/database.php';

// Jika user sudah login, arahkan ke beranda
if (isset($_SESSION['user_id']) && !isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';
$redirect = sanitize($_GET['redirect'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $redirectPost = sanitize($_POST['redirect'] ?? '');

    if (empty($email) || empty($password)) {
        $error = 'Email dan password wajib diisi.';
    } else {
        try {
            $db = getDB();
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Bersihkan sesi Admin jika ada
                unset($_SESSION['admin_id'], $_SESSION['admin_username'], $_SESSION['admin_nama'], $_SESSION['admin_logged_in']);

                // Login Sukses
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_nama'] = $user['nama'];
                $_SESSION['user_email'] = $user['email'];

                setFlash('success', 'Selamat datang kembali, ' . $user['nama'] . '!');

                // Cek apakah ada redirect khusus
                if (!empty($redirectPost)) {
                    if (strpos($redirectPost, 'cart_add_') === 0) {
                        $pId = (int)str_replace('cart_add_', '', $redirectPost);
                        if ($pId > 0) {
                            // Masukkan ke keranjang otomatis
                            $stmtCart = $db->prepare("
                                INSERT INTO carts (user_id, product_id, jumlah) 
                                VALUES (?, ?, 1) 
                                ON DUPLICATE KEY UPDATE jumlah = jumlah + 1
                            ");
                            $stmtCart->execute([$user['id'], $pId]);
                            header("Location: cart.php");
                            exit;
                        }
                    }
                    header("Location: " . urldecode($redirectPost));
                    exit;
                }

                header("Location: index.php");
                exit;
            } else {
                $error = 'Email atau password yang Anda masukkan salah.';
            }
        } catch (Exception $e) {
            $error = 'Terjadi kesalahan sistem: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Login Pembeli';
require_once __DIR__ . '/includes/header.php';
?>

<div class="py-16 px-4 sm:px-6 lg:px-8 max-w-md mx-auto">
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-md p-6 sm:p-8">
        
        <!-- Header Info -->
        <div class="text-center mb-8">
            <div class="w-12 h-12 rounded-2xl bg-brand-100 text-brand-600 flex items-center justify-center mx-auto mb-3 text-xl">
                <i class="fa-solid fa-right-to-bracket"></i>
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900">Masuk Akun Pembeli</h1>
            <p class="text-xs text-slate-500 mt-1">Silakan masuk untuk melanjutkan belanja dan checkout.</p>
        </div>


        <!-- Error Message -->
        <?php if (!empty($error)): ?>
            <div class="mb-6 p-3.5 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation text-sm flex-shrink-0"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form action="login.php" method="POST" class="space-y-4">
            <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">

            <!-- Email -->
            <div>
                <label for="email" class="block text-xs font-semibold text-slate-700 mb-1.5">Email Akun</label>
                <div class="relative">
                    <i class="fa-solid fa-envelope absolute left-3.5 top-3 text-slate-400 text-xs"></i>
                    <input type="email" id="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="nama@email.com" class="w-full pl-9 pr-3 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
                </div>
            </div>

            <!-- Password -->
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <label for="password" class="block text-xs font-semibold text-slate-700">Password</label>
                </div>
                <div class="relative">
                    <i class="fa-solid fa-lock absolute left-3.5 top-3 text-slate-400 text-xs"></i>
                    <input type="password" id="password" name="password" required placeholder="Masukkan password Anda" class="w-full pl-9 pr-3 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
                </div>
            </div>

            <!-- Submit Button -->
            <div class="pt-2">
                <button type="submit" class="w-full py-3 px-4 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-sm shadow-md shadow-brand-600/25 transition">
                    Masuk Sekarang
                </button>
            </div>
        </form>

        <!-- Footer Switch to Register -->
        <div class="mt-6 pt-6 border-t border-slate-100 text-center text-xs text-slate-500 space-y-2">
            <div>
                Belum punya akun? 
                <a href="register.php" class="font-bold text-brand-600 hover:underline">Daftar sekarang</a>
            </div>
            <div>
                <a href="admin/login.php" class="text-slate-400 hover:text-slate-600 inline-flex items-center gap-1 text-[11px]">
                    <i class="fa-solid fa-lock text-[10px]"></i> Masuk sebagai Admin Toko
                </a>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

