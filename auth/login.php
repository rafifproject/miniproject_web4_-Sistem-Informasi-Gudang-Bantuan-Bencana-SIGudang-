<?php
/**
 * Halaman 9: Login Admin & Staff
 */
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/helpers/auth.php';
require_once dirname(__DIR__) . '/helpers/functions.php';

startSecureSession();

// Redirect jika sudah login
if (isLoggedIn()) {
    $u = getCurrentUser();
    redirect('/' . $u['role'] . '/dashboard.php');
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verifikasi CSRF
    $csrfToken = $_POST[CSRF_TOKEN_NAME] ?? '';
    if (!verifyCsrfToken($csrfToken)) {
        $error = 'Token keamanan tidak valid. Silakan muat ulang halaman.';
    } else {
        $username = sanitizeString($_POST['username'] ?? '', 80);
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = 'Username dan password wajib diisi.';
        } else {
            $db = getDB();
            $stmt = $db->prepare("SELECT id, username, password_hash, role, full_name, is_active FROM users WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password_hash'])) {
                $error = 'Username atau password salah.';
            } elseif (!$user['is_active']) {
                $error = 'Akun Anda telah dinonaktifkan. Hubungi administrator.';
            } else {
                loginUser($user);
                flashSet('login_success', 'Selamat datang, ' . $user['full_name'] . '!', 'success');
                redirect('/' . $user['role'] . '/dashboard.php');
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login | <?= e(APP_NAME) ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="login-page">
  <div class="login-card">
    <div class="login-logo">
      <div class="logo-circle">📦</div>
      <h1><?= e(APP_SHORT) ?></h1>
      <p>Login ke Akun Anda</p>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-danger alert-sm py-2 px-3 mb-3" role="alert">
        <i class="bi bi-exclamation-circle me-1"></i><?= e($error) ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="" autocomplete="off" novalidate>
      <?= csrfField() ?>

      <div class="mb-3">
        <label for="username" class="form-label">Username</label>
        <div class="input-group">
          <span class="input-group-text bg-light border-end-0"><i class="bi bi-person"></i></span>
          <input type="text" id="username" name="username" class="form-control border-start-0"
                 value="<?= e($username) ?>" placeholder="Masukkan username" required autofocus>
        </div>
      </div>

      <div class="mb-4">
        <label for="password" class="form-label">Password</label>
        <div class="input-group">
          <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock"></i></span>
          <input type="password" id="password" name="password" class="form-control border-start-0"
                 placeholder="Masukkan password" required>
          <button type="button" class="input-group-text bg-light" id="togglePass" style="cursor:pointer">
            <i class="bi bi-eye" id="eyeIcon"></i>
          </button>
        </div>
      </div>

      <button type="submit" class="btn-login-submit" id="loginBtn">
        <i class="bi bi-box-arrow-in-right me-2"></i>Login
      </button>
    </form>

    <div class="text-center mt-3">
      <a href="<?= BASE_URL ?>/index.php" class="text-muted small">
        <i class="bi bi-arrow-left me-1"></i>Kembali ke Beranda Publik
      </a>
    </div>

    <div class="mt-4 p-3 rounded" style="background:#f8fafc;font-size:0.78rem;color:#64748b">
      <strong>Akun Demo:</strong><br>
      🔵 Admin: <code>admin</code> / <code>admin123</code><br>
      🟢 Staff: <code>staff1</code> / <code>staff123</code>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const togglePass = document.getElementById('togglePass');
const passInput  = document.getElementById('password');
const eyeIcon    = document.getElementById('eyeIcon');
if (togglePass) {
  togglePass.addEventListener('click', () => {
    const show = passInput.type === 'password';
    passInput.type = show ? 'text' : 'password';
    eyeIcon.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
  });
}
// Prevent double-submit
document.querySelector('form').addEventListener('submit', function() {
  const btn = document.getElementById('loginBtn');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Memproses...';
});
</script>
</body>
</html>
