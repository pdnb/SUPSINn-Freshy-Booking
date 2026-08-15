<?php

namespace App\Services\Production;

use App\Models\BookingRound;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Http\Response;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductionSummaryExporter
{
    public function __construct(
        private ProductionSummaryService $summary,
        private ViewFactory $views,
    ) {}

    /**
     * @param  array{booking_round_id?: int|string|null, faculty?: string|null}  $filters
     */
    public function filename(string $extension, array $filters = []): string
    {
        $round = filled($filters['booking_round_id'] ?? null)
            ? BookingRound::query()->find($filters['booking_round_id'])?->name
            : 'ทุกรอบ';

        $faculty = filled($filters['faculty'] ?? null) ? $filters['faculty'] : 'ทุกคณะ';

        return 'สรุปยอดผลิต-'.str_replace(['/', '\\'], '-', (string) $round).'-'.str_replace(['/', '\\'], '-', (string) $faculty).'.'.$extension;
    }

    /**
     * @param  array{booking_round_id?: int|string|null, faculty?: string|null}  $filters
     */
    public function csv(array $filters = []): StreamedResponse
    {
        $rows = $this->summary->summarize($filters);
        $filename = $this->filename('csv', $filters);

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\u{FEFF}");
            fputcsv($handle, $this->headers());

            foreach ($rows as $row) {
                fputcsv($handle, $this->values($row));
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @param  array{booking_round_id?: int|string|null, faculty?: string|null}  $filters
     */
    public function xlsx(array $filters = []): StreamedResponse
    {
        $rows = $this->summary->summarize($filters);
        $filename = $this->filename('xlsx', $filters);

        return response()->streamDownload(function () use ($rows): void {
            $path = tempnam(sys_get_temp_dir(), 'prod-xlsx-');
            $writer = new Writer;
            $writer->openToFile($path);
            $writer->addRow(Row::fromValues($this->headers()));

            foreach ($rows as $row) {
                $writer->addRow(Row::fromValues($this->values($row)));
            }

            $writer->close();
            readfile($path);
            unlink($path);
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @param  array{booking_round_id?: int|string|null, faculty?: string|null}  $filters
     */
    public function pdf(array $filters = []): Response
    {
        $rows = $this->summary->summarize($filters);
        $round = filled($filters['booking_round_id'] ?? null)
            ? BookingRound::query()->find($filters['booking_round_id'])
            : null;

        $html = $this->views->make('admin.production-summary.pdf', [
            'rows' => $rows,
            'roundName' => $round?->name ?? 'ทุกรอบ',
            'faculty' => filled($filters['faculty'] ?? null) ? $filters['faculty'] : 'ทุกคณะ',
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

        return $pdf->download($this->filename('pdf', $filters));
    }

    /**
     * @return list<string>
     */
    public function headers(): array
    {
        return ['สินค้า', 'ตัวเลือก', 'ค่า', 'จำนวน'];
    }

    /**
     * @param  array{product_name: string, choice_label: string, choice_value: string, qty: int}  $row
     * @return list<string|int>
     */
    public function values(array $row): array
    {
        return [
            $row['product_name'],
            $row['choice_label'] !== '' ? $row['choice_label'] : '—',
            $row['choice_value'] !== '' ? $row['choice_value'] : '—',
            $row['qty'],
        ];
    }
}
