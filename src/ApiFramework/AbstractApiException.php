<?php

declare(strict_types=1);

namespace Symphony\Extensions\ApiFramework;

abstract class AbstractApiException extends \Exception
{
    private $status;

    public function getHttpStatusCode(): string
    {
        return $this->status;
    }

    public function __construct(int $status, string $message, int $code = 0, \Exception $previous = null)
    {
        // Save the status code
        $this->status = $status;

        parent::__construct($message, $code, $previous);
    }
}
