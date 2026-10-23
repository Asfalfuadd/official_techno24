<?php
require_once __DIR__ . '/includes/bootstrap.php';
if (current_user()) redirect('beranda.php');

// Dapatkan statistik dinamis dari database
$memberCount = (int)q('SELECT COUNT(*) FROM users WHERE is_active = 1')->fetchColumn();
$photoCount = (int)q('SELECT COUNT(*) FROM photos')->fetchColumn();

// Scan folder file/images secara dinamis untuk background rolling
$imageFolder = __DIR__ . '/file/images';
$slides = [];
if (is_dir($imageFolder)) {
  $scannedFiles = scandir($imageFolder);
  foreach ($scannedFiles as $file) {
    if ($file === '.' || $file === '..') continue;
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp', 'avif'])) {
      // filemtime = version per file: foto diganti/ditambah otomatis
      // tampil versi terbaru, tidak pernah stuck di cache browser
      $slideData = [
        'path'    => 'file/images/' . $file,
        'version' => filemtime($imageFolder . '/' . $file),
      ];
      $slides[] = $slideData['path'] . '?v=' . $slideData['version'];
    }
  }
}

// Urutkan nama file secara natural
natcasesort($slides);
$slides = array_values($slides);

// Data untuk section tambahan
$allMembers = q('SELECT id, name, username, role, avatar, bio 
                 FROM users 
                 WHERE is_active = 1 AND role != "admin"
                 ORDER BY name ASC')->fetchAll(PDO::FETCH_ASSOC);

$allPhotos = q('SELECT p.id, p.title, p.filename, p.created_at, u.name as uploader_name 
                FROM photos p 
                JOIN users u ON p.user_id = u.id 
                WHERE p.is_public = 1
                ORDER BY p.created_at DESC 
                LIMIT 12')->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>TECHNO 24 - One Class, One Journey, Endless Memories</title>
  <link rel="icon" type="image/png" href="<?= e(url('assets/images/favicon.png')) ?>">
  <link rel="shortcut icon" href="<?= e(url('favicon.ico')) ?>">
  <link rel="apple-touch-icon" href="<?= e(url('assets/images/logo-badge.png')) ?>">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    :root {
      --navy: #0a1f44;
      --navy2: #12306a;
      --blue: #2563eb;
      --blue-hover: #1d4ed8;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    html {
      scroll-behavior: smooth;
    }

    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      background-color: var(--navy);
    }

    /* Background Slideshow Container */
    .slideshow-container {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: 0;
      overflow: hidden;
    }

    .slide {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      opacity: 0;
      transform: scale(1.08);
      transition: opacity 1.8s cubic-bezier(0.4, 0, 0.2, 1), transform 8s ease-out;
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
      will-change: opacity, transform;
    }

    .slide.active {
      opacity: 1;
      transform: scale(1);
    }

    .slide-fallback {
      background: radial-gradient(circle at 50% 50%, var(--navy2) 0%, var(--navy) 100%);
    }

    /* Vignette di pinggir slide: pemanis fokus ke tengah, teks makin kontras */
    .slideshow-container::after {
      content: '';
      position: absolute;
      inset: 0;
      z-index: 2;
      pointer-events: none;
      background:
        radial-gradient(ellipse at center,
          transparent 45%,
          rgba(4, 12, 28, 0.18) 68%,
          rgba(4, 12, 28, 0.42) 82%,
          rgba(4, 12, 28, 0.65) 100%);
    }

    /* Gradient overlay elegan: gambar terlihat jelas namun teks kontras & terbaca */
    .slide::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(135deg,
          rgba(10, 31, 68, 0.72) 0%,
          rgba(18, 48, 106, 0.50) 45%,
          rgba(10, 31, 68, 0.78) 100%);
    }

    /* Landing Content Wrapper */
    .landing-content {
      position: relative;
      z-index: 10;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      height: 100vh;
      padding: 40px;
      color: white;
      pointer-events: none;
      /* Izinkan kontrol di belakang jika perlu, anak elemen akan pointer-events: auto */
    }

    .landing-content>* {
      pointer-events: auto;
    }

    /* Hero Section */
    .hero {
      flex: 1;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      text-align: center;
      animation: fadeInUp 1s ease-out;
    }

    .logo {
      font-size: 96px;
      font-weight: 900;
      letter-spacing: -2px;
      margin-bottom: 20px;
      text-shadow: 0 4px 25px rgba(0, 0, 0, 0.45);
      line-height: 1;
    }

    .logo-techno {
      color: #ffffff;
    }

    .logo-24 {
      color: var(--blue);
      text-shadow: 0 0 35px rgba(37, 99, 235, 0.5);
    }

    .tagline {
      font-size: 24px;
      font-weight: 500;
      color: rgba(255, 255, 255, 0.92);
      margin-bottom: 45px;
      letter-spacing: 0.5px;
      text-shadow: 0 2px 10px rgba(0, 0, 0, 0.4);
      animation: fadeInUp 1s ease-out 0.2s backwards;
    }

    .cta-button {
      display: inline-flex;
      align-items: center;
      gap: 12px;
      padding: 18px 42px;
      background: #ffffff;
      color: #0d1b2a;
      font-size: 18px;
      font-weight: 700;
      border-radius: 50px;
      text-decoration: none;
      box-shadow: 0 8px 30px rgba(0, 0, 0, 0.35);
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      animation: fadeInUp 1s ease-out 0.4s backwards;
    }

    .cta-button:hover {
      transform: translateY(-4px) scale(1.02);
      box-shadow: 0 14px 40px rgba(0, 0, 0, 0.45), 0 0 20px rgba(255, 255, 255, 0.4);
      background: #f8fafc;
      color: var(--blue);
    }

    .cta-button i {
      transition: transform 0.25s ease;
    }

    .cta-button:hover i {
      transform: translateX(4px);
    }

    /* Stats Bar */
    .stats-bar {
      display: flex;
      gap: 36px;
      align-items: center;
      animation: fadeInLeft 1s ease-out 0.6s backwards;
    }

    .stat-item {
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 16px;
      font-weight: 600;
      color: rgba(255, 255, 255, 0.95);
      background: rgba(10, 31, 68, 0.45);
      padding: 8px 18px;
      border-radius: 30px;
      backdrop-filter: blur(8px);
      -webkit-backdrop-filter: blur(8px);
      border: 1px solid rgba(255, 255, 255, 0.12);
    }

    .stat-item i {
      font-size: 20px;
      color: #60a5fa;
    }

    .stat-separator {
      width: 4px;
      height: 4px;
      background: rgba(255, 255, 255, 0.4);
      border-radius: 50%;
    }

    /* Footer Section */
    .footer {
      display: flex;
      justify-content: space-between;
      align-items: flex-end;
      animation: fadeInUp 1s ease-out 0.8s backwards;
    }

    .footer-left {
      max-width: 420px;
    }

    .footer-brand {
      font-size: 24px;
      font-weight: 700;
      margin-bottom: 6px;
      text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
    }

    .footer-subtitle {
      font-size: 14px;
      color: rgba(255, 255, 255, 0.78);
      line-height: 1.5;
    }

    .footer-right {
      font-size: 13px;
      color: rgba(255, 255, 255, 0.65);
    }

    /* Slideshow Indicators */
    .slideshow-indicators {
      position: absolute;
      bottom: 120px;
      left: 50%;
      transform: translateX(-50%);
      display: flex;
      gap: 12px;
      z-index: 20;
    }

    .indicator {
      width: 36px;
      height: 5px;
      background: rgba(255, 255, 255, 0.35);
      border: none;
      border-radius: 3px;
      cursor: pointer;
      transition: all 0.35s ease;
      padding: 0;
      outline: none;
    }

    .indicator:hover {
      background: rgba(255, 255, 255, 0.7);
    }

    .indicator.active {
      background: #3b82f6;
      width: 58px;
      box-shadow: 0 0 14px rgba(59, 130, 246, 0.8);
    }

    /* Slideshow Left & Right Controls */
    .slideshow-controls {
      position: fixed;
      top: 50%;
      left: 0;
      right: 0;
      transform: translateY(-50%);
      display: flex;
      justify-content: space-between;
      padding: 0 25px;
      pointer-events: none;
      z-index: 15;
    }

    .slide-nav-btn {
      pointer-events: auto;
      width: 48px;
      height: 48px;
      border-radius: 50%;
      border: 1px solid rgba(255, 255, 255, 0.2);
      background: rgba(10, 31, 68, 0.4);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      color: #ffffff;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      font-size: 20px;
      transition: all 0.3s ease;
      opacity: 0.65;
      outline: none;
    }

    .slide-nav-btn:hover {
      opacity: 1;
      background: rgba(37, 99, 235, 0.85);
      border-color: rgba(96, 165, 250, 0.9);
      transform: scale(1.12);
      box-shadow: 0 4px 20px rgba(37, 99, 235, 0.5);
    }

    /* Animations */
    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    @keyframes fadeInLeft {
      from {
        opacity: 0;
        transform: translateX(-30px);
      }

      to {
        opacity: 1;
        transform: translateX(0);
      }
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
      .landing-content {
        padding: 30px 20px;
      }

      .logo {
        font-size: 64px;
      }

      .tagline {
        font-size: 18px;
        margin-bottom: 35px;
      }

      .cta-button {
        padding: 16px 34px;
        font-size: 16px;
      }

      .stats-bar {
        flex-direction: column;
        gap: 10px;
        align-items: flex-start;
      }

      .stat-separator {
        display: none;
      }

      .footer {
        flex-direction: column;
        gap: 15px;
        align-items: flex-start;
      }

      .slideshow-indicators {
        bottom: 85px;
      }

      .slideshow-controls {
        display: none;
      }
    }

    @media (max-width: 480px) {
      .logo {
        font-size: 48px;
      }

      .tagline {
        font-size: 15px;
      }

      .footer-brand {
        font-size: 20px;
      }

      .indicator {
        width: 24px;
      }

      .indicator.active {
        width: 40px;
      }
    }

    /* Sections baru: Gallery & Members */
    .new-section {
      position: relative;
      z-index: 10;
      background: var(--navy);
      padding: 80px 40px;
      min-height: 100vh;
    }

    .section-header {
      text-align: center;
      margin-bottom: 60px;
    }

    .section-title {
      font-size: 48px;
      font-weight: 900;
      color: #fff;
      margin-bottom: 16px;
      text-shadow: 0 2px 15px rgba(0, 0, 0, 0.3);
    }

    .section-subtitle {
      font-size: 18px;
      color: rgba(255, 255, 255, 0.7);
    }

    .grid-container {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 24px;
      max-width: 1400px;
      margin: 0 auto;
    }

    .photo-card {
      position: relative;
      border-radius: 16px;
      overflow: hidden;
      background: rgba(18, 48, 106, 0.3);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.08);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      cursor: pointer;
      aspect-ratio: 4/3;
    }

    .photo-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 12px 40px rgba(0, 0, 0, 0.5);
    }

    .photo-card img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.5s ease;
    }

    .photo-card:hover img {
      transform: scale(1.08);
    }

    .photo-overlay {
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      padding: 20px;
      background: linear-gradient(to top, rgba(10, 31, 68, 0.95) 0%, transparent 100%);
    }

    .photo-title {
      font-size: 16px;
      font-weight: 700;
      color: #fff;
      margin-bottom: 4px;
    }

    .photo-meta {
      font-size: 13px;
      color: rgba(255, 255, 255, 0.65);
    }

    .member-card {
      background: rgba(18, 48, 106, 0.3);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 16px;
      padding: 32px 24px;
      text-align: center;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .member-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 12px 40px rgba(0, 0, 0, 0.5);
      border-color: rgba(37, 99, 235, 0.3);
    }

    .member-avatar {
      width: 100px;
      height: 100px;
      border-radius: 50%;
      object-fit: cover;
      margin: 0 auto 16px;
      border: 3px solid rgba(255, 255, 255, 0.15);
    }

    .member-avatar-placeholder {
      width: 100px;
      height: 100px;
      border-radius: 50%;
      margin: 0 auto 16px;
      border: 3px solid rgba(255, 255, 255, 0.15);
      background: linear-gradient(135deg, var(--navy2) 0%, var(--navy) 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 36px;
      font-weight: 700;
      color: var(--blue);
    }

    .member-name {
      font-size: 18px;
      font-weight: 700;
      color: #fff;
      margin-bottom: 4px;
    }

    .member-role {
      font-size: 13px;
      font-weight: 600;
      color: #60a5fa;
      text-transform: uppercase;
      letter-spacing: 1px;
      margin-bottom: 12px;
    }

    .member-bio {
      font-size: 14px;
      color: rgba(255, 255, 255, 0.65);
      line-height: 1.5;
    }

    @media (max-width: 768px) {
      .new-section {
        padding: 60px 20px;
      }

      .section-title {
        font-size: 36px;
      }

      .grid-container {
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
        gap: 16px;
      }
    }

    @media (max-width: 480px) {
      .section-title {
        font-size: 28px;
      }

      .grid-container {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>

<body>

  <!-- Slideshow Background (Dinamis dari folder file/images) -->
  <div class="slideshow-container" id="slideshow">
    <?php if (!empty($slides)): ?>
      <?php foreach ($slides as $idx => $slidePath): ?>
        <div class="slide<?= $idx === 0 ? ' active' : '' ?>"
          style="background-image: url('<?= e(url($slidePath)) ?>')"
          data-slide-index="<?= $idx ?>"></div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="slide active slide-fallback"></div>
    <?php endif; ?>
  </div>

  <!-- Slideshow Controls (Tombol Navigasi Kiri / Kanan) -->
  <?php if (count($slides) > 1): ?>
    <div class="slideshow-controls">
      <button type="button" class="slide-nav-btn" id="slidePrevBtn" aria-label="Gambar Sebelumnya">
        <i class="bi bi-chevron-left"></i>
      </button>
      <button type="button" class="slide-nav-btn" id="slideNextBtn" aria-label="Gambar Berikutnya">
        <i class="bi bi-chevron-right"></i>
      </button>
    </div>
  <?php endif; ?>

  <div class="landing-content">
    <!-- Stats Bar -->
    <div class="stats-bar">
      <div class="stat-item">
        <i class="bi bi-people-fill"></i>
        <span><?= $memberCount ?> Anggota</span>
      </div>
      <div class="stat-separator"></div>
      <div class="stat-item">
        <i class="bi bi-camera-fill"></i>
        <span><?= number_format($photoCount) ?>+ Foto</span>
      </div>
    </div>

    <!-- Hero Section -->
    <div class="hero">
      <div class="logo">
        <span class="logo-techno">TECHNO</span><span class="logo-24">24</span>
      </div>
      <div class="tagline">One Class, One Journey, Endless Memories.</div>
      <a href="<?= e(url('login.php')) ?>" class="cta-button">
        <span>Masuk / Login</span>
        <i class="bi bi-arrow-right"></i>
      </a>
    </div>

    <!-- Slideshow Indicators -->
    <?php if (count($slides) > 1): ?>
      <div class="slideshow-indicators" id="slideshowIndicators">
        <?php foreach ($slides as $idx => $slidePath): ?>
          <button type="button"
            class="indicator<?= $idx === 0 ? ' active' : '' ?>"
            data-slide="<?= $idx ?>"
            aria-label="Slide <?= $idx + 1 ?>"></button>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <!-- Footer -->
    <div class="footer">
      <div class="footer-left">
        <div class="footer-brand">TECHNO 24</div>
        <div class="footer-subtitle">The official digital memory vault & student registry.</div>
      </div>
      <div class="footer-right">
        © 2026 TECHNO Cohort. All memories preserved.
      </div>
    </div>
  </div>

  <!-- Gallery Section -->
  <section class="new-section" id="gallery">
    <div class="section-header">
      <h2 class="section-title">Gallery</h2>
      <p class="section-subtitle">Koleksi foto dari semua anggota TECHNO 24</p>
    </div>

    <div class="grid-container">
      <?php if (!empty($allPhotos)): ?>
        <?php foreach ($allPhotos as $photo): ?>
          <div class="photo-card">
            <img src="<?= e(url('uploads/photos/' . $photo['filename'])) ?>?v=<?= strtotime($photo['created_at']) ?>"
              alt="<?= e($photo['title']) ?>">
            <div class="photo-overlay">
              <div class="photo-title"><?= e($photo['title']) ?></div>
              <div class="photo-meta">
                <i class="bi bi-person-circle"></i> <?= e($photo['uploader_name']) ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div style="grid-column: 1/-1; text-align: center; padding: 60px 20px; color: rgba(255,255,255,0.5);">
          <i class="bi bi-images" style="font-size: 48px; margin-bottom: 16px; display: block;"></i>
          <p>Belum ada foto yang diupload.</p>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <!-- Members Section -->
  <section class="new-section" id="members" style="background: rgba(18, 48, 106, 0.15);">
    <div class="section-header">
      <h2 class="section-title">Class Members</h2>
      <p class="section-subtitle">Anggota kelas TECHNO 24 (<?= count($allMembers) ?> orang)</p>
    </div>

    <div class="grid-container">
      <?php foreach ($allMembers as $member): ?>
        <div class="member-card">
          <?php if ($member['avatar']): ?>
            <img src="<?= e(url('uploads/avatars/' . $member['avatar'])) ?>"
              alt="<?= e($member['name']) ?>"
              class="member-avatar">
          <?php else: ?>
            <div class="member-avatar-placeholder">
              <?= strtoupper(substr($member['name'], 0, 1)) ?>
            </div>
          <?php endif; ?>

          <div class="member-name"><?= e($member['name']) ?></div>
          <div class="member-role"><?= e($member['role']) ?></div>

          <?php if ($member['bio']): ?>
            <div class="member-bio"><?= e($member['bio']) ?></div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- Slideshow Script External -->
  <script src="<?= e(url('assets/js/landing-slideshow.js?v=' . (file_exists(__DIR__ . '/assets/js/landing-slideshow.js') ? filemtime(__DIR__ . '/assets/js/landing-slideshow.js') : time()))) ?>"></script>

</body>

</html>