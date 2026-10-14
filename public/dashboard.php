<?php
/**
 * Halaman 2: Dashboard Publik
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/helpers/auth.php';
require_once dirname(__DIR__) . '/helpers/functions.php';

startSecureSession();
$db = getDB();

// Filter tanggal
$dateFrom = sanitizeString($_GET['from'] ?? date('Y-01-01'), 20);
$dateTo   = sanitizeString($_GET['to']   ?? date('Y-m-d'),   20);

// Stats utama
$stats = $db->query("
  SELECT
    (SELECT COUNT(*) FROM products WHERE is_public=1) AS total_jenis,
    (SELECT COALESCE(SUM(stock),0) FROM products WHERE is_public=1) AS total_stok,
    (SELECT COALESCE(SUM(quantity),0) FROM stock_movements WHERE type='IN' AND is_public=1) AS total_masuk,
    (SELECT COALESCE(SUM(quantity),0) FROM stock_movements WHERE type='OUT' AND is_public=1) AS total_keluar,
    (SELECT COUNT(*) FROM suppliers WHERE is_public=1) AS total_supplier
")->fetch();

// Grafik bulanan IN/OUT
$chartData = $db->prepare("
  SELECT DATE_FORMAT(date,'%Y-%m') AS bulan,
         SUM(CASE WHEN type='IN' THEN quantity ELSE 0 END) AS masuk,
         SUM(CASE WHEN type='OUT' THEN quantity ELSE 0 END) AS keluar
  FROM stock_movements
  WHERE is_public=1 AND date BETWEEN ? AND ?
  GROUP BY bulan ORDER BY bulan
");
$chartData->execute([$dateFrom, $dateTo]);
$monthlyData = $chartData->fetchAll();

// Komposisi kategori (stok)
$categoryData = $db->query("
  SELECT c.name, COALESCE(SUM(p.stock),0) AS total
  FROM categories c
  LEFT JOIN products p ON p.category_id=c.id AND p.is_public=1
  GROUP BY c.id,c.name ORDER BY total DESC
")->fetchAll();

// Distribusi per tujuan
$distData = $db->prepare("
  SELECT dt.name, COALESCE(SUM(sm.quantity),0) AS total
  FROM distribution_targets dt
  LEFT JOIN stock_movements sm ON sm.distribution_target_id=dt.id AND sm.type='OUT' AND sm.is_public=1
    AND sm.date BETWEEN ? AND ?
  GROUP BY dt.id,dt.name ORDER BY total DESC LIMIT 8
");
$distData->execute([$dateFrom, $dateTo]);
$distRows = $distData->fetchAll();

$pageTitle = 'Dashboard Publik';
$activeMenu = 'dashboard';
include dirname(__DIR__) . '/views/partials/header_public.php';
?>
<div class="container py-4">
  <div class="d-flex align-items-center justify-content-between mb-1">
    <div>
      <h1 class="section-heading">Dashboard <span>Publik</span></h1>
      <div class="section-divider"></div>
    </div>
    <!-- Filter -->
    <form method="GET" class="d-flex gap-2 align-items-end flex-wrap">
      <div>
        <label class="form-label small fw-semibold mb-1">Dari</label>
        <input type="date" name="from" class="form-control form-control-sm" value="<?= e($dateFrom) ?>">
      </div>
      <div>
        <label class="form-label small fw-semibold mb-1">Sampai</label>
        <input type="date" name="to" class="form-control form-control-sm" value="<?= e($dateTo) ?>">
      </div>
      <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel"></i> Filter</button>
    </form>
  </div>

  <!-- Stat Cards: 5 Kartu Seragam Proporsional -->
  <div class="stat-grid-5 mb-4">
    <div class="stat-card">
      <div class="stat-icon blue"><i class="bi bi-box-seam"></i></div>
      <div class="stat-body">
        <div class="stat-value"><?= formatNumber($stats['total_jenis']) ?></div>
        <div class="stat-label">Jenis Logistik</div>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-icon green"><i class="bi bi-stack"></i></div>
      <div class="stat-body">
        <div class="stat-value"><?= formatNumber($stats['total_stok']) ?></div>
        <div class="stat-label">Total Stok Tersedia</div>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-icon orange"><i class="bi bi-arrow-down-circle"></i></div>
      <div class="stat-body">
        <div class="stat-value"><?= formatNumber($stats['total_masuk']) ?></div>
        <div class="stat-label">Total Barang Masuk</div>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-icon purple"><i class="bi bi-arrow-up-circle"></i></div>
      <div class="stat-body">
        <div class="stat-value"><?= formatNumber($stats['total_keluar']) ?></div>
        <div class="stat-label">Total Tersalur</div>
      </div>
    </div>

    <div class="stat-card">
      <div class="stat-icon yellow"><i class="bi bi-building"></i></div>
      <div class="stat-body">
        <div class="stat-value"><?= formatNumber($stats['total_supplier']) ?></div>
        <div class="stat-label">Lembaga Donatur</div>
      </div>
    </div>
  </div>

  <!-- Charts Row 1 -->
  <div class="row g-4 mb-4">
    <div class="col-lg-7">
      <div class="card-panel h-100">
        <div class="card-panel-header">Perbandingan Barang Masuk & Keluar</div>
        <div class="card-panel-body chart-container" style="height:260px">
          <canvas id="chartMonthly"></canvas>
        </div>
      </div>
    </div>
    <div class="col-lg-5">
      <div class="card-panel h-100">
        <div class="card-panel-header">Komposisi Stok per Kategori</div>
        <div class="card-panel-body chart-container" style="height:260px">
          <canvas id="chartCategory"></canvas>
        </div>
      </div>
    </div>
  </div>

  <!-- Distribution Chart -->
  <div class="card-panel">
    <div class="card-panel-header">Distribusi Bantuan Berdasarkan Tujuan</div>
    <div class="card-panel-body chart-container" style="height:260px">
      <canvas id="chartDist"></canvas>
    </div>
  </div>
</div>

<?php include dirname(__DIR__) . '/views/partials/footer_public.php'; ?>

<script>
const COLORS = ['#1a3a6b','#2456a4','#e85d04','#0fa86a','#7c3aed','#b45309','#dc3545','#0dcaf0'];

// Monthly chart
const monthlyLabels = <?= json_encode(array_column($monthlyData,'bulan')) ?>;
const monthlyIn     = <?= json_encode(array_map('intval',array_column($monthlyData,'masuk'))) ?>;
const monthlyOut    = <?= json_encode(array_map('intval',array_column($monthlyData,'keluar'))) ?>;

new Chart(document.getElementById('chartMonthly'), {
  type: 'bar',
  data: {
    labels: monthlyLabels,
    datasets: [
      { label:'Masuk', data: monthlyIn,  backgroundColor:'rgba(10,132,255,0.75)', borderRadius:4 },
      { label:'Keluar', data: monthlyOut, backgroundColor:'rgba(232,93,4,0.75)',  borderRadius:4 }
    ]
  },
  options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{position:'bottom'}}, scales:{y:{beginAtZero:true}} }
});

// Category doughnut
const catLabels = <?= json_encode(array_column($categoryData,'name')) ?>;
const catData   = <?= json_encode(array_map('intval',array_column($categoryData,'total'))) ?>;
new Chart(document.getElementById('chartCategory'), {
  type: 'doughnut',
  data: { labels: catLabels, datasets:[{ data: catData, backgroundColor: COLORS, hoverOffset:8 }] },
  options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{position:'bottom'}} }
});

// Distribution bar
const distLabels = <?= json_encode(array_column($distRows,'name')) ?>;
const distData   = <?= json_encode(array_map('intval',array_column($distRows,'total'))) ?>;
new Chart(document.getElementById('chartDist'), {
  type: 'bar',
  data: { labels: distLabels, datasets:[{ label:'Jumlah Tersalur', data: distData, backgroundColor:'rgba(15,168,106,0.75)', borderRadius:4 }] },
  options: { indexAxis:'y', responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{x:{beginAtZero:true}} }
});
</script>
