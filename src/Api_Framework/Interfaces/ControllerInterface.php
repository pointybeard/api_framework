<?php

declare(strict_types=1);

namespace pointybeard\Symphony\Extensions\Api_Framework\Interfaces;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface ControllerInterface
{
    public function execute(Request $request): void;
    public function render(Response $response, ?array $data = null): Response;

    //@todo define status and error codes
}
