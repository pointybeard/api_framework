<?php

namespace Symphony\ApiFramework\Lib\Interfaces;

interface JsonSchemaValidationInterface
{
    public function schemas($method);
    public function validate($data, $schema = null);
}
