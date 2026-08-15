# ADR-002: Application services layer

## Status
Accepted

## Date
2026-08-15

## Context
ระบบเป็นโมโนลิธ Laravel + Livewire (ADR-001) มีกฎธุรกิจที่ต้องใช้ซ้ำทั้งหน้าร้านและหลังบ้าน เช่น สินค้าในรอบเปิด, กฎคอมโบ, ค่าส่ง, เกต checksum สลิป, สถานะออเดอร์, ไม่ล็อกสต็อก

ถ้าใส่กฎเหล่านี้ใน Livewire โดยตรง จะทดสอบยาก ซ้ำในหลายคอมโพเนนต์ และผูกโดเมนกับ HTTP/UI

ต้องการชั้นบางๆ สำหรับ use case โดยไม่เพิ่ม DDD/hexagonal หรือ repository ทับ Eloquent

## Decision
ใช้ **application services** เป็นที่อยู่ของกฎธุรกิจและ use case

```text
Livewire / Blade (UI เท่านั้น)
        |
        v
app/Services  (use case + กฎโดเมน)
        |
        +--> Eloquent models / DB
        |
        +--> app/Contracts (พอร์ตภายนอก เช่น SlipVerifier)
                    |
                    v
              Adapter (stub / ผู้ให้บริการจริง)
```

### ที่อยู่โค้ด

```text
app/
  Contracts/
    SlipVerifier.php
  Services/
    Catalog/
    Booking/
    Shipping/
    Cart/
    Checkout/
    Order/
    Payment/
```

### บริการ v1 (หนึ่งคลาสต่อพื้นที่ SRS — แยกเมื่อคลาสโต)

| คลาส | หน้าที่ | ถูกเรียกจาก | Task |
| --- | --- | --- | --- |
| `Catalog\CatalogService` | CRUD สินค้า, กฎ bundle/simple, ตัวเลือก | แอดมินแค็ตตาล็อก | 2 |
| `Booking\BookingRoundService` | CRUD รอบ, ผูกสินค้า, รอบที่เปิดตอนนี้ | แอดมินรอบ, หน้าร้าน | 4, 5 |
| `Shipping\ShippingRateService` | CRUD เรท, เรท active, ค่าส่งตามช่องทาง | แอดมิน, checkout | 3, 6 |
| `Cart\CartService` | ตะกร้า session, เพิ่ม/แก้ภายใต้กฎคอมโบ | หน้าร้าน | 5, 6 |
| `Checkout\CheckoutService` | ฟิลด์ผู้จอง, จุดรับ/จัดส่ง, ยอดสินค้า+ค่าส่ง | หน้าร้าน | 6 |
| `Payment\SlipVerificationService` | เรียก `SlipVerifier`, กันสลิปซ้ำตามผลตรวจ | ขั้นตอนจ่าย | 7 |
| `Order\OrderService` | สร้างออเดอร์หลังสลิปผ่าน (`pending_review`), ไม่ล็อกสต็อก, เปลี่ยนสถานะแอดมิน, ติดตามด้วย token | จ่ายเงิน, แอดมิน, หน้าติดตาม | 7, 8, 9 |

`SlipVerifier` เป็น **สัญญาภายนอก** ไม่ใช่ service ของโดเมน — bind stub ใน Task 0/7 แล้วสลับ adapter จริงทีหลัง

### กติกา

**Always**

- Livewire เรียกเมธอด service ต่อหนึ่งการกระทำของผู้ใช้ (ใส่ตะกร้า, คิดยอด, จบออเดอร์, เปลี่ยนสถานะ)
- กฎที่ข้ามหลายโมเดลหรือมีผลเงิน/สถานะ/รอบเปิด อยู่ใน service
- Service รับข้อมูลเป็น DTO หรือ array ที่ผ่าน validation รูปฟอร์มแล้ว — **ไม่** depend `Request` หรือ Livewire component
- การสร้างออเดอร์+สลิปอยู่ใน transaction ของ `OrderService`
- พอร์ตภายนอกอยู่ที่ `app/Contracts`

**Ask first**

- แยก service เป็น Action ทีละคลาส
- ย้ายกฎไป Model observer / Event โดยไม่มีทางเข้าผ่าน service
- เพิ่ม bounded context ใหม่นอกตารางด้านบน

**Never (v1)**

- กฎรอบเปิด / คอมโบ / ค่าส่ง / เกตสลิป / transition สถานะใน Blade หรือ Livewire
- `Order::create()` จาก Livewire ใน flow checkout
- เรียก `SlipVerifier` จาก Livewire โดยตรง
- God class รวมทุกโดเมน
- Repository layer ทับ Eloquent
- สถาปัตย์ hexagonal / aggregate DDD เต็มรูป

CRUD แอดมินที่แค่ persist ฟิลด์ยังผ่าน service ของพื้นที่นั้น (ไม่ให้ Livewire เขียนโมเดลตรง) เพื่อให้ validation โดเมนที่เดียว

## Alternatives Considered

### กฎธุรกิจใน Livewire + Eloquent
- Pros: ไฟล์น้อย, ส่งของเร็วมากช่วงแรก
- Cons: ซ้ำกฎ, เทสต์ต้องขึ้น HTTP, หน้าร้านกับแอดมินแยกทางแล้ว drift
- Rejected: กฎสลิป/รอบ/คอมโบต้องใช้ร่วมและทดสอบแยกจาก UI

### Laravel Actions (คลาสละหนึ่งเมธอด) เป็นมาตรฐานบังคับ
- Pros: ขอบเขตแคบ ชัด
- Cons: จำนวนคลาสมากเกินสำหรับ v1
- Rejected ตอนนี้: เริ่มจาก service ต่อพื้นที่; แยก Action เมื่อเมธอดโต

### Repository + Domain entities
- Pros: สลับ persistence ได้
- Cons: Eloquent อยู่แล้ว; ชั้นซ้ำโดยไม่มีแผนสลับ ORM
- Rejected: YAGNI

## Consequences
- Task 0 สร้างโครง `app/Contracts` + `app/Services/*` และ bind `SlipVerifier` เป็น stub
- Task 2 เป็นต้น implement กฎใน service ของพื้นที่นั้น แล้วให้ Livewire เรียก
- เทสต์กฎธุรกิจเป็น unit/feature ที่เรียก service โดยไม่ต้องเรนเดอร์ Livewire ทั้งหน้า (UI ยังมี feature/browser test ตามแผน)
- อ้างอิงใน `docs/srs.md` §2.1, `tasks/plan.md`
