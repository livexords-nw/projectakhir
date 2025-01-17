<?php
require_once '../helper/auth.php';

isLogin();

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
ob_start();

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport">
  <title>Tea Bliss</title>

  <!-- Link Logo -->
  <link rel="icon" href="../assets/img/favicon_io/Tea_Bliss_logo-32x32.png" type="image/x-icon">

  <!-- General CSS Files -->
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
  <link rel="stylesheet" href="../node_modules/@fortawesome/fontawesome-free/css/all.min.css">

  <!-- CSS Libraries -->
  <link rel="stylesheet" href="../assets/modules/jqvmap/dist/jqvmap.min.css">
  <link rel="stylesheet" href="../assets/modules/summernote/summernote-bs4.css">
  <link rel="stylesheet" href="../assets/modules/owlcarousel2/dist/assets/owl.carousel.min.css">
  <link rel="stylesheet" href="../assets/modules/owlcarousel2/dist/assets/owl.theme.default.min.css">
  <link rel="stylesheet" href="../assets/modules/datatables/datatables.min.css">
  <link rel="stylesheet" href="../assets/modules/datatables/DataTables-1.10.16/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="../assets/modules/datatables/Select-1.2.4/css/select.bootstrap4.min.css">
  <link rel="stylesheet" href="../assets/modules/izitoast/css/iziToast.min.css">

  <!-- Template CSS -->
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/components.css">

  <style>
    /* Default: Logo besar terlihat, logo kecil tersembunyi */
    aside#sidebar-wrapper:not(.sidebar-collapsed) .logo-large {
      display: block !important;
    }

    aside#sidebar-wrapper:not(.sidebar-collapsed) .logo-small {
      display: none !important;
    }

    /* Ketika sidebar menyusut */
    aside#sidebar-wrapper.sidebar-collapsed .logo-large {
      display: none !important;
    }

    aside#sidebar-wrapper.sidebar-collapsed .logo-small {
      display: block !important;
    }

    .sidebar-menu .nav-link {
      display: flex;
      align-items: center;
      padding: 10px 15px;
    }

    .sidebar-menu .nav-link i {
      margin-right: 10px;
      /* Memberikan jarak antara ikon dan teks */
      font-size: 18px;
      /* Menyesuaikan ukuran ikon */
    }

    .sidebar-menu .dropdown-menu a {
      display: flex;
      align-items: center;
      padding: 10px 15px;
    }

    .sidebar-menu .dropdown-menu a i {
      margin-right: 10px;
      font-size: 16px;
      /* Menyesuaikan ukuran ikon dropdown */
    }
  </style>
</head>

<body>
  <div id="app">
    <div class="main-wrapper main-wrapper-1">
      <?php
      require_once '_header.php';
      require_once '_sidenav.php';
      ?>
      <!-- Main Content -->
      <div class="main-content">