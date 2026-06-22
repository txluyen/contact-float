(function () {
  'use strict';

  var gate    = document.getElementById('cp-gate');
  var gateIn  = document.getElementById('cp-gate-input');
  var gateBtn = document.getElementById('cp-gate-btn');
  var gateErr = document.getElementById('cp-gate-error');
  var app     = document.getElementById('cp-app');

  // ── Password gate ─────────────────────────────────────────
  var SESS_KEY = 'cp_authed';

  function showApp() {
    gate.style.display = 'none';
    app.classList.add('visible');
    initFirebase();
  }

  if (sessionStorage.getItem(SESS_KEY) === '1') {
    showApp();
  } else {
    gateBtn.addEventListener('click', checkPassword);
    gateIn.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') checkPassword();
    });
  }

  function checkPassword() {
    if (gateIn.value === CHAT_PANEL_PASSWORD) {
      sessionStorage.setItem(SESS_KEY, '1');
      showApp();
    } else {
      gateErr.removeAttribute('hidden');
      gateIn.value = '';
      gateIn.focus();
    }
  }

  // ── Firebase ─────────────────────────────────────────────
  var auth, db;

  function initFirebase() {
    firebase.initializeApp(FIREBASE_CONFIG);
    auth = firebase.auth();
    db   = firebase.database();

    auth.signInWithEmailAndPassword(ADMIN_EMAIL, ADMIN_PASSWORD)
      .then(function () {
        setAdminOnline(true);
        initApp();
      })
      .catch(function (err) {
        alert('Lỗi đăng nhập Firebase: ' + err.message);
      });
  }

  // ── Admin presence ────────────────────────────────────────
  function setAdminOnline(online) {
    var presenceRef = db.ref('presence/admin_online');
    presenceRef.set(online);
    if (online) {
      presenceRef.onDisconnect().set(false);
    }
  }

  // Stubs — implemented in Task 7 & 8
  function initApp() {
    initAnnouncement();
    loadConversations();
  }

  function initAnnouncement() {}
  function loadConversations() {}
})();
