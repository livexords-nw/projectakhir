<?php
require_once '../helper/connection.php';
require_once '../helper/auth.php';
require_once '../helper/logger.php';

// Pastikan hanya admin yang dapat mengakses halaman ini
checkAdmin();

// Validasi jika ID pesanan tersedia melalui POST
if (isset($_POST['order_id']) && !empty($_POST['order_id'])) {
    $order_id = $_POST['order_id'];  // ID pesanan yang akan diselesaikan

    // Mulai transaksi
    $connection->begin_transaction();

    try {
        // Siapkan query untuk memperbarui status pesanan menjadi 'completed'
        $query_update_order = "UPDATE pemesanan SET status = 'completed', tanggal_selesai = NOW() WHERE id = ?";
        $stmt_update_order = $connection->prepare($query_update_order);

        if ($stmt_update_order === false) {
            throw new Exception("Gagal menyiapkan query untuk memperbarui status pesanan.");
        }

        // Bind parameter dan eksekusi query
        $stmt_update_order->bind_param("i", $order_id);
        if (!$stmt_update_order->execute()) {
            throw new Exception("Gagal mengupdate status pesanan #$order_id. Error: " . $stmt_update_order->error);
        }

        // Ambil detail pesanan untuk mendapatkan produk yang dipesan
        $query_order_details = "SELECT id_produk, jumlah FROM detail_pemesanan WHERE id_pemesanan = ?";
        $stmt_order_details = $connection->prepare($query_order_details);

        if ($stmt_order_details === false) {
            throw new Exception("Gagal menyiapkan query untuk mengambil detail pesanan.");
        }

        // Bind parameter dan eksekusi query
        $stmt_order_details->bind_param("i", $order_id);
        if (!$stmt_order_details->execute()) {
            throw new Exception("Gagal mengambil detail pesanan untuk pesanan #$order_id. Error: " . $stmt_order_details->error);
        }

        $result_order_details = $stmt_order_details->get_result();

        // Iterasi setiap item pesanan untuk memperbarui jumlah terjual pada tabel produk
        while ($row = $result_order_details->fetch_assoc()) {
            $produk_id = $row['id_produk'];
            $jumlah_dipesan = $row['jumlah'];

            $query_update_produk = "UPDATE produk SET jumlah_terjual = jumlah_terjual + ?, stock = stock - ? WHERE id = ?";
            $stmt_update_produk = $connection->prepare($query_update_produk);

            if ($stmt_update_produk === false) {
                throw new Exception("Gagal menyiapkan query untuk memperbarui jumlah terjual produk.");
            }

            // Bind parameter dan eksekusi query
            $stmt_update_produk->bind_param("iii", $jumlah_dipesan, $jumlah_dipesan, $produk_id);
            if (!$stmt_update_produk->execute()) {
                throw new Exception("Gagal memperbarui jumlah terjual untuk produk #$produk_id. Error: " . $stmt_update_produk->error);
            }
        }

        // Commit transaksi jika semua langkah berhasil
        $connection->commit();

        // Log aktivitas admin
        if (isset($_SESSION['login']['username'])) {
            $username = $_SESSION['login']['username'];
            write_log("Admin '$username' menyelesaikan pesanan #$order_id dan memperbarui jumlah terjual produk.", 'SUCCESS');
        }

        // Simpan informasi sukses ke dalam session
        $_SESSION['info'] = [
            'status' => 'success',
            'message' => 'Pesanan telah selesai diproses dan jumlah terjual telah diperbarui.'
        ];

        header('Location: orders_dashboard.php');
        exit;
    } catch (Exception $e) {
        // Rollback transaksi jika terjadi kesalahan
        $connection->rollback();

        // Log error
        write_log($e->getMessage(), 'ERROR');

        // Simpan informasi error ke dalam session
        $_SESSION['info'] = [
            'status' => 'error',
            'message' => 'Terjadi kesalahan saat memproses pesanan. Coba lagi nanti.'
        ];

        header('Location: orders_dashboard.php');
        exit;
    }
} else {
    // Log error jika order_id tidak ditemukan
    write_log('Tidak ada ID pesanan yang diberikan.', 'ERROR');

    // Simpan informasi error ke dalam session
    $_SESSION['info'] = [
        'status' => 'error',
        'message' => 'Pesanan tidak ditemukan.'
    ];

    header('Location: orders_dashboard.php');
    exit;
}
