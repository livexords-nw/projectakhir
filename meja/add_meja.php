<?php
require_once '../includes/_top.php';
require_once '../helper/connection.php';
require_once '../helper/auth.php';
require_once '../helper/logger.php';

// Pastikan hanya admin yang dapat mengakses halaman ini
checkAdmin();

// Proses penambahan meja jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $table_number = trim($_POST['table_number']);

    // Validasi input
    if (empty($table_number)) {
        $_SESSION['info'] = [
            'status' => 'error',
            'message' => 'Nomor meja harus diisi.'
        ];
    } else {
        // Cek apakah table_number sudah ada
        $checkQuery = "SELECT table_number FROM meja WHERE table_number = ?";
        $stmt = $connection->prepare($checkQuery);
        $stmt->bind_param('s', $table_number);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $existingTable = $result->fetch_assoc()['table_number'];
            $_SESSION['info'] = [
                'status' => 'error',
                'message' => "Nomor meja sudah terdaftar: {$existingTable}."
            ];
        } else {
            // Insert data meja ke database
            $insertQuery = "INSERT INTO meja (table_number) VALUES (?)";
            $stmt = $connection->prepare($insertQuery);
            $stmt->bind_param('s', $table_number);

            if ($stmt->execute()) {
                $_SESSION['info'] = [
                    'status' => 'success',
                    'message' => "Meja baru berhasil ditambahkan! Nomor Meja: {$table_number}."
                ];
                header('Location: index.php');
                exit;
            } else {
                $_SESSION['info'] = [
                    'status' => 'error',
                    'message' => 'Terjadi kesalahan saat menambahkan meja.'
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
        <h1>Tambah Data Meja</h1>
        <div class="section-header-breadcrumb">
            <div class="breadcrumb-item"><a href="index.php">Manajemen Meja</a></div>
            <div class="breadcrumb-item">Tambah Meja</div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4>Form Tambah Meja Baru</h4>
                </div>
                <div class="card-body">
                    <form id="addForm" method="POST" action="add_meja.php">
                        <div class="form-group">
                            <label for="table_number">Nomor Meja</label>
                            <input type="text" name="table_number" id="table_number" class="form-control" required>
                        </div>
                        <button type="button" class="btn btn-primary" onclick="confirmAdd('addForm')">Simpan</button>
                        <a href="index.php" class="btn btn-danger">Batal</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once '../includes/_bottom.php'; ?>

<script>
    function confirmAdd(formId) {
        iziToast.question({
            timeout: false,
            close: false,
            overlay: true,
            displayMode: 'once',
            title: 'Konfirmasi Tambah',
            message: 'Apakah Anda yakin ingin menambahkan meja ini?',
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