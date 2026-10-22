<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$publicPage = true; $u = current_user();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$u) { flash('err', 'Login dulu untuk mengunggah foto.'); redirect('login.php'); }
    csrf_check();
    if (($_POST['action'] ?? '') === 'upload') {
        $title = trim((string)($_POST['title'] ?? '')); $cap = mb_substr(trim((string)($_POST['caption'] ?? '')), 0, 500);
        if ($title === '' || mb_strlen($title) > 150) flash('err', 'Judul wajib diisi (maks. 150 karakter).');
        else {
            try {
                $fn = save_image($_FILES['photo'] ?? [], 'photos');
                q('INSERT INTO photos (user_id, title, caption, filename, is_public) VALUES (?,?,?,?,?)', [$u['id'], $title, $cap ?: null, $fn, isset($_POST['is_public']) ? 1 : 0]);
                audit('photo_upload', $title); flash('ok', 'Foto berhasil diunggah.');
            } catch (RuntimeException $ex) { flash('err', $ex->getMessage()); }
        }
        redirect('pages/gallery.php');
    }
}
$search = trim((string)($_GET['q'] ?? '')); $mine = $u && isset($_GET['mine']);
$w = $u ? '1=1' : 'p.is_public = 1'; $p = []; // pengunjung hanya melihat foto publik
if ($search !== '') { $w .= ' AND (p.title LIKE ? OR p.caption LIKE ? OR u.name LIKE ?)'; $like = '%' . $search . '%'; array_push($p, $like, $like, $like); }
if ($mine) { $w .= ' AND p.user_id = ?'; $p[] = $u['id']; }
$total = (int)q("SELECT COUNT(*) FROM photos p JOIN users u ON u.id = p.user_id WHERE $w", $p)->fetchColumn();
[$off, $lim, $pages, $page] = paginate($total, 12);
$photos = q("SELECT p.id, p.title, p.filename, p.created_at, u.name FROM photos p JOIN users u ON u.id = p.user_id WHERE $w ORDER BY p.created_at DESC LIMIT $lim OFFSET $off", $p)->fetchAll();
$pageTitle = 'Galeri foto'; $active = 'gallery';
require __DIR__ . '/../includes/header.php';
?>
<section class="card" style="background:linear-gradient(160deg,var(--navy),var(--navy2));color:#fff">
  <div class="row" style="align-items:center;gap:20px;flex-wrap:wrap">
    <div style="flex:none;width:64px;height:64px;border-radius:14px;background:rgba(255,255,255,0.15);display:flex;align-items:center;justify-content:center;font-size:32px">
      <i class="bi bi-images"></i>
    </div>
    <div style="flex:1;min-width:280px">
      <h1 style="color:#fff;font-size:26px;margin:0 0 6px">Galeri Foto</h1>
      <p style="margin:0 0 6px;color:#e2e8f0;font-size:14px">Dokumentasi kegiatan dan momen berharga Kelas A.</p>
      <div class="row" style="gap:8px;align-items:center;margin:0;flex-wrap:wrap">
        <span class="tag" style="background:rgba(255,255,255,0.2);color:#fff"><?= $total ?> Total Foto</span>
        <?php if ($search): ?>
          <span class="tag" style="background:rgba(255,255,255,0.3);color:#fff">Hasil pencarian: "<?= e($search) ?>"</span>
        <?php endif; ?>
      </div>
    </div>
    <?php if ($u): ?>
      <div style="flex:none">
        <button type="button" class="btn" style="background:#fff;color:var(--navy);font-weight:600" data-trigger-file="photo" data-focus-after="title"><i class="bi bi-upload"></i> Unggah Foto</button>
      </div>
    <?php endif; ?>
  </div>
</section>
<form class="row card" method="get"><div style="flex:3"><input type="search" name="q" placeholder="Cari judul, keterangan, atau pengunggah" value="<?= e($search) ?>"></div>
 <?php if ($u): ?><label style="flex:none;margin:0;align-self:center"><input type="checkbox" name="mine" <?= $mine ? 'checked' : '' ?>> Foto saya</label><?php endif; ?><button class="btn" style="flex:none" type="submit">Cari</button></form>
<?php if ($u): ?>
<div class="grid g-desktop-split">
  <div>
    <?php if (!$photos): ?><div class="card empty"><i class="bi bi-images"></i><?= ($search || $mine) ? 'Tidak ada foto yang cocok.' : 'Belum ada foto yang ditampilkan.' ?></div>
    <?php else: ?><div class="photos"><?php foreach ($photos as $ph): ?>
     <a class="photo" href="<?= e(url('pages/photo.php?id=' . (int)$ph['id'])) ?>"><img loading="lazy" src="<?= e(url('uploads/photos/' . $ph['filename'])) ?>" alt="<?= e($ph['title']) ?>"><div><strong><?= e($ph['title']) ?></strong><br><small><?= e($ph['name']) ?> · <?= e(fmt_date($ph['created_at'])) ?></small></div></a>
    <?php endforeach; ?></div><?= pager_html($pages, $page) ?><?php endif; ?>
  </div>
  <div>
    <div class="card sticky-card" id="upload"><h2>Unggah foto baru</h2>
     <form method="post" enctype="multipart/form-data"><?= csrf_field() ?><input type="hidden" name="action" value="upload">
      <label for="photo">File gambar (JPG/PNG/WEBP/GIF — foto besar otomatis dikompres sebelum terkirim)</label><input id="photo" type="file" name="photo" accept="image/*" required data-upcompress="1800" data-preview="prev">
      <img id="prev" alt="Pratinjau" style="display:none;max-height:200px;margin-top:8px;border-radius:10px">
      <label for="title">Judul foto</label><input id="title" name="title" required maxlength="150">
      <label for="caption">Keterangan / Caption</label><textarea id="caption" name="caption" maxlength="500" style="min-height:80px"></textarea>
      <label><input type="checkbox" name="is_public" value="1"> Tampilkan ke pengunjung publik</label>
      <p style="margin-top:14px"><button class="btn" type="submit"><i class="bi bi-upload"></i> Unggah foto</button></p></form></div>
  </div>
</div>
<?php else: ?>
  <?php if (!$photos): ?><div class="card empty"><i class="bi bi-images"></i><?= ($search || $mine) ? 'Tidak ada foto yang cocok.' : 'Belum ada foto yang ditampilkan.' ?></div>
  <?php else: ?><div class="photos"><?php foreach ($photos as $ph): ?>
   <a class="photo" href="<?= e(url('pages/photo.php?id=' . (int)$ph['id'])) ?>"><img loading="lazy" src="<?= e(url('uploads/photos/' . $ph['filename'])) ?>" alt="<?= e($ph['title']) ?>"><div><strong><?= e($ph['title']) ?></strong><br><small><?= e($ph['name']) ?> · <?= e(fmt_date($ph['created_at'])) ?></small></div></a>
  <?php endforeach; ?></div><?= pager_html($pages, $page) ?><?php endif; ?>
  <div class="card" style="margin-top:18px"><a class="btn" href="<?= e(url('login.php')) ?>">Login untuk mengunggah foto</a></div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
