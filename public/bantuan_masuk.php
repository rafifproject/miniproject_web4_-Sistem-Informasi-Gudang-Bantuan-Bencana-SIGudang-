<?php
/**
 * Halaman 4: Riwayat Bantuan Masuk Publik
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/helpers/auth.php';
require_once dirname(__DIR__) . '/helpers/functions.php';
startSecureSession();
$db = getDB();

$dateFrom  = sanitizeString($_GET['from']     ?? '', 20);
$dateTo    = sanitizeString($_GET['to']       ?? '', 20);
$catId     = sanitizeInt($_GET['cat']         ?? 0);
$supId     = sanitizeInt($_GET['supplier']    ?? 0);
$page      = max(1, sanitizeInt($_GET['page'] ?? 1));
$perPage   = ITEMS_PER_PAGE;
$offset    = ($page - 1) * $perPage;
$params    = ['from'=>$dateFrom,'to'=>$dateTo,'cat'=>$catId,'supplier'=>$supId];

$cats = $db->query("SELECT id,name FROM categories ORDER BY name")->fetchAll();
$sups = $db->query("SELECT id,name FROM suppliers WHERE is_public=1 ORDER BY name")->fetchAll();

$where = "sm.type='IN' AND sm.is_public=1 AND p.is_public=1";
$bind  = [];
if ($dateFrom) { $where .= " AND sm.date >= ?"; $bind[] = $dateFrom; }
if ($dateTo)   { $where .= " AND sm.date <= ?"; $bind[] = $dateTo; }
if ($catId)    { $where .= " AND p.category_id = ?"; $bind[] = $catId; }
if ($supId)    { $where .= " AND sm.supplier_id = ?"; $bind[] = $supId; }

$total = (int)$db->prepare("SELECT COUNT(*) FROM stock_movements sm JOIN products p ON sm.product_id=p.id WHERE $where")->execute($bind) ? 0 : 0;
$countStmt = $db->prepare("SELECT COUNT(*) FROM stock_movements sm JOIN products p ON sm.product_id=p.id WHERE $where");
$countStmt->execute($bind);
$total = (int)$countStmt->fetchColumn();
$totalPages = (int)ceil($total / $perPage);

$stmt = $db->prepare("
  SELECT sm.date, sm.quantity, sm.reference, sm.notes,
         p.name AS produk, p.unit, c.name AS kategori,
         s.name AS supplier
  FROM stock_movements sm
  JOIN products p ON sm.product_id=p.id
  JOIN categories c ON p.category_id=c.id
  LEFT JOIN suppliers s ON sm.supplier_id=s.id AND s.is_public=1
  WHERE $where
  ORDER BY sm.date DESC, sm.id DESC
  LIMIT $perPage OFFSET $offset
");
$stmt->execute($bind);
$rows = $stmt->fetchAll();

$pageTitle = 'Riwayat Bantuan Masuk';
$activeMenu = 'masuk';
include dirname(__DIR__) . '/views/partials/header_public.php';
?>
<div class="container py-4">
  <div class="mb-4">
    <h1 class="section-heading">Riwayat <span>Bantuan Masuk</span></h1>
    <div class="section-divider"></div>
    <p class="text-muted">Catatan penerimaan bantuan dari supplier dan donatur.</p>
  </div>

  <form method="GET" class="filter-bar mb-4">
    <div class="row g-2 align-items-end">
      <div class="col-md-2"><label class="form-label small fw-semibold mb-1">Dari</label>
        <input type="date" name="from" class="form-control" value="<?= e($dateFrom) ?>"></div>
      <div class="col-md-2"><label class="form-label small fw-semibold mb-1">Sampai</label>
        <input type="date" name="to" class="form-control" value="<?= e($dateTo) ?>"></div>
      <div class="col-md-3">
        <select name="cat" class="form-select">
          <option value="">Semua Kategori</option>
          <?php foreach ($cats as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $catId==$c['id']?'selected':'' ?>><?= e($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <select name="supplier" class="form-select">
          <option value="">Semua Donatur</option>
          <?php foreach ($sups as $s): ?>
            <option value="<?= $s['id'] ?>" <?= $supId==$s['id']?'selected':'' ?>><?= e($s['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2 d-flex gap-1">
        <button type="submit" class="btn btn-primary flex-fill"><i class="bi bi-funnel"></i> Filter</button>
        <a href="?" class="btn btn-outline-secondary"><i class="bi bi-x"></i></a>
      </div>
    </div>
  </form>

  <div class="card-panel">
    <div class="card-panel-header">
      <span>Data Bantuan Masuk</span>
      <small class="text-muted"><?= $total ?> transaksi</small>
    </div>
    <div class="table-responsive">
      <table class="table-modern w-100">
        <thead><tr>
          <th>Tanggal</th><th>Nama Barang</th><th>Kategori</th><th>Jumlah</th><th>Satuan</th><th>Donatur</th><th>Keterangan</th>
        </tr></thead>
        <tbody>
          <?php if (empty($rows)): ?>
            <tr><td colspan="7" class="empty-state"><span class="empty-icon">📥</span>Tidak ada data</td></tr>
          <?php else: ?>
            <?php foreach ($rows as $r): ?>
              <tr>
                <td><?= e(formatDate($r['date'],'d M Y')) ?></td>
                <td><strong><?= e($r['produk']) ?></strong></td>
                <td><?= e($r['kategori']) ?></td>
                <td class="fw-bold text-success">+<?= formatNumber($r['quantity']) ?></td>
                <td><?= e($r['unit']) ?></td>
                <td><?= e($r['supplier'] ?: '-') ?></td>
                <td class="text-muted small"><?= e($r['notes'] ?: '-') ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <div class="card-panel-body d-flex justify-content-between align-items-center flex-wrap gap-2 border-top">
      <small class="text-muted">Menampilkan <?= min($total,$offset+1) ?>–<?= min($total,$offset+$perPage) ?> dari <?= $total ?> data</small>
      <?= paginationLinks($page, $totalPages, $params) ?>
    </div>
  </div>
</div>
<?php include dirname(__DIR__) . '/views/partials/footer_public.php'; ?>
