<?php
/**
 * Halaman 19: Manajemen Pengguna (Khusus Admin)
 * CRUD Pengguna, Ubah Role, Reset Password, Nonaktifkan/Hapus Akun
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/helpers/auth.php';
require_once dirname(__DIR__) . '/helpers/functions.php';

startSecureSession();
requireAdmin();

$db = getDB();
$currentUser = getCurrentUser();
$errors = [];
$success = '';

// Proses Form POST (Tambah, Edit, Reset Password, Toggle Status, Hapus)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
        $errors[] = 'Token keamanan tidak valid. Silakan coba lagi.';
    } else {
        $action = $_POST['action'] ?? '';

        // 1. TAMBAH PENGGUNA
        if ($action === 'create') {
            $username = sanitizeString($_POST['username'] ?? '', 80);
            $fullName = sanitizeString($_POST['full_name'] ?? '', 150);
            $email    = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
            $role     = in_array($_POST['role'] ?? '', ['admin', 'staff']) ? $_POST['role'] : 'staff';
            $password = $_POST['password'] ?? '';
            $isActive = isset($_POST['is_active']) ? 1 : 0;

            if (empty($username) || empty($fullName)) {
                $errors[] = 'Username dan Nama Lengkap wajib diisi.';
            } elseif (strlen($password) < 8) {
                $errors[] = 'Password minimal 8 karakter.';
            } else {
                // Cek duplikat username
                $stmtCheck = $db->prepare("SELECT id FROM users WHERE username = ?");
                $stmtCheck->execute([$username]);
                if ($stmtCheck->fetch()) {
                    $errors[] = "Username '{$username}' sudah digunakan.";
                } else {
                    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                    $stmtInsert = $db->prepare("
                        INSERT INTO users (username, password_hash, role, full_name, email, is_active, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, NOW())
                    ");
                    $stmtInsert->execute([$username, $hash, $role, $fullName, $email ?: null, $isActive]);
                    $newUserId = (int)$db->lastInsertId();

                    // Sinkronisasi relasi One-to-One: user_profiles
                    $db->prepare("INSERT INTO user_profiles (user_id, full_name, created_at) VALUES (?, ?, NOW())")
                       ->execute([$newUserId, $fullName]);

                    flashSet('user_msg', "Pengguna baru '{$username}' berhasil ditambahkan.", 'success');
                    redirect('/admin/pengguna.php');
                }
            }
        }

        // 2. EDIT PENGGUNA
        elseif ($action === 'update') {
            $userId   = sanitizeInt($_POST['user_id'] ?? 0);
            $fullName = sanitizeString($_POST['full_name'] ?? '', 150);
            $email    = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
            $role     = in_array($_POST['role'] ?? '', ['admin', 'staff']) ? $_POST['role'] : 'staff';
            $isActive = isset($_POST['is_active']) ? 1 : 0;

            if (empty($fullName)) {
                $errors[] = 'Nama Lengkap wajib diisi.';
            } else {
                // Jangan izinkan admin aktif menonaktifkan dirinya sendiri atau mencabut hak adminnya
                if ($userId === (int)$currentUser['id']) {
                    $role = 'admin';
                    $isActive = 1;
                }

                $stmtUpdate = $db->prepare("
                    UPDATE users SET full_name = ?, email = ?, role = ?, is_active = ?
                    WHERE id = ?
                ");
                $stmtUpdate->execute([$fullName, $email ?: null, $role, $isActive, $userId]);

                // Sinkronisasi relasi One-to-One: user_profiles
                $db->prepare("INSERT INTO user_profiles (user_id, full_name) VALUES (?, ?) ON DUPLICATE KEY UPDATE full_name=VALUES(full_name)")
                   ->execute([$userId, $fullName]);

                flashSet('user_msg', 'Data pengguna berhasil diperbarui.', 'success');
                redirect('/admin/pengguna.php');
            }
        }

        // 3. RESET PASSWORD PENGGUNA
        elseif ($action === 'reset_password') {
            $userId      = sanitizeInt($_POST['user_id'] ?? 0);
            $newPassword = $_POST['new_password'] ?? '';

            if (strlen($newPassword) < 8) {
                $errors[] = 'Password baru minimal 8 karakter.';
            } else {
                $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
                $stmtReset = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                $stmtReset->execute([$hash, $userId]);
                flashSet('user_msg', 'Password pengguna berhasil direset.', 'success');
                redirect('/admin/pengguna.php');
            }
        }

        // 4. TOGGLE STATUS AKTIF / NONAKTIF
        elseif ($action === 'toggle_status') {
            $userId = sanitizeInt($_POST['user_id'] ?? 0);
            if ($userId === (int)$currentUser['id']) {
                $errors[] = 'Anda tidak dapat menonaktifkan akun Anda sendiri.';
            } else {
                $stmtToggle = $db->prepare("UPDATE users SET is_active = IF(is_active = 1, 0, 1) WHERE id = ?");
                $stmtToggle->execute([$userId]);
                flashSet('user_msg', 'Status akun pengguna berhasil diperbarui.', 'success');
                redirect('/admin/pengguna.php');
            }
        }

        // 5. HAPUS PENGGUNA (DENGAN CEK HISTORI TRANSAKSI)
        elseif ($action === 'delete') {
            $userId = sanitizeInt($_POST['user_id'] ?? 0);
            if ($userId === (int)$currentUser['id']) {
                $errors[] = 'Anda tidak dapat menghapus akun Anda sendiri.';
            } else {
                // Cek histori transaksi di stock_movements
                $stmtCheckMovements = $db->prepare("SELECT COUNT(*) FROM stock_movements WHERE user_id = ?");
                $stmtCheckMovements->execute([$userId]);
                $movementCount = (int)$stmtCheckMovements->fetchColumn();

                if ($movementCount > 0) {
                    $errors[] = "Pengguna tidak dapat dihapus karena memiliki riwayat {$movementCount} transaksi gudang. Silakan nonaktifkan akun sebagai alternatif.";
                } else {
                    $stmtDelete = $db->prepare("DELETE FROM users WHERE id = ?");
                    $stmtDelete->execute([$userId]);
                    flashSet('user_msg', 'Pengguna berhasil dihapus secara permanen.', 'success');
                    redirect('/admin/pengguna.php');
                }
            }
        }
    }
}

// Filter dan Query Data Pengguna
$search = sanitizeString($_GET['q'] ?? '', 100);
$roleFilter = sanitizeString($_GET['role'] ?? '', 10);

$querySql = "
    SELECT u.id, u.username, u.full_name, u.email, u.role, u.is_active, u.created_at,
           (SELECT COUNT(*) FROM stock_movements sm WHERE sm.user_id = u.id) AS movement_count
    FROM users u
    WHERE 1=1
";
$params = [];

if ($search !== '') {
    $querySql .= " AND (u.username LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}
if (in_array($roleFilter, ['admin', 'staff'])) {
    $querySql .= " AND u.role = ?";
    $params[] = $roleFilter;
}

$querySql .= " ORDER BY u.role ASC, u.id ASC";
$stmtUsers = $db->prepare($querySql);
$stmtUsers->execute($params);
$usersList = $stmtUsers->fetchAll();

$flash = flashGet('user_msg');

$pageTitle = 'Manajemen Pengguna';
$activeMenu = 'pengguna';
include dirname(__DIR__) . '/views/partials/header_admin.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
  <div>
    <h2 class="h3 fw-bold mb-1">Manajemen Pengguna Sistem</h2>
    <p class="text-muted mb-0">Kelola akun administrator dan staff operasional gudang bantuan bencana.</p>
  </div>
  <button type="button" class="btn btn-primary fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAddUser">
    <i class="bi bi-person-plus-fill me-1"></i> Tambah Pengguna Baru
  </button>
</div>

<?php if ($flash): ?>
  <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show" role="alert">
    <i class="bi bi-check-circle me-2"></i><?= e($flash['message']) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-2"></i><strong>Terjadi Kesalahan:</strong>
    <ul class="mb-0 mt-1">
      <?php foreach ($errors as $err): ?>
        <li><?= e($err) ?></li>
      <?php endforeach; ?>
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<div class="card-panel">
  <div class="card-panel-header d-flex flex-wrap justify-content-between align-items-center gap-3">
    <h5 class="card-panel-title mb-0">
      <i class="bi bi-people me-2 text-primary"></i>Daftar Pengguna (<?= count($usersList) ?> Pengguna)
    </h5>

    <!-- Search & Filter Bar -->
    <form method="GET" action="" class="d-flex flex-wrap gap-2">
      <div class="input-group input-group-sm" style="width: 220px;">
        <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
        <input type="text" name="q" class="form-control" placeholder="Cari nama/username..." value="<?= e($search) ?>">
      </div>
      <select name="role" class="form-select form-select-sm" style="width: 140px;">
        <option value="">Semua Role</option>
        <option value="admin" <?= $roleFilter === 'admin' ? 'selected' : '' ?>>Admin</option>
        <option value="staff" <?= $roleFilter === 'staff' ? 'selected' : '' ?>>Staff</option>
      </select>
      <button type="submit" class="btn btn-sm btn-outline-primary">Filter</button>
      <?php if ($search !== '' || $roleFilter !== ''): ?>
        <a href="<?= BASE_URL ?>/admin/pengguna.php" class="btn btn-sm btn-outline-secondary">Reset</a>
      <?php endif; ?>
    </form>
  </div>

  <div class="card-panel-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr class="small text-uppercase">
            <th class="ps-3">ID</th>
            <th>Pengguna</th>
            <th>Role</th>
            <th>Email</th>
            <th>Status Akun</th>
            <th>Transaksi</th>
            <th>Tanggal Registrasi</th>
            <th class="text-end pe-3">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($usersList)): ?>
            <tr>
              <td colspan="8" class="text-center py-4 text-muted">
                <i class="bi bi-person-x fs-2 d-block mb-1"></i>
                Tidak ada pengguna yang cocok dengan kriteria pencarian.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($usersList as $usr): ?>
              <tr>
                <td class="ps-3 text-muted small">#<?= $usr['id'] ?></td>
                <td>
                  <div class="d-flex align-items-center gap-2">
                    <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold"
                         style="width: 36px; height: 36px; font-size: 0.85rem; background: <?= $usr['role'] === 'admin' ? '#0d6efd' : '#059669' ?>;">
                      <?= strtoupper(substr($usr['full_name'] ?: $usr['username'], 0, 1)) ?>
                    </div>
                    <div>
                      <div class="fw-semibold text-dark"><?= e($usr['full_name']) ?></div>
                      <div class="text-muted small">@<?= e($usr['username']) ?></div>
                    </div>
                  </div>
                </td>
                <td>
                  <?php if ($usr['role'] === 'admin'): ?>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                      <i class="bi bi-shield-check me-1"></i>Admin
                    </span>
                  <?php else: ?>
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                      <i class="bi bi-person-badge me-1"></i>Staff
                    </span>
                  <?php endif; ?>
                </td>
                <td class="small text-muted">
                  <?= e($usr['email'] ?: '-') ?>
                </td>
                <td>
                  <?php if ($usr['is_active']): ?>
                    <span class="badge bg-success px-2 py-1"><i class="bi bi-check-circle me-1"></i>Aktif</span>
                  <?php else: ?>
                    <span class="badge bg-secondary px-2 py-1"><i class="bi bi-dash-circle me-1"></i>Nonaktif</span>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="badge bg-light text-dark border">
                    <?= formatNumber($usr['movement_count']) ?> mutasi
                  </span>
                </td>
                <td class="small text-muted">
                  <?= formatDate($usr['created_at'], 'd M Y') ?>
                </td>
                <td class="text-end pe-3">
                  <div class="btn-group btn-group-sm">
                    <!-- Edit Button -->
                    <button type="button" class="btn btn-outline-secondary"
                            data-bs-toggle="modal" data-bs-target="#modalEditUser<?= $usr['id'] ?>"
                            title="Edit Data">
                      <i class="bi bi-pencil"></i>
                    </button>

                    <!-- Reset Password Button -->
                    <button type="button" class="btn btn-outline-warning text-dark"
                            data-bs-toggle="modal" data-bs-target="#modalResetPass<?= $usr['id'] ?>"
                            title="Reset Password">
                      <i class="bi bi-key"></i>
                    </button>

                    <!-- Toggle Status Button -->
                    <?php if ($usr['id'] !== (int)$currentUser['id']): ?>
                      <form method="POST" action="" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin mengubah status aktif pengguna ini?')">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="toggle_status">
                        <input type="hidden" name="user_id" value="<?= $usr['id'] ?>">
                        <button type="submit" class="btn btn-outline-<?= $usr['is_active'] ? 'secondary' : 'success' ?>"
                                title="<?= $usr['is_active'] ? 'Nonaktifkan Akun' : 'Aktifkan Akun' ?>">
                          <i class="bi bi-power"></i>
                        </button>
                      </form>

                      <!-- Delete Button (Only if 0 movements) -->
                      <?php if ($usr['movement_count'] == 0): ?>
                        <form method="POST" action="" class="d-inline" onsubmit="return confirm('Hapus permanen akun @<?= e($usr['username']) ?>?')">
                          <?= csrfField() ?>
                          <input type="hidden" name="action" value="delete">
                          <input type="hidden" name="user_id" value="<?= $usr['id'] ?>">
                          <button type="submit" class="btn btn-outline-danger" title="Hapus Permanen">
                            <i class="bi bi-trash"></i>
                          </button>
                        </form>
                      <?php else: ?>
                        <button type="button" class="btn btn-outline-secondary disabled" title="Tidak dapat dihapus karena ada histori mutasi">
                          <i class="bi bi-lock"></i>
                        </button>
                      <?php endif; ?>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>

              <!-- MODAL EDIT PENGGUNA -->
              <div class="modal fade" id="modalEditUser<?= $usr['id'] ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                  <div class="modal-content">
                    <form method="POST" action="">
                      <?= csrfField() ?>
                      <input type="hidden" name="action" value="update">
                      <input type="hidden" name="user_id" value="<?= $usr['id'] ?>">
                      <div class="modal-header">
                        <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Pengguna @<?= e($usr['username']) ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                      </div>
                      <div class="modal-body">
                        <div class="mb-3">
                          <label class="form-label">Username</label>
                          <input type="text" class="form-control" value="<?= e($usr['username']) ?>" disabled>
                          <div class="form-text">Username bersifat tetap dan tidak dapat diubah.</div>
                        </div>
                        <div class="mb-3">
                          <label class="form-label required">Nama Lengkap</label>
                          <input type="text" name="full_name" class="form-control" value="<?= e($usr['full_name']) ?>" required>
                        </div>
                        <div class="mb-3">
                          <label class="form-label">Email</label>
                          <input type="email" name="email" class="form-control" value="<?= e($usr['email']) ?>">
                        </div>
                        <div class="mb-3">
                          <label class="form-label required">Role</label>
                          <select name="role" class="form-select" <?= $usr['id'] === (int)$currentUser['id'] ? 'disabled' : '' ?>>
                            <option value="staff" <?= $usr['role'] === 'staff' ? 'selected' : '' ?>>Staff Gudang</option>
                            <option value="admin" <?= $usr['role'] === 'admin' ? 'selected' : '' ?>>Administrator</option>
                          </select>
                          <?php if ($usr['id'] === (int)$currentUser['id']): ?>
                            <input type="hidden" name="role" value="admin">
                            <div class="form-text text-muted">Role akun aktif Anda sendiri tidak dapat diubah.</div>
                          <?php endif; ?>
                        </div>
                        <div class="form-check form-switch mb-3">
                          <input class="form-check-input" type="checkbox" name="is_active" id="activeSwitch<?= $usr['id'] ?>"
                                 <?= $usr['is_active'] ? 'checked' : '' ?>
                                 <?= $usr['id'] === (int)$currentUser['id'] ? 'disabled' : '' ?>>
                          <label class="form-check-label" for="activeSwitch<?= $usr['id'] ?>">Akun Aktif</label>
                        </div>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary fw-semibold">Simpan Perubahan</button>
                      </div>
                    </form>
                  </div>
                </div>
              </div>

              <!-- MODAL RESET PASSWORD -->
              <div class="modal fade" id="modalResetPass<?= $usr['id'] ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                  <div class="modal-content">
                    <form method="POST" action="">
                      <?= csrfField() ?>
                      <input type="hidden" name="action" value="reset_password">
                      <input type="hidden" name="user_id" value="<?= $usr['id'] ?>">
                      <div class="modal-header">
                        <h5 class="modal-title fw-bold text-warning-emphasis">
                          <i class="bi bi-key-fill me-2"></i>Reset Password @<?= e($usr['username']) ?>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                      </div>
                      <div class="modal-body">
                        <p class="text-muted small">
                          Password baru akan langsung dienkripsi dengan <code>password_hash()</code> dan menggantikan password lama akun ini.
                        </p>
                        <div class="mb-3">
                          <label class="form-label required">Password Baru</label>
                          <input type="password" name="new_password" class="form-control" placeholder="Minimal 8 karakter..." required minlength="8">
                        </div>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning fw-semibold">Terapkan Password Baru</button>
                      </div>
                    </form>
                  </div>
                </div>
              </div>

            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- MODAL TAMBAH PENGGUNA BARU -->
<div class="modal fade" id="modalAddUser" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="create">
        <div class="modal-header">
          <h5 class="modal-title fw-bold"><i class="bi bi-person-plus-fill me-2 text-primary"></i>Tambah Pengguna Baru</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label required">Username</label>
            <input type="text" name="username" class="form-control" placeholder="Contoh: staff_logistik" required>
            <div class="form-text">Hanya huruf, angka, dan underscore tanpa spasi.</div>
          </div>
          <div class="mb-3">
            <label class="form-label required">Nama Lengkap</label>
            <input type="text" name="full_name" class="form-control" placeholder="Contoh: Ahmad Fadilah" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" placeholder="email@contoh.id">
          </div>
          <div class="mb-3">
            <label class="form-label required">Role Pengguna</label>
            <select name="role" class="form-select" required>
              <option value="staff" selected>Staff Operasional Gudang</option>
              <option value="admin">Administrator Sistem</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label required">Password Awal</label>
            <input type="password" name="password" class="form-control" placeholder="Minimal 8 karakter..." required minlength="8">
          </div>
          <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" name="is_active" id="newActive" checked>
            <label class="form-check-label" for="newActive">Langsung Aktifkan Akun</label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary fw-semibold"><i class="bi bi-save me-1"></i>Simpan Pengguna</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include dirname(__DIR__) . '/views/partials/footer_admin.php'; ?>
