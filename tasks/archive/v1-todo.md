# Todo: Freshy Booking v1

> เก็บเป็นประวัติ — todo ที่ใช้งานอยู่คือ `tasks/todo.md`

อ้างอิง: `tasks/archive/v1-plan.md` · `docs/srs.md` · `docs/intent/freshy-booking.md`  
สถานะแผน: **stack ล็อกแล้ว — พร้อม Task 0 เมื่อสั่ง build**

## Stack (ล็อก 2026-08-15)

Laravel + Livewire + SQLite/MySQL/PostgreSQL + Redis  
Application services ตาม `docs/decisions/ADR-002-application-services.md`

## Blockers ก่อน Task 0

- [x] ล็อก tech stack
- [x] (ไม่บล็อก Task 0) ผู้ให้บริการ slip checksum — ใช้ stub ได้จน Task 7

## Phase A — Foundation

- [x] **Task 0:** Bootstrap โปรเจกต์ + brand tokens + โครง `app/Services` + `SlipVerifier` + คำสั่ง dev/test/lint
  - Acceptance: รัน dev/test ได้; หน้าเปล่าใช้ brand ได้; มีโครง services ตาม ADR-002; README มีคำสั่งรัน
  - Verify: `dev` + `test` ผ่าน
  - Depends: ล็อก stack
- [x] **Task 1:** Admin authentication (`/admin/`*)
  - Acceptance: ไม่ล็อกอินเข้าแอดมินไม่ได้; ล็อกอิน/ล็อกเอาท์ได้
  - Verify: feature test + ลองมือ
  - Depends: Task 0

### Checkpoint A

- [x] โปรเจกตรันได้ + แอดมินล็อกอินได้ + โครง services ตาม ADR-002 — review ก่อนโดเมน

## Phase B — Catalog, shipping, rounds

- [x] **Task 2:** Product admin — simple + bundle + ตัวเลือก
  - Acceptance: CRUD/ปิดขาย; bundle มี composition; ตัวเลือกไซส์ฯ บันทึกได้
  - Verify: model/validation tests + สร้างของเทียบปี 69/70
  - Depends: Task 1
- [x] **Task 3:** Shipping rates admin (เรทคงที่ 1–2+)
  - Acceptance: CRUD เรท; มีเรท active ให้ checkout
  - Verify: CRUD tests
  - Depends: Task 1
- [x] **Task 4:** Booking rounds + ผูกสินค้า + query รอบเปิด
  - Acceptance: สร้างรอบ/ผูกสินค้า; นอกช่วงไม่ open; สินค้านอกรอบไม่ขาย
  - Verify: time-window + relation tests
  - Depends: Task 2

### Checkpoint B

- [x] แอดมินตั้งสินค้า + ค่าส่ง + รอบได้ — review โมเดล

## Phase C — Storefront checkout & pay

- [x] **Task 5:** Storefront catalog (เฉพาะรอบเปิด) + UX bundle/simple
  - Acceptance: ปิดรอบแล้วสั่งไม่ได้; เปิดแล้วเห็นของในรอบ; bundle เลือกครบก่อนใส่ตะกร้า
  - Verify: เทียบ mockup home/product
  - Depends: Task 4
- [x] **Task 6:** Cart + checkout ข้อมูลผู้จอง + จุดรับ/จัดส่ง + คิดยอด
  - Acceptance: validation ครบ; ค่าส่งตามช่องทาง; นอกรอบสร้างออเดอร์ไม่ได้
  - Verify: feature tests ยอด/validation
  - Depends: Task 3, Task 5
- [x] **Task 7:** PromptPay + สลิป + checksum gate → `pending_review`
  - Acceptance: fail บล็อก; pass สร้างออเดอร์+สลิป; กันซ้ำตาม verifier; ไม่ล็อกสต็อก
  - Verify: stub pass/fail/duplicate tests
  - Depends: Task 6

### Checkpoint C

- [x] Guest E2E จนเข้าคิวสลิป (stub) — human ลอง happy + fail path

## Phase D — Orders + tracking

- [x] **Task 8:** Admin คิวออเดอร์ + สถานะ + ใบเสร็จตอนรับของ (+ audit พื้นฐาน)
  - Acceptance: คิวตรวจสลิป; เปลี่ยนสถานะ; ใบเสร็จ/รับของ; ค้นรหัสออเดอร์/รหัสนักศึกษา
  - Verify: transition tests + ลองมือ
  - Depends: Task 7
- [x] **Task 9:** Guest order tracking (รหัส + token ลิงก์)
  - Acceptance: ลิงก์ถูกเห็นสถานะ; token ผิดไม่เห็นข้อมูล
  - Verify: feature test
  - Depends: Task 7
- [x] **Task 10:** Seed แคมเปญ 69/70 + polish + README เดโม
  - Acceptance: seed แล้วเดโมได้; ใกล้ครบ SRS §8
  - Verify: checklist SRS §8
  - Depends: Task 8, Task 9

### Checkpoint D — v1 sign-off

- [ ] SRS §8 ผ่าน
- [ ] ไม่รวม pickup assist (Phase 2)

## Phase 2 (ยังไม่ทำ)

- [ ] FR-PCK-01 Pickup assist — แยกแผนเมื่อเริ่ม
