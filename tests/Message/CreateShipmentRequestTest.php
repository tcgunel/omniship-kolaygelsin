<?php

declare(strict_types=1);

use Omniship\Common\Address;
use Omniship\Common\Enum\PaymentType;
use Omniship\Common\Package;
use Omniship\KolayGelsin\Message\CreateShipmentRequest;
use Omniship\KolayGelsin\Message\CreateShipmentResponse;

use function Omniship\KolayGelsin\Tests\createMockHttpClient;
use function Omniship\KolayGelsin\Tests\createMockRequestFactory;
use function Omniship\KolayGelsin\Tests\createMockStreamFactory;

beforeEach(function () {
    $this->request = new CreateShipmentRequest(
        createMockHttpClient(json_encode([
            'ResultCode' => 200,
            'ResultMessage' => 'OK',
            'Payload' => ['ShipmentId' => 12345],
        ])),
        createMockRequestFactory(),
        createMockStreamFactory(),
    );
    $this->request->initialize([
        'apiToken' => 'test-token',
        'customerId' => 20,
        'addressId' => 28,
        'testMode' => true,
        'shipTo' => new Address(
            name: 'Ahmet Yılmaz',
            street1: 'Mehmet Akman Sk. Koşuyolu Mh.',
            city: 'Istanbul',
            district: 'Kadıköy',
            postalCode: '34500',
            phone: '5551234567',
        ),
        'packages' => [
            new Package(
                weight: 1.5,
                length: 30,
                width: 20,
                height: 15,
                description: 'Test parcel',
            ),
        ],
        'customerSpecificCode' => 'ORD-001',
    ]);
});

it('builds correct request data', function () {
    $data = $this->request->getData();

    expect($data)->toHaveKey('SenderCustomer')
        ->and($data['SenderCustomer']['CustomerId'])->toBe(20)
        ->and($data['SenderCustomer']['Address']['AddressId'])->toBe(28)
        ->and($data)->toHaveKey('Recipient')
        ->and($data)->toHaveKey('ShipmentItemList')
        ->and($data['PackageType'])->toBe(2)
        ->and($data['PayingParty'])->toBe(1)
        ->and($data['CustomerSpecificCode'])->toBe('ORD-001');
});

it('builds individual recipient correctly', function () {
    $data = $this->request->getData();
    $recipient = $data['Recipient'];

    expect($recipient['RecipientType'])->toBe(1)
        ->and($recipient['RecipientName'])->toBe('Ahmet')
        ->and($recipient['RecipientSurname'])->toBe('Yılmaz')
        ->and($recipient['Gsm'])->toBe('5551234567')
        ->and($recipient['Address']['CityId'])->toBe(34)
        ->and($recipient['Address']['TownName'])->toBe('Kadıköy')
        ->and($recipient['Address']['PostalCode'])->toBe('34500');
});

it('builds corporate recipient when company is set', function () {
    $this->request->setShipTo(new Address(
        company: 'Ekol Lojistik AŞ',
        street1: 'İmam Hatip Cd.',
        city: 'Istanbul',
        district: 'Sancaktepe',
        taxId: '9240913225',
    ));

    $data = $this->request->getData();
    $recipient = $data['Recipient'];

    expect($recipient['RecipientType'])->toBe(2)
        ->and($recipient['RecipientTitle'])->toBe('Ekol Lojistik AŞ')
        ->and($recipient['RecipientTaxIdentityNumber'])->toBe('9240913225');
});

it('builds shipment items from packages', function () {
    $data = $this->request->getData();
    $items = $data['ShipmentItemList'];

    expect($items)->toHaveCount(1)
        ->and($items[0]['Width'])->toBe(20)
        ->and($items[0]['Length'])->toBe(30)
        ->and($items[0]['Height'])->toBe(15)
        ->and($items[0]['Weight'])->toBe(1.5)
        ->and($items[0]['ContentText'])->toBe('Test parcel')
        ->and($items[0]['CustomerBarcode'])->toBe('ORD-001-1');
});

it('builds multiple items from multi-package shipment', function () {
    $this->request->setPackages([
        new Package(weight: 1.0, length: 10, width: 10, height: 10),
        new Package(weight: 2.0, length: 20, width: 20, height: 20),
    ]);

    $data = $this->request->getData();
    $items = $data['ShipmentItemList'];

    expect($items)->toHaveCount(2)
        ->and($items[0]['Weight'])->toBe(1.0)
        ->and($items[1]['Weight'])->toBe(2.0);
});

it('resolves city names to plate codes', function () {
    $this->request->setShipTo(new Address(
        name: 'Test User',
        street1: 'Test St.',
        city: 'Ankara',
        district: 'Çankaya',
    ));

    $data = $this->request->getData();

    expect($data['Recipient']['Address']['CityId'])->toBe(6);
});

it('accepts numeric city codes', function () {
    $this->request->setShipTo(new Address(
        name: 'Test User',
        street1: 'Test St.',
        city: '35',
    ));

    $data = $this->request->getData();

    expect($data['Recipient']['Address']['CityId'])->toBe(35);
});

it('sets receiver pays when payment type is receiver', function () {
    $this->request->setPaymentType(PaymentType::RECEIVER);

    $data = $this->request->getData();

    expect($data['PayingParty'])->toBe(2);
});

it('includes sender invoice amount for COD', function () {
    $this->request->setSenderInvoiceAmount(250.00);

    $data = $this->request->getData();

    expect($data['SenderInvoiceAmount'])->toBe(250.00);
});

it('throws when required parameters are missing', function () {
    $request = new CreateShipmentRequest(
        createMockHttpClient(),
        createMockRequestFactory(),
        createMockStreamFactory(),
    );
    $request->initialize([]);

    $request->getData();
})->throws(\Omniship\Common\Exception\InvalidRequestException::class);

it('sends and returns CreateShipmentResponse', function () {
    $response = $this->request->send();

    expect($response)->toBeInstanceOf(CreateShipmentResponse::class)
        ->and($response->isSuccessful())->toBeTrue();
});
