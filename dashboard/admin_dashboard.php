<?php
require_once '../includes/_top.php';
require_once '../helper/connection.php';
require_once '../helper/auth.php';
require_once '../helper/logger.php';

// Pastikan hanya admin yang dapat mengakses halaman ini
checkAdmin();

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

// Variabel default untuk data statistik
$total_produk = 0;
$total_harga = 0;
$total_terjual = 0;
$produk_terpopuler = [];
$produk_stok = [];

// Query total produk
$total_produk_result = $connection->query("SELECT COUNT(*) AS total FROM produk");
if ($total_produk_result && $total_produk_result->num_rows > 0) {
  $total_produk = $total_produk_result->fetch_assoc()['total'];
}

// Query total produk terjual
$total_terjual_result = $connection->query("SELECT SUM(jumlah_terjual) AS total FROM produk");
if ($total_terjual_result && $total_terjual_result->num_rows > 0) {
  $total_terjual = $total_terjual_result->fetch_assoc()['total'];
}

// Query produk terlaris
$produk_terpopuler_result = $connection->query("SELECT nama, jumlah_terjual, harga, gambar FROM produk ORDER BY jumlah_terjual DESC LIMIT 5");
if ($produk_terpopuler_result) {
  $produk_terpopuler = $produk_terpopuler_result->fetch_all(MYSQLI_ASSOC);
}

// Query total pendapatan (total harga * jumlah terjual per produk)
$total_pendapatan_result = $connection->query("
    SELECT SUM(harga * jumlah_terjual) AS total
    FROM produk
");
if ($total_pendapatan_result && $total_pendapatan_result->num_rows > 0) {
  $total_pendapatan = $total_pendapatan_result->fetch_assoc()['total'];
}

// Query stok produk
$produk_stok_result = $connection->query("SELECT nama, jumlah_terjual FROM produk");
if ($produk_stok_result) {
  $produk_stok = $produk_stok_result->fetch_all(MYSQLI_ASSOC);
}

// Data untuk grafik
$produk_nama = [];
$produk_terjual_values = [];
foreach ($produk_stok as $item) {
  $produk_nama[] = $item['nama'];
  $produk_terjual_values[] = $item['jumlah_terjual'];
}
?>

<head>
  <style>
    .card-body {
      white-space: nowrap;
      overflow: hidden;
      /* text-overflow: ellipsis; */
    }
  </style>
</head>
<section class="section">
  <div class="section-header">
    <h1>Admin Dashboard</h1>
    <div class="section-header-breadcrumb">
      <div class="breadcrumb-item active">Dashboard</div>
      <div class="breadcrumb-item">Statistik</div>
    </div>
  </div>

  <!-- Statistik Total -->
  <div class="row">
    <div class="col-lg-3 col-md-6 col-sm-6 col-12">
      <div class="card card-statistic-1">
        <div class="card-icon bg-primary">
          <i class="fas fa-box"></i>
        </div>
        <div class="card-wrap">
          <div class="card-header">
            <h4>Total Produk</h4>
          </div>
          <div class="card-body">
            <?= number_format($total_produk, 0, ',', '.') ?>
          </div>
        </div>
      </div>
    </div>
    <div class="col-lg-3 col-md-6 col-sm-6 col-12">
      <div class="card card-statistic-1">
        <div class="card-icon bg-danger">
          <i class="fas fa-dollar-sign"></i>
        </div>
        <div class="card-wrap">
          <div class="card-header">
            <h4>Total Pendapatan (Rp)</h4>
          </div>
          <div class="card-body">
            <?= number_format($total_pendapatan, 0, ',', '.') ?>
          </div>
        </div>
      </div>
    </div>
    <div class="col-lg-3 col-md-6 col-sm-6 col-12">
      <div class="card card-statistic-1">
        <div class="card-icon bg-success">
          <i class="fas fa-chart-line"></i>
        </div>
        <div class="card-wrap">
          <div class="card-header">
            <h4>Total Produk Terjual</h4>
          </div>
          <div class="card-body">
            <?= number_format($total_terjual, 0, ',', '.') ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Grafik Produk per Jumlah Terjual -->
  <!-- <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header">
          <h4>Grafik Jumlah Produk Terjual</h4>
        </div>
        <div class="card-body">
          <canvas id="produkTerjualChart"></canvas>
        </div>
      </div>
    </div>
  </div> -->

  <!-- produk terlaris -->
  <section class="section">
    <div class="section-header">
      <h1>produk terlaris</h1>
    </div>

    <!-- Filter Produk -->
    <div class="row mb-3">
      <div class="col-12">
        <form method="get" class="form-inline">
          <label for="limit" class="mr-2">Tampilkan:</label>
          <select id="limit" name="limit" class="form-control mr-3" onchange="this.form.submit()">
            <option value="10" <?= (isset($_GET['limit']) && $_GET['limit'] == 10) ? 'selected' : '' ?>>10</option>
            <option value="25" <?= (isset($_GET['limit']) && $_GET['limit'] == 25) ? 'selected' : '' ?>>25</option>
            <option value="50" <?= (isset($_GET['limit']) && $_GET['limit'] == 50) ? 'selected' : '' ?>>50</option>
          </select>
        </form>
      </div>
    </div>

    <!-- List Produk -->
    <div id="product-list" class="row">
      <?php
      $items_per_page = isset($_GET['limit']) ? (int)$_GET['limit'] : 10; // Default 10 items
      $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
      $offset = ($page - 1) * $items_per_page;

      $query = "
    SELECT nama, jumlah_terjual, harga, gambar
    FROM produk
    WHERE jumlah_terjual > 0 AND jumlah_terjual IS NOT NULL
    ORDER BY jumlah_terjual DESC
    LIMIT $items_per_page OFFSET $offset
  ";
      $result = $connection->query($query);

      $rank = $offset + 1;
      ?>

      <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($produk = $result->fetch_assoc()): ?>
          <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
            <div class="card h-100">
              <img src="<?= '../uploads/' . htmlspecialchars($produk['gambar']) ?>" class="card-img-top" alt="<?= htmlspecialchars($produk['nama']) ?>" style="height: 200px; object-fit: cover;">
              <div class="card-body">
                <h5 class="card-title">#<?= $rank++ ?> <?= htmlspecialchars($produk['nama']) ?></h5>
                <p class="card-text">
                  <strong>Jumlah Terjual:</strong> <?= number_format($produk['jumlah_terjual'], 0, ',', '.') ?><br>
                  <strong>Harga:</strong> Rp <?= number_format($produk['harga'], 0, ',', '.') ?>
                </p>
              </div>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="col-12">
          <div class="alert alert-info text-center">Tidak ada produk terlaris.</div>
        </div>
      <?php endif; ?>
    </div>


    <!-- Pagination -->
    <div class="row">
      <div class="col-12">
        <?php
        $count_query = "
          SELECT COUNT(*) AS total
          FROM produk
          WHERE jumlah_terjual > 0 AND jumlah_terjual IS NOT NULL
      ";
        $count_result = $connection->query($count_query);
        $total_items = $count_result->fetch_assoc()['total'];
        $total_pages = ceil($total_items / $items_per_page);
        ?>
        <nav aria-label="Page navigation">
          <ul class="pagination justify-content-center">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
              <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                <a class="page-link" href="?limit=<?= $items_per_page ?>&page=<?= $i ?>"><?= $i ?></a>
              </li>
            <?php endfor; ?>
          </ul>
        </nav>
      </div>
    </div>
  </section>

  <!-- Script untuk Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script>
    const produkTerjualChart = new Chart(document.getElementById('produkTerjualChart'), {
      type: 'bar',
      data: {
        labels: <?= json_encode($produk_nama) ?>,
        datasets: [{
          label: 'Jumlah Produk Terjual',
          data: <?= json_encode($produk_terjual_values) ?>,
          backgroundColor: 'rgba(75, 192, 192, 0.2)',
          borderColor: 'rgba(75, 192, 192, 1)',
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            position: 'top'
          },
          title: {
            display: true,
            text: 'Grafik Penjualan Produk'
          }
        },
        scales: {
          y: {
            beginAtZero: true
          }
        }
      }
    });
  </script>

  <?php require_once '../includes/_bottom.php'; ?>