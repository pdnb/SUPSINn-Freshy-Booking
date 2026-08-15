<?php
use App\Enums\FulfillmentMethod;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\Order\OrderService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts.admin')]
#[Title('จัดส่ง')]
class extends Component
{
    #[Url]
    public string $channel = 'bookstore';

    #[Url]
    public string $status = 'active';

    #[Url]
    public string $search = '';

    #[Url]
    public string $id = '';

    public function clearFilters(): void
    {
        $this->status = 'active';
        $this->search = '';
        $this->id = '';
    }

    public function select(string $number): void
    {
        $this->id = $number;
    }

    public function markReady(OrderService $orders): void
    {
        $order = $this->selected();

        if ($order === null) {
            return;
        }

        $orders->transition($order, OrderStatus::ReadyForPickup, Auth::user());
        session()->flash('status', 'ทำเครื่องหมายพร้อมรับแล้ว');
        $this->id = $order->number;
    }

    public function markShipped(OrderService $orders): void
    {
        $order = $this->selected();

        if ($order === null) {
            return;
        }

        $orders->transition($order, OrderStatus::Shipped, Auth::user());
        session()->flash('status', 'ทำเครื่องหมายจัดส่งแล้ว');
        $this->id = $order->number;
    }

    public function render(OrderService $orders)
    {
        $channel = FulfillmentMethod::tryFrom($this->channel) ?? FulfillmentMethod::Bookstore;
        $this->channel = $channel->value;

        $statuses = $this->status === 'all'
            ? [OrderStatus::Confirmed, OrderStatus::ReadyForPickup, OrderStatus::Shipped, OrderStatus::Completed]
            : [OrderStatus::Confirmed];

        $list = $orders->queue([
            'search' => $this->search !== '' ? $this->search : null,
            'status' => null,
            'statuses' => $statuses,
            'fulfillment' => $channel,
        ]);

        $selected = $this->selected();

        return $this->view([
            'channels' => FulfillmentMethod::cases(),
            'orders' => $list,
            'selected' => $selected,
            'canReady' => $selected !== null && in_array(OrderStatus::ReadyForPickup, $orders->allowedTransitions($selected), true),
            'canShip' => $selected !== null && in_array(OrderStatus::Shipped, $orders->allowedTransitions($selected), true),
        ]);
    }

    private function selected(): ?Order
    {
        if ($this->id === '') {
            return null;
        }

        return Order::query()
            ->with(['items', 'bookingRound'])
            ->where('number', $this->id)
            ->first();
    }
};
?>

<div>
    <div class="page-head">
        <div>
            <h1>จัดส่ง / จุดรับ</h1>
            <p class="sub">บอร์ดหลังยืนยันสลิป แยกตามช่องทาง</p>
        </div>
    </div>

    <div class="tabs" role="tablist" aria-label="ช่องทาง">
        @foreach ($channels as $method)
            <button
                type="button"
                class="tab {{ $channel === $method->value ? 'is-active' : '' }}"
                wire:click="$set('channel', '{{ $method->value }}')"
                role="tab"
                aria-selected="{{ $channel === $method->value ? 'true' : 'false' }}"
            >
                {{ $method->label() }}
            </button>
        @endforeach
    </div>

    <div class="toolbar">
        <div class="filters">
            <select class="select" wire:model.live="status" aria-label="สถานะ" style="max-width:200px">
                <option value="active">รอดำเนินการ</option>
                <option value="all">ทั้งหมดในช่องทาง</option>
            </select>
            <input class="input" type="search" wire:model.live.debounce.300ms="search" placeholder="รหัสออเดอร์หรือรหัสนักศึกษา" aria-label="ค้นหา" style="max-width:360px; width:100%">
            <button type="button" class="btn btn-ghost" wire:click="clearFilters">ล้างตัวกรอง</button>
        </div>
    </div>

    <div class="grid-2">
        <section class="panel">
            @if ($orders->isEmpty())
                <p class="empty">ไม่มีออเดอร์ในช่องทางนี้</p>
            @else
                <table class="ds-table">
                    <thead>
                        <tr>
                            <th>ออเดอร์</th>
                            <th>ผู้จอง</th>
                            <th>สถานะ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($orders as $order)
                            <tr
                                class="is-clickable"
                                wire:key="fulfill-{{ $order->id }}"
                                wire:click="select('{{ $order->number }}')"
                            >
                                <td class="mono">{{ $order->number }}</td>
                                <td>{{ $order->full_name }}<div class="meta">{{ $order->student_id }}</div></td>
                                <td><x-admin.status-pill :status="$order->status" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>
        <section class="panel">
            <div class="panel-head">รายละเอียด</div>
            <div class="panel-body stack">
                @if ($selected)
                    <p><span class="mono">{{ $selected->number }}</span> · {{ $selected->full_name }}</p>
                    <p class="muted">{{ $selected->phone }} · {{ $selected->fulfillment->label() }}</p>
                    @if ($selected->address)
                        <p>{{ $selected->address }}</p>
                    @endif
                    <x-admin.status-pill :status="$selected->status" />
                    <div class="row">
                        @if ($canReady)
                            <button type="button" class="btn btn-primary" wire:click="markReady">พร้อมรับของ</button>
                        @endif
                        @if ($canShip)
                            <button type="button" class="btn btn-primary" wire:click="markShipped">จัดส่งแล้ว</button>
                        @endif
                        <a class="btn btn-ghost" href="{{ route('admin.orders.show', $selected) }}">ดูออเดอร์</a>
                    </div>
                @else
                    <p class="empty">เลือกแถวทางซ้าย</p>
                @endif
            </div>
        </section>
    </div>
</div>
