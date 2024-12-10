<?php
require_once '../includes/_top.php';
require_once '../helper/connection.php';
require_once '../helper/auth.php';

checkAdmin();
$result = mysqli_query($connection, "SELECT * FROM produk");
?>

<section class="section">
  <div class="section-header d-flex justify-content-between">
    <h1>Data Barang</h1>
    <a href="./create.php" class="btn btn-primary">Tambah Data</a>
  </div>
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-hover table-striped w-100" id="table-1">
              <thead>
                <tr class="text-center">
                  <th>No</th>
                  <th>Nama</th>
                  <th>Stock</th>
                  <th>Harga</th>
                  <th>Gambar</th>
                  <th style="width: 150px;">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $no = 1;
                while ($data = mysqli_fetch_array($result)) :
                ?>
                  <tr class="text-center">
                    <td><?= $no ?></td>
                    <td><?= $data['nama'] ?></td>
                    <td><?= $data['stock'] ? 'Tersedia' : 'Kosong' ?></td>
                    <td><?= number_format($data['harga'], 0, ',', '.') ?></td>
                    <td>
                      <img src="../uploads/<?= $data['gambar'] ?>" alt="Gambar Barang" style="width: 80px; height: auto;">
                    </td>
                    <td>
                      <a class="btn btn-sm btn-danger mb-md-0 mb-1" href="#"
                        onclick="confirmDelete(<?= $data['id'] ?>)">
                        <i class="fas fa-trash fa-fw"></i>
                      </a>
                      <a class="btn btn-sm btn-info" href="edit.php?id=<?= $data['id'] ?>">
                        <i class="fas fa-edit fa-fw"></i>
                      </a>
                    </td>
                  </tr>
                <?php
                  $no++;
                endwhile;
                ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
</section>

<?php
require_once '../includes/_bottom.php';
?>

<!-- Page Specific JS File -->
<?php
if (isset($_SESSION['info'])) :
  if ($_SESSION['info']['status'] == 'success') {
?>
    <script>
      iziToast.success({
        title: 'Sukses',
        message: `<?= $_SESSION['info']['message'] ?>`,
        position: 'topCenter',
        timeout: 5000
      });
    </script>
  <?php
  } else {
  ?>
    <script>
      iziToast.error({
        title: 'Gagal',
        message: `<?= $_SESSION['info']['message'] ?>`,
        timeout: 5000,
        position: 'topCenter'
      });
    </script>
<?php
  }

  unset($_SESSION['info']);
  $_SESSION['info'] = null;
endif;
?>
<script src="../assets/js/page/modules-datatables.js"></script>

<script>
  function confirmDelete(id) {
    iziToast.question({
      timeout: false,
      close: false,
      overlay: true,
      displayMode: 'once',
      title: 'Konfirmasi Hapus',
      message: 'Apakah Anda yakin ingin menghapus item ini?',
      position: 'center',
      buttons: [
        ['<button>Ya</button>', function(instance, toast) {
          window.location.href = `delete.php?id=${id}`;
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