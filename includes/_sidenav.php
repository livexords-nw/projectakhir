<div class="main-sidebar sidebar-style-2">
  <aside id="sidebar-wrapper">
    <!-- Logo besar -->
    <div class="login-brand text-center mb-3 logo-large">
      <a href="../index.php">
        <img src="../assets/img/Tea_Bliss_logo.png" alt="Logo Besar" class="img-fluid" style="max-width: 150px; height: auto;">
      </a>
    </div>
    <!-- Logo kecil -->
    <div class="login-brand text-center mb-3 logo-small">
      <a href="../index.php">
        <img src="../assets/img/Tea_Bliss_logo.png" alt="Logo Kecil" class="img-fluid" style="max-width: 70px; height: auto;">
      </a>
    </div>
    <ul class="sidebar-menu">
      <li class="menu-header">Dashboard</li>
      <li><a class="nav-link" href="../"><i class="fas fa-home"></i> <span>Home</span></a></li>

      <?php if ($_SESSION['login']['role'] === 'user') : ?>
        <li><a class="nav-link" href="../dashboard/Keranjang.php"><i class="fas fa-shopping-cart"></i> <span>Keranjang</span></a></li>
        <li><a class="nav-link" href="../dashboard/history_pesanan.php"><i class="fas fa-history"></i> <span>History Pesanan</span></a></li>
      <?php endif; ?>

      <?php if ($_SESSION['login']['role'] === 'admin') : ?>
        <li class="menu-header">Main Feature</li>

        <!-- Pesanan (Paling Sering Digunakan) -->
        <li class="dropdown">
          <a href="#" class="nav-link has-dropdown"><i class="fas fa-receipt"></i> <span>Pesanan</span></a>
          <ul class="dropdown-menu">
            <li><a href="../pesanan/index.php"><i class="fas fa-file-invoice"></i> List Pesanan</a></li>
          </ul>
        </li>

        <!-- Produk -->
        <li class="dropdown">
          <a href="#" class="nav-link has-dropdown"><i class="fas fa-box-open"></i> <span>Produk</span></a>
          <ul class="dropdown-menu">
            <li><a class="nav-link" href="../produk/index.php"><i class="fas fa-list-ul"></i> List Produk</a></li>
            <li><a class="nav-link" href="../produk/create.php"><i class="fas fa-plus-circle"></i> Tambah Produk</a></li>
          </ul>
        </li>

        <!-- Logger Pesanan -->
        <li class="dropdown">
          <a href="#" class="nav-link has-dropdown"><i class="fas fa-history"></i> <span>Logger</span></a>
          <ul class="dropdown-menu">
            <li><a href="../dashboard/logs.php"><i class="fas fa-clipboard-list"></i> Log Pesanan</a></li>
          </ul>
        </li>

        <!-- Akun -->
        <li class="dropdown">
          <a href="#" class="nav-link has-dropdown"><i class="fas fa-users"></i> <span>Akun</span></a>
          <ul class="dropdown-menu">
            <li><a href="../account/index.php"><i class="fas fa-user-circle"></i> Akun Pengguna</a></li>
          </ul>
        </li>

        <!-- Meja (Paling Jarang Digunakan) -->
        <li class="dropdown">
          <a href="#" class="nav-link has-dropdown"><i class="fas fa-chair"></i> <span>Meja</span></a>
          <ul class="dropdown-menu">
            <li><a href="../meja/index.php"><i class="fas fa-th-list"></i> Meja List</a></li>
            <li><a href="../meja/add_meja.php"><i class="fas fa-plus"></i> Tambahkan Meja</a></li>
          </ul>
        </li>
      <?php endif; ?>

    </ul>
  </aside>
</div>