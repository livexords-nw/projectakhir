<?php
session_start();
require_once 'helper/connection.php';

if (isset($_GET['token'])) {
    $token = trim($_GET['token']);
} else {
    $_SESSION['info'] = [
        'status' => 'danger',
        'message' => 'Token tidak ditemukan!'
    ];
    header("Location: error_page.php"); // Redirect ke halaman error jika token tidak ada
    exit;
}

if (isset($_POST['submit'])) {
    $otp = trim($_POST['otp']);

    if (empty($otp)) {
        $_SESSION['info'] = [
            'status' => 'danger',
            'message' => 'Kode OTP harus diisi!'
        ];
    } elseif (!preg_match('/^\d{6}$/', $otp)) {
        $_SESSION['info'] = [
            'status' => 'danger',
            'message' => 'Kode OTP harus terdiri dari 6 digit angka!'
        ];
    } else {
        $checkOtpQuery = "
            SELECT id, otp_code, otp_created_at 
            FROM users 
            WHERE token = ? AND otp_code = ?";
        $stmt = mysqli_prepare($connection, $checkOtpQuery);
        mysqli_stmt_bind_param($stmt, 'ss', $token, $otp);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        mysqli_stmt_bind_result($stmt, $userId, $dbOtpCode, $otpCreatedAt);

        if (mysqli_stmt_num_rows($stmt) > 0) {
            mysqli_stmt_fetch($stmt);

            // Periksa apakah OTP masih berlaku (5 menit)
            $currentTimestamp = strtotime(date('Y-m-d H:i:s'));
            $otpTimestamp = strtotime($otpCreatedAt);
            $validityPeriod = 5 * 60; // 5 menit dalam detik

            if (($currentTimestamp - $otpTimestamp) <= $validityPeriod) {
                // Update user sebagai terverifikasi
                $updateQuery = "
                    UPDATE users 
                    SET email_verified = 1, otp_code = NULL, token = NULL, otp_created_at = NULL 
                    WHERE id = ?";
                $stmtUpdate = mysqli_prepare($connection, $updateQuery);
                mysqli_stmt_bind_param($stmtUpdate, 'i', $userId);

                if (mysqli_stmt_execute($stmtUpdate)) {
                    $_SESSION['info'] = [
                        'status' => 'success',
                        'message' => 'Email berhasil diverifikasi! Anda dapat login sekarang.'
                    ];
                    header("Location: login.php");
                    exit;
                } else {
                    $_SESSION['info'] = [
                        'status' => 'danger',
                        'message' => 'Terjadi kesalahan teknis saat memverifikasi email!'
                    ];
                }
                mysqli_stmt_close($stmtUpdate);
            } else {
                $_SESSION['info'] = [
                    'status' => 'danger',
                    'message' => 'Kode OTP sudah kedaluwarsa. Mohon minta kode baru.'
                ];
            }
        } else {
            $_SESSION['info'] = [
                'status' => 'danger',
                'message' => 'Kode OTP salah atau token tidak valid!'
            ];
        }

        mysqli_stmt_close($stmt);
    }
}

$info = isset($_SESSION['info']) ? $_SESSION['info'] : null;
if ($info) {
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
    <title>Verifikasi OTP</title>

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

    <!-- iziToast JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/js/iziToast.min.js"></script>
</head>

<body>
    <div id="app">
        <section class="section">
            <div class="container mt-5">
                <div class="row">
                    <div class="col-12 col-sm-10 offset-sm-1 col-md-8 offset-md-2 col-lg-6 offset-lg-3 col-xl-4 offset-xl-4">
                        <div class="login-brand text-center mb-4">
                            <img src="./assets/img/Tea_Bliss_logo.png" alt="logo" class="img-fluid" style="max-width: 150px; height: auto;">
                        </div>
                        <div class="card card-primary">
                            <div class="card-header">
                                <h4>Verifikasi OTP</h4>
                            </div>

                            <div class="card-body">
                                <form method="POST" action="" class="needs-validation" novalidate="">
                                    <div class="form-group">
                                        <label for="otp">Kode OTP</label>
                                        <div id="otp-container" style="display: flex; justify-content: space-between;">
                                            <!-- Input untuk 6 digit OTP -->
                                            <?php for ($i = 1; $i <= 6; $i++): ?>
                                                <input type="text" maxlength="1" class="form-control otp-input" style="width: 45px; text-align: center; margin-right: 5px;" oninput="moveToNext(this, <?= $i ?>)" required>
                                            <?php endfor; ?>
                                        </div>
                                        <div class="invalid-feedback">
                                            Mohon isi kode OTP Anda
                                        </div>
                                        <input type="hidden" id="otp" name="otp">
                                    </div>

                                    <div class="form-group">
                                        <button type="submit" name="submit" class="btn btn-primary btn-lg btn-block" tabindex="3">
                                            Verifikasi
                                        </button>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>

    <script>
        // Script untuk memindahkan fokus antar kolom OTP
        function moveToNext(input, index) {
            const value = input.value;
            const otp = document.getElementById('otp');
            let currentValue = otp.value || '';

            // Tambahkan angka pada posisi index
            currentValue = currentValue.substring(0, index - 1) + value + currentValue.substring(index);
            otp.value = currentValue;

            if (value && input.nextElementSibling) {
                input.nextElementSibling.focus();
            }
        }
    </script>
</body>

<?php
require_once 'includes/_bottom.php';
?>

</html>