<?php
/**
 * Halaman 1: Beranda Publik
 */
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/auth.php';
require_once __DIR__ . '/helpers/functions.php';

startSecureSession();

$db = getDB();

// Statistik beranda
$stats = $db->query("
  SELECT
    (SELECT COUNT(*) FROM products WHERE is_public=1) AS total_jenis,
    (SELECT COALESCE(SUM(stock),0) FROM products WHERE is_public=1) AS total_stok,
    (SELECT COALESCE(SUM(quantity),0) FROM stock_movements WHERE type='IN' AND is_public=1) AS total_masuk,
    (SELECT COALESCE(SUM(quantity),0) FROM stock_movements WHERE type='OUT' AND is_public=1) AS total_keluar,
    (SELECT COUNT(*) FROM suppliers WHERE is_public=1) AS total_supplier
")->fetch();

// Stok kritis (publik)
$kritis = $db->query("
  SELECT p.name, p.stock, p.minimum_stock, c.name AS kategori
  FROM products p
  JOIN categories c ON p.category_id = c.id
  WHERE p.is_public=1 AND p.stock <= p.minimum_stock
  ORDER BY p.stock ASC LIMIT 4
")->fetchAll();

// Transaksi terbaru (publik)
$recent = $db->query("
  SELECT sm.date, sm.type, sm.quantity, p.name AS produk, p.unit,
         s.name AS supplier, dt.name AS tujuan
  FROM stock_movements sm
  JOIN products p ON sm.product_id = p.id
  LEFT JOIN suppliers s ON sm.supplier_id = s.id AND s.is_public=1
  LEFT JOIN distribution_targets dt ON sm.distribution_target_id = dt.id
  WHERE sm.is_public=1
  ORDER BY sm.date DESC, sm.id DESC LIMIT 5
")->fetchAll();

$pageTitle = 'Beranda';
$activeMenu = 'beranda';
include __DIR__ . '/views/partials/header_public.php';
?>

<!-- ═══ HERO ═══════════════════════════════════════════════════ -->
<section class="hero-section" style="background-image: linear-gradient(90deg, #1b3876 0%, rgba(27, 56, 118, 0.94) 40%, rgba(27, 56, 118, 0.70) 65%, rgba(27, 56, 118, 0.20) 100%), url('<?= BASE_URL ?>/assets/images/hero-bg.png');">
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-lg-6">
        <span class="badge-hero">🏛 Sistem Informasi Publik</span>
        <h1>Transparansi Bantuan, <span>Tepat Sasaran</span></h1>
        <p>Sistem informasi pengelolaan logistik bantuan bencana secara transparan, akuntabel, dan mudah diakses masyarakat.</p>
        <div class="d-flex flex-row align-items-center gap-3 hero-action-buttons">
          <a href="<?= BASE_URL ?>/public/stok.php" class="btn btn-warning fw-bold">
            <i class="bi bi-box-seam me-2"></i>Lihat Stok Bantuan
          </a>
          <a href="<?= BASE_URL ?>/public/penyaluran.php" class="btn btn-outline-light fw-semibold">
            <i class="bi bi-arrow-up-right-circle me-2"></i>Riwayat Penyaluran
          </a>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="hero-stats">
          <div class="hero-stat-card">
            <div class="stat-num"><?= formatNumber($stats['total_jenis']) ?></div>
            <div class="stat-lbl">Jenis Bantuan</div>
          </div>
          <div class="hero-stat-card">
            <div class="stat-num"><?= formatNumber($stats['total_stok']) ?></div>
            <div class="stat-lbl">Total Stok</div>
          </div>
          <div class="hero-stat-card">
            <div class="stat-num"><?= formatNumber($stats['total_masuk']) ?></div>
            <div class="stat-lbl">Total Masuk</div>
          </div>
          <div class="hero-stat-card">
            <div class="stat-num"><?= formatNumber($stats['total_keluar']) ?></div>
            <div class="stat-lbl">Total Tersalur</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ═══ STATISTIK CEPAT ═══════════════════════════════════════ -->
<section class="py-5">
  <div class="container">
    <div class="row g-4 mb-5">
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon blue"><i class="bi bi-box-seam"></i></div>
          <div class="stat-body">
            <div class="stat-value"><?= formatNumber($stats['total_jenis']) ?></div>
            <div class="stat-label">Jenis Logistik</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon green"><i class="bi bi-stack"></i></div>
          <div class="stat-body">
            <div class="stat-value"><?= formatNumber($stats['total_stok']) ?></div>
            <div class="stat-label">Total Stok Tersedia</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon orange"><i class="bi bi-arrow-down-circle"></i></div>
          <div class="stat-body">
            <div class="stat-value"><?= formatNumber($stats['total_masuk']) ?></div>
            <div class="stat-label">Total Masuk</div>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="stat-card">
          <div class="stat-icon purple"><i class="bi bi-arrow-up-circle"></i></div>
          <div class="stat-body">
            <div class="stat-value"><?= formatNumber($stats['total_keluar']) ?></div>
            <div class="stat-label">Total Tersalur</div>
          </div>
        </div>
      </div>
    </div>

    <div class="row g-4">
      <!-- Stok Kritis -->
      <div class="col-lg-6">
        <div class="card-panel h-100">
          <div class="card-panel-header">
            <span><i class="bi bi-exclamation-triangle text-warning me-2"></i>Perhatian: Stok Mendekati Minimum</span>
            <a href="<?= BASE_URL ?>/public/stok.php?status=kritis" class="btn-sm-action btn-view">Lihat Semua</a>
          </div>
          <div class="card-panel-body">
            <?php if (empty($kritis)): ?>
              <div class="empty-state"><span class="empty-icon">✅</span>Semua stok dalam kondisi aman</div>
            <?php else: ?>
              <?php foreach ($kritis as $k): ?>
                <div class="alert-stock-low mb-2">
                  <i class="bi bi-exclamation-circle text-warning"></i>
                  <div class="flex-grow-1">
                    <strong><?= e($k['name']) ?></strong>
                    <div class="text-muted small"><?= e($k['kategori']) ?></div>
                  </div>
                  <span class="badge-status badge-kritis">Stok: <?= formatNumber($k['stock']) ?></span>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Transaksi Terbaru -->
      <div class="col-lg-6">
        <div class="card-panel h-100">
          <div class="card-panel-header">
            <span><i class="bi bi-clock-history me-2"></i>Aktivitas Terbaru</span>
            <a href="<?= BASE_URL ?>/public/bantuan_masuk.php" class="btn-sm-action btn-view">Lihat Semua</a>
          </div>
          <div class="card-panel-body p-0">
            <table class="table-modern w-100">
              <thead><tr>
                <th>Tanggal</th><th>Barang</th><th>Tipe</th><th>Jumlah</th>
              </tr></thead>
              <tbody>
                <?php if (empty($recent)): ?>
                  <tr><td colspan="4" class="empty-state">Belum ada transaksi</td></tr>
                <?php else: ?>
                  <?php foreach ($recent as $r): ?>
                    <tr>
                      <td><?= e(formatDate($r['date'],'d M Y')) ?></td>
                      <td><?= e($r['produk']) ?></td>
                      <td>
                        <span class="badge-status <?= $r['type']==='IN'?'badge-in':'badge-out' ?>">
                          <?= $r['type']==='IN'?'Masuk':'Keluar' ?>
                        </span>
                      </td>
                      <td><?= formatNumber($r['quantity']) ?> <?= e($r['unit']) ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- CTA Section -->
    <div class="row mt-5">
      <div class="col-12">
        <div class="card-panel text-center p-5" style="background:linear-gradient(135deg,var(--primary),var(--primary-light));color:#fff">
          <h2 class="fw-bold mb-2">Bersama Kita Pulih</h2>
          <p class="mb-4" style="color:rgba(255,255,255,0.85)">Pantau transparansi distribusi bantuan bencana secara real-time melalui sistem informasi ini.</p>
          <div class="d-flex justify-content-center gap-3 flex-wrap">
            <a href="<?= BASE_URL ?>/public/dashboard.php" class="btn btn-warning fw-bold">
              <i class="bi bi-bar-chart-line me-2"></i>Dashboard Publik
            </a>
            <a href="<?= BASE_URL ?>/public/statistik.php" class="btn btn-outline-light">
              <i class="bi bi-graph-up me-2"></i>Lihat Statistik
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/views/partials/footer_public.php'; ?>
</body>
</html>
