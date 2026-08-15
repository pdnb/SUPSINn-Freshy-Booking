<?php
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\Order\OrderService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.admin')]
#[Title('ภาพรวม')]
class extends Component
{
    public function render(OrderService $orders)
    {
        $attention = $orders->queue([
            'status' => null,
            'statuses' => $orders->reviewQueueStatuses(),
        ]);

        return $this->view([
            'pendingCount' => $orders->countPendingReview(),
            'fulfillCount' => Order::query()->where('status', OrderStatus::Confirmed)->count(),
            'readyCount' => Order::query()->where('status', OrderStatus::ReadyForPickup)->count(),
            'attention' => $attention,
            'recent' => Order::query()->with('items')->latest()->limit(6)->get(),
        ]);
    }
};
?>

<div>
    <div class="page-head">
        <div>
            <h1>ภาพรวม</h1>
        </div>
        <a class="btn btn-primary" href="{{ route('admin.orders') }}">ดูออเดอร์</a>
    </div>

    <div class="grid-3" style="margin-bottom: 20px;">
        <article class="kpi">
            <div class="label">คิวรอตรวจ</div>
            <div class="value">{{ $pendingCount }}</div>
        </article>
        <article class="kpi">
            <div class="label">รอจัดส่ง / ยืนยันแล้ว</div>
            <div class="value">{{ $fulfillCount }}</div>
        </article>
        <article class="kpi">
            <div class="label">พร้อมรับ</div>
            <div class="value">{{ $readyCount }}</div>
        </article>
    </div>

    <div class="grid-2">
        <section class="panel">
            <div class="panel-head">รายการค้าง</div>
            <div class="panel-body">
                @forelse ($attention as $order)
                    <a class="list-row" href="{{ route('admin.orders.show', $order) }}" wire:key="attn-{{ $order->id }}">
                        <span><span class="mono">{{ $order->number }}</span> · {{ $order->full_name }}</span>
                        <x-admin.status-pill :status="$order->status" />
                    </a>
                @empty
                    <p class="empty">ไม่มีรายการค้าง</p>
                @endforelse
            </div>
        </section>
        <section class="panel">
            <div class="panel-head">ออเดอร์ล่าสุด</div>
            <div class="panel-body">
                @forelse ($recent as $order)
                    @php
                        $href = in_array($order->status, [
                            \App\Enums\OrderStatus::Confirmed,
                            \App\Enums\OrderStatus::ReadyForPickup,
                            \App\Enums\OrderStatus::Shipped,
                        ], true)
                            ? route('admin.fulfillment', ['id' => $order->number])
                            : route('admin.orders.show', $order);
                    @endphp
                    <a class="list-row" href="{{ $href }}" wire:key="recent-{{ $order->id }}">
                        <span><span class="mono">{{ $order->number }}</span> · {{ $order->full_name }}</span>
                        <x-admin.status-pill :status="$order->status" />
                    </a>
                @empty
                    <p class="empty">ยังไม่มีออเดอร์</p>
                @endforelse
            </div>
        </section>
    </div>
</div>
