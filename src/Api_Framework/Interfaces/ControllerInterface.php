<?php

declare(strict_types=1);

namespace pointybeard\Symphony\Extensions\Api_Framework\Interfaces;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface ControllerInterface
{
    public function throwMethodNotAllowedExceptionOnGet(): bool;
    public function respondsToRequestMethod(string $method): bool;
}
