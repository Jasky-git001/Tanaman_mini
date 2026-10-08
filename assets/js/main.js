/**
 * JavaScript Interaksi - Tanaman Hias Mini
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Mobile Menu Toggle
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const mobileMenu = document.getElementById('mobileMenu');
    if (mobileMenuBtn && mobileMenu) {
        mobileMenuBtn.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });
    }

    // 2. Auto-dismiss Flash Notification
    const flashMessage = document.getElementById('flashNotification');
    if (flashMessage) {
        setTimeout(() => {
            flashMessage.style.transition = 'opacity 0.5s ease';
            flashMessage.style.opacity = '0';
            setTimeout(() => flashMessage.remove(), 500);
        }, 4000);
    }

    // 3. User Dropdown Menu Toggle (Desktop)
    const userMenuBtn = document.getElementById('userMenuBtn');
    const userDropdown = document.getElementById('userDropdown');
    if (userMenuBtn && userDropdown) {
        userMenuBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            userDropdown.classList.toggle('hidden');
        });
        document.addEventListener('click', () => {
            if (!userDropdown.classList.contains('hidden')) {
                userDropdown.classList.add('hidden');
            }
        });
    }
});

/**
 * Konfirmasi Aksi Hapus / Tindakan Penting
 */
function confirmAction(message, redirectUrl) {
    if (confirm(message || 'Apakah Anda yakin ingin melanjutkan tindakan ini?')) {
        window.location.href = redirectUrl;
    }
}

