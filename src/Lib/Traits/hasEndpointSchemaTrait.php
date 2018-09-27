<?php

namespace Symphony\ApiFramework\Lib\Traits;

use JsonSchema;
use Symfony\Component\HttpFoundation\Request;
use Symphony\ApiFramework\Lib;

/**
 * This trait will help resolve schema for a particular endpoint.
 */
trait hasEndpointSchemaTrait
{
    /**
     * This will return any JSON schemas for the endpoint
     * @return array           An array containing request and response schemas
     *                           path if they exist.
     */
    public function schemas($method) {

        // Remove the common namepsace
        $path = str_replace("Symphony\\ApiFramework\\Controllers\\", "", __CLASS__);

        // Change back slashes to the system directory separator
        $schema = str_replace("\\", DIRECTORY_SEPARATOR, $path);

        // Join the HTTP method and workspace folder to the schema path
        $schema = sprintf(
            "%s%sschemas%s%s.%s",
            realpath(WORKSPACE), DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR,
            $schema,
            strtolower($method)
        );

        // Format is [controller-name].[http-method].[request|response].json
        // Generate potential paths for both a request and response
        $requestSchemaPath =  $schema . ".request.json";
        $responseSchemaPath = $schema . ".response.json";

        $result = (object)[
            "request" => null,
            "response" => null
        ];

        // Check to see if the schemas exists.
        if(is_readable($requestSchemaPath)) {
            $result->request = $requestSchemaPath;
        }

        if(is_readable($responseSchemaPath)) {
            $result->response = $responseSchemaPath;
        }

        return (object)$result;
    }

    /**
     * Provided with an array of data and a schema, this will validate it and
     * throw an exception if said validation failes
     * @param  mixed  $data   The data to validate against. This is expected to
     *                        be either a JSON formatted string, or an array.
     * @param  string  $schema Optional. Path to the JSON schema
     * @return object         An stdClass object representation of the inputted
     *                           data
     *
     * @throws Lib\Exceptions\SchemaValidationFailedException
     *
     * @usedby eventController
     */
    public function validate($data, $schema = null) {

        // We need to convert the entire data array into an object. Quick way
        // is to convert to json and back again.
        $data = json_decode(
            is_array($data)
                ? json_encode($data)    // $data is an array
                : $data                 // We have a JSON string
        );

        // Handle a situation with $data is empty. We still need an object.
        if(empty($data)) {
            $data = (object)$data;
        }

        // We only need to validate if a schema was supplied
        if($schema != null) {

            // Validate
            $validator = new JsonSchema\Validator;
            $validator->validate(
                $data,
                (object)['$ref' => 'file://' . realpath($schema)]
            );

            // The result was not valid, but we need to dig a little deeper to
            // see what the problem might be.
            if ($validator->isValid() !== true) {
                $errors = [];

                foreach ($validator->getErrors() as $error) {
                    $errors[] = sprintf("[%s] %s", $error['property'], $error['message']);
                }

                // Now throw up an exception along with the processed errors
                throw new Lib\Exceptions\SchemaValidationFailedException($errors, $schema, $data);
            }
        }

        return $data;

    }
}
