# Todo: Admin console HTML mockup

อ้างอิง: `tasks/plan.md` · `docs/intent/admin-console-mockup.md`

แผนเรทค่าส่งที่ปิดแล้วอยู่ที่ `tasks/archive/shipping-qty-tiers-todo.md`

## Phase 0 — Intent + plan files

- [x] **Task 0:** เขียน `docs/intent/admin-console-mockup.md` + แทนที่ `tasks/plan.md` / `tasks/todo.md` (ย้าย shipping ไป archive)
  - Acceptance: intent มี confirmed statement/decisions/non-goals; ไฟล์แผนเป็นเนื้อหา mockup; shipping อยู่ที่ `tasks/archive/`
  - Verify: อ่านเทียบ restate ที่ผู้ใช้ยืนยัน
  - Depends: —

## Phase 1 — Foundation

- [x] **Task 1:** โฟลเดอร์ prototype + tokens + shell + chrome + `admin-data.js` + `index.html`
  - Acceptance: launcher ลิงก์ครบทุกโมดูล; โทเคนอยู่ใน `:root`; ไม่ยืม theme หน้าร้าน
  - Verify: เปิด `docs/references/admin-prototype/index.html`
  - Depends: Task 0

## Phase 2 — Hero screens

- [x] **Task 2:** Overview — KPI `[ตัวอย่าง]` + deep link F1/F2
- [x] **Task 3:** คิวสลิป + รายละเอียด — ยืนยัน/ปฏิเสธ + ถัดไป + hint ปุ่มลัด
- [x] **Task 4:** Fulfillment — กรองช่องทาง + พร้อมรับ/จัดส่งแล้ว
  - Checkpoint: เดิน F1 และ F2 จาก Overview ไม่ตัน
  - Depends: Task 1

## Phase 3 — Remaining modules

- [x] **Task 5:** สินค้า + ฟอร์ม (validation ชื่อ/ราคา, ไม่มี SKU)
- [x] **Task 6:** Pickup + สรุปผลิต
- [x] **Task 7:** รอบจอง + Settings (ค่าส่ง + แบนเนอร์)
  - Depends: Task 1

## Phase 4 — Gate

- [x] **Task 8:** ลิงก์ข้ามจอ + อัปเดต SRS §4.1 + `tests/Feature/AdminPrototypeTest.php`
  - Verify: `php artisan test --compact tests/Feature/AdminPrototypeTest.php`
  - Depends: Task 2–7
