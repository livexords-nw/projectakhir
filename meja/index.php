<?php
require_once '../includes/_top.php';
require_once '../helper/connection.php';
require_once '../helper/auth.php';
require_once '../helper/logger.php';

// Pastikan hanya admin yang dapat mengakses halaman ini
checkAdmin();

// Log aktivitas admin
if (isset($_SESSION['login']['username'])) {
    $username = $_SESSION['login']['username'];
}

// Fetch data meja dari database
$query = "SELECT * FROM meja ORDER BY id ASC";
$result = $connection->query($query);

// Hapus meja jika ada parameter delete_id
if (isset($_GET['delete_id'])) {
    $deleteId = intval($_GET['delete_id']);

    $deleteQuery = "DELETE FROM meja WHERE id = ?";
    $stmt = $connection->prepare($deleteQuery);
    $stmt->bind_param('i', $deleteId);

    if ($stmt->execute()) {
        $_SESSION['info'] = [
            'status' => 'success',
            'message' => 'Meja berhasil dihapus.'
        ];
    } else {
        $_SESSION['info'] = [
            'status' => 'error',
            'message' => 'Gagal menghapus meja.'
        ];
    }

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
?>

<section class="section">
    <div class="section-header">
        <h1>Manajemen Meja</h1>
        <div class="section-header-breadcrumb">
            <div class="breadcrumb-item active">Dashboard</div>
            <div class="breadcrumb-item">Manajemen Meja</div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4>Daftar Meja</h4>
                    <div class="card-header-action">
                        <a href="add_meja.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Tambah Meja Baru
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Tabel Data Meja -->
                    <div class="table-responsive" id="meja-list">
                        <table id="table-1" class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-id-card"></i> ID</th>
                                    <th><i class="fas fa-chair"></i> Nomor Meja</th>
                                    <th><i class="fas fa-cogs"></i> Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result->num_rows > 0): ?>
                                    <?php while ($meja = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($meja['id']) ?></td>
                                            <td><?= htmlspecialchars($meja['table_number']) ?></td>
                                            <td>
                                                <a href="edit_meja.php?id=<?= $meja['id'] ?>" class="btn btn-info btn-sm">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                                <a href="javascript:void(0);" class="btn btn-danger btn-sm" onclick="confirmDelete(<?= $meja['id'] ?>)">
                                                    <i class="fas fa-trash"></i> Hapus
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center">Tidak ada meja yang ditemukan.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    function confirmDelete(id) {
        iziToast.question({
            timeout: false,
            close: false,
            overlay: true,
            displayMode: 'once',
            title: 'Konfirmasi Hapus',
            message: 'Apakah Anda yakin ingin menghapus meja ini?',
            position: 'center',
            buttons: [
                ['<button>Ya</button>', function(instance, toast) {
                    window.location.href = `index.php?delete_id=${id}`;
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

    document.addEventListener("DOMContentLoaded", function() {
        $('#table-1').DataTable({
            responsive: true,
            ordering: true,
            columnDefs: [{
                orderable: false,
                targets: [-1]
            }]
        });
    });
</script>

<?php require_once '../includes/_bottom.php'; ?>