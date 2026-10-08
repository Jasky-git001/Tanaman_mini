# Website E-Commerce "Tanaman Hias Mini" 🌱
### Tugas Mata Kuliah: Digital Entrepreneurship

Website e-commerce modern, bersih, dan responsif bertema **Tanaman Hias Mini** yang dirancang khusus untuk memenuhi kriteria penilaian tugas mata kuliah **Digital Entrepreneurship**. Menggunakan teknologi **PHP (Native Terstruktur), MySQL, Tailwind CSS, JavaScript**, dan siap dijalankan menggunakan **XAMPP / Localhost**.

---

## 1. Teknologi yang Digunakan

* **Frontend:** HTML5, Tailwind CSS (via CDN), Vanilla CSS (`assets/css/style.css`), Font Awesome 6 Icons
* **Backend:** PHP 8.x (Native dengan arsitektur modular tanpa framework berat, mudah dipahami mahasiswa)
* **Database:** MySQL dengan driver **PDO (Prepared Statements)** untuk keamanan dari SQL Injection
* **Payment Gateway:** Midtrans Sandbox Simulator interaktif (QRIS, GoPay, BCA VA, Mandiri, BRI)
* **Pelaporan & Invoice:** Cetak/Download Invoice PDF terformat rapi dan Ekspor Rekap Penjualan ke Excel (`.xlsx` / `.xls`)
* **Visual Asset:** 9 Ilustrasi botani vektor SVG mandiri di `assets/images/products/` (langsung tampil cantik tanpa ketergantungan koneksi internet eksternal)

---

## 2. Struktur Folder Proyek

```
Plant/
├── assets/
│   ├── css/
│   │   └── style.css            # Custom styling & media print invoice PDF
│   ├── js/
│   │   └── main.js              # Interaksi navbar, toast auto-dismiss, konfirmasi
│   └── images/
│       └── products/            # 9 File gambar SVG tanaman mini & foto produk
├── config/
│   ├── database.php             # Koneksi PDO MySQL, format rupiah & helper flash
│   └── midtrans.php             # Konfigurasi Midtrans Sandbox Payment Gateway
├── database/
│   └── tanaman_mini.sql         # Skema database relasional lengkap + seed data awal
├── includes/
│   ├── header.php               # Navbar responsif (kondisi login/belum login)
│   ├── footer.php               # Footer toko, kontak, alamat, dan link admin
│   ├── admin_header.php         # Sidebar & topbar panel administrator
│   ├── admin_footer.php         # Footer admin & integrasi Chart.js
│   └── auth_check.php           # Middleware pembatas akses halaman User & Admin
├── admin/
│   ├── index.php                # Dashboard Admin: omset, modal, laba bersih, grafik
│   ├── login.php                # Halaman login khusus admin
│   ├── logout.php               # Logout admin
│   ├── products.php             # Daftar & kelola produk (pencarian & filter)
│   ├── product_add.php          # Tambah produk baru (upload foto, modal, harga jual)
│   ├── product_edit.php         # Edit data produk
│   ├── product_delete.php       # Hapus produk (proteksi transaksi)
│   ├── orders.php               # Daftar semua pesanan masuk & filter status
│   ├── order_detail.php         # Detail pesanan & form update status alur pesanan
│   ├── users.php                # Daftar customer, kontak WhatsApp, riwayat belanja
│   ├── reports.php              # Laporan penjualan per hari/minggu/bulan/tahun
│   └── export_excel.php         # Unduh laporan penjualan format spreadsheet Excel
├── index.php                    # Halaman utama (Hero, Kategori, Terlaris, Fitur)
├── products.php                 # Katalog lengkap dengan search, filter kategori & sort
├── product_detail.php           # Detail produk, panduan rawat, stok & tambah keranjang
├── login.php                    # Login pembeli (User)
├── register.php                 # Registrasi akun pembeli dengan password_hash
├── logout.php                   # Logout pembeli
├── profile.php                  # Pengaturan akun, ubah data pembeli & ganti password
├── cart.php                     # Halaman keranjang belanja (+ / - jumlah & subtotal)
├── cart_action.php              # Handler aksi keranjang belanja
├── checkout.php                 # Halaman checkout pesanan & pemilihan ongkir
├── payment.php                  # Pembayaran Midtrans Snap Sandbox simulator interaktif
├── order_success.php            # Halaman konfirmasi pembayaran sukses
├── orders.php                   # Riwayat pesanan & pelacakan status belanja
├── invoice.php                  # Cetak & Download Invoice resmi dalam format PDF
└── README.md                    # Dokumentasi lengkap proyek
```

---

## 3. Akun Pengujian Default (Demo)

Sistem sudah dilengkapi akun demo siap pakai:

### A. Akun Administrator (Admin Toko)
* **URL Login:** `http://localhost/Plant/admin/login.php`
* **Username:** `admin`
* **Password:** `admin123`

### B. Akun Pembeli (Customer / User)
* **URL Login:** `http://localhost/Plant/login.php`
* **Email:** `user@gmail.com`
* **Password:** `user123`
*(Atau Anda dapat mendaftar akun baru melalui menu **Daftar**).*

---

## 4. Panduan Menjalankan Proyek Menggunakan XAMPP

### Langkah 1: Pindahkan / Tempatkan Folder Proyek
Pastikan folder proyek ini berada di dalam direktori `htdocs` XAMPP Anda.
Contoh:
`C:\xampp\htdocs\Plant` atau `D:\xampp\htdocs\Plant`.

*(Jika folder berada di lokasi lain, Anda juga dapat menjalankan PHP built-in server dengan mengetikkan perintah berikut pada terminal di dalam folder proyek):*
```powershell
& "D:\xampp\php\php.exe" -S localhost:8000
```

### Langkah 2: Jalankan Apache dan MySQL
1. Buka aplikasi **XAMPP Control Panel**.
2. Klik tombol **Start** pada modul **Apache**.
3. Klik tombol **Start** pada modul **MySQL**.
4. Pastikan kedua modul berubah warna menjadi hijau.

### Langkah 3: Impor Database MySQL
1. Buka browser dan ketik alamat: `http://localhost/phpmyadmin`
2. Klik menu **Databases** (Basis data) pada tab atas.
3. Masukkan nama database: `tanaman_mini_db` lalu klik **Create**.
4. Klik database `tanaman_mini_db` yang baru saja dibuat di sidebar kiri.
5. Klik tab **Import** (Impor) di menu atas.
6. Klik tombol **Choose File** (Pilih File) dan pilih file:
   `database/tanaman_mini.sql`
7. Gulir ke bawah lalu klik tombol **Import** (Kirim / Go).
8. Pesan sukses akan muncul dan seluruh 8 tabel beserta data produk awal telah terisi otomatis!

### Langkah 4: Buka Website di Browser
* **Halaman Toko (Pengunjung/Pembeli):**
  `http://localhost/Plant/index.php`
* **Panel Administrator (Admin):**
  `http://localhost/Plant/admin/login.php`

---

## 5. Cara Pengujian Alur Sistem

### Skenario 1: Alur Pembeli (User)
1. Buka `index.php`. Pengunjung dapat melihat banner Hero, Kategori, Produk Terlaris, dan Keunggulan Toko.
2. Klik menu **Produk** untuk mencari tanaman (misal: "Monstera") atau filter kategori "Sukulen".
3. Klik salah satu produk untuk membuka **Detail Produk**.
4. Coba klik **Beli / Tambah ke Keranjang** saat belum login: sistem secara aman akan mengarahkan ke halaman login dengan notifikasi.
5. Masuk menggunakan akun pembeli (`user@gmail.com` / `user123`) atau klik **Daftar Akun**.
6. Setelah login, tambahkan produk ke keranjang. Buka menu **Keranjang**. Anda dapat menambah/mengurangi jumlah pot tanaman atau menghapusnya.
7. Klik **Lanjut ke Checkout**.
8. Periksa alamat pengiriman, pilih kurir (Reguler Rp 15.000 atau Express Rp 25.000), lalu klik **Bayar Sekarang (Sandbox)**.
9. Anda akan masuk ke halaman simulasi **Midtrans Snap Sandbox**. Pilih metode (misal: QRIS atau Virtual Account BCA) lalu klik tombol **Simulasikan Pembayaran Berhasil (1-Klik)**.
10. Status pesanan seketika menjadi **"Diproses"** dan status bayar menjadi **"Pembayaran Berhasil"**, stok tanaman di database otomatis berkurang, dan keranjang belanja dikosongkan.
11. Buka **Invoice Pembayaran** dan klik tombol **Download Invoice (PDF) / Cetak** untuk mengunduh invoice dalam format PDF.

### Skenario 2: Alur Pengelola Toko (Admin)
1. Buka `admin/login.php` lalu masuk menggunakan akun `admin` / `admin123`.
2. Anda langsung diarahkan ke **Admin Dashboard**:
   * Lihat card metrik: Total Penjualan, Total Modal (HPP), dan **Laba Bersih**.
   * Lihat grafik interaktif tren penjualan 7 hari terakhir (Chart.js).
   * Lihat daftar produk paling laris (Best Seller).
3. Buka menu **Kelola Produk**:
   * Coba klik **Tambah Tanaman Baru**: isi nama, pilih kategori, isi harga modal (misal: 20000) dan harga jual (misal: 40000), masukkan stok, dan unggah foto.
   * Edit harga atau stok produk yang sudah ada.
4. Buka menu **Kelola Pesanan**:
   * Buka salah satu pesanan pembeli.
   * Ubah status dari `Diproses` ➔ `Dikemas` ➔ `Dikirim` ➔ `Selesai`.
5. Buka menu **Kelola User**:
   * Tinjau pembeli yang terdaftar, nomor WhatsApp, alamat, dan total belanja lunas mereka.
6. Buka menu **Laporan Penjualan**:
   * Filter periode berdasarkan Hari Ini, 7 Hari Terakhir, Bulan Ini, atau Tahun Ini.
   * Tinjau kalkulasi otomatis Omset, Modal, dan Laba Bersih.
   * Klik tombol hijau **Export Excel (.xlsx)**: file spreadsheet `.xls` akan otomatis terunduh lengkap dengan kolom No, Invoice, Tanggal, Pembeli, Produk, Modal, Penjualan, Laba, dan Status!

---

## 6. Fitur Keamanan

1. **Password Hashing:** Menggunakan algoritma BCRYPT standar industri (`password_hash` dan `password_verify`).
2. **Prepared Statements (PDO):** Menjamin seluruh query ke database kebal dari serangan SQL Injection.
3. **Session Guards:** User yang belum login tidak dapat melakukan checkout; User biasa dilarang mengakses halaman `/admin/*`; Admin diarahkan ke dashboard khusus.
4. **Input Sanitization:** Fungsi `sanitize()` diterapkan pada semua input form pengguna untuk mencegah serangan XSS.
5. **Stock Validation:** Pengecekan sisa stok sebelum produk dimasukkan ke keranjang dan sebelum checkout diselesaikan.

---

*Proyek dibuat untuk memenuhi tugas mata kuliah **Digital Entrepreneurship**.*

