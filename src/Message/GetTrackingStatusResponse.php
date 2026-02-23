<?php

declare(strict_types=1);

namespace Omniship\KolayGelsin\Message;

use Omniship\Common\Enum\ShipmentStatus;
use Omniship\Common\Message\AbstractResponse;
use Omniship\Common\Message\TrackingResponse;
use Omniship\Common\TrackingEvent;
use Omniship\Common\TrackingInfo;

class GetTrackingStatusResponse extends AbstractResponse implements TrackingResponse
{
    /**
     * KolayGelsin CargoEventType → ShipmentStatus mapping.
     */
    private const STATUS_MAP = [
        1 => ShipmentStatus::PRE_TRANSIT,        // Gönderi oluşturuldu
        2 => ShipmentStatus::PRE_TRANSIT,        // Toplama kuryesi yola çıktı
        12 => ShipmentStatus::PICKED_UP,          // Paket teslim alındı
        13 => ShipmentStatus::IN_TRANSIT,         // Toplama transfer merkezinde
        14 => ShipmentStatus::IN_TRANSIT,         // Dağıtım transfer merkezinde
        17 => ShipmentStatus::IN_TRANSIT,         // Kuryenin aracına yüklendi
        18 => ShipmentStatus::OUT_FOR_DELIVERY,   // Kurye teslimat adresine yola çıktı
        25 => ShipmentStatus::FAILURE,            // Alıcı adresi hatalı
        26 => ShipmentStatus::FAILURE,            // Alıcı adreste bulunamadı
        28 => ShipmentStatus::FAILURE,            // Alıcı teslimatı reddetti
        29 => ShipmentStatus::DELIVERED,           // Teslim edildi
        31 => ShipmentStatus::CANCELLED,           // Gönderen durdurdu
        32 => ShipmentStatus::RETURNED,            // Alıcı talebi ile iade
        33 => ShipmentStatus::FAILURE,            // Gönderi kayıp
        35 => ShipmentStatus::FAILURE,            // Alıcı adresi problemli
        39 => ShipmentStatus::IN_TRANSIT,         // Ertesi güne devredildi
        46 => ShipmentStatus::RETURNED,            // Operasyonel iade
        58 => ShipmentStatus::FAILURE,            // Hasar raporu
    ];

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

    public function getTrackingInfo(): TrackingInfo
    {
        $shipments = $this->getShipmentModelList();

        if ($shipments === []) {
            return new TrackingInfo(
                trackingNumber: '',
                status: ShipmentStatus::UNKNOWN,
                events: [],
                carrier: 'Kolay Gelsin',
            );
        }

        $shipment = $shipments[0];
        $trackingNumber = (string) ($shipment['CustomerBarcode'] ?? $shipment['ShipmentId'] ?? '');
        $events = $this->parseEvents($shipment);
        $status = $events !== [] ? $events[0]->status : ShipmentStatus::UNKNOWN;

        return new TrackingInfo(
            trackingNumber: $trackingNumber,
            status: $status,
            events: $events,
            carrier: 'Kolay Gelsin',
        );
    }

    public static function mapStatus(int $cargoEventType): ShipmentStatus
    {
        return self::STATUS_MAP[$cargoEventType] ?? ShipmentStatus::UNKNOWN;
    }

    /**
     * @param array<string, mixed> $shipment
     * @return TrackingEvent[]
     */
    private function parseEvents(array $shipment): array
    {
        if (!isset($shipment['CargoEventLogModelList']) || !is_array($shipment['CargoEventLogModelList'])) {
            return [];
        }

        $events = [];

        /** @var array<int, array<string, mixed>> $eventList */
        $eventList = $shipment['CargoEventLogModelList'];

        foreach ($eventList as $item) {
            $eventType = isset($item['CargoEventType']) ? (int) $item['CargoEventType'] : null;
            $status = $eventType !== null
                ? (self::STATUS_MAP[$eventType] ?? ShipmentStatus::UNKNOWN)
                : ShipmentStatus::UNKNOWN;

            $dateTime = new \DateTimeImmutable();
            if (isset($item['TimeStamp']) && is_string($item['TimeStamp']) && $item['TimeStamp'] !== '') {
                try {
                    $parsed = \DateTimeImmutable::createFromFormat('d.m.Y H:i:s', $item['TimeStamp']);
                    $dateTime = $parsed instanceof \DateTimeImmutable ? $parsed : new \DateTimeImmutable($item['TimeStamp']);
                } catch (\Exception) {
                    // Keep default
                }
            }

            $location = null;
            if (isset($item['LocationName']) && is_string($item['LocationName']) && $item['LocationName'] !== '') {
                $location = $item['LocationName'];
            }

            $description = '';
            if (isset($item['EventDescription']) && is_string($item['EventDescription'])) {
                $description = $item['EventDescription'];
            }

            $events[] = new TrackingEvent(
                status: $status,
                description: $description,
                occurredAt: $dateTime,
                location: $location,
            );
        }

        return $events;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getShipmentModelList(): array
    {
        if (!is_array($this->data) || !isset($this->data['Payload'])) {
            return [];
        }

        $payload = $this->data['Payload'];

        if (!is_array($payload)) {
            return [];
        }

        /** @var array<int, array<string, mixed>> */
        return $payload;
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
