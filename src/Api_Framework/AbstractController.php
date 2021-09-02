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

/**
 * The base endpoint class that is extended by all API methods.
 */
abstract class AbstractController implements Interfaces\ControllerInterface
{
    public function respondsToRequestMethod(string $method): bool
    {
        return (bool) method_exists($this, strtolower($method));
    }

    public function throwMethodNotAllowedExceptionOnGet(): bool
    {
        return false;
    }
}
