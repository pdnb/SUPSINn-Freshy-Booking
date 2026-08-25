<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

#[Fillable(['product_id', 'product_component_id', 'key', 'label', 'depends_on_key', 'depends_on_values', 'sort_order'])]
class ProductOptionGroup extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'depends_on_values' => 'array',
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
     * @return BelongsTo<ProductComponent, $this>
     */
    public function component(): BelongsTo
    {
        return $this->belongsTo(ProductComponent::class, 'product_component_id');
    }

    /**
     * @return HasMany<ProductOptionValue, $this>
     */
    public function values(): HasMany
    {
        return $this->hasMany(ProductOptionValue::class)->orderBy('sort_order');
    }

    public function hasParent(): bool
    {
        return is_string($this->depends_on_key) && $this->depends_on_key !== '';
    }

    /**
     * @return Collection<int, ProductOptionValue>
     */
    public function valuesAllowedFor(?string $parentValue): Collection
    {
        if (! $this->hasParent()) {
            return $this->values;
        }

        if ($parentValue === null || $parentValue === '') {
            return collect();
        }

        /** @var array<string, list<string>> $map */
        $map = is_array($this->depends_on_values) ? $this->depends_on_values : [];
        $allowed = $map[$parentValue] ?? [];

        if ($allowed === []) {
            return collect();
        }

        return $this->values->filter(
            fn (ProductOptionValue $value): bool => in_array($value->value, $allowed, true)
        )->values();
    }

    public function allowsValueForParent(string $value, ?string $parentValue): bool
    {
        return $this->valuesAllowedFor($parentValue)->contains('value', $value);
    }
}
