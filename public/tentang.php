<?php
/**
 * Halaman 8: Tentang Sistem
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/helpers/auth.php';
require_once dirname(__DIR__) . '/helpers/functions.php';
startSecureSession();

$pageTitle = 'Tentang Sistem';
$activeMenu = 'tentang';
include dirname(__DIR__) . '/views/partials/header_public.php';
?>
<div class="container py-5">
  <!-- Hero About -->
  <div class="row g-5 align-items-center mb-5">
    <div class="col-lg-6">
      <span class="badge-status badge-publik mb-3">📦 Tentang Kami</span>
      <h1 class="fw-bold mb-3" style="font-size:2rem">Tentang Sistem</h1>
      <p class="lead text-muted">
        <strong><?= e(APP_NAME) ?></strong> adalah platform informasi digital yang dirancang untuk mewujudkan transparansi, akuntabilitas, dan ketepatan dalam pengelolaan serta penyaluran bantuan kemanusiaan kepada korban bencana.
      </p>
      <p class="text-muted">
        Sistem ini memungkinkan masyarakat untuk memantau secara real-time bagaimana logistik bantuan bencana dikelola mulai dari penerimaan dari donatur, penyimpanan di gudang, hingga distribusi kepada penerima manfaat.
      </p>
    </div>
    <div class="col-lg-6">
      <div class="card-panel p-4" style="background:linear-gradient(135deg,var(--primary),var(--primary-light));color:#fff">
        <h3 class="fw-bold mb-3">Misi Kami</h3>
        <ul class="list-unstyled">
          <li class="mb-2"><i class="bi bi-check-circle-fill me-2"></i>Memastikan bantuan tepat sasaran dan tepat waktu</li>
          <li class="mb-2"><i class="bi bi-check-circle-fill me-2"></i>Memberikan transparansi penuh kepada publik</li>
          <li class="mb-2"><i class="bi bi-check-circle-fill me-2"></i>Mencegah penyimpangan dan penyalahgunaan bantuan</li>
          <li class="mb-2"><i class="bi bi-check-circle-fill me-2"></i>Mempercepat respons distribusi bantuan darurat</li>
          <li class="mb-2"><i class="bi bi-check-circle-fill me-2"></i>Mendokumentasikan seluruh rantai distribusi bantuan</li>
        </ul>
      </div>
    </div>
  </div>

  <!-- Nilai-Nilai -->
  <div class="mb-5">
    <h2 class="section-heading text-center mb-2">Nilai-Nilai <span>Utama</span></h2>
    <p class="text-center text-muted mb-4">Prinsip yang menjadi landasan sistem informasi ini</p>
    <div class="row g-4">
      <div class="col-md-4">
        <div class="about-value-card">
          <div class="av-icon" style="background:#dbeafe">🔍</div>
          <h3>Transparansi</h3>
          <p>Semua data stok, penerimaan, dan distribusi bantuan dapat diakses oleh masyarakat umum secara terbuka dan real-time melalui platform ini.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="about-value-card">
          <div class="av-icon" style="background:#d1fae5">📋</div>
          <h3>Akuntabilitas</h3>
          <p>Setiap transaksi dicatat dengan detail lengkap termasuk penanggung jawab, tanggal, referensi, dan catatan, sehingga dapat diaudit kapan saja.</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="about-value-card">
          <div class="av-icon" style="background:#ffedd5">🎯</div>
          <h3>Cepat & Tepat</h3>
          <p>Sistem manajemen stok real-time memastikan bantuan tersalurkan kepada penerima yang membutuhkan secara efisien dan tanpa keterlambatan.</p>
        </div>
      </div>
    </div>
  </div>

  <!-- Proses -->
  <div class="card-panel p-4 mb-5">
    <h2 class="section-heading mb-4">Alur <span>Pengelolaan Logistik</span></h2>
    <div class="row g-4 text-center">
      <div class="col-6 col-md-3">
        <div class="rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width:64px;height:64px;background:#dbeafe;font-size:1.5rem">📥</div>
        <h6 class="fw-bold">1. Penerimaan</h6>
        <p class="text-muted small">Bantuan diterima dari supplier/donatur dan dicatat</p>
      </div>
      <div class="col-6 col-md-3">
        <div class="rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width:64px;height:64px;background:#d1fae5;font-size:1.5rem">🏪</div>
        <h6 class="fw-bold">2. Penyimpanan</h6>
        <p class="text-muted small">Barang disimpan dan dikelola di gudang secara sistematis</p>
      </div>
      <div class="col-6 col-md-3">
        <div class="rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width:64px;height:64px;background:#ffedd5;font-size:1.5rem">📦</div>
        <h6 class="fw-bold">3. Pengeluaran</h6>
        <p class="text-muted small">Validasi stok dan proses pengeluaran sesuai kebutuhan</p>
      </div>
      <div class="col-6 col-md-3">
        <div class="rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width:64px;height:64px;background:#ede9fe;font-size:1.5rem">🤝</div>
        <h6 class="fw-bold">4. Distribusi</h6>
        <p class="text-muted small">Bantuan disalurkan ke posko/pengungsian yang membutuhkan</p>
      </div>
    </div>
  </div>

  <!-- CTA -->
  <div class="text-center p-5 card-panel" style="background:linear-gradient(135deg,var(--primary),var(--primary-light));color:#fff">
    <h2 class="fw-bold mb-2">Bersama Kita, Bantuan Lebih Bermakna</h2>
    <p style="color:rgba(255,255,255,0.85);max-width:500px;margin:0 auto 1.5rem">Dukung transparansi bantuan kemanusiaan dengan memantau distribusi bantuan bencana melalui sistem ini.</p>
    <div class="d-flex justify-content-center gap-3 flex-wrap">
      <a href="<?= BASE_URL ?>/public/dashboard.php" class="btn btn-warning fw-bold"><i class="bi bi-bar-chart-line me-2"></i>Dashboard Publik</a>
      <a href="<?= BASE_URL ?>/auth/login.php" class="btn btn-outline-light"><i class="bi bi-lock me-2"></i>Login Admin</a>
    </div>
  </div>
</div>
<?php include dirname(__DIR__) . '/views/partials/footer_public.php'; ?>
