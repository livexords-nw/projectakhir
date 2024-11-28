# **Project Akhir - Sistem CRUD Produk**
Proyek ini adalah aplikasi berbasis web sederhana yang menggunakan PHP native dengan integrasi CSS Bootstrap dan JavaScript. Aplikasi ini dibuat untuk mengelola data produk, mencakup fungsi Create, Read, Update, dan Delete (CRUD).

## **Fitur Utama**
1. **Manajemen Produk**:
   - Tambah produk baru.
   - Lihat daftar produk.
   - Edit informasi produk.
   - Hapus produk.

2. **Dashboard**:
   - Admin dan user memiliki tampilan dashboard masing-masing.
   - Log aktivitas disimpan untuk keperluan audit.

3. **Keamanan**:
   - Sistem login berbasis file `auth.php`.
   - Log aktivitas dicatat dalam file `app.log`.

4. **UI/UX**:
   - Desain antarmuka menggunakan Bootstrap untuk tampilan responsif.

---

## **Struktur Folder**
```plaintext
projectakhir/
├── assets/            # File statis (CSS, JS, gambar)
├── dashboard/         # Halaman dashboard admin dan user
├── helper/            # Fungsi pembantu (auth, connection, logger)
├── includes/          # Layout bagian (header, sidenav, footer)
├── logs/              # File log aplikasi
├── produk/            # Logika CRUD produk
└── README.md          # Dokumentasi proyek
```

---

## **Persyaratan Sistem**
- **Server**: PHP >= 7.4, Apache/Nginx.
- **Database**: MySQL/MariaDB.

---

## **Cara Menjalankan Proyek**
1. Clone repositori:
   ```bash
   git clone https://github.com/livexords-nw/projectakhir.git
   ```
2. Import database:
   - Gunakan file `database.sql` (jika ada) untuk membuat tabel yang diperlukan.
3. Konfigurasi database:
   - Edit file `helper/connection.php` sesuai kredensial database Anda.
4. Jalankan server lokal:
   ```bash
   php -S localhost:8000
   ```
5. Akses aplikasi di browser: `http://localhost:8000`.

---

## **Daftar File Utama**
1. **`index.php`**: Halaman utama aplikasi.
2. **`produk/`**:
   - `create.php`: Tambah produk.
   - `edit.php`: Edit produk.
   - `delete.php`: Hapus produk.
   - `index.php`: Daftar produk.
3. **`helper/connection.php`**: Koneksi ke database.
4. **`includes/_header.php`**: Template header.
5. **`dashboard/admin_dashboard.php`**: Dashboard admin.

---

## **Catatan Tambahan**
Jika Anda menemukan bug atau memiliki saran, silakan buat *issue* di [repositori GitHub ini](https://github.com/livexords-nw/projectakhir).

---