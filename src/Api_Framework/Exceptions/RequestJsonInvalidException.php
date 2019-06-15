<?php

declare(strict_types=1);

namespace pointybeard\Symphony\Extensions\Api_Framework\Exceptions;

use pointybeard\Symphony\Extensions\Api_Framework;
use Symfony\Component\HttpFoundation\Response;

class RequestJsonInvalidException extends ApiFramework\AbstractApiException
{
    public function __construct(int $code = 0, Exception $previous = null)
    {
        parent::__construct(Response::HTTP_BAD_REQUEST, 'Request could not be handled. Check JSON is valid.', $code, $previous);
    }
}