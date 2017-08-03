<?php

namespace Symphony\ApiFramework\Lib;

use Symphony\ApiFramework\Lib\Interfaces;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The base endpoint class that is extended by all API methods.
 */
abstract class AbstractController implements Interfaces\ControllerInterface
{
	abstract public function execute(Request $request);

    public function render(Response $response, array $data)
    {
        $response->setData($data);
        return $response;
    }
}
