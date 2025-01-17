<?php
// Memuat file helper dan konfigurasi
require_once '../includes/_top.php';
require_once '../helper/connection.php';
require_once '../helper/auth.php';

isLogin();

// Pastikan pengguna sedang login
if (!isset($_SESSION['login']['id'])) {
    die('User tidak ditemukan');
}

// Mendapatkan ID pemesanan dari URL
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Validasi ID pemesanan
if ($order_id <= 0) {
    die('ID pemesanan tidak valid.');
}

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
        p.tanggal_selesai, 
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

// Query untuk detail produk
$detail_query = "
    SELECT 
        dp.jumlah, 
        p.nama AS nama_produk, 
        p.harga 
    FROM detail_pemesanan dp
    JOIN produk p ON dp.id_produk = p.id
    WHERE dp.id_pemesanan = ?
";
$stmt = mysqli_prepare($connection, $detail_query);
mysqli_stmt_bind_param($stmt, 'i', $order_id);
mysqli_stmt_execute($stmt);
$detail_result = mysqli_stmt_get_result($stmt);
?>

<head>
    <style>
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card {
            border-radius: 8px;
        }

        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }

        .btn-secondary {
            background-color: #6c757d;
            border-color: #6c757d;
        }
    </style>
</head>

<section class="section">
    <div class="section-header d-flex justify-content-between align-items-center">
        <h1>Detail Pemesanan</h1>
        <div>
            <a href="history_pesanan.php" class="btn btn-primary me-2">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
            <!-- <button class="btn btn-danger" onclick="window.print()">
                <i class="fas fa-print"></i> Cetak
            </button> -->
        </div>
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
                    <p><strong>Tanggal Pemesanan:</strong> <?= htmlspecialchars($order['tanggal_pemesanan']) ?></p>
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
            <p><strong>Alasan Pembatalan:</strong>
                <?= htmlspecialchars(preg_replace('/^user\s*/', '', $order['info'])) ?: '-' ?>
            </p>
            <p><strong>Bukti Pembayaran:</strong>
                <?php if (!empty($order['payment_proof'])): ?>
                    <a href="<?= '../bukti_pembayaran/' . htmlspecialchars($order['payment_proof']) ?>" target="_blank" class="btn btn-success btn-sm">
                        <i class="fas fa-eye"></i> Lihat Bukti
                    </a>
                <?php else: ?>
                    <span class="text-muted"><i class="fas fa-times"></i> Tidak Ada</span>
                <?php endif; ?>
            </p>
        </div>
    </div>

    <div class="card mt-4 shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5>Detail Produk</h5>
        </div>
        <div class="card-body">
            <?php if (mysqli_num_rows($detail_result) > 0): ?>
                <table class="table table-bordered">
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
                        while ($row = mysqli_fetch_assoc($detail_result)):
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
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle"></i> Tidak ada produk dalam pemesanan ini.
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once '../includes/_bottom.php'; ?>