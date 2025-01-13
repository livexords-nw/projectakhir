<?php
require_once '../helper/connection.php';

if (!isset($_GET['start']) || !isset($_GET['end'])) {
    echo json_encode(['error' => 'Invalid parameters']);
    exit;
}

$start = $_GET['start'];
$end = $_GET['end'];

try {
    // Query untuk mendapatkan meja yang tidak bertabrakan dengan waktu booking
    $query = "
        SELECT m.id, m.table_number 
        FROM meja m
        WHERE m.id NOT IN (
            SELECT meja_id 
            FROM pemesanan 
            WHERE 
                (booking_start < ? AND booking_end > ?) OR 
                (booking_start < ? AND booking_end > ?) OR 
                (booking_start >= ? AND booking_end <= ?)
        )
    ";

    $stmt = $connection->prepare($query);
    $stmt->bind_param('ssssss', $end, $start, $end, $start, $start, $end);
    $stmt->execute();
    $result = $stmt->get_result();

    $availableTables = [];
    while ($row = $result->fetch_assoc()) {
        $availableTables[] = $row;
    }

    echo json_encode($availableTables);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
