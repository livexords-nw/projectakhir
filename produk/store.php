<?php

require_once '../helper/connection.php';

class Produk
{
    private $connection;
    private $uploadDir;

    public function __construct($connection, $uploadDir = '../uploads/')
    {
        $this->connection = $connection;
        $this->uploadDir = $uploadDir;
    }

    public function store($data, $file, $username)
    {
        $errors = $this->validate($data, $file);
        if (!empty($errors)) {
            $this->logError($username, $errors, "Validasi gagal saat menambahkan produk.");
            $_SESSION['info'] = [
                'status' => 'danger',
                'message' => 'Gagal menambahkan produk. Periksa kembali input Anda.',
            ];
            return [
                'status' => 'failed',
                'errors' => $errors,
                'message' => 'Validasi gagal, periksa kembali input Anda.'
            ];
        }

        $fileName = $this->uploadFile($file['gambar'], $data['nama'], $username);

        if (!$fileName) {
            $this->logError($username, null, "Gagal mengunggah gambar.");
            $_SESSION['info'] = [
                'status' => 'danger',
                'message' => 'Gagal mengunggah gambar. Silakan coba lagi.',
            ];
            return [
                'status' => 'failed',
                'errors' => ['gambar' => 'Gagal mengunggah gambar.'],
                'message' => 'Gagal mengunggah gambar. Silakan coba lagi.'
            ];
        }

        $jumlah_terjual = 0;
        $query = $this->connection->prepare("INSERT INTO produk (nama, harga, stock, gambar, jumlah_terjual) VALUES (?, ?, ?, ?, ?)");
        $query->bind_param(
            "sdiss",
            $data['nama'],
            $data['harga'],
            $data['stock'],
            $fileName,
            $jumlah_terjual
        );

        if ($query->execute()) {
            $this->logSuccess($username, $data, "Berhasil menambahkan produk.");
            $_SESSION['info'] = [
                'status' => 'success',
                'message' => "Produk berhasil ditambahkan. Nama: {$data['nama']} | Harga: Rp" . number_format($data['harga'], 0, ',', '.') . "."
            ];
            $this->validateUploadedFiles();
            return [
                'status' => 'success',
                'message' => "Produk berhasil ditambahkan. Nama: {$data['nama']} | Harga: Rp" . number_format($data['harga'], 0, ',', '.') . "."
            ];
        } else {
            $this->logError($username, null, "Gagal menyimpan produk. Error: " . $this->connection->error);
            $_SESSION['info'] = [
                'status' => 'danger',
                'message' => 'Terjadi kesalahan saat menyimpan data produk. Silakan coba lagi.',
            ];
            return [
                'status' => 'failed',
                'errors' => ['database' => 'Gagal menyimpan data produk.'],
                'message' => 'Terjadi kesalahan saat menyimpan data produk.'
            ];
        }
    }

    private function uploadFile($file, $username, $namaProduk)
    {
        // Bersihkan nama file
        $fileBaseName = pathinfo($file['name'], PATHINFO_FILENAME);
        $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);

        // Buat nama file baru: datetime_namagambar_namaproduk
        $timestamp = date('YmdHis');
        $safeFileName = preg_replace('/[^a-zA-Z0-9_]/', '_', $fileBaseName);
        $safeNamaProduk = preg_replace('/[^a-zA-Z0-9_]/', '_', $namaProduk);
        $newFileName = "{$timestamp}_{$safeFileName}_{$safeNamaProduk}.{$fileExtension}";

        $targetPath = $this->uploadDir . $newFileName;

        // Pindahkan file
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            $this->logError($username, null, "Gagal mengunggah gambar: {$file['name']}");
            return false;
        }

        return $newFileName;
    }

    private function validateUploadedFiles()
    {
        $uploadedFiles = array_diff(scandir($this->uploadDir), ['.', '..']);
        $query = $this->connection->query("SELECT gambar FROM produk");
        $validFiles = $query->fetch_all(MYSQLI_ASSOC);
        $validFiles = array_column($validFiles, 'gambar');

        foreach ($uploadedFiles as $file) {
            if (!in_array($file, $validFiles)) {
                unlink($this->uploadDir . $file);
            }
        }
    }

    private function validate($data, $file)
    {
        $errors = [];
        if (empty($data['nama'])) {
            $errors['nama'] = 'Nama produk wajib diisi.';
        } elseif ($this->isNamaProdukExists($data['nama'])) {
            $errors['nama'] = 'Nama produk sudah ada, gunakan nama lain.';
        }

        if (empty($data['harga'])) {
            $errors['harga'] = 'Harga produk wajib diisi.';
        } elseif (!is_numeric($data['harga']) || $data['harga'] <= 0) {
            $errors['harga'] = 'Harga produk harus berupa angka positif.';
        }

        if (isset($file['gambar']) && $file['gambar']['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($file['gambar']['type'], $allowedTypes)) {
                $errors['gambar'] = 'Format gambar tidak valid. Gunakan JPG, PNG, atau GIF.';
            }
        } else {
            $errors['gambar'] = 'Gambar produk wajib diunggah.';
        }

        return $errors;
    }

    private function isNamaProdukExists($nama)
    {
        $query = $this->connection->prepare("SELECT COUNT(*) AS count FROM produk WHERE nama = ?");
        $query->bind_param("s", $nama);
        $query->execute();
        $result = $query->get_result();
        $row = $result->fetch_assoc();
        return $row['count'] > 0;
    }

    private function logError($username, $errors, $message)
    {
        $logMessage = "{$username} - {$message}";
        if ($errors) {
            $logMessage .= " | Errors: " . json_encode($errors);
        }
    }

    private function logSuccess($username, $data, $message)
    {
        $logMessage = "{$username} - {$message} | Data: " . json_encode($data);
    }
}

// Eksekusi
session_start();
$username = $_SESSION['login']['username'] ?? 'Unknown User';

$data = [
    'nama' => $_POST['nama'] ?? '',
    'harga' => $_POST['harga'] ?? '',
    'stock' => isset($_POST['stock']) ? 1 : 0
];

$produk = new Produk($connection);
$result = $produk->store($data, $_FILES, $username);

if ($result['status'] === 'failed') {
    $_SESSION['errors'] = $result['errors'];
    $_SESSION['old'] = $data;
    header('Location: ./create.php');
    exit;
}

header('Location: ./index.php');
exit;
