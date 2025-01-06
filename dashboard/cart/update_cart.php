<?php
session_start();
require_once '../../helper/logger.php'; // Pastikan logger.php ada

// Cek apakah keranjang belanja sudah diinisialisasi
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Proses pembaruan jumlah
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['jumlah'])) {
    $username = isset($_SESSION['login']['username']) ? $_SESSION['login']['username'] : 'Unknown User';
    $updatedProducts = [];
    $removedProducts = [];
    $errors = [];

    foreach ($_POST['jumlah'] as $index => $jumlah) {
        $jumlah = intval($jumlah);

        // Pastikan indeks valid dalam keranjang
        if (isset($_SESSION['cart'][$index])) {
            $productName = htmlspecialchars($_SESSION['cart'][$index]['nama'], ENT_QUOTES, 'UTF-8');
            $productId = $_SESSION['cart'][$index]['id'];
            $oldJumlah = $_SESSION['cart'][$index]['jumlah'];

            if ($jumlah > 0) {
                // Update jumlah produk
                $_SESSION['cart'][$index]['jumlah'] = $jumlah;
                $updatedProducts[] = [
                    'name' => $productName,
                    'id' => $productId,
                    'oldJumlah' => $oldJumlah,
                    'newJumlah' => $jumlah
                ];
            } else {
                // Hapus produk jika jumlah <= 0
                $removedProducts[] = "$productName (ID: $productId)";
                unset($_SESSION['cart'][$index]);
            }
        } else {
            $errors[] = "Produk dengan indeks $index tidak valid.";
        }
    }

    // Re-index array keranjang
    $_SESSION['cart'] = array_values($_SESSION['cart']);

    // Logging dan notifikasi
    if (!empty($updatedProducts)) {
        $updateLogs = [];
        $updateNotifs = [];

        foreach ($updatedProducts as $product) {
            $updateLogs[] = "{$product['name']} (ID: {$product['id']}, Jumlah: {$product['oldJumlah']} → {$product['newJumlah']})";
            $updateNotifs[] = "{$product['name']} ({$product['oldJumlah']} → {$product['newJumlah']})";
        }

        $logMessage = implode(', ', $updateLogs);
        $notifMessage = implode(', ', $updateNotifs);

        write_log("User '$username' mengupdate jumlah produk: $logMessage.", 'INFO');
        $_SESSION['info'] = [
            'status' => 'success',
            'message' => "Produk yang diperbarui: $notifMessage."
        ];
    }

    if (!empty($removedProducts)) {
        $removedList = implode(', ', $removedProducts);
        write_log("User '$username' menghapus produk dari keranjang: $removedList.", 'INFO');
        $_SESSION['info'] = [
            'status' => 'success',
            'message' => "Produk yang dihapus: $removedList."
        ];
    }

    if (!empty($errors)) {
        $errorList = implode(', ', $errors);
        write_log("User '$username' mengalami kesalahan saat memperbarui keranjang: $errorList.", 'ERROR');
        $_SESSION['info'] = [
            'status' => 'danger',
            'message' => 'Kesalahan saat memperbarui keranjang. Periksa kembali data Anda.'
        ];
    }
} else {
    // Jika metode bukan POST atau data tidak valid
    write_log('Request tidak valid untuk pembaruan keranjang.', 'ERROR');
    $_SESSION['info'] = [
        'status' => 'danger',
        'message' => 'Request tidak valid.'
    ];
}

// Redirect kembali ke halaman dashboard
header('Location: ../keranjang.php');
exit;
