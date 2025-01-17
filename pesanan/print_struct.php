<?php
require_once '../helper/connection.php';
require_once '../helper/auth.php';
require_once '../helper/logger.php';

// Pastikan pengguna yang mengakses adalah admin
checkAdmin();

// Log aktivitas admin saat mengakses halaman ini
if (isset($_SESSION['login']['username'])) {
    $username = $_SESSION['login']['username'];
}

// Ambil ID pesanan dari URL
$orderId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Validasi ID pesanan
if ($orderId <= 0) {
    die('ID pesanan tidak valid.');
}

// Query untuk mengambil data pesanan utama
$queryPesanan = $connection->prepare(
    "SELECT * FROM pemesanan WHERE id = ?"
);
$queryPesanan->bind_param('i', $orderId);
$queryPesanan->execute();
$resultPesanan = $queryPesanan->get_result();
$pesanan = $resultPesanan->fetch_assoc();

// Jika pesanan tidak ditemukan
if (!$pesanan) {
    die('Pesanan tidak ditemukan.');
}

// Query untuk mengambil nama meja berdasarkan meja_id
$queryMeja = $connection->prepare(
    "SELECT table_number FROM meja WHERE id = ?"
);
$queryMeja->bind_param('i', $pesanan['meja_id']);
$queryMeja->execute();
$resultMeja = $queryMeja->get_result();
$meja = $resultMeja->fetch_assoc();
$tableNumber = $meja ? $meja['table_number'] : 'Meja tidak ditemukan';

// Query untuk mengambil detail pesanan dan nama produk
$queryDetail = $connection->prepare(
    "SELECT dp.jumlah, dp.subtotal, pr.nama AS nama_produk 
     FROM detail_pemesanan dp 
     JOIN produk pr ON dp.id_produk = pr.id 
     WHERE dp.id_pemesanan = ?"
);
$queryDetail->bind_param('i', $orderId);
$queryDetail->execute();
$resultDetail = $queryDetail->get_result();
$detailPesanan = $resultDetail->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Struct Pesanan #<?= htmlspecialchars($pesanan['id']) ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
        }

        .struct-container {
            border: 1px solid #ddd;
            padding: 20px;
            margin: 0 auto;
            max-width: 600px;
            background: #fff;
        }

        h1,
        h2,
        h3 {
            margin: 0 0 15px;
        }

        .info {
            margin-bottom: 20px;
        }

        .info p {
            margin: 5px 0;
        }

        .text-center {
            text-align: center;
        }

        .btn-print {
            display: block;
            width: 100px;
            margin: 20px auto;
            padding: 10px;
            text-align: center;
            background: #007bff;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
        }

        .btn-print:hover {
            background: #0056b3;
        }

        @media print {
            .btn-print {
                display: none;
            }
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table th,
        table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        table th {
            background-color: #f4f4f4;
        }
    </style>
</head>

<body>
    <div class="struct-container" style="position: relative; padding: 20px;">
        <img src="../assets/img/Tea_Bliss_logo.png" alt="Logo" style="width: 120px; position: absolute; top: 20px; right: 20px;">
        <h1 class="text-center" style="margin-top: 0;">Struk Pesanan</h1>
        <h2 class="text-center">#<?= htmlspecialchars($pesanan['id']) ?></h2>
        <div class="info">
            <p><strong>Nama Pemesan:</strong> <?= htmlspecialchars($pesanan['nama_pemesan']) ?></p>
            <p><strong>Tanggal Pemesanan:</strong> <?= date('d M Y H:i', strtotime($pesanan['tanggal_pemesanan'])) ?></p>
            <p><strong>Nomor Meja:</strong> <?= htmlspecialchars($tableNumber) ?></p>
            <?php
            $status = htmlspecialchars($pesanan['status']);
            $info = htmlspecialchars($pesanan['info']);

            if ($status === 'canceled') {
                if (str_starts_with($info, 'user')) {
                    $status .= ' by User';
                }
            }
            ?>
            <p><strong>Status:</strong>
                <span class="badge <?= $badgeColor ?>"><?= $status ?></span><br>
            </p>
            <p><strong>Total Harga:</strong> Rp <?= number_format($pesanan['total_harga'], 0, ',', '.') ?></p>
        </div>
        <hr>
        <div class="info">
            <h3>Detail Pesanan:</h3>
            <table>
                <thead>
                    <tr>
                        <th>Nama Produk</th>
                        <th>Jumlah</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($detailPesanan as $detail) : ?>
                        <tr>
                            <td><?= htmlspecialchars($detail['nama_produk']) ?></td>
                            <td><?= htmlspecialchars($detail['jumlah']) ?></td>
                            <td>Rp <?= number_format($detail['subtotal'], 0, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <a href="#" class="btn-print" onclick="window.print()">Cetak</a>
    </div>
</body>

<?php
require_once '../includes/_bottom.php';
?>

</html>