# Todo: เรทค่าส่งตามช่วงจำนวน

อ้างอิง: `tasks/plan.md` · `docs/intent/shipping-qty-tiers.md`
v2 เสร็จและรับมอบแล้ว — todo v2 เก็บไว้ที่ `tasks/archive/v2-todo.md`

## Phase 0 — Intent + plan files

- [x] **Task 0:** เขียน `docs/intent/shipping-qty-tiers.md` + แทนที่ `tasks/plan.md` / `tasks/todo.md` (ย้าย v2 ไป archive)
  - Acceptance: intent มี confirmed statement/decisions/non-goals; ไฟล์แผนเป็นเนื้อหา shipping qty tiers; v2 อยู่ที่ `tasks/archive/`
  - Verify: อ่านเทียบ restate ที่ผู้ใช้ยืนยัน
  - Depends: —

## Phase 1 — Domain

- [x] **Task 1:** migration `tiers` + backfill จาก `amount` + cast + factory
  - Acceptance: คอลัมน์มี; แถวเดิมได้ช่วงเดียว; factory พร้อม `tiers`
  - Verify: migrate ในเทสต์ผ่าน
  - Depends: Task 0
- [x] **Task 2:** `ShippingRateService::amountForQty` + validate ช่วง + sync `amount` + implicit tier
  - Acceptance: คิดถูก 1 / กลางช่วง / เกินช่วง / ช่องว่างกลาง; reject min ซ้ำ/ติดลบ; `amount` อย่างเดียวยังผ่าน
  - Verify: `php artisan test --compact tests/Feature/ShippingRateServiceTest.php`
  - Depends: Task 1

## Phase 2 — Checkout + admin

- [x] **Task 3:** `CheckoutService::quote` คิดจาก `cart->count()` เมื่อไปรษณีย์
  - Acceptance: qty รวมได้ยอดตามช่วง; pickup ยัง 0; เรทแรกตาม `sort_order`
  - Verify: `php artisan test --compact tests/Feature/CheckoutServiceTest.php`
  - Depends: Task 2
- [x] **Task 4:** ฟอร์มแอดมิน repeater ช่วง + สรุปในรายการ
  - Acceptance: บันทึกช่วงแล้ว `tiers` + `amount` ตรง; รายการแสดงสรุป
  - Verify: `php artisan test --compact tests/Feature/AdminShippingRateTest.php`
  - Depends: Task 2

## Phase 3 — Docs + rule

- [x] **Task 5:** อัปเดต `.ai/rules/checkout-shipping.md` + `docs/srs.md` FR-CHK-04 / FR-ADM-02
  - Acceptance: เอกสารบอกสูตรช่วง + overflow; เทสต์ที่เกี่ยวผ่าน
  - Verify: `php artisan test --compact` เฉพาะไฟล์ที่เกี่ยว
  - Depends: Task 3, Task 4
