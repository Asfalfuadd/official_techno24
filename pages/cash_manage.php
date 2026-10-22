<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$u = require_treasurer(); // admin atau bendahara (dicek di server)

// Buat transaksi pemasukan dari pembayaran terverifikasi, atomik dalam satu database transaction.
function verify_payment(int $payId, array $u): string {
    $pdo = db(); $pdo->beginTransaction();
    try {
        $p = q('SELECT p.*, cp.label FROM cash_payments p JOIN cash_periods cp ON cp.id = p.period_id WHERE p.id = ? FOR UPDATE', [$payId])->fetch();
        if (!$p || $p['status'] !== 'pending') { $pdo->rollBack(); return 'Pembayaran tidak dalam status menunggu.'; }
        if ((int)$p['user_id'] === (int)$u['id'] && $u['role'] !== 'admin') { $pdo->rollBack(); return 'Anda tidak dapat memverifikasi pembayaran Anda sendiri.'; }
        $cat = (int)q("SELECT id FROM cash_categories WHERE name = 'Uang kas' AND type = 'pemasukan'")->fetchColumn() ?: null;
        $name = q('SELECT name FROM users WHERE id = ?', [$p['user_id']])->fetchColumn();
        q("UPDATE cash_payments SET status='verified', verified_by=?, verified_at=?, verify_note=NULL WHERE id=?", [$u['id'], now(), $payId]);
        q("INSERT INTO cash_transactions (type, amount, tx_date, category_id, description, created_by, status, verified_by, verified_at, payment_id) VALUES ('pemasukan',?,?,?,?,?, 'verified',?,?,?)",
          [$p['amount'], $p['paid_at'], $cat, 'Kas ' . $p['label'] . ' - ' . $name, $u['id'], $u['id'], now(), $payId]);
        $pdo->commit();
        notify_user((int)$p['user_id'], 'Pembayaran kas diverifikasi', $p['label'], 'pages/cash.php');
        audit('payment_verify', "payment=$payId");
        return '';
    } catch (Throwable $e) { $pdo->rollBack(); error_log($e->getMessage()); return 'Terjadi kesalahan saat memverifikasi.'; }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $act = $_POST['action'] ?? '';
    if ($act === 'period') {
        $label = trim((string)($_POST['label'] ?? '')); $amt = parse_money($_POST['amount'] ?? '');
        $due = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['due_date'] ?? '') ? $_POST['due_date'] : null;
        if ($label === '' || mb_strlen($label) > 60 || $amt === null || (float)$amt <= 0) flash('err', 'Label dan nominal (> 0) wajib diisi.');
        elseif (q('SELECT 1 FROM cash_periods WHERE label = ?', [$label])->fetchColumn()) flash('err', 'Label periode sudah ada.');
        else { q('INSERT INTO cash_periods (label, amount, due_date, created_by) VALUES (?,?,?,?)', [$label, $amt, $due, $u['id']]); notify_all('Kas baru: ' . $label, rupiah($amt) . ' per anggota', 'pages/cash.php', (int)$u['id']); audit('period_create', $label); flash('ok', 'Periode kas dibuat.'); }
    } elseif ($act === 'verify') {
        $err = verify_payment((int)($_POST['id'] ?? 0), $u); flash($err ? 'err' : 'ok', $err ?: 'Pembayaran diverifikasi dan dicatat sebagai pemasukan.');
    } elseif ($act === 'reject') {
        $id = (int)($_POST['id'] ?? 0); $note = mb_substr(trim((string)($_POST['note'] ?? '')), 0, 255);
        $p = q("SELECT user_id FROM cash_payments WHERE id = ? AND status = 'pending'", [$id])->fetch();
        if ($p && ((int)$p['user_id'] !== (int)$u['id'] || $u['role'] === 'admin')) {
            q("UPDATE cash_payments SET status='rejected', verified_by=?, verified_at=?, verify_note=? WHERE id=?", [$u['id'], now(), $note ?: 'Ditolak', $id]);
            notify_user((int)$p['user_id'], 'Pembayaran kas ditolak', $note, 'pages/cash.php'); audit('payment_reject', "payment=$id"); flash('ok', 'Pembayaran ditolak.');
        } else flash('err', 'Tidak dapat menolak pembayaran ini.');
    } elseif ($act === 'record') { // bendahara mencatat pembayaran tunai atas nama anggota
        $uid = (int)($_POST['user_id'] ?? 0); $pid = (int)($_POST['period_id'] ?? 0);
        $per = q('SELECT * FROM cash_periods WHERE id = ?', [$pid])->fetch();
        $usr = q('SELECT id FROM users WHERE id = ? AND is_active = 1', [$uid])->fetch();
        if (!$per || !$usr) flash('err', 'Anggota atau periode tidak valid.');
        elseif ($uid === (int)$u['id'] && $u['role'] !== 'admin') flash('err', 'Pembayaran Anda sendiri harus diverifikasi orang lain.');
        elseif (q('SELECT 1 FROM cash_payments WHERE period_id = ? AND user_id = ?', [$pid, $uid])->fetchColumn()) flash('err', 'Pembayaran periode ini sudah ada.');
        else {
            q("INSERT INTO cash_payments (period_id, user_id, amount, paid_at, method, note, status) VALUES (?,?,?,?,?,?, 'pending')", [$pid, $uid, $per['amount'], date('Y-m-d'), mb_substr(trim((string)($_POST['method'] ?? 'Tunai')), 0, 60), mb_substr(trim((string)($_POST['note'] ?? '')), 0, 255) ?: null]);
            $err = verify_payment((int)db()->lastInsertId(), $u); flash($err ? 'err' : 'ok', $err ?: 'Pembayaran dicatat dan diverifikasi.');
        }
    } elseif ($act === 'tx') {
        $type = ($_POST['type'] ?? '') === 'pemasukan' ? 'pemasukan' : 'pengeluaran'; $amt = parse_money($_POST['amount'] ?? '');
        $date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['tx_date'] ?? '') ? $_POST['tx_date'] : null; $desc = trim((string)($_POST['description'] ?? ''));
        $cat = (int)($_POST['category_id'] ?? 0);
        $catOk = $cat && q('SELECT 1 FROM cash_categories WHERE id = ? AND type = ?', [$cat, $type])->fetchColumn();
        if ($amt === null || (float)$amt <= 0 || !$date || $date > date('Y-m-d') || $desc === '' || mb_strlen($desc) > 255 || !$catOk) flash('err', 'Lengkapi transaksi: nominal > 0, tanggal (tidak di masa depan), kategori sesuai jenis, dan deskripsi.');
        else {
            try {
                $rc = save_image($_FILES['receipt'] ?? [], 'receipts', false);
                q("INSERT INTO cash_transactions (type, amount, tx_date, category_id, description, created_by, receipt_filename, status, verified_by, verified_at) VALUES (?,?,?,?,?,?,?, 'verified',?,?)", [$type, $amt, $date, $cat, $desc, $u['id'], $rc, $u['id'], now()]);
                audit('tx_create', "$type $amt $desc"); flash('ok', 'Transaksi dicatat.');
            } catch (RuntimeException $ex) { flash('err', $ex->getMessage()); }
        }
    } elseif ($act === 'void') { // pembatalan, bukan penghapusan: riwayat tetap ada
        $id = (int)($_POST['id'] ?? 0); $reason = mb_substr(trim((string)($_POST['reason'] ?? '')), 0, 255);
        if ($reason === '') flash('err', 'Alasan pembatalan wajib diisi.');
        else {
            $pdo = db(); $pdo->beginTransaction();
            try {
                $t = q("SELECT * FROM cash_transactions WHERE id = ? AND status = 'verified' FOR UPDATE", [$id])->fetch();
                if (!$t) { $pdo->rollBack(); flash('err', 'Transaksi tidak ditemukan.'); }
                else {
                    q("UPDATE cash_transactions SET status='void', voided_by=?, voided_at=?, void_reason=? WHERE id=?", [$u['id'], now(), $reason, $id]);
                    if ($t['payment_id']) q("UPDATE cash_payments SET status='rejected', verify_note=? WHERE id=?", ['Transaksi dibatalkan: ' . $reason, $t['payment_id']]);
                    $pdo->commit(); audit('tx_void', "id=$id: $reason"); flash('ok', 'Transaksi dibatalkan (jejak audit tersimpan).');
                }
            } catch (Throwable $ex) { $pdo->rollBack(); error_log($ex->getMessage()); flash('err', 'Gagal membatalkan transaksi.'); }
        }
    } elseif ($act === 'opening') {
        $amt = parse_money($_POST['opening'] ?? ''); if ($amt === null) $amt = '0.00';
        set_setting('opening_balance', $amt); audit('opening_balance', $amt); flash('ok', 'Saldo awal diperbarui.');
    } elseif ($act === 'visibility' && is_admin()) {
        set_setting('show_cash_to_members', isset($_POST['show']) ? '1' : '0'); flash('ok', 'Kebijakan tampilan kas diperbarui.');
    } elseif ($act === 'remind') {
        $pid = (int)($_POST['period_id'] ?? 0); $per = q('SELECT * FROM cash_periods WHERE id = ?', [$pid])->fetch();
        if ($per) { $ids = q("SELECT id FROM users WHERE is_active = 1 AND id NOT IN (SELECT user_id FROM cash_payments WHERE period_id = ? AND status <> 'rejected')", [$pid])->fetchAll(PDO::FETCH_COLUMN);
          foreach ($ids as $i) notify_user((int)$i, 'Pengingat kas: ' . $per['label'], rupiah($per['amount']), 'pages/cash.php'); flash('ok', count($ids) . ' pengingat terkirim.'); }
    }
    redirect('pages/cash_manage.php');
}
$periods = q('SELECT * FROM cash_periods ORDER BY id DESC')->fetchAll();
$pending = q("SELECT p.*, u.name, cp.label FROM cash_payments p JOIN users u ON u.id = p.user_id JOIN cash_periods cp ON cp.id = p.period_id WHERE p.status = 'pending' ORDER BY p.created_at")->fetchAll();
$members = q('SELECT id, name FROM users WHERE is_active = 1 ORDER BY name')->fetchAll();
$cats = q('SELECT * FROM cash_categories ORDER BY type, name')->fetchAll();
$recent = q("SELECT t.*, c.name AS cat FROM cash_transactions t LEFT JOIN cash_categories c ON c.id = t.category_id ORDER BY t.id DESC LIMIT 30")->fetchAll();
$sum = cash_summary();
$pageTitle = 'Kelola kas'; $active = 'cash_manage';
require __DIR__ . '/../includes/header.php';
?>
<section class="card" style="background:linear-gradient(160deg,var(--navy),var(--navy2));color:#fff">
  <div class="row" style="align-items:center;gap:20px;flex-wrap:wrap">
    <div style="flex:none;width:64px;height:64px;border-radius:14px;background:rgba(255,255,255,0.15);display:flex;align-items:center;justify-content:center;font-size:32px">
      <i class="bi bi-wallet2"></i>
    </div>
    <div style="flex:1;min-width:280px">
      <h1 style="color:#fff;font-size:26px;margin:0 0 6px">Kelola Uang Kas</h1>
      <p style="margin:0 0 6px;color:#e2e8f0;font-size:14px">Verifikasi pembayaran, catat transaksi, kelola periode kas.</p>
      <div class="row" style="gap:8px;align-items:center;margin:0;flex-wrap:wrap">
        <span class="tag" style="background:rgba(255,255,255,0.2);color:#fff">Saldo: <?= e(rupiah($sum['balance'])) ?></span>
        <span class="tag" style="background:rgba(37,99,235,0.2);color:#fff;border:1px solid rgba(37,99,235,0.4)"><?= count($pending) ?> Menunggu Verifikasi</span>
      </div>
    </div>
    <div style="flex:none">
      <a class="btn" style="background:#fff;color:var(--navy);font-weight:600" href="<?= e(url('pages/cash_report.php')) ?>"><i class="bi bi-file-earmark-bar-graph"></i> Laporan</a>
    </div>
  </div>
</section>

<div class="card"><h2>Pembayaran menunggu verifikasi (<?= count($pending) ?>)</h2>
<?php if (!$pending): ?><div class="empty"><i class="bi bi-check2-circle"></i>Tidak ada yang menunggu.</div><?php else: ?><div class="tablewrap"><table><tr><th>Anggota</th><th>Periode</th><th>Nominal</th><th>Metode</th><th></th></tr>
<?php foreach ($pending as $p): ?><tr><td><?= e($p['name']) ?></td><td><?= e($p['label']) ?></td><td><?= e(rupiah($p['amount'])) ?></td><td><?= e($p['method']) ?><?= $p['receipt_filename'] ? ' <a target="_blank" rel="noopener" href="' . e(url('pages/receipt.php?type=payment&id=' . (int)$p['id'])) . '">Bukti</a>' : '' ?></td>
 <td><div class="row" style="flex-wrap:nowrap"><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="verify"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><button class="btn btn-sm" type="submit">Verifikasi</button></form>
 <form method="post" class="row" style="flex-wrap:nowrap" data-confirm="Tolak pembayaran ini?"><?= csrf_field() ?><input type="hidden" name="action" value="reject"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><input name="note" placeholder="Alasan" maxlength="255" style="min-width:110px"><button class="btn btn-danger btn-sm" type="submit">Tolak</button></form></div></td></tr><?php endforeach; ?></table></div><?php endif; ?></div>

<div class="grid g2">
<div class="card"><h2>Periode kas</h2>
 <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="period"><div class="row"><div><label>Label (mis. Oktober 2026)</label><input name="label" required maxlength="60"></div><div><label>Nominal per anggota (Rp)</label><input name="amount" required inputmode="numeric"></div><div><label>Jatuh tempo</label><input type="date" name="due_date"></div></div><p><button class="btn" type="submit">Buat periode</button></p></form>
 <?php foreach ($periods as $pr): ?><div class="list-item"><div class="grow"><strong><?= e($pr['label']) ?></strong> <span class="muted"><?= e(rupiah($pr['amount'])) ?></span></div>
  <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="remind"><input type="hidden" name="period_id" value="<?= (int)$pr['id'] ?>"><button class="btn btn-ghost btn-sm" type="submit">Kirim pengingat</button></form></div><?php endforeach; ?></div>
<div class="card"><h2>Catat pembayaran anggota (tunai)</h2>
 <?php if (!$periods): ?><p class="muted">Buat periode kas dulu.</p><?php else: ?><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="record">
 <label>Anggota</label><select name="user_id"><?php foreach ($members as $m): ?><option value="<?= (int)$m['id'] ?>"><?= e($m['name']) ?></option><?php endforeach; ?></select>
 <label>Periode</label><select name="period_id"><?php foreach ($periods as $pr): ?><option value="<?= (int)$pr['id'] ?>"><?= e($pr['label']) ?></option><?php endforeach; ?></select>
 <label>Metode / catatan</label><input name="method" value="Tunai" maxlength="60"><p><button class="btn" type="submit">Catat dan verifikasi</button></p></form><?php endif; ?>
 <h2 style="margin-top:18px">Saldo awal & kebijakan</h2>
 <form method="post" class="row" style="flex-wrap:nowrap"><?= csrf_field() ?><input type="hidden" name="action" value="opening"><input name="opening" inputmode="numeric" value="<?= e((int)$sum['opening']) ?>"><button class="btn btn-ghost" style="flex:none" type="submit">Simpan</button></form>
 <?php if (is_admin()): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="visibility"><label><input type="checkbox" name="show" <?= setting('show_cash_to_members', '1') === '1' ? 'checked' : '' ?>> Anggota boleh melihat ringkasan & transaksi kas</label><button class="btn btn-ghost btn-sm" type="submit">Simpan kebijakan</button></form><?php endif; ?></div>
</div>

<div class="card"><h2>Catat transaksi</h2><form method="post" enctype="multipart/form-data"><?= csrf_field() ?><input type="hidden" name="action" value="tx">
 <div class="row"><div><label>Jenis</label><select name="type"><option value="pengeluaran">Pengeluaran</option><option value="pemasukan">Pemasukan</option></select></div>
 <div><label>Kategori</label><select name="category_id"><?php foreach ($cats as $c): ?><option value="<?= (int)$c['id'] ?>"><?= e(ucfirst($c['type']) . ': ' . $c['name']) ?></option><?php endforeach; ?></select></div>
 <div><label>Nominal (Rp)</label><input name="amount" required inputmode="numeric"></div><div><label>Tanggal</label><input type="date" name="tx_date" required max="<?= e(date('Y-m-d')) ?>" value="<?= e(date('Y-m-d')) ?>"></div></div>
 <label>Deskripsi</label><input name="description" required maxlength="255"><label>Bukti (opsional — otomatis dikompres)</label><input type="file" name="receipt" accept="image/*" data-upcompress="1536">
 <p class="muted">Kategori harus sesuai jenis transaksi (pemasukan/pengeluaran).</p><p><button class="btn" type="submit">Simpan transaksi</button></p></form></div>

<div class="card"><h2>Transaksi terbaru</h2><?php if (!$recent): ?><div class="empty"><i class="bi bi-receipt"></i>Belum ada transaksi.</div><?php else: ?><div class="tablewrap"><table><tr><th>Tanggal</th><th>Keterangan</th><th>Nominal</th><th>Status</th><th></th></tr>
<?php foreach ($recent as $t): ?><tr style="<?= $t['status'] === 'void' ? 'opacity:.55' : '' ?>"><td><?= e(fmt_date($t['tx_date'])) ?></td><td><?= e($t['description']) ?><div class="muted"><?= e($t['cat']) ?><?= $t['receipt_filename'] ? ' · <a target="_blank" rel="noopener" href="' . e(url('pages/receipt.php?type=tx&id=' . (int)$t['id'])) . '">Bukti</a>' : '' ?></div></td>
 <td><?= $t['type'] === 'pemasukan' ? '+' : '-' ?><?= e(rupiah($t['amount'])) ?></td><td><?= $t['status'] === 'void' ? '<span class="tag tag-bad">Batal</span><div class="muted">' . e($t['void_reason']) . '</div>' : '<span class="tag tag-ok">Sah</span>' ?></td>
 <td><?php if ($t['status'] === 'verified'): ?><form method="post" class="row" style="flex-wrap:nowrap" data-confirm="Batalkan transaksi ini?"><?= csrf_field() ?><input type="hidden" name="action" value="void"><input type="hidden" name="id" value="<?= (int)$t['id'] ?>"><input name="reason" required placeholder="Alasan" maxlength="255" style="min-width:110px"><button class="btn btn-danger btn-sm" type="submit">Batalkan</button></form><?php endif; ?></td></tr><?php endforeach; ?></table></div><?php endif; ?></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>