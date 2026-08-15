<?php

namespace App\Models;

use Database\Factories\InventoryItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['product_id', 'choice_label', 'choice_value', 'on_hand', 'threshold'])]
class InventoryItem extends Model
{
    /** @use HasFactory<InventoryItemFactory> */
    use HasFactory;

    protected $attributes = [
        'on_hand' => 0,
        'threshold' => 0,
        'choice_label' => '',
        'choice_value' => '',
    ];

    protected function casts(): array
    {
        return [
            'on_hand' => 'integer',
            'threshold' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return HasMany<InventoryAdjustment, $this>
     */
    public function adjustments(): HasMany
    {
        return $this->hasMany(InventoryAdjustment::class)->latest();
    }
}
