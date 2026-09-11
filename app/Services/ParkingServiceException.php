<?php

namespace App\Services;

use RuntimeException;

class ParkingServiceException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $statusCode = 400,
    ) {
        parent::__construct($message);
    }

    public static function badRequest(string $message): self
    {
        return new self($message, 400);
    }

    public static function notFound(string $message): self
    {
        return new self($message, 404);
    }
}
