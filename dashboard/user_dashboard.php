<?php
require_once '../includes/_top.php';
require_once '../helper/connection.php';
require_once '../helper/auth.php';
require_once '../helper/logger.php';

isLogin();

if (isset($_SESSION['login']['username'])) {
    $username = $_SESSION['login']['username'];
    write_log("User '$username' mengakses halaman dashboard.", 'INFO');
}

// Ambil data produk dari database
$query = "SELECT * FROM produk WHERE stock > 0";
$result = mysqli_query($connection, $query);

// Inisialisasi keranjang belanja di sesi
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$errors = isset($_SESSION['errors']) ? $_SESSION['errors'] : [];
$info = isset($_SESSION['info']) ? $_SESSION['info'] : null;

if ($info) {
    $status = $info['status']; 
    $message = $info['message'];

    if (is_array($message)) {
        $message = implode(' | ', $message);
    }
    
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            iziToast." . ($status === 'success' ? 'success' : 'error') . "( {
                title: '" . ($status === 'success' ? 'Sukses' : 'Gagal') . "',
                message: '{$message}',
                position: 'topCenter',
                timeout: 5000
            });
        });
    </script>";
    
    unset($_SESSION['info']);
}
unset($_SESSION['errors'])
?>

<section class="section">
    <div class="section-header">
        <h1>Dashboard</h1>
    </div>

    <!-- Keranjang Belanja -->
    <h2>Keranjang Belanja</h2>
    <div class="card mb-4">
        <div class="card-body">
            <table class="table table-bordered" id="cart-table">
                <thead>
                    <tr>
                        <th>Nama Produk</th>
                        <th>Harga</th>
                        <th>Jumlah</th>
                        <th>Subtotal</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($_SESSION['cart'])): ?>
                        <?php
                        $total = 0;
                        foreach ($_SESSION['cart'] as $item):
                            $subtotal = $item['harga'] * $item['jumlah'];
                            $total += $subtotal;
                        ?>
                            <tr>
                                <td><?= $item['nama'] ?></td>
                                <td><?= number_format($item['harga'], 2) ?></td>
                                <td><?= $item['jumlah'] ?></td>
                                <td><?= number_format($subtotal, 2) ?></td>
                                <td><a href="remove_from_cart.php?id=<?= $item['id'] ?>" class="btn btn-danger btn-sm">Hapus</a></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td colspan="3"><strong>Total</strong></td>
                            <td><strong><?= number_format($total, 2) ?></strong></td>
                            <td></td>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center">Keranjang kosong.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <a href="../produk/checkout.php" class="btn btn-success mt-2">Konfirmasi Pesanan</a>
        </div>
    </div>

    <!-- Daftar Produk -->
    <h2>Daftar Produk</h2>
    <div class="row">
        <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                <div class="card">
                    <img src="../uploads/<?= $row['gambar'] ?>" class="card-img-top" alt="<?= $row['nama'] ?>" style="height: 200px; object-fit: cover;">
                    <div class="card-body">
                        <h5 class="card-title"><?= $row['nama'] ?></h5>
                        <p class="card-text">Rp <?= number_format($row['harga'], 0, ',', '.') ?></p>
                        <p class="card-text"><small>Stok <?= $row['stock'] ? 'Tersedia' : 'Kosong' ?></small></p>
                        <form action="add_to_cart.php" method="POST">
                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                            <div class="form-group">
                                <label for="jumlah-<?= $row['id'] ?>">Jumlah</label>
                                <input type="number" name="jumlah" id="jumlah-<?= $row['id'] ?>" class="form-control" min="1" value="1" required>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">Tambah ke Troli</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</section>

<script>
  $(document).on("click", ".add-to-cart", function () {
    const productId = $(this).data("id");

    console.log("Mengirim data ke server untuk ID:", productId); // Debug
    $.post("add_to_cart.php", { id: productId }, function (response) {
        console.log("Respon server:", response); // Debug
        if (response.status === "success") {
            alert(response.message);
            location.reload();
        } else {
            alert(response.message);
        }
    }, "json").fail(function (xhr, status, error) {
        console.error("AJAX error:", status, error); // Debug jika ada error
        alert("Terjadi kesalahan saat menambahkan ke troli.");
    });
});
</script>

<?php require_once '../includes/_bottom.php'; ?>
