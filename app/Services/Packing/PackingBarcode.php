<?php

namespace App\Services\Packing;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Picqer\Barcode\Renderers\PngRenderer;
use Picqer\Barcode\Types\TypeCode128;

class PackingBarcode
{
    public function dataUri(string $number): string
    {
        $barcode = (new TypeCode128)->getBarcode($number);
        $png = (new PngRenderer)->render($barcode, $barcode->getWidth() * 2, 40);

        return 'data:image/png;base64,'.base64_encode($png);
    }

    public function qrDataUri(string $number): string
    {
        return (new Builder(
            writer: new PngWriter,
            data: $number,
            size: 120,
            margin: 4,
        ))->build()->getDataUri();
    }
}
