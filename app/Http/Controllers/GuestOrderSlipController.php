<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\Order\OrderService;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class GuestOrderSlipController extends Controller
{
    public function __construct(private OrderService $orders) {}

    public function __invoke(Order $order): BinaryFileResponse
    {
        $tracked = $this->orders->trackedGuestOrder();

        abort_if($tracked === null || $tracked->isNot($order), 404);

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
