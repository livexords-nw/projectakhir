<?php
require_once '../includes/_top.php'; 
require_once '../helper/connection.php'; 
require_once '../helper/auth.php'; 
require_once '../helper/logger.php'; 

// Pastikan hanya admin yang dapat mengakses halaman ini
checkAdmin();

// Log aktivitas admin
if (isset($_SESSION['login']['username'])) {
    $username = $_SESSION['login']['username'];
    write_log("Admin '$username' mengakses halaman dashboard.");
} else {
    write_log("Akses dashboard tanpa sesi login.");
}

// Query untuk daftar pesanan yang belum diterima
$pesanan_pending_result = $connection->query("SELECT * FROM pemesanan WHERE status = 'pending' ORDER BY tanggal_pemesanan DESC");
$pesanan_pending = $pesanan_pending_result ? $pesanan_pending_result->fetch_all(MYSQLI_ASSOC) : [];

// Query untuk daftar pesanan yang telah selesai
$pesanan_selesai_result = $connection->query("SELECT * FROM pemesanan WHERE status = 'completed' ORDER BY tanggal_pemesanan DESC");
$pesanan_selesai = $pesanan_selesai_result ? $pesanan_selesai_result->fetch_all(MYSQLI_ASSOC) : [];

$errors = isset($_SESSION['errors']) ? $_SESSION['errors'] : [];
$old = isset($_SESSION['old']) ? $_SESSION['old'] : [];
$info = isset($_SESSION['info']) ? $_SESSION['info'] : null;

if ($info) {
  $status = $info['status']; 
  $message = $info['message'];

  if (is_array($message)) {
    $message = implode(' | ', $message);
  }
  
  echo "<script>
      document.addEventListener('DOMContentLoaded', function() {
          iziToast." . ($status === 'success' ? 'success' : 'error') . "( {
              title: '" . ($status === 'success' ? 'Sukses' : 'Gagal') . "',
              message: '{$message}',
              position: 'topCenter',
              timeout: 5000
          });
      });
  </script>";
  
  unset($_SESSION['info']);
}

unset($_SESSION['errors'], $_SESSION['old']);
?>

<section class="section">
  <div class="section-header">
    <h1>Admin Dashboard - Pesanan</h1>
    <div class="section-header-breadcrumb">
      <div class="breadcrumb-item active">Dashboard</div>
      <div class="breadcrumb-item">Pesanan</div>
    </div>
  </div>

  <!-- Statistik Pesanan -->
  <div class="row mb-4">
    <div class="col-lg-4 col-md-6 col-sm-6 col-12">
      <div class="card card-statistic-1">
        <div class="card-icon bg-primary">
          <i class="fas fa-box"></i>
        </div>
        <div class="card-wrap">
          <div class="card-header">
            <h4>Total Pesanan Pending</h4>
          </div>
          <div class="card-body">
            <?= count($pesanan_pending) ?>
          </div>
        </div>
      </div>
    </div>
    <div class="col-lg-4 col-md-6 col-sm-6 col-12">
      <div class="card card-statistic-1">
        <div class="card-icon bg-success">
          <i class="fas fa-check-circle"></i>
        </div>
        <div class="card-wrap">
          <div class="card-header">
            <h4>Total Pesanan Selesai</h4>
          </div>
          <div class="card-body">
            <?= count($pesanan_selesai) ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Daftar Pesanan Pending -->
  <section class="section">
    <div class="section-header">
      <h1>Pesanan Pending</h1>
    </div>

    <div class="row">
      <?php if (count($pesanan_pending) > 0): ?>
        <?php foreach ($pesanan_pending as $pesanan): ?>
          <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
            <div class="card shadow-sm h-100">
              <div class="card-body">
                <h5 class="card-title">#<?= $pesanan['id'] ?> - <?= htmlspecialchars($pesanan['nama_pemesan']) ?></h5>
                <p class="card-text">
                  <strong>Tanggal:</strong> <?= date('d M Y H:i', strtotime($pesanan['tanggal_pemesanan'])) ?><br>
                  <strong>Total:</strong> Rp <?= number_format($pesanan['total_harga'], 0, ',', '.') ?><br>
                  <strong>Status:</strong> <span class="badge badge-warning">Pending</span>
                </p>
                <form method="post" action="complete_order.php">
                  <input type="hidden" name="order_id" value="<?= $pesanan['id'] ?>">
                  <button type="submit" class="btn btn-success">Pesanan Selesai</button>
                </form>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="col-12">
          <div class="alert alert-info">Tidak ada pesanan pending.</div>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <!-- Daftar Pesanan Selesai (History) -->
  <section class="section mt-5">
    <div class="section-header">
      <h1>History Pesanan Selesai</h1>
    </div>

    <div class="row">
      <?php if (count($pesanan_selesai) > 0): ?>
        <?php foreach ($pesanan_selesai as $pesanan): ?>
          <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
            <div class="card shadow-sm h-100">
              <div class="card-body">
                <h5 class="card-title">#<?= $pesanan['id'] ?> - <?= htmlspecialchars($pesanan['nama_pemesan']) ?></h5>
                <p class="card-text">
                  <strong>Tanggal:</strong> <?= date('d M Y H:i', strtotime($pesanan['tanggal_pemesanan'])) ?><br>
                  <strong>Total:</strong> Rp <?= number_format($pesanan['total_harga'], 0, ',', '.') ?><br>
                  <strong>Status:</strong> <span class="badge badge-success">Selesai</span>
                </p>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="col-12">
          <div class="alert alert-info">Tidak ada pesanan yang selesai.</div>
        </div>
      <?php endif; ?>
    </div>
  </section>
</section>

<?php require_once '../includes/_bottom.php'; ?>
