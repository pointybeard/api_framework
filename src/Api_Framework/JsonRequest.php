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

use Symfony\Component\HttpFoundation;

/**
 * This extends the Symfony Request class found in HttpFoundation. It addes some
 * specific functionality for parsing JSON input and making it available in a
 * more standard way.
 */
class JsonRequest extends HttpFoundation\Request
{
    public static function createFromGlobals(?int $options = 0): HttpFoundation\Request
    {
        // Call the parent method to generate a standard request object and
        // populate with data.
        $request = parent::createFromGlobals();

        // Grab whatever we were sent
        $request->body = file_get_contents('php://input');

        // Initialise an array to hold our input data.
        $input = [];

        // If we got something, decode it (making the assumption it's actually
        // json.)
        if (strlen(trim((string) $request->body)) > 0) {
            try {
                $input = json_decode((string) $request->body, true, 512, JSON_THROW_ON_ERROR | $options);
            } catch (\JsonException $ex) {
                throw new Exceptions\RequestJsonInvalidException(0, $ex);
            }
        }

        // Inject this into the $request object which is then returned.
        $request->json = new HttpFoundation\ParameterBag($input);

        return $request;
    }
}
