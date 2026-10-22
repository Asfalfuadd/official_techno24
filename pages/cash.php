<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$u = require_login();
$showAll = is_treasurer() || setting('show_cash_to_members', '1') === '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (($_POST['action'] ?? '') === 'pay') {
        $pid = (int)($_POST['period_id'] ?? 0);
        $period = q('SELECT * FROM cash_periods WHERE id = ?', [$pid])->fetch();
        $ex = $period ? q('SELECT * FROM cash_payments WHERE period_id = ? AND user_id = ?', [$pid, $u['id']])->fetch() : null;
        if (!$period) flash('err', 'Periode tidak ditemukan.');
        elseif ($ex && $ex['status'] !== 'rejected') flash('err', 'Pembayaran periode ini sudah diajukan.');
        else {
            try {
                $rc = save_image($_FILES['receipt'] ?? [], 'receipts', false);
                $method = mb_substr(trim((string)($_POST['method'] ?? '')), 0, 60); $note = mb_substr(trim((string)($_POST['note'] ?? '')), 0, 255);
                // Status selalu 'pending': anggota tidak bisa memverifikasi sendiri, nominal diambil dari periode (bukan dari input).
                if ($ex) { q("UPDATE cash_payments SET amount=?, paid_at=?, method=?, note=?, status='pending', verify_note=NULL, receipt_filename=COALESCE(?, receipt_filename) WHERE id=?", [$period['amount'], date('Y-m-d'), $method ?: null, $note ?: null, $rc, $ex['id']]); }
                else { q('INSERT INTO cash_payments (period_id, user_id, amount, paid_at, method, note, receipt_filename) VALUES (?,?,?,?,?,?,?)', [$pid, $u['id'], $period['amount'], date('Y-m-d'), $method ?: null, $note ?: null, $rc]); }
                $tr = q("SELECT id FROM users WHERE role IN ('admin','bendahara') AND is_active = 1")->fetchAll(PDO::FETCH_COLUMN);
                foreach ($tr as $tid) if ((int)$tid !== (int)$u['id']) notify_user((int)$tid, 'Pembayaran kas menunggu verifikasi', $u['name'] . ' - ' . $period['label'], 'pages/cash_manage.php');
                flash('ok', 'Pembayaran diajukan dan menunggu verifikasi bendahara.');
            } catch (RuntimeException $ex2) { flash('err', $ex2->getMessage()); }
        }
    }
    redirect('pages/cash.php');
}
$mine = q('SELECT p.*, cp.label, cp.due_date FROM cash_payments p JOIN cash_periods cp ON cp.id = p.period_id WHERE p.user_id = ? ORDER BY cp.id DESC', [$u['id']])->fetchAll();
$payable = q("SELECT cp.id, cp.label, cp.amount FROM cash_periods cp WHERE NOT EXISTS (SELECT 1 FROM cash_payments p WHERE p.period_id = cp.id AND p.user_id = ? AND p.status <> 'rejected') ORDER BY cp.id DESC", [$u['id']])->fetchAll();
$from = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['from'] ?? '') ? $_GET['from'] : null; $to = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['to'] ?? '') ? $_GET['to'] : null;
$extraScripts = '';
if ($showAll) {
    $sum = cash_summary($from, $to);
    $w = "t.status = 'verified'"; $p = [];
    if ($from) { $w .= ' AND t.tx_date >= ?'; $p[] = $from; } if ($to) { $w .= ' AND t.tx_date <= ?'; $p[] = $to; }
    $total = (int)q("SELECT COUNT(*) FROM cash_transactions t WHERE $w", $p)->fetchColumn();
    [$off, $lim, $pages, $page] = paginate($total, 15);
    $txs = q("SELECT t.*, c.name AS cat FROM cash_transactions t LEFT JOIN cash_categories c ON c.id = t.category_id WHERE $w ORDER BY t.tx_date DESC, t.id DESC LIMIT $lim OFFSET $off", $p)->fetchAll();
    $chart = q("SELECT DATE_FORMAT(tx_date,'%Y-%m') AS m, SUM(CASE WHEN type='pemasukan' THEN amount ELSE 0 END) AS i, SUM(CASE WHEN type='pengeluaran' THEN amount ELSE 0 END) AS o FROM cash_transactions WHERE status='verified' GROUP BY m ORDER BY m DESC LIMIT 6")->fetchAll();
    $chart = array_reverse($chart);
    $extraScripts = '<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script><script>var d=' . json_encode($chart) . ';if(d.length&&window.Chart){new Chart(document.getElementById("cashChart"),{type:"bar",data:{labels:d.map(x=>x.m),datasets:[{label:"Pemasukan",data:d.map(x=>+x.i),backgroundColor:"#2563eb"},{label:"Pengeluaran",data:d.map(x=>+x.o),backgroundColor:"#f59e0b"}]},options:{responsive:true,maintainAspectRatio:false}});}</script>';
}
$pageTitle = 'Uang kas'; $active = 'cash';
require __DIR__ . '/../includes/header.php';
?>
<section class="card" style="background:linear-gradient(160deg,var(--navy),var(--navy2));color:#fff">
  <div class="row" style="align-items:center;gap:20px;flex-wrap:wrap">
    <div style="flex:none;width:64px;height:64px;border-radius:14px;background:rgba(255,255,255,0.15);display:flex;align-items:center;justify-content:center;font-size:32px">
      <i class="bi bi-wallet2"></i>
    </div>
    <div style="flex:1;min-width:280px">
      <h1 style="color:#fff;font-size:26px;margin:0 0 6px">Uang Kas Kelas</h1>
      <p style="margin:0 0 6px;color:#e2e8f0;font-size:14px">Transparansi keuangan dan pembayaran kas TECHNO 24 Kelas A.</p>
      <?php if ($showAll): ?>
      <div class="row" style="gap:8px;align-items:center;margin:0;flex-wrap:wrap">
        <span class="tag" style="background:rgba(255,255,255,0.2);color:#fff">Saldo: <?= e(rupiah($sum['balance'])) ?></span>
        <span class="tag" style="background:rgba(255,255,255,0.2);color:#fff">Pemasukan: <?= e(rupiah($sum['in'])) ?></span>
        <span class="tag" style="background:rgba(255,255,255,0.2);color:#fff">Pengeluaran: <?= e(rupiah($sum['out'])) ?></span>
      </div>
      <?php endif; ?>
    </div>
    <?php if (is_treasurer()): ?>
      <div style="flex:none">
        <a class="btn" style="background:#fff;color:var(--navy);font-weight:600" href="<?= e(url('pages/cash_manage.php')) ?>"><i class="bi bi-cash-coin"></i> Kelola Kas</a>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php if ($showAll): ?>
<div class="grid g4"><div class="card stat"><b><?= e(rupiah($sum['opening'])) ?></b><span>Saldo awal</span></div><div class="card stat"><b><?= e(rupiah($sum['in'])) ?></b><span>Total pemasukan</span></div>
 <div class="card stat"><b><?= e(rupiah($sum['out'])) ?></b><span>Total pengeluaran</span></div><div class="card stat"><b><?= e(rupiah($sum['balance'])) ?></b><span>Saldo saat ini</span></div></div>
<div class="grid g2"><div class="card stat"><b><?= e(rupiah($sum['received'])) ?></b><span>Pembayaran kas diterima</span></div><div class="card stat"><b><?= e(rupiah($sum['outstanding'])) ?></b><span>Belum diterima (semua anggota aktif)</span></div></div>
<div class="card"><h2>Pemasukan & pengeluaran (6 bulan terakhir)</h2><div style="height:260px"><canvas id="cashChart"></canvas></div><?php if (!$chart): ?><p class="muted">Grafik muncul setelah ada transaksi.</p><?php endif; ?></div>
<?php endif; ?>

<div class="card"><h2>Pembayaran kas saya</h2>
<?php if (!$mine): ?><div class="empty"><i class="bi bi-wallet2"></i>Belum ada pembayaran tercatat.</div><?php else: ?><div class="tablewrap"><table><tr><th>Periode</th><th>Nominal</th><th>Tanggal</th><th>Status</th><th>Catatan</th></tr>
<?php foreach ($mine as $m): ?><tr><td><?= e($m['label']) ?></td><td><?= e(rupiah($m['amount'])) ?></td><td><?= e(fmt_date($m['paid_at'])) ?></td>
 <td><span class="tag <?= $m['status'] === 'verified' ? 'tag-ok' : ($m['status'] === 'rejected' ? 'tag-bad' : 'tag-warn') ?>"><?= $m['status'] === 'verified' ? 'Lunas' : ($m['status'] === 'rejected' ? 'Ditolak' : 'Menunggu verifikasi') ?></span></td>
 <td class="muted"><?= e($m['verify_note']) ?><?= $m['receipt_filename'] ? ' <a href="' . e(url('pages/receipt.php?type=payment&id=' . (int)$m['id'])) . '" target="_blank" rel="noopener">Bukti</a>' : '' ?></td></tr><?php endforeach; ?></table></div><?php endif; ?>
<?php if ($payable): ?><h2 style="margin-top:18px">Ajukan pembayaran</h2><form method="post" enctype="multipart/form-data"><?= csrf_field() ?><input type="hidden" name="action" value="pay">
 <div class="row"><div><label>Periode</label><select name="period_id"><?php foreach ($payable as $pp): ?><option value="<?= (int)$pp['id'] ?>"><?= e($pp['label'] . ' - ' . rupiah($pp['amount'])) ?></option><?php endforeach; ?></select></div>
 <div><label>Metode</label><input name="method" maxlength="60" placeholder="Tunai / transfer"></div></div>
 <label>Catatan</label><input name="note" maxlength="255"><label>Bukti (opsional, gambar)</label><input type="file" name="receipt" accept="image/*" data-upcompress="1536">
 <p><button class="btn" type="submit">Ajukan</button></p></form>
<?php elseif (!$mine): ?><p class="muted">Bendahara belum membuat periode kas.</p><?php endif; ?></div>

<?php if ($showAll): ?><div class="card"><h2>Riwayat transaksi</h2>
 <form class="row" method="get"><div><label>Dari</label><input type="date" name="from" value="<?= e($from) ?>"></div><div><label>Sampai</label><input type="date" name="to" value="<?= e($to) ?>"></div><div style="flex:none;align-self:end"><button class="btn" type="submit">Filter</button></div></form>
 <?php if (!$txs): ?><div class="empty"><i class="bi bi-receipt"></i>Belum ada transaksi.</div><?php else: ?><div class="tablewrap"><table><tr><th>Tanggal</th><th>Keterangan</th><th>Kategori</th><th style="text-align:right">Nominal</th></tr>
 <?php foreach ($txs as $t): ?><tr><td><?= e(fmt_date($t['tx_date'])) ?></td><td><?= e($t['description']) ?></td><td><?= e($t['cat']) ?></td><td style="text-align:right;color:<?= $t['type'] === 'pemasukan' ? 'var(--ok)' : 'var(--bad)' ?>"><?= $t['type'] === 'pemasukan' ? '+' : '-' ?><?= e(rupiah($t['amount'])) ?></td></tr><?php endforeach; ?></table></div><?= pager_html($pages, $page) ?><?php endif; ?></div><?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
