<?php

namespace App\Exceptions;

use Exception;

class OtpDeliveryException extends Exception
{
    public function __construct(string $userMessage, protected ?string $providerDetail = null)
    {
        parent::__construct($userMessage);
    }

    public function providerDetail(): ?string
    {
        return $this->providerDetail;
    }
}
