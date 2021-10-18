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
use stdClass;
use Opis\JsonSchema\Validator;
use Opis\JsonSchema\Helper;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Errors\ValidationError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use pointybeard\Symphony\Extended\Route;
use pointybeard\Symphony\Extensions\Api_Framework\JsonRoute;
use pointybeard\Symphony\Extensions\Api_Framework\Exceptions\SchemaValidationFailedException;

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

    private function validateWithSchema($data, string $schema): void
    {
        // (guard) the schema is not readable
        if (false == is_readable($schema)) {
            throw new Exception("Schema {$schema} does not exist or is not readable.");
        }

        // validate data against provided schema
        $result = (new Validator())
            ->setMaxErrors(5)
            ->validate(
                (
                    false == empty($data)
                        ? Helper::toJSON($data)
                        : (object)[]
                ),
                file_get_contents($schema)
            )
        ;

        // (guard) data failed validation
        if (false == $result->isValid()) {
            $formatter = new ErrorFormatter();
            throw new SchemaValidationFailedException(
                array_values($formatter->format(
                    $result->error(),
                    false,
                    function (ValidationError $error) use ($formatter) {
                        $fullPath = $error->data()->fullPath();
                        return [
                            "path" => "/" . (false == empty($fullPath) ? implode('/', $error->data()->fullPath()) : ""),
                            "info" => $formatter->formatErrorMessage($error),
                            'more' => [
                                'keyword' => $error->keyword(),
                                'args' => $error->args(),
                                'message' => $error->message(),
                                'data' => [
                                    'type' => $error->data()->type(),
                                    'value' => $error->data()->value(),
                                ]
                            ]
                        ];
                    }
                )),
                $schema,
                $data
            );
        }
    }
}
