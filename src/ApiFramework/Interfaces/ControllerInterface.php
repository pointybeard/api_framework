<?php declare(strict_types=1);

namespace Symphony\ApiFramework\ApiFramework\Interfaces;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface ControllerInterface
{
    public function execute(Request $request) : void;
    //@todo define status and error codes
}
