<?php
require_once '../includes/_top.php';
require_once '../helper/connection.php';
require_once '../helper/auth.php';
require_once '../helper/logger.php';

isLogin();

if (isset($_SESSION['login']['id'])) {
    $userId = $_SESSION['login']['id'];
} else {
    die('User tidak ditemukan');
}

// Handle cancel order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_id'], $_POST['cancel_reason'])) {
    $cancelId = intval($_POST['cancel_id']);
    $cancelReason = mysqli_real_escape_string($connection, $_POST['cancel_reason']);
    $currentDatetime = date('Y-m-d H:i:s'); // Waktu saat pembatalan dilakukan

    // Ambil data pesanan sebelum pembatalan
    $orderQuery = "
        SELECT id, nama_pemesan, total_harga 
        FROM pemesanan 
        WHERE id = ? AND user_id = ? AND status = 'pending'
    ";

    $stmtOrder = mysqli_prepare($connection, $orderQuery);
    mysqli_stmt_bind_param($stmtOrder, 'ii', $cancelId, $userId);
    mysqli_stmt_execute($stmtOrder);
    $orderResult = mysqli_stmt_get_result($stmtOrder);

    if ($orderResult && $orderData = mysqli_fetch_assoc($orderResult)) {
        $order_id = $orderData['id'];
        $nama_pemesan = $orderData['nama_pemesan'];
        $total = $orderData['total_harga'];

        // Query untuk update data
        $cancelQuery = "
            UPDATE pemesanan 
            SET 
                status = 'canceled', 
                info = CONCAT(IFNULL(info, ''), 'user ', ?), 
                tanggal_selesai = ? 
            WHERE 
                id = ? AND 
                user_id = ? AND 
                status = 'pending'
        ";

        $stmtCancel = mysqli_prepare($connection, $cancelQuery);
        mysqli_stmt_bind_param($stmtCancel, 'ssii', $cancelReason, $currentDatetime, $cancelId, $userId);
        mysqli_stmt_execute($stmtCancel);

        if (mysqli_stmt_affected_rows($stmtCancel) > 0) {
            $_SESSION['info'] = ['status' => 'success', 'message' => 'Pemesanan berhasil dibatalkan.'];
            write_log(
                "Pesanan dibatalkan oleh user: ID Pesanan={$order_id}, Pemesan={$nama_pemesan}, Total={$total}, Alasan={$cancelReason}",
                'INFO'
            );
        } else {
            $_SESSION['info'] = ['status' => 'error', 'message' => 'Gagal membatalkan pemesanan. Pastikan pemesanan masih berstatus pending.'];
        }
    } else {
        $_SESSION['info'] = ['status' => 'error', 'message' => 'Pesanan tidak ditemukan atau sudah dibatalkan.'];
    }

    // Redirect setelah proses selesai
    header('Location: history_pesanan.php');
    exit;
}

// Fetch orders
$query = "
    SELECT 
        p.id, 
        p.nama_pemesan, 
        p.tanggal_pemesanan, 
        p.total_harga, 
        p.status, 
        p.booking_start, 
        p.booking_end, 
        p.tanggal_selesai, 
        p.payment_proof, 
        p.info, 
        p.type_payment, 
        m.table_number
    FROM pemesanan p
    LEFT JOIN meja m ON p.meja_id = m.id
    WHERE p.user_id = ?
    ORDER BY p.tanggal_pemesanan DESC
";

$stmt = mysqli_prepare($connection, $query);
mysqli_stmt_bind_param($stmt, 'i', $userId);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if (!$result) {
    die("Query gagal: " . mysqli_error($connection));
}

// Toast notification system
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
unset($_SESSION['errors']);
?>

<head>
    <style>
        .button-group {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Backdrop hitam */
        .modal-backdrop-custom {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1040;
            /* Di bawah modal */
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
        }

        .modal-backdrop-custom.show {
            opacity: 1;
        }

        /* Animasi modal */
        .modal-content {
            transform: translateY(-20px);
            opacity: 0;
            transition: transform 0.3s ease-in-out, opacity 0.3s ease-in-out;
        }

        .modal.show .modal-content {
            transform: translateY(0);
            opacity: 1;
        }
    </style>
</head>

<section class="section">
    <div class="section-header">
        <h1>History Pemesanan</h1>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <?php if (mysqli_num_rows($result) > 0): ?>
                <div class="d-flex flex-wrap justify-content-start gap-3" id="cardsWrapper">
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <div class="card mb-3 flex-item" style="width: 18rem;">
                            <div class="card-body ">
                                <h5 class="card-title"><i class="fas fa-id-card"></i> ID Pemesanan: <?= htmlspecialchars($row['id']) ?></h5>
                                <p class="card-text">
                                    <strong>Nama Pemesan:</strong> <?= htmlspecialchars($row['nama_pemesan']) ?><br>
                                    <strong>Tanggal Pemesanan:</strong> <?= htmlspecialchars($row['tanggal_pemesanan']) ?><br>
                                    <strong>Total Harga:</strong> Rp <?= number_format($row['total_harga'], 0, ',', '.') ?><br>
                                    <strong>Status:</strong>
                                    <?php
                                    $status = htmlspecialchars($row['status']);
                                    $info = htmlspecialchars($row['info']);
                                    $badgeColor = match ($status) {
                                        'pending' => 'badge-warning',
                                        'canceled' => 'badge-danger',
                                        'approved' => 'badge-success',
                                        default => 'badge-secondary',
                                    };

                                    if ($status === 'canceled') {
                                        if (str_starts_with($info, 'user')) {
                                            $status .= ' by User';
                                        }
                                    }
                                    ?>
                                    <span class="badge <?= $badgeColor ?>"><?= $status ?></span><br>

                                    <strong>Nomor Meja:</strong> <?= htmlspecialchars($row['table_number']) ?: 'N/A' ?>
                                </p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="detail_pesanan.php?id=<?= $row['id'] ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-search"></i> Lihat Detail
                                    </a>
                                    <?php if ($status === 'pending'): ?>
                                        <button type="button" class="btn btn-danger btn-sm" onclick="showCancelModal(<?= htmlspecialchars($row['id']) ?>)">
                                            <i class="fas fa-times"></i> Batalkan Pesanan
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle"></i> Belum ada history pemesanan untuk Anda.
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Modal Alasan Pembatalan -->
<div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="cancelModalLabel">Batalkan Pemesanan</h5>
                <button type="button" class="btn-close" onclick="closeCancelModal()" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="cancelForm" method="POST">
                <div class="modal-body">
                    <!-- Input hidden untuk ID pemesanan -->
                    <input type="hidden" name="cancel_id" id="cancel_id">

                    <!-- Textarea untuk alasan pembatalan -->
                    <div class="mb-3">
                        <label for="cancel_reason" class="form-label">Alasan Pembatalan:</label>
                        <textarea name="cancel_reason" id="cancel_reason" class="form-control" style="height: 95px;" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary btn-sm" onclick="closeCancelModal()">Batal</button>
                    <button type="button" class="btn btn-danger btn-sm" onclick="confirmCancel()">Konfirmasi Pembatalan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/_bottom.php'; ?>

<script>
    function showCancelModal(orderId) {
        // Set ID pemesanan ke dalam modal
        document.getElementById('cancel_id').value = orderId;

        // Tambahkan class "show" dan "d-block" untuk modal
        const cancelModal = document.getElementById('cancelModal');
        cancelModal.classList.add('show', 'd-block');
        cancelModal.setAttribute('aria-hidden', 'false');
        cancelModal.setAttribute('style', 'display: block;');

        // Tambahkan backdrop
        const backdrop = document.createElement('div');
        backdrop.classList.add('modal-backdrop-custom', 'show');
        backdrop.id = 'modalBackdrop';
        document.body.appendChild(backdrop);
    }

    function closeCancelModal() {
        // Hapus class "show" dan "d-block" dari modal
        const cancelModal = document.getElementById('cancelModal');
        cancelModal.classList.remove('show', 'd-block');
        cancelModal.setAttribute('aria-hidden', 'true');
        cancelModal.setAttribute('style', 'display: none;');

        // Hapus backdrop dengan transisi
        const backdrop = document.getElementById('modalBackdrop');
        if (backdrop) {
            backdrop.classList.remove('show');
            setTimeout(() => {
                if (backdrop.parentNode) {
                    backdrop.parentNode.removeChild(backdrop);
                }
            }, 300); // Durasi transisi backdrop (sama dengan CSS)
        }
    }

    function validateCancelForm() {
        const cancelReason = document.getElementById('cancel_reason').value.trim();

        if (!cancelReason) {
            iziToast.error({
                title: 'Error',
                message: 'Alasan pembatalan tidak boleh kosong!',
                position: 'topCenter',
                timeout: 3000
            });
            return false; // Mencegah pengiriman form
        }

        return true; // Melanjutkan pengiriman form
    }

    function confirmCancel() {
        if (!validateCancelForm()) {
            return; // Jika validasi gagal, hentikan proses
        }

        iziToast.question({
            timeout: false,
            close: false,
            overlay: true,
            displayMode: 'once',
            title: 'Konfirmasi Pembatalan',
            message: 'Apakah Anda yakin ingin membatalkan pesanan ini?',
            position: 'center',
            buttons: [
                ['<button>Ya</button>', function(instance, toast) {
                    document.getElementById('cancelForm').submit();
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