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

// Ambil input pencarian
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Query default untuk produk dengan stok lebih dari 0
$query = "SELECT * FROM produk WHERE stock > 0";

// Tambahkan filter jika ada pencarian
if (!empty($search)) {
    $query .= " AND nama LIKE '%" . mysqli_real_escape_string($connection, $search) . "%'";
}

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
unset($_SESSION['errors']);
?>

<section class="section">
    <div class="section-header">
        <h1>Keranjang Belanja</h1>
    </div>
    <h2 class="mb-4">Keranjang Belanja Anda</h2>
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form id="updateCartForm" action="cart/update_cart.php" method="POST">
                <table class="table table-bordered table-striped" id="cart-table">
                    <thead class="table-light">
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
                            foreach ($_SESSION['cart'] as $index => $item):
                                $subtotal = $item['harga'] * $item['jumlah'];
                                $total += $subtotal;
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['nama']) ?></td>
                                    <td>Rp <?= number_format($item['harga'], 0, ',', '.') ?></td>
                                    <td>
                                        <input
                                            type="number"
                                            name="jumlah[<?= $index ?>]"
                                            class="form-control form-control-sm"
                                            min="1"
                                            value="<?= $item['jumlah'] ?>"
                                            required>
                                    </td>
                                    <td>Rp <?= number_format($subtotal, 0, ',', '.') ?></td>
                                    <td>
                                        <button
                                            type="button"
                                            class="btn btn-danger btn-sm"
                                            onclick="confirmDelete(<?= $item['id'] ?>)">
                                            <i class="fas fa-trash-alt">Hapus</i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <tr>
                                <td colspan="3" class="text-end"><strong>Total</strong></td>
                                <td><strong>Rp <?= number_format($total, 0, ',', '.') ?></strong></td>
                                <td></td>
                            </tr>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">Keranjang kosong. Silakan tambahkan produk.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <?php if (!empty($_SESSION['cart'])): ?>
                    <div class="d-flex justify-content-between mt-2">
                        <a href="../produk/checkout.php" class="btn btn-success">Konfirmasi Pesanan</a>
                        <button
                            type="button"
                            class="btn btn-primary"
                            onclick="confirmUpdate()">
                            Update Semua
                        </button>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
</section>
<?php require_once '../includes/_bottom.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/izitoast/dist/js/iziToast.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/izitoast/dist/css/iziToast.min.css">

<script>
    function confirmDelete(id) {
        iziToast.question({
            timeout: false,
            close: false,
            overlay: true,
            displayMode: 'once',
            title: 'Konfirmasi Hapus',
            message: 'Apakah Anda yakin ingin menghapus item ini?',
            position: 'center',
            buttons: [
                ['<button>Ya</button>', function(instance, toast) {
                    window.location.href = `cart/remove_from_cart.php?id=${id}`;
                    instance.hide({
                        transitionOut: 'fadeOut'
                    }, toast, 'button');
                }],
                ['<button>Tidak</button>', function(instance, toast) {
                    instance.hide({
                        transitionOut: 'fadeOut'
                    }, toast, 'button');
                }]
            ]
        });
    }
</script>

<script>
    // Konfirmasi Update
    function confirmUpdate() {
        iziToast.question({
            timeout: false,
            close: false,
            overlay: true,
            displayMode: 'once',
            title: 'Konfirmasi Update',
            message: 'Apakah Anda yakin ingin memperbarui jumlah semua item di keranjang?',
            position: 'center',
            buttons: [
                ['<button>Ya</button>', function(instance, toast) {
                    // Submit form untuk update semua item
                    document.getElementById('updateCartForm').submit();
                    instance.hide({
                        transitionOut: 'fadeOut'
                    }, toast, 'button');
                }],
                ['<button>Tidak</button>', function(instance, toast) {
                    instance.hide({
                        transitionOut: 'fadeOut'
                    }, toast, 'button');
                }]
            ]
        });
    }
</script>