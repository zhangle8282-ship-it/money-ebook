/* 그린청소 홈페이지: 모바일 메뉴, 청소 범위 탭, 서비스 고르고 견적 칸으로 이동, 견적 문의 보내기 */
(function () {
  var header = document.querySelector('[data-header]');
  var menuBtn = document.querySelector('[data-menu-btn]');
  var nav = document.getElementById('g-nav');

  // 머리말: 스크롤하면 그림자
  if (header) {
    var onScroll = function () { header.classList.toggle('is-scrolled', window.scrollY > 8); };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  // 모바일 메뉴 열고 닫기
  if (menuBtn && nav) {
    var setMenu = function (open) {
      nav.classList.toggle('is-open', open);
      menuBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
      menuBtn.querySelector('.g-sr').textContent = open ? '메뉴 닫기' : '메뉴 열기';
    };
    menuBtn.addEventListener('click', function () { setMenu(!nav.classList.contains('is-open')); });
    nav.addEventListener('click', function (e) { if (e.target.closest('a')) setMenu(false); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') setMenu(false); });
  }

  var form = document.querySelector('[data-inquiry-form]');
  var sent = document.querySelector('[data-sent]');
  var quote = document.getElementById('quote');

  function goQuote(focusName) {
    if (!quote) return;
    if (sent && !sent.hidden && form) { sent.hidden = true; form.hidden = false; }
    quote.scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'start' });
    if (focusName && form) {
      setTimeout(function () { form.querySelector('[name="name"]').focus({ preventScroll: true }); }, 450);
    }
  }

  // 서비스를 누르면 그 종류를 고른 채로 견적 칸으로
  document.querySelectorAll('[data-pick-kind]').forEach(function (a) {
    a.addEventListener('click', function (e) {
      if (!form) return;
      e.preventDefault();
      var radio = form.querySelector('input[name="kind"][value="' + a.getAttribute('data-pick-kind') + '"]');
      if (radio) radio.checked = true;
      goQuote(true);
    });
  });
  document.querySelectorAll('[data-go-quote]').forEach(function (a) {
    a.addEventListener('click', function (e) { e.preventDefault(); goQuote(true); });
  });

  // 청소 범위 탭(방향키로도 이동)
  var tabs = Array.prototype.slice.call(document.querySelectorAll('[data-tab]'));
  function selectTab(tab, focus) {
    tabs.forEach(function (t) {
      var on = t === tab;
      t.setAttribute('aria-selected', on ? 'true' : 'false');
      t.tabIndex = on ? 0 : -1;
      var panel = document.getElementById(t.getAttribute('aria-controls'));
      if (panel) { if (on) panel.removeAttribute('data-hidden'); else panel.setAttribute('data-hidden', ''); }
    });
    if (focus) tab.focus();
  }
  tabs.forEach(function (t, i) {
    t.addEventListener('click', function () { selectTab(t, false); });
    t.addEventListener('keydown', function (e) {
      var next = { ArrowDown: 1, ArrowRight: 1, ArrowUp: -1, ArrowLeft: -1 }[e.key];
      if (next) { e.preventDefault(); selectTab(tabs[(i + next + tabs.length) % tabs.length], true); }
    });
  });

  if (!form) return;

  function showErrors(errors) {
    form.querySelectorAll('[data-err]').forEach(function (p) {
      var key = p.getAttribute('data-err');
      p.textContent = errors[key] || '';
      var input = form.querySelector('[name="' + key + '"]');
      if (input && input.type !== 'checkbox') {
        if (errors[key]) input.setAttribute('aria-invalid', 'true'); else input.removeAttribute('aria-invalid');
      }
    });
    var first = Object.keys(errors)[0];
    var target = first && form.querySelector('[name="' + first + '"]');
    if (target && target.focus) target.focus();
  }

  // 견적 문의: 화면을 새로 열지 않고 보내기. 안 되면 일반 전송으로.
  form.addEventListener('submit', function (e) {
    if (!window.fetch || !window.FormData) return;
    e.preventDefault();
    var errors = {};
    if (!form.name.value.trim()) errors.name = '업체명이나 담당자 이름을 적어 주세요.';
    if (form.phone.value.replace(/[^0-9]/g, '').length < 9) errors.phone = '연락받을 전화번호를 숫자로 적어 주세요.';
    if (!form.agree.checked) errors.agree = '개인정보 수집·이용에 동의해 주세요.';
    if (Object.keys(errors).length) { showErrors(errors); return; }
    var button = form.querySelector('button[type="submit"]');
    button.disabled = true;
    button.textContent = '보내는 중…';
    fetch(form.action, { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json' }, credentials: 'same-origin' })
      .then(function (res) { return res.json().then(function (data) { return { ok: res.ok, data: data }; }); })
      .then(function (r) {
        if (r.ok && r.data.ok) {
          showErrors({});
          form.reset();
          form.hidden = true;
          sent.hidden = false;
          sent.focus && sent.setAttribute('tabindex', '-1');
          sent.focus();
        } else {
          showErrors((r.data && r.data.errors) || { form: '보내지 못했어요. 잠시 뒤 다시 시도해 주세요.' });
        }
      })
      .catch(function () { HTMLFormElement.prototype.submit.call(form); })
      .then(function () { button.disabled = false; button.textContent = '견적 문의 보내기'; });
  });

  var reset = document.querySelector('[data-reset]');
  if (reset) {
    reset.addEventListener('click', function (e) {
      e.preventDefault();
      sent.hidden = true;
      form.hidden = false;
      if (location.search.indexOf('sent=1') !== -1 && history.replaceState) history.replaceState(null, '', '/#quote');
      form.querySelector('[name="name"]').focus();
    });
  }
})();
