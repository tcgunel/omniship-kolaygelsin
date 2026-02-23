<?php

declare(strict_types=1);

namespace Omniship\KolayGelsin\Message;

use Omniship\Common\Message\AbstractResponse;
use Omniship\Common\Message\CancelResponse;

class CancelShipmentResponse extends AbstractResponse implements CancelResponse
{
    public function isSuccessful(): bool
    {
        return $this->getResultCode() === 200.0 || $this->getResultCode() === 200;
    }

    public function isCancelled(): bool
    {
        return $this->isSuccessful();
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
