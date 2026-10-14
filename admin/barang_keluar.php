<?php
/**
 * Halaman 15: Transaksi Barang Keluar (Admin & Staff)
 * - SELECT ... FOR UPDATE (locking) untuk cegah race condition stok
 * - Validasi stok cukup sebelum commit
 * - Rollback otomatis jika stok tidak cukup
 * - PRG pattern untuk cegah double submit
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

$autoRef = getNextReference($db, 'OUT');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $errors[] = 'Token keamanan tidak valid.';
    } else {
        $prodId   = sanitizeInt($_POST['product_id']             ?? 0);
        $distId   = sanitizeInt($_POST['distribution_target_id'] ?? 0);
        $qty      = sanitizeInt($_POST['quantity']               ?? 0);
        $date     = sanitizeString($_POST['date']                ?? '', 20);
        $ref      = sanitizeString($_POST['reference']           ?? '', 100);
        if (empty($ref)) {
            $ref = $autoRef;
        }
        $notes    = sanitizeString($_POST['notes']               ?? '', 500);
        $isPublic = isset($_POST['is_public']) ? 1 : 0;

        if (!$prodId) $errors[] = 'Produk harus dipilih.';
        if (!$distId) $errors[] = 'Tujuan distribusi harus dipilih.';
        if ($qty <= 0) $errors[] = 'Jumlah harus lebih dari 0.';
        if (!$date)   $errors[] = 'Tanggal wajib diisi.';
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $errors[] = 'Format tanggal tidak valid.';

        if (empty($errors)) {
            try {
                $db->beginTransaction();

                // Lock baris produk agar tidak ada transaksi paralel
                $stProd = $db->prepare("SELECT id, name, stock, unit FROM products WHERE id = ? FOR UPDATE");
                $stProd->execute([$prodId]);
                $prod = $stProd->fetch();

                if (!$prod) {
                    $db->rollBack();
                    $errors[] = 'Produk tidak ditemukan.';
                } elseif ($prod['stock'] < $qty) {
                    $db->rollBack();
                    $errors[] = "Stok tidak mencukupi! Stok tersedia: {$prod['stock']} {$prod['unit']}, diminta: {$qty} {$prod['unit']}.";
                } else {
                    // Kurangi stok
                    $db->prepare("UPDATE products SET stock = stock - ? WHERE id = ?")->execute([$qty, $prodId]);

                    // Catat histori
                    $db->prepare("
                        INSERT INTO stock_movements
                          (product_id, user_id, distribution_target_id, type, quantity, date, reference, notes, is_public)
                        VALUES (?, ?, ?, 'OUT', ?, ?, ?, ?, ?)
                    ")->execute([$prodId, $u['id'], $distId ?: null, $qty, $date, $ref, $notes, $isPublic]);

                    $db->commit();

                    flashSet('msg', "Berhasil! Stok <strong>" . htmlspecialchars($prod['name']) . "</strong> berkurang {$qty} {$prod['unit']}.", 'success');
                    redirect('/admin/barang_keluar.php');
                }
            } catch (\Exception $e) {
                if ($db->inTransaction()) $db->rollBack();
                $errors[] = 'Transaksi gagal. Silakan coba kembali.';
                error_log('Barang keluar error: ' . $e->getMessage());
            }
        }
    }
}

$products = $db->query("SELECT id,sku,name,stock,unit,minimum_stock FROM products ORDER BY name")->fetchAll();
$targets  = $db->query("SELECT id,name,location FROM distribution_targets ORDER BY name")->fetchAll();

$recent = $db->query("
    SELECT sm.date, sm.quantity, sm.reference, sm.notes,
           p.name AS produk, p.unit, dt.name AS tujuan, u.username
    FROM stock_movements sm
    JOIN products p ON sm.product_id=p.id
    LEFT JOIN distribution_targets dt ON sm.distribution_target_id=dt.id
    JOIN users u ON sm.user_id=u.id
    WHERE sm.type='OUT'
    ORDER BY sm.created_at DESC LIMIT 10
")->fetchAll();

$pageTitle = 'Barang Keluar';
$activeMenu = 'keluar';
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
      <div class="card-panel-header"><i class="bi bi-box-arrow-up me-2 text-danger"></i>Input Barang Keluar</div>
      <div class="card-panel-body">
        <form method="POST" action="" id="formKeluar">
          <?= csrfField() ?>

          <div class="mb-3">
            <label class="form-label fw-semibold">Produk <span class="text-danger">*</span></label>
            <select name="product_id" class="form-select" required id="selProdukK" onchange="cekStok(this)">
              <option value="">— Pilih Produk —</option>
              <?php foreach ($products as $p): ?>
                <option value="<?= $p['id'] ?>"
                  data-stok="<?= $p['stock'] ?>"
                  data-unit="<?= e($p['unit']) ?>"
                  data-min="<?= $p['minimum_stock'] ?>"
                  <?= (isset($_POST['product_id']) && $_POST['product_id']==$p['id'])?'selected':'' ?>>
                  <?= e($p['sku']) ?> — <?= e($p['name']) ?> (stok: <?= $p['stock'] ?> <?= e($p['unit']) ?>)
                </option>
              <?php endforeach; ?>
            </select>
            <div id="stokInfoK" class="form-text mt-1"></div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Tujuan Distribusi <span class="text-danger">*</span></label>
            <select name="distribution_target_id" class="form-select" required>
              <option value="">— Pilih Tujuan —</option>
              <?php foreach ($targets as $t): ?>
                <option value="<?= $t['id'] ?>" <?= (isset($_POST['distribution_target_id']) && $_POST['distribution_target_id']==$t['id'])?'selected':'' ?>>
                  <?= e($t['name']) ?><?= $t['location'] ? ' — '.e($t['location']) : '' ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="row g-3">
            <div class="col-6">
              <label class="form-label fw-semibold">Jumlah <span class="text-danger">*</span></label>
              <input type="number" name="quantity" id="inputQty" class="form-control"
                value="<?= isset($_POST['quantity'])?sanitizeInt($_POST['quantity']):'' ?>"
                min="1" required>
            </div>
            <div class="col-6">
              <label class="form-label fw-semibold">Tanggal <span class="text-danger">*</span></label>
              <input type="date" name="date" class="form-control"
                value="<?= isset($_POST['date'])?e($_POST['date']):date('Y-m-d') ?>" required>
            </div>
          </div>

          <div class="mb-3 mt-3">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <label class="form-label fw-semibold mb-0">No. Referensi <span class="badge bg-danger-subtle text-danger border border-danger-subtle ms-1"><i class="bi bi-magic me-1"></i>Otomatis</span></label>
              <small class="text-muted">Terisi urutan transaksi berikutnya</small>
            </div>
            <input type="text" name="reference" class="form-control fw-semibold"
              placeholder="cth: <?= e($autoRef) ?>"
              value="<?= isset($_POST['reference']) ? e($_POST['reference']) : e($autoRef) ?>" required>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Catatan</label>
            <textarea name="notes" class="form-control" rows="2"><?= isset($_POST['notes'])?e($_POST['notes']):'' ?></textarea>
          </div>

          <div class="mb-3">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="is_public" id="isPublicK" value="1" checked>
              <label class="form-check-label" for="isPublicK">Tampilkan di halaman publik</label>
            </div>
          </div>

          <button type="submit" class="btn btn-danger w-100 py-2 fw-bold" id="btnSubmitK">
            <i class="bi bi-check-circle me-2"></i>Simpan Transaksi Keluar
          </button>
        </form>
      </div>
    </div>
  </div>

  <!-- Riwayat terbaru -->
  <div class="col-lg-7">
    <div class="card-panel">
      <div class="card-panel-header">
        <span>Riwayat Barang Keluar Terbaru</span>
        <a href="<?= BASE_URL ?>/admin/mutasi_stok.php?type=OUT" class="btn-sm-action btn-view">Lihat Semua</a>
      </div>
      <div class="table-responsive">
        <table class="table-modern w-100">
          <thead><tr><th>Tanggal</th><th>Produk</th><th>Jumlah</th><th>Tujuan</th><th>Oleh</th></tr></thead>
          <tbody>
            <?php if (empty($recent)): ?>
              <tr><td colspan="5" class="empty-state"><span class="empty-icon">📤</span>Belum ada transaksi</td></tr>
            <?php else: ?>
              <?php foreach ($recent as $r): ?>
              <tr>
                <td class="small"><?= e(formatDate($r['date'],'d M Y')) ?></td>
                <td><?= e($r['produk']) ?></td>
                <td class="text-danger fw-bold">-<?= formatNumber($r['quantity']) ?> <?= e($r['unit']) ?></td>
                <td class="small text-muted"><?= e($r['tujuan'] ?: '-') ?></td>
                <td class="small text-muted"><?= e($r['username']) ?></td>
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
function cekStok(sel) {
  const opt = sel.selectedOptions[0];
  const info = document.getElementById('stokInfoK');
  const qtyInput = document.getElementById('inputQty');
  if (opt && opt.value) {
    const stok = parseInt(opt.dataset.stok);
    const unit = opt.dataset.unit;
    const min  = parseInt(opt.dataset.min);
    const isKritis = stok <= min && min > 0;
    info.innerHTML = `Stok tersedia: <strong class="${isKritis?'text-danger':'text-success'}">${stok} ${unit}</strong>${isKritis?' ⚠️ (Kritis!)':''}`;
    info.className = 'form-text';
    qtyInput.max = stok;
  } else {
    info.textContent = '';
    qtyInput.removeAttribute('max');
  }
}
// Validasi client-side sebelum submit
document.getElementById('formKeluar').addEventListener('submit', function(e) {
  const sel = document.getElementById('selProdukK');
  const qty = parseInt(document.getElementById('inputQty').value);
  const opt = sel.selectedOptions[0];
  if (opt && opt.value) {
    const stok = parseInt(opt.dataset.stok);
    if (qty > stok) {
      e.preventDefault();
      alert(`Stok tidak mencukupi! Stok tersedia: ${stok}, Anda meminta: ${qty}`);
      return false;
    }
  }
  const btn = document.getElementById('btnSubmitK');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...';
});
</script>
