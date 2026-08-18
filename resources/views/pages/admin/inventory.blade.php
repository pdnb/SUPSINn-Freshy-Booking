<?php
use App\Enums\InventoryAdjustmentReason;
use App\Models\Product;
use App\Services\Inventory\InventoryService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts.admin')]
#[Title('สต็อก')]
class extends Component
{
    #[Url]
    public string $search = '';

    #[Url]
    public string $stock = '';

    public bool $showAdjust = false;

    public ?int $adjustProductId = null;

    public string $adjustLabel = '';

    public string $adjustValue = '';

    public string $adjustName = '';

    public string $delta = '10';

    public string $threshold = '0';

    public string $reason = 'factory_receipt';

    public function clearFilters(): void
    {
        $this->search = '';
        $this->stock = '';
    }

    public function openAdjust(int $productId, string $label, string $value, string $name, int $threshold = 0): void
    {
        $this->adjustProductId = $productId;
        $this->adjustLabel = $label;
        $this->adjustValue = $value;
        $this->adjustName = $name;
        $this->delta = '10';
        $this->threshold = (string) $threshold;
        $this->reason = InventoryAdjustmentReason::FactoryReceipt->value;
        $this->showAdjust = true;
        $this->resetErrorBag();
    }

    public function closeAdjust(): void
    {
        $this->showAdjust = false;
    }

    public function applyAdjust(InventoryService $inventory): void
    {
        if ($this->adjustProductId === null) {
            return;
        }

        try {
            $reason = InventoryAdjustmentReason::from($this->reason);
            $product = Product::query()->findOrFail($this->adjustProductId);
            $inventory->adjust(
                $product,
                $this->adjustLabel,
                $this->adjustValue,
                (int) $this->delta,
                $reason,
                Auth::user(),
            );
            $inventory->setThreshold(
                $product,
                $this->adjustLabel,
                $this->adjustValue,
                (int) $this->threshold,
            );
            $this->dispatch('admin-toast', message: 'ปรับยอดของที่มีแล้ว');
            $this->showAdjust = false;
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->errors());
        }
    }

    public function render(InventoryService $inventory)
    {
        return $this->view([
            'rows' => $inventory->list([
                'search' => $this->search !== '' ? $this->search : null,
                'stock' => $this->stock !== '' ? $this->stock : null,
            ]),
            'reasons' => InventoryAdjustmentReason::cases(),
        ]);
    }
};
?>

<div>
    <div class="page-head">
        <div>
            <h1>สต็อก</h1>
            <p class="sub">ของที่มีจากโรงงาน เทียบกับออเดอร์ที่ยืนยัน — ไม่บล็อกการจอง</p>
        </div>
    </div>

    <div class="toolbar">
        <div class="filters">
            <input class="input" type="search" wire:model.live.debounce.300ms="search" placeholder="ค้นหาสินค้าหรือไซส์" style="max-width:240px">
            <select class="select" wire:model.live="stock" style="max-width:160px">
                <option value="">ทุกสถานะ</option>
                <option value="low">ต่ำกว่าเกณฑ์</option>
                <option value="ok">พอ</option>
            </select>
            <button type="button" class="btn btn-ghost" wire:click="clearFilters">ล้างตัวกรอง</button>
        </div>
    </div>

    <section class="panel">
        @if ($rows === [])
            <p class="empty">ยังไม่มีแถวสต็อก</p>
        @else
            <table class="ds-table">
                <thead>
                    <tr>
                        <th>สินค้า</th>
                        <th>ตัวเลือก</th>
                        <th class="num-col">ของที่มี</th>
                        <th class="num-col">ออเดอร์ยืนยัน</th>
                        <th class="num-col">เหลือ</th>
                        <th class="num-col">เกณฑ์</th>
                        <th>สถานะ</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr wire:key="inv-{{ $row['key'] }}">
                            <td>{{ $row['product_name'] }}</td>
                            <td>{{ $row['choice_label'] !== '' ? $row['choice_label'].' '.$row['choice_value'] : '—' }}</td>
                            <td class="num-col">{{ $row['on_hand'] }}</td>
                            <td class="num-col">{{ $row['confirmed_qty'] }}</td>
                            <td class="num-col">{{ $row['remaining'] }}</td>
                            <td class="num-col">{{ $row['threshold'] }}</td>
                            <td>
                                <span class="pill {{ $row['is_low'] ? 'pill-low' : 'pill-active' }}">
                                    {{ $row['is_low'] ? 'ต่ำ' : 'พอ' }}
                                </span>
                            </td>
                            <td>
                                <button
                                    type="button"
                                    class="btn btn-ghost btn-sm"
                                    wire:click="openAdjust({{ $row['product_id'] }}, @js($row['choice_label']), @js($row['choice_value']), @js($row['product_name']), {{ $row['threshold'] }})"
                                >
                                    ปรับยอด
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>

    <div class="overlay {{ $showAdjust ? 'is-open' : '' }}" wire:click="closeAdjust"></div>
    <div class="dialog {{ $showAdjust ? 'is-open' : '' }}" role="dialog" aria-modal="true" aria-labelledby="adjust-title">
        <div class="dialog-head">
            <h2 id="adjust-title">ปรับยอดของที่มี</h2>
            <button type="button" class="icon-btn" wire:click="closeAdjust" aria-label="ปิด">
                <x-icon name="x-mark" size="sm" />
            </button>
        </div>
        <div class="dialog-body stack">
            <p class="muted">{{ $adjustName }} @if ($adjustLabel !== '') · {{ $adjustLabel }} {{ $adjustValue }} @endif</p>
            <label class="field">
                จำนวนที่เปลี่ยน (+/−)
                <input class="input" type="number" step="1" wire:model="delta">
                @error('delta') <span class="error">{{ $message }}</span> @enderror
            </label>
            <label class="field">
                เกณฑ์เตือน
                <input class="input" type="number" min="0" wire:model="threshold">
                @error('threshold') <span class="error">{{ $message }}</span> @enderror
            </label>
            <label class="field">
                เหตุผล
                <select class="select" wire:model="reason">
                    @foreach ($reasons as $item)
                        <option value="{{ $item->value }}">{{ $item->label() }}</option>
                    @endforeach
                </select>
            </label>
        </div>
        <div class="dialog-foot">
            <button type="button" class="btn btn-secondary" wire:click="closeAdjust">ยกเลิก</button>
            <button type="button" class="btn btn-primary" wire:click="applyAdjust">บันทึกการปรับ</button>
        </div>
    </div>
</div>
