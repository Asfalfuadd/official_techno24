/**
 * TECHNO 24 - Background Rolling Picture / Slideshow Engine
 * Automatically cycles background pictures from file/images with smooth Ken Burns zoom,
 * indicator controls, prev/next arrows, swipe support, and keyboard arrows.
 */
(function () {
  function initSlideshow() {
    const slides = document.querySelectorAll('.slideshow-container .slide');
    const indicators = document.querySelectorAll('.slideshow-indicators .indicator');
    const prevBtn = document.getElementById('slidePrevBtn');
    const nextBtn = document.getElementById('slideNextBtn');
    const totalSlides = slides.length;

    if (totalSlides <= 1) return;

    // Preload semua foto dari folder sekaligus: transisi slide mulus,
    // tidak ada flash kosong / grey screen saat pertama ganti slide
    slides.forEach(function (slide) {
      const url = (slide.getAttribute('style') || '').match(/url\(['"]?(.*?)['"]?\)/);
      if (url && url[1]) {
        const img = new Image();
        img.onload = img.onerror = function () {}; // silencing, cache only
        img.src = url[1];
      }
    });

    let currentSlide = 0;
    let slideTimer = null;
    const intervalMs = 5000; // 5 detik pergantian slide

    function goToSlide(n) {
      if (n < 0) {
        currentSlide = totalSlides - 1;
      } else if (n >= totalSlides) {
        currentSlide = 0;
      } else {
        currentSlide = n;
      }

      for (let i = 0; i < totalSlides; i++) {
        if (i === currentSlide) {
          slides[i].classList.add('active');
        } else {
          slides[i].classList.remove('active');
        }
      }

      indicators.forEach((ind, idx) => {
        if (idx === currentSlide) {
          ind.classList.add('active');
        } else {
          ind.classList.remove('active');
        }
      });
    }

    function next() {
      goToSlide(currentSlide + 1);
    }

    function prev() {
      goToSlide(currentSlide - 1);
    }

    function startTimer() {
      stopTimer();
      slideTimer = setInterval(next, intervalMs);
    }

    function stopTimer() {
      if (slideTimer) {
        clearInterval(slideTimer);
        slideTimer = null;
      }
    }

    function restartTimer() {
      stopTimer();
      startTimer();
    }

    // Indikator klik
    indicators.forEach((ind) => {
      ind.addEventListener('click', function (e) {
        e.preventDefault();
        const targetIdx = parseInt(this.getAttribute('data-slide'), 10);
        if (!isNaN(targetIdx)) {
          goToSlide(targetIdx);
          restartTimer();
        }
      });
    });

    // Tombol Navigasi Next / Prev
    if (nextBtn) {
      nextBtn.addEventListener('click', function (e) {
        e.preventDefault();
        next();
        restartTimer();
      });
    }

    if (prevBtn) {
      prevBtn.addEventListener('click', function (e) {
        e.preventDefault();
        prev();
        restartTimer();
      });
    }

    // Keyboard navigation: Panah Kiri dan Kanan
    document.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowRight') {
        next();
        restartTimer();
      } else if (e.key === 'ArrowLeft') {
        prev();
        restartTimer();
      }
    });

    // Touch gesture swipe untuk mobile
    let touchStartX = 0;
    let touchEndX = 0;

    document.addEventListener('touchstart', function (e) {
      if (e.changedTouches && e.changedTouches.length > 0) {
        touchStartX = e.changedTouches[0].screenX;
      }
    }, { passive: true });

    document.addEventListener('touchend', function (e) {
      if (e.changedTouches && e.changedTouches.length > 0) {
        touchEndX = e.changedTouches[0].screenX;
        const diffX = touchStartX - touchEndX;
        if (Math.abs(diffX) > 40) {
          if (diffX > 0) {
            next();
          } else {
            prev();
          }
          restartTimer();
        }
      }
    }, { passive: true });

    // Mulai autoplay rolling gambar
    startTimer();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSlideshow);
  } else {
    initSlideshow();
  }
})();
