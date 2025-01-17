<?php
require_once '../helper/connection.php';
require_once '../helper/auth.php';

// Pastikan hanya admin yang dapat mengakses
checkAdmin();

// Ambil parameter filter dari GET
$status = $_GET['status'] ?? '';
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';

// Query untuk mengambil data laporan
$query = "SELECT * FROM pemesanan WHERE 1=1";
$params = [];
$types = "";

if (!empty($startDate)) {
    $query .= " AND tanggal_pemesanan >= ?";
    $params[] = $startDate . " 00:00:00";
    $types .= "s";
}
if (!empty($endDate)) {
    $query .= " AND tanggal_pemesanan <= ?";
    $params[] = $endDate . " 23:59:59";
    $types .= "s";
}
if (!empty($status)) {
    $query .= " AND status = ?";
    $params[] = $status;
    $types .= "s";
}

$stmt = $connection->prepare($query);
if ($types !== "") {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$rows = $result->fetch_all(MYSQLI_ASSOC);

// Hitung total harga
$totalHarga = array_sum(array_column($rows, 'total_harga'));
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Laporan Penjualan</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
        }

        .container {
            margin: 0 auto;
            max-width: 800px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .header img {
            width: 100px;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
        }

        .header p {
            margin: 5px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
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

        .total {
            margin-top: 20px;
            text-align: right;
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
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <img src="../assets/img/Tea_Bliss_logo.png" alt="Logo">
            <h1>Laporan Penjualan</h1>
            <p>Periode: <?= htmlspecialchars($startDate) ?> s/d <?= htmlspecialchars($endDate) ?></p>
        </div>
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tanggal Pemesanan</th>
                    <th>ID Pesanan</th>
                    <th>Status</th>
                    <th>Total Harga</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)) : ?>
                    <tr>
                        <td colspan="5" class="text-center">Tidak ada data yang ditemukan.</td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($rows as $index => $row) : ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td><?= date('d M Y H:i', strtotime($row['tanggal_pemesanan'])) ?></td>
                            <td><?= htmlspecialchars($row['id']) ?></td>
                            <?php
                            $status = htmlspecialchars($row['status']);
                            $info = htmlspecialchars($row['info']);

                            if ($status === 'canceled') {
                                if (str_starts_with($info, 'user')) {
                                    $status .= ' by User';
                                }
                            }
                            ?>

                            <td><?= ucfirst(htmlspecialchars($status)) ?></td>
                            <td>Rp <?= number_format($row['total_harga'], 0, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <div class="total">
            <strong>Total Semua Pesanan: Rp <?= number_format($totalHarga, 0, ',', '.') ?></strong>
        </div>
        <a href="#" class="btn-print" onclick="window.print()">Cetak</a>
    </div>
</body>

</html>