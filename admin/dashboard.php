<?php
/**
 * Halaman 10: Dashboard Admin
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/helpers/auth.php';
require_once dirname(__DIR__) . '/helpers/functions.php';

startSecureSession();
requireAdmin();

$db = getDB();

// Dashboard stats (semua dari query agregat)
$stats = $db->query("
  SELECT
    (SELECT COUNT(*) FROM products) AS total_jenis,
    (SELECT COALESCE(SUM(stock),0) FROM products) AS total_stok,
    (SELECT COALESCE(SUM(stock*price),0) FROM products) AS nilai_persediaan,
    (SELECT COUNT(*) FROM products WHERE stock <= minimum_stock AND minimum_stock > 0) AS stok_min,
    (SELECT COALESCE(SUM(quantity),0) FROM stock_movements WHERE type='IN') AS total_masuk,
    (SELECT COALESCE(SUM(quantity),0) FROM stock_movements WHERE type='OUT') AS total_keluar,
    (SELECT COUNT(*) FROM suppliers) AS total_supplier
")->fetch();

// Stok per kategori
$katStats = $db->query("
  SELECT c.name, COALESCE(SUM(p.stock),0) AS total_stok, COUNT(p.id) AS jumlah_produk
  FROM categories c LEFT JOIN products p ON p.category_id=c.id
  GROUP BY c.id, c.name ORDER BY total_stok DESC
")->fetchAll();

// Transaksi terbaru
$recent = $db->query("
  SELECT sm.date, sm.type, sm.quantity, sm.reference,
         p.name AS produk, p.unit, u.username,
         s.name AS supplier, dt.name AS tujuan
  FROM stock_movements sm
  JOIN products p ON sm.product_id=p.id
  JOIN users u ON sm.user_id=u.id
  LEFT JOIN suppliers s ON sm.supplier_id=s.id
  LEFT JOIN distribution_targets dt ON sm.distribution_target_id=dt.id
  ORDER BY sm.created_at DESC LIMIT 8
")->fetchAll();

// Stok kritis/rendah
$kritis = $db->query("
  SELECT p.name, p.sku, p.stock, p.minimum_stock, c.name AS kategori, p.unit
  FROM products p JOIN categories c ON p.category_id=c.id
  WHERE p.stock <= p.minimum_stock AND p.minimum_stock > 0
  ORDER BY p.stock ASC LIMIT 5
")->fetchAll();

// Chart mutasi bulanan (12 bulan terakhir)
$chartMutasi = $db->query("
  SELECT DATE_FORMAT(date,'%Y-%m') AS bulan,
         SUM(CASE WHEN type='IN' THEN quantity ELSE 0 END) AS masuk,
         SUM(CASE WHEN type='OUT' THEN quantity ELSE 0 END) AS keluar
  FROM stock_movements
  WHERE date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
  GROUP BY bulan ORDER BY bulan
")->fetchAll();

$pageTitle = 'Dashboard Admin';
$activeMenu = 'dashboard';
include dirname(__DIR__) . '/views/partials/header_admin.php';
?>

<?= flashAlert('login_success') ?>
<?= flashAlert('msg') ?>

<!-- Stat Cards: 2 baris x 3 kartu (Sesuai Desain Halaman 10) -->
<div class="row g-3 mb-4">
  <div class="col-12 col-sm-6 col-xl-4">
    <div class="stat-card">
      <div class="stat-icon blue"><i class="bi bi-box-seam"></i></div>
      <div class="stat-body">
        <div class="stat-value"><?= formatNumber($stats['total_jenis']) ?></div>
        <div class="stat-label">Jenis Barang</div>
      </div>
    </div>
  </div>

  <div class="col-12 col-sm-6 col-xl-4">
    <div class="stat-card">
      <div class="stat-icon green"><i class="bi bi-stack"></i></div>
      <div class="stat-body">
        <div class="stat-value"><?= formatNumber($stats['total_stok']) ?></div>
        <div class="stat-label">Total Stok Tersedia</div>
      </div>
    </div>
  </div>

  <div class="col-12 col-sm-6 col-xl-4">
    <div class="stat-card">
      <div class="stat-icon purple"><i class="bi bi-cash-coin"></i></div>
      <div class="stat-body">
        <div class="stat-value" style="font-size:1.35rem"><?= formatRupiah($stats['nilai_persediaan']) ?></div>
        <div class="stat-label">Estimasi Nilai Persediaan</div>
      </div>
    </div>
  </div>

  <div class="col-12 col-sm-6 col-xl-4">
    <div class="stat-card">
      <div class="stat-icon red"><i class="bi bi-exclamation-triangle"></i></div>
      <div class="stat-body">
        <div class="stat-value text-<?= $stats['stok_min'] > 0 ? 'danger' : 'success' ?>"><?= formatNumber($stats['stok_min']) ?></div>
        <div class="stat-label">Stok Minimum / Kritis</div>
      </div>
    </div>
  </div>

  <div class="col-12 col-sm-6 col-xl-4">
    <div class="stat-card">
      <div class="stat-icon orange"><i class="bi bi-arrow-down-circle"></i></div>
      <div class="stat-body">
        <div class="stat-value"><?= formatNumber($stats['total_masuk']) ?></div>
        <div class="stat-label">Total Barang Masuk</div>
      </div>
    </div>
  </div>

  <div class="col-12 col-sm-6 col-xl-4">
    <div class="stat-card">
      <div class="stat-icon yellow"><i class="bi bi-arrow-up-circle"></i></div>
      <div class="stat-body">
        <div class="stat-value"><?= formatNumber($stats['total_keluar']) ?></div>
        <div class="stat-label">Total Barang Keluar</div>
      </div>
    </div>
  </div>
</div>

<div class="row g-4 mb-4">
  <!-- Chart Mutasi -->
  <div class="col-lg-7">
    <div class="card-panel h-100">
      <div class="card-panel-header">Mutasi Stok (12 Bulan Terakhir)</div>
      <div class="card-panel-body chart-container" style="height:260px">
        <canvas id="chartMutasi"></canvas>
      </div>
    </div>
  </div>

  <!-- Ringkasan Kategori -->
  <div class="col-lg-5">
    <div class="card-panel h-100">
      <div class="card-panel-header">Ringkasan Kategori</div>
      <div class="card-panel-body p-0">
        <table class="table-modern w-100">
          <thead><tr><th>Kategori</th><th class="text-end">Produk</th><th class="text-end">Total Stok</th></tr></thead>
          <tbody>
            <?php foreach ($katStats as $k): ?>
            <tr>
              <td><?= e($k['name']) ?></td>
              <td class="text-end"><?= $k['jumlah_produk'] ?></td>
              <td class="text-end fw-bold"><?= formatNumber($k['total_stok']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="row g-4">
  <!-- Transaksi Terbaru -->
  <div class="col-lg-8">
    <div class="card-panel">
      <div class="card-panel-header">
        <span>Transaksi Terbaru</span>
        <a href="<?= BASE_URL ?>/admin/mutasi_stok.php" class="btn-sm-action btn-view">Lihat Semua</a>
      </div>
      <div class="table-responsive">
        <table class="table-modern w-100">
          <thead><tr><th>Tgl</th><th>Tipe</th><th>Produk</th><th>Jumlah</th><th>Oleh</th></tr></thead>
          <tbody>
            <?php foreach ($recent as $r): ?>
            <tr>
              <td class="text-muted small"><?= e(formatDate($r['date'],'d M Y')) ?></td>
              <td><span class="badge-status <?= $r['type']==='IN'?'badge-in':'badge-out' ?>"><?= $r['type'] ?></span></td>
              <td><?= e($r['produk']) ?></td>
              <td class="<?= $r['type']==='IN'?'text-success':'text-danger' ?> fw-bold"><?= $r['type']==='IN'?'+':'-' ?><?= formatNumber($r['quantity']) ?></td>
              <td class="text-muted small"><?= e($r['username']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Stok Kritis -->
  <div class="col-lg-4">
    <div class="card-panel">
      <div class="card-panel-header">
        <span><i class="bi bi-exclamation-triangle text-warning me-1"></i>Peringatan Stok</span>
        <a href="<?= BASE_URL ?>/admin/produk.php?status=kritis" class="btn-sm-action btn-edit">Detail</a>
      </div>
      <div class="card-panel-body p-0">
        <?php if (empty($kritis)): ?>
          <div class="empty-state"><span class="empty-icon">✅</span>Semua stok aman</div>
        <?php else: ?>
          <?php foreach ($kritis as $k): ?>
          <div class="alert-stock-low border-bottom">
            <div class="flex-grow-1">
              <div class="fw-semibold small"><?= e($k['name']) ?></div>
              <div class="text-muted" style="font-size:0.72rem"><?= e($k['kategori']) ?> • SKU: <?= e($k['sku']) ?></div>
            </div>
            <span class="badge-status badge-kritis">
              <?= formatNumber($k['stock']) ?>/<?= formatNumber($k['minimum_stock']) ?>
            </span>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php include dirname(__DIR__) . '/views/partials/footer_admin.php'; ?>
<script>
new Chart(document.getElementById('chartMutasi'), {
  type: 'bar',
  data: {
    labels: <?= json_encode(array_column($chartMutasi,'bulan')) ?>,
    datasets: [
      { label:'Masuk',  data: <?= json_encode(array_map('intval',array_column($chartMutasi,'masuk'))) ?>,  backgroundColor:'rgba(15,168,106,0.7)',  borderRadius:4 },
      { label:'Keluar', data: <?= json_encode(array_map('intval',array_column($chartMutasi,'keluar'))) ?>, backgroundColor:'rgba(232,93,4,0.7)', borderRadius:4 }
    ]
  },
  options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{position:'bottom'}}, scales:{y:{beginAtZero:true}} }
});
</script>
