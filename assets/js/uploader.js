// TECHNO 24 — Upload gambar pintar: kompres otomatis di browser + crop persegi foto profil.
// Foto HP (5–10 MB) dikompres jadi < ~1 MB sebelum dikirim (aman untuk hosting,
// hemat kuota, cepat di jaringan HP). Pratinjau diperbarui otomatis.
// Cara pakai di form: <input type="file" accept="image/*" data-upcompress="1600"
//                      data-upcrop (opsional: potong 1:1) data-preview="idImg">
(function () {
  'use strict';

  var TYPE = 'image/jpeg';
  var QUALITY = 0.85;
  var CROP_SIDE = 800;   // keluaran crop profil: persegi 800x800
  var SKIP_BELOW = 96 * 1024; // file < 96 KB dilewatkan (sudah kecil)

  function err(msg) { try { window.alert(msg); } catch (e) {} }

  // ---- baca berbagai sumber (ImageBitmap / Image) dengan rotasi EXIF dihormati ----
  function loadViaImg(file) {
    return new Promise(function (resolve, reject) {
      var url = URL.createObjectURL(file);
      var img = new Image();
      img.onload = function () { resolve(img); };
      img.onerror = function () { URL.revokeObjectURL(url); reject(new Error('read')); };
      img.src = url;
    });
  }
  function loadSource(file) {
    if (window.createImageBitmap) {
      return createImageBitmap(file, { imageOrientation: 'from-image' }).catch(function () { return loadViaImg(file); });
    }
    return loadViaImg(file);
  }

  // ---- gambar sumber ke kanvas (skala ke sisi terpanjang <= maxDim, latar putih) ----
  function drawCanvas(src, maxDim) {
    var w = src.width || src.naturalWidth;
    var h = src.height || src.naturalHeight;
    var k = Math.min(1, maxDim / Math.max(w, h));
    var c = document.createElement('canvas');
    c.width = Math.max(1, Math.round(w * k));
    c.height = Math.max(1, Math.round(h * k));
    var g = c.getContext('2d');
    g.fillStyle = '#ffffff'; // latar putih agar PNG transparan tidak jadi hitam di JPEG
    g.fillRect(0, 0, c.width, c.height);
    g.imageSmoothingQuality = 'high';
    g.drawImage(src, 0, 0, w, h, 0, 0, c.width, c.height);
    return c;
  }

  function toFile(canvas, name) {
    return new Promise(function (resolve, reject) {
      canvas.toBlob(function (b) {
        if (b) resolve(new File([b], name, { type: TYPE }));
        else reject(new Error('compress'));
      }, TYPE, QUALITY);
    });
  }

  // ================= Crop persegi (foto profil) =================
  var overlay = null, lastFocus = null;

  function closeCrop(focusBack) {
    if (!overlay) return;
    var el = overlay; overlay = null;
    el.remove();
    document.body.style.overflow = '';
    if (focusBack && lastFocus && lastFocus.focus) { try { lastFocus.focus(); } catch (e) {} }
  }

  function openCrop(src, side, onDone, onCancel) {
    closeCrop(false);
    lastFocus = document.activeElement;
    document.body.style.overflow = 'hidden';

    overlay = document.createElement('div');
    overlay.className = 'up-back';
    overlay.innerHTML =
      '<div class="up-card" role="dialog" aria-modal="true" aria-label="Potong foto profil">' +
        '<div class="up-head"><i class="bi bi-crop" aria-hidden="true"></i><span>Potong foto profil</span></div>' +
        '<div class="up-wrap"><canvas></canvas><div class="up-shine" aria-hidden="true"></div></div>' +
        '<div class="up-hint"><i class="bi bi-arrows-move" aria-hidden="true"></i> Geser untuk atur posisi &middot; cubit / scroll untuk zoom</div>' +
        '<div class="up-foot">' +
          '<button type="button" class="btn btn-ghost" data-up-cancel>Batal</button>' +
          '<button type="button" class="btn" data-up-ok><i class="bi bi-check-lg"></i> Gunakan Foto</button>' +
        '</div>' +
      '</div>';
    document.body.appendChild(overlay);

    var cv = overlay.querySelector('canvas');
    var wrap = overlay.querySelector('.up-wrap');
    var viewW = Math.max(240, Math.min(380, window.innerWidth - 120, window.innerHeight - 250));
    cv.width = viewW; cv.height = viewW;
    wrap.style.width = viewW + 'px'; wrap.style.height = viewW + 'px';

    var imgW = src.width || src.naturalWidth;
    var imgH = src.height || src.naturalHeight;
    var st = { min: Math.max(viewW / imgW, viewW / imgH), scale: 0, ox: 0, oy: 0 };
    st.scale = st.min;

    function clamp() {
      st.scale = Math.max(st.min, Math.min(st.scale, st.min * 5));
      var limW = Math.max(0, imgW * st.scale / 2 - viewW / 2);
      var limH = Math.max(0, imgH * st.scale / 2 - viewW / 2);
      st.ox = Math.max(-limW, Math.min(limW, st.ox));
      st.oy = Math.max(-limH, Math.min(limH, st.oy));
    }
    function render() {
      var g = cv.getContext('2d');
      g.fillStyle = '#0b1730';
      g.fillRect(0, 0, viewW, viewW);
      g.imageSmoothingQuality = 'high';
      g.drawImage(src, 0, 0, imgW, imgH,
        viewW / 2 + st.ox - imgW * st.scale / 2, viewW / 2 + st.oy - imgH * st.scale / 2,
        imgW * st.scale, imgH * st.scale);
    }
    render();

    // Interaksi: pointer menyatu (mouse + sentuh), cubit dengan 2 jari, zoom scroll.
    var pts = new Map(), lastDist = 0;
    function dist() {
      var a = []; pts.forEach(function (p) { a.push(p); });
      if (a.length < 2) return 0;
      return Math.hypot(a[0].x - a[1].x, a[0].y - a[1].y);
    }
    wrap.addEventListener('pointerdown', function (ev) {
      ev.preventDefault();
      try { wrap.setPointerCapture && wrap.setPointerCapture(ev.pointerId); } catch (e) {}
      pts.set(ev.pointerId, { x: ev.clientX, y: ev.clientY });
      lastDist = dist();
    });
    wrap.addEventListener('pointermove', function (ev) {
      if (!pts.has(ev.pointerId)) return;
      var prev = pts.get(ev.pointerId);
      var dx = ev.clientX - prev.x, dy = ev.clientY - prev.y;
      pts.set(ev.pointerId, { x: ev.clientX, y: ev.clientY });
      if (pts.size >= 2) {
        var d = dist();
        if (lastDist > 0 && d > 0) { st.scale *= d / lastDist; lastDist = d; clamp(); render(); }
      } else {
        st.ox += dx; st.oy += dy; clamp(); render();
      }
    });
    ['pointerup', 'pointercancel'].forEach(function (t) {
      wrap.addEventListener(t, function (ev) {
        pts.delete(ev.pointerId); lastDist = dist();
      });
    });
    wrap.addEventListener('wheel', function (ev) {
      ev.preventDefault();
      st.scale *= (ev.deltaY < 0 ? 1.08 : 0.92);
      clamp(); render();
    }, { passive: false });

    overlay.querySelector('[data-up-cancel]').addEventListener('click', function () {
      closeCrop(true); onCancel && onCancel();
    });
    overlay.addEventListener('keydown', function (ev) {
      if (ev.key === 'Escape') { ev.stopPropagation(); closeCrop(true); onCancel && onCancel(); }
    });
    overlay.querySelector('.up-card').focus = function () {};
    (overlay.querySelector('.up-card')).setAttribute('tabindex', '-1');

    overlay.querySelector('[data-up-ok]').addEventListener('click', function () {
      var out = document.createElement('canvas');
      out.width = side; out.height = side;
      var k = side / viewW;
      var g = out.getContext('2d');
      g.fillStyle = '#ffffff'; g.fillRect(0, 0, side, side);
      g.imageSmoothingQuality = 'high';
      g.drawImage(src, 0, 0, imgW, imgH,
        side / 2 + st.ox * k - imgW * st.scale * k / 2, side / 2 + st.oy * k - imgH * st.scale * k / 2,
        imgW * st.scale * k, imgH * st.scale * k);
      toFile(out, 'avatar.jpg').then(function (f) { closeCrop(true); onDone(f); })
        .catch(function () { err('Gagal memproses foto, coba lagi.'); });
    });
  }

  // ================= pengikat input file =================
  var VMAX_BYTES = 19 * 1048576; // sejalan dengan MAX_VIDEO_BYTES (config/app.php)

  function bindInput(inp) {
    var maxDim = parseInt(inp.getAttribute('data-upcompress') || '1600', 10);
    var wantCrop = inp.hasAttribute('data-upcrop');
    var preview = inp.hasAttribute('data-preview') ? document.getElementById(inp.getAttribute('data-preview')) : null;
    var busy = false;

    // Blokir submit saat foto masih diproses / modal crop masih terbuka
    var form = inp.closest ? inp.closest('form') : null;
    if (form) form.addEventListener('submit', function (ev) {
      if (busy) { ev.preventDefault(); err('Foto masih diproses, tunggu sebentar lalu ulangi.'); }
    });

    function hidePreview() { if (preview) { preview.removeAttribute('src'); preview.style.display = 'none'; } }
    function showPreview(file) {
      if (preview) { preview.src = URL.createObjectURL(file); preview.style.display = 'block'; }
      afterPick();
    }
    function videoPreview() {
      return inp.hasAttribute('data-previewvideo') ? document.getElementById(inp.getAttribute('data-previewvideo')) : null;
    }
    function showVideoPreview(file) {
      var vid = videoPreview();
      hidePreview();
      if (vid) { vid.src = URL.createObjectURL(file); vid.style.display = 'block'; }
      afterPick();
    }
    function hideVideoPreview() {
      var vid = videoPreview();
      if (vid) { vid.removeAttribute('src'); vid.style.display = 'none'; }
    }
    // Setelah file terpilih via tombol pemicu, geser & fokus ke field lanjutan (mis. judul)
    function afterPick() {
      var fa = window.__t24UpFocus && window.__t24UpFocus[inp.id];
      if (fa) { var el = document.getElementById(fa); if (el) { try { el.scrollIntoView({ behavior: 'smooth', block: 'center' }); el.focus({ preventScroll: true }); } catch (e) {} } try { delete window.__t24UpFocus[inp.id]; } catch (e) {} }
    }
    function swap(f) {
      try {
        var dt = new DataTransfer(); dt.items.add(f); inp.files = dt.files;
      } catch (e) { /* ponsel/Safari lawas: biarkan file asli terkirim */ }
      if (/^video\//.test(f.type)) showVideoPreview(f); else showPreview(f);
      busy = false;
    }
    function baseName() {
      var n = (inp.value || '').split(/[\\/]/).pop();
      return (n || 'gambar') + '.jpg';
    }

    inp.addEventListener('change', function () {
      if (busy) return;
      var file = inp.files && inp.files[0];
      hideVideoPreview();
      if (!file) { hidePreview(); busy = false; return; }
      // ---- video (story): cek durasi & ukuran, kirim tanpa kompresi ----
      if (/^video\//.test(file.type)) {
        busy = true;
        if (file.size > VMAX_BYTES) { inp.value = ''; busy = false; err('Ukuran video maksimal 19 MB.'); return; }
        var vb = URL.createObjectURL(file);
        var vEl = document.createElement('video');
        vEl.preload = 'metadata';
        var doneMeta = false;
        var shedOnce = function () { if (!doneMeta) { doneMeta = true; swap(file); } }; // fallback: metadata lambat/tak ada -> kirim saja, server memvalidasi ulang
        setTimeout(shedOnce, 4000);
        vEl.onloadedmetadata = function () {
          if (doneMeta) return; doneMeta = true;
          var dur = vEl.duration || 0;
          URL.revokeObjectURL(vb);
          var maxS = parseInt(inp.getAttribute('data-vidmax') || '60', 10);
          if (dur > maxS + 0.4) {
            inp.value = ''; busy = false;
            err('Durasi video maksimal ' + maxS + ' detik (video ini sekitar ' + Math.round(dur) + ' detik).');
            return;
          }
          swap(file); // video dikirim utuh server yang memvalidasi ulang
        };
        vEl.onerror = function () { if (doneMeta) return; doneMeta = true; URL.revokeObjectURL(vb); busy = false; inp.value = ''; err('Video tidak dapat dibaca. Gunakan MP4/WebM/MOV.'); };
        vEl.src = vb;
        return;
      }
      var okType = /^image\//.test(file.type) && file.type !== 'image/gif';
      var canCanvas = !!(document.createElement('canvas').getContext);
      if (!okType || !canCanvas || (!wantCrop && file.size < SKIP_BELOW)) { showPreview(file); busy = false; return; }
      busy = true;
      loadSource(file).then(function (src) {
        if (wantCrop) { openCrop(src, CROP_SIDE, function (f) { swap(f); }, function () { busy = false; inp.value = ''; hidePreview(); }); return null; }
        return toFile(drawCanvas(src, maxDim), baseName()).then(function (f) { swap(f); });
      }).catch(function () {
        busy = false;
        showPreview(file); // tetap kirim file asli jika proses gagal di ponsel lawas
      });
    });
  }

  document.querySelectorAll('input[type=file][data-upcompress]').forEach(bindInput);

  // Tombol pemicu pemilih berkas: klik tombol langsung membuka dialog pilih file
  // (desktop: sama seperti "Choose file"; HP: langsung gallery/kamera)
  document.querySelectorAll('[data-trigger-file]').forEach(function (t) {
    t.addEventListener('click', function () {
      var inp = document.getElementById(t.getAttribute('data-trigger-file'));
      if (!inp) return;
      if (t.hasAttribute('data-focus-after')) {
        if (!window.__t24UpFocus) window.__t24UpFocus = {};
        window.__t24UpFocus[inp.id] = t.getAttribute('data-focus-after');
      }
      inp.click();
    });
  });
})();
