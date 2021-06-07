<?php

declare(strict_types=1);

namespace pointybeard\Symphony\Extensions\Api_Framework\Middleware;

use pointybeard\Symphony\Extensions\Api_Framework\JsonFrontendPage;
use pointybeard\Symphony\Extensions\Api_Framework\JsonFrontend;
use pointybeard\Symphony\Extended;
use Symfony\Component\HttpFoundation\Response;

use Closure;
use stdClass;
use Exception;

final class InitialiseResponse
{
    public function handle(Response $response, JsonFrontendPage $page, JsonFrontend $frontend) {

        $response->headers->set('Content-Type', 'application/json; charset=utf-8');
        $response->headers->set('X-API-Framework-Page-Renderer',  array_pop(explode('\\', get_class($page))));
        $response->setEncodingOptions($frontend->getEncodingOptions());

        Extended\ServiceContainer::getInstance()->register("response", $response, true);
    }

}