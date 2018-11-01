<?php

namespace Symphony\ApiFramework\Lib\Exceptions;

use Symphony\ApiFramework\Lib;
use Symfony\Component\HttpFoundation\Response;

class MethodNotAllowedException extends Lib\AbstractApiException
{
    public $method;

    public function getHttpMethod()
    {
        return $this->method;
    }

    public function __construct($method, $code = 0, Exception $previous = null)
    {
        // Save the method
        $this->method = $method;

        parent::__construct(Response::HTTP_METHOD_NOT_ALLOWED, "Method '{$this->method}' is not allowed by this endpoint.", $code, $previous);
    }
}
