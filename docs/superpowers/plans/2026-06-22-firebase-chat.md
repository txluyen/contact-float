# Firebase Chat Box — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Thêm real-time chat widget vào plugin WordPress `tquanreal Contact Float`, kết nối visitor với admin qua Firebase Realtime Database, kèm admin panel HTML standalone trên điện thoại.

**Architecture:** Chat widget là Vanilla JS nhúng vào WP footer qua plugin enqueue; admin panel là file HTML độc lập hosted trong thư mục plugin. Cả hai kết nối đến Firebase Realtime DB — visitor dùng Anonymous Auth, admin dùng Email/Password Auth. Firebase config được inject vào frontend qua `wp_localize_script`.

**Tech Stack:** PHP/WordPress, Vanilla JS (ES6+), Firebase JS SDK v10 (compat mode, CDN), Firebase Realtime Database, Firebase Authentication.

## Global Constraints

- Prefix PHP functions: `tquanreal_cf_*` (không dùng `txluyen_cf_*` cho code mới)
- CSS class prefix: `tquanreal-cf-chat-*` (tránh xung đột với `.tquanreal-cf-*` hiện có)
- Firebase SDK version: `10.12.0` (pin cứng để tránh breaking changes)
- Không có build step — tất cả JS là vanilla, không transpile
- Không thêm npm dependencies vào plugin
- Plugin option key: `tquanreal_contact_float_options` (đã có, giữ nguyên)
- Không sửa naming bug `txluyen_cf_*` trong code hiện tại — ngoài phạm vi

---

## File Structure

```
tquanreal-contact-float/
├── tquanreal-contact-float.php        MODIFY — thêm chat options, enqueue, render widget HTML
├── admin/
│   ├── settings.php                   MODIFY — thêm section "Chat & Firebase"
│   └── chat-panel/
│       ├── index.html                 CREATE — admin panel standalone
│       ├── chat-panel.js              CREATE — admin panel JS
│       └── chat-panel.css             CREATE — admin panel CSS
└── assets/
    ├── chat-widget.js                 CREATE — widget JS (Firebase init, session, messaging)
    └── chat-widget.css               CREATE — widget CSS
```

---

## Task 1: Firebase Setup (Manual) + WP Settings Fields

**Files:**
- Modify: `tquanreal-contact-float.php`
- Modify: `admin/settings.php`

**Interfaces:**
- Produces: `tquanreal_cf_get_options()` trả về các key mới: `chat_enabled`, `firebase_api_key`, `firebase_auth_domain`, `firebase_database_url`, `firebase_project_id`, `firebase_app_id`, `chat_admin_email`, `chat_admin_password`, `chat_panel_password`, `chat_license_key`
- Produces: `wp_localize_script` inject object `tquanrealChat` vào frontend

- [ ] **Step 1: Tạo Firebase project (thủ công)**

  Vào https://console.firebase.google.com:
  1. Tạo project mới (ví dụ: `kamado-chat`)
  2. Vào **Build → Realtime Database** → Create database → chọn region `asia-southeast1` → **Start in test mode**
  3. Vào **Build → Authentication** → Get started → Enable **Anonymous** và **Email/Password**
  4. Vào **Authentication → Users** → Add user → nhập email/pass admin của bạn → copy UID
  5. Vào **Project Settings → General → Your apps** → Add app (Web) → copy `firebaseConfig`

- [ ] **Step 2: Cập nhật Firebase Security Rules**

  Vào **Realtime Database → Rules** → paste:
  ```json
  {
    "rules": {
      "announcement": {
        ".read": true,
        ".write": "auth != null && auth.uid === 'PASTE_ADMIN_UID_HERE'"
      },
      "presence": {
        ".read": true,
        ".write": "auth != null"
      },
      "conversations": {
        "$session_id": {
          ".read": "auth != null",
          ".write": "auth != null",
          "messages": {
            ".indexOn": ["timestamp"]
          }
        }
      }
    }
  }
  ```
  Thay `PASTE_ADMIN_UID_HERE` bằng UID admin từ bước 1. Publish rules.

- [ ] **Step 3: Thêm chat keys vào defaults trong `tquanreal_cf_get_options()`**

  File: `tquanreal-contact-float.php`, hàm `tquanreal_cf_get_options()`, sửa `$defaults`:
  ```php
  $defaults = array(
      'phone'                  => '',
      'zalo_url'               => '',
      'banggia_shortcode'      => '',
      'bg_color'               => '#1a3c6e',
      'text_color'             => '#ffffff',
      'position'               => 'right',
      // Chat settings
      'chat_enabled'           => '0',
      'firebase_api_key'       => '',
      'firebase_auth_domain'   => '',
      'firebase_database_url'  => '',
      'firebase_project_id'    => '',
      'firebase_app_id'        => '',
      'chat_admin_email'       => '',
      'chat_admin_password'    => '',
      'chat_panel_password'    => '',
      'chat_license_key'       => '',
  );
  ```

- [ ] **Step 4: Thêm sanitize cho chat fields trong `txluyen_cf_sanitize()`**

  File: `admin/settings.php`, hàm `txluyen_cf_sanitize()`, thêm vào cuối trước `return $clean;`:
  ```php
  $clean['chat_enabled']          = isset( $input['chat_enabled'] ) ? '1' : '0';
  $clean['firebase_api_key']      = sanitize_text_field( $input['firebase_api_key'] ?? '' );
  $clean['firebase_auth_domain']  = sanitize_text_field( $input['firebase_auth_domain'] ?? '' );
  $clean['firebase_database_url'] = esc_url_raw( $input['firebase_database_url'] ?? '' );
  $clean['firebase_project_id']   = sanitize_text_field( $input['firebase_project_id'] ?? '' );
  $clean['firebase_app_id']       = sanitize_text_field( $input['firebase_app_id'] ?? '' );
  $clean['chat_admin_email']      = sanitize_email( $input['chat_admin_email'] ?? '' );
  $clean['chat_admin_password']   = sanitize_text_field( $input['chat_admin_password'] ?? '' );
  $clean['chat_panel_password']   = sanitize_text_field( $input['chat_panel_password'] ?? '' );
  $clean['chat_license_key']      = sanitize_text_field( $input['chat_license_key'] ?? '' );
  ```

- [ ] **Step 5: Thêm settings section + fields vào `admin/settings.php`**

  Thêm vào cuối hàm `txluyen_cf_register_settings()`, sau các `add_settings_field` hiện có:
  ```php
  add_settings_section( 'tquanreal_cf_section_chat', 'Chat Box (Firebase)', '__return_false', 'txluyen-contact-float' );
  add_settings_field( 'chat_enabled',          'Bật chat box',         'tquanreal_cf_field_chat_enabled',    'txluyen-contact-float', 'tquanreal_cf_section_chat' );
  add_settings_field( 'firebase_api_key',      'Firebase API Key',     'tquanreal_cf_field_fb_api_key',      'txluyen-contact-float', 'tquanreal_cf_section_chat' );
  add_settings_field( 'firebase_auth_domain',  'Firebase Auth Domain', 'tquanreal_cf_field_fb_auth_domain',  'txluyen-contact-float', 'tquanreal_cf_section_chat' );
  add_settings_field( 'firebase_database_url', 'Firebase Database URL','tquanreal_cf_field_fb_db_url',       'txluyen-contact-float', 'tquanreal_cf_section_chat' );
  add_settings_field( 'firebase_project_id',   'Firebase Project ID',  'tquanreal_cf_field_fb_project_id',   'txluyen-contact-float', 'tquanreal_cf_section_chat' );
  add_settings_field( 'firebase_app_id',       'Firebase App ID',      'tquanreal_cf_field_fb_app_id',       'txluyen-contact-float', 'tquanreal_cf_section_chat' );
  add_settings_field( 'chat_admin_email',      'Admin Email (Firebase)','tquanreal_cf_field_admin_email',    'txluyen-contact-float', 'tquanreal_cf_section_chat' );
  add_settings_field( 'chat_admin_password',   'Admin Password',       'tquanreal_cf_field_admin_password',  'txluyen-contact-float', 'tquanreal_cf_section_chat' );
  add_settings_field( 'chat_panel_password',   'Admin Panel Password', 'tquanreal_cf_field_panel_password',  'txluyen-contact-float', 'tquanreal_cf_section_chat' );
  add_settings_field( 'chat_license_key',      'License Key (Premium)','tquanreal_cf_field_license_key',    'txluyen-contact-float', 'tquanreal_cf_section_chat' );
  ```

- [ ] **Step 6: Thêm các field render functions vào `admin/settings.php`**

  Thêm các hàm sau vào cuối file `admin/settings.php`:
  ```php
  function tquanreal_cf_field_chat_enabled() {
      $opts = tquanreal_cf_get_options();
      printf(
          '<input type="checkbox" name="txluyen_contact_float_options[chat_enabled]" value="1" %s> Hiển thị chat bubble trên website',
          checked( $opts['chat_enabled'], '1', false )
      );
  }

  function tquanreal_cf_field_fb_api_key() {
      $opts = tquanreal_cf_get_options();
      printf( '<input type="text" name="txluyen_contact_float_options[firebase_api_key]" value="%s" class="regular-text">', esc_attr( $opts['firebase_api_key'] ) );
  }

  function tquanreal_cf_field_fb_auth_domain() {
      $opts = tquanreal_cf_get_options();
      printf( '<input type="text" name="txluyen_contact_float_options[firebase_auth_domain]" value="%s" class="regular-text" placeholder="your-project.firebaseapp.com">', esc_attr( $opts['firebase_auth_domain'] ) );
  }

  function tquanreal_cf_field_fb_db_url() {
      $opts = tquanreal_cf_get_options();
      printf( '<input type="url" name="txluyen_contact_float_options[firebase_database_url]" value="%s" class="regular-text" placeholder="https://your-project-default-rtdb.asia-southeast1.firebasedatabase.app">', esc_attr( $opts['firebase_database_url'] ) );
  }

  function tquanreal_cf_field_fb_project_id() {
      $opts = tquanreal_cf_get_options();
      printf( '<input type="text" name="txluyen_contact_float_options[firebase_project_id]" value="%s" class="regular-text">', esc_attr( $opts['firebase_project_id'] ) );
  }

  function tquanreal_cf_field_fb_app_id() {
      $opts = tquanreal_cf_get_options();
      printf( '<input type="text" name="txluyen_contact_float_options[firebase_app_id]" value="%s" class="large-text">', esc_attr( $opts['firebase_app_id'] ) );
  }

  function tquanreal_cf_field_admin_email() {
      $opts = tquanreal_cf_get_options();
      printf( '<input type="email" name="txluyen_contact_float_options[chat_admin_email]" value="%s" class="regular-text">', esc_attr( $opts['chat_admin_email'] ) );
      echo '<p class="description">Email đăng nhập Firebase của admin (dùng trong admin panel).</p>';
  }

  function tquanreal_cf_field_admin_password() {
      $opts = tquanreal_cf_get_options();
      printf( '<input type="password" name="txluyen_contact_float_options[chat_admin_password]" value="%s" class="regular-text">', esc_attr( $opts['chat_admin_password'] ) );
      echo '<p class="description">Password Firebase admin. Lưu ý: lưu trong WP options, chỉ admin WP mới thấy.</p>';
  }

  function tquanreal_cf_field_panel_password() {
      $opts = tquanreal_cf_get_options();
      printf( '<input type="text" name="txluyen_contact_float_options[chat_panel_password]" value="%s" class="regular-text" placeholder="mat-khau-admin-panel">', esc_attr( $opts['chat_panel_password'] ) );
      echo '<p class="description">Mật khẩu bảo vệ trang admin panel chat.</p>';
  }

  function tquanreal_cf_field_license_key() {
      $opts = tquanreal_cf_get_options();
      printf( '<input type="text" name="txluyen_contact_float_options[chat_license_key]" value="%s" class="regular-text" placeholder="Để trống = bản miễn phí">', esc_attr( $opts['chat_license_key'] ) );
      echo '<p class="description">License key kích hoạt tính năng Premium (lưu lịch sử chat).</p>';
  }
  ```

- [ ] **Step 7: Thêm enqueue chat assets vào `tquanreal_cf_enqueue()`**

  File: `tquanreal-contact-float.php`, hàm `tquanreal_cf_enqueue()`, thêm vào cuối hàm:
  ```php
  $opts = tquanreal_cf_get_options();
  if ( '1' === $opts['chat_enabled'] && ! empty( $opts['firebase_api_key'] ) ) {
      // Firebase SDK
      wp_enqueue_script( 'firebase-app',  'https://www.gstatic.com/firebasejs/10.12.0/firebase-app-compat.js',      array(),              '10.12.0', true );
      wp_enqueue_script( 'firebase-auth', 'https://www.gstatic.com/firebasejs/10.12.0/firebase-auth-compat.js',     array( 'firebase-app' ), '10.12.0', true );
      wp_enqueue_script( 'firebase-db',   'https://www.gstatic.com/firebasejs/10.12.0/firebase-database-compat.js', array( 'firebase-app' ), '10.12.0', true );

      wp_enqueue_style(
          'tquanreal-cf-chat-style',
          TQUANREAL_CF_URL . 'assets/chat-widget.css',
          array(),
          TQUANREAL_CF_VERSION
      );
      wp_enqueue_script(
          'tquanreal-cf-chat',
          TQUANREAL_CF_URL . 'assets/chat-widget.js',
          array( 'firebase-app', 'firebase-auth', 'firebase-db' ),
          TQUANREAL_CF_VERSION,
          true
      );

      $is_premium = ! empty( $opts['chat_license_key'] );
      wp_localize_script( 'tquanreal-cf-chat', 'tquanrealChat', array(
          'firebase' => array(
              'apiKey'      => $opts['firebase_api_key'],
              'authDomain'  => $opts['firebase_auth_domain'],
              'databaseURL' => $opts['firebase_database_url'],
              'projectId'   => $opts['firebase_project_id'],
              'appId'       => $opts['firebase_app_id'],
          ),
          'position'  => $opts['position'],
          'isPremium' => $is_premium,
      ) );
  }
  ```

- [ ] **Step 8: Thêm render chat widget HTML vào `tquanreal_cf_render()`**

  File: `tquanreal-contact-float.php`, hàm `tquanreal_cf_render()`, thêm vào cuối hàm (sau `</div>` closing tag, trước `<?php`):
  ```php
  $opts = tquanreal_cf_get_options();
  if ( '1' === $opts['chat_enabled'] && ! empty( $opts['firebase_api_key'] ) ) {
      $pos_class = $opts['position'] === 'left' ? 'tquanreal-cf-chat-left' : 'tquanreal-cf-chat-right';
      ?>
      <div id="tquanreal-cf-chat-widget" class="tquanreal-cf-chat-widget <?php echo esc_attr( $pos_class ); ?>">
          <button id="tquanreal-cf-chat-bubble" class="tquanreal-cf-chat-bubble" aria-label="Mở chat" aria-expanded="false">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="currentColor"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>
              <span id="tquanreal-cf-chat-badge" class="tquanreal-cf-chat-badge" hidden>0</span>
              <span id="tquanreal-cf-chat-dot" class="tquanreal-cf-chat-dot" hidden></span>
          </button>

          <div id="tquanreal-cf-chat-panel" class="tquanreal-cf-chat-panel" hidden>
              <div class="tquanreal-cf-chat-header">
                  <span class="tquanreal-cf-chat-title">Chat với chúng tôi</span>
                  <span id="tquanreal-cf-chat-presence" class="tquanreal-cf-chat-presence"></span>
                  <button id="tquanreal-cf-chat-close" class="tquanreal-cf-chat-close" aria-label="Đóng">&times;</button>
              </div>

              <div id="tquanreal-cf-chat-announcement" class="tquanreal-cf-chat-announcement" hidden>
                  <span id="tquanreal-cf-chat-announcement-text"></span>
              </div>

              <div id="tquanreal-cf-chat-form" class="tquanreal-cf-chat-form">
                  <p>Để lại thông tin để chúng tôi hỗ trợ tốt hơn (tùy chọn)</p>
                  <input type="text" id="tquanreal-cf-chat-name" placeholder="Tên của bạn">
                  <input type="tel" id="tquanreal-cf-chat-phone" placeholder="Số điện thoại">
                  <div class="tquanreal-cf-chat-form-actions">
                      <button id="tquanreal-cf-chat-skip" type="button">Bỏ qua</button>
                      <button id="tquanreal-cf-chat-start" type="button">Bắt đầu chat</button>
                  </div>
              </div>

              <div id="tquanreal-cf-chat-messages" class="tquanreal-cf-chat-messages" hidden></div>

              <div id="tquanreal-cf-chat-typing" class="tquanreal-cf-chat-typing" hidden>Admin đang gõ...</div>

              <div id="tquanreal-cf-chat-input-area" class="tquanreal-cf-chat-input-area" hidden>
                  <input type="text" id="tquanreal-cf-chat-input" placeholder="Nhập tin nhắn...">
                  <button id="tquanreal-cf-chat-send" aria-label="Gửi">&#10148;</button>
              </div>
          </div>
      </div>
      <?php
  }
  ```

- [ ] **Step 9: Verify settings page**

  Vào WP Admin → Settings → Contact Float. Cuộn xuống cuối — phải thấy section "Chat Box (Firebase)" với các field. Tick "Bật chat box", điền Firebase config, lưu. Reload trang, config phải còn đó.

- [ ] **Step 10: Commit**

  ```bash
  git add tquanreal-contact-float.php admin/settings.php
  git commit -m "feat: add Firebase chat settings and asset enqueue"
  ```

---

## Task 2: Chat Widget CSS

**Files:**
- Create: `assets/chat-widget.css`

**Interfaces:**
- Consumes: CSS class prefix `tquanreal-cf-chat-*`, CSS variables `--tquanreal-cf-bg` và `--tquanreal-cf-color` (đã inject bởi plugin hiện tại)
- Produces: Styles cho bubble, panel, form, messages, announcement

- [ ] **Step 1: Tạo `assets/chat-widget.css`**

  ```css
  /* ── Widget container ─────────────────────────────── */
  .tquanreal-cf-chat-widget {
      position: fixed;
      bottom: 20px;
      z-index: 9998;
      display: flex;
      flex-direction: column;
      align-items: flex-end;
      gap: 8px;
  }

  .tquanreal-cf-chat-right { right: 20px; align-items: flex-end; }
  .tquanreal-cf-chat-left  { left: 20px;  align-items: flex-start; }

  /* ── Bubble ───────────────────────────────────────── */
  .tquanreal-cf-chat-bubble {
      position: relative;
      width: 52px;
      height: 52px;
      border-radius: 50%;
      background: var(--tquanreal-cf-bg, #1a3c6e);
      color: var(--tquanreal-cf-color, #fff);
      border: none;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 3px 12px rgba(0,0,0,0.25);
      transition: transform 0.2s ease;
  }

  .tquanreal-cf-chat-bubble:hover { transform: scale(1.08); }

  .tquanreal-cf-chat-badge {
      position: absolute;
      top: -4px;
      right: -4px;
      background: #e53e3e;
      color: #fff;
      font-size: 11px;
      font-weight: 700;
      min-width: 18px;
      height: 18px;
      border-radius: 9px;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 0 4px;
  }

  .tquanreal-cf-chat-badge[hidden] { display: none; }

  .tquanreal-cf-chat-dot {
      position: absolute;
      top: -3px;
      right: -3px;
      width: 12px;
      height: 12px;
      background: #ed8936;
      border-radius: 50%;
      animation: tquanreal-cf-blink 1.2s infinite;
  }

  .tquanreal-cf-chat-dot[hidden] { display: none; }

  @keyframes tquanreal-cf-blink {
      0%, 100% { opacity: 1; }
      50%       { opacity: 0.3; }
  }

  /* ── Panel ────────────────────────────────────────── */
  .tquanreal-cf-chat-panel {
      width: 320px;
      max-height: 480px;
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 8px 32px rgba(0,0,0,0.18);
      display: flex;
      flex-direction: column;
      overflow: hidden;
  }

  .tquanreal-cf-chat-panel[hidden] { display: none; }

  /* ── Header ───────────────────────────────────────── */
  .tquanreal-cf-chat-header {
      background: var(--tquanreal-cf-bg, #1a3c6e);
      color: var(--tquanreal-cf-color, #fff);
      padding: 12px 14px;
      display: flex;
      align-items: center;
      gap: 8px;
  }

  .tquanreal-cf-chat-title  { flex: 1; font-weight: 700; font-size: 14px; }

  .tquanreal-cf-chat-presence {
      font-size: 11px;
      opacity: 0.85;
  }

  .tquanreal-cf-chat-close {
      background: none;
      border: none;
      color: inherit;
      font-size: 20px;
      cursor: pointer;
      line-height: 1;
      opacity: 0.8;
      padding: 0;
  }
  .tquanreal-cf-chat-close:hover { opacity: 1; }

  /* ── Announcement ─────────────────────────────────── */
  .tquanreal-cf-chat-announcement {
      background: #fffbeb;
      border-bottom: 1px solid #f6e05e;
      padding: 8px 14px;
      font-size: 12px;
      color: #744210;
      display: flex;
      align-items: flex-start;
      gap: 6px;
  }

  .tquanreal-cf-chat-announcement[hidden] { display: none; }

  .tquanreal-cf-chat-announcement::before { content: "📢"; flex-shrink: 0; }

  /* ── Pre-chat form ────────────────────────────────── */
  .tquanreal-cf-chat-form {
      padding: 16px;
      display: flex;
      flex-direction: column;
      gap: 10px;
  }

  .tquanreal-cf-chat-form p {
      margin: 0;
      font-size: 13px;
      color: #555;
  }

  .tquanreal-cf-chat-form input {
      width: 100%;
      padding: 8px 10px;
      border: 1px solid #ddd;
      border-radius: 6px;
      font-size: 13px;
      box-sizing: border-box;
  }

  .tquanreal-cf-chat-form-actions {
      display: flex;
      gap: 8px;
  }

  .tquanreal-cf-chat-form-actions button {
      flex: 1;
      padding: 8px;
      border-radius: 6px;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      border: none;
  }

  #tquanreal-cf-chat-skip {
      background: #f0f0f0;
      color: #555;
  }

  #tquanreal-cf-chat-start {
      background: var(--tquanreal-cf-bg, #1a3c6e);
      color: var(--tquanreal-cf-color, #fff);
  }

  /* ── Messages area ────────────────────────────────── */
  .tquanreal-cf-chat-messages {
      flex: 1;
      overflow-y: auto;
      padding: 12px;
      display: flex;
      flex-direction: column;
      gap: 8px;
      min-height: 160px;
  }

  .tquanreal-cf-chat-messages[hidden] { display: none; }

  .tquanreal-cf-chat-msg {
      max-width: 78%;
      padding: 8px 12px;
      border-radius: 12px;
      font-size: 13px;
      line-height: 1.4;
      word-break: break-word;
  }

  .tquanreal-cf-chat-msg-user {
      align-self: flex-end;
      background: var(--tquanreal-cf-bg, #1a3c6e);
      color: var(--tquanreal-cf-color, #fff);
      border-bottom-right-radius: 3px;
  }

  .tquanreal-cf-chat-msg-admin {
      align-self: flex-start;
      background: #f0f0f0;
      color: #333;
      border-bottom-left-radius: 3px;
  }

  /* ── Typing indicator ─────────────────────────────── */
  .tquanreal-cf-chat-typing {
      padding: 6px 14px;
      font-size: 11px;
      color: #888;
      font-style: italic;
  }

  .tquanreal-cf-chat-typing[hidden] { display: none; }

  /* ── Input area ───────────────────────────────────── */
  .tquanreal-cf-chat-input-area {
      display: flex;
      border-top: 1px solid #eee;
      padding: 8px;
      gap: 6px;
  }

  .tquanreal-cf-chat-input-area[hidden] { display: none; }

  .tquanreal-cf-chat-input-area input {
      flex: 1;
      padding: 8px 10px;
      border: 1px solid #ddd;
      border-radius: 20px;
      font-size: 13px;
      outline: none;
  }

  #tquanreal-cf-chat-send {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      background: var(--tquanreal-cf-bg, #1a3c6e);
      color: var(--tquanreal-cf-color, #fff);
      border: none;
      cursor: pointer;
      font-size: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
  }

  /* ── Mobile ───────────────────────────────────────── */
  @media (max-width: 480px) {
      .tquanreal-cf-chat-panel {
          width: calc(100vw - 24px);
          max-height: 60vh;
      }

      .tquanreal-cf-chat-right { right: 12px; }
      .tquanreal-cf-chat-left  { left: 12px;  }
  }
  ```

- [ ] **Step 2: Verify CSS load**

  Bật chat trong WP settings, mở website. DevTools → Network → tìm `chat-widget.css` → status 200. Bubble tròn phải hiện ở góc phải màn hình.

- [ ] **Step 3: Commit**

  ```bash
  git add assets/chat-widget.css
  git commit -m "feat: add chat widget CSS"
  ```

---

## Task 3: Chat Widget JS — Core (Firebase Init, Session, Bubble)

**Files:**
- Create: `assets/chat-widget.js`

**Interfaces:**
- Consumes: `window.tquanrealChat` (inject bởi `wp_localize_script`)
- Produces: `window.__tquanrealChatDB` (Firebase DB ref, dùng trong tasks tiếp theo nếu debug)

- [ ] **Step 1: Tạo `assets/chat-widget.js` — phần core**

  ```js
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
  ```

- [ ] **Step 2: Verify bubble open/close**

  Mở website, bubble chat phải hiện. Click bubble → panel mở (không có content vì chưa impl). Click lại → đóng. Click X → đóng. Console không có error.

- [ ] **Step 3: Commit**

  ```bash
  git add assets/chat-widget.js
  git commit -m "feat: chat widget core - Firebase init, session, bubble toggle"
  ```

---

## Task 4: Chat Widget JS — Pre-chat Form + Messaging

**Files:**
- Modify: `assets/chat-widget.js`

**Interfaces:**
- Consumes: `sessionId`, `db` từ task 3
- Produces: Firebase path `conversations/{sessionId}/messages/{id}` với `{ text, sender, timestamp }`

- [ ] **Step 1: Thêm pre-chat form logic vào `initChat()`**

  Trong `assets/chat-widget.js`, thay `function initChat()` bằng:
  ```js
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
  ```

- [ ] **Step 2: Thêm `saveMeta()` và `startChatUI()`**

  Thêm sau `function initChat() { ... }`:
  ```js
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
  ```

- [ ] **Step 3: Thêm `listenMessages()` và `sendMessage()`**

  ```js
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
  ```

- [ ] **Step 4: Thêm typing indicator listener**

  Thay `function listenTyping() {}` (stub sẽ thêm ở đây):
  ```js
  function listenTyping() {
    db.ref('presence/admin_typing').on('value', function (snap) {
      if (snap.val() === true) {
        typingEl.removeAttribute('hidden');
      } else {
        typingEl.setAttribute('hidden', '');
      }
    });
  }
  ```

- [ ] **Step 5: Verify form + messaging**

  1. Mở website → click chat bubble → form hiện
  2. Bỏ qua → form ẩn, ô nhập hiện
  3. Gõ tin nhắn, Enter hoặc click ➤ → tin gửi đi
  4. Vào Firebase Console → Realtime Database → kiểm tra `conversations/{sessionId}/messages` có entry mới
  5. Đóng + mở lại bubble → badge không tăng (vì không có admin reply)

- [ ] **Step 6: Commit**

  ```bash
  git add assets/chat-widget.js
  git commit -m "feat: chat widget pre-chat form and messaging"
  ```

---

## Task 5: Chat Widget JS — Announcement + Admin Presence

**Files:**
- Modify: `assets/chat-widget.js`

**Interfaces:**
- Consumes: Firebase path `/announcement`, `/presence/admin_online`
- Produces: Banner text visible to user; online/offline indicator in header

- [ ] **Step 1: Implement `listenAnnouncement()`**

  Thay `function listenAnnouncement() {}` bằng:
  ```js
  var announcementEl     = document.getElementById('tquanreal-cf-chat-announcement');
  var announcementTextEl = document.getElementById('tquanreal-cf-chat-announcement-text');
  var announcementSeen   = storage.getItem(STORAGE_KEY + '_ann_seen');

  function listenAnnouncement() {
    db.ref('announcement').on('value', function (snap) {
      var data = snap.val();
      if (data && data.enabled && data.text) {
        announcementTextEl.textContent = data.text;
        announcementEl.removeAttribute('hidden');

        // Show orange dot if announcement is newer than last seen
        var updatedAt = String(data.updated_at || '');
        if (updatedAt !== announcementSeen) {
          dot.removeAttribute('hidden');
        }
      } else {
        announcementEl.setAttribute('hidden', '');
        dot.setAttribute('hidden', '');
      }
    });
  }
  ```

  Thêm vào `openPanel()`, sau `clearBadge()`:
  ```js
  // Mark announcement as seen
  if (announcementEl && !announcementEl.hasAttribute('hidden')) {
    var seenVal = String(announcementEl.dataset.updatedAt || Date.now());
    storage.setItem(STORAGE_KEY + '_ann_seen', seenVal);
    dot.setAttribute('hidden', '');
  }
  ```

- [ ] **Step 2: Implement `listenPresence()`**

  Thay `function listenPresence() {}` bằng:
  ```js
  var presenceEl = document.getElementById('tquanreal-cf-chat-presence');

  function listenPresence() {
    db.ref('presence/admin_online').on('value', function (snap) {
      if (snap.val() === true) {
        presenceEl.textContent = '● Online';
        presenceEl.style.color = '#68d391';
      } else {
        presenceEl.textContent = 'Sẽ phản hồi sớm';
        presenceEl.style.color = 'rgba(255,255,255,0.7)';
      }
    });
  }
  ```

- [ ] **Step 3: Verify announcement**

  1. Vào Firebase Console → Realtime Database → thêm thủ công:
     ```
     announcement: { text: "Test giảm giá 30%", enabled: true, updated_at: 1234567890 }
     ```
  2. Reload website → mở chat → banner vàng hiện với text
  3. Đóng + mở lại → dot cam nhấp nháy trên bubble (chưa đánh dấu seen)

- [ ] **Step 4: Commit**

  ```bash
  git add assets/chat-widget.js
  git commit -m "feat: chat widget announcement banner and admin presence indicator"
  ```

---

## Task 6: Admin Panel — HTML + CSS + Auth

**Files:**
- Create: `admin/chat-panel/index.html`
- Create: `admin/chat-panel/chat-panel.css`
- Create: `admin/chat-panel/chat-panel.js` (skeleton)

**Interfaces:**
- Consumes: Firebase config (hardcoded trong HTML — admin copy từ WP settings)
- Produces: Màn hình login, redirect sang conversation list sau khi auth thành công

- [ ] **Step 1: Tạo `admin/chat-panel/chat-panel.css`**

  ```css
  * { box-sizing: border-box; margin: 0; padding: 0; }

  body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      background: #f5f5f5;
      color: #333;
      height: 100vh;
      overflow: hidden;
  }

  /* ── Password gate ──────────────────────────────────── */
  #cp-gate {
      display: flex;
      align-items: center;
      justify-content: center;
      height: 100vh;
      background: #1a3c6e;
  }

  #cp-gate-form {
      background: #fff;
      padding: 32px 24px;
      border-radius: 12px;
      width: 280px;
      display: flex;
      flex-direction: column;
      gap: 12px;
  }

  #cp-gate-form h2 { font-size: 18px; text-align: center; }

  #cp-gate-form input {
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 6px;
      font-size: 14px;
      width: 100%;
  }

  #cp-gate-form button {
      padding: 10px;
      background: #1a3c6e;
      color: #fff;
      border: none;
      border-radius: 6px;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
  }

  #cp-gate-error { color: #e53e3e; font-size: 12px; text-align: center; }

  /* ── App shell ─────────────────────────────────────── */
  #cp-app { display: none; height: 100vh; flex-direction: column; }
  #cp-app.visible { display: flex; }

  /* ── Header ────────────────────────────────────────── */
  #cp-header {
      background: #1a3c6e;
      color: #fff;
      padding: 12px 16px;
      display: flex;
      align-items: center;
      gap: 10px;
      flex-shrink: 0;
  }

  #cp-header h1 { font-size: 16px; flex: 1; }

  #cp-badge {
      background: #e53e3e;
      color: #fff;
      font-size: 11px;
      font-weight: 700;
      min-width: 20px;
      height: 20px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 0 5px;
  }

  #cp-badge[hidden] { display: none; }

  /* ── Announcement bar ─────────────────────────────── */
  #cp-announcement-bar {
      padding: 10px 16px;
      background: #fffbeb;
      border-bottom: 1px solid #f6e05e;
      display: flex;
      gap: 8px;
      align-items: center;
      flex-shrink: 0;
  }

  #cp-announcement-bar[hidden] { display: none; }
  #cp-announcement-bar input { flex: 1; padding: 6px 8px; border: 1px solid #ddd; border-radius: 5px; font-size: 13px; }
  #cp-announcement-toggle { padding: 6px 10px; border: none; border-radius: 5px; font-size: 12px; font-weight: 600; cursor: pointer; }
  #cp-announcement-save { padding: 6px 10px; background: #1a3c6e; color: #fff; border: none; border-radius: 5px; font-size: 12px; cursor: pointer; }

  /* ── Screens ────────────────────────────────────────── */
  #cp-screen-list, #cp-screen-conv {
      flex: 1;
      overflow-y: auto;
  }

  #cp-screen-conv { display: none; flex-direction: column; }
  #cp-screen-conv.visible { display: flex; }

  /* ── Conversation list ─────────────────────────────── */
  .cp-conv-item {
      padding: 12px 16px;
      border-bottom: 1px solid #eee;
      cursor: pointer;
      background: #fff;
  }

  .cp-conv-item:hover { background: #f9f9f9; }
  .cp-conv-item.unread { border-left: 3px solid #1a3c6e; }

  .cp-conv-item-header { display: flex; justify-content: space-between; align-items: center; }
  .cp-conv-name { font-weight: 600; font-size: 14px; }
  .cp-conv-time { font-size: 11px; color: #888; }
  .cp-conv-phone { font-size: 12px; color: #666; margin-top: 2px; }
  .cp-conv-preview { font-size: 12px; color: #888; margin-top: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

  /* ── Conversation view ─────────────────────────────── */
  #cp-conv-header {
      background: #1a3c6e;
      color: #fff;
      padding: 10px 14px;
      display: flex;
      align-items: center;
      gap: 10px;
      flex-shrink: 0;
  }

  #cp-conv-back { background: none; border: none; color: #fff; font-size: 20px; cursor: pointer; line-height: 1; }
  #cp-conv-name { flex: 1; font-weight: 600; font-size: 14px; }
  #cp-conv-archive { padding: 5px 10px; background: rgba(255,255,255,0.2); border: none; color: #fff; border-radius: 4px; font-size: 12px; cursor: pointer; }

  #cp-conv-messages {
      flex: 1;
      overflow-y: auto;
      padding: 12px;
      display: flex;
      flex-direction: column;
      gap: 8px;
      background: #f9f9f9;
  }

  .cp-msg { max-width: 78%; padding: 8px 12px; border-radius: 12px; font-size: 13px; line-height: 1.4; word-break: break-word; }
  .cp-msg-user  { align-self: flex-start; background: #fff; border: 1px solid #eee; border-bottom-left-radius: 3px; }
  .cp-msg-admin { align-self: flex-end; background: #1a3c6e; color: #fff; border-bottom-right-radius: 3px; }

  #cp-conv-input-area {
      display: flex;
      padding: 8px;
      gap: 6px;
      border-top: 1px solid #eee;
      background: #fff;
      flex-shrink: 0;
  }

  #cp-conv-input { flex: 1; padding: 8px 12px; border: 1px solid #ddd; border-radius: 20px; font-size: 13px; outline: none; }
  #cp-conv-send { width: 36px; height: 36px; border-radius: 50%; background: #1a3c6e; color: #fff; border: none; cursor: pointer; font-size: 16px; }
  ```

- [ ] **Step 2: Tạo `admin/chat-panel/index.html`**

  Lấy Firebase config từ WP Admin → Settings → Contact Float và thay vào `PASTE_*` bên dưới:
  ```html
  <!DOCTYPE html>
  <html lang="vi">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1a3c6e">
    <title>Chat Admin</title>
    <link rel="stylesheet" href="chat-panel.css">
  </head>
  <body>

  <!-- Password gate -->
  <div id="cp-gate">
    <div id="cp-gate-form">
      <h2>🔐 Chat Admin</h2>
      <input type="password" id="cp-gate-input" placeholder="Nhập mật khẩu admin panel">
      <button id="cp-gate-btn">Đăng nhập</button>
      <p id="cp-gate-error" hidden>Sai mật khẩu</p>
    </div>
  </div>

  <!-- App (ẩn cho đến khi pass đúng) -->
  <div id="cp-app">
    <div id="cp-header">
      <h1>💬 Chat Admin</h1>
      <div id="cp-badge" hidden>0</div>
    </div>

    <div id="cp-announcement-bar" hidden>
      <input type="text" id="cp-ann-input" placeholder="Nhập thông báo...">
      <button id="cp-announcement-toggle">ON</button>
      <button id="cp-announcement-save">Lưu</button>
    </div>

    <div id="cp-screen-list"></div>

    <div id="cp-screen-conv">
      <div id="cp-conv-header">
        <button id="cp-conv-back">&#8592;</button>
        <span id="cp-conv-name"></span>
        <button id="cp-conv-archive">✓ Xong</button>
      </div>
      <div id="cp-conv-messages"></div>
      <div id="cp-conv-input-area">
        <input type="text" id="cp-conv-input" placeholder="Nhập reply...">
        <button id="cp-conv-send">&#10148;</button>
      </div>
    </div>
  </div>

  <!-- Firebase config — thay bằng config thật từ WP Settings -->
  <script>
    var CHAT_PANEL_PASSWORD = 'PASTE_PANEL_PASSWORD_HERE';
    var FIREBASE_CONFIG = {
      apiKey:      'PASTE_API_KEY',
      authDomain:  'PASTE_AUTH_DOMAIN',
      databaseURL: 'PASTE_DATABASE_URL',
      projectId:   'PASTE_PROJECT_ID',
      appId:       'PASTE_APP_ID'
    };
    var ADMIN_EMAIL    = 'PASTE_ADMIN_EMAIL';
    var ADMIN_PASSWORD = 'PASTE_ADMIN_PASSWORD';
  </script>

  <script src="https://www.gstatic.com/firebasejs/10.12.0/firebase-app-compat.js"></script>
  <script src="https://www.gstatic.com/firebasejs/10.12.0/firebase-auth-compat.js"></script>
  <script src="https://www.gstatic.com/firebasejs/10.12.0/firebase-database-compat.js"></script>
  <script src="chat-panel.js"></script>
  </body>
  </html>
  ```

- [ ] **Step 3: Tạo `admin/chat-panel/chat-panel.js` — password gate + Firebase auth**

  ```js
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
  ```

- [ ] **Step 4: Verify admin panel**

  Truy cập: `https://yoursite.com/wp-content/plugins/tquanreal-contact-float/admin/chat-panel/index.html`
  - Màn hình password gate hiện → nhập đúng password → vào app
  - Console không có error Firebase
  - Vào Firebase Console → Realtime Database → `presence/admin_online` phải là `true`
  - Đóng tab → `presence/admin_online` chuyển thành `false` (onDisconnect)

- [ ] **Step 5: Commit**

  ```bash
  git add admin/chat-panel/
  git commit -m "feat: admin panel HTML, CSS, password gate, and Firebase auth"
  ```

---

## Task 7: Admin Panel JS — Conversation List + Browser Notifications

**Files:**
- Modify: `admin/chat-panel/chat-panel.js`

**Interfaces:**
- Consumes: Firebase path `conversations/` (tất cả conversations)
- Produces: Danh sách conversations render ra `#cp-screen-list`; browser notification khi có tin mới

- [ ] **Step 1: Implement `loadConversations()`**

  Thay `function loadConversations() {}` bằng:
  ```js
  var screenList = document.getElementById('cp-screen-list');
  var badge      = document.getElementById('cp-badge');
  var convMap    = {}; // sessionId -> { meta, lastMsg, unread }
  var totalUnread = 0;

  function loadConversations() {
    requestNotificationPermission();

    db.ref('conversations').on('value', function (snap) {
      convMap = {};
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
        if (unread) totalUnread++;
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

    var name   = conv.meta.name  || 'Ẩn danh';
    var phone  = conv.meta.phone ? '<div class="cp-conv-phone">📞 ' + conv.meta.phone + '</div>' : '';
    var time   = conv.lastMsg ? formatTime(conv.lastMsg.timestamp) : '';
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

  function formatTime(ts) {
    if (!ts) return '';
    var d = new Date(ts);
    return d.getHours() + ':' + String(d.getMinutes()).padStart(2, '0');
  }

  function escapeHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  }

  // Stub
  function openConversation(id, name) {}
  ```

- [ ] **Step 2: Thêm browser notification**

  ```js
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
  ```

  Trong `loadConversations()`, sau `if (unread) totalUnread++;` thêm:
  ```js
  if (unread && lastMsg) {
    var name = (data.meta && data.meta.name) ? data.meta.name : 'Khách';
    notifyNewMessage(child.key, name, lastMsg.text);
  }
  ```

- [ ] **Step 3: Verify conversation list**

  1. Mở website → gửi 1-2 tin nhắn từ chat widget
  2. Mở admin panel → danh sách conversation phải hiện với tên "Ẩn danh" và preview
  3. Admin panel hỏi notification permission → Allow
  4. Gửi thêm tin từ widget (tab khác) → notification hiện trên điện thoại/desktop

- [ ] **Step 4: Commit**

  ```bash
  git add admin/chat-panel/chat-panel.js
  git commit -m "feat: admin panel conversation list and browser notifications"
  ```

---

## Task 8: Admin Panel JS — Conversation View + Reply + Announcement + Archive

**Files:**
- Modify: `admin/chat-panel/chat-panel.js`

**Interfaces:**
- Consumes: Firebase path `conversations/{id}/messages`, `announcement`
- Produces: Admin reply ghi vào Firebase; announcement update; conversation archived

- [ ] **Step 1: Implement `openConversation()`**

  Thay `function openConversation(id, name) {}` bằng:
  ```js
  var screenConv   = document.getElementById('cp-screen-conv');
  var convName     = document.getElementById('cp-conv-name');
  var convMessages = document.getElementById('cp-conv-messages');
  var convInput    = document.getElementById('cp-conv-input');
  var convSend     = document.getElementById('cp-conv-send');
  var convBack     = document.getElementById('cp-conv-back');
  var convArchive  = document.getElementById('cp-conv-archive');

  var currentConvId   = null;
  var currentMsgListener = null;
  var adminTypingTimer  = null;

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

    // Typing indicator for user
    convInput.addEventListener('input', function () {
      clearTimeout(adminTypingTimer);
      db.ref('presence/admin_typing').set(true);
      adminTypingTimer = setTimeout(function () {
        db.ref('presence/admin_typing').set(false);
      }, 1500);
    });
  }

  function renderAdminMsg(text, sender) {
    var div = document.createElement('div');
    div.className = 'cp-msg ' + (sender === 'admin' ? 'cp-msg-admin' : 'cp-msg-user');
    div.textContent = text;
    convMessages.appendChild(div);
    convMessages.scrollTop = convMessages.scrollHeight;
  }
  ```

- [ ] **Step 2: Thêm send reply + back + archive**

  ```js
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
  ```

- [ ] **Step 3: Implement `initAnnouncement()`**

  Thay `function initAnnouncement() {}` bằng:
  ```js
  var annBar    = document.getElementById('cp-announcement-bar');
  var annInput  = document.getElementById('cp-ann-input');
  var annToggle = document.getElementById('cp-announcement-toggle');
  var annSave   = document.getElementById('cp-announcement-save');
  var annEnabled = false;

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
  ```

- [ ] **Step 4: Verify full flow**

  1. Mở website → chat widget → gửi "Xin chào"
  2. Mở admin panel → thấy conversation → click vào
  3. Gõ reply "Dạ, em nghe" → Enter → reply hiện trong widget realtime
  4. Gõ "Đang gõ..." → widget hiện "Admin đang gõ..."
  5. Admin panel: cập nhật announcement → "Test thông báo" → Lưu → widget cập nhật ngay
  6. Admin panel: click "✓ Xong" → conversation ẩn khỏi danh sách
  7. Mở trên điện thoại (homescreen shortcut) → toàn bộ flow hoạt động

- [ ] **Step 5: Commit**

  ```bash
  git add admin/chat-panel/chat-panel.js
  git commit -m "feat: admin panel conversation view, reply, announcement, and archive"
  ```

---

## Self-Review Checklist

**Spec coverage:**
- [x] Chat bubble tách biệt khỏi floating bar hiện có (Task 1 render widget riêng)
- [x] Admin panel HTML standalone mobile-first (Task 6)
- [x] Form tùy chọn trước khi chat (Task 4)
- [x] Freemium — sessionStorage (free) / localStorage (premium) (Task 3)
- [x] Announcement banner admin cập nhật realtime (Task 5, Task 8)
- [x] Badge số tin chưa đọc trên bubble (Task 4)
- [x] Dot cam khi có announcement mới (Task 5)
- [x] Admin presence online/offline (Task 5, Task 6)
- [x] Browser notification (Task 7)
- [x] Typing indicator (Task 4, Task 8)
- [x] Archive conversation (Task 8)
- [x] Firebase Security Rules (Task 1)
- [x] License key field (Task 1)

**Out of scope (không có task):** File attachment, chatbot, multiple admin, Ruby license enforcement, scheduled cleanup — per spec section 9. ✓
