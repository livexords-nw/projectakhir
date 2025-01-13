<?php
require_once '../includes/_top.php';
require_once '../helper/connection.php';
require_once '../helper/auth.php';
require_once '../helper/logger.php';

isLogin();

if (isset($_SESSION['login']['username'])) {
    $username = $_SESSION['login']['username'];
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

// Ambil input pencarian
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Query default untuk produk dengan stok lebih dari 0
$query = "SELECT * FROM produk WHERE stock > 0";

// Tambahkan filter jika ada pencarian
if (!empty($search)) {
    $query .= " AND nama LIKE '%" . mysqli_real_escape_string($connection, $search) . "%'";
}

$result = mysqli_query($connection, $query);

// Inisialisasi keranjang belanja di sesi
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
?>

<section class="section">
    <div class="section-header d-flex justify-content-between">
        <h1>Dashboard</h1>
    </div>

    <!-- Live Search -->
    <div class="mb-4">
        <input
            type="text"
            id="search-bar"
            class="form-control"
            placeholder="Cari produk..."
            autocomplete="off">
    </div>

    <!-- Hasil Pencarian -->
    <div id="product-list" class="row">
        <?php if (mysqli_num_rows($result) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                    <div class="card">
                        <img src="../uploads/<?= $row['gambar'] ?>" class="card-img-top" alt="<?= $row['nama'] ?>" style="height: 200px; object-fit: cover;">
                        <div class="card-body">
                            <h5 class="card-title"><?= $row['nama'] ?></h5>
                            <p class="card-text">Rp <?= number_format($row['harga'], 0, ',', '.') ?></p>
                            <p class="card-text"><small>Stok <?= $row['stock'] ? 'Tersedia' : 'Kosong' ?></small></p>
                            <form action="cart/add_to_cart.php" method="POST">
                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                <div class="form-group">
                                    <label for="jumlah-<?= $row['id'] ?>">Jumlah</label>
                                    <input type="number" name="jumlah" id="jumlah-<?= $row['id'] ?>" class="form-control" min="1" value="1" required>
                                </div>
                                <button type="submit" class="btn btn-primary btn-block">Tambah ke Troli</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <p class="text-center">Produk tidak ditemukan.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Script Live Search -->
<script>
    document.getElementById('search-bar').addEventListener('input', function() {
        const searchValue = this.value.trim();
        const productList = document.getElementById('product-list');

        // Request ke server
        const xhr = new XMLHttpRequest();
        xhr.open('GET', `live_search.php?search=${encodeURIComponent(searchValue)}`, true);
        xhr.onload = function() {
            if (xhr.status === 200) {
                productList.innerHTML = xhr.responseText;
            } else {
                console.error('Gagal memuat hasil pencarian.');
            }
        };
        xhr.send();
    });
</script>

<?php require_once '../includes/_bottom.php'; ?>