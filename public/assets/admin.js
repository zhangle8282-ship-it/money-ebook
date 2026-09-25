// 전자책 스토어 — 관리자 화면 동작
// 등록 화면: 미리보기 방식 전환, 표지·파일 끌어놓기, 입력 확인, PDF 미리보기 이미지 만들기(pdf.js)
(function () {
  'use strict';

  var PDFJS = 'https://cdn.jsdelivr.net/npm/pdfjs-dist@4.10.38/build/';

  // 되돌리기 어려운 동작은 한 번 더 묻습니다.
  document.addEventListener('submit', function (event) {
    var message = event.target.getAttribute('data-confirm');
    if (message && !window.confirm(message)) event.preventDefault();
  });
  document.addEventListener('click', function (event) {
    var btn = event.target.closest('[data-confirm-click]');
    if (btn && !window.confirm(btn.getAttribute('data-confirm-click'))) event.preventDefault();
  });

  // 서버호스팅 비밀번호: '보기'를 눌렀을 때만 보여 줍니다.
  document.addEventListener('click', function (event) {
    var btn = event.target.closest('[data-reveal]');
    if (!btn) return;
    var box = btn.parentNode;
    var secret = box.querySelector('[data-secret]');
    var mask = box.querySelector('[data-secret-mask]');
    var show = secret.hidden;
    secret.hidden = !show;
    mask.hidden = show;
    btn.textContent = show ? '숨기기' : '보기';
  });

  // 디자인: 고르는 즉시 미리보기에 반영
  var designForm = document.getElementById('design-form');
  if (designForm) {
    var preview = document.getElementById('design-preview');
    var val = function (name) {
      var el = designForm.querySelector('[name="' + name + '"]:checked') || designForm.querySelector('[name="' + name + '"]');
      return el ? el.value : '';
    };
    var family = function (name) {
      var sel = designForm.querySelector('select[name="' + name + '"]');
      return sel.options[sel.selectedIndex].getAttribute('data-family');
    };
    var dark = function (hex) {
      var c = [1, 3, 5].map(function (i) {
        var v = parseInt(hex.substr(i, 2), 16) / 255;
        return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4);
      });
      return 0.2126 * c[0] + 0.7152 * c[1] + 0.0722 * c[2] < 0.36;
    };
    var logoUrl = document.getElementById('logo-img').getAttribute('src');
    var paint = function () {
      var st = preview.style;
      ['header', 'footer'].forEach(function (area) {
        var bg = val('design_' + area + '_bg');
        st.setProperty('--p-' + area + '-bg', bg);
        st.setProperty('--p-' + area + '-fg', dark(bg) ? '#F6F4EF' : '#1D1C1A');
        st.setProperty('--p-' + area + '-sub', dark(bg) ? 'rgba(246,244,239,.74)' : '#5F5B53');
      });
      st.setProperty('--p-main-bg', val('design_main_bg'));
      st.setProperty('--p-header-h', val('design_header_h') + 'px');
      st.setProperty('--p-header-font', family('design_header_font'));
      st.setProperty('--p-header-size', val('design_header_size') + 'px');
      st.setProperty('--p-logo-font', family('design_logo_font'));
      st.setProperty('--p-logo-size', val('design_logo_size') + 'px');
      st.setProperty('--p-logo-h', val('design_logo_height') + 'px');
      st.setProperty('--p-body-font', family('design_body_font'));
      st.setProperty('--p-heading-font', family('design_heading_font'));
      st.setProperty('--p-scale', Number(val('design_body_size')) / 16);
      st.setProperty('--p-footer-font', family('design_footer_font'));
      st.setProperty('--p-footer-scale', Number(val('design_footer_size')) / 13);
      var image = val('design_logo_type') === 'image';
      designForm.querySelectorAll('[data-logo-panel]').forEach(function (p) { p.hidden = p.getAttribute('data-logo-panel') !== (image ? 'image' : 'text'); });
      var img = document.getElementById('dp-logo-img');
      img.hidden = !(image && logoUrl);
      if (logoUrl) img.src = logoUrl;
      document.getElementById('dp-logo-text').hidden = image && !!logoUrl;
      designForm.querySelectorAll('[data-out]').forEach(function (o) {
        var input = designForm.querySelector('[name="' + o.getAttribute('data-out') + '"]');
        o.textContent = input.value + (input.getAttribute('data-unit') || '');
      });
    };
    designForm.addEventListener('input', paint);
    designForm.addEventListener('change', paint);
    designForm.querySelectorAll('[data-swatch]').forEach(function (b) {
      b.addEventListener('click', function () {
        document.getElementById(b.getAttribute('data-swatch')).value = b.getAttribute('data-color');
        paint();
      });
    });
    document.getElementById('logo_image').addEventListener('change', function (e) {
      var f = e.target.files[0];
      if (!f) return;
      logoUrl = URL.createObjectURL(f);
      var cur = document.getElementById('logo-img');
      cur.src = logoUrl;
      cur.hidden = false;
      document.getElementById('logo-empty').hidden = true;
      paint();
    });
    paint();
  }

  var form = document.getElementById('book-form');
  if (!form) return;

  var statusBox = form.querySelector('.form-status');
  var dirty = false;
  form.addEventListener('input', function () { dirty = true; });
  form.addEventListener('change', function () { dirty = true; });
  window.addEventListener('beforeunload', function (event) {
    if (dirty) { event.preventDefault(); event.returnValue = ''; }
  });

  function showStatus(message, isError) {
    statusBox.hidden = !message;
    statusBox.textContent = message || '';
    statusBox.className = 'form-status' + (isError ? ' alert' : '');
  }

  function formatSize(bytes) {
    return bytes >= 1048576 ? (bytes / 1048576).toFixed(1) + 'MB' : Math.max(1, Math.round(bytes / 1024)) + 'KB';
  }

  // 숫자 칸: 숫자만 남기고 판매가는 천 단위 쉼표
  form.querySelectorAll('[data-number]').forEach(function (input) {
    input.addEventListener('input', function () {
      var digits = input.value.replace(/[^0-9]/g, '');
      input.value = input.name === 'price' && digits ? Number(digits).toLocaleString('ko-KR') : digits;
    });
  });

  // 미리보기 방식: 앞부분 자동 공개 / 직접 입력
  function syncMode() {
    var mode = form.querySelector('input[name="preview_mode"]:checked').value;
    form.querySelectorAll('[data-mode-panel]').forEach(function (panel) {
      panel.hidden = panel.getAttribute('data-mode-panel') !== mode;
    });
  }
  form.querySelectorAll('input[name="preview_mode"]').forEach(function (r) { r.addEventListener('change', syncMode); });
  syncMode();

  // 끌어다 놓기
  form.querySelectorAll('[data-dropzone]').forEach(function (zone) {
    var input = zone.querySelector('input[type="file"]');
    ['dragenter', 'dragover'].forEach(function (type) {
      zone.addEventListener(type, function (event) { event.preventDefault(); zone.classList.add('is-over'); });
    });
    ['dragleave', 'drop'].forEach(function (type) {
      zone.addEventListener(type, function () { zone.classList.remove('is-over'); });
    });
    zone.addEventListener('drop', function (event) {
      event.preventDefault();
      if (!event.dataTransfer.files.length) return;
      var dt = new DataTransfer();
      dt.items.add(event.dataTransfer.files[0]);
      input.files = dt.files;
      input.dispatchEvent(new Event('change', { bubbles: true }));
    });
  });

  // 표지
  var coverInput = form.querySelector('input[name="cover"]');
  var coverZone = coverInput.closest('.dropzone');
  var coverImg = coverZone.querySelector('.dropzone-img');
  var removeCover = form.querySelector('[data-remove-cover]');
  var removeCoverFlag = form.querySelector('input[name="remove_cover"]');
  coverInput.addEventListener('change', function () {
    var file = coverInput.files[0];
    if (!file) return;
    coverImg.src = URL.createObjectURL(file);
    coverImg.hidden = false;
    coverZone.classList.add('has-file');
    removeCover.hidden = false;
    removeCoverFlag.value = '';
  });
  removeCover.addEventListener('click', function () {
    coverInput.value = '';
    coverImg.hidden = true;
    coverImg.removeAttribute('src');
    coverZone.classList.remove('has-file');
    removeCover.hidden = true;
    removeCoverFlag.value = '1';
    dirty = true;
  });

  // 전자책 파일
  var fileInput = form.querySelector('input[name="book_file"]');
  var fileRow = form.querySelector('[data-file-row]');
  var fileBadge = fileRow.querySelector('.file-badge');
  var fileName = fileRow.querySelector('.file-name');
  var fileState = fileRow.querySelector('.file-state');
  var clearFile = fileRow.querySelector('[data-clear-file]');
  var original = { hidden: fileRow.hidden, badge: fileBadge.textContent, name: fileName.textContent, state: fileState.textContent };

  function selectedFormat() {
    var file = fileInput.files[0];
    if (file) return /\.pdf$/i.test(file.name) ? 'PDF' : (/\.epub$/i.test(file.name) ? 'EPUB' : '');
    return form.getAttribute('data-format');
  }
  fileInput.addEventListener('change', function () {
    var file = fileInput.files[0];
    if (!file) return;
    var format = selectedFormat();
    fileRow.hidden = false;
    fileBadge.textContent = format || '?';
    fileName.textContent = file.name;
    fileState.textContent = format ? formatSize(file.size) + ' · 등록하면 업로드돼요' : 'EPUB 또는 PDF 파일만 올릴 수 있어요';
    fileState.classList.toggle('warn', !format);
    clearFile.hidden = false;
  });
  clearFile.addEventListener('click', function () {
    fileInput.value = '';
    fileRow.hidden = original.hidden;
    fileBadge.textContent = original.badge;
    fileName.textContent = original.name;
    fileState.textContent = original.state;
    fileState.classList.remove('warn');
    clearFile.hidden = true;
  });

  // 판매 공개 스위치
  var publish = form.querySelector('input[name="publish"]');
  var publishHelp = form.querySelector('[data-publish-help]');
  publish.addEventListener('change', function () {
    publishHelp.textContent = publish.checked ? '등록 즉시 스토어에 노출돼요.' : '스토어에 노출되지 않아요.';
  });

  // 등록하기: 필수 항목 확인
  function validate() {
    var problems = [];
    form.querySelectorAll('.is-invalid').forEach(function (el) { el.classList.remove('is-invalid'); });
    function need(selector, label) {
      var el = form.querySelector(selector);
      if (el.value.trim() === '') {
        (el.closest('.input-suffix') || el).classList.add('is-invalid');
        problems.push({ el: el, label: label });
      }
    }
    need('[name="title"]', '제목');
    need('[name="author"]', '저자');
    need('[name="category"]', '카테고리');
    need('[name="price"]', '판매가');
    if (!fileInput.files[0] && !form.getAttribute('data-format')) problems.push({ el: fileInput, label: '전자책 파일' });
    else if (fileInput.files[0] && !selectedFormat()) problems.push({ el: fileInput, label: '전자책 파일(EPUB·PDF)' });
    if (problems.length) {
      showStatus('입력해 주세요: ' + problems.map(function (p) { return p.label; }).join(', '), true);
      (problems[0].el.type === 'file' ? problems[0].el.closest('.dropzone') : problems[0].el).scrollIntoView({ block: 'center' });
      if (problems[0].el.type !== 'file') problems[0].el.focus({ preventScroll: true });
    }
    return !problems.length;
  }

  // PDF 미리보기가 새로 필요한지: 새 PDF, 쪽수·방식 변경, 아직 이미지가 없을 때
  function needsPdfPreview() {
    var mode = form.querySelector('input[name="preview_mode"]:checked').value;
    if (mode !== 'auto' || selectedFormat() !== 'PDF') return false;
    if (fileInput.files[0]) return true;
    var pages = form.querySelector('[name="preview_pages"]').value;
    return form.getAttribute('data-orig-mode') !== 'auto'
      || pages !== form.getAttribute('data-orig-pages')
      || !form.getAttribute('data-has-images');
  }

  async function renderPdfPreview(count, onProgress) {
    var pdfjs = await import(PDFJS + 'pdf.min.mjs');
    pdfjs.GlobalWorkerOptions.workerSrc = PDFJS + 'pdf.worker.min.mjs';
    var data;
    if (fileInput.files[0]) {
      data = await fileInput.files[0].arrayBuffer();
    } else {
      var res = await fetch(form.getAttribute('data-file-url'), { credentials: 'same-origin' });
      if (!res.ok) throw new Error('원본 파일을 불러오지 못했어요.');
      data = await res.arrayBuffer();
    }
    var pdf = await pdfjs.getDocument({ data: new Uint8Array(data) }).promise;
    var total = Math.min(count, pdf.numPages);
    var files = [];
    for (var i = 1; i <= total; i++) {
      onProgress(i, total);
      var page = await pdf.getPage(i);
      var base = page.getViewport({ scale: 1 });
      var viewport = page.getViewport({ scale: Math.min(2, 1100 / base.width) });
      var canvas = document.createElement('canvas');
      canvas.width = Math.floor(viewport.width);
      canvas.height = Math.floor(viewport.height);
      var ctx = canvas.getContext('2d');
      ctx.fillStyle = '#fff';
      ctx.fillRect(0, 0, canvas.width, canvas.height);
      await page.render({ canvasContext: ctx, viewport: viewport }).promise;
      var blob = await new Promise(function (resolve) { canvas.toBlob(resolve, 'image/jpeg', 0.82); });
      files.push(new File([blob], 'page-' + i + '.jpg', { type: 'image/jpeg' }));
      page.cleanup();
    }
    var numPages = pdf.numPages;
    await pdf.destroy();
    return { files: files, numPages: numPages };
  }

  var intentInput = document.createElement('input');
  intentInput.type = 'hidden';
  intentInput.name = 'intent';
  form.prepend(intentInput);
  var busy = false;

  form.addEventListener('submit', async function (event) {
    if (busy) { event.preventDefault(); return; }
    var submitter = event.submitter;
    intentInput.value = submitter && submitter.value ? submitter.value : 'save';
    var isDraft = intentInput.value === 'draft';
    if (!isDraft && !validate()) { event.preventDefault(); return; }
    if (!needsPdfPreview()) { dirty = false; return; }

    event.preventDefault();
    busy = true;
    var buttons = form.querySelectorAll('button[type="submit"]');
    buttons.forEach(function (b) { b.disabled = true; });
    var count = Math.max(1, Math.min(100, parseInt(form.querySelector('[name="preview_pages"]').value, 10) || 10));
    try {
      var result = await renderPdfPreview(count, function (i, total) {
        showStatus('PDF 미리보기를 만드는 중이에요… (' + i + ' / ' + total + '쪽)');
      });
      var dt = new DataTransfer();
      result.files.forEach(function (f) { dt.items.add(f); });
      form.querySelector('.preview-images-input').files = dt.files;
      form.querySelector('[name="pdf_pages"]').value = String(result.numPages);
      showStatus('업로드하는 중이에요…');
    } catch (err) {
      busy = false;
      buttons.forEach(function (b) { b.disabled = false; });
      showStatus('PDF 미리보기를 만들지 못했어요. 인터넷 연결을 확인하거나 ‘직접 입력’을 써 주세요. (' + (err && err.message ? err.message : err) + ')', true);
      if (!window.confirm('PDF 미리보기 없이 저장할까요?')) return;
      busy = true;
    }
    dirty = false;
    HTMLFormElement.prototype.submit.call(form);
  });
})();

// 설정 › 사업자 정보: 스토어 하단(푸터) 미리보기
(function () {
  var box = document.getElementById('footer-preview');
  if (!box) return;
  var form = box.closest('form');
  var fields = [
    ['biz_name', '상호'], ['biz_owner', '대표'], ['biz_number', '사업자등록번호'], ['biz_mail_order', '통신판매업 신고'],
    ['biz_address', '주소'], ['biz_phone', '고객센터'], ['biz_email', '이메일']
  ];
  var list = box.querySelector('[data-fp-biz]');
  function render() {
    list.textContent = '';
    fields.forEach(function (f) {
      var input = form.querySelector('[name="' + f[0] + '"]');
      var value = input ? input.value.trim() : '';
      if (!value) return;
      var li = document.createElement('li');
      var label = document.createElement('span');
      label.className = 'fp-label';
      label.textContent = f[1];
      li.appendChild(label);
      li.appendChild(document.createTextNode(' ' + value));
      list.appendChild(li);
    });
    list.hidden = !list.children.length;
    var name = form.querySelector('[name="store_name"]');
    box.querySelectorAll('[data-fp-name]').forEach(function (el) { el.textContent = name ? name.value.trim() : ''; });
  }
  form.addEventListener('input', render);
  render();
})();
