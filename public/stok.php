<?php
/**
 * Halaman 3: Stok Bantuan Publik
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/helpers/auth.php';
require_once dirname(__DIR__) . '/helpers/functions.php';
startSecureSession();

$db = getDB();

// Params
$search   = sanitizeString($_GET['q']        ?? '', 100);
$catId    = sanitizeInt($_GET['cat']          ?? 0);
$status   = sanitizeString($_GET['status']   ?? '', 20);
$sortCol  = getAllowedSort($_GET['sort'] ?? 'name', ['name','sku','stock','kategori'], 'name');
$sortDir  = getAllowedOrder($_GET['dir'] ?? 'ASC');
$page     = max(1, sanitizeInt($_GET['page'] ?? 1));
$perPage  = ITEMS_PER_PAGE;
$offset   = ($page - 1) * $perPage;

// Base params for pagination links
$params = ['q'=>$search,'cat'=>$catId,'status'=>$status,'sort'=>$sortCol,'dir'=>$sortDir];

// Categories dropdown
$cats = $db->query("SELECT id,name FROM categories ORDER BY name")->fetchAll();

// WHERE
$where = "p.is_public=1";
$bind  = [];
if ($search) { $where .= " AND (p.name LIKE ? OR p.sku LIKE ?)"; $bind[] = "%$search%"; $bind[] = "%$search%"; }
if ($catId)  { $where .= " AND p.category_id=?"; $bind[] = $catId; }
if ($status === 'kritis') { $where .= " AND p.stock <= p.minimum_stock"; }
elseif ($status === 'aman') { $where .= " AND p.stock > p.minimum_stock"; }

$countStmt = $db->prepare("SELECT COUNT(*) FROM products p WHERE $where");
$countStmt->execute($bind);
$total = (int)$countStmt->fetchColumn();
$totalPages = (int)ceil($total / $perPage);

$sort = ($sortCol === 'kategori') ? 'c.name' : "p.$sortCol";
$stmt = $db->prepare("
  SELECT p.id,p.sku,p.name,p.stock,p.unit,p.minimum_stock,p.price,c.name AS kategori
  FROM products p JOIN categories c ON p.category_id=c.id
  WHERE $where
  ORDER BY $sort $sortDir
  LIMIT $perPage OFFSET $offset
");
$stmt->execute($bind);
$rows = $stmt->fetchAll();

$pageTitle = 'Stok Bantuan';
$activeMenu = 'stok';
include dirname(__DIR__) . '/views/partials/header_public.php';
?>
<div class="container py-4">
  <div class="mb-4">
    <h1 class="section-heading">Stok <span>Bantuan Tersedia</span></h1>
    <div class="section-divider"></div>
    <p class="text-muted">Data stok logistik bantuan bencana yang tersedia di gudang.</p>
  </div>

  <!-- Filter Bar -->
  <form method="GET" class="filter-bar mb-4">
    <div class="row g-2 align-items-end">
      <div class="col-md-4">
        <input type="text" name="q" class="form-control" placeholder="🔍 Cari nama barang atau SKU..." value="<?= e($search) ?>">
      </div>
      <div class="col-md-2">
        <select name="cat" class="form-select">
          <option value="">Semua Kategori</option>
          <?php foreach ($cats as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $catId==$c['id']?'selected':'' ?>><?= e($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <select name="status" class="form-select">
          <option value="">Semua Status</option>
          <option value="aman"   <?= $status==='aman'  ?'selected':'' ?>>Aman</option>
          <option value="kritis" <?= $status==='kritis'?'selected':'' ?>>Kritis</option>
        </select>
      </div>
      <div class="col-md-2">
        <select name="sort" class="form-select">
          <option value="name"     <?= $sortCol==='name'    ?'selected':'' ?>>Nama</option>
          <option value="sku"      <?= $sortCol==='sku'     ?'selected':'' ?>>SKU</option>
          <option value="stock"    <?= $sortCol==='stock'   ?'selected':'' ?>>Stok</option>
          <option value="kategori" <?= $sortCol==='kategori'?'selected':'' ?>>Kategori</option>
        </select>
      </div>
      <div class="col-md-1">
        <select name="dir" class="form-select">
          <option value="ASC"  <?= $sortDir==='ASC' ?'selected':'' ?>>↑</option>
          <option value="DESC" <?= $sortDir==='DESC'?'selected':'' ?>>↓</option>
        </select>
      </div>
      <div class="col-md-1 d-flex gap-1">
        <button type="submit" class="btn btn-primary flex-fill"><i class="bi bi-search"></i></button>
        <a href="?" class="btn btn-outline-secondary flex-fill"><i class="bi bi-x"></i></a>
      </div>
    </div>
  </form>

  <div class="card-panel">
    <div class="card-panel-header">
      <span>Daftar Stok Bantuan</span>
      <small class="text-muted">Total: <?= $total ?> barang</small>
    </div>
    <div class="table-responsive">
      <table class="table-modern w-100">
        <thead><tr>
          <th>SKU</th>
          <th><a href="?<?= http_build_query(array_merge($params,['sort'=>'name','dir'=>$sortDir==='ASC'?'DESC':'ASC','page'=>1])) ?>" class="sort-link">Nama Barang <?= $sortCol==='name'?($sortDir==='ASC'?'↑':'↓'):'' ?></a></th>
          <th>Kategori</th>
          <th><a href="?<?= http_build_query(array_merge($params,['sort'=>'stock','dir'=>$sortDir==='ASC'?'DESC':'ASC','page'=>1])) ?>" class="sort-link">Stok <?= $sortCol==='stock'?($sortDir==='ASC'?'↑':'↓'):'' ?></a></th>
          <th>Satuan</th>
          <th>Status</th>
        </tr></thead>
        <tbody>
          <?php if (empty($rows)): ?>
            <tr><td colspan="6" class="empty-state"><span class="empty-icon">📦</span>Tidak ada data ditemukan</td></tr>
          <?php else: ?>
            <?php foreach ($rows as $r): ?>
              <?php $isKritis = $r['stock'] <= $r['minimum_stock']; ?>
              <tr>
                <td><code><?= e($r['sku']) ?></code></td>
                <td><strong><?= e($r['name']) ?></strong></td>
                <td><?= e($r['kategori']) ?></td>
                <td>
                  <?= formatNumber($r['stock']) ?>
                  <?php if ($r['minimum_stock'] > 0): ?>
                    <div class="stock-progress">
                      <?php $pct = min(100, round($r['stock']/$r['minimum_stock']*100)); ?>
                      <div class="stock-progress-bar" style="width:<?= $pct ?>%;background:<?= $isKritis?'#dc3545':'#0fa86a' ?>"></div>
                    </div>
                  <?php endif; ?>
                </td>
                <td><?= e($r['unit']) ?></td>
                <td><span class="badge-status <?= $isKritis?'badge-kritis':'badge-aman' ?>"><?= $isKritis?'Kritis':'Aman' ?></span></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <div class="card-panel-body d-flex justify-content-between align-items-center flex-wrap gap-2 border-top">
      <small class="text-muted">Menampilkan <?= min($total, $offset+1) ?>–<?= min($total, $offset+$perPage) ?> dari <?= $total ?> data</small>
      <?= paginationLinks($page, $totalPages, $params) ?>
    </div>
  </div>
</div>
<?php include dirname(__DIR__) . '/views/partials/footer_public.php'; ?>
