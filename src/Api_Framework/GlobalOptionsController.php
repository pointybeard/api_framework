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

namespace pointybeard\Symphony\Extensions\Api_Framework;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class GlobalOptionsController extends AbstractController
{
    // Catch any OPTIONS requests and return a 204 No Content response
    public function options(Request $request, Response $response): Response
    {
        $response->setStatusCode(Response::HTTP_NO_CONTENT);

        return $response;
    }
}
