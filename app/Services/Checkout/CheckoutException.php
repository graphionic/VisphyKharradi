<?php

namespace App\Services\Checkout;

final class CheckoutException extends \RuntimeException
{
    public function __construct(string $message, public readonly int $httpStatus = 400)
    {
        parent::__construct($message);
    }
}
