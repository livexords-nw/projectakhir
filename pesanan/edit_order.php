<?php
require_once '../includes/_top.php';
require_once '../helper/connection.php';
require_once '../helper/auth.php';
require_once '../helper/logger.php'; // Tambahkan logger

checkAdmin();

// Mendapatkan ID pemesanan dari URL
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Pesan kesalahan atau status
$errors = $_SESSION['errors'] ?? [];
$old = $_SESSION['old'] ?? [];
$info = $_SESSION['info'] ?? null;

if ($info) {
    $status = $info['status'];
    $message = is_array($info['message']) ? implode(' | ', $info['message']) : $info['message'];

    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            iziToast." . ($status === 'success' ? 'success' : 'error') . "({
                title: '" . ($status === 'success' ? 'Sukses' : 'Gagal') . "',
                message: '{$message}',
                position: 'topCenter',
                timeout: 5000
            });
        });
    </script>";

    unset($_SESSION['info']);
}

// Validasi ID pesanan
if ($order_id <= 0) {
    $_SESSION['info'] = ['status' => 'error', 'message' => 'ID pesanan tidak valid.'];
    header('Location: orders_dashboard.php');
    exit;
}

// Ambil data pesanan
$order_query = "SELECT * FROM pemesanan WHERE id = ?";
$stmt = $connection->prepare($order_query);
$stmt->bind_param('i', $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    $_SESSION['info'] = ['status' => 'error', 'message' => 'Pesanan tidak ditemukan.'];
    header('Location: orders_dashboard.php');
    exit;
}

// Tambah detail pemesanan
if (isset($_POST['add_detail'])) {
    $produk_id = $_POST['produk_id'] ?? null;
    $jumlah = $_POST['jumlah'] ?? 0;

    if ($produk_id && $jumlah > 0) {
        $produk_query = "SELECT harga FROM produk WHERE id = ?";
        $stmt = $connection->prepare($produk_query);
        $stmt->bind_param('i', $produk_id);
        $stmt->execute();
        $produk = $stmt->get_result()->fetch_assoc();

        if ($produk) {
            $subtotal = $jumlah * $produk['harga'];

            $insert_query = "INSERT INTO detail_pemesanan (id_pemesanan, id_produk, jumlah, subtotal) VALUES (?, ?, ?, ?)";
            $stmt = $connection->prepare($insert_query);
            $stmt->bind_param("iiid", $order_id, $produk_id, $jumlah, $subtotal);

            if ($stmt->execute()) {
                write_log("Detail pesanan berhasil ditambahkan [id_produk => $produk_id, jumlah => $jumlah, subtotal => $subtotal]", "SUCCESS");
                $_SESSION['info'] = ['status' => 'success', 'message' => 'Detail pesanan berhasil ditambahkan.'];
            } else {
                write_log("Gagal menambahkan detail pesanan.", "ERROR");
                $_SESSION['info'] = ['status' => 'error', 'message' => 'Gagal menambahkan detail pesanan.'];
            }
        } else {
            write_log("Produk tidak ditemukan.", "ERROR");
            $_SESSION['info'] = ['status' => 'error', 'message' => 'Produk tidak ditemukan.'];
        }
    } else {
        write_log("Validasi gagal untuk tambah detail.", "ERROR");
        $_SESSION['info'] = ['status' => 'error', 'message' => 'Validasi data gagal.'];
    }

    header("Location: edit_order.php?id=$order_id");
    exit;
}

// Update detail pemesanan
if (isset($_POST['update_detail'])) {
    $detail_id = $_POST['detail_id'] ?? null;
    $jumlah = $_POST['jumlah'] ?? 0;

    if ($detail_id && $jumlah > 0) {
        $update_query = "UPDATE detail_pemesanan SET jumlah = ? WHERE id = ?";
        $stmt = $connection->prepare($update_query);
        $stmt->bind_param("ii", $jumlah, $detail_id);

        if ($stmt->execute()) {
            write_log("Detail pesanan berhasil diperbarui [id_detail => $detail_id, jumlah => $jumlah]", "SUCCESS");
            $_SESSION['info'] = ['status' => 'success', 'message' => 'Detail pesanan berhasil diperbarui.'];
        } else {
            write_log("Gagal memperbarui detail pesanan.", "ERROR");
            $_SESSION['info'] = ['status' => 'error', 'message' => 'Gagal memperbarui detail pesanan.'];
        }
    } else {
        write_log("Validasi gagal untuk update detail.", "ERROR");
        $_SESSION['info'] = ['status' => 'error', 'message' => 'Validasi data gagal.'];
    }

    header("Location: edit_order.php?id=$order_id");
    exit;
}

// Hapus detail pemesanan
if (isset($_GET['cancel_detail'])) {
    $detail_id = (int)$_GET['cancel_detail'];

    $delete_query = "DELETE FROM detail_pemesanan WHERE id = ?";
    $stmt = $connection->prepare($delete_query);
    $stmt->bind_param("i", $detail_id);

    if ($stmt->execute()) {
        write_log("Detail pesanan berhasil dihapus [id_detail => $detail_id]", "SUCCESS");
        $_SESSION['info'] = ['status' => 'success', 'message' => 'Detail pesanan berhasil dihapus.'];
    } else {
        write_log("Gagal menghapus detail pesanan.", "ERROR");
        $_SESSION['info'] = ['status' => 'error', 'message' => 'Gagal menghapus detail pesanan.'];
    }

    header("Location: edit_order.php?id=$order_id");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Pesanan</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/css/iziToast.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/izitoast/1.4.0/js/iziToast.min.js"></script>
</head>

<body>
    <div class="container my-4">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Edit Pesanan #<?= $order['id'] ?></h1>
            <a href="orders_dashboard.php" class="btn btn-secondary">Kembali</a>
        </div>

        <!-- Form Edit Pemesanan -->
        <form method="post" action="">
            <div class="card">
                <div class="card-body">
                    <div class="mb-3">
                        <label for="total_harga" class="form-label">Total Harga</label>
                        <input type="number" class="form-control" id="total_harga" name="total_harga" value="<?= $order['total_harga'] ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-control" id="status" name="status">
                            <option value="pending" <?= $order['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="completed" <?= $order['status'] == 'completed' ? 'selected' : '' ?>>Selesai</option>
                            <option value="canceled" <?= $order['status'] == 'canceled' ? 'selected' : '' ?>>Dibatalkan</option>
                        </select>
                    </div>
                </div>

                <div class="card-footer text-end">
                    <button type="submit" name="submit" class="btn btn-primary">Update Pesanan</button>
                </div>
            </div>
        </form>

        <!-- Detail Pemesanan -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5>Detail Pemesanan</h5>
            </div>
            <div class="card-body">
                <!-- Form Tambah Detail -->
                <form method="post" action="">
                    <div class="row mb-3">
                        <div class="col-md-5">
                            <select name="produk_id" class="form-select" required>
                                <option value="">Pilih Produk</option>
                                <?php
                                $produk_query = $connection->query("SELECT id, nama FROM produk");
                                while ($produk = $produk_query->fetch_assoc()):
                                ?>
                                    <option value="<?= $produk['id'] ?>"><?= $produk['nama'] ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="number" name="jumlah" class="form-control" placeholder="Jumlah" required>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" name="add_detail" class="btn btn-success">Tambah</button>
                        </div>
                    </div>
                </form>

                <!-- Tabel Detail -->
                <?php
                require_once '../helper/logger.php';

                $detail_query = "SELECT dp.*, p.nama AS nama_produk, p.harga 
                FROM detail_pemesanan dp
                INNER JOIN produk p ON dp.id_produk = p.id
                WHERE dp.id_pemesanan = ?";
                $stmt = $connection->prepare($detail_query);
                $stmt->bind_param("i", $order_id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0):
                ?>
                    <table class="table table-bordered table-striped">
                        <thead class="table-primary">
                            <tr>
                                <th>#</th>
                                <th>Nama Produk</th>
                                <th>Harga</th>
                                <th>Jumlah</th>
                                <th>Subtotal</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no = 1;
                            $grand_total = 0;
                            while ($row = $result->fetch_assoc()):
                                $subtotal = $row['harga'] * $row['jumlah'];
                                $grand_total += $subtotal;
                            ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= htmlspecialchars($row['nama_produk']) ?></td>
                                    <td>Rp<?= number_format($row['harga'], 0, ',', '.') ?></td>
                                    <td>
                                        <form method="post" action="" class="d-inline-block">
                                            <input type="hidden" name="detail_id" value="<?= $row['id'] ?>">
                                            <input type="number" name="jumlah" value="<?= $row['jumlah'] ?>" class="form-control d-inline-block w-75" min="1">
                                            <button type="submit" name="update_detail" class="btn btn-warning btn-sm">Update</button>
                                        </form>
                                    </td>
                                    <td>Rp<?= number_format($subtotal, 0, ',', '.') ?></td>
                                    <td>
                                        <a class="btn btn-sm btn-danger" href="#" onclick="confirmDelete(<?= $row['id'] ?>)">
                                            <i class="fas fa-trash fa-fw"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                            <tr>
                                <td colspan="4" class="text-end"><strong>Grand Total</strong></td>
                                <td><strong>Rp<?= number_format($grand_total, 0, ',', '.') ?></strong></td>
                            </tr>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-center">Tidak ada detail pemesanan untuk pesanan ini.</p>
                <?php endif; ?>

                <script>
                    function confirmDelete(id) {
                        iziToast.question({
                            timeout: false,
                            close: false,
                            overlay: true,
                            title: 'Konfirmasi',
                            message: 'Apakah Anda yakin ingin menghapus detail pesanan ini?',
                            position: 'center',
                            buttons: [
                                ['<button>Ya</button>', function(instance, toast) {
                                    window.location.href = `edit_order.php?id=<?= $order_id ?>&cancel_detail=${id}`;
                                    instance.hide({
                                        transitionOut: 'fadeOut'
                                    }, toast, 'button');
                                }],
                                ['<button>Tidak</button>', function(instance, toast) {
                                    instance.hide({
                                        transitionOut: 'fadeOut'
                                    }, toast, 'button');
                                }]
                            ]
                        });
                    }
                </script>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>