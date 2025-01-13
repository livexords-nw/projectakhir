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

// Fungsi untuk mengambil log dengan totalisasi
function getLogsAndTotal($logFile, $search = '', $dateFrom = '', $dateTo = '', $timeFrom = '', $timeTo = '')
{
    $logs = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $totalAmount = 0;

    // Filter log berdasarkan pencarian
    if ($search) {
        $logs = array_filter($logs, function ($log) use ($search) {
            return stripos($log, $search) !== false;
        });
    }

    // Filter berdasarkan rentang tanggal (dari dan sampai)
    if ($dateFrom) {
        $logs = array_filter($logs, function ($log) use ($dateFrom) {
            preg_match('/\[(.*?)\]/', $log, $matches);
            $logDate = isset($matches[1]) ? explode(' ', $matches[1])[0] : '';
            return strtotime($logDate) >= strtotime($dateFrom);
        });
    }
    if ($dateTo) {
        $logs = array_filter($logs, function ($log) use ($dateTo) {
            preg_match('/\[(.*?)\]/', $log, $matches);
            $logDate = isset($matches[1]) ? explode(' ', $matches[1])[0] : '';
            return strtotime($logDate) <= strtotime($dateTo);
        });
    }

    // Filter berdasarkan rentang waktu (dari dan sampai)
    if ($timeFrom) {
        $logs = array_filter($logs, function ($log) use ($timeFrom) {
            preg_match('/\[(.*?)\]/', $log, $matches);
            $logTime = isset($matches[1]) ? explode(' ', $matches[1])[1] : '';
            return strtotime($logTime) >= strtotime($timeFrom);
        });
    }
    if ($timeTo) {
        $logs = array_filter($logs, function ($log) use ($timeTo) {
            preg_match('/\[(.*?)\]/', $log, $matches);
            $logTime = isset($matches[1]) ? explode(' ', $matches[1])[1] : '';
            return strtotime($logTime) <= strtotime($timeTo);
        });
    }

    // Hitung total dari log yang memenuhi filter
    foreach ($logs as $log) {
        if (preg_match('/Total=([0-9.]+)/', $log, $matches)) {
            $totalAmount += (float) $matches[1];
        }
    }

    return [array_reverse($logs), $totalAmount];
}

// Mendapatkan parameter pencarian dan filter
$search = $_GET['search'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$timeFrom = $_GET['time_from'] ?? '';
$timeTo = $_GET['time_to'] ?? '';

// Pagination
$logsPerPage = 20;
list($filteredLogs, $totalAmount) = getLogsAndTotal($logFile, $search, $dateFrom, $dateTo, $timeFrom, $timeTo);
$totalLogs = count($filteredLogs);
$totalPages = ceil($totalLogs / $logsPerPage);
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
$page = max(1, min($page, $totalPages));

$startLine = ($page - 1) * $logsPerPage;
$logs = array_slice($filteredLogs, $startLine, $logsPerPage);

function renderPagination($currentPage, $totalPages, $search, $dateFrom, $dateTo, $timeFrom, $timeTo)
{
    $html = '<ul class="pagination">';
    if ($currentPage > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="?page=' . ($currentPage - 1) . '&search=' . urlencode($search) . '&date_from=' . urlencode($dateFrom) . '&date_to=' . urlencode($dateTo) . '&time_from=' . urlencode($timeFrom) . '&time_to=' . urlencode($timeTo) . '">&laquo;</a></li>';
    }
    if ($currentPage > 3) {
        $html .= '<li class="page-item"><a class="page-link" href="?page=1&search=' . urlencode($search) . '&date_from=' . urlencode($dateFrom) . '&date_to=' . urlencode($dateTo) . '&time_from=' . urlencode($timeFrom) . '&time_to=' . urlencode($timeTo) . '">1</a></li>';
        $html .= '<li class="page-item"><span class="page-link">...</span></li>';
    }
    for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++) {
        $html .= '<li class="page-item ' . ($i === $currentPage ? 'active' : '') . '"><a class="page-link" href="?page=' . $i . '&search=' . urlencode($search) . '&date_from=' . urlencode($dateFrom) . '&date_to=' . urlencode($dateTo) . '&time_from=' . urlencode($timeFrom) . '&time_to=' . urlencode($timeTo) . '">' . $i . '</a></li>';
    }
    if ($currentPage < $totalPages - 2) {
        $html .= '<li class="page-item"><span class="page-link">...</span></li>';
        $html .= '<li class="page-item"><a class="page-link" href="?page=' . $totalPages . '&search=' . urlencode($search) . '&date_from=' . urlencode($dateFrom) . '&date_to=' . urlencode($dateTo) . '&time_from=' . urlencode($timeFrom) . '&time_to=' . urlencode($timeTo) . '">' . $totalPages . '</a></li>';
    }
    if ($currentPage < $totalPages) {
        $html .= '<li class="page-item"><a class="page-link" href="?page=' . ($currentPage + 1) . '&search=' . urlencode($search) . '&date_from=' . urlencode($dateFrom) . '&date_to=' . urlencode($dateTo) . '&time_from=' . urlencode($timeFrom) . '&time_to=' . urlencode($timeTo) . '">&raquo;</a></li>';
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
            <form method="get" class="form-inline mb-3" id="searchForm">
                <div class="form-group mr-2">
                    <input type="text" id="searchInput" name="search" class="form-control" placeholder="Cari log" value="<?= htmlspecialchars($search); ?>">
                </div>
                <div class="form-group mr-2">
                    <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($dateFrom); ?>" placeholder="Dari tanggal">
                </div>
                <div class="form-group mr-2">
                    <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($dateTo); ?>" placeholder="Sampai tanggal">
                </div>
                <div class="form-group mr-2">
                    <input type="time" name="time_from" class="form-control" value="<?= htmlspecialchars($timeFrom); ?>" placeholder="Dari jam">
                </div>
                <div class="form-group mr-2">
                    <input type="time" name="time_to" class="form-control" value="<?= htmlspecialchars($timeTo); ?>" placeholder="Sampai jam">
                </div>
                <button type="submit" class="btn btn-primary">Cari</button>
                <a href="logs.php" class="btn btn-primary ml-2">Reset</a>
            </form>

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

            <div class="mt-3">
                <h5>Total Nilai Pesanan: <strong>Rp<?= number_format($totalAmount, 2, ',', '.'); ?></strong></h5>
            </div>

            <?= renderPagination($page, $totalPages, $search, $dateFrom, $dateTo, $timeFrom, $timeTo); ?>
        </div>
    </div>
</section>

<?php require_once '../includes/_bottom.php'; ?>