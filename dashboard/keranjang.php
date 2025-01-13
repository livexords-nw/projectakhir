<?php
require_once '../includes/_top.php';
require_once '../helper/connection.php';
require_once '../helper/auth.php';
isLogin();

if (isset($_SESSION['login']['username'])) {
    $username = $_SESSION['login']['username'];
    $userId = $_SESSION['login']['id'];
}

$info = isset($_SESSION['info']) ? $_SESSION['info'] : null;

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

// Ambil semua data pemesanan
$pemesanan = [];
$result = $connection->query("SELECT * FROM pemesanan");
while ($row = $result->fetch_assoc()) {
    $pemesanan[] = $row;
}

// Ambil semua data meja
$meja = [];
$result = $connection->query("SELECT * FROM meja");
while ($row = $result->fetch_assoc()) {
    $meja[] = $row;
}

echo "<script>
    const allPemesanan = " . json_encode($pemesanan) . ";
    const allMeja = " . json_encode($meja) . ";
</script>";
?>

<section class="section">
    <div class="section-header">
        <h1>Keranjang Belanja</h1>
    </div>
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form id="updateCartForm" action="cart/update_cart.php" method="POST">
                <table class="table table-bordered table-striped">
                    <thead class="table-light">
                        <tr>
                            <th>Nama Produk</th>
                            <th>Harga</th>
                            <th>Jumlah</th>
                            <th>Subtotal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($_SESSION['cart'])): ?>
                            <?php
                            $total = 0;
                            foreach ($_SESSION['cart'] as $index => $item):
                                $subtotal = $item['harga'] * $item['jumlah'];
                                $total += $subtotal;
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['nama']) ?></td>
                                    <td>Rp <?= number_format($item['harga'], 0, ',', '.') ?></td>
                                    <td>
                                        <input type="number" name="jumlah[<?= $index ?>]" class="form-control form-control-sm" min="1" value="<?= $item['jumlah'] ?>" required>
                                    </td>
                                    <td>Rp <?= number_format($subtotal, 0, ',', '.') ?></td>
                                    <td>
                                        <button type="button" class="btn btn-danger btn-sm" onclick="confirmDelete(<?= $item['id'] ?>)">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <tr>
                                <td colspan="3" class="text-right"><strong>Total</strong></td>
                                <td><strong>Rp <?= number_format($total, 0, ',', '.') ?></strong></td>
                                <td></td>
                            </tr>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">Keranjang kosong. Silakan tambahkan produk.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <?php if (!empty($_SESSION['cart'])): ?>
                    <div class="d-flex justify-content-between mt-2">
                        <button type="button" class="btn btn-primary" onclick="confirmUpdate()">Update Pesanan</button>
                    </div>
                <?php endif; ?>
            </form>

            <?php if (!empty($_SESSION['cart'])): ?>
                <hr>
                <h5>Form Checkout</h5>
                <form id="checkoutForm" action="../produk/checkout.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="user_id" value="<?= $userId ?>">

                    <div class="form-group">
                        <label for="booking_start">Booking di Jam Berapa</label>
                        <input type="datetime-local" name="booking_start" id="booking_start" class="form-control" required onfocus="this.showPicker()">
                    </div>
                    <div class="form-group">
                        <label for="booking_duration">Durasi Booking</label>
                        <select name="booking_duration" id="booking_duration" class="form-control" required>
                            <option value="15">15 menit</option>
                            <option value="30">30 menit</option>
                            <option value="60">1 jam</option>
                            <option value="90">1 jam 30 menit</option>
                            <option value="120">2 jam</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="meja_id">Pilih Meja</label>
                        <select name="meja_id" id="meja_id" class="form-control" required>
                            <option value="" disabled selected>Pilih meja</option>
                            <!-- Meja akan diisi oleh JavaScript -->
                        </select>
                    </div>
                    <input type="hidden" name="booking_end" id="booking_end">

                    <div class="form-group">
                        <label>Metode Pembayaran</label><br>
                        <input type="radio" name="payment_method" value="cash" id="payment_cash" required>
                        <label for="payment_cash">Cash</label><br>
                        <input type="radio" name="payment_method" value="dana" id="payment_dana">
                        <label for="payment_dana">Dana</label>
                    </div>
                    <div class="form-group" id="paymentProofContainer" style="display: none;">
                        <p>Silahkan kirim melalui QR code atau nomor ini: <strong>0858-4710-3494</strong></p>
                        <img src="../assets/img/qr_code_dana.jpg" alt="QR Code Dana" style="max-width: 100%; margin-bottom: 10px;">
                        <label for="payment_proof" style="display: block; margin-top: 10px;">Upload Bukti Pembayaran</label>
                        <input type="file" name="payment_proof" id="payment_proof" class="form-control" accept="image/png, image/jpeg" disabled>
                    </div>
                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-primary" onclick="confirmCheckout()">Checkout</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php require_once '../includes/_bottom.php'; ?>

<script>
    function confirmDelete(id) {
        iziToast.question({
            timeout: false,
            close: false,
            overlay: true,
            displayMode: 'once',
            title: 'Konfirmasi Hapus',
            message: 'Apakah Anda yakin ingin menghapus item ini?',
            position: 'center',
            buttons: [
                ['<button>Ya</button>', function(instance, toast) {
                    window.location.href = `cart/remove_from_cart.php?id=${id}`;
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

    function confirmUpdate() {
        iziToast.question({
            timeout: false,
            close: false,
            overlay: true,
            displayMode: 'once',
            title: 'Konfirmasi Simpan',
            message: 'Apakah Anda yakin ingin menyimpan perubahan?',
            position: 'center',
            buttons: [
                ['<button>Ya</button>', function(instance, toast) {
                    document.getElementById('updateCartForm').submit();
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

    function confirmCheckout() {
        iziToast.question({
            timeout: false,
            close: false,
            overlay: true,
            displayMode: 'once',
            title: 'Konfirmasi Checkout',
            message: 'Apakah Anda yakin ingin melanjutkan checkout?',
            position: 'center',
            buttons: [
                ['<button>Ya</button>', function(instance, toast) {
                    document.getElementById('checkoutForm').submit();
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

    function calculateBookingEnd() {
        const startInput = document.getElementById('booking_start');
        const durationInput = document.getElementById('booking_duration');
        const endInput = document.getElementById('booking_end');

        startInput.addEventListener('change', updateEndTime);
        durationInput.addEventListener('change', updateEndTime);

        function updateEndTime() {
            const startTime = new Date(startInput.value);
            const duration = parseInt(durationInput.value, 10);

            if (!isNaN(startTime.getTime()) && duration) {
                const endTime = new Date(startTime.getTime() + duration * 60000);
                const endTimeString = endTime.toISOString().slice(0, 16);
                endInput.value = endTimeString;
            }
        }
    }

    function updateAvailableTablesLocally() {
        const startInput = document.getElementById('booking_start');
        const durationInput = document.getElementById('booking_duration');
        const mejaSelect = document.getElementById('meja_id');

        function filterTables() {
            const startTime = new Date(startInput.value);
            const duration = parseInt(durationInput.value, 10);
            if (isNaN(startTime.getTime()) || !duration) {
                console.error("Invalid start time or duration.");
                return;
            }

            const endTime = new Date(startTime.getTime() + duration * 60000);

            // Filter meja yang tersedia berdasarkan data pemesanan
            const availableTables = allMeja.filter(meja => {
                return !allPemesanan.some(pesan => {
                    const bookingStart = new Date(pesan.booking_start);
                    const bookingEnd = new Date(pesan.booking_end);
                    return meja.id === pesan.meja_id &&
                        ((startTime < bookingEnd && endTime > bookingStart) ||
                            (startTime >= bookingStart && endTime <= bookingEnd));
                });
            });

            // Perbarui dropdown meja
            mejaSelect.innerHTML = '<option value="" disabled selected>Pilih meja</option>';
            availableTables.forEach(meja => {
                mejaSelect.innerHTML += `<option value="${meja.id}">Meja ${meja.table_number}</option>`;
            });
        }

        startInput.addEventListener('change', filterTables);
        durationInput.addEventListener('change', filterTables);
    }


    function togglePaymentProof() {
        const paymentDana = document.getElementById('payment_dana');
        const paymentProofContainer = document.getElementById('paymentProofContainer');
        const paymentProofInput = document.getElementById('payment_proof');

        paymentDana.addEventListener('change', () => {
            if (paymentDana.checked) {
                paymentProofContainer.style.display = 'block';
                paymentProofInput.disabled = false;
            }
        });

        const paymentCash = document.getElementById('payment_cash');
        paymentCash.addEventListener('change', () => {
            if (paymentCash.checked) {
                paymentProofContainer.style.display = 'none';
                paymentProofInput.disabled = true;
            }
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        togglePaymentProof();
        calculateBookingEnd();
        updateAvailableTablesLocally();
        setInterval(updateAvailableTablesLocally, 1000);
    });
</script>