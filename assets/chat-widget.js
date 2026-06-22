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

  var formEl      = document.getElementById('tquanreal-cf-chat-form');
  var messagesEl  = document.getElementById('tquanreal-cf-chat-messages');
  var inputArea   = document.getElementById('tquanreal-cf-chat-input-area');
  var skipBtn     = document.getElementById('tquanreal-cf-chat-skip');
  var startBtn    = document.getElementById('tquanreal-cf-chat-start');
  var nameInput   = document.getElementById('tquanreal-cf-chat-name');
  var phoneInput  = document.getElementById('tquanreal-cf-chat-phone');
  var chatInput   = document.getElementById('tquanreal-cf-chat-input');
  var sendBtn     = document.getElementById('tquanreal-cf-chat-send');
  var typingEl    = document.getElementById('tquanreal-cf-chat-typing');

  var chatStarted = false;
  var unreadCount = 0;

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

    // Check if session already had chat started (premium: localStorage)
    var existingMeta = storage.getItem(STORAGE_KEY + '_started');
    if (existingMeta === '1') {
      startChatUI();
    } else {
      formEl.removeAttribute('hidden');
    }

    skipBtn.addEventListener('click', function () {
      saveMeta('', '');
      startChatUI();
    });

    startBtn.addEventListener('click', function () {
      saveMeta(nameInput.value.trim(), phoneInput.value.trim());
      startChatUI();
    });
  }

  function saveMeta(name, phone) {
    storage.setItem(STORAGE_KEY + '_started', '1');
    db.ref('conversations/' + sessionId + '/meta').set({
      name:       name || '',
      phone:      phone || '',
      page_url:   window.location.href,
      started_at: firebase.database.ServerValue.TIMESTAMP,
      status:     'open'
    });
  }

  function startChatUI() {
    chatStarted = true;
    formEl.setAttribute('hidden', '');
    messagesEl.removeAttribute('hidden');
    inputArea.removeAttribute('hidden');
    listenMessages();
    listenTyping();
  }

  function listenMessages() {
    db.ref('conversations/' + sessionId + '/messages')
      .orderByChild('timestamp')
      .on('child_added', function (snap) {
        var msg = snap.val();
        renderMessage(msg.text, msg.sender);
        if (!isOpen && msg.sender === 'admin') {
          unreadCount++;
          showBadge(unreadCount);
        }
      });
  }

  function renderMessage(text, sender) {
    var div = document.createElement('div');
    div.className = 'tquanreal-cf-chat-msg ' +
      (sender === 'user' ? 'tquanreal-cf-chat-msg-user' : 'tquanreal-cf-chat-msg-admin');
    div.textContent = text;
    messagesEl.appendChild(div);
    messagesEl.scrollTop = messagesEl.scrollHeight;
  }

  function sendMessage(text) {
    if (!text.trim()) return;
    db.ref('conversations/' + sessionId + '/messages').push({
      text:      text.trim(),
      sender:    'user',
      timestamp: firebase.database.ServerValue.TIMESTAMP
    });
  }

  sendBtn.addEventListener('click', function () {
    sendMessage(chatInput.value);
    chatInput.value = '';
  });

  chatInput.addEventListener('keydown', function (e) {
    if (e.key === 'Enter') { sendMessage(chatInput.value); chatInput.value = ''; }
  });

  function listenTyping() {
    db.ref('presence/admin_typing').on('value', function (snap) {
      if (snap.val() === true) {
        typingEl.removeAttribute('hidden');
      } else {
        typingEl.setAttribute('hidden', '');
      }
    });
  }

  // Expose for debugging
  window.__tquanrealChatDB = db;

  // ── Realtime listeners ────────────────────────────────────
  function listenAnnouncement() {}
  function listenPresence() {}
})();
