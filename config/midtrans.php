<?php
/**
 * Konfigurasi Midtrans Sandbox Payment Gateway
 * Proyek: Tanaman Hias Mini - Digital Entrepreneurship
 */

// Kredensial Midtrans Sandbox (Testing Keys untuk Tugas Kuliah)
define('MIDTRANS_SERVER_KEY', 'SB-Mid-server-YOUR_SANDBOX_SERVER_KEY');
define('MIDTRANS_CLIENT_KEY', 'SB-Mid-client-YOUR_SANDBOX_CLIENT_KEY');
define('MIDTRANS_IS_PRODUCTION', false);
define('MIDTRANS_IS_SANITIZED', true);
define('MIDTRANS_IS_3DS', true);

// Mode Simulasi Interaktif (true = simulasi langsung tanpa perlu API key sungguhan, sangat cocok untuk demo presentasi dosen)
define('MIDTRANS_SIMULATION_MODE', true);

