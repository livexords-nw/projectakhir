<?php
require_once 'helper/connection.php';

// Include PHPMailer
require 'libs/PHPMailer/src/PHPMailer.php';
require 'libs/PHPMailer/src/SMTP.php';
require 'libs/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();

// Fungsi untuk mengirim email dengan OTP dan link
function sendOtpEmail($email, $verificationUrl)
{
    $smtpCredentials = [];

    $maxRetries = 5; // Maksimum percobaan pengiriman
    $attempt = 0;

    while ($attempt < $maxRetries) {
        // Pilih kredensial berdasarkan percobaan ke-berapa
        $currentCred = $smtpCredentials[$attempt % count($smtpCredentials)];

        $mail = new PHPMailer(true);
        try {
            // Konfigurasi SMTP
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = $currentCred['username']; // Menggunakan kredensial dinamis
            $mail->Password = $currentCred['password']; // Menggunakan kredensial dinamis
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Pengirim dan penerima
            $mail->setFrom($currentCred['username'], 'Tea Bliss');
            $mail->addAddress($email);

            // Isi email
            $mail->isHTML(true);
            $mail->Subject = "Reset Password Akun Anda";
            $mail->Body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
                    <div style='text-align: center;'>
                        <img src='https://raw.githubusercontent.com/livexords-nw/projectakhir/main/assets/img/Tea_Bliss_logo.png' alt='Tea Bliss Logo' style='width: 200px;'>
                        <p style='color: #555;'>Klik tombol di bawah untuk mereset password akun Anda:</p>
                        <div style='text-align: center; margin-top: 30px;'>
                            <a href='$verificationUrl' style='background-color: #4CAF50; color: white; text-decoration: none; padding: 10px 20px; border-radius: 5px; font-size: 14px;'>Reset Password</a>
                        </div>
                        <p style='text-align: center; font-size: 14px; color: #555;'>Jika Anda tidak meminta reset password, abaikan email ini. Link hanya berlaku selama 5 menit.</p>
                    </div>
                </div>";
            $mail->AltBody = "Klik link berikut untuk mereset password akun Anda: $verificationUrl\nLink hanya berlaku selama 5 menit.";

            $mail->send();
            return true; // Jika berhasil, keluar dari loop dan return true
        } catch (Exception $e) {
            error_log("Email gagal dikirim menggunakan {$currentCred['username']}. Error: {$mail->ErrorInfo}");

            // Jika percobaan masih gagal, tunggu 3 detik dan coba lagi dengan kredensial berikutnya
            sleep(3);
            $attempt++;
        }
    }

    // Jika mencapai jumlah percobaan maksimal dan semua gagal
    return false;
}

if (isset($_POST['submit'])) {
    $username = mysqli_real_escape_string($connection, $_POST['username']);

    // Query untuk memeriksa username
    $sql = "SELECT * FROM users WHERE username='$username' LIMIT 1";
    $result = mysqli_query($connection, $sql);
    $row = mysqli_fetch_assoc($result);

    if ($row) {
        // Generate token dan expired time
        $token = bin2hex(random_bytes(16));
        $otp = rand(100000, 999999);
        $otpCreatedAt = date('Y-m-d H:i:s');

        // Simpan token dan OTP ke database
        $updateOTP = "UPDATE users SET token='$token', otp_code='$otp', otp_created_at='$otpCreatedAt' WHERE username='{$row['username']}'";
        mysqli_query($connection, $updateOTP);

        // Ambil nama domain
        $host = $_SERVER['HTTP_HOST'];

        // Buat link verifikasi
        $verificationUrl = "http://$host/projectakhir_esteh/verify_change_password.php?token=$token&otp=$otp";

        // Kirim email
        if (sendOtpEmail($row['email'], $verificationUrl)) {
            $_SESSION['info'] = [
                'status' => 'success',
                'message' => "Link reset password telah dikirim. Silakan periksa email Anda."
            ];
        } else {
            $_SESSION['info'] = [
                'status' => 'error',
                'message' => "Gagal mengirim email. Silakan coba lagi."
            ];
        }
    } else {
        $_SESSION['info'] = [
            'status' => 'error',
            'message' => "Username tidak ditemukan."
        ];
    }

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
    <title>Tea Bliss</title>

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
                    <div class="col-12 col-sm-8 offset-sm-2 col-md-6 offset-md-3 col-lg-6 offset-lg-3 col-xl-4 offset-xl-4">
                        <div class="login-brand text-center mb-3">
                            <img src="./assets/img/Tea_Bliss_logo.png" alt="logo" class="img-fluid" style="max-width: 150px; height: auto;">
                        </div>
                        <div class="card card-primary">
                            <div class="card-header">
                                <h4>Lupa password</h4>
                            </div>

                            <div class="card-body">
                                <form method="POST" action="" class="needs-validation" novalidate="">
                                    <div class="form-group">
                                        <label for="username">Username</label>
                                        <input id="username" type="text" class="form-control" name="username" tabindex="1" required autofocus>
                                        <div class="invalid-feedback">
                                            Mohon isi username
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <button name="submit" type="submit" class="btn btn-primary btn-lg btn-block" tabindex="3">
                                            Reset Password
                                        </button>
                                    </div>
                                </form>
                                <div class="mt-3 text-center">
                                    Tidak jadi reset password? <a href="login.php">Klik di sini untuk login</a>
                                </div>
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
</body>

<?php
require_once 'includes/_bottom.php';
?>

</html>