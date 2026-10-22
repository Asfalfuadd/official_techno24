<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$u = require_login(true); // boleh diakses saat wajib ganti password
$force = (int)$u['must_change_password'] === 1;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $act = $_POST['action'] ?? '';
    if ($act === 'password') {
        $row = q('SELECT password_hash FROM users WHERE id = ?', [$u['id']])->fetch();
        $old = (string)($_POST['old'] ?? ''); $new = (string)($_POST['new'] ?? '');
        if (!password_verify($old, $row['password_hash'])) flash('err', 'Password lama salah.');
        elseif ($new !== (string)($_POST['new2'] ?? '')) flash('err', 'Konfirmasi password baru tidak sama.');
        elseif ($new === $old) flash('err', 'Password baru harus berbeda dari password lama.');
        elseif (($p = password_problem($new)) !== null) flash('err', $p);
        else {
            q('UPDATE users SET password_hash = ?, must_change_password = 0 WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $u['id']]);
            session_regenerate_id(true);
            audit('change_password');
            flash('ok', 'Password berhasil diganti.');
            redirect('pages/profile.php');
        }
    } elseif ($act === 'delavatar' && !$force) {
        // Hapus foto profil yang aktif (file fisik di uploads/avatars ikut terhapus)
        try {
            $old = q('SELECT avatar FROM users WHERE id = ?', [$u['id']])->fetchColumn();
            if (!$old) {
                flash('err', 'Kamu belum memasang foto profil.');
            } else {
                delete_upload('avatars', $old);
                q('UPDATE users SET avatar = NULL WHERE id = ?', [$u['id']]);
                audit('avatar_delete'); flash('ok', 'Foto profil dihapus.');
            }
        } catch (Throwable $ex) { error_log('delavatar: ' . $ex->getMessage()); flash('err', 'Gagal menghapus foto profil.'); }
        redirect('pages/profile.php');
    } elseif ($act === 'profile' && !$force) {
        $bio = mb_substr(trim((string)($_POST['bio'] ?? '')), 0, 255);
        $bd = (string)($_POST['birthday'] ?? ''); $bd = preg_match('/^\d{4}-\d{2}-\d{2}$/', $bd) ? $bd : null;
        $pub = isset($_POST['birthday_public']) ? 1 : 0; $pp = isset($_POST['profile_public']) ? 1 : 0;
        try {
            $av = save_image($_FILES['avatar'] ?? [], 'avatars', false);
            if ($av) { $old = q('SELECT avatar FROM users WHERE id = ?', [$u['id']])->fetchColumn(); delete_upload('avatars', $old ?: null); q('UPDATE users SET avatar = ? WHERE id = ?', [$av, $u['id']]); }
            q('UPDATE users SET bio = ?, birthday = ?, birthday_public = ?, profile_public = ? WHERE id = ?', [$bio ?: null, $bd, $pub, $pp, $u['id']]);
            flash('ok', 'Profil diperbarui.');
        } catch (RuntimeException $ex) { flash('err', $ex->getMessage()); }
        redirect('pages/profile.php');
    }
}
$me = q('SELECT * FROM users WHERE id = ?', [$u['id']])->fetch();
$hist = q('SELECT ip, user_agent, logged_at FROM login_history WHERE user_id = ? ORDER BY logged_at DESC LIMIT 8', [$u['id']])->fetchAll();
$pageTitle = 'Pengaturan akun'; $active = 'profile'; $allowPwChange = true;
require __DIR__ . '/../includes/header.php';
?>
<section class="card" style="background:linear-gradient(160deg,var(--navy),var(--navy2));color:#fff">
  <div class="row" style="align-items:center;gap:20px;flex-wrap:wrap">
    <div style="flex:none;width:64px;height:64px;border-radius:14px;background:rgba(255,255,255,0.15);display:flex;align-items:center;justify-content:center;font-size:32px">
      <i class="bi bi-gear-fill"></i>
    </div>
    <div style="flex:1;min-width:280px">
      <h1 style="color:#fff;font-size:26px;margin:0 0 6px">Pengaturan Akun</h1>
      <p style="margin:0 0 6px;color:#e2e8f0;font-size:14px">Kelola password, foto profil, bio, dan preferensi privasi.</p>
      <div class="row" style="gap:8px;align-items:center;margin:0;flex-wrap:wrap">
        <span class="tag" style="background:rgba(255,255,255,0.2);color:#fff"><?= e($me['name']) ?> · <?= e($me['username']) ?></span>
        <span class="tag" style="background:rgba(77,184,168,0.3);color:#fff;border:1px solid rgba(77,184,168,0.5)">Peran: <?= e($me['role']) ?></span>
      </div>
    </div>
  </div>
</section>

<?php if ($force): ?><div class="alert alert-info">Demi keamanan, ganti password awal Anda sebelum memakai website.</div><?php endif; ?>
<div class="grid g2">
 <div class="card"><h2>Ganti password</h2>
  <form method="post" autocomplete="off"><?= csrf_field() ?><input type="hidden" name="action" value="password">
   <label for="old">Password lama</label><input id="old" type="password" name="old" required>
   <label for="new">Password baru</label><input id="new" type="password" name="new" required minlength="8">
   <label for="new2">Konfirmasi password baru</label><input id="new2" type="password" name="new2" required minlength="8">
   <p class="muted">Minimal 8 karakter, mengandung huruf dan angka.</p>
   <button class="btn" type="submit">Simpan</button></form></div>
 <?php if (!$force): ?>
 <div class="card"><h2>Profil</h2>
  <p><?= avatar_html($me, 'lg') ?></p>
  <p class="muted">Nama: <strong><?= e($me['name']) ?></strong> · Username: <strong><?= e($me['username']) ?></strong> · Peran: <?= e($me['role']) ?> (tidak dapat diubah sendiri)</p>
  <form method="post" enctype="multipart/form-data"><?= csrf_field() ?><input type="hidden" name="action" value="profile">
   <label for="avatar">Foto profil (opsional — bisa dipotong &amp; diatur posisinya)</label><input id="avatar" type="file" name="avatar" accept="image/*" data-upcompress="900" data-upcrop data-preview="pv">
   <img id="pv" alt="Pratinjau foto profil" style="display:none;max-height:180px;margin-top:8px;border-radius:12px">
   <p class="muted">Foto otomatis dipotong persegi dan dikompres, jadi foto besar dari HP tetap bisa diunggah.</p>
   <label for="bio">Bio singkat</label><input id="bio" name="bio" maxlength="255" value="<?= e($me['bio']) ?>">
   <label for="bd">Tanggal ulang tahun</label><input id="bd" type="date" name="birthday" value="<?= e($me['birthday']) ?>">
   <label><input type="checkbox" name="profile_public" <?= $me['profile_public'] ? 'checked' : '' ?>> Tampilkan profil saya (nama, foto, bio) ke pengunjung publik</label>
   <label><input type="checkbox" name="birthday_public" <?= $me['birthday_public'] ? 'checked' : '' ?>> Tampilkan ulang tahun di direktori</label>
   <p><button class="btn" type="submit">Simpan profil</button></p></form>
  <?php if ($me['avatar']): ?>
   <form method="post" data-confirm="Hapus foto profil ini? Bisa unggah ulang kapan saja."><?= csrf_field() ?><input type="hidden" name="action" value="delavatar"><button class="btn btn-danger btn-sm" type="submit"><i class="bi bi-trash"></i> Hapus foto profil</button></form>
  <?php endif; ?>
 </div>
 <?php endif; ?>
</div>
<?php if (!$force): ?>
<div class="card"><h2>Riwayat login</h2><div class="tablewrap"><table><tr><th>Waktu</th><th>IP</th><th>Perangkat</th></tr>
 <?php foreach ($hist as $h): ?><tr><td><?= e(fmt_date($h['logged_at'], true)) ?></td><td><?= e($h['ip']) ?></td><td class="muted"><?= e(mb_substr((string)$h['user_agent'], 0, 60)) ?></td></tr><?php endforeach; ?></table></div></div>
<?php endif; ?>
<form method="post" action="<?= e(url('logout.php')) ?>"><?= csrf_field() ?><button class="btn btn-danger" type="submit"><i class="bi bi-box-arrow-right"></i> Logout</button></form>
<?php require __DIR__ . '/../includes/footer.php'; ?>