<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport">
    <title>Error - Verifikasi Gagal</title>

    <!-- Link Logo -->
    <link rel="icon" href="../assets/img/favicon_io/Tea_Bliss_logo-32x32.png" type="image/x-icon">

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
                    <div class="col-12 col-sm-10 offset-sm-1 col-md-8 offset-md-2 col-lg-6 offset-lg-3 col-xl-4 offset-xl-4">
                        <div class="login-brand text-center mb-4">
                            <img src="./assets/img/Tea_Bliss_logo.png" alt="logo" class="img-fluid" style="max-width: 150px; height: auto;">
                        </div>
                        <div class="card card-danger">
                            <div class="card-header">
                                <h4>Verifikasi Gagal</h4>
                            </div>
                            <div class="card-body text-center">
                                <p style="font-size: 16px; color: #333;">
                                    Maaf, terjadi kesalahan dalam proses verifikasi. Token tidak ditemukan atau tidak valid.
                                </p>
                                <p style="font-size: 14px; color: #555;">
                                    Silakan coba lagi atau hubungi tim dukungan kami jika masalah ini terus berlanjut.
                                </p>
                                <a href="register.php" class="btn btn-primary btn-lg mt-3">
                                    Kembali ke Halaman Registrasi
                                </a>
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
</body>

<?php
require_once 'includes/_bottom.php';
?>

</html>