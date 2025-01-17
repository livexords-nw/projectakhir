<?php
session_start();
require_once 'helper/connection.php';

// Include PHPMailer
require 'libs/PHPMailer/src/PHPMailer.php';
require 'libs/PHPMailer/src/SMTP.php';
require 'libs/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Fungsi untuk mengirim email OTP
function sendOtpEmail($email, $otp, $verificationLink, $username)
{
  $smtpCredentials = [];

  $maxRetries = 5; // Jumlah maksimal percobaan
  $retryDelay = 3; // Delay antar percobaan dalam detik (3 detik)
  $emailSent = false; // Flag untuk memeriksa apakah email berhasil dikirim
  $attempt = 0; // Untuk menghitung percobaan

  // Inisialisasi PHPMailer
  $mail = new PHPMailer(true);

  while ($attempt < $maxRetries && !$emailSent) {
    try {
      // Ambil kredensial yang sesuai dari array berdasarkan percobaan
      $credentials = $smtpCredentials[$attempt];

      // Konfigurasi SMTP
      $mail->isSMTP();
      $mail->Host = 'smtp.gmail.com';
      $mail->SMTPAuth = true;
      $mail->Username = $credentials['username']; // Username SMTP
      $mail->Password = $credentials['password']; // Password SMTP
      $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
      $mail->Port = 587;

      // Pengirim dan penerima
      $mail->setFrom($credentials['username'], 'Tea Bliss');
      $mail->addAddress($email);

      // Isi email
      $mail->isHTML(true);
      $mail->Subject = $username;
      $mail->Body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
                    <div style='text-align: center;'>
                        <img src='https://raw.githubusercontent.com/livexords-nw/projectakhir/main/assets/img/Tea_Bliss_logo.png' alt='Tea Bliss Logo' style='width: 200px;'>
                        <p style='color: #555;'>Kode OTP Anda:</p>
                        <h2 style='color: #4CAF50;'>$otp</h2>
                    </div>
                    <div style='text-align: center; margin-top: 30px;'>
                        <a href='$verificationLink' style='background-color: #4CAF50; color: white; text-decoration: none; padding: 10px 20px; border-radius: 5px; font-size: 14px;'>Verifikasi Akun Anda</a>
                    </div>
                    <p style='text-align: center; font-size: 14px; color: #555;'>Kode ini hanya berlaku selama 5 menit. Jika Anda tidak meminta kode ini, abaikan email ini.</p>
                </div>";
      $mail->AltBody = "Kode OTP Anda adalah: $otp\nKode ini hanya berlaku selama 5 menit.";

      // Kirim email
      if ($mail->send()) {
        echo 'Email berhasil dikirim dengan ' . $credentials['username'] . '.';
        $emailSent = true;
      } else {
        throw new Exception('Email gagal dikirim.');
      }
    } catch (Exception $e) {
      // Tangkap error dan tampilkan pesan
      error_log("Percobaan ke-" . ($attempt + 1) . " gagal. Error: {$mail->ErrorInfo}");

      // Tunggu sebelum mencoba lagi
      sleep($retryDelay);

      // Increment attempt counter
      $attempt++;
    }
  }

  // Jika email gagal dikirim setelah 5 kali percobaan, ganti ke kredensial berikutnya
  if (!$emailSent && $attempt < $maxRetries) {
    $attempt = 0; // Reset percobaan jika perlu ganti kredensial
    return sendOtpEmail($email, $otp, $verificationLink, $username); // Panggil ulang dengan kredensial yang baru
  }

  // Jika masih gagal setelah mencoba semua kredensial
  if (!$emailSent) {
    error_log('Email gagal dikirim setelah 5 kali percobaan dengan kredensial berbeda.');
    return false;
  }

  return true;
}

// Fungsi untuk memvalidasi input registrasi
function validateInput($username, $password, $repeatPassword, $email, $connection)
{
  if (empty($username) || empty($password) || empty($repeatPassword) || empty($email)) {
    return 'Semua field harus diisi dengan lengkap!';
  }
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    return 'Alamat email tidak valid!';
  }
  if ($password !== $repeatPassword) {
    return 'Password tidak cocok!';
  }
  if (strlen($password) < 6) {
    return 'Password minimal 6 karakter!';
  }

  // Cek apakah username atau email sudah terdaftar
  $checkUserQuery = "SELECT id FROM users WHERE username = ?";
  $stmtUser = mysqli_prepare($connection, $checkUserQuery);
  mysqli_stmt_bind_param($stmtUser, 's', $username);
  mysqli_stmt_execute($stmtUser);
  mysqli_stmt_store_result($stmtUser);

  if (mysqli_stmt_num_rows($stmtUser) > 0) {
    return 'Username sudah terdaftar!';
  }

  $checkEmailQuery = "SELECT id FROM users WHERE email = ?";
  $stmtEmail = mysqli_prepare($connection, $checkEmailQuery);
  mysqli_stmt_bind_param($stmtEmail, 's', $email);
  mysqli_stmt_execute($stmtEmail);
  mysqli_stmt_store_result($stmtEmail);

  if (mysqli_stmt_num_rows($stmtEmail) > 0) {
    return 'Email sudah terdaftar!';
  }

  mysqli_stmt_close($stmtUser);
  mysqli_stmt_close($stmtEmail);

  return null; // Tidak ada error
}

if (isset($_POST['submit'])) {
  $username = trim($_POST['username']);
  $password = trim($_POST['password']);
  $repeatPassword = trim($_POST['repeat_password']);
  $email = trim($_POST['email']);

  // Validasi input
  $validationError = validateInput($username, $password, $repeatPassword, $email, $connection);
  if ($validationError) {
    $_SESSION['info'] = ['status' => 'danger', 'message' => $validationError];
    header("Location: register.php");
    exit;
  }

  // Generate OTP dan Token
  $otp = rand(100000, 999999);
  $token = bin2hex(random_bytes(16));
  $otpCreatedAt = date('Y-m-d H:i:s');

  // Ambil nama domain
  $host = $_SERVER['HTTP_HOST'];

  $verificationLink = "https://$host/projectakhir_esteh/verify_otp.php?token=$token";

  // Kirim OTP
  if (sendOtpEmail($email, $otp, $verificationLink,  $_POST['username'])) {
    // Simpan data ke database
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $insertQuery = "INSERT INTO users (username, password, email, role, otp_code, token, otp_created_at) VALUES (?, ?, ?, 'user', ?, ?, ?)";
    $stmtInsert = mysqli_prepare($connection, $insertQuery);
    mysqli_stmt_bind_param($stmtInsert, 'ssssss', $username, $hashedPassword, $email, $otp, $token, $otpCreatedAt);

    if (mysqli_stmt_execute($stmtInsert)) {
      $_SESSION['info'] = [
        'status' => 'success',
        'message' => 'Kode OTP telah dikirim ke email Anda. Klik link di email untuk menyelesaikan proses verifikasi akun Anda.'
      ];
      header("Location: register.php");
      exit;
    } else {
      $_SESSION['info'] = ['status' => 'danger', 'message' => 'Terjadi kesalahan saat menyimpan data!'];
    }
    mysqli_stmt_close($stmtInsert);
  } else {
    $_SESSION['info'] = ['status' => 'danger', 'message' => 'Gagal mengirim OTP. Coba lagi nanti!'];
  }
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
  <title>Register Page</title>

  <!-- General CSS Files -->
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.2/css/all.css">

  <!-- Template CSS -->
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/components.css">

  <!-- Link Logo -->
  <link rel="icon" href="assets/img/favicon_io/Tea_Bliss_logo-32x32.png" type="image/x-icon">

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
                <h4>Register</h4>
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
                    <label for="email">Email</label>
                    <input id="email" type="email" class="form-control" name="email" tabindex="2" required>
                    <div class="invalid-feedback">
                      Mohon isi email
                    </div>
                  </div>

                  <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-group">
                      <input id="password" type="password" class="form-control" name="password" tabindex="3" required>
                      <div class="input-group-append">
                        <button type="button" class="btn btn-outline-secondary" id="toggle-password">
                          <i class="fas fa-eye"></i>
                        </button>
                      </div>
                    </div>
                    <div class="invalid-feedback">
                      Mohon isi password
                    </div>
                  </div>

                  <div class="form-group">
                    <label for="repeat_password">Repeat Password</label>
                    <input id="repeat_password" type="password" class="form-control" name="repeat_password" tabindex="4" required>
                    <div class="invalid-feedback">
                      Mohon isi ulang password
                    </div>
                  </div>

                  <div class="form-group">
                    <button name="submit" type="submit" class="btn btn-primary btn-lg btn-block" tabindex="5">
                      Register
                    </button>
                  </div>
                </form>
                <div class="mt-3 text-center">
                  Sudah punya akun? <a href="login.php">Login di sini</a>
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
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
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

    // Validate email format before form submission
    document.querySelector('form').addEventListener('submit', function(event) {
      const email = document.getElementById('email').value;
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

      if (!emailRegex.test(email)) {
        event.preventDefault();
        iziToast.error({
          title: 'Gagal',
          message: 'Format email tidak valid!',
          position: 'topCenter',
          timeout: 5000
        });
        return;
      }

      const password = document.getElementById('password').value;
      const repeatPassword = document.getElementById('repeat_password').value;

      if (password !== repeatPassword) {
        event.preventDefault();
        iziToast.error({
          title: 'Gagal',
          message: 'Password dan Repeat Password tidak cocok!',
          position: 'topCenter',
          timeout: 5000
        });
      }
    });
  </script>
</body>

<?php
require_once 'includes/_bottom.php';
?>

</html>