<?php
use App\Enums\OrderStatus;
use App\Services\Booking\BookingRoundService;
use App\Services\Order\OrderService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts.admin')]
#[Title('ออเดอร์')]
class extends Component
{
    #[Url]
    public string $search = '';

    #[Url]
    public string $status = 'pending_review';

    #[Url]
    public string $booking_round_id = '';

    #[Url]
    public string $date_from = '';

    #[Url]
    public string $date_to = '';

    public function clearFilters(): void
    {
        $this->search = '';
        $this->status = 'pending_review';
        $this->booking_round_id = '';
        $this->date_from = '';
        $this->date_to = '';
    }

    public function render(OrderService $orders, BookingRoundService $rounds)
    {
        $status = $this->status === 'all' ? null : $this->status;

        return $this->view([
            'orders' => $orders->queue([
                'search' => $this->search !== '' ? $this->search : null,
                'status' => $status,
                'booking_round_id' => $this->booking_round_id !== '' ? $this->booking_round_id : null,
                'date_from' => $this->date_from !== '' ? $this->date_from : null,
                'date_to' => $this->date_to !== '' ? $this->date_to : null,
            ]),
            'rounds' => $rounds->list(),
            'statuses' => [
                OrderStatus::PendingReview->value => 'รอตรวจสลิป',
                OrderStatus::NeedReslip->value => 'ขอสลิปใหม่',
                'all' => 'ทั้งหมด',
            ],
        ]);
    }
};
?>

<div>
    <div class="page-head">
        <div>
            <h1>ออเดอร์</h1>
            <p class="sub">ดูสลิป ยอด ผู้จอง แล้วยืนยันหรือปฏิเสธ</p>
        </div>
    </div>

    <div class="toolbar">
        <div class="filters">
            <input class="input" type="search" wire:model.live.debounce.300ms="search" placeholder="รหัสออเดอร์หรือรหัสนักศึกษา" aria-label="ค้นหา" style="max-width:260px">
            <select class="select" wire:model.live="status" aria-label="สถานะ" style="max-width:180px">
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
            <select class="select" wire:model.live="booking_round_id" aria-label="รอบจอง" style="max-width:200px">
                <option value="">ทุกรอบ</option>
                @foreach ($rounds as $round)
                    <option value="{{ $round->id }}">{{ $round->name }}</option>
                @endforeach
            </select>
            <input class="input" type="date" wire:model.live="date_from" aria-label="ตั้งแต่วันที่" style="max-width:160px">
            <input class="input" type="date" wire:model.live="date_to" aria-label="ถึงวันที่" style="max-width:160px">
            <button type="button" class="btn btn-ghost" wire:click="clearFilters">เคลียร์</button>
        </div>
    </div>

    <section class="panel">
        @if ($orders->isEmpty())
            <p class="empty">ไม่มีออเดอร์ในตัวกรองนี้</p>
        @else
            <table class="ds-table">
                <thead>
                    <tr>
                        <th>ออเดอร์</th>
                        <th>วันที่</th>
                        <th>ผู้จอง</th>
                        <th>ช่องทาง</th>
                        <th>ยอด</th>
                        <th>สถานะ</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($orders as $order)
                        <tr class="is-clickable" wire:key="order-{{ $order->id }}" onclick="window.location='{{ route('admin.orders.show', $order) }}'">
                            <td><a class="linkish" href="{{ route('admin.orders.show', $order) }}">{{ $order->number }}</a></td>
                            <td class="mono">{{ $order->created_at?->toThaiDatetime() }}</td>
                            <td>{{ $order->full_name }}<div class="meta">{{ $order->student_id }}</div></td>
                            <td>{{ $order->fulfillment->label() }}</td>
                            <td class="num-col">{{ number_format((float) $order->total, 0) }}</td>
                            <td><x-admin.status-pill :status="$order->status" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>
</div>
