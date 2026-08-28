<?php
use App\Services\Booking\BookingRoundService;
use App\Services\Packing\PackingChecklistService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts.admin')]
#[Title('แพ็คของ')]
class extends Component
{
    #[Url]
    public string $booking_round_id = '';

    #[Url]
    public string $fulfillment = '';

    #[Url]
    public string $faculty = '';

    #[Url]
    public string $tab = 'scan';

    public string $packNumber = '';

    public function mount(): void
    {
        $this->normalizeTab();
    }

    public function updatedTab(): void
    {
        $this->normalizeTab();
    }

    public function clearFilters(): void
    {
        $this->booking_round_id = '';
        $this->fulfillment = '';
        $this->faculty = '';
    }

    public function markPacked(PackingChecklistService $checklist): void
    {
        $this->validate([
            'packNumber' => ['required', 'string', 'max:32'],
        ], [
            'packNumber.required' => 'กรอกรหัสออเดอร์',
        ]);

        try {
            $checklist->markPacked($this->packNumber, Auth::user());
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->errors());

            return;
        }

        $this->reset('packNumber');
        $this->resetValidation();
        $this->dispatch('admin-toast', message: 'แพ็คแล้ว');
    }

    public function unmarkPacked(PackingChecklistService $checklist, ?string $number = null): void
    {
        $fromField = $number === null;

        if ($fromField) {
            $this->validate([
                'packNumber' => ['required', 'string', 'max:32'],
            ], [
                'packNumber.required' => 'กรอกรหัสออเดอร์',
            ]);
        }

        try {
            $checklist->unmarkPacked($number ?? $this->packNumber, Auth::user());
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->errors());

            return;
        }

        if ($fromField) {
            $this->reset('packNumber');
        }

        $this->resetValidation();
        $this->dispatch('admin-toast', message: 'ยกเลิกแพ็คแล้ว');
    }

    /**
     * @return array{booking_round_id?: int|string|null, fulfillment?: string|null, faculty?: string|null}
     */
    public function filters(): array
    {
        return [
            'booking_round_id' => $this->booking_round_id !== '' ? $this->booking_round_id : null,
            'fulfillment' => $this->fulfillment !== '' ? $this->fulfillment : null,
            'faculty' => $this->faculty !== '' ? $this->faculty : null,
        ];
    }

    public function render(PackingChecklistService $checklist, BookingRoundService $rounds)
    {
        $this->normalizeTab();

        $filters = $this->filters();

        return $this->view([
            'orders' => $checklist->orders($filters),
            'packedToday' => $checklist->packedToday(),
            'rounds' => $rounds->list(),
            'channels' => $checklist->channels(),
            'faculties' => $checklist->faculties($filters),
        ]);
    }

    private function normalizeTab(): void
    {
        if ($this->tab !== 'print') {
            $this->tab = 'scan';
        }
    }
};
?>

<div>
    @php
        $hasActiveFilters = $booking_round_id !== '' || $fulfillment !== '' || $faculty !== '';
        $ordersByChannel = $orders->groupBy(fn ($order) => $order->fulfillment->value);
    @endphp

    <div class="page-head">
        <div>
            <h1>แพ็คของ</h1>
            <p class="sub">พิมพ์ checklist หนึ่งออเดอร์ต่อหน้า สำหรับโต๊ะแพ็ค</p>
        </div>
        @if ($tab === 'print')
            <div class="row row-tight">
                <a class="btn btn-secondary" href="{{ route('admin.packing-checklist.export', $this->filters()) }}">
                    <x-icon name="arrow-down-tray" size="sm" />
                    PDF · {{ $orders->count() }} ใบ
                </a>
            </div>
        @endif
    </div>

    <div class="tabs" role="tablist">
        <button
            type="button"
            class="tab {{ $tab === 'scan' ? 'is-active' : '' }}"
            wire:click="$set('tab', 'scan')"
            role="tab"
            aria-selected="{{ $tab === 'scan' ? 'true' : 'false' }}"
        >
            สแกน
        </button>
        <button
            type="button"
            class="tab {{ $tab === 'print' ? 'is-active' : '' }}"
            wire:click="$set('tab', 'print')"
            role="tab"
            aria-selected="{{ $tab === 'print' ? 'true' : 'false' }}"
        >
            พิมพ์
        </button>
    </div>

    @if ($tab === 'scan')
        <section
            class="panel packing-scan"
            x-data
            x-init="$nextTick(() => $refs.packNumber?.focus())"
            x-on:admin-toast.window="$nextTick(() => $refs.packNumber?.focus())"
        >
            <div class="panel-head">
                <span class="row row-tight">
                    <x-icon name="qr-code" size="sm" />
                    สแกนรหัสออเดอร์
                </span>
            </div>
            <div class="panel-body">
                <div @class(['packing-scan-bar', 'is-invalid' => $errors->has('packNumber')])>
                    <input
                        class="input"
                        type="text"
                        x-ref="packNumber"
                        wire:model="packNumber"
                        wire:keydown.enter.prevent="markPacked"
                        placeholder="รหัสออเดอร์"
                        aria-label="รหัสออเดอร์"
                        aria-describedby="packing-scan-hint"
                        autocomplete="off"
                        spellcheck="false"
                        autofocus
                    >
                    <button
                        type="button"
                        class="btn btn-primary"
                        wire:click="markPacked"
                        wire:loading.attr="disabled"
                        wire:target="markPacked"
                    >
                        <x-icon name="check" size="sm" />
                        <span wire:loading.remove wire:target="markPacked">แพ็คแล้ว</span>
                        <span wire:loading wire:target="markPacked">กำลังแพ็ค…</span>
                    </button>
                </div>
                @error('packNumber') <span class="error packing-scan-error">{{ $message }}</span> @enderror
                <div class="packing-scan-meta">
                    <p id="packing-scan-hint" class="muted packing-scan-hint">กด Enter เพื่อแพ็ค</p>
                    <button
                        type="button"
                        class="btn btn-ghost"
                        wire:click="unmarkPacked"
                        wire:loading.attr="disabled"
                        wire:target="unmarkPacked"
                    >
                        <x-icon name="arrow-uturn-left" size="sm" />
                        <span wire:loading.remove wire:target="unmarkPacked">ยกเลิกแพ็คแล้ว</span>
                        <span wire:loading wire:target="unmarkPacked">กำลังยกเลิก…</span>
                    </button>
                </div>
            </div>
        </section>

        <section class="panel">
            <div class="panel-head">
                <span>แพ็คแล้ววันนี้</span>
                <span class="meta">{{ $packedToday->count() }}</span>
            </div>
            @if ($packedToday->isEmpty())
                <div class="empty packing-empty">
                    <x-icon name="clipboard-document-check" size="lg" />
                    <p>ยังไม่มีออเดอร์ที่แพ็ควันนี้</p>
                </div>
            @else
                <table class="ds-table">
                    <thead>
                        <tr>
                            <th>รหัส</th>
                            <th>ผู้จอง</th>
                            <th>คณะ</th>
                            <th>ช่องทาง</th>
                            <th>เวลา</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($packedToday as $order)
                            <tr wire:key="packed-{{ $order->id }}">
                                <td class="mono">{{ $order->number }}</td>
                                <td>
                                    {{ $order->full_name }}
                                    <div class="meta">{{ $order->student_id }}</div>
                                </td>
                                <td>{{ $order->faculty }}</td>
                                <td>{{ $order->fulfillment->label() }}</td>
                                <td class="mono">{{ $order->packed_at?->toThaiDatetime() }}</td>
                                <td>
                                    <button
                                        type="button"
                                        class="btn btn-ghost btn-sm"
                                        wire:click="unmarkPacked('{{ $order->number }}')"
                                        wire:loading.attr="disabled"
                                        wire:target="unmarkPacked"
                                        aria-label="ยกเลิกแพ็ค {{ $order->number }}"
                                    >
                                        ยกเลิก
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>
    @elseif ($tab === 'print')
        <div class="toolbar">
            <div class="filters">
                <select class="select" wire:model.live="booking_round_id" aria-label="รอบจอง" style="max-width:240px">
                    <option value="">ทุกรอบ</option>
                    @foreach ($rounds as $round)
                        <option value="{{ $round->id }}">{{ $round->name }}</option>
                    @endforeach
                </select>
                <select class="select" wire:model.live="fulfillment" aria-label="ช่องทาง" style="max-width:280px">
                    <option value="">ทุกช่องทาง</option>
                    @foreach ($channels as $channel)
                        <option value="{{ $channel->value }}">{{ $channel->label() }}</option>
                    @endforeach
                </select>
                <select class="select" wire:model.live="faculty" aria-label="คณะ" style="max-width:280px">
                    <option value="">ทุกคณะ</option>
                    @foreach ($faculties as $name)
                        <option value="{{ $name }}">{{ $name }}</option>
                    @endforeach
                </select>
                <button type="button" class="btn btn-ghost" wire:click="clearFilters">ล้างตัวกรอง</button>
            </div>
        </div>

        <section class="panel">
            <div class="panel-head">
                <span>ออเดอร์</span>
                <span class="meta">{{ $orders->count() }}</span>
            </div>
            @if ($orders->isEmpty())
                <div class="empty packing-empty">
                    <x-icon name="inbox" size="lg" />
                    <p>ไม่มีออเดอร์ในตัวกรองนี้</p>
                    @if ($hasActiveFilters)
                        <button type="button" class="btn btn-ghost" wire:click="clearFilters">ล้างตัวกรอง</button>
                    @endif
                </div>
            @else
                <table class="ds-table">
                    <thead>
                        <tr>
                            <th>รหัส</th>
                            <th>ผู้จอง</th>
                            <th>คณะ</th>
                            <th class="num-col">จำนวน</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($ordersByChannel as $channel => $channelOrders)
                            <tr class="packing-group" wire:key="pack-group-{{ $channel }}">
                                <th scope="colgroup" colspan="4">
                                    {{ $channelOrders->first()->fulfillment->label() }}
                                    <span class="meta">{{ $channelOrders->count() }}</span>
                                </th>
                            </tr>
                            @foreach ($channelOrders as $order)
                                <tr wire:key="pack-{{ $order->id }}">
                                    <td class="mono">{{ $order->number }}</td>
                                    <td>
                                        {{ $order->full_name }}
                                        <div class="meta">{{ $order->student_id }}</div>
                                    </td>
                                    <td>{{ $order->faculty }}</td>
                                    <td class="num-col">{{ $order->items->sum('qty') }}</td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>
    @endif
</div>
