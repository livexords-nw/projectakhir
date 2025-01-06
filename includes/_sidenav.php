<div class="main-sidebar sidebar-style-2">
  <aside id="sidebar-wrapper">
    <div class="login-brand text-center mb-3">
      <a href="../index.php">
        <img src="../assets/img/avatar/Tea_Bliss_logo.png" alt="logo" class="img-fluid" style="max-width: 150px; height: auto;">
      </a>
    </div>
    <div class="sidebar-brand sidebar-brand-sm">
      <a href="index.php">EF</a>
    </div>
    <ul class="sidebar-menu">
      <li class="menu-header">Dashboard</li>
      <li><a class="nav-link" href="../"><i class="fas fa-fire"></i> <span>Home</span></a></li>
      <?php if ($_SESSION['login']['role'] === 'user') : ?>
        <li><a class="nav-link" href="../dashboard/Keranjang.php"><i class="fas fa-shopping-cart"></i> <span>Keranjang</span></a></li>
      <?php endif; ?>
      <?php if ($_SESSION['login']['role'] === 'admin') : ?>
        <li class="menu-header">Main Feature</li>
        <li class="dropdown">
          <a href="#" class="nav-link has-dropdown" data-toggle="dropdown"><i class="fas fa-box"></i> <span>Produk</span></a>
          <ul class="dropdown-menu">
            <li><a class="nav-link" href="../produk/index.php"><i class="fas fa-list"></i> List Produk</a></li>
            <li><a class="nav-link" href="../produk/create.php"><i class="fas fa-plus-circle"></i> Tambah Produk</a></li>
          </ul>
        </li>
        <li class="dropdown">
          <a href="#" class="nav-link has-dropdown" data-toggle="dropdown"><i class="fas fa-file-alt"></i> <span>Pesanan</span></a>
          <ul class="dropdown-menu">
            <li><a href="../pesanan/orders_dashboard.php"><i class="fas fa-file-alt"></i> List Pesanan</a></li>
          </ul>
        </li>
        <li class="dropdown">
          <a href="#" class="nav-link has-dropdown" data-toggle="dropdown"><i class="fas fa-file-alt"></i> <span>Logger</span></a>
          <ul class="dropdown-menu">
            <li><a href="../dashboard/logs.php"><i class="fas fa-file-alt"></i> Log Aktivitas</a></li>
          </ul>
        </li>
      <?php endif; ?>
    </ul>
  </aside>
</div>