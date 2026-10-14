<?php
/**
 * Halaman 13: Relasi Produk-Supplier (Admin)
 * Implementasi many-to-many melalui tabel product_suppliers
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/helpers/auth.php';
require_once dirname(__DIR__) . '/helpers/functions.php';
startSecureSession();
requireAdmin();

$db = getDB();
$msg = ''; $msgType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $msg = 'Token tidak valid.'; $msgType='danger';
    } else {
        $action = $_POST['action'] ?? '';
        if ($action === 'add') {
            $prodId  = sanitizeInt($_POST['product_id'] ?? 0);
            $supId   = sanitizeInt($_POST['supplier_id'] ?? 0);
            $price   = max(0, (float)($_POST['supplier_price'] ?? 0));
            if (!$prodId || !$supId) { $msg='Produk dan supplier wajib dipilih.'; $msgType='danger'; }
            else {
                try {
                    $db->prepare("INSERT INTO product_suppliers (product_id,supplier_id,supplier_price) VALUES (?,?,?)")
                       ->execute([$prodId,$supId,$price]);
                    $msg = 'Relasi berhasil ditambahkan.';
                } catch (\PDOException $e) {
                    $msg = 'Pasangan produk-supplier sudah ada.'; $msgType='danger';
                }
            }
        } elseif ($action === 'edit_price') {
            $prodId = sanitizeInt($_POST['product_id'] ?? 0);
            $supId  = sanitizeInt($_POST['supplier_id'] ?? 0);
            $price  = max(0, (float)($_POST['supplier_price'] ?? 0));
            $db->prepare("UPDATE product_suppliers SET supplier_price=? WHERE product_id=? AND supplier_id=?")
               ->execute([$price,$prodId,$supId]);
            $msg = 'Harga supplier diperbarui.';
        } elseif ($action === 'delete') {
            $prodId = sanitizeInt($_POST['product_id'] ?? 0);
            $supId  = sanitizeInt($_POST['supplier_id'] ?? 0);
            $db->prepare("DELETE FROM product_suppliers WHERE product_id=? AND supplier_id=?")->execute([$prodId,$supId]);
            $msg = 'Relasi dihapus.';
        }
    }
}

// List relasi
$search = sanitizeString($_GET['q'] ?? '', 100);
$where  = "1=1"; $bind = [];
if ($search) { $where .= " AND (p.name LIKE ? OR s.name LIKE ?)"; $bind[]="%$search%"; $bind[]="%$search%"; }

$st = $db->prepare("
  SELECT ps.product_id, ps.supplier_id, ps.supplier_price,
         p.name AS produk, p.sku, c.name AS kategori,
         s.name AS supplier, s.type AS sup_type
  FROM product_suppliers ps
  JOIN products p ON ps.product_id=p.id
  JOIN categories c ON p.category_id=c.id
  JOIN suppliers s ON ps.supplier_id=s.id
  WHERE $where
  ORDER BY p.name, s.name
");
$st->execute($bind);
$rows = $st->fetchAll();

// Dropdowns
$products  = $db->query("SELECT id,sku,name FROM products ORDER BY name")->fetchAll();
$suppliers = $db->query("SELECT id,name,type FROM suppliers ORDER BY name")->fetchAll();

$pageTitle = 'Pasokan Produk & Supplier';
$activeMenu = 'produk';
include dirname(__DIR__) . '/views/partials/header_admin.php';
?>
<nav class="breadcrumb-bar"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/dashboard.php">Dashboard</a></li><li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/produk.php">Produk</a></li><li class="breadcrumb-item active">Pasokan Supplier</li></ol></nav>
<?php if ($msg): ?><div class="alert alert-<?= $msgType ?> alert-dismissible fade show"><?= e($msg) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

<div class="row g-4">
  <!-- Form tambah relasi -->
  <div class="col-lg-4">
    <div class="card-panel">
      <div class="card-panel-header">Atur Pasokan & Donatur Produk</div>
      <div class="card-panel-body">
        <form method="POST">
          <?= csrfField() ?>
          <input type="hidden" name="action" value="add">
          <div class="mb-3">
            <label class="form-label fw-semibold">Produk <span class="text-danger">*</span></label>
            <select name="product_id" class="form-select" required>
              <option value="">— Pilih Produk —</option>
              <?php foreach ($products as $p): ?><option value="<?= $p['id'] ?>"><?= e($p['sku']) ?> - <?= e($p['name']) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Supplier <span class="text-danger">*</span></label>
            <select name="supplier_id" class="form-select" required>
              <option value="">— Pilih Supplier —</option>
              <?php foreach ($suppliers as $s): ?><option value="<?= $s['id'] ?>"><?= e($s['name']) ?> (<?= e($s['type']) ?>)</option><?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Harga Supplier (Rp)</label>
            <input type="number" name="supplier_price" class="form-control" value="0" min="0">
          </div>
          <button type="submit" class="btn-primary-custom w-100"><i class="bi bi-link-45deg me-1"></i>Hubungkan Pasokan</button>
        </form>
      </div>
    </div>
  </div>

  <!-- Daftar relasi -->
  <div class="col-lg-8">
    <div class="card-panel">
      <div class="card-panel-header">
        <span>Daftar Pasokan Produk & Supplier</span>
        <form method="GET" class="d-flex gap-2">
          <input type="text" name="q" class="form-control form-control-sm" placeholder="🔍 Cari..." value="<?= e($search) ?>">
          <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search"></i></button>
        </form>
      </div>
      <div class="table-responsive">
        <table class="table-modern w-100">
          <thead><tr><th>Produk (SKU)</th><th>Kategori</th><th>Supplier</th><th>Tipe</th><th>Harga Supplier</th><th>Aksi</th></tr></thead>
          <tbody>
            <?php if (empty($rows)): ?>
              <tr><td colspan="6" class="empty-state"><span class="empty-icon">🔗</span>Belum ada relasi</td></tr>
            <?php else: ?>
              <?php foreach ($rows as $r): ?>
              <tr>
                <td><strong><?= e($r['produk']) ?></strong><br><code class="small"><?= e($r['sku']) ?></code></td>
                <td><?= e($r['kategori']) ?></td>
                <td><?= e($r['supplier']) ?></td>
                <td><span class="badge-status badge-publik"><?= e($r['sup_type']) ?></span></td>
                <td>
                  <form method="POST" class="d-flex gap-1 align-items-center">
                    <?= csrfField() ?><input type="hidden" name="action" value="edit_price">
                    <input type="hidden" name="product_id" value="<?= $r['product_id'] ?>">
                    <input type="hidden" name="supplier_id" value="<?= $r['supplier_id'] ?>">
                    <input type="number" name="supplier_price" value="<?= $r['supplier_price'] ?>" class="form-control form-control-sm" style="width:100px" min="0">
                    <button type="submit" class="btn-sm-action btn-edit"><i class="bi bi-check"></i></button>
                  </form>
                </td>
                <td>
                  <form method="POST" class="d-inline" onsubmit="return confirm('Hapus relasi ini?')">
                    <?= csrfField() ?><input type="hidden" name="action" value="delete">
                    <input type="hidden" name="product_id" value="<?= $r['product_id'] ?>">
                    <input type="hidden" name="supplier_id" value="<?= $r['supplier_id'] ?>">
                    <button type="submit" class="btn-sm-action btn-delete"><i class="bi bi-trash"></i></button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php include dirname(__DIR__) . '/views/partials/footer_admin.php'; ?>
