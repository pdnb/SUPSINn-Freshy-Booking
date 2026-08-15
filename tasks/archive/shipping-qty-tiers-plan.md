# Implementation Plan: เรทค่าส่งตามช่วงจำนวน

> v2 เสร็จและรับมอบแล้ว — แผน v2 เก็บไว้ที่ `tasks/archive/v2-plan.md`

อ้างอิง: `docs/intent/shipping-qty-tiers.md` · `docs/srs.md` · `.ai/rules/checkout-shipping.md`

## Overview

ขยายเรทค่าส่งจากยอดคงที่ เป็นช่วงจำนวนที่แอดมินตั้งได้ต่อเรท แล้ว checkout คิดยอดจากผลรวม qty ทุกบรรทัดในตะกร้า โดยยังไม่ให้ลูกค้าเลือกเรท

## Architecture decisions

- เก็บช่วงใน JSON `tiers` บน `shipping_rates` ไม่แยกตาราง
- คงคอลัมน์ `amount` เป็นยอดของช่วงแรก (min_qty ต่ำสุด)
- สูตรคิดอยู่ใน `ShippingRateService::amountForQty()` ตาม ADR-002
- จับคู่: เรียงตาม `min_qty` แล้วเลือกช่วงสุดท้ายที่ `min_qty <= cartQty`
- สร้างด้วย `amount` อย่างเดียว แปลงเป็นช่วง `{min_qty: 1, max_qty: null, amount}` อัตโนมัติ

## Task List

### Phase 0 — Intent + plan files

#### Task 0: บันทึก intent + แทนที่ไฟล์แผน

**Description:** เขียน `docs/intent/shipping-qty-tiers.md` จาก restate ที่ยืนยัน ย้าย v2 ไป `tasks/archive/` แล้วแทนที่ `tasks/plan.md` / `tasks/todo.md`

**Acceptance criteria:**

- [x] intent มี confirmed statement + decisions + non-goals
- [x] ไฟล์แผนเป็นเนื้อหา shipping qty tiers ทั้งไฟล์
- [x] v2 ยังอ่านได้ที่ `tasks/archive/v2-plan.md` และ `tasks/archive/v2-todo.md`

**Verification:** อ่านไฟล์เทียบกับ restate ที่ผู้ใช้ตอบ Yes
**Dependencies:** None
**Scope:** S

### Phase 1 — Domain

#### Task 1: schema + factory

**Description:** migration เพิ่ม `tiers` (json, nullable) + backfill แถวเดิมจาก `amount` เป็นช่วงเดียว `min_qty = 1` — แยก schema กับ data; cast ใน model; factory ใส่ช่วงเดียวจาก `amount`

**Acceptance criteria:**

- [x] คอลัมน์ `tiers` มีใน `shipping_rates`
- [x] แถวเดิมได้ช่วงเดียวจาก `amount`
- [x] factory สร้างเรทพร้อม `tiers`

**Verification:** migrate ในเทสต์ผ่าน
**Dependencies:** Task 0
**Scope:** S

#### Task 2: `amountForQty` + validate

**Description:** `ShippingRateService::amountForQty()` + validate ช่วง แล้ว sync `amount` จากช่วงแรก; `create`/`update` ที่ส่งแค่ `amount` ยังผ่าน

**Acceptance criteria:**

- [x] คิดถูกสำหรับ 1 / กลางช่วง / เกินช่วง / ช่องว่างกลาง
- [x] `min_qty` ซ้ำหรือติดลบถูก reject
- [x] `amount` อย่างเดียวยังสร้างช่วง implicit

**Verification:** `php artisan test --compact tests/Feature/ShippingRateServiceTest.php`
**Dependencies:** Task 1
**Scope:** M

### Phase 2 — Checkout + admin

#### Task 3: checkout quote ตามจำนวน

**Description:** `CheckoutService::quote()` ใช้ `amountForQty($rate, $this->cart->count())` เมื่อส่งไปรษณีย์ — ไม่เพิ่ม `<select>` เรท

**Acceptance criteria:**

- [x] ตะกร้า qty รวมแล้วได้ยอดตามช่วง
- [x] pickup ยัง 0
- [x] เรทยังเป็นตัวแรกตาม `sort_order`

**Verification:** `php artisan test --compact tests/Feature/CheckoutServiceTest.php`
**Dependencies:** Task 2
**Scope:** S

#### Task 4: ฟอร์มแอดมิน repeater

**Description:** ฟอร์มเป็น repeater ช่วง (min / max ว่างได้ / ยอด) เพิ่ม-ลบแถว; รายการแสดงสรุปช่วง

**Acceptance criteria:**

- [x] บันทึกช่วงจากฟอร์มแล้ว `tiers` + `amount` ตรง
- [x] รายการแสดงยอดช่วงหรือสรุปสั้นๆ

**Verification:** `php artisan test --compact tests/Feature/AdminShippingRateTest.php`
**Dependencies:** Task 2
**Scope:** M

### Phase 3 — Docs + rule

#### Task 5: อัปเดต SRS + checkout-shipping rule

**Description:** อัปเดต `.ai/rules/checkout-shipping.md` และ `docs/srs.md` FR-CHK-04 / FR-ADM-02

**Acceptance criteria:**

- [x] เอกสารบอกสูตรช่วง + overflow
- [x] เทสต์ที่เกี่ยวผ่าน

**Verification:** `php artisan test --compact` เฉพาะไฟล์ที่เกี่ยว
**Dependencies:** Task 3, Task 4
**Scope:** S

## Out of scope

คิดตามจังหวัด/รหัสไปรษณีย์, ส่งฟรีตามยอด, ให้ลูกค้าเลือกเรท, น้ำหนักต่อสินค้า, เลขพัสดุ, เปลี่ยน `FulfillmentMethod`

## ลำดับแนะนำ

`0 → 1 → 2 → 3 → 4 → 5`
