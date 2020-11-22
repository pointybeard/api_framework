<?php

declare(strict_types=1);

namespace pointybeard\Symphony\Extensions\Api_Framework\Exceptions;

use pointybeard\Helpers\Exceptions\ReadableTrace;
use Symfony\Component\HttpFoundation;

class ApiFrameworkException extends ReadableTrace\ReadableTraceException
{
    private $status;

    public function getHttpStatusCode(): int
    {
        return $this->status;
    }

    public function __construct(int $status = HttpFoundation\Response::HTTP_INTERNAL_SERVER_ERROR, string $message, int $code = 0, \Exception $previous = null)
    {
        $this->status = $status;
        parent::__construct($message, $code, $previous);
    }
}
