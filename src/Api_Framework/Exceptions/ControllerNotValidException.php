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

class ControllerNotValidException extends ApiFrameworkException
{
    public function __construct(string $controller, \Exception $previous = null)
    {
        return parent::__construct(
            '/errors/controller-not-valid',
            'Controller Not Valid',
            "Controller '{$controller}' is not a valid instance of pointybeard\\Symphony\\Extensions\\Api_Framework\\AbstractController.",
            HttpFoundation\Response::HTTP_INTERNAL_SERVER_ERROR, 215, $previous
        );
    }
}
