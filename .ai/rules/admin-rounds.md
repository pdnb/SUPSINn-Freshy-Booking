---
paths:
  - 'resources/views/pages/admin/rounds.blade.php'
  - 'resources/views/pages/admin/round-edit.blade.php'
  - 'resources/views/components/admin/product-picker.blade.php'
---

# Booking rounds admin

รอบจองเป็นหน้า list + editor แยกแบบสินค้า (`admin.rounds` / `admin.rounds.create` / `admin.rounds.edit`). อย่าใส่ฟอร์มอินไลน์บน list และอย่าใช้ dialog. ทั้งสองหน้าใช้ `layouts.admin` พร้อม sidebar ซ้าย. หน้า editor ใช้ `grid-2-1`: ซ้ายสินค้าในรอบ, ขวารายละเอียดรอบ. เลือกสินค้าด้วย `x-admin.product-picker` เป็นกริดการ์ดกดสลับเลือก พร้อมค้นหา ปุ่ม `เลือกทั้งหมด` และแบ่งหน้า ไม่ใช่ checkbox ยาว, chip list, หรือ `x-admin.tag-input`. `เลือกทั้งหมด` รวมสินค้าที่ตรงกับคำค้นปัจจุบันทุกหน้าเข้ากับรายการที่เลือกอยู่แล้ว ไม่ได้แทนที่ของเดิม และไม่จำกัดแค่หน้าปัจจุบัน. บันทึกแล้ว redirect กลับ list พร้อม `session('status')`.
