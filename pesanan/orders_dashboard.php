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

$errors = isset($_SESSION['errors']) ? $_SESSION['errors'] : [];
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
unset($_SESSION['errors']);

// Query untuk daftar pesanan yang belum diterima
$pesanan_pending_result = $connection->query("
    SELECT * FROM pemesanan 
    WHERE status = 'pending' 
    ORDER BY tanggal_pemesanan DESC
");
$pesanan_pending = $pesanan_pending_result ? $pesanan_pending_result->fetch_all(MYSQLI_ASSOC) : [];

// Query untuk daftar pesanan yang telah selesai
$pesanan_selesai_result = $connection->query("
    SELECT * FROM pemesanan 
    WHERE status = 'completed' 
    ORDER BY tanggal_pemesanan DESC
");
$pesanan_selesai = $pesanan_selesai_result ? $pesanan_selesai_result->fetch_all(MYSQLI_ASSOC) : [];

// Query untuk daftar pesanan yang telah dibatalkan
$pesanan_canceled_result = $connection->query("
    SELECT * FROM pemesanan 
    WHERE status = 'canceled' 
    ORDER BY tanggal_pemesanan DESC
");
$pesanan_canceled = $pesanan_canceled_result ? $pesanan_canceled_result->fetch_all(MYSQLI_ASSOC) : [];

// Query untuk semua pesanan
$pesanan_semua_result = $connection->query("
    SELECT * FROM pemesanan 
    ORDER BY tanggal_pemesanan DESC
");
$pesanan_semua = $pesanan_semua_result ? $pesanan_semua_result->fetch_all(MYSQLI_ASSOC) : [];

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
    <div class="col-lg-4 col-md-6 col-sm-6 col-12">
      <div class="card card-statistic-1">
        <div class="card-icon bg-success">
          <i class="fas fa-check-circle"></i>
        </div>
        <div class="card-wrap">
          <div class="card-header">
            <h4>Total Pesanan Yang di Batalkan</h4>
          </div>
          <div class="card-body">
            <?= count($pesanan_canceled) ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Daftar Pesanan Pending -->
  <section>
    <h1>Pesanan Pending</h1>
    <div class="row">
      <?php if (count($pesanan_pending) > 0): ?>
        <?php foreach ($pesanan_pending as $pesanan): ?>
          <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
            <div class="card shadow-sm h-100">
              <div class="card-body">
                <h5>#<?= $pesanan['id'] ?> - <?= htmlspecialchars($pesanan['nama_pemesan']) ?></h5>
                <p><strong>Tanggal:</strong> <?= date('d M Y H:i', strtotime($pesanan['tanggal_pemesanan'])) ?></p>
                <p><strong>Total:</strong> Rp <?= number_format($pesanan['total_harga'], 0, ',', '.') ?></p>
                <div class="d-flex justify-content-between">
                  <form method="post" action="complete_order.php">
                    <input type="hidden" name="order_id" value="<?= $pesanan['id'] ?>">
                    <button type="submit" class="btn btn-success btn-sm">Pesanan Selesai</button>
                  </form>
                  <a href="edit_order.php?id=<?= $pesanan['id'] ?>" class="btn btn-primary btn-sm">Edit</a>
                </div>
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

  <!-- Sejarah pesanan -->
  <section>
    <h1>Sejarah Pesanan</h1>
    <div class="form-group">
      <input type="text" id="searchInput" class="form-control" placeholder="Cari pesanan berdasarkan ID, nama pemesan, atau status...">
    </div>
    <div class="row" id="orderHistory">
      <?php if (count($pesanan_semua) > 0): ?>
        <?php foreach ($pesanan_semua as $pesanan): ?>
          <div class="col-lg-4 col-md-6 col-sm-12 mb-4 order-item" data-id="<?= $pesanan['id'] ?>" data-name="<?= htmlspecialchars($pesanan['nama_pemesan']) ?>" data-status="<?= $pesanan['status'] ?>">
            <div class="card shadow-sm h-100">
              <div class="card-body">
                <h5>#<?= $pesanan['id'] ?> - <?= htmlspecialchars($pesanan['nama_pemesan']) ?></h5>
                <p><strong>Status:</strong> <?= ucfirst($pesanan['status']) ?></p>
                <p><strong>Tanggal:</strong> <?= date('d M Y H:i', strtotime($pesanan['tanggal_pemesanan'])) ?></p>
                <p><strong>Total:</strong> Rp <?= number_format($pesanan['total_harga'], 0, ',', '.') ?></p>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="col-12">
          <div class="alert alert-info">Tidak ada pesanan ditemukan.</div>
        </div>
      <?php endif; ?>
    </div>
  </section>

</section>

<?php require_once '../includes/_bottom.php'; ?>

<script>
  document.getElementById('searchInput').addEventListener('input', function() {
    const query = this.value.toLowerCase();
    const items = document.querySelectorAll('.order-item');

    items.forEach(item => {
      const id = item.getAttribute('data-id').toLowerCase();
      const name = item.getAttribute('data-name').toLowerCase();
      const status = item.getAttribute('data-status').toLowerCase();

      if (id.includes(query) || name.includes(query) || status.includes(query)) {
        item.style.display = 'block';
      } else {
        item.style.display = 'none';
      }
    });
  });
</script>