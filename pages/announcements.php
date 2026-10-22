<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$publicPage = true; $u = current_user();
const ANN_CATS = ['Akademik', 'Uang kas', 'Kegiatan kelas', 'Perubahan jadwal', 'Informasi umum'];
$edit = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (!is_admin()) forbidden();
    $act = $_POST['action'] ?? ''; $id = (int)($_POST['id'] ?? 0);
    if ($act === 'delete') { q('DELETE FROM announcements WHERE id = ?', [$id]); audit('announcement_delete', "id=$id"); flash('ok', 'Pengumuman dihapus.'); redirect('pages/announcements.php'); }
    if ($act === 'pin') { q('UPDATE announcements SET is_pinned = 1 - is_pinned WHERE id = ?', [$id]); redirect('pages/announcements.php'); }
    if ($act === 'save') {
        $t = trim((string)($_POST['title'] ?? '')); $c = trim((string)($_POST['content'] ?? '')); $cat = (string)($_POST['category'] ?? '');
        if ($t === '' || mb_strlen($t) > 150 || $c === '' || !in_array($cat, ANN_CATS, true)) { flash('err', 'Judul, isi, dan kategori wajib diisi dengan benar.'); $edit = array_merge(['id' => $id], $_POST); }
        else {
            $imp = isset($_POST['is_important']) ? 1 : 0; $pin = isset($_POST['is_pinned']) ? 1 : 0;
            if ($id) q('UPDATE announcements SET title=?, content=?, category=?, is_important=?, is_pinned=? WHERE id=?', [$t, $c, $cat, $imp, $pin, $id]);
            else {
                q('INSERT INTO announcements (title, content, category, is_important, is_pinned, created_by, published_at) VALUES (?,?,?,?,?,?,?)', [$t, $c, $cat, $imp, $pin, $u['id'], now()]);
                notify_all('Pengumuman baru: ' . $t, $cat, 'pages/announcements.php', (int)$u['id']);
            }
            audit($id ? 'announcement_update' : 'announcement_create', $t); flash('ok', 'Pengumuman disimpan.'); redirect('pages/announcements.php');
        }
    }
}
if (!$edit && is_admin() && isset($_GET['edit'])) $edit = q('SELECT * FROM announcements WHERE id = ?', [(int)$_GET['edit']])->fetch() ?: null;
$cat = (string)($_GET['cat'] ?? ''); $w = '1=1'; $p = [];
if (in_array($cat, ANN_CATS, true)) { $w = 'a.category = ?'; $p = [$cat]; }
$total = (int)q("SELECT COUNT(*) FROM announcements a WHERE $w", $p)->fetchColumn();
[$off, $lim, $pages, $page] = paginate($total, 10);
$rows = q("SELECT a.*, u.name FROM announcements a LEFT JOIN users u ON u.id = a.created_by WHERE $w ORDER BY a.is_pinned DESC, a.published_at DESC LIMIT $lim OFFSET $off", $p)->fetchAll();

// Widget samping: Kategori count, Pinned list, Pengurus kelas
$catCounts = [];
$countsRaw = q('SELECT category, COUNT(*) as c FROM announcements GROUP BY category')->fetchAll();
foreach ($countsRaw as $cr) {
    $catCounts[$cr['category']] = (int)$cr['c'];
}
$pinnedList = q('SELECT id, title, category, published_at FROM announcements WHERE is_pinned = 1 ORDER BY published_at DESC LIMIT 5')->fetchAll();
$classOfficers = q("SELECT name, role, avatar FROM users WHERE role IN ('admin', 'bendahara') AND is_active = 1 ORDER BY role, name LIMIT 5")->fetchAll();

$pageTitle = 'Pengumuman'; $active = 'announcements';
require __DIR__ . '/../includes/header.php';
$v = fn($k, $d = '') => e($edit[$k] ?? $d);
?>
<section class="card" style="background:linear-gradient(160deg,var(--navy),var(--navy2));color:#fff">
  <div class="row" style="align-items:center;gap:20px;flex-wrap:wrap">
    <div style="flex:none;width:64px;height:64px;border-radius:14px;background:rgba(255,255,255,0.15);display:flex;align-items:center;justify-content:center;font-size:32px">
      <i class="bi bi-megaphone-fill"></i>
    </div>
    <div style="flex:1;min-width:280px">
      <h1 style="color:#fff;font-size:26px;margin:0 0 6px">Papan Pengumuman Kelas</h1>
      <p style="margin:0 0 6px;color:#e2e8f0;font-size:14px">Pusat informasi resmi, jadwal ujian, edaran kas, dan pengumuman penting TECHNO 24.</p>
      <div class="row" style="gap:8px;align-items:center;margin:0;flex-wrap:wrap">
        <span class="tag" style="background:rgba(255,255,255,0.2);color:#fff"><?= $total ?> Total Pengumuman</span>
        <?php if ($cat): ?>
          <span class="tag" style="background:rgba(255,255,255,0.3);color:#fff">Kategori: <?= e($cat) ?></span>
        <?php endif; ?>
        <?php if (count($pinnedList) > 0): ?>
          <span class="tag" style="background:rgba(255,255,255,0.2);color:#fff"><i class="bi bi-pin-angle-fill"></i> <?= count($pinnedList) ?> Disematkan</span>
        <?php endif; ?>
      </div>
    </div>
    <?php if (is_admin()): ?>
      <div style="flex:none">
        <a class="btn" style="background:#fff;color:var(--navy);font-weight:600" href="#form"><i class="bi bi-plus-circle"></i> Buat Pengumuman</a>
      </div>
    <?php endif; ?>
  </div>
</section>

<div class="row" style="margin-bottom:16px;gap:8px;flex-wrap:wrap">
  <a class="btn btn-sm <?= $cat ? 'btn-ghost' : '' ?>" style="flex:none" href="?">Semua Kategori (<?= $total ?>)</a>
  <?php foreach (ANN_CATS as $c): ?>
    <a class="btn btn-sm <?= $cat === $c ? '' : 'btn-ghost' ?>" style="flex:none" href="?cat=<?= urlencode($c) ?>">
      <?= e($c) ?> <?= isset($catCounts[$c]) ? '· ' . $catCounts[$c] : '' ?>
    </a>
  <?php endforeach; ?>
</div>

<div class="grid g-desktop-split">
  <!-- Kolom Kiri: Feed Pengumuman -->
  <div>
    <?php if (!$rows): ?>
      <div class="card empty"><i class="bi bi-megaphone"></i>Belum ada pengumuman<?= $cat ? ' untuk kategori ' . e($cat) : '' ?>.</div>
    <?php endif; ?>
    <?php foreach ($rows as $a): ?>
      <div class="card" id="a<?= (int)$a['id'] ?>" <?= $a['is_pinned'] ? 'style="border-left:4px solid var(--warn);box-shadow:0 4px 12px rgba(245,158,11,0.12)"' : '' ?>>
        <div class="row" style="align-items:center;margin-bottom:8px">
          <h2 style="margin:0;flex:1">
            <?php if ($a['is_pinned']): ?>
              <i class="bi bi-pin-angle-fill" style="color:var(--warn);margin-right:4px" title="Disematkan di atas"></i>
            <?php endif; ?>
            <?= e($a['title']) ?>
          </h2>
          <div style="flex:none;display:flex;gap:6px;align-items:center">
            <span class="tag"><?= e($a['category']) ?></span>
            <?php if ($a['is_important']): ?>
              <span class="tag tag-bad">Penting</span>
            <?php endif; ?>
          </div>
        </div>
        <p class="muted" style="font-size:12px;margin-bottom:12px">
          <i class="bi bi-clock"></i> <?= e(fmt_date($a['published_at'], true)) ?> · Oleh <strong><?= e($a['name'] ?: 'Pengurus Kelas') ?></strong>
        </p>
        <div style="line-height:1.6;font-size:14px;color:var(--text);margin-bottom:14px">
          <?= nl2br(e($a['content'])) ?>
        </div>
        <?php if (is_admin()): ?>
          <div class="row" style="flex-wrap:nowrap;gap:6px;padding-top:10px;border-top:1px solid var(--line)">
            <a class="btn btn-ghost btn-sm" href="?edit=<?= (int)$a['id'] ?>#form"><i class="bi bi-pencil"></i> Edit</a>
            <form method="post">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="pin">
              <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
              <button class="btn btn-ghost btn-sm" type="submit"><?= $a['is_pinned'] ? 'Lepas Sematan' : 'Sematkan' ?></button>
            </form>
            <form method="post" data-confirm="Hapus pengumuman ini?">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
              <button class="btn btn-danger btn-sm" type="submit"><i class="bi bi-trash"></i> Hapus</button>
            </form>
          </div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
    <?= pager_html($pages, $page) ?>
  </div>

  <!-- Kolom Kanan: Form Admin atau Widget Kategori & Pengurus -->
  <div>
    <?php if (is_admin()): ?>
      <div class="card sticky-card" id="form">
        <h2><i class="bi bi-pencil-square" style="color:var(--blue);margin-right:6px"></i> <?= $edit ? 'Edit Pengumuman' : 'Buat Pengumuman Baru' ?></h2>
        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="save">
          <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
          <label>Judul Pengumuman</label>
          <input name="title" required maxlength="150" placeholder="Contoh: Jadwal Ujian Tengah Semester Genap" value="<?= $v('title') ?>">
          <label>Kategori</label>
          <select name="category">
            <?php foreach (ANN_CATS as $c): ?>
              <option <?= ($edit['category'] ?? '') === $c ? 'selected' : '' ?>><?= e($c) ?></option>
            <?php endforeach; ?>
          </select>
          <label>Isi Lengkap Pengumuman</label>
          <textarea name="content" required style="min-height:130px" placeholder="Tuliskan detail pengumuman secara jelas..."><?= $v('content') ?></textarea>
          <label style="margin-top:10px">
            <input type="checkbox" name="is_important" <?= !empty($edit['is_important']) ? 'checked' : '' ?>> Tandai sebagai informasi penting
          </label>
          <label>
            <input type="checkbox" name="is_pinned" <?= !empty($edit['is_pinned']) ? 'checked' : '' ?>> Sematkan di paling atas daftar
          </label>
          <p style="margin-top:16px">
            <button class="btn" type="submit"><i class="bi bi-send-check"></i> Simpan Pengumuman</button>
            <?php if ($edit): ?><a class="btn btn-ghost" href="?">Batal</a><?php endif; ?>
          </p>
        </form>
      </div>
    <?php endif; ?>

    <?php if ($pinnedList): ?>
      <div class="card">
        <h2><i class="bi bi-pin-angle-fill" style="color:var(--warn);margin-right:6px"></i> Pengumuman Disematkan</h2>
        <?php foreach ($pinnedList as $pl): ?>
          <a class="list-item" style="color:inherit" href="#a<?= (int)$pl['id'] ?>">
            <div class="grow">
              <strong><?= e($pl['title']) ?></strong>
              <div class="muted" style="font-size:11px"><?= e($pl['category']) ?> · <?= e(fmt_date($pl['published_at'])) ?></div>
            </div>
            <i class="bi bi-chevron-right muted" style="font-size:12px"></i>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="card">
      <h2><i class="bi bi-tags" style="color:var(--blue);margin-right:6px"></i> Kategori Informasi</h2>
      <div class="row" style="gap:8px;flex-wrap:wrap">
        <?php foreach (ANN_CATS as $c): ?>
          <a class="tag <?= $cat === $c ? 'tag-blue' : '' ?>" href="?cat=<?= urlencode($c) ?>" style="padding:6px 12px;font-size:12px">
            <?= e($c) ?> <span style="opacity:0.75;margin-left:4px">(<?= $catCounts[$c] ?? 0 ?>)</span>
          </a>
        <?php endforeach; ?>
      </div>
    </div>

    <?php if ($classOfficers): ?>
      <div class="card">
        <h2><i class="bi bi-person-badge" style="color:var(--ok);margin-right:6px"></i> Pengurus Kelas</h2>
        <?php foreach ($classOfficers as $co): ?>
          <div class="list-item">
            <?= avatar_html($co, 'sm') ?>
            <div class="grow">
              <strong><?= e($co['name']) ?></strong>
              <div class="muted" style="font-size:11px"><?= ucfirst(e($co['role'])) ?> TECHNO 24</div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="card">
      <h2><i class="bi bi-info-circle" style="color:var(--navy);margin-right:6px"></i> Panduan Pengumuman</h2>
      <p class="muted" style="font-size:13px;line-height:1.5">
        Seluruh pengumuman di halaman ini dikelola oleh pengurus dan admin kelas. Pastikan selalu memantau informasi berlabel <strong>Penting</strong> agar tidak ketinggalan jadwal akademik maupun kegiatan bersama.
      </p>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
