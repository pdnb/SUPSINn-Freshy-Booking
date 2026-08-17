# Freshy Booking

เว็บจองชุดเฟรชชี่ มรส. — Laravel 13 + Livewire 4 (view-based pages) + Tailwind 4

เอกสาร: `docs/srs.md` · แผน: `tasks/plan.md` · ฟีเจอร์: `docs/features.md`

## รันบนเครื่อง

ต้องมี PHP 8.4+, Composer, Node, และ SQLite (ค่าเริ่มต้น)

บน Laravel Herd ถ้าไซต์ยังใช้ PHP 8.3 จะเจอ 500 — isolate เป็น 8.5:

```bash
herd isolate 8.5
```

```bash
composer setup
```

หรือทีละขั้น:

```bash
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
```

คำสั่งที่ใช้บ่อย:

| คำสั่ง | ทำอะไร |
| --- | --- |
| `composer run dev` | PHP server + queue + Vite |
| `composer test` | รันเทสต์ |
| `composer lint` | ตรวจสไตล์ด้วย Pint |
| `npm run build` | บิลด์ CSS/JS สำหรับ Herd |

บน Laravel Herd เปิด [http://supsinn-freshy-booking.test](http://supsinn-freshy-booking.test) หลัง `npm run build` หรือเปิด Vite คู่กับ `npm run dev`

หน้าร้านโชว์เฉพาะสินค้าในรอบที่เปิดอยู่ — ไม่มีรอบเปิดจะขึ้นว่ายังไม่เปิดรับจอง  
ตะกร้า `/cart` · ข้อมูลจอง `/checkout` · ชำระ `/pay` · ติดตาม `/orders`

สลิปตรวจด้วย stub: ชื่อไฟล์มี `fail` จะไม่ผ่าน, มี `dup` ถือว่าซ้ำ, อื่นๆ ผ่าน — แล้วเข้าคิว `pending_review`

## แชร์ไซต์ด้วย Cloudflare Tunnel

เปิดไซต์ Herd ให้เข้าจากอินเทอร์เน็ต (มือถือ / LINE LIFF) โดยไม่ต้องเปิดพอร์ต — ใช้ `npm run build` ก่อน อย่าใช้ `npm run dev` ผ่าน tunnel

ติดตั้งครั้งแรก:

```bash
winget install --id Cloudflare.cloudflared
```

Quick Tunnel (URL สุ่ม `https://….trycloudflare.com` เปลี่ยนทุกครั้งที่รีสตาร์ท):

```bash
cloudflared tunnel --url http://127.0.0.1:80 --http-host-header supsinn-freshy-booking.test
```

ถ้า Herd ฟังเฉพาะ HTTPS:

```bash
cloudflared tunnel --url https://127.0.0.1:443 --http-host-header supsinn-freshy-booking.test --no-tls-verify
```

`--http-host-header` จำเป็น เพราะ Herd เลือกไซต์จาก hostname `.test`

ได้ URL แล้วใส่ใน `.env` แล้วเคลียร์คอนฟิก:

```env
APP_URL=https://xxxx.trycloudflare.com
```

```bash
php artisan config:clear
php artisan view:clear
```

ถ้าใช้ LIFF ตั้ง Endpoint URL ใน LINE Developers ให้ตรง origin นี้ด้วย

Named tunnel (hostname คงที่ — ต้องมีบัญชี Cloudflare และโดเมนบน Cloudflare):

```bash
cloudflared tunnel login
cloudflared tunnel create freshy-booking
cloudflared tunnel route dns freshy-booking demo.yourdomain.com
cloudflared tunnel run freshy-booking
```

ใน `%USERPROFILE%\.cloudflared\config.yml` ต้องมี `httpHostHeader: supsinn-freshy-booking.test` แล้วตั้ง `APP_URL` เป็น hostname นั้น

Tunnel เปิดทั้งแอป รวม `/admin` — เปลี่ยนรหัสแอดมินก่อนแชร์ URL

## ฐานข้อมูล / Redis

- Local ใช้ **SQLite** (`database/database.sqlite`)
- สลับ MySQL / PostgreSQL ได้จาก `.env` — ดูคอมเมนต์ใน `.env.example`
- Redis เป็นตัวเลือก; ค่าเริ่มต้นใช้ `database` driver ให้บูตได้โดยไม่มี Redis

## โครงบริการ

กฎธุรกิจอยู่ที่ `app/Services` ตาม `docs/decisions/ADR-002-application-services.md`  
`SlipVerifier` bind เป็น stub — สลับ adapter จริงได้โดยไม่แตะ `OrderService`

## แอดมิน (local)

```bash
php artisan migrate:fresh --seed
```

หรือ `php artisan db:seed` บนฐานที่ migrate แล้ว (seeder แคมเปญทำซ้ำได้ ไม่สร้างสินค้า/รอบซ้ำ)

แล้วเปิด [http://supsinn-freshy-booking.test/admin](http://supsinn-freshy-booking.test/admin)

ค่าเริ่มต้นจาก `.env.example`: `admin@example.com` / `password` — เปลี่ยนก่อนใช้ร่วมกับคนอื่น

หลังล็อกอินมีคอนโซล Livewire (ไม่ใช้ Filament): ภาพรวม · ออเดอร์ · จัดส่ง · รับของ · สรุปยอด · สต็อก · สินค้า · รอบจอง · ตั้งค่า  
ยืนยัน/ปฏิเสธสลิปและเปลี่ยนสถานะออเดอร์ทำที่คิวออเดอร์ / หน้ารายละเอียด

## เดโมแคมเปญ 69/70

หลัง seed หน้าร้านจองได้ทันที (รอบเปิดครอบคลุมเวลาปัจจุบัน)

| สิ่งที่ได้ | รายละเอียด |
| --- | --- |
| สินค้าปี 69 | เสื้อ / กางเกง ซื้อแยก ไซส์ S–2XL |
| สินค้าปี 70 | SRU คอมโบเซ็ต 4 ส่วน (ชุดนักศึกษา, ชุดเฟรชชี่, เสื้อกิจกรรม, เครื่องหมาย) |
| ค่าส่ง | เรท seed: ทั่วประเทศ 50 บาท · พื้นที่ห่างไกล 80 บาท — หน้าร้านไม่ให้เลือกเรท ระบบใช้เรท active แรกแล้วคิดตามช่วงจำนวนในตะกร้า |
| รอบ | `รอบเปิดจองชุดเฟรชชี่` — เปิดอยู่ |

ทางลัดเดโม:

1. เปิด [http://supsinn-freshy-booking.test](http://supsinn-freshy-booking.test) เลือกเสื้อปี 69 หรือคอมโบปี 70
2. เลือกไซส์ให้ครบ → ตะกร้า → กรอกรหัสนักศึกษา / ชื่อ / คณะ / เบอร์ → เลือกรับที่ร้าน หอประชุมฯ หรือไปรษณีย์
3. หน้าชำระ: สแกน PromptPay ตามยอด แล้วแนบสลิป
4. ชื่อไฟล์สลิปมี `fail` จะถูกบล็อก, มี `dup` ถือว่าซ้ำ, อื่นๆ ผ่านแล้วได้ลิงก์ติดตาม (token ใน URL เดายาก — อย่าแชร์)
5. เปิด `/admin` ดูคิว `รอตรวจสลิป` แล้วยืนยันหรือขอสลิปใหม่

### Checklist รับมอบ v1 (SRS §8)

- [ ] แอดมินสร้าง/เห็นคอมโบปี 70 และสินค้าปี 69 พร้อมไซส์ได้จากหลังบ้าน
- [ ] มีรอบเปิดจองผูกสินค้า — นอกช่วงเปิดแล้วสั่งไม่ได้
- [ ] นักศึกษาจองบนมือถือได้: กรอกข้อมูล เลือกจุดรับหรือจัดส่ง เห็นค่าส่งตามช่วงจำนวนและยอดรวมถูก
- [ ] ไม่มีล็อกอินนักศึกษา; หลังสั่งได้รหัสและลิงก์ติดตาม
- [ ] สลิป checksum ไม่ผ่านแล้วจบออเดอร์ไม่ได้
- [ ] ออเดอร์ที่ผ่านโผล่คิวแอดมิน; เปลี่ยนสถานะได้จนรับของ/จัดส่ง
- [ ] การจองไม่ล็อกสต็อก (สั่งซ้ำได้)
- [ ] แอดมินตั้งเรทค่าส่งได้ และมีอย่างน้อยสองจุดรับของหน้าร้าน

Phase 2 pickup assist ยังไม่ทำ — ไม่บล็อกรับมอบ v1
