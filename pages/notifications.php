<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$u = require_login();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (($_POST['action'] ?? '') === 'read_all') { q('UPDATE notifications SET is_read = 1 WHERE user_id = ?', [$u['id']]); flash('ok', 'Semua notifikasi ditandai sudah dibaca.'); }
    elseif (($_POST['action'] ?? '') === 'read') { q('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?', [(int)($_POST['id'] ?? 0), $u['id']]); } // cek kepemilikan
    redirect('pages/notifications.php');
}
// Tautan: tandai dibaca lalu arahkan (hanya notifikasi milik sendiri)
if (isset($_GET['go'])) {
    $n = q('SELECT id, link FROM notifications WHERE id = ? AND user_id = ?', [(int)$_GET['go'], $u['id']])->fetch();
    if ($n) { q('UPDATE notifications SET is_read = 1 WHERE id = ?', [$n['id']]); $l = (string)$n['link']; redirect(($l !== '' && !preg_match('#^(https?:)?//#', $l)) ? ltrim($l, '/') : 'pages/notifications.php'); }
    redirect('pages/notifications.php');
}
$total = (int)q('SELECT COUNT(*) FROM notifications WHERE user_id = ?', [$u['id']])->fetchColumn();
$unreadCount = (int)q('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0', [$u['id']])->fetchColumn();
[$off, $lim, $pages, $page] = paginate($total, 20);
$rows = q("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT $lim OFFSET $off", [$u['id']])->fetchAll();
$pageTitle = 'Notifikasi'; $active = 'notifications';
require __DIR__ . '/../includes/header.php';
?>
<section class="card" style="background:linear-gradient(160deg,var(--navy),var(--navy2));color:#fff">
  <div class="row" style="align-items:center;gap:20px;flex-wrap:wrap">
    <div style="flex:none;width:64px;height:64px;border-radius:14px;background:rgba(255,255,255,0.15);display:flex;align-items:center;justify-content:center;font-size:32px">
      <i class="bi bi-bell-fill"></i>
    </div>
    <div style="flex:1;min-width:280px">
      <h1 style="color:#fff;font-size:26px;margin:0 0 6px">Notifikasi</h1>
      <p style="margin:0 0 6px;color:#e2e8f0;font-size:14px">Informasi dan pemberitahuan terbaru untuk Anda dari TECHNO 24.</p>
      <div class="row" style="gap:8px;align-items:center;margin:0;flex-wrap:wrap">
        <span class="tag" style="background:rgba(255,255,255,0.2);color:#fff"><?= $total ?> Total Notifikasi</span>
        <?php if ($unreadCount > 0): ?>
          <span class="tag" style="background:rgba(245,158,11,0.3);color:#fff;border:1px solid rgba(245,158,11,0.5)"><i class="bi bi-circle-fill" style="font-size:8px"></i> <?= $unreadCount ?> Belum Dibaca</span>
        <?php endif; ?>
      </div>
    </div>
    <?php if ($unreadCount > 0): ?>
      <div style="flex:none">
        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="read_all">
          <button class="btn" style="background:#fff;color:var(--navy);font-weight:600" type="submit"><i class="bi bi-check-all"></i> Tandai Semua</button>
        </form>
      </div>
    <?php endif; ?>
  </div>
</section>
<div class="card"><?php if (!$rows): ?><div class="empty"><i class="bi bi-bell-slash"></i>Belum ada notifikasi.</div>
<?php else: foreach ($rows as $n): ?>
 <div class="list-item" style="<?= $n['is_read'] ? '' : 'background:#f0f6ff;border-radius:8px;padding:10px' ?>">
  <i class="bi bi-bell"></i><div class="grow"><a href="?go=<?= (int)$n['id'] ?>" style="color:inherit"><strong><?= e($n['title']) ?></strong><div class="muted"><?= e($n['message']) ?></div></a><div class="muted"><?= e(time_ago($n['created_at'])) ?></div></div>
  <?php if (!$n['is_read']): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="read"><input type="hidden" name="id" value="<?= (int)$n['id'] ?>"><button class="btn btn-ghost btn-sm" type="submit">Tandai dibaca</button></form><?php endif; ?></div>
<?php endforeach; endif; ?></div><?= pager_html($pages, $page) ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
