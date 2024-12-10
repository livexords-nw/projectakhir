<?php
session_start();
require_once '../helper/logger.php';

// Display notifications if available
if (isset($_SESSION['info'])) {
    echo "<script>";
    echo "iziToast." . $_SESSION['info']['status'] . "({";
    echo "title: '" . $_SESSION['info']['message'] . "',";
    echo "position: 'topRight',";
    echo "transitionIn: 'flipInX',";
    echo "transitionOut: 'flipOutX'";
    echo "});";
    echo "</script>";

    unset($_SESSION['info']); // Clear info after display
}

// Get table number from session
$table_number = $_SESSION['table_number'] ?? 'Unknown'; // Default to 'Unknown' if no table number exists

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

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Pemesanan</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Izitoast CSS -->
    <link href="https://cdn.jsdelivr.net/npm/izitoast@1.4.0/dist/css/iziToast.min.css" rel="stylesheet">

    <!-- Custom Styles -->
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f8f9fa;
        }

        .container {
            margin-top: 50px;
            text-align: center;
        }

        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .btn-back {
            margin-top: 20px;
        }

        .thank-you-msg {
            font-size: 24px;
            font-weight: bold;
            color: #28a745;
        }

        .order-number {
            font-size: 18px;
            color: #6c757d;
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="card p-4">
            <div class="login-brand text-center mb-3">
                <img src="../assets/img/avatar/Tea_Bliss_logo.png" alt="logo" class="img-fluid" style="max-width: 150px; height: auto;">
            </div>
            <h2 class="thank-you-msg">Terima kasih telah berbelanja!</h2>
            <p class="order-number">Pesanan Anda telah berhasil diproses. Nomor meja Anda adalah <strong>#<?= htmlspecialchars($table_number); ?></strong></p>
            <p class="order-number">Silakan menuju ke kasir untuk melakukan pembayaran.</p>

            <a href="../dashboard/user_dashboard.php" class="btn btn-primary btn-back">Kembali ke Dashboard</a>
        </div>
    </div>

    <!-- jQuery and Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Izitoast JS -->
    <script src="https://cdn.jsdelivr.net/npm/izitoast@1.4.0/dist/js/iziToast.min.js"></script>

</body>

</html>