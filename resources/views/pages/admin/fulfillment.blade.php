<?php
use App\Enums\FulfillmentMethod;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\Order\OrderService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts.admin')]
#[Title('จัดส่ง')]
class extends Component
{
    #[Url]
    public string $status = 'active';

    #[Url]
    public string $search = '';

    #[Url]
    public string $id = '';

    public string $parcelNumber = '';

    public function mount(): void
    {
        $this->parcelNumber = $this->selected()?->parcel_number ?? '';
    }

    public function updatedId(): void
    {
        $this->parcelNumber = $this->selected()?->parcel_number ?? '';
        $this->resetErrorBag('parcel_number');
    }

    public function clearFilters(): void
    {
        $this->status = 'active';
        $this->search = '';
        $this->id = '';
        $this->parcelNumber = '';
    }

    public function select(string $number): void
    {
        $this->id = $number;
    }

    public function markShipped(OrderService $orders): void
    {
        $order = $this->selected();

        if ($order === null) {
            return;
        }

        try {
            $updated = $orders->markShipped($order, Auth::user(), $this->parcelNumber);
            $this->dispatch('admin-toast', message: 'ทำเครื่องหมายจัดส่งแล้ว');
            $this->id = $updated->number;
            $this->parcelNumber = $updated->parcel_number ?? '';
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->errors());
        }
    }

    public function saveParcelNumber(OrderService $orders): void
    {
        $order = $this->selected();

        if ($order === null) {
            return;
        }

        try {
            $updated = $orders->updateParcelNumber($order, Auth::user(), $this->parcelNumber);
            $this->dispatch('admin-toast', message: 'บันทึกเลขพัสดุแล้ว');
            $this->id = $updated->number;
            $this->parcelNumber = $updated->parcel_number ?? '';
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->errors());
        }
    }

    public function render(OrderService $orders)
    {
        $filters = [
            'search' => $this->search !== '' ? $this->search : null,
            'status' => null,
            'fulfillment' => FulfillmentMethod::Post,
        ];

        if ($this->status === 'all') {
            $filters['statuses'] = [
                OrderStatus::Confirmed,
                OrderStatus::ReadyForPickup,
                OrderStatus::Shipped,
                OrderStatus::Completed,
            ];
        } else {
            $filters['awaiting_parcel'] = true;
        }

        $list = $orders->queue($filters);
        $selected = $this->selected();

        return $this->view([
            'orders' => $list,
            'selected' => $selected,
            'canShip' => $selected !== null && in_array(OrderStatus::Shipped, $orders->allowedTransitions($selected), true),
            'canUpdateParcel' => $selected !== null && $selected->status === OrderStatus::Shipped,
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
            ->where('fulfillment', FulfillmentMethod::Post)
            ->first();
    }
};
?>

<div>
    <div class="page-head">
        <div>
            <h1>จัดส่ง</h1>
            <p class="sub">คิวไปรษณีย์หลังยืนยันสลิป</p>
        </div>
    </div>

    <div class="toolbar">
        <div class="filters">
            <select class="select" wire:model.live="status" aria-label="สถานะ" style="max-width:200px">
                <option value="active">รอดำเนินการ</option>
                <option value="all">ทั้งหมด</option>
            </select>
            <input class="input" type="search" wire:model.live.debounce.300ms="search" placeholder="รหัสออเดอร์หรือรหัสนักศึกษา" aria-label="ค้นหา" style="max-width:360px; width:100%">
            <button type="button" class="btn btn-ghost" wire:click="clearFilters">ล้างตัวกรอง</button>
        </div>
    </div>

    <div class="grid-2">
        <section class="panel">
            @if ($orders->isEmpty())
                <p class="empty">ไม่มีออเดอร์</p>
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
                    @if ($canShip || $canUpdateParcel)
                        <input
                            class="input"
                            type="text"
                            wire:model="parcelNumber"
                            placeholder="เลขพัสดุ"
                            aria-label="เลขพัสดุ"
                        >
                        @error('parcel_number') <span class="error">{{ $message }}</span> @enderror
                    @endif
                    <div class="row">
                        @if ($canShip)
                            <button type="button" class="btn btn-primary" wire:click="markShipped">จัดส่งแล้ว</button>
                        @endif
                        @if ($canUpdateParcel)
                            <button type="button" class="btn btn-primary" wire:click="saveParcelNumber">บันทึกเลขพัสดุ</button>
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
