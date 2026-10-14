<?php
/**
 * Public Layout Footer
 */
?>
<!-- Footer -->
<footer class="site-footer mt-5">
  <div class="container">
    <div class="row g-4">
      <div class="col-md-4">
        <div class="d-flex align-items-center gap-2 mb-2">
          <span style="font-size:1.3rem">📦</span>
          <span style="color:#fff;font-weight:700">SIGudang Bencana</span>
        </div>
        <p style="font-size:0.82rem">Sistem Informasi Gudang Bantuan Bencana — mewujudkan transparansi, akuntabilitas, dan ketepatan penyaluran bantuan kemanusiaan.</p>
      </div>
      <div class="col-md-2">
        <h6 style="color:#fff;font-size:0.85rem;font-weight:700">Navigasi</h6>
        <ul style="list-style:none;padding:0;margin:0">
          <li><a href="<?= BASE_URL ?>/index.php">Beranda</a></li>
          <li><a href="<?= BASE_URL ?>/public/stok.php">Stok Bantuan</a></li>
          <li><a href="<?= BASE_URL ?>/public/penyaluran.php">Penyaluran</a></li>
          <li><a href="<?= BASE_URL ?>/public/statistik.php">Statistik</a></li>
        </ul>
      </div>
      <div class="col-md-2">
        <h6 style="color:#fff;font-size:0.85rem;font-weight:700">Informasi</h6>
        <ul style="list-style:none;padding:0;margin:0">
          <li><a href="<?= BASE_URL ?>/public/laporan_publik.php">Laporan Publik</a></li>
          <li><a href="<?= BASE_URL ?>/public/tentang.php">Tentang Sistem</a></li>
          <li><a href="<?= BASE_URL ?>/auth/login.php">Login Admin</a></li>
        </ul>
      </div>
      <div class="col-md-4">
        <h6 style="color:#fff;font-size:0.85rem;font-weight:700">Kontak</h6>
        <p style="font-size:0.82rem">
          <i class="bi bi-envelope me-1"></i> info@sigudang.id<br>
          <i class="bi bi-telephone me-1"></i> (021) 1234-5678<br>
          <i class="bi bi-geo-alt me-1"></i> Jakarta, Indonesia
        </p>
      </div>
    </div>
    <hr style="border-color:rgba(255,255,255,0.15)">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
      <small>&copy; <?= date('Y') ?> <?= e(APP_NAME) ?>. Hak cipta dilindungi.</small>
      <small>Transparansi • Akuntabilitas • Ketepatan Penyaluran</small>
    </div>
  </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
