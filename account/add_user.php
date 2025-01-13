<?php
require_once '../includes/_top.php';
require_once '../helper/connection.php';
require_once '../helper/auth.php';
require_once '../helper/logger.php';

// Pastikan hanya admin yang dapat mengakses halaman ini
checkAdmin();

// Proses pembuatan akun baru jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $role = 'user';  // Default role for new users

    // Validasi input
    $errors = [];

    if (empty($username)) {
        $errors[] = 'Username tidak boleh kosong.';
    } elseif (strlen($username) < 3 || strlen($username) > 20) {
        $errors[] = 'Username harus antara 3 hingga 20 karakter.';
    }

    if (empty($email)) {
        $errors[] = 'Email tidak boleh kosong.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Format email tidak valid.';
    }

    if (empty($password)) {
        $errors[] = 'Password tidak boleh kosong.';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Password harus memiliki setidaknya 8 karakter.';
    }

    if (count($errors) > 0) {
        $_SESSION['info'] = [
            'status' => 'error',
            'message' => $errors
        ];
    } else {
        // Enkripsi password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Cek apakah email sudah terdaftar
        $checkEmailQuery = "SELECT * FROM users WHERE email = ?";
        $stmt = $connection->prepare($checkEmailQuery);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $_SESSION['info'] = [
                'status' => 'error',
                'message' => 'Email sudah terdaftar. Coba gunakan email lain yang belum terdaftar.'
            ];
        } else {
            // Insert data akun baru ke database
            $insertQuery = "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)";
            $insertStmt = $connection->prepare($insertQuery);
            $insertStmt->bind_param('ssss', $username, $email, $hashedPassword, $role);

            if ($insertStmt->execute()) {
                $_SESSION['info'] = [
                    'status' => 'success',
                    'message' => "Akun berhasil dibuat! Username: {$username}."
                ];
                header('Location: index.php');
                exit;
            } else {
                $_SESSION['info'] = [
                    'status' => 'error',
                    'message' => 'Terjadi kesalahan saat membuat akun.'
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
    $message = is_array($info['message']) ? implode(' | ', $info['message']) : $info['message'];

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
        <h1>Tambah Akun Pengguna</h1>
        <div class="section-header-breadcrumb">
            <div class="breadcrumb-item"><a href="index.php">Manajemen Akun</a></div>
            <div class="breadcrumb-item">Tambah Akun</div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4>Form Tambah Akun Baru</h4>
                </div>
                <div class="card-body">
                    <form method="POST" action="" id="formAdd">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" name="username" id="username" class="form-control" required tabindex="1">
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" name="email" id="email" class="form-control" required tabindex="2">
                        </div>
                        <div class="form-group">
                            <label for="password">Password</label>
                            <div class="input-group">
                                <input id="password" type="password" class="form-control" name="password" required tabindex="3">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-secondary" id="toggle-password" tabindex="-1">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary" tabindex="4" onclick="confirmAdd('formAdd')">Simpan Akun</button>
                        <a href="index.php" class="btn btn-danger" tabindex="5">Batal</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once '../includes/_bottom.php'; ?>

<script>
    // Toggle Password Visibility
    document.getElementById('toggle-password').addEventListener('click', function() {
        const passwordInput = document.getElementById('password');
        const icon = this.querySelector('i');
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });
</script>

<script>
    function confirmAdd(formId) {
        iziToast.question({
            timeout: false,
            close: false,
            overlay: true,
            displayMode: 'once',
            title: 'Konfirmasi Tambah',
            message: 'Apakah Anda yakin ingin menambahkan akun ini?',
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