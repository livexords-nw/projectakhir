<?php
require_once '../includes/_top.php';
require_once '../helper/logger.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if ($_SESSION['role'] !== 'admin') {
    header('Location: ./index.php');
    exit;
}

// File log
$logFile = '../logs/app.log';

// Set timezone Indonesia (WIB)
date_default_timezone_set('Asia/Jakarta');

// Fungsi untuk mengambil log berdasarkan filter
function getLogsAndTotal($logFile, $status = '', $startDate = '', $endDate = '', $startTime = '', $endTime = '')
{
    $logs = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $totalAmount = 0;

    // Filter log berdasarkan status
    if ($status) {
        $logs = array_filter($logs, function ($log) use ($status) {
            return stripos($log, "Pesanan $status") !== false;
        });
    }

    // Filter log berdasarkan tanggal dan waktu
    if ($startDate && $endDate) {
        $logs = array_filter($logs, function ($log) use ($startDate, $endDate, $startTime, $endTime) {
            if (preg_match('/\[(.*?)\]/', $log, $matches)) {
                $logTime = strtotime($matches[1]);
                $startDateTime = strtotime("$startDate $startTime");
                $endDateTime = strtotime("$endDate $endTime");

                return $logTime >= $startDateTime && $logTime <= $endDateTime;
            }
            return false;
        });
    }

    // Hitung total dari log sesuai status
    foreach ($logs as $log) {
        if (($status === 'approved' || $status === 'dibatalkan') && preg_match('/Total=([0-9.]+)/', $log, $matches)) {
            $totalAmount += (float) $matches[1];
        }
    }

    return [array_reverse($logs), $totalAmount];
}

// Mendapatkan parameter filter
$status = $_GET['status'] ?? '';
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';
$startTime = $_GET['start_time'] ?? '00:00';
$endTime = $_GET['end_time'] ?? '23:59';

// Pagination
$logsPerPage = 20;
list($filteredLogs, $totalAmount) = getLogsAndTotal($logFile, $status, $startDate, $endDate, $startTime, $endTime);
$totalLogs = count($filteredLogs);
$totalPages = ceil($totalLogs / $logsPerPage);
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, min($page, $totalPages));

$startLine = ($page - 1) * $logsPerPage;
$logs = array_slice($filteredLogs, $startLine, $logsPerPage);

function renderPagination($currentPage, $totalPages, $queryParams)
{
    $html = '<ul class="pagination">';
    if ($currentPage > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="?' . http_build_query(array_merge($queryParams, ['page' => $currentPage - 1])) . '">&laquo;</a></li>';
    }
    if ($currentPage > 3) {
        $html .= '<li class="page-item"><a class="page-link" href="?' . http_build_query(array_merge($queryParams, ['page' => 1])) . '">1</a></li>';
        $html .= '<li class="page-item"><span class="page-link">...</span></li>';
    }
    for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++) {
        $html .= '<li class="page-item ' . ($i === $currentPage ? 'active' : '') . '"><a class="page-link" href="?' . http_build_query(array_merge($queryParams, ['page' => $i])) . '">' . $i . '</a></li>';
    }
    if ($currentPage < $totalPages - 2) {
        $html .= '<li class="page-item"><span class="page-link">...</span></li>';
        $html .= '<li class="page-item"><a class="page-link" href="?' . http_build_query(array_merge($queryParams, ['page' => $totalPages])) . '">' . $totalPages . '</a></li>';
    }
    if ($currentPage < $totalPages) {
        $html .= '<li class="page-item"><a class="page-link" href="?' . http_build_query(array_merge($queryParams, ['page' => $currentPage + 1])) . '">&raquo;</a></li>';
    }
    $html .= '</ul>';
    return $html;
}
?>

<section class="section">
    <div class="section-header">
        <h1>Log Pesanan</h1>
    </div>
    <div class="card">
        <div class="card-body">
            <form method="get" class="form-inline mb-3" id="filterForm">
                <div class="form-group mr-2">
                    <label for="status">Status</label>
                    <select name="status" id="status" class="form-control" title="Pilih status pesanan yang ingin ditampilkan">
                        <option value="approved" <?= $status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="dibatalkan" <?= $status === 'dibatalkan' ? 'selected' : ''; ?>>Dibatalkan</option>
                    </select>
                </div>
                <div class="form-group mr-1">
                    <label for="start_date">Dari Tanggal</label>
                    <input
                        type="date"
                        name="start_date"
                        id="start_date"
                        class="form-control"
                        value="<?= htmlspecialchars($startDate); ?>"
                        placeholder="Pilih tanggal mulai"
                        title="Pilih tanggal awal untuk menyaring data">
                </div>
                <div class="form-group mr-1">
                    <label for="end_date">Hingga Tanggal</label>
                    <input
                        type="date"
                        name="end_date"
                        id="end_date"
                        class="form-control"
                        value="<?= htmlspecialchars($endDate); ?>"
                        placeholder="Pilih tanggal akhir"
                        title="Pilih tanggal akhir untuk menyaring data">
                </div>
                <div class="form-group mr-1">
                    <label for="start_time">Dari Jam</label>
                    <input
                        type="time"
                        name="start_time"
                        id="start_time"
                        class="form-control"
                        value="<?= htmlspecialchars($startTime); ?>"
                        placeholder="Pilih jam mulai"
                        title="Pilih waktu awal untuk menyaring data">
                </div>
                <div class="form-group mr-1">
                    <label for="end_time">Hingga Jam</label>
                    <input
                        type="time"
                        name="end_time"
                        id="end_time"
                        class="form-control"
                        value="<?= htmlspecialchars($endTime); ?>"
                        placeholder="Pilih jam akhir">
                </div>
                <button
                    type="submit"
                    class="btn btn-primary">
                    Terapkan
                </button>
                <a
                    href="logs.php"
                    class="btn btn-danger ml-2">
                    Reset
                </a>

            </form>

            <div class="mt-3">
                <h5>Total Nilai Pesanan <?= $status === 'approved' ? 'approved' : ($status === 'dibatalkan' ? 'Dibatalkan' : ''); ?>: <strong>Rp<?= number_format($totalAmount, 2, ',', '.'); ?></strong></h5>
            </div>

            <div id="logTable">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Pesan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="2" class="text-center">Tidak ada log yang ditemukan.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <?php
                                preg_match('/\[(.*?)\] (.*)/', $log, $matches);
                                $time = isset($matches[1]) ? $matches[1] : '-';
                                $message = isset($matches[2]) ? $matches[2] : '-';
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($time); ?></td>
                                    <td><?= htmlspecialchars($message); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?= renderPagination($page, $totalPages, $_GET); ?>
        </div>
    </div>
</section>

<?php require_once '../includes/_bottom.php'; ?>