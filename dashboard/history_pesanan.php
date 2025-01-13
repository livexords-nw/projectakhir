<?php
require_once '../includes/_top.php';
require_once '../helper/connection.php';
require_once '../helper/auth.php';

isLogin();

// Pastikan pengguna sedang login dan ambil user_id dari sesi
if (isset($_SESSION['login']['id'])) {
    $userId = $_SESSION['login']['id'];
} else {
    die('User tidak ditemukan');
}

// Query untuk mengambil data pemesanan berdasarkan user_id
$query = "
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
        m.table_number
    FROM pemesanan p
    LEFT JOIN meja m ON p.meja_id = m.id
    WHERE p.user_id = ?
    ORDER BY p.tanggal_pemesanan DESC
";

// Persiapkan statement untuk mencegah SQL injection
$stmt = mysqli_prepare($connection, $query);
mysqli_stmt_bind_param($stmt, 'i', $userId);
mysqli_stmt_execute($stmt);

// Ambil hasil query
$result = mysqli_stmt_get_result($stmt);

// Validasi apakah query berhasil dijalankan
if (!$result) {
    die("Query gagal: " . mysqli_error($connection));
}
?>

<section class="section">
    <div class="section-header">
        <h1><i class="fas fa-history"></i> History Pemesanan</h1>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <!-- Tambahkan wrapper untuk scroll horizontal -->
            <div class="table-responsive">
                <table id="historyTable" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th><i class="fas fa-id-card"></i> ID</th>
                            <th><i class="fas fa-user"></i> Nama Pemesan</th>
                            <th><i class="fas fa-calendar-alt"></i> Tanggal Pemesanan</th>
                            <th><i class="fas fa-money-bill-wave"></i> Total Harga</th>
                            <th><i class="fas fa-bell"></i> Status</th>
                            <th><i class="fas fa-utensils"></i> Nomor Meja</th>
                            <th><i class="fas fa-clock"></i> Booking Mulai</th>
                            <th><i class="fas fa-clock"></i> Booking Selesai</th>
                            <th><i class="fas fa-paperclip"></i> Bukti Pembayaran</th>
                            <th><i class="fas fa-info-circle"></i> Info</th>
                            <th><i class="fas fa-search"></i> Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($result) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['id']) ?></td>
                                    <td><?= htmlspecialchars($row['nama_pemesan']) ?></td>
                                    <td><?= htmlspecialchars($row['tanggal_pemesanan']) ?></td>
                                    <td>Rp <?= number_format($row['total_harga'], 0, ',', '.') ?></td>
                                    <td>
                                        <?php
                                        $status = htmlspecialchars($row['status']);
                                        $badgeColor = match ($status) {
                                            'pending' => 'badge-warning',
                                            'canceled' => 'badge-danger',
                                            'completed' => 'badge-success',
                                            default => 'badge-secondary',
                                        };
                                        ?>
                                        <span class="badge <?= $badgeColor ?>"><?= $status ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($row['table_number']) ?: 'N/A' ?></td>
                                    <td><?= htmlspecialchars($row['booking_start']) ?></td>
                                    <td><?= htmlspecialchars($row['booking_end']) ?></td>
                                    <td>
                                        <?php if (!empty($row['payment_proof'])): ?>
                                            <a href="<?= '../bukti_pembayaran/' . htmlspecialchars($row['payment_proof']) ?>" target="_blank" class="btn btn-success btn-sm"><i class="fas fa-eye"></i> Lihat</a>
                                        <?php else: ?>
                                            <span class="text-muted"><i class="fas fa-times"></i> Tidak Ada</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($row['info']) ?: '-' ?></td>
                                    <td>
                                        <a href="detail_pesanan.php?id=<?= $row['id'] ?>" class="btn btn-info btn-sm"><i class="fas fa-search"></i> Lihat Detail</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="11" class="text-center"><i class="fas fa-info-circle"></i> Belum ada history pemesanan untuk Anda.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<?php require_once '../includes/_bottom.php'; ?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link rel="stylesheet" href="../assets/modules/datatables/datatables.min.css">
<script src="../assets/modules/datatables/datatables.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        $('#historyTable').DataTable({
            language: {
                url: "../assets/modules/datatables/Indonesian.json"
            },
            order: [
                [2, 'desc']
            ] // Mengatur kolom tanggal pemesanan (index 2) sebagai urutan default
        });
    });
</script>
<style>
    .badge-warning {
        background-color: #ffc107;
        color: #212529;
    }

    .badge-danger {
        background-color: #dc3545;
        color: #fff;
    }

    .badge-success {
        background-color: #28a745;
        color: #fff;
    }

    .badge-secondary {
        background-color: #6c757d;
        color: #fff;
    }
</style>