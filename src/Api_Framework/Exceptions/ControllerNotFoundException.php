<?php

declare(strict_types=1);

namespace pointybeard\Symphony\Extensions\Api_Framework\Exceptions;

use Symfony\Component\HttpFoundation;

class ControllerNotFoundException extends ApiFrameworkException
{
    public function __construct(string $controller, int $code = 0, \Exception $previous = null)
    {
        parent::__construct(HttpFoundation\Response::HTTP_NOT_FOUND, "Controller '{$controller}' could not be located.", $code, $previous);
    }
}
