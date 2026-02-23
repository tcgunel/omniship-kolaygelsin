<?php

declare(strict_types=1);

use Omniship\Common\Enum\LabelFormat;
use Omniship\KolayGelsin\Message\CreateShipmentResponse;

use function Omniship\KolayGelsin\Tests\createMockHttpClient;
use function Omniship\KolayGelsin\Tests\createMockRequestFactory;
use function Omniship\KolayGelsin\Tests\createMockStreamFactory;

function createShipmentResponseWith(array $data): CreateShipmentResponse
{
    $request = new \Omniship\KolayGelsin\Message\CreateShipmentRequest(
        createMockHttpClient(),
        createMockRequestFactory(),
        createMockStreamFactory(),
    );

    return new CreateShipmentResponse($request, $data);
}

it('parses successful response', function () {
    $response = createShipmentResponseWith([
        'ResultCode' => 200,
        'ResultMessage' => 'OK',
        'Payload' => [
            'ShipmentId' => 12345,
            'CustomerSpecificCode' => 'ORD-001',
            'ShipmentTrackingLink' => 'https://esubebeta.klyglsn.com/track/12345',
            'ShipmentItemLabelList' => [],
        ],
    ]);

    expect($response->isSuccessful())->toBeTrue()
        ->and($response->getShipmentId())->toBe('12345')
        ->and($response->getTrackingNumber())->toBe('12345')
        ->and($response->getBarcode())->toBe('ORD-001')
        ->and($response->getTrackingLink())->toBe('https://esubebeta.klyglsn.com/track/12345')
        ->and($response->getMessage())->toBe('OK')
        ->and($response->getCode())->toBe('200');
});

it('parses error response', function () {
    $response = createShipmentResponseWith([
        'ResultCode' => 500.0,
        'ResultMessage' => 'CustomerNotFound',
        'Payload' => null,
    ]);

    expect($response->isSuccessful())->toBeFalse()
        ->and($response->getMessage())->toBe('CustomerNotFound')
        ->and($response->getShipmentId())->toBeNull()
        ->and($response->getCode())->toBe('500');
});

it('returns label when available', function () {
    $response = createShipmentResponseWith([
        'ResultCode' => 200,
        'ResultMessage' => 'OK',
        'Payload' => [
            'ShipmentId' => 12345,
            'ShipmentItemLabelList' => [
                [
                    'ShipmentItemId' => '67890',
                    'CustomerBarcode' => 'ORD-001-1',
                    'ShipmentItemIdLabel' => '<div>Label HTML</div>',
                ],
            ],
        ],
    ]);

    $label = $response->getLabel();

    expect($label)->not->toBeNull()
        ->and($label->content)->toBe('<div>Label HTML</div>')
        ->and($label->format)->toBe(LabelFormat::HTML)
        ->and($label->barcode)->toBe('ORD-001-1')
        ->and($label->shipmentId)->toBe('67890');
});

it('returns null label when no labels available', function () {
    $response = createShipmentResponseWith([
        'ResultCode' => 200,
        'ResultMessage' => 'OK',
        'Payload' => [
            'ShipmentId' => 12345,
            'ShipmentItemLabelList' => [],
        ],
    ]);

    expect($response->getLabel())->toBeNull();
});

it('handles float result code', function () {
    $response = createShipmentResponseWith([
        'ResultCode' => 200.0,
        'ResultMessage' => 'OK',
        'Payload' => ['ShipmentId' => 123],
    ]);

    expect($response->isSuccessful())->toBeTrue();
});

it('returns raw data', function () {
    $data = ['ResultCode' => 200, 'Payload' => null];
    $response = createShipmentResponseWith($data);

    expect($response->getData())->toBe($data);
});
