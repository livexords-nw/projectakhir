<?php
require_once '../includes/_top.php';
require_once '../helper/connection.php';
require_once '../helper/auth.php';
require_once '../helper/logger.php';

// Pastikan hanya admin yang dapat mengakses halaman ini
checkAdmin();

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

// Log aktivitas admin
if (isset($_SESSION['login']['username'])) {
    $username = $_SESSION['login']['username'];
}

// Fetch data akun dari database dengan role 'user' saja dan urutkan berdasarkan ID
$query = "SELECT * FROM users WHERE role = 'user' ORDER BY id ASC";
$result = $connection->query($query);

if (isset($_GET['delete_id'])) {
    $deleteId = intval($_GET['delete_id']);

    $deleteQuery = "DELETE FROM users WHERE id = ?";
    $stmt = $connection->prepare($deleteQuery);
    $stmt->bind_param('i', $deleteId);

    if ($stmt->execute()) {
        $_SESSION['info'] = [
            'status' => 'success',
            'message' => 'Akun berhasil dihapus.'
        ];
    } else {
        $_SESSION['info'] = [
            'status' => 'error',
            'message' => 'Gagal menghapus akun.'
        ];
    }

    header('Location: account_dashboard.php');
    exit;
}

?>

<section class="section">
    <div class="section-header">
        <h1>Manajemen Akun Pengguna</h1>
        <div class="section-header-breadcrumb">
            <div class="breadcrumb-item active">Dashboard</div>
            <div class="breadcrumb-item">Manajemen Akun</div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4>Daftar Akun</h4>
                    <div class="card-header-action">
                        <a href="add_user.php" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i> Tambah Akun Baru
                        </a>
                    </div>
                </div>
                <div class="card-body">

                    <!-- Tabel Data Akun -->
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive" id="user-list">
                                <table id="table-1" class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th><i class="fas fa-id-card"></i> ID</th>
                                            <th><i class="fas fa-user"></i> Username</th>
                                            <th><i class="fas fa-envelope"></i> Email</th>
                                            <th><i class="fas fa-user-tag"></i> Role</th>
                                            <th><i class="fas fa-calendar-alt"></i> Created At</th>
                                            <th><i class="fas fa-cogs"></i> Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($result->num_rows > 0): ?>
                                            <?php while ($user = $result->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($user['id']) ?></td>
                                                    <td><?= htmlspecialchars($user['username']) ?></td>
                                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                                    <td><?= htmlspecialchars($user['role']) ?></td>
                                                    <td><?= htmlspecialchars($user['created_at']) ?></td>
                                                    <td>
                                                        <a href="edit_user.php?id=<?= $user['id'] ?>" class="btn btn-info btn-sm">
                                                            <i class="fas fa-edit"></i> Edit
                                                        </a>
                                                        <a href="javascript:void(0);" class="btn btn-danger btn-sm" onclick="confirmDelete(<?= $user['id'] ?>)">
                                                            <i class="fas fa-trash"></i> Hapus
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center">Tidak ada akun yang ditemukan.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>


                </div>
            </div>
        </div>
    </div>
</section>

<!-- Script Live Search -->
<script>
    document.getElementById('search-bar').addEventListener('input', function() {
        const searchValue = this.value.trim();
        const userList = document.getElementById('user-list');

        // Request ke server
        const xhr = new XMLHttpRequest();
        xhr.open('GET', `live_search_user.php?search=${encodeURIComponent(searchValue)}`, true);
        xhr.onload = function() {
            if (xhr.status === 200) {
                userList.innerHTML = xhr.responseText;
            } else {
                console.error('Gagal memuat hasil pencarian.');
            }
        };
        xhr.send();
    });
</script>

<?php require_once '../includes/_bottom.php'; ?>

<script>
    function confirmDelete(id) {
        iziToast.question({
            timeout: false,
            close: false,
            overlay: true,
            displayMode: 'once',
            title: 'Konfirmasi Hapus',
            message: 'Apakah Anda yakin ingin menghapus akun ini?',
            position: 'center',
            buttons: [
                ['<button>Ya</button>', function(instance, toast) {
                    window.location.href = `account_dashboard.php?delete_id=${id}`;
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
                targets: [-1, -2]
            }]
        });
    });
</script>