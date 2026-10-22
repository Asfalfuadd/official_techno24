<?php
require_once __DIR__ . '/includes/bootstrap.php';
$u = require_login();
$pageTitle = 'Dashboard'; $active = 'dashboard';
$dow = today_dow(); $nowTime = date('H:i:s');
$todaySched = q('SELECT * FROM schedules WHERE day_of_week = ? ORDER BY start_time', [$dow])->fetchAll();
$next = q('SELECT * FROM schedules WHERE (day_of_week = ? AND start_time > ?) OR day_of_week > ? ORDER BY day_of_week, start_time LIMIT 1', [$dow, $nowTime, $dow])->fetch();
if (!$next) $next = q('SELECT * FROM schedules ORDER BY day_of_week, start_time LIMIT 1')->fetch();
$stories = q('SELECT u.id, u.name, u.avatar, MAX(s.created_at) AS latest FROM stories s JOIN users u ON u.id = s.user_id WHERE s.expires_at > ? GROUP BY u.id, u.name, u.avatar ORDER BY latest DESC', [now()])->fetchAll();
$photos = q('SELECT p.id, p.title, p.filename FROM photos p ORDER BY p.created_at DESC LIMIT 4')->fetchAll();
$anns = q('SELECT id, title, category, is_important, published_at FROM announcements ORDER BY is_pinned DESC, published_at DESC LIMIT 4')->fetchAll();
$agendas = q("SELECT id, title, event_date, status FROM agendas WHERE status NOT IN ('selesai','dibatalkan') AND (event_date IS NULL OR event_date >= ?) ORDER BY event_date IS NULL, event_date LIMIT 4", [now()])->fetchAll();
$members = (int)q('SELECT COUNT(*) FROM users WHERE is_active = 1')->fetchColumn();
if (is_admin()) {
    $adm = [
      'Akun aktif' => $members,
      'Foto' => (int)q('SELECT COUNT(*) FROM photos')->fetchColumn(),
      'Album' => (int)q('SELECT COUNT(*) FROM albums')->fetchColumn(),
      'Story aktif' => (int)q('SELECT COUNT(*) FROM stories WHERE expires_at > ?', [now()])->fetchColumn(),
      'Agenda menunggu konfirmasi' => (int)q("SELECT COUNT(*) FROM agendas WHERE status='menunggu_konfirmasi'")->fetchColumn(),
    ];
}
require __DIR__ . '/includes/header.php';
?>
<section class="card" style="background:linear-gradient(160deg,var(--navy),var(--navy2));color:#fff">
  <div class="row" style="align-items:center;gap:20px;flex-wrap:wrap">
    <div style="flex:none;width:64px;height:64px;border-radius:14px;background:rgba(255,255,255,0.15);display:flex;align-items:center;justify-content:center;font-size:32px">
      <i class="bi bi-grid-1x2-fill"></i>
    </div>
    <div style="flex:1;min-width:280px">
      <h1 style="color:#fff;font-size:26px;margin:0 0 6px">Selamat datang, <?= e($u['name']) ?>!</h1>
      <p style="margin:0 0 6px;color:#e2e8f0;font-size:14px"><?= e(HARI[$dow] . ', ' . fmt_date(date('Y-m-d'))) ?> · Dashboard pribadi untuk akses cepat semua fitur kelas.</p>
      <div class="row" style="gap:8px;align-items:center;margin:0;flex-wrap:wrap">
        <span class="tag" style="background:rgba(255,255,255,0.2);color:#fff"><i class="bi bi-people"></i> <?= $members ?> Anggota Aktif</span>
        <?php if ($todaySched): ?>
          <span class="tag" style="background:rgba(255,255,255,0.2);color:#fff"><i class="bi bi-calendar-check"></i> <?= count($todaySched) ?> Kuliah Hari Ini</span>
        <?php endif; ?>
      </div>
    </div>
    <div style="flex:none;display:flex;gap:8px;flex-wrap:wrap">
      <a class="btn" style="background:#fff;color:var(--navy);font-weight:600" href="<?= e(url('pages/stories.php')) ?>"><i class="bi bi-play-circle"></i> Story</a>
      <a class="btn btn-ghost" style="background:rgba(255,255,255,0.15);color:#fff;border:1px solid rgba(255,255,255,0.3)" href="<?= e(url('pages/gallery.php?upload=1')) ?>"><i class="bi bi-upload"></i> Upload</a>
    </div>
  </div>
</section>

<?php if (is_admin() && !empty($adm)): ?>
<div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr));margin-bottom:18px">
  <?php foreach ($adm as $k => $v): ?>
    <div class="card stat" style="margin-bottom:0">
      <b><?= e(is_numeric($v) ? number_format((int)$v, 0, ',', '.') : $v) ?></b>
      <span><?= e($k) ?></span>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- 3 Kolom Desktop: Jadwal, Pengumuman, Agenda -->
<div class="grid g-desktop-3">
  <div class="card" style="display:flex;flex-direction:column">
    <div class="row" style="align-items:center;margin-bottom:12px">
      <h2 style="margin:0;flex:1"><i class="bi bi-calendar-week" style="color:var(--blue);margin-right:6px"></i> Jadwal hari ini</h2>
      <a href="<?= e(url('pages/schedules.php')) ?>" class="muted" style="font-size:12px;flex:none">Mingguan</a>
    </div>
    <div style="flex:1">
      <?php if (!$todaySched): ?>
        <div class="empty"><i class="bi bi-calendar-check"></i>Tidak ada jadwal hari ini.</div>
      <?php else: foreach ($todaySched as $s): ?>
        <div class="list-item">
          <i class="bi bi-book" style="color:var(--blue)"></i>
          <div class="grow">
            <strong><?= e($s['course_name']) ?></strong>
            <div class="muted"><?= e(substr($s['start_time'],0,5) . ' - ' . substr($s['end_time'],0,5)) ?> · <?= e($s['room'] ?: '-') ?></div>
          </div>
        </div>
      <?php endforeach; endif; ?>
    </div>
    <?php if ($next): ?>
      <div style="margin-top:14px;padding-top:10px;border-top:1px solid var(--line);font-size:13px" class="muted">
        Berikutnya: <strong><?= e($next['course_name']) ?></strong>, <?= e(HARI[(int)$next['day_of_week']]) ?> <?= e(substr($next['start_time'],0,5)) ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="card" style="display:flex;flex-direction:column">
    <div class="row" style="align-items:center;margin-bottom:12px">
      <h2 style="margin:0;flex:1"><i class="bi bi-megaphone" style="color:var(--warn);margin-right:6px"></i> Pengumuman</h2>
      <a href="<?= e(url('pages/announcements.php')) ?>" class="muted" style="font-size:12px;flex:none">Semua</a>
    </div>
    <div style="flex:1">
      <?php if (!$anns): ?>
        <div class="empty"><i class="bi bi-megaphone"></i>Belum ada pengumuman.</div>
      <?php else: foreach ($anns as $a): ?>
        <a class="list-item" href="<?= e(url('pages/announcements.php#a' . (int)$a['id'])) ?>" style="color:inherit">
          <div class="grow">
            <strong><?= e($a['title']) ?></strong>
            <div class="muted"><?= e($a['category']) ?> · <?= e(fmt_date($a['published_at'])) ?></div>
          </div>
          <?php if ($a['is_important']): ?><span class="tag tag-bad">Penting</span><?php endif; ?>
        </a>
      <?php endforeach; endif; ?>
    </div>
  </div>

  <div class="card" style="display:flex;flex-direction:column">
    <div class="row" style="align-items:center;margin-bottom:12px">
      <h2 style="margin:0;flex:1"><i class="bi bi-airplane" style="color:var(--ok);margin-right:6px"></i> Agenda mendatang</h2>
      <a href="<?= e(url('pages/agendas.php')) ?>" class="muted" style="font-size:12px;flex:none">Semua</a>
    </div>
    <div style="flex:1">
      <?php if (!$agendas): ?>
        <div class="empty"><i class="bi bi-calendar-event"></i>Belum ada agenda.</div>
      <?php else: foreach ($agendas as $a): ?>
        <a class="list-item" href="<?= e(url('pages/agendas.php?id=' . (int)$a['id'])) ?>" style="color:inherit">
          <div class="grow">
            <strong><?= e($a['title']) ?></strong>
            <div class="muted"><?= e(fmt_date($a['event_date'], true)) ?></div>
          </div>
          <span class="tag tag-blue"><?= e(str_replace('_', ' ', $a['status'])) ?></span>
        </a>
      <?php endforeach; endif; ?>
    </div>
  </div>
</div>

<!-- Grid 2 Kolom Desktop: Foto & Stories -->
<div class="grid g-desktop-split">
  <div>
    <div class="card">
      <div class="row" style="align-items:center;margin-bottom:14px">
        <h2 style="margin:0;flex:1"><i class="bi bi-images" style="color:var(--blue);margin-right:6px"></i> Foto terbaru kegiatan</h2>
        <a class="btn btn-ghost btn-sm" href="<?= e(url('pages/gallery.php')) ?>" style="flex:none">Buka galeri</a>
      </div>
      <?php if (!$photos): ?>
        <div class="empty"><i class="bi bi-images"></i>Belum ada foto. Jadilah yang pertama mengunggah.</div>
      <?php else: ?>
        <div class="photos">
          <?php foreach ($photos as $p): ?>
            <a class="photo" href="<?= e(url('pages/gallery.php')) ?>">
              <img loading="lazy" src="<?= e(url('uploads/photos/' . $p['filename'])) ?>" alt="<?= e($p['title']) ?>">
              <div><?= e($p['title']) ?></div>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div>
    <div class="card">
      <div class="row" style="align-items:center;margin-bottom:14px">
        <h2 style="margin:0;flex:1"><i class="bi bi-play-circle" style="color:var(--blue);margin-right:6px"></i> Story terbaru</h2>
        <a href="<?= e(url('pages/stories.php')) ?>" class="muted" style="font-size:12px;flex:none">Buka story</a>
      </div>
      <?php if (!$stories): ?>
        <div class="empty" style="padding:24px 16px"><i class="bi bi-camera"></i>Belum ada story aktif.</div>
      <?php else: ?>
        <div class="stories" style="padding-bottom:8px">
          <?php foreach ($stories as $s): ?>
            <a class="story-bubble" href="<?= e(url('pages/stories.php?u=' . (int)$s['id'])) ?>">
              <span class="ring"><?= avatar_html($s, 'md') ?></span><br><?= e($s['name']) ?>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="card stat">
      <b><?= $members ?></b>
      <span>Jumlah anggota kelas aktif</span>
      <div style="margin-top:12px">
        <a class="btn btn-ghost btn-sm" href="<?= e(url('pages/members.php')) ?>"><i class="bi bi-people"></i> Lihat direktori anggota</a>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
