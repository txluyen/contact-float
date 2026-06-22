(function () {
  'use strict';

  var cfg = window.tquanrealChat;
  if (!cfg || !cfg.firebase.apiKey) return;

  // ── Firebase init ─────────────────────────────────────────
  firebase.initializeApp(cfg.firebase);
  var auth = firebase.auth();
  var db   = firebase.database();

  // ── Session ID ────────────────────────────────────────────
  var STORAGE_KEY = 'tquanreal_chat_session';
  var storage     = cfg.isPremium ? localStorage : sessionStorage;

  function getOrCreateSessionId() {
    var id = storage.getItem(STORAGE_KEY);
    if (!id) {
      id = 'sess_' + Date.now() + '_' + Math.random().toString(36).slice(2, 9);
      storage.setItem(STORAGE_KEY, id);
    }
    return id;
  }

  var sessionId = null;

  // ── DOM refs ──────────────────────────────────────────────
  var widget      = document.getElementById('tquanreal-cf-chat-widget');
  var bubble      = document.getElementById('tquanreal-cf-chat-bubble');
  var panel       = document.getElementById('tquanreal-cf-chat-panel');
  var closeBtn    = document.getElementById('tquanreal-cf-chat-close');
  var badge       = document.getElementById('tquanreal-cf-chat-badge');
  var dot         = document.getElementById('tquanreal-cf-chat-dot');

  if (!widget || !bubble) return;

  // ── Bubble open/close ─────────────────────────────────────
  var isOpen = false;

  function openPanel() {
    isOpen = true;
    panel.removeAttribute('hidden');
    bubble.setAttribute('aria-expanded', 'true');
    clearBadge();
  }

  function closePanel() {
    isOpen = false;
    panel.setAttribute('hidden', '');
    bubble.setAttribute('aria-expanded', 'false');
  }

  function clearBadge() {
    badge.setAttribute('hidden', '');
    badge.textContent = '0';
  }

  function showBadge(count) {
    if (isOpen) return;
    badge.textContent = count;
    badge.removeAttribute('hidden');
  }

  bubble.addEventListener('click', function () {
    if (isOpen) { closePanel(); } else { openPanel(); }
  });

  closeBtn.addEventListener('click', closePanel);

  // ── Anonymous auth + init ─────────────────────────────────
  auth.signInAnonymously().then(function () {
    sessionId = getOrCreateSessionId();
    initChat();
  }).catch(function (err) {
    console.error('[tquanreal chat] auth error:', err.message);
  });

  function initChat() {
    listenAnnouncement();
    listenPresence();
  }

  // Expose for debugging
  window.__tquanrealChatDB = db;

  // Stubs — implemented in subsequent tasks
  function listenAnnouncement() {}
  function listenPresence() {}
})();
