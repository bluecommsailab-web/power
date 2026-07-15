/* =====================================================
 *  파워머니플랜 프론트 ↔ 어드민 연동 스크립트
 *  HTML 디자인은 건드리지 않고, </body> 직전에 아래 한 줄만 추가하면 됩니다.
 *    <script src="powermoney_admin/form-connect.js"></script>
 *
 *  하는 일:
 *   1) 첫 방문 시 URL의 utm_* 파라미터 + 리퍼러를 localStorage에 저장 (30일, 최초 유입 기준)
 *   2) 접속자 로그 핑 (IP당 하루 1회 서버 기록)
 *   3) 기존 fakeSubmit 을 실제 접수 API 호출로 교체 + 입력 검증 + 고급 알림 모달
 * ===================================================== */
(function () {
  'use strict';

  var API_URL = 'powermoney_admin/api/apply.php'; // 도메인 루트가 다르면 절대경로로 수정
  var TRACK_URL = 'powermoney_admin/api/track.php';
  var LS_KEY = 'pm_utm_v1';
  var TTL = 30 * 24 * 3600 * 1000; // 30일

  /* ---------- 1. UTM / 리퍼러 저장 (첫 유입 기준 유지) ---------- */
  function captureUtm() {
    var qs = new URLSearchParams(location.search);
    var hasUtm = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content']
      .some(function (k) { return qs.get(k); });

    var saved = null;
    try { saved = JSON.parse(localStorage.getItem(LS_KEY) || 'null'); } catch (e) {}
    if (saved && Date.now() - saved.ts > TTL) saved = null;

    if (saved && !hasUtm) return saved; // 최초 유입 정보 유지 (first-touch)

    var data = {
      ts: Date.now(),
      utm_source:   qs.get('utm_source')   || (saved && saved.utm_source)   || '',
      utm_medium:   qs.get('utm_medium')   || (saved && saved.utm_medium)   || '',
      utm_campaign: qs.get('utm_campaign') || (saved && saved.utm_campaign) || '',
      utm_term:     qs.get('utm_term')     || (saved && saved.utm_term)     || '',
      utm_content:  qs.get('utm_content')  || (saved && saved.utm_content)  || '',
      referrer:     (saved && saved.referrer) || document.referrer || '',
      landing_url:  (saved && saved.landing_url) || location.href.slice(0, 500)
    };
    try { localStorage.setItem(LS_KEY, JSON.stringify(data)); } catch (e) {}
    return data;
  }
  var utm = captureUtm();

  /* ---------- 2. 접속자 로그 핑 (실패해도 화면에 영향 없음) ---------- */
  try {
    var trackBody = new URLSearchParams({
      utm_source: utm.utm_source, utm_term: utm.utm_term,
      referrer: utm.referrer, landing_url: utm.landing_url
    });
    if (navigator.sendBeacon) {
      navigator.sendBeacon(TRACK_URL, trackBody);
    } else {
      fetch(TRACK_URL, { method: 'POST', body: trackBody, keepalive: true }).catch(function () {});
    }
  } catch (e) {}

  /* ---------- 3. 알림 모달 (사이트 톤에 맞춘 고급 스타일) ---------- */
  function ensureModal() {
    if (document.getElementById('pmNoticeWrap')) return;

    var style = document.createElement('style');
    style.textContent = [
      '#pmNoticeWrap{position:fixed;inset:0;z-index:99999;display:none;align-items:center;justify-content:center;padding:20px;',
      '  background:rgba(9,15,30,.58);backdrop-filter:blur(7px);-webkit-backdrop-filter:blur(7px)}',
      '#pmNoticeWrap.show{display:flex;animation:pmFade .28s ease}',
      '#pmNoticeCard{width:min(460px,94vw);background:#fff;border-radius:24px;padding:48px 36px 34px;text-align:center;',
      '  box-shadow:0 30px 80px rgba(9,15,30,.35),0 4px 18px rgba(9,15,30,.18);animation:pmPop .42s cubic-bezier(.2,.9,.3,1.2)}',
      '#pmNoticeIcon{width:76px;height:76px;margin:0 auto 22px;border-radius:50%;display:flex;align-items:center;justify-content:center}',
      '#pmNoticeIcon.ok{background:linear-gradient(135deg,#2563eb,#1e3a8a);box-shadow:0 12px 28px rgba(37,99,235,.38)}',
      '#pmNoticeIcon.warn{background:linear-gradient(135deg,#f59e0b,#d97706);box-shadow:0 12px 28px rgba(245,158,11,.35)}',
      '#pmNoticeIcon svg{width:38px;height:38px}',
      '#pmNoticeIcon.ok svg path{stroke-dasharray:48;stroke-dashoffset:48;animation:pmDraw .55s .18s ease forwards}',
      '#pmNoticeTitle{font-size:21px;font-weight:800;color:#1b2436;letter-spacing:-.02em;margin:0 0 10px}',
      '#pmNoticeMsg{font-size:15.5px;line-height:1.65;color:#5b6577;margin:0 0 28px;white-space:pre-line}',
      '#pmNoticeBtn{display:block;width:100%;border:0;border-radius:14px;padding:15px 0;font-size:16px;font-weight:700;cursor:pointer;',
      '  color:#fff;background:linear-gradient(135deg,#2563eb,#1d4ed8);box-shadow:0 8px 20px rgba(37,99,235,.3);font-family:inherit}',
      '#pmNoticeBtn:hover{filter:brightness(1.07)}',
      '#pmNoticeSub{margin:16px 0 0;font-size:12.5px;color:#9aa4b5}',
      '@keyframes pmFade{from{opacity:0}to{opacity:1}}',
      '@keyframes pmPop{from{opacity:0;transform:translateY(26px) scale(.93)}to{opacity:1;transform:none}}',
      '@keyframes pmDraw{to{stroke-dashoffset:0}}',
      '@media (prefers-reduced-motion:reduce){#pmNoticeWrap.show,#pmNoticeCard,#pmNoticeIcon.ok svg path{animation:none;stroke-dashoffset:0}}'
    ].join('\n');
    document.head.appendChild(style);

    var wrap = document.createElement('div');
    wrap.id = 'pmNoticeWrap';
    wrap.setAttribute('role', 'dialog');
    wrap.setAttribute('aria-modal', 'true');
    wrap.innerHTML =
      '<div id="pmNoticeCard">' +
      '  <div id="pmNoticeIcon" class="ok"></div>' +
      '  <h3 id="pmNoticeTitle"></h3>' +
      '  <p id="pmNoticeMsg"></p>' +
      '  <button type="button" id="pmNoticeBtn">확인</button>' +
      '  <p id="pmNoticeSub">POWER MONEYPLAN 고객센터</p>' +
      '</div>';
    document.body.appendChild(wrap);

    function close() { wrap.classList.remove('show'); }
    document.getElementById('pmNoticeBtn').addEventListener('click', close);
    wrap.addEventListener('click', function (e) { if (e.target === wrap) close(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });
  }

  var ICON_OK = '<svg viewBox="0 0 24 24" fill="none"><path d="M4.5 12.5l5 5L19.5 7" stroke="#fff" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/></svg>';
  var ICON_WARN = '<svg viewBox="0 0 24 24" fill="none"><path d="M12 6.5v7" stroke="#fff" stroke-width="2.6" stroke-linecap="round"/><circle cx="12" cy="17.4" r="1.5" fill="#fff"/></svg>';

  function notice(type, title, msg) {
    ensureModal();
    var icon = document.getElementById('pmNoticeIcon');
    icon.className = type;
    icon.innerHTML = type === 'ok' ? ICON_OK : ICON_WARN;
    document.getElementById('pmNoticeTitle').textContent = title;
    document.getElementById('pmNoticeMsg').textContent = msg;
    document.getElementById('pmNoticeWrap').classList.add('show');
    document.getElementById('pmNoticeBtn').focus();
  }

  /* ---------- 4. 입력 검증 ---------- */
  function validate(name, phone) {
    if (name.length < 2 || name.length > 6 || /[^가-힣a-zA-Z]/.test(name)) {
      return '성함을 정상적으로 입력해 주세요.\n(한글/영문 2~6자)';
    }
    if (phone.length > 15) {
      return '연락처를 정상적으로 입력해 주세요.';
    }
    var digits = phone.replace(/[-\s]/g, '');
    if (!/^01[016789]\d{7,8}$/.test(digits)) {
      return '연락처를 정상적으로 입력해 주세요.\n(- 는 넣어도, 빼도 됩니다)';
    }
    return '';
  }

  /* 폼 입력칸에 최대 길이 지정 (디자인 변경 없음) */
  document.querySelectorAll('form').forEach(function (form) {
    if (!(form.getAttribute('onsubmit') || '').includes('fakeSubmit')) return;
    var nameInput = form.querySelector('input[type="text"]');
    var telInput = form.querySelector('input[type="tel"]');
    if (nameInput) nameInput.maxLength = 6;
    if (telInput) telInput.maxLength = 15;
  });

  /* ---------- 5. 폼 실제 전송 ---------- */
  var sending = false;

  window.fakeSubmit = function (ev) {
    ev.preventDefault();
    if (sending) return false;

    var form = ev.target;
    var selects = form.querySelectorAll('select');
    var name = ((form.querySelector('input[type="text"]') || {}).value || '').trim();
    var phone = ((form.querySelector('input[type="tel"]') || {}).value || '').trim();

    var err = validate(name, phone);
    if (err) {
      notice('warn', '입력 내용을 확인해 주세요', err);
      return false;
    }

    var payload = new URLSearchParams({
      product: selects[0] ? selects[0].value : '',
      amount:  selects[1] ? selects[1].value : '',
      name: name,
      phone: phone,
      website: '', // 허니팟 (항상 빈값)
      utm_source: utm.utm_source, utm_medium: utm.utm_medium,
      utm_campaign: utm.utm_campaign, utm_term: utm.utm_term, utm_content: utm.utm_content,
      referrer: utm.referrer, landing_url: utm.landing_url
    });

    sending = true;
    var btn = form.querySelector('[type="submit"]');
    var btnLabel = btn ? btn.textContent : '';
    if (btn) { btn.disabled = true; btn.textContent = '접수 중...'; }

    fetch(API_URL, { method: 'POST', body: payload })
      .then(function (res) { return res.json(); })
      .then(function (j) {
        if (j.ok) {
          notice('ok', '접수가 완료되었습니다', j.msg || '정상적으로 접수되었습니다.\n잠시 후 연락드리겠습니다.');
          form.reset();
        } else {
          notice('warn', '접수하지 못했습니다', j.msg || '잠시 후 다시 시도해 주세요.');
        }
      })
      .catch(function () {
        notice('warn', '네트워크 오류', '연결 상태를 확인하신 뒤\n잠시 후 다시 시도해 주세요.');
      })
      .finally(function () {
        sending = false;
        if (btn) { btn.disabled = false; btn.textContent = btnLabel; }
      });

    return false;
  };
})();
