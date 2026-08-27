<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Packing\PackingChecklistExporter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PackingExportController extends Controller
{
    public function __invoke(Request $request, PackingChecklistExporter $exporter): Response
    {
        return $exporter->pdf([
            'booking_round_id' => $request->query('booking_round_id'),
            'fulfillment' => $request->query('fulfillment'),
            'faculty' => $request->query('faculty'),
        ]);
    }
}
