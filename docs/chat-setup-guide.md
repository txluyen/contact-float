# Hướng dẫn cài đặt và sử dụng — Chat Box Firebase

Plugin: **tquanreal Contact Float** — Tính năng Chat Box

---

## Yêu cầu trước khi bắt đầu

- WordPress 5.8+ với plugin Contact Float đã kích hoạt
- Tài khoản Google (để dùng Firebase)
- Hosting shared (không cần Node.js hay server riêng)

---

## Phần 1: Tạo Firebase Project

### 1.1 Tạo project

1. Vào [console.firebase.google.com](https://console.firebase.google.com)
2. Click **Add project** → đặt tên (ví dụ: `kamado-chat`) → Continue
3. Tắt Google Analytics nếu không cần → Create project

### 1.2 Bật Realtime Database

1. Trong sidebar: **Build → Realtime Database → Create database**
2. Chọn region: **asia-southeast1 (Singapore)**
3. Chọn **Start in test mode** → Enable
4. Copy **Database URL** (dạng: `https://kamado-chat-default-rtdb.asia-southeast1.firebasedatabase.app`)

### 1.3 Bật Authentication

1. **Build → Authentication → Get started**
2. Tab **Sign-in method** → Enable **Anonymous**
3. Enable **Email/Password**
4. Tab **Users → Add user** → nhập email và password admin của bạn
5. Copy **UID** của user vừa tạo (dùng ở bước cấu hình Rules)

### 1.4 Lấy Firebase Config

1. **Project Settings** (icon bánh răng) → **General**
2. Kéo xuống **Your apps → Add app → Web** (icon `</>`)
3. Đặt tên app → Register app
4. Copy toàn bộ object `firebaseConfig`:

```js
const firebaseConfig = {
  apiKey: "AIzaSy...",
  authDomain: "kamado-chat.firebaseapp.com",
  databaseURL: "https://kamado-chat-default-rtdb.asia-southeast1.firebasedatabase.app",
  projectId: "kamado-chat",
  appId: "1:123456:web:abcdef"
};
```

### 1.5 Cấu hình Security Rules

1. **Realtime Database → Rules**
2. Thay toàn bộ nội dung bằng rules sau (thay `PASTE_UID_ADMIN` bằng UID từ bước 1.3):

```json
{
  "rules": {
    "announcement": {
      ".read": true,
      ".write": "auth != null && auth.uid === 'PASTE_UID_ADMIN'"
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

3. Click **Publish**

---

## Phần 2: Cài đặt Plugin

### 2.1 Điền Firebase config vào WP Admin

1. Vào **WordPress Admin → Settings → Contact Float**
2. Kéo xuống section **Chat Box (Firebase)**
3. Điền các field:

| Field | Giá trị |
|-------|---------|
| Bật chat box | ✓ (tick) |
| Firebase API Key | `apiKey` từ firebaseConfig |
| Firebase Auth Domain | `authDomain` từ firebaseConfig |
| Firebase Database URL | `databaseURL` từ firebaseConfig |
| Firebase Project ID | `projectId` từ firebaseConfig |
| Firebase App ID | `appId` từ firebaseConfig |
| Admin Email (Firebase) | Email admin bạn tạo ở bước 1.3 |
| Admin Password | Password admin Firebase |
| Admin Panel Password | Mật khẩu tự đặt để bảo vệ trang admin panel |
| License Key (Premium) | Để trống = bản free |

4. Click **Lưu cài đặt**

> **Lưu ý:** Sau khi lưu, chat bubble sẽ xuất hiện ở góc dưới website.

### 2.2 Cấu hình Admin Panel

File admin panel nằm tại:
```
/wp-content/plugins/tquanreal-contact-float/admin/chat-panel/index.html
```

Mở file này bằng trình soạn thảo (FTP hoặc File Manager trên cPanel), tìm đoạn:

```html
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
```

Thay các giá trị `PASTE_*` bằng thông tin thực tế:

```html
<script>
  var CHAT_PANEL_PASSWORD = 'mat-khau-cua-ban';
  var FIREBASE_CONFIG = {
    apiKey:      'AIzaSy...',
    authDomain:  'kamado-chat.firebaseapp.com',
    databaseURL: 'https://kamado-chat-default-rtdb.asia-southeast1.firebasedatabase.app',
    projectId:   'kamado-chat',
    appId:       '1:123456:web:abcdef'
  };
  var ADMIN_EMAIL    = 'admin@email.com';
  var ADMIN_PASSWORD = 'firebase-admin-password';
</script>
```

Lưu file.

---

## Phần 3: Thiết lập Admin Panel trên điện thoại

### 3.1 Truy cập admin panel

URL admin panel:
```
https://yoursite.com/wp-content/plugins/tquanreal-contact-float/admin/chat-panel/index.html
```

Thay `yoursite.com` bằng domain thật của bạn.

### 3.2 Thêm vào màn hình chính điện thoại

**Trên Chrome (Android):**
1. Mở URL admin panel trên Chrome
2. Nhập mật khẩu admin panel
3. Menu (3 chấm) → **Add to Home screen**
4. Đặt tên (ví dụ: "Chat Admin") → Add

**Trên Safari (iPhone):**
1. Mở URL admin panel trên Safari
2. Nhập mật khẩu admin panel
3. Icon Share (ô vuông + mũi tên) → **Add to Home Screen**
4. Đặt tên → Add

Từ đây bạn có thể mở admin panel như một app từ màn hình chính.

### 3.3 Bật notification

Lần đầu mở admin panel, trình duyệt sẽ hỏi quyền thông báo → chọn **Allow**.

Sau đó mỗi khi có tin nhắn mới từ visitor, điện thoại sẽ hiện notification ngay cả khi đang dùng app khác (miễn là trình duyệt đang chạy nền).

---

## Phần 4: Hướng dẫn sử dụng

### 4.1 Chat Widget (phía visitor)

Visitor truy cập website sẽ thấy bubble chat ở góc dưới màn hình:

- **Click bubble** → mở hộp chat
- **Form tùy chọn** → nhập tên và số điện thoại, hoặc click "Bỏ qua" để chat ngay
- **Gửi tin nhắn** → gõ nội dung → Enter hoặc click nút gửi
- **Thông báo (📢)** → banner vàng hiện nội dung announcement do admin cài đặt

### 4.2 Admin Panel

Sau khi đăng nhập bằng mật khẩu admin panel:

**Danh sách hội thoại:**
- Hiển thị tất cả conversations đang mở, sắp xếp theo thời gian mới nhất
- Badge đỏ = số tin chưa reply
- Click vào conversation để mở

**Trả lời tin nhắn:**
- Gõ nội dung → Enter hoặc click nút gửi
- Tin xuất hiện realtime trên widget của visitor

**Cập nhật thông báo (Announcement):**
- Thanh màu vàng ở trên cùng
- Nhập nội dung → toggle ON/OFF → click **Lưu**
- Tất cả visitor đang mở widget sẽ thấy banner cập nhật ngay lập tức

**Kết thúc hội thoại:**
- Click **✓ Xong** để archive conversation
- Conversation sẽ ẩn khỏi danh sách

---

## Phần 5: Freemium — Free vs Premium

| Tính năng | Free | Premium |
|-----------|------|---------|
| Chat realtime | ✓ | ✓ |
| Announcement banner | ✓ | ✓ |
| Admin presence indicator | ✓ | ✓ |
| Browser notification | ✓ | ✓ |
| Lịch sử chat khi quay lại tab | Không | Không |
| **Lịch sử chat khi quay lại website** | **Không** | **Có** |

**Kích hoạt Premium:**
1. Nhập License Key vào WP Admin → Settings → Contact Float → License Key
2. Lưu cài đặt

---

## Phần 6: Giới hạn Free Tier Firebase

Firebase Spark (miễn phí) đủ dùng cho traffic thông thường:

| Chỉ số | Giới hạn |
|--------|----------|
| Realtime Database storage | 1 GB |
| Download mỗi tháng | 10 GB |
| Kết nối đồng thời | 100 |

Với traffic solopreneur/SMB, free tier dùng được lâu dài.

---

## Xử lý sự cố thường gặp

**Chat bubble không hiện:**
- Kiểm tra WP Admin → Settings → Contact Float → "Bật chat box" đã tick chưa
- Kiểm tra Firebase API Key đã nhập đúng chưa

**Admin panel không đăng nhập được Firebase:**
- Kiểm tra email/password trong `index.html` khớp với user trong Firebase Console → Authentication → Users
- Kiểm tra Email/Password provider đã được Enable trong Firebase Console

**Tin nhắn không gửi được:**
- Kiểm tra Firebase Security Rules đã Publish chưa
- Mở DevTools → Console xem có lỗi Firebase không

**Notification không hiện trên điện thoại:**
- Kiểm tra đã Allow notification permission trong trình duyệt
- Trên iPhone: Safari không hỗ trợ Web Push Notification đầy đủ — dùng Chrome/Firefox

**Settings không lưu được:**
- Đây là known bug (option key mismatch) — sẽ được fix trong phiên bản tiếp theo
- Workaround tạm thời: nhập thẳng config vào `index.html` (đã hướng dẫn ở Phần 2.2)
