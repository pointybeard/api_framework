<?php

declare(strict_types=1);

namespace Symphony\Extensions\ApiFramework\Interfaces;

interface ModifiesExceptionOutputInterface
{
    public function modifyOutput(array $output): array;
}
