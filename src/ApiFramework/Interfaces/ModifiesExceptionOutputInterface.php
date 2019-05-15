<?php declare(strict_types=1);

namespace Symphony\ApiFramework\ApiFramework\Interfaces;

interface ModifiesExceptionOutputInterface
{
    public function modifyOutput(array $output) : array;
}
