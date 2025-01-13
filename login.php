<?php
require_once 'helper/connection.php';

// Include PHPMailer
require 'libs/PHPMailer/src/PHPMailer.php';
require 'libs/PHPMailer/src/SMTP.php';
require 'libs/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();

// Fungsi untuk mengirim email OTP
function sendOtpEmail($email, $otp, $verificationLink, $username)
{
  $mail = new PHPMailer(true);

  try {
    // Konfigurasi SMTP
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = ''; // Ganti dengan email Anda
    $mail->Password = ''; // Ganti dengan App Password Gmail Anda
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    // Pengirim dan penerima
    $mail->setFrom('', 'Tea Bliss');
    $mail->addAddress($email);

    // Isi email
    $mail->isHTML(true);
    $mail->Subject = $username;
    $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
                <div style='text-align: center;'>
                    <img src='https://raw.githubusercontent.com/livexords-nw/projectakhir/main/assets/img/avatar/Tea_Bliss_logo.png' alt='Tea Bliss Logo' style='width: 200px;'>
                    <p style='color: #555;'>Kode OTP Anda:</p>
                    <h2 style='color: #4CAF50;'>$otp</h2>
                </div>
                <div style='text-align: center; margin-top: 30px;'>
                    <a href='$verificationLink' style='background-color: #4CAF50; color: white; text-decoration: none; padding: 10px 20px; border-radius: 5px; font-size: 14px;'>Verifikasi Akun Anda</a>
                </div>
                <p style='text-align: center; font-size: 14px; color: #555;'>Kode ini hanya berlaku selama 5 menit. Jika Anda tidak meminta kode ini, abaikan email ini.</p>
            </div>";
    $mail->AltBody = "Kode OTP Anda adalah: $otp\nKode ini hanya berlaku selama 5 menit.";

    $mail->send();
    return true;
  } catch (Exception $e) {
    error_log("Email gagal dikirim. Error: {$mail->ErrorInfo}");
    return false;
  }
}

if (isset($_POST['submit'])) {
  $username = mysqli_real_escape_string($connection, $_POST['username']);
  $password = mysqli_real_escape_string($connection, $_POST['password']);

  // Query untuk memeriksa user dan role
  $sql = "SELECT * FROM users WHERE username='$username' LIMIT 1";
  $result = mysqli_query($connection, $sql);
  $row = mysqli_fetch_assoc($result);

  if ($row) {
    if (password_verify($password, $row['password'])) {
      if ($row['email_verified'] == 0) {
        // Generate OTP
        $otp = rand(100000, 999999);
        $token = bin2hex(random_bytes(16));
        $otpCreatedAt = date('Y-m-d H:i:s');

        // Simpan OTP ke database
        $updateOTP = "UPDATE users SET token='$token', otp_code='$otp', otp_created_at='$otpCreatedAt' WHERE username='{$row['username']}'";
        mysqli_query($connection, $updateOTP);

        // Kirim email dengan OTP
        $verificationLink = "http://localhost/projectakhir_esteh/verify_otp.php?token=$token";

        if (sendOtpEmail($row['email'], $otp, $verificationLink,  $row['username'])) {
          $_SESSION['info'] = [
            'status' => 'danger',
            'message' => "Email anda belum terverifikasi ,kode OTP telah dikirim ke email Anda."
          ];
        } else {
          $_SESSION['info'] = [
            'status' => 'error',
            'message' => "Gagal mengirim email OTP. Silakan coba lagi."
          ];
        }

        header('Location: login.php');
        exit();
      }

      // Login berhasil
      $_SESSION['login'] = $row;
      $_SESSION['username'] = $row['username'];
      $_SESSION['role'] = $row['role'];
      $_SESSION['user_id'] = $row['id'];

      $_SESSION['info'] = [
        'status' => 'success',
        'message' => $row['role'] === 'admin'
          ? "Kamu berhasil login sebagai admin."
          : "Kamu berhasil login dengan username {$username}."
      ];

      header('Location: ' . ($row['role'] === 'admin' ? 'dashboard/admin_dashboard.php' : 'dashboard/user_dashboard.php'));
      exit();
    } else {
      $_SESSION['info'] = [
        'status' => 'error',
        'message' => "Password salah untuk username {$username}."
      ];
    }
  } else {
    $_SESSION['info'] = [
      'status' => 'error',
      'message' => "Username {$username} tidak ditemukan."
    ];
  }

  header('Location: login.php');
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
  <title>Login Page</title>

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
                <h4>Login</h4>
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
                    <label for="password">Password</label>
                    <div class="input-group">
                      <input id="password" type="password" class="form-control" name="password" tabindex="2" required>
                      <div class="input-group-append">
                        <button type="button" class="btn btn-outline-secondary" id="toggle-password">
                          <i class="fas fa-eye"></i>
                        </button>
                      </div>
                    </div>
                    <div class="invalid-feedback">
                      Mohon isi kata sandi
                    </div>
                  </div>

                  <div class="form-group">
                    <div class="custom-control custom-checkbox">
                      <input type="checkbox" name="remember" class="custom-control-input" tabindex="3" id="remember-me">
                      <label class="custom-control-label" for="remember-me">Ingat Saya</label>
                    </div>
                  </div>

                  <div class="form-group">
                    <button name="submit" type="submit" class="btn btn-primary btn-lg btn-block" tabindex="3">
                      Login
                    </button>
                  </div>
                </form>
                <div class="mt-3 text-center">
                  Belum punya akun? <a href="register.php">Daftar di sini</a>
                </div>
                <!-- <div class="mt-3 text-center">
                  Lupa password? <a href="lupa_password.php">Klik di sini</a>
                </div> -->
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

<?php
require_once 'includes/_bottom.php';
?>

</html>