<?php
/**
 * Admin/Staff Sidebar Layout Header
 * Requires: $pageTitle, $activeMenu, $user (from getCurrentUser())
 */
if (!defined('APP_NAME')) require_once dirname(__DIR__,2) . '/config/app.php';
require_once dirname(__DIR__,2) . '/helpers/auth.php';
startSecureSession();
$u = getCurrentUser();
$role = $u['role'] ?? 'staff';
$activeMenu = $activeMenu ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle ?? 'Dashboard') ?> | <?= e(APP_NAME) ?></title>
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📦</text></svg>">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= filemtime(dirname(__DIR__, 2) . '/assets/css/style.css') ?>">
</head>
<body>
<div class="app-wrapper">

<!-- ═══════ SIDEBAR ═══════ -->
<aside class="sidebar" id="appSidebar">
  <div class="sidebar-header">
    <div class="logo-icon">📦</div>
    <div class="logo-text">
      SIGudang
      <span>Bantuan Bencana</span>
    </div>
  </div>

  <nav class="sidebar-nav">

    <div class="nav-section-label">Utama</div>
    <div class="nav-item">
      <a href="<?= BASE_URL ?>/<?= $role ?>/dashboard.php" class="nav-link <?= $activeMenu==='dashboard'?'active':'' ?>">
        <i class="bi bi-speedometer2 nav-icon"></i> Dashboard
      </a>
    </div>

    <?php if ($role === 'admin' || $role === 'staff'): ?>
    <div class="nav-section-label">Transaksi</div>
    <div class="nav-item">
      <a href="<?= BASE_URL ?>/admin/barang_masuk.php" class="nav-link <?= $activeMenu==='masuk'?'active':'' ?>">
        <i class="bi bi-box-arrow-in-down nav-icon"></i> Barang Masuk
      </a>
    </div>
    <div class="nav-item">
      <a href="<?= BASE_URL ?>/admin/barang_keluar.php" class="nav-link <?= $activeMenu==='keluar'?'active':'' ?>">
        <i class="bi bi-box-arrow-up nav-icon"></i> Barang Keluar
      </a>
    </div>
    <div class="nav-item">
      <a href="<?= BASE_URL ?>/admin/mutasi_stok.php" class="nav-link <?= $activeMenu==='mutasi'?'active':'' ?>">
        <i class="bi bi-arrow-left-right nav-icon"></i> Riwayat Mutasi
      </a>
    </div>
    <?php endif; ?>

    <?php if ($role === 'admin'): ?>
    <div class="nav-section-label">Master Data</div>
    <div class="nav-item">
      <a href="<?= BASE_URL ?>/admin/produk.php" class="nav-link <?= $activeMenu==='produk'?'active':'' ?>">
        <i class="bi bi-box-seam nav-icon"></i> Produk
      </a>
    </div>
    <div class="nav-item">
      <a href="<?= BASE_URL ?>/admin/supplier.php" class="nav-link <?= $activeMenu==='supplier'?'active':'' ?>">
        <i class="bi bi-building nav-icon"></i> Supplier
      </a>
    </div>
    <div class="nav-item">
      <a href="<?= BASE_URL ?>/admin/kategori.php" class="nav-link <?= $activeMenu==='kategori'?'active':'' ?>">
        <i class="bi bi-tags nav-icon"></i> Kategori
      </a>
    </div>
    <div class="nav-item">
      <a href="<?= BASE_URL ?>/admin/produk_supplier.php" class="nav-link <?= $activeMenu==='produk_supplier'?'active':'' ?>">
        <i class="bi bi-truck nav-icon"></i> Pasokan Supplier
      </a>
    </div>

    <div class="nav-section-label">Laporan</div>
    <div class="nav-item">
      <a href="<?= BASE_URL ?>/admin/laporan.php" class="nav-link <?= $activeMenu==='laporan'?'active':'' ?>">
        <i class="bi bi-file-earmark-bar-graph nav-icon"></i> Laporan Admin
      </a>
    </div>

    <div class="nav-section-label">Pengguna</div>
    <div class="nav-item">
      <a href="<?= BASE_URL ?>/admin/pengguna.php" class="nav-link <?= $activeMenu==='pengguna'?'active':'' ?>">
        <i class="bi bi-people nav-icon"></i> Manajemen Pengguna
      </a>
    </div>
    <?php endif; ?>

    <div class="nav-section-label">Akun</div>
    <div class="nav-item">
      <a href="<?= BASE_URL ?>/auth/profil.php" class="nav-link <?= $activeMenu==='profil'?'active':'' ?>">
        <i class="bi bi-person-circle nav-icon"></i> Profil Saya
      </a>
    </div>
    <div class="nav-item">
      <a href="<?= BASE_URL ?>/index.php" class="nav-link">
        <i class="bi bi-globe nav-icon"></i> Lihat Publik
      </a>
    </div>

  </nav>

  <div class="sidebar-footer">
    <div class="user-info">
      <div class="user-avatar"><?= strtoupper(substr($u['full_name'] ?: $u['username'], 0, 1)) ?></div>
      <div>
        <div class="user-name"><?= e($u['full_name'] ?: $u['username']) ?></div>
        <div class="user-role"><?= ucfirst($role) ?></div>
      </div>
    </div>
    <a href="<?= BASE_URL ?>/auth/logout.php" class="nav-link text-danger d-flex align-items-center gap-2" style="font-size:0.83rem;padding:0.4rem 0.5rem">
      <i class="bi bi-box-arrow-right"></i> Keluar
    </a>
  </div>
</aside>

<!-- ═══════ MAIN CONTENT ═══════ -->
<div class="main-content">

  <!-- Topbar -->
  <div class="topbar">
    <div class="d-flex align-items-center gap-2">
      <button class="btn btn-sm btn-light d-lg-none" id="sidebarToggle">
        <i class="bi bi-list"></i>
      </button>
      <span class="page-title"><?= e($pageTitle ?? 'Dashboard') ?></span>
    </div>
    <div class="topbar-right">
      <span class="badge-status <?= $role==='admin'?'badge-admin':'badge-staff' ?>">
        <?= ucfirst($role) ?>
      </span>
      <a href="<?= BASE_URL ?>/auth/profil.php" class="btn btn-sm btn-light">
        <i class="bi bi-person-circle"></i>
        <span class="d-none d-md-inline ms-1"><?= e($u['username']) ?></span>
      </a>
      <a href="<?= BASE_URL ?>/auth/logout.php" class="btn btn-sm btn-outline-danger">
        <i class="bi bi-box-arrow-right"></i>
      </a>
    </div>
  </div>

  <div class="content-area">
