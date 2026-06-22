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

  // ── Conversation list (Task 7) ────────────────────────────
  var screenList  = document.getElementById('cp-screen-list');
  var badge       = document.getElementById('cp-badge');
  var convMap     = {}; // sessionId -> { meta, lastMsg, unread }
  var totalUnread = 0;

  // ── Conversation view (Task 8) ────────────────────────────
  var screenConv   = document.getElementById('cp-screen-conv');
  var convName     = document.getElementById('cp-conv-name');
  var convMessages = document.getElementById('cp-conv-messages');
  var convInput    = document.getElementById('cp-conv-input');
  var convSend     = document.getElementById('cp-conv-send');
  var convBack     = document.getElementById('cp-conv-back');
  var convArchive  = document.getElementById('cp-conv-archive');

  var currentConvId      = null;
  var currentMsgListener = null;
  var adminTypingTimer   = null;

  // ── Announcement (Task 8) ─────────────────────────────────
  var annBar    = document.getElementById('cp-announcement-bar');
  var annInput  = document.getElementById('cp-ann-input');
  var annToggle = document.getElementById('cp-announcement-toggle');
  var annSave   = document.getElementById('cp-announcement-save');
  var annEnabled = false;

  // ── Browser notifications ─────────────────────────────────
  var lastNotifiedMsg = {};

  function requestNotificationPermission() {
    if ('Notification' in window && Notification.permission === 'default') {
      Notification.requestPermission();
    }
  }

  function notifyNewMessage(convId, senderName, text) {
    if (lastNotifiedMsg[convId] === text) return;
    lastNotifiedMsg[convId] = text;

    if ('Notification' in window && Notification.permission === 'granted' && document.hidden) {
      new Notification('Tin nhắn mới — ' + senderName, {
        body: text,
        icon: '/wp-content/plugins/tquanreal-contact-float/assets/chat-icon.png'
      });
    }
  }

  // ── Helpers ───────────────────────────────────────────────
  function formatTime(ts) {
    if (!ts) return '';
    var d = new Date(ts);
    return d.getHours() + ':' + String(d.getMinutes()).padStart(2, '0');
  }

  function escapeHtml(str) {
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
  }

  // ── Stubs — implemented in Task 7 & 8 ────────────────────
  function initApp() {
    initAnnouncement();
    loadConversations();
  }

  function initAnnouncement() {
    annBar.removeAttribute('hidden');

    db.ref('announcement').once('value', function (snap) {
      var data = snap.val() || {};
      annInput.value = data.text || '';
      annEnabled     = !!data.enabled;
      annToggle.textContent = annEnabled ? 'ON' : 'OFF';
      annToggle.style.background = annEnabled ? '#38a169' : '#e53e3e';
      annToggle.style.color = '#fff';
      annToggle.style.border = 'none';
      annToggle.style.borderRadius = '5px';
      annToggle.style.padding = '6px 10px';
      annToggle.style.cursor = 'pointer';
    });

    annToggle.addEventListener('click', function () {
      annEnabled = !annEnabled;
      annToggle.textContent = annEnabled ? 'ON' : 'OFF';
      annToggle.style.background = annEnabled ? '#38a169' : '#e53e3e';
    });

    annSave.addEventListener('click', function () {
      db.ref('announcement').set({
        text:       annInput.value.trim(),
        enabled:    annEnabled,
        updated_at: firebase.database.ServerValue.TIMESTAMP
      });
    });
  }

  function loadConversations() {
    requestNotificationPermission();

    db.ref('conversations').on('value', function (snap) {
      convMap     = {};
      totalUnread = 0;
      screenList.innerHTML = '';

      if (!snap.exists()) {
        screenList.innerHTML = '<p style="padding:20px;color:#888;text-align:center;">Chưa có cuộc trò chuyện nào.</p>';
        return;
      }

      var convs = [];
      snap.forEach(function (child) {
        var data = child.val();
        if (!data || data.meta && data.meta.status === 'archived') return;

        var msgs    = data.messages ? Object.values(data.messages) : [];
        var lastMsg = msgs.sort(function (a, b) { return b.timestamp - a.timestamp; })[0];
        var unread  = msgs.filter(function (m) { return m.sender === 'user'; }).length > 0 &&
                      (!lastMsg || lastMsg.sender === 'user');

        convs.push({
          id:      child.key,
          meta:    data.meta || {},
          lastMsg: lastMsg,
          unread:  unread
        });
        convMap[child.key] = { meta: data.meta || {}, lastMsg: lastMsg, unread: unread };
        if (unread) totalUnread++;
        if (unread && lastMsg) {
          var name = (data.meta && data.meta.name) ? data.meta.name : 'Khách';
          notifyNewMessage(child.key, name, lastMsg.text);
        }
      });

      convs.sort(function (a, b) {
        var ta = a.lastMsg ? a.lastMsg.timestamp : (a.meta.started_at || 0);
        var tb = b.lastMsg ? b.lastMsg.timestamp : (b.meta.started_at || 0);
        return tb - ta;
      });

      convs.forEach(renderConvItem);
      updateGlobalBadge();
    });
  }

  function renderConvItem(conv) {
    var el = document.createElement('div');
    el.className = 'cp-conv-item' + (conv.unread ? ' unread' : '');
    el.dataset.id = conv.id;

    var name    = conv.meta.name  || 'Ẩn danh';
    var phone   = conv.meta.phone ? '<div class="cp-conv-phone">📞 ' + escapeHtml(conv.meta.phone) + '</div>' : '';
    var time    = conv.lastMsg ? formatTime(conv.lastMsg.timestamp) : '';
    var preview = conv.lastMsg ? conv.lastMsg.text : '(chưa có tin)';

    el.innerHTML =
      '<div class="cp-conv-item-header">' +
        '<span class="cp-conv-name">' + escapeHtml(name) + '</span>' +
        '<span class="cp-conv-time">' + time + '</span>' +
      '</div>' +
      phone +
      '<div class="cp-conv-preview">' + escapeHtml(preview) + '</div>';

    el.addEventListener('click', function () { openConversation(conv.id, name); });
    screenList.appendChild(el);
  }

  function updateGlobalBadge() {
    if (totalUnread > 0) {
      badge.textContent = totalUnread;
      badge.removeAttribute('hidden');
      document.title = '(' + totalUnread + ') Chat Admin';
    } else {
      badge.setAttribute('hidden', '');
      document.title = 'Chat Admin';
    }
  }

  function openConversation(id, name) {
    currentConvId = id;
    convName.textContent = name;
    convMessages.innerHTML = '';

    screenList.style.display = 'none';
    screenConv.classList.add('visible');

    // Detach previous listener
    if (currentMsgListener) {
      db.ref('conversations/' + currentMsgListener + '/messages').off();
    }
    currentMsgListener = id;

    db.ref('conversations/' + id + '/messages')
      .orderByChild('timestamp')
      .on('child_added', function (snap) {
        var msg = snap.val();
        renderAdminMsg(msg.text, msg.sender);
      });

  }

  function renderAdminMsg(text, sender) {
    var div = document.createElement('div');
    div.className = 'cp-msg ' + (sender === 'admin' ? 'cp-msg-admin' : 'cp-msg-user');
    div.textContent = text;
    convMessages.appendChild(div);
    convMessages.scrollTop = convMessages.scrollHeight;
  }

  convInput.addEventListener('input', function () {
    clearTimeout(adminTypingTimer);
    db.ref('presence/admin_typing').set(true);
    adminTypingTimer = setTimeout(function () {
      db.ref('presence/admin_typing').set(false);
    }, 1500);
  });

  convSend.addEventListener('click', sendReply);
  convInput.addEventListener('keydown', function (e) {
    if (e.key === 'Enter') sendReply();
  });

  function sendReply() {
    var text = convInput.value.trim();
    if (!text || !currentConvId) return;
    db.ref('conversations/' + currentConvId + '/messages').push({
      text:      text,
      sender:    'admin',
      timestamp: firebase.database.ServerValue.TIMESTAMP
    });
    db.ref('presence/admin_typing').set(false);
    convInput.value = '';
  }

  convBack.addEventListener('click', function () {
    if (currentMsgListener) {
      db.ref('conversations/' + currentMsgListener + '/messages').off();
      currentMsgListener = null;
    }
    db.ref('presence/admin_typing').set(false);
    screenConv.classList.remove('visible');
    screenList.style.display = '';
  });

  convArchive.addEventListener('click', function () {
    if (!currentConvId) return;
    db.ref('conversations/' + currentConvId + '/meta/status').set('archived');
    convBack.click();
  });
})();
