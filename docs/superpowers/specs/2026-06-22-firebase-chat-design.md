# Firebase Chat Box — Design Spec
**Date:** 2026-06-22
**Plugin:** tquanreal Contact Float (v1.1.2+)
**Scope:** Thêm tính năng chat realtime giữa website visitor và admin, tích hợp vào plugin WordPress hiện có.

---

## 1. Mục tiêu

- Cho phép user nhắn tin trực tiếp trên website mà không cần Zalo hay tài khoản bên thứ ba.
- Admin nhận và reply tin nhắn qua admin panel HTML trên điện thoại (browser shortcut màn hình chính).
- Có kênh broadcast thông báo nhanh (announcement) hiển thị khi user mở widget.
- Freemium: bản free không lưu history, bản premium lưu lịch sử theo session.

---

## 2. Kiến trúc tổng thể

```
[Website visitor]
    ↓ click chat bubble
[Chat Widget - Vanilla JS]  ←→  [Firebase Realtime DB]  ←→  [Admin Panel HTML]
    · Nhúng qua WP plugin footer                               · Mobile-first
    · Firebase Anonymous Auth                                  · Browser notification
    · Form tùy chọn (tên, SĐT)                                · Hosted trên WP server
    · sessionStorage (free) / localStorage (premium)
```

**3 thành phần:**

| Thành phần | Công nghệ | Nơi chạy |
|---|---|---|
| Chat Widget | Vanilla JS + Firebase SDK | WP footer (plugin enqueue) |
| Firebase Backend | Realtime Database + Auth | Google Cloud (free tier) |
| Admin Panel | HTML + JS + Firebase SDK | `/wp-content/plugins/tquanreal-contact-float/admin-chat.html` |

---

## 3. Firebase Data Structure

```
/announcement
  text:       string   — nội dung thông báo
  enabled:    boolean  — bật/tắt banner
  updated_at: number   — timestamp

/conversations
  {session_id}/
    meta/
      name:       string   — tên user (có thể rỗng)
      phone:      string   — SĐT user (có thể rỗng)
      page_url:   string   — trang user đang xem
      started_at: number   — timestamp tạo session
      status:     string   — "open" | "archived"
    messages/
      {msg_id}/
        text:      string
        sender:    string   — "user" | "admin"
        timestamp: number
```

---

## 4. Chat Widget

### 4.1 Giao diện

```
[Bubble đóng]          [Widget mở]
  💬 [2]            ┌─────────────────────────┐
                    │  💬 Chat với chúng tôi  │
                    ├─────────────────────────┤
                    │ 📢 Giảm 30% ghế K20...  │  ← announcement (ẩn nếu disabled)
                    ├─────────────────────────┤
                    │   [messages area]        │
                    ├─────────────────────────┤
                    │  Tên: ___________        │  ← form tùy chọn
                    │  SĐT: ___________        │    (ẩn sau khi submit/bỏ qua)
                    │  [Bỏ qua]  [Bắt đầu]    │
                    ├─────────────────────────┤
                    │  [Nhập tin nhắn...  ] ➤  │
                    └─────────────────────────┘
```

### 4.2 Trạng thái bubble

| Trạng thái | Hiển thị |
|---|---|
| Đóng, có tin chưa đọc | Bubble + badge số đỏ |
| Đóng, có announcement mới | Bubble + dot cam nhấp nháy |
| Mở | Announcement banner + chat |

### 4.3 Behavior

- Vị trí: góc dưới phải (hoặc trái theo config WP plugin hiện có).
- Click bubble → mở widget; click lại → đóng.
- Form tùy chọn hiện trước — có nút "Bỏ qua" để chat ngay.
- Sau khi submit form hoặc bỏ qua → tạo session ID → lắng nghe Firebase.
- Indicator "Đang gõ..." khi admin đang nhập.
- Badge "Admin online" (dot xanh) / "Sẽ phản hồi sớm" (offline).
- Announcement lắng nghe realtime — cập nhật không cần reload.

### 4.4 Session & Freemium

| | Free | Premium |
|---|---|---|
| Lưu session ID | `sessionStorage` | `localStorage` |
| History khi quay lại tab | Không | Không (sessionStorage clear) |
| History khi quay lại website | Không | Có (localStorage persist) |
| License check | Không cần | License key trong WP Admin settings |
| Enforcement giai đoạn 1 | — | Client-side |
| Enforcement giai đoạn 2 | — | Ruby endpoint trên cPanel |

---

## 5. Admin Panel

### 5.1 Giao diện (mobile-first)

**Màn hình danh sách:**
```
┌─────────────────────────┐
│  Chat Admin  🔴 2 mới   │
├─────────────────────────┤
│ 📢 [Nhập thông báo...] [ON/OFF] │
├─────────────────────────┤
│ ● Nguyễn Văn A  14:32  │
│   SĐT: 0901234567       │
│   "Tôi muốn hỏi về..."  │
├─────────────────────────┤
│   Ẩn danh       14:15  │
│   "Giá sản phẩm K20?"  │
└─────────────────────────┘
```

**Màn hình conversation:**
```
┌─────────────────────────┐
│ ← Nguyễn Văn A  ● Online│
├─────────────────────────┤
│  [messages area]         │
├─────────────────────────┤
│  [Nhập reply...     ] ➤  │
└─────────────────────────┘
```

### 5.2 Tính năng

| Tính năng | Chi tiết |
|---|---|
| Danh sách hội thoại | Sắp xếp mới nhất lên đầu |
| Badge chưa đọc | Hiện trên tab title + từng conversation — tính là "chưa đọc" khi conversation có tin nhắn từ user mà chưa có reply từ admin |
| Announcement editor | Text input + toggle bật/tắt, lưu realtime |
| Browser notification | Hỏi permission lần đầu; notify khi có tin mới |
| Admin presence | Tự động "online" khi panel mở, "offline" khi đóng |
| Archive conversation | Button đánh dấu đã xử lý, ẩn khỏi danh sách chính |

### 5.3 Bảo mật

- URL admin panel có token ngẫu nhiên: `admin-chat-{random8}.html`
- Password check đơn giản bằng JS: nhập pass → lưu `sessionStorage`
- Firebase Auth: admin đăng nhập bằng Email/Password (UID cố định)

---

## 6. Firebase Security Rules

```json
{
  "rules": {
    "announcement": {
      ".read": true,
      ".write": "auth != null && auth.uid === 'ADMIN_UID'"
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

- User: Firebase Anonymous Auth (tự động, không cần đăng ký).
- Admin: Email/Password Auth → UID cố định → kiểm tra trong rules.

---

## 7. Tích hợp vào WP Plugin

- Thêm `chat_enabled` vào options (`tquanreal_cf_get_options`).
- Enqueue `assets/chat-widget.js` và `assets/chat-widget.css` khi option bật.
- Inject Firebase config qua `wp_localize_script`.
- Admin panel: file `admin-chat-{token}.html` trong thư mục plugin.
- WP Admin settings: thêm tab Chat với các field:
  - Firebase config (apiKey, projectId, databaseURL…)
  - Admin email/password (để login Firebase)
  - License key (premium)
  - Bật/tắt chat widget

---

## 8. Dọn dữ liệu

- Conversation có `status: "archived"` và không có activity sau 30 ngày → xóa thủ công hoặc cron job đơn giản.
- Giai đoạn 1: admin tự archive; giai đoạn 2 xem xét Firebase scheduled functions.

---

## 9. Ngoài phạm vi (giai đoạn 1)

- File/image attachment trong chat
- Chatbot tự động reply
- Multiple admin accounts
- Ruby license enforcement (để giai đoạn 2)
- Firebase scheduled functions cho auto-cleanup
