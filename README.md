# Sistem Informasi Gudang Bantuan Bencana (SIGudang)

> **Mini Project 3 — Relational Database + Advanced PHP**  
> Sistem manajemen persediaan dan logistik kemanusiaan dengan transparansi publik, kontrol akses berbasis peran (RBAC), integritas transaksi atomik database, dan antarmuka modern responsif.

---

## 📋 Daftar Isi
1. [Fitur & 22 Halaman Sistem](#-fitur--22-halaman-sistem)
2. [Teknologi yang Digunakan](#-teknologi-yang-digunakan)
3. [Struktur Database](#-struktur-database)
4. [Akun Pengguna Default](#-akun-pengguna-default)
5. [Panduan Instalasi & Menjalankan](#-panduan-instalasi--menjalankan)
6. [Skenario Pengujian Penting](#-skenario-pengujian-penting)
   - [Pengujian Login & Otorisasi RBAC](#1-pengujian-login--otorisasi-rbac)
   - [Pengujian Transaksi Barang Masuk (Atomik)](#2-pengujian-transaksi-barang-masuk-atomik)
   - [Pengujian Transaksi Barang Keluar & Validasi Stok Tidak Cukup](#3-pengujian-transaksi-barang-keluar--validasi-stok-tidak-cukup)
   - [Pengujian Akses Terlarang Staff ke Halaman Admin (403 Forbidden)](#4-pengujian-akses-terlarang-staff-ke-halaman-admin-403-forbidden)

---

## 🚀 Fitur & 22 Halaman Sistem

Sistem ini telah mengimplementasikan seluruh **22 halaman spesifikasi**:

### Bagian A — Halaman Publik (Tanpa Login)
1. **Halaman 1: Beranda Publik (`index.php`)**  
   Hero section "Transparansi Bantuan, Tepat Sasaran", 4 kartu ringkasan logistik, peringatan stok bantuan menipis, dan riwayat aktivitas publik terbaru.
2. **Halaman 2: Dashboard Publik (`public/dashboard.php`)**  
   Ringkasan ketersediaan bantuan, grafik perbandingan barang masuk vs keluar (Chart.js), komposisi kategori donasi, dan distribusi bantuan.
3. **Halaman 3: Halaman Stok Bantuan (`public/stok.php`)**  
   Katalog stok logistik publik lengkap dengan filter kategori, filter status ketersediaan, pencarian nama/SKU, pengurutan, dan pagination.
4. **Halaman 4: Riwayat Bantuan Masuk Publik (`public/bantuan_masuk.php`)**  
   Daftar penerimaan donasi/bantuan masuk yang disetujui publik dengan filter rentang tanggal, kategori logistik, dan donatur.
5. **Halaman 5: Riwayat Penyaluran Publik (`public/penyaluran.php`)**  
   Daftar distribusi logistik ke posko/titik bencana dengan filter tanggal, kategori, dan lokasi tujuan.
6. **Halaman 6: Statistik Distribusi Publik (`public/statistik.php`)**  
   Analisis visual distribusi bantuan per wilayah tujuan bencana, proporsi kategori, dan tren waktu penyaluran.
7. **Halaman 7: Laporan Publik (`public/laporan_publik.php`)**  
   Laporan ringkas terbuka untuk masyarakat: laporan persediaan, donasi masuk, dan penyaluran berkala.
8. **Halaman 8: Tentang Sistem (`public/tentang.php`)**  
   Informasi profil sistem, komitmen transparansi & akuntabilitas, serta diagram alur tata kelola logistik kemanusiaan.

### Bagian B — Autentikasi
9. **Halaman 9: Login Admin & Staff (`auth/login.php`)**  
   Form autentikasi aman dengan proteksi CSRF, sanitasi input, `password_verify()`, regenerasi session ID (`session_regenerate_id(true)`), dan pengalihan otomatis berdasarkan role.

### Bagian C — Administrasi & Master Data (Admin Only)
10. **Halaman 10: Dashboard Admin (`admin/dashboard.php`)**  
    Pusat kendali administrator dengan 6 metrik agregat SQL (total jenis, total stok, estimasi nilai persediaan, stok menipis, total masuk, total keluar), grafik mutasi bulanan, dan tabel stok kritis.
11. **Halaman 11: Manajemen Produk (`admin/produk.php`)**  
    CRUD lengkap data barang logistik (SKU unik, nama, kategori, harga estimasi, stok minimum, satuan, toggle visibilitas publik). Terproteksi dari perubahan stok manual langsung tanpa transaksi.
12. **Halaman 12: Manajemen Supplier & Donatur (`admin/supplier.php`)**  
    CRUD data supplier/lembaga donor kemanusiaan dengan validasi integritas relasi referensial sebelum penghapusan.
13. **Halaman 13: Relasi Produk-Supplier (`admin/produk_supplier.php`)**  
    Manajemen relasi many-to-many (`product_suppliers`) dengan penetapan harga per supplier dan pencegahan duplikasi pasangan foreign key.
14. **Halaman Manajemen Kategori (`admin/kategori.php`)**  
    CRUD kategori logistik bantuan dengan proteksi relasi produk aktif.

### Bagian D — Transaksi Gudang (Admin & Staff)
15. **Halaman 14: Transaksi Barang Masuk (`admin/barang_masuk.php`)**  
    Pencatatan penerimaan bantuan dengan transaksi atomik PDO (`beginTransaction`, `commit`, `rollBack`), penambahan stok otomatis, dan pencatatan histori mutasi `IN`. Menggunakan pola Post-Redirect-Get (PRG) untuk mencegah submit ganda.
16. **Halaman 15: Transaksi Barang Keluar (`admin/barang_keluar.php`)**  
    Pencatatan penyaluran bantuan ke lokasi bencana dengan mekanisme penguncian baris `SELECT ... FOR UPDATE`, verifikasi kecukupan stok, pengurangan stok otomatis, dan histori mutasi `OUT`. Mencegah terjadinya stok negatif.
17. **Halaman 16: Riwayat Mutasi Stok (`admin/mutasi_stok.php`)**  
    Audit trail seluruh mutasi logistik dengan filter multi-parameter (tipe IN/OUT, rentang tanggal, produk, kata kunci), sorting dinamis, dan pagination.

### Bagian E — Staff Operasional
18. **Halaman 17: Dashboard Staff (`staff/dashboard.php`)**  
    Dashboard terfokus pada alur kerja operasional lapangan: pencarian ketersediaan barang secara instan, peringatan stok menipis, akses cepat pencatatan barang masuk & keluar, tanpa menampilkan menu master data atau manajemen user.

### Bagian F — Laporan & Manajemen Pengguna
19. **Halaman 18: Laporan Admin (`admin/laporan.php`)**  
    8 jenis laporan komprehensif menggunakan query agregat SQL (`JOIN`, `GROUP BY`, `SUM`, `COUNT`): Laporan Stok Terkini, Nilai Persediaan, Barang Masuk, Barang Keluar, Mutasi Stok, Ringkasan Kategori, Distribusi per Tujuan, dan Aktivitas Operator. Dilengkapi fitur cetak (*print-friendly*).
20. **Halaman 19: Manajemen Pengguna (`admin/pengguna.php` / `admin/users.php`)**  
    Kelola akun operator: tambah pengguna, ganti role (Admin/Staff), reset password aman, toggle status aktif/nonaktif, serta proteksi penghapusan akun yang memiliki riwayat transaksi mutasi.
21. **Halaman 20: Profil Pengguna (`auth/profil.php`)**  
    Lihat data akun, pembaruan nama/email, dan ganti password dengan verifikasi password lama dan enkripsi `password_hash(PASSWORD_BCRYPT)`.

### Bagian G — Penanganan Kesalahan & Sesi
22. **Halaman 21: Error Handling (`views/errors/404.php` & `views/errors/403.php`)**  
    Tampilan respons standar HTTP 404 (Not Found) dan HTTP 403 (Forbidden) dengan gaya visual selaras.
23. **Halaman 22: Logout Aman (`auth/logout.php`)**  
    Pembersihan variabel `$_SESSION`, penghapusan cookie sesi, destruksi session (`session_destroy()`), dan proteksi halaman internal dari akses ulang via tombol back browser.

---

## 🛠 Teknologi yang Digunakan

- **Backend**: PHP 8.1+ (Native dengan struktur modular & OOP/prosedural terorganisasi)
- **Database**: MySQL 5.7+ / MariaDB 10.4+ melalui **PHP Data Objects (PDO)**
- **Keamanan**:
  - Prepared Statements pada 100% kueri database (Anti SQL Injection).
  - Enkripsi password menggunakan `password_hash()` algoritma BCRYPT (cost 12).
  - Proteksi Cross-Site Request Forgery (CSRF Token) pada setiap formulir POST.
  - Sanitasi & validasi input serta proteksi XSS menggunakan `htmlspecialchars()`.
  - Middleware otorisasi Role-Based Access Control (`requireAdmin()`, `requireAdminOrStaff()`).
  - Mekanisme transaksi database atomik & row-level locking (`SELECT ... FOR UPDATE`).
- **Frontend**:
  - HTML5 & CSS3 Vanilla kustom (Navy `#1e3a8a`, Amber `#f59e0b`, Emerald `#059669`).
  - Bootstrap 5.3 (Grid responsif & komponen modal/dropdown).
  - Bootstrap Icons 1.11.
  - Chart.js 4.4 (Visualisasi statistik interaktif).

---

## 🗄 Struktur Database

Database `gudang_bencana` dirancang dengan integritas referensial dan foreign key lengkap:

1. **`users`**: Akun pengguna sistem (`id`, `username`, `password_hash`, `role`, `full_name`, `email`, `is_active`, `created_at`).
2. **`categories`**: Kategori logistik (`id`, `name`, `description`, `created_at`).
3. **`products`**: Data barang bantuan (`id`, `category_id`, `sku`, `name`, `unit`, `stock`, `minimum_stock`, `unit_price`, `is_public`, `created_at`).
4. **`suppliers`**: Pemasok / Lembaga donor (`id`, `name`, `type`, `contact_person`, `phone`, `email`, `address`, `is_public`, `created_at`).
5. **`product_suppliers`**: Tabel relasi many-to-many (`product_id`, `supplier_id`, `supplier_price`, `lead_time_days`, `created_at`) dengan composite primary key `(product_id, supplier_id)`.
6. **`distribution_targets`**: Lokasi posko/titik bencana penerima bantuan (`id`, `name`, `location_type`, `region`, `pic_name`, `phone`, `status`, `created_at`).
7. **`stock_movements`**: Riwayat transaksi mutasi logistik (`id`, `product_id`, `supplier_id`, `distribution_target_id`, `user_id`, `type`, `quantity`, `date`, `reference`, `notes`, `is_public`, `created_at`).

---

## 👤 Akun Pengguna Default

Data seed otomatis menyertakan akun siap pakai untuk demonstrasi:

| Role | Username | Password | Deskripsi |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin` | `password` | Akses penuh ke seluruh menu Master Data, Pengguna, Laporan, & Transaksi |
| **Administrator** | `admin2` | `password` | Akun admin pendamping |
| **Staff Operasional** | `staff` | `password` | Akses ke Dashboard Operasional & Transaksi Barang Masuk/Keluar/Mutasi |
| **Staff Operasional** | `staff2` | `password` | Akun staff lapangan kedua |

---

## ⚙️ Panduan Instalasi & Menjalankan

### Melalui Laragon (Rekomendasi)
1. Buka aplikasi **Laragon** dan klik tombol **Start All** (Apache & MySQL).
2. Pastikan folder proyek berada di direktori `D:\laragon\www\RPL-Pertemuan4`.
3. Buka **HeidiSQL** atau **phpMyAdmin** (`http://localhost/phpmyadmin`).
4. Buat database baru bernama `gudang_bencana`:
   ```sql
   CREATE DATABASE IF NOT EXISTS `gudang_bencana` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
5. Cukup impor **1 file all-in-one**:
   - Impor berkas: `database/gudang_bencana.sql` (Otomatis membuat database `gudang_bencana`, seluruh 7 tabel, foreign key, dan data seed lengkap).
   - *(Atau alternatif 2 tahap: jalankan `database/schema.sql` lalu `database/seed.sql`)*.
6. Buka peramban (browser) dan akses:
   - **Halaman Publik**: `http://localhost/RPL-Pertemuan4/index.php`
   - **Halaman Login**: `http://localhost/RPL-Pertemuan4/auth/login.php`

---

## 🧪 Skenario Pengujian Penting

### 1. Pengujian Login & Otorisasi RBAC
- Masuk menggunakan akun **Admin** (`admin` / `password`):
  - Sistem mengarahkan langsung ke `/admin/dashboard.php`.
  - Seluruh menu pada sidebar (Utama, Transaksi, Master Data, Laporan, Pengguna) tampil dan dapat diakses.
- Masuk menggunakan akun **Staff** (`staff` / `password`):
  - Sistem mengarahkan ke `/staff/dashboard.php`.
  - Sidebar hanya menampilkan menu Utama (Dashboard Staff) dan Transaksi (Barang Masuk, Barang Keluar, Riwayat Mutasi). Menu Master Data, Laporan, dan Pengguna disembunyikan.

### 2. Pengujian Transaksi Barang Masuk (Atomik)
1. Buka menu **Barang Masuk** (`/admin/barang_masuk.php`).
2. Pilih barang (misal: Beras Premium 5kg, stok awal = 100).
3. Masukkan jumlah penerimaan: `50`, pilih donatur, isi tanggal & referensi surat jalan.
4. Klik **Simpan Transaksi**.
5. Sistem mengeksekusi satu blok transaksi database atomik:
   - `UPDATE products SET stock = stock + 50 WHERE id = ?`
   - `INSERT INTO stock_movements (..., type='IN', quantity=50, ...)`
   - `COMMIT`
6. Stok barang bertambah menjadi `150` dan histori langsung tercatat di Riwayat Mutasi.

### 3. Pengujian Transaksi Barang Keluar & Validasi Stok Tidak Cukup
1. Buka menu **Barang Keluar** (`/admin/barang_keluar.php`).
2. Pilih barang dengan stok terbatas (misal: Obat Antibiotik dengan sisa stok `10`).
3. Masukkan jumlah penyaluran yang melebihi stok: `50`.
4. Pilih lokasi posko bencana dan klik **Simpan Transaksi**.
5. **Hasil yang Terjadi**:
   - Sistem melakukan verifikasi dengan *row-level locking* (`SELECT stock FROM products ... FOR UPDATE`).
   - Karena `quantity (50) > current_stock (10)`, sistem **menolak transaksi** dan memicu `rollBack()`.
   - Muncul notifikasi kesalahan berwarna merah: *"Stok tidak mencukupi! Stok saat ini: 10, jumlah diminta: 50."*
   - Stok barang di database **tetap aman (tidak berubah)** dan **tidak ada mutasi palsu** yang tercatat.

### 4. Pengujian Akses Terlarang Staff ke Halaman Admin (403 Forbidden)
1. Login menggunakan akun Staff (`staff` / `password`).
2. Coba akses langsung tautan khusus admin melalui address bar browser:
   - Buka URL: `http://localhost/RPL-Pertemuan4/admin/pengguna.php` atau `http://localhost/RPL-Pertemuan4/admin/produk.php`.
3. **Hasil yang Terjadi**:
   - Middleware `requireAdmin()` mendeteksi role sesi pengguna adalah `staff`.
   - Server mengembalikan status code `HTTP/1.1 403 Forbidden`.
   - Pengguna diarahkan ke tampilan halaman **Error 403: Akses Terlarang** (`views/errors/403.php`) dengan pesan penolakan dan tombol kembali ke dashboard staff.

---

## 📁 Struktur Berkas Proyek

```
RPL-Pertemuan4/
├── 404.php                     # Handler Error 404 Root
├── index.php                   # Halaman 1: Beranda Publik
├── README.md                   # Dokumentasi Lengkap Proyek
├── admin/                      # Halaman Khusus Administrator & Transaksi
│   ├── barang_keluar.php       # Halaman 15: Transaksi Pengeluaran Bantuan
│   ├── barang_masuk.php        # Halaman 14: Transaksi Penerimaan Bantuan
│   ├── dashboard.php           # Halaman 10: Dashboard Utama Administrator
│   ├── kategori.php            # Manajemen Kategori Logistik
│   ├── laporan.php             # Halaman 18: Laporan Agregat SQL & Cetak
│   ├── mutasi_stok.php         # Halaman 16: Audit Trail Mutasi Logistik
│   ├── pengguna.php            # Halaman 19: Manajemen Pengguna & Reset Password
│   ├── produk.php              # Halaman 11: Master Data Produk Logistik
│   ├── produk_supplier.php     # Halaman 13: Relasi Many-to-Many Produk & Supplier
│   └── supplier.php            # Halaman 12: Master Data Supplier & Donatur
├── assets/                     # Aset Tampilan
│   ├── css/style.css           # Styling Tema Kemanusiaan & Desain Responsif
│   └── js/main.js              # Script Pembantu Frontend & Chart
├── auth/                       # Modul Autentikasi & Akun
│   ├── login.php               # Halaman 9: Login Multi-Role
│   ├── logout.php              # Halaman 22: Logout Aman & Hapus Sesi
│   └── profil.php              # Halaman 20: Profil & Ganti Password
├── config/                     # Konfigurasi Sistem
│   ├── app.php                 # Konstanta Aplikasi & URL
│   └── database.php            # Koneksi PDO Database MySQL
├── database/                   # Skrip Database
│   ├── schema.sql              # Struktur Tabel & Foreign Key
│   └── seed.sql                # Data Awal Lengkap (Seeders)
├── helpers/                    # Fungsi Pembantu Global
│   ├── auth.php                # Helper Sesi, Role, & CSRF Token
│   └── functions.php           # Helper Sanitasi, Format Rupiah/Tanggal, & Flash Message
├── public/                     # Halaman Terbuka untuk Publik
│   ├── bantuan_masuk.php       # Halaman 4: Riwayat Penerimaan Donasi Publik
│   ├── dashboard.php           # Halaman 2: Dashboard Statistik Publik
│   ├── laporan_publik.php      # Halaman 7: Publikasi Laporan Bantuan
│   ├── penyaluran.php          # Halaman 5: Riwayat Penyaluran Bencana Publik
│   ├── statistik.php           # Halaman 6: Grafik Distribusi & Wilayah Bencana
│   ├── stok.php                # Halaman 3: Katalog Ketersediaan Stok Logistik
│   └── tentang.php             # Halaman 8: Profil & Alur Sistem Bantuan
├── staff/                      # Halaman Khusus Staff Operasional
│   └── dashboard.php           # Halaman 17: Dashboard Operasional Lapangan
└── views/                      # Komponen Template & Halaman Error
    ├── errors/
    │   ├── 403.php             # Halaman 21: Tampilan Error 403 Forbidden
    │   └── 404.php             # Halaman 21: Tampilan Error 404 Not Found
    └── partials/
        ├── footer_admin.php    # Footer Internal Dashboard
        ├── footer_public.php   # Footer Portal Publik
        ├── header_admin.php    # Header & Sidebar Navigasi Internal
        └── header_public.php   # Header & Navbar Portal Publik
```

---
*Pengembangan Mini Project 3 — Sistem Informasi Gudang Bantuan Bencana &copy; 2026*
