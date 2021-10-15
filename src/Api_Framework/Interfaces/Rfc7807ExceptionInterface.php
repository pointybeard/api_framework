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

namespace pointybeard\Symphony\Extensions\Api_Framework\Interfaces;

interface Rfc7807ExceptionInterface
{
    public function getType(): string;
    public function getTitle(): string;
    public function getStatus(): ?int;
    public function getDetail(): string;
    public function getInstance(): string;
}
