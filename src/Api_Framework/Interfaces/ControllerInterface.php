<?php

declare(strict_types=1);

namespace pointybeard\Symphony\Extensions\Api_Framework\Interfaces;

use Symfony\Component\HttpFoundation\Request;

interface ControllerInterface
{
    public function execute(Request $request): void;

    //@todo define status and error codes
}
