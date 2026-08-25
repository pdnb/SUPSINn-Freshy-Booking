<?php

namespace App\Services\Cart;

use App\Enums\ProductType;
use App\Models\Product;
use App\Models\ProductComponent;
use App\Models\ProductOptionGroup;
use App\Services\Booking\BookingRoundService;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CartService
{
    public function __construct(
        private BookingRoundService $booking,
        private Session $session,
    ) {}

    /**
     * @param  array<string, mixed>  $selections
     * @return array<string, mixed>
     */
    public function add(Product $product, array $selections = [], int $qty = 1): array
    {
        $product->loadMissing(['optionGroups.values', 'components.optionGroups.values']);

        if ($qty < 1) {
            throw ValidationException::withMessages([
                'qty' => 'จำนวนต้องอย่างน้อย 1',
            ]);
        }

        if (! $product->is_active || ! $this->booking->isProductAvailable($product)) {
            throw ValidationException::withMessages([
                'product' => 'สินค้านี้ไม่เปิดจองในขณะนี้',
            ]);
        }

        $choices = $product->type === ProductType::Bundle
            ? $this->validatedBundleChoices($product, $selections)
            : $this->validatedSimpleChoices($product, $selections);

        $item = [
            'id' => (string) Str::uuid(),
            'product_id' => $product->id,
            'name' => $product->name,
            'price' => $product->price,
            'qty' => $qty,
            'choices' => $choices,
        ];

        $items = $this->rawItems();
        $items[] = $item;
        $this->session->put('cart.items', $items);

        return $item;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function items(): Collection
    {
        return collect($this->rawItems())->values();
    }

    public function count(): int
    {
        return (int) $this->items()->sum('qty');
    }

    public function subtotal(): string
    {
        return $this->items()->reduce(
            fn (string $carry, array $item): string => $this->addMoney(
                $carry,
                $this->multiplyMoney((string) $item['price'], (int) $item['qty'])
            ),
            '0.00'
        );
    }

    public function updateQty(string $itemId, int $qty): void
    {
        if ($qty < 1) {
            throw ValidationException::withMessages([
                'qty' => 'จำนวนต้องอย่างน้อย 1',
            ]);
        }

        $this->assertAvailable();

        $items = $this->rawItems();
        $found = false;

        foreach ($items as $index => $item) {
            if ($item['id'] === $itemId) {
                $items[$index]['qty'] = $qty;
                $found = true;
                break;
            }
        }

        if (! $found) {
            throw ValidationException::withMessages([
                'cart' => 'ไม่พบรายการในตะกร้า',
            ]);
        }

        $this->session->put('cart.items', $items);
    }

    public function remove(string $itemId): void
    {
        $this->session->put(
            'cart.items',
            array_values(array_filter(
                $this->rawItems(),
                fn (array $item): bool => $item['id'] !== $itemId
            ))
        );
    }

    public function clear(): void
    {
        $this->session->forget('cart.items');
    }

    public function assertAvailable(): void
    {
        if ($this->items()->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => 'ตะกร้าว่าง',
            ]);
        }

        foreach ($this->items() as $item) {
            $product = Product::query()->find($item['product_id']);

            if (! $product || ! $product->is_active || ! $this->booking->isProductAvailable($product)) {
                throw ValidationException::withMessages([
                    'cart' => 'ไม่สามารถสร้างออเดอร์ได้ เพราะไม่อยู่ในช่วงเปิดจอง',
                ]);
            }
        }
    }

    private function addMoney(string $left, string $right): string
    {
        return number_format((float) $left + (float) $right, 2, '.', '');
    }

    private function multiplyMoney(string $price, int $qty): string
    {
        return number_format((float) $price * $qty, 2, '.', '');
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function rawItems(): array
    {
        /** @var list<array<string, mixed>> $items */
        $items = $this->session->get('cart.items', []);

        return $items;
    }

    /**
     * @param  array<string, mixed>  $selections
     * @return list<array{label: string, value: string}>
     */
    private function validatedSimpleChoices(Product $product, array $selections): array
    {
        /** @var array<string, mixed> $options */
        $options = $selections['options'] ?? [];

        return $this->choicesForGroups($product->optionGroups, $options, 'options');
    }

    /**
     * @param  array<string, mixed>  $selections
     * @return list<array{label: string, value: string}>
     */
    private function validatedBundleChoices(Product $product, array $selections): array
    {
        /** @var array<int|string, mixed> $components */
        $components = $selections['components'] ?? [];

        if ($product->components->isEmpty()) {
            throw ValidationException::withMessages([
                'components' => 'กรุณาเลือกตัวเลือกให้ครบทุกชิ้น',
            ]);
        }

        $choices = [];

        foreach ($product->components as $component) {
            $selected = $components[$component->id] ?? $components[(string) $component->id] ?? null;

            if (! is_array($selected)) {
                throw ValidationException::withMessages([
                    'components' => 'กรุณาเลือกตัวเลือกให้ครบทุกชิ้น',
                ]);
            }

            foreach ($this->choicesForGroups($component->optionGroups, $selected, 'components', $component) as $choice) {
                $choices[] = $choice;
            }
        }

        return $choices;
    }

    /**
     * @param  Collection<int, ProductOptionGroup>  $groups
     * @param  array<string, mixed>  $selected
     * @return list<array{label: string, value: string}>
     */
    private function choicesForGroups(Collection $groups, array $selected, string $errorKey, ?ProductComponent $component = null): array
    {
        $choices = [];

        foreach ($groups as $group) {
            $value = $selected[$group->key] ?? null;

            if (! is_string($value) || $value === '' || ! $group->values->contains('value', $value)) {
                throw ValidationException::withMessages([
                    $errorKey => $component
                        ? 'กรุณาเลือกตัวเลือกให้ครบทุกชิ้น'
                        : 'กรุณาเลือกตัวเลือกให้ครบ',
                ]);
            }

            if ($group->hasParent()) {
                $parentValue = $selected[$group->depends_on_key] ?? null;

                if (! $group->allowsValueForParent($value, is_string($parentValue) ? $parentValue : null)) {
                    throw ValidationException::withMessages([
                        $errorKey => 'ตัวเลือกที่เลือกไม่เข้ากัน กรุณาเลือกใหม่',
                    ]);
                }
            }

            $choices[] = [
                'label' => $component ? $component->name.' · '.$group->label : $group->label,
                'value' => $value,
            ];
        }

        return $choices;
    }
}
