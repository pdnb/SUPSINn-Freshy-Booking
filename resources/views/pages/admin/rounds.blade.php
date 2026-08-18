<?php
use App\Models\BookingRound;
use App\Services\Booking\BookingRoundService;
use App\Services\Catalog\CatalogService;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.admin')]
#[Title('รอบจอง')]
class extends Component
{
    public ?int $editingId = null;

    public string $name = '';

    public string $starts_at = '';

    public string $ends_at = '';

    public bool $is_enabled = true;

    /**
     * @var list<int>
     */
    public array $product_ids = [];

    public function create(): void
    {
        $this->resetForm();
        $this->editingId = 0;
    }

    public function edit(int $id): void
    {
        $round = BookingRound::query()->with('products')->findOrFail($id);
        $this->editingId = $round->id;
        $this->name = $round->name;
        $this->starts_at = $round->starts_at?->format('Y-m-d\TH:i') ?? '';
        $this->ends_at = $round->ends_at?->format('Y-m-d\TH:i') ?? '';
        $this->is_enabled = $round->is_enabled;
        $this->product_ids = $round->products->pluck('id')->all();
    }

    public function save(BookingRoundService $rounds): void
    {
        try {
            $payload = [
                'name' => $this->name,
                'starts_at' => $this->starts_at,
                'ends_at' => $this->ends_at,
                'is_enabled' => $this->is_enabled,
                'product_ids' => $this->product_ids,
            ];

            if ($this->editingId) {
                $rounds->update(BookingRound::query()->findOrFail($this->editingId), $payload);
            } else {
                $rounds->create($payload);
            }

            $this->dispatch('admin-toast', message: 'บันทึกรอบจองแล้ว');
            $this->resetForm();
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->errors());
        }
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    public function render(BookingRoundService $rounds, CatalogService $catalog)
    {
        return $this->view([
            'rounds' => $rounds->list(),
            'products' => $catalog->list(),
        ]);
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->starts_at = '';
        $this->ends_at = '';
        $this->is_enabled = true;
        $this->product_ids = [];
        $this->resetErrorBag();
    }
};
?>

<div>
    <div class="page-head">
        <div>
            <h1>รอบจอง</h1>
            <p class="sub">เปิด-ปิดรอบและผูกสินค้า — ไม่มีกราฟรายได้</p>
        </div>
        <button type="button" class="btn btn-primary" wire:click="create">สร้างรอบ</button>
    </div>

    @if ($editingId !== null)
        <form class="panel" style="margin-bottom:16px" wire:submit="save">
            <div class="panel-head">{{ $editingId ? 'แก้ไขรอบ' : 'สร้างรอบ' }}</div>
            <div class="panel-body stack">
                <label class="field">
                    ชื่อรอบ
                    <input class="input" type="text" wire:model="name">
                    @error('name') <span class="error">{{ $message }}</span> @enderror
                </label>
                <div class="field-row">
                    <label class="field">
                        เริ่ม
                        <input class="input" type="datetime-local" wire:model="starts_at">
                        @error('starts_at') <span class="error">{{ $message }}</span> @enderror
                    </label>
                    <label class="field">
                        สิ้นสุด
                        <input class="input" type="datetime-local" wire:model="ends_at">
                        @error('ends_at') <span class="error">{{ $message }}</span> @enderror
                    </label>
                </div>
                <x-admin.switch wire:model="is_enabled">เปิดใช้รอบนี้</x-admin.switch>
                <fieldset class="field">
                    <legend>สินค้าในรอบ</legend>
                    @foreach ($products as $product)
                        <label wire:key="round-product-{{ $product->id }}">
                            <input type="checkbox" class="check" value="{{ $product->id }}" wire:model="product_ids">
                            {{ $product->name }}
                        </label>
                    @endforeach
                </fieldset>
                <div class="row">
                    <button type="submit" class="btn btn-primary">บันทึก</button>
                    <button type="button" class="btn btn-ghost" wire:click="cancel">ยกเลิก</button>
                </div>
            </div>
        </form>
    @endif

    <section class="panel">
        <table class="ds-table">
            <thead>
                <tr>
                    <th>ชื่อรอบ</th>
                    <th>เริ่ม</th>
                    <th>สิ้นสุด</th>
                    <th>สถานะ</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rounds as $round)
                    @php
                        $status = $round->effectiveStatus();
                        $pill = match ($status) {
                            'open' => 'pill-active',
                            'scheduled' => 'pill-info',
                            'draft' => 'pill-draft',
                            default => 'pill-neutral',
                        };
                        $label = match ($status) {
                            'open' => 'เปิดอยู่',
                            'scheduled' => 'รอเปิด',
                            'draft' => 'ปิดใช้',
                            default => 'ปิดแล้ว',
                        };
                    @endphp
                    <tr wire:key="round-{{ $round->id }}">
                        <td>{{ $round->name }}</td>
                        <td class="mono">{{ $round->starts_at?->toThaiDatetime() }}</td>
                        <td class="mono">{{ $round->ends_at?->toThaiDatetime() }}</td>
                        <td><span class="pill {{ $pill }}">{{ $label }}</span></td>
                        <td><button type="button" class="btn btn-ghost btn-sm" wire:click="edit({{ $round->id }})">แก้ไข</button></td>
                    </tr>
                @empty
                    <tr><td colspan="5"><p class="empty">ยังไม่มีรอบจอง</p></td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
</div>
