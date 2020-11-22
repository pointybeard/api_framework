<?php

declare(strict_types=1);

namespace pointybeard\Symphony\Extensions\Api_Framework\Exceptions;

use Symfony\Component\HttpFoundation;

class RequestJsonInvalidException extends ApiFrameworkException
{
    public function __construct(int $code = 0, \Exception $previous = null)
    {
        parent::__construct(HttpFoundation\Response::HTTP_BAD_REQUEST, 'Request could not be handled. Check JSON is valid.', $code, $previous);
    }
}
