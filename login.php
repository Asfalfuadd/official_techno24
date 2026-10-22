<?php
require_once __DIR__ . '/includes/bootstrap.php';
if (current_user()) redirect('dashboard.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $username = strtolower(trim((string)($_POST['username'] ?? '')));
    $password = (string)($_POST['password'] ?? '');
    $ip = client_ip();
    $since = date('Y-m-d H:i:s', time() - LOGIN_LOCK_MINUTES * 60);
    $fails = (int)q('SELECT COUNT(*) FROM login_attempts WHERE attempted_at >= ? AND (username = ? OR ip = ?)', [$since, $username, $ip])->fetchColumn();
    if ($fails >= MAX_LOGIN_FAILS) {
        $error = 'Terlalu banyak percobaan. Coba lagi dalam ' . LOGIN_LOCK_MINUTES . ' menit.';
    } else {
        $u = q('SELECT id, password_hash, is_active FROM users WHERE username = ?', [$username])->fetch();
        // Selalu jalankan verifikasi agar waktu respons tidak membocorkan keberadaan username.
        $hash = $u['password_hash'] ?? '$2y$10$usesomesillystringforsaleaeuYIBVVJ3Eg7ZPHlzVnb7SqEzG2mR2';
        $ok = password_verify($password, $hash) && $u && (int)$u['is_active'] === 1;
        if ($ok) {
            q('DELETE FROM login_attempts WHERE username = ?', [$username]);
            login_user((int)$u['id']);
            audit('login', $username);
            redirect('dashboard.php');
        }
        q('INSERT INTO login_attempts (username, ip, attempted_at) VALUES (?,?,?)', [substr($username, 0, 50), $ip, now()]);
        $error = 'Username atau password salah, atau akun tidak aktif.';
    }
}
?><!DOCTYPE html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login · TECHNO 24</title>
<link rel="icon" type="image/png" href="<?= e(url('assets/images/favicon.png')) ?>">
<link rel="shortcut icon" href="<?= e(url('favicon.ico')) ?>">
<link rel="apple-touch-icon" href="<?= e(url('assets/images/logo-badge.png')) ?>">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="<?= e(url('assets/css/app.css')) ?>"></head>
<body>
<div class="login">
  <section class="login-hero">
    <?php $logoSize = 88; include __DIR__ . '/includes/logo.php'; ?>
    <h1>TECHNO 24</h1>
    <p><?= e(APP_TAGLINE) ?></p>
  </section>
  <section class="login-form">
    <form method="post" autocomplete="off">
      <h2>Login ke akun Anda</h2>
      <p class="muted">Masuk untuk mengakses semua fitur TECHNO 24.</p>
      <?php if ($error): ?><div class="alert alert-err" role="alert"><?= e($error) ?></div><?php endif; ?>
      <?= csrf_field() ?>
      <label for="username">Username</label>
      <input id="username" name="username" required autofocus autocapitalize="none" maxlength="50" placeholder="Masukkan username">
      <label for="password">Password</label>
      <input id="password" name="password" type="password" required placeholder="Masukkan password">
      <p><button class="btn" style="width:100%;justify-content:center" type="submit">Login</button></p>
      <p class="muted">Lupa password? Hubungi administrator kelas untuk reset.</p>
      <p><a href="<?= e(url('index.php')) ?>">&larr; Kembali ke beranda</a></p>
    </form>
  </section>
</div>
<script src="<?= e(url('assets/js/page-transitions.js?v=' . (file_exists(__DIR__ . '/assets/js/page-transitions.js') ? filemtime(__DIR__ . '/assets/js/page-transitions.js') : time()))) ?>"></script>
</body></html>
