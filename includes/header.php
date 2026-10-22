<?php
// Layout utama: sidebar (desktop), topbar, bottom nav (smartphone).
// Pemakaian: $pageTitle = '...'; $active = 'kunci'; require header.php; ... require footer.php
// Halaman publik ($publicPage = true) boleh dibuka tanpa login; halaman lain wajib login.
$u = !empty($publicPage) ? current_user() : require_login($allowPwChange ?? false);
if ($u && !empty($publicPage) && (int)$u['must_change_password'] === 1) redirect('pages/profile.php?force=1');
$unread = $u ? (int)q('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0', [$u['id']])->fetchColumn() : 0;
$menu = [
  ['dashboard',     $u ? 'dashboard.php' : 'index.php', 'bi-grid-1x2', $u ? 'Dashboard' : 'Beranda'],
  ['schedules',     'pages/schedules.php',      'bi-calendar-week',  'Jadwal Kuliah'],
  ['gallery',       'pages/gallery.php',        'bi-images',         'Galeri Foto'],
  ['albums',        'pages/albums.php',         'bi-collection',     'Album Foto'],
  ['stories',       'pages/stories.php',        'bi-play-circle',    'Story 24 Jam'],
  ['cash',          'pages/cash.php',           'bi-wallet2',        'Uang Kas', true],
  ['agendas',       'pages/agendas.php',        'bi-airplane',       'Agenda & Liburan'],
  ['diaries',       'pages/diaries.php',        'bi-journal-text',   'Diary', true],
  ['announcements', 'pages/announcements.php',  'bi-megaphone',      'Pengumuman'],
  ['polls',         'pages/polls.php',          'bi-bar-chart',      'Polling', true],
  ['members',       'pages/members.php',        'bi-people',         'Direktori Anggota'],
  ['notifications', 'pages/notifications.php',  'bi-bell',           'Notifikasi', true],
  ['profile',       'pages/profile.php',        'bi-gear',           'Pengaturan', true],
];
?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle ?? 'Dashboard') ?> · TECHNO 24</title>
<link rel="icon" type="image/png" href="<?= e(url('assets/images/favicon.png')) ?>">
<link rel="shortcut icon" href="<?= e(url('favicon.ico')) ?>">
<link rel="apple-touch-icon" href="<?= e(url('assets/images/logo-badge.png')) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="<?= e(url('assets/css/app.css?v=' . filemtime(__DIR__ . '/../assets/css/app.css'))) ?>">
</head>
<body data-base="<?= e(BASE_URL) ?>">
<div class="shell" id="shell">
  <aside class="sidebar" id="sidebar">
    <a class="brand" href="<?= e(url($u ? 'dashboard.php' : 'index.php')) ?>">
      <?php include __DIR__ . '/logo.php'; ?><span class="brand-name">TECHNO 24</span>
    </a>
    <nav>
      <?php foreach ($menu as $mi): [$key, $href, $icon, $label] = $mi; if (!empty($mi[4]) && !$u) continue; ?>
        <a href="<?= e(url($href)) ?>" class="<?= ($active ?? '') === $key ? 'on' : '' ?>">
          <i class="bi <?= $icon ?>"></i><span><?= e($label) ?></span>
          <?php if ($key === 'notifications' && $unread > 0): ?><b class="badge"><?= $unread ?></b><?php endif; ?>
        </a>
      <?php endforeach; ?>
      <?php if ($u && is_admin()): ?>
        <div class="nav-sep">Administrator</div>
        <a href="<?= e(url('admin/index.php')) ?>" class="<?= ($active ?? '') === 'admin' ? 'on' : '' ?>"><i class="bi bi-shield-lock"></i><span>Panel Admin</span></a>
        <a href="<?= e(url('admin/users.php')) ?>" class="<?= ($active ?? '') === 'users' ? 'on' : '' ?>"><i class="bi bi-person-gear"></i><span>Kelola Akun</span></a>
      <?php endif; ?>
      <?php if ($u && is_treasurer()): ?>
        <div class="nav-sep">Bendahara</div>
        <a href="<?= e(url('pages/cash_manage.php')) ?>" class="<?= ($active ?? '') === 'cash_manage' ? 'on' : '' ?>"><i class="bi bi-cash-coin"></i><span>Kelola Kas</span></a>
        <a href="<?= e(url('pages/cash_report.php')) ?>" class="<?= ($active ?? '') === 'cash_report' ? 'on' : '' ?>"><i class="bi bi-file-earmark-bar-graph"></i><span>Laporan Keuangan</span></a>
      <?php endif; ?>
    </nav>
  </aside>
  <div class="scrim" id="scrim"></div>
  <div class="main">
    <header class="topbar">
      <button class="icon-btn" id="menuToggle" aria-label="Buka atau ciutkan menu"><i class="bi bi-list"></i></button>
      <div class="topbar-title"><?= e($pageTitle ?? '') ?></div>
      <div class="topbar-right">
        <?php if ($u): ?>
        <a class="icon-btn" href="<?= e(url('pages/notifications.php')) ?>" aria-label="Notifikasi">
          <i class="bi bi-bell"></i><?php if ($unread > 0): ?><b class="dot"><?= $unread > 9 ? '9+' : $unread ?></b><?php endif; ?>
        </a>
        <a class="usercard" href="<?= e(url('pages/profile.php')) ?>">
          <?= avatar_html($u, 'sm') ?>
          <span><strong><?= e($u['name']) ?></strong><small><?= e(ucfirst($u['role'])) ?></small></span>
        </a>
        <?php else: ?>
        <a class="btn btn-sm" href="<?= e(url('login.php')) ?>"><i class="bi bi-box-arrow-in-right"></i> Login</a>
        <?php endif; ?>
      </div>
    </header>
    <main class="content">
      <?= flash_render() ?>
