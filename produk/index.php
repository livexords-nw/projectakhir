<?php
require_once '../includes/_top.php';
require_once '../helper/connection.php';
require_once '../helper/auth.php';

checkAdmin();
$result = mysqli_query($connection, "SELECT * FROM produk");
?>

<section class="section">
  <div class="section-header d-flex justify-content-between">
    <h1>Data Produk</h1>
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
                  <th><i class="fas fa-list-ol"></i> No</th>
                  <th><i class="fas fa-box"></i> Nama</th>
                  <th><i class="fas fa-boxes"></i> Stock</th>
                  <th><i class="fas fa-dollar-sign"></i> Harga</th>
                  <th><i class="fas fa-image"></i> Gambar</th>
                  <th style="width: 150px;"><i class="fas fa-cogs"></i> Aksi</th>
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
                      <a class="btn btn-sm btn-info" href="edit.php?id=<?= $data['id'] ?>">
                        <i class="fas fa-edit fa-fw"></i> Edit
                      </a>
                      <a class="btn btn-sm btn-danger " href="#"
                        onclick="confirmDelete(<?= $data['id'] ?>)">
                        <i class="fas fa-trash fa-fw"></i> Hapus
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