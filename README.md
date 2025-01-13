# **Project Akhir - Sistem CRUD Produk**

Aplikasi berbasis web sederhana menggunakan PHP native dengan CSS Bootstrap dan JavaScript. Proyek ini mendukung manajemen produk, mencakup CRUD, serta fitur keamanan, tampilan responsif, dan sistem tambahan yang canggih.

## **Fitur Utama**

1. **Manajemen Produk**:
   - Tambah, lihat, edit, dan hapus produk.
2. **Dashboard**:
   - Tampilan khusus admin dan user.
   - Log aktivitas untuk audit.
3. **Keamanan**:
   - Sistem login berbasis file `auth.php`.
   - Sistem OTP (One-Time Password) untuk lapisan keamanan tambahan.
4. **Manajemen Akun**:
   - Fitur untuk mengelola akun pengguna (buat, edit, hapus).
5. **Sistem Pemesanan**:
   - Pemesanan produk dengan sistem yang diperbarui untuk lebih efisien.
6. **UI/UX**:
   - Responsif dengan Bootstrap.

## **Versi Terbaru (2.0.0)**

**Big Update**: Pembaruan besar dengan fitur-fitur canggih dan sistem yang diperbaiki:

- **Penambahan Sistem OTP**:
  - Mendukung OTP sebagai langkah verifikasi tambahan.
- **Peningkatan Sistem Pemesanan**:
  - Pemrosesan pesanan lebih cepat dan efisien.
  - Fitur edit pesanan lebih fleksibel.
- **Sistem Logger Diperbarui**:
  - Log aktivitas lebih rinci untuk analisis dan audit.
- **Penambahan Sistem Manajemen Akun**:
  - Fitur pengelolaan akun untuk admin dan user.

## **Struktur Folder**

```plaintext
projectakhir/
├── assets/            # File statis (CSS, JS, gambar)
├── dashboard/         # Halaman dashboard admin dan user
├── helper/            # Fungsi pembantu (auth, koneksi, logger, otp)
├── includes/          # Layout (header, sidenav, footer)
├── logs/              # Log aktivitas
├── produk/            # Logika CRUD produk
├── account/           # Sistem manajemen akun
└── README.md          # Dokumentasi proyek
```

````

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

Dengan pembaruan besar ini, aplikasi kini memiliki sistem yang lebih aman, efisien, dan kaya fitur untuk memenuhi kebutuhan pengguna yang lebih kompleks.
````
