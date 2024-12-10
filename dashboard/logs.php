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

// Fungsi untuk mengambil log dengan filter pencarian yang fleksibel
function getLogs($logFile, $search = '', $dateFrom = '', $dateTo = '', $timeFrom = '', $timeTo = '')
{
    $logs = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    // Filter log berdasarkan pencarian
    if ($search) {
        $logs = array_filter($logs, function ($log) use ($search) {
            return stripos($log, $search) !== false;  // Mencari kata kunci
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

    // Membalikkan array untuk menampilkan log terbaru di awal
    return array_reverse($logs);
}

// Mendapatkan parameter pencarian dan filter
$search = $_GET['search'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$timeFrom = $_GET['time_from'] ?? '';
$timeTo = $_GET['time_to'] ?? '';

// Pagination
$logsPerPage = 20; // Baris log per halaman
$filteredLogs = getLogs($logFile, $search, $dateFrom, $dateTo, $timeFrom, $timeTo);
$totalLogs = count($filteredLogs);
$totalPages = ceil($totalLogs / $logsPerPage);
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
$page = max(1, min($page, $totalPages));

// Ambil log sesuai halaman
$startLine = ($page - 1) * $logsPerPage;
$logs = array_slice($filteredLogs, $startLine, $logsPerPage);
?>

<section class="section">
    <div class="section-header">
        <h1>Log Aktivitas</h1>
    </div>
    <div class="card">
        <div class="card-body">
            <!-- Form Pencarian dan Filter -->
            <form method="get" class="form-inline mb-3">
                <div class="form-group mr-2">
                    <input type="text" name="search" class="form-control" placeholder="Cari log" value="<?= htmlspecialchars($search); ?>">
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
                <!-- Tombol Reset -->
                <a href="logs.php" class="btn btn-primary ml-2">Reset</a>
            </form>

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

            <!-- Pagination -->
            <nav>
                <ul class="pagination">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i; ?>&search=<?= urlencode($search); ?>&date_from=<?= urlencode($dateFrom); ?>&date_to=<?= urlencode($dateTo); ?>&time_from=<?= urlencode($timeFrom); ?>&time_to=<?= urlencode($timeTo); ?>"><?= $i; ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
    </div>
</section>

<?php require_once '../includes/_bottom.php'; ?>