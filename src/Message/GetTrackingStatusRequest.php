<?php

declare(strict_types=1);

namespace Omniship\KolayGelsin\Message;

use Omniship\Common\Message\ResponseInterface;

class GetTrackingStatusRequest extends AbstractKolayGelsinRequest
{
    protected function getEndpoint(): string
    {
        return 'GetCorporateShipmentsStatus';
    }

    public function getOnlyLatestEvents(): bool
    {
        return (bool) ($this->getParameter('onlyLatestEvents') ?? false);
    }

    public function setOnlyLatestEvents(bool $value): static
    {
        return $this->setParameter('onlyLatestEvents', $value);
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        $this->validate('apiToken');

        $shipmentId = $this->getShipmentId();
        $trackingNumber = $this->getTrackingNumber();

        if ($shipmentId === null && $trackingNumber === null) {
            throw new \Omniship\Common\Exception\InvalidRequestException(
                'Either shipmentId or trackingNumber is required.',
            );
        }

        return [
            'ShipmentIdList' => $shipmentId !== null ? [$shipmentId] : [],
            'CustomerSpecificCodeList' => [],
            'CustomerBarcodeList' => $trackingNumber !== null ? [$trackingNumber] : [],
            'CustomerTrackingIdList' => [],
            'OnlyLatestEvents' => $this->getOnlyLatestEvents(),
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function createResponse(array $data): ResponseInterface
    {
        return $this->response = new GetTrackingStatusResponse($this, $data);
    }
}
