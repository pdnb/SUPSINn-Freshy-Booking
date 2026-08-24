<?php

use App\Services\Booking\BookingRoundService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.admin')]
#[Title('รอบจอง')]
class extends Component
{
    public function render(BookingRoundService $rounds)
    {
        return $this->view([
            'rounds' => $rounds->list(),
        ]);
    }
};
?>

<div>
    <div class="page-head">
        <div>
            <h1>รอบจอง</h1>
            <p class="sub">เปิด-ปิดรอบและผูกสินค้า — ไม่มีกราฟรายได้</p>
        </div>
        <a class="btn btn-primary" href="{{ route('admin.rounds.create') }}">สร้างรอบ</a>
    </div>

    <section class="panel">
        @if ($rounds->isEmpty())
            <p class="empty">ยังไม่มีรอบจอง</p>
        @else
            <table class="ds-table">
                <thead>
                    <tr>
                        <th>ชื่อรอบ</th>
                        <th>เริ่ม</th>
                        <th>สิ้นสุด</th>
                        <th>สถานะ</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rounds as $round)
                        @php
                            $status = $round->effectiveStatus();
                            $pill = match ($status) {
                                'open' => 'pill-active',
                                'scheduled' => 'pill-info',
                                'draft' => 'pill-draft',
                                default => 'pill-neutral',
                            };
                            $label = match ($status) {
                                'open' => 'เปิดอยู่',
                                'scheduled' => 'รอเปิด',
                                'draft' => 'ปิดใช้',
                                default => 'ปิดแล้ว',
                            };
                        @endphp
                        <tr wire:key="round-{{ $round->id }}">
                            <td>
                                <a class="linkish" href="{{ route('admin.rounds.edit', $round) }}">{{ $round->name }}</a>
                            </td>
                            <td class="mono">{{ $round->starts_at?->toThaiDatetime() }}</td>
                            <td class="mono">{{ $round->ends_at?->toThaiDatetime() }}</td>
                            <td><span class="pill {{ $pill }}">{{ $label }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>
</div>
