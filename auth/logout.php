<?php
/**
 * Halaman 22: Logout
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/helpers/auth.php';
require_once dirname(__DIR__) . '/helpers/functions.php';

startSecureSession();

if (!isLoggedIn()) {
    redirect('/auth/login.php');
}

// CSRF check for POST logout; allow GET with confirmation page
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST[CSRF_TOKEN_NAME] ?? '';
    if (verifyCsrfToken($token)) {
        logoutUser();
    }
    // Redirect even if token invalid to prevent session fixation
    header('Location: ' . BASE_URL . '/auth/login.php?logout=1');
    exit;
}

// GET: show confirmation
$u = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Logout | <?= e(APP_NAME) ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="login-page">
  <div class="login-card text-center">
    <div class="mb-3" style="font-size:3rem">👋</div>
    <h2 class="fw-bold mb-1">Keluar dari Sistem?</h2>
    <p class="text-muted mb-4">Halo <strong><?= e($u['full_name'] ?: $u['username']) ?></strong>, apakah Anda yakin ingin keluar?</p>
    <form method="POST" action="">
      <?= csrfField() ?>
      <div class="d-flex gap-2 justify-content-center">
        <button type="submit" class="btn btn-danger px-4 fw-bold">
          <i class="bi bi-box-arrow-right me-2"></i>Ya, Keluar
        </button>
        <a href="javascript:history.back()" class="btn btn-outline-secondary px-4">
          <i class="bi bi-arrow-left me-2"></i>Batal
        </a>
      </div>
    </form>
    <div class="mt-3">
      <a href="<?= BASE_URL ?>/index.php" class="text-muted small">
        <i class="bi bi-house me-1"></i>Ke Beranda Publik
      </a>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
