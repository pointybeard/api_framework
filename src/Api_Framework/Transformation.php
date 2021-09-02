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

class Transformation
{
    private $test;

    private $action;

    public function __construct(callable $test, callable $action)
    {
        $this->test = $test;
        $this->action = $action;
    }

    public function __call($name, $args)
    {
        if (!isset($this->$name) || !is_callable($this->$name)) {
            throw new \Exception(__CLASS__." has no callable member '{$name}'.");
        }

        return call_user_func_array($this->$name, $args);
    }
}
