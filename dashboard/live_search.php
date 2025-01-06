<?php
require_once '../helper/connection.php';

// Ambil input pencarian
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Query default untuk produk dengan stok lebih dari 0
$query = "SELECT * FROM produk WHERE stock > 0";

// Tambahkan filter jika ada pencarian
if (!empty($search)) {
    $query .= " AND nama LIKE '%" . mysqli_real_escape_string($connection, $search) . "%'";
}

$result = mysqli_query($connection, $query);

if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)): ?>
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
<?php endwhile;
} else {
    echo '<div class="col-12"><p class="text-center">Produk tidak ditemukan.</p></div>';
}
?>