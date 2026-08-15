# Implementation Plan: Freshy Booking (v1)

> เก็บเป็นประวัติ — v1 รับมอบแล้ว แผนที่ใช้งานอยู่คือ `tasks/plan.md`

## Overview

สร้างเว็บจองชุดเฟรชชี่ตาม `docs/srs.md` / `docs/intent/freshy-booking.md` เป็นแนว eCommerce ผู้ขายเดียว: หน้าร้าน guest (mobile-first) + หลังบ้านแอดมิน (สินค้าเต็มรูปแบบ, รอบจองผูกสินค้า, ค่าส่ง, ตรวจสลิปหลัง checksum, สถานะออเดอร์) — **ไม่รวม** pickup assist (Phase 2)

## Architecture decisions

> Stack **ล็อกแล้ว** (2026-08-15): Laravel + Livewire + SQLite/MySQL/PostgreSQL + Redis  
> Services **ล็อกแล้ว:** `app/Services` + `app/Contracts` ตาม ADR-002


| การตัดสินใจ                           | ค่าที่ล็อก                                                                       | เหตุผลสั้นๆ                                                                   |
| ------------------------------------- | -------------------------------------------------------------------------------- | ----------------------------------------------------------------------------- |
| Framework                             | **Laravel** (เว็บโมโนลิธ)                                                        | Auth แอดมิน, อัปโหลดสลิป, CRUD หลังบ้าน, deploy เดียวครบ storefront+admin     |
| UI                                    | **Livewire + Blade** ตาม brand-spec                                              | เร็วสำหรับแอดมิน + ฟอร์ม checkout; mobile UI จาก mockup แปลงเป็น Blade ได้ตรง |
| DB                                    | **SQLite** (local/dev ได้) / **MySQL** / **PostgreSQL** (ผ่าน Laravel DB driver) | โดเมน relational ชัด; สลับ env ตาม deploy โดยไม่เปลี่ยนโมเดล                  |
| Cache / queue / session (ตามที่เหมาะ) | **Redis**                                                                        | คิวงาน, cache, session/scale ช่วงเปิดจอง                                      |
| สลิป                                  | Object storage หรือ `storage` + adapter                                          | แยกไดรฟ์ไฟล์จากโค้ด                                                           |
| Slip checksum                         | Interface `SlipVerifier` + adapter ผู้ให้บริการจริง                              | สลับ vendor ได้โดยไม่ผูกโดเมนออเดอร์                                          |
| PromptPay                             | Config ร้าน + แสดงยอด; QR dynamic ถ้าทำได้ ไม่งั้น QR คงที่ + ยอดบังคับตรงสลิป   | SRS เปิดไว้ — เริ่มจาก config ได้                                             |
| Cart                                  | Session/cookie cart ฝั่ง guest                                                   | ไม่มีล็อกอินนักศึกษา                                                          |
| Order track                           | `order_number` + `public_token` ในลิงก์                                          | กันเดาเลขรัน                                                                  |
| Stock                                 | ไม่มี reservation / ไม่ลดสต็อกตอนสั่ง                                            | ตาม intent                                                                    |
| Application services                  | `app/Services` + `app/Contracts`                                                 | กฎธุรกิจนอก Livewire; ดู ADR-002                                              |


```text
Vertical slices (ลำดับขึ้นต่อกัน)

0 Stack bootstrap
1 Admin auth
2 Catalog admin (simple + bundle)
3 Shipping rates
4 Booking rounds ↔ products
5 Storefront catalog (รอบเปิดเท่านั้น)
6 Cart + checkout fields + fulfillment channel
7 Payment QR + slip upload + checksum gate
8 Admin order queue + status + receipt mark
9 Guest order tracking
10 Seed แคมเปญ 69/70 + polish NFR
── Phase 2 ── Pickup assist (นอกแผน v1)
```



## Risks and mitigations


| Risk                            | Impact | Mitigation                                                                                       |
| ------------------------------- | ------ | ------------------------------------------------------------------------------------------------ |
| ผู้ให้บริการตรวจสลิปยังไม่เลือก | สูง    | Task 7 เริ่มด้วย fake/stub verifier ที่ผ่าน/ไม่ผ่านตามฟิกซ์เจอร์; สลับ adapter จริงทีหลัง        |
| กฎคอมโบซับซ้อน (ไซส์หลายชิ้น)   | กลาง   | โมเดล BundleComposition + validation แยกตั้งแต่ Task 2; ทดสอบด้วย seed ปี 70                     |
| โหลดช่วงเปิดจอง                 | กลาง   | Index ตามสถานะ/รอบ; เลื่อน cache หน้าร้านหลัง flow หลักนิ่ง                                      |
| Redis ไม่พร้อมบนบาง host        | กลาง   | Dev ใช้ Redis local/Docker; fallback `database`/`file` driver ใน `.env.example` ถ้าจำเป็นช่วงแรก |




## Open questions (เหลือ)

1. ผู้ให้บริการ checksum สลิป
2. PromptPay: QR คงที่ vs dynamic
3. หลายรอบเปิดพร้อมกันได้หรือไม่ (แผนรองรับหลายรอบ; UI โชว์รวมสินค้าจากทุกรอบที่ open)
4. แจ้งเตือน SMS/LINE มีใน v1 หรือไม่ (แผนนี้ **ยังไม่ทำ** จนกว่าจะยืนยัน)

---



## Task List



### Phase A — Foundation



#### Task 0: Bootstrap โปรเจกต์ตาม stack ที่ล็อก

**Description:** สร้างแอปเปล่า, lint/test/dev scripts, โครงโฟลเดอร์ตาม ADR-002 (`app/Services/`*, `app/Contracts/SlipVerifier`), ธีมพื้นฐานตาม `docs/references/mockup/brand-spec.md`, env ตัวอย่างโดยไม่มี secret จริง

**Acceptance criteria:**

- [ ] `dev` / `test` / `lint` (หรือเทียบเท่า) รันได้
- [ ] หน้าเปล่าโชว์ฟอนต์/สี brand ได้
- [ ] มีโครง `app/Services/{Catalog,Booking,Shipping,Cart,Checkout,Order,Payment}` และ `app/Contracts/SlipVerifier` (stub bind ได้)
- [ ] README สั้นๆ บอกคำสั่งรัน

**Verification:** รัน dev + test ผ่าน; เปิดหน้าแรกได้  
**Dependencies:** None (stack ล็อกแล้ว)  
**Scope:** M

**Stack เป้าหมาย Task 0:** Laravel + Livewire; DB ผ่าน env (`sqlite`/`mysql`/`pgsql`); Redis สำหรับ cache/queue/session ตาม config

#### Task 1: Admin authentication

**Description:** ล็อกอิน/ล็อกเอาท์แอดมิน, middleware กันหลังบ้าน, บัญชี seed สำหรับ dev

**Acceptance criteria:**

- [ ] ไม่ล็อกอินแล้วเข้า `/admin/*` ไม่ได้
- [ ] ล็อกอินแล้วเข้าได้และล็อกเอาท์ได้

**Verification:** feature test auth; ลองมือ  
**Dependencies:** Task 0  
**Scope:** S

### Checkpoint A

- [x] โปรเจกต์รันได้ + แอดมินล็อกอินได้
- [x] Human ยืนยัน stack + โครง services (`app/Services`, `SlipVerifier`) ก่อนโดเมนสินค้า

---



### Phase B — Catalog, shipping, rounds



#### Task 2: Product admin — simple + bundle

**Description:** CRUD สินค้าผ่าน `CatalogService`, ราคา, เปิด/ปิดขาย, ตัวเลือก (ไซส์ ฯลฯ), ประเภท simple vs bundle พร้อมส่วนประกอบบังคับของคอมโบ — Livewire เรียก service ไม่เขียนโมเดลตรง

**Acceptance criteria:**

- [ ] สร้าง/แก้/ปิดขายสินค้าได้จากแอดมิน
- [ ] Bundle บังคับมี composition; simple ไม่บังคับ
- [ ] บันทึกตัวเลือกไซส์/เพศเสื้อ/กางเกง|กระโปรง/ไซส์เข็มขัดได้ตามที่โมเดลรองรับ

**Verification:** tests โมเดล+policy validation; ลองสร้างสินค้าเทียบปี 69/70  
**Dependencies:** Task 1  
**Scope:** M (ถ้าใหญ่เกินให้แยก 2a simple / 2b bundle)

#### Task 3: Shipping rates admin

**Description:** CRUD เรทค่าส่งคงที่ผ่าน `ShippingRateService` (อย่างน้อยรองรับ 1–2 เรท) สำหรับใช้ตอน checkout

**Acceptance criteria:**

- [ ] แอดมินสร้าง/แก้เรทได้
- [ ] มีเรท active ให้ storefront ดึงได้

**Verification:** test CRUD + อ่านเรท active  
**Dependencies:** Task 1  
**Scope:** S

#### Task 4: Booking rounds ผูกสินค้า

**Description:** CRUD รอบผ่าน `BookingRoundService` (ชื่อ, เริ่ม, สิ้นสุด, สถานะ) และผูกสินค้า; query “รอบที่เปิดตอนนี้” + สินค้าในรอบ

**Acceptance criteria:**

- [x] สร้างรอบและผูกสินค้าได้
- [x] นอกช่วงเวลา สถานะไม่ถือว่า open สำหรับหน้าร้าน
- [x] สินค้าไม่อยู่ในรอบเปิดต้องไม่ถูกเลือกขายบน storefront API/query

**Verification:** unit/feature เรื่องหน้าต่างเวลา + ความสัมพันธ์  
**Dependencies:** Task 2  
**Scope:** M

### Checkpoint B

- [ ] แอดมินตั้งสินค้า + เรทส่ง + รอบจองครบ
- [ ] Review โมเดลกับ SRS §6

---



### Phase C — Storefront checkout & pay



#### Task 5: Storefront catalog (รอบเปิดเท่านั้น)

**Description:** หน้า home/รายการ/รายละเอียดสินค้า mobile-first ตาม mockup; ดึงสินค้าจาก `BookingRoundService` / `CatalogService` เฉพาะรอบเปิด; เลือกตัวเลือกสำหรับ simple และ wizard/ฟอร์มครบชุดสำหรับ bundle (`CartService` ตอนเพิ่มตะกร้า)

**Acceptance criteria:**

- [x] ไม่มีรอบเปิด → สื่อสารปิดจอง ใส่ตะกร้าไม่ได้
- [x] มีรอบเปิด → เห็นเฉพาะสินค้าในรอบ
- [x] Bundle ต้องเลือกครบส่วนประกอบก่อนเพิ่มตะกร้า

**Verification:** feature/browser check ตาม mockup home + product  
**Dependencies:** Task 4  
**Scope:** M

#### Task 6: Cart + checkout ข้อมูลผู้จอง + ช่องทางรับของ

**Description:** ตะกร้าผ่าน `CartService`, ข้อมูลผู้จอง/จุดรับ/ยอดผ่าน `CheckoutService` (รหัสนักศึกษา/ชื่อ/คณะ/เบอร์, จุดรับ 2 ที่หรือไปรษณีย์ + ที่อยู่เมื่อจัดส่ง, ยอดสินค้า+ค่าส่งจากเรท)

**Acceptance criteria:**

- [x] validation ฟิลด์บังคับครบ
- [x] เลือกไปรษณีย์แล้วมีค่าส่งในยอด; รับเองแล้วค่าส่ง = 0
- [x] นอกรอบเปิดสร้างออเดอร์ไม่ได้

**Verification:** feature tests ยอดรวม + validation  
**Dependencies:** Task 3, Task 5  
**Scope:** M

#### Task 7: PromptPay + อัปโหลดสลิป + checksum gate

**Description:** หน้าชำระแสดง QR/ยอด; อัปโหลดสลิป; `OrderService` เรียก `SlipVerificationService` → `SlipVerifier`; **ไม่ผ่านแล้วไม่สร้างออเดอร์สำเร็จ**; ผ่านแล้วสร้างออเดอร์สถานะ `pending_review` พร้อมเก็บผล checksum และไฟล์ (transaction ใน service)

**Acceptance criteria:**

- [x] verifier fail → บล็อก จบออเดอร์ไม่ได้
- [x] verifier pass → ออเดอร์ `pending_review` + มีสลิป
- [x] สลิปซ้ำ (ตาม stub/adapter) ถูกปฏิเสธ
- [x] ไม่มีการล็อกสต็อกตอนสร้างออเดอร์

**Verification:** tests ด้วย stub pass/fail/duplicate; manual อัปโหลด  
**Dependencies:** Task 6  
**Scope:** M

### Checkpoint C

- [x] Guest จองจากมือถือจนเข้าคิวตรวจสลิปได้แบบ end-to-end (stub verifier)
- [x] Human ลอง happy path + สลิปไม่ผ่าน

---



### Phase D — Admin orders + guest track



#### Task 8: Admin คิวออเดอร์ + สถานะ + ใบเสร็จตอนรับของ

**Description:** คิว/กรอง/เปลี่ยนสถานะผ่าน `OrderService` ตาม SRS (ยืนยัน/ปฏิเสธ/พร้อมรับ/จัดส่ง/เสร็จ/ยกเลิก), ดูสลิปและผล checksum, ทำเครื่องหมายออกใบเสร็จตอนรับของ, audit ว่าใครเปลี่ยนสถานะเมื่อไหร่ (P1)

**Acceptance criteria:**

- [x] คิว `pending_review` ใช้งานได้
- [x] เปลี่ยนสถานะตามที่อนุญาตได้
- [x] ทำเครื่องหมายใบเสร็จ/รับของได้
- [x] ค้นด้วยรหัสออเดอร์หรือรหัสนักศึกษาได้

**Verification:** feature tests transition; ลองมือ  
**Dependencies:** Task 7  
**Scope:** M

#### Task 9: Guest order tracking

**Description:** หน้าสถานะออเดอร์ด้วยรหัส + token ในลิงก์หลังสั่งสำเร็จ ผ่าน `OrderService` (อ้างอิง mockup order-status)

**Acceptance criteria:**

- [x] เปิดลิงก์ถูกต้องแล้วเห็นสถานะ
- [x] token ผิด/ไม่มีแล้วไม่โชว์ข้อมูลออเดอร์

**Verification:** feature test authorization ลิงก์  
**Dependencies:** Task 7  
**Scope:** S

#### Task 10: Seed แคมเปญ + เก็บรายละเอียด NFR

**Description:** seed สินค้าปี 69/70 ตัวอย่าง, รอบตัวอย่าง, เรทส่ง, แอดมิน; เก็บ UI/touch target; ปิดช่องโหว่ลิงก์ติดตาม; README วิธีเดโม

**Acceptance criteria:**

- [x] `db:seed` แล้วเดโมจองได้ทันที
- [x] ตรง success criteria SRS §8 ข้อหลักด้วยมือหรือเทสต์

**Verification:** checklist SRS §8  
**Dependencies:** Task 8, Task 9  
**Scope:** M

### Checkpoint D — v1 complete

- [x] SRS §8 success criteria ผ่าน
- [x] Phase 2 pickup assist ยังไม่เริ่ม (นอกแผน)
- [x] Human sign-off รับมอบ v1

---



## Phase 2 (นอกแผน implement ตอนนี้)

- FR-PCK-01 Pickup assist — แยก plan เมื่อเริ่ม



## Parallelization


| ช่วง        | ขนานได้                                      |
| ----------- | -------------------------------------------- |
| หลัง Task 1 | Task 3 ขนานกับต้น Task 2 ได้                 |
| หลัง Task 7 | Task 8 กับ Task 9 ขนานได้ถ้าสัญญาออเดอร์นิ่ง |




## ลำดับแนะนำ (sequential default)

`0 → 1 → 2 → 3 → 4 → 5 → 6 → 7 → 8 → 9 → 10`
