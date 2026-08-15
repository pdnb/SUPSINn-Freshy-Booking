<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OrderSlipController extends Controller
{
    public function __invoke(Order $order): BinaryFileResponse
    {
        $slip = $order->slip;

        abort_if($slip === null || ! Storage::disk('local')->exists($slip->path), 404);

        $filename = str_replace(['\\', '"', "\r", "\n"], '', basename($slip->original_name));

        return response()->file(Storage::disk('local')->path($slip->path), [
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
