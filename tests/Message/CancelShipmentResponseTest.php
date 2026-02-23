<?php

declare(strict_types=1);

use Omniship\KolayGelsin\Message\CancelShipmentResponse;

use function Omniship\KolayGelsin\Tests\createMockHttpClient;
use function Omniship\KolayGelsin\Tests\createMockRequestFactory;
use function Omniship\KolayGelsin\Tests\createMockStreamFactory;

function createCancelResponseWith(array $data): CancelShipmentResponse
{
    $request = new \Omniship\KolayGelsin\Message\CancelShipmentRequest(
        createMockHttpClient(),
        createMockRequestFactory(),
        createMockStreamFactory(),
    );

    return new CancelShipmentResponse($request, $data);
}

it('parses successful cancel response', function () {
    $response = createCancelResponseWith([
        'ResultCode' => 200,
        'ResultMessage' => 'OK',
    ]);

    expect($response->isSuccessful())->toBeTrue()
        ->and($response->isCancelled())->toBeTrue()
        ->and($response->getMessage())->toBe('OK')
        ->and($response->getCode())->toBe('200');
});

it('parses failed cancel response', function () {
    $response = createCancelResponseWith([
        'ResultCode' => 500.0,
        'ResultMessage' => 'ShipmentAlreadyDelivered',
    ]);

    expect($response->isSuccessful())->toBeFalse()
        ->and($response->isCancelled())->toBeFalse()
        ->and($response->getMessage())->toBe('ShipmentAlreadyDelivered');
});

it('handles float result code for success', function () {
    $response = createCancelResponseWith([
        'ResultCode' => 200.0,
        'ResultMessage' => 'OK',
    ]);

    expect($response->isSuccessful())->toBeTrue()
        ->and($response->isCancelled())->toBeTrue();
});

it('returns raw data', function () {
    $data = ['ResultCode' => 200, 'ResultMessage' => 'OK'];
    $response = createCancelResponseWith($data);

    expect($response->getData())->toBe($data);
});
