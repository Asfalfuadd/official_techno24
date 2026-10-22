/**
 * TECHNO 24 - Transisi Halaman Interaktif
 * 1. Klik link internal -> halaman lama fade/slide keluar, lalu navigasi.
 *    Halaman baru masuk lewat animasi fadeInUp app.css (otomatis).
 * 2. Hormati prefers-reduced-motion, tombol modifier, link eksternal,
 *    download, target _blank, hash, dan file statis (gambar/pdf/zip).
 * Lampirkan di semua page: admin, bendahara, anggota (via footer.php) + login.php
 */
(function () {
  'use strict';

  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

  var EXIT_MS = 230;

  function sameOriginInternal(a) {
    var href = a.getAttribute('href');
    if (!href || href.charAt(0) === '#') return false;
    if (a.target === '_blank') return false;
    if (a.hasAttribute('download')) return false;
    var u;
    try {
      u = new URL(a.href, window.location.href);
    } catch (err) {
      return false;
    }
    if (u.origin !== window.location.origin) return false;
    if (/\.(jpe?g|png|gif|webp|avif|bmp|svg|pdf|zip|rar|ico|css|js|txt)(\?|$)/i.test(u.pathname)) return false;
    return true;
  }

  document.addEventListener('click', function (e) {
    if (e.defaultPrevented || e.button !== 0 ||
        e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) return;

    var a = e.target.closest('a');
    if (!a || !sameOriginInternal(a)) return;

    e.preventDefault();

    // Anchor hash dihalaman sama: biarkan browser scroll, tanpa exit animasi
    var u = new URL(a.href, window.location.href);
    if (u.href === window.location.href || u.pathname === window.location.pathname) {
      window.location.href = u.href;
      return;
    }

    document.body.classList.add('page-exit');
    window.setTimeout(function () {
      window.location.href = u.href;
    }, EXIT_MS);

    // Fallback: jika browser tetap di halaman (mis. ter-interrupt), hapus kelas
    window.setTimeout(function () {
      document.body.classList.remove('page-exit');
    }, EXIT_MS + 700);
  }, true);

  // Kembali lewat tombol back/forward (bfcache): kembalikan tampilan
  window.addEventListener('pageshow', function (e) {
    if (e.persisted) document.body.classList.remove('page-exit');
  });
})();
