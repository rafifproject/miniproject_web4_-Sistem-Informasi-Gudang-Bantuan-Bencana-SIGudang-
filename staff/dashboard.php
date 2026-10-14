<?php
/**
 * Halaman 17: Dashboard Staff
 * Fokus operasional: Cek stok, quick entry barang masuk & keluar, ringkasan transaksi & stok menipis
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/helpers/auth.php';
require_once dirname(__DIR__) . '/helpers/functions.php';

startSecureSession();
requireLogin();

// Boleh diakses oleh staff dan admin
$u = getCurrentUser();
$db = getDB();

// Pencarian cepat ketersediaan barang
$searchKeyword = sanitizeString($_GET['q'] ?? '', 100);
$searchCategory = sanitizeInt($_GET['cat'] ?? 0);

// 1. Agregat statistik operasional
$stats = $db->query("
    SELECT
        (SELECT COUNT(*) FROM products) AS total_jenis,
        (SELECT COUNT(*) FROM products WHERE stock <= minimum_stock) AS stok_kritis,
        (SELECT COALESCE(SUM(quantity), 0) FROM stock_movements WHERE type = 'IN') AS total_masuk,
        (SELECT COALESCE(SUM(quantity), 0) FROM stock_movements WHERE type = 'OUT') AS total_keluar,
        (SELECT COALESCE(SUM(quantity), 0) FROM stock_movements WHERE type = 'IN' AND MONTH(date) = MONTH(CURRENT_DATE()) AND YEAR(date) = YEAR(CURRENT_DATE())) AS masuk_bulan_ini,
        (SELECT COALESCE(SUM(quantity), 0) FROM stock_movements WHERE type = 'OUT' AND MONTH(date) = MONTH(CURRENT_DATE()) AND YEAR(date) = YEAR(CURRENT_DATE())) AS keluar_bulan_ini
")->fetch();

// 2. Transaksi mutasi terbaru
$recentMovements = $db->query("
    SELECT sm.id, sm.date, sm.type, sm.quantity, sm.reference, sm.notes,
           p.name AS product_name, p.unit, p.sku,
           u.full_name AS operator_name,
           s.name AS supplier_name,
           dt.name AS target_name
    FROM stock_movements sm
    JOIN products p ON sm.product_id = p.id
    LEFT JOIN users u ON sm.user_id = u.id
    LEFT JOIN suppliers s ON sm.supplier_id = s.id
    LEFT JOIN distribution_targets dt ON sm.distribution_target_id = dt.id
    ORDER BY sm.date DESC, sm.id DESC
    LIMIT 6
")->fetchAll();

// 3. Daftar stok menipis / kritis
$lowStockItems = $db->query("
    SELECT p.id, p.sku, p.name, p.stock, p.minimum_stock, p.unit, c.name AS category_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE p.stock <= p.minimum_stock
    ORDER BY p.stock ASC
    LIMIT 5
")->fetchAll();

// 4. Kueri pencarian ketersediaan barang (jika ada filter atau default 8 barang teratas)
$searchSql = "
    SELECT p.id, p.sku, p.name, p.stock, p.minimum_stock, p.unit, c.name AS category_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE 1=1
";
$searchParams = [];

if ($searchKeyword !== '') {
    $searchSql .= " AND (p.name LIKE ? OR p.sku LIKE ?)";
    $searchParams[] = "%{$searchKeyword}%";
    $searchParams[] = "%{$searchKeyword}%";
}
if ($searchCategory > 0) {
    $searchSql .= " AND p.category_id = ?";
    $searchParams[] = $searchCategory;
}

$searchSql .= " ORDER BY p.name ASC LIMIT 8";
$stmtSearch = $db->prepare($searchSql);
$stmtSearch->execute($searchParams);
$quickProducts = $stmtSearch->fetchAll();

// Kategori untuk dropdown filter pencarian
$categories = $db->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();

$pageTitle = 'Dashboard Staff Operasional';
$activeMenu = 'dashboard';
include dirname(__DIR__) . '/views/partials/header_admin.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
  <div>
    <h2 class="h3 fw-bold mb-1">Dashboard Operasional Gudang</h2>
    <p class="text-muted mb-0">Selamat bertugas, <strong><?= e($u['full_name'] ?: $u['username']) ?></strong>. Monitor stok dan catat arus logistik dengan teliti.</p>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= BASE_URL ?>/admin/barang_masuk.php" class="btn btn-success fw-semibold shadow-sm">
      <i class="bi bi-box-arrow-in-down me-1"></i> Catat Barang Masuk
    </a>
    <a href="<?= BASE_URL ?>/admin/barang_keluar.php" class="btn btn-primary fw-semibold shadow-sm">
      <i class="bi bi-box-arrow-up me-1"></i> Catat Barang Keluar
    </a>
  </div>
</div>

<!-- ═══ 4 KARTU STATISTIK OPERASIONAL ═══ -->
<div class="row g-3 mb-4">
  <div class="col-sm-6 col-xl-3">
    <div class="stat-card">
      <div class="stat-icon blue">
        <i class="bi bi-box-seam"></i>
      </div>
      <div class="stat-body">
        <div class="stat-value"><?= formatNumber($stats['total_jenis']) ?></div>
        <div class="stat-label">Jenis Logistik Terdaftar</div>
      </div>
    </div>
  </div>

  <div class="col-sm-6 col-xl-3">
    <div class="stat-card">
      <div class="stat-icon <?= $stats['stok_kritis'] > 0 ? 'red' : 'green' ?>">
        <i class="bi bi-exclamation-triangle"></i>
      </div>
      <div class="stat-body">
        <div class="stat-value text-<?= $stats['stok_kritis'] > 0 ? 'danger' : 'success' ?>">
          <?= formatNumber($stats['stok_kritis']) ?>
        </div>
        <div class="stat-label">Barang Stok Minimum / Kritis</div>
      </div>
    </div>
  </div>

  <div class="col-sm-6 col-xl-3">
    <div class="stat-card">
      <div class="stat-icon green">
        <i class="bi bi-box-arrow-in-down"></i>
      </div>
      <div class="stat-body">
        <div class="stat-value"><?= formatNumber($stats['total_masuk']) ?></div>
        <div class="stat-label">Total Masuk (Bln: <?= formatNumber($stats['masuk_bulan_ini']) ?>)</div>
      </div>
    </div>
  </div>

  <div class="col-sm-6 col-xl-3">
    <div class="stat-card">
      <div class="stat-icon orange">
        <i class="bi bi-box-arrow-up"></i>
      </div>
      <div class="stat-body">
        <div class="stat-value"><?= formatNumber($stats['total_keluar']) ?></div>
        <div class="stat-label">Total Keluar (Bln: <?= formatNumber($stats['keluar_bulan_ini']) ?>)</div>
      </div>
    </div>
  </div>
</div>

<div class="row g-4">
  <!-- ═══ PENCARIAN CEPAT KETERSEDIAAN STOK ═══ -->
  <div class="col-lg-7">
    <div class="card-panel h-100">
      <div class="card-panel-header d-flex justify-content-between align-items-center">
        <h5 class="card-panel-title mb-0">
          <i class="bi bi-search me-2 text-primary"></i>Pencarian Ketersediaan Barang
        </h5>
        <span class="badge bg-light text-dark border"><?= count($quickProducts) ?> Hasil Ditampilkan</span>
      </div>
      <div class="card-panel-body">
        <form method="GET" action="" class="row g-2 mb-3">
          <div class="col-sm-6">
            <input type="text" name="q" class="form-control form-control-sm"
                   placeholder="Ketik SKU atau nama barang..."
                   value="<?= e($searchKeyword) ?>">
          </div>
          <div class="col-sm-4">
            <select name="cat" class="form-select form-select-sm">
              <option value="">Semua Kategori</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= $searchCategory == $cat['id'] ? 'selected' : '' ?>>
                  <?= e($cat['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-sm-2 d-flex gap-1">
            <button type="submit" class="btn btn-sm btn-primary w-100" title="Cari">
              <i class="bi bi-search"></i>
            </button>
            <?php if ($searchKeyword !== '' || $searchCategory > 0): ?>
              <a href="<?= BASE_URL ?>/staff/dashboard.php" class="btn btn-sm btn-outline-secondary" title="Reset">
                <i class="bi bi-arrow-counterclockwise"></i>
              </a>
            <?php endif; ?>
          </div>
        </form>

        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr class="small text-uppercase">
                <th>SKU</th>
                <th>Nama Logistik</th>
                <th>Kategori</th>
                <th class="text-end">Stok Fisik</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($quickProducts)): ?>
                <tr>
                  <td colspan="5" class="text-center py-4 text-muted">
                    <i class="bi bi-inbox fs-3 d-block mb-1"></i>
                    Barang tidak ditemukan sesuai kriteria pencarian.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($quickProducts as $p): ?>
                  <tr>
                    <td><code class="small fw-semibold"><?= e($p['sku']) ?></code></td>
                    <td class="fw-semibold text-dark"><?= e($p['name']) ?></td>
                    <td><span class="badge bg-light text-secondary border"><?= e($p['category_name']) ?></span></td>
                    <td class="text-end fw-bold">
                      <?= formatNumber($p['stock']) ?> <small class="text-muted fw-normal"><?= e($p['unit']) ?></small>
                    </td>
                    <td>
                      <?php if ($p['stock'] <= 0): ?>
                        <span class="badge bg-danger">Habis</span>
                      <?php elseif ($p['stock'] <= $p['minimum_stock']): ?>
                        <span class="badge bg-warning text-dark">Kritis (&le; <?= $p['minimum_stock'] ?>)</span>
                      <?php else: ?>
                        <span class="badge bg-success">Tersedia</span>
                      <?php endif; ?>
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

  <!-- ═══ PERINGATAN STOK MINIMUM ═══ -->
  <div class="col-lg-5">
    <div class="card-panel h-100">
      <div class="card-panel-header d-flex justify-content-between align-items-center">
        <h5 class="card-panel-title mb-0 text-danger">
          <i class="bi bi-exclamation-octagon me-2"></i>Peringatan Stok Menipis
        </h5>
        <a href="<?= BASE_URL ?>/admin/barang_masuk.php" class="btn btn-sm btn-outline-danger">
          <i class="bi bi-plus-lg me-1"></i>Restock
        </a>
      </div>
      <div class="card-panel-body">
        <?php if (empty($lowStockItems)): ?>
          <div class="text-center py-4 text-success">
            <i class="bi bi-check-circle-fill fs-1 d-block mb-2"></i>
            <h6 class="fw-bold">Seluruh Stok Aman</h6>
            <p class="text-muted small mb-0">Tidak ada barang logistik di bawah batas stok minimum.</p>
          </div>
        <?php else: ?>
          <div class="list-group list-group-flush">
            <?php foreach ($lowStockItems as $item): ?>
              <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                <div>
                  <div class="fw-semibold text-dark"><?= e($item['name']) ?></div>
                  <div class="text-muted small">
                    SKU: <code><?= e($item['sku']) ?></code> &bull; Kategori: <?= e($item['category_name']) ?>
                  </div>
                </div>
                <div class="text-end">
                  <div class="fw-bold text-danger fs-6">
                    <?= formatNumber($item['stock']) ?> <small><?= e($item['unit']) ?></small>
                  </div>
                  <div class="text-muted" style="font-size: 0.75rem;">
                    Batas min: <?= formatNumber($item['minimum_stock']) ?>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- ═══ TABEL TRANSAKSI MUTASI TERBARU OPERASIONAL ═══ -->
<div class="card-panel mt-4">
  <div class="card-panel-header d-flex justify-content-between align-items-center">
    <h5 class="card-panel-title mb-0">
      <i class="bi bi-clock-history me-2 text-primary"></i>Aktivitas Mutasi Gudang Terbaru
    </h5>
    <a href="<?= BASE_URL ?>/admin/mutasi_stok.php" class="btn btn-sm btn-outline-primary">
      Lihat Semua Riwayat <i class="bi bi-arrow-right ms-1"></i>
    </a>
  </div>
  <div class="card-panel-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr class="small text-uppercase">
            <th class="ps-3">Waktu</th>
            <th>Tipe</th>
            <th>Nama Barang</th>
            <th class="text-end">Jumlah</th>
            <th>Pihak Terkait (Supplier / Tujuan)</th>
            <th>Operator</th>
            <th class="pe-3">Referensi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($recentMovements)): ?>
            <tr>
              <td colspan="7" class="text-center py-4 text-muted">Belum ada transaksi logistik yang tercatat.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($recentMovements as $m): ?>
              <tr>
                <td class="ps-3 text-nowrap small text-muted">
                  <?= formatDate($m['date'], 'd M Y') ?>
                </td>
                <td>
                  <?php if ($m['type'] === 'IN'): ?>
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                      <i class="bi bi-arrow-down-left me-1"></i>MASUK
                    </span>
                  <?php else: ?>
                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1">
                      <i class="bi bi-arrow-up-right me-1"></i>KELUAR
                    </span>
                  <?php endif; ?>
                </td>
                <td class="fw-semibold">
                  <?= e($m['product_name']) ?>
                  <div class="text-muted small"><code><?= e($m['sku']) ?></code></div>
                </td>
                <td class="text-end fw-bold <?= $m['type'] === 'IN' ? 'text-success' : 'text-danger' ?>">
                  <?= $m['type'] === 'IN' ? '+' : '-' ?><?= formatNumber($m['quantity']) ?>
                  <small class="text-muted fw-normal"><?= e($m['unit']) ?></small>
                </td>
                <td>
                  <?php if ($m['type'] === 'IN'): ?>
                    <span class="small text-muted"><i class="bi bi-building me-1"></i><?= e($m['supplier_name'] ?: 'Penerimaan Donasi') ?></span>
                  <?php else: ?>
                    <span class="small text-dark fw-semibold"><i class="bi bi-geo-alt me-1 text-danger"></i><?= e($m['target_name'] ?: 'Distribusi Bencana') ?></span>
                  <?php endif; ?>
                </td>
                <td class="small">
                  <i class="bi bi-person me-1 text-secondary"></i><?= e($m['operator_name'] ?: 'Sistem') ?>
                </td>
                <td class="pe-3 small text-muted">
                  <?= e($m['reference'] ?: '-') ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include dirname(__DIR__) . '/views/partials/footer_admin.php'; ?>
