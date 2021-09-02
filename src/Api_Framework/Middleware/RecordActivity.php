<?php

declare(strict_types=1);

/*
 * This file is part of the "RESTful API Framework Extension for Symphony CMS" repository.
 *
 * Copyright 2017-2021 Alannah Kearney <hi@alannahkearney.com>
 *
 * For the full copyright and license information, please view the LICENCE
 * file that was distributed with this source code.
 */

namespace pointybeard\Symphony\Extensions\Api_Framework\Middleware;

use pointybeard\Symphony\Extended;
use pointybeard\Symphony\Extensions\Api_Framework\Models;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class RecordActivity
{
    public function handle(Request $request)
    {
        $activity = (new Models\Activity())
            ->dateRequestedAt('now')
            ->requestUri((string) $request->server->get('REQUEST_URI'))
            ->requestMethod($request->getMethod())
            ->request((string) $request->headers.PHP_EOL.(string) $request->body)
            ->save()
        ;

        // Register the activity object with our service container so we can update it later
        Extended\ServiceContainer::getInstance()->register('activity', $activity, true);
    }

    public function terminate(Response $response)
    {
        Extended\ServiceContainer::getInstance()->get('activity')
            ->responseCode($response->getStatusCode())
            ->response((string) $response->headers.PHP_EOL.(string) $response->getContent())
            ->save()
        ;
    }
}
