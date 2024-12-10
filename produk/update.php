<?php

require_once '../helper/connection.php';
require_once '../helper/logger.php';

class Produk
{
    private $connection;

    public function __construct($connection)
    {
        $this->connection = $connection;
    }

    public function update($id, $data, $file)
    {
        // Validasi ID
        if (!$id || !is_numeric($id)) {
            write_log("Validasi gagal: ID produk tidak valid (ID: {$id}).", 'ERROR');
            throw new Exception('ID produk tidak valid.');
        }

        // Ambil data produk lama
        $produkLama = $this->getProdukById($id);
        if (!$produkLama) {
            write_log("Produk tidak ditemukan: ID ({$id}) tidak ada.", 'ERROR');
            throw new Exception('Produk tidak ditemukan.');
        }

        // Validasi data
        $this->validate($data, $file);

        // Proses file gambar (jika ada)
        $gambarBaru = $produkLama['gambar'];
        if (!empty($file['name'])) {
            $gambarBaru = $this->handleFileUpload($file);
        }

        // Update data di database
        $query = "UPDATE produk SET 
            nama = ?, 
            harga = ?, 
            stock = ?, 
            gambar = ? 
        WHERE id = ?";

        $stmt = $this->connection->prepare($query);
        $stmt->bind_param(
            "sdssi",
            $data['nama'],
            $data['harga'],
            $data['stock'],
            $gambarBaru,
            $id
        );

        if ($stmt->execute()) {
            // Log keberhasilan update produk
            write_log("Produk ID {$id} berhasil diperbarui. Data: " . json_encode($data) . " | Gambar: {$gambarBaru}", 'SUCCESS');

            // Menyimpan pesan sukses dengan detail produk yang diperbarui
            $_SESSION['info'] = [
                'status' => 'success',
                'message' => "Produk berhasil diperbarui. Nama: {$data['nama']} | Harga: Rp" . number_format($data['harga'], 0, ',', '.') . " | Stock: {$data['stock']}."
            ];

            return true;
        } else {
            // Log kegagalan update produk
            write_log("Gagal memperbarui produk ID {$id}. Error: " . $stmt->error, 'ERROR');
            throw new Exception('Gagal memperbarui data produk.');
        }
    }

    private function getProdukById($id)
    {
        $stmt = $this->connection->prepare("SELECT * FROM produk WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    private function validate($data, $file)
    {
        $errors = [];
        if (empty($data['nama'])) {
            $errors['nama'] = 'Nama produk wajib diisi.';
        }

        if (empty($data['harga']) || !is_numeric($data['harga']) || $data['harga'] <= 0) {
            $errors['harga'] = 'Harga produk harus berupa angka positif atau terisi.';
        }

        if (!empty($file['name'])) {
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedTypes)) {
                $errors['gambar'] = 'Format file gambar tidak valid. Gunakan JPG, PNG, atau GIF.';
            }
        }

        if (!empty($errors)) {
            write_log("Validasi gagal: " . json_encode($errors), 'ERROR');
            throw new Exception(json_encode($errors));
        }
    }

    private function handleFileUpload($file)
    {
        $uploadDir = '../uploads/';
        $uploadFile = $uploadDir . basename($file['name']);
        if (!move_uploaded_file($file['tmp_name'], $uploadFile)) {
            write_log("Gagal mengunggah file gambar: " . $file['name'], 'ERROR');
            throw new Exception('Gagal mengunggah file gambar.');
        }
        return basename($file['name']);
    }
}

// Eksekusi proses
session_start();
try {
    // Ambil data dari form
    $produk = new Produk($connection);
    $data = [
        'nama' => $_POST['nama'] ?? '',
        'harga' => $_POST['harga'] ?? '',
        'stock' => isset($_POST['stock']) ? 1 : 0
    ];

    // Melakukan update produk
    $produk->update($_POST['id'], $data, $_FILES['gambar']);

    // Menyimpan pesan sukses ke dalam session
    $_SESSION['info'] = [
        'status' => 'success',
        'message' => "Produk berhasil diperbarui. Nama: {$data['nama']} | Harga: Rp" . number_format($data['harga'], 0, ',', '.') . " | Stock: {$data['stock']}."
    ];

    // Arahkan ke halaman index setelah berhasil
    header('Location: ./index.php');
    exit;
} catch (Exception $e) {
    // Menyimpan pesan error ke dalam session
    $errorMessage = json_decode($e->getMessage(), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $errorMessage = ['error' => 'Terjadi kesalahan pada sistem.'];
    }
    $_SESSION['info'] = [
        'status' => 'danger',
        'message' => implode(' | ', $errorMessage)
    ];
    write_log("Error memperbarui produk: " . $e->getMessage(), 'ERROR');

    // Menyimpan input lama untuk digunakan di form edit
    $_SESSION['old'] = $_POST;
    $_SESSION['errors'] = $errorMessage; // Menyimpan error untuk ditampilkan di form
    header('Location: ./edit.php?id=' . $_POST['id']);
    exit;
}
