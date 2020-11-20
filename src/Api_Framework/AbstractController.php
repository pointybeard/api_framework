<?php

declare(strict_types=1);

namespace pointybeard\Symphony\Extensions\Api_Framework;

use Symfony\Component\HttpFoundation\Response;

/**
 * The base endpoint class that is extended by all API methods.
 */
abstract class AbstractController implements Interfaces\ControllerInterface
{
    public function render(Response $response, ?array $data = null): Response
    {
        if(null !== $data) {
            $response->setData($data);
        }

        return $response;
    }

    public function respondsToRequestMethod(string $method): bool
    {
        return (bool)method_exists($this, strtolower($method));
    }

    public function throwMethodNotAllowedExceptionOnGet(): bool
    {
        return false;
    }
}
