<?php

declare(strict_types=1);

namespace pointybeard\Symphony\Extensions\Api_Framework;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class GlobalOptionsController extends AbstractController
{
    // Catch any OPTIONS requests and return a 204 No Content response
    public function options(Request $request, Response $response): Response {
        $response->setStatusCode(Response::HTTP_NO_CONTENT);
        return $response;
    }
}

