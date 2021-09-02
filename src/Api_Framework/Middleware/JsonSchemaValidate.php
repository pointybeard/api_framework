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

namespace pointybeard\Symphony\Extensions\Api_Framework\Middleware;

use Exception;
use Opis\JsonSchema;

use OpisErrorPresenter\Implementation\MessageFormatterFactory;

use OpisErrorPresenter\Implementation\PresentedValidationErrorFactory;
use OpisErrorPresenter\Implementation\Strategies;

use OpisErrorPresenter\Implementation\ValidationErrorPresenter;
use pointybeard\Helpers\Functions\Json;
use pointybeard\Symphony\Extended\Route;
use pointybeard\Symphony\Extensions\Api_Framework\Exceptions\SchemaValidationFailedException;
use pointybeard\Symphony\Extensions\Api_Framework\JsonRoute;

use stdClass;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class JsonSchemaValidate
{
    public function handle(Request $request, JsonRoute $route)
    {

        // (guard) No request schema was supplied with this route
        if (null == $route->schemaRequest) {
            return;
        }

        $this->validateWithSchema($request->json->all(), $route->schemaRequest);
    }

    public function terminate(Response $response, Route $route)
    {

        // (guard) No response schema was supplied with this route
        if (null == $route->schemaResponse) {
            return;
        }

        $this->validateWithSchema($response->getContent(), $route->schemaResponse);
    }

    private function validateWithSchema($data, string $schemaPathname): stdClass
    {
        if (false == is_readable($schemaPathname)) {
            throw new \Exception("Schema {$schemaPathname} does not exist or is not readable.");
        } elseif (false == Json\json_validate_file($schemaPathname)) {
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

        $result = (new JsonSchema\Validator())->schemaValidation(
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
                    (null != $pointer ? "{$pointer}: " : ''),
                    $message
                );
            }

            // Now throw up an exception along with the processed errors
            throw new SchemaValidationFailedException($errors, $schemaPathname, $data);
        }

        return $data;
    }
}
