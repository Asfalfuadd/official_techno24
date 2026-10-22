// JS vanilla TECHNO 24: menu, konfirmasi hapus, preview upload, story viewer.
(function () {
  var body = document.body;
  function toggleMenu() {
    if (window.innerWidth <= 900) body.classList.toggle('drawer');
    else { body.classList.toggle('collapsed'); try { localStorage.setItem('t24c', body.classList.contains('collapsed') ? '1' : '0'); } catch (e) {} }
  }
  try { if (window.innerWidth > 900 && localStorage.getItem('t24c') === '1') body.classList.add('collapsed'); } catch (e) {}
  ['menuToggle', 'menuToggle2'].forEach(function (id) { var b = document.getElementById(id); if (b) b.addEventListener('click', toggleMenu); });
  var scrim = document.getElementById('scrim'); if (scrim) scrim.addEventListener('click', function () { body.classList.remove('drawer'); });

  // Konfirmasi sebelum hapus / aksi penting: <form data-confirm="Pesan">
  document.addEventListener('submit', function (ev) {
    var m = ev.target.getAttribute('data-confirm');
    if (m && !window.confirm(m)) ev.preventDefault();
    else { var b = ev.target.querySelector('button[type=submit]'); if (b) { setTimeout(function () { b.disabled = true; }, 0); } }
  });

  // Preview gambar sebelum upload: <input type=file data-preview="idImg">
  // File VIDEO dilewatkan (pratinjau video ditangani uploader.js lewat data-previewvideo)
  document.querySelectorAll('input[data-preview]').forEach(function (inp) {
    inp.addEventListener('change', function () {
      var f = inp.files && inp.files[0];
      if (!f || (/^video\//.test(f.type) && inp.hasAttribute('data-previewvideo'))) return;
      var img = document.getElementById(inp.getAttribute('data-preview'));
      if (img) { img.src = URL.createObjectURL(f); img.style.display = 'block'; }
    });
  });

  // Story viewer: data dari window.STORIES (diisi PHP dari database).
  // window.STORIES didefinisikan oleh $extraScripts SETELAH app.js dimuat,
  // jadi inisialisasi ditunda sampai window load (inline script berjalan sebelum 'load').
  var modal = document.getElementById('storyModal');
  var PHOTO_STORY_MS = 10000; // story foto tampil maksimal 10 detik, lalu lanjut ke story berikutnya
  function initStoryViewer() {
    if (!modal || !window.STORIES || modal.dataset.initialized) return;
    modal.dataset.initialized = '1';
    var list = window.STORIES, i = 0, timer = null, rafId = null;
    function clearAuto() { if (timer) { clearTimeout(timer); timer = null; } if (rafId) { cancelAnimationFrame(rafId); rafId = null; } }
    function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }
    function show(n) {
      if (n < 0 || n >= list.length) { modal.classList.remove('open'); clearAuto(); return; }
      clearAuto();
      i = n; var s = list[i];
      // bar progres durasi: segmen lama penuh, segmen aktif berjalan (foto: animasi CSS 15s; video: realtime)
      var bars = ''; list.forEach(function (_, k) {
        if (k < i) bars += '<i><b class="full"></b></i>';
        else if (k === i) bars += '<i><b data-fillbar style="width:0"></b></i>';
        else bars += '<i><b></b></i>';
      });
      // media: video diputar dengan kontrol; bila selesai/menit pertama habis -> lanjut otomatis
      var media = '';
      if (s.type === 'video') media = '<video src="' + esc(s.img) + '" controls playsinline autoplay></video>';
      else if (s.img) media = '<img src="' + esc(s.img) + '" alt="">';
      var text = s.caption ? '<div class="' + (s.img ? 'story-top' : 'story-text') + '">' + esc(s.caption) + '</div>' : '';
      var del = s.canDelete ? '<form method="post" data-confirm="Hapus story ini?" style="margin-left:auto"><input type="hidden" name="csrf" value="' + esc(window.CSRF) + '"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="' + s.id + '"><button class="btn btn-danger btn-sm" type="submit">Hapus</button></form>' : '';
      modal.querySelector('.story-view').innerHTML = '<div class="story-bars">' + bars + '</div><div class="story-top"><strong>' + esc(s.name) + '</strong><span class="muted" style="color:#9fb0d0">' + esc(s.ago) + ' · ' + esc(s.left) + '</span>' + del + '<button type="button" class="btn btn-ghost btn-sm" data-close style="margin-left:' + (s.canDelete ? '0' : 'auto') + '">Tutup</button></div>' + media + text +
        '<button class="story-nav" style="left:8px" data-prev aria-label="Sebelumnya">&#8249;</button><button class="story-nav" style="right:8px" data-next aria-label="Berikutnya">&#8250;</button>';
      // lanjut otomatis: foto 10 detik; video -> saat video selesai diputar; bar mengikuti progres
      if (s.type === 'video') {
        var v = modal.querySelector('.story-view video');
        var fb = modal.querySelector('[data-fillbar]');
        if (v) {
          setTimeout(function () { if (v && v.currentTime > 60) v.pause(); }, 60000);
          v.addEventListener('ended', function () { show(i + 1); });
          if (fb) {
            fb.style.animation = 'none';
            // bar halus ala Instagram/WhatsApp: diperbarui setiap frame sesuai currentTime (berhenti saat video pause)
            (function tick() {
              if (!v.isConnected) return;
              var d = v.duration || 0;
              fb.style.width = (d > 0 ? Math.min(100, v.currentTime / d * 100) : Math.min(100, v.currentTime / 60 * 100)) + '%';
              rafId = requestAnimationFrame(tick);
            })();
          }
        }
      } else {
        // --- story FOTO: bar mengisi real-time (ala Instagram) DAN pemicu lanjut di 10 dtk ---
        // JS interval 80ms (bukan CSS animation): tetap berjalan walau OS/browser
        // mematikan animasi (prefers-reduced-motion) atau tab tidak aktif.
        var fb = modal.querySelector('[data-fillbar]');
        if (fb) {
          fb.style.animation = 'none';
          fb.style.width = '0%';
          var t0 = performance.now();
          var iv = setInterval(function () {
            if (!fb.isConnected) { clearInterval(iv); return; } // story diganti / modal ditutup
            var pr = Math.min(1, (performance.now() - t0) / PHOTO_STORY_MS);
            fb.style.width = (pr * 100) + '%';
            if (pr >= 1) { clearInterval(iv); show(i + 1); }
          }, 80);
          timer = iv;
          // clearAuto memanggil clearTimeout: untuk interval juga bekerja (berbagi tipe ID)
        } else {
          // tanpa bar (kasus luar biasa): tetap lanjut lewat timer
          timer = setTimeout(function () { show(i + 1); }, PHOTO_STORY_MS);
        }
      }
    }
    modal.addEventListener('click', function (ev) {
      var t = ev.target;
      if (t.hasAttribute('data-next')) show(i + 1); else if (t.hasAttribute('data-prev')) show(i - 1);
      else if (t.hasAttribute('data-close') || t === modal) { modal.classList.remove('open'); clearAuto(); }
      if (t.tagName === 'VIDEO') t.pause();
    });
    document.addEventListener('keydown', function (ev) { if (!modal.classList.contains('open')) return; if (ev.key === 'ArrowRight') show(i + 1); if (ev.key === 'ArrowLeft') show(i - 1); if (ev.key === 'Escape') { modal.classList.remove('open'); clearAuto(); } });
    document.querySelectorAll('[data-story]').forEach(function (b) {
      b.addEventListener('click', function () {
        var uid = b.getAttribute('data-story'); var idx = list.findIndex(function (s) { return String(s.uid) === uid; });
        modal.classList.add('open'); show(Math.max(0, idx));
      });
    });
  }
  if (modal) {
    initStoryViewer();
    window.addEventListener('load', initStoryViewer);
  }
})();
