<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$me = require_admin(); // hanya admin (dicek di server)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $act = $_POST['action'] ?? ''; $id = (int)($_POST['id'] ?? 0);
    $target = $id ? q('SELECT * FROM users WHERE id = ?', [$id])->fetch() : null;
    $self = $target && (int)$target['id'] === (int)$me['id'];
    if ($act === 'create') {
        $name = trim((string)($_POST['name'] ?? '')); $un = strtolower(trim((string)($_POST['username'] ?? ''))); $role = (string)($_POST['role'] ?? 'anggota'); $pw = (string)($_POST['password'] ?? '');
        if (!in_array($role, ['admin', 'bendahara', 'anggota'], true)) $role = 'anggota';
        if ($name === '' || !preg_match('/^[a-z0-9_.]{3,30}$/', $un)) flash('err', 'Nama wajib diisi; username 3-30 karakter (huruf kecil, angka, _ .).');
        elseif (($pr = password_problem($pw)) !== null) flash('err', str_replace('Password baru', 'Password awal', $pr));
        elseif (q('SELECT 1 FROM users WHERE username = ?', [$un])->fetchColumn()) flash('err', 'Username sudah dipakai.');
        else { q('INSERT INTO users (name, username, password_hash, role, must_change_password) VALUES (?,?,?,?,1)', [$name, $un, password_hash($pw, PASSWORD_DEFAULT), $role]); audit('user_create', "$un ($role)"); flash('ok', 'Akun dibuat. Pengguna wajib mengganti password saat login pertama.'); }
    } elseif ($target && $act === 'toggle') {
        if ($self) flash('err', 'Anda tidak dapat menonaktifkan akun sendiri.');
        else { q('UPDATE users SET is_active = 1 - is_active WHERE id = ?', [$id]); audit('user_toggle', $target['username']); flash('ok', 'Status akun diubah.'); }
    } elseif ($target && $act === 'role') {
        $role = (string)($_POST['role'] ?? '');
        if (!in_array($role, ['admin', 'bendahara', 'anggota'], true)) flash('err', 'Peran tidak valid.');
        elseif ($self) flash('err', 'Anda tidak dapat mengubah peran akun sendiri.');
        else { q('UPDATE users SET role = ? WHERE id = ?', [$role, $id]); audit('user_role', $target['username'] . ' -> ' . $role); flash('ok', 'Peran diperbarui.'); }
    } elseif ($target && $act === 'name') {
        $name = trim((string)($_POST['name'] ?? '')); if ($name !== '' && mb_strlen($name) <= 100) { q('UPDATE users SET name = ? WHERE id = ?', [$name, $id]); flash('ok', 'Nama diperbarui.'); }
    } elseif ($target && $act === 'reset') {
        $pw = (string)($_POST['password'] ?? '');
        if (($pr = password_problem($pw)) !== null) flash('err', str_replace('Password baru', 'Password sementara', $pr));
        else {
            q('UPDATE users SET password_hash = ?, must_change_password = 1 WHERE id = ?', [password_hash($pw, PASSWORD_DEFAULT), $id]);
            q('INSERT INTO password_reset_logs (target_user_id, reset_by) VALUES (?,?)', [$id, $me['id']]); audit('password_reset', $target['username']);
            flash('ok', 'Password direset. Pengguna wajib menggantinya saat login berikutnya.');
        }
    }
    redirect('admin/users.php');
}
$rows = q('SELECT id, name, username, role, is_active, last_login_at FROM users ORDER BY role = "anggota", name')->fetchAll();
$pageTitle = 'Kelola akun'; $active = 'users';
require __DIR__ . '/../includes/header.php';
?>
<section class="card" style="background:linear-gradient(160deg,var(--navy),var(--navy2));color:#fff">
  <div class="row" style="align-items:center;gap:20px;flex-wrap:wrap">
    <div style="flex:none;width:64px;height:64px;border-radius:14px;background:rgba(255,255,255,0.15);display:flex;align-items:center;justify-content:center;font-size:32px">
      <i class="bi bi-people-fill"></i>
    </div>
    <div style="flex:1;min-width:280px">
      <h1 style="color:#fff;font-size:26px;margin:0 0 6px">Kelola Akun</h1>
      <p style="margin:0 0 6px;color:#e2e8f0;font-size:14px"><?= count($rows) ?> akun terdaftar. Password tidak pernah ditampilkan.</p>
      <div class="row" style="gap:8px;align-items:center;margin:0;flex-wrap:wrap">
        <span class="tag" style="background:rgba(255,255,255,0.2);color:#fff">Admin Only</span>
        <span class="tag" style="background:rgba(77,184,168,0.3);color:#fff;border:1px solid rgba(77,184,168,0.5)"><i class="bi bi-key"></i> Reset Password Manual</span>
      </div>
    </div>
  </div>
</section>
<div class="card"><div class="tablewrap"><table><tr><th>Nama</th><th>Username</th><th>Peran</th><th>Status</th><th>Login terakhir</th><th>Aksi</th></tr>
<?php foreach ($rows as $r): ?><tr><td><?= e($r['name']) ?></td><td><?= e($r['username']) ?></td>
 <td><form method="post" class="row" style="flex-wrap:nowrap"><?= csrf_field() ?><input type="hidden" name="action" value="role"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
  <select name="role" <?= (int)$r['id'] === (int)$me['id'] ? 'disabled' : '' ?>><?php foreach (['anggota', 'bendahara', 'admin'] as $ro): ?><option <?= $r['role'] === $ro ? 'selected' : '' ?>><?= $ro ?></option><?php endforeach; ?></select>
  <?php if ((int)$r['id'] !== (int)$me['id']): ?><button class="btn btn-ghost btn-sm" type="submit">Ubah</button><?php endif; ?></form></td>
 <td><span class="tag <?= $r['is_active'] ? 'tag-ok' : 'tag-bad' ?>"><?= $r['is_active'] ? 'Aktif' : 'Nonaktif' ?></span></td><td class="muted"><?= e($r['last_login_at'] ? fmt_date($r['last_login_at'], true) : 'Belum pernah') ?></td>
 <td><div class="row" style="flex-wrap:nowrap"><?php if ((int)$r['id'] !== (int)$me['id']): ?><form method="post" data-confirm="Ubah status akun ini?"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn btn-ghost btn-sm" type="submit"><?= $r['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?></button></form><?php endif; ?>
 <form method="post" class="row" style="flex-wrap:nowrap" data-confirm="Reset password akun ini?"><?= csrf_field() ?><input type="hidden" name="action" value="reset"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="text" name="password" placeholder="Password sementara" required minlength="8" autocomplete="off" style="min-width:130px"><button class="btn btn-sm" type="submit">Reset</button></form></div></td></tr><?php endforeach; ?></table></div></div>
<div class="card"><h2>Buat akun baru</h2><p class="muted">Tidak ada registrasi publik; hanya admin yang dapat membuat akun.</p><form method="post" autocomplete="off"><?= csrf_field() ?><input type="hidden" name="action" value="create">
 <div class="row"><div><label>Nama</label><input name="name" required maxlength="100"></div><div><label>Username</label><input name="username" required maxlength="30" autocapitalize="none"></div>
 <div><label>Peran</label><select name="role"><option>anggota</option><option>bendahara</option><option>admin</option></select></div><div><label>Password awal (min. 8, huruf + angka)</label><input type="text" name="password" required minlength="8" autocomplete="off"></div></div>
 <p><button class="btn" type="submit">Buat akun</button></p></form></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
