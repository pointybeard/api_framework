<?php

namespace Symphony\ApiFramework\Lib\Exceptions;

use Symphony\ApiFramework\Lib;
use Symphony\ApiFramework\Lib\Interfaces;
use Symfony\Component\HttpFoundation\Response;

/**
 * @thrownby Symphony\ApiFramework\Lib\Traits\hasEndpointSchemaTrait
 */
class SchemaValidationFailedException extends Lib\AbstractApiException implements Interfaces\ModifiesExceptionOutputInterface
{

    /**
     * Holds errors that are passed in through the constructor
     * @var array
     */
    private $schemaErrors = [];

    /**
     * Provided by the caller. This is the path to the schema that was used
     * when the validation failed.
     * @var string
     */
    private $schemaPath = null;

    /**
     * Data used in the valid validation attempt.
     * @var mixed
     */
    private $dataProvided = null;

    public function __construct($schemaErrors, $schemaPath, $dataProvided, $code = 0, Exception $previous = null)
    {
        $this->schemaErrors = $schemaErrors;
        $this->schemaPath = $schemaPath;
        $this->dataProvided = $dataProvided;

        parent::__construct(
            Response::HTTP_BAD_REQUEST,
            "Validation failed. Errors where encountered while validating data against the supplied schema.",
            $code,
            $previous
        );
    }

    /**
     * This exception needs to inject the schema errors into the final
     * API error output
     */
    public function modifyOutput(array $output)
    {
        // We want to see the schema validation errors in the output
        $output['error'] = $this->schemaErrors;
        $output['validation'] = [
            'schema' => str_replace(
                realpath(WORKSPACE) . DIRECTORY_SEPARATOR,
                "",
                $this->schemaPath
            ),
            'input' => $this->dataProvided
        ];
        return $output;
    }
}
