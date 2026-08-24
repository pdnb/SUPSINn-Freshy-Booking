# Implementation Plan: Admin console HTML mockup

อ้างอิง: `docs/intent/admin-console-mockup.md` · `docs/srs.md` §4.1

แผนเรทค่าส่งที่ปิดแล้วอยู่ที่ `tasks/archive/shipping-qty-tiers-plan.md`

## Overview

สร้างโปรโตไทป์ HTML แอดมิน 11 ไฟล์ของระบบจองชุดเฟรชชี่ มรส. ที่ `docs/references/admin-prototype/` — โทน tech-utility, UI ไทย, hero เท่ากันระหว่างคิวสลิปกับ fulfillment ไม่แตะ Livewire ในรอบนี้

## Architecture decisions

- สแตติก HTML + `admin-data.js` ชุดเดียว; mutation เก็บใน sessionStorage
- Chrome ฉีดจาก `assets/chrome.js` (sidebar + topbar)
- โทเคน tech-utility ใน `:root` เท่านั้น — slate + accent เขียว ไม่ยืม `theme.js` หน้าร้าน
- ฟอนต์ IBM Plex Sans Thai + IBM Plex Mono (ไม่มี serif ละติน)
- สถานะ/ช่องทางตามโดเมนจริง ไม่มี SKU / ส่วนลด / กราฟรายได้

## Task List

### Task 0: บันทึก intent + แทนที่ไฟล์แผน

**Acceptance criteria:**

- [x] `docs/intent/admin-console-mockup.md` มี confirmed statement + decisions + non-goals
- [x] แผน shipping อยู่ที่ `tasks/archive/`
- [x] `tasks/plan.md` / `tasks/todo.md` เป็นงาน mockup นี้

**Verification:** อ่านไฟล์เทียบกับ restate ที่ผู้ใช้ตอบ Yes
**Dependencies:** None
**Scope:** S

### Task 1: Foundation

โฟลเดอร์ + tokens + shell + chrome + data + launcher

**Verification:** เปิด `index.html` แล้วลิงก์ครบ 11 โมดูล
**Dependencies:** Task 0
**Scope:** M

### Task 2: Overview

KPI `[ตัวอย่าง]` + attention + recent orders ลิงก์ F1/F2

**Dependencies:** Task 1
**Scope:** S

### Task 3: F1 คิวสลิป + รายละเอียด

ยืนยัน/ปฏิเสธ, ถัดไป, hint j/k/c/r

**Dependencies:** Task 1
**Scope:** M

### Task 4: F2 Fulfillment

กรองช่องทาง, พร้อมรับ / จัดส่งแล้ว

**Dependencies:** Task 1
**Scope:** M

### Task 5: สินค้า + ฟอร์ม

ค้นหา, validation ชื่อ/ราคา, ไม่มี SKU

**Dependencies:** Task 1
**Scope:** M

### Task 6: Pickup + สรุปผลิต

ค้นรหัสนักศึกษา, ตารางไซส์ `[ตัวอย่าง]`

**Dependencies:** Task 1
**Scope:** M

### Task 7: รอบจอง + Settings

รายการรอบ; แท็บค่าส่งช่วงจำนวน + แบนเนอร์

**Dependencies:** Task 1
**Scope:** M

### Task 8: Gate + SRS + เทสต์

ลิงก์ข้ามจอ, อัปเดต SRS §4.1, Pest ตรวจไฟล์/`data-od-id`/รหัสออเดอร์ร่วม

**Verification:** `php artisan test --compact tests/Feature/AdminPrototypeTest.php`
**Dependencies:** Task 2–7
**Scope:** S

## Out of scope

Livewire restyle, แก้ `.ai/rules/admin.md`, Meridian, ล็อกอิน, CRM, SKU, ส่วนลด, กราฟรายได้, Filament, claim lock, auto-verify

## ลำดับแนะนำ

`0 → 1 → 2 → 3 → 4` (checkpoint F1/F2) `→ 5 → 6 → 7 → 8`
