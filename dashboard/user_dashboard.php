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

// Ambil input pencarian dan limit
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 12; // Default 12 item per halaman
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max($page, 1); // Pastikan halaman minimal 1
$offset = ($page - 1) * $limit; // Hitung offset berdasarkan halaman dan limit

// Query untuk produk
$query = "SELECT * FROM produk WHERE stock > 0";

// Tambahkan filter pencarian jika ada
if (!empty($search)) {
    $query .= " AND nama LIKE '%" . mysqli_real_escape_string($connection, $search) . "%'";
}

// Hitung total data untuk pagination
$countQuery = "SELECT COUNT(*) as total FROM produk WHERE stock > 0";
if (!empty($search)) {
    $countQuery .= " AND nama LIKE '%" . mysqli_real_escape_string($connection, $search) . "%'";
}
$totalResult = mysqli_query($connection, $countQuery);
$totalRow = mysqli_fetch_assoc($totalResult);
$totalItems = (int)$totalRow['total'];

// Hitung jumlah halaman
$totalPages = ceil($totalItems / $limit);

// Tambahkan LIMIT dan OFFSET
$query .= " LIMIT $limit OFFSET $offset";

// Jalankan query produk
$result = mysqli_query($connection, $query);

// Inisialisasi keranjang belanja di sesi
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
?>

<head>
    <style>
        .card {
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .card-body {
            flex-grow: 1;
            /* Mengisi ruang yang tersisa untuk keseragaman */
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .card-title {
            font-size: 1rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
            min-height: 48px;
            /* Sesuaikan agar cukup untuk 2 baris teks */
            overflow: hidden;
            text-overflow: ellipsis;
            word-wrap: break-word;
        }

        .card-text {
            margin-bottom: 1rem;
            min-height: 24px;
            /* Tetap rata meskipun harga berbeda panjang */
        }

        .btn-block {
            margin-top: auto;
            /* Mendorong tombol ke bawah */
        }
    </style>
</head>

<section class="section">
    <div class="section-header d-flex justify-content-between">
        <h1>Dashboard</h1>
    </div>

    <!-- Filter Limit -->
    <div class="mb-4 d-flex justify-content-between align-items-center">
        <input
            type="text"
            id="search-bar"
            class="form-control w-50"
            placeholder="Cari produk..."
            value="<?= htmlspecialchars($search) ?>"
            autocomplete="off">
        <select id="limit-select" class="form-control w-25 ml-2">
            <?php
            $limits = [5, 10, 12, 20, 50]; // Pilihan jumlah produk per halaman
            foreach ($limits as $option) {
                $selected = $option == $limit ? 'selected' : '';
                echo "<option value='$option' $selected>$option per halaman</option>";
            }
            ?>
        </select>
    </div>

    <!-- Hasil Pencarian -->
    <div id="product-list" class="row">
        <?php if (mysqli_num_rows($result) > 0): ?>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                    <div class="card">
                        <img src="../uploads/<?= $row['gambar'] ?>" class="card-img-top" alt="<?= $row['nama'] ?>" style="height: 200px; object-fit: cover;">
                        <div class="card-body">
                            <h5 class="card-title" title="<?= htmlspecialchars($row['nama']) ?>">
                                <?= htmlspecialchars($row['nama']) ?>
                            </h5>
                            <p class="card-text">
                                Rp <?= number_format($row['harga'], 0, ',', '.') ?>
                            </p>
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

    <!-- Pagination -->
    <nav>
        <ul class="pagination justify-content-center">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>&limit=<?= $limit ?>&search=<?= urlencode($search) ?>">
                        <?= $i ?>
                    </a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
</section>

<!-- Script -->
<script>
    const searchBar = document.getElementById('search-bar');
    const limitSelect = document.getElementById('limit-select');
    const productList = document.getElementById('product-list');
    const paginationContainer = document.querySelector('.pagination');

    // Fungsi untuk memuat data produk
    const loadProducts = async (search = '', limit = 12, page = 1) => {
        try {
            const response = await fetch(`?search=${encodeURIComponent(search)}&limit=${limit}&page=${page}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            // Ambil data HTML dari server
            const html = await response.text();

            // Parse dan sisipkan hasil ke dalam elemen productList
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newProductList = doc.getElementById('product-list');
            const newPagination = doc.querySelector('.pagination');

            // Update produk dan pagination
            productList.innerHTML = newProductList.innerHTML;
            paginationContainer.innerHTML = newPagination ? newPagination.innerHTML : '';
        } catch (error) {
            console.error('Gagal memuat produk:', error);
        }
    };

    // Event listener untuk input di search bar
    searchBar.addEventListener('input', () => {
        const searchValue = searchBar.value.trim();
        const limit = limitSelect.value;
        loadProducts(searchValue, limit, 1); // Mulai dari halaman 1
    });

    // Event listener untuk perubahan limit
    limitSelect.addEventListener('change', () => {
        const searchValue = searchBar.value.trim();
        const limit = limitSelect.value;
        loadProducts(searchValue, limit, 1); // Mulai dari halaman 1
    });

    // Event listener untuk pagination
    paginationContainer.addEventListener('click', (event) => {
        if (event.target.tagName === 'A') {
            event.preventDefault();
            const searchValue = searchBar.value.trim();
            const limit = limitSelect.value;
            const page = new URL(event.target.href).searchParams.get('page');
            loadProducts(searchValue, limit, page);
        }
    });
</script>

<?php require_once '../includes/_bottom.php'; ?>