<?php

declare(strict_types=1);

namespace pointybeard\Symphony\Extensions\Api_Framework;

use Symfony\Component\HttpFoundation;

/**
 * This extends the Symfony Request class found in HttpFoundation. It addes some
 * specific functionality for parsing JSON input and making it available in a
 * more standard way.
 */
class JsonRequest extends HttpFoundation\Request
{
    public static function createFromGlobals()
    {
        // Call the parent method to generate a standard request object and
        // populate with data.
        $request = parent::createFromGlobals();

        // Grab whatever we were sent; hopefully it's valid Json.
        $requestBody = file_get_contents('php://input');

        // Initialise an array to hold our input data.
        $input = [];

        // If we got something, decode it (making the assumption it's actually
        // json.)
        if (!empty($requestBody)) {
            $input = json_decode($requestBody, true);

            // json_decode() will return false or NULL. NULL specifically
            // means the json was invalid.
            if (false === $input || null === $input) {
                throw new Exceptions\RequestJsonInvalidException();
            }
        }

        // Inject this into the $request object which is then returned.
        $request->json = new HttpFoundation\ParameterBag($input);

        return $request;
    }
}