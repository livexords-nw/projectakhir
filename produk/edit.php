<?php
require_once '../includes/_top.php';
require_once '../helper/connection.php';
require_once '../helper/logger.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ambil ID dari parameter GET
$id = $_GET['id'] ?? null;

// Pastikan ID ada dan valid
if (!$id || !is_numeric($id)) {
    // Jika ID tidak valid, kembalikan ke index dengan pesan error
    $_SESSION['info'] = ['status' => 'danger', 'message' => 'ID produk tidak valid.'];
    header('Location: ./index.php');
    exit;
}

// Ambil data produk berdasarkan ID
$query = mysqli_query($connection, "SELECT * FROM produk WHERE id = '$id'");
$row = mysqli_fetch_assoc($query);

// Jika data tidak ditemukan
if (!$row) {
    $_SESSION['info'] = ['status' => 'danger', 'message' => 'Data produk tidak ditemukan.'];
    header('Location: ./index.php');
    exit;
}

// Ambil data validasi jika ada
$errors = isset($_SESSION['errors']) ? $_SESSION['errors'] : [];
$old = isset($_SESSION['old']) ? $_SESSION['old'] : [];
$info = isset($_SESSION['info']) ? $_SESSION['info'] : null;

if ($info) {
  $status = $info['status']; 
  $message = $info['message'];

  if (is_array($message)) {
    $message = implode(' | ', $message);
  }
  
  echo "<script>
      document.addEventListener('DOMContentLoaded', function() {
          iziToast." . ($status === 'success' ? 'success' : 'error') . "({
              title: '" . ($status === 'success' ? 'Sukses' : 'Gagal') . "',
              message: '{$message}',
              position: 'topCenter',
              timeout: 5000
          });
      });
  </script>";
  
  unset($_SESSION['info']);
}

unset($_SESSION['errors'], $_SESSION['old']);

$username = $_SESSION['login']['username'] ?? 'Unknown User';
write_log("{$username} membuka form Edit Produk.", 'INFO');
?>

<section class="section">
  <div class="section-header d-flex justify-content-between">
    <h1>Ubah Data Produk</h1>
    <a href="./index.php" class="btn btn-light">Kembali</a>
  </div>
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-body">
          <!-- Form -->
          <form id="formupdate" action="./update.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?= htmlspecialchars($row['id']) ?>">
            <table cellpadding="8" class="w-100">
              <tr>
                <td>Nama Produk</td>
                <td>
                  <input class="form-control <?= isset($errors['nama']) ? 'is-invalid' : ''; ?>" type="text" name="nama" placeholder="Nama Produk" value="<?= isset($old['nama']) ? htmlspecialchars($old['nama'], ENT_QUOTES) : htmlspecialchars($row['nama'], ENT_QUOTES); ?>" required>
                  <?php if (isset($errors['nama'])): ?>
                    <div class="invalid-feedback">
                      <?= htmlspecialchars($errors['nama'], ENT_QUOTES); ?>
                    </div>
                  <?php else: ?>
                    <div class="invalid-feedback">
                      Nama produk wajib diisi.
                    </div>
                  <?php endif; ?>
                </td>
              </tr>
              <tr>
                <td>Harga</td>
                <td>
                  <input type="number" placeholder="Harga Produk" class="form-control <?= isset($errors['harga']) ? 'is-invalid' : ''; ?>" name="harga" value="<?= isset($old['harga']) ? htmlspecialchars($old['harga'], ENT_QUOTES) : htmlspecialchars($row['harga'], ENT_QUOTES); ?>" required>
                  <?php if (isset($errors['harga'])): ?>
                    <div class="invalid-feedback">
                      <?= htmlspecialchars($errors['harga'], ENT_QUOTES); ?>
                    </div>
                  <?php else: ?>
                    <div class="invalid-feedback">
                      Harga produk wajib diisi.
                    </div>
                  <?php endif; ?>
                </td>
              </tr>
              <tr>
                <td>Stok Tersedia</td>
                <td>
                  <input type="checkbox" name="stock" <?= ($old['stock'] ?? $row['stock']) ? 'checked' : '' ?>>
                  <div class="invalid-feedback">
                    Tersedia
                  </div>
                </td>
              </tr>
              <tr>
                <td>Gambar Produk</td>
                <td>
                  <input type="file" class="form-control <?= isset($errors['gambar']) ? 'is-invalid' : ''; ?>" name="gambar" accept="image/*">
                  <small>Gambar saat ini:</small><br>
                  <?php if (!empty($row['gambar'])): ?>
                    <img src="../uploads/<?= htmlspecialchars($row['gambar']) ?>" alt="Gambar Produk" width="150">
                  <?php else: ?>
                    <p><i>Tidak ada gambar</i></p>
                  <?php endif; ?>
                  <?php if (isset($errors['gambar'])): ?>
                    <div class="invalid-feedback">
                      <?= htmlspecialchars($errors['gambar'], ENT_QUOTES); ?>
                    </div>
                  <?php else: ?>
                    <div class="invalid-feedback">
                      Gambar produk wajib diunggah.
                    </div>
                  <?php endif; ?>
                </td>
              </tr>
              <tr>
                <td>
                  <button type="button" class="btn btn-primary" onclick="confirmUpdate()">Simpan</button>
                  <a href="./index.php" class="btn btn-danger ml-1">Batal</a>
                </td>
              </tr>
            </table>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>

<?php
require_once '../includes/_bottom.php';
?>

<script>
  function confirmUpdate() {
    iziToast.question({
      timeout: false,
      close: false,
      overlay: true,
      displayMode: 'once',
      title: 'Konfirmasi Simpan',
      message: 'Apakah Anda yakin ingin menyimpan perubahan?',
      position: 'center',
      buttons: [
        ['<button>Ya</button>', function (instance, toast) {
          document.getElementById('formupdate').submit();
          instance.hide({ transitionOut: 'fadeOut' }, toast, 'button');
        }],
        ['<button>Tidak</button>', function (instance, toast) {
          instance.hide({ transitionOut: 'fadeOut' }, toast, 'button');
        }]
      ]
    });
  }
</script>
