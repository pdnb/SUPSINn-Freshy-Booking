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

    public string $packNumber = '';

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
        $filters = $this->filters();

        return $this->view([
            'orders' => $checklist->orders($filters),
            'packedToday' => $checklist->packedToday(),
            'rounds' => $rounds->list(),
            'channels' => $checklist->channels(),
            'faculties' => $checklist->faculties($filters),
        ]);
    }
};
?>

<div>
    <div class="page-head">
        <div>
            <h1>แพ็คของ</h1>
            <p class="sub">พิมพ์ checklist หนึ่งออเดอร์ต่อหน้า สำหรับโต๊ะแพ็ค</p>
        </div>
        <div class="row row-tight">
            <a class="btn btn-secondary" href="{{ route('admin.packing-checklist.export', $this->filters()) }}">
                <x-icon name="arrow-down-tray" size="sm" />
                PDF
            </a>
        </div>
    </div>

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

    <div
        class="toolbar"
        x-data
        x-on:admin-toast.window="$nextTick(() => $refs.packNumber?.focus())"
    >
        <div class="filters filters-align-start">
            <div class="field" style="max-width:260px">
                <input
                    class="input @error('packNumber') is-invalid @enderror"
                    type="text"
                    x-ref="packNumber"
                    wire:model="packNumber"
                    wire:keydown.enter.prevent="markPacked"
                    placeholder="รหัสออเดอร์"
                    aria-label="รหัสออเดอร์"
                    autocomplete="off"
                    autofocus
                >
                @error('packNumber') <span class="error">{{ $message }}</span> @enderror
            </div>
            <div class="row row-tight">
                <button type="button" class="btn btn-primary" wire:click="markPacked">แพ็คแล้ว</button>
                <button type="button" class="btn btn-ghost" wire:click="unmarkPacked">ยกเลิกแพ็คแล้ว</button>
            </div>
        </div>
    </div>

    <div class="stack">
        <section class="panel">
            @if ($orders->isEmpty())
                <p class="empty">ไม่มีออเดอร์ในตัวกรองนี้</p>
            @else
                <table class="ds-table">
                    <thead>
                        <tr>
                            <th>รหัส</th>
                            <th>ชื่อ</th>
                            <th>รหัสนักศึกษา</th>
                            <th>ช่องทาง</th>
                            <th>คณะ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($orders as $order)
                            <tr wire:key="pack-{{ $order->id }}">
                                <td class="mono">{{ $order->number }}</td>
                                <td>{{ $order->full_name }}</td>
                                <td class="mono">{{ $order->student_id }}</td>
                                <td>{{ $order->fulfillment->label() }}</td>
                                <td>{{ $order->faculty }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>

        <section class="panel">
            <div class="panel-head">แพ็คแล้ววันนี้</div>
            @if ($packedToday->isEmpty())
                <p class="empty">ยังไม่มีออเดอร์ที่แพ็ควันนี้</p>
            @else
                <table class="ds-table">
                    <thead>
                        <tr>
                            <th>รหัส</th>
                            <th>ชื่อ</th>
                            <th>รหัสนักศึกษา</th>
                            <th>ช่องทาง</th>
                            <th>คณะ</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($packedToday as $order)
                            <tr wire:key="packed-{{ $order->id }}">
                                <td class="mono">{{ $order->number }}</td>
                                <td>{{ $order->full_name }}</td>
                                <td class="mono">{{ $order->student_id }}</td>
                                <td>{{ $order->fulfillment->label() }}</td>
                                <td>{{ $order->faculty }}</td>
                                <td>
                                    <button type="button" class="btn btn-ghost" wire:click="unmarkPacked('{{ $order->number }}')">ยกเลิก</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>
    </div>
</div>
