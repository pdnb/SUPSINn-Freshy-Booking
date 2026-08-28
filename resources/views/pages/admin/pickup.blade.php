<?php
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\Order\OrderService;
use Illuminate\Support\Collection;
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

    public function updatedSearch(): void
    {
        $this->resetErrorBag();
    }

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
            $this->dispatch('admin-toast', message: 'บันทึกเก็บส่วนที่เหลือแล้ว');
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
            $this->dispatch('admin-toast', message: 'รับของแล้ว และออกใบเสร็จแล้ว');
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

        $this->syncSelection($results);

        $selected = $this->selected();

        return $this->view([
            'results' => $results,
            'selected' => $selected,
            'canCollect' => $selected !== null && $selected->hasOutstandingBalance(),
            'canPickUp' => $selected !== null
                && $selected->status === OrderStatus::ReadyForPickup
                && ! $selected->hasOutstandingBalance(),
        ]);
    }

    /**
     * @param  Collection<int, Order>  $results
     */
    private function syncSelection(Collection $results): void
    {
        if ($results->count() === 1) {
            $this->selectedId = $results->first()->id;

            return;
        }

        if ($this->search === '' || ($this->selectedId !== null && $results->doesntContain('id', $this->selectedId))) {
            $this->selectedId = null;
        }
    }

    private function selected(): ?Order
    {
        if ($this->selectedId === null) {
            return null;
        }

        return Order::query()
            ->with(['items', 'bookingRound', 'statusChanges.user'])
            ->find($this->selectedId);
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
        <div class="filters" x-data x-init="$nextTick(() => $refs.pickupSearch?.focus())">
            <input
                class="input"
                type="search"
                x-ref="pickupSearch"
                wire:model.live.debounce.300ms="search"
                placeholder="รหัสออเดอร์ รหัสนักศึกษา ชื่อ หรือเบอร์โทร"
                aria-label="ค้นหาออเดอร์"
                autocomplete="off"
                spellcheck="false"
                autofocus
                style="max-width:420px; width:100%"
            >
            <button type="button" class="btn btn-ghost" wire:click="clearFilters">เคลียร์</button>
        </div>
    </div>

    <div class="fulfill-split">
        <section class="panel">
            <div class="panel-head">
                <span class="row row-tight">
                    <x-icon name="magnifying-glass" size="sm" />
                    ผลการค้นหา
                </span>
                <span class="meta">{{ $results->count() }}</span>
            </div>
            @if ($search === '')
                <div class="empty fulfill-empty">
                    <x-icon name="magnifying-glass" size="lg" />
                    <p>พิมพ์เพื่อค้นหา</p>
                </div>
            @elseif ($results->isEmpty())
                <div class="empty fulfill-empty">
                    <x-icon name="inbox" size="lg" />
                    <p>ไม่พบออเดอร์</p>
                </div>
            @else
                <div class="fulfill-table-wrap">
                    <table class="ds-table">
                        <thead>
                            <tr>
                                <th>ออเดอร์</th>
                                <th>ผู้จอง</th>
                                <th>จุดรับ</th>
                                <th>สถานะ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($results as $order)
                                <tr
                                    @class(['is-clickable', 'is-selected' => $selectedId === $order->id])
                                    wire:key="pickup-{{ $order->id }}"
                                    wire:click="select({{ $order->id }})"
                                    aria-selected="{{ $selectedId === $order->id ? 'true' : 'false' }}"
                                >
                                    <td class="mono">{{ $order->number }}</td>
                                    <td>
                                        {{ $order->full_name }}
                                        <div class="meta">{{ $order->student_id }}</div>
                                    </td>
                                    <td>{{ $order->fulfillment->label() }}</td>
                                    <td>
                                        <x-admin.status-pill :status="$order->status" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="panel fulfill-detail pickup-detail">
            <div class="panel-head">
                <span class="row row-tight">
                    <x-icon name="clipboard-document-list" size="sm" />
                    รายละเอียด
                </span>
                @if ($selected)
                    <x-admin.status-pill :status="$selected->status" />
                @endif
            </div>
            <div class="panel-body stack" wire:key="pickup-detail-{{ $selected?->id ?? 'none' }}">
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
                        <div>
                            <dt>จุดรับ</dt>
                            <dd>{{ $selected->fulfillment->label() }}</dd>
                        </div>
                        @if ($selected->packed_at)
                            <div>
                                <dt>แพ็คแล้ว</dt>
                                <dd class="mono">{{ $selected->packed_at->toThaiDatetime() }}</dd>
                            </div>
                        @endif
                    </dl>

                    @if ($selected->items->isNotEmpty())
                        <ul class="fulfill-items">
                            @foreach ($selected->items as $item)
                                <li wire:key="pickup-item-{{ $item->id }}">
                                    {{ $item->name }} × {{ $item->qty }}
                                    @if (($item->choices ?? []) !== [])
                                        <ul class="choice-list">
                                            @foreach ($item->choices as $index => $choice)
                                                <li wire:key="pickup-choice-{{ $item->id }}-{{ $index }}">
                                                    {{ $choice['label'] ?? '' }} · {{ $choice['value'] ?? '' }}
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    <dl class="totals-list">
                        <div class="is-total">
                            <dt>ยอดสุทธิ</dt>
                            <dd class="mono">{{ number_format((float) $selected->total, 0) }} บาท</dd>
                        </div>
                        <div>
                            <dt>โหมดชำระ</dt>
                            <dd>{{ $selected->payment_mode->label() }}</dd>
                        </div>
                        <div>
                            <dt>จ่ายตอนสั่ง</dt>
                            <dd class="mono">{{ number_format((float) $selected->amount_due_now, 0) }} บาท</dd>
                        </div>
                        @if ((float) $selected->amount_remaining > 0)
                            <div>
                                <dt>คงเหลือตอนรับ</dt>
                                <dd class="mono">{{ number_format((float) $selected->amount_remaining, 0) }} บาท</dd>
                            </div>
                            <div>
                                <dt>สถานะเก็บเงิน</dt>
                                <dd>{{ $selected->balance_collected_at ? 'เก็บครบแล้ว' : 'ยังไม่เก็บ' }}</dd>
                            </div>
                        @endif
                    </dl>

                    @if ($selected->statusChanges->isNotEmpty())
                        <ol class="timeline fulfill-timeline">
                            @foreach ($selected->statusChanges as $change)
                                <li @class(['is-latest' => $loop->first]) wire:key="pickup-change-{{ $change->id }}">
                                    <div>{{ $change->to_status->label() }}</div>
                                    <div class="muted">{{ $change->created_at?->toThaiDatetime() }} · {{ $change->user?->name }}</div>
                                </li>
                            @endforeach
                        </ol>
                    @endif

                    @error('status') <span class="error">{{ $message }}</span> @enderror
                    @error('balance') <span class="error">{{ $message }}</span> @enderror

                    @if (! $canCollect && ! $canPickUp)
                        @if ($selected->status !== OrderStatus::Completed)
                            <p class="muted">สถานะนี้ยังไม่รับของหน้างาน</p>
                        @endif
                        <div class="row">
                            <a class="btn btn-ghost" href="{{ route('admin.orders.show', $selected) }}">ดูออเดอร์</a>
                        </div>
                    @endif
                @elseif ($search === '')
                    <div class="empty fulfill-empty">
                        <x-icon name="queue-list" size="lg" />
                        <p>พิมพ์เพื่อค้นหา</p>
                    </div>
                @elseif ($results->isEmpty())
                    <div class="empty fulfill-empty">
                        <x-icon name="inbox" size="lg" />
                        <p>ไม่พบออเดอร์</p>
                    </div>
                @else
                    <div class="empty fulfill-empty">
                        <x-icon name="queue-list" size="lg" />
                        <p>เลือกแถวทางซ้าย</p>
                    </div>
                @endif
            </div>
            @if ($canCollect)
                <div class="fulfill-action pickup-detail-action">
                    <div class="pickup-due">
                        <p class="field-caption">ต้องเก็บส่วนที่เหลือ</p>
                        <p class="mono pickup-due-amount">{{ number_format((float) $selected->amount_remaining, 0) }} บาท</p>
                    </div>
                    <button
                        type="button"
                        class="btn btn-primary pickup-action-btn"
                        wire:click="collectBalance"
                        wire:loading.attr="disabled"
                        wire:target="collectBalance"
                    >
                        <x-icon name="banknotes" size="sm" />
                        <span wire:loading.remove wire:target="collectBalance">บันทึกเก็บส่วนที่เหลือ</span>
                        <span wire:loading wire:target="collectBalance">กำลังบันทึก…</span>
                    </button>
                    <div class="packing-scan-meta">
                        <p class="muted packing-scan-hint">เก็บเงินสดหรือโอนก่อนมาร์ครับของ</p>
                        <a class="btn btn-ghost" href="{{ route('admin.orders.show', $selected) }}">ดูออเดอร์</a>
                    </div>
                </div>
            @elseif ($canPickUp)
                <div class="fulfill-action pickup-detail-action">
                    <button
                        type="button"
                        class="btn btn-primary pickup-action-btn"
                        wire:click="markPickedUp"
                        wire:loading.attr="disabled"
                        wire:target="markPickedUp"
                    >
                        <x-icon name="check" size="sm" />
                        <span wire:loading.remove wire:target="markPickedUp">รับของแล้ว</span>
                        <span wire:loading wire:target="markPickedUp">กำลังบันทึก…</span>
                    </button>
                    <div class="packing-scan-meta">
                        <p class="muted packing-scan-hint">กดแล้วจะออกใบเสร็จ</p>
                        <a class="btn btn-ghost" href="{{ route('admin.orders.show', $selected) }}">ดูออเดอร์</a>
                    </div>
                </div>
            @endif
        </section>
    </div>
</div>
