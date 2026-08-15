<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Production\ProductionSummaryExporter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductionExportController extends Controller
{
    public function __invoke(Request $request, string $format, ProductionSummaryExporter $exporter): StreamedResponse|Response
    {
        $filters = [
            'booking_round_id' => $request->query('booking_round_id'),
            'faculty' => $request->query('faculty'),
        ];

        return match ($format) {
            'csv' => $exporter->csv($filters),
            'xlsx' => $exporter->xlsx($filters),
            'pdf' => $exporter->pdf($filters),
            default => abort(404),
        };
    }
}
