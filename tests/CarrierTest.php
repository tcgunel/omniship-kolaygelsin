<?php

declare(strict_types=1);

use Omniship\KolayGelsin\Carrier;
use Omniship\KolayGelsin\Message\CancelShipmentRequest;
use Omniship\KolayGelsin\Message\CreateShipmentRequest;
use Omniship\KolayGelsin\Message\GetTrackingStatusRequest;

use function Omniship\KolayGelsin\Tests\createMockHttpClient;
use function Omniship\KolayGelsin\Tests\createMockRequestFactory;
use function Omniship\KolayGelsin\Tests\createMockStreamFactory;

beforeEach(function () {
    $this->carrier = new Carrier(
        createMockHttpClient(),
        createMockRequestFactory(),
        createMockStreamFactory(),
    );
    $this->carrier->initialize([
        'apiToken' => 'test-token-123',
        'customerId' => 20,
        'addressId' => 28,
        'testMode' => true,
    ]);
});

it('has the correct name', function () {
    expect($this->carrier->getName())->toBe('Kolay Gelsin')
        ->and($this->carrier->getShortName())->toBe('KolayGelsin');
});

it('has correct default parameters', function () {
    $carrier = new Carrier(
        createMockHttpClient(),
        createMockRequestFactory(),
        createMockStreamFactory(),
    );
    $carrier->initialize();

    expect($carrier->getApiToken())->toBe('')
        ->and($carrier->getCustomerId())->toBe(0)
        ->and($carrier->getAddressId())->toBe(0)
        ->and($carrier->getTestMode())->toBeFalse();
});

it('initializes with custom parameters', function () {
    expect($this->carrier->getApiToken())->toBe('test-token-123')
        ->and($this->carrier->getCustomerId())->toBe(20)
        ->and($this->carrier->getAddressId())->toBe(28)
        ->and($this->carrier->getTestMode())->toBeTrue();
});

it('returns test base URL in test mode', function () {
    expect($this->carrier->getBaseUrl())->toContain('apiintg.klyglsn.com');
});

it('returns production base URL in production mode', function () {
    $this->carrier->setTestMode(false);
    expect($this->carrier->getBaseUrl())->toContain('api.kolaygelsin.com');
});

it('supports createShipment method', function () {
    expect($this->carrier->supports('createShipment'))->toBeTrue();
});

it('supports getTrackingStatus method', function () {
    expect($this->carrier->supports('getTrackingStatus'))->toBeTrue();
});

it('supports cancelShipment method', function () {
    expect($this->carrier->supports('cancelShipment'))->toBeTrue();
});

it('creates a CreateShipmentRequest', function () {
    $request = $this->carrier->createShipment([
        'customerSpecificCode' => 'TEST123',
    ]);

    expect($request)->toBeInstanceOf(CreateShipmentRequest::class);
});

it('creates a GetTrackingStatusRequest', function () {
    $request = $this->carrier->getTrackingStatus([
        'trackingNumber' => 'TEST123',
    ]);

    expect($request)->toBeInstanceOf(GetTrackingStatusRequest::class);
});

it('creates a CancelShipmentRequest', function () {
    $request = $this->carrier->cancelShipment([
        'shipmentId' => '9442',
    ]);

    expect($request)->toBeInstanceOf(CancelShipmentRequest::class);
});
