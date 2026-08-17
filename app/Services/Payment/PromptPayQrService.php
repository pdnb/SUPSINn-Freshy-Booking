<?php

namespace App\Services\Payment;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\SvgWriter;
use InvalidArgumentException;
use phumin\PromptParse\Generate\PromptPay;
use phumin\PromptParse\Library\TLV;

final class PromptPayQrService
{
    public function payload(float $amount): string
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('PromptPay amount must be greater than zero.');
        }

        $amount = round($amount, 2);
        $staticPayload = trim((string) config('booking.promptpay_qr_payload'));

        if ($staticPayload !== '') {
            return $this->billPaymentFromStaticPayload($staticPayload, $amount);
        }

        $target = preg_replace('/\D+/', '', (string) config('booking.promptpay_id')) ?? '';

        if ($target === '') {
            throw new InvalidArgumentException('PromptPay ID is not configured.');
        }

        return PromptPay::anyId(
            $this->proxyType($target),
            $target,
            $amount,
        );
    }

    public function dataUri(float $amount): string
    {
        $result = new Builder(
            writer: new SvgWriter,
            data: $this->payload($amount),
            size: 192,
            margin: 8,
        )->build();

        return $result->getDataUri();
    }

    private function billPaymentFromStaticPayload(string $staticPayload, float $amount): string
    {
        $fields = $this->extractBillPaymentFields($staticPayload);

        return PromptPay::billPayment(
            $fields['biller_id'],
            $fields['ref1'],
            $fields['ref2'],
            $fields['ref3'],
            $amount,
        );
    }

    /**
     * Tag 59 (merchant name) may use multi-byte Thai that breaks full-payload TLV
     * length parsing, so only Tag 30 (ASCII bill-payment account) is extracted.
     *
     * @return array{biller_id: string, ref1: string, ref2: ?string, ref3: ?string}
     */
    private function extractBillPaymentFields(string $payload): array
    {
        if (! preg_match('/30(\d{2})/', $payload, $match, PREG_OFFSET_CAPTURE)) {
            throw new InvalidArgumentException('PromptPay QR payload is missing bill payment Tag 30.');
        }

        $length = (int) $match[1][0];
        $valueStart = $match[0][1] + 4;
        $tag30Value = substr($payload, $valueStart, $length);

        if (strlen($tag30Value) !== $length) {
            throw new InvalidArgumentException('PromptPay QR payload Tag 30 is truncated.');
        }

        $subTags = collect(TLV::decode($tag30Value))->keyBy('id');
        $billerId = (string) ($subTags->get('01')?->value ?? '');
        $ref1 = (string) ($subTags->get('02')?->value ?? '');

        if ($billerId === '' || $ref1 === '') {
            throw new InvalidArgumentException('PromptPay QR payload Tag 30 is incomplete.');
        }

        $ref2 = $subTags->get('03')?->value;
        $ref3 = $subTags->get('04')?->value;

        return [
            'biller_id' => $billerId,
            'ref1' => $ref1,
            'ref2' => is_string($ref2) && $ref2 !== '' ? $ref2 : null,
            'ref3' => is_string($ref3) && $ref3 !== '' ? $ref3 : null,
        ];
    }

    private function proxyType(string $target): string
    {
        if (strlen($target) === 13) {
            return PromptPay::PROXY_TYPE_NATID;
        }

        return PromptPay::PROXY_TYPE_MSISDN;
    }
}
