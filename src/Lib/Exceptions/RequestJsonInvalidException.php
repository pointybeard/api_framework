<?php

namespace Symphony\ApiFramework\Lib\Exceptions;

use Symphony\ApiFramework\Lib;
use Symfony\Component\HttpFoundation\Response;

class RequestJsonInvalidException extends Lib\AbstractApiException
{
    public function __construct($code = 0, Exception $previous = null) {
        parent::__construct(Response::HTTP_BAD_REQUEST, "Request could not be handled. Check JSON is valid.", $code, $previous);
    }
}
