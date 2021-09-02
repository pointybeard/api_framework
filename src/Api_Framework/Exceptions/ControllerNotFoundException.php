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

class ControllerNotFoundException extends ApiFrameworkException
{
    public function __construct(string $controller, int $code = 0, \Exception $previous = null)
    {
        parent::__construct(HttpFoundation\Response::HTTP_NOT_FOUND, "Controller '{$controller}' could not be located.", $code, $previous);
    }
}
