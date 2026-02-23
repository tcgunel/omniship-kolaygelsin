<?php

declare(strict_types=1);

namespace Omniship\KolayGelsin\Message;

use Omniship\Common\Enum\LabelFormat;
use Omniship\Common\Label;
use Omniship\Common\Message\AbstractResponse;
use Omniship\Common\Message\ShipmentResponse;

class CreateShipmentResponse extends AbstractResponse implements ShipmentResponse
{
    public function isSuccessful(): bool
    {
        return $this->getResultCode() === 200.0 || $this->getResultCode() === 200;
    }

    public function getMessage(): ?string
    {
        return $this->getResultMessage();
    }

    public function getCode(): ?string
    {
        $code = $this->getResultCode();

        return $code !== null ? (string) $code : null;
    }

    public function getShipmentId(): ?string
    {
        $payload = $this->getPayload();

        if ($payload !== null && isset($payload['ShipmentId'])) {
            return (string) $payload['ShipmentId'];
        }

        return null;
    }

    public function getTrackingNumber(): ?string
    {
        return $this->getShipmentId();
    }

    public function getBarcode(): ?string
    {
        $payload = $this->getPayload();

        if ($payload !== null && isset($payload['CustomerSpecificCode'])) {
            return (string) $payload['CustomerSpecificCode'];
        }

        return null;
    }

    public function getTrackingLink(): ?string
    {
        $payload = $this->getPayload();

        if ($payload !== null && isset($payload['ShipmentTrackingLink'])) {
            return (string) $payload['ShipmentTrackingLink'];
        }

        return null;
    }

    public function getLabel(): ?Label
    {
        $payload = $this->getPayload();

        if ($payload === null || !isset($payload['ShipmentItemLabelList'])) {
            return null;
        }

        /** @var array<int, array{ShipmentItemId?: string, ShipmentItemIdLabel?: string, CustomerBarcode?: string}> $labels */
        $labels = $payload['ShipmentItemLabelList'];

        if ($labels === []) {
            return null;
        }

        $firstLabel = $labels[0];
        $html = $firstLabel['ShipmentItemIdLabel'] ?? '';

        if ($html === '') {
            return null;
        }

        return new Label(
            trackingNumber: $this->getShipmentId() ?? '',
            content: $html,
            format: LabelFormat::HTML,
            barcode: $firstLabel['CustomerBarcode'] ?? null,
            shipmentId: $firstLabel['ShipmentItemId'] ?? null,
        );
    }

    public function getTotalCharge(): ?float
    {
        return null;
    }

    public function getCurrency(): ?string
    {
        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getPayload(): ?array
    {
        if (!is_array($this->data) || !isset($this->data['Payload'])) {
            return null;
        }

        /** @var array<string, mixed> */
        return $this->data['Payload'];
    }

    private function getResultCode(): float|int|null
    {
        if (!is_array($this->data) || !isset($this->data['ResultCode'])) {
            return null;
        }

        return $this->data['ResultCode'];
    }

    private function getResultMessage(): ?string
    {
        if (!is_array($this->data) || !isset($this->data['ResultMessage'])) {
            return null;
        }

        return (string) $this->data['ResultMessage'];
    }
}
