<?php
require_once 'helper/connection.php';
session_start();

// Fungsi untuk memvalidasi OTP dan token
function isValidTokenAndOtp($token, $otp, $connection)
{
    $query = "SELECT * FROM users WHERE token='$token' AND otp_code='$otp' LIMIT 1";
    $result = mysqli_query($connection, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);

        // Cek jika OTP masih dalam batas waktu 5 menit
        $otpCreatedAt = strtotime($row['otp_created_at']);
        $currentTime = time();

        if (($currentTime - $otpCreatedAt) <= 300) { // 300 detik = 5 menit
            return $row;
        }
    }

    return false;
}

// Validasi permintaan
if (isset($_GET['token']) && isset($_GET['otp'])) {
    $token = mysqli_real_escape_string($connection, $_GET['token']);
    $otp = mysqli_real_escape_string($connection, $_GET['otp']);

    // Validasi token dan OTP
    $user = isValidTokenAndOtp($token, $otp, $connection);

    if ($user) {
        if (isset($_POST['submit'])) {
            $newPassword = mysqli_real_escape_string($connection, $_POST['password']);
            $confirmPassword = mysqli_real_escape_string($connection, $_POST['repeat_password']);

            // Validasi password
            if ($newPassword === $confirmPassword) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

                // Update password di database
                $updatePasswordQuery = "UPDATE users SET password='$hashedPassword', token=NULL, otp_code=NULL, otp_created_at=NULL WHERE id='{$user['id']}'";
                if (mysqli_query($connection, $updatePasswordQuery)) {
                    $_SESSION['info'] = [
                        'status' => 'success',
                        'message' => 'Password berhasil diubah! Silakan login dengan password baru.'
                    ];
                    header('Location: login.php');
                    exit();
                } else {
                    $_SESSION['info'] = [
                        'status' => 'error',
                        'message' => 'Terjadi kesalahan saat memperbarui password.'
                    ];
                }
            } else {
                $_SESSION['info'] = [
                    'status' => 'error',
                    'message' => 'Password tidak cocok. Silakan coba lagi.'
                ];
            }
        }
    } else {
        $_SESSION['info'] = [
            'status' => 'error',
            'message' => 'Token atau OTP tidak valid atau telah kedaluwarsa.'
        ];
        header('Location: lupa_password.php');
        exit();
    }
} else {
    header('Location: lupa_password.php');
    exit();
}

// iziToast Notifikasi
if (isset($_SESSION['info'])) {
    $info = $_SESSION['info'];
    $status = $info['status'];
    $message = $info['message'];

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
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport">
    <title>Reset Password</title>

    <!-- Link Logo -->
    <link rel="icon" href="assets/img/favicon_io/Tea_Bliss_logo-32x32.png" type="image/x-icon">

    <!-- General CSS Files -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.2/css/all.css">

    <!-- Template CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/components.css">

    <!-- iziToast CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/css/iziToast.min.css">
</head>

<body>
    <div id="app">
        <section class="section">
            <div class="container mt-5">
                <div class="row">
                    <div class="col-12 col-sm-8 offset-sm-2 col-md-6 offset-md-3 col-lg-6 offset-lg-3 col-xl-4 offset-xl-4">
                        <div class="login-brand text-center mb-3">
                            <img src="assets/img/Tea_Bliss_logo.png" alt="logo" class="img-fluid" style="max-width: 150px; height: auto;">
                        </div>
                        <div class="card card-primary">
                            <div class="card-header">
                                <h4>Reset Password</h4>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="" class="needs-validation" novalidate>
                                    <div class="form-group">
                                        <label for="password">Password Baru</label>
                                        <div class="input-group">
                                            <input id="password" type="password" class="form-control" name="password" required>
                                            <div class="input-group-append">
                                                <button type="button" class="btn btn-outline-secondary" id="toggle-password">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="invalid-feedback">Mohon isi password baru</div>
                                    </div>
                                    <div class="form-group">
                                        <label for="repeat_password">Ulangi Password</label>
                                        <input id="repeat_password" type="password" class="form-control" name="repeat_password" required>
                                        <div class="invalid-feedback">Mohon ulangi password baru</div>
                                    </div>
                                    <div class="form-group">
                                        <button type="submit" name="submit" class="btn btn-primary btn-lg btn-block">Reset Password</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- General JS Scripts -->
    <script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>

    <!-- iziToast JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/js/iziToast.min.js"></script>

    <!-- Toggle Password Visibility -->
    <script>
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
</body>

</html>