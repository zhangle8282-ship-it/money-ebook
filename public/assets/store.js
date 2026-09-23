// 전자책 스토어 — 공개 화면 동작: 미리보기 펼치기, 별점 고르기, 확인 창, 계좌번호 복사
(function () {
  'use strict';

  // 미리보기 계속 읽기 / 접기
  document.querySelectorAll('[data-reader-toggle]').forEach(function (btn) {
    var reader = document.getElementById(btn.getAttribute('aria-controls'));
    if (!reader) return;
    btn.addEventListener('click', function () {
      var collapsed = reader.classList.toggle('is-collapsed');
      btn.setAttribute('aria-expanded', String(!collapsed));
      btn.textContent = collapsed ? '미리보기 계속 읽기' : '미리보기 접기';
      if (collapsed) {
        reader.scrollTop = 0;
        reader.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
      } else {
        reader.focus({ preventScroll: true });
      }
    });
  });

  // 리뷰 별점: 고른 별까지 색칠하고, 올려 두면 미리 보여 줍니다.
  document.querySelectorAll('.star-picker').forEach(function (picker) {
    var labels = Array.prototype.slice.call(picker.querySelectorAll('.star-btn'));
    var text = picker.querySelector('.rate-text');
    function paint(n) {
      labels.forEach(function (label) {
        label.classList.toggle('is-on', Number(label.getAttribute('data-star')) <= n);
      });
    }
    function checked() {
      var input = picker.querySelector('input:checked');
      return input ? Number(input.value) : 0;
    }
    picker.addEventListener('change', function () {
      var n = checked();
      paint(n);
      if (text) text.textContent = n + '점';
    });
    labels.forEach(function (label) {
      label.addEventListener('mouseenter', function () { paint(Number(label.getAttribute('data-star'))); });
    });
    picker.addEventListener('mouseleave', function () { paint(checked()); });
  });

  // 삭제·취소처럼 되돌리기 어려운 동작은 한 번 더 묻습니다.
  document.addEventListener('submit', function (event) {
    var form = event.target;
    var message = form.getAttribute('data-confirm');
    if (message && !window.confirm(message)) event.preventDefault();
  });

  // 계좌번호 복사
  document.querySelectorAll('[data-copy]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var target = document.querySelector(btn.getAttribute('data-copy'));
      if (!target) return;
      var value = target.textContent.trim();
      var done = function () {
        btn.textContent = '복사됨';
        setTimeout(function () { btn.textContent = '복사'; }, 1600);
      };
      if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(value).then(done, function () { window.prompt('계좌번호를 복사하세요', value); });
      } else {
        window.prompt('계좌번호를 복사하세요', value);
      }
    });
  });
})();
