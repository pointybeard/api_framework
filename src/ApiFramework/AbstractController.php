<?php

declare(strict_types=1);

namespace pointybeard\Symphony\Extensions\Api_Framework;

use Symfony\Component\HttpFoundation\Response;

/**
 * The base endpoint class that is extended by all API methods.
 */
abstract class AbstractController implements Interfaces\ControllerInterface
{
    public function render(Response $response, array $data): Response
    {
        $response->setData($data);

        return $response;
    }
}
