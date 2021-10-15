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

namespace pointybeard\Symphony\Extensions\Api_Framework\Exceptions;

use Symfony\Component\HttpFoundation;

class RequestJsonInvalidException extends ApiFrameworkException
{
    public function __construct(\Exception $previous = null)
    {
        return parent::__construct(
            '/errors/invalid-contents-json',
            "Invalid JSON Contents",
            "Request could not be handled as the JSON contents is invalid.",
            HttpFoundation\Response::HTTP_UNPROCESSABLE_ENTITY, 215, $previous
        );
    }
}
