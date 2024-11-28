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

    public function delete($id)
    {
        // Validasi ID
        if (!$id || !is_numeric($id)) {
            write_log("Validasi gagal: ID produk tidak valid (ID: {$id}).", 'ERROR');
            throw new Exception('ID produk tidak valid.');
        }

        // Ambil data produk
        $produk = $this->getProdukById($id);
        if (!$produk) {
            write_log("Produk tidak ditemukan: ID ({$id}) tidak ada.", 'ERROR');
            throw new Exception('Data produk tidak ditemukan.');
        }

        // Hapus file gambar jika ada
        $this->deleteFile($produk['gambar']);

        // Hapus data dari database
        $stmt = $this->connection->prepare("DELETE FROM produk WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute() && $stmt->affected_rows > 0) {
            // Log keberhasilan penghapusan produk
            write_log("Produk ID {$id} berhasil dihapus. Nama: {$produk['nama']}", 'SUCCESS');

            // Menyimpan pesan sukses dengan detail nama produk
            $_SESSION['info'] = [
                'status' => 'success',
                'message' => "Produk '{$produk['nama']}' berhasil dihapus."
            ];
            return true;
        } else {
            write_log("Gagal menghapus produk ID {$id}. Error: " . $stmt->error, 'ERROR');
            throw new Exception('Gagal menghapus data produk.');
        }
    }

    private function getProdukById($id)
    {
        $stmt = $this->connection->prepare("SELECT * FROM produk WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    private function deleteFile($filename)
    {
        $filePath = "../uploads/" . $filename;
        if (file_exists($filePath) && is_file($filePath)) {
            if (!unlink($filePath)) {
                write_log("Gagal menghapus file gambar: {$filename}", 'ERROR');
                throw new Exception('Gagal menghapus file gambar: ' . $filename);
            }
        }
    }
}

// Eksekusi proses
session_start();
try {
    $produk = new Produk($connection);
    $produk->delete($_GET['id']);
} catch (Exception $e) {
    // Menyimpan pesan error ke dalam session
    $_SESSION['info'] = [
        'status' => 'danger',
        'message' => $e->getMessage()
    ];
    write_log("Error menghapus produk: " . $e->getMessage(), 'ERROR');
}

header('Location: ./index.php');
exit;

?>
