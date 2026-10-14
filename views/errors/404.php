<?php
/**
 * Halaman 21: Error 404 Not Found
 */
http_response_code(404);
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
  <title>404 - Halaman Tidak Ditemukan | <?= e(APP_NAME) ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="login-page">
  <div class="login-card text-center" style="max-width: 500px;">
    <div class="mb-3 text-warning" style="font-size: 4.5rem;">
      <i class="bi bi-question-circle"></i>
    </div>
    <span class="badge bg-warning-subtle text-warning-emphasis px-3 py-1 rounded-pill mb-2 fw-semibold">Error 404</span>
    <h2 class="fw-bold mb-2">Halaman Tidak Ditemukan</h2>
    <p class="text-muted mb-4">
      Halaman yang Anda cari mungkin telah dipindahkan, dihapus, atau tautan yang Anda tuju salah. Silakan periksa kembali URL atau kembali ke halaman utama.
    </p>

    <div class="d-flex flex-wrap gap-2 justify-content-center">
      <?php if ($u): ?>
        <a href="<?= BASE_URL ?>/<?= $u['role'] ?>/dashboard.php" class="btn btn-primary px-4 fw-semibold">
          <i class="bi bi-speedometer2 me-2"></i>Kembali ke Dashboard
        </a>
      <?php else: ?>
        <a href="<?= BASE_URL ?>/index.php" class="btn btn-primary px-4 fw-semibold">
          <i class="bi bi-house me-2"></i>Beranda Publik
        </a>
      <?php endif; ?>
      <button type="button" onclick="history.back()" class="btn btn-outline-secondary px-4">
        <i class="bi bi-arrow-left me-2"></i>Halaman Sebelumnya
      </button>
    </div>

    <div class="mt-4 pt-3 border-top text-muted small">
      <?= e(APP_NAME) ?> &copy; <?= date('Y') ?>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
