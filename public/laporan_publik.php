<?php
/**
 * Halaman 7: Laporan Publik
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/helpers/auth.php';
require_once dirname(__DIR__) . '/helpers/functions.php';
startSecureSession();
$db = getDB();

$dateFrom = sanitizeString($_GET['from'] ?? date('Y-01-01'), 20);
$dateTo   = sanitizeString($_GET['to']   ?? date('Y-m-d'),   20);
$jenisLaporan = sanitizeString($_GET['jenis'] ?? 'stok', 20);

$jenisList = [
  'stok'      => 'Laporan Stok Saat Ini',
  'distribusi' => 'Laporan Distribusi',
  'masuk'     => 'Laporan Bantuan Masuk',
];

$data = [];
$columns = [];

if ($jenisLaporan === 'stok') {
  $stmt = $db->query("
    SELECT p.sku, p.name AS produk, c.name AS kategori, p.stock, p.unit, p.minimum_stock,
           CASE WHEN p.stock <= p.minimum_stock THEN 'Kritis' ELSE 'Aman' END AS status
    FROM products p JOIN categories c ON p.category_id=c.id
    WHERE p.is_public=1 ORDER BY p.name
  ");
  $data = $stmt->fetchAll();
  $columns = ['SKU','Produk','Kategori','Stok','Satuan','Min. Stok','Status'];
} elseif ($jenisLaporan === 'distribusi') {
  $stmt = $db->prepare("
    SELECT sm.date, p.name AS produk, c.name AS kategori, sm.quantity, p.unit, dt.name AS tujuan
    FROM stock_movements sm
    JOIN products p ON sm.product_id=p.id
    JOIN categories c ON p.category_id=c.id
    LEFT JOIN distribution_targets dt ON sm.distribution_target_id=dt.id
    WHERE sm.type='OUT' AND sm.is_public=1 AND p.is_public=1
      AND sm.date BETWEEN ? AND ?
    ORDER BY sm.date DESC
  ");
  $stmt->execute([$dateFrom, $dateTo]);
  $data = $stmt->fetchAll();
  $columns = ['Tanggal','Produk','Kategori','Jumlah','Satuan','Tujuan'];
} elseif ($jenisLaporan === 'masuk') {
  $stmt = $db->prepare("
    SELECT sm.date, p.name AS produk, c.name AS kategori, sm.quantity, p.unit,
           s.name AS supplier, sm.reference
    FROM stock_movements sm
    JOIN products p ON sm.product_id=p.id
    JOIN categories c ON p.category_id=c.id
    LEFT JOIN suppliers s ON sm.supplier_id=s.id AND s.is_public=1
    WHERE sm.type='IN' AND sm.is_public=1 AND p.is_public=1
      AND sm.date BETWEEN ? AND ?
    ORDER BY sm.date DESC
  ");
  $stmt->execute([$dateFrom, $dateTo]);
  $data = $stmt->fetchAll();
  $columns = ['Tanggal','Produk','Kategori','Jumlah','Satuan','Donatur','Referensi'];
}

$pageTitle = 'Laporan Publik';
$activeMenu = 'laporan';
include dirname(__DIR__) . '/views/partials/header_public.php';
?>
<div class="container py-4">
  <div class="mb-4">
    <h1 class="section-heading">Laporan <span>Publik</span></h1>
    <div class="section-divider"></div>
    <p class="text-muted">Data laporan yang diizinkan untuk diakses oleh masyarakat umum.</p>
  </div>

  <form method="GET" class="filter-bar mb-4">
    <div class="row g-2 align-items-end">
      <div class="col-md-3">
        <label class="form-label small fw-semibold mb-1">Jenis Laporan</label>
        <select name="jenis" class="form-select">
          <?php foreach ($jenisList as $k=>$v): ?>
            <option value="<?= $k ?>" <?= $jenisLaporan===$k?'selected':'' ?>><?= e($v) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php if ($jenisLaporan !== 'stok'): ?>
      <div class="col-md-2">
        <label class="form-label small fw-semibold mb-1">Dari</label>
        <input type="date" name="from" class="form-control" value="<?= e($dateFrom) ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label small fw-semibold mb-1">Sampai</label>
        <input type="date" name="to" class="form-control" value="<?= e($dateTo) ?>">
      </div>
      <?php endif; ?>
      <div class="col-md-2 d-flex gap-1">
        <button type="submit" class="btn btn-primary flex-fill"><i class="bi bi-search"></i> Tampilkan</button>
      </div>
    </div>
  </form>

  <div class="card-panel">
    <div class="card-panel-header">
      <span><?= e($jenisList[$jenisLaporan]) ?></span>
      <small class="text-muted"><?= count($data) ?> baris data</small>
    </div>
    <div class="table-responsive">
      <table class="table-modern w-100">
        <thead><tr>
          <?php foreach ($columns as $col): ?>
            <th><?= e($col) ?></th>
          <?php endforeach; ?>
        </tr></thead>
        <tbody>
          <?php if (empty($data)): ?>
            <tr><td colspan="<?= count($columns) ?>" class="empty-state"><span class="empty-icon">📊</span>Tidak ada data untuk parameter ini</td></tr>
          <?php else: ?>
            <?php foreach ($data as $row): ?>
              <tr>
                <?php foreach (array_values($row) as $i => $cell): ?>
                  <td>
                    <?php if ($columns[$i]==='Status'): ?>
                      <span class="badge-status <?= $cell==='Kritis'?'badge-kritis':'badge-aman' ?>"><?= e($cell) ?></span>
                    <?php elseif ($columns[$i]==='Tanggal'): ?>
                      <?= e(formatDate((string)$cell,'d M Y')) ?>
                    <?php elseif ($columns[$i]==='Jumlah'): ?>
                      <strong><?= formatNumber((int)$cell) ?></strong>
                    <?php else: ?>
                      <?= e((string)$cell) ?>
                    <?php endif; ?>
                  </td>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php include dirname(__DIR__) . '/views/partials/footer_public.php'; ?>
