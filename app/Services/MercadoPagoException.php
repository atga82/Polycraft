<?php
declare(strict_types=1);
namespace App\Services;

final class MercadoPagoException extends \RuntimeException
{
    public function __construct(string $message, private int $httpStatus = 502)
    {
        parent::__construct($message);
    }
    public function httpStatus(): int { return $this->httpStatus; }
}
