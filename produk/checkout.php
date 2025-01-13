<?php
// checkout.php
session_start();
require_once '../helper/connection.php';
require_once '../helper/logger.php';

// **1. Validasi Login Pengguna**
if (!isset($_SESSION['login']['id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['login']['id'];
$username = $_SESSION['login']['username'] ?? 'Pengguna Tidak Dikenal';

// **2. Validasi Keranjang Belanja**
if (empty($_SESSION['cart'])) {
    $_SESSION['info'] = [
        'status' => 'danger',
        'message' => 'Keranjang belanja Anda kosong!'
    ];
    header("Location: ../dashboard/user_dashboard.php");
    exit;
}

// **3. Validasi Data Booking**
$meja_id = $_POST['meja_id'] ?? null;
$booking_start = date('Y-m-d H:i:s', strtotime($_POST['booking_start'] ?? ''));
$booking_duration = intval($_POST['booking_duration'] ?? 0);
$booking_end = date('Y-m-d H:i:s', strtotime($booking_start . "+{$booking_duration} minutes"));

if (!$meja_id || !$booking_start || !$booking_end) {
    $_SESSION['info'] = [
        'status' => 'danger',
        'message' => 'Data meja, waktu mulai, dan durasi booking harus diisi!'
    ];
    header("Location: ../dashboard/user_dashboard.php");
    exit;
}

// **4. Validasi dan Upload Bukti Pembayaran**
$payment_method = $_POST['payment_method'] ?? 'cash';

if ($payment_method !== 'cash') {
    if (!isset($_FILES['payment_proof']) || $_FILES['payment_proof']['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['info'] = [
            'status' => 'danger',
            'message' => 'Bukti pembayaran wajib diunggah!'
        ];
        header("Location: ../dashboard/user_dashboard.php");
        exit;
    }

    $uploadedFile = $_FILES['payment_proof'];
    $fileExtension = pathinfo($uploadedFile['name'], PATHINFO_EXTENSION);
    $targetDir = '../bukti_pembayaran/';
    $filename = "{$user_id}_" . pathinfo($uploadedFile['name'], PATHINFO_FILENAME) . "_" . date('YmdHis') . ".$fileExtension";
    $targetFile = $targetDir . $filename;

    if (!move_uploaded_file($uploadedFile['tmp_name'], $targetFile)) {
        $_SESSION['info'] = [
            'status' => 'danger',
            'message' => 'Gagal mengunggah bukti pembayaran!'
        ];
        header("Location: ../dashboard/user_dashboard.php");
        exit;
    }
} else {
    $filename = null; // Tidak ada bukti pembayaran untuk metode cash
}


// **5. Mulai Transaksi**
$connection->begin_transaction();

try {
    // **5.1 Cek Konflik Jadwal Meja**
    $queryCheckConflict = "SELECT * FROM pemesanan 
                           WHERE meja_id = ? 
                           AND status IN ('pending', 'completed') 
                           AND (booking_start < ? AND booking_end > ?)";
    $stmtCheckConflict = $connection->prepare($queryCheckConflict);
    $stmtCheckConflict->bind_param("iss", $meja_id, $booking_end, $booking_start);
    $stmtCheckConflict->execute();
    $resultConflict = $stmtCheckConflict->get_result();

    if ($resultConflict->num_rows > 0) {
        throw new Exception("Meja ini sudah dipesan untuk waktu tersebut. Silakan pilih meja lain atau ubah waktu booking.");
    }

    // **5.2 Hitung Total Harga dari Keranjang**
    $total_harga = array_reduce($_SESSION['cart'], function ($total, $item) {
        return $total + ($item['harga'] * $item['jumlah']);
    }, 0);

    // **5.3 Tambahkan Data Pemesanan**
    $queryPemesanan = "INSERT INTO pemesanan (nama_pemesan, tanggal_pemesanan, total_harga, status, user_id, meja_id, booking_start, booking_end, payment_proof) 
                       VALUES (?, NOW(), ?, 'pending', ?, ?, ?, ?, ?)";
    $stmtPemesanan = $connection->prepare($queryPemesanan);
    $stmtPemesanan->bind_param("sdissss", $username, $total_harga, $user_id, $meja_id, $booking_start, $booking_end, $filename);
    $stmtPemesanan->execute();

    $pemesanan_id = $stmtPemesanan->insert_id;

    // **5.4 Tambahkan Detail Pemesanan**
    $queryDetail = "INSERT INTO detail_pemesanan (id_pemesanan, id_produk, user_id, jumlah, subtotal) VALUES (?, ?, ?, ?, ?)";
    $stmtDetail = $connection->prepare($queryDetail);

    foreach ($_SESSION['cart'] as $item) {
        $subtotal = $item['harga'] * $item['jumlah'];
        $stmtDetail->bind_param("iiidd", $pemesanan_id, $item['id'], $user_id, $item['jumlah'], $subtotal);
        $stmtDetail->execute();
    }

    // **5.5 Commit Transaksi**
    $connection->commit();

    // Kosongkan keranjang
    unset($_SESSION['cart']);

    $_SESSION['info'] = [
        'status' => 'success',
        'message' => "Checkout berhasil! Pesanan Anda telah dibuat."
    ];

    write_log("User '$username' berhasil checkout dengan ID pemesanan: $pemesanan_id", 'INFO');
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
} finally {
    if (isset($stmtPemesanan)) $stmtPemesanan->close();
    if (isset($stmtDetail)) $stmtDetail->close();
    $connection->close();
}
