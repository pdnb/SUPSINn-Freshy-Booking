<?php

namespace App\Services\Inventory;

use App\Enums\InventoryAdjustmentReason;
use App\Models\InventoryItem;
use App\Models\Product;
use App\Models\User;
use App\Services\Catalog\CatalogService;
use App\Services\Production\ProductionSummaryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function __construct(
        private CatalogService $catalog,
        private ProductionSummaryService $production,
    ) {}

    /**
     * @param  array{search?: string|null, stock?: string|null, booking_round_id?: int|string|null}  $filters
     * @return list<array{key: string, product_id: int, product_name: string, choice_label: string, choice_value: string, on_hand: int, confirmed_qty: int, remaining: int, threshold: int, is_low: bool}>
     */
    public function list(array $filters = []): array
    {
        $confirmed = [];

        foreach ($this->production->summarize([
            'booking_round_id' => $filters['booking_round_id'] ?? null,
        ]) as $row) {
            if ($row['product_id'] === null) {
                continue;
            }

            $key = $this->rowKey($row['product_id'], $row['choice_label'], $row['choice_value']);
            $confirmed[$key] = ($confirmed[$key] ?? 0) + $row['qty'];
        }

        $items = InventoryItem::query()
            ->with('product')
            ->get()
            ->keyBy(fn (InventoryItem $item): string => $this->rowKey(
                $item->product_id,
                $item->choice_label,
                $item->choice_value,
            ));

        $rows = [];

        foreach ($this->catalog->list() as $product) {
            foreach ($this->choiceRowsFor($product) as $choice) {
                $key = $this->rowKey($product->id, $choice['label'], $choice['value']);
                $item = $items->get($key);
                $rows[$key] = $this->present(
                    $product->id,
                    $product->name,
                    $choice['label'],
                    $choice['value'],
                    (int) ($item?->on_hand ?? 0),
                    (int) ($confirmed[$key] ?? 0),
                    (int) ($item?->threshold ?? 0),
                );
            }
        }

        foreach ($items as $key => $item) {
            if (isset($rows[$key]) || $item->product === null) {
                continue;
            }

            $rows[$key] = $this->present(
                $item->product_id,
                $item->product->name,
                $item->choice_label,
                $item->choice_value,
                $item->on_hand,
                (int) ($confirmed[$key] ?? 0),
                $item->threshold,
            );
        }

        foreach ($confirmed as $key => $qty) {
            if (isset($rows[$key])) {
                continue;
            }

            [$productId, $label, $value] = explode("\u{1f}", $key, 3);
            $product = Product::query()->find($productId);

            if ($product === null) {
                continue;
            }

            $rows[$key] = $this->present(
                (int) $productId,
                $product->name,
                $label,
                $value,
                0,
                $qty,
                0,
            );
        }

        $search = trim((string) ($filters['search'] ?? ''));
        $stock = $filters['stock'] ?? '';

        $list = array_values($rows);

        if ($search !== '') {
            $list = array_values(array_filter($list, function (array $row) use ($search): bool {
                return str_contains(mb_strtolower($row['product_name']), mb_strtolower($search))
                    || str_contains(mb_strtolower($row['choice_value']), mb_strtolower($search))
                    || str_contains(mb_strtolower($row['choice_label']), mb_strtolower($search));
            }));
        }

        if ($stock === 'low') {
            $list = array_values(array_filter($list, fn (array $row): bool => $row['is_low']));
        }

        if ($stock === 'ok') {
            $list = array_values(array_filter($list, fn (array $row): bool => ! $row['is_low']));
        }

        usort($list, fn (array $left, array $right): int => [$left['product_name'], $left['choice_label'], $left['choice_value']]
            <=> [$right['product_name'], $right['choice_label'], $right['choice_value']]);

        return $list;
    }

    public function adjust(
        Product $product,
        string $choiceLabel,
        string $choiceValue,
        int $delta,
        InventoryAdjustmentReason $reason,
        User $actor,
    ): InventoryItem {
        if ($delta === 0) {
            throw ValidationException::withMessages([
                'delta' => 'ระบุจำนวนที่ต้องการปรับ',
            ]);
        }

        return DB::transaction(function () use ($product, $choiceLabel, $choiceValue, $delta, $reason, $actor) {
            $item = InventoryItem::query()->firstOrCreate(
                [
                    'product_id' => $product->id,
                    'choice_label' => $choiceLabel,
                    'choice_value' => $choiceValue,
                ],
                ['on_hand' => 0, 'threshold' => 0],
            );

            $next = $item->on_hand + $delta;

            if ($next < 0) {
                throw ValidationException::withMessages([
                    'delta' => 'ยอดของที่มีต้องไม่ติดลบ',
                ]);
            }

            $item->update(['on_hand' => $next]);
            $item->adjustments()->create([
                'delta' => $delta,
                'reason' => $reason,
                'user_id' => $actor->id,
            ]);

            return $item->fresh();
        });
    }

    public function setThreshold(Product $product, string $choiceLabel, string $choiceValue, int $threshold): InventoryItem
    {
        if ($threshold < 0) {
            throw ValidationException::withMessages([
                'threshold' => 'เกณฑ์เตือนต้องไม่ติดลบ',
            ]);
        }

        $item = InventoryItem::query()->firstOrCreate(
            [
                'product_id' => $product->id,
                'choice_label' => $choiceLabel,
                'choice_value' => $choiceValue,
            ],
            ['on_hand' => 0, 'threshold' => 0],
        );

        $item->update(['threshold' => $threshold]);

        return $item->fresh();
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    private function choiceRowsFor(Product $product): array
    {
        $rows = [];

        foreach ($product->optionGroups as $group) {
            foreach ($group->values as $value) {
                $rows[] = ['label' => $group->label, 'value' => $value->value];
            }
        }

        foreach ($product->components as $component) {
            foreach ($component->optionGroups as $group) {
                foreach ($group->values as $value) {
                    $rows[] = ['label' => $group->label, 'value' => $value->value];
                }
            }
        }

        if ($rows === []) {
            return [['label' => '', 'value' => '']];
        }

        return $rows;
    }

    /**
     * @return array{key: string, product_id: int, product_name: string, choice_label: string, choice_value: string, on_hand: int, confirmed_qty: int, remaining: int, threshold: int, is_low: bool}
     */
    private function present(
        int $productId,
        string $productName,
        string $choiceLabel,
        string $choiceValue,
        int $onHand,
        int $confirmedQty,
        int $threshold,
    ): array {
        $remaining = $onHand - $confirmedQty;
        $isLow = $onHand < $confirmedQty || ($threshold > 0 && $remaining <= $threshold);

        return [
            'key' => $this->rowKey($productId, $choiceLabel, $choiceValue),
            'product_id' => $productId,
            'product_name' => $productName,
            'choice_label' => $choiceLabel,
            'choice_value' => $choiceValue,
            'on_hand' => $onHand,
            'confirmed_qty' => $confirmedQty,
            'remaining' => $remaining,
            'threshold' => $threshold,
            'is_low' => $isLow,
        ];
    }

    private function rowKey(int $productId, string $choiceLabel, string $choiceValue): string
    {
        return implode("\u{1f}", [(string) $productId, $choiceLabel, $choiceValue]);
    }
}
