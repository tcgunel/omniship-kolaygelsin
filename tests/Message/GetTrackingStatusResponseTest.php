<?php

declare(strict_types=1);

use Omniship\Common\Enum\ShipmentStatus;
use Omniship\KolayGelsin\Message\GetTrackingStatusResponse;

use function Omniship\KolayGelsin\Tests\createMockHttpClient;
use function Omniship\KolayGelsin\Tests\createMockRequestFactory;
use function Omniship\KolayGelsin\Tests\createMockStreamFactory;

function createTrackingResponseWith(array $data): GetTrackingStatusResponse
{
    $request = new \Omniship\KolayGelsin\Message\GetTrackingStatusRequest(
        createMockHttpClient(),
        createMockRequestFactory(),
        createMockStreamFactory(),
    );

    return new GetTrackingStatusResponse($request, $data);
}

it('parses delivered shipment response', function () {
    $response = createTrackingResponseWith([
        'ResultCode' => 200,
        'ResultMessage' => 'OK',
        'Payload' => [
            'ShipmentModelList' => [[
                'ShipmentId' => '12345',
                'CustomerBarcode' => 'ORD-001',
                'CargoEventLogModelList' => [[
                    'CargoEventType' => 29,
                    'TimeStamp' => '2024-01-15T14:30:00+03:00',
                    'LocationName' => 'ISTANBUL',
                    'EventDescription' => 'Teslim edildi',
                ]],
            ]],
        ],
    ]);

    expect($response->isSuccessful())->toBeTrue();

    $info = $response->getTrackingInfo();

    expect($info->trackingNumber)->toBe('ORD-001')
        ->and($info->status)->toBe(ShipmentStatus::DELIVERED)
        ->and($info->carrier)->toBe('Kolay Gelsin')
        ->and($info->events)->toHaveCount(1)
        ->and($info->events[0]->status)->toBe(ShipmentStatus::DELIVERED)
        ->and($info->events[0]->location)->toBe('ISTANBUL')
        ->and($info->events[0]->description)->toBe('Teslim edildi');
});

it('parses in-transit response with multiple events', function () {
    $response = createTrackingResponseWith([
        'ResultCode' => 200,
        'ResultMessage' => 'OK',
        'Payload' => [
            'ShipmentModelList' => [[
                'ShipmentId' => '12345',
                'CustomerBarcode' => 'ORD-001',
                'CargoEventLogModelList' => [
                    [
                        'CargoEventType' => 1,
                        'TimeStamp' => '2024-01-14T10:00:00+03:00',
                        'LocationName' => 'ISTANBUL',
                    ],
                    [
                        'CargoEventType' => 12,
                        'TimeStamp' => '2024-01-14T14:00:00+03:00',
                        'LocationName' => 'ISTANBUL',
                    ],
                    [
                        'CargoEventType' => 17,
                        'TimeStamp' => '2024-01-15T08:00:00+03:00',
                        'LocationName' => 'ANKARA',
                    ],
                ],
            ]],
        ],
    ]);

    $info = $response->getTrackingInfo();

    expect($info->events)->toHaveCount(3)
        ->and($info->status)->toBe(ShipmentStatus::PRE_TRANSIT)
        ->and($info->events[0]->status)->toBe(ShipmentStatus::PRE_TRANSIT)
        ->and($info->events[1]->status)->toBe(ShipmentStatus::PICKED_UP)
        ->and($info->events[2]->status)->toBe(ShipmentStatus::IN_TRANSIT);
});

it('parses cancelled response', function () {
    $response = createTrackingResponseWith([
        'ResultCode' => 200,
        'ResultMessage' => 'OK',
        'Payload' => [
            'ShipmentModelList' => [[
                'ShipmentId' => '12345',
                'CustomerBarcode' => 'ORD-001',
                'CargoEventLogModelList' => [[
                    'CargoEventType' => 31,
                    'TimeStamp' => '2024-01-15T09:00:00+03:00',
                ]],
            ]],
        ],
    ]);

    $info = $response->getTrackingInfo();

    expect($info->status)->toBe(ShipmentStatus::CANCELLED);
});

it('parses failure response (recipient refused)', function () {
    $response = createTrackingResponseWith([
        'ResultCode' => 200,
        'ResultMessage' => 'OK',
        'Payload' => [
            'ShipmentModelList' => [[
                'ShipmentId' => '12345',
                'CustomerBarcode' => 'ORD-001',
                'CargoEventLogModelList' => [[
                    'CargoEventType' => 28,
                    'TimeStamp' => '2024-01-15T14:00:00+03:00',
                ]],
            ]],
        ],
    ]);

    $info = $response->getTrackingInfo();

    expect($info->status)->toBe(ShipmentStatus::FAILURE);
});

it('parses out for delivery response', function () {
    $response = createTrackingResponseWith([
        'ResultCode' => 200,
        'ResultMessage' => 'OK',
        'Payload' => [
            'ShipmentModelList' => [[
                'ShipmentId' => '12345',
                'CustomerBarcode' => 'ORD-001',
                'CargoEventLogModelList' => [[
                    'CargoEventType' => 18,
                    'TimeStamp' => '2024-01-15T08:30:00+03:00',
                    'LocationName' => 'KADIKOY',
                ]],
            ]],
        ],
    ]);

    $info = $response->getTrackingInfo();

    expect($info->status)->toBe(ShipmentStatus::OUT_FOR_DELIVERY);
});

it('handles empty shipment list', function () {
    $response = createTrackingResponseWith([
        'ResultCode' => 200,
        'ResultMessage' => 'OK',
        'Payload' => [
            'ShipmentModelList' => [],
        ],
    ]);

    $info = $response->getTrackingInfo();

    expect($info->trackingNumber)->toBe('')
        ->and($info->status)->toBe(ShipmentStatus::UNKNOWN)
        ->and($info->events)->toBe([]);
});

it('parses error response', function () {
    $response = createTrackingResponseWith([
        'ResultCode' => 500.0,
        'ResultMessage' => 'CustomerNotFound',
    ]);

    expect($response->isSuccessful())->toBeFalse()
        ->and($response->getMessage())->toBe('CustomerNotFound');
});

it('maps all known cargo event types', function () {
    $expectedMappings = [
        1 => ShipmentStatus::PRE_TRANSIT,
        2 => ShipmentStatus::PRE_TRANSIT,
        12 => ShipmentStatus::PICKED_UP,
        13 => ShipmentStatus::IN_TRANSIT,
        14 => ShipmentStatus::IN_TRANSIT,
        17 => ShipmentStatus::IN_TRANSIT,
        18 => ShipmentStatus::OUT_FOR_DELIVERY,
        25 => ShipmentStatus::FAILURE,
        26 => ShipmentStatus::FAILURE,
        28 => ShipmentStatus::FAILURE,
        29 => ShipmentStatus::DELIVERED,
        31 => ShipmentStatus::CANCELLED,
        32 => ShipmentStatus::RETURNED,
        33 => ShipmentStatus::FAILURE,
        35 => ShipmentStatus::FAILURE,
        39 => ShipmentStatus::IN_TRANSIT,
        46 => ShipmentStatus::RETURNED,
        58 => ShipmentStatus::FAILURE,
    ];

    foreach ($expectedMappings as $eventType => $expectedStatus) {
        expect(GetTrackingStatusResponse::mapStatus($eventType))->toBe($expectedStatus);
    }
});

it('uses ShipmentId as tracking number when barcode is missing', function () {
    $response = createTrackingResponseWith([
        'ResultCode' => 200,
        'ResultMessage' => 'OK',
        'Payload' => [
            'ShipmentModelList' => [[
                'ShipmentId' => '99999',
                'CargoEventLogModelList' => [],
            ]],
        ],
    ]);

    $info = $response->getTrackingInfo();

    expect($info->trackingNumber)->toBe('99999');
});
