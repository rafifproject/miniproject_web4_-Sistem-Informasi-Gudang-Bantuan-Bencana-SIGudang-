<?php
/**
 * Halaman 20: Profil Pengguna (Admin & Staff)
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/helpers/auth.php';
require_once dirname(__DIR__) . '/helpers/functions.php';

startSecureSession();
requireLogin();

$db   = getDB();
$u    = getCurrentUser();
$msg  = '';
$msgType = 'success';

// Ambil data terkini dari DB
$stmt = $db->prepare("SELECT id,username,full_name,email,role,created_at FROM users WHERE id=?");
$stmt->execute([$u['id']]);
$user = $stmt->fetch();

if (!$user) { redirect('/auth/login.php'); }

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST[CSRF_TOKEN_NAME] ?? '';
    if (!verifyCsrfToken($token)) {
        $msg = 'Token keamanan tidak valid.'; $msgType='danger';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'update_profile') {
            $fullName = sanitizeString($_POST['full_name'] ?? '', 150);
            $email    = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
            if (empty($fullName)) {
                $msg = 'Nama lengkap tidak boleh kosong.'; $msgType='danger';
            } else {
                $stmt2 = $db->prepare("UPDATE users SET full_name=?, email=? WHERE id=?");
                $stmt2->execute([$fullName, $email ?: null, $u['id']]);

                // Sinkronisasi tabel relasi One-to-One: user_profiles
                $db->prepare("INSERT INTO user_profiles (user_id, full_name) VALUES (?, ?) ON DUPLICATE KEY UPDATE full_name=VALUES(full_name)")
                   ->execute([$u['id'], $fullName]);

                $_SESSION['full_name'] = $fullName;
                $msg = 'Profil berhasil diperbarui.';
                $user['full_name'] = $fullName;
                $user['email'] = $email;
            }
        } elseif ($action === 'change_password') {
            $oldPass  = $_POST['old_password'] ?? '';
            $newPass  = $_POST['new_password'] ?? '';
            $confPass = $_POST['confirm_password'] ?? '';
            // Verifikasi password lama
            $stmt3 = $db->prepare("SELECT password_hash FROM users WHERE id=?");
            $stmt3->execute([$u['id']]);
            $ph = $stmt3->fetchColumn();
            if (!password_verify($oldPass, $ph)) {
                $msg = 'Password lama tidak sesuai.'; $msgType='danger';
            } elseif (strlen($newPass) < 8) {
                $msg = 'Password baru minimal 8 karakter.'; $msgType='danger';
            } elseif ($newPass !== $confPass) {
                $msg = 'Konfirmasi password tidak cocok.'; $msgType='danger';
            } else {
                $hash = password_hash($newPass, PASSWORD_BCRYPT, ['cost'=>12]);
                $stmt4 = $db->prepare("UPDATE users SET password_hash=? WHERE id=?");
                $stmt4->execute([$hash, $u['id']]);
                $msg = 'Password berhasil diubah.';
            }
        }
    }
}

$pageTitle = 'Profil Pengguna';
$activeMenu = 'profil';
include __DIR__ . '/../views/partials/header_admin.php';
?>

<nav class="breadcrumb-bar" aria-label="breadcrumb">
  <ol class="breadcrumb"><li class="breadcrumb-item"><a href="<?= BASE_URL ?>/<?= $u['role'] ?>/dashboard.php">Dashboard</a></li><li class="breadcrumb-item active">Profil</li></ol>
</nav>

<?php if ($msg): ?>
  <div class="alert alert-<?= $msgType ?> alert-dismissible fade show" role="alert">
    <?= e($msg) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<div class="row g-4">
  <!-- Info Akun -->
  <div class="col-md-4">
    <div class="card-panel">
      <div class="card-panel-body text-center py-4">
        <div class="user-avatar mx-auto mb-3" style="width:72px;height:72px;font-size:2rem;background:var(--primary)">
          <?= strtoupper(substr($user['full_name'] ?: $user['username'],0,1)) ?>
        </div>
        <h4 class="fw-bold mb-1"><?= e($user['full_name'] ?: $user['username']) ?></h4>
        <p class="text-muted small mb-2">@<?= e($user['username']) ?></p>
        <span class="badge-status <?= $user['role']==='admin'?'badge-admin':'badge-staff' ?>"><?= ucfirst($user['role']) ?></span>
        <hr>
        <div class="text-start">
          <div class="mb-2"><small class="text-muted d-block">Email</small><?= e($user['email'] ?: '-') ?></div>
          <div class="mb-2"><small class="text-muted d-block">Bergabung</small><?= e(formatDate($user['created_at'],'d M Y')) ?></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Form Edit -->
  <div class="col-md-8">
    <!-- Update Profil -->
    <div class="card-panel mb-4">
      <div class="card-panel-header">Ubah Informasi Profil</div>
      <div class="card-panel-body">
        <form method="POST" action="">
          <?= csrfField() ?>
          <input type="hidden" name="action" value="update_profile">
          <div class="row g-3">
            <div class="col-sm-6">
              <label class="form-label fw-semibold">Username</label>
              <input type="text" class="form-control" value="<?= e($user['username']) ?>" disabled>
            </div>
            <div class="col-sm-6">
              <label class="form-label fw-semibold">Role</label>
              <input type="text" class="form-control" value="<?= ucfirst($user['role']) ?>" disabled>
            </div>
            <div class="col-sm-6">
              <label for="full_name" class="form-label fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
              <input type="text" id="full_name" name="full_name" class="form-control" value="<?= e($user['full_name']) ?>" required>
            </div>
            <div class="col-sm-6">
              <label for="email" class="form-label fw-semibold">Email</label>
              <input type="email" id="email" name="email" class="form-control" value="<?= e($user['email']) ?>">
            </div>
            <div class="col-12">
              <button type="submit" class="btn-primary-custom">
                <i class="bi bi-check-lg"></i> Simpan Perubahan
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- Ganti Password -->
    <div class="card-panel">
      <div class="card-panel-header">Ganti Password</div>
      <div class="card-panel-body">
        <form method="POST" action="">
          <?= csrfField() ?>
          <input type="hidden" name="action" value="change_password">
          <div class="row g-3">
            <div class="col-12">
              <label for="old_password" class="form-label fw-semibold">Password Lama <span class="text-danger">*</span></label>
              <input type="password" id="old_password" name="old_password" class="form-control" required>
            </div>
            <div class="col-sm-6">
              <label for="new_password" class="form-label fw-semibold">Password Baru <span class="text-danger">*</span></label>
              <input type="password" id="new_password" name="new_password" class="form-control" minlength="8" required>
              <div class="form-text">Minimal 8 karakter</div>
            </div>
            <div class="col-sm-6">
              <label for="confirm_password" class="form-label fw-semibold">Konfirmasi Password <span class="text-danger">*</span></label>
              <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
            </div>
            <div class="col-12">
              <button type="submit" class="btn btn-warning fw-bold">
                <i class="bi bi-shield-lock me-1"></i> Ganti Password
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../views/partials/footer_admin.php'; ?>
