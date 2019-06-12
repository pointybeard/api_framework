<?php

declare(strict_types=1);

namespace Symphony\Extensions\ApiFramework\Interfaces;

use Symfony\Component\HttpFoundation\Request;

interface ControllerInterface
{
    public function execute(Request $request): void;

    //@todo define status and error codes
}
