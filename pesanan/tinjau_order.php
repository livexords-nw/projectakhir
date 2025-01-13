<?php
require_once '../includes/_top.php';
require_once '../helper/connection.php';
require_once '../helper/auth.php';
require_once '../helper/logger.php';

// Memeriksa apakah pengguna adalah admin
checkAdmin();

// Mendapatkan ID pemesanan dari URL
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

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
            iziToast." . ($status === 'success' ? 'success' : 'error') . "({
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

// Validasi ID pesanan
if ($order_id <= 0) {
    $_SESSION['info'] = ['status' => 'error', 'message' => 'ID pesanan tidak valid.'];
    header('Location: index.php');
    exit;
}

// Ambil data pesanan utama
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

// Ambil detail pemesanan
$details_query = "SELECT dp.*, p.nama, p.harga, (dp.jumlah * p.harga) AS subtotal FROM detail_pemesanan dp JOIN produk p ON dp.id_produk = p.id WHERE dp.id_pemesanan = ?";
$details_stmt = $connection->prepare($details_query);
$details_stmt->bind_param('i', $order_id);
$details_stmt->execute();
$details_result = $details_stmt->get_result();
$details = $details_result->fetch_all(MYSQLI_ASSOC);
?>

<section class="section">
    <div class="container my-4">
        <div class="section-header d-flex justify-content-between">
            <h1>Tinjau Pesanan #<?= $order['id'] ?> <?= $order['nama_pemesan'] ?></h1>
            <a href="index.php" class="btn btn-primary">Kembali</a>
        </div>

        <form method="post" action="order_management.php" id="formTinjau">
            <div class="card">
                <div class="card-body">
                    <input type="hidden" name="id" value="<?= $order['id'] ?>">

                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-control" id="status" name="action" onchange="togglePesanArea()">
                            <option value="completed" <?= $order['status'] == 'completed' ? 'selected' : '' ?>>Selesai</option>
                            <option value="canceled" <?= $order['status'] == 'canceled' ? 'selected' : '' ?>>Dibatalkan</option>
                        </select>
                    </div>

                    <div class="mb-3" id="pesanArea" style="display: none;">
                        <label for="pesan" class="form-label">Alasan</label>
                        <textarea class="form-control" id="pesan" name="info" rows="5" style="font-size: 16px; height: 80px;"></textarea>
                    </div>

                    <?php if (!empty($order['payment_proof'])): ?>
                        <div class="mb-3">
                            <label for="buktiPembayaran" class="form-label">Bukti Pembayaran</label>
                            <p><a href="../bukti_pembayaran/<?= htmlspecialchars($order['payment_proof']) ?>" target="_blank">Lihat Bukti Pembayaran</a></p>
                        </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <h4>Detail Pemesanan</h4>
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Nama Produk</th>
                                    <th>Jumlah</th>
                                    <th>Harga Satuan</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($details as $detail): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($detail['nama']) ?></td>
                                        <td><?= (int)$detail['jumlah'] ?></td>
                                        <td><?= number_format($detail['harga'], 2) ?></td>
                                        <td><?= number_format($detail['subtotal'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card-footer text-end">
                    <button type="button" class="btn btn-primary" onclick="confirmTinjau()">Perbarui Status</button>
                </div>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</section>

<?php require_once '../includes/_bottom.php'; ?>

<script>
    function togglePesanArea() {
        const statusSelect = document.getElementById('status');
        const pesanArea = document.getElementById('pesanArea');

        if (statusSelect.value === 'canceled') {
            pesanArea.style.display = 'block';
        } else {
            pesanArea.style.display = 'none';
        }
    }

    document.addEventListener('DOMContentLoaded', togglePesanArea);
</script>

<script>
    function confirmTinjau() {
        iziToast.question({
            timeout: false,
            close: false,
            overlay: true,
            displayMode: 'once',
            title: 'Konfirmasi Peninjauan',
            message: 'Apakah Anda yakin ingin meninjau pesanan ini?',
            position: 'center',
            buttons: [
                ['<button>Ya</button>', function(instance, toast) {
                    document.getElementById('formTinjau').submit();
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