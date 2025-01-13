<?php
require_once '../includes/_top.php';
require_once '../helper/connection.php';
require_once '../helper/auth.php';
require_once '../helper/logger.php';

// Pastikan hanya admin yang dapat mengakses halaman ini
checkAdmin();

// Ambil ID meja dari URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['info'] = [
        'status' => 'error',
        'message' => 'ID meja tidak ditemukan.'
    ];
    header('Location: index.php');
    exit;
}

$mejaId = intval($_GET['id']);

// Ambil data meja dari database
$query = "SELECT * FROM meja WHERE id = ?";
$stmt = $connection->prepare($query);
$stmt->bind_param('i', $mejaId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['info'] = [
        'status' => 'error',
        'message' => 'Data meja tidak ditemukan.'
    ];
    header('Location: index.php');
    exit;
}

$meja = $result->fetch_assoc();

// Proses update data meja jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $table_number = trim($_POST['table_number']);

    // Validasi input
    if (empty($table_number)) {
        $_SESSION['info'] = [
            'status' => 'error',
            'message' => 'Nomor meja harus diisi.'
        ];
    } else {
        // Cek apakah table_number sudah ada dan bukan milik meja ini
        $checkQuery = "SELECT table_number FROM meja WHERE table_number = ? AND id != ?";
        $stmt = $connection->prepare($checkQuery);
        $stmt->bind_param('si', $table_number, $mejaId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $existingTable = $result->fetch_assoc()['table_number'];
            $_SESSION['info'] = [
                'status' => 'error',
                'message' => "Nomor meja sudah terdaftar pada meja lain: {$existingTable}."
            ];
        } else {
            // Update data meja di database
            $updateQuery = "UPDATE meja SET table_number = ? WHERE id = ?";
            $updateStmt = $connection->prepare($updateQuery);
            $updateStmt->bind_param('si', $table_number, $mejaId);

            if ($updateStmt->execute()) {
                $_SESSION['info'] = [
                    'status' => 'success',
                    'message' => 'Data meja berhasil diperbarui.'
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
        <h1>Edit Data Meja</h1>
        <div class="section-header-breadcrumb">
            <div class="breadcrumb-item"><a href="index.php">Manajemen Meja</a></div>
            <div class="breadcrumb-item">Edit Meja</div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4>Form Edit Meja</h4>
                </div>
                <div class="card-body">
                    <form id="editForm" method="POST" action="edit_meja.php?id=<?= $mejaId ?>">
                        <div class="form-group">
                            <label for="table_number">Nomor Meja</label>
                            <input type="text" name="table_number" id="table_number" class="form-control" value="<?= htmlspecialchars($meja['table_number']) ?>" required>
                        </div>
                        <button type="button" class="btn btn-primary" onclick="confirmEdit('editForm')">Simpan Perubahan</button>
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
            message: 'Apakah Anda yakin ingin menyimpan perubahan pada meja ini?',
            position: 'center',
            buttons: [
                ['<button>Ya</button>', function(instance, toast) {
                    document.getElementById(formId).submit(); // Submit formulir dengan ID formId
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