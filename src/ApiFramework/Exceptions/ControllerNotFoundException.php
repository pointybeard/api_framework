<?php

declare(strict_types=1);

namespace Symphony\Extensions\ApiFramework\Exceptions;

use Symphony\Extensions\ApiFramework;
use Symfony\Component\HttpFoundation\Response;

class ControllerNotFoundException extends ApiFramework\AbstractApiException
{
    public function __construct(string $controller, int $code = 0, Exception $previous = null)
    {
        parent::__construct(Response::HTTP_NOT_FOUND, "Controller '{$controller}' could not be located.", $code, $previous);
    }
}
