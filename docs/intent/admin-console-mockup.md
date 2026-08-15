# Intent: Admin console HTML mockup

| รายการ | ค่า |
| --- | --- |
| สถานะ | Confirmed |
| วันที่ยืนยัน | 2026-08-15 |
| ต้นทาง | grilling `/grill-me` บน Admin Console PRD (Stitch / Meridian) |
| แผน | `tasks/plan.md` · `tasks/todo.md` |
| อ้างอิงต่อ | `docs/srs.md` §4.1 · `docs/intent/admin-redesign.md` · `docs/references/admin-prototype/` |

## Confirmed statement

- **Outcome:** โปรโตไทป์ HTML แอดมินความละเอียดสูง (คลิกได้) ของระบบจองชุดเฟรชชี่ มรส. — โครง 11 ไฟล์แบบ PRD แต่เนื้อหาเป็นงานจริง ไม่ใช่ร้าน Meridian
- **User:** เจ้าหน้าที่แอดมิน 1–ไม่กี่คน ที่วันเปิดจองทำสองงานน้ำหนักเท่ากัน: ตรวจสลิป แล้วจัด fulfillment (ไปรษณีย์ / จุดรับ)
- **Why now:** SRS ยังไม่มี mockup แอดมินครบ; มี brief Stitch ร้านทั่วไปที่ต้องซ่อมก่อนลงมือวาด; และนี่จะเป็นสเปกหน้าตาแอดมินรุ่นถัดไป (ย้าย Livewire ไป tech-utility ทีหลัง)
- **Success:** `index.html` ลิงก์ครบ; evaluator เดิน Overview → คิวสลิป (ดูสลิป/ยอด/ผู้จอง → ยืนยันหรือปฏิเสธ → ถัดไป) → Fulfillment (อัปเดตช่องทาง/สถานะ) โดยไม่ตัน; หน้าสินค้า/Pickup/สรุปผลิต/รอบจอง/Settings เปิดได้และข้อมูลตัวอย่างต่อกัน; UI ไทยทั้งจอ; ตัวเลขติดป้าย Sample; โทน tech-utility คนละชุดกับหน้าร้าน
- **Constraint:** สแตติก HTML (+ `admin-data.js` ชุดเดียว); แคตตาล็อกตัวอย่าง = คอมโบปี 70 + เฟรชชี่ปี 69; ไม่มี SKU/สต็อกขาย / โค้ดส่วนลด / ลูกค้า CRM / กราฟรายได้; desktop เป็นหลัก (≥1280 ใช้ได้ถึง ~920); ปุ่มลัดคิว (j/k/c/r) มี hint ใน mockup
- **Out of scope:** ลงธีมนี้ใน Livewire ในรอบนี้; แก้กฎพาเลทตอนนี้; ชื่อ Meridian; ล็อกอิน; multi-vendor / chat / CMS / tax engine / แอปมือถือ; ฟีเจอร์โดเมนใหม่ (claim lock, auto-verify)

## Decisions captured during grill

| หัวข้อ | คำตอบ |
| --- | --- |
| งานของ PRD | HTML mockup ของโปรเจ็กนี้ — ไม่สร้าง Meridian และไม่ restyle Livewire ในรอบนี้ |
| โครงไฟล์ | คง 11 ไฟล์ แล้ว remap เนื้อหา (ชื่อไฟล์ตรงโมดูลจริง ไม่ใช้ discounts/analytics) |
| โทนภาพ | tech-utility คนละชุดกับหน้าร้าน (slate + accent เขียว) |
| หลังมี HTML | เป็นสเปกหน้าตาแอดมินรุ่นถัดไป — ย้าย Livewire ทีหลัง |
| Hero | Overview → คิวสลิป และ Overview → Fulfillment น้ำหนักเท่ากัน |
| ภาษา | ไทยทั้งจอ ชื่อระบบจองชุดเฟรชชี่ มรส. ไม่ใช้คำว่า Meridian |

## 11 ไฟล์

Launcher · Overview · คิวสลิป · รายละเอียดออเดอร์ · สินค้า · แก้สินค้า · Pickup · สรุปผลิต · Fulfillment · รอบจอง · Settings (แท็บเรทส่ง + แบนเนอร์)

## Non-goals (ชัดเจน)

- Livewire restyle / พาเลทที่สองใน `app.css` / แก้ `.ai/rules/admin.md` ในรอบนี้
- ชื่อ Meridian, chrome ภาษาอังกฤษ, แคตตาล็อก home goods
- ล็อกอิน, ลูกค้า CRM, inventory แบบ SKU, โค้ดส่วนลด, กราฟรายได้
- Filament, claim lock, auto-verify, pickup เลย์เอาต์มือถือเนทีฟ
