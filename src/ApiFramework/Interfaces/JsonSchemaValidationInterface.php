<?php declare(strict_types=1);

namespace Symphony\ApiFramework\ApiFramework\Interfaces;

interface JsonSchemaValidationInterface
{
    public function schemas(string $method) : \stdClass;
    public function validate($data, string $schema = null) : \stdClass;
}
