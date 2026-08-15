<?php

namespace App\Models;

use Database\Factories\BookingRoundFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

#[Fillable(['name', 'starts_at', 'ends_at', 'is_enabled'])]
class BookingRound extends Model
{
    /** @use HasFactory<BookingRoundFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_enabled' => 'boolean',
        ];
    }

    /**
     * @return BelongsToMany<Product, $this>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)->withTimestamps();
    }

    public function isOpenAt(?Carbon $at = null): bool
    {
        $at ??= now();

        return $this->is_enabled
            && $at->greaterThanOrEqualTo($this->starts_at)
            && $at->lessThanOrEqualTo($this->ends_at);
    }

    public function effectiveStatus(?Carbon $at = null): string
    {
        $at ??= now();

        if (! $this->is_enabled) {
            return 'draft';
        }

        if ($at->lt($this->starts_at)) {
            return 'scheduled';
        }

        if ($at->gt($this->ends_at)) {
            return 'closed';
        }

        return 'open';
    }
}
