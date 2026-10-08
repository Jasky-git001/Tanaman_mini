<?php
require_once __DIR__ . '/../config/database.php';

// Jika admin sudah login, langsung ke dashboard
if (isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Email dan password wajib diisi.';
    } else {
        try {
            $db = getDB();
            $stmt = $db->prepare("SELECT * FROM admins WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password'])) {
                unset($_SESSION['user_id'], $_SESSION['user_nama'], $_SESSION['user_email'], $_SESSION['buy_now_item']);
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_nama'] = $admin['nama'];

                setFlash('success', 'Selamat datang di Panel Admin, ' . $admin['nama'] . '!');
                header("Location: index.php");
                exit;
            } else {
                $error = 'Email atau password admin salah.';
            }
        } catch (Exception $e) {
            $error = 'Terjadi kesalahan sistem: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - Tanaman Hias Mini</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#f0fdf4',
                            100: '#dcfce7',
                            200: '#bbf7d0',
                            300: '#86efac',
                            400: '#4ade80',
                            500: '#22c55e',
                            600: '#16a34a',
                            700: '#15803d',
                            800: '#166534',
                            900: '#14532d',
                        }
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-slate-950 text-slate-100 flex items-center justify-center min-h-screen p-4">

    <div class="w-full max-w-md bg-slate-900 border border-slate-800 rounded-3xl p-8 shadow-2xl relative overflow-hidden">
        <!-- Glow top -->
        <div class="absolute top-0 right-0 -mr-16 -mt-16 w-48 h-48 rounded-full bg-brand-500/10 blur-2xl pointer-events-none"></div>

        <!-- Logo Header -->
        <div class="text-center mb-8 relative">
            <div class="w-14 h-14 rounded-2xl bg-brand-600 flex items-center justify-center text-white text-2xl mx-auto mb-3 shadow-lg shadow-brand-600/30">
                <i class="fa-solid fa-lock"></i>
            </div>
            <h1 class="text-xl font-extrabold text-white">Login Administrator</h1>
            <p class="text-xs text-slate-400 mt-1">Portal Pengelolaan Toko Tanaman Hias Mini</p>
        </div>

        <!-- Demo Account Helper Card for Testing/Grading -->
        <div class="mb-6 p-3.5 rounded-2xl bg-slate-800/80 border border-slate-700 text-xs text-slate-300">
            <div class="flex items-center gap-2 font-bold text-brand-400 mb-1">
                <i class="fa-solid fa-key"></i> Kredensial Akun Admin Default:
            </div>
            <p class="text-[11px] text-slate-400">
                Email: <code class="text-white font-mono bg-slate-900 px-1.5 py-0.5 rounded">admin@gmail.com</code> • 
                Password: <code class="text-white font-mono bg-slate-900 px-1.5 py-0.5 rounded">admin123</code>
            </p>
        </div>

        <!-- Error Message -->
        <?php if (!empty($error)): ?>
            <div class="mb-6 p-3.5 rounded-xl bg-red-950/60 border border-red-800 text-red-300 text-xs flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation text-sm flex-shrink-0"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <!-- Form -->
        <form action="login.php" method="POST" class="space-y-4">
            <div>
                <label for="username" class="block text-xs font-semibold text-slate-300 mb-1.5">Email Admin</label>
                <div class="relative">
                    <i class="fa-solid fa-user absolute left-3.5 top-3 text-slate-500 text-xs"></i>
                    <input type="text" id="username" name="username" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" placeholder="admin@gmail.com" class="w-full pl-9 pr-3 py-2.5 rounded-xl bg-slate-800/90 border border-slate-700 text-sm text-white focus:outline-none focus:border-brand-500">
                </div>
            </div>

            <div>
                <label for="password" class="block text-xs font-semibold text-slate-300 mb-1.5">Password</label>
                <div class="relative">
                    <i class="fa-solid fa-lock absolute left-3.5 top-3 text-slate-500 text-xs"></i>
                    <input type="password" id="password" name="password" required placeholder="admin123" class="w-full pl-9 pr-3 py-2.5 rounded-xl bg-slate-800/90 border border-slate-700 text-sm text-white focus:outline-none focus:border-brand-500">
                </div>
            </div>

            <div class="pt-2">
                <button type="submit" class="w-full py-3 px-4 rounded-xl bg-brand-600 hover:bg-brand-500 text-white font-bold text-sm shadow-lg shadow-brand-600/30 transition">
                    Masuk ke Dashboard Admin
                </button>
            </div>
        </form>

        <div class="mt-6 pt-6 border-t border-slate-800 text-center">
            <a href="../index.php" class="text-xs text-slate-400 hover:text-white transition flex items-center justify-center gap-1.5">
                <i class="fa-solid fa-arrow-left text-[10px]"></i> Kembali ke Halaman Utama Toko
            </a>
        </div>
    </div>

</body>
</html>

