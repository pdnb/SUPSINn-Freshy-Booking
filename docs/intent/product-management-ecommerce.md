# Intent: Product management (ecommerce UX)

| รายการ | ค่า |
| --- | --- |
| สถานะ | Confirmed |
| วันที่ยืนยัน | 2026-08-15 |
| ต้นทาง | grilling `/grill-me /idea-refine หน้าจัดการสินค้าตาม Ecommerce` |
| แผน | Product Admin Ecommerce Redesign (A+B+C) |

## Confirmed statement

- **Outcome:** ยกโมดูลจัดการสินค้าแอดมิน (list + form) ให้มี UX แบบ ecommerce บน shell/ธีมเดิม — จุดชนะคือเตรียมสินค้าตอนเปิดรอบใหม่ได้เร็ว ทำซ้ำหลายตัวไม่เจ็บ
- **User:** แอดมิน 1–ไม่กี่คน ที่ตั้งค่าสินค้าตอนเปิดรอบใหม่ และแก้เร็วๆ ระหว่างรอบ
- **Why now:** v2 ปิดงานคิวครบแล้ว ความเจ็บที่เคยเลื่อนไว้ (non-goal ของ admin-redesign คือ "เลื่อน" ไม่ใช่ "ตัด") ถึงคิว — ฟอร์ม nested bundle ยาว ทำซ้ำหลายตัวช้า list จืด ไม่มีค้นหา/ฟิลเตอร์/รูปปก
- **Success:** สร้างสินค้าชุดใหม่ตอนเปิดรอบ (เสื้อ + ของที่ระลึกหลายตัว) เร็วขึ้นแบบรู้สึกได้ — duplicate โครงเดิมมาแก้ได้, ฟอร์มอ่านรู้เรื่อง ไม่หลง, list มีค้นหา/ฟิลเตอร์/รูปปก/bulk toggle
- **Constraint:** Livewire + `CatalogService`/`ProductImageService` เดิม, ไม่เปลี่ยนโครง variant/`choices[]`, ไม่เปลี่ยนกฎสต็อก, ใช้ `<x-admin.*>` + brand tokens เดิม (Anuphan/cool gray/blue — ไม่เอาส้ม Shopee), การผูกสินค้าเข้ารอบยังอยู่ที่ฟอร์ม BookingRounds
- **Out of scope:** SKU/สต็อก/ราคาต่อตัวเลือก, แตะ production summary aggregation, Filament, storefront product page, ฟีเจอร์โดเมนใหม่, round-centric products (ย้าย CRUD เข้าไปในรอบ)

## Decisions captured during grill

| หัวข้อ | คำตอบ |
| --- | --- |
| ขอบเขต | ทั้งโมดูล — list + form ยกใหม่ให้ฟีล ecommerce ทั้งคู่ |
| ทำไมตอนนี้ | ตอน admin-redesign ตัดออกเพื่อให้ v2 เร็ว ตอนนี้ถึงคิว products แล้ว |
| ความลึกฟีเจอร์ | UX + ฟีเจอร์เบา (ค้นหา/ฟิลเตอร์/duplicate/bulk) ไม่แตะโครง variant |
| ช่วงที่ใช้หนัก | ตอนเตรียมเปิดรอบใหม่ — สร้าง/ปรับสินค้าหลายตัวพร้อมกัน |
| ทิศทาง | A+B+C: list-first + form polish + round-clone wizard |

## Decisions locked at implement

| หัวข้อ | ค่า | เหตุผล |
| --- | --- | --- |
| รูปตอน duplicate/clone | copy ไฟล์ใหม่ ไม่ชี้ path เดิม | `deleteImage` ลบไฟล์จากดิสก์ — แชร์ path จะลบข้ามสินค้า |
| ตำแหน่ง wizard | `/admin/products/clone-from-round` เลือกต้นทาง+ปลายทาง | ไม่ย้ายการผูกสินค้าออกจากฟอร์ม BookingRounds; ลิงก์จาก list สินค้าและหน้ารอบ |
| ฟิลเตอร์รอบบน list | ไม่ default — แสดงทุกสินค้าจนกว่าจะเลือก | จำนวนสินค้าน้อย; default รอบจะซ่อนสินค้าที่ยังไม่ผูก |

## Non-goals (ชัดเจน)

- Round-centric products (สร้าง/แก้สินค้าจากในรอบ) — ขัด constraint + กับดักแก้ข้ามรอบ
- SKU / สต็อก / ราคาต่อตัวเลือก — แตะโครง `choices[]` และ production summary
- คอลัมน์ยอดจองใน list — มี `ProductionSummaryService` เพิ่มทีหลังได้
- Drag-sort รูปด้วย JS lib
- Storefront product page
- Filament / admin kit

## Open questions (ปิดแล้วตอน implement)

- clone รูป: copy ไฟล์ใหม่
- list default filter: ไม่กรองรอบ
- ตำแหน่ง wizard: หน้า `/admin/products/clone-from-round`
