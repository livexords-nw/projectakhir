<?php
session_start();
require_once '../helper/connection.php';
require_once '../helper/logger.php';

// **1. Validasi Login Pengguna**
if (!isset($_SESSION['login']['id'])) {
    header("Location: login.php"); // Arahkan ke halaman login jika belum login
    exit;
}

// Ambil data pengguna dari sesi
$user_id = $_SESSION['login']['id'];
$username = $_SESSION['login']['username'] ?? 'Pengguna Tidak Dikenal';

// **2. Validasi Keranjang Belanja**
if (empty($_SESSION['cart'])) {
    $_SESSION['info'] = [
        'status' => 'danger',
        'message' => 'Keranjang belanja Anda kosong!'
    ];
    header("Location: ../dashboard/user_dashboard.php"); // Arahkan kembali ke dashboard
    exit;
}

// **3. Mulai Transaksi Database**
$connection->begin_transaction();

try {
    // **4. Hitung Total Harga dari Keranjang**
    $total_harga = array_reduce($_SESSION['cart'], function ($total, $item) {
        return $total + ($item['harga'] * $item['jumlah']);
    }, 0);

    // Nomor meja diacak antara T1-T20
    $table_number = 'T' . rand(1, 20);

    // **5. Simpan Data Pemesanan**
    $queryPemesanan = "INSERT INTO pemesanan (nama_pemesan, total_harga, tanggal_pemesanan, status, table_number) 
                       VALUES (?, ?, NOW(), 'pending', ?)";
    $stmtPemesanan = $connection->prepare($queryPemesanan);

    if (!$stmtPemesanan) {
        throw new Exception("Gagal menyiapkan query pemesanan: " . $connection->error);
    }

    $stmtPemesanan->bind_param("sds", $username, $total_harga, $table_number);
    $stmtPemesanan->execute();

    // Dapatkan ID pemesanan yang baru dibuat
    $pemesanan_id = $stmtPemesanan->insert_id;

    // **6. Simpan Data Detail Pemesanan**
    $queryDetail = "INSERT INTO detail_pemesanan (id_pemesanan, id_produk, user_id, jumlah, subtotal) 
                    VALUES (?, ?, ?, ?, ?)";
    $stmtDetail = $connection->prepare($queryDetail);

    if (!$stmtDetail) {
        throw new Exception("Gagal menyiapkan query detail pemesanan: " . $connection->error);
    }

    // Masukkan setiap item dari keranjang ke detail pemesanan
    foreach ($_SESSION['cart'] as $item) {
        $subtotal = $item['harga'] * $item['jumlah'];
        $stmtDetail->bind_param("iiidd", $pemesanan_id, $item['id'], $user_id, $item['jumlah'], $subtotal);
        $stmtDetail->execute();
    }

    // **7. Commit Transaksi**
    $connection->commit();

    // Kosongkan keranjang setelah berhasil checkout
    unset($_SESSION['cart']);

    // Simpan nomor meja untuk digunakan di halaman konfirmasi
    $_SESSION['table_number'] = $table_number;

    // Simpan pesan sukses ke sesi
    $_SESSION['info'] = [
        'status' => 'success',
        'message' => "Checkout berhasil! Pesanan Anda telah dibuat. Nomor meja Anda adalah $table_number"
    ];

    // Log keberhasilan
    write_log("User '$username' berhasil checkout dengan ID pemesanan: $pemesanan_id dan nomor meja: $table_number", 'INFO');

    // Arahkan ke halaman konfirmasi pesanan
    header("Location: ./order_confirmation.php");
    exit;
} catch (Exception $e) {
    // **8. Rollback jika terjadi error**
    $connection->rollback();

    // Log kesalahan
    write_log("Checkout gagal untuk user '$username': " . $e->getMessage(), 'ERROR');

    // Simpan pesan error ke sesi
    $_SESSION['info'] = [
        'status' => 'danger',
        'message' => 'Terjadi kesalahan saat checkout: ' . $e->getMessage()
    ];

    // Arahkan kembali ke dashboard
    header("Location: ../dashboard/user_dashboard.php");
    exit;
}
