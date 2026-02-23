<?php

declare(strict_types=1);

namespace Omniship\KolayGelsin\Message;

use Omniship\Common\Message\AbstractHttpRequest;
use Omniship\Common\Message\ResponseInterface;

abstract class AbstractKolayGelsinRequest extends AbstractHttpRequest
{
    abstract protected function getEndpoint(): string;

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

    protected function getBaseUrl(): string
    {
        return $this->getTestMode()
            ? 'https://apiintg.klyglsn.com/api/request'
            : 'https://api.kolaygelsin.com/api/request';
    }

    /**
     * @param array<string, mixed> $data
     */
    public function sendData(array $data): ResponseInterface
    {
        $url = $this->getBaseUrl() . '/' . $this->getEndpoint();

        $response = $this->sendHttpRequest(
            method: 'POST',
            url: $url,
            headers: [
                'Authorization' => 'Bearer ' . $this->getApiToken(),
                'Content-Type' => 'application/json',
            ],
            body: json_encode($data, JSON_THROW_ON_ERROR),
        );

        $body = (string) $response->getBody();

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

        return $this->response = $this->createResponse($decoded);
    }

    /**
     * @param array<string, mixed> $data
     */
    abstract protected function createResponse(array $data): ResponseInterface;
}
