<?php
// Memuat file helper dan konfigurasi
require_once '../includes/_top.php';
require_once '../helper/connection.php';
require_once '../helper/auth.php';
require_once '../helper/logger.php';

// Memeriksa apakah pengguna adalah admin
isLogin();

if (isset($_SESSION['login']['username'])) {
    $username = $_SESSION['login']['username'];
}

// Mendapatkan ID pemesanan dari URL
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Validasi ID pesanan
if ($order_id <= 0) {
    $_SESSION['info'] = ['status' => 'error', 'message' => 'ID pesanan tidak valid.'];
    header('Location: index.php');
    exit;
}

// Ambil data pesanan
$order_query = "SELECT * FROM pemesanan WHERE id = ?";
$stmt = $connection->prepare($order_query);
$stmt->bind_param('i', $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    $_SESSION['info'] = ['status' => 'error', 'message' => 'Pesanan tidak ditemukan.'];
    header('Location: index.php');
    exit;
}

// Pesan kesalahan atau status
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
    <div class="container my-4">
        <div class="section-header d-flex justify-content-between">
            <h1>Detail Pemesanan #<?= htmlspecialchars(string: $order['id']) ?></h1>
            <a href="history_pesanan.php" class="btn btn-primary">Kembali</a>
        </div>

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5>Detail Pemesanan</h5>
            </div>
            <div class="card-body">

                <?php
                $detail_query = "SELECT dp.*, p.nama AS nama_produk, p.harga FROM detail_pemesanan dp INNER JOIN produk p ON dp.id_produk = p.id WHERE dp.id_pemesanan = ?";
                $stmt = $connection->prepare($detail_query);
                $stmt->bind_param("i", $order_id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0):
                ?>
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nama Produk</th>
                                <th>Harga</th>
                                <th>Jumlah</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no = 1;
                            $grand_total = 0;
                            while ($row = $result->fetch_assoc()):
                                $subtotal = $row['harga'] * $row['jumlah'];
                                $grand_total += $subtotal;
                            ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= htmlspecialchars($row['nama_produk']) ?></td>
                                    <td>Rp<?= number_format($row['harga'], 0, ',', '.') ?></td>
                                    <td><?= htmlspecialchars($row['jumlah']) ?></td>
                                    <td>Rp<?= number_format($subtotal, 0, ',', '.') ?></td>
                                </tr>
                            <?php endwhile; ?>
                            <tr>
                                <td colspan="4" class="text-end"><strong>Grand Total</strong></td>
                                <td><strong>Rp<?= number_format($grand_total, 0, ',', '.') ?></strong></td>
                            </tr>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-center">Tidak ada detail pemesanan.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require_once '../includes/_bottom.php'; ?>