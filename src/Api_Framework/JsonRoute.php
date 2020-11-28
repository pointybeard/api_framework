<?php

declare(strict_types=1);

namespace pointybeard\Symphony\Extensions\Api_Framework;

use Opis\JsonSchema;
use pointybeard\Symphony\Extended;
use pointybeard\Helpers\Functions\Json;
use Symfony\Component\HttpFoundation;

use OpisErrorPresenter\Contracts\PresentedValidationError;
use OpisErrorPresenter\Implementation\MessageFormatterFactory;
use OpisErrorPresenter\Implementation\PresentedValidationErrorFactory;
use OpisErrorPresenter\Implementation\ValidationErrorPresenter;
use OpisErrorPresenter\Implementation\Strategies;

class JsonRoute extends Extended\Route
{
    protected $schemaRequest = null;
    protected $schemaResponse = null;

    public function schemaRequest(string $value): self
    {
        if(false == is_readable($value)) {
            throw new \Exception("Schema {$value} does not exist or is not readable.");

        } elseif(false == Json\json_validate_file($value)) {
            throw new \Exception("Schema {$value} is not valid JSON.");
        }

        $this->schemaRequest = $value;
        return $this;
    }

    public function schemaResponse(string $value): self
    {
        if(false == is_readable($value)) {
            throw new \Exception("Schema {$value} does not exist or is not readable.");

        } elseif(false == Json\json_validate_file($value)) {
            throw new \Exception("Schema {$value} is not valid JSON.");
        }

        $this->schemaResponse = $value;
        return $this;
    }

    public function canValidateRequest(): bool
    {
        return null !== $this->schemaRequest;
    }

    public function canValidateResponse(): bool
    {
        return null !== $this->schemaResponse;
    }

    public function validateRequest(HttpFoundation\Request $request): \stdClass
    {
        return $this->validateWithSchema($request->json->all(), $this->schemaRequest);
    }

    public function validateResponse(HttpFoundation\Response $response): \stdClass
    {
        return $this->validateWithSchema($response->getContent(), $this->schemaResponse);
    }

    protected function validateWithSchema($data, string $schemaPathname): \stdClass
    {
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
            throw new Exceptions\SchemaValidationFailedException(
                $errors,
                $schemaPathname,
                $data
            );
        }
        

        return $data;
    }
}
