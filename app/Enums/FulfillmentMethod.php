<?php

namespace App\Enums;

enum FulfillmentMethod: string
{
    case Bookstore = 'bookstore';
    case Hall = 'hall';
    case Post = 'post';

    public function label(): string
    {
        return match ($this) {
            self::Bookstore => 'รับที่ศูนย์หนังสือและเอกสารตำรา',
            self::Hall => 'รับที่หอประชุมฯ วันรายงานตัว',
            self::Post => 'จัดส่งทางไปรษณีย์',
        };
    }

    public function caption(): string
    {
        return match ($this) {
            self::Bookstore => 'เปิดรับตามประกาศของสำนักจัดการทรัพย์สิน',
            self::Hall => 'จุดรับชุดในวันรายงานตัวนักศึกษาใหม่',
            self::Post => 'มีค่าจัดส่งเพิ่ม — ระบบคำนวณให้อัตโนมัติ',
        };
    }

    public function chargesShipping(): bool
    {
        return $this === self::Post;
    }
}
