<?php
// Dimuat paling awal oleh setiap halaman: konfigurasi, session aman, helper.
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

ini_set('display_errors', '0'); // jangan bocorkan error ke pengguna
ini_set('log_errors', '1');
ini_set('error_log', ROOT_PATH . '/storage/error.log');

session_name('techno24_sess');
session_set_cookie_params([
    'lifetime' => 0, 'path' => '/', 'httponly' => true,
    'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS']),
]);
ini_set('session.use_strict_mode', '1');
session_start();

header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('Content-Security-Policy: default-src \'self\'; style-src \'self\' \'unsafe-inline\' https://fonts.googleapis.com https://cdn.jsdelivr.net; font-src \'self\' https://fonts.gstatic.com https://cdn.jsdelivr.net; img-src \'self\' data: blob:; media-src \'self\' blob:; script-src \'self\' \'unsafe-inline\' https://code.jquery.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; connect-src \'self\';');

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/auth.php';
