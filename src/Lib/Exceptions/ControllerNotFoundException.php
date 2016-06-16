<?php

namespace Symphony\ApiFramework\Lib\Exceptions;

use Symphony\ApiFramework\Lib;
use Symfony\Component\HttpFoundation\Response;

class ControllerNotFoundException extends Lib\AbstractApiException
{
    public function __construct($controller, $code = 0, Exception $previous = null) {
        parent::__construct(Response::HTTP_NOT_FOUND, "Controller '{$controller}' could not be located.", $code, $previous);
    }
}
