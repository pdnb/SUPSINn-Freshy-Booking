<?php
use App\Models\Order;
use App\Services\Order\OrderService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts.admin')]
#[Title('รับของ')]
class extends Component
{
    #[Url]
    public string $search = '';

    public ?int $selectedId = null;

    public function clearFilters(): void
    {
        $this->search = '';
        $this->selectedId = null;
    }

    public function select(int $id): void
    {
        $this->selectedId = $id;
    }

    public function collectBalance(OrderService $orders): void
    {
        $order = $this->selected();

        if ($order === null) {
            return;
        }

        try {
            $orders->collectBalance($order, Auth::user());
            session()->flash('status', 'บันทึกเก็บส่วนที่เหลือแล้ว');
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->errors());
        }

        $this->selectedId = $order->id;
    }

    public function markPickedUp(OrderService $orders): void
    {
        $order = $this->selected();

        if ($order === null) {
            return;
        }

        try {
            $orders->markPickedUp($order, Auth::user());
            session()->flash('status', 'รับของแล้ว และออกใบเสร็จแล้ว');
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->errors());
        }

        $this->selectedId = $order->id;
    }

    public function render(OrderService $orders)
    {
        $results = $this->search === ''
            ? collect()
            : $orders->queue([
                'search' => $this->search,
                'status' => null,
            ]);

        return $this->view([
            'results' => $results,
            'selected' => $this->selected(),
        ]);
    }

    private function selected(): ?Order
    {
        if ($this->selectedId === null) {
            return null;
        }

        return Order::query()->with('items')->find($this->selectedId);
    }
};
?>

<div>
    <div class="page-head">
        <div>
            <h1>รับของหน้างาน</h1>
            <p class="sub">ค้นด้วยรหัสออเดอร์ รหัสนักศึกษา ชื่อ หรือเบอร์โทร</p>
        </div>
    </div>

    <div class="toolbar">
        <div class="filters">
            <input class="input" type="search" wire:model.live.debounce.300ms="search" placeholder="รหัสออเดอร์หรือรหัสนักศึกษา" aria-label="ค้นหาออเดอร์" style="max-width:420px; width:100%">
            <button type="button" class="btn btn-ghost" wire:click="clearFilters">ล้างตัวกรอง</button>
        </div>
    </div>

    <div class="grid-2">
        <section class="panel">
            <div class="panel-head">ผลการค้นหา</div>
            <div class="panel-body">
                @if ($search === '')
                    <p class="empty">พิมพ์เพื่อค้นหา</p>
                @elseif ($results->isEmpty())
                    <p class="empty">ไม่พบออเดอร์</p>
                @else
                    @foreach ($results as $order)
                        <button
                            type="button"
                            class="list-row"
                            style="width:100%;text-align:left"
                            wire:key="pickup-{{ $order->id }}"
                            wire:click="select({{ $order->id }})"
                        >
                            <span><span class="mono">{{ $order->number }}</span> · {{ $order->full_name }}</span>
                            <x-admin.status-pill :status="$order->status" />
                        </button>
                    @endforeach
                @endif
            </div>
        </section>
        <section class="panel">
            <div class="panel-head">รายละเอียด</div>
            <div class="panel-body stack">
                @if ($selected)
                    <p><span class="mono">{{ $selected->number }}</span> · {{ $selected->full_name }}</p>
                    <p class="muted">{{ $selected->student_id }} · {{ $selected->phone }}</p>
                    <x-admin.status-pill :status="$selected->status" />
                    <ul class="choice-list">
                        @foreach ($selected->items as $item)
                            <li>{{ $item->name }} × {{ $item->qty }}</li>
                        @endforeach
                    </ul>
                    <p>ยอดสุทธิ <span class="mono">{{ number_format((float) $selected->total, 0) }}</span> บาท · จ่ายตอนสั่ง <span class="mono">{{ number_format((float) $selected->amount_due_now, 0) }}</span> บาท</p>
                    @if ((float) $selected->amount_remaining > 0)
                        <p>
                            คงเหลือ <span class="mono">{{ number_format((float) $selected->amount_remaining, 0) }}</span> บาท
                            · {{ $selected->balance_collected_at ? 'เก็บครบแล้ว' : 'ยังไม่เก็บ' }}
                        </p>
                    @endif
                    @error('status') <span class="error">{{ $message }}</span> @enderror
                    @error('balance') <span class="error">{{ $message }}</span> @enderror
                    @if ($selected->hasOutstandingBalance())
                        <button type="button" class="btn btn-secondary" wire:click="collectBalance">บันทึกเก็บส่วนที่เหลือ</button>
                    @endif
                    @if ($selected->status === \App\Enums\OrderStatus::ReadyForPickup && ! $selected->hasOutstandingBalance())
                        <button type="button" class="btn btn-primary" wire:click="markPickedUp">รับของแล้ว</button>
                    @endif
                @else
                    <p class="empty">พิมพ์เพื่อค้นหา</p>
                @endif
            </div>
        </section>
    </div>
</div>
