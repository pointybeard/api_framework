<?php

declare(strict_types=1);

namespace pointybeard\Symphony\Extensions\Api_Framework\Middleware;

use pointybeard\Symphony\Extensions\Api_Framework\Models;
use pointybeard\Symphony\Extended;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Closure;
use stdClass;
use Exception;

final class RecordActivity
{
    public function handle(Request $request) {

        $activity = (new Models\Activity)
            ->dateRequestedAt("now")
            ->requestUri((string)$request->server->get("REQUEST_URI"))
            ->requestMethod($request->getMethod())
            ->request((string)$request->headers . PHP_EOL . (string)$request->body)
            ->save()
        ;

        // Register the activity object with our service container so we can update it later
        Extended\ServiceContainer::getInstance()->register("activity", $activity, true);

    }

    public function terminate(Response $response) {
        Extended\ServiceContainer::getInstance()->get("activity")
            ->responseCode($response->getStatusCode())
            ->response((string)$response->headers . PHP_EOL . (string)$response->getContent())
            ->save()
        ;
    }
}