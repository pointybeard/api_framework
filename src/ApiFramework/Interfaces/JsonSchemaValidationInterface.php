<?php

declare(strict_types=1);

namespace pointybeard\Symphony\Extensions\Api_Framework\Interfaces;

interface JsonSchemaValidationInterface
{
    public function schemas(string $method): \stdClass;

    public function validate($data, string $schema = null): \stdClass;
}
