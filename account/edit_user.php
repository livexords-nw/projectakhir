<?php
require_once '../includes/_top.php';
require_once '../helper/connection.php';
require_once '../helper/auth.php';
require_once '../helper/logger.php';

// Pastikan hanya admin yang dapat mengakses halaman ini
checkAdmin();

// Ambil ID user yang akan diedit dari URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['info'] = [
        'status' => 'error',
        'message' => 'ID pengguna tidak ditemukan.'
    ];
    header('Location: index.php');
    exit;
}

$userId = intval($_GET['id']);

// Ambil data user dari database
$query = "SELECT * FROM users WHERE id = ? AND role = 'user'";
$stmt = $connection->prepare($query);
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['info'] = [
        'status' => 'error',
        'message' => 'Pengguna tidak ditemukan atau bukan user biasa.'
    ];
    header('Location: index.php');
    exit;
}

$user = $result->fetch_assoc();

// Proses update data user jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);

    // Validasi input
    if (empty($username) || empty($email)) {
        $_SESSION['info'] = [
            'status' => 'error',
            'message' => 'Semua kolom harus diisi.'
        ];
    } else {
        // Update data user di database
        $updateQuery = "UPDATE users SET username = ?, email = ? WHERE id = ?";
        $updateStmt = $connection->prepare($updateQuery);
        $updateStmt->bind_param('ssi', $username, $email, $userId);

        if ($updateStmt->execute()) {
            $_SESSION['info'] = [
                'status' => 'success',
                'message' => 'Data pengguna berhasil diperbarui.'
            ];
            header('Location: index.php');
            exit;
        } else {
            $_SESSION['info'] = [
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat memperbarui data.'
            ];
        }
    }
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
    <div class="section-header">
        <h1>Edit Akun Pengguna</h1>
        <div class="section-header-breadcrumb">
            <div class="breadcrumb-item"><a href="index.php">Manajemen Akun</a></div>
            <div class="breadcrumb-item">Edit Akun</div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4>Form Edit Akun</h4>
                </div>
                <div class="card-body">
                    <form method="POST" action="" id="formedituser">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" name="username" id="username" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" name="email" id="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>
                        <button type="button" class="btn btn-primary" onclick="confirmEdit('formedituser')">Simpan Perubahan</button>
                        <a href="index.php" class="btn btn-danger">Batal</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once '../includes/_bottom.php'; ?>

<script>
    function confirmEdit(formId) {
        iziToast.question({
            timeout: false,
            close: false,
            overlay: true,
            displayMode: 'once',
            title: 'Konfirmasi Edit',
            message: 'Apakah Anda yakin ingin menyimpan perubahan pada Akun ini?',
            position: 'center',
            buttons: [
                ['<button>Ya</button>', function(instance, toast) {
                    document.getElementById(formId).submit();
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