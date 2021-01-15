<?php

declare(strict_types=1);

namespace pointybeard\Symphony\Extensions\Api_Framework\Interfaces;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface ControllerInterface
{
    //@todo define status and error codes

    public function initialise(Request $request): void;
}
