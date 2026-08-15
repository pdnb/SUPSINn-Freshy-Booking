<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['product_option_group_id', 'value', 'label', 'sort_order'])]
class ProductOptionValue extends Model
{
    /**
     * @return BelongsTo<ProductOptionGroup, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(ProductOptionGroup::class, 'product_option_group_id');
    }
}
