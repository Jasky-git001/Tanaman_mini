        </main>
        
        <!-- Admin Footer Note -->
        <footer class="bg-white border-t border-slate-200 px-6 py-3 text-xs text-slate-500 flex flex-col sm:flex-row items-center justify-between">
            <span>&copy; <?= date('Y') ?> Tanaman Hias Mini - E-Commerce Management System</span>
            <span class="text-slate-400">Versi 1.0</span>
        </footer>

    </div>

    <!-- Admin JS Interactions -->
    <script>
        const adminMobileBtn = document.getElementById('adminMobileBtn');
        const adminMobileMenu = document.getElementById('adminMobileMenu');
        if (adminMobileBtn && adminMobileMenu) {
            adminMobileBtn.addEventListener('click', () => {
                adminMobileMenu.classList.toggle('hidden');
            });
        }
    </script>
</body>
</html>

