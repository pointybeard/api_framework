<?php

declare(strict_types=1);

/*
 * This file is part of the "RESTful API Framework Extension for Symphony CMS" repository.
 *
 * Copyright 2017-2021 Alannah Kearney <hi@alannahkearney.com>
 *
 * For the full copyright and license information, please view the LICENCE
 * file that was distributed with this source code.
 */

namespace pointybeard\Symphony\Extensions\Api_Framework;

use Extension_API_Framework;
use pointybeard\Symphony\Extended;
use pointybeard\Symphony\Extended\Router;

use Symfony\Component\HttpFoundation;
use Symphony;
use Exception;
use TypeError;

/**
 * This extends the core Symphony class to give us a vector to
 * overload various functionality. There is a certain amount of code
 * duplication, taken from Frontend, since $_page is a private variable
 * and thus we cannot extend the Frontend class and inherit it's core features.
 */
class JsonFrontend extends Symphony
{
    // JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
    public const DEFAULT_ENCODING_OPTIONS = 207;

    protected $encodingOptions = self::DEFAULT_ENCODING_OPTIONS;

    /**
     * An instance of the Api_Framework\JsonFrontendPage class.
     *
     * @var JsonFrontendPage
     */
    protected static $_page;

    /**
     * Code duplication from core Frontend class.
     */
    protected function __construct()
    {
        parent::__construct();

        // When the parent construtor is called, the GenericExceptionHandler is
        // turned off, which is fine normally unless you want exceptions to
        // bubble up in a JSON api. Lets turn it back on.
        \GenericExceptionHandler::$enabled = true;

        $this->_env = [];
    }

    /**
     * Code duplication from core Frontend class, however it returns an
     * instance of HttpFoundation\Response rather than FrontendPage.
     */
    public function display(HttpFoundation\Request $request)
    {
        $routes = new Router();
        $container = Extended\ServiceContainer::getInstance();

        // Load routes
        $loader = include WORKSPACE.'/routes.php';
        $loader($routes);

        self::ExtensionManager()->notifyMembers(
            'ModifyRoutes',
            '/backend/',
            ['routes' => &$routes]
        );

        // Check to see if we have global OPTIONS route enabled
        if (true == Extension_API_Framework::isGlobalOptionsRouteEnabled()) {
            $routes
                ->add(
                    (new Extended\Route())
                        ->url('/{anything}')
                        ->controller([GlobalOptionsController::class, 'options'])
                        ->methods(Extended\Route::METHOD_OPTIONS)
                        ->validate(['anything' => '.*'])
                )
            ;
        }

        // Check to see if we have default routes enabled
        if (false == Extension_API_Framework::isDefaultRoutesDisabled()) {
            $routes->buildDefaultRoutes();
        }

        try {
            $route = $routes->find($request);
        } catch (Extended\Exceptions\MethodNotAllowedException $ex) {
            throw new Exceptions\MethodNotAllowedException($ex->getMessage(), $ex);
        } catch (Extended\Exceptions\MatchingRouteNotFound $ex) {
            throw new Exceptions\MatchingRouteNotFoundException($ex->getMessage(), $ex);
        }

        // Register the route with service container
        $container->register('route', $route);

        // GET Requests on pages that are of type 'cacheable' can be cached.
        $isCacheable =
        (
            true == Extension_API_Framework::isCacheEnabled()
            && HttpFoundation\Request::METHOD_GET == $request->getMethod()
            && true == is_array($route->page()->type)
            && true == in_array(JsonFrontendPage::PAGE_TYPE_CACHEABLE, $route->page()->type)
        );

        self::$_page =
        (
            true == $isCacheable
                ? new CacheableJsonFrontendPage()
                : new JsonFrontendPage()
        );

        $container->register('request', $request);
        $container->register('response', new HttpFoundation\JsonResponse(), true);
        $container->register('page', self::$_page);
        $container->register('frontend', JsonFrontend::instance());

        // Run middleware now
        $response = $route->runMiddleware();

        // Check if "Disable Cache Cleanup" has been set. If not, go ahead and
        // delete all expired cache entries. This can be disabled in the
        // preferences.
        if (true == $isCacheable && Extension_API_Framework::isCacheCleanupEnabled()) {
            $response->headers->set('X-API-Framework-Expired-Cache-Entries', Models\PageCache::deleteExpired());
        }

        $isCacheHit = (true == $isCacheable && true == (self::$_page->isCacheHit($request) instanceof Models\PageCache));

        self::ExtensionManager()->notifyMembers('FrontendInitialised', '/frontend/');

        // Get the controller
        [$controllerClass, $controllerMethod] = $route->controller();

        // There are a couple of pathways here:
        // 1. There is no controller or there is a hit on the cache so it should
        //      just pass on to the normal page rendering process
        if (null == $route->controller() || true == $isCacheHit) {
            $response = self::$_page->render($request, $response);

        // 2c. There is a page controller, all is valid, and it responds to this method. Yay!!
        } else {

            // Register the controller so we can let the service container deal with dependencies
            $container->register($controllerClass, "{$controllerClass}::{$controllerMethod}", false, $route->parse($request)->elements);

            // Invoke the controller. Service Container will do the auto-wiring for us
            $response = $container->get($controllerClass);

        }

        // Response will have been modified so we need to register the updated version with the service container
        // before we call the terminatation middleware
        $container->register('response', $response);

        // Run termination middleware
        if (true == $route->hasMiddleware()) {
            foreach ($route->middleware() as $m) {
                try {
                    $container->get("{$m}_terminate");
                } catch (Extended\Exceptions\ServiceContainerEntryNotFoundException $ex) {
                    // No terminate method. Keep going.
                }
            }
        }

        // Save to cache if this is a cachable page type and response code is 200 OK
        if (true == $isCacheable && $container->get('response')->getStatusCode() == HttpFoundation\Response::HTTP_OK && false === $isCacheHit) {
            self::$_page->saveToCache($request, $container->get('response'));
        }

        return $container->get('response');
    }

    /**
     * Code duplication from core Frontend class, however it returns an
     * instance of self rather than hard coding the class name.
     */
    public static function instance(): self
    {
        if (!(self::$_instance instanceof self)) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * Code duplication from core Frontend class.
     */
    public static function Page()
    {
        return self::$_page;
    }

    /**
     * Code duplication from core Frontend class.
     */
    public static function isLoggedIn(): bool
    {
        if (isset($_REQUEST['auth-token']) && $_REQUEST['auth-token'] && 8 == strlen($_REQUEST['auth-token'])) {
            return self::loginFromToken($_REQUEST['auth-token']);
        }

        return Symphony::isLoggedIn();
    }

    public function getEncodingOptions(): int
    {
        return $this->encodingOptions;
    }

    public function setEncodingOptions(int $options): void
    {
        $this->encodingOptions = $options;
    }
}
