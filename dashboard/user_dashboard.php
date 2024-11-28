<?php
require_once '../includes/_top.php';
require_once '../helper/connection.php';
require_once '../helper/auth.php';
require_once '../helper/logger.php';

isLogin(); 

if (isset($_SESSION['login']['username'])) {
  $username = $_SESSION['login']['username'];
  write_log("User '$username' mengakses halaman dashboard.", 'INFO');
}

$total_matakuliah = 4; // Contoh data
$total_nilai = 231; // Contoh data
?>

<section class="section">
  <div class="section-header">
    <h1>Dashboard</h1>
  </div>
  <div class="column">
    <div class="row">
      <div class="col-lg-6 col-md-6 col-sm-6 col-12">
        <div class="card card-statistic-1">
          <div class="card-icon bg-warning">
            <i class="far fa-file"></i>
          </div>
          <div class="card-wrap">
            <div class="card-header">
              <h4>Total Mata Kuliah</h4>
            </div>
            <div class="card-body">
              <?= $total_matakuliah ?>
            </div>
          </div>
        </div>
      </div>
      <div class="col-lg-6 col-md-6 col-sm-6 col-12">
        <div class="card card-statistic-1">
          <div class="card-icon bg-success">
            <i class="far fa-newspaper"></i>
          </div>
          <div class="card-wrap">
            <div class="card-header">
              <h4>Total Nilai Masuk</h4>
            </div>
            <div class="card-body">
              <?= $total_nilai ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Navbar tidak ditampilkan di user_dashboard -->
<!-- Optional: Anda bisa tambahkan footer atau elemen lain -->

<?php
require_once '../includes/_bottom.php';
?>
