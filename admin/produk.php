<?php
/**
 * Halaman 11: Manajemen Produk (Admin)
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/helpers/auth.php';
require_once dirname(__DIR__) . '/helpers/functions.php';

startSecureSession();
requireAdmin();

$db = getDB();
$msg = '';
$msgType = 'success';

// ── POST Handler ───────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST[CSRF_TOKEN_NAME] ?? '';
    if (!verifyCsrfToken($token)) {
        $msg = 'Token keamanan tidak valid.'; $msgType = 'danger';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'add' || $action === 'edit') {
            $catId   = sanitizeInt($_POST['category_id'] ?? 0);
            $sku     = sanitizeString($_POST['sku'] ?? '', 50);
            $name    = sanitizeString($_POST['name'] ?? '', 200);
            $unit    = sanitizeString($_POST['unit'] ?? 'pcs', 30);
            $price   = max(0, (float)($_POST['price'] ?? 0));
            $minStk  = max(0, sanitizeInt($_POST['minimum_stock'] ?? 0));
            $desc    = sanitizeString($_POST['description'] ?? '', 500);
            $isPublic= isset($_POST['is_public']) ? 1 : 0;

            if (!$catId || !$sku || !$name) {
                $msg = 'Kategori, SKU, dan Nama wajib diisi.'; $msgType = 'danger';
            } else {
                try {
                    if ($action === 'add') {
                        $st = $db->prepare("INSERT INTO products (category_id,sku,name,unit,price,minimum_stock,description,is_public) VALUES (?,?,?,?,?,?,?,?)");
                        $st->execute([$catId,$sku,$name,$unit,$price,$minStk,$desc,$isPublic]);
                        $msg = "Produk '$name' berhasil ditambahkan.";
                    } else {
                        $id = sanitizeInt($_POST['id'] ?? 0);
                        $st = $db->prepare("UPDATE products SET category_id=?,sku=?,name=?,unit=?,price=?,minimum_stock=?,description=?,is_public=? WHERE id=?");
                        $st->execute([$catId,$sku,$name,$unit,$price,$minStk,$desc,$isPublic,$id]);
                        $msg = "Produk '$name' berhasil diperbarui.";
                    }
                } catch (\PDOException $e) {
                    $msg = str_contains($e->getMessage(),'Duplicate') ? "SKU '$sku' sudah digunakan." : 'Terjadi kesalahan database.';
                    $msgType = 'danger';
                }
            }
        } elseif ($action === 'delete') {
            $id = sanitizeInt($_POST['id'] ?? 0);
            // Cek apakah produk punya histori
            $hasHistory = (int)$db->prepare("SELECT COUNT(*) FROM stock_movements WHERE product_id=?")->execute([$id]) && (bool)$db->query("SELECT COUNT(*) FROM stock_movements WHERE product_id=$id")->fetchColumn();
            $st = $db->prepare("SELECT COUNT(*) FROM stock_movements WHERE product_id=?");
            $st->execute([$id]);
            if ((int)$st->fetchColumn() > 0) {
                $msg = 'Produk tidak dapat dihapus karena memiliki riwayat transaksi.'; $msgType = 'danger';
            } else {
                $db->prepare("DELETE FROM products WHERE id=?")->execute([$id]);
                $msg = 'Produk berhasil dihapus.';
            }
        }
    }
}

// ── Query list ──────────────────────────────────────────────────────────────
$search  = sanitizeString($_GET['q']       ?? '', 100);
$catId   = sanitizeInt($_GET['cat']        ?? 0);
$status  = sanitizeString($_GET['status'] ?? '', 20);
$sortCol = getAllowedSort($_GET['sort'] ?? 'name', ['name','sku','stock','price','kategori'], 'name');
$sortDir = getAllowedOrder($_GET['dir'] ?? 'ASC');
$page    = max(1, sanitizeInt($_GET['page'] ?? 1));
$perPage = ITEMS_PER_PAGE;
$offset  = ($page-1)*$perPage;
$params  = ['q'=>$search,'cat'=>$catId,'status'=>$status,'sort'=>$sortCol,'dir'=>$sortDir];

$cats = $db->query("SELECT id,name FROM categories ORDER BY name")->fetchAll();

$where = "1=1"; $bind = [];
if ($search) { $where .= " AND (p.name LIKE ? OR p.sku LIKE ?)"; $bind[]="%$search%"; $bind[]="%$search%"; }
if ($catId)  { $where .= " AND p.category_id=?"; $bind[]=$catId; }
if ($status === 'kritis') $where .= " AND p.stock <= p.minimum_stock AND p.minimum_stock > 0";
elseif ($status === 'aman') $where .= " AND (p.stock > p.minimum_stock OR p.minimum_stock=0)";

$cntSt = $db->prepare("SELECT COUNT(*) FROM products p JOIN categories c ON p.category_id=c.id WHERE $where");
$cntSt->execute($bind);
$total = (int)$cntSt->fetchColumn();
$totalPages = (int)ceil($total/$perPage);

$sortSQL = $sortCol === 'kategori' ? 'c.name' : "p.$sortCol";
$st = $db->prepare("SELECT p.*,c.name AS kategori FROM products p JOIN categories c ON p.category_id=c.id WHERE $where ORDER BY $sortSQL $sortDir LIMIT $perPage OFFSET $offset");
$st->execute($bind);
$rows = $st->fetchAll();

$pageTitle = 'Manajemen Produk';
$activeMenu = 'produk';
include dirname(__DIR__) . '/views/partials/header_admin.php';
?>
<nav class="breadcrumb-bar"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/dashboard.php">Dashboard</a></li><li class="breadcrumb-item active">Produk</li></ol></nav>

<?php if ($msg): ?>
<div class="alert alert-<?= $msgType ?> alert-dismissible fade show" role="alert"><?= e($msg) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div></div>
  <button class="btn-primary-custom" data-bs-toggle="modal" data-bs-target="#modalProduk" onclick="resetForm()">
    <i class="bi bi-plus-lg"></i> Tambah Produk
  </button>
</div>

<!-- Filter -->
<form method="GET" class="filter-bar mb-3">
  <div class="row g-2 align-items-end">
    <div class="col-md-4"><input type="text" name="q" class="form-control" placeholder="🔍 Cari nama atau SKU..." value="<?= e($search) ?>"></div>
    <div class="col-md-2">
      <select name="cat" class="form-select"><option value="">Semua Kategori</option>
        <?php foreach ($cats as $c): ?><option value="<?= $c['id'] ?>" <?= $catId==$c['id']?'selected':'' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <select name="status" class="form-select"><option value="">Semua Status</option>
        <option value="aman" <?= $status==='aman'?'selected':'' ?>>Aman</option>
        <option value="kritis" <?= $status==='kritis'?'selected':'' ?>>Kritis</option>
      </select>
    </div>
    <div class="col-md-2">
      <select name="sort" class="form-select">
        <option value="name" <?= $sortCol==='name'?'selected':'' ?>>Nama</option>
        <option value="sku" <?= $sortCol==='sku'?'selected':'' ?>>SKU</option>
        <option value="stock" <?= $sortCol==='stock'?'selected':'' ?>>Stok</option>
        <option value="price" <?= $sortCol==='price'?'selected':'' ?>>Harga</option>
      </select>
    </div>
    <div class="col-md-1">
      <select name="dir" class="form-select"><option value="ASC" <?= $sortDir==='ASC'?'selected':'' ?>>↑</option><option value="DESC" <?= $sortDir==='DESC'?'selected':'' ?>>↓</option></select>
    </div>
    <div class="col-md-1 d-flex gap-1">
      <button type="submit" class="btn btn-primary flex-fill"><i class="bi bi-search"></i></button>
      <a href="?" class="btn btn-outline-secondary"><i class="bi bi-x"></i></a>
    </div>
  </div>
</form>

<div class="card-panel">
  <div class="card-panel-header"><span>Data Produk</span><small class="text-muted"><?= $total ?> produk</small></div>
  <div class="table-responsive">
    <table class="table-modern w-100">
      <thead><tr>
        <th>SKU</th><th>Nama Produk</th><th>Kategori</th><th>Harga</th><th>Stok</th><th>Min. Stok</th><th>Satuan</th><th>Status</th><th>Publik</th><th>Aksi</th>
      </tr></thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr><td colspan="10" class="empty-state"><span class="empty-icon">📦</span>Tidak ada produk</td></tr>
        <?php else: ?>
          <?php foreach ($rows as $r): ?>
            <?php $kritis = $r['stock'] <= $r['minimum_stock'] && $r['minimum_stock'] > 0; ?>
            <tr>
              <td><code><?= e($r['sku']) ?></code></td>
              <td><strong><?= e($r['name']) ?></strong></td>
              <td><?= e($r['kategori']) ?></td>
              <td><?= formatRupiah($r['price']) ?></td>
              <td class="<?= $kritis?'text-danger fw-bold':'' ?>"><?= formatNumber($r['stock']) ?></td>
              <td><?= formatNumber($r['minimum_stock']) ?></td>
              <td><?= e($r['unit']) ?></td>
              <td><span class="badge-status <?= $kritis?'badge-kritis':'badge-aman' ?>"><?= $kritis?'Kritis':'Aman' ?></span></td>
              <td><span class="badge-status <?= $r['is_public']?'badge-publik':'badge-privat' ?>"><?= $r['is_public']?'Ya':'Tidak' ?></span></td>
              <td>
                <button class="btn-sm-action btn-edit me-1" onclick="editProduk(<?= htmlspecialchars(json_encode($r)) ?>)"><i class="bi bi-pencil"></i></button>
                <form method="POST" class="d-inline" onsubmit="return confirm('Hapus produk ini?')">
                  <?= csrfField() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $r['id'] ?>">
                  <button type="submit" class="btn-sm-action btn-delete"><i class="bi bi-trash"></i></button>
                </form>
              </td>
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

<!-- Modal Tambah/Edit Produk -->
<div class="modal fade" id="modalProduk" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalProdukTitle">Tambah Produk</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="">
        <?= csrfField() ?>
        <input type="hidden" name="action" id="formAction" value="add">
        <input type="hidden" name="id" id="produkId" value="">
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Kategori <span class="text-danger">*</span></label>
              <select name="category_id" id="produkCat" class="form-select" required>
                <option value="">— Pilih Kategori —</option>
                <?php foreach ($cats as $c): ?><option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">SKU <span class="text-danger">*</span></label>
              <input type="text" name="sku" id="produkSku" class="form-control" placeholder="cth: PRD016" required>
            </div>
            <div class="col-md-12">
              <label class="form-label fw-semibold">Nama Produk <span class="text-danger">*</span></label>
              <input type="text" name="name" id="produkName" class="form-control" required>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Satuan</label>
              <input type="text" name="unit" id="produkUnit" class="form-control" value="pcs">
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Harga (Rp)</label>
              <input type="number" name="price" id="produkPrice" class="form-control" value="0" min="0">
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Stok Minimum</label>
              <input type="number" name="minimum_stock" id="produkMinStk" class="form-control" value="0" min="0">
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold">Deskripsi</label>
              <textarea name="description" id="produkDesc" class="form-control" rows="2"></textarea>
            </div>
            <div class="col-12">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="is_public" id="produkPublic" value="1" checked>
                <label class="form-check-label" for="produkPublic">Tampilkan di halaman publik</label>
              </div>
            </div>
            <div class="col-12">
              <div class="alert alert-info py-2 px-3 small mb-0">
                <i class="bi bi-info-circle me-1"></i>
                <strong>Perhatian:</strong> Stok tidak dapat diubah langsung di sini. Gunakan menu <a href="<?= BASE_URL ?>/admin/barang_masuk.php">Barang Masuk</a> untuk menambah stok awal.
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn-primary-custom"><i class="bi bi-check-lg me-1"></i>Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include dirname(__DIR__) . '/views/partials/footer_admin.php'; ?>
<script>
function resetForm() {
  document.getElementById('modalProdukTitle').textContent = 'Tambah Produk';
  document.getElementById('formAction').value = 'add';
  document.getElementById('produkId').value = '';
  document.getElementById('produkCat').value = '';
  document.getElementById('produkSku').value = '';
  document.getElementById('produkName').value = '';
  document.getElementById('produkUnit').value = 'pcs';
  document.getElementById('produkPrice').value = '0';
  document.getElementById('produkMinStk').value = '0';
  document.getElementById('produkDesc').value = '';
  document.getElementById('produkPublic').checked = true;
}
function editProduk(d) {
  document.getElementById('modalProdukTitle').textContent = 'Edit Produk';
  document.getElementById('formAction').value = 'edit';
  document.getElementById('produkId').value = d.id;
  document.getElementById('produkCat').value = d.category_id;
  document.getElementById('produkSku').value = d.sku;
  document.getElementById('produkName').value = d.name;
  document.getElementById('produkUnit').value = d.unit;
  document.getElementById('produkPrice').value = d.price;
  document.getElementById('produkMinStk').value = d.minimum_stock;
  document.getElementById('produkDesc').value = d.description || '';
  document.getElementById('produkPublic').checked = d.is_public == 1;
  new bootstrap.Modal(document.getElementById('modalProduk')).show();
}
</script>
