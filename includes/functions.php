<?php
function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function url(string $path = ''): string { return BASE_URL . '/' . ltrim($path, '/'); }
function redirect(string $path): void { header('Location: ' . url($path)); exit; }
function now(): string { return date('Y-m-d H:i:s'); }
function client_ip(): string { return substr($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', 0, 45); }

function flash(string $type, string $msg): void { $_SESSION['flash'][] = ['t' => $type, 'm' => $msg]; }
function flash_render(): string {
    $out = '';
    foreach ($_SESSION['flash'] ?? [] as $f) {
        $out .= '<div class="alert alert-' . e($f['t']) . '" role="alert">' . e($f['m']) . '</div>';
    }
    unset($_SESSION['flash']);
    return $out;
}

function rupiah($n): string { return 'Rp ' . number_format((float)$n, 0, ',', '.'); }

const BULAN = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
const HARI = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
function fmt_date($d, bool $time = false): string {
    if (!$d) return '-';
    $t = strtotime((string)$d);
    $s = date('j', $t) . ' ' . BULAN[(int)date('n', $t)] . ' ' . date('Y', $t);
    return $time ? $s . ', ' . date('H:i', $t) : $s;
}
function today_dow(): int { return (int)date('N'); }

function q(string $sql, array $params = []): PDOStatement {
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}

function audit(string $action, string $detail = ''): void {
    try {
        q('INSERT INTO audit_logs (user_id, action, detail, ip, created_at) VALUES (?,?,?,?,?)',
          [$_SESSION['uid'] ?? null, $action, substr($detail, 0, 255), client_ip(), now()]);
    } catch (Throwable $e) { error_log('audit: ' . $e->getMessage()); }
}

function notify_user(int $uid, string $title, string $msg = '', string $link = ''): void {
    q('INSERT INTO notifications (user_id, title, message, link, created_at) VALUES (?,?,?,?,?)',
      [$uid, substr($title, 0, 150), substr($msg, 0, 255), $link, now()]);
}
function notify_all(string $title, string $msg = '', string $link = '', ?int $except = null): void {
    $ids = q('SELECT id FROM users WHERE is_active = 1')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($ids as $id) { if ($except === null || (int)$id !== $except) notify_user((int)$id, $title, $msg, $link); }
}

function setting(string $k, string $default = ''): string {
    $r = q('SELECT v FROM settings WHERE k = ?', [$k])->fetchColumn();
    return $r === false ? $default : (string)$r;
}
function set_setting(string $k, string $v): void {
    q('INSERT INTO settings (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)', [$k, $v]);
}

// ---- Upload media aman (gambar ATAU video, dideteksi dari MIME asli): cek error, ukuran, dimensi; nama acak ----
function save_media(array $file, string $subdir, bool $required = true): ?string {
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        if ($required) throw new RuntimeException('Pilih file gambar terlebih dahulu.');
        return null;
    }
    // Pesan ramah per kode error — termasuk batas server hosting (mis. InfinityFree 20 MB).
    switch ((int)$file['error']) {
        case UPLOAD_ERR_OK: break;
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            throw new RuntimeException('Ukuran file melebihi batas unggah. Pilih file yang lebih kecil atau kompres dulu.');
        case UPLOAD_ERR_PARTIAL:
            throw new RuntimeException('Koneksi terputus saat mengunggah. Coba lagi, sebaiknya lewat Wi-Fi.');
        case UPLOAD_ERR_NO_TMP_DIR:
        case UPLOAD_ERR_CANT_WRITE:
        case UPLOAD_ERR_EXTENSION:
            error_log('save_media: sistem gagal menulis file sementara (kode ' . (int)$file['error'] . ')');
            throw new RuntimeException('Server gagal menyimpan file sementara (izin folder temp/CHMOD).');
        default:
            throw new RuntimeException('Upload gagal (kode ' . (int)$file['error'] . ').');
    }
    if (!is_uploaded_file($file['tmp_name'])) throw new RuntimeException('File tidak valid.');
    $mime = null;
    if (class_exists('finfo')) {
        $mime = @((new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name'])) ?: null;
    }
    if (!$mime) { $gi = @getimagesize($file['tmp_name']); $mime = $gi['mime'] ?? null; } // fallback jika ekstensi fileinfo mati di hosting
    if ($mime && str_starts_with($mime, 'video/')) {
        // ---- cabang video (story): mp4 / webm / mov, koneksi video terputus dsb ----
        $allowed = ['video/mp4' => 'mp4', 'video/webm' => 'webm', 'video/quicktime' => 'mov'];
        if ($file['size'] > MAX_VIDEO_BYTES) throw new RuntimeException('Ukuran video maksimal ' . (int)(MAX_VIDEO_BYTES / 1048576) . ' MB.');
    } else {
        // ---- cabang gambar ----
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
        if ($file['size'] > MAX_UPLOAD_BYTES) throw new RuntimeException('Ukuran file maksimal ' . (MAX_UPLOAD_BYTES / 1048576) . ' MB.');
        $info = @getimagesize($file['tmp_name']);
        if (!$info || $info[0] > 6000 || $info[1] > 6000 || $info[0] < 1 || $info[1] < 1) {
            throw new RuntimeException('Dimensi gambar tidak valid (maksimal 6000x6000 px).');
        }
    }
    if (!$mime || !isset($allowed[$mime])) throw new RuntimeException('Format tidak didukung (Gambar: JPG/PNG/WEBP/GIF · Video: MP4/WEBM/MOV).');
    $name = bin2hex(random_bytes(16)) . '.' . $allowed[$mime]; // ekstensi dari MIME asli, bukan dari nama file pengguna
    $dir = UPLOAD_PATH . '/' . $subdir;
    if (!is_dir($dir)) { @mkdir($dir, 0755, true); }
    if ((!is_dir($dir) || !is_writable($dir)) && (is_dir($dir) || @mkdir($dir, 0777, true))) {
        @chmod($dir, 0777); // sebagian hosting (mis. InfinityFree) eksekusi PHP beda user: perlukan 777
    }
    if (!is_dir($dir) || !is_writable($dir)) {
        error_log('save_media: folder tidak dapat ditulis: ' . $dir);
        throw new RuntimeException('Folder unggahan tidak dapat ditulis. CHMOD folder uploads/ (dan subfolder) ke 755 atau 777 di File Manager hosting.');
    }
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
        error_log('save_media: move_uploaded_file gagal ke ' . $dir . '/' . $name);
        throw new RuntimeException('Gagal menyimpan file. Pastikan folder uploads/ dapat ditulis (CHMOD 777).');
    }
    return $name;
}
function save_image(array $file, string $subdir, bool $required = true): ?string {
    return save_media($file, $subdir, $required);
}
function delete_upload(string $subdir, ?string $name): void {
    if (!$name) return;
    $p = UPLOAD_PATH . '/' . $subdir . '/' . basename($name);
    if (is_file($p)) @unlink($p);
}

function avatar_html(array $u, string $size = 'md'): string {
    if (!empty($u['avatar'])) {
        return '<img class="avatar avatar-' . $size . '" src="' . e(url('uploads/avatars/' . $u['avatar'])) . '" alt="' . e($u['name']) . '">';
    }
    return '<span class="avatar avatar-' . $size . ' avatar-initial" aria-label="' . e($u['name']) . '">' . e(mb_strtoupper(mb_substr($u['name'], 0, 1))) . '</span>';
}

function time_left(string $expires): string {
    $s = strtotime($expires) - time();
    if ($s <= 0) return 'berakhir';
    return floor($s / 3600) . ' jam ' . floor(($s % 3600) / 60) . ' mnt lagi';
}
function time_ago(string $dt): string {
    $s = max(0, time() - strtotime($dt));
    if ($s < 60) return 'baru saja';
    if ($s < 3600) return floor($s / 60) . ' menit lalu';
    if ($s < 86400) return floor($s / 3600) . ' jam lalu';
    return fmt_date($dt);
}

// Pagination sederhana. Kembalikan [offset, limit, totalPages, page].
function paginate(int $total, int $per = 12): array {
    $pages = max(1, (int)ceil($total / $per));
    $page = min($pages, max(1, (int)($_GET['p'] ?? 1)));
    return [($page - 1) * $per, $per, $pages, $page];
}
function pager_html(int $pages, int $page): string {
    if ($pages <= 1) return '';
    $out = '<nav class="pager" aria-label="Halaman">';
    for ($i = 1; $i <= $pages; $i++) {
        $qs = $_GET; $qs['p'] = $i;
        $out .= '<a class="' . ($i === $page ? 'on' : '') . '" href="?' . e(http_build_query($qs)) . '">' . $i . '</a>';
    }
    return $out . '</nav>';
}

// Keuangan: ringkasan saldo dari transaksi terverifikasi (bukan void).
function cash_summary(?string $from = null, ?string $to = null): array {
    $w = "status = 'verified'"; $p = [];
    if ($from) { $w .= ' AND tx_date >= ?'; $p[] = $from; }
    if ($to)   { $w .= ' AND tx_date <= ?'; $p[] = $to; }
    $in  = (float)q("SELECT COALESCE(SUM(amount),0) FROM cash_transactions WHERE type='pemasukan' AND $w", $p)->fetchColumn();
    $out = (float)q("SELECT COALESCE(SUM(amount),0) FROM cash_transactions WHERE type='pengeluaran' AND $w", $p)->fetchColumn();
    $open = (float)setting('opening_balance', '0');
    $members = (int)q('SELECT COUNT(*) FROM users WHERE is_active = 1')->fetchColumn();
    $expected = (float)q('SELECT COALESCE(SUM(amount),0) FROM cash_periods')->fetchColumn() * $members;
    $received = (float)q("SELECT COALESCE(SUM(amount),0) FROM cash_payments WHERE status='verified'")->fetchColumn();
    return ['opening' => $open, 'in' => $in, 'out' => $out, 'balance' => $open + $in - $out,
            'received' => $received, 'outstanding' => max(0, $expected - $received)];
}

function parse_money($v): ?string {
    $v = preg_replace('/[^0-9]/', '', (string)$v);
    if ($v === '' || strlen($v) > 11) return null;
    return $v . '.00';
}
