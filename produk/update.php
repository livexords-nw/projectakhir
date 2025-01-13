<?php
require_once '../helper/connection.php';

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
            throw new Exception('ID produk tidak valid.');
        }

        // Ambil data produk lama
        $produkLama = $this->getProdukById($id);
        if (!$produkLama) {
            throw new Exception('Produk tidak ditemukan.');
        }

        // Validasi data
        $this->validate($data, $file);

        // Proses file gambar (jika ada)
        $gambarBaru = $produkLama['gambar'];
        if (!empty($file['name'])) {
            // Hapus gambar lama jika ada
            $this->hapusFileGambar($produkLama['gambar']);
            $gambarBaru = $this->uploadFile($file, $data['nama']);
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
            $_SESSION['info'] = [
                'status' => 'success',
                'message' => "Produk berhasil diperbarui. Nama: {$data['nama']} | Harga: Rp" . number_format($data['harga'], 0, ',', '.') . " | Stock: {$data['stock']}."
            ];
            return true;
        } else {
            throw new Exception('Gagal memperbarui data produk.');
        }
    }

    // Fungsi untuk menghapus file gambar
    private function hapusFileGambar($gambar)
    {
        $uploadDir = '../uploads/';
        $filePath = $uploadDir . $gambar;

        // Cek apakah file gambar ada
        if (file_exists($filePath)) {
            if (!unlink($filePath)) {
                throw new Exception("Gagal menghapus file gambar: {$gambar}");
            }
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
            throw new Exception(json_encode($errors));
        }
    }

    private function uploadFile($file, $namaProduk)
    {
        $uploadDir = '../uploads/';
        $fileBaseName = pathinfo($file['name'], PATHINFO_FILENAME);
        $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);

        // Format nama file dengan timestamp dan nama produk
        $timestamp = date('YmdHis');
        $safeFileName = preg_replace('/[^a-zA-Z0-9_]/', '_', $fileBaseName);
        $safeNamaProduk = preg_replace('/[^a-zA-Z0-9_]/', '_', $namaProduk);
        $newFileName = "{$timestamp}_{$safeFileName}_{$safeNamaProduk}.{$fileExtension}";

        $targetPath = $uploadDir . $newFileName;

        // Pindahkan file
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new Exception('Gagal mengunggah file gambar.');
        }

        return $newFileName;
    }
}

// Eksekusi proses
session_start();
try {
    $produk = new Produk($connection);
    $data = [
        'nama' => $_POST['nama'] ?? '',
        'harga' => $_POST['harga'] ?? '',
        'stock' => isset($_POST['stock']) ? 1 : 0
    ];

    $produk->update($_POST['id'], $data, $_FILES['gambar']);

    $_SESSION['info'] = [
        'status' => 'success',
        'message' => "Produk berhasil diperbarui. Nama: {$data['nama']} | Harga: Rp" . number_format($data['harga'], 0, ',', '.') . " | Stock: {$data['stock']}."
    ];

    header('Location: ./index.php');
    exit;
} catch (Exception $e) {
    $errorMessage = json_decode($e->getMessage(), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $errorMessage = ['error' => 'Terjadi kesalahan pada sistem.'];
    }
    $_SESSION['info'] = [
        'status' => 'danger',
        'message' => implode(' | ', $errorMessage)
    ];

    $_SESSION['old'] = $_POST;
    $_SESSION['errors'] = $errorMessage;
    header('Location: ./edit.php?id=' . $_POST['id']);
    exit;
}
