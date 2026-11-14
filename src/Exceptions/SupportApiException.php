<?php

declare(strict_types=1);

namespace Am2tec\SupportSdk\Exceptions;

use RuntimeException;

class SupportApiException extends RuntimeException
{
    public function __construct(string $message, public readonly ?int $status = null, public readonly array $body = [])
    {
        parent::__construct($message, $status ?? 0);
    }
}
