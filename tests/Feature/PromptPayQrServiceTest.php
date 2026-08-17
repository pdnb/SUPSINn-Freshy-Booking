<?php

use App\Services\Payment\PromptPayQrService;
use phumin\PromptParse\Parser;

test('it builds a promptpay anyid payload for the configured amount', function () {
    config([
        'booking.promptpay_id' => '0812345678',
        'booking.promptpay_name' => 'ศูนย์หนังสือ มรส.',
        'booking.promptpay_qr_payload' => null,
    ]);

    $payload = app(PromptPayQrService::class)->payload(350.0);
    $parsed = Parser::parse($payload);

    expect($payload)->toStartWith('000201')
        ->and($parsed->getTagValue('54'))->toBe('350.00')
        ->and($parsed->getTagValue('53'))->toBe('764')
        ->and($parsed->getTagValue('58'))->toBe('TH');
});

test('it uses national id proxy type for 13-digit targets', function () {
    config([
        'booking.promptpay_id' => '1234567890123',
        'booking.promptpay_qr_payload' => null,
    ]);

    $payload = app(PromptPayQrService::class)->payload(100.5);
    $parsed = Parser::parse($payload);

    expect($parsed->getTagValue('29', '02'))->toBe('1234567890123')
        ->and($parsed->getTagValue('54'))->toBe('100.50');
});

test('it builds a bill payment payload from the static shop qr', function () {
    config([
        'booking.promptpay_qr_payload' => '00020101021230740016A00000067701011201150107537000882010220085657554009940000150307SRUSHOP53037645802TH5907เป๋าตุง6207070300163049DD0',
    ]);

    $payload = app(PromptPayQrService::class)->payload(500.0);
    $parsed = Parser::parse($payload);

    expect($parsed->getTagValue('01'))->toBe('11')
        ->and($parsed->getTagValue('54'))->toBe('500.00')
        ->and($parsed->getTagValue('30', '01'))->toBe('010753700088201')
        ->and($parsed->getTagValue('30', '02'))->toBe('08565755400994000015')
        ->and($parsed->getTagValue('30', '03'))->toBe('SRUSHOP');
});

test('it returns an svg data uri for the payment qr', function () {
    config([
        'booking.promptpay_id' => '0812345678',
        'booking.promptpay_qr_payload' => null,
    ]);

    $dataUri = app(PromptPayQrService::class)->dataUri(500.0);

    expect($dataUri)->toStartWith('data:image/svg+xml');
});
