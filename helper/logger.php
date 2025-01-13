<?php

// Fungsi untuk menulis log ke file
function write_log($message, $level = 'INFO')
{
    // Set timezone Indonesia (WIB)
    date_default_timezone_set('Asia/Jakarta');

    // Ambil waktu sekarang dalam WIB
    $timestamp = date('Y-m-d H:i:s');

    // Buat format log yang lebih sederhana
    $logMessage = "[{$timestamp}] [{$level}] - {$message}" . PHP_EOL;

    // Tentukan file log
    $logDirectory = __DIR__ . '/../logs';
    $logFile = $logDirectory . '/app.log';

    // Periksa apakah folder logs ada, jika tidak buat
    if (!is_dir($logDirectory)) {
        mkdir($logDirectory, 0755, true);
    }

    // Tulis log ke file
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}
