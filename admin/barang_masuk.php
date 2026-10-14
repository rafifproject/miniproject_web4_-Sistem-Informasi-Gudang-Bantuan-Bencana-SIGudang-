<?php
/**
 * Halaman 14: Transaksi Barang Masuk (Admin & Staff)
 * - Atomic: UPDATE products.stock + INSERT stock_movements dalam 1 transaksi PDO
 * - CSRF protection
 * - PRG pattern (Post-Redirect-Get) untuk cegah double submit
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/helpers/auth.php';
require_once dirname(__DIR__) . '/helpers/functions.php';

startSecureSession();
requireAdminOrStaff();

$db   = getDB();
$u    = getCurrentUser();
$errors = [];

$autoRef = getNextReference($db, 'IN');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $errors[] = 'Token keamanan tidak valid.';
    } else {
        $prodId    = sanitizeInt($_POST['product_id']  ?? 0);
        $supId     = sanitizeInt($_POST['supplier_id'] ?? 0);
        $qty       = sanitizeInt($_POST['quantity']    ?? 0);
        $date      = sanitizeString($_POST['date']     ?? '', 20);
        $ref       = sanitizeString($_POST['reference'] ?? '', 100);
        if (empty($ref)) {
            $ref = $autoRef;
        }
        $notes     = sanitizeString($_POST['notes']    ?? '', 500);
        $isPublic  = isset($_POST['is_public']) ? 1 : 0;

        // Validasi
        if (!$prodId)        $errors[] = 'Produk harus dipilih.';
        if (!$supId)         $errors[] = 'Supplier/Donatur harus dipilih.';
        if ($qty <= 0)       $errors[] = 'Jumlah harus lebih dari 0.';
        if (!$date)          $errors[] = 'Tanggal wajib diisi.';
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $errors[] = 'Format tanggal tidak valid.';

        if (empty($errors)) {
            // Verifikasi produk & supplier ada
            $stProd = $db->prepare("SELECT id,name FROM products WHERE id=?"); $stProd->execute([$prodId]);
            $prod = $stProd->fetch();
            $stSup = $db->prepare("SELECT id,name FROM suppliers WHERE id=?"); $stSup->execute([$supId]);
            $sup = $stSup->fetch();

            if (!$prod) $errors[] = 'Produk tidak ditemukan.';
            if (!$sup)  $errors[] = 'Supplier tidak ditemukan.';

            if (empty($errors)) {
                try {
                    $db->beginTransaction();

                    // Tambah stok produk
                    $db->prepare("UPDATE products SET stock = stock + ? WHERE id=?")->execute([$qty, $prodId]);

                    // Simpan ke stock_movements
                    $db->prepare("
                        INSERT INTO stock_movements
                          (product_id, user_id, supplier_id, type, quantity, date, reference, notes, is_public)
                        VALUES (?,?,?,'IN',?,?,?,?,?)
                    ")->execute([$prodId, $u['id'], $supId, $qty, $date, $ref, $notes, $isPublic]);

                    $db->commit();

                    flashSet('msg', "Berhasil! Stok <strong>" . htmlspecialchars($prod['name']) . "</strong> bertambah {$qty} unit.", 'success');
                    // PRG — redirect untuk cegah double submit
                    redirect('/admin/barang_masuk.php');

                } catch (\Exception $e) {
                    $db->rollBack();
                    $errors[] = 'Transaksi gagal. Silakan coba kembali.';
                    error_log('Barang masuk error: ' . $e->getMessage());
                }
            }
        }
    }
}

// Data untuk form dropdowns
$products  = $db->query("SELECT id,sku,name,stock,unit FROM products ORDER BY name")->fetchAll();
$suppliers = $db->query("SELECT id,name,type FROM suppliers ORDER BY name")->fetchAll();
$targets   = $db->query("SELECT id,name FROM distribution_targets ORDER BY name")->fetchAll();

// Riwayat terbaru (10 baris)
$recent = $db->query("
    SELECT sm.date, sm.quantity, sm.reference, sm.notes,
           p.name AS produk, p.unit, s.name AS supplier, u.username
    FROM stock_movements sm
    JOIN products p ON sm.product_id=p.id
    LEFT JOIN suppliers s ON sm.supplier_id=s.id
    JOIN users u ON sm.user_id=u.id
    WHERE sm.type='IN'
    ORDER BY sm.created_at DESC LIMIT 10
")->fetchAll();

$pageTitle = 'Barang Masuk';
$activeMenu = 'masuk';
include dirname(__DIR__) . '/views/partials/header_admin.php';
?>

<?= flashAlert('msg') ?>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
  <strong>Terdapat kesalahan:</strong>
  <ul class="mb-0 mt-1">
    <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
  </ul>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row g-4">
  <!-- Form -->
  <div class="col-lg-5">
    <div class="card-panel">
      <div class="card-panel-header"><i class="bi bi-box-arrow-in-down me-2 text-success"></i>Input Barang Masuk</div>
      <div class="card-panel-body">
        <form method="POST" action="" id="formMasuk">
          <?= csrfField() ?>

          <div class="mb-3">
            <label class="form-label fw-semibold">Produk <span class="text-danger">*</span></label>
            <select name="product_id" class="form-select" required id="selProduk" onchange="updateStok(this)">
              <option value="">— Pilih Produk —</option>
              <?php foreach ($products as $p): ?>
                <option value="<?= $p['id'] ?>" data-stok="<?= $p['stock'] ?>" data-unit="<?= e($p['unit']) ?>"
                  <?= (isset($_POST['product_id']) && $_POST['product_id']==$p['id'])?'selected':'' ?>>
                  <?= e($p['sku']) ?> — <?= e($p['name']) ?> (stok: <?= $p['stock'] ?> <?= e($p['unit']) ?>)
                </option>
              <?php endforeach; ?>
            </select>
            <div id="stokInfo" class="form-text"></div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Supplier / Donatur <span class="text-danger">*</span></label>
            <select name="supplier_id" class="form-select" required>
              <option value="">— Pilih Supplier —</option>
              <?php foreach ($suppliers as $s): ?>
                <option value="<?= $s['id'] ?>" <?= (isset($_POST['supplier_id']) && $_POST['supplier_id']==$s['id'])?'selected':'' ?>>
                  <?= e($s['name']) ?> (<?= e($s['type']) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="row g-3">
            <div class="col-6">
              <label class="form-label fw-semibold">Jumlah <span class="text-danger">*</span></label>
              <input type="number" name="quantity" class="form-control" value="<?= isset($_POST['quantity'])?sanitizeInt($_POST['quantity']):'' ?>" min="1" required>
            </div>
            <div class="col-6">
              <label class="form-label fw-semibold">Tanggal <span class="text-danger">*</span></label>
              <input type="date" name="date" class="form-control" value="<?= isset($_POST['date'])?e($_POST['date']):date('Y-m-d') ?>" required>
            </div>
          </div>

          <div class="mb-3 mt-3">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <label class="form-label fw-semibold mb-0">No. Referensi <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-1"><i class="bi bi-magic me-1"></i>Otomatis</span></label>
              <small class="text-muted">Terisi urutan transaksi berikutnya</small>
            </div>
            <input type="text" name="reference" class="form-control fw-semibold"
                   value="<?= isset($_POST['reference']) ? e($_POST['reference']) : e($autoRef) ?>"
                   placeholder="cth: <?= e($autoRef) ?>" required>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Catatan</label>
            <textarea name="notes" class="form-control" rows="2" placeholder="Keterangan tambahan..."><?= isset($_POST['notes'])?e($_POST['notes']):'' ?></textarea>
          </div>

          <div class="mb-3">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="is_public" id="isPublicMasuk" value="1" checked>
              <label class="form-check-label" for="isPublicMasuk">Tampilkan di halaman publik</label>
            </div>
          </div>

          <button type="submit" class="btn-primary-custom w-100 py-2" id="btnSubmit">
            <i class="bi bi-check-circle me-2"></i>Simpan Transaksi Masuk
          </button>
        </form>
      </div>
    </div>
  </div>

  <!-- Riwayat terbaru -->
  <div class="col-lg-7">
    <div class="card-panel">
      <div class="card-panel-header">
        <span>Riwayat Barang Masuk Terbaru</span>
        <a href="<?= BASE_URL ?>/admin/mutasi_stok.php?type=IN" class="btn-sm-action btn-view">Lihat Semua</a>
      </div>
      <div class="table-responsive">
        <table class="table-modern w-100">
          <thead><tr><th>Tanggal</th><th>Produk</th><th>Jumlah</th><th>Supplier</th><th>Oleh</th><th>Ref</th></tr></thead>
          <tbody>
            <?php if (empty($recent)): ?>
              <tr><td colspan="6" class="empty-state"><span class="empty-icon">📥</span>Belum ada transaksi</td></tr>
            <?php else: ?>
              <?php foreach ($recent as $r): ?>
              <tr>
                <td class="small"><?= e(formatDate($r['date'],'d M Y')) ?></td>
                <td><?= e($r['produk']) ?></td>
                <td class="text-success fw-bold">+<?= formatNumber($r['quantity']) ?> <?= e($r['unit']) ?></td>
                <td class="small text-muted"><?= e($r['supplier'] ?: '-') ?></td>
                <td class="small text-muted"><?= e($r['username']) ?></td>
                <td class="small text-muted"><?= e($r['reference'] ?: '-') ?></td>
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
<script>
function updateStok(sel) {
  const opt = sel.selectedOptions[0];
  const info = document.getElementById('stokInfo');
  if (opt && opt.value) {
    info.textContent = `Stok saat ini: ${opt.dataset.stok} ${opt.dataset.unit}`;
    info.className = 'form-text text-info fw-semibold';
  } else { info.textContent = ''; }
}
// Anti double-submit
document.getElementById('formMasuk').addEventListener('submit', function() {
  const btn = document.getElementById('btnSubmit');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...';
});
</script>
