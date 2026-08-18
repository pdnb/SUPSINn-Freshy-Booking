<?php
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\Order\OrderService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.admin')]
#[Title('รายละเอียดออเดอร์')]
class extends Component
{
    public Order $order;

    public bool $showCancelConfirm = false;

    public function mount(Order $order): void
    {
        $this->order = $order->load(['items', 'slip', 'bookingRound', 'statusChanges.user']);
    }

    public function confirm(OrderService $orders): void
    {
        $this->transitionTo($orders, OrderStatus::Confirmed, 'ยืนยันสลิปแล้ว');
    }

    public function requestReslip(OrderService $orders): void
    {
        $this->transitionTo($orders, OrderStatus::NeedReslip, 'ขอสลิปใหม่แล้ว');
    }

    public function openCancelConfirm(): void
    {
        $this->showCancelConfirm = true;
    }

    public function closeCancelConfirm(): void
    {
        $this->showCancelConfirm = false;
    }

    public function cancel(OrderService $orders): void
    {
        $this->showCancelConfirm = false;
        $this->transitionTo($orders, OrderStatus::Cancelled, 'ยกเลิกออเดอร์แล้ว');
    }

    public function collectBalance(OrderService $orders): void
    {
        try {
            $this->order = $orders->collectBalance($this->order, Auth::user());
            $this->dispatch('admin-toast', message: 'บันทึกเก็บส่วนที่เหลือแล้ว');
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->errors());
        }
    }

    public function render(OrderService $orders)
    {
        $queue = $orders->queue([
            'status' => null,
            'statuses' => $orders->reviewQueueStatuses(),
        ])->values();

        $index = $queue->search(fn (Order $order): bool => $order->is($this->order));

        return $this->view([
            'canReview' => in_array($this->order->status, $orders->reviewQueueStatuses(), true),
            'previous' => $index !== false ? $queue->get($index - 1) : null,
            'next' => $index !== false ? $queue->get($index + 1) : $orders->nextInReviewQueue($this->order),
        ]);
    }

    private function transitionTo(OrderService $orders, OrderStatus $status, string $message): void
    {
        $next = $orders->nextInReviewQueue($this->order);
        $orders->transition($this->order, $status, Auth::user());
        session()->flash('status', $message);

        $this->redirect(
            $next !== null ? route('admin.orders.show', $next) : route('admin.orders'),
        );
    }
};
?>

<div
    x-data
    x-on:keydown.window.j="window.location = @js($next ? route('admin.orders.show', $next) : null) || undefined"
    x-on:keydown.window.k="window.location = @js($previous ? route('admin.orders.show', $previous) : null) || undefined"
>
    <div class="page-head">
        <div>
            <h1>ออเดอร์ <span class="mono">{{ $order->number }}</span></h1>
            <p class="sub">{{ $order->full_name }} · {{ $order->student_id }} · {{ $order->fulfillment->label() }}</p>
        </div>
        <div class="row">
            <a class="btn btn-ghost" href="{{ route('admin.orders') }}">กลับคิว</a>
            @if ($previous)
                <a class="btn btn-ghost" href="{{ route('admin.orders.show', $previous) }}">ก่อนหน้า</a>
            @endif
            @if ($next)
                <a class="btn btn-ghost" href="{{ route('admin.orders.show', $next) }}">ถัดไป</a>
            @endif
        </div>
    </div>

    <p class="hotkeys"><kbd>j</kbd> ถัดไป <kbd>k</kbd> ก่อนหน้า</p>

    <div class="grid-2">
        <section class="panel">
            <div class="panel-head">สลิป PromptPay</div>
            <div class="panel-body">
                @if ($order->slip)
                    @if (str_ends_with(mb_strtolower($order->slip->original_name), '.pdf'))
                        <iframe class="slip-frame" title="สลิปการโอน" src="{{ route('admin.orders.slip', $order) }}"></iframe>
                    @else
                        <img class="slip-frame" src="{{ route('admin.orders.slip', $order) }}" alt="สลิปการโอน">
                    @endif
                    <p class="muted" style="margin-top:12px">ไฟล์ {{ $order->slip->original_name }}</p>
                @else
                    <p class="empty">ไม่มีสลิป</p>
                @endif
            </div>
        </section>
        <div class="stack">
            <section class="panel">
                <div class="panel-head">
                    <span>สรุป</span>
                    <x-admin.status-pill :status="$order->status" />
                </div>
                <div class="panel-body stack">
                    <dl class="detail-list">
                        <div>
                            <dt>ผู้จอง</dt>
                            <dd>{{ $order->full_name }}</dd>
                        </div>
                        <div>
                            <dt>รหัสนักศึกษา</dt>
                            <dd class="mono">{{ $order->student_id }}</dd>
                        </div>
                        <div>
                            <dt>คณะ</dt>
                            <dd>{{ $order->faculty }}</dd>
                        </div>
                        <div>
                            <dt>สาขาวิชา</dt>
                            <dd>{{ $order->major }}</dd>
                        </div>
                        <div>
                            <dt>โทร</dt>
                            <dd class="mono">{{ $order->phone }}</dd>
                        </div>
                        <div>
                            <dt>วิธีรับ</dt>
                            <dd>{{ $order->fulfillment->label() }}</dd>
                        </div>
                        @if ($order->address)
                            <div>
                                <dt>ที่อยู่จัดส่ง</dt>
                                <dd class="address">{{ $order->address }}</dd>
                            </div>
                        @endif
                    </dl>
                    <table class="ds-table">
                        <thead>
                            <tr>
                                <th>รายการ</th>
                                <th>ตัวเลือก</th>
                                <th class="num-col">จำนวน</th>
                                <th class="num-col">ราคา</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($order->items as $item)
                                <tr wire:key="item-{{ $item->id }}">
                                    <td>{{ $item->name }}</td>
                                    <td>
                                        @if (($item->choices ?? []) !== [])
                                            <ul class="choice-list">
                                                @foreach ($item->choices as $index => $choice)
                                                    <li wire:key="choice-{{ $item->id }}-{{ $index }}">
                                                        {{ $choice['label'] ?? '' }} · {{ $choice['value'] ?? '' }}
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </td>
                                    <td class="num-col">{{ $item->qty }}</td>
                                    <td class="num-col">{{ number_format((float) $item->price * $item->qty, 0) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <dl class="totals-list">
                        <div>
                            <dt>ยอดสินค้า</dt>
                            <dd class="mono">{{ number_format((float) $order->subtotal, 0) }} บาท</dd>
                        </div>
                        <div>
                            <dt>ค่าส่ง{{ $order->shipping_rate_name ? ' · '.$order->shipping_rate_name : '' }}</dt>
                            <dd class="mono">{{ number_format((float) $order->shipping_amount, 0) }} บาท</dd>
                        </div>
                        <div class="is-total">
                            <dt>ยอดสุทธิ</dt>
                            <dd class="mono">{{ number_format((float) $order->total, 0) }} บาท</dd>
                        </div>
                        <div>
                            <dt>โหมดชำระ</dt>
                            <dd>{{ $order->payment_mode->label() }}</dd>
                        </div>
                        <div>
                            <dt>จ่ายตอนสั่ง</dt>
                            <dd class="mono">{{ number_format((float) $order->amount_due_now, 0) }} บาท</dd>
                        </div>
                        @if ((float) $order->amount_remaining > 0)
                            <div>
                                <dt>คงเหลือตอนรับ</dt>
                                <dd class="mono">{{ number_format((float) $order->amount_remaining, 0) }} บาท</dd>
                            </div>
                            <div>
                                <dt>สถานะเก็บเงิน</dt>
                                <dd>{{ $order->balance_collected_at ? 'เก็บครบแล้ว' : 'ยังไม่เก็บ' }}</dd>
                            </div>
                        @endif
                    </dl>
                    @if ($order->hasOutstandingBalance())
                        <div class="row">
                            <button type="button" class="btn btn-secondary" wire:click="collectBalance">บันทึกเก็บส่วนที่เหลือ</button>
                        </div>
                        @error('balance') <span class="error">{{ $message }}</span> @enderror
                    @endif
                    @if ($canReview)
                        <div class="row">
                            <button type="button" class="btn btn-primary" wire:click="confirm">ยืนยันสลิป</button>
                            <button type="button" class="btn btn-secondary" wire:click="requestReslip">ขอสลิปใหม่</button>
                            <button type="button" class="btn btn-danger" wire:click="openCancelConfirm">ยกเลิก</button>
                        </div>
                    @endif
                </div>
            </section>
            <section class="panel">
                <div class="panel-head">ไทม์ไลน์</div>
                <div class="panel-body">
                    <ol class="timeline">
                        @forelse ($order->statusChanges as $change)
                            <li @class(['is-latest' => $loop->first]) wire:key="change-{{ $change->id }}">
                                <div>{{ $change->to_status->label() }}</div>
                                <div class="muted">{{ $change->created_at?->toThaiDatetime() }} · {{ $change->user?->name }}</div>
                            </li>
                        @empty
                            <li>รับออเดอร์เข้าระบบ</li>
                        @endforelse
                    </ol>
                </div>
            </section>
        </div>
    </div>

    <x-admin.confirm-dialog
        :open="$showCancelConfirm"
        title="ยกเลิกออเดอร์"
        title-id="cancel-order-title"
        close="closeCancelConfirm"
        confirm="cancel"
        confirm-label="ยืนยันยกเลิก"
    >
        ต้องการยกเลิกออเดอร์ <span class="mono">{{ $order->number }}</span> ของ {{ $order->full_name }} หรือไม่?
    </x-admin.confirm-dialog>
</div>
