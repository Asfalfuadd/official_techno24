<?php
// Setup awal: (1) buat admin pertama dengan password pilihan sendiri, (2) seed 22 akun anggota.
// Aman dijalankan berulang (akun yang sudah ada dilewati). Setelah selesai, set SETUP_ENABLED = false di config/app.php.
require_once __DIR__ . '/includes/bootstrap.php';
if (!SETUP_ENABLED) { http_response_code(404); exit('Setup dinonaktifkan.'); }

$members = [
 ['ILHAM','ilham'],['WINATA','winata'],['LIAN','lian'],['FAREL','farel'],['IRUL','irul'],['ULOK','ulok'],
 ['DIKA','dika'],['NABIL','nabil'],['YAUMIL','yaumil'],['ASFAL','asfal'],['JALL','jall'],['MINA','mina'],
 ['CACA','caca'],['DINDA','dinda'],['ALDA','alda'],['FATIMAH','fatimah'],['OBY','oby'],['FARHAN','farhan'],
 ['SUSAN','susan'],['HARIS','haris'],['NURI','nuri'],['NADYA','nadya'],
]; // password awal = username + "123" (wajib diganti saat login pertama)

$hasAdmin = (int)q("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn() > 0;
$msg = ''; $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $act = $_POST['action'] ?? '';
    if ($act === 'admin' && !$hasAdmin) {
        $name = trim((string)$_POST['name']); $un = strtolower(trim((string)$_POST['username']));
        $pw = (string)$_POST['password'];
        if ($name === '' || !preg_match('/^[a-z0-9_.]{3,30}$/', $un)) $err = 'Nama wajib diisi; username 3-30 karakter (huruf kecil, angka, _ .).';
        elseif ($pw !== (string)$_POST['password2']) $err = 'Konfirmasi password tidak sama.';
        elseif (($p = password_problem($pw)) !== null) $err = $p;
        elseif (in_array($un, array_column($members, 1), true)) $err = 'Username admin tidak boleh sama dengan username anggota.';
        else {
            q('INSERT INTO users (name, username, password_hash, role, must_change_password) VALUES (?,?,?,?,0)',
              [$name, $un, password_hash($pw, PASSWORD_DEFAULT), 'admin']);
            $hasAdmin = true; $msg = 'Admin pertama dibuat. Silakan login, lalu kembali ke halaman ini untuk membuat akun anggota.';
        }
    } elseif ($act === 'seed' && is_admin()) {
        $made = 0;
        $ins = db()->prepare('INSERT INTO users (name, username, password_hash, role, must_change_password) VALUES (?,?,?,?,1)');
        foreach ($members as [$n, $un]) {
            if (q('SELECT 1 FROM users WHERE username = ?', [$un])->fetchColumn()) continue;
            $ins->execute([$n, $un, password_hash($un . '123', PASSWORD_DEFAULT), 'anggota']); $made++;
        }
        audit('seed_members', "dibuat: $made");
        $msg = "Selesai. $made akun baru dibuat (yang sudah ada dilewati).";
    }
}
$count = (int)q("SELECT COUNT(*) FROM users WHERE role <> 'admin'")->fetchColumn();
?><!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Setup · TECHNO 24</title>
<link rel="icon" type="image/png" href="<?= e(url('assets/images/favicon.png')) ?>">
<link rel="shortcut icon" href="<?= e(url('favicon.ico')) ?>">
<link rel="stylesheet" href="<?= e(url('assets/css/app.css')) ?>"></head>
<body style="background:#f3f5f9"><div style="max-width:480px;margin:40px auto;padding:0 14px">
<div class="card"><h1>Setup TECHNO 24</h1>
<?php if ($msg): ?><div class="alert alert-ok"><?= e($msg) ?></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-err"><?= e($err) ?></div><?php endif; ?>
<?php if (!$hasAdmin): ?>
  <p class="sub">Langkah 1: buat administrator pertama. Tentukan password Anda sendiri.</p>
  <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="admin">
    <label>Nama</label><input name="name" required maxlength="100">
    <label>Username</label><input name="username" required maxlength="30" autocapitalize="none">
    <label>Password (min. 8, huruf + angka)</label><input type="password" name="password" required>
    <label>Ulangi password</label><input type="password" name="password2" required>
    <p><button class="btn" type="submit">Buat admin</button></p></form>
<?php elseif (!is_admin()): ?>
  <p class="sub">Admin sudah ada. <a href="<?= e(url('login.php')) ?>">Login sebagai admin</a>, lalu buka halaman ini lagi untuk membuat 22 akun anggota.</p>
<?php else: ?>
  <p class="sub">Langkah 2: buat 22 akun anggota (<?= $count ?> akun non-admin saat ini). Password awal memakai pola bawaan dan wajib diganti saat login pertama.</p>
  <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="seed"><button class="btn" type="submit">Buat akun anggota</button></form>
  <p class="muted">Selesai? Ubah <code>SETUP_ENABLED</code> menjadi <code>false</code> di config/app.php. <a href="<?= e(url('dashboard.php')) ?>">Ke dashboard</a></p>
<?php endif; ?>
</div></div></body></html>
