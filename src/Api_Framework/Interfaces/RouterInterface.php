<?php

declare(strict_types=1);

namespace pointybeard\Symphony\Extensions\Api_Framework\Interfaces;

use pointybeard\Symphony\Extensions\Api_Framework;
use Symfony\Component\HttpFoundation;

interface RouterInterface {
    public function find(HttpFoundation\Request $request): Api_Framework\Route;
    public function add(Api_Framework\Route $route): self;
}
