<?php

declare(strict_types=1);

namespace pointybeard\Symphony\Extensions\Api_Framework;

use Symphony;
use pointybeard\Symphony\Extended;
use pointybeard\Symphony\Extended\Router;
use Symfony\Component\HttpFoundation;

/**
 * This extends the core Symphony class to give us a vector to
 * overload various functionality. There is a certain amount of code
 * duplication, taken from Frontend, since $_page is a private variable
 * and thus we cannot extend the Frontend class and inherit it's core features.
 */
class JsonFrontend extends Symphony
{
    // JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
    const DEFAULT_ENCODING_OPTIONS = 207;

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

        $routes = new Router;

        // Load routes
        $loader = include WORKSPACE . "/routes.php";
        $loader($routes);

        self::ExtensionManager()->notifyMembers(
            'ModifyRoutes',
            '/backend/',
            ['routes' => &$routes]
        );

        // Check to see if we have global OPTIONS route enabled
        if(true == \Extension_API_Framework::isGlobalOptionsRouteEnabled()) {
            $routes
                ->add(
                    (new Extended\Route)
                        ->url("/{anything}")
                        ->controller([GlobalOptionsController::class, "options"])
                        ->methods(Extended\Route::METHOD_OPTIONS)
                        ->validate(['anything' => '.*'])
                )
            ;
        }

        // Check to see if we have default routes enabled
        if(false == \Extension_API_Framework::isDefaultRoutesDisabled()) {
            $routes->buildDefaultRoutes();
        }

        try {
            $route = $routes->find($request);

        } catch(Extended\Exceptions\MethodNotAllowedException $ex) {
            throw new Exceptions\ApiFrameworkException(HttpFoundation\Response::HTTP_METHOD_NOT_ALLOWED, $ex->getMessage(), 0, $ex);

        } catch(Extended\Exceptions\MatchingRouteNotFound $ex) {
            throw new Exceptions\ApiFrameworkException(HttpFoundation\Response::HTTP_NOT_FOUND, $ex->getMessage(), 0, $ex);
        }

        // GET Requests on pages that are of type 'cacheable' can be cached.
        $isCacheable =
        (
            true == \Extension_API_Framework::isCacheEnabled()
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

        // Prepare the response.
        $response = new HttpFoundation\JsonResponse();
        $response->headers->set('Content-Type', 'application/json');
        $response->headers->set('X-API-Framework-Page-Renderer',  array_pop(explode('\\', get_class(self::$_page))));
        $response->setEncodingOptions(JsonFrontend::instance()->getEncodingOptions());

        // Check if "Disable Cache Cleanup" has been set. If not, go ahead and
        // delete all expired cache entries. This can be disabled in the
        // preferences.
        if (true == $isCacheable && \Extension_API_Framework::isCacheCleanupEnabled()) {
            $response->headers->set('X-API-Framework-Expired-Cache-Entries', Models\PageCache::deleteExpired());
        }

        $isCacheHit = (true == $isCacheable && true == (self::$_page->isCacheHit($request) instanceof Models\PageCache));

        self::ExtensionManager()->notifyMembers('FrontendInitialised', '/frontend/');

        // Get the controller
        [$controllerClass, $controllerMethod] = $route->controller();

        // There are a couple of pathways here:
        // 1. There is no controller or there is a hit on the cache so it should
        //      just pass on to the normal page rendering process
        if(null == $route->controller() || true == $isCacheHit) {
            $response = self::$_page->render($request, $response);

        // 2a. There is a page controller specified
        //      but it or the method does not exist
        } elseif(false == class_exists($controllerClass) || false == method_exists($controllerClass, $controllerMethod)) {
            throw new Exceptions\ControllerNotFoundException("{$controllerClass}::{$controllerMethod}");

        // 2b. There is a page controller
        //      but it does not implement Api_Framework\AbstractController
        } elseif(false == (new \ReflectionClass($controllerClass))->implementsInterface(__NAMESPACE__ . '\\Interfaces\\ControllerInterface')) {
            throw new Exceptions\ControllerNotValidException("Controller {$controllerClass} does not implement ControllerInterface");

        // 2c. There is a page controller, all is valid, and it responds to this method. Yay!!
        } else {

            $controller = new $controllerClass;

            // Validate output if a request schema was provided
            if(true == ($route instanceof JsonRoute) && $route->canValidateRequest()) {
                $route->validateRequest($request);
            }

            $response = call_user_func_array(
                [$controller, $controllerMethod], 
                array_merge([$request, $response], $route->parse($request)->elements)
            );
        }

        // Validate output if a response schema was provided
        if(true == ($route instanceof JsonRoute) && $route->canValidateResponse()) {
            $route->validateResponse($response);
        }

        // Save to cache if this is a cachable page type
        if(true == $isCacheable && false === $isCacheHit) {
            self::$_page->saveToCache($request, $response);
        }

        return $response;

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
