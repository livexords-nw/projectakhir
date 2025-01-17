<?php
require_once '../includes/_top.php';
require_once '../helper/connection.php';
require_once '../helper/auth.php';
isLogin();

if ($_SESSION['role'] !== 'admin') {
    header('Location: ./index.php');
    exit;
}

date_default_timezone_set('Asia/Jakarta');

// Fungsi untuk mengambil data pemesanan berdasarkan filter
function getPemesananAndTotal($conn, $start_date, $end_date, $status, $start_time, $end_time)
{
    $query = "SELECT * FROM pemesanan WHERE 1=1";

    $params = [];
    $types = "";

    if (!empty($start_date)) {
        $query .= " AND tanggal_pemesanan >= ?";
        $params[] = $start_date . " " . $start_time;
        $types .= "s";
    }

    if (!empty($end_date)) {
        $query .= " AND tanggal_pemesanan <= ?";
        $params[] = $end_date . " " . $end_time;
        $types .= "s";
    }

    if (!empty($status)) {
        $query .= " AND status = ?";
        $params[] = $status;
        $types .= "s";
    }

    $stmt = $conn->prepare($query);

    if (!$stmt) {
        die("Query Error: " . $conn->error);
    }

    if ($types !== "") {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $totalAmount = 0;
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $totalAmount += $row['total_harga'];
        $rows[] = $row;
    }

    return [$rows, $totalAmount];
}

// Mendapatkan parameter filter
$status = $_GET['status'] ?? '';
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';
$startTime = $_GET['start_time'] ?? '00:00';
$endTime = $_GET['end_time'] ?? '23:59';

list($filteredPemesanan, $totalAmount) = getPemesananAndTotal($connection, $startDate, $endDate, $status, $startTime, $endTime);
?>

<section class="section">
    <div class="section-header">
        <h1> Laporan Penjualan</h1>
    </div>
    <div class="card">
        <div class="card-body">
            <form method="get" class="form-inline mb-3">
                <div class="form-group mr-2">
                    <label for="start_date">Dari Tanggal:</label>
                    <input type="date" name="start_date" id="start_date" class="form-control ml-2" value="<?= htmlspecialchars($startDate); ?>">
                </div>
                <div class="form-group mr-2">
                    <label for="end_date">Hingga Tanggal:</label>
                    <input type="date" name="end_date" id="end_date" class="form-control ml-2" value="<?= htmlspecialchars($endDate); ?>">
                </div>
                <div class="form-group mr-2">
                    <label for="status">Status:</label>
                    <select name="status" id="status" class="form-control ml-2">
                        <option value="">Semua</option>
                        <option value="approved" <?= $status === 'approved' ? 'selected' : ''; ?>>approved</option>
                        <option value="canceled" <?= $status === 'canceled' ? 'selected' : ''; ?>>Dibatalkan</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
                <a href="cetak_laporan.php?start_date=<?= htmlspecialchars($startDate) ?>&end_date=<?= htmlspecialchars($endDate) ?>&status=<?= htmlspecialchars($status) ?>"
                    class="btn btn-danger ml-3" target="_blank"><i class="fas fa-print"></i> Cetak Laporan</a>
            </form>

            <div id="laporan">
                <h3 class="text-center">Laporan Penjualan</h3>
                <p class="text-center">Periode: <?= htmlspecialchars($startDate); ?> s/d <?= htmlspecialchars($endDate); ?></p>
                <p><b>Total Pesanan: Rp<?= number_format($totalAmount, 2, ',', '.'); ?></b></p>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th><i class="fas fa-calendar-alt"></i> Tanggal Pemesanan</th>
                            <th><i class="fas fa-receipt"></i> ID Pemesanan</th>
                            <th><i class="fas fa-money-bill-wave"></i> Total Harga</th>
                            <th><i class="fas fa-clipboard-list"></i> Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($filteredPemesanan)): ?>
                            <tr>
                                <td colspan="4" class="text-center">Tidak ada data yang ditemukan.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($filteredPemesanan as $index => $data): ?>
                                <tr>
                                    <td><?= $index + 1; ?></td>
                                    <td><?= htmlspecialchars($data['tanggal_pemesanan']); ?></td>
                                    <td><?= htmlspecialchars($data['id']); ?></td>
                                    <td>Rp<?= number_format($data['total_harga'], 2, ',', '.'); ?></td>
                                    <?php
                                    $status = htmlspecialchars($data['status']);
                                    $info = htmlspecialchars($data['info']); // Ambil kolom info
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
                                    <td><span class="badge <?= $badgeColor ?>"><?= $status ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<!-- CSS khusus untuk cetak -->
<style>
    @media print {

        .section-header,
        .form-inline,
        .btn {
            display: none;
        }

        .card {
            box-shadow: none;
            border: none;
        }

        .text-center {
            text-align: center;
            margin: 0;
            padding: 0;
        }

        .table {
            border-collapse: collapse;
            width: 100%;
        }

        .table th,
        .table td {
            border: 1px solid black;
            padding: 8px;
        }
    }
</style>

<?php require_once '../includes/_bottom.php'; ?>