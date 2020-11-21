<?php

declare(strict_types=1);

namespace pointybeard\Symphony\Extensions\Api_Framework;

use pointybeard\Symphony\Extensions\Api_Framework;
use Symfony\Component\HttpFoundation;

class Router implements Interfaces\RouterInterface {

    protected $routes = [];

    public function add(Api_Framework\Route $route): self {
        $this->routes[] = $route;
        return $this;
    }

    public function find(HttpFoundation\Request $request, ?int $flags = null): Api_Framework\Route 
    {
        foreach($this->routes as $route) {
            if(true == $route->match($request, $flags)) {
                return $route;
            }
        }

        // Determine if there is no route at all, or if its just because 
        // the request method is not allowed.
        $methodsAllowed = null;
        foreach($this->routes as $route) {
            if(true == $route->match($request, $flags | Api_Framework\Route::SKIP_METHOD_MATCH)) {
                $methodsAllowed = $methodsAllowed | $route->methods();
            }
        }

        if(null !== $methodsAllowed) {
            throw new Exceptions\MethodNotAllowedException($request->getMethod());
        }

        throw new Exceptions\MatchingRouteNotFound();
    }

    // This method will use the Page entries in Symphony to build
    // a set of default routes. They are simplistic and have no
    // validation attached but fill the gap for any routes that aren't defined
    // manually
    public function buildDefaultRoutes(): self
    {

        $pages = \PageManager::fetch();

        foreach($pages as $page) {

            $page = (object)$page;

            // Ignore anything that isn't a JSON page
            if(false == is_array($page->type) || false == in_array(Api_Framework\JsonFrontendPage::PAGE_TYPE_JSON, $page->type)) {
                continue;
            }

            // See if there is already a route defined for this page
            $params = [];
            if(false == empty($page->params)) {
                $params = explode("/", trim($page->params, '/'));
                $params = array_map(function(string $item){return "{{$item}}";}, $params);
            }

            // Note: Symphony allows all, some, or no params in a request. e.g.
            // if page is /p/{a}/{b}/{c}, then /p/{a}, /p/{a}/{b}, and /p/{a}/{b}/{c}
            // are all possible. We'll need to check for and add a route for each of these
            // variations

            $variations = [
                "/" . trim(implode("/", [$page->path, $page->handle]), "/")
            ];

            $chain = [];
            foreach($params as $p) {
                $chain[] = $p;
                $variations[] = "/" . trim(implode("/", [$page->path, $page->handle, implode("/", $chain)]), "/");
            }


            foreach($variations as $url) {
                // Create a dummy request for this variation. Method doesn't matter.
                $request = HttpFoundation\Request::create($url, HttpFoundation\Request::METHOD_GET);

                $match = false;
                foreach($this->routes as $r) {
                    $match = $r->match(
                        $request,
                        Route:: SKIP_METHOD_MATCH
                        | Route::SKIP_REGEX_VALIDATION
                        | Route::TOKENIZER_USE_SYMPHONY_COMPATIBLE_URL
                    );

                    if(true == $match) {
                        break;
                    }
                }

                // A match is found, but there are some methods it doesn't handle
                // i.e. the difference of Route::METHOD_ALL and $r->methods is not
                // int(0)
                if(true == $match && Route::METHOD_ALL !== $r->methods()) {
                    $this->add(
                        (new Route)
                            ->url($request->getPathInfo())
                            ->methods(Route::METHOD_ALL ^ $r->methods())
                    ); 

                // No match was found at all
                } elseif(false == $match) {
                    $this->add(
                        (new Route)
                            ->url($request->getPathInfo())
                            ->methods(Route::METHOD_ALL)
                    );
                }
            }
        }

        return $this;
    }

    public function toArray(): array
    {
        $result = [];
        foreach($this->routes as $r) {
            $result[] = $r->toArray();
        }
        return $result;
    }

    public function toJson(): string
    {
        return (string)json_encode($this->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
    }

    public function __toString() {
        return $this->toJson();
    }
}
