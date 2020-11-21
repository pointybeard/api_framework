<?php

declare(strict_types=1);

namespace pointybeard\Symphony\Extensions\Api_Framework\Exceptions;

use pointybeard\Symphony\Extensions\Api_Framework;
use Symfony\Component\HttpFoundation\Response;

class MatchingRouteNotFound extends Api_Framework\AbstractApiException
{
    public function __construct(int $code = 0, \Exception $previous = null)
    {
        parent::__construct(Response::HTTP_NOT_FOUND, "No route found for request", $code, $previous);
    }
}
