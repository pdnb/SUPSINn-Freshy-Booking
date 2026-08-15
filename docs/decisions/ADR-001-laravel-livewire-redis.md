# ADR-001: Laravel + Livewire + SQL DB + Redis

## Status
Accepted

## Date
2026-08-15

## Context
ระบบจองชุดเฟรชชี่ต้องการเว็บโมโนลิธที่รวมหน้าร้าน guest กับหลังบ้านแอดมิน, อัปโหลดสลิป, และโดเมน eCommerce เชิง relational โดยทีมล็อก stack ก่อน bootstrap (Task 0)

## Decision
ใช้ **Laravel + Livewire** เป็นแอปหลัก, ฐานข้อมูลผ่าน Laravel เป็น **SQLite / MySQL / PostgreSQL** ตาม environment, และ **Redis** สำหรับ cache / queue / session ตามที่เหมาะกับ deploy

## Alternatives Considered

### Next.js (หรือ SPA แยก) + API
- Pros: แยก frontend สมัยใหม่
- Cons: สองโค้ดเบส, auth/admin/upload ซับซ้อนขึ้นสำหรับแคมเปญที่ต้อง ship เร็ว
- Rejected: เกินความต้องการ v1 ของทีมเดียว

### Laravel + Inertia (React/Vue)
- Pros: UX ใกล้ SPA
- Cons: เพิ่มชั้น JS build และความซับซ้อนเทียบ Livewire สำหรับฟอร์มแอดมิน/checkout
- Rejected ตอนนี้: เลือก Livewire เพื่อความเร็วของ CRUD และฟอร์ม; เปลี่ยนได้ภายหลังถ้าจำเป็น

## Consequences
- Task 0 bootstrap ตาม Laravel + Livewire
- `.env` สลับ `DB_CONNECTION` ระหว่าง sqlite/mysql/pgsql ได้โดยไม่เปลี่ยนโมเดลโดเมน
- ต้องมี Redis ในสภาพแวดล้อมที่ต้องการ queue/cache จริง; dev อาจใช้ Docker หรือ fallback driver ชั่วคราวตามแผน
- อ้างอิงใน `tasks/plan.md`, `tasks/todo.md`, `docs/srs.md`
- ชั้นกฎธุรกิจ: application services ตาม `docs/decisions/ADR-002-application-services.md`
