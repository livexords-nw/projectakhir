<?php
session_start();
require_once '../helper/connection.php';
require_once '../helper/logger.php';

// Cek apakah user sudah login
if (!isset($_SESSION['login']['id'])) {
    // Jika tidak, arahkan ke halaman login
    header("Location: login.php");
    exit;
}

// Ambil ID dan username dari session
$user_id = $_SESSION['login']['id'];
$username = $_SESSION['login']['username'] ?? 'Unknown User';

// Cek apakah keranjang belanja kosong
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    $_SESSION['info'] = [
        'status' => 'danger',
        'message' => 'Keranjang belanja Anda kosong!'
    ];
    // Arahkan kembali ke halaman dashboard user
    header("Location: ../dashboard/user_dashboard.php");
    exit;
}

// Mulai transaksi database
$connection->begin_transaction();

try {
    // Hitung total harga dari semua item di dalam keranjang
    $total_harga = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total_harga += $item['harga'] * $item['jumlah'];
    }

    // Menetapkan nomor meja secara acak (misalnya T1, T2, dsb)
    $table_number = 'T' . rand(1, 20);

    // Query untuk memasukkan data pemesanan
    $queryPemesanan = "INSERT INTO pemesanan (nama_pemesan, total_harga, tanggal_pemesanan, status, table_number) 
                       VALUES (?, ?, NOW(), 'pending', ?)";
    $stmtPemesanan = $connection->prepare($queryPemesanan);

    if (!$stmtPemesanan) {
        throw new Exception("Prepare error (queryPemesanan): " . $connection->error);
    }

    // Simpan nomor meja dalam session untuk digunakan di halaman lain
    $_SESSION['table_number'] = $table_number;

    // Bind parameter dan eksekusi query
    $stmtPemesanan->bind_param("sds", $username, $total_harga, $table_number);
    $stmtPemesanan->execute();
    $pemesanan_id = $stmtPemesanan->insert_id;

    // Query untuk memasukkan data ke tabel detail_pemesanan
    $queryDetail = "INSERT INTO detail_pemesanan (id_pemesanan, id_produk, user_id, jumlah, subtotal) 
                    VALUES (?, ?, ?, ?, ?)";
    $stmtDetail = $connection->prepare($queryDetail);

    if (!$stmtDetail) {
        throw new Exception("Prepare error (queryDetail): " . $connection->error);
    }

    // Masukkan detail setiap item dalam keranjang
    foreach ($_SESSION['cart'] as $item) {
        $subtotal = $item['harga'] * $item['jumlah'];

        // Bind parameter dan eksekusi query
        $stmtDetail->bind_param("iiidd", $pemesanan_id, $item['id'], $user_id, $item['jumlah'], $subtotal);
        $stmtDetail->execute();
    }

    // Hapus atau biarkan stok produk tidak berubah jika tidak perlu
    // Jika ingin mengurangi stok produk, aktifkan bagian berikut
    // $queryUpdateStock = "UPDATE produk SET stock = stock - ?, jumlah_terjual = jumlah_terjual + ? WHERE id = ?";
    // $stmtUpdateStock = $connection->prepare($queryUpdateStock);

    // if (!$stmtUpdateStock) {
    //     throw new Exception("Prepare error (queryUpdateStock): " . $connection->error);
    // }

    // foreach ($_SESSION['cart'] as $item) {
    //     $stmtUpdateStock->bind_param("iii", $item['jumlah'], $item['jumlah'], $item['id']);
    //     $stmtUpdateStock->execute();
    // }

    // Commit transaksi jika semuanya berhasil
    $connection->commit();

    // Kosongkan keranjang setelah pemesanan berhasil
    unset($_SESSION['cart']);

    // Simpan pesan sukses dalam session untuk ditampilkan di halaman berikutnya
    $_SESSION['info'] = [
        'status' => 'success',
        'message' => 'Checkout berhasil! Pesanan Anda telah dibuat. Nomor meja Anda adalah ' . $table_number
    ];

    // Log keberhasilan checkout
    write_log("User '$username' berhasil melakukan checkout dengan ID pemesanan: $pemesanan_id dan nomor meja: $table_number", 'INFO');

    // Arahkan ke halaman konfirmasi pesanan
    header("Location: ./order_confirmation.php");
    exit;
} catch (Exception $e) {
    // Jika ada kesalahan, rollback transaksi
    $connection->rollback();

    // Log kesalahan yang terjadi
    write_log("Checkout gagal untuk user '$username': " . $e->getMessage(), 'ERROR');

    // Simpan pesan kesalahan dalam session
    $_SESSION['info'] = [
        'status' => 'danger',
        'message' => 'Terjadi kesalahan saat checkout: ' . $e->getMessage()
    ];

    // Arahkan kembali ke halaman dashboard user
    header("Location: ../dashboard/user_dashboard.php");
    exit;
}
