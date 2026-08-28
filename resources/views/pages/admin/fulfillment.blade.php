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
            ->with(['items', 'bookingRound', 'statusChanges.user'])
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
            <p class="sub">คิวพัสดุลหลังแพ็คของแล้ว</p>
        </div>
    </div>

    <div class="toolbar">
        <div class="filters">
            <select class="select" wire:model.live="status" aria-label="สถานะ" style="max-width:200px">
                <option value="active">รอดำเนินการ</option>
                <option value="all">ทั้งหมด</option>
            </select>
            <input class="input" type="search" wire:model.live.debounce.300ms="search" placeholder="รหัสออเดอร์หรือรหัสนักศึกษา" aria-label="ค้นหา" style="max-width:360px; width:100%">
            <button type="button" class="btn btn-ghost" wire:click="clearFilters">เคลียร์</button>
        </div>
    </div>

    <div class="fulfill-split">
        <section class="panel">
            <div class="panel-head">
                <span class="row row-tight">
                    <x-icon name="truck" size="sm" />
                    คิวพัสดุ
                </span>
                <span class="meta">{{ $orders->count() }}</span>
            </div>
            @if ($orders->isEmpty())
                <div class="empty fulfill-empty">
                    <x-icon name="inbox" size="lg" />
                    <p>ไม่มีออเดอร์</p>
                </div>
            @else
                <div class="fulfill-table-wrap">
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
                                    @class(['is-clickable', 'is-selected' => $id === $order->number])
                                    wire:key="fulfill-{{ $order->id }}"
                                    wire:click="select('{{ $order->number }}')"
                                    aria-selected="{{ $id === $order->number ? 'true' : 'false' }}"
                                >
                                    <td class="mono">{{ $order->number }}</td>
                                    <td>{{ $order->full_name }}<div class="meta">{{ $order->student_id }}</div></td>
                                    <td>
                                        <x-admin.status-pill :status="$order->status" />
                                        @if ($order->status === OrderStatus::Shipped && blank($order->parcel_number))
                                            <div class="meta">รอเลขพัสดุ</div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section
            class="panel fulfill-detail"
            x-data
            x-effect="$wire.id && $nextTick(() => $refs.parcelNumber?.focus())"
        >
            <div class="panel-head">
                <span class="row row-tight">
                    <x-icon name="clipboard-document-list" size="sm" />
                    รายละเอียด
                </span>
                @if ($selected)
                    <x-admin.status-pill :status="$selected->status" />
                @endif
            </div>
            <div class="panel-body stack" wire:key="fulfill-detail-{{ $selected?->id ?? 'none' }}">
                @if ($selected)
                    <div class="fulfill-identity">
                        <p class="mono fulfill-number">{{ $selected->number }}</p>
                        <p>{{ $selected->full_name }}</p>
                    </div>

                    <dl class="detail-list">
                        <div>
                            <dt>รหัสนักศึกษา</dt>
                            <dd class="mono">{{ $selected->student_id }}</dd>
                        </div>
                        <div>
                            <dt>โทร</dt>
                            <dd class="mono">{{ $selected->phone }}</dd>
                        </div>
                        @if ($selected->faculty)
                            <div>
                                <dt>คณะ</dt>
                                <dd>{{ $selected->faculty }}</dd>
                            </div>
                        @endif
                        @if ($selected->address)
                            <div>
                                <dt>ที่อยู่จัดส่ง</dt>
                                <dd class="address">{{ $selected->address }}</dd>
                            </div>
                        @endif
                        @if ($selected->packed_at)
                            <div>
                                <dt>แพ็คแล้ว</dt>
                                <dd class="mono">{{ $selected->packed_at->toThaiDatetime() }}</dd>
                            </div>
                        @endif
                        @if (! $canShip && ! $canUpdateParcel)
                            <div>
                                <dt>เลขพัสดุ</dt>
                                <dd class="mono">{{ $selected->parcel_number ?: 'ไม่มี' }}</dd>
                            </div>
                        @endif
                    </dl>

                    @if ($selected->items->isNotEmpty())
                        <ul class="fulfill-items">
                            @foreach ($selected->items as $item)
                                <li wire:key="fulfill-item-{{ $item->id }}">
                                    {{ $item->name }} × {{ $item->qty }}
                                    @if (($item->choices ?? []) !== [])
                                        <ul class="choice-list">
                                            @foreach ($item->choices as $index => $choice)
                                                <li wire:key="fulfill-choice-{{ $item->id }}-{{ $index }}">
                                                    {{ $choice['label'] ?? '' }} · {{ $choice['value'] ?? '' }}
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @if ($selected->statusChanges->isNotEmpty())
                        <ol class="timeline fulfill-timeline">
                            @foreach ($selected->statusChanges as $change)
                                <li @class(['is-latest' => $loop->first]) wire:key="fulfill-change-{{ $change->id }}">
                                    <div>{{ $change->to_status->label() }}</div>
                                    <div class="muted">{{ $change->created_at?->toThaiDatetime() }} · {{ $change->user?->name }}</div>
                                </li>
                            @endforeach
                        </ol>
                    @endif

                    @if ($canShip || $canUpdateParcel)
                        <div class="fulfill-action">
                            <p class="field-caption">เลขพัสดุ</p>
                            <div @class(['fulfill-parcel-bar', 'is-invalid' => $errors->has('parcel_number')])>
                                <input
                                    class="input"
                                    type="text"
                                    x-ref="parcelNumber"
                                    wire:model="parcelNumber"
                                    @if ($canShip)
                                        wire:keydown.enter.prevent="markShipped"
                                    @else
                                        wire:keydown.enter.prevent="saveParcelNumber"
                                    @endif
                                    placeholder="เลขพัสดุ"
                                    aria-label="เลขพัสดุ"
                                    autocomplete="off"
                                    spellcheck="false"
                                >
                                @if ($canShip)
                                    <button
                                        type="button"
                                        class="btn btn-primary"
                                        wire:click="markShipped"
                                        wire:loading.attr="disabled"
                                        wire:target="markShipped"
                                    >
                                        <x-icon name="check" size="sm" />
                                        <span wire:loading.remove wire:target="markShipped">จัดส่งแล้ว</span>
                                        <span wire:loading wire:target="markShipped">กำลังบันทึก…</span>
                                    </button>
                                @endif
                                @if ($canUpdateParcel)
                                    <button
                                        type="button"
                                        class="btn btn-primary"
                                        wire:click="saveParcelNumber"
                                        wire:loading.attr="disabled"
                                        wire:target="saveParcelNumber"
                                    >
                                        <x-icon name="check" size="sm" />
                                        <span wire:loading.remove wire:target="saveParcelNumber">บันทึกเลขพัสดุ</span>
                                        <span wire:loading wire:target="saveParcelNumber">กำลังบันทึก…</span>
                                    </button>
                                @endif
                            </div>
                            @error('parcel_number') <span class="error packing-scan-error">{{ $message }}</span> @enderror
                            <div class="packing-scan-meta">
                                <p class="muted packing-scan-hint">
                                    @if ($canShip)
                                        กด Enter เพื่อจัดส่ง ไม่บังคับ
                                    @else
                                        กด Enter เพื่อบันทึก
                                    @endif
                                </p>
                                <a class="btn btn-ghost" href="{{ route('admin.orders.show', $selected) }}">ดูออเดอร์</a>
                            </div>
                        </div>
                    @else
                        <div class="row">
                            <a class="btn btn-ghost" href="{{ route('admin.orders.show', $selected) }}">ดูออเดอร์</a>
                        </div>
                    @endif
                @else
                    <div class="empty fulfill-empty">
                        <x-icon name="queue-list" size="lg" />
                        <p>เลือกแถวทางซ้าย</p>
                    </div>
                @endif
            </div>
        </section>
    </div>
</div>
