<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$me = require_admin();
// Moderasi foto & story lewat halaman terkait (admin memiliki hak hapus di sana). Di sini: ringkasan, audit, dan backup.
$count = fn($sql, $p = []) => (int)q($sql, $p)->fetchColumn();
$stats = [
  'Akun aktif' => $count('SELECT COUNT(*) FROM users WHERE is_active = 1'), 'Akun nonaktif' => $count('SELECT COUNT(*) FROM users WHERE is_active = 0'),
  'Foto' => $count('SELECT COUNT(*) FROM photos'), 'Album' => $count('SELECT COUNT(*) FROM albums'),
  'Story aktif' => $count('SELECT COUNT(*) FROM stories WHERE expires_at > ?', [now()]), 'Pembayaran pending' => $count("SELECT COUNT(*) FROM cash_payments WHERE status='pending'"),
];
$sum = cash_summary();
$logs = q('SELECT l.*, u.username FROM audit_logs l LEFT JOIN users u ON u.id = l.user_id ORDER BY l.id DESC LIMIT 40')->fetchAll();
$pageTitle = 'Panel administrator'; $active = 'admin';
require __DIR__ . '/../includes/header.php';
?>
<section class="card" style="background:linear-gradient(160deg,var(--navy),var(--navy2));color:#fff">
  <div class="row" style="align-items:center;gap:20px;flex-wrap:wrap">
    <div style="flex:none;width:64px;height:64px;border-radius:14px;background:rgba(255,255,255,0.15);display:flex;align-items:center;justify-content:center;font-size:32px">
      <i class="bi bi-shield-lock-fill"></i>
    </div>
    <div style="flex:1;min-width:280px">
      <h1 style="color:#fff;font-size:26px;margin:0 0 6px">Panel Administrator</h1>
      <p style="margin:0 0 6px;color:#e2e8f0;font-size:14px">Ringkasan sistem, statistik, dan riwayat aktivitas audit.</p>
      <div class="row" style="gap:8px;align-items:center;margin:0;flex-wrap:wrap">
        <span class="tag" style="background:rgba(255,255,255,0.2);color:#fff"><?= count($stats) ?> Kategori Statistik</span>
        <span class="tag" style="background:rgba(77,184,168,0.3);color:#fff;border:1px solid rgba(77,184,168,0.5)"><?= count($logs) ?> Log Terbaru</span>
      </div>
    </div>
    <div style="flex:none">
      <a class="btn" style="background:#fff;color:var(--navy);font-weight:600" href="<?= e(url('admin/users.php')) ?>"><i class="bi bi-people"></i> Kelola Akun</a>
    </div>
  </div>
</section>
<div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(200px,1fr));margin-bottom:18px">
  <?php foreach ($stats as $k => $v): ?><div class="card stat" style="margin-bottom:0"><b><?= $v ?></b><span><?= e($k) ?></span></div><?php endforeach; ?>
  <div class="card stat" style="margin-bottom:0"><b><?= e(rupiah($sum['balance'])) ?></b><span>Saldo kas</span></div>
</div>

<div class="grid g-desktop-split">
  <div>
    <div class="card">
      <h2>Log audit terbaru</h2>
      <div class="tablewrap" style="max-height:540px;overflow:auto">
        <table>
          <tr><th>Waktu</th><th>Pengguna</th><th>Aksi</th><th>Detail</th></tr>
          <?php foreach ($logs as $l): ?>
            <tr>
              <td><small><?= e(fmt_date($l['created_at'], true)) ?></small></td>
              <td><strong><?= e($l['username'] ?: '-') ?></strong></td>
              <td><span class="tag tag-blue"><?= e($l['action']) ?></span></td>
              <td class="muted"><small><?= e($l['detail']) ?></small></td>
            </tr>
          <?php endforeach; ?>
        </table>
      </div>
    </div>
  </div>
  <div>
    <div class="card sticky-card">
      <h2>Akses moderasi cepat</h2>
      <p class="muted">Sebagai admin, Anda dapat mengelola dan memoderasi seluruh data kelas langsung di halaman terkait.</p>
      <div style="display:flex;flex-direction:column;gap:8px;margin-top:14px">
        <a class="btn btn-ghost" href="<?= e(url('pages/schedules.php')) ?>"><i class="bi bi-calendar-week"></i> Kelola Jadwal Kuliah</a>
        <a class="btn btn-ghost" href="<?= e(url('pages/announcements.php')) ?>"><i class="bi bi-megaphone"></i> Kelola Pengumuman</a>
        <a class="btn btn-ghost" href="<?= e(url('pages/polls.php')) ?>"><i class="bi bi-bar-chart"></i> Kelola Polling</a>
        <a class="btn btn-ghost" href="<?= e(url('pages/gallery.php')) ?>"><i class="bi bi-images"></i> Moderasi Galeri Foto</a>
        <a class="btn btn-ghost" href="<?= e(url('pages/albums.php')) ?>"><i class="bi bi-collection"></i> Moderasi Album</a>
        <a class="btn btn-ghost" href="<?= e(url('pages/stories.php')) ?>"><i class="bi bi-play-circle"></i> Moderasi Story 24 Jam</a>
        <a class="btn btn-ghost" href="<?= e(url('pages/cash_manage.php')) ?>"><i class="bi bi-wallet2"></i> Kelola Kas & Keuangan</a>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
