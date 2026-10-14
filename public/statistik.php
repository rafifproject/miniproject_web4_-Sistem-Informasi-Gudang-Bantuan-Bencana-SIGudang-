<?php
/**
 * Halaman 6: Statistik Distribusi Publik
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/helpers/auth.php';
require_once dirname(__DIR__) . '/helpers/functions.php';
startSecureSession();
$db = getDB();

$dateFrom = sanitizeString($_GET['from'] ?? date('Y-01-01'), 20);
$dateTo   = sanitizeString($_GET['to']   ?? date('Y-m-d'),   20);

// Summary
$summary = $db->prepare("
  SELECT
    SUM(CASE WHEN type='IN' THEN quantity ELSE 0 END) AS total_masuk,
    SUM(CASE WHEN type='OUT' THEN quantity ELSE 0 END) AS total_keluar
  FROM stock_movements
  WHERE is_public=1 AND date BETWEEN ? AND ?
");
$summary->execute([$dateFrom, $dateTo]);
$sumRow = $summary->fetch();

// Per destination
$byDest = $db->prepare("
  SELECT dt.name, COALESCE(SUM(sm.quantity),0) AS total
  FROM distribution_targets dt
  LEFT JOIN stock_movements sm ON sm.distribution_target_id=dt.id
    AND sm.type='OUT' AND sm.is_public=1 AND sm.date BETWEEN ? AND ?
  GROUP BY dt.id, dt.name ORDER BY total DESC
");
$byDest->execute([$dateFrom, $dateTo]);
$destRows = $byDest->fetchAll();

// Per category
$byCat = $db->prepare("
  SELECT c.name, COALESCE(SUM(sm.quantity),0) AS total
  FROM categories c
  LEFT JOIN products p ON p.category_id=c.id AND p.is_public=1
  LEFT JOIN stock_movements sm ON sm.product_id=p.id
    AND sm.type='OUT' AND sm.is_public=1 AND sm.date BETWEEN ? AND ?
  GROUP BY c.id, c.name ORDER BY total DESC
");
$byCat->execute([$dateFrom, $dateTo]);
$catRows = $byCat->fetchAll();

// Time series
$timeSeries = $db->prepare("
  SELECT DATE_FORMAT(date,'%Y-%m') AS bulan,
         SUM(CASE WHEN type='IN' THEN quantity ELSE 0 END) AS masuk,
         SUM(CASE WHEN type='OUT' THEN quantity ELSE 0 END) AS keluar
  FROM stock_movements WHERE is_public=1 AND date BETWEEN ? AND ?
  GROUP BY bulan ORDER BY bulan
");
$timeSeries->execute([$dateFrom, $dateTo]);
$tsRows = $timeSeries->fetchAll();

$pageTitle = 'Statistik Distribusi';
$activeMenu = 'statistik';
include dirname(__DIR__) . '/views/partials/header_public.php';
?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
      <h1 class="section-heading">Statistik <span>Distribusi</span></h1>
      <div class="section-divider"></div>
    </div>
    <form method="GET" class="d-flex gap-2 align-items-end flex-wrap">
      <div>
        <label class="form-label small fw-semibold mb-1">Dari</label>
        <input type="date" name="from" class="form-control form-control-sm" value="<?= e($dateFrom) ?>">
      </div>
      <div>
        <label class="form-label small fw-semibold mb-1">Sampai</label>
        <input type="date" name="to" class="form-control form-control-sm" value="<?= e($dateTo) ?>">
      </div>
      <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel"></i> Terapkan</button>
      <a href="?" class="btn btn-outline-secondary btn-sm">Reset</a>
    </form>
  </div>

  <!-- Summary -->
  <div class="row g-3 mb-4">
    <div class="col-md-6">
      <div class="stat-card">
        <div class="stat-icon green"><i class="bi bi-arrow-down-circle"></i></div>
        <div class="stat-body">
          <div class="stat-value"><?= formatNumber($sumRow['total_masuk'] ?? 0) ?></div>
          <div class="stat-label">Total Masuk pada Periode</div>
          <div class="stat-sub"><?= e($dateFrom) ?> s/d <?= e($dateTo) ?></div>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="stat-card">
        <div class="stat-icon orange"><i class="bi bi-arrow-up-circle"></i></div>
        <div class="stat-body">
          <div class="stat-value"><?= formatNumber($sumRow['total_keluar'] ?? 0) ?></div>
          <div class="stat-label">Total Tersalur pada Periode</div>
          <div class="stat-sub"><?= e($dateFrom) ?> s/d <?= e($dateTo) ?></div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4 mb-4">
    <div class="col-lg-6">
      <div class="card-panel h-100">
        <div class="card-panel-header">Distribusi Berdasarkan Tujuan</div>
        <div class="card-panel-body chart-container" style="height:280px">
          <canvas id="chartDest"></canvas>
        </div>
      </div>
    </div>
    <div class="col-lg-6">
      <div class="card-panel h-100">
        <div class="card-panel-header">Distribusi Berdasarkan Kategori</div>
        <div class="card-panel-body chart-container" style="height:280px">
          <canvas id="chartCat"></canvas>
        </div>
      </div>
    </div>
  </div>

  <div class="card-panel">
    <div class="card-panel-header">Tren Barang Masuk & Keluar</div>
    <div class="card-panel-body chart-container" style="height:280px">
      <canvas id="chartTrend"></canvas>
    </div>
  </div>
</div>

<?php include dirname(__DIR__) . '/views/partials/footer_public.php'; ?>
<script>
const COLORS=['#1a3a6b','#2456a4','#e85d04','#0fa86a','#7c3aed','#b45309','#dc3545','#0dcaf0'];

new Chart(document.getElementById('chartDest'),{type:'bar',data:{labels:<?=json_encode(array_column($destRows,'name'))?>,datasets:[{label:'Tersalur',data:<?=json_encode(array_map('intval',array_column($destRows,'total')))?>,backgroundColor:'rgba(36,86,164,0.75)',borderRadius:4}]},options:{indexAxis:'y',responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{x:{beginAtZero:true}}}});

new Chart(document.getElementById('chartCat'),{type:'doughnut',data:{labels:<?=json_encode(array_column($catRows,'name'))?>,datasets:[{data:<?=json_encode(array_map('intval',array_column($catRows,'total')))?>,backgroundColor:COLORS}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom'}}}});

new Chart(document.getElementById('chartTrend'),{type:'line',data:{labels:<?=json_encode(array_column($tsRows,'bulan'))?>,datasets:[{label:'Masuk',data:<?=json_encode(array_map('intval',array_column($tsRows,'masuk')))?>,borderColor:'#0fa86a',backgroundColor:'rgba(15,168,106,0.1)',fill:true,tension:0.4},{label:'Keluar',data:<?=json_encode(array_map('intval',array_column($tsRows,'keluar')))?>,borderColor:'#e85d04',backgroundColor:'rgba(232,93,4,0.1)',fill:true,tension:0.4}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom'}},scales:{y:{beginAtZero:true}}}});
</script>
