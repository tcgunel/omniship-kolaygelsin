<?php

declare(strict_types=1);

use Omniship\KolayGelsin\Message\CancelShipmentRequest;
use Omniship\KolayGelsin\Message\CancelShipmentResponse;

use function Omniship\KolayGelsin\Tests\createMockHttpClient;
use function Omniship\KolayGelsin\Tests\createMockRequestFactory;
use function Omniship\KolayGelsin\Tests\createMockStreamFactory;

beforeEach(function () {
    $this->request = new CancelShipmentRequest(
        createMockHttpClient(json_encode([
            'ResultCode' => 200,
            'ResultMessage' => 'OK',
        ])),
        createMockRequestFactory(),
        createMockStreamFactory(),
    );
    $this->request->initialize([
        'apiToken' => 'test-token',
        'testMode' => true,
        'shipmentId' => '9442',
    ]);
});

it('builds correct cancel request data', function () {
    $data = $this->request->getData();

    expect($data['ShipmentId'])->toBe(9442)
        ->and($data['DeliveryCancellationReason'])->toBe(1);
});

it('supports custom cancellation reason', function () {
    $this->request->setCancellationReason(2);

    $data = $this->request->getData();

    expect($data['DeliveryCancellationReason'])->toBe(2);
});

it('throws when shipmentId is missing', function () {
    $request = new CancelShipmentRequest(
        createMockHttpClient(),
        createMockRequestFactory(),
        createMockStreamFactory(),
    );
    $request->initialize([
        'apiToken' => 'test-token',
    ]);

    $request->getData();
})->throws(\Omniship\Common\Exception\InvalidRequestException::class);

it('sends and returns CancelShipmentResponse', function () {
    $response = $this->request->send();

    expect($response)->toBeInstanceOf(CancelShipmentResponse::class)
        ->and($response->isSuccessful())->toBeTrue();
});
