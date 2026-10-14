<?php
/**
 * Halaman 18: Laporan Admin
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/helpers/auth.php';
require_once dirname(__DIR__) . '/helpers/functions.php';

startSecureSession();
requireAdmin();

$db = getDB();

$dateFrom  = sanitizeString($_GET['from']   ?? date('Y-01-01'), 20);
$dateTo    = sanitizeString($_GET['to']     ?? date('Y-m-d'),   20);
$catId     = sanitizeInt($_GET['cat']       ?? 0);
$jenis     = sanitizeString($_GET['jenis']  ?? 'stok_sekarang', 40);

$cats = $db->query("SELECT id,name FROM categories ORDER BY name")->fetchAll();

$jenisList = [
  'stok_sekarang'  => 'Stok Saat Ini',
  'stok_kategori'  => 'Stok per Kategori',
  'barang_masuk'   => 'Barang Masuk',
  'barang_keluar'  => 'Barang Keluar',
  'mutasi_stok'    => 'Mutasi Stok',
  'distribusi_tujuan' => 'Distribusi per Tujuan',
  'nilai_persediaan'  => 'Estimasi Nilai Persediaan',
  'aktivitas_user'    => 'Aktivitas Pengguna',
];

$data = [];
$columns = [];
$summary = [];

switch ($jenis) {
  case 'stok_sekarang':
    $where = "1=1"; $bind = [];
    if ($catId) { $where .= " AND p.category_id=?"; $bind[]=$catId; }
    $st = $db->prepare("SELECT p.sku, p.name AS produk, c.name AS kategori, p.stock, p.unit, p.minimum_stock, p.price, (p.stock*p.price) AS nilai, CASE WHEN p.stock<=p.minimum_stock AND p.minimum_stock>0 THEN 'Kritis' ELSE 'Aman' END AS status FROM products p JOIN categories c ON p.category_id=c.id WHERE $where ORDER BY p.name");
    $st->execute($bind);
    $data = $st->fetchAll();
    $columns = ['SKU','Produk','Kategori','Stok','Satuan','Min. Stok','Harga','Nilai','Status'];
    $summary = ['Total Produk'=>count($data), 'Total Stok'=>array_sum(array_column($data,'stock')), 'Total Nilai'=>formatRupiah(array_sum(array_column($data,'nilai')))];
    break;

  case 'stok_kategori':
    $data = $db->query("SELECT c.name AS kategori, COUNT(p.id) AS jumlah_produk, COALESCE(SUM(p.stock),0) AS total_stok, COALESCE(SUM(p.stock*p.price),0) AS nilai FROM categories c LEFT JOIN products p ON p.category_id=c.id GROUP BY c.id,c.name ORDER BY total_stok DESC")->fetchAll();
    $columns = ['Kategori','Jumlah Produk','Total Stok','Nilai'];
    break;

  case 'barang_masuk':
    $where = "sm.type='IN'"; $bind = [];
    if ($dateFrom) { $where .= " AND sm.date >= ?"; $bind[]=$dateFrom; }
    if ($dateTo)   { $where .= " AND sm.date <= ?"; $bind[]=$dateTo; }
    if ($catId)    { $where .= " AND p.category_id=?"; $bind[]=$catId; }
    $st = $db->prepare("SELECT sm.date, p.sku, p.name AS produk, c.name AS kategori, sm.quantity, p.unit, s.name AS supplier, sm.reference, u.username FROM stock_movements sm JOIN products p ON sm.product_id=p.id JOIN categories c ON p.category_id=c.id LEFT JOIN suppliers s ON sm.supplier_id=s.id JOIN users u ON sm.user_id=u.id WHERE $where ORDER BY sm.date DESC");
    $st->execute($bind);
    $data = $st->fetchAll();
    $columns = ['Tanggal','SKU','Produk','Kategori','Jumlah','Satuan','Supplier','Referensi','Oleh'];
    $summary = ['Total Transaksi'=>count($data), 'Total Qty'=>array_sum(array_column($data,'quantity'))];
    break;

  case 'barang_keluar':
    $where = "sm.type='OUT'"; $bind = [];
    if ($dateFrom) { $where .= " AND sm.date >= ?"; $bind[]=$dateFrom; }
    if ($dateTo)   { $where .= " AND sm.date <= ?"; $bind[]=$dateTo; }
    if ($catId)    { $where .= " AND p.category_id=?"; $bind[]=$catId; }
    $st = $db->prepare("SELECT sm.date, p.sku, p.name AS produk, c.name AS kategori, sm.quantity, p.unit, dt.name AS tujuan, sm.reference, u.username FROM stock_movements sm JOIN products p ON sm.product_id=p.id JOIN categories c ON p.category_id=c.id LEFT JOIN distribution_targets dt ON sm.distribution_target_id=dt.id JOIN users u ON sm.user_id=u.id WHERE $where ORDER BY sm.date DESC");
    $st->execute($bind);
    $data = $st->fetchAll();
    $columns = ['Tanggal','SKU','Produk','Kategori','Jumlah','Satuan','Tujuan','Referensi','Oleh'];
    $summary = ['Total Transaksi'=>count($data), 'Total Qty'=>array_sum(array_column($data,'quantity'))];
    break;

  case 'mutasi_stok':
    $where = "1=1"; $bind = [];
    if ($dateFrom) { $where .= " AND sm.date >= ?"; $bind[]=$dateFrom; }
    if ($dateTo)   { $where .= " AND sm.date <= ?"; $bind[]=$dateTo; }
    $st = $db->prepare("SELECT sm.date, sm.type, p.name AS produk, sm.quantity, p.unit, u.username, sm.reference FROM stock_movements sm JOIN products p ON sm.product_id=p.id JOIN users u ON sm.user_id=u.id WHERE $where ORDER BY sm.date DESC, sm.id DESC");
    $st->execute($bind);
    $data = $st->fetchAll();
    $columns = ['Tanggal','Tipe','Produk','Jumlah','Satuan','Oleh','Referensi'];
    break;

  case 'distribusi_tujuan':
    $where = "sm.type='OUT'"; $bind = [];
    if ($dateFrom) { $where .= " AND sm.date >= ?"; $bind[]=$dateFrom; }
    if ($dateTo)   { $where .= " AND sm.date <= ?"; $bind[]=$dateTo; }
    $st = $db->prepare("SELECT dt.name AS tujuan, dt.location, COUNT(sm.id) AS transaksi, SUM(sm.quantity) AS total_qty FROM distribution_targets dt LEFT JOIN stock_movements sm ON sm.distribution_target_id=dt.id AND $where GROUP BY dt.id,dt.name,dt.location ORDER BY total_qty DESC");
    $st->execute($bind);
    $data = $st->fetchAll();
    $columns = ['Tujuan','Lokasi','Transaksi','Total Qty'];
    break;

  case 'nilai_persediaan':
    $data = $db->query("SELECT p.sku, p.name AS produk, c.name AS kategori, p.stock, p.unit, p.price, (p.stock*p.price) AS nilai FROM products p JOIN categories c ON p.category_id=c.id ORDER BY nilai DESC")->fetchAll();
    $columns = ['SKU','Produk','Kategori','Stok','Satuan','Harga/Unit','Nilai Total'];
    $summary = ['Total Nilai'=>formatRupiah(array_sum(array_column($data,'nilai')))];
    break;

  case 'aktivitas_user':
    $where = "1=1"; $bind = [];
    if ($dateFrom) { $where .= " AND sm.date >= ?"; $bind[]=$dateFrom; }
    if ($dateTo)   { $where .= " AND sm.date <= ?"; $bind[]=$dateTo; }
    $st = $db->prepare("SELECT u.username, u.role, COUNT(sm.id) AS total_transaksi, SUM(CASE WHEN sm.type='IN' THEN 1 ELSE 0 END) AS masuk, SUM(CASE WHEN sm.type='OUT' THEN 1 ELSE 0 END) AS keluar FROM users u LEFT JOIN stock_movements sm ON sm.user_id=u.id AND $where GROUP BY u.id,u.username,u.role ORDER BY total_transaksi DESC");
    $st->execute($bind);
    $data = $st->fetchAll();
    $columns = ['Username','Role','Total Transaksi','Transaksi Masuk','Transaksi Keluar'];
    break;
}

$pageTitle = 'Laporan Admin';
$activeMenu = 'laporan';
include dirname(__DIR__) . '/views/partials/header_admin.php';
?>
<nav class="breadcrumb-bar"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/dashboard.php">Dashboard</a></li><li class="breadcrumb-item active">Laporan</li></ol></nav>

<!-- Filter -->
<form method="GET" class="filter-bar mb-4">
  <div class="row g-2 align-items-end">
    <div class="col-md-3">
      <label class="form-label small fw-semibold mb-1">Jenis Laporan</label>
      <select name="jenis" class="form-select" onchange="this.form.submit()">
        <?php foreach ($jenisList as $k=>$v): ?>
          <option value="<?= $k ?>" <?= $jenis===$k?'selected':'' ?>><?= e($v) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php if (!in_array($jenis,['stok_sekarang','stok_kategori','nilai_persediaan','aktivitas_user'])): ?>
    <div class="col-md-2"><label class="form-label small fw-semibold mb-1">Dari</label><input type="date" name="from" class="form-control" value="<?= e($dateFrom) ?>"></div>
    <div class="col-md-2"><label class="form-label small fw-semibold mb-1">Sampai</label><input type="date" name="to" class="form-control" value="<?= e($dateTo) ?>"></div>
    <?php endif; ?>
    <?php if (in_array($jenis,['stok_sekarang','barang_masuk','barang_keluar'])): ?>
    <div class="col-md-3">
      <label class="form-label small fw-semibold mb-1">Kategori</label>
      <select name="cat" class="form-select"><option value="">Semua Kategori</option>
        <?php foreach ($cats as $c): ?><option value="<?= $c['id'] ?>" <?= $catId==$c['id']?'selected':'' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>
    <div class="col-md-2">
      <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>Tampilkan</button>
    </div>
  </div>
</form>

<!-- Summary -->
<?php if (!empty($summary)): ?>
<div class="row g-3 mb-4">
  <?php foreach ($summary as $label=>$val): ?>
  <div class="col-auto">
    <div class="stat-card py-2 px-3">
      <div class="stat-body"><div class="stat-value" style="font-size:1.2rem"><?= is_numeric($val)?formatNumber((float)$val):$val ?></div><div class="stat-label"><?= e($label) ?></div></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="card-panel">
  <div class="card-panel-header">
    <span><?= e($jenisList[$jenis]) ?></span>
    <small class="text-muted"><?= count($data) ?> baris</small>
  </div>
  <div class="table-responsive">
    <table class="table-modern w-100">
      <thead><tr><?php foreach ($columns as $col): ?><th><?= e($col) ?></th><?php endforeach; ?></tr></thead>
      <tbody>
        <?php if (empty($data)): ?>
          <tr><td colspan="<?= count($columns) ?>" class="empty-state"><span class="empty-icon">📊</span>Tidak ada data</td></tr>
        <?php else: ?>
          <?php foreach ($data as $row): ?>
          <tr>
            <?php $vals = array_values($row); foreach ($vals as $i=>$cell): ?>
            <td>
              <?php if ($columns[$i]==='Status'): ?>
                <span class="badge-status <?= $cell==='Kritis'?'badge-kritis':'badge-aman' ?>"><?= e($cell) ?></span>
              <?php elseif ($columns[$i]==='Tipe'): ?>
                <span class="badge-status <?= $cell==='IN'?'badge-in':'badge-out' ?>"><?= $cell==='IN'?'Masuk':'Keluar' ?></span>
              <?php elseif ($columns[$i]==='Role'): ?>
                <span class="badge-status <?= $cell==='admin'?'badge-admin':'badge-staff' ?>"><?= ucfirst(e($cell)) ?></span>
              <?php elseif ($columns[$i]==='Tanggal'): ?>
                <?= e(formatDate((string)$cell,'d M Y')) ?>
              <?php elseif (in_array($columns[$i],['Stok','Jumlah','Total Stok','Total Qty','Nilai','Nilai Total','Total Transaksi','Transaksi Masuk','Transaksi Keluar','Jumlah Produk','transaksi','total_qty'])): ?>
                <strong><?= is_numeric($cell)?formatNumber((float)$cell):e((string)$cell) ?></strong>
              <?php elseif ($columns[$i]==='Harga' || $columns[$i]==='Harga/Unit' || $columns[$i]==='Nilai Total' || $columns[$i]==='Nilai'): ?>
                <?= formatRupiah((float)($cell??0)) ?>
              <?php else: ?>
                <?= e((string)($cell ?? '-')) ?>
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
<?php include dirname(__DIR__) . '/views/partials/footer_admin.php'; ?>
