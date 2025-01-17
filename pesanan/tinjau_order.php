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

// Query data pemesanan
$order_query = "
    SELECT 
        p.id, 
        p.nama_pemesan, 
        p.tanggal_pemesanan, 
        p.total_harga, 
        p.status, 
        p.booking_start, 
        p.booking_end, 
        p.payment_proof, 
        p.info, 
        p.type_payment, 
        m.table_number
    FROM pemesanan p
    LEFT JOIN meja m ON p.meja_id = m.id
    WHERE p.id = ?
";
$stmt = mysqli_prepare($connection, $order_query);
mysqli_stmt_bind_param($stmt, 'i', $order_id);
mysqli_stmt_execute($stmt);
$order_result = mysqli_stmt_get_result($stmt);
$order = mysqli_fetch_assoc($order_result);

// Jika tidak ada data pemesanan
if (!$order) {
    die('Data pemesanan tidak ditemukan.');
}
?>

<section class="section">
    <div class="container my-4">
        <div class="section-header d-flex justify-content-between">
            <h1>Tinjau Pesanan #<?= $order['id'] ?> <?= $order['nama_pemesan'] ?></h1>
            <a href="index.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i>
                Kembali
            </a>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5>Informasi Pemesanan</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>ID Pemesanan:</strong> <?= htmlspecialchars($order['id']) ?></p>
                        <p><strong>Nama Pemesan:</strong> <?= htmlspecialchars($order['nama_pemesan']) ?></p>
                        <p><strong>Tanggal Pemesanan:</strong> <?= htmlspecialchars($order['tanggal_pemesanan']) ?: ' -' ?></p>
                        <p><strong>Nomor Meja:</strong> <?= htmlspecialchars($order['table_number']) ?: 'N/A' ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Total Harga:</strong> Rp <?= number_format($order['total_harga'], 0, ',', '.') ?></p>
                        <p><strong>Tipe Pembayaran:</strong> <?= htmlspecialchars($order['type_payment']) ?: 'Tidak Diketahui' ?></p>
                        <p><strong>Status:</strong>
                            <?php
                            $status = htmlspecialchars($order['status']);
                            $info = htmlspecialchars($order['info']); // Ambil kolom info
                            $badgeColor = match ($status) {
                                'pending' => 'badge-warning',
                                'canceled' => 'badge-danger',
                                'approved' => 'badge-success',
                                default => 'badge-secondary',
                            };

                            // Tentukan apakah status "Canceled by User" atau "Canceled by Admin"
                            if ($status === 'canceled') {
                                if (str_starts_with($info, 'user')) {
                                    $status .= ' by User';
                                }
                            }
                            ?>
                            <span class="badge <?= $badgeColor ?>"><?= $status ?></span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <form method="post" action="order_management.php" id="formTinjau">
            <div class="card">
                <div class="card-body">
                    <input type="hidden" name="id" value="<?= $order['id'] ?>">

                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-control" id="status" name="action" onchange="togglePesanArea()">
                            <option value="approved" <?= $order['status'] == 'approved' ? 'selected' : '' ?>>Approved</option>
                            <option value="canceled" <?= $order['status'] == 'canceled' ? 'selected' : '' ?>>Dibatalkan</option>
                        </select>
                    </div>

                    <div class="mb-3" id="pesanArea" style="display: none;">
                        <label for="pesan" class="form-label">Alasan</label>
                        <textarea class="form-control" id="pesan" name="info" rows="5" style="font-size: 16px; height: 80px;"></textarea>
                    </div>

                    <!-- Tambahkan Dropdown untuk Template Alasan -->
                    <div class="mb-3" id="templateArea" style="display: none;">
                        <label for="template" class="form-label">Template Alasan</label>
                        <select class="form-control" id="template" onchange="insertTemplate(this.value)">
                            <option value="">Pilih Template</option>
                            <option value="Stok barang habis">Stok barang habis</option>
                            <option value="Pembayaran tidak valid">Pembayaran tidak valid</option>
                            <option value="Kesalahan teknis pada sistem">Kesalahan teknis pada sistem</option>
                        </select>
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

    // Fungsi untuk menampilkan atau menyembunyikan textarea alasan
    function togglePesanArea() {
        const statusSelect = document.getElementById('status');
        const pesanArea = document.getElementById('pesanArea');

        if (statusSelect.value === 'canceled') {
            pesanArea.style.display = 'block';
        } else {
            pesanArea.style.display = 'none';
        }
    }

    // Fungsi untuk menambahkan template alasan
    function insertTemplate(template) {
        const pesanField = document.getElementById('pesan');
        pesanField.value = template;
    }

    // Fungsi untuk validasi sebelum submit
    function confirmTinjau() {
        const statusSelect = document.getElementById('status');
        const pesanField = document.getElementById('pesan');

        if (statusSelect.value === 'canceled' && pesanField.value.trim() === '') {
            iziToast.error({
                title: 'Error',
                message: 'Anda harus mengisi alasan pembatalan.',
                position: 'topCenter',
                timeout: 5000
            });
            return; // Jangan submit jika validasi gagal
        }

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

    // Event listener untuk memastikan togglePesanArea dipanggil saat halaman dimuat
    document.addEventListener('DOMContentLoaded', togglePesanArea);

    // Tampilkan template area jika status adalah "canceled"
    function toggleTemplateArea() {
        const statusSelect = document.getElementById('status');
        const templateArea = document.getElementById('templateArea');

        if (statusSelect.value === 'canceled') {
            templateArea.style.display = 'block';
        } else {
            templateArea.style.display = 'none';
        }
    }

    // Gabungkan togglePesanArea dan toggleTemplateArea
    function togglePesanArea() {
        const statusSelect = document.getElementById('status');
        const pesanArea = document.getElementById('pesanArea');
        const templateArea = document.getElementById('templateArea');

        if (statusSelect.value === 'canceled') {
            pesanArea.style.display = 'block';
            templateArea.style.display = 'block';
        } else {
            pesanArea.style.display = 'none';
            templateArea.style.display = 'none';
        }
    }

    document.addEventListener('DOMContentLoaded', togglePesanArea);
</script>