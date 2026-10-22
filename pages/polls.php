<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$u = require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $act = $_POST['action'] ?? '';
    if ($act === 'vote') {
        $pid = (int)($_POST['poll_id'] ?? 0); $oid = (int)($_POST['option_id'] ?? 0);
        $poll = q('SELECT * FROM polls WHERE id = ?', [$pid])->fetch();
        $okOpt = $poll && q('SELECT 1 FROM poll_options WHERE id = ? AND poll_id = ?', [$oid, $pid])->fetchColumn();
        $open = $poll && !$poll['is_closed'] && $poll['starts_at'] <= now() && $poll['ends_at'] > now(); // validasi waktu di server
        if (!$okOpt || !$open) flash('err', 'Polling tidak aktif atau pilihan tidak valid.');
        else {
            try { q('INSERT INTO poll_votes (poll_id, option_id, user_id) VALUES (?,?,?)', [$pid, $oid, $u['id']]); flash('ok', 'Suara Anda tercatat.'); }
            catch (PDOException $ex) { flash('err', 'Anda sudah memberikan suara pada polling ini.'); } // UNIQUE(poll_id,user_id) di database
        }
        redirect('pages/polls.php');
    }
    if (!is_admin()) forbidden();
    if ($act === 'create') {
        $t = trim((string)($_POST['title'] ?? '')); $opts = array_values(array_filter(array_map('trim', explode("\n", (string)($_POST['options'] ?? '')))));
        $opts = array_values(array_unique(array_map(fn($o) => mb_substr($o, 0, 150), $opts)));
        $s = str_replace('T', ' ', (string)($_POST['starts_at'] ?? '')) . ':00'; $en = str_replace('T', ' ', (string)($_POST['ends_at'] ?? '')) . ':00';
        $sr = (string)($_POST['show_results'] ?? ''); if (!in_array($sr, ['selalu', 'setelah_memilih', 'setelah_selesai'], true)) $sr = 'setelah_memilih';
        if ($t === '' || count($opts) < 2 || count($opts) > 12) flash('err', 'Judul wajib diisi dan butuh 2-12 pilihan (satu per baris).');
        elseif (!strtotime($s) || !strtotime($en) || strtotime($en) <= strtotime($s)) flash('err', 'Waktu mulai/berakhir tidak valid.');
        else {
            $pdo = db(); $pdo->beginTransaction();
            try {
                q('INSERT INTO polls (title, description, starts_at, ends_at, show_results, created_by) VALUES (?,?,?,?,?,?)', [$t, mb_substr(trim((string)($_POST['description'] ?? '')), 0, 500) ?: null, $s, $en, $sr, $u['id']]);
                $pid = (int)$pdo->lastInsertId();
                foreach ($opts as $o) q('INSERT INTO poll_options (poll_id, label) VALUES (?,?)', [$pid, $o]);
                $pdo->commit();
                notify_all('Polling baru: ' . $t, 'Berikan suara Anda.', 'pages/polls.php', (int)$u['id']); audit('poll_create', $t); flash('ok', 'Polling dibuat.');
            } catch (Throwable $ex) { $pdo->rollBack(); error_log($ex->getMessage()); flash('err', 'Gagal membuat polling.'); }
        }
    } elseif ($act === 'close') { q('UPDATE polls SET is_closed = 1 WHERE id = ?', [(int)($_POST['id'] ?? 0)]); flash('ok', 'Polling ditutup.'); }
    elseif ($act === 'delete') { q('DELETE FROM polls WHERE id = ?', [(int)($_POST['id'] ?? 0)]); audit('poll_delete', 'id=' . (int)($_POST['id'] ?? 0)); flash('ok', 'Polling dihapus.'); }
    redirect('pages/polls.php');
}
$polls = q('SELECT * FROM polls ORDER BY ends_at DESC LIMIT 30')->fetchAll();
$pageTitle = 'Polling'; $active = 'polls';
require __DIR__ . '/../includes/header.php';
?>
<section class="card" style="background:linear-gradient(160deg,var(--navy),var(--navy2));color:#fff">
  <div class="row" style="align-items:center;gap:20px;flex-wrap:wrap">
    <div style="flex:none;width:64px;height:64px;border-radius:14px;background:rgba(255,255,255,0.15);display:flex;align-items:center;justify-content:center;font-size:32px">
      <i class="bi bi-bar-chart-fill"></i>
    </div>
    <div style="flex:1;min-width:280px">
      <h1 style="color:#fff;font-size:26px;margin:0 0 6px">Polling & Voting</h1>
      <p style="margin:0 0 6px;color:#e2e8f0;font-size:14px">Satu suara per anggota untuk keputusan bersama TECHNO 24.</p>
      <div class="row" style="gap:8px;align-items:center;margin:0;flex-wrap:wrap">
        <span class="tag" style="background:rgba(255,255,255,0.2);color:#fff"><?= count($polls) ?> Total Polling</span>
      </div>
    </div>
    <?php if (is_admin()): ?>
      <div style="flex:none">
        <a class="btn" style="background:#fff;color:var(--navy);font-weight:600" href="#form-poll"><i class="bi bi-plus-circle"></i> Buat Polling</a>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php if (!$polls): ?><div class="card empty"><i class="bi bi-bar-chart"></i>Belum ada polling.</div><?php endif; ?>
<?php foreach ($polls as $p):
  $isOpen = !$p['is_closed'] && $p['starts_at'] <= now() && $p['ends_at'] > now();
  $opts = q('SELECT o.id, o.label, (SELECT COUNT(*) FROM poll_votes v WHERE v.option_id = o.id) AS c FROM poll_options o WHERE o.poll_id = ? ORDER BY o.id', [$p['id']])->fetchAll();
  $mine = q('SELECT option_id FROM poll_votes WHERE poll_id = ? AND user_id = ?', [$p['id'], $u['id']])->fetchColumn();
  $totalV = array_sum(array_column($opts, 'c'));
  $showRes = is_admin() || $p['show_results'] === 'selalu' || ($p['show_results'] === 'setelah_memilih' && $mine) || !$isOpen;
  if ($p['show_results'] === 'setelah_selesai' && $isOpen && !is_admin()) $showRes = false;
?>
 <div class="card"><div class="row" style="align-items:center"><h2 style="margin:0;flex:3"><?= e($p['title']) ?></h2><span class="tag <?= $isOpen ? 'tag-ok' : '' ?>" style="flex:none"><?= $isOpen ? 'Aktif' : 'Ditutup' ?></span></div>
 <p class="muted"><?= e($p['description']) ?> Berakhir <?= e(fmt_date($p['ends_at'], true)) ?>.</p>
 <?php if ($isOpen && !$mine): ?>
  <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="vote"><input type="hidden" name="poll_id" value="<?= (int)$p['id'] ?>">
  <?php foreach ($opts as $o): ?><label style="font-weight:400"><input type="radio" name="option_id" value="<?= (int)$o['id'] ?>" required> <?= e($o['label']) ?></label><?php endforeach; ?>
  <p><button class="btn" type="submit">Kirim suara</button></p></form>
 <?php endif; ?>
 <?php if ($showRes): foreach ($opts as $o): $pc = $totalV ? round($o['c'] * 100 / $totalV) : 0; ?>
  <div style="margin:8px 0"><div class="row" style="flex-wrap:nowrap"><span><?= e($o['label']) ?> <?= (int)$mine === (int)$o['id'] ? '<span class="tag tag-blue">pilihan Anda</span>' : '' ?></span><span class="muted" style="flex:none;text-align:right"><?= (int)$o['c'] ?> suara (<?= $pc ?>%)</span></div><div class="bar"><i style="width:<?= $pc ?>%"></i></div></div>
 <?php endforeach; ?><p class="muted"><?= $totalV ?> suara masuk.</p>
 <?php elseif ($mine): ?><p class="muted">Suara Anda tercatat. Hasil tampil setelah polling selesai.</p><?php endif; ?>
 <?php if (is_admin()): ?><div class="row" style="flex-wrap:nowrap"><?php if ($isOpen): ?><form method="post" data-confirm="Tutup polling sekarang?"><?= csrf_field() ?><input type="hidden" name="action" value="close"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><button class="btn btn-ghost btn-sm" type="submit">Tutup</button></form><?php endif; ?>
 <form method="post" data-confirm="Hapus polling beserta suaranya?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><button class="btn btn-danger btn-sm" type="submit">Hapus</button></form></div><?php endif; ?></div>
<?php endforeach; ?>
<?php if (is_admin()): ?><div class="card"><h2>Buat polling</h2><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="create">
 <label>Judul</label><input name="title" required maxlength="150"><label>Deskripsi</label><input name="description" maxlength="500">
 <label>Pilihan (satu per baris, minimal 2)</label><textarea name="options" required></textarea>
 <div class="row"><div><label>Mulai</label><input type="datetime-local" name="starts_at" required value="<?= e(date('Y-m-d\TH:i')) ?>"></div><div><label>Berakhir</label><input type="datetime-local" name="ends_at" required></div>
 <div><label>Tampilkan hasil</label><select name="show_results"><option value="setelah_memilih">Setelah memilih</option><option value="selalu">Selalu</option><option value="setelah_selesai">Setelah polling selesai</option></select></div></div>
 <p><button class="btn" type="submit">Buat polling</button></p></form></div><?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
