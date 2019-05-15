<?php declare(strict_types=1);

namespace Symphony\ApiFramework\ApiFramework;

use Symphony\ApiFramework\ApiFramework\Interfaces;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The base endpoint class that is extended by all API methods.
 */
abstract class AbstractController implements Interfaces\ControllerInterface
{
    public function render(Response $response, array $data) : Response
    {
        $response->setData($data);
        return $response;
    }
}
