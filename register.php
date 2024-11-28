<?php
require_once 'helper/connection.php';
require_once 'helper/logger.php'; 

if (isset($_POST['submit'])) {
    $username = mysqli_real_escape_string($connection, $_POST['username']);
    $password = mysqli_real_escape_string($connection, $_POST['password']);
    $email = mysqli_real_escape_string($connection, $_POST['email']);

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $checkUserQuery = "SELECT * FROM users WHERE username = '$username' LIMIT 1";
    $checkResult = mysqli_query($connection, $checkUserQuery);

    if (mysqli_num_rows($checkResult) > 0) {
        echo "<script>alert('Username sudah digunakan, silakan pilih username lain!');</script>";
        write_log("Gagal registrasi: Username '$username' sudah digunakan.", 'INFO'); 
    } else {
        $sql = "INSERT INTO users (username, password, email, role) VALUES ('$username', '$hashedPassword', '$email', 'user')";
        if (mysqli_query($connection, $sql)) {
            echo "<script>alert('Registrasi berhasil! Silakan login.'); window.location.href = 'login.php';</script>";
            write_log("User '$username' berhasil registrasi."); 
        } else {
            echo "<script>alert('Terjadi kesalahan, silakan coba lagi!');</script>";
            write_log("Gagal registrasi: Terjadi kesalahan saat menyimpan data pengguna.", 'ERROR'); 
        }
    }
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
                    <button name="submit" type="submit" class="btn btn-primary btn-lg btn-block" tabindex="4">
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
  </script>
</body>

</html>
