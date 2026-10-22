<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$publicPage = true; $u = current_user();
$search = trim((string)($_GET['q'] ?? '')); $w = $u ? 'is_active = 1' : 'is_active = 1 AND profile_public = 1'; $p = []; // pengunjung hanya melihat profil yang diizinkan
if ($search !== '') { $w .= ' AND (name LIKE ? OR username LIKE ?)'; $p = ['%' . $search . '%', '%' . $search . '%']; }
// Hanya kolom non-sensitif yang diambil (tanpa password_hash)
$rows = q("SELECT name, username, avatar, bio, role, birthday, birthday_public FROM users WHERE $w ORDER BY name", $p)->fetchAll();
$pageTitle = 'Direktori anggota'; $active = 'members';
require __DIR__ . '/../includes/header.php';
?>
<section class="card" style="background:linear-gradient(160deg,var(--navy),var(--navy2));color:#fff">
  <div class="row" style="align-items:center;gap:20px;flex-wrap:wrap">
    <div style="flex:none;width:64px;height:64px;border-radius:14px;background:rgba(255,255,255,0.15);display:flex;align-items:center;justify-content:center;font-size:32px">
      <i class="bi bi-people"></i>
    </div>
    <div style="flex:1;min-width:280px">
      <h1 style="color:#fff;font-size:26px;margin:0 0 6px">Direktori Anggota</h1>
      <p style="margin:0 0 6px;color:#e2e8f0;font-size:14px">Profil dan informasi kontak seluruh anggota TECHNO 24 Kelas A.</p>
      <div class="row" style="gap:8px;align-items:center;margin:0;flex-wrap:wrap">
        <span class="tag" style="background:rgba(255,255,255,0.2);color:#fff"><?= count($rows) ?> Anggota<?= $u ? ' Aktif' : '' ?></span>
      </div>
    </div>
  </div>
</section>
<form class="card row" method="get"><div style="flex:3"><input type="search" name="q" placeholder="Cari anggota" value="<?= e($search) ?>"></div><button class="btn" style="flex:none" type="submit">Cari</button></form>
<?php if (!$rows): ?><div class="card empty"><i class="bi bi-people"></i>Belum ada anggota yang cocok atau bersedia ditampilkan.</div>
<?php else: ?><div class="g-members"><?php foreach ($rows as $m): ?>
 <div class="card" style="text-align:center"><?= avatar_html($m, 'lg') ?><h2 style="margin:8px 0 0"><?= e($m['name']) ?></h2><?php if ($u): ?><div class="muted">@<?= e($m['username']) ?></div><?php endif; ?>
  <?php if ($m['role'] !== 'anggota'): ?><span class="tag tag-blue"><?= e(ucfirst($m['role'])) ?></span><?php endif; ?>
  <?php if ($m['bio']): ?><p class="muted"><?= e($m['bio']) ?></p><?php endif; ?>
  <?php if ($m['birthday'] && $m['birthday_public']): ?><div class="muted"><i class="bi bi-gift"></i> <?= e(date('j', strtotime($m['birthday'])) . ' ' . BULAN[(int)date('n', strtotime($m['birthday']))]) ?></div><?php endif; ?></div>
<?php endforeach; ?></div><?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
