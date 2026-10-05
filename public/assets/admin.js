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

// 사진 관리: 큰 사진은 올리기 전에 가로·세로 1600px 안으로 줄이고(JPG), 미리보기를 바로 바꿉니다.
(function () {
  var inputs = document.querySelectorAll('input[type="file"][data-resize]');
  if (!inputs.length || !window.DataTransfer || !window.URL) return;
  var MAX = 1600;
  inputs.forEach(function (input) {
    input.addEventListener('change', function () {
      var file = input.files && input.files[0];
      if (!file || !/^image\/(jpeg|png|webp)$/.test(file.type)) return;
      var url = URL.createObjectURL(file);
      var img = new Image();
      img.onload = function () {
        var preview = input.closest('.photo-slot') && input.closest('.photo-slot').querySelector('.photo-preview');
        if (preview) preview.innerHTML = '<img alt="" src="' + url + '">';
        var scale = Math.min(1, MAX / Math.max(img.naturalWidth, img.naturalHeight));
        if (scale === 1 && file.size < 900 * 1024) return;
        var canvas = document.createElement('canvas');
        canvas.width = Math.round(img.naturalWidth * scale);
        canvas.height = Math.round(img.naturalHeight * scale);
        var ctx = canvas.getContext('2d');
        ctx.fillStyle = '#fff';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
        canvas.toBlob(function (blob) {
          if (!blob || blob.size >= file.size) return;
          var dt = new DataTransfer();
          dt.items.add(new File([blob], file.name.replace(/\.[^.]+$/, '') + '.jpg', { type: 'image/jpeg' }));
          input.files = dt.files;
        }, 'image/jpeg', 0.85);
      };
      img.src = url;
    });
  });
})();

// 도급 정산: 금액 칸 천 단위 쉼표, 새 청소 화면 한 달 정산 미리보기(세금 10% → 도급 → 갑·을)
(function () {
  document.querySelectorAll('input[data-money]').forEach(function (input) {
    input.addEventListener('input', function () {
      var digits = input.value.replace(/[^0-9]/g, '');
      input.value = digits ? Number(digits).toLocaleString('ko-KR') : '';
    });
  });
  var form = document.querySelector('[data-contract-form]');
  if (!form) return;
  var won = function (n) { return Number(n).toLocaleString('ko-KR') + '원'; };
  var set = function (key, text) { form.querySelectorAll('[data-calc="' + key + '"]').forEach(function (el) { el.textContent = text; }); };
  function calc() {
    var fee = Number((form.monthly_fee.value || '').replace(/[^0-9]/g, '')) || 0;
    var invoice = (form.querySelector('input[name="invoice"]:checked') || {}).value !== '0';
    var rate = Number((form.querySelector('input[name="contract_rate"]:checked') || {}).value || 20);
    var gap = Math.max(0, Math.min(100, Number(form.gap_rate.value) || 0));
    var wh = (form.querySelector('input[name="withholding"]:checked') || {}).value !== '0';
    var tax = invoice ? Math.round(fee * 0.1) : 0;
    var after = fee - tax;
    var contract = Math.round(after * rate / 100);
    var byeong = after - contract;
    // 원천징수 3.3% = 소득세 3% + 지방소득세(소득세의 10%), 각각 10원 아래는 버림
    var incomeTax = wh ? Math.floor(byeong * 3 / 100 / 10) * 10 : 0;
    var localTax = wh ? Math.floor(incomeTax / 10 / 10) * 10 : 0;
    var withheld = incomeTax + localTax;
    var gapAmt = Math.round(contract * gap / 100);
    set('fee', won(fee));
    set('tax', tax ? '− ' + won(tax) : '0원 (발행 안 함)');
    set('after_tax', won(after));
    set('rate', rate);
    set('byeong_rate', 100 - rate);
    set('byeong_rate2', 100 - rate);
    set('byeong', won(byeong));
    set('withholding', withheld ? won(withheld) : '0원');
    set('income_tax', incomeTax.toLocaleString('ko-KR'));
    set('local_tax', localTax.toLocaleString('ko-KR'));
    set('byeong_pay', won(byeong - withheld));
    set('contract', won(contract));
    set('gap_rate', gap);
    set('eul_rate', 100 - gap);
    set('eul_rate2', 100 - gap);
    set('gap', won(gapAmt));
    set('eul', won(contract - gapAmt));
  }
  form.addEventListener('input', calc);
  form.addEventListener('change', calc);
  form.querySelectorAll('[data-gap]').forEach(function (b) {
    b.addEventListener('click', function () { form.gap_rate.value = b.getAttribute('data-gap'); calc(); });
  });
  calc();
})();

// 블로그 글쓰기: 꾸미기 단추, 사진 넣기(올리기 전 줄이기), 검색 결과 미리보기, 검색 점검
(function () {
  var form = document.querySelector('[data-blog-form]');
  if (!form) return;
  var area = form.querySelector('textarea[name="body"]');
  var status = form.querySelector('[data-md-status]');
  var fileInput = form.querySelector('[data-md-file]');

  function insert(before, after, placeholder, lineStart) {
    var start = area.selectionStart, end = area.selectionEnd;
    var text = area.value;
    var sel = text.slice(start, end) || placeholder;
    var prefix = before;
    if (lineStart && start > 0 && text.charAt(start - 1) !== '\n') prefix = '\n' + before;
    area.value = text.slice(0, start) + prefix + sel + after + text.slice(end);
    var caret = start + prefix.length;
    area.focus();
    area.setSelectionRange(caret, caret + sel.length);
    update();
  }
  form.querySelectorAll('[data-md]').forEach(function (b) {
    b.addEventListener('click', function () {
      var t = b.getAttribute('data-md');
      if (t === 'h2') insert('## ', '\n', '소제목', true);
      else if (t === 'h3') insert('### ', '\n', '작은 제목', true);
      else if (t === 'bold') insert('**', '**', '굵게 쓸 글');
      else if (t === 'list') insert('- ', '\n', '목록 내용', true);
      else if (t === 'quote') insert('> ', '\n', '인용할 글', true);
      else if (t === 'link') {
        var url = window.prompt('연결할 주소를 넣어 주세요 (https://…)', 'https://');
        if (url && /^https?:\/\//.test(url)) insert('[', '](' + url + ')', '링크 글자');
      } else if (t === 'image') fileInput.click();
    });
  });

  function shrink(file) {
    return new Promise(function (resolve) {
      if (!/^image\/(jpeg|png|webp)$/.test(file.type)) return resolve(file);
      var img = new Image();
      img.onload = function () {
        var scale = Math.min(1, 1600 / Math.max(img.naturalWidth, img.naturalHeight));
        if (scale === 1 && file.size < 900 * 1024) return resolve(file);
        var c = document.createElement('canvas');
        c.width = Math.round(img.naturalWidth * scale);
        c.height = Math.round(img.naturalHeight * scale);
        var ctx = c.getContext('2d');
        ctx.fillStyle = '#fff';
        ctx.fillRect(0, 0, c.width, c.height);
        ctx.drawImage(img, 0, 0, c.width, c.height);
        c.toBlob(function (blob) { resolve(blob ? new File([blob], 'photo.jpg', { type: 'image/jpeg' }) : file); }, 'image/jpeg', 0.85);
      };
      img.onerror = function () { resolve(file); };
      img.src = URL.createObjectURL(file);
    });
  }
  fileInput.addEventListener('change', function () {
    var file = fileInput.files && fileInput.files[0];
    if (!file) return;
    status.textContent = '사진 올리는 중…';
    shrink(file).then(function (small) {
      var fd = new FormData();
      fd.append('csrf', form.querySelector('input[name="csrf"]').value);
      fd.append('image', small);
      return fetch(form.getAttribute('data-upload-url'), { method: 'POST', body: fd, credentials: 'same-origin', headers: { Accept: 'application/json' } });
    }).then(function (r) { return r.json(); }).then(function (d) {
      if (!d.ok) throw new Error(d.error || '올리지 못했어요');
      var alt = window.prompt('사진 설명(검색에 도움돼요). 예: 금왕 사무실 바닥 청소 후', '') || '';
      insert('\n![' + alt.replace(/[\[\]]/g, '') + '](' + d.path + ')\n', '', '', false);
      status.textContent = '사진을 넣었어요.';
    }).catch(function (e) { status.textContent = '사진을 넣지 못했어요: ' + e.message; })
      .then(function () { fileInput.value = ''; });
  });

  // 검색 결과 미리보기 · 점검
  var get = function (n) { var el = form.querySelector('[name="' + n + '"]'); return el ? el.value.trim() : ''; };
  var plain = function (t) {
    return t.replace(/!\[[^\]]*\]\([^)]*\)/g, '').replace(/\[([^\]]+)\]\([^)]*\)/g, '$1').replace(/^\s*(#{2,3}|[-*]|>)\s+/gm, '').replace(/\*\*/g, '').replace(/\s+/g, ' ').trim();
  };
  var slugify = function (t) { return t.toLowerCase().replace(/[^0-9a-z\u3131-\uD79D]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 60); };
  var siteName = document.querySelector('.brand-name') ? document.querySelector('.brand-name').textContent.trim() : '';
  function update() {
    var title = get('title'), body = area.value, summary = get('summary'), keywords = get('keywords');
    var bodyText = plain(body);
    var desc = summary || bodyText.slice(0, 150);
    form.querySelector('[data-serp="title"]').textContent = get('seo_title') || ((title || '글 제목') + (siteName ? ' | ' + siteName : ''));
    form.querySelector('[data-serp="desc"]').textContent = desc || '검색 설명이 여기에 보여요.';
    form.querySelector('[data-serp="slug"]').textContent = get('slug') || slugify(title) || '글-주소';
    var cnt = form.querySelector('[data-count="summary"]');
    if (cnt) cnt.textContent = summary.length + '/160';
    var kws = keywords.split(',').map(function (k) { return k.trim().replace(/\s+/g, ''); }).filter(Boolean);
    var flat = bodyText.replace(/\s+/g, '');
    var checks = {
      region: /(음성|금왕|대소|진천|혁신도시)/.test(title),
      title_len: title.length >= 15 && title.length <= 40,
      desc_len: desc.length >= 50 && desc.length <= 160,
      keyword: kws.length > 0 && kws.some(function (k) { return flat.indexOf(k) !== -1 || bodyText.indexOf(k) !== -1; }),
      body_len: bodyText.length >= 800,
      heading: (body.match(/^##\s+/gm) || []).length >= 2,
      image: /!\[[^\]]*\]\([^)]+\)/.test(body) || !!form.querySelector('.photo-preview img') || (form.querySelector('input[name="cover"]') && form.querySelector('input[name="cover"]').files.length > 0)
    };
    form.querySelectorAll('[data-check]').forEach(function (li) { li.classList.toggle('ok', !!checks[li.getAttribute('data-check')]); });
  }
  form.addEventListener('input', update);
  form.addEventListener('change', update);
  update();
})();

// 계좌 복사: 은행 계좌번호 예금주를 클립보드로
document.addEventListener('click', function (e) {
  var btn = e.target.closest && e.target.closest('[data-copy-text]');
  if (!btn) return;
  var text = btn.getAttribute('data-copy-text');
  var done = function () { var old = btn.textContent; btn.textContent = '복사됨'; setTimeout(function () { btn.textContent = old; }, 1500); };
  if (navigator.clipboard && navigator.clipboard.writeText) {
    navigator.clipboard.writeText(text).then(done, function () { window.prompt('복사해 주세요', text); });
  } else {
    window.prompt('복사해 주세요', text);
  }
});

// 헤드 코드: Tab 들여쓰기, 줄·글자 수, 저장할 때 base64로 감싸 보내기(웹 방화벽이 <script> 를 막지 않게)
(function () {
  var form = document.querySelector('[data-code-form]');
  if (!form) return;
  var areas = form.querySelectorAll('textarea.code-area');
  var count = function (ta) {
    var el = form.querySelector('[data-code-count="' + ta.name + '"]');
    if (el) el.textContent = (ta.value ? ta.value.split('\n').length : 0) + '줄 · ' + ta.value.length.toLocaleString('ko-KR') + '자';
  };
  areas.forEach(function (ta) {
    count(ta);
    ta.addEventListener('input', function () { count(ta); });
    ta.addEventListener('keydown', function (e) {
      if (e.key !== 'Tab' || e.shiftKey || e.metaKey || e.ctrlKey || e.altKey) return;
      e.preventDefault();
      var s = ta.selectionStart, en = ta.selectionEnd;
      ta.value = ta.value.slice(0, s) + '  ' + ta.value.slice(en);
      ta.selectionStart = ta.selectionEnd = s + 2;
      count(ta);
    });
  });
  form.addEventListener('submit', function () {
    areas.forEach(function (ta) {
      var hidden = form.querySelector('input[name="' + ta.name + '_b64"]');
      if (!hidden) {
        hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = ta.name + '_b64';
        form.appendChild(hidden);
      }
      var bytes = new TextEncoder().encode(ta.value);
      var bin = '';
      bytes.forEach(function (b) { bin += String.fromCharCode(b); });
      hidden.value = ta.value === '' ? '' : btoa(bin);
      ta.dataset.name = ta.name;
      ta.removeAttribute('name'); // 원래 글자는 보내지 않음
    });
  });
})();

// 검색 등록: 확인 태그(<meta … content="값">)를 통째로 붙여 넣으면 값만 남겨 보내기(웹 방화벽이 태그를 막지 않게)
document.querySelectorAll('input[data-verify]').forEach(function (input) {
  var clean = function () {
    var m = input.value.match(/content\s*=\s*["']([^"']+)["']/i);
    if (m) input.value = m[1];
  };
  input.addEventListener('change', clean);
  input.addEventListener('paste', function () { setTimeout(clean, 0); });
  if (input.form) input.form.addEventListener('submit', clean);
});
