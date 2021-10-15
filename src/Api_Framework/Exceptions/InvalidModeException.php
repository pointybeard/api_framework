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

class InvalidModeException extends ApiFrameworkException
{
    public function __construct(\Exception $previous = null)
    {
        return parent::__construct(
            '/errors/invalid-renderer-mode',
            "Invalid Renderer Mode",
            "JSON Renderer launcher is only available on the frontend",
            HttpFoundation\Response::HTTP_INTERNAL_SERVER_ERROR, 215, $previous
        );
    }
}
