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

use pointybeard\Symphony\Extensions\Api_Framework\Interfaces;
use Symfony\Component\HttpFoundation;

/**
 * @thrownby pointybeard\Symphony\Extensions\Api_Framework\Traits\hasEndpointSchemaTrait
 */
class SchemaValidationFailedException extends ApiFrameworkException implements Interfaces\ModifiesExceptionOutputInterface
{
    /**
     * Holds errors that are passed in through the constructor.
     *
     * @var array
     */
    private $schemaErrors = [];

    /**
     * Provided by the caller. This is the path to the schema that was used
     * when the validation failed.
     *
     * @var string
     */
    private $schemaPath = null;

    /**
     * Data used in the valid validation attempt.
     *
     * @var mixed
     */
    private $dataProvided = null;

    public function __construct(array $schemaErrors, string $schemaPath, $dataProvided, \Exception $previous = null)
    {
        $this->schemaErrors = $schemaErrors;
        $this->schemaPath = $schemaPath;
        $this->dataProvided = $dataProvided;

        return parent::__construct(
            '/errors/schema-validation-failed',
            'Schema Validation Failed',
            'Errors were encountered while attempting to validate data against the supplied schema.',
            HttpFoundation\Response::HTTP_UNPROCESSABLE_ENTITY, 215, $previous
        );
    }

    /**
     * This exception needs to inject the schema errors into the final
     * API error output.
     */
    public function modifyOutput(array $output): array
    {
        $output = parent::modifyOutput($output);

        // We want to see the schema validation errors in the output
        $output['error']['errors'] = $this->schemaErrors;
        $output['error']['schema'] = str_replace(realpath(WORKSPACE).DIRECTORY_SEPARATOR, '', $this->schemaPath);
        $output['data'] = $this->dataProvided;

        return $output;
    }
}
