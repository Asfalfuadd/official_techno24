<?php
function current_user(): ?array {
    static $user = false;
    if ($user === false) {
        $user = null;
        if (!empty($_SESSION['uid'])) {
            $u = q('SELECT id, name, username, role, is_active, must_change_password, avatar FROM users WHERE id = ?', [$_SESSION['uid']])->fetch();
            if ($u && (int)$u['is_active'] === 1) $user = $u; // akun nonaktif otomatis keluar
            else { $_SESSION = []; }
        }
    }
    return $user;
}
function is_admin(): bool { $u = current_user(); return $u && $u['role'] === 'admin'; }
function is_treasurer(): bool { $u = current_user(); return $u && in_array($u['role'], ['admin', 'bendahara'], true); }

function forbidden(): void {
    http_response_code(403);
    exit('Akses ditolak. Anda tidak memiliki izin untuk halaman ini.');
}

// Wajib login; jika belum ganti password awal, arahkan ke profil.
function require_login(bool $allowPwChange = false): array {
    $u = current_user();
    if (!$u) redirect('login.php');
    if ((int)$u['must_change_password'] === 1 && !$allowPwChange) redirect('pages/profile.php?force=1');
    return $u;
}
function require_admin(): array { $u = require_login(); if ($u['role'] !== 'admin') forbidden(); return $u; }
function require_treasurer(): array { $u = require_login(); if (!in_array($u['role'], ['admin', 'bendahara'], true)) forbidden(); return $u; }

function login_user(int $id): void {
    session_regenerate_id(true); // cegah session fixation
    $_SESSION['uid'] = $id;
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
    q('UPDATE users SET last_login_at = ? WHERE id = ?', [now(), $id]);
    q('INSERT INTO login_history (user_id, ip, user_agent, logged_at) VALUES (?,?,?,?)',
      [$id, client_ip(), substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255), now()]);
}
function logout_user(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
function password_problem(string $pw): ?string {
    if (strlen($pw) < 8) return 'Password baru minimal 8 karakter.';
    if (!preg_match('/[A-Za-z]/', $pw) || !preg_match('/[0-9]/', $pw)) return 'Password baru harus mengandung huruf dan angka.';
    return null;
}
