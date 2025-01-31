<?php
session_start();
require_once '../../helper/logger.php'; // Pastikan logger.php ada

// Cek jika ID produk dikirim melalui GET
if (isset($_GET['id'])) {
    $productId = intval($_GET['id']); // Mengambil ID produk dari parameter GET

    // Cek jika keranjang ada dan ID produk valid
    if ($productId > 0 && isset($_SESSION['cart'])) {
        // Flag untuk menandakan apakah produk ditemukan
        $found = false;
        $productName = ''; // Menyimpan nama produk yang akan dihapus
        $productJumlah = 0; // Menyimpan jumlah produk yang akan dihapus

        // Loop untuk mencari produk dalam keranjang
        foreach ($_SESSION['cart'] as $key => $item) {
            if ($item['id'] == $productId) {
                // Ambil nama produk dan jumlah, pastikan aman untuk karakter khusus
                $productName = isset($item['nama']) ? htmlspecialchars($item['nama'], ENT_QUOTES, 'UTF-8') : 'Unknown Product';
                $productJumlah = isset($item['jumlah']) ? intval($item['jumlah']) : 0;
                unset($_SESSION['cart'][$key]); // Menghapus produk dari keranjang
                $found = true;
                break;
            }
        }

        // Re-index array keranjang agar indeks tetap urut setelah produk dihapus
        $_SESSION['cart'] = array_values($_SESSION['cart']);

        // Log dan set notifikasi jika produk berhasil dihapus
        if ($found) {
            $username = isset($_SESSION['login']['username']) ? $_SESSION['login']['username'] : 'Unknown User';

            $_SESSION['info'] = [
                'status' => 'success',
                'message' => "Produk {$productName} (Jumlah: {$productJumlah}) berhasil dihapus dari keranjang."
            ];
        } else {
            // Log dan notifikasi jika produk tidak ditemukan
            $username = isset($_SESSION['login']['username']) ? $_SESSION['login']['username'] : 'Unknown User';

            $_SESSION['info'] = [
                'status' => 'danger',
                'message' => "Produk tidak ditemukan di keranjang atau sudah dihapus."
            ];
        }
    } else {
        // Jika ID produk tidak valid atau keranjang kosong
        $_SESSION['info'] = [
            'status' => 'danger',
            'message' => 'ID produk tidak valid atau keranjang kosong.'
        ];
    }
} else {
    // Jika ID produk tidak ditemukan dalam GET
    $_SESSION['info'] = [
        'status' => 'danger',
        'message' => 'ID produk tidak ditemukan dalam request.'
    ];
}

// Redirect kembali ke halaman dashboard
header("Location: ../keranjang.php");
exit;
