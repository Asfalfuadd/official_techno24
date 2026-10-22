<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$publicPage = true; $u = current_user();
const AG_STATUS = ['ide' => 'Ide', 'direncanakan' => 'Direncanakan', 'menunggu_konfirmasi' => 'Menunggu konfirmasi', 'dikonfirmasi' => 'Dikonfirmasi', 'berlangsung' => 'Sedang berlangsung', 'selesai' => 'Selesai', 'dibatalkan' => 'Dibatalkan'];
$id = (int)($_GET['id'] ?? 0); $edit = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$u) redirect('login.php');
    csrf_check();
    $act = $_POST['action'] ?? ''; $aid = (int)($_POST['id'] ?? 0);
    $ag = $aid ? q('SELECT * FROM agendas WHERE id = ?', [$aid])->fetch() : null;
    $canManage = $ag && (is_admin() || (int)$ag['created_by'] === (int)$u['id']);
    if ($act === 'rsvp') {
        $r = ($_POST['response'] ?? '') === 'ikut' ? 'ikut' : 'tidak';
        if (!$ag || in_array($ag['status'], ['selesai', 'dibatalkan'], true)) flash('err', 'Agenda tidak dapat direspons.');
        else { // satu respons aktif per agenda (PRIMARY KEY agenda_id,user_id)
            q('INSERT INTO agenda_participants (agenda_id, user_id, response) VALUES (?,?,?) ON DUPLICATE KEY UPDATE response = VALUES(response), responded_at = ?', [$aid, $u['id'], $r, now()]);
            flash('ok', 'Respons Anda disimpan.');
        }
        redirect('pages/agendas.php?id=' . $aid);
    }
    if ($act === 'delete') { if (!$canManage) forbidden(); q('DELETE FROM agendas WHERE id = ?', [$aid]); audit('agenda_delete', "id=$aid"); flash('ok', 'Agenda dihapus.'); redirect('pages/agendas.php'); }
    if ($act === 'save') {
        if ($aid && !$canManage) forbidden();
        $t = trim((string)($_POST['title'] ?? ''));
        $dt = trim((string)($_POST['event_date'] ?? '')); $dt = $dt !== '' ? str_replace('T', ' ', $dt) . ':00' : null;
        $cost = parse_money($_POST['cost'] ?? '0') ?? '0.00'; $target = max(0, min(500, (int)($_POST['target'] ?? 0)));
        $st = (string)($_POST['status'] ?? 'ide');
        if (!is_admin() || !isset(AG_STATUS[$st])) $st = $ag['status'] ?? 'ide'; // hanya admin boleh mengubah status
        if ($t === '' || mb_strlen($t) > 150) { flash('err', 'Nama kegiatan wajib diisi.'); $edit = array_merge(['id' => $aid], $_POST); }
        elseif ($dt !== null && !strtotime($dt)) { flash('err', 'Tanggal tidak valid.'); $edit = array_merge(['id' => $aid], $_POST); }
        else {
            $vals = [$t, trim((string)($_POST['description'] ?? '')) ?: null, trim((string)($_POST['location'] ?? '')) ?: null, $dt, $cost, $target, $st,
                     trim((string)($_POST['equipment'] ?? '')) ?: null, trim((string)($_POST['notes'] ?? '')) ?: null, trim((string)($_POST['budget_notes'] ?? '')) ?: null];
            if ($aid) {
                q('UPDATE agendas SET title=?, description=?, location=?, event_date=?, cost_per_member=?, target_participants=?, status=?, equipment=?, notes=?, budget_notes=? WHERE id=?', array_merge($vals, [$aid]));
                if ($ag['status'] !== $st && $st === 'dikonfirmasi') notify_all('Kegiatan dikonfirmasi: ' . $t, fmt_date($dt, true), 'pages/agendas.php?id=' . $aid, (int)$u['id']);
            } else {
                q('INSERT INTO agendas (title, description, location, event_date, cost_per_member, target_participants, status, equipment, notes, budget_notes, created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?)', array_merge($vals, [$u['id']]));
                $aid = (int)db()->lastInsertId(); notify_all('Agenda baru: ' . $t, 'Ayo beri respons ikut atau tidak.', 'pages/agendas.php?id=' . $aid, (int)$u['id']);
            }
            audit('agenda_save', $t); flash('ok', 'Agenda disimpan.'); redirect('pages/agendas.php?id=' . $aid);
        }
    }
}
if (!$edit && isset($_GET['edit'])) { $e0 = q('SELECT *, DATE_FORMAT(event_date, "%Y-%m-%dT%H:%i") AS event_date FROM agendas WHERE id = ?', [(int)$_GET['edit']])->fetch();
  if ($e0 && $u && (is_admin() || (int)$e0['created_by'] === (int)$u['id'])) { $edit = $e0; $edit['cost'] = (int)$e0['cost_per_member']; $edit['target'] = $e0['target_participants']; } }
$pageTitle = 'Agenda & liburan'; $active = 'agendas';
$v = fn($k, $d = '') => e($edit[$k] ?? $d);

if ($id) {
    $ag = q('SELECT a.*, u.name AS creator FROM agendas a LEFT JOIN users u ON u.id = a.created_by WHERE a.id = ?', [$id])->fetch();
    if (!$ag) { http_response_code(404); exit('Agenda tidak ditemukan.'); }
    $parts = q('SELECT p.response, u.name FROM agenda_participants p JOIN users u ON u.id = p.user_id WHERE p.agenda_id = ? ORDER BY u.name', [$id])->fetchAll();
    $going = array_filter($parts, fn($p) => $p['response'] === 'ikut'); $mine = null;
    $m = $u ? q('SELECT response FROM agenda_participants WHERE agenda_id = ? AND user_id = ?', [$id, $u['id']])->fetchColumn() : null; $mine = $m ?: null;
    $canManage = $u && (is_admin() || (int)$ag['created_by'] === (int)$u['id']);
}
require __DIR__ . '/../includes/header.php';

if ($id): ?>
<p><a href="<?= e(url('pages/agendas.php')) ?>">&larr; Semua agenda</a></p>
<div class="grid g-desktop-split">
  <div>
    <div class="card">
      <div class="row" style="align-items:center"><h1 style="flex:3"><?= e($ag['title']) ?></h1><span class="tag tag-blue" style="flex:none"><?= e(AG_STATUS[$ag['status']]) ?></span></div>
      <p class="muted"><?= e(fmt_date($ag['event_date'], true)) ?> · <?= e($ag['location'] ?: 'Lokasi belum ditentukan') ?> · Dibuat oleh <?= e($ag['creator'] ?: '-') ?></p>
      <?php if ($ag['description']): ?><p style="line-height:1.6"><?= nl2br(e($ag['description'])) ?></p><?php endif; ?>
      <p>Estimasi biaya per anggota: <strong><?= e(rupiah($ag['cost_per_member'])) ?></strong> · Peserta: <strong><?= count($going) ?></strong><?= $ag['target_participants'] ? ' / target ' . (int)$ag['target_participants'] : '' ?></p>
      <?php foreach (['equipment' => 'Perlengkapan', 'budget_notes' => 'Catatan anggaran', 'notes' => 'Catatan tambahan'] as $k => $lbl): if ($ag[$k]): ?><div style="margin-top:12px"><strong><?= $lbl ?></strong><p style="margin:4px 0"><?= nl2br(e($ag[$k])) ?></p></div><?php endif; endforeach; ?>
    </div>
  </div>
  <div>
    <div class="card sticky-card">
      <h2>Respons kehadiran</h2>
      <?php if (!$u): ?>
        <p><a class="btn" href="<?= e(url('login.php')) ?>">Login untuk ikut agenda</a></p>
      <?php elseif (!in_array($ag['status'], ['selesai', 'dibatalkan'], true)): ?>
        <form method="post" class="row" style="margin-bottom:16px"><?= csrf_field() ?><input type="hidden" name="action" value="rsvp"><input type="hidden" name="id" value="<?= $id ?>">
          <button class="btn <?= $mine === 'ikut' ? '' : 'btn-ghost' ?>" style="flex:1" name="response" value="ikut" type="submit"><i class="bi bi-check-circle"></i> Ikut</button>
          <button class="btn <?= $mine === 'tidak' ? 'btn-danger' : 'btn-ghost' ?>" style="flex:1" name="response" value="tidak" type="submit"><i class="bi bi-x-circle"></i> Tidak</button>
        </form>
      <?php endif; ?>
      <h3 style="font-size:15px;margin:14px 0 8px">Peserta terdaftar (<?= count($going) ?>)</h3>
      <?php if (!$u): ?><p class="muted">Daftar nama peserta hanya terlihat setelah login.</p>
      <?php elseif (!$parts): ?><p class="muted">Belum ada respons.</p>
      <?php else: ?><div style="display:flex;flex-wrap:wrap;gap:4px"><?php foreach ($parts as $pt): ?><span class="tag <?= $pt['response'] === 'ikut' ? 'tag-ok' : 'tag-bad' ?>"><?= e($pt['name']) ?></span><?php endforeach; ?></div><?php endif; ?>
      <p class="muted" style="margin-top:14px;font-size:12px">Ingin voting tanggal atau destinasi? Diskusikan di grup kelas.</p>
      <?php if ($canManage): ?>
        <div class="row" style="margin-top:16px;padding-top:14px;border-top:1px solid var(--line)">
          <a class="btn btn-ghost btn-sm" href="?edit=<?= $id ?>"><i class="bi bi-pencil"></i> Edit</a>
          <form method="post" data-confirm="Hapus agenda ini?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $id ?>"><button class="btn btn-danger btn-sm" type="submit"><i class="bi bi-trash"></i> Hapus</button></form>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php
endif;
if (!$id || $edit):
  $tab = $_GET['tab'] ?? 'akan';
  if (!$id) {
    $rows = $tab === 'riwayat' ? q("SELECT a.*, (SELECT COUNT(*) FROM agenda_participants p WHERE p.agenda_id = a.id AND p.response='ikut') AS going FROM agendas a WHERE status IN ('selesai','dibatalkan') ORDER BY event_date DESC LIMIT 50")->fetchAll()
      : q("SELECT a.*, (SELECT COUNT(*) FROM agenda_participants p WHERE p.agenda_id = a.id AND p.response='ikut') AS going FROM agendas a WHERE status NOT IN ('selesai','dibatalkan') ORDER BY event_date IS NULL, event_date LIMIT 50")->fetchAll();
?>
<section class="card" style="background:linear-gradient(160deg,var(--navy),var(--navy2));color:#fff">
  <div class="row" style="align-items:center;gap:20px;flex-wrap:wrap">
    <div style="flex:none;width:64px;height:64px;border-radius:14px;background:rgba(255,255,255,0.15);display:flex;align-items:center;justify-content:center;font-size:32px">
      <i class="bi bi-airplane"></i>
    </div>
    <div style="flex:1;min-width:280px">
      <h1 style="color:#fff;font-size:26px;margin:0 0 6px">Agenda & Liburan</h1>
      <p style="margin:0 0 6px;color:#e2e8f0;font-size:14px">Rencana kegiatan, event seru, dan jadwal liburan bersama TECHNO 24.</p>
      <div class="row" style="gap:8px;align-items:center;margin:0;flex-wrap:wrap">
        <span class="tag" style="background:rgba(255,255,255,0.2);color:#fff"><?= count($rows) ?> Agenda <?= $tab === 'riwayat' ? 'Selesai' : 'Aktif' ?></span>
      </div>
    </div>
    <?php if ($u): ?>
      <div style="flex:none">
        <a class="btn" style="background:#fff;color:var(--navy);font-weight:600" href="#form-agenda"><i class="bi bi-plus-circle"></i> Usulkan Agenda</a>
      </div>
    <?php endif; ?>
  </div>
</section>
<div class="row" style="margin-bottom:14px"><a class="btn btn-sm <?= $tab === 'riwayat' ? 'btn-ghost' : '' ?>" style="flex:none" href="?">Akan datang</a><a class="btn btn-sm <?= $tab === 'riwayat' ? '' : 'btn-ghost' ?>" style="flex:none" href="?tab=riwayat">Riwayat</a></div>
<?php if (!$rows): ?><div class="card empty"><i class="bi bi-calendar-event"></i>Belum ada agenda di sini.</div><?php else: ?>
<div class="g-cards">
  <?php foreach ($rows as $a): ?>
    <a class="card" style="display:block;color:inherit;transition:transform .15s ease,box-shadow .15s ease" href="?id=<?= (int)$a['id'] ?>">
      <div class="row" style="align-items:center"><h2 style="margin:0;flex:3"><?= e($a['title']) ?></h2><span class="tag tag-blue" style="flex:none"><?= e(AG_STATUS[$a['status']]) ?></span></div>
      <p class="muted"><?= e(fmt_date($a['event_date'], true)) ?> · <?= e($a['location'] ?: '-') ?></p>
      <div class="muted"><i class="bi bi-people"></i> <?= (int)$a['going'] ?><?= $a['target_participants'] ? ' / ' . (int)$a['target_participants'] : '' ?> peserta ikut</div>
    </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>
<?php } ?>
<?php if ($u): ?><div class="card" id="form-agenda"><h2><?= $edit ? 'Edit agenda' : 'Usulkan agenda' ?></h2><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
 <label>Nama kegiatan</label><input name="title" required maxlength="150" value="<?= $v('title') ?>">
 <div class="row"><div><label>Lokasi</label><input name="location" maxlength="150" value="<?= $v('location') ?>"></div><div><label>Tanggal dan jam</label><input type="datetime-local" name="event_date" value="<?= $v('event_date') ?>"></div></div>
 <div class="row"><div><label>Estimasi biaya per anggota (Rp)</label><input name="cost" inputmode="numeric" value="<?= $v('cost', '0') ?>"></div><div><label>Target peserta</label><input type="number" min="0" max="500" name="target" value="<?= $v('target', '0') ?>"></div>
 <?php if (is_admin()): ?><div><label>Status</label><select name="status"><?php foreach (AG_STATUS as $k => $l): ?><option value="<?= $k ?>" <?= ($edit['status'] ?? 'ide') === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></div><?php endif; ?></div>
 <label>Deskripsi</label><textarea name="description" style="min-height:80px"><?= $v('description') ?></textarea>
 <label>Daftar perlengkapan</label><textarea name="equipment" style="min-height:60px"><?= $v('equipment') ?></textarea>
 <label>Catatan anggaran</label><textarea name="budget_notes" style="min-height:60px"><?= $v('budget_notes') ?></textarea>
 <label>Catatan tambahan</label><textarea name="notes" style="min-height:60px"><?= $v('notes') ?></textarea>
 <p><button class="btn" type="submit">Simpan</button> <?php if ($edit): ?><a class="btn btn-ghost" href="?id=<?= (int)$edit['id'] ?>">Batal</a><?php endif; ?></p></form></div><?php endif; ?>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
