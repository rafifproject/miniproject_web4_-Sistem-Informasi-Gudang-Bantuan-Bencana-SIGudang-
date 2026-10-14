<?php
/**
 * Halaman 16: Riwayat Mutasi Stok (Admin & Staff)
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/helpers/auth.php';
require_once dirname(__DIR__) . '/helpers/functions.php';

startSecureSession();
requireAdminOrStaff();

$db = getDB();

$search   = sanitizeString($_GET['q']        ?? '', 100);
$type     = sanitizeString($_GET['type']     ?? '', 5);
$prodId   = sanitizeInt($_GET['product']     ?? 0);
$dateFrom = sanitizeString($_GET['from']     ?? '', 20);
$dateTo   = sanitizeString($_GET['to']       ?? '', 20);
$sortCol  = getAllowedSort($_GET['sort'] ?? 'date', ['date','quantity','type','produk'], 'date');
$sortDir  = getAllowedOrder($_GET['dir']  ?? 'DESC');
$page     = max(1, sanitizeInt($_GET['page'] ?? 1));
$perPage  = ITEMS_PER_PAGE;
$offset   = ($page-1)*$perPage;
$params   = ['q'=>$search,'type'=>$type,'product'=>$prodId,'from'=>$dateFrom,'to'=>$dateTo,'sort'=>$sortCol,'dir'=>$sortDir];

$products = $db->query("SELECT id,sku,name FROM products ORDER BY name")->fetchAll();

$where = "1=1"; $bind = [];
if ($search)   { $where .= " AND (p.name LIKE ? OR sm.reference LIKE ?)"; $bind[]="%$search%"; $bind[]="%$search%"; }
if ($type === 'IN' || $type === 'OUT') { $where .= " AND sm.type=?"; $bind[]=$type; }
if ($prodId)   { $where .= " AND sm.product_id=?"; $bind[]=$prodId; }
if ($dateFrom) { $where .= " AND sm.date >= ?"; $bind[]=$dateFrom; }
if ($dateTo)   { $where .= " AND sm.date <= ?"; $bind[]=$dateTo; }

$cntSt = $db->prepare("SELECT COUNT(*) FROM stock_movements sm JOIN products p ON sm.product_id=p.id WHERE $where");
$cntSt->execute($bind);
$total = (int)$cntSt->fetchColumn();
$totalPages = (int)ceil($total/$perPage);

$sortSQL = $sortCol === 'produk' ? 'p.name' : "sm.$sortCol";
$st = $db->prepare("
    SELECT sm.id, sm.date, sm.type, sm.quantity, sm.reference, sm.notes, sm.created_at,
           p.name AS produk, p.unit, p.sku,
           u.username,
           s.name AS supplier,
           dt.name AS tujuan
    FROM stock_movements sm
    JOIN products p ON sm.product_id=p.id
    JOIN users u ON sm.user_id=u.id
    LEFT JOIN suppliers s ON sm.supplier_id=s.id
    LEFT JOIN distribution_targets dt ON sm.distribution_target_id=dt.id
    WHERE $where
    ORDER BY $sortSQL $sortDir
    LIMIT $perPage OFFSET $offset
");
$st->execute($bind);
$rows = $st->fetchAll();

$pageTitle = 'Riwayat Mutasi Stok';
$activeMenu = 'mutasi';
include dirname(__DIR__) . '/views/partials/header_admin.php';
?>
<nav class="breadcrumb-bar"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/dashboard.php">Dashboard</a></li><li class="breadcrumb-item active">Mutasi Stok</li></ol></nav>

<!-- Filter -->
<form method="GET" class="filter-bar mb-3">
  <div class="row g-2 align-items-end">
    <div class="col-md-3"><input type="text" name="q" class="form-control" placeholder="🔍 Cari produk / referensi..." value="<?= e($search) ?>"></div>
    <div class="col-md-2">
      <select name="type" class="form-select">
        <option value="">Semua Tipe</option>
        <option value="IN"  <?= $type==='IN' ?'selected':'' ?>>Masuk (IN)</option>
        <option value="OUT" <?= $type==='OUT'?'selected':'' ?>>Keluar (OUT)</option>
      </select>
    </div>
    <div class="col-md-2">
      <select name="product" class="form-select">
        <option value="">Semua Produk</option>
        <?php foreach ($products as $p): ?>
          <option value="<?= $p['id'] ?>" <?= $prodId==$p['id']?'selected':'' ?>><?= e($p['sku'].' - '.$p['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2"><input type="date" name="from" class="form-control" value="<?= e($dateFrom) ?>" placeholder="Dari"></div>
    <div class="col-md-2"><input type="date" name="to" class="form-control" value="<?= e($dateTo) ?>" placeholder="Sampai"></div>
    <div class="col-md-1 d-flex gap-1">
      <button type="submit" class="btn btn-primary flex-fill"><i class="bi bi-search"></i></button>
      <a href="?" class="btn btn-outline-secondary"><i class="bi bi-x"></i></a>
    </div>
  </div>
</form>

<div class="card-panel">
  <div class="card-panel-header">
    <span>Riwayat Mutasi Stok</span>
    <small class="text-muted"><?= $total ?> transaksi</small>
  </div>
  <div class="table-responsive">
    <table class="table-modern w-100">
      <thead><tr>
        <th><a href="?<?= http_build_query(array_merge($params,['sort'=>'date','dir'=>$sortDir==='ASC'?'DESC':'ASC'])) ?>" class="sort-link">Tanggal <?= $sortCol==='date'?($sortDir==='ASC'?'↑':'↓'):'' ?></a></th>
        <th><a href="?<?= http_build_query(array_merge($params,['sort'=>'produk','dir'=>$sortDir==='ASC'?'DESC':'ASC'])) ?>" class="sort-link">Produk <?= $sortCol==='produk'?($sortDir==='ASC'?'↑':'↓'):'' ?></a></th>
        <th>Tipe</th>
        <th><a href="?<?= http_build_query(array_merge($params,['sort'=>'quantity','dir'=>$sortDir==='ASC'?'DESC':'ASC'])) ?>" class="sort-link">Jumlah <?= $sortCol==='quantity'?($sortDir==='ASC'?'↑':'↓'):'' ?></a></th>
        <th>Asal/Tujuan</th>
        <th>Oleh</th>
        <th>Referensi</th>
        <th>Catatan</th>
      </tr></thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr><td colspan="8" class="empty-state"><span class="empty-icon">📋</span>Tidak ada data</td></tr>
        <?php else: ?>
          <?php foreach ($rows as $r): ?>
          <tr>
            <td class="small"><?= e(formatDate($r['date'],'d M Y')) ?></td>
            <td><strong><?= e($r['produk']) ?></strong><br><code class="small text-muted"><?= e($r['sku']) ?></code></td>
            <td><span class="badge-status <?= $r['type']==='IN'?'badge-in':'badge-out' ?>"><?= $r['type']==='IN'?'📥 Masuk':'📤 Keluar' ?></span></td>
            <td class="<?= $r['type']==='IN'?'text-success':'text-danger' ?> fw-bold">
              <?= $r['type']==='IN'?'+':'-' ?><?= formatNumber($r['quantity']) ?> <?= e($r['unit']) ?>
            </td>
            <td class="small text-muted"><?= e($r['supplier'] ?? $r['tujuan'] ?? '-') ?></td>
            <td class="small"><?= e($r['username']) ?></td>
            <td class="small text-muted"><?= e($r['reference'] ?: '-') ?></td>
            <td class="small text-muted"><?= e(mb_substr($r['notes'] ?? '', 0, 40)) ?><?= strlen($r['notes']??'')>40?'…':'' ?></td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <div class="card-panel-body d-flex justify-content-between align-items-center flex-wrap gap-2 border-top">
    <small class="text-muted">Menampilkan <?= min($total,$offset+1) ?>–<?= min($total,$offset+$perPage) ?> dari <?= $total ?></small>
    <?= paginationLinks($page, $totalPages, $params) ?>
  </div>
</div>
<?php include dirname(__DIR__) . '/views/partials/footer_admin.php'; ?>
