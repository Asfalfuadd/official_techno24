<?php
require_once __DIR__ . '/../includes/bootstrap.php';
$u = require_treasurer();
$month = preg_match('/^\d{4}-\d{2}$/', $_GET['month'] ?? '') ? $_GET['month'] : date('Y-m');
$from = $month . '-01'; $to = date('Y-m-t', strtotime($from));
$open = (float)setting('opening_balance', '0');
// Saldo awal bulan = saldo awal kas + semua transaksi sah sebelum bulan ini
$before = cash_summary(null, date('Y-m-d', strtotime($from . ' -1 day')));
$startBal = $open + $before['in'] - $before['out'];
$txs = q("SELECT t.*, c.name AS cat FROM cash_transactions t LEFT JOIN cash_categories c ON c.id = t.category_id WHERE t.status='verified' AND t.tx_date BETWEEN ? AND ? ORDER BY t.tx_date, t.id", [$from, $to])->fetchAll();
$in = 0; $out = 0; foreach ($txs as $t) { if ($t['type'] === 'pemasukan') $in += (float)$t['amount']; else $out += (float)$t['amount']; }
$unpaid = q("SELECT u.name, cp.label FROM users u JOIN cash_periods cp ON 1=1 WHERE u.is_active = 1 AND NOT EXISTS (SELECT 1 FROM cash_payments p WHERE p.user_id = u.id AND p.period_id = cp.id AND p.status = 'verified') ORDER BY cp.id DESC, u.name LIMIT 200")->fetchAll();
$pageTitle = 'Laporan keuangan'; $active = 'cash_report';
require __DIR__ . '/../includes/header.php';
?>
<section class="card" style="background:linear-gradient(160deg,var(--navy),var(--navy2));color:#fff">
  <div class="row" style="align-items:center;gap:20px;flex-wrap:wrap">
    <div style="flex:none;width:64px;height:64px;border-radius:14px;background:rgba(255,255,255,0.15);display:flex;align-items:center;justify-content:center;font-size:32px">
      <i class="bi bi-file-earmark-bar-graph"></i>
    </div>
    <div style="flex:1;min-width:280px">
      <h1 style="color:#fff;font-size:26px;margin:0 0 6px">Laporan Keuangan</h1>
      <p style="margin:0 0 6px;color:#e2e8f0;font-size:14px">Ringkasan arus kas bulanan & status pembayaran anggota.</p>
      <div class="row" style="gap:8px;align-items:center;margin:0;flex-wrap:wrap">
        <span class="tag" style="background:rgba(255,255,255,0.2);color:#fff">Bulan: <?= e(BULAN[(int)date('n', strtotime($from))] . ' ' . date('Y', strtotime($from))) ?></span>
        <span class="tag" style="background:rgba(37,99,235,0.2);color:#fff;border:1px solid rgba(37,99,235,0.4)"><?= count($txs) ?> Transaksi Sah</span>
      </div>
    </div>
    <div style="flex:none;display:flex;gap:8px;flex-wrap:wrap">
      <form class="row" method="get" style="gap:8px;align-items:center;flex-wrap:wrap">
        <input type="month" name="month" value="<?= e($month) ?>" style="flex:none">
        <button class="btn" style="background:#fff;color:var(--navy);font-weight:600" type="submit">Tampilkan</button>
        <button class="btn btn-ghost" style="flex:none" type="button" onclick="window.print()"><i class="bi bi-printer"></i> Cetak</button>
      </form>
    </div>
  </div>
</section>

<div class="card"><h2>TECHNO 24 · Laporan kas <?= e(BULAN[(int)date('n', strtotime($from))] . ' ' . date('Y', strtotime($from))) ?></h2>
 <p>Saldo awal bulan: <strong><?= e(rupiah($startBal)) ?></strong> · Pemasukan: <strong><?= e(rupiah($in)) ?></strong> · Pengeluaran: <strong><?= e(rupiah($out)) ?></strong> · Saldo akhir: <strong><?= e(rupiah($startBal + $in - $out)) ?></strong></p>
 <?php if (!$txs): ?><div class="empty"><i class="bi bi-receipt"></i>Tidak ada transaksi pada bulan ini.</div><?php else: ?><div class="tablewrap"><table><tr><th>Tanggal</th><th>Keterangan</th><th>Kategori</th><th style="text-align:right">Masuk</th><th style="text-align:right">Keluar</th></tr>
 <?php foreach ($txs as $t): ?><tr><td><?= e(fmt_date($t['tx_date'])) ?></td><td><?= e($t['description']) ?></td><td><?= e($t['cat']) ?></td><td style="text-align:right"><?= $t['type'] === 'pemasukan' ? e(rupiah($t['amount'])) : '' ?></td><td style="text-align:right"><?= $t['type'] === 'pengeluaran' ? e(rupiah($t['amount'])) : '' ?></td></tr><?php endforeach; ?></table></div><?php endif; ?></div>

<div class="card"><h2>Anggota yang belum lunas</h2><?php if (!$unpaid): ?><p class="muted">Semua anggota lunas, atau belum ada periode kas.</p><?php else: ?><div class="tablewrap"><table><tr><th>Anggota</th><th>Periode</th></tr><?php foreach ($unpaid as $r): ?><tr><td><?= e($r['name']) ?></td><td><?= e($r['label']) ?></td></tr><?php endforeach; ?></table></div><?php endif; ?></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>