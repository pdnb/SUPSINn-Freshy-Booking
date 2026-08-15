# Intent: Admin redesign (queue-first)

| รายการ | ค่า |
| --- | --- |
| สถานะ | Confirmed |
| วันที่ยืนยัน | 2026-08-15 |
| ต้นทาง | grilling `/grill-me redesign ระบบ admin` |
| แผน | Admin queue redesign (queue-first Livewire) |

## Confirmed statement

- **Outcome:** Admin ที่ใช้วันเปิดจองแล้วลื่น โดยเฉพาะลูปตรวจสลิป: ดูสลิป/ยอด/ผู้จอง → ยืนยันหรือปฏิเสธ → รายการถัดไป โดยไม่กระโดดหลายหน้า
- **User:** เจ้าหน้าที่แอดมิน (1–ไม่กี่คน) ที่เคลียร์คิว `pending_review` ช่วงรอบเปิด
- **Why now:** ฟีเจอร์ v1 ครบแล้ว แต่ UI admin ยังเป็น scaffolding และไม่มี mockup แอดมินครบ — bottleneck จริงอยู่ที่คิว ไม่ใช่ CRUD ตั้งค่า
- **Success:** คิวเป็นศูนย์กลางงาน; หลัง redesign ทำ approve/reject ต่อเนื่องได้เร็วขึ้นชัดเจน; หน้าอื่นใช้ shell/nav/ธีมเดียวกันและใช้งานได้โดยไม่รู้สึกคนละระบบ
- **Constraint:** อยู่บน Livewire + services/โดเมนที่มีอยู่แล้ว — redesign ไม่ rewrite เป็น Filament; ไม่ขุดฟอร์ม products/rounds/shipping ลึกเท่าคิว
- **Out of scope:** ย้าย Filament/admin kit; redesign CRUD ลึกทั้งแผง; ฟีเจอร์ใหม่โดเมน (roles แยก, pickup assist, auto-verify สลิปแทนคน); เปลี่ยนกฎธุรกิจออเดอร์/สต็อก

## Decisions captured during grill

| หัวข้อ | คำตอบ |
| --- | --- |
| ปัญหาหลัก | UI เป็น scaffolding / ใช้ยากตอนคิวหนาแน่น — ไม่ใช่ฟีเจอร์ขาด |
| งานวันเปิดจอง | คิวสลิป → ดูรายละเอียด → ยืนยัน/ปฏิเสธ → ถัดไป |
| ขอบเขตนอกคิว | shell/nav/ธีมเดียวกัน พอใช้ได้ ไม่ redesign ฟอร์ม CRUD ลึก |
| Stack | อยู่ Livewire เดิม ไม่ย้าย Filament |

## Non-goals (ชัดเจน)

- Filament / admin kit migration
- Deep redesign of product / shipping / booking-round forms
- Claim locks, multi-staff assignment, auto-verify replacing humans
- Changing OrderService transition rules
