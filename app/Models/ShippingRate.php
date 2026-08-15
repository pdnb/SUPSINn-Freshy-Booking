<?php

namespace App\Models;

use Database\Factories\ShippingRateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'amount', 'tiers', 'is_active', 'sort_order'])]
class ShippingRate extends Model
{
    /** @use HasFactory<ShippingRateFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'tiers' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function amountSummary(): string
    {
        $amounts = collect($this->tiers ?? [])->pluck('amount');

        if ($amounts->isEmpty()) {
            $amounts = collect([$this->amount]);
        }

        return $amounts
            ->map(fn (mixed $amount): string => number_format((float) $amount, 2))
            ->implode(' · ');
    }
}
