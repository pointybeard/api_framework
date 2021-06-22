<?php

declare(strict_types=1);

namespace pointybeard\Symphony\Extensions\Api_Framework\Middleware;

use pointybeard\Symphony\Extensions\Api_Framework\JsonRoute;
use pointybeard\Symphony\Extensions\Api_Framework\Models;
use pointybeard\Symphony\Extensions\Api_Framework\Exceptions\SchemaValidationFailedException;

use pointybeard\Symphony\Extended;
use pointybeard\Symphony\Extended\Route;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Opis\JsonSchema;
use OpisErrorPresenter\Contracts\PresentedValidationError;
use OpisErrorPresenter\Implementation\MessageFormatterFactory;
use OpisErrorPresenter\Implementation\PresentedValidationErrorFactory;
use OpisErrorPresenter\Implementation\ValidationErrorPresenter;
use OpisErrorPresenter\Implementation\Strategies;

use pointybeard\Helpers\Functions\Json;

use Closure;
use stdClass;
use Exception;

final class JsonSchemaValidate
{
    public function handle(Request $request, JsonRoute $route) {

        // (guard) No request schema was supplied with this route
        if(null == $route->schemaRequest) {
            return;
        }
        
        $this->validateWithSchema($request->json->all(), $route->schemaRequest);

    }

    public function terminate(Response $response, Route $route) {

        // (guard) No response schema was supplied with this route
        if(null == $route->schemaResponse) {
            return;
        }
    
        $this->validateWithSchema($response->getContent(), $route->schemaResponse);

    }

    protected function validateWithSchema($data, string $schemaPathname): \stdClass
    {

        if(false == is_readable($schemaPathname)) {
            throw new \Exception("Schema {$schemaPathname} does not exist or is not readable.");

        } elseif(false == Json\json_validate_file($schemaPathname)) {
            throw new \Exception("Schema {$schemaPathname} is not valid JSON.");
        }

        // We need to convert the entire data array into an object. Quick way
        // is to convert to json and back again.
        $data = json_decode(
            true == is_array($data)
                ? json_encode($data)    // $data is an array
                : $data                 // We have a JSON string
        );

        // Handle a situation with $data is empty. We still need an object.
        if (true == empty($data)) {
            $data = (object) $data;
        }

        $result = (new JsonSchema\Validator)->schemaValidation(
            $data, 
            JsonSchema\Schema::fromJsonString(file_get_contents($schemaPathname)),
            -1
        );

        // The result was not valid, but we need to dig a little deeper to
        // see what the problem might be.
        if (true == $result->hasErrors()) {

            $presenter = new ValidationErrorPresenter(
                new PresentedValidationErrorFactory(
                    new MessageFormatterFactory(
                        new Strategies\FirstError()
                    )
                )
            );

            $presented = $presenter->present(...$result->getErrors());
            $errors = [];

            foreach ($presented as $error) {
                [$keyword, $pointer, $message] = array_values($error->toArray());
                $errors[] = sprintf(
                    '[%s] %s%s',
                    $keyword,
                    (null != $pointer ? "{$pointer}: " : ""),
                    $message
                );
            }

            // Now throw up an exception along with the processed errors
            throw new SchemaValidationFailedException(
                $errors,
                $schemaPathname,
                $data
            );
        }

        return $data;
    }
}