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

// Validasi tipe aksi
$action = isset($_POST['action']) ? $_POST['action'] : '';
$order_id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

if (!$action) {
    $_SESSION['info'] = [
        'status' => 'error',
        'message' => 'Tipe aksi tidak valid.'
    ];
    header('Location: index.php');
    exit;
}

try {
    // Ambil informasi detail pesanan
    $order_query = "SELECT nama_pemesan, 
                           (SELECT SUM(dp.jumlah * p.harga) 
                            FROM detail_pemesanan dp 
                            JOIN produk p ON dp.id_produk = p.id 
                            WHERE dp.id_pemesanan = pemesanan.id) AS total
                    FROM pemesanan WHERE id = ?";
    $stmt = $connection->prepare($order_query);
    $stmt->bind_param('i', $order_id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();

    if (!$order) {
        throw new Exception("Pesanan dengan ID {$order_id} tidak ditemukan.");
    }

    $nama_pemesan = $order['nama_pemesan'];
    $total = $order['total'];

    switch ($action) {
        case 'approved':
            // Tandai pesanan sebagai selesai
            $query = "UPDATE pemesanan SET status = 'approved', tanggal_selesai = NOW() WHERE id = ?";
            $stmt = $connection->prepare($query);
            $stmt->bind_param("i", $order_id);

            if (!$stmt->execute()) {
                throw new Exception("Gagal menyelesaikan pesanan. Error: " . $stmt->error);
            }

            // Update jumlah terjual pada tabel produk
            $details_query = "SELECT id_produk, jumlah FROM detail_pemesanan WHERE id_pemesanan = ?";
            $details_stmt = $connection->prepare($details_query);
            $details_stmt->bind_param('i', $order_id);
            $details_stmt->execute();
            $details_result = $details_stmt->get_result();

            while ($detail = $details_result->fetch_assoc()) {
                $update_product_query = "UPDATE produk SET jumlah_terjual = jumlah_terjual + ? WHERE id = ?";
                $update_stmt = $connection->prepare($update_product_query);
                $update_stmt->bind_param('ii', $detail['jumlah'], $detail['id_produk']);

                if (!$update_stmt->execute()) {
                    throw new Exception("Gagal memperbarui jumlah terjual untuk produk ID {$detail['id_produk']}. Error: " . $update_stmt->error);
                }
            }

            // Catat log
            write_log("Pesanan approved: ID Pesanan={$order_id}, Pemesan={$nama_pemesan}, Total={$total}", 'INFO');

            $_SESSION['info'] = ['status' => 'success', 'message' => 'Pesanan dengan id ' . $order_id . ' berhasil di approved.'];
            break;

        case 'canceled':
            // Tandai pesanan sebagai dibatalkan
            $info = $_POST['info'] ?? '';
            $query = "UPDATE pemesanan SET status = 'canceled', info = ?, tanggal_selesai = NOW() WHERE id = ?";
            $stmt = $connection->prepare($query);
            $stmt->bind_param("si", $info, $order_id);

            if (!$stmt->execute()) {
                throw new Exception("Gagal membatalkan pesanan. Error: " . $stmt->error);
            }

            // Catat log
            write_log("Pesanan dibatalkan: ID Pesanan={$order_id}, Pemesan={$nama_pemesan}, Total={$total}, Alasan={$info}", 'INFO');

            $_SESSION['info'] = ['status' => 'success', 'message' => 'Pesanan dengan id ' . $order_id . ' berhasil dibatalkan.'];
            break;

        default:
            $_SESSION['info'] = ['status' => 'error', 'message' => 'Aksi tidak dikenali.'];
            break;
    }

    header('Location: index.php');
    exit;
} catch (Exception $e) {
    $_SESSION['info'] = [
        'status' => 'error',
        'message' => 'Terjadi kesalahan: ' . $e->getMessage()
    ];
    header('Location: index.php');
    exit;
}
