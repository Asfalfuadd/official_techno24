<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$publicPage = true; $u = current_user();
$id = (int)($_GET['id'] ?? 0);
$album = $id ? q('SELECT a.*, u.name AS owner FROM albums a JOIN users u ON u.id = a.user_id WHERE a.id = ?', [$id])->fetch() : null;
if ($id && !$album) { http_response_code(404); exit('Album tidak ditemukan.'); }
if ($album && !$u && !$album['is_public']) redirect('login.php');
$canManage = $album && $u && (is_admin() || (int)$album['user_id'] === (int)$u['id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$u) redirect('login.php');
    csrf_check();
    $act = $_POST['action'] ?? '';
    if ($act === 'create') {
        $t = trim((string)($_POST['title'] ?? ''));
        $d = (string)($_POST['event_date'] ?? ''); $d = preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) ? $d : null;
        if ($t === '' || mb_strlen($t) > 150) { flash('err', 'Judul album wajib diisi.'); redirect('pages/albums.php'); }
        q('INSERT INTO albums (user_id, title, description, event_date, is_public) VALUES (?,?,?,?,?)', [$u['id'], $t, mb_substr(trim((string)($_POST['description'] ?? '')), 0, 500) ?: null, $d, isset($_POST['is_public']) ? 1 : 0]);
        $nid = (int)db()->lastInsertId(); audit('album_create', $t); flash('ok', 'Album dibuat. Tambahkan foto di bawah.'); redirect('pages/albums.php?id=' . $nid);
    }
    if (!$album || !$canManage) forbidden(); // semua aksi lain butuh hak kelola album
    if ($act === 'edit') {
        $t = trim((string)($_POST['title'] ?? '')); $d = (string)($_POST['event_date'] ?? ''); $d = preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) ? $d : null;
        if ($t === '') flash('err', 'Judul wajib diisi.');
        else { q('UPDATE albums SET title=?, description=?, event_date=?, is_public=? WHERE id=?', [$t, mb_substr(trim((string)($_POST['description'] ?? '')), 0, 500) ?: null, $d, isset($_POST['is_public']) ? 1 : 0, $id]); flash('ok', 'Album diperbarui.'); }
    } elseif ($act === 'delete') {
        q('DELETE FROM albums WHERE id = ?', [$id]); audit('album_delete', "id=$id"); // foto asli tidak ikut terhapus
        flash('ok', 'Album dihapus (foto tetap ada di galeri).'); redirect('pages/albums.php');
    } elseif ($act === 'add') {
        $ids = array_filter(array_map('intval', (array)($_POST['photo_ids'] ?? [])));
        foreach ($ids as $pid) q('INSERT IGNORE INTO album_photos (album_id, photo_id) SELECT ?, id FROM photos WHERE id = ?', [$id, $pid]);
        flash('ok', count($ids) . ' foto diproses.');
    } elseif ($act === 'remove') {
        $pid = (int)($_POST['photo_id'] ?? 0);
        q('DELETE FROM album_photos WHERE album_id = ? AND photo_id = ?', [$id, $pid]);
        q('UPDATE albums SET cover_photo_id = NULL WHERE id = ? AND cover_photo_id = ?', [$id, $pid]);
        flash('ok', 'Foto dikeluarkan dari album.');
    } elseif ($act === 'cover') {
        $pid = (int)($_POST['photo_id'] ?? 0);
        if (q('SELECT 1 FROM album_photos WHERE album_id = ? AND photo_id = ?', [$id, $pid])->fetchColumn()) { q('UPDATE albums SET cover_photo_id = ? WHERE id = ?', [$pid, $id]); flash('ok', 'Sampul diganti.'); }
    }
    redirect('pages/albums.php?id=' . $id);
}
$pageTitle = $album ? $album['title'] : 'Album foto'; $active = 'albums';
require __DIR__ . '/../includes/header.php';

if (!$album):
  $search = trim((string)($_GET['q'] ?? '')); $base = $u ? '1=1' : 'a.is_public = 1'; $w = $base; $p = []; // pengunjung hanya melihat album publik
  $pc = $u ? '' : ' AND is_public = 1'; $pp = $u ? '' : ' AND p.is_public = 1';
  if ($search !== '') { $w = $base . ' AND (a.title LIKE ? OR a.description LIKE ?)'; $p = ['%' . $search . '%', '%' . $search . '%']; }
  $total = (int)q("SELECT COUNT(*) FROM albums a WHERE $w", $p)->fetchColumn();
  [$off, $lim, $pages, $page] = paginate($total, 12);
  $albums = q("SELECT a.*, u.name AS owner, (SELECT COUNT(*) FROM album_photos ap JOIN photos p ON p.id = ap.photo_id WHERE ap.album_id = a.id{$pp}) AS cnt,
     COALESCE((SELECT filename FROM photos WHERE id = a.cover_photo_id{$pc}), (SELECT p.filename FROM album_photos ap JOIN photos p ON p.id = ap.photo_id WHERE ap.album_id = a.id{$pp} ORDER BY ap.added_at LIMIT 1)) AS cover
     FROM albums a JOIN users u ON u.id = a.user_id WHERE $w ORDER BY a.event_date DESC, a.created_at DESC LIMIT $lim OFFSET $off", $p)->fetchAll();
?>
<section class="card" style="background:linear-gradient(160deg,var(--navy),var(--navy2));color:#fff">
  <div class="row" style="align-items:center;gap:20px;flex-wrap:wrap">
    <div style="flex:none;width:64px;height:64px;border-radius:14px;background:rgba(255,255,255,0.15);display:flex;align-items:center;justify-content:center;font-size:32px">
      <i class="bi bi-collection"></i>
    </div>
    <div style="flex:1;min-width:280px">
      <h1 style="color:#fff;font-size:26px;margin:0 0 6px">Album Foto</h1>
      <p style="margin:0 0 6px;color:#e2e8f0;font-size:14px">Kumpulan foto terorganisir per kegiatan dan event kelas.</p>
      <div class="row" style="gap:8px;align-items:center;margin:0;flex-wrap:wrap">
        <span class="tag" style="background:rgba(255,255,255,0.2);color:#fff"><?= $total ?> Total Album</span>
      </div>
    </div>
    <?php if ($u): ?>
      <div style="flex:none">
        <a class="btn" style="background:#fff;color:var(--navy);font-weight:600" href="#buat-album"><i class="bi bi-plus-circle"></i> Buat Album</a>
      </div>
    <?php endif; ?>
  </div>
</section>
<form class="card row" method="get"><div style="flex:3"><input type="search" name="q" placeholder="Cari album" value="<?= e($search) ?>"></div><button class="btn" style="flex:none" type="submit">Cari</button></form>

<?php if ($u): ?>
<div class="grid g-desktop-split">
  <div>
    <?php if (!$albums): ?><div class="card empty"><i class="bi bi-collection"></i>Belum ada album.</div>
    <?php else: ?><div class="photos"><?php foreach ($albums as $a): ?>
     <a class="photo" href="?id=<?= (int)$a['id'] ?>"><?php if ($a['cover']): ?><img loading="lazy" src="<?= e(url('uploads/photos/' . $a['cover'])) ?>" alt=""><?php else: ?><div style="aspect-ratio:4/3;background:#e2e8f0;display:flex;align-items:center;justify-content:center"><i class="bi bi-image" style="font-size:36px;color:#94a3b8"></i></div><?php endif; ?>
     <div><strong><?= e($a['title']) ?></strong><br><small><?= (int)$a['cnt'] ?> foto · <?= e($a['event_date'] ? fmt_date($a['event_date']) : 'Tanpa tanggal') ?></small></div></a>
    <?php endforeach; ?></div><?= pager_html($pages, $page) ?><?php endif; ?>
  </div>
  <div>
    <div class="card sticky-card" id="buat-album"><h2>Buat album baru</h2><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="create">
     <label>Judul album</label><input name="title" required maxlength="150">
     <label>Tanggal kegiatan</label><input type="date" name="event_date">
     <label>Deskripsi</label><textarea name="description" maxlength="500" style="min-height:80px"></textarea>
     <label><input type="checkbox" name="is_public" value="1"> Tampilkan ke pengunjung publik</label>
     <p><button class="btn" type="submit"><i class="bi bi-plus-circle"></i> Buat album</button></p></form></div>
  </div>
</div>
<?php else: ?>
  <?php if (!$albums): ?><div class="card empty"><i class="bi bi-collection"></i>Belum ada album.</div>
  <?php else: ?><div class="photos"><?php foreach ($albums as $a): ?>
   <a class="photo" href="?id=<?= (int)$a['id'] ?>"><?php if ($a['cover']): ?><img loading="lazy" src="<?= e(url('uploads/photos/' . $a['cover'])) ?>" alt=""><?php else: ?><div style="aspect-ratio:4/3;background:#e2e8f0;display:flex;align-items:center;justify-content:center"><i class="bi bi-image" style="font-size:36px;color:#94a3b8"></i></div><?php endif; ?>
   <div><strong><?= e($a['title']) ?></strong><br><small><?= (int)$a['cnt'] ?> foto · <?= e($a['event_date'] ? fmt_date($a['event_date']) : 'Tanpa tanggal') ?></small></div></a>
  <?php endforeach; ?></div><?= pager_html($pages, $page) ?><?php endif; ?>
  <div class="card" style="margin-top:18px"><a class="btn" href="<?= e(url('login.php')) ?>">Login untuk membuat album</a></div>
<?php endif; ?>
<?php else:
  $photos = q('SELECT p.id, p.title, p.filename FROM album_photos ap JOIN photos p ON p.id = ap.photo_id WHERE ap.album_id = ?' . ($u ? '' : ' AND p.is_public = 1') . ' ORDER BY ap.added_at DESC', [$id])->fetchAll();
  $avail = $canManage ? q('SELECT id, title FROM photos WHERE id NOT IN (SELECT photo_id FROM album_photos WHERE album_id = ?) ORDER BY created_at DESC LIMIT 100', [$id])->fetchAll() : [];
?>
<p><a href="<?= e(url('pages/albums.php')) ?>">&larr; Semua album</a></p>
<div class="card">
  <h1><?= e($album['title']) ?></h1>
  <p class="muted">Oleh <?= e($album['owner']) ?> · <?= e($album['event_date'] ? fmt_date($album['event_date']) : 'Tanpa tanggal') ?> · <?= count($photos) ?> foto</p>
  <?php if ($album['description']): ?><p><?= e($album['description']) ?></p><?php endif; ?>
</div>

<div class="grid <?= $canManage ? 'g-desktop-split' : '' ?>">
  <div>
    <?php if (!$photos): ?><div class="card empty"><i class="bi bi-image"></i>Album ini belum berisi foto.</div>
    <?php else: ?><div class="photos"><?php foreach ($photos as $ph): ?>
     <div class="photo"><a href="<?= e(url('pages/photo.php?id=' . (int)$ph['id'])) ?>"><img loading="lazy" src="<?= e(url('uploads/photos/' . $ph['filename'])) ?>" alt="<?= e($ph['title']) ?>"></a>
     <div><?= e($ph['title']) ?><?php if ($canManage): ?><div class="row" style="flex-wrap:wrap;gap:6px;margin-top:6px">
      <form method="post" style="flex:none"><?= csrf_field() ?><input type="hidden" name="action" value="cover"><input type="hidden" name="photo_id" value="<?= (int)$ph['id'] ?>"><button class="btn btn-ghost btn-sm" type="submit">Sampul</button></form>
      <form method="post" data-confirm="Keluarkan foto dari album? Foto asli tidak dihapus." style="flex:none"><?= csrf_field() ?><input type="hidden" name="action" value="remove"><input type="hidden" name="photo_id" value="<?= (int)$ph['id'] ?>"><button class="btn btn-danger btn-sm" type="submit">Keluarkan</button></form></div><?php endif; ?></div></div>
    <?php endforeach; ?></div><?php endif; ?>
  </div>

  <?php if ($canManage): ?>
  <div>
    <div class="card sticky-card">
      <h2>Tambah foto ke album</h2>
      <?php if (!$avail): ?><p class="muted">Semua foto galeri sudah ada di album ini.</p><?php else: ?>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="add">
      <div class="tablewrap" style="max-height:200px;overflow:auto;margin-bottom:12px;border:1px solid var(--line);border-radius:8px;padding:8px">
      <?php foreach ($avail as $a): ?><label style="font-weight:400;margin:4px 0;display:block"><input type="checkbox" name="photo_ids[]" value="<?= (int)$a['id'] ?>"> <?= e($a['title']) ?></label><?php endforeach; ?></div>
      <p><button class="btn" type="submit">Tambahkan terpilih</button></p></form><?php endif; ?>

      <h2 style="margin-top:20px;padding-top:14px;border-top:1px solid var(--line)">Edit album</h2>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="edit">
       <label>Judul</label><input name="title" required maxlength="150" value="<?= e($album['title']) ?>">
       <label>Tanggal</label><input type="date" name="event_date" value="<?= e($album['event_date']) ?>">
       <label>Deskripsi</label><textarea name="description" maxlength="500" style="min-height:70px"><?= e($album['description']) ?></textarea>
       <label><input type="checkbox" name="is_public" value="1" <?= $album['is_public'] ? 'checked' : '' ?>> Tampilkan ke pengunjung publik</label>
       <p style="margin-top:12px"><button class="btn" type="submit">Simpan perubahan</button></p></form>
      <form method="post" data-confirm="Hapus album ini? Foto tetap ada di galeri."><?= csrf_field() ?><input type="hidden" name="action" value="delete"><button class="btn btn-danger btn-sm" type="submit">Hapus album</button></form>
    </div>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
