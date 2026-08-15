<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PendingReview = 'pending_review';
    case Confirmed = 'confirmed';
    case NeedReslip = 'need_reslip';
    case ReadyForPickup = 'ready_for_pickup';
    case Shipped = 'shipped';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PendingReview => 'รอเจ้าหน้าที่ตรวจสลิป',
            self::Confirmed => 'ยืนยันการชำระแล้ว',
            self::NeedReslip => 'ต้องแนบสลิปใหม่',
            self::ReadyForPickup => 'พร้อมรับของ',
            self::Shipped => 'จัดส่งแล้ว',
            self::Completed => 'รับของแล้ว',
            self::Cancelled => 'ยกเลิก',
        };
    }

    public function pillClass(): string
    {
        return match ($this) {
            self::PendingReview => 'pill-pending',
            self::NeedReslip => 'pill-danger',
            self::Confirmed, self::ReadyForPickup => 'pill-paid',
            self::Shipped, self::Completed => 'pill-info',
            self::Cancelled => 'pill-neutral',
        };
    }
}
