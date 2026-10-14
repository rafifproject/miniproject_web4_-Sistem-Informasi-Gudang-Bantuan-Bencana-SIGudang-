<?php
/**
 * Public Layout Header
 * Usage: include at the top of every public page.
 * Expects: $pageTitle (string), $activeMenu (string, optional)
 */
if (!defined('APP_NAME')) {
    require_once dirname(__DIR__, 2) . '/config/app.php';
}
require_once dirname(__DIR__, 2) . '/helpers/auth.php';
startSecureSession();
$activePage = $activeMenu ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle ?? APP_NAME) ?> | <?= e(APP_NAME) ?></title>
  <meta name="description" content="Sistem Informasi Gudang Bantuan Bencana — transparansi bantuan tepat sasaran">
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📦</text></svg>">
  <!-- Bootstrap 5 -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <!-- Custom CSS with cache buster -->
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= filemtime(dirname(__DIR__, 2) . '/assets/css/style.css') ?>">
</head>
<body>

<!-- Navbar Public -->
<nav class="navbar navbar-public navbar-expand-lg">
  <div class="container">
    <a class="navbar-brand" href="<?= BASE_URL ?>/index.php">
      <span class="brand-icon">📦</span>
      <span><?= e(APP_SHORT) ?></span>
    </a>
    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navPublic">
      <span style="color:#fff;font-size:1.2rem"><i class="bi bi-list"></i></span>
    </button>
    <div class="collapse navbar-collapse" id="navPublic">
      <ul class="navbar-nav me-auto ms-3 gap-1">
        <li class="nav-item"><a class="nav-link<?= $activePage==='beranda'?' active':'' ?>" href="<?= BASE_URL ?>/index.php">Beranda</a></li>
        <li class="nav-item"><a class="nav-link<?= $activePage==='dashboard'?' active':'' ?>" href="<?= BASE_URL ?>/public/dashboard.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link<?= $activePage==='stok'?' active':'' ?>" href="<?= BASE_URL ?>/public/stok.php">Stok Bantuan</a></li>
        <li class="nav-item"><a class="nav-link<?= $activePage==='masuk'?' active':'' ?>" href="<?= BASE_URL ?>/public/bantuan_masuk.php">Bantuan Masuk</a></li>
        <li class="nav-item"><a class="nav-link<?= $activePage==='penyaluran'?' active':'' ?>" href="<?= BASE_URL ?>/public/penyaluran.php">Penyaluran</a></li>
        <li class="nav-item"><a class="nav-link<?= $activePage==='statistik'?' active':'' ?>" href="<?= BASE_URL ?>/public/statistik.php">Statistik</a></li>
        <li class="nav-item"><a class="nav-link<?= $activePage==='laporan'?' active':'' ?>" href="<?= BASE_URL ?>/public/laporan_publik.php">Laporan</a></li>
        <li class="nav-item"><a class="nav-link<?= $activePage==='tentang'?' active':'' ?>" href="<?= BASE_URL ?>/public/tentang.php">Tentang</a></li>
      </ul>
      <div class="d-flex align-items-center gap-2">
        <?php if (isLoggedIn()): ?>
          <?php $u = getCurrentUser(); ?>
          <a href="<?= BASE_URL ?>/<?= $u['role'] ?>/dashboard.php" class="btn-login nav-link small">
            <i class="bi bi-speedometer2"></i> Dashboard
          </a>
        <?php else: ?>
          <a href="<?= BASE_URL ?>/auth/login.php" class="btn-login nav-link">
            <i class="bi bi-lock-fill"></i> Login Admin
          </a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>
