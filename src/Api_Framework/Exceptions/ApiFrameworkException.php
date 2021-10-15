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

use pointybeard\Symphony\Extensions\Api_Framework\Interfaces\Rfc7807ExceptionInterface;
use pointybeard\Symphony\Extensions\Api_Framework\Interfaces\ModifiesExceptionOutputInterface;
use pointybeard\Helpers\Exceptions\ReadableTrace\ReadableTraceException;
use Symfony\Component\HttpFoundation\Response;
use pointybeard\Symphony\Extended;
use Exception;

class ApiFrameworkException extends ReadableTraceException implements Rfc7807ExceptionInterface, ModifiesExceptionOutputInterface
{
    private $type;
    private $title;
    private $status;
    private $detail;
    private $instance;

    public function getHttpStatusCode(): int
    {
        return $this->status;
    }

    public function __construct(string $type, string $title, string $detail, int $status = Response::HTTP_INTERNAL_SERVER_ERROR, int $code = 0, Exception $previous = null)
    {
        $this->type = $type;
        $this->title = $title;
        $this->status = $status;
        $this->detail = $detail;
        $this->instance = $_SERVER['REQUEST_URI'];

        parent::__construct($title, $code, $previous);
    }

    public function modifyOutput(array $output): array
    {
        $output['error']['type'] = $this->getType();
        $output['error']['title'] = $this->getTitle();
        $output['error']['status'] = $this->getHttpStatusCode();
        $output['error']['detail'] = $this->getDetail();
        $output['error']['instance'] = $this->getInstance();
        return $output;
    }

    public function getType(): string
    {
        return $this->type;
    }
    public function getTitle(): string
    {
        return $this->title;
    }
    public function getStatus(): ?int
    {
        return $this->status;
    }
    public function getDetail(): string
    {
        return $this->detail;
    }
    public function getInstance(): string
    {
        return $this->instance;
    }
}
