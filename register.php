<?php
session_start();
require_once 'helper/connection.php';
require_once 'helper/logger.php';

if (isset($_POST['submit'])) {
  $username = trim($_POST['username']);
  $password = trim($_POST['password']);
  $repeatPassword = trim($_POST['repeat_password']);
  $email = trim($_POST['email']);

  if (empty($username) || empty($password) || empty($repeatPassword) || empty($email)) {
    $_SESSION['info'] = [
      'status' => 'danger',
      'message' => 'Semua field harus diisi!'
    ];
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['info'] = [
      'status' => 'danger',
      'message' => 'Format email tidak valid!'
    ];
  } elseif ($password !== $repeatPassword) {
    $_SESSION['info'] = [
      'status' => 'danger',
      'message' => 'Password dan Repeat Password tidak cocok!'
    ];
  } elseif (strlen($password) < 6) {
    $_SESSION['info'] = [
      'status' => 'danger',
      'message' => 'Password minimal 6 karakter!'
    ];
  } else {
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $checkUserQuery = "SELECT id FROM users WHERE username = ?";
    $stmt = mysqli_prepare($connection, $checkUserQuery);
    mysqli_stmt_bind_param($stmt, 's', $username);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {
      $_SESSION['info'] = [
        'status' => 'danger',
        'message' => 'Username sudah digunakan, silakan pilih username lain!'
      ];
      write_log("Gagal registrasi: Username '$username' sudah digunakan.", 'INFO');
    } else {
      $insertQuery = "INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, 'user')";
      $stmt = mysqli_prepare($connection, $insertQuery);
      mysqli_stmt_bind_param($stmt, 'sss', $username, $hashedPassword, $email);

      if (mysqli_stmt_execute($stmt)) {
        $_SESSION['info'] = [
          'status' => 'success',
          'message' => 'Registrasi berhasil! Silakan login.'
        ];
        write_log("User '$username' berhasil registrasi.");
        header("Location: login.php");
        exit;
      } else {
        $_SESSION['info'] = [
          'status' => 'danger',
          'message' => 'Terjadi kesalahan, silakan coba lagi!'
        ];
        write_log("Gagal registrasi: Kesalahan saat menyimpan data pengguna.", 'ERROR');
      }
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
  <title>Register Page</title>

  <!-- General CSS Files -->
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.2/css/all.css">

  <!-- Template CSS -->
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/components.css">
</head>

<body>
  <div id="app">
    <section class="section">
      <div class="container mt-5">
        <div class="row">
          <div class="col-12 col-sm-10 offset-sm-1 col-md-8 offset-md-2 col-lg-6 offset-lg-3 col-xl-4 offset-xl-4">
            <div class="login-brand text-center mb-4">
              <img src="./assets/img/avatar/Tea_Bliss_logo.png" alt="logo" class="img-fluid" style="max-width: 150px; height: auto;">
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

    // Validate password and repeat password
    document.querySelector('form').addEventListener('submit', function(event) {
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

</html>