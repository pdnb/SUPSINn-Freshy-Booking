<?php
use App\Services\Booking\BookingRoundService;
use App\Services\Production\ProductionSummaryService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts.admin')]
#[Title('สรุปยอดผลิต')]
class extends Component
{
    #[Url]
    public string $booking_round_id = '';

    #[Url]
    public string $faculty = '';

    public function clearFilters(): void
    {
        $this->booking_round_id = '';
        $this->faculty = '';
    }

    /**
     * @return array{booking_round_id?: int|string|null, faculty?: string|null}
     */
    public function filters(): array
    {
        return [
            'booking_round_id' => $this->booking_round_id !== '' ? $this->booking_round_id : null,
            'faculty' => $this->faculty !== '' ? $this->faculty : null,
        ];
    }

    public function render(ProductionSummaryService $summary, BookingRoundService $rounds)
    {
        $filters = $this->filters();

        return $this->view([
            'rows' => $summary->summarize($filters),
            'rounds' => $rounds->list(),
            'faculties' => $summary->faculties($filters),
        ]);
    }
};
?>

<div>
    <div class="page-head">
        <div>
            <h1>สรุปยอดผลิต</h1>
            <p class="sub">นับจำนวนต่อสินค้าจากออเดอร์ที่ยืนยันแล้ว</p>
        </div>
        <div class="row row-tight">
            <a class="btn btn-secondary" href="{{ route('admin.production.export', ['format' => 'csv', ...$this->filters()]) }}">
                <x-icon name="arrow-down-tray" size="sm" />
                CSV
            </a>
            <a class="btn btn-secondary" href="{{ route('admin.production.export', ['format' => 'xlsx', ...$this->filters()]) }}">
                <x-icon name="arrow-down-tray" size="sm" />
                Excel
            </a>
            <a class="btn btn-secondary" href="{{ route('admin.production.export', ['format' => 'pdf', ...$this->filters()]) }}">
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
        @if ($rows === [])
            <p class="empty">ยังไม่มียอดผลิตจากออเดอร์ที่ยืนยัน</p>
        @else
            <table class="ds-table">
                <thead>
                    <tr>
                        <th>สินค้า</th>
                        <th>ตัวเลือก</th>
                        <th>ค่า</th>
                        <th class="num-col">จำนวน</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr wire:key="prod-{{ $row['product_id'] }}-{{ $row['choice_label'] }}-{{ $row['choice_value'] }}">
                            <td>{{ $row['product_name'] }}</td>
                            <td>{{ $row['choice_label'] !== '' ? $row['choice_label'] : '—' }}</td>
                            <td>{{ $row['choice_value'] !== '' ? $row['choice_value'] : '—' }}</td>
                            <td class="num-col">{{ $row['qty'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>
</div>
