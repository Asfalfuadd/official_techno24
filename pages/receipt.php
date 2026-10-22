<?php
// Penyaji bukti pembayaran/transaksi privat. Folder uploads/receipts tidak bisa diakses langsung.
require_once __DIR__ . '/../includes/bootstrap.php';
$u = require_login();
$id = (int)($_GET['id'] ?? 0); $type = $_GET['type'] ?? '';
$file = null;
if ($type === 'payment') {
    $r = q('SELECT user_id, receipt_filename FROM cash_payments WHERE id = ?', [$id])->fetch();
    if ($r && ((int)$r['user_id'] === (int)$u['id'] || is_treasurer())) $file = $r['receipt_filename'];
} elseif ($type === 'tx') {
    $r = q('SELECT receipt_filename FROM cash_transactions WHERE id = ?', [$id])->fetch();
    if ($r && is_treasurer()) $file = $r['receipt_filename'];
}
$path = $file ? UPLOAD_PATH . '/receipts/' . basename($file) : '';
if (!$file || !is_file($path)) { http_response_code(404); exit('Bukti tidak ditemukan atau Anda tidak berhak melihatnya.'); }
header('Content-Type: ' . (new finfo(FILEINFO_MIME_TYPE))->file($path));
header('Content-Length: ' . filesize($path));
header('Content-Disposition: inline');
header('Cache-Control: private, no-store');
readfile($path);
