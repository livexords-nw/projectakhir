<?php
if (session_status() == PHP_SESSION_NONE) {
  session_start();
}

require_once '../includes/_top.php';
require_once '../helper/connection.php';
require_once '../helper/logger.php';

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
          iziToast." . ($status === 'success' ? 'success' : 'error') . "( {
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
?>

<section class="section">
  <div class="section-header d-flex justify-content-between">
    <h1>Tambah Data Produk</h1>
    <a href="./index.php" class="btn btn-primary">Kembali</a>
  </div>
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-body">
          <!-- Formulir Produk -->
          <form id="formProduk" action="./store.php" method="post" enctype="multipart/form-data">
            <table cellpadding="8" class="w-100">
              <tr>
                <td>Nama Produk</td>
                <td>
                  <input class="form-control <?= isset($errors['nama']) ? 'is-invalid' : ''; ?>" type="text" name="nama" placeholder="Nama Produk" value="<?= isset($old['nama']) ? htmlspecialchars($old['nama'], ENT_QUOTES) : ''; ?>" required>
                  <?php if (isset($errors['nama'])): ?>
                    <div class="invalid-feedback"><?= htmlspecialchars($errors['nama'], ENT_QUOTES); ?></div>
                  <?php endif; ?>
                </td>
              </tr>
              <tr>
                <td>Harga</td>
                <td>
                  <input class="form-control <?= isset($errors['harga']) ? 'is-invalid' : ''; ?>" type="number" name="harga" placeholder="Harga Produk" value="<?= isset($old['harga']) ? htmlspecialchars($old['harga'], ENT_QUOTES) : ''; ?>" required>
                  <?php if (isset($errors['harga'])): ?>
                    <div class="invalid-feedback"><?= htmlspecialchars($errors['harga'], ENT_QUOTES); ?></div>
                  <?php endif; ?>
                </td>
              </tr>
              <tr>
                <td>Stok Tersedia</td>
                <td>
                  <input type="checkbox" name="stock" value="1" <?= isset($old['stock']) && $old['stock'] ? 'checked' : ''; ?>>
                  <label for="stock">Tersedia</label>
                </td>
              </tr>
              <tr>
                <td>Gambar Produk</td>
                <td>
                  <input class="form-control <?= isset($errors['gambar']) ? 'is-invalid' : ''; ?>" type="file" name="gambar" accept="image/*">
                  <?php if (isset($errors['gambar'])): ?>
                    <div class="invalid-feedback"><?= htmlspecialchars($errors['gambar'], ENT_QUOTES); ?></div>
                  <?php endif; ?>
                </td>
              </tr>
              <tr>
                <td>
                  <button type="button" onclick="confirmSubmit()" class="btn btn-primary">Simpan</button>
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

<script>
  function confirmSubmit() {
    iziToast.question({
      timeout: false,
      close: false,
      overlay: true,
      displayMode: 'once',
      title: 'Konfirmasi Simpan',
      message: 'Apakah Anda yakin ingin menyimpan data ini?',
      position: 'center',
      buttons: [
        ['<button>Ya</button>', function(instance, toast) {
          document.getElementById('formProduk').submit();
          instance.hide({
            transitionOut: 'fadeOut'
          }, toast, 'button');
        }],
        ['<button>Tidak</button>', function(instance, toast) {
          instance.hide({
            transitionOut: 'fadeOut'
          }, toast, 'button');
        }]
      ]
    });
  }
</script>

<?php
require_once '../includes/_bottom.php';
?>