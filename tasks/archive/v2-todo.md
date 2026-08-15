# Todo: Freshy Booking v2

อ้างอิง: `tasks/plan.md` · `docs/intent/v2.md` · `docs/intent/admin-redesign.md` · `docs/intent/ads-banner.md`
ลำดับบังคับ: **Production summary → Fulfillment → Queue redesign → Pickup → Ads**
v1 เสร็จและรับมอบแล้ว — todo v1 เก็บไว้ที่ `tasks/archive/v1-todo.md`

## Phase 0 — Intent + plan files

- [x] **Task 0:** เขียน `docs/intent/v2.md` + แทนที่ `tasks/plan.md` / `tasks/todo.md` ด้วยแผน v2
  - Acceptance: intent มี confirmed statement/decisions/non-goals; ไฟล์แผนเป็นเนื้อหา v2 ทั้งไฟล์; v1 เก็บไว้ที่ `tasks/archive/`
  - Verify: อ่านเทียบ restate ที่ผู้ใช้ยืนยัน
  - Depends: —

## Phase 1 — Production summary

- [x] **Task 1:** `ProductionSummaryService` — รอบจอง + คณะ (optional) + สถานะที่ยืนยันสลิปแล้ว จาก `order_items.choices × qty`
  - Acceptance: นับเฉพาะ `confirmed`/`ready_for_pickup`/`shipped`/`completed`; กรองรอบ+คณะ; bundle นับทีละ label; ลำดับผลลัพธ์คงที่
  - Verify: unit/feature test + seed ปี 70
  - Depends: Task 0
- [x] **Task 2:** Admin Livewire สรุปยอด + ฟิลเตอร์ + nav + ดาวน์โหลด CSV/PDF/Excel
  - Acceptance: ตารางตามฟิลเตอร์; สามไฟล์ตรงกับตารางบนจอ; ภาษาไทยไม่เพี้ยน; เข้าถึงจาก nav
  - Verify: feature test export 200 + แถวที่คาด
  - Depends: Task 1

### Checkpoint A

- [x] เลือกรอบจอง/คณะแล้วเห็นยอด + ดาวน์โหลดครบสามรูปแบบตรงกับออเดอร์ทดสอบ

## Phase 2 — Fulfillment

- [x] **Task 3:** คิว fulfillment ตามช่องทาง (`bookstore`/`hall`/`post`) + action จนจบผ่าน `OrderService`
  - Acceptance: กรองตามช่องทาง; ทำพร้อมรับ/ส่งแล้ว/รับครบ+ใบเสร็จ; ไม่มีเลขพัสดุ/พิมพ์/ขนส่ง
  - Verify: feature test ต่อช่องทาง + ต่อ transition
  - Depends: Task 2
- [x] **Task 4:** Tests + polish ลูปหลังคิวสลิป (ไม่แตะกฎ transition)
  - Acceptance: ครอบ transition ที่อนุญาต/ไม่อนุญาต; ใช้ได้บนมือถือ/แล็ปท็อป
  - Verify: `php artisan test --compact` เฉพาะไฟล์ที่เกี่ยว
  - Depends: Task 3

### Checkpoint B

- [x] เคลียร์ออเดอร์ `confirmed` ตามช่องทางจน `completed` ได้โดยไม่ใช้คิว `pending_review`

## Phase 3 — Queue redesign (gap-fill)

- [x] **Task 5:** Audit คิวสลิปเทียบ `docs/intent/admin-redesign.md` แล้วปิดเฉพาะช่องว่าง
  - Acceptance: มีรายการช่องว่าง (หรือบันทึกว่าไม่มี); ที่ปิดมีเทสต์/ตรวจมือกำกับ; ไม่ deep-CRUD
  - Verify: เทสต์คิวเดิมยังผ่าน
  - Depends: Task 4
- [x] **Task 6:** Shell/nav เดียวครอบ Production / Fulfillment / Orders / Pickup (+ ช่อง Ads)
  - Acceptance: เข้าถึงทุกโมดูลจาก nav เดียว; ธีมเดียวกัน
  - Verify: ตรวจมือ + smoke test หน้าแอดมิน
  - Depends: Task 5

### Checkpoint C

- [x] ลูป approve/reject ยังผ่านเทสต์เดิม + ช่องว่าง intent ถูกปิดหรือบันทึกว่าไม่มี

## Phase 4 — Staff pickup

- [x] **Task 7:** หน้า Pickup assist — ค้นหา → รายการ+ไซส์+จุดรับ → รับครบ+ใบเสร็จ
  - Acceptance: ค้นด้วยรหัสออเดอร์/รหัสนักศึกษา; เห็นไซส์+จุดรับ; `markPickedUp`+ใบเสร็จ; ไม่มี claim lock/สแกน/จอคิว
  - Verify: feature test ค้นหา + mark picked up
  - Depends: Task 6
- [x] **Task 8:** Feature tests pickup (ไม่รวม student checklist)
  - Acceptance: ครอบ happy path + สถานะที่ไม่อนุญาต + ค้นไม่เจอ
  - Verify: `php artisan test --compact --filter=Pickup`
  - Depends: Task 7

### Checkpoint D

- [x] รับของหน้างานจากมือถือ/แล็ปท็อปได้ด้วยรหัสออเดอร์หรือรหัสนักศึกษา

## Phase 5 — Ads banner

- [x] **Task 9:** `ads_banners` migration/model + `AdsBannerService` + admin CRUD เรียง/เปิดปิด
  - Acceptance: อัปโหลดรูป+URL+ลำดับ+active; query active เรียงลำดับผ่าน service
  - Verify: feature test CRUD + ordering
  - Depends: Task 8
- [x] **Task 10:** Storefront home carousel (`home-banner`)
  - Acceptance: มีแบนเนอร์ → เลื่อนได้; ไม่มี → ซ่อนช่อง; แตะแล้วเปิด URL นอก
  - Verify: feature test หน้าแรกทั้งสองกรณี
  - Depends: Task 9

### Checkpoint E — v2 complete

- [x] ครบห้าสไลซ์ตาม success ใน `docs/intent/v2.md`
- [x] Out of scope (เลขพัสดุ, API โรงงาน, แจ้งเตือน, verifier จริง, student checklist, Filament, roles) ยังไม่เริ่ม
