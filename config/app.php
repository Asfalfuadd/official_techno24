<?php
// Konfigurasi umum TECHNO 24
define('APP_NAME', 'TECHNO 24');
define('APP_TAGLINE', 'One Class, One Journey, Endless Memories.');
define('ROOT_PATH', dirname(__DIR__));
// BASE_URL auto-detect: lokasi lokal = subfolder (mis. /techno24) di htdocs;
// di hosting (file ada di htdocs root) otomatis ''. Tidak perlu edit saat deploy.
define('BASE_URL', basename(ROOT_PATH) === 'htdocs' ? '' : '/' . basename(ROOT_PATH));
define('UPLOAD_PATH', ROOT_PATH . '/uploads');
define('APP_TIMEZONE', 'Asia/Makassar'); // WITA (NTB)
define('MAX_UPLOAD_BYTES', 4 * 1024 * 1024); // 4 MB
define('MAX_VIDEO_BYTES', 19 * 1024 * 1024); // 19 MB — di bawah limit upload InfinityFree (20 MB)
define('MAX_LOGIN_FAILS', 5);       // percobaan gagal
define('LOGIN_LOCK_MINUTES', 15);   // jendela pembatasan
define('SETUP_ENABLED', false);      // set false setelah setup selesai
date_default_timezone_set(APP_TIMEZONE);
