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
     * Given a request object, this will return any JSON schema for the endpoint
     * @param  Request $request A Symfony HTTP request object
     * @return array           An array containing request and response schemas
     *                           path if they exist.
     */
    public function getSchema(Request $request) {

        // Remove the common namepsace
        $path = str_replace("Symphony\\ApiFramework\\Controllers\\", "", __CLASS__);

        // Split by slashes
        $schema = explode("\\", $path);

        // Ditch the 'controller' part in the last item
        $schema[count($schema) - 1] = preg_replace("@^Controller@i", "", end($schema));

        // Concatinate with underscores
        $schema = implode("_", $schema);

        // Join the HTTP method to the concatinated schema name
        $schema = strtolower(sprintf("%s.%s", $schema, $request->getMethod()));

        // All schemas live in WORKSPACE
        $schemaDirectory = WORKSPACE . DIRECTORY_SEPARATOR . "schema" . DIRECTORY_SEPARATOR;

        // Format is path.method.(request|response).json
        // Generate potential paths for both a request and response
        $requestSchemaPath =  $schemaDirectory . $schema . ".request.json";
        $responseSchemaPath = $schemaDirectory . $schema . ".response.json";

        $result = [
            "request" => null,
            "response" => null
        ];

        // Check to see if the request schema exists. If so, add the path into
        // the return array.
        if(file_exists($requestSchemaPath) || is_readable($requestSchemaPath)) {
            $result['request'] = $requestSchemaPath;
        }

        // Check to see if the request schema exists. If so, add the path into
        // the return array.
        if(file_exists($responseSchemaPath) || is_readable($responseSchemaPath)) {
            $result['response'] = $responseSchemaPath;
        }

        return $result;
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
    public function validate($data, $schemaPath = null) {

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
        if($schemaPath != null) {

            // Set up the JsonSchema validation objects
            $refResolver = new JsonSchema\RefResolver(new JsonSchema\Uri\UriRetriever(), new JsonSchema\Uri\UriResolver());
            $schema = $refResolver->resolve("file://{$schemaPath}");

            // Validate
            $validator = new JsonSchema\Validator();
            $validator->check($data, $schema);

            // The result was not valid, but we need to dig a little deeper to
            // see what the problem might be.
            if ($validator->isValid() === false) {
                $errors = [];

                foreach ($validator->getErrors() as $error) {
                    $errors[] = sprintf("[%s] %s", $error['property'], $error['message']);
                }

                // Now throw up an exception along with the processed errors
                throw new Lib\Exceptions\SchemaValidationFailedException($errors, $schemaPath, $data);
            }
        }

        return $data;

    }
}
