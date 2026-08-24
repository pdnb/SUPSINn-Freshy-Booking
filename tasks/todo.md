# Todo: เลขพัสดุไปรษณีย์ (v3 slice)

อ้างอิง: `tasks/plan.md` · `docs/intent/v3-post-parcel.md`
แผน mockup แอดมินที่ปิดแล้วอยู่ที่ `tasks/archive/admin-console-mockup-todo.md`
v2 ยังปิดอยู่ที่ `tasks/archive/v2-todo.md` — ไม่เปิดโปรแกรม v3

## Phase 0 — Intent + plan files

- [x] **Task 0:** เขียน `docs/intent/v3-post-parcel.md` + แทนที่ `tasks/plan.md` / `tasks/todo.md` (ย้าย mockup ไป archive)
  - Acceptance: intent มี confirmed statement/decisions/non-goals; ไฟล์แผนเป็นเนื้อหาเลขพัสดุ; mockup อยู่ที่ `tasks/archive/`
  - Verify: อ่านเทียบ restate ที่ผู้ใช้ยืนยัน
  - Depends: —

## Phase 1 — Domain

- [x] **Task 1:** migration `orders.parcel_number` + factory + `OrderService::markShipped` / `updateParcelNumber`
  - Acceptance: คอลัมน์คนละตัวกับ `tracking_token`; ส่งได้มีหรือไม่มีเลข; ค่าว่างเป็น `null`; ปฏิเสธเลขบน bookstore; อัปเดต/ล้างหลัง shipped ได้
  - Verify: `php artisan test --compact tests/Feature/OrderAdminServiceTest.php`
  - Depends: Task 0

## Phase 2 — Admin fulfillment

- [x] **Task 2:** คิวรอดำเนินการของ post + ช่องเลขพัสดุบนแผงรายละเอียด + แสดงเลขบน order-detail
  - Acceptance: post active = confirmed + shipped ที่ยังไม่มีเลข; จัดส่งแล้ว / บันทึกเลขพัสดุ; toast + error inline; ไม่มีปุ่ม completed สำหรับ post
  - Verify: `php artisan test --compact tests/Feature/AdminPickupTest.php`
  - Depends: Task 1

## Phase 3 — Guest confirmation

- [x] **Task 3:** ไทม์ไลน์ post สามขั้น + แสดง/คัดลอกเลขพัสดุ + ไม่ใช้ข้อความใบเสร็จตอนรับของกับ post
  - Acceptance: post จบที่ shipped; pickup ไม่เปลี่ยน; เลขโชว์เมื่อมีค่า; ไม่มี URL ผู้ขนส่ง
  - Verify: `php artisan test --compact tests/Feature/GuestOrderTrackingTest.php`
  - Depends: Task 1

## Phase 4 — Tests + pint

- [x] **Task 4:** เทสต์ service / fulfillment queue / guest steps ครบ แล้ว `vendor/bin/pint --dirty --format agent`
  - Acceptance: เคสในแผนครบ; ไฟล์ที่แตะผ่าน; pint จัดรูปแบบแล้ว
  - Verify: `php artisan test --compact tests/Feature/OrderAdminServiceTest.php tests/Feature/AdminPickupTest.php tests/Feature/GuestOrderTrackingTest.php`
  - Depends: Task 1, Task 2, Task 3
