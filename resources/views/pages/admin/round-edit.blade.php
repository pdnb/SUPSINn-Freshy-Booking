<?php

use App\Models\BookingRound;
use App\Models\Product;
use App\Services\Booking\BookingRoundService;
use App\Services\Catalog\CatalogService;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;

new #[Layout('layouts.admin')]
#[Title('รอบจอง')]
class extends Component
{
    use WithPagination, WithoutUrlPagination;

    private const PRODUCT_PAGE_SIZE = 9;

    public ?int $roundId = null;

    public string $name = '';

    public string $starts_at = '';

    public string $ends_at = '';

    public bool $is_enabled = true;

    /**
     * @var list<int>
     */
    public array $product_ids = [];

    public string $productSearch = '';

    public function mount(?BookingRound $round = null): void
    {
        if ($round === null || ! $round->exists) {
            return;
        }

        $round->load('products');
        $this->roundId = $round->id;
        $this->name = $round->name;
        $this->starts_at = $round->starts_at?->format('Y-m-d\TH:i') ?? '';
        $this->ends_at = $round->ends_at?->format('Y-m-d\TH:i') ?? '';
        $this->is_enabled = $round->is_enabled;
        $this->product_ids = $round->products->pluck('id')->all();
    }

    public function updatedProductSearch(): void
    {
        $this->resetPage();
    }

    public function toggleProduct(int $id): void
    {
        $ids = $this->selectedProductIds();

        if (in_array($id, $ids, true)) {
            $this->product_ids = array_values(array_filter(
                $ids,
                fn (int $productId): bool => $productId !== $id,
            ));

            return;
        }

        if (! Product::query()->whereKey($id)->exists()) {
            return;
        }

        $this->product_ids = [...$ids, $id];
    }

    public function selectAllProducts(CatalogService $catalog): void
    {
        $search = trim($this->productSearch);
        $matchingIds = $catalog->adminList([
            'search' => $search !== '' ? $search : null,
        ])->pluck('id')->map(intval(...))->all();

        $this->product_ids = array_values(array_unique([
            ...$this->selectedProductIds(),
            ...$matchingIds,
        ]));
    }

    public function save(BookingRoundService $rounds): void
    {
        try {
            $payload = [
                'name' => $this->name,
                'starts_at' => $this->starts_at,
                'ends_at' => $this->ends_at,
                'is_enabled' => $this->is_enabled,
                'product_ids' => $this->selectedProductIds(),
            ];

            if ($this->roundId) {
                $rounds->update(BookingRound::query()->findOrFail($this->roundId), $payload);
            } else {
                $rounds->create($payload);
            }

            session()->flash('status', 'บันทึกรอบจองแล้ว');
            $this->redirect(route('admin.rounds'));
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->errors());
        }
    }

    public function render(CatalogService $catalog)
    {
        $search = trim($this->productSearch);

        return $this->view([
            'products' => $catalog->adminPaginate(
                ['search' => $search !== '' ? $search : null],
                self::PRODUCT_PAGE_SIZE,
            ),
            'selectedIds' => $this->selectedProductIds(),
        ]);
    }

    /**
     * @return list<int>
     */
    private function selectedProductIds(): array
    {
        return array_values(array_map('intval', $this->product_ids));
    }
};
?>

<div>
    <div class="page-head">
        <div>
            <p class="meta"><a href="{{ route('admin.rounds') }}">รอบจอง</a> / {{ $roundId ? 'แก้ไข' : 'สร้าง' }}</p>
            <h1 style="margin-top: 6px;">{{ $name !== '' ? $name : ($roundId ? 'แก้ไขรอบ' : 'สร้างรอบ') }}</h1>
            <p class="sub">เปิด-ปิดรอบและผูกสินค้า — ไม่มีกราฟรายได้</p>
        </div>
        <div class="row">
            <a class="btn btn-ghost" href="{{ route('admin.rounds') }}">ยกเลิก</a>
            <button type="button" class="btn btn-primary" wire:click="save" wire:loading.attr="disabled">บันทึก</button>
        </div>
    </div>

    <div class="grid-2-1">
        <div class="stack">
            <section class="panel">
                <div class="panel-head">
                    <h2>สินค้าในรอบ</h2>
                    <span class="pill {{ $selectedIds !== [] ? 'pill-active' : 'pill-neutral' }}">
                        {{ count($selectedIds) }} รายการ
                    </span>
                </div>
                <div class="panel-body stack">
                    <x-admin.product-picker
                        :products="$products"
                        :selected-ids="$selectedIds"
                        :search="$productSearch"
                        toggle-method="toggleProduct"
                        select-all-method="selectAllProducts"
                    />
                    @error('product_ids') <span class="error">{{ $message }}</span> @enderror
                </div>
            </section>
        </div>

        <form class="panel" wire:submit="save">
            <div class="panel-head">
                <h2>รายละเอียดรอบ</h2>
                <span class="pill {{ $is_enabled ? 'pill-active' : 'pill-draft' }}">
                    {{ $is_enabled ? 'เปิดใช้' : 'ปิดใช้' }}
                </span>
            </div>
            <div class="panel-body stack">
                <div class="field">
                    <label for="round-name">ชื่อรอบ</label>
                    <input
                        id="round-name"
                        class="input @error('name') is-invalid @enderror"
                        type="text"
                        wire:model="name"
                    >
                    @error('name') <span class="error">{{ $message }}</span> @enderror
                </div>
                <div class="field">
                    <label for="round-starts-at">เริ่ม</label>
                    <input
                        id="round-starts-at"
                        class="input @error('starts_at') is-invalid @enderror"
                        type="datetime-local"
                        wire:model="starts_at"
                    >
                    @error('starts_at') <span class="error">{{ $message }}</span> @enderror
                </div>
                <div class="field">
                    <label for="round-ends-at">สิ้นสุด</label>
                    <input
                        id="round-ends-at"
                        class="input @error('ends_at') is-invalid @enderror"
                        type="datetime-local"
                        wire:model="ends_at"
                    >
                    @error('ends_at') <span class="error">{{ $message }}</span> @enderror
                </div>
                <x-admin.switch wire:model.live="is_enabled">เปิดใช้รอบนี้</x-admin.switch>
            </div>
        </form>
    </div>
</div>
