<?php
/**
 * Halaman 12: Manajemen Supplier (Admin)
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
    $token = $_POST[CSRF_TOKEN_NAME] ?? '';
    if (!verifyCsrfToken($token)) {
        $msg = 'Token tidak valid.'; $msgType = 'danger';
    } else {
        $action = $_POST['action'] ?? '';
        if ($action === 'add' || $action === 'edit') {
            $name = sanitizeString($_POST['name'] ?? '', 200);
            $type = sanitizeString($_POST['type'] ?? '', 100);
            $contact = sanitizeString($_POST['contact'] ?? '', 150);
            $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
            $address = sanitizeString($_POST['address'] ?? '', 500);
            $desc = sanitizeString($_POST['description'] ?? '', 500);
            $isPublic = isset($_POST['is_public']) ? 1 : 0;

            if (!$name) { $msg = 'Nama supplier wajib diisi.'; $msgType='danger'; }
            else {
                if ($action === 'add') {
                    $db->prepare("INSERT INTO suppliers (name,type,contact,email,address,description,is_public) VALUES (?,?,?,?,?,?,?)")
                       ->execute([$name,$type,$contact,$email ?: null,$address,$desc,$isPublic]);
                    $msg = "Supplier '$name' berhasil ditambahkan.";
                } else {
                    $id = sanitizeInt($_POST['id'] ?? 0);
                    $db->prepare("UPDATE suppliers SET name=?,type=?,contact=?,email=?,address=?,description=?,is_public=? WHERE id=?")
                       ->execute([$name,$type,$contact,$email ?: null,$address,$desc,$isPublic,$id]);
                    $msg = "Supplier '$name' berhasil diperbarui.";
                }
            }
        } elseif ($action === 'delete') {
            $id = sanitizeInt($_POST['id'] ?? 0);
            $st = $db->prepare("SELECT COUNT(*) FROM stock_movements WHERE supplier_id=?");
            $st->execute([$id]);
            if ((int)$st->fetchColumn() > 0) {
                $msg = 'Supplier tidak dapat dihapus karena memiliki riwayat transaksi.'; $msgType='danger';
            } else {
                $db->prepare("DELETE FROM suppliers WHERE id=?")->execute([$id]);
                $msg = 'Supplier berhasil dihapus.';
            }
        }
    }
}

$search = sanitizeString($_GET['q'] ?? '', 100);
$type   = sanitizeString($_GET['type'] ?? '', 100);
$page   = max(1, sanitizeInt($_GET['page'] ?? 1));
$perPage= ITEMS_PER_PAGE; $offset = ($page-1)*$perPage;
$params = ['q'=>$search,'type'=>$type];

$types = $db->query("SELECT DISTINCT type FROM suppliers ORDER BY type")->fetchAll(PDO::FETCH_COLUMN);

$where = "1=1"; $bind = [];
if ($search) { $where .= " AND (name LIKE ? OR contact LIKE ?)"; $bind[]="%$search%"; $bind[]="%$search%"; }
if ($type)   { $where .= " AND type=?"; $bind[]=$type; }

$cntSt = $db->prepare("SELECT COUNT(*) FROM suppliers WHERE $where");
$cntSt->execute($bind);
$total = (int)$cntSt->fetchColumn();
$totalPages = (int)ceil($total/$perPage);

$st = $db->prepare("SELECT * FROM suppliers WHERE $where ORDER BY name LIMIT $perPage OFFSET $offset");
$st->execute($bind);
$rows = $st->fetchAll();

$pageTitle = 'Manajemen Supplier';
$activeMenu = 'supplier';
include dirname(__DIR__) . '/views/partials/header_admin.php';
?>
<nav class="breadcrumb-bar"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/dashboard.php">Dashboard</a></li><li class="breadcrumb-item active">Supplier</li></ol></nav>
<?php if ($msg): ?><div class="alert alert-<?= $msgType ?> alert-dismissible fade show"><?= e($msg) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div></div>
  <button class="btn-primary-custom" data-bs-toggle="modal" data-bs-target="#modalSup" onclick="resetSup()">
    <i class="bi bi-plus-lg"></i> Tambah Supplier
  </button>
</div>

<form method="GET" class="filter-bar mb-3">
  <div class="row g-2 align-items-end">
    <div class="col-md-5"><input type="text" name="q" class="form-control" placeholder="🔍 Cari nama atau kontak..." value="<?= e($search) ?>"></div>
    <div class="col-md-3">
      <select name="type" class="form-select"><option value="">Semua Tipe</option>
        <?php foreach ($types as $t): ?><option value="<?= e($t) ?>" <?= $type===$t?'selected':'' ?>><?= e($t) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2 d-flex gap-1">
      <button type="submit" class="btn btn-primary flex-fill"><i class="bi bi-search"></i></button>
      <a href="?" class="btn btn-outline-secondary"><i class="bi bi-x"></i></a>
    </div>
  </div>
</form>

<div class="card-panel">
  <div class="card-panel-header"><span>Data Supplier / Donatur</span><small class="text-muted"><?= $total ?> supplier</small></div>
  <div class="table-responsive">
    <table class="table-modern w-100">
      <thead><tr><th>Nama</th><th>Tipe</th><th>Kontak</th><th>Email</th><th>Deskripsi</th><th>Publik</th><th>Aksi</th></tr></thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr><td colspan="7" class="empty-state"><span class="empty-icon">🏢</span>Tidak ada supplier</td></tr>
        <?php else: ?>
          <?php foreach ($rows as $r): ?>
          <tr>
            <td><strong><?= e($r['name']) ?></strong></td>
            <td><span class="badge-status badge-publik"><?= e($r['type']) ?></span></td>
            <td><?= e($r['contact'] ?: '-') ?></td>
            <td><?= e($r['email'] ?: '-') ?></td>
            <td class="text-muted small"><?= e(mb_substr($r['description'] ?? '', 0, 60)) ?><?= strlen($r['description']??'')>60?'…':'' ?></td>
            <td><span class="badge-status <?= $r['is_public']?'badge-aktif':'badge-nonaktif' ?>"><?= $r['is_public']?'Ya':'Tidak' ?></span></td>
            <td>
              <button class="btn-sm-action btn-edit me-1" onclick="editSup(<?= htmlspecialchars(json_encode($r)) ?>)"><i class="bi bi-pencil"></i></button>
              <form method="POST" class="d-inline" onsubmit="return confirm('Hapus supplier ini?')">
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

<!-- Modal -->
<div class="modal fade" id="modalSup" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title" id="modalSupTitle">Tambah Supplier</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <form method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="action" id="supAction" value="add">
        <input type="hidden" name="id" id="supId" value="">
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-8"><label class="form-label fw-semibold">Nama <span class="text-danger">*</span></label><input type="text" name="name" id="supName" class="form-control" required></div>
            <div class="col-md-4"><label class="form-label fw-semibold">Tipe</label>
              <select name="type" id="supType" class="form-select">
                <option>NGO</option><option>Pemerintah</option><option>Perusahaan</option><option>Yayasan</option><option>Lembaga</option><option>Individu</option>
              </select>
            </div>
            <div class="col-md-6"><label class="form-label fw-semibold">Kontak</label><input type="text" name="contact" id="supContact" class="form-control"></div>
            <div class="col-md-6"><label class="form-label fw-semibold">Email</label><input type="email" name="email" id="supEmail" class="form-control"></div>
            <div class="col-12"><label class="form-label fw-semibold">Alamat</label><input type="text" name="address" id="supAddress" class="form-control"></div>
            <div class="col-12"><label class="form-label fw-semibold">Deskripsi</label><textarea name="description" id="supDesc" class="form-control" rows="2"></textarea></div>
            <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_public" id="supPublic" value="1" checked><label class="form-check-label" for="supPublic">Tampilkan nama di halaman publik</label></div></div>
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
function resetSup(){
  document.getElementById('modalSupTitle').textContent='Tambah Supplier';
  document.getElementById('supAction').value='add';
  ['supId','supName','supContact','supEmail','supAddress','supDesc'].forEach(id=>document.getElementById(id).value='');
  document.getElementById('supType').value='NGO';
  document.getElementById('supPublic').checked=true;
}
function editSup(d){
  document.getElementById('modalSupTitle').textContent='Edit Supplier';
  document.getElementById('supAction').value='edit';
  document.getElementById('supId').value=d.id;
  document.getElementById('supName').value=d.name;
  document.getElementById('supType').value=d.type;
  document.getElementById('supContact').value=d.contact||'';
  document.getElementById('supEmail').value=d.email||'';
  document.getElementById('supAddress').value=d.address||'';
  document.getElementById('supDesc').value=d.description||'';
  document.getElementById('supPublic').checked=d.is_public==1;
  new bootstrap.Modal(document.getElementById('modalSup')).show();
}
</script>
