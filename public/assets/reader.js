// 전자책 뷰어 동작: 보기 설정(글자 크기·글꼴·줄 간격·배경), 막대 숨기기, 목차, 읽던 위치 저장, PDF 쪽 그리기
(function () {
  'use strict';

  var R = window.READER || {};
  var root = document.documentElement;
  var KEY = 'reader-settings';
  var SCALES = [0.85, 0.92, 1, 1.1, 1.2, 1.35, 1.5, 1.7];
  var ZOOMS = [1.25, 1.5, 2, 2.5, 3]; // 화면 맞춤(0) 다음 단계들
  var PDFJS = 'https://cdn.jsdelivr.net/npm/pdfjs-dist@4.10.38/build/';

  /* ───────── 보기 설정 (이 기기에 저장) ───────── */

  var settings = {};
  try { settings = JSON.parse(localStorage.getItem(KEY) || '{}') || {}; } catch (e) { settings = {}; }
  settings.theme = settings.theme || root.getAttribute('data-theme') || 'light';
  settings.font = settings.font || 'serif';
  settings.lh = settings.lh || 'normal';
  settings.scale = SCALES.indexOf(Number(settings.scale)) >= 0 ? Number(settings.scale) : 1;
  settings.zoom = Math.max(0, Math.min(ZOOMS.length, Number(settings.zoom) || 0)); // 0 = 화면 맞춤

  function saveSettings() {
    try { localStorage.setItem(KEY, JSON.stringify(settings)); } catch (e) { /* 저장 못 해도 화면은 동작 */ }
  }

  function pressed(attr, value) {
    document.querySelectorAll('.rd-sheet [data-' + attr + ']').forEach(function (b) {
      b.setAttribute('aria-pressed', String(b.getAttribute('data-' + attr) === value));
    });
  }

  function applySettings() {
    root.setAttribute('data-theme', settings.theme);
    root.setAttribute('data-font', settings.font);
    root.setAttribute('data-lh', settings.lh);
    root.style.setProperty('--rd-scale', settings.scale);
    pressed('theme', settings.theme);
    pressed('font', settings.font);
    pressed('lh', settings.lh);
    var out = document.getElementById('rd-scale-out');
    if (out) out.textContent = Math.round(settings.scale * 100) + '%';
    var i = SCALES.indexOf(settings.scale);
    var down = document.querySelector('[data-scale="-1"]');
    var up = document.querySelector('[data-scale="1"]');
    if (down) down.disabled = i <= 0;
    if (up) up.disabled = i >= SCALES.length - 1;
    var zoomOut = document.getElementById('rd-zoom-out');
    if (zoomOut) zoomOut.textContent = settings.zoom === 0 ? '화면 맞춤' : Math.round(ZOOMS[settings.zoom - 1] * 100) + '%';
    var zMinus = document.querySelector('[data-zoom="-1"]');
    var zPlus = document.querySelector('[data-zoom="1"]');
    if (zMinus) zMinus.disabled = settings.zoom <= 0;
    if (zPlus) zPlus.disabled = settings.zoom >= ZOOMS.length;
  }

  // 글자 크기를 바꿔도 읽던 자리가 그대로 보이게 합니다.
  function keepPlace(change) {
    var r = scrollRatio();
    change();
    requestAnimationFrame(function () { window.scrollTo(0, r * maxScroll()); });
  }

  document.addEventListener('click', function (event) {
    var b = event.target.closest('.rd-sheet button');
    if (!b) return;
    if (b.hasAttribute('data-theme')) settings.theme = b.getAttribute('data-theme');
    else if (b.hasAttribute('data-font')) keepPlace(function () { settings.font = b.getAttribute('data-font'); applySettings(); });
    else if (b.hasAttribute('data-lh')) keepPlace(function () { settings.lh = b.getAttribute('data-lh'); applySettings(); });
    else if (b.hasAttribute('data-scale')) {
      var i = SCALES.indexOf(settings.scale) + Number(b.getAttribute('data-scale'));
      keepPlace(function () { settings.scale = SCALES[Math.max(0, Math.min(SCALES.length - 1, i))]; applySettings(); });
    } else if (b.hasAttribute('data-zoom')) {
      settings.zoom = Math.max(0, Math.min(ZOOMS.length, settings.zoom + Number(b.getAttribute('data-zoom'))));
      if (pdf) pdf.relayout(true);
    } else return;
    applySettings();
    saveSettings();
  });
  applySettings();

  /* ───────── 설정·목차 시트 ───────── */

  var openSheet = null;
  var opener = null;
  function showSheet(name, btn) {
    closeSheet();
    var el = document.getElementById('sheet-' + name);
    if (!el) return;
    el.hidden = false;
    openSheet = el;
    opener = btn;
    btn.setAttribute('aria-expanded', 'true');
    var current = el.querySelector('[aria-current]');
    if (current) current.scrollIntoView({ block: 'center' });
    var first = el.querySelector('[aria-current], [aria-pressed="true"], button');
    if (first) first.focus({ preventScroll: true });
  }
  function closeSheet() {
    if (!openSheet) return;
    openSheet.hidden = true;
    openSheet = null;
    if (opener) {
      opener.setAttribute('aria-expanded', 'false');
      opener.focus({ preventScroll: true });
    }
  }
  document.querySelectorAll('[data-sheet]').forEach(function (btn) {
    btn.addEventListener('click', function () { showSheet(btn.getAttribute('data-sheet'), btn); });
  });
  document.querySelectorAll('[data-close]').forEach(function (el) { el.addEventListener('click', closeSheet); });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') { closeSheet(); return; }
    if (openSheet || event.altKey || event.ctrlKey || event.metaKey) return;
    if (/^(INPUT|TEXTAREA|SELECT)$/.test((event.target.tagName || ''))) return;
    var link = event.key === 'ArrowRight' ? document.querySelector('[data-next]') : event.key === 'ArrowLeft' ? document.querySelector('[data-prev]') : null;
    if (link) link.click();
  });

  /* ───────── 위·아래 막대: 내려 읽으면 숨고, 올리거나 끝에 닿으면 나타남 ───────── */

  function maxScroll() { return Math.max(0, document.documentElement.scrollHeight - window.innerHeight); }
  function scrollRatio() { var m = maxScroll(); return m > 0 ? Math.min(1, Math.max(0, window.scrollY / m)) : 1; }

  var lastY = window.scrollY;
  function setBars(hidden) { document.body.classList.toggle('rd-bars-hidden', hidden); }
  window.addEventListener('scroll', function () {
    var y = window.scrollY;
    if (!openSheet) {
      if (y > lastY + 6 && y > 80) setBars(true);
      else if (y < lastY - 6 || y < 40 || y >= maxScroll() - 40) setBars(false);
    }
    lastY = y;
    onScroll();
  }, { passive: true });

  // 휴대폰·태블릿: 본문 가운데를 누르면 막대를 보였다 숨깁니다.
  if (window.matchMedia && matchMedia('(pointer: coarse)').matches) {
    document.getElementById('rd-main').addEventListener('click', function (event) {
      if (event.target.closest('a, button, input')) return;
      var sel = window.getSelection && window.getSelection();
      if (sel && String(sel)) return;
      setBars(!document.body.classList.contains('rd-bars-hidden'));
    });
  }

  /* ───────── 읽던 위치 저장 ───────── */

  var bar = document.getElementById('rd-progress-bar');
  var text = document.getElementById('rd-progress-text');
  var saveTimer = null;
  var lastSent = '';

  function currentPosition() {
    if (R.format === 'EPUB') {
      var r = scrollRatio();
      var pct = R.chapters ? ((R.chapter - 1) + r) / R.chapters * 100 : 0;
      return { data: { c: R.chapter, r: r.toFixed(4) }, pct: pct, label: Math.floor(pct) + '% · ' + R.chapter + '/' + R.chapters + '장' };
    }
    if (!pdf || !pdf.count) return null;
    var p = pdf.currentPage();
    return { data: { p: p }, pct: p / pdf.count * 100, label: p + ' / ' + pdf.count + '쪽' };
  }

  function sendProgress(beacon) {
    if (!R.save) return;
    var pos = currentPosition();
    if (!pos) return;
    var body = new URLSearchParams();
    body.set('csrf', R.csrf);
    body.set('pct', Math.round(pos.pct));
    Object.keys(pos.data).forEach(function (k) { body.set(k, pos.data[k]); });
    var key = body.toString();
    if (key === lastSent) return;
    lastSent = key;
    var url = '/read/' + R.bookId + '/progress';
    if (beacon && navigator.sendBeacon) {
      navigator.sendBeacon(url, new Blob([key], { type: 'application/x-www-form-urlencoded' }));
    } else {
      fetch(url, { method: 'POST', body: body, credentials: 'same-origin', keepalive: true }).catch(function () {});
    }
  }

  function onScroll() {
    var pos = currentPosition();
    if (pos) {
      bar.style.width = Math.min(100, pos.pct).toFixed(1) + '%';
      text.textContent = pos.label;
    }
    clearTimeout(saveTimer);
    saveTimer = setTimeout(function () { sendProgress(false); }, 1500);
  }

  document.addEventListener('visibilitychange', function () {
    if (document.visibilityState === 'hidden') sendProgress(true);
  });
  window.addEventListener('pagehide', function () { sendProgress(true); });

  /* ───────── EPUB: 이어 읽기 ───────── */

  if (R.format === 'EPUB') {
    var restore = function () {
      if (typeof R.restore === 'number' && R.restore > 0 && !location.hash) {
        window.scrollTo(0, R.restore * maxScroll());
      }
      onScroll();
    };
    if (document.readyState === 'complete') restore();
    else window.addEventListener('load', restore);
    if (document.fonts && document.fonts.ready) document.fonts.ready.then(function () { if (!location.hash && typeof R.restore === 'number') restore(); });
  }

  /* ───────── PDF: 화면 폭에 맞춰 쪽 그리기 ───────── */

  var pdf = null;
  if (R.format === 'PDF') initPdf();

  function initPdf() {
    var box = document.getElementById('rd-pages');
    var loading = document.getElementById('rd-loading');
    var doc = null;
    var pages = [];
    var observer = null;

    pdf = {
      count: 0,
      currentPage: function () {
        var line = window.innerHeight * 0.35;
        var current = 1;
        for (var i = 0; i < pages.length; i++) {
          if (pages[i].el.getBoundingClientRect().top <= line) current = i + 1;
          else break;
        }
        return current;
      },
      relayout: function (keep) {
        var p = keep ? pdf.currentPage() : 0;
        var gutter = window.innerWidth >= 600 ? 32 : 0;
        var fit = Math.min(box.clientWidth - gutter, 920);
        var width = settings.zoom === 0 ? fit : fit * ZOOMS[settings.zoom - 1];
        box.style.setProperty('--pdf-w', Math.floor(width) + 'px');
        box.style.alignItems = width > box.clientWidth ? 'flex-start' : 'center';
        pages.forEach(function (pg) { pg.dirty = true; });
        if (p) scrollToPage(p);
        refreshVisible();
      }
    };

    function scrollToPage(n) {
      var pg = pages[n - 1];
      if (!pg) return;
      var top = pg.el.getBoundingClientRect().top + window.scrollY - (document.getElementById('rd-bar').offsetHeight + 8);
      window.scrollTo(0, Math.max(0, top));
    }

    function render(pg) {
      if (pg.busy) { pg.again = true; return; }
      pg.busy = true;
      pg.dirty = false;
      doc.getPage(pg.n).then(function (page) {
        var base = page.getViewport({ scale: 1 });
        pg.el.style.aspectRatio = base.width + ' / ' + base.height;
        var dpr = Math.min(window.devicePixelRatio || 1, 2);
        var viewport = page.getViewport({ scale: pg.el.clientWidth / base.width * dpr });
        var canvas = document.createElement('canvas');
        canvas.width = Math.floor(viewport.width);
        canvas.height = Math.floor(viewport.height);
        var ctx = canvas.getContext('2d');
        return page.render({ canvasContext: ctx, viewport: viewport }).promise.then(function () {
          pg.el.textContent = '';
          pg.el.appendChild(canvas);
          page.cleanup();
        });
      }).catch(function () {}).then(function () {
        pg.busy = false;
        if (pg.again || (pg.visible && pg.dirty)) { pg.again = false; render(pg); }
      });
    }

    function refreshVisible() {
      pages.forEach(function (pg) { if (pg.visible && pg.dirty) render(pg); });
    }

    function start(buffer) {
      return import(PDFJS + 'pdf.min.mjs').then(function (pdfjs) {
        pdfjs.GlobalWorkerOptions.workerSrc = PDFJS + 'pdf.worker.min.mjs';
        return pdfjs.getDocument({ data: new Uint8Array(buffer) }).promise;
      }).then(function (d) {
        doc = d;
        pdf.count = d.numPages;
        return d.getPage(1);
      }).then(function (first) {
        var base = first.getViewport({ scale: 1 });
        loading.remove();
        for (var n = 1; n <= pdf.count; n++) {
          var el = document.createElement('div');
          el.className = 'rd-page';
          el.setAttribute('role', 'img');
          el.setAttribute('aria-label', n + '쪽');
          el.setAttribute('data-n', n);
          el.style.aspectRatio = base.width + ' / ' + base.height;
          box.appendChild(el);
          pages.push({ n: n, el: el, dirty: true, visible: false, busy: false });
        }
        // 보이는 쪽 근처만 그리고, 멀어진 쪽은 지워서 메모리를 아낍니다.
        observer = new IntersectionObserver(function (entries) {
          entries.forEach(function (entry) {
            var pg = pages[Number(entry.target.getAttribute('data-n')) - 1];
            pg.visible = entry.isIntersecting;
            if (pg.visible && (pg.dirty || !pg.el.firstChild)) render(pg);
            if (!pg.visible && pg.el.firstChild && !pg.busy) { pg.el.textContent = ''; pg.dirty = true; }
          });
        }, { rootMargin: '1500px 0px' });
        pages.forEach(function (pg) { observer.observe(pg.el); });
        pdf.relayout(false);
        if (R.page > 1) scrollToPage(Math.min(R.page, pdf.count));
        onScroll();
      });
    }

    fetch(R.fileUrl, { credentials: 'same-origin' })
      .then(function (res) {
        if (!res.ok) throw new Error('파일을 불러오지 못했어요.');
        return res.arrayBuffer();
      })
      .then(start)
      .catch(function () {
        loading.textContent = '책을 불러오지 못했어요. 인터넷 연결을 확인하고 새로고침해 주세요.';
      });

    var resizeTimer = null;
    window.addEventListener('resize', function () {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(function () { if (pages.length) pdf.relayout(true); }, 200);
    });
  }
})();
