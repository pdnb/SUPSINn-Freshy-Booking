<?php

namespace App\Models;

use App\Enums\FulfillmentMethod;
use App\Enums\OrderStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'number',
    'tracking_token',
    'student_id',
    'full_name',
    'faculty',
    'phone',
    'fulfillment',
    'address',
    'shipping_rate_id',
    'shipping_rate_name',
    'subtotal',
    'shipping_amount',
    'total',
    'status',
    'booking_round_id',
    'receipt_issued_at',
])]
#[Hidden(['tracking_token'])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    public function getRouteKeyName(): string
    {
        return 'number';
    }

    protected function casts(): array
    {
        return [
            'fulfillment' => FulfillmentMethod::class,
            'status' => OrderStatus::class,
            'subtotal' => 'decimal:2',
            'shipping_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'receipt_issued_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return HasOne<PaymentSlip, $this>
     */
    public function slip(): HasOne
    {
        return $this->hasOne(PaymentSlip::class);
    }

    /**
     * @return BelongsTo<BookingRound, $this>
     */
    public function bookingRound(): BelongsTo
    {
        return $this->belongsTo(BookingRound::class);
    }

    /**
     * @return HasMany<OrderStatusChange, $this>
     */
    public function statusChanges(): HasMany
    {
        return $this->hasMany(OrderStatusChange::class)->latest();
    }
}
