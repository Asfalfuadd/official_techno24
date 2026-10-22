<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$u = require_login();
$edit = null; $view = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $act = $_POST['action'] ?? ''; $id = (int)($_POST['id'] ?? 0);
    if ($act === 'delete') {
        $d = q('SELECT user_id FROM diaries WHERE id = ?', [$id])->fetch();
        if (!$d || (int)$d['user_id'] !== (int)$u['id']) forbidden();   // hanya penulis yang boleh menghapus
        q('DELETE FROM diaries WHERE id = ?', [$id]); flash('ok', 'Tulisan dihapus.'); redirect('pages/diaries.php');
    }
    if ($act === 'save') {
        $t = trim((string)($_POST['title'] ?? '')); $c = trim((string)($_POST['content'] ?? ''));
        $priv = ($_POST['privacy'] ?? '') === 'kelas' ? 'kelas' : 'pribadi'; $draft = isset($_POST['draft']) ? 1 : 0;
        if ($id) { $d = q('SELECT user_id FROM diaries WHERE id = ?', [$id])->fetch(); if (!$d || (int)$d['user_id'] !== (int)$u['id']) forbidden(); }
        if ($t === '' || mb_strlen($t) > 150 || $c === '' || mb_strlen($c) > 20000) { flash('err', 'Judul dan isi wajib diisi (isi maks. 20.000 karakter).'); $edit = array_merge(['id' => $id], $_POST); }
        else {
            if ($id) q('UPDATE diaries SET title=?, content=?, privacy=?, is_draft=? WHERE id=?', [$t, $c, $priv, $draft, $id]);
            else q('INSERT INTO diaries (user_id, title, content, privacy, is_draft) VALUES (?,?,?,?,?)', [$u['id'], $t, $c, $priv, $draft]);
            flash('ok', $draft ? 'Draft disimpan.' : 'Tulisan disimpan.'); redirect('pages/diaries.php');
        }
    }
}
// Aturan akses dibaca di server: pemilik, atau (dibagikan ke kelas DAN bukan draft). Admin tidak otomatis membaca diary pribadi.
$canRead = "(d.user_id = ? OR (d.privacy = 'kelas' AND d.is_draft = 0))";
if (isset($_GET['id'])) {
    $view = q("SELECT d.*, u.name FROM diaries d JOIN users u ON u.id = d.user_id WHERE d.id = ? AND $canRead", [(int)$_GET['id'], $u['id']])->fetch();
    if (!$view) { http_response_code(404); exit('Tulisan tidak ditemukan.'); }
}
if (!$edit && isset($_GET['edit'])) $edit = q('SELECT * FROM diaries WHERE id = ? AND user_id = ?', [(int)$_GET['edit'], $u['id']])->fetch() ?: null;
$w = $canRead; $p = [$u['id']];
if (!empty($_GET['author'])) { $w .= ' AND d.user_id = ?'; $p[] = (int)$_GET['author']; }
if (!empty($_GET['date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date'])) { $w .= ' AND DATE(d.created_at) = ?'; $p[] = $_GET['date']; }
$total = (int)q("SELECT COUNT(*) FROM diaries d WHERE $w", $p)->fetchColumn();
[$off, $lim, $pages, $page] = paginate($total, 10);
$rows = q("SELECT d.id, d.user_id, d.title, d.privacy, d.is_draft, d.created_at, LEFT(d.content, 160) AS snip, u.name FROM diaries d JOIN users u ON u.id = d.user_id WHERE $w ORDER BY d.created_at DESC LIMIT $lim OFFSET $off", $p)->fetchAll();
$authors = q('SELECT id, name FROM users WHERE is_active = 1 ORDER BY name')->fetchAll();
$pageTitle = 'Diary kelas'; $active = 'diaries';
require __DIR__ . '/../includes/header.php';
$v = fn($k, $d = '') => e($edit[$k] ?? $d);
if ($view): ?>
 <p><a href="<?= e(url('pages/diaries.php')) ?>">&larr; Kembali</a></p>
 <div class="card"><h1><?= e($view['title']) ?></h1><p class="muted"><?= e($view['name']) ?> · <?= e(fmt_date($view['created_at'], true)) ?> · <?= $view['privacy'] === 'kelas' ? 'Dibagikan' : 'Pribadi' ?><?= $view['is_draft'] ? ' · Draft' : '' ?></p>
 <div style="line-height:1.7"><?= nl2br(e($view['content'])) ?></div>
 <?php if ((int)$view['user_id'] === (int)$u['id']): ?><p><a class="btn btn-ghost btn-sm" href="?edit=<?= (int)$view['id'] ?>">Edit</a></p><?php endif; ?></div>
<?php else: ?>
<section class="card" style="background:linear-gradient(160deg,var(--navy),var(--navy2));color:#fff">
  <div class="row" style="align-items:center;gap:20px;flex-wrap:wrap">
    <div style="flex:none;width:64px;height:64px;border-radius:14px;background:rgba(255,255,255,0.15);display:flex;align-items:center;justify-content:center;font-size:32px">
      <i class="bi bi-journal-text"></i>
    </div>
    <div style="flex:1;min-width:280px">
      <h1 style="color:#fff;font-size:26px;margin:0 0 6px">Diary Kelas</h1>
      <p style="margin:0 0 6px;color:#e2e8f0;font-size:14px">Cerita, kesan, dan kenangan selama perkuliahan bersama TECHNO 24.</p>
      <div class="row" style="gap:8px;align-items:center;margin:0;flex-wrap:wrap">
        <span class="tag" style="background:rgba(255,255,255,0.2);color:#fff"><?= $total ?> Total Tulisan</span>
      </div>
    </div>
    <div style="flex:none">
      <a class="btn" style="background:#fff;color:var(--navy);font-weight:600" href="#form"><i class="bi bi-pencil"></i> Tulis Diary</a>
    </div>
  </div>
</section>
<form class="card row" method="get"><div><select name="author"><option value="">Semua penulis</option><?php foreach ($authors as $a): ?><option value="<?= (int)$a['id'] ?>" <?= (int)($_GET['author'] ?? 0) === (int)$a['id'] ? 'selected' : '' ?>><?= e($a['name']) ?></option><?php endforeach; ?></select></div>
 <div><input type="date" name="date" value="<?= e($_GET['date'] ?? '') ?>"></div><button class="btn" style="flex:none" type="submit">Filter</button></form>
<?php if (!$rows): ?><div class="card empty"><i class="bi bi-journal-text"></i>Belum ada tulisan.</div><?php endif; ?>
<?php foreach ($rows as $d): ?><div class="card"><div class="row" style="align-items:center"><h2 style="margin:0;flex:3"><a href="?id=<?= (int)$d['id'] ?>" style="color:inherit"><?= e($d['title']) ?></a></h2>
 <div style="flex:none"><span class="tag <?= $d['privacy'] === 'kelas' ? 'tag-ok' : 'tag-warn' ?>"><?= $d['privacy'] === 'kelas' ? 'Dibagikan' : 'Pribadi' ?></span><?= $d['is_draft'] ? ' <span class="tag">Draft</span>' : '' ?></div></div>
 <p class="muted"><?= e($d['name']) ?> · <?= e(fmt_date($d['created_at'])) ?></p><p><?= e($d['snip']) ?>...</p>
 <?php if ((int)$d['user_id'] === (int)$u['id']): ?><div class="row" style="flex-wrap:nowrap"><a class="btn btn-ghost btn-sm" style="flex:none" href="?edit=<?= (int)$d['id'] ?>">Edit</a>
  <form method="post" data-confirm="Hapus tulisan ini?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$d['id'] ?>"><button class="btn btn-danger btn-sm" type="submit">Hapus</button></form></div><?php endif; ?></div>
<?php endforeach; ?><?= pager_html($pages, $page) ?>
<div class="card" id="form"><h2><?= $edit ? 'Edit tulisan' : 'Tulis diary' ?></h2><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
 <label>Judul</label><input name="title" required maxlength="150" value="<?= $v('title') ?>">
 <label>Isi</label><textarea name="content" required style="min-height:200px"><?= $v('content') ?></textarea>
 <label>Privasi</label><select name="privacy"><option value="pribadi" <?= ($edit['privacy'] ?? 'pribadi') === 'pribadi' ? 'selected' : '' ?>>Pribadi</option><option value="kelas" <?= ($edit['privacy'] ?? '') === 'kelas' ? 'selected' : '' ?>>Dibagikan kepada anggota kelas</option></select>
 <label><input type="checkbox" name="draft" <?= !empty($edit['is_draft']) ? 'checked' : '' ?>> Simpan sebagai draft</label>
 <p><button class="btn" type="submit">Simpan</button> <?php if ($edit): ?><a class="btn btn-ghost" href="?">Batal</a><?php endif; ?></p></form></div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
