<?php

declare(strict_types=1);

namespace pointybeard\Symphony\Extensions\Api_Framework\Interfaces;

interface ModifiesExceptionOutputInterface
{
    public function modifyOutput(array $output): array;
}
