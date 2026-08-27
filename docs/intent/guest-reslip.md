# Intent: แนบสลิปใหม่ (guest)

| รายการ | ค่า |
| --- | --- |
| สถานะ | Confirmed |
| วันที่ยืนยัน | 2026-08-27 |
| ต้นทาง | grilling `/grill-me` |

## Confirmed statement

- **Outcome:** นักศึกษาที่ถูกขอสลิปใหม่จ่ายและแนบสลิปใหม่จากหน้ารายละเอียดออเดอร์ได้จริง แล้วออเดอร์กลับเข้าคิวตรวจ
- **User:** นักศึกษาที่เปิดออเดอร์ด้วยลิงก์ติดตามเดิม (guest / LINE เหมือนวันนี้)
- **Why now:** สถานะ `NeedReslip` มีแล้ว แต่หน้าออเดอร์ไม่มีที่จ่าย/แนบ — ทำต่อไม่ได้
- **Success:** ออเดอร์ `NeedReslip` โชว์ QR PromptPay ตาม `amount_due_now` + dropzone; สลิปผ่าน checksum แล้วแทนที่ของเก่า และกลับ `PendingReview` อัตโนมัติ
- **Constraint:** checksum / ประเภทไฟล์ / ยอด QR ใช้กฎเดียวกับ checkout; แนบใหม่ได้เฉพาะสถานะนี้; ไม่สร้างหน้าจ่ายแยก
- **Out of scope:** เหตุผลจากแอดมิน, ประวัติสลิปเก่า, LINE แจ้งเมื่อขอสลิปใหม่, เปลี่ยนปุ่มขอสลิปใหม่ฝั่งแอดมิน

## Implementation notes

- Domain: `OrderService::replaceSlip()` — ไม่ใช้ `transition()` เพราะไม่มี `User` actor
- UI: [`resources/views/pages/storefront/order-confirmation.blade.php`](../../resources/views/pages/storefront/order-confirmation.blade.php) — action `resubmit()` เฉพาะ `NeedReslip`
- สลิปเก่าถูกลบ (ไฟล์ + แถว) ไม่เก็บประวัติ
