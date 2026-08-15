<?php

namespace App\Models;

use App\Enums\SlipVerificationResult;
use Database\Factories\PaymentSlipFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['order_id', 'path', 'original_name', 'checksum', 'verifier_result'])]
class PaymentSlip extends Model
{
    /** @use HasFactory<PaymentSlipFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'verifier_result' => SlipVerificationResult::class,
        ];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
