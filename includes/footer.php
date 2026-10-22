    </main>
    <footer class="foot">TECHNO 24 · Kelas A · Teknologi Informasi</footer>
  </div>
</div>
<nav class="bottomnav" aria-label="Menu utama">
  <a href="<?= e(url(current_user() ? 'dashboard.php' : 'index.php')) ?>" class="<?= ($active ?? '') === 'dashboard' ? 'on' : '' ?>"><i class="bi bi-grid-1x2"></i>Beranda</a>
  <a href="<?= e(url('pages/gallery.php')) ?>" class="<?= ($active ?? '') === 'gallery' ? 'on' : '' ?>"><i class="bi bi-images"></i>Galeri</a>
  <a href="<?= e(url('pages/stories.php')) ?>" class="<?= ($active ?? '') === 'stories' ? 'on' : '' ?>"><i class="bi bi-play-circle"></i>Story</a>
  <?php if (current_user()): ?><a href="<?= e(url('pages/cash.php')) ?>" class="<?= ($active ?? '') === 'cash' ? 'on' : '' ?>"><i class="bi bi-wallet2"></i>Kas</a>
  <?php else: ?><a href="<?= e(url('pages/schedules.php')) ?>" class="<?= ($active ?? '') === 'schedules' ? 'on' : '' ?>"><i class="bi bi-calendar-week"></i>Jadwal</a><?php endif; ?>
  <button type="button" id="menuToggle2"><i class="bi bi-three-dots"></i>Menu</button>
</nav>
<script src="<?= e(url('assets/js/app.js?v=' . filemtime(__DIR__ . '/../assets/js/app.js'))) ?>"></script>
<script src="<?= e(url('assets/js/uploader.js?v=' . filemtime(__DIR__ . '/../assets/js/uploader.js'))) ?>"></script>
<script src="<?= e(url('assets/js/page-transitions.js?v=' . filemtime(__DIR__ . '/../assets/js/page-transitions.js'))) ?>"></script>
<?= $extraScripts ?? '' ?>
</body>
</html>
