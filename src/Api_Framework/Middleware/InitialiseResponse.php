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
use pointybeard\Symphony\Extensions\Api_Framework\JsonFrontend;
use pointybeard\Symphony\Extensions\Api_Framework\JsonFrontendPage;
use Symfony\Component\HttpFoundation\Response;

final class InitialiseResponse
{
    public function handle(Response $response, JsonFrontendPage $page, JsonFrontend $frontend)
    {
        $response->headers->set('Content-Type', 'application/json; charset=utf-8');
        $response->headers->set('X-API-Framework-Page-Renderer', array_pop(explode('\\', get_class($page))));
        $response->setEncodingOptions($frontend->getEncodingOptions());

        Extended\ServiceContainer::getInstance()->register('response', $response, true);
    }
}
