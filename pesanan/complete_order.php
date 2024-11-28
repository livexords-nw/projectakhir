<?php
require_once '../helper/connection.php'; 
require_once '../helper/auth.php'; 
require_once '../helper/logger.php'; 

// Pastikan hanya admin yang dapat mengakses halaman ini
checkAdmin();

// Validasi jika ID pesanan tersedia melalui POST
if (isset($_POST['order_id']) && !empty($_POST['order_id'])) {
    $order_id = $_POST['order_id'];  // ID pesanan yang akan diselesaikan

    // Siapkan query untuk memperbarui status pesanan menjadi 'completed'
    $query = "UPDATE pemesanan SET status = 'completed', tanggal_selesai = NOW() WHERE id = ?";

    // Persiapkan statement
    $stmt = $connection->prepare($query);

    // Cek jika prepare gagal
    if ($stmt === false) {
        // Log error jika query gagal disiapkan
        write_log("Gagal menyiapkan query untuk memperbarui status pesanan #$order_id.", 'ERROR');
        
        // Simpan informasi error ke dalam session
        $_SESSION['info'] = [
            'status' => 'error',
            'message' => 'Terjadi kesalahan saat memproses pesanan. Coba lagi nanti.'
        ];
        
        header('Location: orders_dashboard.php');  // Redirect setelah menulis pesan ke session
        exit;
    }

    // Bind parameter dan eksekusi query
    $stmt->bind_param("i", $order_id);

    // Cek apakah eksekusi berhasil
    if ($stmt->execute()) {
        // Jika berhasil, log aktivitas admin
        if (isset($_SESSION['login']['username'])) {
            $username = $_SESSION['login']['username'];
            write_log("Admin '$username' menyelesaikan pesanan #$order_id.", 'SUCCESS');
        }

        // Simpan informasi sukses ke dalam session
        $_SESSION['info'] = [
            'status' => 'success',
            'message' => 'Pesanan telah selesai diproses.'
        ];
        
        header('Location: orders_dashboard.php');  // Redirect setelah menulis pesan ke session
        exit;
    } else {
        // Log error jika gagal mengupdate status
        write_log("Gagal mengupdate status pesanan #$order_id. Error: " . $stmt->error, 'ERROR');
        
        // Simpan informasi error ke dalam session
        $_SESSION['info'] = [
            'status' => 'error',
            'message' => 'Gagal menyelesaikan pesanan. Silakan coba lagi.'
        ];
        
        header('Location: orders_dashboard.php');  // Redirect setelah menulis pesan ke session
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

    header('Location: orders_dashboard.php');  // Redirect setelah menulis pesan ke session
    exit;
}
?>
