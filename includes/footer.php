    <?php
// Mencegah error jika $base_path belum di-set
$base_path = $base_path ?? ''; 
?>
    
    </main>

    <!-- Footer Section -->
    <footer class="bg-slate-900 text-slate-300 pt-16 pb-8 border-t border-slate-800 mt-20" id="footer">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-10 mb-12">
                
                <!-- Brand & About -->
                <div>
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-9 h-9 rounded-xl bg-brand-500 flex items-center justify-center text-white">
                            <i class="fa-solid fa-seedling"></i>
                        </div>
                        <span class="text-xl font-extrabold text-white tracking-tight">Tanaman Hias <span class="text-brand-400">Mini</span></span>
                    </div>
                    <p class="text-slate-400 text-sm leading-relaxed mb-5">
                        Toko online penyedia aneka tanaman hias mini berkualitas premium untuk mempercantik meja kerja, kamar tidur, rak hias, dan ruangan interior Anda.
                    </p>
                    <div class="flex items-center space-x-3 text-slate-400">
                        <a href="#" class="w-9 h-9 rounded-lg bg-slate-800 hover:bg-brand-600 hover:text-white flex items-center justify-center transition"><i class="fa-brands fa-instagram"></i></a>
                        <a href="#" class="w-9 h-9 rounded-lg bg-slate-800 hover:bg-brand-600 hover:text-white flex items-center justify-center transition"><i class="fa-brands fa-whatsapp"></i></a>
                        <a href="#" class="w-9 h-9 rounded-lg bg-slate-800 hover:bg-brand-600 hover:text-white flex items-center justify-center transition"><i class="fa-brands fa-tiktok"></i></a>
                        <a href="#" class="w-9 h-9 rounded-lg bg-slate-800 hover:bg-brand-600 hover:text-white flex items-center justify-center transition"><i class="fa-brands fa-facebook-f"></i></a>
                    </div>
                </div>

                <!-- Navigation Links -->
                <div>
                    <h4 class="text-white font-semibold text-base mb-4 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-brand-500"></span> Navigasi
                    </h4>
                    <ul class="space-y-2.5 text-sm text-slate-400">
                        <li><a href="<?= $base_path ?>index.php" class="hover:text-white transition">Beranda</a></li>
                        <li><a href="<?= $base_path ?>products.php" class="hover:text-white transition">Katalog Tanaman</a></li>
                        <li><a href="<?= $base_path ?>cart.php" class="hover:text-white transition">Keranjang Belanja</a></li>
                        <li><a href="<?= $base_path ?>orders.php" class="hover:text-white transition">Lacak Pesanan</a></li>
                        <li><a href="<?= $base_path ?>login.php" class="hover:text-white transition">Login Akun</a></li>
                    </ul>
                </div>

                <!-- Kategori Pilihan -->
                <div>
                    <h4 class="text-white font-semibold text-base mb-4 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-brand-500"></span> Kategori Populer
                    </h4>
                    <ul class="space-y-2.5 text-sm text-slate-400">
                        <li><a href="<?= $base_path ?>products.php?category=sukulen" class="hover:text-white transition">Sukulen Estetik</a></li>
                        <li><a href="<?= $base_path ?>products.php?category=kaktus" class="hover:text-white transition">Kaktus Mini Meja</a></li>
                        <li><a href="<?= $base_path ?>products.php?category=tanaman-indoor" class="hover:text-white transition">Tanaman Pembersih Udara</a></li>
                        <li><a href="<?= $base_path ?>products.php?category=tanaman-meja" class="hover:text-white transition">Tanaman Meja Minimalis</a></li>
                        <li><a href="<?= $base_path ?>products.php?category=tanaman-mini" class="hover:text-white transition">Tanaman Daun Mini Unik</a></li>
                    </ul>
                </div>

                <!-- Kontak & Alamat -->
                <div id="kontak">
                    <h4 class="text-white font-semibold text-base mb-4 flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-brand-500"></span> Kontak & Alamat
                    </h4>
                    <ul class="space-y-3 text-sm text-slate-400">
                        <li class="flex items-start gap-3">
                            <i class="fa-solid fa-location-dot text-brand-400 mt-1"></i>
                            <span>Jl. Raya Kampus Digital No. 88, Universitas Pamulang, Tangerang</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <i class="fa-solid fa-phone text-brand-400"></i>
                            <span>+62 812-3456-7890</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <i class="fa-solid fa-envelope text-brand-400"></i>
                            <span>halo@tanamanmini.local</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <i class="fa-regular fa-clock text-brand-400"></i>
                            <span>Senin - Sabtu: 08.00 - 18.00 WIB</span>
                        </li>
                    </ul>
                </div>

            </div>

            <!-- Bottom Copyright & Academic Meta -->
            <div class="border-t border-slate-800 pt-8 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 gap-4">
                <p>&copy; <?= date('Y') ?> <strong>Tanaman Hias Mini</strong>.</p>
                <div class="flex items-center gap-4">
                    <a href="<?= $base_path ?>admin/login.php" class="text-slate-400 hover:text-brand-400 transition flex items-center gap-1.5">
                        <i class="fa-solid fa-lock text-[10px]"></i>
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="<?= $base_path ?>assets/js/main.js"></script>
</body>
</html>

