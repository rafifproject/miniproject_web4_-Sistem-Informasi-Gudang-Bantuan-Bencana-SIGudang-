<?php
/**
 * Halaman 21: Error 403 Forbidden
 */
http_response_code(403);
if (!defined('APP_NAME')) {
    require_once dirname(__DIR__, 2) . '/config/app.php';
}
require_once dirname(__DIR__, 2) . '/helpers/auth.php';
require_once dirname(__DIR__, 2) . '/helpers/functions.php';
startSecureSession();
$u = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>403 - Akses Ditolak | <?= e(APP_NAME) ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="login-page">
  <div class="login-card text-center" style="max-width: 500px;">
    <div class="mb-3 text-danger" style="font-size: 4rem;">
      <i class="bi bi-shield-x"></i>
    </div>
    <span class="badge bg-danger-subtle text-danger px-3 py-1 rounded-pill mb-2 fw-semibold">Error 403: Forbidden</span>
    <h2 class="fw-bold mb-2">Akses Terlarang</h2>
    <p class="text-muted mb-4">
      Maaf, Anda tidak memiliki izin untuk mengakses halaman ini. Halaman ini hanya diperuntukkan bagi pengguna dengan role <strong>Administrator</strong>.
    </p>

    <div class="d-flex flex-wrap gap-2 justify-content-center">
      <?php if ($u): ?>
        <a href="<?= BASE_URL ?>/<?= $u['role'] ?>/dashboard.php" class="btn btn-primary px-4 fw-semibold">
          <i class="bi bi-speedometer2 me-2"></i>Kembali ke Dashboard
        </a>
      <?php else: ?>
        <a href="<?= BASE_URL ?>/auth/login.php" class="btn btn-primary px-4 fw-semibold">
          <i class="bi bi-box-arrow-in-right me-2"></i>Login
        </a>
      <?php endif; ?>
      <a href="<?= BASE_URL ?>/index.php" class="btn btn-outline-secondary px-4">
        <i class="bi bi-house me-2"></i>Beranda Publik
      </a>
    </div>

    <?php if ($u): ?>
      <div class="mt-4 pt-3 border-top text-muted small">
        Masuk sebagai: <strong><?= e($u['full_name'] ?: $u['username']) ?></strong> (Role: <span class="badge-status badge-staff"><?= ucfirst($u['role']) ?></span>)
      </div>
    <?php endif; ?>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
