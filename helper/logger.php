<?php

// Fungsi untuk menulis log ke file
function write_log($message, $level = 'INFO')
{
    // Set timezone Indonesia (WIB)
    date_default_timezone_set('Asia/Jakarta');

    // Ambil waktu sekarang dalam WIB
    $timestamp = date('Y-m-d H:i:s');

    // Ambil detail debug backtrace (file dan baris pemanggil)
    $debug_backtrace = debug_backtrace();
    $caller_file = isset($debug_backtrace[0]['file']) ? $debug_backtrace[0]['file'] : 'unknown_file';
    $caller_line = isset($debug_backtrace[0]['line']) ? $debug_backtrace[0]['line'] : 'unknown_line';

    // Buat format log yang lebih detail
    $logMessage = "[{$timestamp}] [{$level}] [File: {$caller_file}] [Line: {$caller_line}] - {$message}" . PHP_EOL;

    // Tentukan file log
    $logFile = '../logs/app.log';

    // Tulis log ke file
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}
?>
