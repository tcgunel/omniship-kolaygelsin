# Omniship KolayGelsin (Sendeo)

KolayGelsin (Sendeo) carrier driver for [Omniship](https://github.com/tcgunel/omniship-common).

Uses KolayGelsin's REST/JSON API.

## Installation

```bash
composer require tcgunel/omniship-kolaygelsin
```

## Quick Start

```php
use Omniship\Omniship;
use Omniship\Common\Address;
use Omniship\Common\Package;

$carrier = Omniship::create('KolayGelsin');
$carrier->initialize([
    'apiToken' => 'your-api-token',
    'customerId' => 12345,
    'addressId' => 67890,
    'testMode' => true,
]);
```

## Operations

### Create Shipment

```php
$response = $carrier->createShipment([
    'shipTo' => new Address(
        name: 'Mehmet Demir',
        street1: 'Kızılay Mah. 123. Sok. No:5',
        city: 'Ankara',           // Auto-resolved to plate code (6)
        district: 'Çankaya',
        postalCode: '06420',
        phone: '05559876543',
        email: 'mehmet@example.com',
    ),
    'packages' => [
        new Package(
            weight: 2.5,
            length: 30,
            width: 20,
            height: 15,
            description: 'Elektronik ürün',
        ),
    ],
    'customerSpecificCode' => 'ORDER-001',
    'packageType' => 2,   // 1=Document, 2=Package
])->send();

if ($response->isSuccessful()) {
    echo $response->getTrackingNumber();
    echo $response->getShipmentId();
}
```

### Track Shipment

```php
$response = $carrier->getTrackingStatus([
    'trackingNumber' => 'your-tracking-number',
])->send();

if ($response->isSuccessful()) {
    $info = $response->getTrackingInfo();
    echo $info->status->name;
    echo $info->trackingNumber;

    foreach ($info->events as $event) {
        echo $event->description;
        echo $event->occurredAt->format('Y-m-d H:i');
    }
}
```

### Cancel Shipment

```php
$response = $carrier->cancelShipment([
    'trackingNumber' => 'your-tracking-number',
])->send();

if ($response->isSuccessful() && $response->isCancelled()) {
    echo 'Shipment cancelled';
}
```

## API Details

### Endpoints

| Environment | URL |
|-------------|-----|
| Test | `https://apiintg.klyglsn.com/api/request` |
| Production | `https://api.kolaygelsin.com/api/request` |

### Authentication

Uses Bearer token authentication via `apiToken` parameter.

### Key Features

- **REST/JSON API**: Modern JSON-based API
- **City plate code resolution**: Automatically maps Turkish city names to plate codes (1-81)
- **Per-piece dimensions**: Each shipment item has individual width/length/height/weight
- **Recipient types**: Individual (`1`) or corporate (`2`) recipients
- **Address types**: Residential (`1`) or commercial (`2`)
- **Name splitting**: Full names automatically split into first/last name

### API Methods

| Endpoint | Description |
|----------|-------------|
| `SaveIntegrationShipmentV2` | Create shipment |
| `GetIntegrationShipment` | Track shipment |
| `CancelIntegrationShipment` | Cancel shipment |

## Testing

```bash
vendor/bin/pest
```

## License

MIT
