<?php

namespace App\Enums;

enum InventoryAdjustmentReason: string
{
    case FactoryReceipt = 'factory_receipt';
    case CycleCount = 'cycle_count';
    case Damaged = 'damaged';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::FactoryReceipt => 'รับจากโรงงาน',
            self::CycleCount => 'ตรวจนับแก้',
            self::Damaged => 'ของเสีย',
            self::Other => 'อื่นๆ',
        };
    }
}
