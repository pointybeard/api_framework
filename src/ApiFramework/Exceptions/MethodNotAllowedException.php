<?php

declare(strict_types=1);

namespace Symphony\Extensions\ApiFramework\Exceptions;

use Symphony\Extensions\ApiFramework;
use Symfony\Component\HttpFoundation\Response;

class MethodNotAllowedException extends ApiFramework\AbstractApiException
{
    public $method;

    public function getHttpMethod(): ?string
    {
        return $this->method;
    }

    public function __construct(string $method, int $code = 0, Exception $previous = null)
    {
        // Save the method
        $this->method = $method;

        parent::__construct(Response::HTTP_METHOD_NOT_ALLOWED, "Method '{$this->method}' is not allowed by this endpoint.", $code, $previous);
    }
}
