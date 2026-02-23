<?php

declare(strict_types=1);

namespace Omniship\KolayGelsin\Message;

use Omniship\Common\Address;
use Omniship\Common\Enum\PaymentType;
use Omniship\Common\Message\ResponseInterface;
use Omniship\Common\Package;

class CreateShipmentRequest extends AbstractKolayGelsinRequest
{
    /** Turkish city plate codes */
    private const CITY_PLATE_MAP = [
        'adana' => 1, 'adıyaman' => 2, 'afyon' => 3, 'afyonkarahisar' => 3,
        'ağrı' => 4, 'amasya' => 5, 'ankara' => 6, 'antalya' => 7,
        'artvin' => 8, 'aydın' => 9, 'balıkesir' => 10, 'bilecik' => 11,
        'bingöl' => 12, 'bitlis' => 13, 'bolu' => 14, 'burdur' => 15,
        'bursa' => 16, 'çanakkale' => 17, 'çankırı' => 18, 'çorum' => 19,
        'denizli' => 20, 'diyarbakır' => 21, 'edirne' => 22, 'elazığ' => 23,
        'erzincan' => 24, 'erzurum' => 25, 'eskişehir' => 26, 'gaziantep' => 27,
        'giresun' => 28, 'gümüşhane' => 29, 'hakkari' => 30, 'hatay' => 31,
        'isparta' => 32, 'mersin' => 33, 'icel' => 33, 'istanbul' => 34,
        'izmir' => 35, 'kars' => 36, 'kastamonu' => 37, 'kayseri' => 38,
        'kırklareli' => 39, 'kırşehir' => 40, 'kocaeli' => 41, 'konya' => 42,
        'kütahya' => 43, 'malatya' => 44, 'manisa' => 45, 'kahramanmaraş' => 46,
        'mardin' => 47, 'muğla' => 48, 'muş' => 49, 'nevşehir' => 50,
        'niğde' => 51, 'ordu' => 52, 'rize' => 53, 'sakarya' => 54,
        'samsun' => 55, 'siirt' => 56, 'sinop' => 57, 'sivas' => 58,
        'tekirdağ' => 59, 'tokat' => 60, 'trabzon' => 61, 'tunceli' => 62,
        'şanlıurfa' => 63, 'uşak' => 64, 'van' => 65, 'yozgat' => 66,
        'zonguldak' => 67, 'aksaray' => 68, 'bayburt' => 69, 'karaman' => 70,
        'kırıkkale' => 71, 'batman' => 72, 'şırnak' => 73, 'bartın' => 74,
        'ardahan' => 75, 'iğdır' => 76, 'yalova' => 77, 'karabük' => 78,
        'kilis' => 79, 'osmaniye' => 80, 'düzce' => 81,
    ];

    protected function getEndpoint(): string
    {
        return 'SaveIntegrationShipmentV2';
    }

    public function getCustomerSpecificCode(): ?string
    {
        return $this->getParameter('customerSpecificCode');
    }

    public function setCustomerSpecificCode(string $code): static
    {
        return $this->setParameter('customerSpecificCode', $code);
    }

    public function getPackageType(): int
    {
        return $this->getParameter('packageType') ?? 2;
    }

    public function setPackageType(int $type): static
    {
        return $this->setParameter('packageType', $type);
    }

    public function getPaymentType(): ?PaymentType
    {
        return $this->getParameter('paymentType');
    }

    public function setPaymentType(PaymentType $paymentType): static
    {
        return $this->setParameter('paymentType', $paymentType);
    }

    public function getSenderInvoiceAmount(): ?float
    {
        return $this->getParameter('senderInvoiceAmount');
    }

    public function setSenderInvoiceAmount(float $amount): static
    {
        return $this->setParameter('senderInvoiceAmount', $amount);
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        $this->validate('apiToken', 'customerId', 'addressId', 'shipTo');

        $shipTo = $this->getShipTo();
        assert($shipTo instanceof Address);

        $packages = $this->getPackages() ?? [];
        $shipmentItems = $this->buildShipmentItems($packages);

        $paymentType = $this->getPaymentType();
        $payingParty = ($paymentType === PaymentType::RECEIVER) ? 2 : 1;

        $data = [
            'SenderCustomer' => [
                'CustomerId' => $this->getCustomerId(),
                'Address' => [
                    'AddressId' => $this->getAddressId(),
                ],
            ],
            'Recipient' => $this->buildRecipient($shipTo),
            'ShipmentItemList' => $shipmentItems,
            'PackageType' => $this->getPackageType(),
            'PayingParty' => $payingParty,
            'OnlyDeliverToRecipient' => false,
        ];

        if ($this->getCustomerSpecificCode() !== null) {
            $data['CustomerSpecificCode'] = $this->getCustomerSpecificCode();
        }

        if ($this->getSenderInvoiceAmount() !== null) {
            $data['SenderInvoiceAmount'] = $this->getSenderInvoiceAmount();
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function createResponse(array $data): ResponseInterface
    {
        return $this->response = new CreateShipmentResponse($this, $data);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildRecipient(Address $address): array
    {
        $isCompany = $address->company !== null && $address->company !== '';

        $recipient = [
            'RecipientType' => $isCompany ? 2 : 1,
            'Address' => [
                'CityId' => $this->resolveCityId($address->city ?? ''),
                'TownName' => $address->district ?? '',
                'AddressTypeId' => $address->residential ? 1 : 2,
                'AddressText' => $this->buildAddressText($address),
                'PostalCode' => $address->postalCode ?? '',
            ],
            'Gsm' => $address->phone ?? '',
            'OnlyDeliverToRecipient' => false,
        ];

        if ($isCompany) {
            $recipient['RecipientTitle'] = $address->company;
            if ($address->taxId !== null) {
                $recipient['RecipientTaxIdentityNumber'] = $address->taxId;
            }
        } else {
            $nameParts = $this->splitName($address->name ?? '');
            $recipient['RecipientName'] = $nameParts[0];
            $recipient['RecipientSurname'] = $nameParts[1];
            if ($address->nationalId !== null) {
                $recipient['RecipientIdentityNumber'] = $address->nationalId;
            }
        }

        if ($address->email !== null) {
            $recipient['Email'] = $address->email;
        }

        return $recipient;
    }

    /**
     * @param Package[] $packages
     * @return array<int, array<string, mixed>>
     */
    private function buildShipmentItems(array $packages): array
    {
        if ($packages === []) {
            return [[
                'Width' => 0,
                'Length' => 0,
                'Height' => 0,
                'Weight' => 0,
                'ContentText' => '',
                'HasCommercialValue' => false,
            ]];
        }

        $items = [];
        $index = 0;

        foreach ($packages as $package) {
            for ($i = 0; $i < $package->quantity; $i++) {
                $item = [
                    'Width' => (int) ($package->width ?? 0),
                    'Length' => (int) ($package->length ?? 0),
                    'Height' => (int) ($package->height ?? 0),
                    'Weight' => $package->weight,
                    'ContentText' => $package->description ?? '',
                    'HasCommercialValue' => ($package->insuredValue ?? 0) > 0,
                ];

                $code = $this->getCustomerSpecificCode();
                if ($code !== null) {
                    $item['CustomerBarcode'] = $code . '-' . ($index + 1);
                }

                $items[] = $item;
                $index++;
            }
        }

        return $items;
    }

    private function buildAddressText(Address $address): string
    {
        $parts = array_filter([
            $address->street1,
            $address->street2,
            $address->district,
            $address->city,
        ]);

        return implode(' ', $parts);
    }

    private function resolveCityId(string $city): int
    {
        $normalized = mb_strtolower(trim($city));

        if (isset(self::CITY_PLATE_MAP[$normalized])) {
            return self::CITY_PLATE_MAP[$normalized];
        }

        // Try numeric plate code directly
        if (is_numeric($city) && (int) $city >= 1 && (int) $city <= 81) {
            return (int) $city;
        }

        return 0;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitName(string $fullName): array
    {
        $parts = preg_split('/\s+/', trim($fullName), 2);
        if ($parts === false || count($parts) < 2) {
            return [$fullName, ''];
        }

        return [$parts[0], $parts[1]];
    }
}
