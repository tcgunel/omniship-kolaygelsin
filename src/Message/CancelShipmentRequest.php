<?php

declare(strict_types=1);

namespace Omniship\KolayGelsin\Message;

use Omniship\Common\Message\ResponseInterface;

class CancelShipmentRequest extends AbstractKolayGelsinRequest
{
    protected function getEndpoint(): string
    {
        return 'CancelPickupAndDelivery';
    }

    public function getCancellationReason(): int
    {
        return $this->getParameter('cancellationReason') ?? 1;
    }

    public function setCancellationReason(int $reason): static
    {
        return $this->setParameter('cancellationReason', $reason);
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        $this->validate('apiToken', 'shipmentId');

        return [
            'ShipmentId' => (int) $this->getShipmentId(),
            'DeliveryCancellationReason' => $this->getCancellationReason(),
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function createResponse(array $data): ResponseInterface
    {
        return $this->response = new CancelShipmentResponse($this, $data);
    }
}
