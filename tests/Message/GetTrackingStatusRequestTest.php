<?php

declare(strict_types=1);

use Omniship\KolayGelsin\Message\GetTrackingStatusRequest;
use Omniship\KolayGelsin\Message\GetTrackingStatusResponse;

use function Omniship\KolayGelsin\Tests\createMockHttpClient;
use function Omniship\KolayGelsin\Tests\createMockRequestFactory;
use function Omniship\KolayGelsin\Tests\createMockStreamFactory;

beforeEach(function () {
    $this->request = new GetTrackingStatusRequest(
        createMockHttpClient(json_encode([
            'ResultCode' => 200,
            'ResultMessage' => 'OK',
            'Payload' => ['ShipmentModelList' => []],
        ])),
        createMockRequestFactory(),
        createMockStreamFactory(),
    );
    $this->request->initialize([
        'apiToken' => 'test-token',
        'testMode' => true,
        'trackingNumber' => 'ABC123',
    ]);
});

it('builds correct request data with tracking number', function () {
    $data = $this->request->getData();

    expect($data['CustomerBarcodeList'])->toBe(['ABC123'])
        ->and($data['ShipmentIdList'])->toBe([])
        ->and($data['OnlyLatestEvents'])->toBeFalse();
});

it('builds correct request data with shipment ID', function () {
    $this->request->setTrackingNumber('');
    $this->request->initialize([
        'apiToken' => 'test-token',
        'shipmentId' => '12345',
    ]);

    $data = $this->request->getData();

    expect($data['ShipmentIdList'])->toBe(['12345'])
        ->and($data['CustomerBarcodeList'])->toBe([]);
});

it('supports only latest events flag', function () {
    $this->request->setOnlyLatestEvents(true);

    $data = $this->request->getData();

    expect($data['OnlyLatestEvents'])->toBeTrue();
});

it('throws when both shipmentId and trackingNumber are missing', function () {
    $request = new GetTrackingStatusRequest(
        createMockHttpClient(),
        createMockRequestFactory(),
        createMockStreamFactory(),
    );
    $request->initialize([
        'apiToken' => 'test-token',
    ]);

    $request->getData();
})->throws(\Omniship\Common\Exception\InvalidRequestException::class);

it('sends and returns GetTrackingStatusResponse', function () {
    $response = $this->request->send();

    expect($response)->toBeInstanceOf(GetTrackingStatusResponse::class)
        ->and($response->isSuccessful())->toBeTrue();
});
