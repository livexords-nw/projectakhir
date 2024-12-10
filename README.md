# **Project Akhir - Sistem CRUD Produk**

Aplikasi berbasis web sederhana menggunakan PHP native dengan CSS Bootstrap dan JavaScript. Proyek ini mendukung manajemen produk, mencakup CRUD, serta fitur keamanan dan tampilan responsif.

## **Fitur Utama**

1. **Manajemen Produk**:
   - Tambah, lihat, edit, dan hapus produk.
2. **Dashboard**:
   - Tampilan khusus admin dan user.
   - Log aktivitas untuk audit.
3. **Keamanan**:
   - Sistem login berbasis file `auth.php`.
4. **UI/UX**:
   - Responsif dengan Bootstrap.

## **Versi Terbaru (1.3.3)**

- Penambahan sistem manajemen pesanan
- Membenahi beberapa bug
- Menambahi beberapa logger agar lebih jelas

## **Struktur Folder**

```plaintext
projectakhir/
├── assets/            # File statis (CSS, JS, gambar)
├── dashboard/         # Halaman dashboard admin dan user
├── helper/            # Fungsi pembantu (auth, koneksi, logger)
├── includes/          # Layout (header, sidenav, footer)
├── logs/              # Log aktivitas
├── produk/            # Logika CRUD produk
└── README.md          # Dokumentasi proyek
```

## **Cara Menggunakan**

1. Clone repositori:
   ```bash
   git clone https://github.com/livexords-nw/projectakhir.git
   ```
2. Import file `database.sql` untuk membuat tabel.
3. Konfigurasi `helper/connection.php` sesuai database.
4. Jalankan:
   ```bash
   php -S localhost:8000
   ```
5. Akses: `http://localhost:8000`.

## **Kontribusi**

Buka _issue_ atau buat _pull request_ untuk saran atau perbaikan.

---
