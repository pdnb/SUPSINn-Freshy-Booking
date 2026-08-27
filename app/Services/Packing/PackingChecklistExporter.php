<?php

namespace App\Services\Packing;

use App\Enums\FulfillmentMethod;
use App\Models\BookingRound;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Http\Response;

class PackingChecklistExporter
{
    public function __construct(
        private PackingChecklistService $checklist,
        private ViewFactory $views,
    ) {}

    /**
     * @param  array{booking_round_id?: int|string|null, fulfillment?: string|FulfillmentMethod|null, faculty?: string|null}  $filters
     */
    public function filename(array $filters = []): string
    {
        $round = filled($filters['booking_round_id'] ?? null)
            ? BookingRound::query()->find($filters['booking_round_id'])?->name
            : 'ทุกรอบ';

        $faculty = filled($filters['faculty'] ?? null) ? $filters['faculty'] : 'ทุกคณะ';

        return 'แพ็คของ-'.$this->safeSegment((string) $round).'-'.$this->safeSegment((string) $faculty).'-'.$this->safeSegment($this->channelLabel($filters)).'.pdf';
    }

    /**
     * @param  array{booking_round_id?: int|string|null, fulfillment?: string|FulfillmentMethod|null, faculty?: string|null}  $filters
     */
    public function pdf(array $filters = []): Response
    {
        $orders = $this->checklist->orders($filters);
        $round = filled($filters['booking_round_id'] ?? null)
            ? BookingRound::query()->find($filters['booking_round_id'])
            : null;

        $html = $this->views->make('admin.packing-checklist.pdf', [
            'orders' => $orders,
            'roundName' => $round?->name ?? 'ทุกรอบ',
            'faculty' => filled($filters['faculty'] ?? null) ? $filters['faculty'] : 'ทุกคณะ',
            'channelLabel' => $this->channelLabel($filters),
            'fontPath' => str_replace('\\', '/', resource_path('fonts/Sarabun-Regular.ttf')),
        ])->render();

        $fontCache = storage_path('fonts');

        if (! is_dir($fontCache)) {
            mkdir($fontCache, 0755, true);
        }

        $pdf = Pdf::loadHTML($html)->setPaper('a4', 'portrait');
        $options = $pdf->getDomPDF()->getOptions();
        $options->setChroot([base_path(), $fontCache]);
        $options->setFontDir($fontCache);
        $options->setFontCache($fontCache);

        return $pdf->download($this->filename($filters));
    }

    /**
     * @param  array{fulfillment?: string|FulfillmentMethod|null}  $filters
     */
    private function channelLabel(array $filters): string
    {
        $fulfillment = $filters['fulfillment'] ?? null;

        if ($fulfillment instanceof FulfillmentMethod) {
            return $fulfillment->label();
        }

        if (! is_string($fulfillment) || $fulfillment === '') {
            return 'ทุกช่องทาง';
        }

        return FulfillmentMethod::tryFrom($fulfillment)?->label() ?? 'ทุกช่องทาง';
    }

    private function safeSegment(string $value): string
    {
        return str_replace(['/', '\\'], '-', $value);
    }
}
