<?php

namespace App\Models;

use App\Enums\InventoryAdjustmentReason;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['inventory_item_id', 'delta', 'reason', 'user_id'])]
class InventoryAdjustment extends Model
{
    protected function casts(): array
    {
        return [
            'delta' => 'integer',
            'reason' => InventoryAdjustmentReason::class,
        ];
    }

    /**
     * @return BelongsTo<InventoryItem, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
