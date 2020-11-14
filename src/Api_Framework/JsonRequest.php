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
    public static function createFromGlobals(?int $options = 0): HttpFoundation\Request
    {
        // Call the parent method to generate a standard request object and
        // populate with data.
        $request = parent::createFromGlobals();

        // Grab whatever we were sent; hopefully it's valid Json.
        $requestBody = trim((string)file_get_contents('php://input'));

        // Initialise an array to hold our input data.
        $input = [];

        // If we got something, decode it (making the assumption it's actually
        // json.)
        if (false == empty($requestBody)) {
            try {
                $input = json_decode($requestBody, true, 512, JSON_THROW_ON_ERROR | $options);
            } catch (\JsonException $ex) {
                throw new Exceptions\RequestJsonInvalidException(0, $ex);
            }
        }

        // Inject this into the $request object which is then returned.
        $request->json = new HttpFoundation\ParameterBag($input);

        return $request;
    }
}
