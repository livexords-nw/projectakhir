<?php
session_start();
require_once '../helper/connection.php';
require_once '../helper/logger.php';

if (!isset($_SESSION['login']['id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['login']['id']; // Ambil user ID dari session
$username = $_SESSION['login']['username'] ?? 'Unknown User';

if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    $_SESSION['info'] = [
        'status' => 'danger',
        'message' => 'Keranjang belanja Anda kosong!'
    ];
    header("Location: ./user_dashboard.php");
    exit;
}

$connection->begin_transaction();

try {
    // Hitung total harga
    $total_harga = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total_harga += $item['harga'] * $item['jumlah'];
    }

    // Menetapkan nomor meja secara acak atau menggunakan aturan tertentu
    // Misalnya kita menggunakan format 'T1', 'T2', dsb. 
    // Atau bisa mengambil nomor meja dari tabel lain jika ada
    $table_number = 'T' . rand(1, 20); // Menetapkan nomor meja secara acak (contoh: T1, T2, dst.)

    // Masukkan data ke tabel pemesanan
    $queryPemesanan = "INSERT INTO pemesanan (nama_pemesan, total_harga, tanggal_pemesanan, status, table_number) 
                       VALUES (?, ?, NOW(), 'pending', ?)";
    $stmtPemesanan = $connection->prepare($queryPemesanan);

    if (!$stmtPemesanan) {
        throw new Exception("Prepare error (queryPemesanan): " . $connection->error);
    }

    $_SESSION['table_number'] = $table_number;  // Di file checkout.php

    $stmtPemesanan->bind_param("sds", $username, $total_harga, $table_number);
    $stmtPemesanan->execute();
    $pemesanan_id = $stmtPemesanan->insert_id;

    // Masukkan data ke tabel detail_pemesanan
    $queryDetail = "INSERT INTO detail_pemesanan (id_pemesanan, id_produk, user_id, jumlah, subtotal) 
                    VALUES (?, ?, ?, ?, ?)";
    $stmtDetail = $connection->prepare($queryDetail);

    if (!$stmtDetail) {
        throw new Exception("Prepare error (queryDetail): " . $connection->error);
    }

    foreach ($_SESSION['cart'] as $item) {
        $subtotal = $item['harga'] * $item['jumlah'];

        $stmtDetail->bind_param("iiidd", $pemesanan_id, $item['id'], $user_id, $item['jumlah'], $subtotal);
        $stmtDetail->execute();
    }

    // Hapus atau jangan jalankan query Update Stok produk jika stok tidak ingin berkurang
    // Hapus bagian berikut jika stok tidak perlu diubah
    // $queryUpdateStock = "UPDATE produk SET stock = stock - ?, jumlah_terjual = jumlah_terjual + ? WHERE id = ?";
    // $stmtUpdateStock = $connection->prepare($queryUpdateStock);

    // if (!$stmtUpdateStock) {
    //     throw new Exception("Prepare error (queryUpdateStock): " . $connection->error);
    // }

    // foreach ($_SESSION['cart'] as $item) {
    //     $stmtUpdateStock->bind_param("iii", $item['jumlah'], $item['jumlah'], $item['id']);
    //     $stmtUpdateStock->execute();
    // }

    $connection->commit();

    unset($_SESSION['cart']);

    $_SESSION['info'] = [
        'status' => 'success',
        'message' => 'Checkout berhasil! Pesanan Anda telah dibuat. Nomor meja Anda adalah ' . $table_number
    ];

    write_log("User '$username' berhasil melakukan checkout dengan ID pemesanan: $pemesanan_id dan nomor meja: $table_number", 'INFO');

    header("Location: ./order_confirmation.php");
    exit;

} catch (Exception $e) {
    $connection->rollback();

    write_log("Checkout gagal untuk user '$username': " . $e->getMessage(), 'ERROR');

    $_SESSION['info'] = [
        'status' => 'danger',
        'message' => 'Terjadi kesalahan saat checkout: ' . $e->getMessage()
    ];

    header("Location: ../dashboard/user_dashboard.php");
    exit;
}
?>
