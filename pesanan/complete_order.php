<?php
require_once '../helper/connection.php';
require_once '../helper/auth.php';
require_once '../helper/logger.php';

// Pastikan hanya admin yang dapat mengakses halaman ini
checkAdmin();

// Hanya menerima request POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    exit;
}

// Validasi jika ID pesanan tersedia melalui POST
$order_id = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
if ($order_id <= 0) {
    write_log('ID pesanan tidak valid.', 'ERROR');
    $_SESSION['info'] = [
        'status' => 'error',
        'message' => 'ID pesanan tidak valid.'
    ];
    header('Location: orders_dashboard.php');
    exit;
}

// Mulai transaksi
$connection->begin_transaction();

try {
    // Perbarui status pesanan menjadi 'completed'
    $query_update_order = "UPDATE pemesanan SET status = 'completed', tanggal_selesai = NOW() WHERE id = ?";
    $stmt_update_order = $connection->prepare($query_update_order);

    if ($stmt_update_order === false) {
        throw new Exception("Gagal menyiapkan query untuk memperbarui status pesanan.");
    }

    $stmt_update_order->bind_param("i", $order_id);
    if (!$stmt_update_order->execute()) {
        throw new Exception("Gagal mengupdate status pesanan #$order_id. Error: " . $stmt_update_order->error);
    }

    // Ambil detail pesanan
    $query_order_details = "SELECT id_produk, jumlah FROM detail_pemesanan WHERE id_pemesanan = ?";
    $stmt_order_details = $connection->prepare($query_order_details);

    if ($stmt_order_details === false) {
        throw new Exception("Gagal menyiapkan query untuk mengambil detail pesanan.");
    }

    $stmt_order_details->bind_param("i", $order_id);
    if (!$stmt_order_details->execute()) {
        throw new Exception("Gagal mengambil detail pesanan untuk pesanan #$order_id. Error: " . $stmt_order_details->error);
    }

    $result_order_details = $stmt_order_details->get_result();

    // Iterasi item pesanan untuk memperbarui produk
    while ($row = $result_order_details->fetch_assoc()) {
        $produk_id = $row['id_produk'];
        $jumlah_dipesan = $row['jumlah'];

        $query_update_produk = "UPDATE produk SET jumlah_terjual = jumlah_terjual + ?, stock = stock - ? WHERE id = ?";
        $stmt_update_produk = $connection->prepare($query_update_produk);

        if ($stmt_update_produk === false) {
            throw new Exception("Gagal menyiapkan query untuk memperbarui jumlah terjual produk.");
        }

        $stmt_update_produk->bind_param("iii", $jumlah_dipesan, $jumlah_dipesan, $produk_id);
        if (!$stmt_update_produk->execute()) {
            throw new Exception("Gagal memperbarui jumlah terjual untuk produk #$produk_id. Error: " . $stmt_update_produk->error);
        }
    }

    // Commit transaksi
    $connection->commit();

    // Log aktivitas admin
    if (isset($_SESSION['login']['username'])) {
        $username = $_SESSION['login']['username'];
        write_log("Admin '$username' menyelesaikan pesanan #$order_id dan memperbarui jumlah terjual produk.", 'SUCCESS');
    }

    // Simpan informasi sukses
    $_SESSION['info'] = [
        'status' => 'success',
        'message' => 'Pesanan telah selesai diproses dan jumlah terjual telah diperbarui.'
    ];

    header('Location: orders_dashboard.php');
    exit;
} catch (Exception $e) {
    // Rollback transaksi
    $connection->rollback();

    // Log error
    write_log($e->getMessage(), 'ERROR');

    // Simpan informasi error
    $message = 'Terjadi kesalahan saat memproses pesanan.';
    if (getenv('APP_ENV') === 'development') {
        $message .= ' ' . $e->getMessage();
    }
    $_SESSION['info'] = [
        'status' => 'error',
        'message' => $message
    ];

    header('Location: orders_dashboard.php');
    exit;
}
