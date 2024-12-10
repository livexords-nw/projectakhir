<?php
session_start();
require_once '../../helper/connection.php';
require_once '../../helper/logger.php'; // Pastikan logger.php berada di lokasi yang sesuai

// Validasi data POST
if (!isset($_POST['id'])) {
    write_log('ID produk tidak ditemukan dalam POST request.', 'ERROR');
    echo "<script>alert('ID produk tidak ditemukan.'); window.history.back();</script>";
    exit;
}

$product_id = (int) $_POST['id'];
$jumlah = isset($_POST['jumlah']) ? (int) $_POST['jumlah'] : 1; // Ambil jumlah dari input

// Ambil username dari session
$username = isset($_SESSION['login']['username']) ? $_SESSION['login']['username'] : 'Unknown User';

// Validasi jumlah produk
if ($jumlah < 1) {
    $_SESSION['info'] = [
        'status' => 'danger',
        'message' => "Jumlah harus minimal 1."
    ];
    header("Location: ../user_dashboard.php");
    exit;
}

// Ambil detail produk dari database
$query = "SELECT * FROM produk WHERE id = $product_id AND stock > 0";
$result = mysqli_query($connection, $query);

if ($result && mysqli_num_rows($result) > 0) {
    $product = mysqli_fetch_assoc($result);

    // Cek apakah produk dan nama produk ada
    $product_name = isset($product['nama']) ? htmlspecialchars($product['nama'], ENT_QUOTES, 'UTF-8') : 'Unknown Product';

    // Inisialisasi keranjang jika belum ada
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // Periksa apakah produk sudah ada di keranjang
    $found = false;
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['id'] == $product_id) {
            $item['jumlah'] += $jumlah; // Tambahkan jumlah ke keranjang
            $found = true;
            break;
        }
    }

    // Jika belum ada, tambahkan produk ke keranjang
    if (!$found) {
        $_SESSION['cart'][] = [
            'id' => $product['id'],
            'nama' => $product_name,
            'harga' => $product['harga'],
            'jumlah' => $jumlah // Gunakan jumlah dari input
        ];
    }

    // Menyimpan info ke session
    $_SESSION['info'] = [
        'status' => 'success',
        'message' => "Berhasil menambahkan {$jumlah} produk {$product_name} ke keranjang."
    ];

    // Log aksi ke logger
    write_log("User '$username' menambahkan {$jumlah} unit produk '{$product_name}' ke keranjang.", 'INFO');

    header("Location: ../user_dashboard.php");
    exit;
} else {
    // Jika produk tidak ditemukan atau stok tidak cukup
    $_SESSION['info'] = [
        'status' => 'danger',
        'message' => "Produk tidak ditemukan atau stok tidak mencukupi untuk jumlah {$jumlah}."
    ];
    write_log("User '$username' gagal menambahkan produk (ID: $product_id) ke keranjang. Stok tidak cukup atau produk tidak ditemukan.", 'ERROR');
    header("Location: ../user_dashboard.php");
    exit;
}
