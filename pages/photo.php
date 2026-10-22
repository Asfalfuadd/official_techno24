<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$publicPage = true; $u = current_user();
$id = (int)($_GET['id'] ?? 0);
$ph = q('SELECT p.*, u.name FROM photos p JOIN users u ON u.id = p.user_id WHERE p.id = ?', [$id])->fetch();
if (!$ph) { http_response_code(404); exit('Foto tidak ditemukan.'); }
if (!$u && !$ph['is_public']) redirect('login.php'); // foto non-publik hanya untuk anggota
$canManage = $u && (is_admin() || (int)$ph['user_id'] === (int)$u['id']); // cegah IDOR: pemilik atau admin

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (!$canManage) forbidden();
    $act = $_POST['action'] ?? '';
    if ($act === 'edit') {
        $title = trim((string)($_POST['title'] ?? ''));
        if ($title === '' || mb_strlen($title) > 150) flash('err', 'Judul wajib diisi.');
        else { q('UPDATE photos SET title = ?, caption = ?, is_public = ? WHERE id = ?', [$title, mb_substr(trim((string)($_POST['caption'] ?? '')), 0, 500) ?: null, isset($_POST['is_public']) ? 1 : 0, $id]); audit('photo_edit', "id=$id"); flash('ok', 'Keterangan diperbarui.'); }
        redirect('pages/photo.php?id=' . $id);
    }
    if ($act === 'delete') {
        q('DELETE FROM photos WHERE id = ?', [$id]);        // relasi album ikut terhapus (CASCADE), sampul menjadi NULL
        delete_upload('photos', $ph['filename']);            // hapus file fisik
        audit('photo_delete', "id=$id by " . $u['id']); flash('ok', 'Foto dihapus.'); redirect('pages/gallery.php');
    }
}
$pub = $u ? '' : ' AND is_public = 1';
$prev = q('SELECT id FROM photos WHERE (created_at > ? OR (created_at = ? AND id > ?))' . $pub . ' ORDER BY created_at ASC, id ASC LIMIT 1', [$ph['created_at'], $ph['created_at'], $id])->fetchColumn();
$nextp = q('SELECT id FROM photos WHERE (created_at < ? OR (created_at = ? AND id < ?))' . $pub . ' ORDER BY created_at DESC, id DESC LIMIT 1', [$ph['created_at'], $ph['created_at'], $id])->fetchColumn();
$albums = q('SELECT a.id, a.title FROM albums a JOIN album_photos ap ON ap.album_id = a.id WHERE ap.photo_id = ?' . ($u ? '' : ' AND a.is_public = 1'), [$id])->fetchAll();
$pageTitle = $ph['title']; $active = 'gallery';
require __DIR__ . '/../includes/header.php';
?>
<p><a href="<?= e(url('pages/gallery.php')) ?>">&larr; Kembali ke galeri</a></p>
<div class="card"><img src="<?= e(url('uploads/photos/' . $ph['filename'])) ?>" alt="<?= e($ph['title']) ?>" style="width:100%;max-height:70vh;object-fit:contain;background:#0b1730;border-radius:10px">
 <h1 style="margin-top:12px"><?= e($ph['title']) ?></h1>
 <p class="muted">Diunggah oleh <?= e($ph['name']) ?> · <?= e(fmt_date($ph['created_at'], true)) ?></p>
 <?php if ($ph['caption']): ?><p><?= nl2br(e($ph['caption'])) ?></p><?php endif; ?>
 <?php if ($albums): ?><p class="muted">Album: <?php foreach ($albums as $a): ?><a href="<?= e(url('pages/albums.php?id=' . (int)$a['id'])) ?>"><?= e($a['title']) ?></a> <?php endforeach; ?></p><?php endif; ?>
 <div class="row" style="flex-wrap:nowrap"><?php if ($prev): ?><a class="btn btn-ghost" style="flex:none" href="?id=<?= (int)$prev ?>">&larr; Lebih baru</a><?php endif; ?><?php if ($nextp): ?><a class="btn btn-ghost" style="flex:none" href="?id=<?= (int)$nextp ?>">Lebih lama &rarr;</a><?php endif; ?></div></div>
<?php if ($canManage): ?>
<div class="card"><h2>Kelola foto</h2>
 <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="edit">
  <label>Judul</label><input name="title" required maxlength="150" value="<?= e($ph['title']) ?>">
  <label>Keterangan</label><textarea name="caption" maxlength="500" style="min-height:70px"><?= e($ph['caption']) ?></textarea>
  <label><input type="checkbox" name="is_public" value="1" <?= $ph['is_public'] ? 'checked' : '' ?>> Tampilkan ke pengunjung publik</label>
  <p><button class="btn" type="submit">Simpan</button></p></form>
 <form method="post" data-confirm="Hapus foto ini secara permanen?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><button class="btn btn-danger" type="submit"><i class="bi bi-trash"></i> Hapus foto</button></form></div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
