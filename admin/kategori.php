<?php
/**
 * Halaman Manajemen Kategori (Admin)
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
        $name = sanitizeString($_POST['name'] ?? '', 100);
        $desc = sanitizeString($_POST['description'] ?? '', 500);
        if ($action === 'add') {
            if (!$name) { $msg='Nama kategori wajib diisi.'; $msgType='danger'; }
            else {
                try {
                    $db->prepare("INSERT INTO categories (name,description) VALUES (?,?)")->execute([$name,$desc]);
                    $msg = "Kategori '$name' ditambahkan.";
                } catch (\PDOException $e) {
                    $msg = "Nama '$name' sudah ada."; $msgType='danger';
                }
            }
        } elseif ($action === 'edit') {
            $id = sanitizeInt($_POST['id'] ?? 0);
            $db->prepare("UPDATE categories SET name=?,description=? WHERE id=?")->execute([$name,$desc,$id]);
            $msg = "Kategori diperbarui.";
        } elseif ($action === 'delete') {
            $id = sanitizeInt($_POST['id'] ?? 0);
            $cnt = (int)$db->query("SELECT COUNT(*) FROM products WHERE category_id=$id")->fetchColumn();
            if ($cnt > 0) { $msg="Kategori memiliki $cnt produk, tidak dapat dihapus."; $msgType='danger'; }
            else {
                $db->prepare("DELETE FROM categories WHERE id=?")->execute([$id]);
                $msg = "Kategori dihapus.";
            }
        }
    }
}

$rows = $db->query("SELECT c.*, COUNT(p.id) AS jumlah_produk FROM categories c LEFT JOIN products p ON p.category_id=c.id GROUP BY c.id ORDER BY c.name")->fetchAll();

$pageTitle = 'Manajemen Kategori';
$activeMenu = 'kategori';
include dirname(__DIR__) . '/views/partials/header_admin.php';
?>
<nav class="breadcrumb-bar"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/dashboard.php">Dashboard</a></li><li class="breadcrumb-item active">Kategori</li></ol></nav>
<?php if ($msg): ?><div class="alert alert-<?= $msgType ?> alert-dismissible fade show"><?= e($msg) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

<div class="d-flex justify-content-between mb-3">
  <div></div>
  <button class="btn-primary-custom" data-bs-toggle="modal" data-bs-target="#modalKat" onclick="resetKat()"><i class="bi bi-plus-lg"></i> Tambah Kategori</button>
</div>

<div class="card-panel">
  <div class="card-panel-header"><span>Data Kategori</span><small class="text-muted"><?= count($rows) ?> kategori</small></div>
  <table class="table-modern w-100">
    <thead><tr><th>#</th><th>Nama Kategori</th><th>Deskripsi</th><th>Jumlah Produk</th><th>Aksi</th></tr></thead>
    <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="5" class="empty-state"><span class="empty-icon">🏷️</span>Belum ada kategori</td></tr>
      <?php else: ?>
        <?php foreach ($rows as $i=>$r): ?>
        <tr>
          <td><?= $i+1 ?></td>
          <td><strong><?= e($r['name']) ?></strong></td>
          <td class="text-muted small"><?= e($r['description'] ?: '-') ?></td>
          <td><?= $r['jumlah_produk'] ?> produk</td>
          <td>
            <button class="btn-sm-action btn-edit me-1" onclick="editKat(<?= htmlspecialchars(json_encode($r)) ?>)"><i class="bi bi-pencil"></i></button>
            <form method="POST" class="d-inline" onsubmit="return confirm('Hapus kategori ini?')">
              <?= csrfField() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $r['id'] ?>">
              <button type="submit" class="btn-sm-action btn-delete" <?= $r['jumlah_produk']>0?'disabled title="Ada produk"':'' ?>><i class="bi bi-trash"></i></button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<div class="modal fade" id="modalKat" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title" id="modalKatTitle">Tambah Kategori</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <form method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="action" id="katAction" value="add">
        <input type="hidden" name="id" id="katId" value="">
        <div class="modal-body">
          <div class="mb-3"><label class="form-label fw-semibold">Nama Kategori <span class="text-danger">*</span></label><input type="text" name="name" id="katName" class="form-control" required></div>
          <div class="mb-3"><label class="form-label fw-semibold">Deskripsi</label><textarea name="description" id="katDesc" class="form-control" rows="3"></textarea></div>
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
function resetKat(){document.getElementById('modalKatTitle').textContent='Tambah Kategori';document.getElementById('katAction').value='add';document.getElementById('katId').value='';document.getElementById('katName').value='';document.getElementById('katDesc').value='';}
function editKat(d){document.getElementById('modalKatTitle').textContent='Edit Kategori';document.getElementById('katAction').value='edit';document.getElementById('katId').value=d.id;document.getElementById('katName').value=d.name;document.getElementById('katDesc').value=d.description||'';new bootstrap.Modal(document.getElementById('modalKat')).show();}
</script>
