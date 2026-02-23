<?php

declare(strict_types=1);

namespace Omniship\KolayGelsin;

use Omniship\Common\AbstractHttpCarrier;
use Omniship\Common\Message\RequestInterface;
use Omniship\KolayGelsin\Message\CancelShipmentRequest;
use Omniship\KolayGelsin\Message\CreateShipmentRequest;
use Omniship\KolayGelsin\Message\GetTrackingStatusRequest;

class Carrier extends AbstractHttpCarrier
{
    private const BASE_URL_TEST = 'https://apiintg.klyglsn.com/api/request';
    private const BASE_URL_PRODUCTION = 'https://api.kolaygelsin.com/api/request';

    public function getName(): string
    {
        return 'Kolay Gelsin';
    }

    public function getShortName(): string
    {
        return 'KolayGelsin';
    }

    /**
     * @return array<string, mixed>
     */
    public function getDefaultParameters(): array
    {
        return [
            'apiToken' => '',
            'customerId' => 0,
            'addressId' => 0,
            'testMode' => false,
        ];
    }

    public function getApiToken(): string
    {
        return (string) $this->getParameter('apiToken');
    }

    public function setApiToken(string $apiToken): static
    {
        return $this->setParameter('apiToken', $apiToken);
    }

    public function getCustomerId(): int
    {
        return (int) $this->getParameter('customerId');
    }

    public function setCustomerId(int $customerId): static
    {
        return $this->setParameter('customerId', $customerId);
    }

    public function getAddressId(): int
    {
        return (int) $this->getParameter('addressId');
    }

    public function setAddressId(int $addressId): static
    {
        return $this->setParameter('addressId', $addressId);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function createShipment(array $options = []): RequestInterface
    {
        return $this->createRequest(CreateShipmentRequest::class, $options);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function getTrackingStatus(array $options = []): RequestInterface
    {
        return $this->createRequest(GetTrackingStatusRequest::class, $options);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function cancelShipment(array $options = []): RequestInterface
    {
        return $this->createRequest(CancelShipmentRequest::class, $options);
    }

    public function getBaseUrl(): string
    {
        return $this->getTestMode() ? self::BASE_URL_TEST : self::BASE_URL_PRODUCTION;
    }
}
